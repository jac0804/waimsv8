<?php

namespace App\Http\Classes\modules\sales;

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
use App\Http\Classes\builder\helpClass;

class ai
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'SERVICE INVOICE';
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
  private $fields = ['trno', 'docno', 'dateid', 'due', 'client', 'clientname', 'yourref', 'ourref', 'rem', 'terms', 'forex', 'cur', 'wh', 'address', 'contra', 'tax', 'vattype', 'agent', 'projectid', 'creditinfo', 'billid', 'shipid', 'branch', 'deptid', 'sotrno', 'shipcontactid', 'billcontactid', 'ewt', 'ewtrate'];
  private $except = ['trno', 'dateid', 'due', 'creditinfo'];
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
    $this->sqlquery = new sqlquery;
    $this->logger = new Logger;
    $this->reporter = new SBCPDF;
    $this->helpClass = new helpClass;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 2707,
      'edit' => 2708,
      'new' => 2709,
      'save' => 2710,
      // 'change' => 2711, remove change doc
      'delete' => 2712,
      'print' => 2713,
      'lock' => 2714,
      'unlock' => 2715,
      'acctg' => 2721,
      'changeamt' => 2718,
      'post' => 2716,
      'unpost' => 2717,
      'additem' => 2722,
      'edititem' => 2723,
      'deleteitem' => 2724
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $companyid = $config['params']['companyid'];
    $userid = $config['params']['adminid'];
    $dept = '';
    if ($companyid == 10) { //afti
      if ($userid != 0) {
        $deptid = $this->coreFunctions->getfieldvalue("client", "deptid", "clientid = ?", [$userid]);
        $dept = $this->coreFunctions->getfieldvalue("client", "clientname", "clientid = ?", [$deptid]);
      }
    }
    $action = 0;
    $liststatus = 1;
    $listdocument = 2;
    $listdate = 3;
    $listclientname = 4;
    $yourref = 5;
    $ar = 6;
    $postedby = 7;
    $createby = 8;
    $editby = 9;
    $viewby = 10;
    $receiveby = 11;
    $receivedate = 12;

    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'yourref',  'ar', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby', 'receiveby', 'receivedate'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    if ($companyid == 11 || $companyid == 12) { //afti, afti usd
      $cols[$yourref]['label'] = 'Customer PO';
      $cols[$ar]['label'] = 'Balance';
      $cols[$liststatus]['name'] = 'statuscolor';
      if ($dept != 'ACCTG') {
        $cols[$ar]['type'] = 'coldel';
      }
    }
    $cols = $this->tabClass->delcollisting($cols);
    return $cols;
  }

  public function paramsdatalisting($config)
  {
    $fields = [];
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

    $join = '';
    $hjoin = '';
    $addparams = '';

    $companyid = $config['params']['companyid'];

    $userid = $config['params']['adminid'];
    $dept = '';
    if ($companyid == 10) { //afti
      if ($userid != 0) {
        $deptid = $this->coreFunctions->getfieldvalue("client", "deptid", "clientid = ?", [$userid]);
        $dept = $this->coreFunctions->getfieldvalue("client", "clientname", "clientid = ?", [$deptid]);
      }
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

    switch ($itemfilter) {
      case 'draft':
        $condition = ' and num.postdate is null ';
        break;
      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
    }


    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $lstat = "'DRAFT'";
        $gstat = "case '" . $dept . "' when 'ACCTG' then (case (select format(sum(ar.bal),2) from arledger as ar where ar.trno=head.trno) when 0 then 'PAID' 
        else (case when date(date_add(ifnull(ds.receivedate,head.dateid), interval terms.days day))<now() then 'OVERDUE' else 'UNPAID' end ) end)  
        else 'POSTED' end ";

        $gstatcolor = "case '" . $dept . "' when 'ACCTG' then (case (select format(sum(ar.bal),2) from arledger as ar where ar.trno=head.trno) when 0 then 'green' 
        else (case when date(date_add(ifnull(ds.receivedate,head.dateid), interval terms.days day))<now() then 'red' else 'orange' end ) end)  
        else 'grey' end ";

        $dateid = "date_format(head.dateid,'%m-%d-%Y') as dateid";
        $gfield = ",head.yourref,ds.receiveby,date_format(ds.receivedate,'%m-%d-%Y') as receivedate,
        (select format(sum(ar.bal),2) from arledger as ar where ar.trno=head.trno) as ar";
        $lfield = ",head.yourref,ds.receiveby,date_format(ds.receivedate,'%m-%d-%Y') as receivedate,
        format(sum(stock.ext),2) as ar";
        $ljoin = 'left join ' . $this->stock . ' as stock on stock.trno=head.trno left join delstatus as ds on ds.trno=head.trno left join terms on terms.terms = head.terms';
        $gjoin = 'left join delstatus as ds on ds.trno=head.trno left join terms on terms.terms = head.terms ';
        if ($searchfilter == "") $limit = 'limit 25';
        $group = 'group by head.trno,head.docno,head.clientname,head.dateid,
        head.createby,head.editby,head.viewby,num.postedby,
         head.yourref,ds.receiveby,ds.receivedate,terms.terms,terms.days';
        $orderby = "order by dateid desc, docno desc";
        break;

      default:
        $dateid = "left(head.dateid,10) as dateid";
        if ($searchfilter == "") $limit = 'limit 150';
        $orderby = "order by dateid desc, docno desc";
        break;
    }

    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'head.clientname','head.yourref', 'head.createby', 'head.editby', 'head.viewby', 'num.postedby'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    } else {
      $limit = 'limit 25';
    }

    $qry = "select head.dateid as date2,head.trno,head.docno,head.clientname,$dateid, $lstat as status,$lstatcolor as statuscolor,head.createby,head.editby,head.viewby,num.postedby $lfield
     from " . $this->head . " as head left join " . $this->tablenum . " as num
     on num.trno=head.trno $ljoin " . $join . " where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? $filtersearch " . $condition . $addparams . $group . "
     union all
     select head.dateid as date2,head.trno,head.docno,head.clientname,$dateid, $gstat as status,$gstatcolor as statuscolor,head.createby,head.editby,head.viewby, num.postedby $gfield
     from " . $this->hhead . " as head left join " . $this->tablenum . " as num
     on num.trno=head.trno $gjoin 
     " . $hjoin . " where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? $filtersearch " . $condition . $addparams .  $group . "
     order by date2 desc, docno desc $limit";

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
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => $config['params']['doc'], 'title' => strtoupper($this->modulename) . '_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
    }

    return $buttons;
  } // createHeadbutton

  public function createtab2($access, $config)
  {
    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrycntnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);
    $companyid = $config['params']['companyid'];

    $billshipdefault = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewbillingshipping']];
    $deliverystatus = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewdeliverystatus']];

    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];

    switch ($companyid) {
      case 10: //afti
        $return['SHIPPING/BILLING ADDRESS'] = ['icon' => 'fa fa-map-marker-alt', 'customform' => $billshipdefault];
        $return['DELIVERY STATUS'] = ['icon' => 'fa fa-map-marker-alt', 'customform' => $deliverystatus];
        break;
    }

    return $return;
  }


  public function createTab($access, $config)
  {
    $companyid = $config['params']['companyid'];
    $viewcost = $this->othersClass->checkAccess($config['params']['user'], 368);
    $isexpiry = $this->companysetup->getisexpiry($config['params']);
    $ispallet = $this->companysetup->getispallet($config['params']);
    $makecr = $this->othersClass->checkAccess($config['params']['user'], 3578);
    $action = 0;
    $itemdesc = 1;
    $serial = 2;
    $isqty = 3;
    $uom = 4;
    $isamt = 5;
    $disc = 6;
    $ext = 7;
    $markup = 8;
    $insurance = 9;
    $rebate = 10;
    $wh = 11;
    $whname = 12;
    $ref = 13;
    $loc = 14;
    $expiry = 15;
    $itemname = 16;
    $stock_projectname = 17;
    $noprint = 18;
    $barcode = 19;

    $column = ['action', 'itemdescription', 'serialno', 'isqty', 'uom', 'isamt', 'disc', 'ext', 'markup', 'insurance', 'rebate', 'wh', 'whname', 'ref', 'loc', 'expiry', 'itemname', 'stock_projectname','noprint', 'barcode'];
    $sortcolumn = ['action', 'itemdescription', 'serialno', 'isqty', 'uom', 'isamt', 'disc', 'ext', 'markup', 'insurance', 'rebate', 'wh', 'whname', 'ref', 'loc', 'expiry', 'itemname', 'stock_projectname', 'noprint','barcode'];
    $headgridbtns = ['viewdistribution', 'viewref', 'viewdiagram', 'viewitemstockinfo'];

    if ($companyid == 10) { //afti
      if ($makecr != 0) {
        array_push($headgridbtns, 'makecv');
      }
    }

    $tab = [
      $this->gridname => [
        'gridcolumns' => $column,
        'sortcolumns' => $sortcolumn,
        'computefield' => ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'disc' => 'disc', 'total' => 'ext'],
        'headgridbtns' => $headgridbtns
      ],
    ];

    if ($this->companysetup->getserial($config['params'])) {
      $stockbuttons = ['save', 'delete', 'serialout'];
    } else {
      $stockbuttons = ['save', 'delete', 'showbalance'];
    }

    switch ($this->companysetup->getsystemtype($config['params'])) {
      case 'AIMS':
        if ($companyid == 0) { //main
          array_push($stockbuttons, 'stockinfo');
        } else if ($companyid == 10) { //afti
          array_push($stockbuttons, 'iteminfo');
        }
        break;
    }

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    if ($this->companysetup->isinvonly($config['params'])) {
      $obj[0]['inventory']['headgridbtns']['viewdistribution']['visible'] = false;
    }

    if ($viewcost == '0') {
      $obj[0]['inventory']['columns'][$markup]['type'] = 'coldel';
    }

    if (!$isexpiry) {
      $obj[0]['inventory']['columns'][$loc]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$expiry]['type'] = 'coldel';
    }

    switch ($config['params']['companyid']) {
      case 1: //vitaline
        $obj[0]['inventory']['columns'][$insurance]['type'] = 'coldel';
        break;
      case 10: //afti
        $obj[0]['inventory']['columns'][$rebate]['type'] = 'coldel';
        break;
      default:
        $obj[0]['inventory']['columns'][$rebate]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$insurance]['type'] = 'coldel';
        break;
    }

    $obj[0]['inventory']['columns'][$ref]['lookupclass'] = 'refrr';


    if (!$access['changeamt']) {
      // 3 - isamt
      $obj[0]['inventory']['columns'][$isamt]['readonly'] = true;
      // 4 - disc
      $obj[0]['inventory']['columns'][$disc]['readonly'] = true;
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $obj[0]['inventory']['descriptionrow'] = [];
      $obj[0]['inventory']['columns'][$itemdesc]['type'] = 'textarea';
      $obj[0]['inventory']['columns'][$itemdesc]['readonly'] = true;
      $obj[0]['inventory']['columns'][$itemdesc]['style'] = 'text-align: left; width: 350px;whiteSpace: normal;min-width:350px;max-width:350px;';
      $obj[0]['inventory']['columns'][$serial]['type'] = 'textarea';
      $obj[0]['inventory']['columns'][$serial]['style'] = 'text-align: left; width: 150px;whiteSpace: normal;min-width:150px;max-width:150px;';
      $obj[0]['inventory']['columns'][$serial]['readonly'] = true;
      $obj[0]['inventory']['columns'][$wh]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$whname]['type'] = 'lookup';
      $obj[0]['inventory']['columns'][$whname]['lookupclass'] = 'whstock';
      $obj[0]['inventory']['columns'][$whname]['action'] = 'lookupclient';
    } else {
      $obj[0]['inventory']['columns'][$itemdesc]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$stock_projectname]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$serial]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$whname]['type'] = 'coldel';
    }
    $obj[0]['inventory']['columns'][$barcode]['type'] = 'hidden';
    $obj[0]['inventory']['columns'][$barcode]['label'] = '';

    $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);

    return $obj;
  }

  public function createtabbutton($config)
  {
    $isexpiry = $this->companysetup->getisexpiry($config['params']);
    $ispallet = $this->companysetup->getispallet($config['params']);
    if ($isexpiry) {
      $tbuttons = ['poserial', 'pendingso', 'additem', 'quickadd', 'saveitem', 'deleteallitem'];
    } elseif ($ispallet) {
      $tbuttons = ['poserial', 'additem', 'saveitem', 'deleteallitem'];
    } else {
      $tbuttons = [];
      if ($config['params']['companyid'] == 10) { //afti
        array_push($tbuttons, 'saveitem', 'deleteallitem', 'pendingsq');
      } else {
        array_push($tbuttons, 'additem', 'quickadd', 'saveitem', 'deleteallitem', 'pendingso');
      }
    }

    $obj = $this->tabClass->createtabbutton($tbuttons);

    if ($config['params']['companyid'] == 10) { //afti
      $obj[2]['lookupclass'] = 'pendingsssummary';
      $obj[2]['action'] = 'pendingsssummary';
    }
    if ($isexpiry) {
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
    if ($config['params']['companyid'] == 10) { //afti
      $fields = ['docno', 'client', 'clientname', 'tin', 'dagentname'];
      $col1 = $this->fieldClass->create($fields);
      data_set($col1, 'client.lookupclass', 'customersi');
      data_set($col1, 'clientname.type', 'textarea');
      data_set($col1, 'tin.class', 'cstin sbccsreadonly');
    } else {
      $fields = ['docno', 'client', 'clientname', 'address', 'dprojectname'];
      $col1 = $this->fieldClass->create($fields);
      data_set($col1, 'client.lookupclass', 'customer');
    }
    data_set($col1, 'docno.label', 'Transaction#');

    if ($config['params']['companyid'] == 10) { //afti
      $fields = [['dateid', 'terms'], ['due', 'dvattype'], 'dewt', 'dacnoname', 'dwhname'];
    } else {
      $fields = [['dateid', 'terms'], ['due', 'dvattype'], 'dacnoname', 'dwhname'];
    }

    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'dacnoname.label', 'AR Account');
    data_set($col2, 'dacnoname.lookupclass', 'AR');

    $fields = ['yourref', ['cur', 'forex'], 'dbranchname'];
    if ($config['params']['companyid'] == 10) { //afti
      array_push($fields, 'ddeptname');      
    }
    $col3 = $this->fieldClass->create($fields);

    if ($config['params']['companyid'] == 10 || $config['params']['companyid'] == 12) { //afti, afti usd
      data_set($col3, 'yourref.label', 'Customer PO');
      data_set($col3, 'ddeptname.label', 'Department');
      data_set($col3, 'ddeptname.required', true);
      data_set($col3, 'dbranchname.required', true);
    }

    $fields = ['rem', 'creditinfo'];
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
    $data[0]['tin'] = '';
    $data[0]['yourref'] = '';
    $data[0]['address'] = '';
    $data[0]['ourref'] = '';
    $data[0]['rem'] = '';
    $data[0]['terms'] = '';
    $data[0]['forex'] = 1;
    $data[0]['cur'] = $this->companysetup->getdefaultcurrency($params);
    $data[0]['tax'] = 0;
    $data[0]['dagentname'] = '';
    $data[0]['agent'] = '';
    $data[0]['creditinfo'] = '';
    $data[0]['agentname'] = '';
    $data[0]['vattype'] = 'NON-VATABLE';
    $data[0]['contra'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', [$this->defaultContra]);
    $data[0]['acnoname'] = $this->coreFunctions->getfieldvalue('coa', 'acnoname', 'acno=?', [$data[0]['contra']]);
    $data[0]['wh'] = $this->companysetup->getwh($params);
    $name = $this->coreFunctions->getfieldvalue('client', 'clientname', 'client=?', [$data[0]['wh']]);
    $data[0]['whname'] = $name;
    $data[0]['projectid'] = '0';
    $data[0]['billid'] = '0';
    $data[0]['shipid'] = '0';
    $data[0]['shipcontactid'] = '0';
    $data[0]['billcontactid'] = '0';
    $data[0]['projectcode'] = '';
    $data[0]['projectname'] = '';
    $data[0]['branchcode'] = '';
    $data[0]['branchname'] = '';
    $data[0]['dbranchname'] = '';
    $data[0]['branch'] = 0;
    $data[0]['ddeptname'] = '';
    $data[0]['deptid'] = '0';
    $data[0]['dept'] = '';
    $data[0]['sotrno'] = '0';
    $data[0]['ewt'] = '';
    $data[0]['dewt'] = '';
    $data[0]['ewtrate'] = 0;

    return $data;
  }

  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $trno = $config['params']['trno'];
    $center = $config['params']['center'];
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
    $tablenum = $this->tablenum;
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
         client.tin,
         head.address,
         head.shipto,
         date_format(head.createdate,'%Y-%m-%d') as createdate,
         head.rem,
         ifnull(agent.client,'') as agent,
         ifnull(agent.clientname,'') as agentname,'' as dagentname,
         head.tax,
         head.vattype,
         '' as dvattype,
        head.ewt,
        '' as dewt,
        head.ewtrate,
         warehouse.client as wh,
         warehouse.clientname as whname,
         '' as dwhname,
         left(head.due,10) as due,
          head.projectid,
         ifnull(project.name,'') as projectname,
         '' as dprojectname,
         client.groupid,head.creditinfo,ifnull(project.code,'') as projectcode,
         head.billid, head.shipid,ifnull(b.client,'') as branchcode ,ifnull(b.clientname,'') as branchname, head.branch,'' as dbranchname,ifnull(d.client,'') as dept,ifnull(d.clientname,'') as deptname,head.deptid,'' as ddeptname,head.sotrno,
         head.billcontactid, head.shipcontactid ";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as agent on agent.client = head.agent
        left join coa on coa.acno=head.contra
        left join projectmasterfile as project on project.line=head.projectid
        left join client as b on b.clientid = head.branch
        left join client as d on d.clientid = head.deptid
        where head.trno = ? and num.doc=? and num.center = ?
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.clientid = client.clientid
        left join client as warehouse on warehouse.clientid = head.whid
        left join client as agent on agent.clientid = head.agentid
        left join coa on coa.acno=head.contra
        left join projectmasterfile as project on project.line=head.projectid
        left join client as b on b.clientid = head.branch
        left join client as d on d.clientid = head.deptid
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
      return  ['head' => $head, 'griddata' => ['inventory' => $stock], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg];
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
      if ($config['params']['companyid'] == 10) { //afti
        $this->autocreatestock($config, $data, $head['sotrno']);
      }
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
      if ($config['params']['companyid'] == 10) { //afti
        $checkacct = $this->othersClass->checkcoaacct(['AR1', 'TX2']);
      }


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

          if (floatval($crline) < floatval($totalso)) {
            $this->logger->sbcwritelog($trno, $config, 'POST', 'Above Credit Limit.');
            return ['status' => false, 'msg' => 'Posting failed. Overdue account or credit limit exceeded.'];
          }
        }
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
    stock.refx,
    stock.linex,
    item.barcode,
    item.itemname,
    stock.uom,
    stock.cost,
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
    ifnull(uom.factor,1) as uomfactor,
    round(case when (stock.Amt>0 and stock.iss>0 and stock.Cost>0) then (((((stock.Amt * stock.ISS) - (stock.Cost * stock.Iss)) / (stock.Amt * stock.Iss))/head.forex)*100) else 0 end,2) markup,stock.rebate,
    '' as bgcolor,
    '' as errcolor,
    prj.name as stock_projectname,
    stock.projectid as projectid,stock.sgdrate,stock.insurance,case when stock.noprint=0 then 'false' else 'true' end as noprint,
    concat(item.itemname,'\\n',ifnull(brand.brand_desc,''),'\\r\\n',ifnull(mm.model_name,''),'\\r\\n',ifnull(i.itemdescription,'')) as itemdescription,ifnull(group_concat(rr.serial separator '\\n\\r'),'') as serialno
    ";
    return $sqlselect;
  }

  public function openstock($trno, $config)
  {
    $companyid = $config['params']['companyid'];
    $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $qty_dec = 0;
    }

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
    left join iteminfo as i on i.itemid  = item.itemid 
    left join serialout as rr on rr.trno = stock.trno and rr.line = stock.line
    where stock.trno =?
    group by head.forex,item.brand,mm.model_name,item.itemid,stock.trno,stock.line,
    stock.refx,stock.linex,item.barcode,item.itemname,stock.uom,stock.cost,
    stock." . $this->hamt . ",stock." . $this->hqty . " ,
    FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . "),
    FORMAT(stock." . $this->dqty . "," . $qty_dec . "),
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . "),
    stock.encodeddate,stock.disc,stock.void,stock.ref,stock.whid,warehouse.client,warehouse.clientname,
    stock.loc,stock.expiry,item.brand,stock.rem,stock.palletid,stock.locid,pallet.name,
    location.loc,uom.factor,stock.rebate,
    prj.name,stock.projectid,stock.sgdrate,
    brand.brand_desc,i.itemdescription,stock.insurance,stock.noprint
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
    left join iteminfo as i on i.itemid  = item.itemid 
    left join serialout as rr on rr.trno = stock.trno and rr.line = stock.line
    where stock.trno =? group by head.forex,item.brand,mm.model_name,item.itemid,stock.trno,stock.line,
    stock.refx,stock.linex,item.barcode,item.itemname,stock.uom,stock.cost,
    stock." . $this->hamt . ",stock." . $this->hqty . " ,
    FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . "),
    FORMAT(stock." . $this->dqty . "," . $qty_dec . "),
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . "),
    stock.encodeddate,stock.disc,stock.void,stock.ref,stock.whid,warehouse.client,warehouse.clientname,
    stock.loc,stock.expiry,item.brand,stock.rem,stock.palletid,stock.locid,pallet.name,
    location.loc,uom.factor,stock.rebate,
    prj.name,stock.projectid,stock.sgdrate,
    brand.brand_desc,i.itemdescription,stock.insurance,stock.noprint order by line";

    $stock = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $stock;
  } //end function

  public function openstockline($config)
  {
    $companyid = $config['params']['companyid'];
    $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $qty_dec = 0;
    }

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
    left join iteminfo as i on i.itemid  = item.itemid 
    left join serialout as rr on rr.trno = stock.trno and rr.line = stock.line
    where stock.trno = ? and stock.line = ? 
    group by head.forex,item.brand,mm.model_name,item.itemid,stock.trno,stock.line,
    stock.refx,stock.linex,item.barcode,item.itemname,stock.uom,stock.cost,
    stock." . $this->hamt . ",stock." . $this->hqty . " ,
    FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . "),
    FORMAT(stock." . $this->dqty . "," . $qty_dec . "),
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . "),
    stock.encodeddate,stock.disc,stock.void,stock.ref,stock.whid,warehouse.client,warehouse.clientname,
    stock.loc,stock.expiry,item.brand,stock.rem,stock.palletid,stock.locid,pallet.name,
    location.loc,uom.factor,stock.rebate,
    prj.name,stock.projectid,stock.sgdrate,
    brand.brand_desc,i.itemdescription,stock.insurance,stock.noprint";
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
        return ['status' => true, 'msg' => 'Refresh Data.', 'data' => $data];
        break;
      default:
        return ['status' => false, 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
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

    $qry = "
    select head.trno,head.docno,left(head.dateid,10) as dateid,
     CAST(concat('Total SO Amt: ',round(sum(stock.ext),2)) as CHAR) as rem
    from hsshead as head
    left join hsrhead as hsrhead on hsrhead.sotrno = head.trno
    left join hsrstock as stock on stock.trno = hsrhead.trno
    left join glstock as glstock on glstock.refx = stock.trno and glstock.linex = stock.line
    where glstock.trno = ?
    group by head.docno,head.dateid, head.trno";
    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
    if (!empty($t)) {
      $startx = 550;
      $a = 0;
      foreach ($t as $key => $value) {
        // AO
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
        array_push($links, ['from' => $t[$key]->docno, 'to' => 'sc']);
        $a = $a + 100;
      }
    }

    //SC
    $scqry = "
      select hsrhead.docno,hsrhead.dateid,hsrhead.rem as rem
      from glhead as head
      left join glstock as stock on stock.trno = head.trno
      left join hsrstock as hsrstock on hsrstock.trno = stock.refx and hsrstock.line = stock.linex
      left join hsrhead as hsrhead on hsrhead.trno = hsrstock.trno
      where head.trno = ?
      group by hsrhead.docno,hsrhead.dateid, hsrhead.rem";

    $scdata = $this->coreFunctions->opentable($scqry, [$config['params']['trno']]);
    if (!empty($scdata)) {
      data_set(
        $nodes,
        'sc',
        [
          'align' => 'left',
          'x' => $startx,
          'y' => 100,
          'w' => 250,
          'h' => 80,
          'type' => $scdata[0]->docno,
          'label' => $scdata[0]->rem,
          'color' => 'orange',
          'details' => [$scdata[0]->dateid]
        ]
      );
      array_push($links, ['from' => 'sc', 'to' => 'si']);
      $a = $a + 100;
      //SI
      $qry = "
      select head.docno,
      date(head.dateid) as dateid,
      CAST(concat('Total SJ Amt: ', round(sum(stock.ext),2), ' - ', 'Balance: ', round(ar.bal, 2)) as CHAR) as rem,
      head.trno
      from glhead as head
      left join glstock as stock on head.trno = stock.trno
      left join arledger as ar on ar.trno = head.trno
      where head.trno=? and head.doc = 'AI'
      group by head.docno, head.dateid, head.trno, ar.bal";
      $t = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
      if (!empty($t)) {
        data_set(
          $nodes,
          'si',
          [
            'align' => 'left',
            'x' => 300 + $startx,
            'y' => 200,
            'w' => 250,
            'h' => 80,
            'type' => $t[0]->docno,
            'label' => $t[0]->rem,
            'color' => 'green',
            'details' => [$t[0]->dateid]
          ]
        );
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
      case 'makepayment':
        return $this->othersClass->generateShortcutTransaction($config, 0, 'SJCR');
        break;
      case 'navigation':
        return $this->othersClass->navigatedocno($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
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
        // if($data[$key]->refx!=0){
        //    $msg = ' Issued Qty is Greater than SO Qty ';
        // }
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
  public function additem($action, $config)
  {
    $companyid = $config['params']['companyid'];
    $ispallet = $this->companysetup->getispallet($config['params']);
    $uom = $config['params']['data']['uom'];
    $itemid = $config['params']['data']['itemid'];
    $trno = $config['params']['trno'];
    $disc = $config['params']['data']['disc'];
    $wh = $config['params']['data']['wh'];
    $loc = isset($config['params']['data']['loc']) ? $config['params']['data']['loc'] : '';
    $locid = isset($config['params']['data']['locid']) ? $config['params']['data']['locid'] : 0;
    $palletid = isset($config['params']['data']['palletid']) ? $config['params']['data']['palletid'] : 0;
    $expiry = '';
    if (isset($config['params']['data']['expiry'])) {
      $expiry = $config['params']['data']['expiry'];
    }
    $rebate = 0;
    $refx = 0;
    $linex = 0;
    $ref = '';
    $projectid = 0;
    $sgdrate = 0;
    $insurance = 0;
    $noprint = 'false';

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

    if (isset($config['params']['data']['sgdrate'])) {
      $sgdrate = $config['params']['data']['sgdrate'];
    } else {
      $sgdrate = $this->othersClass->getexchangerate('PHP', 'SGD');
    }

    if (isset($config['params']['data']['insurance'])) {
      $insurance = $config['params']['data']['insurance'];
    }
    if (isset($config['params']['data']['noprint'])) {
      $noprint = $config['params']['data']['noprint'];
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
    }
    $amt = $this->othersClass->sanitizekeyfield('amt', $amt);
    $qty = $this->othersClass->sanitizekeyfield('qty', $qty);

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
    $cur = $this->coreFunctions->getfieldvalue($this->head, 'cur', 'trno=?', [$trno]);
    $curtopeso = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
    $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);
    if ($companyid == 10) { //afti
      if ($disc != "") {
        $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor, 0, $cur, 0, 1, 1);
      } else {
        $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor, 0, $cur);
      }
    } else {
      $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor, 0, $cur);
    }


    if (floatval($curtopeso) == 0) {
      $curtopeso = 1;
    }

    $hamt = $computedata['amt'] * $curtopeso;
    if ($companyid == 10) { //afti
      if ($disc != "") {
        $hamt = number_format($computedata['amt'] * $curtopeso, 2, '.', '');
      }
    }
    $hamt = $this->othersClass->sanitizekeyfield('amt', $hamt);

    $data = [
      'trno' => $trno,
      'line' => $line,
      'itemid' => $itemid,
      $this->damt => $amt,
      $this->hamt => $hamt,
      $this->dqty => $qty,
      $this->hqty => $computedata['qty'],
      'ext' => $computedata['ext'],
      'disc' => $disc,
      'whid' => $whid,
      'refx' => $refx,
      'linex' => $linex,
      'ref' => $ref,
      'loc' => $loc,
      'expiry' => $expiry,
      'uom' => $uom,
      'locid' => $locid,
      'palletid' => $palletid,
      'rebate' => $rebate,
      'noprint' => $noprint
    ];

    if ($companyid == 10) {
      $data['projectid'] = $projectid;
      $data['sgdrate'] = $sgdrate;
      $data['insurance'] = $insurance;
    }

    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $data['editdate'] = $current_timestamp;
    $data['editby'] = $config['params']['user'];

    //insert item
    if ($action == 'insert') {
      $data['encodeddate'] = $current_timestamp;
      $data['encodedby'] = $config['params']['user'];
      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
        $havestock = true;
        $msg = 'Item was successfully added.';
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Qty' . $qty . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:' . $computedata['ext'] . ' Uom:' . $uom);
        if ($isnoninv == 0) {
          if ($ispallet) {
            $cost = $this->othersClass->computecostingpallet($data['itemid'], $data['whid'], $data['locid'], $data['palletid'], $trno, $line, $data['iss'], $config['params']['doc'], $config['params']);
          } else {
            $cost = $this->othersClass->computecosting($data['itemid'], $data['whid'], $data['loc'], $expiry, $trno, $line, $data['iss'], $config['params']['doc'], $config['params']['companyid']);
          }
          if ($cost != -1) {
            $this->coreFunctions->sbcupdate($this->stock, ['cost' => $cost], ['trno' => $trno, 'line' => $line]);

            //CHECK BELOW COST
            $belowcost = $this->othersClass->checkbelowcost($trno, $line, $config);
            if ($belowcost == 1) {
              $msg = '(' . $item[0]->barcode . ') Is this free of charge? Please check.';
            } elseif ($belowcost == 2) {
              $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'BELOW COST', 'editdate' => $current_timestamp], ['trno' => $trno, 'line' => $line]);
              $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
              $this->logger->sbcwritelog($trno, $config, 'STOCK', 'BELOW COST - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Qty' . $qty . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0');
              $msg = "(" . $item[0]->barcode . ") You can't issue this item/s because it's BELOW COST!!!";
            }
          } else {
            $havestock = false;
            $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'OUT_STOCK', 'editdate' => $current_timestamp], ['trno' => $trno, 'line' => $line]);
            $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
            $this->logger->sbcwritelog($trno, $config, 'STOCK', 'OUT OF STOCK - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Qty' . $qty . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0');
          }
        } else { //for noninventory get available cost for services if available
          if ($ispallet) {
            $cost = $this->othersClass->computecostingpallet($data['itemid'], $data['whid'], $data['locid'], $data['palletid'], $trno, $line, $data['iss'], $config['params']['doc'], $config['params']);
          } else {
            $cost = $this->othersClass->computecosting($data['itemid'], $data['whid'], $data['loc'], $expiry, $trno, $line, $data['iss'], $config['params']['doc'], $config['params']['companyid']);
          }

          if ($cost != -1) {
            $this->coreFunctions->sbcupdate($this->stock, ['cost' => $cost], ['trno' => $trno, 'line' => $line]);
          } else {
            $this->logger->sbcwritelog($trno, $config, 'STOCK', 'OUT OF STOCK - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Qty' . $qty . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0');
          }
        }
        if ($config['params']['companyid'] == 10) { //afti
          if ($this->setservedsqitems($refx, $linex) == 0) {
            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
            $this->setservedsqitems($refx, $linex);
            $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
            $return = false;
            $msg = "(" . $item[0]->barcode . ") Qty Received is Greater than SO Qty.";
          }
        } else {
          if ($this->setserveditems($refx, $linex) == 0) {
            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
            $this->setserveditems($refx, $linex);
            $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
            $return = false;
            $msg = "(" . $item[0]->barcode . ") Qty Received is Greater than RR Qty.";
          }
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
      if ($isnoninv == 0) {
        if ($ispallet) {
          $cost = $this->othersClass->computecostingpallet($data['itemid'], $data['whid'], $data['locid'], $data['palletid'], $trno, $line, $data['iss'], $config['params']['doc'], $config['params']);
        } else {
          $cost = $this->othersClass->computecosting($data['itemid'], $data['whid'], $data['loc'], $data['expiry'], $trno, $line, $data['iss'], $config['params']['doc'], $config['params']['companyid']);
        }
        if ($cost != -1) {
          $this->coreFunctions->sbcupdate($this->stock, ['cost' => $cost], ['trno' => $trno, 'line' => $line]);

          //CHECK BELOW COST
          $belowcost = $this->othersClass->checkbelowcost($trno, $line, $config);
          if ($belowcost == 1) {
            $msg = '(' . $item[0]->barcode . ') Is this free if charge? Please check.';
          } elseif ($belowcost == 2) {
            $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'BELOW COST', 'editdate' => $current_timestamp], ['trno' => $trno, 'line' => $line]);
            $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
            $this->logger->sbcwritelog($trno, $config, 'STOCK', 'BELOW COST - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Qty' . $qty . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0');
            $msg = "(" . $item[0]->barcode . ") You can't issue this item/s because it's BELOW COST!!!";
            $return = false;
          }
        } else {
          $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'OUT_STOCK', 'editdate' => $current_timestamp], ['trno' => $trno, 'line' => $line]);
          $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
          if ($config['params']['companyid'] == 10) { //afti
            $this->setservedsqitems($refx, $linex);
          } else {
            $this->setserveditems($refx, $linex);
          }
          $this->logger->sbcwritelog($trno, $config, 'STOCK', 'OUT OF STOCK - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0');
          $return = false;
          $msg = "(" . $item[0]->barcode . ") Out of Stock.";
        }
      } else { //for noninventory get available cost for services if available
        if ($ispallet) {
          $cost = $this->othersClass->computecostingpallet($data['itemid'], $data['whid'], $data['locid'], $data['palletid'], $trno, $line, $data['iss'], $config['params']['doc'], $config['params']);
        } else {
          $cost = $this->othersClass->computecosting($data['itemid'], $data['whid'], $data['loc'], $data['expiry'], $trno, $line, $data['iss'], $config['params']['doc'], $config['params']['companyid']);
        }
        if ($cost != -1) {
          $this->coreFunctions->sbcupdate($this->stock, ['cost' => $cost], ['trno' => $trno, 'line' => $line]);
        } else {
          $this->logger->sbcwritelog($trno, $config, 'STOCK', 'OUT OF STOCK - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0');
        }
      }
      if ($config['params']['companyid'] == 10) { //afti
        if ($this->setservedsqitems($refx, $linex) == 0) {
          $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
          $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
          $this->setservedsqitems($refx, $linex);
          $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
          $return = false;
          $msg = "(" . $item[0]->barcode . ") Qty Issued is Greater than SO Qty.";
        }
      } else {
        if ($this->setserveditems($refx, $linex) == 0) {
          $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
          $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
          $this->setserveditems($refx, $linex);
          $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
          $return = false;
          $msg = "(" . $item[0]->barcode . ") Qty Issued is Greater than SO Qty.";
        }
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
      if ($config['params']['companyid'] == 10) { //afti
        $this->setservedsqitems($data[$key]->refx, $data[$key]->linex);
      } else {
        $this->setserveditems($data[$key]->refx, $data[$key]->linex);
      }
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
    stock on stock.trno=head.trno where head.doc='SJ' and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry1 = $qry1 . " union all select glstock." . $this->hqty . " from glhead left join glstock on glstock.trno=
    glhead.trno where glhead.doc='SJ' and glstock.refx=" . $refx . " and glstock.linex=" . $linex;

    $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty == '') {
      $qty = 0;
    }
    return $this->coreFunctions->execqry("update hsostock set qa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
  }

  public function setservedsqitems($refx, $linex)
  {
    if ($refx == 0) {
      return 1;
    }
    $qry1 = "select stock." . $this->hqty . " from lahead as head left join lastock as
    stock on stock.trno=head.trno where head.doc ='AI' and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry1 = $qry1 . " union all select glstock." . $this->hqty . " from glhead left join glstock on glstock.trno=
    glhead.trno where glhead.doc='AI' and glstock.refx=" . $refx . " and glstock.linex=" . $linex;

    $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty == '') {
      $qty = 0;
    }
    return $this->coreFunctions->execqry("update hsrstock set sjqa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
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

    if ($config['params']['companyid'] == 10) { //afti
      $this->setservedsqitems($data[0]->refx, $data[0]->linex);
    } else {
      $this->setserveditems($data[0]->refx, $data[0]->linex);
    }

    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' barcode:' . $data[0]->barcode . ' Qty:' . $data[0]->isqty . ' Amt:' . $data[0]->isamt . ' Disc:' . $data[0]->disc . ' wh:' . $data[0]->wh . ' ext:' . $data[0]->ext);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
  } // end function

  public function getlatestprice($config)
  {
    $barcode = $config['params']['barcode'];
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
    $qry = "select docno,left(dateid,10) as dateid,round(amt,2) as amt,disc,uom from(select head.docno,head.dateid,
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
          order by dateid desc limit 5) as tbl order by dateid desc limit 1";
    $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $client, $trno, $center, $barcode, $client, $trno]);

    $usdprice = 0;
    $forex = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
    $cur = $this->coreFunctions->getfieldvalue($this->head, 'cur', 'trno=?', [$trno]);
    $dollarrate = $this->coreFunctions->getfieldvalue('forex_masterfile', 'dollartocur', 'cur=?', [$cur]);

    if (!empty($data)) {
      return ['status' => true, 'msg' => 'Found the latest price...', 'data' => $data];
    } else {
      $qry = "select amt,disc,uom, 'STOCKCARD'  as docno from item where barcode=?";
      $data = $this->coreFunctions->opentable($qry, [$barcode]);

      if ($this->companysetup->getisuomamt($config['params'])) {
        $data[0]->docno = 'UOM';
        $data[0]->amt = $this->coreFunctions->datareader("select ifnull(uom.amt,0) as value from item left join uom on uom.itemid=item.itemid and uom.uom=item.uom where item.barcode=?", [$barcode]);
      }

      if (floatval($forex) <> 1) {
        $usdprice = $this->coreFunctions->getfieldvalue('item', 'foramt', 'barcode=?', [$barcode]);
        if ($cur == '$') {
          $data[0]->amt = $usdprice;
        } else {
          $data[0]->amt = round($usdprice * $dollarrate, 2);
        }
      }

      if (floatval($data[0]->amt) == 0) {
        return ['status' => false, 'msg' => 'No Latest price found...'];
      } else {
        return ['status' => true, 'msg' => 'Found the latest price...', 'data' => $data];
      }
    }
  } // end function


  public function getsosummary($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    $msg = '';
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.amt,
        (stock.iss-stock.qa) as iss,stock.isamt,
        round((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
        stock.disc,stock.loc,stock.expiry,stock.projectid
        FROM hsohead as head left join hsostock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom where stock.trno = ? and stock.iss>stock.qa and stock.void=0
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
          $config['params']['data']['loc'] = $data[$key2]->loc;
          $config['params']['data']['expiry'] = $data[$key2]->expiry;
          $config['params']['data']['rem'] = '';
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->isamt;
          $config['params']['data']['projectid'] = $data[$key2]->projectid;
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
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => $msg];
  } //end function


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
          // $config['params']['data']['expiry'] = $data[$key2]->expiry;
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
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    $msg = '';
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.amt,
        (stock.iss-stock.qa) as iss,stock.isamt,
        round((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
        stock.disc,stock.loc,stock.expiry,stock.projectid
        FROM hsohead as head left join hsostock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom where stock.trno = ? and stock.line=? and stock.iss>stock.qa and stock.void=0
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
          $config['params']['data']['loc'] = $data[$key2]->loc;
          $config['params']['data']['expiry'] = $data[$key2]->expiry;
          $config['params']['data']['rem'] = '';
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->isamt;
          $config['params']['data']['projectid'] = $data[$key2]->projectid;
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
      select concat(stock.trno,stock.line) as keyid, stock.trno, stock.line, stock.itemid, item.barcode, item.itemname, stock.uom, so.docno, date(head.dateid) as dateid,
      (stock.iss-stock.sjqa) as iss,stock.isamt, stock.disc,stock.amt,stock.ext,stock.insurance,
      FORMAT(((stock.iss-stock.sjqa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,stock.projectid,stock.sgdrate
      from hsshead as so left join hsrhead as head on head.sotrno=so.trno left join hsrstock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid
      left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
      left join transnum on transnum.trno = head.trno
      where so.doc='AO' and stock.qa<>0 and stock.iss > stock.sjqa and stock.void = 0 and stock.trno=?
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
          $config['params']['data']['insurance'] = $data[$key2]->insurance;
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
      select concat(stock.trno,stock.line) as keyid, stock.trno, stock.line, stock.itemid, item.barcode, item.itemname, stock.uom, so.docno, date(head.dateid) as dateid,
      (stock.iss-(stock.qa+stock.sjqa)) as iss,stock.isamt, stock.disc,stock.amt,stock.ext,
      FORMAT(((stock.iss-(stock.qa+stock.sjqa))/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
      stock.projectid,stock.sgdrate,stock.insurance
      from hsshead as so left join hsrhead as head on head.sotrno=so.trno left join hsrstock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid
      left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
      left join transnum on transnum.trno = head.trno
      where so.doc='SQ' and stock.qa<>0 and stock.iss > stock.sjqa and stock.void = 0 and stock.trno=? and stock.line=?
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
          $config['params']['data']['insurance'] = $data[$key2]->insurance;
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
    $isvatexsales = $this->companysetup->getvatexsales($config['params']);
    $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);

    if ($companyid == 10) { //afti
      $qry = 'select head.dateid,head.client,head.tax,head.contra,head.cur,head.forex,stock.ext,wh.client as wh,ifnull(a.acno,"") as asset,ifnull(r.acno,"") as revenue,
      ifnull(e.acnoid,"") as expense,stock.isamt,stock.disc,stock.isqty,stock.cost,stock.iss,stock.fcost,stock.projectid,client.rev,stock.rebate,head.deptid,
      head.branch, head.yourref,head.sotrno,item.isnoninv
          from ' . $this->head . ' as head left join ' . $this->stock . ' as stock on stock.trno=head.trno
          left join item on item.itemid=stock.itemid left join client on client.client = head.client left join client as wh on wh.clientid = stock.whid 
          left join projectmasterfile as p on p.line = stock.projectid left join coa as a on a.acnoid = p.assetid left join coa as r on r.acnoid = p.revenueid left join coa as e on e.acnoid = p.expenseid 
          where head.trno=?';
    } else {
      $qry = 'select head.dateid,head.client,head.tax,head.contra,head.cur,head.forex,stock.ext,wh.client as wh,ifnull(item.asset,"") as asset,ifnull(item.revenue,"") as revenue,ifnull(item.expense,"") as expense,stock.isamt,stock.disc,stock.isqty,stock.cost,stock.iss,stock.fcost,head.projectid,client.rev,stock.rebate
          from ' . $this->head . ' as head left join ' . $this->stock . ' as stock on stock.trno=head.trno
          left join item on item.itemid=stock.itemid left join client on client.client = head.client left join client as wh on wh.clientid = stock.whid where head.trno=?';
    }

    $stock = $this->coreFunctions->opentable($qry, [$trno]);
    $tax = 0;
    $totalar = 0;
    $cost = 0;
    $fcost = 0;
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
        $cost = 0;
        $fcost = 0;
        $params = [];
        if ($this->companysetup->getisdiscperqty($config['params'])) {
          $discamt = $stock[$key]->isamt - ($this->othersClass->discount($stock[$key]->isamt, $stock[$key]->disc));
          $disc = $discamt * $stock[$key]->isqty;
        } else {
          $disc = ($stock[$key]->isamt * $stock[$key]->isqty) - ($this->othersClass->discount($stock[$key]->isamt * $stock[$key]->isqty, $stock[$key]->disc));
        }
        if ($vat !== 0) {
          if ($isvatexsales) {
            $tax = ($stock[$key]->ext * $tax2);
            $totalar = $totalar + $stock[$key]->ext;
          } else {
            $tax = ($stock[$key]->ext / $tax1);
            $tax = $stock[$key]->ext - $tax;
            $totalar = $totalar + $stock[$key]->ext;
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
        if (floatval($stock[$key]->isnoninv) == 0) {
          $cost = $stock[$key]->cost * $stock[$key]->iss;
          $fcost = $stock[$key]->fcost * $stock[$key]->iss;
        }
        $params = [
          'client' => $stock[$key]->client,
          'acno' => $stock[$key]->contra,
          'ext' => $stock[$key]->ext,
          'wh' => $stock[$key]->wh,
          'date' => $stock[$key]->dateid,
          'inventory' => $stock[$key]->asset !== '' ? $stock[$key]->asset : $invacct,
          'revenue' => $revacct,
          'expense' => $expense,
          'tax' =>  $tax,
          'discamt' => $disc,
          'cur' => $stock[$key]->cur,
          'forex' => $stock[$key]->forex,
          'cost' => $cost,
          'fcost' => $fcost,
          'projectid' => $stock[$key]->projectid,
          'rebate' => $stock[$key]->rebate
        ];
        if ($companyid == 10) { //afti
          $params['branch'] = $stock[$key]->branch;
          $params['deptid'] = $stock[$key]->deptid;
          $params['cogs'] = $stock[$key]->expense;
          $params['poref']  = $stock[$key]->yourref;
          $params['sotrno'] = $stock[$key]->sotrno;
        }
        if ($isvatexsales) {
          $this->distributionvatex($params, $config);
        } else {
          $this->distribution($params, $config);
        }
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
    $companyid = $config['params']['companyid'];
    $entry = [];
    $forex = $params['forex'];
    $cur = $params['cur'];
    $sales = 0;
    if (floatval($forex) == 0) {
      $forex = 1;
    }
    //AR
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
    if ($this->companysetup->getissalesdisc($config['params'])) {
      if (floatval($params['discamt']) != 0) {
        $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['SD1']);
        $entry = ['acnoid' => $input, 'client' => $params['client'], 'db' => ($params['discamt'] * $forex), 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($params['discamt']), 'projectid' => $params['projectid']];
        if ($companyid == 10) { //afti
          $entry['branch'] = $params['branch'];
          $entry['deptid'] = $params['deptid'];
        }
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }
    }


    //INV
    if (floatval($params['cost']) != 0) {
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['inventory']]);
      $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'db' => 0, 'cr' => $params['cost'], 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : $params['fcost'], 'fdb' => 0, 'projectid' => $params['projectid']];
      if ($companyid == 10) { //afti
        $entry['branch'] = $params['branch'];
        $entry['deptid'] = $params['deptid'];
      }
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

      //cogs
      $cogs =  $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['CG1']);
      if ($companyid == 10) { //afti
        if ($params['cogs'] != '') {
          $cogs =  $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['cogs']]);
        }
      }
      $entry = ['acnoid' => $cogs, 'client' => $params['wh'], 'db' => $params['cost'], 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : $params['fcost'], 'projectid' => $params['projectid']];
      if ($companyid == 10) { //afti
        $entry['branch'] = $params['branch'];
        $entry['deptid'] = $params['deptid'];
      }
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }

    if (floatval($params['tax']) != 0) {
      //sales
      $sales = ($params['ext'] - $params['rebate'] - $params['tax']);
      if ($this->companysetup->getissalesdisc($config['params'])) {
        $sales = round(($sales + $params['discamt']), 2);
      }
      if (floatval($sales) != 0) {
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['revenue']]);
        $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'cr' => ($sales * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : $sales, 'fdb' => 0, 'projectid' => $params['projectid']];
        if ($companyid == 10) { //afti
          $entry['branch'] = $params['branch'];
          $entry['deptid'] = $params['deptid'];
        }
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }


      // output tax
      $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['TX2']);
      $entry = ['acnoid' => $input, 'client' => $params['client'], 'cr' => ($params['tax'] * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : ($params['tax']), 'fdb' => 0, 'projectid' => $params['projectid']];
      if ($companyid == 10) { //afti
        $entry['branch'] = $params['branch'];
        $entry['deptid'] = $params['deptid'];
        $entry['projectid'] = 0;
      }
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    } else {
      //sales
      $sales = ($params['ext'] - $params['rebate']);
      if ($this->companysetup->getissalesdisc($config['params'])) {
        $sales = round(($sales + $params['discamt']), 2);
      }
      if (floatval($sales) != 0) {
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['revenue']]);
        $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'cr' => ($sales * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : $sales, 'fdb' => 0, 'projectid' => $params['projectid']];
        if ($companyid == 10) { //afti
          $entry['branch'] = $params['branch'];
          $entry['deptid'] = $params['deptid'];
        }
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
    $cur = $params['cur'];
    $sales = 0;
    if (floatval($forex) == 0) {
      $forex = 1;
    }

    //AR
    if (floatval($params['ext']) != 0) {
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['acno']]);
      $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'db' => (($params['ext'] + $params['tax']) * $forex), 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => floatval($forex) == 1 ? 0 : $params['ar'] + $params['tax'], 'fcr' => 0, 'projectid' => $params['projectid']];
      if ($companyid == 10) { //afti
        $entry['branch'] = $params['branch'];
        $entry['deptid'] = $params['deptid'];
        $entry['projectid'] = 0;
        $entry['poref'] = $params['poref'];
        if ($params['sotrno'] != 0) {
          $qttrno = $this->coreFunctions->getfieldvalue("hsrhead", "qtrno", "sotrno=?", [$params['sotrno']]);
          $entry['podate'] = $this->coreFunctions->getfieldvalue("hqshead", "due", "trno=?", [$qttrno]);
        }
      }
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }


    //disc
    if ($this->companysetup->getissalesdisc($config['params'])) {
      if (floatval($params['discamt']) != 0) {
        $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['SD1']);
        $entry = ['acnoid' => $input, 'client' => $params['client'], 'db' => ($params['discamt'] * $forex), 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($params['discamt']), 'projectid' => $params['projectid']];
        if ($companyid == 10) { //afti
          $entry['branch'] = $params['branch'];
          $entry['deptid'] = $params['deptid'];
        }
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }
    }

    //INV 
    if (floatval($params['cost']) != 0) {
      $alias = $this->coreFunctions->getfieldvalue('coa', 'left(alias,2)', 'acno=?', [$params['inventory']]);
      if ($alias != 'CG') {
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['inventory']]);
        $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'db' => 0, 'cr' => $params['cost'], 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : $params['fcost'], 'fdb' => 0, 'projectid' => $params['projectid']];
        if ($companyid == 10) { //afti
          $entry['branch'] = $params['branch'];
          $entry['deptid'] = $params['deptid'];
        }
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

        //cogs
        $cogs =  $params['expense'] == 0 ? $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['CG1']) : $params['expense'];
        $entry = ['acnoid' => $cogs, 'client' => $params['wh'], 'db' => $params['cost'], 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : $params['fcost'], 'projectid' => $params['projectid']];
        if ($companyid == 10) { //afti
          $entry['branch'] = $params['branch'];
          $entry['deptid'] = $params['deptid'];
        }
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
      if ($companyid == 10) { //afti
        $entry['branch'] = $params['branch'];
        $entry['deptid'] = $params['deptid'];;
      }
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }

    // output tax
    if ($params['tax'] != 0) {
      $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['TX2']);
      $entry = ['acnoid' => $input, 'client' => $params['client'], 'cr' => ($params['tax'] * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : ($params['tax']), 'fdb' => 0, 'projectid' => $params['projectid']];
      if ($companyid == 10) { //afti
        $entry['branch'] = $params['branch'];
        $entry['deptid'] = $params['deptid'];
        $entry['projectid'] = 0;
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
    if ($companyid == 10 || $companyid != 12) { //afti, not afti usd
    } else {
      $this->logger->sbcviewreportlog($config);
    }

    switch ($companyid) {
      case 10: //afti
        $sjoptions = $config['params']['dataparams']['radiosjafti'];
        $data = app($this->companysetup->getreportpath($config['params']))->report_ai_query($config['params']['dataid']);
        switch ($sjoptions) {
          case 'billingstatement':
            $str = app($this->companysetup->getreportpath($config['params']))->reportsalesinvoicepdf($config, $data);
            break;
          default:
            $str = app($this->companysetup->getreportpath($config['params']))->reportservicedrpdf($config, $data);
            break;
        }
        break;

      default:
        $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config['params']['dataid']);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
        break;
    }

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  private function autocreatestock($config, $data2, $trno)
  {
    $wh = $data2['wh'];
    $rows = [];
    $msg = '';
    $qry = "select concat(stock.trno,stock.line) as keyid, stock.trno, stock.line, stock.itemid, item.barcode, item.itemname, stock.uom, so.docno, date(head.dateid) as dateid,
    (stock.iss-stock.sjqa) as iss,stock.isamt, stock.disc,stock.amt,stock.ext,
    FORMAT(((stock.iss-stock.sjqa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
    stock.projectid,stock.sgdrate,stock.insurance
    from hsshead as so left join hsrhead as head on head.sotrno=so.trno left join hsrstock as stock on stock.trno=head.trno
    left join item on item.itemid=stock.itemid
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
    left join transnum on transnum.trno = head.trno
    where so.doc='AO' and stock.qa<>0 and  stock.iss > (stock.sjqa+stock.voidqty) and stock.void = 0 and item.isnoninv =  0  and so.trno=?
    union all
    select concat(stock.trno,stock.line) as keyid, stock.trno, stock.line, stock.itemid, item.barcode, item.itemname, stock.uom, so.docno, date(head.dateid) as dateid,
    (stock.iss-stock.sjqa) as iss,stock.isamt, stock.disc,stock.amt,stock.ext,
    FORMAT(((stock.iss-stock.sjqa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
    stock.projectid,stock.sgdrate,stock.insurance
    from hsshead as so left join hsrhead as head on head.sotrno=so.trno left join hsrstock as stock on stock.trno=head.trno
    left join item on item.itemid=stock.itemid
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
    left join transnum on transnum.trno = head.trno
    where so.doc='AO' and  stock.iss > (stock.sjqa+stock.voidqty) and stock.void = 0 and item.isnoninv =  1  and so.trno=?
    ";
    $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    if (!empty($data)) {
      foreach ($data as $key2 => $value) {
        $config['params']['data']['uom'] = $data[$key2]->uom;
        $config['params']['data']['itemid'] = $data[$key2]->itemid;
        $config['params']['trno'] = $config['params']['trno'];
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
        $config['params']['data']['insurance'] = $data[$key2]->insurance;
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

  public function recomputestock($head, $config)
  {
    $data = $this->openstock($head['trno'], $config);
    $data2 = json_decode(json_encode($data), true);
    $exec = true;
    $companyid = $config['params']['companyid'];
    foreach ($data2 as $key => $value) {
      $damt = $this->othersClass->sanitizekeyfield('amt', $data2[$key][$this->damt]);
      $dqty = $this->othersClass->sanitizekeyfield('qty', round($data2[$key][$this->dqty], $this->companysetup->getdecimal('qty', $config['params'])));
      if ($companyid == 10) { //afti
        if ($data[$key]->disc != "") {
          $computedata = $this->othersClass->computestock(
            $damt * $head['forex'],
            $data[$key]->disc,
            $dqty,
            $data[$key]->uomfactor,
            0,
            '',
            0,
            1
          );
        } else {
          $computedata = $this->othersClass->computestock(
            $damt * $head['forex'],
            $data[$key]->disc,
            $damt,
            $data[$key]->uomfactor,
            0
          );
        }
      } else {
        $computedata = $this->othersClass->computestock(
          $damt * $head['forex'],
          $data[$key]->disc,
          $dqty,
          $data[$key]->uomfactor,
          0
        );
      }
      $computedata['amt']  = number_format($computedata['amt'], 2, '.', '');
      $computedata['amt'] = $this->othersClass->sanitizekeyfield('amt', $computedata['amt']);

      $exec = $this->coreFunctions->execqry("update lastock set amt = " . $computedata['amt'] . " where trno = " . $head['trno'] . " and line=" . $data[$key]->line, "update");
    }
    return $exec;
  }
} //end class
