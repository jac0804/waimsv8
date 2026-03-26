<?php

namespace App\Http\Classes\modules\purchase;

use Illuminate\Http\Request;
use DB;
use Session;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Milon\Barcode\DNS1D;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\builder\helpClass;
use App\Http\Classes\modules\calendar\em;
use Exception;

class ru
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'MATERIAL RECEIVING REPORT';
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
  public $defaultContra = 'AP1';

  private $fields = ['trno', 'docno', 'dateid', 'due', 'client', 'clientname', 'yourref', 'ourref', 'rem', 'terms', 'forex', 'cur', 'wh', 'address', 'contra', 'tax', 'vattype', 'projectid', 'subproject', 'branch', 'deptid', 'billid', 'shipid', 'billcontactid', 'shipcontactid', 'invoiceno', 'invoicedate', 'ewt', 'ewtrate', 'driver', 'plateno', 'cur2', 'forex2'];
  private $except = ['trno', 'dateid', 'due'];
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
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 3939,
      'edit' => 3940,
      'new' => 3941,
      'save' => 3942,
      // 'change' => 83, remove change doc
      'delete' => 3943,
      'print' => 3944,
      'lock' => 3945,
      'unlock' => 3946,
      'acctg' => 3949,
      'changeamt' => 3950,
      'post' => 3947,
      'unpost' => 3948,
      'additem' => 3951,
      'edititem' => 3952,
      'deleteitem' => 3953
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

    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'yourref', 'ourref', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];

    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$listclientname]['style'] = 'width:350px;whiteSpace: normal;min-width:350px;';

    $cols[5]['align'] = 'text-left';
    $cols[6]['align'] = 'text-left';
    $cols[7]['label'] = 'Post Date';
    return $cols;
  }

  public function paramsdatalisting($config)
  {
    $companyid = $config['params']['companyid'];
    $isshortcutpo = $this->companysetup->getisshortcutpo($config['params']);

    $fields = [];
    if ($isshortcutpo) {
      $allownew = $this->othersClass->checkAccess($config['params']['user'], 81);
      if ($allownew == '1') {
        array_push($fields, 'pickpo');
      }
    }
    $col1 = $this->fieldClass->create($fields);
    $data = [];
    $fields = [];
    $col2 = $this->fieldClass->create($fields);

    return ['status' => true, 'data' => $data, 'txtfield' => ['col1' => $col1, 'col2' => $col2]];
  }

  public function loaddoclisting($config)
  {
    $isproject = $this->companysetup->getisproject($config['params']);
    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));
    $itemfilter = $config['params']['itemfilter'];
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];

    $condition = '';
    $projectfilter = '';
    $limit = '';
    $fields = '';
    $searchfilter = $config['params']['search'];

    $join = '';
    $hjoin = '';
    $addparams = '';

    $balfilter = '';
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

    switch ($itemfilter) {
      case 'draft':
        $condition = ' and num.postdate is null ';
        if ($companyid == 3) { //conti
          $condition = ' and num.postdate is null and head.lockdate is null ';
        }
        break;

      case 'locked':
        $condition = ' and num.postdate is null and head.lockdate is not null ';
        $status = "'LOCKED'";
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
    $companyid = $config['params']['companyid'];
    $status = "'POSTED'";
    $lstatus = "'DRAFT'";
    $lstatcolor = "'red'";
    $gstatcolor = "'grey'";

    $fields .= ",left(head.dateid,10) as dateid";
    if ($searchfilter == "") $limit = 'limit 150';
    $orderby =  "order by  dateid desc, docno desc";

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
            $join .= " left join lastock on lastock.trno = head.trno
            left join item on item.itemid = lastock.itemid left join item as item2 on item2.itemid = lastock.itemid
            left join model_masterfile as model on model.model_id = item.model 
            left join model_masterfile as model2 on model2.model_id = item2.model 
            left join frontend_ebrands as brand on brand.brandid = item.brand 
            left join frontend_ebrands as brand2 on brand2.brandid = item2.brand
            left join projectmasterfile as p on p.line = item.projectid 
            left join projectmasterfile as p2 on p2.line = item2.projectid ";

            $hjoin .= " left join glstock on glstock.trno = head.trno
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

    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'head.clientname', 'head.createby', 'head.editby', 'head.viewby', 'num.postedby', 'head.yourref', 'head.ourref'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    } else {
      $limit = 'limit 25';
    }

    $qry = "select head.trno,head.docno,head.clientname, 'DRAFT' as status,$lstatcolor as statuscolor,
    head.createby,head.editby,head.viewby,num.postedby, date(num.postdate) as postdate,
     head.yourref, head.ourref $fields
     from " . $this->head . " as head left join " . $this->tablenum . " as num
     on num.trno=head.trno 
     " . $join . "
     where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $projectfilter . $condition . $addparams . " $filtersearch
     union all
     select head.trno,head.docno,head.clientname,$status as status,$gstatcolor as statuscolor,
     head.createby,head.editby,head.viewby, num.postedby, date(num.postdate) as postdate,
      head.yourref, head.ourref $fields
     from " . $this->hhead . " as head left join " . $this->tablenum . " as num
     on num.trno=head.trno 
     " . $hjoin . "
     where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $projectfilter . $condition . $addparams . $filtersearch . $balfilter . $groupby . $having . "
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
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => 'rr', 'title' => 'RR_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
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
    $isexpiry = $this->companysetup->getisexpiry($config['params']);
    $isproject = $this->companysetup->getisproject($config['params']);
    $ispallet = $this->companysetup->getispallet($config['params']);
    $isfa = $this->companysetup->getisfixasset($config['params']);
    $iskgs = $this->companysetup->getiskgs($config['params']);
    $viewcost = $this->othersClass->checkAccess($config['params']['user'], 368);
    $rr_btnreceived_access = $this->othersClass->checkAccess($config['params']['user'], 2728);
    $rr_btnunreceived_access = $this->othersClass->checkAccess($config['params']['user'], 2729);
    $makecv = $this->othersClass->checkAccess($config['params']['user'], 3577);
    $isserial = $this->companysetup->getserial($config['params']);

    $action = 0;
    $itemdesc = 1;
    $serial = 2;
    $rrqty = 3;
    $uom = 4;
    $kgs = 5;
    $rrcost = 6;
    $disc = 7;
    $cost = 8;
    $ext = 9;
    $freight = 10;
    $wh = 11;
    $whname = 12;
    $ref = 13;
    $poref = 14;
    $rem = 15;
    $loc = 16;
    $expiry = 17;
    $stage = 18;
    $pallet = 19;
    $location = 20;
    $itemname = 21;
    $barcode = 22;
    $stock_projectname = 23;
    $partno = 24;
    $subcode = 25;
    $boxcount = 26;

    $column = [
      'action',
      'itemdescription',
      'serialno',
      'rrqty',
      'uom',
      'kgs',
      'rrcost',
      'disc',
      'cost',
      'ext',
      'freight',
      'wh',
      'whname',
      'ref',
      'poref',
      'rem',
      'loc',
      'expiry',
      'stage',
      'pallet',
      'location',
      'itemname',
      'barcode',
      'stock_projectname',
      'partno',
      'subcode',
      'boxcount'
    ];

    $sortcolumn =  [
      'action',
      'itemdescription',
      'serialno',
      'rrqty',
      'uom',
      'kgs',
      'rrcost',
      'disc',
      'cost',
      'ext',
      'freight',
      'wh',
      'whname',
      'ref',
      'poref',
      'rem',
      'loc',
      'expiry',
      'stage',
      'pallet',
      'location',
      'itemname',
      'barcode',
      'stock_projectname',
      'partno',
      'subcode',
      'boxcount'
    ];

    $headgridbtns = ['viewref', 'viewdiagram'];

    if ($isfa) {
      array_push($headgridbtns, 'generatedepsched');
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
      $stockbuttons = ['save', 'delete', 'serialin'];
    } else {
      $stockbuttons = ['save', 'delete', 'showbalance'];
    }

    if ($this->companysetup->getiseditsortline($config['params'])) {
      array_push($stockbuttons, 'sortline');
    }

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0]['inventory']['columns'][$barcode]['type'] = 'hidden';
    $obj[0]['inventory']['columns'][$barcode]['label'] = '';

    if ($viewcost == '0') {
      $obj[0]['inventory']['columns'][$rrcost]['type'] = 'coldel';
    }

    $obj[0]['inventory']['columns'][$rrcost]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px';

    if (!$isexpiry) {
      $obj[0]['inventory']['columns'][$loc]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$expiry]['type'] = 'coldel';
    } else {
      $obj[0]['inventory']['columns'][$expiry]['type'] = 'date';
    }

    $obj[0]['inventory']['columns'][$kgs]['label'] = 'Buying Kgs';
    if (!$iskgs) {
      $obj[0]['inventory']['columns'][$kgs]['type'] = 'coldel';
    }

    if (!$isproject) {
      $obj[0]['inventory']['columns'][$stage]['type'] = 'coldel';
    }

    $obj[0]['inventory']['columns'][$pallet]['type'] = 'coldel';
    if (!$ispallet) {
      $obj[0]['inventory']['columns'][$location]['type'] = 'coldel';
    }

    $obj[0]['inventory']['columns'][$ref]['lookupclass'] = 'refrr';

    if (!$access['changeamt']) {
      $obj[0]['inventory']['columns'][$rrcost]['readonly'] = true;
      $obj[0]['inventory']['columns'][$disc]['readonly'] = true;
    }

    $obj[0]['inventory']['columns'][$stage]['readonly'] = true;

    $obj[0]['inventory']['columns'][$stock_projectname]['type'] = 'coldel';
    $obj[0]['inventory']['columns'][$poref]['type'] = 'coldel';
    $obj[0]['inventory']['columns'][$serial]['type'] = 'coldel';
    $obj[0]['inventory']['columns'][$whname]['type'] = 'coldel';

    $obj[0]['inventory']['columns'][$itemdesc]['type'] = 'coldel';

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

    $obj[0]['inventory']['columns'][$partno]['type'] = 'coldel';
    $obj[0]['inventory']['columns'][$subcode]['type'] = 'coldel';
    $obj[0]['inventory']['columns'][$boxcount]['type'] = 'coldel';

    if ($isexpiry) {
      $obj[0]['inventory']['columns'][$expiry]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px';
    }

    $obj[0]['inventory']['columns'][$rem]['type'] = 'coldel';

    $obj[0]['inventory']['columns'][$cost]['style'] = 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px';
    $obj[0]['inventory']['columns'][$loc]['label'] = 'Batch No';
    $obj[0]['inventory']['columns'][$loc]['type'] = 'input';
    $obj[0]['inventory']['columns'][$loc]['readonly'] = false;

    $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $isproject = $this->companysetup->getisproject($config['params']);
    $isserial = $this->companysetup->getserial($config['params']);
    $companyid = $config['params']['companyid'];

    $tbuttons = ['pendingpo', 'additem', 'quickadd', 'saveitem', 'deleteallitem'];

    if ($isproject) {
      $viewall = $this->othersClass->checkAccess($config['params']['user'], 2232);
      if ($viewall == '0') {
        $tbuttons = ['pendingpo', 'saveitem', 'deleteallitem'];
      }
    }

    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['label'] = "PICK PU";
    return $obj;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];
    $fields = ['docno', 'client', 'clientname', 'address'];

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.label', 'Vendor');
    data_set($col1, 'docno.label', 'Transaction#');

    $fields = [['dateid', 'terms'], ['due', 'dvattype'], 'dacnoname', 'dwhname'];

    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'dacnoname.label', 'AP Account');
    data_set($col2, 'dwhname.condition', ['checkstock']);


    $fields = [['yourref', 'ourref'], ['cur', 'forex'], 'dprojectname', 'hauler'];

    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'hauler.name', 'haulersupplier');
    data_set($col3, 'hauler.label', 'Hauler Supplier');

    if ($this->companysetup->getisproject($config['params'])) {
      $viewall = $this->othersClass->checkAccess($config['params']['user'], 2232);

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
        data_set($col4, 'rem.style', 'height: 130px; max-width: 400px;font-size:120%;');
        data_set($col4, 'subprojectname.required', false);
      } else {
        data_set($col3, 'dprojectname.type', 'input');
        $fields = ['rem', 'subprojectname'];
        if ($this->companysetup->getistodo($config['params'])) {
          array_push($fields, 'donetodo');
        }
        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'rem.style', 'height: 130px; max-width: 400px;font-size:120%;');
        data_set($col4, 'subprojectname.type', 'lookup');
        data_set($col4, 'subprojectname.lookupclass', 'lookupsubproject');
        data_set($col4, 'subprojectname.action', 'lookupsubproject');
        data_set($col4, 'subprojectname.addedparams', ['projectid']);
        data_set($col4, 'subprojectname.required', true);
      }
    } else {
      $fields = ['rem', 'haulerrate'];
      if ($this->companysetup->getistodo($config['params'])) {
        array_push($fields, 'donetodo');
      }
      $col4 = $this->fieldClass->create($fields);
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
    $data[0]['terms'] = '';
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
      $viewall = $this->othersClass->checkAccess($params['user'], 2232);
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
    $data[0]['subproject'] = '0';
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
    $data[0]['invoiceno'] = '';
    $data[0]['invoicedate'] = $this->othersClass->getCurrentDate();
    $data[0]['ewt'] = '';
    $data[0]['dewt'] = '';
    $data[0]['ewtrate'] = 0;
    $data[0]['driver'] = '';
    $data[0]['plateno'] = '';
    $data[0]['forex2'] = 1;
    $data[0]['cur2'] = $this->companysetup->getdefaultcurrency($params);

    $data[0]['haulersupplier'] = '';
    $data[0]['haulerrate'] = 0;
    return $data;
  }

  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $trno = $config['params']['trno'];
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];
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
      $viewall = $this->othersClass->checkAccess($config['params']['user'], 2232);
      $project = $this->coreFunctions->getfieldvalue("useraccess", "project", "username=?", [$config['params']['user']]);
      $projectid = $this->coreFunctions->getfieldvalue("projectmasterfile", "line", "code=?", [$project]);
      if ($viewall == '0') {
        $projectfilter = " and head.projectid = " . $projectid . " ";
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
         head.billid,
         head.shipid,
         head.billcontactid,
         head.shipcontactid,
         '' as dvattype,
         warehouse.client as wh,
         warehouse.clientname as whname,
         '' as dwhname,
         head.projectid,
         '' as dprojectname,
         left(head.due,10) as due,
         client.groupid,ifnull(p.code,'') as projectcode,ifnull(p.name,'') as projectname,ifnull(s.line,0) as subproject,ifnull(s.subproject,'') as subprojectname,
         head.branch,ifnull(b.clientname,'') as branchname,ifnull(b.client,'') as branchcode,'' as dbranchname,ifnull(d.client,'') as dept,ifnull(d.clientname,'') as deptname,
         head.deptid,'' as ddeptname,head.invoiceno,left(head.invoicedate,10) as invoicedate,head.ewt,head.ewtrate,
         head.driver,head.plateno,head.cur2,head.forex2,
         ifnull(hinfo.haulersupplier,'') as haulersupplier,
         ifnull(hinfo.haulerrate,'') as haulerrate";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as b on b.clientid = head.branch
        left join coa on coa.acno=head.contra
        left join projectmasterfile as p on p.line=head.projectid
        left join client as d on d.clientid = head.deptid
        left join subproject as s on s.line = head.subproject
        left join cntnuminfo as hinfo on hinfo.trno = head.trno
        where head.trno = ? and num.doc=? and num.center = ? " . $projectfilter . "
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.clientid = client.clientid
        left join client as warehouse on warehouse.clientid = head.whid
        left join client as b on b.clientid = head.branch
        left join coa on coa.acno=head.contra
        left join projectmasterfile as p on p.line=head.projectid
        left join client as d on d.clientid = head.deptid
        left join subproject as s on s.line = head.subproject
        left join hcntnuminfo as hinfo on hinfo.trno = head.trno
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

      $receivedby = $this->coreFunctions->datareader("select receivedby as value from cntnum  where trno=?", [$trno]);

      $lblreceived_stat = $receivedby == "" ? true : false;
      $hideobj = ['lblreceived' => $lblreceived_stat];
      $hideheadergridbtns = ['tagreceived' => !$lblreceived_stat, 'untagreceived' => $lblreceived_stat];

      if ($this->companysetup->getistodo($config['params'])) {
        $btndonetodo = $this->othersClass->checkdonetodo($config, $tablenum);
        $hideobj['donetodo'] = !$btndonetodo;
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

    $data['due'] = $this->othersClass->computeterms($data['dateid'], $data['due'], $data['terms']);

    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    if ($data['invoicedate'] == null) {
      $data['invoicedate'] = '';
    }

    $info = [];
    $info['trno'] = $head['trno'];
    $info['haulersupplier'] = $head['haulersupplier'];
    $info['haulerrate'] = $head['haulerrate'];

    if ($isupdate) {
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
      $this->recomputecost($head, $config);

      $this->coreFunctions->sbcupdate('cntnuminfo', $info, ['trno' => $head['trno']]);
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head, $data);
      $this->coreFunctions->sbcinsert('cntnuminfo', $info);
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
    $companyid = $config['params']['companyid'];
    if (!$this->othersClass->checkserialin($config)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. There are serialized items. To proceed, please encode the serial number.'];
    }
    if ($this->companysetup->isinvonly($config['params'])) {
      return $this->othersClass->posttranstock($config);
    } else {
      $checkacct = $this->othersClass->checkcoaacct(['AP1', 'IN1', 'PD1', 'TX1']);

      if ($checkacct != '') {
        return ['trno' => $trno, 'status' => false, 'msg' => 'Accounts not yet setup:' . $checkacct];
      }

      $checkcontra = $this->coreFunctions->getfieldvalue("lahead", "contra", "trno=?", [$trno]);
      if ($checkcontra == '') {
        return ['trno' => $trno, 'status' => false, 'msg' => 'Missing AP Account'];
      }

      $return = $this->othersClass->posttranstock($config);
      if ($this->companysetup->getisproject($config['params'])) {
        if ($return['status']) {
          $data = $this->coreFunctions->opentable("select sum(a.cr-a.db) as bal,d.projectid,d.subproject,d.stageid from apledger as a left join gldetail as d on d.trno = a.trno  where a.trno =" . $trno . " group by d.projectid,d.subproject,d.stageid");
          $this->updateprojmngmtap($config, $data);
        }
      }
      return $return;
    }
  } //end function

  public function unposttrans($config)
  {
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $isfa = $this->companysetup->getisfixasset($config['params']);
    $trno = $config['params']['trno'];
    $companyid = $config['params']['companyid'];
    $data = $this->coreFunctions->opentable("select sum(a.cr-a.db) as bal,d.projectid,d.subproject,d.stageid from apledger as a left join gldetail as d on d.trno = a.trno where a.trno =" . $trno . " group by d.projectid,d.subproject,d.stageid");

    if ($isfa) {
      $isexist = $this->coreFunctions->getfieldvalue("fasched", "rrtrno", "rrtrno = ? and jvtrno <>0", [$trno]);

      if (floatval($isexist) != 0) {
        return ['status' => false, 'msg' => 'Already have posted depreciation schedule.'];
      }
    }

    $return = $this->othersClass->unposttranstock($config);
    if ($this->companysetup->getisproject($config['params'])) {
      if ($return['status']) {
        $this->updateprojmngmtap($config, $data);
      }
    }

    return $return;
  } //end function

  private function getstockselect($config)
  {
    $companyid = $config['params']['companyid'];
    if ($companyid == 24) {
      $qty_dec = 3;
    } else {
      $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);
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
    stock.kgs,
    stock." . $this->hamt . ",
    stock." . $this->hqty . " as qty,
    FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . ") as " . $this->damt . ",
    FORMAT(stock." . $this->dqty . "," . $qty_dec . ")  as " . $this->dqty . ",
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,
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
    stock.rem,
    stock.palletid,
    stock.locid,
    stock.freight,
    ifnull(pallet.name,'') as pallet,
    ifnull(location.loc,'') as location,
    ifnull(uom.factor,1) as uomfactor,stock.fcost,ifnull(stock.stageid,0) as stageid ,ifnull(st.stage,'') as stage,
    '' as bgcolor,
    '' as errcolor,
    prj.name as stock_projectname,
    stock.projectid as projectid,
    item.subcode, item.partno, round(item.dqty, " . $this->companysetup->getdecimal('qty', $config['params']) . ") as boxcount,stock.poref,stock.sgdrate,
    concat(item.itemname,'\\n',ifnull(brand.brand_desc,''),'\\r\\n',ifnull(mm.model_name,''),'\\r\\n',ifnull(i.itemdescription,'')) as itemdescription,ifnull(group_concat(rr.serial separator '\\n\\r'),'') as serialno
    ";
    return $sqlselect;
  }

  public function openstock($trno, $config)
  {
    $companyid = $config['params']['companyid'];
    if ($companyid == 24) {
      $qty_dec = 3;
    } else {
      $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);
    }

    $sqlselect = $this->getstockselect($config);

    $qry = $sqlselect . "
    FROM $this->stock as stock
    left join item on item.itemid=stock.itemid
    left join pallet on pallet.line=stock.palletid
    left join location on location.line=stock.locid
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid
    left join stagesmasterfile as st on st.line = stock.stageid
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join model_masterfile as mm on mm.model_id = item.model
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid
    left join serialin as rr on rr.trno = stock.trno and rr.line = stock.line
    where stock.trno =?
    group by item.brand,
    mm.model_name,item.itemid,stock.trno,stock.line,stock.sortline,
    stock.refx,stock.linex,item.barcode,item.itemname,stock.uom,stock.kgs,
    stock." . $this->hamt . ", stock." . $this->hqty . ",
    FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . "),
    FORMAT(stock." . $this->dqty . "," . $qty_dec . ") ,
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . "),
    stock.encodeddate,stock.disc,stock.void,round((stock." . $this->hqty . "-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") ,
    stock.ref,stock.whid,warehouse.client,warehouse.clientname, stock.loc,stock.expiry,item.brand,stock.rem,stock.palletid,stock.locid,
    pallet.name,location.loc,uom.factor,stock.fcost,stock.stageid,st.stage,
    prj.name,stock.projectid,item.subcode, item.partno, round(item.dqty, " . $this->companysetup->getdecimal('qty', $config['params']) . "),stock.poref,stock.sgdrate,
    brand.brand_desc,i.itemdescription,stock.freight
    UNION ALL
    " . $sqlselect . "
    FROM $this->hstock as stock
    left join item on item.itemid=stock.itemid
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
    left join client as warehouse on warehouse.clientid=stock.whid
    left join pallet on pallet.line=stock.palletid
    left join location on location.line=stock.locid
    left join stagesmasterfile as st on st.line = stock.stageid
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join model_masterfile as mm on mm.model_id = item.model
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid
    left join serialin as rr on rr.trno = stock.trno and rr.line = stock.line
    where stock.trno =? group by item.brand,
    mm.model_name,item.itemid,stock.trno,stock.line,stock.sortline,
    stock.refx,stock.linex,item.barcode,item.itemname,stock.uom,stock.kgs,
    stock." . $this->hamt . ", stock." . $this->hqty . ",
    FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . "),
    FORMAT(stock." . $this->dqty . "," . $qty_dec . ") ,
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . "),
    stock.encodeddate,stock.disc,stock.void,round((stock." . $this->hqty . "-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") ,
    stock.ref,stock.whid,warehouse.client,warehouse.clientname, stock.loc,stock.expiry,item.brand,stock.rem,stock.palletid,stock.locid,
    pallet.name,location.loc,uom.factor,stock.fcost,stock.stageid,st.stage,
    prj.name,stock.projectid,item.subcode, item.partno, round(item.dqty, " . $this->companysetup->getdecimal('qty', $config['params']) . "),stock.poref,stock.sgdrate,
    brand.brand_desc,i.itemdescription,stock.freight order by sortline,line";

    $stock = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $stock;
  } //end function

  public function openstockline($config)
  {
    $companyid = $config['params']['companyid'];
    $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);

    $sqlselect = $this->getstockselect($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = $sqlselect . "
   FROM $this->stock as stock
    left join item on item.itemid=stock.itemid
    left join pallet on pallet.line=stock.palletid
    left join location on location.line=stock.locid
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid
    left join stagesmasterfile as st on st.line = stock.stageid 
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join model_masterfile as mm on mm.model_id = item.model
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid
    left join serialin as rr on rr.trno = stock.trno and rr.line = stock.line
    where stock.trno = ? and stock.line = ? 
    group by item.brand,
    mm.model_name,item.itemid,stock.trno,stock.line,stock.sortline,
    stock.refx,stock.linex,item.barcode,item.itemname,stock.uom,stock.kgs,
    stock." . $this->hamt . ", stock." . $this->hqty . ",
    FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . "),
    FORMAT(stock." . $this->dqty . "," . $qty_dec . ") ,
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . "),
    stock.encodeddate,stock.disc,stock.void,round((stock." . $this->hqty . "-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") ,
    stock.ref,stock.whid,warehouse.client,warehouse.clientname, stock.loc,stock.expiry,item.brand,stock.rem,stock.palletid,stock.locid,
    pallet.name,location.loc,uom.factor,stock.fcost,stock.stageid,st.stage,
    prj.name,stock.projectid,item.subcode, item.partno, round(item.dqty, " . $this->companysetup->getdecimal('qty', $config['params']) . "),stock.poref,stock.sgdrate,
    brand.brand_desc,i.itemdescription,stock.freight";
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
      case 'getposummary':
        return $this->getposummary($config);
        break;
      case 'getpodetails':
        return $this->getpodetails($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
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
      case 'tagreceived':
        return $this->tagreceived($config);
        break;
      case 'untagreceived':
        return $this->untagreceived($config);
        break;
      case 'uploadexcel':
        return $this->uploadexcel($config);
        break;
      case 'makepayment':
        return $this->othersClass->generateShortcutTransaction($config, 0, 'RRCV');
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

  public function uploadexcel($config)
  {
    $rawdata = $config['params']['data'];
    $trno = $config['params']['dataparams']['trno'];
    $msg = '';
    $status = true;

    if ($trno == 0) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Kindly create the document number first.'];
    }

    foreach ($rawdata as $key => $value) {
      try {
        $itemid = $this->coreFunctions->getfieldvalue("item", "itemid", "barcode = '" . $rawdata[$key]['itemcode'] . "'");
        if ($itemid == '') {
          $status = false;
          $msg .= 'Failed to upload. ' . $rawdata[$key]['itemcode'] . ' does not exist. ';
          continue;
        }

        $config['params']['trno'] = $trno;
        $config['params']['data']['uom'] = $rawdata[$key]['uom'];
        $config['params']['data']['itemid'] = $itemid;
        $config['params']['data']['qty'] = $rawdata[$key]['qty'];
        $config['params']['data']['wh'] =  $this->coreFunctions->getfieldvalue($this->head, "wh", "trno = ?", [$trno]);
        $config['params']['data']['amt'] = $rawdata[$key]['cost'];
        $config['params']['data']['loc'] = isset($rawdata[$key]['location']) ? $rawdata[$key]['location'] : "";
        $config['params']['data']['disc'] = isset($rawdata[$key]['disc']) ? $rawdata[$key]['disc'] : "";

        $expiry = isset($rawdata[$key]['expiry']) ? $rawdata[$key]['expiry'] : '';
        if ($expiry != '') {
          $UNIX_DATE = ($expiry - 25569) * 86400;
          $config['params']['data']['expiry'] = gmdate("Y-m-d", $UNIX_DATE);
        }

        if (isset($rawdata[$key]['kgs'])) {
          $config['params']['data']['kgs'] = $rawdata[$key]['kgs'];
        }

        $return = $this->additem('insert', $config);
        if (!$return['status']) {
          $status = false;
          $msg .= 'Failed to upload. ' . $return['msg'];
          goto exithere;
        }
      } catch (Exception $e) {
        $status = false;
        $msg .= 'Failed to upload. Exception error ' . $e->getMessage();
        goto exithere;
      }
    }

    exithere:
    if ($msg == '') {
      $this->logger->sbcwritelog($trno, $config, 'IMPORT', 'UPLOAD EXCEL FILE');
      $msg = 'Successfully uploaded.';
    }

    if (!$status) {
      $this->coreFunctions->execqry("delete from lastock where trno=" . $trno);
    }

    return ['trno' => $trno, 'status' => $status, 'msg' => $msg, 'reloadhead' => true];
  }


  public function tagreceived($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];

    $msg = "";
    $status = true;
    $qry = "select receivedby, ifnull(date(receiveddate), '') as receiveddate from cntnum where trno = ?";
    $checking = $this->coreFunctions->opentable($qry, [$trno]);

    if ($checking[0]->receiveddate != "") {
      $msg = "Already Received! " . $checking[0]->receivedby . ' ' . $checking[0]->receiveddate;
    } else {
      $tag = $this->coreFunctions->execqry("update cntnum set receivedby='" . $user . "',
      receiveddate = '" . date("Y-m-d") . "' where trno=? ", "update", [$trno]);

      if ($tag) {
        $msg = "Received Success!";
        $status = true;
        $this->logger->sbcwritelog($trno, $config, 'RECEIVED', 'CLICKED RECEIVED');
      } else {
        $msg = "Received Failed!";
        $status = false;
      }
    }

    return ['status' => $status, 'msg' => $msg, 'dd' => 'Received', 'reloadhead' => true];
  }

  public function untagreceived($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];

    $msg = "";
    $status = true;
    $qry = "select receivedby, ifnull(date(receiveddate), '') as receiveddate from cntnum where trno = ?";
    $checking = $this->coreFunctions->opentable($qry, [$trno]);

    if ($checking[0]->receiveddate == "") {
      $msg = "Already Unreceived! " . $checking[0]->receivedby . ' ' . $checking[0]->receiveddate;
    } else {
      $tag = $this->coreFunctions->execqry("update cntnum set receivedby='',
        receiveddate = null where trno=? ", "update", [$trno]);

      if ($tag) {
        $msg = "Unreceived Success!";
        $status = true;
        $this->logger->sbcwritelog($trno, $config, 'UNRECEIVED', 'CLICKED UNRECEIVED');
      } else {
        $msg = "Unreceived Failed!";
        $status = false;
      }
    }

    return ['status' => $status, 'msg' => $msg, 'dd' => 'Unreceived', 'reloadhead' => true];
  }


  public function gethead_receivedbutton($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];

    $table = $this->head;
    $htable = $this->hhead;
    $tablenum = $this->tablenum;

    $isproject = $this->companysetup->getisproject($config['params']);
    $projectfilter  = "";

    if ($isproject) {
      $viewall = $this->othersClass->checkAccess($config['params']['user'], 2232);
      $project = $this->coreFunctions->getfieldvalue("useraccess", "project", "username=?", [$config['params']['user']]);
      $projectid = $this->coreFunctions->getfieldvalue("projectmasterfile", "line", "code=?", [$project]);
      if ($viewall == '0') {
        $projectfilter = " and head.projectid = " . $projectid . " ";
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
      head.billid,
      head.shipid,
      head.billcontactid,
      head.shipcontactid,
      '' as dvattype,
      warehouse.client as wh,
      warehouse.clientname as whname,
      '' as dwhname,
      head.projectid,
      '' as dprojectname,
      left(head.due,10) as due,
      client.groupid,ifnull(p.code,'') as projectcode,ifnull(p.name,'') as projectname,ifnull(s.line,0) as subproject,ifnull(s.subproject,'') as subprojectname,
      head.branch,ifnull(b.clientname,'') as branchname,ifnull(b.client,'') as branchcode,'' as dbranchname,ifnull(d.client,'') as dept,ifnull(d.clientname,'') as deptname,
      head.deptid,'' as ddeptname,head.invoiceno,left(head.invoicedate,10) as invoicedate,head.ewt,head.ewtrate ";

    $qry = $qryselect . " from $table as head
      left join $tablenum as num on num.trno = head.trno
      left join client on head.client = client.client
      left join client as warehouse on warehouse.client = head.wh
      left join client as b on b.clientid = head.branch
      left join coa on coa.acno=head.contra
      left join projectmasterfile as p on p.line=head.projectid
      left join client as d on d.clientid = head.deptid
      left join subproject as s on s.line = head.subproject
      where head.trno = ? and num.doc=? and num.center = ? " . $projectfilter . "
      union all " . $qryselect . " from $htable as head
      left join $tablenum as num on num.trno = head.trno
      left join client on head.clientid = client.clientid
      left join client as warehouse on warehouse.clientid = head.whid
      left join client as b on b.clientid = head.branch
      left join coa on coa.acno=head.contra
      left join projectmasterfile as p on p.line=head.projectid
      left join client as d on d.clientid = head.deptid
      left join subproject as s on s.line = head.subproject
      where head.trno = ? and num.doc=? and num.center=? " . $projectfilter;

    $head = $this->coreFunctions->opentable($qry, [$trno, $doc, $center, $trno, $doc, $center]);

    if (empty($head)) {
      $head = [];
    }

    return $head;
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
    $row = [];
    foreach ($config['params']['row'] as $key => $value) {
      $msg = 'Successfully saved.';
      $config['params']['data'] = $value;
      $row = $this->additem('insert', $config);
      if (isset($config['params']['data']['refx'])) {
        if ($config['params']['data']['refx'] != 0) {
          if ($this->othersClass->setserveditemsRR($config['params']['data']['refx'], $config['params']['data']['linex'], $this->hqty) == 0) {
            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $row['row'][0]->trno, 'line' => $row['row'][0]->line]);
            $this->othersClass->setserveditemsRR($config['params']['data']['refx'], $config['params']['data']['linex'], $this->hqty);
          }
        }
      }
      if ($row['status'] == false) {
        $msg = $row['msg'];
        break;
      }
    }
    $data = $this->openstock($config['params']['trno'], $config);
    if ($config['params']['companyid'] == 8) { //maxipro
      return ['inventory' => $data, 'status' => true, 'msg' => $row['msg']];
    } else {
      return ['inventory' => $data, 'status' => true, 'msg' => $msg];
    }
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

    if (!empty($item)) {
      $config['params']['barcode'] = $barcode;
      $lprice = $this->getlatestprice($config, $forex);
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
  public function additem($action, $config, $setlog = false)
  {
    $companyid = $config['params']['companyid'];
    $isproject = $this->companysetup->getisproject($config['params']);

    $uom = $config['params']['data']['uom'];
    $itemid = $config['params']['data']['itemid'];
    $trno = $config['params']['trno'];
    $disc = isset($config['params']['data']['disc']) ? $config['params']['data']['disc'] : '';
    $wh = $config['params']['data']['wh'];
    $loc = $config['params']['data']['loc'];
    $expiry = isset($config['params']['data']['expiry']) ? $config['params']['data']['expiry'] : '';
    $rem = isset($config['params']['data']['rem']) ? $config['params']['data']['rem'] : '';
    $freight = isset($config['params']['data']['freight']) ? $config['params']['data']['freight'] : 0;

    if ($this->companysetup->getiskgs($config['params'])) {
      $kgs = isset($config['params']['data']['kgs']) ? $config['params']['data']['kgs'] : 1;
    } else {
      $kgs = 0;
    }

    $refx = 0;
    $linex = 0;
    $fcost = 0;
    $ref = '';
    $stageid = 0;
    $palletid = 0;
    $locid = 0;
    $stock_projectid = 0;
    $poref = '';
    $sgdrate = 0;

    if (isset($config['params']['data']['refx'])) {
      $refx = $config['params']['data']['refx'];
    }
    if (isset($config['params']['data']['linex'])) {
      $linex = $config['params']['data']['linex'];
    }
    if (isset($config['params']['data']['ref'])) {
      $ref = $config['params']['data']['ref'];
    }

    if (isset($config['params']['data']['stageid'])) {
      $stageid = $config['params']['data']['stageid'];
    }

    if (isset($config['params']['data']['palletid'])) {
      $palletid = $config['params']['data']['palletid'];
    }

    if (isset($config['params']['data']['stageid'])) {
      $stageid = $config['params']['data']['stageid'];
    }

    if (isset($config['params']['data']['palletid'])) {
      $palletid = $config['params']['data']['palletid'];
    }

    if (isset($config['params']['data']['locid'])) {
      $locid = $config['params']['data']['locid'];
    }

    if (isset($config['params']['data']['poref'])) {
      $poref = $config['params']['data']['poref'];
    }

    if (isset($config['params']['data']['sgdrate'])) {
      $sgdrate = $config['params']['data']['sgdrate'];
    } else {
      $sgdrate = $this->othersClass->getexchangerate('PHP', 'SGD');
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

    $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
    $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);

    $qty = $this->othersClass->sanitizekeyfield('qty', $qty);
    $amt = $this->othersClass->sanitizekeyfield('amt', $amt);
    $kgs = $this->othersClass->sanitizekeyfield('qty', $kgs);
    $freight = $this->othersClass->sanitizekeyfield('amt', $freight);
    $factor = 1;
    if (!empty($item)) {
      $item[0]->factor = $this->othersClass->val($item[0]->factor);
      if ($item[0]->factor !== 0) $factor = $item[0]->factor;
    }
    $vat = $this->coreFunctions->getfieldvalue($this->head, 'tax', 'trno=?', [$trno]);
    $forex = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
    $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);
    $projectid = $this->coreFunctions->getfieldvalue($this->head, 'projectid', 'trno=?', [$trno]);

    if (floatval($forex) <> 1) {
      $fcost = $amt;
    }

    $forex = $this->othersClass->val($forex);
    if ($forex == 0) $forex = 1;

    $qty = round($qty, $this->companysetup->getdecimal('qty', $config['params']));
    if ($this->companysetup->getvatexpurch($config['params'])) {
      $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor, 0, 'P', $kgs);
    } else {
      $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor, $vat, 'P', $kgs);
    }

    $data = [
      'trno' => $trno,
      'line' => $line,
      'itemid' => $itemid,
      $this->damt => $amt,
      $this->hamt => number_format((($computedata['amt'] * $forex) + $freight), $this->companysetup->getdecimal('price', $config['params']), '.', ''),
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
      'expiry' => $expiry,
      'uom' => $uom,
      'rem' => $rem,
      'palletid' => $palletid,
      'locid' => $locid,
      'stageid' => $stageid,
      'freight' => $freight
    ];

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
      if ($isproject) {
        $isho = $this->coreFunctions->getfieldvalue("projectmasterfile", "isho", "line = " . $projectid);
        if (!$isho) {
          if ($data['stageid'] == 0) {
            $msg = 'Stage cannot be blank -' . $item[0]->barcode;
            return ['status' => false, 'msg' => $msg];
          }
        }
      }
      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
        switch ($this->companysetup->getsystemtype($config['params'])) {
          case 'AIMS':
            if ($companyid == 0) { //main
              $stockinfo_data = [
                'trno' => $trno,
                'line' => $line,
                'rem' => $rem
              ];
              $this->coreFunctions->sbcinsert('stockinfo', $stockinfo_data);
            }
            break;
        }

        if ($isproject) {
          $this->updateprojmngmt($config, $stageid);
        }
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' Barcode:' . $item[0]->barcode . ' Uom:' . $uom . ' Amt:' . $amt . ' Disc:' . $disc . ' WH:' . $wh . ' Ext:' . $computedata['ext'], $setlog ? $this->tablelogs : '');
        $row = $this->openstockline($config);

        return ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
      } else {
        return ['status' => false, 'msg' => 'Add item Failed'];
      }
    } elseif ($action == 'update') {
      $return = true;
      $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
      $this->updateprojmngmt($config, $stageid);
      if ($this->othersClass->setserveditemsRR($refx, $linex, $this->hqty) === 0) {
        $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
        $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
        $this->othersClass->setserveditemsRR($refx, $linex, $this->hqty);
        $return = false;
      }
      return $return;
    }
  } // end function

  public function deleteallitem($config)
  {
    $trno = $config['params']['trno'];
    $data = $this->coreFunctions->opentable('select refx,linex,stageid from ' . $this->stock . ' where trno=? and refx<>0', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from serialin where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from stockinfo where trno=?', 'delete', [$trno]);
    foreach ($data as $key => $value) {
      $this->updateprojmngmt($config, $data[$key]->stageid);
      $this->othersClass->setserveditemsRR($data[$key]->refx, $data[$key]->linex, $this->hqty);
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
    $this->coreFunctions->execqry('delete from stockinfo where trno=? and line=?', 'delete', [$trno, $line]);
    $this->logger->sbcwritelog(
      $trno,
      $config,
      'STOCKINFO',
      'DELETE - Line:' . $line
        . ' Notes:' . $config['params']['row']['rem']
    );
    $qry = "delete from serialin where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
    $this->updateprojmngmt($config, $data[0]->stageid);
    if ($data[0]->refx !== 0) {
      $this->othersClass->setserveditemsRR($data[0]->refx, $data[0]->linex, $this->hqty);
    }
    $data = json_decode(json_encode($data), true);
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' Barcode:' . $data[0]['barcode'] . ' Qty:' . $data[0][$this->dqty] . ' Amt:' . $data[0][$this->damt] . ' Disc:' . $data[0]['disc'] . ' WH:' . $data[0]['wh'] . ' Ext:' . $data[0]['ext']);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
  } // end function

  public function getlatestprice($config, $forex = 1)
  {
    $barcode = $config['params']['barcode'];
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];

    $qry = "select docno,left(dateid,10) as dateid,round(amt,2) as amt,disc,uom from(select head.docno,head.dateid,
    case " . $forex . " when 1 then stock." . $this->damt . "*head.forex else stock." . $this->damt . " end as amt,stock.uom,stock.disc
    from lahead as head
    left join lastock as stock on stock.trno = head.trno
    left join cntnum on cntnum.trno=head.trno
    left join item on item.itemid = stock.itemid
    where head.doc = '" . $config['params']['doc'] . "' and cntnum.center = ?
    and item.barcode = ? and head.client =?
    and stock.cost <> 0 and cntnum.trno <>?
    UNION ALL
    select head.docno,head.dateid,case " . $forex . " when 1 then stock." . $this->damt . "*head.forex else stock." . $this->damt . " end as amt,
    stock.uom,stock.disc from glhead as head
    left join glstock as stock on stock.trno = head.trno
    left join item on item.itemid = stock.itemid
    left join client on client.clientid = head.clientid
    left join cntnum on cntnum.trno=head.trno
    where head.doc = '" . $config['params']['doc'] . "' and cntnum.center = ?
    and item.barcode = ? and client.client =?
    and stock." . $this->hamt . " <> 0 and cntnum.trno <>?
    order by dateid desc limit 5) as tbl order by dateid desc limit 1";

    $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $client, $trno, $center, $barcode, $client, $trno]);

    if (!empty($data)) {
      return ['status' => true, 'msg' => 'Found the latest purchase price...', 'data' => $data];
    } else {
      return ['status' => false, 'msg' => 'No Latest price found...'];
    }
  } // end function

  public function getposummaryqry($config)
  {
    $joins = "";
    $fields = "";
    $companyid = $config['params']['companyid'];

    return "
    select head.docno, head.client, head.clientname, head.address, ifnull(head.rem,'') as rem, head.cur, head.forex, head.shipto, head.ourref, head.yourref, head.projectid, head.terms,
    item.itemid,stock.trno, stock.line, item.barcode,stock.uom, stock.cost, (stock.qty-stock.qa) as qty,stock.rrcost, head.wh,
    round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,stock.rem,
    stock.disc,stock.stageid,head.branch,head.billcontactid,head.shipcontactid,head.billid,head.shipid,head.tax,head.vattype,head.yourref,head.deptid,stock.sgdrate,wh.client as swh,stock.loc $fields
    FROM hpohead as head 
    left join hpostock as stock on stock.trno=head.trno 
    left join item on item.itemid=stock.itemid 
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as wh on wh.clientid=stock.whid
    $joins
    where stock.trno = ? and stock.qty>stock.qa and stock.void=0 ";
  }

  public function getposummary($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $companyid = $config['params']['companyid'];
    $rows = [];
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

          if ($companyid == 24) { //goodfound
            $config['params']['data']['loc'] = $data[$key2]->loc;
            if ($data[$key2]->yourref != "") {
              $this->coreFunctions->execqry("update lahead set yourref='" . $data[$key2]->yourref . "' where trno = " . $trno, 'update');
            }
            if ($data[$key2]->terms != "") {
              $this->coreFunctions->execqry("update lahead set terms='" . $data[$key2]->terms . "' where trno = " . $trno, 'update');
            }
          } else {
            $config['params']['data']['loc'] = '';
          }
          $config['params']['data']['expiry'] = '';
          $config['params']['data']['rem'] = $data[$key2]->rem;
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->rrcost;
          $config['params']['data']['stageid'] = $data[$key2]->stageid;
          $return = $this->additem('insert', $config);
          if ($return['status']) {
            if ($this->othersClass->setserveditemsRR($data[$key2]->trno, $data[$key2]->line, $this->hqty) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->othersClass->setserveditemsRR($data[$key2]->trno, $data[$key2]->line, $this->hqty);
              $row = $this->openstockline($config);
              $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
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

    $joins = "";
    $fields = "";

    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.docno, item.itemid,stock.trno,stock.rem,
        stock.line, item.barcode,stock.uom, stock.cost,
        (stock.qty-stock.qa) as qty,stock.rrcost,
        round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
        stock.disc,stock.stageid,head.yourref,head.terms,stock.loc" . $fields . "
        FROM hpohead as head left join hpostock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom " . $joins . " where stock.trno = ? and stock.line=? and stock.qty>stock.qa and stock.void=0
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

          if ($companyid == 24) { //goodfound
            $config['params']['data']['loc'] = $data[$key2]->loc;
            if ($data[$key2]->yourref != "") {
              $this->coreFunctions->execqry("update lahead set yourref='" . $data[$key2]->yourref . "' where trno = " . $trno, 'update');
            }
            if ($data[$key2]->terms != "") {
              $this->coreFunctions->execqry("update lahead set terms='" . $data[$key2]->terms . "' where trno = " . $trno, 'update');
            }
          } else {
            $config['params']['data']['loc'] = '';
          }

          $config['params']['data']['expiry'] = '';
          if ($companyid == 17) { //unihome
            $config['params']['data']['rem'] = $data[$key2]->stockinfo;
          } else {
            $config['params']['data']['rem'] = $data[$key2]->rem;
          }
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->rrcost;
          $config['params']['data']['stageid'] = $data[$key2]->stageid;
          if ($companyid == 10) { //afti
            $config['params']['data']['poref'] = $data[$key2]->yourref;
          }
          $return = $this->additem('insert', $config);
          if ($return['status']) {
            if ($this->othersClass->setserveditemsRR($data[$key2]->trno, $data[$key2]->line, $this->hqty) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->othersClass->setserveditemsRR($data[$key2]->trno, $data[$key2]->line, $this->hqty);
              $row = $this->openstockline($config);
              $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
            }
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.', 'reloadhead' => true];
  } //end function

  public function createdistribution($config)
  {
    $trno = $config['params']['trno'];
    $companyid = $config['params']['companyid'];
    $status = true;
    $isvatexpurch = $this->companysetup->getvatexpurch($config['params']);
    $periodic = $this->companysetup->getisperiodic($config['params']);
    $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $qry = 'select head.dateid,head.client,head.tax,head.contra,head.cur,head.forex,stock.ext,wh.client as wh,ifnull(a.acno,"") as asset,ifnull(r.acno,"") as revenue,
      stock.rrcost,stock.cost,stock.disc,stock.rrqty,stock.qty,stock.projectid,head.subproject,stock.stageid,head.branch,head.deptid,head.ewtrate,head.ewt,stock.freight
          from ' . $this->head . ' as head left join ' . $this->stock . ' as stock on stock.trno=head.trno
          left join client as wh on wh.clientid=stock.whid
          left join item on item.itemid=stock.itemid left join projectmasterfile as p on p.line = stock.projectid 
          left join coa as a on a.acnoid = p.assetid left join coa as r on r.acnoid = p.revenueid where head.trno=?';
    } else {
      $qry = 'select head.dateid,head.client,head.tax,head.contra,head.cur,head.forex,stock.ext,wh.client as wh,ifnull(item.asset,"") as asset,ifnull(item.revenue,"") as revenue,
      stock.rrcost,stock.cost,stock.disc,stock.rrqty,stock.qty,head.projectid,head.subproject,stock.stageid,stock.freight
          from ' . $this->head . ' as head left join ' . $this->stock . ' as stock on stock.trno=head.trno
          left join client as wh on wh.clientid=stock.whid
          left join item on item.itemid=stock.itemid where head.trno=?';
    }
    $stock = $this->coreFunctions->opentable($qry, [$trno]);
    $tax = 0;
    $ewt = 0;
    $totalap = 0;
    $delcharge = 0;

    if (!empty($stock)) {
      if ($periodic) {
        $invacct = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['PS1']); //Purchases acct under asset 
      } else {
        $invacct = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['IN1']);
      }

      $vat = $stock[0]->tax;
      $tax1 = 0;
      $tax2 = 0;
      if ($vat != 0) {
        $tax1 = 1 + ($vat / 100);
        $tax2 = $vat / 100;
      }

      if ($companyid == 10) { //afti
        if ($stock[0]->ewtrate != 0) {
          $ewt = $stock[0]->ewtrate / 100;
        }
      }

      foreach ($stock as $key => $value) {
        $params = [];
        $disc = $stock[$key]->rrcost - ($this->othersClass->discount($stock[$key]->rrcost, $stock[$key]->disc));

        if ($vat != 0) {
          if ($isvatexpurch) {
            $tax = round(($stock[$key]->ext * $tax2), 2);
          } else {
            $tax = round(($stock[$key]->ext / $tax1), 2);
            $tax = round($stock[$key]->ext - $tax, 2);
          }
        }
        $delcharge += $stock[$key]->freight;

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
          'cost' => $stock[$key]->ext - $tax,
          'projectid' => $stock[$key]->projectid,
          'subproject' => $stock[$key]->subproject,
          'stageid' => $stock[$key]->stageid,
          'freight' => $stock[$key]->freight
        ];
        if ($companyid == 10 || $companyid == 12) { //afti, afti usd
          $params['branch'] = $stock[$key]->branch;
          $params['deptid'] = $stock[$key]->deptid;
          $params['ewt'] = $ewt;
          $params['ewtcode'] = $stock[$key]->ewt;
        }

        if ($isvatexpurch) {
          $this->distributionvatex($params, $config);
        } else {
          $this->distribution($params, $config);
        }
      }

      if ($delcharge != 0) {
        $qry = "select client,forex,dateid,cur,branch,deptid,contra from " . $this->head . " where trno = ?";
        $d = $this->coreFunctions->opentable($qry, [$trno]);
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['DC1']);
        $entry = ['acnoid' => $acnoid, 'client' => $d[0]->client, 'db' => 0, 'cr' => $delcharge, 'postdate' => $d[0]->dateid, 'cur' => $d[0]->cur, 'forex' => $d[0]->forex, 'fcr' => floatval($d[0]->forex) == 1 ? 0 : $delcharge, 'fdb' => 0];
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
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
    //$doc,$trno,$client,$acno,$alias,$amt,$famt,$charge,$cogsamt,$wh,$date,$project='',$inventory='',$cogs='',$tax=0,$rem='',$revenue='',$disc='',$discamt=0
    $companyid = $config['params']['companyid'];
    $entry = [];
    $forex = $params['forex'];
    if ($forex == 0) {
      $forex = 1;
    }
    $suppinvoice = $this->companysetup->getsupplierinvoice($config['params']);
    $periodic = $this->companysetup->getisperiodic($config['params']);

    if (!$this->companysetup->getispurchasedisc($config['params'])) {
      $params['discamt'] = 0;
    }

    $cur = $params['cur'];
    $invamt = $params['cost'];
    $ewt = isset($params['ewt']) ? $params['ewt'] : 0;
    $ext = $params['ext'];

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      if ($params['ewt'] != 0) {
        if ($params['tax'] != 0) {
          $ewt = round(($params['ext'] - $params['tax']) * $params['ewt'], 2);
          $ext = round($params['ext'] - $ewt, 2);
        } else {
          $ewt = round($params['ext'] * $params['ewt'], 2);
          $ext = round($params['ext'] - $ewt, 2);
        }
      }
    }


    //AP
    if (!$suppinvoice) {
      if (floatval($ext) != 0) {
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['acno']]);
        $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'db' => 0, 'cr' => ($ext * $forex), 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => 0, 'fcr' => floatval($forex) == 1 ? 0 : $ext, 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];
        if ($companyid == 10) { //afti
          $entry['branch'] = $params['branch'];
          $entry['deptid'] = $params['deptid'];
          $entry['projectid'] = 0;
        }
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }

      //disc
      if (floatval($params['discamt']) != 0) {
        $inputid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['PD1']);
        $entry = ['acnoid' => $inputid, 'client' => $params['client'], 'cr' => ($params['discamt'] * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => 0, 'fcr' => floatval($forex) == 1 ? 0 : ($params['discamt']), 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];
        if ($companyid == 10) { //afti
          $entry['branch'] = $params['branch'];
          $entry['deptid'] = $params['deptid'];
        }
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }

      if (floatval($params['tax']) != 0) {
        // input tax
        $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['TX1']);
        $entry = ['acnoid' => $input, 'client' => $params['client'], 'cr' => 0, 'db' => ($params['tax'] * $forex), 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($params['tax']), 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];
        if ($companyid == 10) { //afti
          $entry['branch'] = $params['branch'];
          $entry['deptid'] = $params['deptid'];
          $entry['projectid'] = 0;
        }
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }

      if (floatval($ewt) != 0) {
        // EWt
        $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['APWT1']);
        $entry = ['acnoid' => $input, 'client' => $params['client'], 'db' => 0, 'cr' => ($ewt * $forex), 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => 0, 'fcr' => floatval($forex) == 1 ? 0 : ($ewt), 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];
        if ($companyid == 10) { //afti
          $entry['branch'] = $params['branch'];
          $entry['deptid'] = $params['deptid'];
          $entry['projectid'] = 0;
        }
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }
    }

    //INV
    if (floatval($invamt) != 0) {
      if (floatval($params['discamt']) != 0) {
        $invamt  = $invamt + ($params['discamt'] * $forex);
      }
      $freight = $params['freight'];
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['inventory']]);
      $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'db' => ($invamt * $forex) + $freight, 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($invamt), 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];
      if ($companyid == 10) { //afti
        $entry['branch'] = $params['branch'];
        $entry['deptid'] = $params['deptid'];
      }
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

      if ($suppinvoice) {
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['CG1']);
        $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'cr' => ($invamt * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($invamt), 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }
    }
  } //end function

  public function distributionvatex($params, $config)
  {
    //$doc,$trno,$client,$acno,$alias,$amt,$famt,$charge,$cogsamt,$wh,$date,$project='',$inventory='',$cogs='',$tax=0,$rem='',$revenue='',$disc='',$discamt=0
    $companyid = $config['params']['companyid'];
    $entry = [];
    $forex = $params['forex'];
    if ($forex == 0) {
      $forex = 1;
    }
    $suppinvoice = $this->companysetup->getsupplierinvoice($config['params']);

    $cur = $params['cur'];
    $invamt = round($params['ext'], 2); //round(($params['ext']-$params['tax']) + $params['discamt'],2);
    $ewt = isset($params['ewt']) ? $params['ewt'] : 0;
    $ext = round($params['ext'], 2);

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      if ($params['ewt'] != 0) {
        $ewt = round($params['ext'] * $params['ewt'], 2);
        $ext = round($params['ext'] - $ewt, 2);
      }
    }


    //AP
    if (!$suppinvoice) {
      if (floatval($ext) != 0) {
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['acno']]);
        $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'db' => 0, 'cr' => (($ext + $params['tax']) * $forex), 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => 0, 'fcr' => floatval($forex) == 1 ? 0 : $ext, 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];
        if ($companyid == 10) { //afti
          $entry['branch'] = $params['branch'];
          $entry['deptid'] = $params['deptid'];
          $entry['projectid'] = 0;
        }
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }

      //disc
      if (floatval($params['discamt']) != 0) {
        $inputid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['PD1']);
        $entry = ['acnoid' => $inputid, 'client' => $params['client'], 'cr' => ($params['discamt'] * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => 0, 'fcr' => floatval($forex) == 1 ? 0 : ($params['discamt']), 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];
        if ($companyid == 10) { //afti
          $entry['branch'] = $params['branch'];
          $entry['deptid'] = $params['deptid'];
        }
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }

      if (floatval($params['tax']) != 0) {
        // input tax
        $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['TX1']);
        $entry = ['acnoid' => $input, 'client' => $params['client'], 'cr' => 0, 'db' => ($params['tax'] * $forex), 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($params['tax']), 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];
        if ($companyid == 10) { //afti
          $entry['branch'] = $params['branch'];
          $entry['deptid'] = $params['deptid'];
          $entry['projectid'] = 0;
        }
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }

      if (floatval($ewt) != 0) {
        // EWt
        $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['APWT1']);
        $entry = ['acnoid' => $input, 'client' => $params['client'], 'db' => 0, 'cr' => ($ewt * $forex), 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => 0, 'fcr' => floatval($forex) == 1 ? 0 : ($ewt), 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];
        if ($companyid == 10) { //afti
          $entry['branch'] = $params['branch'];
          $entry['deptid'] = $params['deptid'];
          $entry['projectid'] = 0;
        }
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }
    }

    //INV
    if (floatval($invamt) != 0) {
      if (floatval($params['discamt']) != 0) {
        $invamt  = $invamt + ($params['discamt'] * $forex);
      }
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['inventory']]);
      $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'db' => ($invamt * $forex), 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($invamt), 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];
      if ($companyid == 10) { //afti
        $entry['branch'] = $params['branch'];
        $entry['deptid'] = $params['deptid'];
        if ($ewt != 0) {
          $entry['isewt'] = 1;
          $entry['ewtcode'] = $params['ewtcode'];
        }

        if (floatval($params['tax']) != 0) {
          $entry['isvat'] = 1;
        }
      }
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

      if ($suppinvoice) {
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['CG1']);
        $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'cr' => ($invamt * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($invamt), 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }
    }
  } //end function

  private function updateprojmngmt($config, $stage)
  {
    $trno = $config['params']['trno'];
    $data = $this->openstock($trno, $config);
    $proj = $this->coreFunctions->getfieldvalue($this->head, "projectid", "trno=?", [$trno]);
    $sub = $this->coreFunctions->getfieldvalue($this->head, "subproject", "trno=?", [$trno]);

    $qry1 = "select stock.ext from " . $this->head . " as head left join " . $this->stock . " as
    stock on stock.trno=head.trno where head.doc='RR' and head.projectid = " . $proj . " and head.subproject = " . $sub . " and stock.stageid=" . $stage;

    $qry1 = $qry1 . " union all select stock.ext from " . $this->hhead . " as head left join " . $this->hstock . " as stock on stock.trno=
      head.trno where head.doc='RR' and head.projectid = " . $proj . " and head.subproject = " . $sub . " and stock.stageid=" . $stage;

    $qry2 = "select ifnull(sum(ext),0) as value from (" . $qry1 . ") as t";

    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty === '') {
      $qty = 0;
    }

    $editdate = $this->othersClass->getCurrentTimeStamp();
    $editby = $config['params']['user'];

    return $this->coreFunctions->execqry("update stages set rr=" . $qty . ", editdate = '" . $editdate . "', editby = '" . $editby . "' where projectid = " . $proj . " and subproject=" . $sub . " and stage=" . $stage, 'update');
  }

  private function updateprojmngmtap($config, $data)
  {
    $trno = $config['params']['trno'];
    foreach ($data as $key => $value) {
      $data2 = $this->coreFunctions->datareader("select ifnull(sum(a.cr-a.db),0) as value from apledger as a left join gldetail as d on d.trno = a.trno and d.line = a.line  where d.projectid =" . $data[$key]->projectid . " and d.subproject=" . $data[$key]->subproject . " and d.stageid=" . $data[$key]->stageid);

      if (empty($data2)) {
        $data2 = 0;
      }
      $this->coreFunctions->execqry("update stages set ap=" . $data2 . " where projectid = " . $data[$key]->projectid . " and subproject=" . $data[$key]->subproject . " and stage=" . $data[$key]->stageid, 'update');
    }
    return true;
  }

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

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    $dataparams = $config['params']['dataparams'];
    if (isset($dataparams['approved'])) $this->othersClass->writeSignatories($config, 'approved', $dataparams['approved']);
    if (isset($dataparams['checked'])) $this->othersClass->writeSignatories($config, 'checked', $dataparams['checked']);

    // auto lock
    $date = date("Y-m-d H:i:s");
    $user = $config['params']['user'];
    $trno = $config['params']['dataid'];
    $this->coreFunctions->sbcupdate($this->head, ['lockdate' => $date, 'lockuser' => $user], ['trno' => $trno]);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
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


  public function recomputecost($head, $config)
  {
    $data = $this->openstock($head['trno'], $config);
    $data2 = json_decode(json_encode($data), true);
    $exec = true;
    foreach ($data2 as $key => $value) {
      $damt = $this->othersClass->sanitizekeyfield('amt', $data2[$key][$this->damt]);
      $dqty = round($this->othersClass->sanitizekeyfield('qty', $data2[$key][$this->dqty]), $this->companysetup->getdecimal('qty', $config['params']));
      $kgs = $this->othersClass->sanitizekeyfield('qty', $data2[$key]['kgs']);

      if ($this->companysetup->getvatexpurch($config['params'])) {
        $computedata = $this->othersClass->computestock($damt * $head['forex'], $data[$key]->disc, $dqty, $data[$key]->uomfactor, 0, 'P', $kgs);
      } else {
        $computedata = $this->othersClass->computestock($damt * $head['forex'], $data[$key]->disc, $dqty, $data[$key]->uomfactor, $head['tax'], 'P', $kgs);
      }
      $exec = $this->coreFunctions->execqry("update lastock set cost = " . $computedata['amt'] . " where trno = " . $head['trno'] . " and line=" . $data[$key]->line, "update");
    }
    return $exec;
  }
} //end class
