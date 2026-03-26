<?php

namespace App\Http\Classes\modules\purchase;

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

class pr
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'PURCHASE REQUISITION';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false];
  public $tablenum = 'transnum';
  public $head = 'prhead';
  public $hhead = 'hprhead';
  public $stock = 'prstock';
  public $hstock = 'hprstock';
  public $tablelogs = 'transnum_log';
  public $statlogs = 'transnum_stat';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';
  private $stockselect;
  public $dqty = 'rrqty';
  public $hqty = 'qty';
  public $damt = 'rrcost';
  public $hamt = 'cost';
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
    'purtype',
    'requestor',
    'budgetreqno',
    'projectid',
    'phaseid',
    'modelid',
    'blklotid',
    'amenityid',
    'subamenityid'
  ];
  private $except = ['trno', 'dateid', 'due'];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  private $reporter;
  private $helpClass;

  public $showfilterlabel = [
    ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
    ['val' => 'forapproval', 'label' => 'For Approval', 'color' => 'primary'],
    ['val' => 'approved', 'label' => 'Approved', 'color' => 'primary'],
    ['val' => 'locked', 'label' => 'Locked', 'color' => 'red'],
    ['val' => 'posted', 'label' => 'Posted', 'color' => 'orange']
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
      'view' => 619,
      'edit' => 620,
      'new' => 621,
      'save' => 622,
      // 'change' => 623, remove change doc
      'delete' => 624,
      'print' => 625,
      'lock' => 626,
      'unlock' => 627,
      'changeamt' => 628,
      'post' => 630,
      'unpost' => 631,
      'additem' => 814,
      'edititem' => 815,
      'deleteitem' => 816
    );
    return $attrib;
  }


  public function createdoclisting($config)
  {
    // $action = 0;
    // $liststatus = 1;
    // $listdocument = 2;
    // $listdate = 3;
    // $listclientname = 4;
    // $yourref = 5;
    // $ourref = 6;
    // $postdate = 7;
    // $listpostedby = 8;
    // $listcreateby = 9;
    // $listeditby = 10;
    // $listviewby = 11;

    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'whname', 'listclientname', 'yourref', 'ourref', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];

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
    if (!$this->companysetup->linearapproval($config['params'])) {
      unset($this->showfilterlabel[1]);
      unset($this->showfilterlabel[2]);
    }
    return $cols;
  }

  public function paramsdatalisting($config)
  {
    $companyid = $config['params']['companyid'];
    switch ($companyid) {
      case 16: //ati
        $fields = ['uploadexcel'];
        $col1 = $this->fieldClass->create($fields);
        $data = [];
        return ['status' => true, 'data' => $data, 'txtfield' => ['col1' => $col1]];
        break;
      case 10: //afti
      case 12: //afti usd
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
        return ['status' => true, 'data' => [], 'txtfield' => ['col1' => []]];
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
    $company = $config['params']['companyid'];
    $limit = 'limit 150';
    $searchfield = [];
    $filtersearch = "";
    $search = $config['params']['search'];

    $join = '';
    $hjoin = '';
    $addparams = '';

    if (isset($config['params']['doclistingparam'])) {
      $test = $config['params']['doclistingparam'];
      if (isset($test['selectprefix'])) {
        if ($test['selectprefix'] != "") {
          if ($test['docno'] != '') {
            switch ($test['selectprefix']) {
              case 'Item Code':
                $addparams = " and (item.partno like '%" . $test['docno'] . "%' or item2.partno like '%" . $test['docno'] . "%')";
                break;
              case 'Item Name':
                $addparams = " and (item.itemname like '%" . $test['docno'] . "%' or item2.itemname like '%" . $test['docno'] . "%')";
                break;
              case 'Model':
                $addparams = " and (model.model_name like '%" . $test['docno'] . "%' or model2.model_name like '%" . $test['docno'] . "%')";
                break;
              case 'Brand':
                $addparams = " and (brand.brand_desc like '%" . $test['docno'] . "%' or brand2.brand_desc like '%" . $test['docno'] . "%')";
                break;
              case 'Item Group':
                $addparams = " and (p.name like '%" . $test['docno'] . "%' or p2.name like '%" . $test['docno'] . "%')";
                break;
            }
          }

          if (isset($test)) {
            $join = " left join prstock on prstock.trno = head.trno
            left join item on item.itemid = prstock.itemid left join item as item2 on item2.itemid = prstock.itemid
            left join model_masterfile as model on model.model_id = item.model 
            left join model_masterfile as model2 on model2.model_id = item2.model 
            left join frontend_ebrands as brand on brand.brandid = item.brand 
            left join frontend_ebrands as brand2 on brand2.brandid = item2.brand
            left join projectmasterfile as p on p.line = item.projectid 
            left join projectmasterfile as p2 on p2.line = item2.projectid ";

            $hjoin = " left join hprstock on hprstock.trno = head.trno
            left join item on item.itemid = hprstock.itemid left join item as item2 on item2.itemid = hprstock.itemid
            left join model_masterfile as model on model.model_id = item.model 
            left join model_masterfile as model2 on model2.model_id = item2.model
            left join frontend_ebrands as brand on brand.brandid = item.brand 
            left join frontend_ebrands as brand2 on brand2.brandid = item2.brand
            left join projectmasterfile as p on p.line = item.projectid 
            left join projectmasterfile as p2 on p2.line = item2.projectid ";
            $limit = '';
          }
        }
      }
    }


    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'head.clientname', 'head.yourref', 'head.ourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby', 'wh.clientname'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    } else {
      $limit = 'limit 25';
    }
    $status = " if(head.lockdate is not null,'LOCKED','DRAFT')  as status, ";
    if ($this->companysetup->linearapproval($config['params'])) {
      $itemfilter = isset($config['params']['doclistingparam']['typecode']) ? $config['params']['doclistingparam']['typecode'] : $itemfilter;
      $user = $config['params']['user'];
      $userid = $this->coreFunctions->getfieldvalue("useraccess", "userid", "username=?", [$user]);
      if ($userid != 0) {
        $qry = "select s.isapprover as value
                from approversetup as s
                left join approverdetails as d on d.appline=s.line
                left join useraccess as u on u.username=d.approver
                where u.userid=? and s.doc=? ";

        $isapprover = $this->coreFunctions->datareader($qry, [$userid, $doc]);
        if ($isapprover == 1) {
          $itemfilter = isset($config['params']['doclistingparam']['typecode']) ? $config['params']['doclistingparam']['typecode'] : 'forapproval';
        }
      }
      $status = " (case when head.lockdate is not null then 'LOCKED' when num.postdate is null and head.lockdate is null and num.statid=10 then 'FOR APPROVAL' 
    when num.postdate is null and num.statid=36 then 'APPROVED' when num.postdate is not null then 'POSTED' else 'DRAFT' end) as status, ";
    }
    $companyid = $config['params']['companyid'];
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $dateid = "date_format(head.dateid,'%m-%d-%Y') as dateid,head.dateid as date2 ";
        break;
      default:
        $dateid = "left(head.dateid,10) as dateid,head.dateid as date2 ";
        break;
    }

    switch ($itemfilter) {
      case 'draft':
        $condition = ' and num.postdate is null and head.lockdate is null and num.statid = 0';
        break;
      case 'forapproval':
        $condition .= " and num.postdate is null and head.lockdate is null and num.statid=10 
                        and num.appuser='" . $config['params']['user'] . "'";
        break;
      case 'approved':
        $condition .= " and num.postdate is null and head.lockdate is null and num.statid=36";
        break;
      case 'locked':
        $condition = ' and num.postdate is null and head.lockdate is not null ';
        break;
      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
    }

    $qry = "select head.trno,head.docno,head.clientname,$dateid,
    $status date(num.postdate) as postdate,
    head.createby,head.editby,head.viewby,num.postedby, 
     head.yourref, head.ourref, wh.clientname as whname
     from " . $this->head . " as head left join " . $this->tablenum . " as num on num.trno=head.trno
     left join client as wh on wh.client=head.wh
     " . $join . "
     where head.doc=? and num.center=? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $addparams . " " . $filtersearch . "
     union all
     select head.trno,head.docno,head.clientname,$dateid, 
     'POSTED' as status, date(num.postdate) as postdate,
     head.createby,head.editby,head.viewby, num.postedby, 
      head.yourref, head.ourref, wh.clientname as whname  
     from " . $this->hhead . " as head left join " . $this->tablenum . " as num on num.trno=head.trno
     left join client as wh on wh.client=head.wh
     " . $hjoin . "
     where head.doc=? and num.center=? and convert(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $addparams . " " . $filtersearch . "
     order by date2 desc,docno desc $limit";

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
      'help'
    );

    if ($this->companysetup->getclientlength($config['params']) != 0) {
      array_push($btns, 'others');
    }

    $buttons = $this->btnClass->create($btns);
    $step1 = $this->helpClass->getFields(['btnnew', 'department', 'dateid', 'cswhname', 'yourref', 'csrem', 'btnsave']);
    $step2 = $this->helpClass->getFields(['btnedit', 'department', 'dateid', 'cswhname', 'yourref', 'csrem', 'btnsave']);
    $step3 = $this->helpClass->getFields(['btnadditem', 'btnquickadd', 'rrqty', 'uom', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
    $step4 = $this->helpClass->getFields(['rrqty', 'uom', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
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
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => $config['params']['doc'], 'title' => strtoupper($this->modulename) . '_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
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
    $sq_makepo = $this->othersClass->checkAccess($config['params']['user'], 2873);
    $sq_makejo = $this->othersClass->checkAccess($config['params']['user'], 3984);
    $pr_btnvoid_access = $this->othersClass->checkAccess($config['params']['user'], 3601);
    $companyid = $config['params']['companyid'];
    $systype = $this->companysetup->getsystemtype($config['params']);

    $columns = [
      'action',
      'itemdescription',
      'rrqty',
      'rrcost',
      'uom',
      'disc',
      'netamt',
      'ext',
      'qa',
      'rem',
      'wh',
      'whname',
      'void',
      'itemname',
      'partno',
      'subcode',
      'boxcount',
      'barcode'
    ];

    $action = 0;
    $itemdesc = 1;
    $rrqty = 2;
    $rrcost = 3;
    $uom = 4;
    $disc = 5;
    $netamt = 6;
    $ext = 7;
    $qa = 8;
    $rem = 9;
    $wh = 10;
    $whname = 11;
    $void = 12;
    $itemname = 13;
    $partno = 14;
    $subcode = 15;
    $boxcount = 16;
    $barcode = 17;

    switch ($systype) {
      case 'REALESTATE':
        $project = 18;
        $phasename = 19;
        $housemodel = 20;
        $blk = 21;
        $lot = 22;
        $amenityname = 23;
        $subamenityname = 24;
        array_push($columns, 'project', 'phasename', 'housemodel', 'blk', 'lot', 'amenityname', 'subamenityname');
        break;
    }

    $headgridbtns = ['itemvoiding', 'viewref', 'viewdiagram'];
    switch ($companyid) {
      case 10: //afti
        array_push($headgridbtns, 'viewitemstockinfo');
        if ($sq_makepo != 0) array_push($headgridbtns, 'sq_makepo');
        if ($sq_makejo != 0) array_push($headgridbtns, 'makejo');
        break;
    }

    if ($pr_btnvoid_access == 0) {
      unset($headgridbtns[0]);
    }

    $tab = [
      $this->gridname => [
        'gridcolumns' => $columns,
        'computefield' => ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'disc' => 'disc', 'total' => 'ext'],
        'headgridbtns' => $headgridbtns
      ]
    ];


    $stockbuttons = ['save', 'delete', 'showbalance'];
    switch ($systype) {
      case 'AIMS':
        switch ($companyid) {
          case 0: //main
          case 36: //rozlab
            array_push($stockbuttons, 'stockinfo');
            break;
          case 10: //afti
          case 12: //afti usd
            array_push($stockbuttons, 'iteminfo');
            break;
        }

        break;
    }

    if ($this->companysetup->getiseditsortline($config['params'])) {
      array_push($stockbuttons, 'sortline');
    }

    // 7 - ref
    $obj[0]['inventory']['columns'][$itemname]['lookupclass'] = 'refpo';

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    if (!$access['changeamt']) {
      $obj[0]['inventory']['columns'][$qa]['readonly'] = true;
      $obj[0]['inventory']['columns'][$rem]['readonly'] = true;
    }

    $obj[0]['inventory']['columns'][$partno]['label'] = 'Part No.';
    $obj[0]['inventory']['columns'][$partno]['type'] = 'label';
    $obj[0]['inventory']['columns'][$partno]['align'] = 'left';
    $obj[0]['inventory']['columns'][$partno]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px';

    $obj[0]['inventory']['columns'][$subcode]['label'] = 'Old SKU';
    $obj[0]['inventory']['columns'][$subcode]['type'] = 'label';
    $obj[0]['inventory']['columns'][$subcode]['align'] = 'left';
    $obj[0]['inventory']['columns'][$subcode]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px';

    $obj[0]['inventory']['columns'][$boxcount]['label'] = 'QTY Per Box';
    $obj[0]['inventory']['columns'][$boxcount]['type'] = 'label';
    $obj[0]['inventory']['columns'][$boxcount]['align'] = 'left';
    $obj[0]['inventory']['columns'][$boxcount]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px';

    if ($companyid != 6) { //not mitsukoshi
      $obj[0]['inventory']['columns'][$partno]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$subcode]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$boxcount]['type'] = 'coldel';
    }

    if ($companyid == 10 || $companyid == 12) { //afti,afti usd
      $obj[0]['inventory']['columns'][$wh]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$whname]['type'] = 'lookup';
      $obj[0]['inventory']['columns'][$whname]['lookupclass'] = 'whstock';
      $obj[0]['inventory']['columns'][$whname]['action'] = 'lookupclient';
    } else {
      $obj[0]['inventory']['columns'][$whname]['type'] = 'coldel';
    }

    switch ($systype) {
      case 'AIMS':
        if ($companyid == 0 || $companyid == 10) { //main,afti
          $obj[0]['inventory']['columns'][$rem]['type'] = 'coldel';
        }
        break;
      case 'REALESTATE':
        $obj[0][$this->gridname]['columns'][$blk]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$lot]['readonly'] = true;
        break;
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $obj[0]['inventory']['descriptionrow'] = [];
      $obj[0]['inventory']['columns'][$itemdesc]['type'] = 'textarea';
      $obj[0]['inventory']['columns'][$itemdesc]['readonly'] = true;
      $obj[0]['inventory']['columns'][$itemdesc]['style'] = 'text-align: left; width: 350px;whiteSpace: normal;min-width:350px;max-width:350px;';
      $obj[0]['inventory']['columns'][$disc]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$ext]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$netamt]['type'] = 'coldel';
    } elseif ($companyid == 39) { //cbbsi
      $obj[0]['inventory']['columns'][$itemdesc]['type'] = 'coldel';
    } else {
      $obj[0]['inventory']['columns'][$netamt]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$ext]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$disc]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$itemdesc]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$rrcost]['type'] = 'coldel';
    }
    $obj[0]['inventory']['columns'][$barcode]['type'] = 'hidden';
    $obj[0]['inventory']['columns'][$barcode]['label'] = '';

    $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    switch ($config['params']['companyid']) {
      case 0: //main
        $tbuttons = ['additem', 'quickadd', 'saveitem', 'deleteallitem'];
        break;
      default:
        $tbuttons = ['additem', 'quickadd', 'saveitem', 'deleteallitem'];
        break;
    }
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $companyid = $config['params']['companyid'];

    $fields = ['docno', 'client', 'clientname'];

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      array_push($fields, 'requestorname');
    } else {
      array_push($fields, 'address');
    }
    $col1 = $this->fieldClass->create($fields);

    switch ($companyid) {
      case 3: //conti
        data_set($col1, 'client.label', 'Department');
        break;
      default:
        data_set($col1, 'client.label', 'Department');
        data_set($col1, 'client.lookupclass', 'replookupdepartment');
        break;
    }

    data_set($col1, 'docno.label', 'Transaction#');

    $fields = [['dateid', 'due'], 'whname'];
    switch ($companyid) {
      case 10: //afti
        array_push($fields, 'purtype', 'ourref');
        data_set($col1, 'clientname.label', 'Department Name');
        data_set($col1, 'clientname.class', 'sbccsreadonly');
        break;
    }

    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'due.label', 'Required Date');
    data_set($col2, 'whname.required', true);
    data_set($col2, 'whname.type', 'lookup');
    data_set($col2, 'whname.action', 'lookupclient');
    data_set($col2, 'whname.lookupclass', 'wh');
    switch ($companyid) {
      case 10: //afti
        data_set($col2, 'ourref.label', 'Ref #');
        data_set($col2, 'ourref.required', true);
        break;
    }

    switch ($companyid) {
      case 10: //afti
        $fields = ['yourref', 'budgetreqno', 'rem'];
        if ($this->companysetup->getistodo($config['params'])) {
          array_push($fields, 'donetodo');
        }
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'yourref.label', 'PO Reference');
        data_set($col3, 'yourref.type', 'lookup');
        data_set($col3, 'yourref.action', 'lookupsjref');
        data_set($col3, 'yourref.lookupclass', 'lookupsjref');

        data_set($col3, 'yourref.addedparams', ['purtype']);
        data_set($col3, 'yourref.required', true);
        break;

      default:
        $fields = [['yourref', 'ourref'], 'rem'];
        if ($this->companysetup->getistodo($config['params'])) {
          array_push($fields, 'donetodo');
        }

        $col3 = $this->fieldClass->create($fields);
        break;
    }
    // 


    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $fields = [['cur', 'forex']];
      $col4 = $this->fieldClass->create($fields);
    } else {
      $fields = [];
      if ($this->companysetup->linearapproval($config['params'])) {
        array_push($fields, 'forapproval', 'doneapproved', 'lblapproved');
      }
      $col4 = $this->fieldClass->create($fields);
      data_set($col4, 'lblapproved.type', 'label');
      data_set($col4, 'lblapproved.label', 'APPROVED!');
      data_set($col4, 'lblapproved.style', 'font-weight:bold;font-family:Century Gothic;color: green;');
    }


    return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4];
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
    $data[0]['shipto'] = '';
    $data[0]['ourref'] = '';
    $data[0]['rem'] = '';
    $data[0]['terms'] = '';
    $data[0]['forex'] = 1;
    $data[0]['requestor'] = 0;
    $data[0]['requestorname'] = '';
    $data[0]['requestorcode'] = '';
    $data[0]['cur'] = $this->companysetup->getdefaultcurrency($params);
    $data[0]['wh'] = $this->companysetup->getwh($params);
    $name = $this->coreFunctions->datareader("select clientname as value from client where client='" . $data[0]['wh'] . "'");
    $data[0]['whname'] = $name;
    $data[0]['address'] = '';
    $data[0]['purtype'] = '';
    $data[0]['budgetreqno'] = '';

    $data[0]['projectid'] = '0';
    $data[0]['projectname'] = '';
    $data[0]['projectcode'] = '';
    $data[0]['dprojectname'] = '';

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
    head.terms,
    head.cur,
    head.forex,
    head.yourref,
    head.ourref,
    left(head.dateid,10) as dateid,
    head.clientname,
    head.address,
    head.shipto,
    date_format(head.createdate,'%Y-%m-%d') as createdate,
    head.rem,
    head.agent,
    head.purtype,
    head.requestor,
    req.clientname as requestorname,
    agent.clientname as agentname,
    head.wh as wh,
    warehouse.clientname as whname,
    '' as dwhname,
    left(head.due,10) as due,
    client.groupid,
    head.budgetreqno,
         
    head.projectid,
    ifnull(project.name,'') as projectname,
    ifnull(project.code,'') as projectcode,
    '' as dprojectname,

    head.phaseid, 
    ph.code as phase,

    head.modelid, 
    hm.model as housemodel, 
    
    head.blklotid, 
    bl.blk as blklot, 
    bl.lot,
    
    amh.line as amenityid,
    amh.description as amenityname,
    subamh.line as subamenityid,
    subamh.description as subamenityname,num.statid";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as agent on agent.client = head.agent
        left join client as req on req.clientid = head.requestor

        left join projectmasterfile as project on project.line=head.projectid 
        left join phase as ph on ph.line = head.phaseid
        left join housemodel as hm on hm.line = head.modelid
        left join blklot as bl on bl.line = head.blklotid

        left join amenities as amh on amh.line= head.amenityid
        left join subamenities as subamh on subamh.line=head.subamenityid and subamh.amenityid=head.amenityid

        where head.trno = ? and num.center = ?
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as agent on agent.client = head.agent
        left join client as req on req.clientid = head.requestor

        left join projectmasterfile as project on project.line=head.projectid 
        left join phase as ph on ph.line = head.phaseid
        left join housemodel as hm on hm.line = head.modelid
        left join blklot as bl on bl.line = head.blklotid

        left join amenities as amh on amh.line= head.amenityid
        left join subamenities as subamh on subamh.line=head.subamenityid and subamh.amenityid=head.amenityid

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
      // fortesting
      if ($this->companysetup->linearapproval($config['params'])) {
        switch ($head[0]->statid) {
          case 10: // forapproval
            $hideobj = ['forapproval' => true, 'doneapproved' => false, 'lblapproved' => true];
            break;
          case 36: // approved
            $hideobj = ['forapproval' => true, 'doneapproved' => true, 'lblapproved' => false];
            break;
          case 0: // draf
            $hideobj = ['forapproval' => false, 'doneapproved' => true, 'lblapproved' => true];
            break;
        }
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
    $qry = "select trno from " . $this->stock . " where trno=? and qty=0 limit 1";
    $isitemzeroqty = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($isitemzeroqty)) {
      return ['status' => false, 'msg' => 'Posting failed. Check carefully, some items have zero quantity.'];
    }
    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    if ($this->othersClass->isposted($config)) {
      return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
    }
    //for glhead
    $qry = "insert into " . $this->hhead . "(trno,doc,docno,client,clientname,address,shipto,dateid,
      terms,rem,forex,yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,agent,wh,due,cur,purtype,requestor, 
      budgetreqno,
      projectid,phaseid,modelid,blklotid,amenityid,subamenityid)
      SELECT head.trno,head.doc, head.docno,head.client, head.clientname, head.address,head.shipto,
      head.dateid as dateid, head.terms, head.rem, head.forex,head.yourref, head.ourref,
      head.createdate,head.createby,head.editby,head.editdate, head.lockdate,head.lockuser,head.agent,head.wh,
      head.due,head.cur,head.purtype,head.requestor,
      head.budgetreqno,
      head.projectid,head.phaseid,head.modelid,head.blklotid,head.amenityid,head.subamenityid
      FROM " . $this->head . " as head left join cntnum on cntnum.trno=head.trno
      where head.trno=? limit 1";
    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($posthead) {
      // for glstock

      if (!$this->othersClass->postingstockinfotrans($config)) {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'An error occurred while posting stock/s.'];
      }

      $qry = "insert into " . $this->hstock . "(trno,line,itemid,uom,
        whid,loc,ref,disc,cost,qty,void,rrcost,rrqty,ext,
        encodeddate,qa,encodedby,editdate,editby,refx,linex,cdqa,rem,sortline,projectid,phaseid,modelid,blklotid,amenityid,subamenityid)
        SELECT trno, line, itemid, uom,whid,loc,ref,disc,cost, qty,void,rrcost, rrqty, ext,
        encodeddate,qa, encodedby,editdate,editby,refx,linex,cdqa,rem,sortline,
        projectid,phaseid,modelid,blklotid,amenityid,subamenityid
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

    $qry = "insert into " . $this->head . "(trno,doc,docno,client,clientname,address,shipto,dateid,terms,rem,forex,
    yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,wh,due,cur,purtype,requestor, budgetreqno,
    projectid,phaseid,modelid,blklotid,amenityid,subamenityid)
    select head.trno, head.doc, head.docno, client.client, head.clientname, head.address, head.shipto,
    head.dateid as dateid, head.terms, head.rem, head.forex, head.yourref, head.ourref, head.createdate,
    head.createby, head.editby, head.editdate, head.lockdate, head.lockuser,head.wh,head.due,head.cur,head.purtype,head.requestor, head.budgetreqno,
    head.projectid,head.phaseid,head.modelid,head.blklotid,head.amenityid,head.subamenityid
    from (" . $this->hhead . " as head left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno)left join client on client.client=head.client
    where head.trno=? limit 1";
    //head
    if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
      if (!$this->othersClass->unpostingstockinfotrans($config)) {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Unposting failed. There are issues with inventory.'];
      }

      $qry = "insert into " . $this->stock . "(
      trno,line,itemid,uom,whid,loc,ref,disc,
      cost,qty,void,rrcost,rrqty,ext,rem,encodeddate,qa,encodedby,editdate,editby,refx,linex,cdqa,sortline,
      projectid,phaseid,modelid,blklotid,amenityid,subamenityid)
      select trno, line, itemid, uom,whid,loc,ref,disc,cost, qty,void, rrcost, rrqty,
      ext,rem, encodeddate, qa, encodedby, editdate, editby,refx,linex,cdqa,sortline,
      projectid,phaseid,modelid,blklotid,amenityid,subamenityid
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
    } else {
      return ['status' => false, 'msg' => 'Error on Unposting Head'];
    }
  } //end function

  private function getstockselect($config)
  {
    $companyid = $config['params']['companyid'];
    $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $qty_dec = 0;
    }

    $sqlselect = "select item.brand as brand,
    ifnull(mm.model_name,'') as model,
    item.itemid,
    stock.trno,
    stock.line,
    stock.refx,
    stock.linex,
    item.barcode,
    if(ifnull(sit.itemdesc,'')='',item.itemname,sit.itemdesc) as itemname,
    stock.uom,
    stock.cost,
    '' as netamt,
    stock.qty as qty,
    FORMAT(stock.rrcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrcost,
    round((stock.cost)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as netamt,
    FORMAT(stock.rrqty," . $qty_dec . ")  as rrqty,
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,
    left(stock.encodeddate,10) as encodeddate,
    stock.disc,
    case when stock.void=0 then 'false' else 'true' end as void,
    round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
    stock.ref,
    stock.whid,
    warehouse.client as wh,
    warehouse.clientname as whname,
    stock.loc,
    item.brand,
    stock.rem,
    ifnull(uom.factor,1) as uomfactor,
    '' as bgcolor,
    case when stock.void=0 then '' else 'bg-red-2' end as errcolor,
    item.subcode, item.partno, round(item.dqty, " . $this->companysetup->getdecimal('qty', $config['params']) . ") as boxcount,
    concat(item.itemname,'\\n',ifnull(brand.brand_desc,''),'\\r\\n',ifnull(mm.model_name,''),'\\r\\n',ifnull(i.itemdescription,'')) as itemdescription,

    stock.projectid, proj.code as project,

    stock.phaseid, ph.code as phasename,

    stock.modelid, hm.model as housemodel, 

    stock.blklotid, bl.blk, bl.lot,
    
    am.line as amenity,
    am.description as amenityname,
    subam.line as subamenity,
    subam.description as subamenityname,

    1+1 as ordernum,stock.sortline
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
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid 
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid
    left join stockinfotrans as sit on sit.trno = stock.trno and sit.line=stock.line

    left join projectmasterfile as proj on proj.line = stock.projectid
    left join phase as ph on ph.line = stock.phaseid
    left join housemodel as hm on hm.line = stock.modelid
    left join blklot as bl on bl.line = stock.blklotid
    left join amenities as am on am.line= stock.amenityid
    left join subamenities as subam on subam.line=stock.subamenityid and subam.amenityid=stock.amenityid

    where stock.trno =?
    UNION ALL
    " . $sqlselect . "
    FROM $this->hstock as stock
    left join item on item.itemid=stock.itemid
    left join model_masterfile as mm on mm.model_id = item.model
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
    left join client as warehouse on warehouse.clientid=stock.whid 
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid
    left join hstockinfotrans as sit on sit.trno = stock.trno and sit.line=stock.line
    
    left join projectmasterfile as proj on proj.line = stock.projectid
    left join phase as ph on ph.line = stock.phaseid
    left join housemodel as hm on hm.line = stock.modelid
    left join blklot as bl on bl.line = stock.blklotid
    left join amenities as am on am.line= stock.amenityid
    left join subamenities as subam on subam.line=stock.subamenityid and subam.amenityid=stock.amenityid
    
    where stock.trno =? order by sortline,line";

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
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid 
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid
    left join stockinfotrans as sit on sit.trno = stock.trno and sit.line=stock.line
    
    left join projectmasterfile as proj on proj.line = stock.projectid
    left join phase as ph on ph.line = stock.phaseid
    left join housemodel as hm on hm.line = stock.modelid
    left join blklot as bl on bl.line = stock.blklotid
    left join amenities as am on am.line= stock.amenityid
    left join subamenities as subam on subam.line=stock.subamenityid and subam.amenityid=stock.amenityid
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
      case 'addallitem': // save all item selected from lookup
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
      case 'gettrsummary':
        return $this->gettrsummary($config);
        break;
      case 'gettrdetails':
        return $this->gettrdetails($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
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
        stock.disc
        FROM htrhead as head left join htrstock as stock on stock.trno=head.trno left join item on item.itemid=
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
        stock.disc
        FROM htrhead as head left join htrstock as stock on stock.trno=head.trno left join item on item.itemid=
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

            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.'];
  } //end function


  public function stockstatusposted($config)
  {
    $action = $config['params']['action'];
    if ($action == 'stockstatusposted') {
      $action = $config['params']['lookupclass'];
    }

    switch ($action) {
      case 'updateitemvoid':
        return $this->updateitemvoid($config);
        break;
      case 'diagram':
        return $this->diagram($config);
        break;
      case 'donetodo':
        $tablenum = $this->tablenum;
        return $this->othersClass->donetodo($config, $tablenum);
        break;
      case 'navigation':
        return $this->othersClass->navigatedocno($config);
        break;
      case 'forapproval':
        $tablenum = $this->tablenum;
        return $this->othersClass->forapproval($config, $tablenum);
        break;
      case 'doneapproved':
        $tablenum = $this->tablenum;
        return $this->othersClass->approvedsetup($config, $tablenum);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function getposummaryqry($config)
  {
    return "
    select head.trno as refx, stock.line as linex, head.yourref,
    stock.itemid, stock.uom, stock.disc,
    stock.rrqtY as rrqty, stock.qty as qty,
    stock.cost as cost, stock.rrcost as rrcost, stock.ext,
    stock.qa as qa, stock.whid, head.docno as ref,
    item.famt,
    FORMAT(((stock.qty-(stock.qa+stock.cdqa))/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,
    head.cur, head.forex,stock.sortline
    from hprhead as head
    left join hprstock as stock on stock.trno = head.trno
    left join item on item.itemid=stock.itemid
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
    where head.trno = ? and stock.qty>(stock.qa+stock.cdqa) and item.islabor=0 and stock.void=0 order by sortline,linex";
  }

  public function getjosummaryqry($config)
  {
    return "
    select head.trno as refx, stock.line as linex, head.yourref,
    stock.itemid, stock.uom, stock.disc,
    stock.rrqtY as rrqty, stock.qty as qty,
    stock.cost as cost, stock.rrcost as rrcost, stock.ext,
    stock.qa as qa, stock.whid, head.docno as ref,
    item.famt,
    FORMAT(((stock.qty-(stock.qa+stock.cdqa))/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,
    head.cur, head.forex,stock.sortline
    from hprhead as head
    left join hprstock as stock on stock.trno = head.trno
    left join item on item.itemid=stock.itemid
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
    where head.trno = ? and stock.qty>(stock.qa+stock.cdqa) and item.islabor=1 and stock.void=0 order by sortline,linex ";
  }

  public function diagram($config)
  {
    $data = [];
    $nodes = [];
    $links = [];
    $data['width'] = 1500;
    $startx = 100;

    $qry = "select po.trno,po.docno,left(po.dateid,10) as dateid,
       CAST(concat('Total PO Amt: ',round(sum(s.ext),2)) as CHAR) as rem,s.refx
       from hpohead as po
       left join hpostock as s on s.trno = po.trno
       where s.refx = ?
       group by po.trno,po.docno,po.dateid,s.refx
       union all
       select po.trno,po.docno,left(po.dateid,10) as dateid,
       CAST(concat('Total PO Amt: ',round(sum(s.ext),2)) as CHAR) as rem,s.refx
       from pohead as po
       left join postock as s on s.trno = po.trno
       where s.refx = ?
       group by po.trno,po.docno,po.dateid,s.refx";
    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno'], $config['params']['trno']]);
    if (!empty($t)) {
      $startx = 550;
      $a = 0;

      foreach ($t as $key => $value) {
        //PO
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
            'color' => '#B5EAEA',
            'details' => [$t[$key]->dateid]
          ]
        );
        array_push($links, ['from' => $t[$key]->docno, 'to' => 'rr']);
        $a = $a + 100;

        if (floatval($t[$key]->refx) != 0) {
          //pr
          $qry = "select pr.docno,left(pr.dateid,10) as dateid,
            CAST(concat('Total PR Amt: ',round(sum(s.ext),2)) as CHAR) as rem
            from hprhead as pr left join hprstock as s on s.trno = pr.trno
            where pr.trno = ?
            group by pr.docno,pr.dateid";
          $x = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
          $poref = $t[$key]->docno;
          if (!empty($x)) {
            foreach ($x as $key2 => $value) {
              data_set(
                $nodes,
                $x[$key2]->docno,
                [
                  'align' => 'left',
                  'x' => 10,
                  'y' => 50 + $a,
                  'w' => 250,
                  'h' => 80,
                  'type' => $x[$key2]->docno,
                  'label' => $x[$key2]->rem,
                  'color' => '#F5FCC1',
                  'details' => [$x[$key2]->dateid]
                ]
              );
              array_push($links, ['from' => $x[$key2]->docno, 'to' => $poref]);
              $a = $a + 100;
            }
          }
        }
      }

      //RR
      $qry = "
        select head.docno,
        date(head.dateid) as dateid,
        CAST(concat('Total RR Amt: ', round(sum(stock.ext),2), ' - ', 'Balance: ', round(ap.bal, 2)) as CHAR) as rem,
        head.trno
        from glhead as head
        left join glstock as stock on head.trno = stock.trno
        left join apledger as ap on ap.trno = head.trno
        where stock.refx=?
        group by head.docno, head.dateid, head.trno, ap.bal
        union all
        select head.docno,
        date(head.dateid) as dateid,
        CAST(concat('Total RR Amt: ', round(sum(stock.ext),2), ' - ', 'Balance: ', round(sum(stock.ext),2)) as CHAR) as rem,
        head.trno
        from lahead as head
        left join lastock as stock on head.trno = stock.trno
        where stock.refx=?
        group by head.docno, head.dateid, head.trno";
      $t = $this->coreFunctions->opentable($qry, [$t[0]->trno, $t[0]->trno]);
      if (!empty($t)) {
        data_set(
          $nodes,
          'rr',
          [
            'align' => 'left',
            'x' => $startx,
            'y' => 100,
            'w' => 250,
            'h' => 80,
            'type' => $t[0]->docno,
            'label' => $t[0]->rem,
            'color' => '#1EAE98',
            'details' => [$t[0]->dateid]
          ]
        );

        foreach ($t as $key => $value) {
          //APV
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
                'apv',
                [
                  'align' => 'left',
                  'x' => $startx + 400,
                  'y' => 100,
                  'w' => 250,
                  'h' => 80,
                  'type' => $apvdata[$key2]->docno,
                  'label' => $apvdata[$key2]->rem,
                  'color' => '#EC4646',
                  'details' => [$apvdata[$key2]->dateid]
                ]
              );
              array_push($links, ['from' => 'rr', 'to' => 'apv']);
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
            where detail.refx = ?
            union all
            select head.docno, date(head.dateid) as dateid, head.trno,
            CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
            from lahead as head
            left join ladetail as detail on head.trno = detail.trno
            where detail.refx = ?";
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
                  'color' => '#EAE3C8',
                  'details' => [$cvdata[$key2]->dateid]
                ]
              );
              array_push($links, ['from' => 'apv', 'to' => $cvdata[$key2]->docno]);
              $a = $a + 100;
            }
          }

          //DM
          $dmqry = "
            select head.docno as docno,left(head.dateid,10) as dateid,
            CAST(concat('Total DM Amt: ', round(sum(stock.ext), 2)) as CHAR) as rem
            from glhead as head
            left join glstock as stock on stock.trno=head.trno
            left join item on item.itemid = stock.itemid
            where stock.refx=?
            group by head.docno, head.dateid
            union all
            select head.docno as docno,left(head.dateid,10) as dateid,
            CAST(concat('Total DM Amt: ', round(sum(stock.ext), 2)) as CHAR) as rem
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
                  'x' => $startx + 400,
                  'y' => 200,
                  'w' => 250,
                  'h' => 80,
                  'type' => $dmdata[$key2]->docno,
                  'label' => $dmdata[$key2]->rem,
                  'color' => '#FFBCBC',
                  'details' => [$dmdata[$key2]->dateid]
                ]
              );
              array_push($links, ['from' => 'rr', 'to' => $dmdata[$key2]->docno]);
              $a = $a + 100;
            }
          }
        }
      }
    }

    $data['nodes'] = $nodes;
    $data['links'] = $links;

    return ['status' => true, 'msg' => 'Successfully fetched.', 'data' => $data];
  }


  private function updateitemvoid($config)
  {
    $trno = $config['params']['trno'];
    $rows = $config['params']['rows'];
    foreach ($rows as $key) {
      $this->coreFunctions->execqry('update ' . $this->hstock . ' set void=1 where trno=? and line=?', 'update', [$key['trno'], $key['line']]);
    }
  } //end function

  public function updateperitem($config)
  {
    $config['params']['data'] = $config['params']['row'];
    $this->additem('update', $config);
    $data = $this->openstockline($config);
    return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
  }


  public function updateitem($config)
  {
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $this->additem('update', $config);
    }
    $data = $this->openstock($config['params']['trno'], $config);
    return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
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
    $systype = $this->companysetup->getsystemtype($config['params']);
    $companyid = $config['params']['companyid'];
    $uom = $config['params']['data']['uom'];
    $itemid = $config['params']['data']['itemid'];
    $trno = $config['params']['trno'];
    $disc = $config['params']['data']['disc'];
    $wh = $config['params']['data']['wh'];
    $loc = $config['params']['data']['loc'];
    $void = 'false';
    $itemdesc = '';
    if (isset($config['params']['data']['void'])) {
      $void = $config['params']['data']['void'];
    }
    $rem = '';
    if (isset($config['params']['data']['rem'])) {
      $rem = $config['params']['data']['rem'];
    }
    if (isset($config['params']['data']['itemname'])) {
      $itemdesc = $config['params']['data']['itemname'];
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
    } elseif ($action == 'update') {
      $config['params']['line'] = $config['params']['data']['line'];
      $line = $config['params']['data']['line'];
      $amt = $config['params']['data'][$this->damt];
      $qty = $config['params']['data'][$this->dqty];

      $config['params']['line'] = $line;
    }
    $amt = $this->othersClass->sanitizekeyfield('amt', $amt);
    $qty = $this->othersClass->sanitizekeyfield('qty', $qty);


    if ($systype == 'REALESTATE') {
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

    if ($companyid == 39) { //cbbsi
      $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor, 0, 'P', 0, 1, 1);
    } else {
      $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor);
    }

    $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);
    $data = [
      'trno' => $trno,
      'line' => $line,
      'itemid' => $itemid,
      'rrcost' => $amt,
      'cost' =>  $computedata['amt'],
      'rrqty' => $qty,
      'qty' => $computedata['qty'],
      'ext' => $computedata['ext'],
      'disc' => $disc,
      'whid' => $whid,
      'loc' => $loc,
      'uom' => $uom,
      'void' => $void,
      'rem' => $rem
    ];
    if ($systype == 'REALESTATE') {
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
      $data['sortline'] =  $data['line'];
      $data['encodeddate'] = $current_timestamp;
      $data['encodedby'] = $config['params']['user'];
      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
        switch ($this->companysetup->getsystemtype($config['params'])) {
          case 'AIMS':
            if ($companyid == 0 || $companyid == 10) { //main, afti
              $stockinfo_data = [
                'trno' => $trno,
                'line' => $line,
                'rem' => $rem
              ];
              $this->coreFunctions->sbcinsert('stockinfotrans', $stockinfo_data);
            }
            if ($companyid == 36) { //rozlab
              $stockinfo_data = [
                'trno' => $trno,
                'line' => $line,
                'itemdesc' => $itemdesc
              ];
              $this->coreFunctions->sbcinsert('stockinfotrans', $stockinfo_data);
            }
            break;
        }

        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:' . $computedata['ext'] . ' Uom:' . $uom);
        $row = $this->openstockline($config);
        return ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
      } else {
        return ['status' => false, 'msg' => 'Add item Failed'];
      }
    } elseif ($action == 'update') {
      return $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
    }
  } // end function



  public function deleteallitem($config)
  {
    $isallow = true;
    $trno = $config['params']['trno'];
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from stockinfotrans where trno=?', 'delete', [$trno]);
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

    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' barcode:' . $data[0]->barcode . ' Qty:' . $data[0]->rrqty . ' Amt:' . $data[0]->rrcost . ' Disc:' . $data[0]->disc . ' wh:' . $data[0]->wh . ' ext:' . $data[0]->ext);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
  } // end function

  public function getlatestprice($config)
  {
    $barcode = $config['params']['barcode'];
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $qry = "select docno,left(dateid,10) as dateid,round(amt,2) as amt,disc,uom from(select head.docno,head.dateid,
          stock.rrcost as amt,stock.uom,stock.disc
          from lahead as head
          left join lastock as stock on stock.trno = head.trno
          left join cntnum on cntnum.trno=head.trno
          left join item on item.itemid = stock.itemid
          where head.doc = 'RR' and cntnum.center = ?
          and item.barcode = ? and head.client = ?
          and stock.rrcost <> 0
          UNION ALL
          select head.docno,head.dateid,stock.rrcost as computeramt,
          stock.uom,stock.disc from glhead as head
          left join glstock as stock on stock.trno = head.trno
          left join item on item.itemid = stock.itemid
          left join client on client.clientid = head.clientid
          left join cntnum on cntnum.trno=head.trno
          where head.doc = 'RR' and cntnum.center = ?
          and item.barcode = ? and client.client = ?
          and stock.rrcost <> 0
          order by dateid desc limit 5) as tbl order by dateid desc limit 1";
    $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $client, $center, $barcode, $client]);
    if (!empty($data)) {
      return ['status' => true, 'msg' => 'Found the latest purchase price...', 'data' => $data];
    } else {
      return ['status' => false, 'msg' => 'No Latest price found...'];
    }
  } // end function

  // report start to

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
    $this->logger->sbcviewreportlog($config);

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config['params']['dataid']);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    $dataparams = $config['params']['dataparams'];
    if ($companyid == 3 || $companyid == 39) { //conti,cbbsi

      if (isset($dataparams['received'])) $this->othersClass->writeSignatories($config, 'received', $dataparams['received']);
      if (isset($dataparams['approved'])) $this->othersClass->writeSignatories($config, 'approved', $dataparams['approved']);
      if (isset($dataparams['prepared'])) $this->othersClass->writeSignatories($config, 'prepared', $dataparams['prepared']);
    }

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
