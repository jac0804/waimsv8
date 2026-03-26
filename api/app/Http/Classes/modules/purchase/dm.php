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


class dm
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'PURCHASE RETURN';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
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
  public $statlogs = 'transnum_stat';
  public $dqty = 'isqty';
  public $hqty = 'iss';
  public $damt = 'isamt';
  public $hamt = 'amt';
  public $defaultContra = 'AP1';
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
    'projectid',
    'subproject',
    'branch',
    'deptid',
    'billid',
    'shipid',
    'billcontactid',
    'shipcontactid',
    'returndate',
    'refunddate',
    'phaseid',
    'modelid',
    'blklotid',
    'amenityid',
    'subamenityid'
  ];

  private $otherfields = ['trno', 'isapproved', 'isreturned', 'isrefunded'];

  private $except = ['trno', 'dateid', 'due'];
  private $acctg = [];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  private $reporter;
  private $helpClass;

  public $showfilterlabel = [
    ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
    ['val' => 'forapproval', 'label' => 'For Approval', 'color' => 'primary'],
    ['val' => 'approved', 'label' => 'Approved', 'color' => 'primary'],
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
      'view' => 98,
      'edit' => 99,
      'new' => 100,
      'save' => 101,
      // 'change' => 102, remove change doc
      'delete' => 103,
      'print' => 104,
      'lock' => 105,
      'unlock' => 106,
      'acctg' => 90,
      'changeamt' => 110,
      'post' => 107,
      'unpost' => 108,
      'additem' => 820,
      'edititem' => 821,
      'deleteitem' => 822,
      'viewcost' => 368
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $companyid = $config['params']['companyid'];
    // $action = 0;
    // $liststatus = 1;
    // $listdocument = 2;
    // $whname = 3;
    // $listdate = 4;
    // $listclientname = 5;
    // $yourref = 6;
    // $ourref = 7;
    // $rem = 8;
    // $postdate = 9;

    if ($companyid == 56) { //homeworks
      $getcols = ['action', 'liststatus', 'listdocument', 'whname', 'listdate', 'client', 'listclientname', 'yourref', 'ourref', 'rem', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
    } else {
      $getcols = ['action', 'liststatus', 'listdocument', 'whname', 'listdate', 'listclientname', 'yourref', 'ourref', 'rem', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
    }

    $stockbuttons = ['view'];
    foreach ($getcols as $key => $value) {
      $$value = $key;
    }

    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$listclientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $cols[$yourref]['align'] = 'text-left';
    $cols[$ourref]['align'] = 'text-left';
    $cols[$postdate]['label'] = 'Post Date';
    if ($companyid != 28) $cols[$rem]['type'] = 'coldel'; //not xcomp
    if ($companyid != 21) $cols[$whname]['type'] = 'coldel'; //not kinggeorge
    if (!$this->companysetup->linearapproval($config['params'])) {
      unset($this->showfilterlabel[1]);
      unset($this->showfilterlabel[2]);
    }

    if ($companyid == 56) { //homeworks
      $cols[$listdocument]['style'] = 'width:180px;whiteSpace: normal;min-width:180px;';
      $cols[$client]['style'] = 'width:80px;whiteSpace: normal;min-width:80px;';
      $cols[$client]['type'] = 'label';
      $cols[$client]['label'] = 'Code';
    }


    $cols = $this->tabClass->delcollisting($cols);
    return $cols;
  }

  public function paramsdatalisting($config)
  {
    $companyid = $config['params']['companyid'];
    switch ($companyid) {
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
    $companyid = $config['params']['companyid'];
    $isproject = $this->companysetup->getisproject($config['params']);
    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));
    $itemfilter = $config['params']['itemfilter'];
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $condition = '';
    $projectfilter = '';
    $searchfield = [];
    $filtersearch = "";
    $search = $config['params']['search'];
    $limit = '';


    $field = '';
    $join = '';
    $hjoin = '';
    $addparams = '';

    if ($isproject) {
      $viewall = $this->othersClass->checkAccess($config['params']['user'], 2233);
      $project = $this->coreFunctions->getfieldvalue("useraccess", "project", "username=?", [$config['params']['user']]);
      $projectid = $this->coreFunctions->getfieldvalue("projectmasterfile", "line", "code=?", [$project]);
      if ($viewall == '0') {
        if ($projectid != '') {
          $projectfilter = " and head.projectid = " . $projectid . " ";
        }
      }
    }
    $status = " case when num.postdate is not null then 'POSTED' else 'DRAFT' end ";
    if ($this->companysetup->linearapproval($config['params'])) {
      $itemfilter = isset($config['params']['doclistingparam']['typecode']) ? $config['params']['doclistingparam']['typecode'] : $itemfilter;
      $user = $config['params']['user'];
      $userid = $this->coreFunctions->getfieldvalue("useraccess", "userid", "username=?", [$user]);
      if ($userid != 0) {
        $qry = "select s.isapprover as value
                from approversetup as s
                left join approverdetails as d on d.appline=s.line
                left join useraccess as u on u.username=d.approver
                where u.userid=? and and s.doc=?";

        $isapprover = $this->coreFunctions->datareader($qry, [$userid, $doc]);
        if ($isapprover == 1) {
          $itemfilter = isset($config['params']['doclistingparam']['typecode']) ? $config['params']['doclistingparam']['typecode'] : 'forapproval';
        }
      }
      $status = " case when num.postdate is null and num.statid = 10 then 'FOR APPROVAL' 
    when num.postdate is null and num.statid=36 then 'APPROVED' else 'DRAFT' end ";
    }
    switch ($itemfilter) {
      case 'draft':
        $condition = ' and num.postdate is null and num.statid = 0';
        break;
      case 'forapproval':
        $condition .= " and num.postdate is null and head.lockdate is null and num.statid=10 
                        and num.appuser='" . $config['params']['user'] . "'";
        $status = "'FOR APPROVAL'";
        break;
      case 'approved':
        $condition .= " and num.postdate is null and head.lockdate is null and num.statid=36";
        $status = "'APPROVED'";
        break;
      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
    }



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
            $join = " left join lastock on lastock.trno = head.trno
            left join item on item.itemid = lastock.itemid left join item as item2 on item2.itemid = lastock.itemid
            left join model_masterfile as model on model.model_id = item.model 
            left join model_masterfile as model2 on model2.model_id = item2.model 
            left join frontend_ebrands as brand on brand.brandid = item.brand 
            left join frontend_ebrands as brand2 on brand2.brandid = item2.brand
            left join projectmasterfile as p on p.line = item.projectid 
            left join projectmasterfile as p2 on p2.line = item2.projectid ";

            $hjoin = " left join glstock on glstock.trno = head.trno
            left join item on item.itemid = glstock.itemid left join item as item2 on item2.itemid = glstock.itemid
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
      $searchfield = ['head.docno', 'head.clientname', 'head.yourref', 'head.ourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];

      if ($companyid == 28) { //xcomp
        array_push($searchfield, 'head.rem');
      }
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }


    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $dateid = "date_format(head.dateid,'%m-%d-%Y') as dateid";
        if ($search == "") $limit = 'limit 25';
        $orderby =  "order by  dateid desc, docno desc";
        break;
      case 19: //housegem
        $dateid = "left(head.dateid,10) as dateid";
        if ($search == "") $limit = 'limit 150';
        $orderby = "order by docno desc, dateid desc";
        break;
      case 56: //homeworks
        $dateid = "left(head.dateid,10) as dateid";
        if ($search == "") $limit = 'limit 150';
        $orderby = "order by dateid desc, docno desc";
        $join = " left join client as cl on cl.client= head.client";
        $hjoin = " left join client as cl on cl.clientid= head.clientid";
        $field = ", cl.client";
        break;
      default:
        $dateid = "left(head.dateid,10) as dateid";
        if ($search == "") $limit = 'limit 150';
        $orderby = "order by dateid desc, docno desc";
        break;
    }

    if ($companyid == 21) { //kinggeorge
      $field .= ",concat(wh.client,'~',wh.clientname) as whname";
      $join .= "left join client as wh on wh.client=head.wh";
      $hjoin .= "left join client as wh on wh.clientid=head.whid";
      if ($search == "") $limit = 'limit 150';
      $orderby =  "order by  dateid desc, docno desc";
    }


    $qry = "select head.trno,head.docno,head.clientname,$dateid, $status as status,
    head.createby,head.editby,head.viewby,num.postedby, date(num.postdate) as postdate, head.rem,
     head.yourref, head.ourref  $field
     from " . $this->head . " as head left join " . $this->tablenum . " as num
     on num.trno=head.trno 
     " . $join . "
     where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $projectfilter . $condition . $addparams . " " . $filtersearch . "
     union all
     select head.trno,head.docno,head.clientname,$dateid,'POSTED' as status,
     head.createby,head.editby,head.viewby, num.postedby, date(num.postdate) as postdate, head.rem,
      head.yourref, head.ourref  $field
     from " . $this->hhead . " as head left join " . $this->tablenum . " as num
     on num.trno=head.trno 
     " . $hjoin . "
     where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $projectfilter . $condition . $addparams . " " . $filtersearch . "
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
    $step1 = $this->helpClass->getFields(['btnnew', 'supplier', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
    $step2 = $this->helpClass->getFields(['btnedit', 'supplier', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
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
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => 'dm', 'title' => 'Purchase Return Manual', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
    }
    if ($config['params']['companyid'] == 56) { // homeworks
      $buttons['others']['items']['uploadexcel'] = ['label' => 'Upload Items', 'todo' => ['type' => 'uploadexcel', 'action' => 'uploadexcel', 'lookupclass' => 'uploadexcel', 'access' => 'view']];
      $buttons['others']['items']['downloadexcel'] = ['label' => 'Download DM Template', 'todo' => ['type' => 'downloadexcel', 'action' => 'downloadexcel', 'lookupclass' => 'downloadexcel', 'access' => 'view']];
    }
    return $buttons;
  } // createHeadbutton

  public function createtab2($access, $config)
  {
    $companyid = $config['params']['companyid'];

    $billshipdefault = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewbillingshipping']];

    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrycntnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);

    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];
    if ($companyid == 10) { //afti
      $return['SHIPPING/BILLING ADDRESS'] = ['icon' => 'fa fa-map-marker-alt', 'customform' => $billshipdefault];
    }

    if ($this->companysetup->getistodo($config['params'])) {
      $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrycntnumtodo', 'label' => 'To Do', 'access' => 'view']];
      $objtodo = $this->tabClass->createtab($tab, []);
      $return['To Do'] = ['icon' => 'fa fa-list', 'tab' => $objtodo];
    }

    if ($config['params']['companyid'] == 60) { //transpower      
      $changecode = $this->othersClass->checkAccess($config['params']['user'], 5493);
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
    $ispallet = $this->companysetup->getispallet($config['params']);
    $isproject = $this->companysetup->getisproject($config['params']);
    $iskgs = $this->companysetup->getiskgs($config['params']);
    $invonly = $this->companysetup->isinvonly($config['params']);

    $systype = $this->companysetup->getsystemtype($config['params']);
    $allowviewbalance = $this->othersClass->checkAccess($config['params']['user'], 5451); //kinggeorge

    $action = 0;
    $isqty = 1;
    $uom = 2;
    $kgs = 3;
    $isamt  = 4;
    $disc = 5;
    $ext = 6;
    $wh = 7;
    $whname = 8;
    $ref = 9;
    $loc = 10;
    $expiry = 11;
    $pallet = 12;
    $location = 13;
    $stage = 14;
    $rem = 15;
    $itemname = 16;
    $barcode = 17;
    $stock_projectname = 18;
    $partno = 19;
    $subcode = 20;
    $boxcount = 21;

    $column = [
      'action',
      'isqty',
      'uom',
      'kgs',
      'isamt',
      'disc',
      'ext',
      'wh',
      'whname',
      'ref',
      'loc',
      'expiry',
      'pallet',
      'location',
      'stage',
      'rem',
      'itemname',
      'barcode',
      'stock_projectname',
      'partno',
      'subcode',
      'boxcount'
    ];

    $sortcolumn =  [
      'action',
      'isqty',
      'uom',
      'kgs',
      'isamt',
      'disc',
      'ext',
      'wh',
      'whname',
      'ref',
      'loc',
      'expiry',
      'pallet',
      'location',
      'stage',
      'rem',
      'itemname',
      'barcode',
      'stock_projectname',
      'partno',
      'subcode',
      'boxcount'
    ];


    switch ($systype) {
      case 'REALESTATE':
        $project = 22;
        $phasename = 23;
        $housemodel = 24;
        $blk = 25;
        $lot = 26;
        $amenityname = 27;
        $subamenityname = 28;
        array_push($column, 'project', 'phasename', 'housemodel', 'blk', 'lot', 'amenityname', 'subamenityname');
        array_push($sortcolumn, 'project', 'phasename', 'housemodel', 'blk', 'lot', 'amenityname', 'subamenityname');
        break;
    }


    if ($invonly) {
      $headgridbtns = ['viewref', 'viewdiagram'];
    } else {
      $headgridbtns = ['viewdistribution', 'viewref', 'viewdiagram'];
    }


    if ($companyid == 10 || $companyid ==60) { //afti and transpower
      array_push($headgridbtns, 'viewitemstockinfo');
    }

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
          case 0: //main
            array_push($stockbuttons, 'stockinfo');
            break;
          
        }
        
        break;
    }


    if ($companyid != 19) { //not housegem
      $obj[0]['inventory']['columns'][$rem]['type'] = 'coldel';
    }

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0]['inventory']['columns'][$barcode]['type'] = 'hidden';
    $obj[0]['inventory']['columns'][$barcode]['label'] = '';

    if (!$isexpiry) {
      $obj[0]['inventory']['columns'][$loc]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$expiry]['type'] = 'coldel';

      $obj[0][$this->gridname]['columns'][$pallet]['action'] = 'lookuppalletbalance';
    }

    $obj[0]['inventory']['columns'][$isamt]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px';

    $obj[0]['inventory']['columns'][$kgs]['label'] = 'Buying Kgs';
    if (!$iskgs) {
      $obj[0]['inventory']['columns'][$kgs]['type'] = 'coldel';
    }

    if ($companyid == 8) { // maxipro
      $obj[0]['inventory']['columns'][$loc]['label'] = 'Brand';
      $obj[0]['inventory']['columns'][$loc]['type'] = 'lookup';
    }

    $obj[0]['inventory']['columns'][$pallet]['type'] = 'coldel';
    if (!$ispallet) {
      $obj[0]['inventory']['columns'][$location]['type'] = 'coldel';
    }

    if (!$isproject) {
      $obj[0]['inventory']['columns'][$stage]['type'] = 'coldel';
    }

    $obj[0]['inventory']['columns'][$ref]['lookupclass'] = 'refdm';

    if (!$access['changeamt']) {
      $obj[0]['inventory']['columns'][$isamt]['readonly'] = true;
      $obj[0]['inventory']['columns'][$disc]['readonly'] = true;
    }

    if ($companyid != 10) { //not afti
      $obj[0]['inventory']['columns'][$stock_projectname]['type'] = 'coldel';
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

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $obj[0]['inventory']['columns'][$wh]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$whname]['type'] = 'lookup';
      $obj[0]['inventory']['columns'][$whname]['lookupclass'] = 'whstock';
      $obj[0]['inventory']['columns'][$whname]['action'] = 'lookupclient';
    } else {
      $obj[0]['inventory']['columns'][$whname]['type'] = 'coldel';
    }

    if ($companyid == 23) { //labsol cebu
      $obj[0]['inventory']['columns'][$loc]['label'] = 'Lot/Serial#';
      $obj[0]['inventory']['columns'][$expiry]['label'] = 'Expiry/Mfr Date';
    }

    switch ($systype) {
      case 'REALESTATE':
        $obj[0]['inventory']['columns'][$blk]['readonly'] = true;
        $obj[0]['inventory']['columns'][$lot]['readonly'] = true;
        break;
    }

    $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['additem', 'quickadd', 'saveitem', 'deleteallitem', 'pendingrr'];
    if ($config['params']['companyid'] == 63) { //ericco
      $tbuttons = ['multiitem', 'quickadd', 'saveitem', 'deleteallitem', 'pendingrr'];
    }

    if ($this->companysetup->getispallet($config['params'])) {
      array_push($tbuttons, 'pendingrp', 'pendingsplitqtypicker');
    }
    $obj = $this->tabClass->createtabbutton($tbuttons);

    if ($config['params']['companyid'] == 8) { //maxipro
      $obj[2]['label'] = "SAVE ALL";
      $obj[3]['label'] = "DELETE ALL";
    }
    return $obj;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];
    $invonly = $this->companysetup->isinvonly($config['params']);
    $systype = $this->companysetup->getsystemtype($config['params']);
    $noeditdate = $this->othersClass->checkAccess($config['params']['user'], 4851);
    $fields = ['docno', 'client', 'clientname'];
    $col1 = $this->fieldClass->create($fields);
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      data_set($col1, 'clientname.type', 'textarea');
    } else {
      array_push($fields, 'address');
    }

    data_set($col1, 'client.label', 'Vendor');
    data_set($col1, 'docno.label', 'Transaction#');

    if ($invonly) {
      $fields = [['dateid', 'terms'], 'due', 'dwhname'];
    } else {
      $fields = [['dateid', 'terms'], ['due', 'dvattype'], 'dacnoname', 'dwhname'];
    }

    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'dacnoname.label', 'AP Account');

    if ($companyid == 40) { //cdo
      if ($noeditdate) {
        data_set($col2, 'dateid.class', 'sbccsreadonly');
      }
    }

    $fields = [['yourref', 'ourref'], ['cur', 'forex'], 'dprojectname'];

    switch ($companyid) {
      case 10: //afti
        unset($fields[2]); // project
        array_push($fields, 'dbranchname');
        break;

      case 21: // kinggeorge
        unset($fields[2]); // project
        break;
      case 16: // ati
        unset($fields[2]); // project
        array_push($fields, ['returndate', 'refunddate'], 'isapproved', 'isreturned', 'isrefunded');
        break;
    }

    if ($systype == 'REALESTATE') {
      $fields = ['yourref', 'ourref', ['cur', 'forex'], 'rem'];
    }


    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'returndate.type', 'date');
    data_set($col3, 'isapproved.label', 'Approved by Supplier');
    data_set($col3, 'isreturned.label', 'Item Returned');

    if ($this->companysetup->getisproject($config['params'])) {
      $viewall = $this->othersClass->checkAccess($config['params']['user'], 2233);
      if ($viewall) {
        data_set($col3, 'dprojectname.lookupclass', 'projectcode');
        data_set($col3, 'dprojectname.addedparams', []);
        data_set($col3, 'dprojectname.required', true);
        data_set($col3, 'dprojectname.condition', ['checkstock']);
        $fields = ['rem', 'subprojectname'];
        if ($this->companysetup->getistodo($config['params'])) {
          array_push($fields, 'donetodo');
        }
        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'rem.style', 'height: 130px; max-width: 400px');
        data_set($col4, 'subprojectname.required', true);
      } else {
        data_set($col3, 'dprojectname.type', 'input');
        $fields = ['rem', 'subprojectname'];
        if ($this->companysetup->getistodo($config['params'])) {
          array_push($fields, 'donetodo');
        }
        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'rem.style', 'height: 130px; max-width: 400px');
        data_set($col4, 'subprojectname.type', 'lookup');
        data_set($col4, 'subprojectname.lookupclass', 'lookupsubproject');
        data_set($col4, 'subprojectname.action', 'lookupsubproject');
        data_set($col4, 'subprojectname.addedparams', ['projectid']);
        data_set($col4, 'subprojectname.required', true);
      }
    } else {

      if ($companyid == 60) { //transpower
        data_set($col3, 'yourref.label', 'RR# / SI #');
        data_set($col3, 'ourref.label', 'PO#');
        data_set($col3, 'yourref.required', true);
        data_set($col3, 'ourref.required', true);
      }

      $fields = ['rem'];

      if ($this->companysetup->getistodo($config['params'])) {
        array_push($fields, 'donetodo');
      }
      if ($companyid == 10) { //afti
        array_push($fields, 'ddeptname');
      }

      if ($systype == 'REALESTATE') {
        $fields = ['dprojectname', 'phase', 'housemodel', ['blklot', 'lot'], 'amenityname', 'subamenityname'];
        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'dprojectname.lookupclass', 'project');
        data_set($col4, 'phase.addedparams', ['projectid']);
        data_set($col4, 'housemodel.addedparams', ['projectid']);
        data_set($col4, 'blklot.addedparams', ['projectid', 'phaseid', 'modelid', 'fpricesqm']);
        data_set($col4, 'subamenityname.addedparams', ['amenityid']);
      } else {
        if ($this->companysetup->linearapproval($config['params'])) {
          array_push($fields, 'forapproval', 'doneapproved', 'lblapproved');
        }
        $col4 = $this->fieldClass->create($fields);
        if ($companyid == 10) { //afti
          data_set($col4, 'ddeptname.label', 'Department');
        }
        data_set($col4, 'lblapproved.type', 'label');
        data_set($col4, 'lblapproved.label', 'APPROVED!');
        data_set($col4, 'lblapproved.style', 'font-weight:bold;font-family:Century Gothic;color: green;');
      }
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
    $data[0]['shipto'] = '';
    $data[0]['ourref'] = '';
    $data[0]['rem'] = '';

    if ($params['companyid'] == 56) {
      $data[0]['terms'] = '15 DAYS';
    } else {
      $data[0]['terms'] = '';
    }

    $data[0]['forex'] = 1;
    $data[0]['cur'] = $this->companysetup->getdefaultcurrency($params);
    $data[0]['tax'] = 0;
    $data[0]['vattype'] = 'NON-VATABLE';
    $data[0]['contra'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', [$this->defaultContra]);
    $data[0]['acnoname'] = $this->coreFunctions->getfieldvalue('coa', 'acnoname', 'acno=?', [$data[0]['contra']]);
    $data[0]['wh'] = $this->companysetup->getwh($params);
    $name = $this->coreFunctions->getfieldvalue('client', 'clientname', 'client=?', [$data[0]['wh']]);
    $data[0]['whname'] = $name;

    $isproject = $this->companysetup->getisproject($params);

    if ($isproject) {
      $viewall = $this->othersClass->checkAccess($params['user'], 2233);
      $data[0]['projectid'] = '0';
      $data[0]['projectname'] = '';
      $data[0]['projectcode'] = '';

      if ($viewall == '0') {
        $pid = $this->coreFunctions->getfieldvalue("useraccess", "project", "username=?", [$params['user']]);
        $data[0]['projectid'] = $this->coreFunctions->getfieldvalue("projectmasterfile", "line", "code=?", [$pid]);
        $data[0]['projectcode'] =  $pid;
        $data[0]['projectname'] = $this->coreFunctions->getfieldvalue("projectmasterfile", "name", "code=?", [$pid]);
      }
    } else {
      $data[0]['projectid'] = '0';
      $data[0]['projectname'] = '';
      $data[0]['projectcode'] = '';
    }
    $data[0]['dprojectname'] = '';
    $data[0]['subproject'] = '';
    $data[0]['subprojectname'] = '';
    $data[0]['address'] = '';
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
    $data[0]['returndate'] = null;
    $data[0]['refunddate'] = null;

    $data[0]['isapproved'] = '0';
    $data[0]['isreturned'] = '0';
    $data[0]['isrefunded'] = '0';


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
    $tablenum = $this->tablenum;
    $isproject = $this->companysetup->getisproject($config['params']);
    $projectfilter = "";

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

    $center = $config['params']['center'];
    $head = [];
    $islocked = $this->othersClass->islocked($config);
    $isposted = $this->othersClass->isposted($config);
    $table = $this->head;
    $htable = $this->hhead;

    if ($isproject) {
      $viewall = $this->othersClass->checkAccess($config['params']['user'], 2233);
      $project = $this->coreFunctions->getfieldvalue("useraccess", "project", "username=?", [$config['params']['user']]);
      $projectid = $this->coreFunctions->getfieldvalue("projectmasterfile", "line", "code=?", [$project]);
      if ($viewall == '0') {
        if ($projectid != '') {
          $projectfilter = " and head.projectid = " . $projectid . " ";
        }
      }
    }

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
        head.returndate,
        head.refunddate,
        '' as dvattype,
        warehouse.client as wh,
        warehouse.clientname as whname,
        '' as dwhname,
        left(head.due,10) as due,

        head.projectid,
        ifnull(project.code,'') as projectcode,
        ifnull(project.name,'') as projectname,
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
        subamh.description as subamenityname,

        cast(ifnull(info.isapproved,0) as char) as isapproved,
        cast(ifnull(info.isreturned,0) as char) as isreturned,
        cast(ifnull(info.isrefunded,0) as char) as isrefunded,
        client.groupid,
        ifnull(s.line,0) as subproject,ifnull(s.subproject,'') as subprojectname,head.branch,ifnull(b.clientname,'') as branchname,ifnull(b.client,'') as branchcode,'' as dbranchname,ifnull(d.client,'') as dept,ifnull(d.clientname,'') as deptname,head.deptid,'' as ddeptname,head.billid,head.shipid,head.billcontactid,head.shipcontactid,num.statid";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as b on b.clientid = head.branch
        left join client as d on d.clientid = head.deptid
        left join coa on coa.acno=head.contra
        left join projectmasterfile as project on project.line=head.projectid

        left join phase as ph on ph.line = head.phaseid
        left join housemodel as hm on hm.line = head.modelid
        left join blklot as bl on bl.line = head.blklotid

        left join amenities as amh on amh.line= head.amenityid
        left join subamenities as subamh on subamh.line=head.subamenityid and subamh.amenityid=head.amenityid

        left join subproject as s on s.line = head.subproject
        left join cntnuminfo as info on info.trno=head.trno
        where head.trno = ? and num.doc=? and num.center = ? " . $projectfilter . "
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.clientid = client.clientid
        left join client as warehouse on warehouse.clientid = head.whid
        left join client as b on b.clientid = head.branch
        left join client as d on d.clientid = head.deptid
        left join coa on coa.acno=head.contra
        left join projectmasterfile as project on project.line=head.projectid

        left join phase as ph on ph.line = head.phaseid
        left join housemodel as hm on hm.line = head.modelid
        left join blklot as bl on bl.line = head.blklotid

        left join amenities as amh on amh.line= head.amenityid
        left join subamenities as subamh on subamh.line=head.subamenityid and subamh.amenityid=head.amenityid
        
        left join subproject as s on s.line = head.subproject
        left join hcntnuminfo as info on info.trno=head.trno
        where head.trno = ? and num.doc=? and num.center=? " . $projectfilter;

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

    if ($data['terms'] == '') {
      $data['due'] =  $data['dateid'];
    } else {
      $data['due'] = $this->othersClass->computeterms($data['dateid'], $data['dateid'], $data['terms']);
    }

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

    if ($config['params']['companyid'] == 16) { //ati
      $infotransexist = $this->coreFunctions->getfieldvalue("cntnuminfo", "trno", "trno=?", [$head['trno']]);
      if ($infotransexist == '') {
        $this->coreFunctions->sbcinsert("cntnuminfo", $dataother);
      } else {
        $dataother['editby'] = $config['params']['user'];
        $dataother['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $this->coreFunctions->sbcupdate("cntnuminfo", $dataother, ['trno' => $head['trno']]);
      }
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
      $checkacct = $this->othersClass->checkcoaacct(['AP1', 'IN1', 'TX1', 'GLC', 'CG1', 'PD1']);

      if ($checkacct != '') {
        return ['trno' => $trno, 'status' => false, 'msg' => 'Accounts not yet setup:' . $checkacct];
      }

      $stock = $this->openstock($trno, $config);

      $checkcosting = $this->othersClass->checkcosting($stock, $config['params']['companyid']);

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
    return $this->othersClass->unposttranstock($config);
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
    stock.sortline,
    stock.refx,
    stock.linex,
    item.barcode,
    item.itemname,
    stock.uom,
    stock.cost,
    stock.kgs,
    stock." . $this->hamt . ",
    stock." . $this->hqty . " as iss,
    FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . ") as isamt,
    FORMAT(stock." . $this->dqty . "," . $qty_dec . ")  as isqty,
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
    ifnull(uom.factor,1) as uomfactor,ifnull(stock.stageid,0) as stageid ,ifnull(st.stage,'') as stage,
    '' as bgcolor,
    '' as errcolor,

    prj.name as stock_projectname,
    prj.line as projectid,
    prj.code as project,

    stock.phaseid, ph.code as phasename,

    stock.modelid, hm.model as housemodel, 

    stock.blklotid, bl.blk, bl.lot,
    
    am.line as amenity,
    am.description as amenityname,
    subam.line as subamenity,
    subam.description as subamenityname,

    item.subcode, item.partno, round(item.dqty, " . $this->companysetup->getdecimal('qty', $config['params']) . ") as boxcount ";
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
    left join stagesmasterfile as st on st.line = stock.stageid
    
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join phase as ph on ph.line = stock.phaseid
    left join housemodel as hm on hm.line = stock.modelid
    left join blklot as bl on bl.line = stock.blklotid
    left join amenities as am on am.line= stock.amenityid
    left join subamenities as subam on subam.line=stock.subamenityid and subam.amenityid=stock.amenityid

    left join client as warehouse on warehouse.clientid=stock.whid 
    where stock.trno =?
    UNION ALL
    " . $sqlselect . "
    FROM $this->hstock as stock
    left join item on item.itemid=stock.itemid
    left join model_masterfile as mm on mm.model_id = item.model
    left join pallet on pallet.line=stock.palletid
    left join location on location.line=stock.locid
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
    left join stagesmasterfile as st on st.line = stock.stageid
    
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join phase as ph on ph.line = stock.phaseid
    left join housemodel as hm on hm.line = stock.modelid
    left join blklot as bl on bl.line = stock.blklotid
    left join amenities as am on am.line= stock.amenityid
    left join subamenities as subam on subam.line=stock.subamenityid and subam.amenityid=stock.amenityid

    left join client as warehouse on warehouse.clientid=stock.whid 
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
    left join pallet on pallet.line=stock.palletid
    left join location on location.line=stock.locid
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid 
    left join stagesmasterfile as st on st.line = stock.stageid
    
    left join projectmasterfile as prj on prj.line = stock.projectid
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
      case 'getrrsummary':
      case 'getrpsummary':
        return $this->getrrsummary($config);
        break;
      case 'getrrdetails':
      case 'getrpdetails':
        return $this->getrrdetails($config);
        break;
      case 'getpendingpickeradj':
        return $this->getpendingpickeradj($config);
        break;
      case 'getitem':
        return $this->othersClass->getmultiitem($config);
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
      case 'forapproval':
        $tablenum = $this->tablenum;
        return $this->othersClass->forapproval($config, $tablenum);
        break;
      case 'doneapproved':
        $tablenum = $this->tablenum;
        return $this->othersClass->approvedsetup($config, $tablenum);
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

  public function updatecost($config)
  {
    ini_set('max_execution_time', 0);
    $trno = $config['params']['trno'];

    $data = $this->coreFunctions->opentable("select docno, doc, trno, line, iss, cost, actualcost,actualcost * iss
      from (
      select h.docno, h.doc, s.trno, s.line, s.iss, s.cost, 
      (  select round(ifnull(sum(rs.cost * c.served) / stock.iss,0), 6) from glstock as stock left join costing as c on c.trno=stock.trno and c.line=stock.line
          left join rrstatus as rs on rs.trno=c.refx and rs.line=c.linex 
          where stock.trno=s.trno and stock.line=s.line  
          group by stock.trno,stock.line,stock.iss) as actualcost
      from glstock as s left join glhead as h on h.trno=s.trno
      where s.iss<>0 and s.trno=" . $trno . "
      ) as x where round(cost,4)<>round(actualcost,4)");

    if (!empty($data)) {
      foreach ($data as $key => $value) {
        $this->coreFunctions->execqry("update glstock set cost=" . $value->actualcost . " where trno=" . $value->trno . " and line=" . $value->line);
      }
      $this->coreFunctions->execqry("update glhead set isreentryinv=1 where trno=" . $trno);
      $this->coreFunctions->execqry("update cntnum set isok=2 where trno=" . $trno);
    }

    return ['status' => true, 'msg' => 'Finished'];
  }

  public function diagram($config)
  {

    $data = [];
    $nodes = [];
    $links = [];
    $data['width'] = 1500;
    $startx = 100;

    //DM
    $qry = "
    select head.trno, head.docno, date(head.dateid) as dateid,
    CAST(concat('Total DM Amt: ',round(sum(stock.ext),2)) as CHAR) as rem, stock.refx
    from glhead as head
    left join glstock as stock on head.trno = stock.trno
    where head.trno = ?
    group by head.trno,head.docno,head.dateid,stock.refx
    union all
    select head.trno, head.docno, date(head.dateid) as dateid,
    CAST(concat('Total DM Amt: ',round(sum(stock.ext),2)) as CHAR) as rem, stock.refx
    from lahead as head
    left join lastock as stock on head.trno = stock.trno
    where head.trno = ?
    group by head.trno,head.docno,head.dateid,stock.refx";
    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno'], $config['params']['trno']]);
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
          //RR
          $qry = "
          select head.docno,
          date(head.dateid) as dateid,
          CAST(concat('Total RR Amt: ', round(sum(stock.ext),2), ' - ', 'Balance: ', round(ap.bal, 2)) as CHAR) as rem,
          stock.refx, head.trno
          from glhead as head
          left join glstock as stock on head.trno = stock.trno
          left join apledger as ap on ap.trno = head.trno
          where head.trno=?
          group by head.docno, head.dateid, head.trno, ap.bal, stock.refx";
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

              //PO
              $qry = "select po.trno,po.docno,left(po.dateid,10) as dateid,
              CAST(concat('Total PO Amt: ',round(sum(s.ext),2)) as CHAR) as rem,s.refx
              from hpohead as po
              left join hpostock as s on s.trno = po.trno
              where po.trno = ?
              group by po.trno,po.docno,po.dateid,s.refx";
              $podata = $this->coreFunctions->opentable($qry, [$x[$key2]->refx]);
              if (!empty($podata)) {
                foreach ($podata as $k => $v) {
                  data_set(
                    $nodes,
                    $podata[$k]->docno,
                    [
                      'align' => 'right',
                      'x' => 200,
                      'y' => 50 + $a,
                      'w' => 250,
                      'h' => 80,
                      'type' => $podata[$k]->docno,
                      'label' => $podata[$k]->rem,
                      'color' => 'blue',
                      'details' => [$podata[$k]->dateid]
                    ]
                  );
                  array_push($links, ['from' => $x[$key2]->docno, 'to' => $podata[$k]->docno]);
                  $a = $a + 100;

                  $qry = "select po.docno,left(po.dateid,10) as dateid,
                  CAST(concat('Total PR Amt: ',round(sum(s.ext),2)) as CHAR) as rem
                  from hprhead as po left join hprstock as s on s.trno = po.trno
                  where po.trno = ?
                  group by po.docno,po.dateid";
                  $prdata = $this->coreFunctions->opentable($qry, [$podata[$k]->refx]);
                  if (!empty($prdata)) {
                    foreach ($prdata as $kk => $vv) {
                      data_set(
                        $nodes,
                        $prdata[$kk]->docno,
                        [
                          'align' => 'left',
                          'x' => 10,
                          'y' => 50 + $a,
                          'w' => 250,
                          'h' => 80,
                          'type' => $prdata[$kk]->docno,
                          'label' => $prdata[$kk]->rem,
                          'color' => 'yellow',
                          'details' => [$prdata[$kk]->dateid]
                        ]
                      );
                      array_push($links, ['from' => $podata[$k]->docno, 'to' => $prdata[$kk]->docno]);
                      $a = $a + 100;
                    }
                  }
                }
              }

              //APV
              $rrtrno = $x[$key2]->trno;
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

  public function updateperitem($config)
  {
    $config['params']['data'] = $config['params']['row'];
    $isupdate = $this->additem('update', $config);
    $data = $this->openstockline($config);
    $msg = '';
    if (!$isupdate) {
      $data[0]->errcolor = 'bg-red-2';
      if ($data[0]->refx == 0) {
        $msg = ' Out of stock ';
      } else {
        $msg = ' Qty Received is Greater than RR Qty ';
      }
      return ['row' => $data, 'status' => true, 'msg' => $msg];
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
          $msg2 = ' Qty Received is Greater than RR Qty ';
        }
      }
    }
    if ($isupdate) {
      return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    } else {
      return ['inventory' => $data, 'status' => true, 'msg' => 'Please check, some items have zero qty(' . $msg1 . ' / ' . $msg2 . ')'];
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


    foreach ($data2 as $key => $value) {
      if ($data2[$key][$this->dqty] == 0) {
        $data[$key]->errcolor = 'bg-red-2';
        $status = false;
        if ($data[$key]->refx == 0) {
          $msg = 'Please check; some items are out of stock.';
        } else {
          $msg = ' Qty Received is Greater than RR Qty ';
        }
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
      $lprice = $this->getlatestprice($config);
      $lprice = json_decode(json_encode($lprice), true);

      if (!empty($lprice['data'])) {
        $item[0]->amt = $lprice['data'][0]['amt'];
        $item[0]->disc = $lprice['data'][0]['disc'];
      }

      $config['params']['data'] = json_decode(json_encode($item[0]), true);
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
    $isproject = $this->companysetup->getisproject($config['params']);
    $systype = $this->companysetup->getsystemtype($config['params']);

    $uom = $config['params']['data']['uom'];
    $itemid = $config['params']['data']['itemid'];
    $trno = $config['params']['trno'];
    $disc = isset($config['params']['data']['disc']) ? $config['params']['data']['disc'] : "";
    $wh = isset($config['params']['data']['wh']) ? $config['params']['data']['wh'] : "";
    $loc = isset($config['params']['data']['loc']) ? $config['params']['data']['loc'] : "";
    $expiry = isset($config['params']['data']['expiry']) ? $config['params']['data']['expiry'] : "";
    if ($expiry == null) {
      $expiry = '';
    }

    if ($this->companysetup->getiskgs($config['params'])) {
      $kgs = isset($config['params']['data']['kgs']) ? $config['params']['data']['kgs'] : 1;
    } else {
      $kgs = 0;
    }

    $refx = 0;
    $linex = 0;
    $ref = '';
    $palletid = 0;
    $stageid = 0;
    $locid = 0;
    $projectid = 0;
    $reqtrno = 0;
    $reqline = 0;

    $phaseid = 0;
    $modelid = 0;
    $blklotid = 0;
    $amenityid = 0;
    $subamenityid = 0;

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

    if (isset($config['params']['data']['stageid'])) {
      $stageid = $config['params']['data']['stageid'];
    }

    if (isset($config['params']['data']['projectid'])) {
      $projectid = $config['params']['data']['projectid'];
    }

    $rem = '';
    if (isset($config['params']['data']['rem'])) {
      $rem = $config['params']['data']['rem'];
    }

    if (isset($config['params']['data']['reqtrno'])) {
      $reqtrno = $config['params']['data']['reqtrno'];
    }
    if (isset($config['params']['data']['reqline'])) {
      $reqline = $config['params']['data']['reqline'];
    }



    if ($systype == 'REALESTATE') {

      if (isset($config['params']['data']['projectid'])) {
        $projectid = $config['params']['data']['projectid'];
      }
      if (isset($config['params']['data']['phaseid'])) {
        $phaseid = $config['params']['data']['phaseid'];
      }
      if (isset($config['params']['data']['modelid'])) {
        $modelid = $config['params']['data']['modelid'];
      }
      if (isset($config['params']['data']['blklotid'])) {
        $blklotid = $config['params']['data']['blklotid'];
      }
      if (isset($config['params']['data']['amenityid'])) {
        $amenityid = $config['params']['data']['amenityid'];
      }
      if (isset($config['params']['data']['subamenityid'])) {
        $subamenityid = $config['params']['data']['subamenityid'];
      }

      if ($projectid == 0) {
        $projectid = $this->coreFunctions->getfieldvalue($this->head, "projectid", "trno=?", [$trno]);
      }
      if ($phaseid == 0) {
        $phaseid = $this->coreFunctions->getfieldvalue($this->head, "phaseid", "trno=?", [$trno]);
      }
      if ($modelid == 0) {
        $modelid = $this->coreFunctions->getfieldvalue($this->head, "modelid", "trno=?", [$trno]);
      }
      if ($blklotid == 0) {
        $blklotid = $this->coreFunctions->getfieldvalue($this->head, "blklotid", "trno=?", [$trno]);
      }
      if ($amenityid == 0) {
        $amenityid = $this->coreFunctions->getfieldvalue($this->head, "amenityid", "trno=?", [$trno]);
      }
      if ($subamenityid == 0) {
        $subamenityid = $this->coreFunctions->getfieldvalue($this->head, "subamenityid", "trno=?", [$trno]);
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
      $qty = $config['params']['data']['qty'];

      if ($companyid == 10) { //afti
        if ($projectid == 0) {
          $projectid = $this->coreFunctions->getfieldvalue("item", 'projectid', 'itemid=?', [$itemid]);
        }
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

    $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor,item.isnoninv from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
    $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
    $factor = 1;
    $isnoninv = 0;
    if (!empty($item)) {
      $isnoninv = $item[0]->isnoninv;
      $item[0]->factor = $this->othersClass->val($item[0]->factor);
      if ($item[0]->factor !== 0) $factor = $item[0]->factor;
    }
    $vat = $this->coreFunctions->getfieldvalue($this->head, 'tax', 'trno=?', [$trno]);
    $forex = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
    $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);
    $qty = round($qty, $this->companysetup->getdecimal('qty', $config['params']));

    if ($companyid == 60) { //TRANSPOWER
      if ($disc != "") {
        $discper = "";
        if (!str_contains($disc, '%')) {
          $d = explode("/", $disc);
          foreach ($d as $k => $x) {
            if ($discper != "") {
              $discper .= "/";
            }

            $discper .= $x . '%';
          }
          $disc = $discper;
        }
      }
    }

    $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor, 0, 'P', $kgs);

    if ($companyid == 8) { //maxipro
      if ($loc == '') {
        $qry = "select distinct loc from rrstatus where itemid =? and whid=?";
        $stockloc = $this->coreFunctions->opentable($qry, [$itemid, $whid]);
        if (count($stockloc) == 1) {
          $loc = $stockloc[0]->loc;
        }
      }
    }

    $data = [
      'trno' => $trno,
      'line' => $line,
      'itemid' => $itemid,
      $this->damt => $amt,
      $this->hamt => number_format($computedata['amt'] * $forex, $this->companysetup->getdecimal('price', $config['params']), '.', ''),
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
      'rem' => $rem,
      'expiry' => $expiry,
      'palletid' => $palletid,
      'locid' => $locid,
      'uom' => $uom,
      'stageid' => $stageid,
      'reqtrno' => $reqtrno,
      'reqline' => $reqline
    ];


    if ($systype == 'REALESTATE') {
      $data['projectid'] = $projectid;
      $data['phaseid'] = $phaseid;
      $data['modelid'] = $modelid;
      $data['blklotid'] = $blklotid;
      $data['amenityid'] = $amenityid;
      $data['subamenityid'] = $subamenityid;
    }

    switch ($companyid) {
      case 10: //afti
      case 8: //maxipro
        $data['projectid'] = $projectid;
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

    //insert item
    if ($action == 'insert') {
      $data['encodeddate'] = $current_timestamp;
      $data['encodedby'] = $config['params']['user'];
      $data['sortline'] =  $data['line'];
      if ($isproject) {
        if ($data['stageid'] == 0) {
          $msg = 'Stage cannot be blank -' . $item[0]->barcode;
          return ['status' => false, 'msg' => $msg];
        }
      }
      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
        switch ($this->companysetup->getsystemtype($config['params'])) {
          case 'AIMS':
            if ($companyid == 0 || $companyid == 10) { //main,afti
              $stockinfo_data = [
                'trno' => $trno,
                'line' => $line,
                'rem' => $rem
              ];
              $this->coreFunctions->sbcinsert('stockinfo', $stockinfo_data);
              $this->logger->sbcwritelog(
                $trno,
                $config,
                'STOCKINFO',
                'ADD - Line:' . $line
                  . ' Notes:' . $rem
              );
            }
            break;
        }
        $havestock = true;
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' Barcode:' . $item[0]->barcode . ' Amt:' . $amt . ' Disc:' . $disc . ' WH:' . $wh . ' Ext:' . $computedata['ext'] . ' Uom:' . $uom);
        if ($isnoninv == 0) {
          if ($ispallet) {
            $cost = $this->othersClass->computecostingpallet($data['itemid'], $data['whid'], $data['locid'], $data['palletid'], $trno, $line, $data['iss'], $config['params']['doc'], $config['params']);
          } else {
            $cost = $this->othersClass->computecosting($itemid, $whid, $data['loc'], $expiry, $trno, $line, $data['iss'], $config['params']['doc'], $config['params']['companyid']);
          }
          if ($cost != -1) {
            $this->coreFunctions->sbcupdate($this->stock, ['cost' => $cost], ['trno' => $trno, 'line' => $line]);
          } else {
            $havestock = false;
            $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'OUT_STOCK', 'editdate' => $current_timestamp], ['trno' => $trno, 'line' => $line]);
            $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
            $this->logger->sbcwritelog($trno, $config, 'STOCK', 'OUT OF STOCK - Line:' . $line . ' Barcode:' . $item[0]->barcode . ' Amt:' . $amt . ' Disc:' . $disc . ' WH:' . $wh . ' Ext:0.0');
          }
        }
        if ($this->setserveditems($refx, $linex) == 0) {
          $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
          $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
          $this->setserveditems($refx, $linex);
          $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
          $return = false;
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
      if ($isnoninv == 0) {
        if ($ispallet) {
          $cost = $this->othersClass->computecostingpallet($data['itemid'], $data['whid'], $data['locid'], $data['palletid'], $trno, $line, $data['iss'], $config['params']['doc'], $config['params']);
        } else {
          $cost = $this->othersClass->computecosting($itemid, $whid, $data['loc'], $data['expiry'], $trno, $line, $data['iss'], $config['params']['doc'], $config['params']['companyid']);
        }
        if ($cost != -1) {
          $this->coreFunctions->sbcupdate($this->stock, ['cost' => $cost], ['trno' => $trno, 'line' => $line]);
        } else {
          $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'OUT_STOCK', 'editdate' => $current_timestamp], ['trno' => $trno, 'line' => $line]);
          $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
          $this->setserveditems($refx, $linex);
          $this->logger->sbcwritelog($trno, $config, 'STOCK', 'OUT OF STOCK - Line:' . $line . ' Barcode:' . $item[0]->barcode . ' Amt:' . $amt . ' Disc:' . $disc . ' WH:' . $wh . ' Ext:0.0');
          $return = false;
        }
      }
      if ($this->setserveditems($refx, $linex) == 0) {
        $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
        $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
        $this->setserveditems($refx, $linex);
        $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
        $return = false;
      }

      return $return;
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
    $this->setservedsplitqtyitems(0, 0, $trno, true);
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
    stock on stock.trno=head.trno where head.doc='DM' and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry1 = $qry1 . " union all select glstock." . $this->hqty . " from glhead left join glstock on glstock.trno=
    glhead.trno where glhead.doc='DM' and glstock.refx=" . $refx . " and glstock.linex=" . $linex;

    $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty == '') {
      $qty = 0;
    }
    return $this->coreFunctions->execqry("update rrstatus set qa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
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

    if ($this->companysetup->getispallet($config['params'])) {
      $this->setservedsplitqtyitems($data[0]->refx, $data[0]->linex, 0, false);
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
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' Barcode:' . $data[0]->barcode . ' Qty:' . $data[0]->isqty . ' Amt:' . $data[0]->isamt . ' Disc:' . $data[0]->disc . ' WH:' . $data[0]->wh . ' Ext:' . $data[0]->ext);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
  } // end function

  public function getlatestprice($config)
  {
    $barcode = $config['params']['barcode'];
    $client = $config['params']['client'];
    $center = $config['params']['center'];

    if ($config['params']['companyid'] == 56) { //homeworks
      $supplier = $this->coreFunctions->opentable("select 'ITEM' as docno, left(client.client,3) as supplier, item.amt, item.avecost, '' as disc, item.uom from item left join client on client.clientid=item.supplier where barcode=?", [$barcode]);
      if (!empty($supplier)) {
        if ($supplier[0]->supplier == '161') {  // (outright) - base sa cost default ng items
          $supplier[0]->amt = $supplier[0]->avecost;
        }
        return ['status' => true, 'msg' => 'Found the latest purchase price...', 'data' => $supplier];
      }
    }

    $qry = "select docno,left(dateid,10) as dateid,round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,disc,uom from(select head.docno,head.dateid,
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
          order by dateid desc limit 5) as tbl order by dateid desc";

    $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $client, $center, $barcode, $client]);
    if (!empty($data)) {
      return ['status' => true, 'msg' => 'Found the latest purchase price...', 'data' => $data];
    } else {
      return ['status' => false, 'msg' => 'No Latest price found...'];
    }
  } // end function


  public function getrrsummary($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];

    $systype = $this->companysetup->getsystemtype($config['params']);

    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.cost,
        (stock.qty-stock.qa) as qty,stock.rrcost,
        round((stock.qty-rrstatus.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
        stock.disc,ifnull(stock.loc,'') as loc,ifnull(stock.expiry,'') as expiry,
        stock.palletid,stock.locid,ifnull(pallet.name,'') as pallet, ifnull(location.loc,'') as location,
        stock.reqtrno,stock.reqline,
        stock.projectid,stock.phaseid, stock.modelid, stock.blklotid, stock.amenityid, stock.subamenityid
        FROM glhead as head 
        left join glstock as stock on stock.trno=head.trno
        left join item on item.itemid=stock.itemid
        left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
        left join rrstatus on rrstatus.trno=stock.trno and rrstatus.line=stock.line
        left join pallet on pallet.line=stock.palletid
        left join location on location.line=stock.locid
        where stock.trno = ? and stock.qty>rrstatus.qa and stock.void=0
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
          $config['params']['data']['loc'] = $data[$key2]->loc;
          $config['params']['data']['expiry'] = $data[$key2]->expiry;
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->rrcost;
          $config['params']['data']['palletid'] = $data[$key2]->palletid;
          $config['params']['data']['locid'] = $data[$key2]->locid;
          $config['params']['data']['projectid'] = $data[$key2]->projectid;
          $config['params']['data']['reqtrno'] = $data[$key2]->reqtrno;
          $config['params']['data']['reqline'] = $data[$key2]->reqline;
          if ($systype == 'REALESTATE') {

            $config['params']['data']['projectid'] = $data[$key2]->projectid;
            $config['params']['data']['phaseid'] = $data[$key2]->phaseid;
            $config['params']['data']['modelid'] = $data[$key2]->modelid;
            $config['params']['data']['blklotid'] = $data[$key2]->blklotid;
            $config['params']['data']['amenityid'] = $data[$key2]->amenityid;
            $config['params']['data']['subamenityid'] = $data[$key2]->subamenityid;
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
    return ['row' => $rows, 'status' => true, 'msg' => $return['msg']];
  } //end function


  public function getrrdetails($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];

    $systype = $this->companysetup->getsystemtype($config['params']);

    $rows = [];
    $addfield = '';
    $leftjoin = '';
    if ($config['params']['companyid'] == 8) { //maxipro
      $addfield = ' , stock.stageid,st.stage, head.projectid ';
      $leftjoin = ' left join stagesmasterfile as st on st.line = stock.stageid ';
    } else {
      $addfield = ' , stock.projectid ';
    }
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "select head.docno, item.itemid,stock.trno,stock.line, item.barcode,stock.uom, stock.cost,
                    (stock.qty-rrstatus.qa) as qty,stock.rrcost,
                    round((stock.qty-rrstatus.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end,
                    " . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
                    stock.disc,ifnull(stock.loc,'') as loc,ifnull(stock.expiry,'') as expiry,
                    stock.palletid,stock.locid,ifnull(pallet.name,'') as pallet, 
                    ifnull(location.loc,'') as location $addfield ,stock.reqtrno,stock.reqline,
                    stock.phaseid, stock.modelid, stock.blklotid, stock.amenityid, stock.subamenityid
              FROM glhead as head left join glstock as stock on stock.trno=head.trno
              left join item on item.itemid=stock.itemid
              left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
              left join rrstatus on rrstatus.trno=stock.trno and rrstatus.line=stock.line
              left join pallet on pallet.line=stock.palletid
              left join location on location.line=stock.locid $leftjoin
              where stock.trno = ? and stock.line=? and stock.qty>rrstatus.qa and stock.void=0";
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line']]);
      if (!empty($data)) {
        foreach ($data as $key2 => $value) {

          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['qty'] = $data[$key2]->rrqty;
          $config['params']['data']['wh'] = $wh;
          $config['params']['data']['loc'] = $data[$key2]->loc;
          $config['params']['data']['expiry'] = $data[$key2]->expiry;
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->rrcost;
          $config['params']['data']['palletid'] = $data[$key2]->palletid;
          $config['params']['data']['locid'] = $data[$key2]->locid;
          $config['params']['data']['projectid'] = $data[$key2]->projectid;
          $config['params']['data']['reqtrno'] = $data[$key2]->reqtrno;
          $config['params']['data']['reqline'] = $data[$key2]->reqline;
          if ($config['params']['companyid'] == 8) { //maxipro
            $config['params']['data']['stageid'] = $data[$key2]->stageid;
          }
          if ($systype == 'REALESTATE') {
            $config['params']['data']['projectid'] = $data[$key2]->projectid;
            $config['params']['data']['phaseid'] = $data[$key2]->phaseid;
            $config['params']['data']['modelid'] = $data[$key2]->modelid;
            $config['params']['data']['blklotid'] = $data[$key2]->blklotid;
            $config['params']['data']['amenityid'] = $data[$key2]->amenityid;
            $config['params']['data']['subamenityid'] = $data[$key2]->subamenityid;
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

    return ['row' => $rows, 'status' => true, 'msg' => $return['msg']];
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
      $config['params']['data']['qty'] = $value['isqty'];
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
    $periodic = $this->companysetup->getisperiodic($config['params']);
    $status = true;
    $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);
    if ($companyid == 10) { //afti
      $qry = 'select head.dateid,head.client,head.tax,head.contra,head.cur,head.forex,stock.ext,wh.client as wh,ifnull(a.acno,"") as asset,ifnull(r.acno,"") as revenue,stock.isamt,stock.disc,stock.isqty,stock.cost,stock.iss,stock.fcost,stock.projectid,head.branch,head.deptid
          from ' . $this->head . ' as head left join ' . $this->stock . ' as stock on stock.trno=head.trno
          left join client as wh on wh.clientid=stock.whid
          left join item on item.itemid=stock.itemid left join projectmasterfile as p on p.line = stock.projectid 
          left join coa as a on a.acnoid = p.assetid left join coa as r on r.acnoid = p.revenueid where head.trno=?';
    } else {
      $qry = 'select head.dateid,head.client,head.tax,head.contra,head.cur,head.forex,stock.ext,wh.client as wh,ifnull(item.asset,"") as asset,ifnull(item.revenue,"") as revenue,stock.isamt,stock.disc,stock.isqty,stock.cost,stock.iss,stock.fcost,head.projectid
          from ' . $this->head . ' as head left join ' . $this->stock . ' as stock on stock.trno=head.trno
          left join client as wh on wh.clientid=stock.whid
          left join item on item.itemid=stock.itemid where head.trno=?';
    }

    $stock = $this->coreFunctions->opentable($qry, [$trno]);
    $tax = 0;
    if (!empty($stock)) {
      if (!$periodic) {
        $invacct = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['IN1']);
      } else {
        $invacct = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['PR1']);
      }

      $vat = floatval($stock[0]->tax);
      $tax1 = 0;
      $tax2 = 0;
      if ($vat !== 0) {
        $tax1 = 1 + ($vat / 100);
        $tax2 = $vat / 100;
      }
      foreach ($stock as $key => $value) {
        $params = [];
        $disc = $stock[$key]->isamt - ($this->othersClass->discount($stock[$key]->isamt, $stock[$key]->disc));
        if ($vat !== 0) {

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
          'tax' =>  $tax,
          'discamt' => $disc * $stock[$key]->iss,
          'cur' => $stock[$key]->cur,
          'forex' => $stock[$key]->forex,
          'cost' => round($stock[$key]->cost * $stock[$key]->iss, 2),
          'fcost' => round($stock[$key]->fcost * $stock[$key]->iss, 2),
          'projectid' => $stock[$key]->projectid,
        ];
        if ($companyid == 10) { //afti
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
        $status = true;
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
    $companyid = $config['params']['companyid'];
    $forex = $params['forex'];
    $cur = $params['cur'];
    $periodic = $this->companysetup->getisperiodic($config['params']);

    if (!$this->companysetup->getispurchasedisc($config['params'])) {
      $params['discamt'] = 0;
    }

    //AP
    if (floatval($params['ext']) != 0) {
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['acno']]);
      $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'db' => ($params['ext'] * $forex), 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => floatval($forex) == 1 ? 0 : $params['ext'], 'fcr' => 0, 'projectid' => $params['projectid']];
      if ($companyid == 10) { //afti
        $entry['branch'] = $params['branch'];
        $entry['deptid'] = $params['deptid'];
        $entry['projectid'] = 0;
      }
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }


    //disc
    if (floatval($params['discamt']) != 0) {
      $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['PD1']);
      if ($params['discamt'] < 0) {
        $entry = ['acnoid' => $input, 'client' => $params['client'], 'cr' => abs($params['discamt'] * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($params['discamt']), 'projectid' => $params['projectid']];
        if ($companyid == 10) { //afti
          $entry['branch'] = $params['branch'];
          $entry['deptid'] = $params['deptid'];
        }
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      } else {
        $entry = ['acnoid' => $input, 'client' => $params['client'], 'db' => ($params['discamt'] * $forex), 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($params['discamt']), 'projectid' => $params['projectid']];
        if ($companyid == 10) { //afti
          $entry['branch'] = $params['branch'];
          $entry['deptid'] = $params['deptid'];
        }
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }
    }

    if ($periodic) {
      //INV
      if (floatval($params['ext']) != 0) {
        $inv = ($params['ext'] + $params['discamt']) - $params['tax'];
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['inventory']]);
        $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'db' => 0, 'cr' => round($inv, 2), 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : $params['fcost'], 'fdb' => 0, 'projectid' => $params['projectid']];
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }
    } else {
      //INV
      if (floatval($params['cost']) != 0) {
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['inventory']]);
        $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'db' => 0, 'cr' => $params['cost'], 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : $params['fcost'], 'fdb' => 0, 'projectid' => $params['projectid']];
        if ($companyid == 10) { //afti
          $entry['branch'] = $params['branch'];
          $entry['deptid'] = $params['deptid'];
        }
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }
    }


    if (floatval($params['tax']) != 0) {
      // input tax
      $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['TX1']);
      $entry = ['acnoid' => $input, 'client' => $params['client'], 'cr' => ($params['tax'] * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : ($params['tax']), 'fdb' => 0, 'projectid' => $params['projectid']];
      if ($companyid == 10) { //afti
        $entry['branch'] = $params['branch'];
        $entry['deptid'] = $params['deptid'];
        $entry['projectid'] = 0;
      }
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }

    if (!$periodic) {

      $variance = (($params['ext'] * $forex) - $params['cost']);
      $variance = $variance + (($params['discamt'] * $forex) - $params['tax'] * $forex);

      $fvariance = $params['ext'] - $params['fcost'];
      $fvariance = $fvariance + ($params['discamt'] - $params['tax']);

      if (floatval($variance) != 0) {
        // input tax
        $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['GLC']);
        if (floatval($variance) > 0) {
          $entry = ['acnoid' => $input, 'client' => $params['client'], 'cr' => abs($variance), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : abs($fvariance), 'fdb' => 0, 'projectid' => $params['projectid']];
          if ($companyid == 10) { //afti
            $entry['branch'] = $params['branch'];
            $entry['deptid'] = $params['deptid'];
          }
        } else {
          $entry = ['acnoid' => $input, 'client' => $params['client'], 'db' => abs($variance), 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => floatval($forex) == 1 ? 0 : abs($fvariance), 'fcr' => 0, 'projectid' => $params['projectid']];
          if ($companyid == 10) { //afti
            $entry['branch'] = $params['branch'];
            $entry['deptid'] = $params['deptid'];
          }
        }
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }
    }

    if (floatval($params['cost']) != 0) {
      if ($companyid == 3) { //conti
        //Cost of GOODS
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['CG1']);
        $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'db' => $params['cost'], 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : $params['fcost'], 'fdb' => 0, 'projectid' => $params['projectid']];
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

        //Purhase Return
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['PD1']);
        $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'db' => 0, 'cr' => $params['cost'], 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : $params['fcost'], 'fdb' => 0, 'projectid' => $params['projectid']];
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }
    }
  } //end function

  // report startto

  public function reportsetup($config)
  {
    $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
    $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);

    $modulename = $this->modulename;
    $data = [];
    $isreload = false;
    if ($config['params']['companyid'] == 60) { //transpower
      $this->posttrans($config);
      $isreload = true;
    }
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false, 'reloadhead' => $isreload];
  }

  public function reportdata($config)
  {
    $companyid = $config['params']['companyid'];
    $this->logger->sbcviewreportlog($config);

    switch ($companyid) {
      case 39: // CBBSI
        $dataparams = $config['params']['dataparams'];
        if (isset($dataparams['received'])) $this->othersClass->writeSignatories($config, 'received', $dataparams['received']);
        if (isset($dataparams['approved'])) $this->othersClass->writeSignatories($config, 'approved', $dataparams['approved']);
        // if (isset($dataparams['prepared'])) $this->othersClass->writeSignatories($config, 'prepared', $dataparams['prepared']);
      case 40: // cdo
        $dataparams = $config['params']['dataparams'];
        if (isset($dataparams['prepared'])) $this->othersClass->writeSignatories($config, 'prepared', $dataparams['prepared']);
        if (isset($dataparams['approved'])) $this->othersClass->writeSignatories($config, 'approved', $dataparams['approved']);
        if (isset($dataparams['received'])) $this->othersClass->writeSignatories($config, 'received', $dataparams['received']);
        break;
    }

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
      $computedata = $this->othersClass->computestock(
        $data2[$key][$this->damt] * $head['forex'],
        $data[$key]->disc,
        round($data2[$key][$this->dqty], $this->companysetup->getdecimal('qty', $config['params'])),
        $data[$key]->uomfactor,
        0
      );
      $exec = $this->coreFunctions->execqry("update lastock set amt = " . $computedata['amt'] . " where trno = " . $head['trno'] . " and line=" . $data[$key]->line, "update");
    }
    return $exec;
  }
} //end class
