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

class sr
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'SERVICE RECEIVING';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => true, 'show' => true, 'showdate' => false];
  public $tablenum = 'transnum';
  public $head = 'srhead';
  public $hhead = 'hsrhead';
  public $stock = 'srstock';
  public $hstock = 'hsrstock';
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';
  private $stockselect;
  public $dqty = 'isqty';
  public $hqty = 'iss';
  public $damt = 'isamt';
  public $hamt = 'amt';
  public $fields = ['trno', 'docno', 'dateid', 'due', 'client', 'clientname', 'yourref', 'ourref', 'rem', 'terms', 'forex', 'cur', 'wh', 'address', 'agent', 'branch', 'deptid', 'shipid', 'billid', 'shipcontactid', 'billcontactid', 'vattype', 'tax'];
  public $except = ['trno', 'dateid', 'due'];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  public $showfilterlabel = [
    ['val' => 'draft', 'label' => 'Draft', 'color' => 'Primary'],
    ['val' => 'locked', 'label' => 'Locked', 'color' => 'Primary'],
    ['val' => 'posted', 'label' => 'Posted', 'color' => 'Primary'],
    ['val' => 'all', 'label' => 'All', 'color' => 'Primary'],
  ];
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
      'view' => 2644,
      'edit' => 2645,
      'new' => 2646,
      'save' => 2647,
      // 'change' => 2648, remove change doc
      'delete' => 2649,
      'print' => 2650,
      'lock' => 2651,
      'unlock' => 2652,
      'changeamt' => 2655,
      'post' => 2653,
      'unpost' => 2654,
      'additem' => 2656,
      'edititem' => 2657,
      'deleteitem' => 2658
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'yourref', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[5]['label'] = 'Customer PO';
    return $cols;
  }

  public function paramsdatalisting($config)
  {
    $companyid = $config['params']['companyid'];
    $isshortcutpo = $this->companysetup->getisshortcutpo($config['params']);

    $fields = [];
    if ($isshortcutpo) {
      $allownew = $this->othersClass->checkAccess($config['params']['user'], 2646);
      if ($allownew == '1') {
        array_push($fields, 'pickpo');
      }
    }
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'pickpo.label', 'PICK SERVICE QOUTATION');
    data_set($col1, 'pickpo.action', 'pendingqtssummary');
    data_set($col1, 'pickpo.lookupclass', 'pendingqtssummaryshortcut');
    data_set($col1, 'pickpo.confirmlabel', 'Proceed to pick SERVICE QOUTATION?');

    $fields = [];
    $data = [];
    $col2 = $this->fieldClass->create($fields);
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $fields = ['selectprefix', 'docno'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'docno.type', 'input');
        data_set($col2, 'docno.label', 'Search');
        data_set($col2, 'selectprefix.label', 'Search by');
        data_set($col2, 'selectprefix.type', 'lookup');
        data_set($col2, 'selectprefix.lookupclass', 'lookupsearchby');
        data_set($col2, 'selectprefix.action', 'lookupsearchby');
        $data = $this->coreFunctions->opentable("select '' as docno,'' as selectprefix");
        $data = $data[0];
        break;
    }

    return ['status' => true, 'data' => $data, 'txtfield' => ['col1' => $col1, 'col2' => $col2]];
  }

  public function loaddoclisting($config)
  {
    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));
    $itemfilter = $config['params']['itemfilter'];
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $condition = '';
    $limit = '';

    $join = '';
    $hjoin = '';
    $addparams = '';

    $searchfilter = $config['params']['search'];

    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'head.clientname', 'head.createby', 'head.editby', 'head.viewby', 'num.postedby', 'head.yourref'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    } else {
      $limit = 'limit 25';
    }

    switch ($itemfilter) {
      case 'draft':
        $condition = ' and num.postdate is null ';
        break;
      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
      case 'locked':
        $condition = ' and head.lockdate is not null ';
        break;
    }

    $companyid = $config['params']['companyid'];
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $dateid = "date_format(head.dateid,'%m-%d-%Y') as dateid";
        if ($searchfilter == "") $limit = 'limit 25';
        break;
      default:
        $dateid = "left(head.dateid,10) as dateid";
        if ($searchfilter == "") $limit = 'limit 150';
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
            $join = " left join srstock on srstock.trno = head.trno
            left join item on item.itemid = srstock.itemid left join item as item2 on item2.itemid = srstock.itemid
            left join model_masterfile as model on model.model_id = item.model 
            left join model_masterfile as model2 on model2.model_id = item2.model 
            left join frontend_ebrands as brand on brand.brandid = item.brand 
            left join frontend_ebrands as brand2 on brand2.brandid = item2.brand
            left join projectmasterfile as p on p.line = item.projectid 
            left join projectmasterfile as p2 on p2.line = item2.projectid ";

            $hjoin = " left join hsrstock on hsrstock.trno = head.trno
            left join item on item.itemid = hsrstock.itemid left join item as item2 on item2.itemid = hsrstock.itemid
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

    $qry = "select head.trno,head.docno,head.clientname,$dateid, 'DRAFT' as status,head.createby,head.editby,head.viewby,num.postedby ,head.yourref 
     from " . $this->head . " as head left join " . $this->tablenum . " as num 
     on num.trno=head.trno " . $join . " where head.doc=? and num.center=? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $addparams . " $filtersearch
     union all
     select head.trno,head.docno,head.clientname,$dateid,'POSTED' as status,head.createby,head.editby,head.viewby, num.postedby  ,head.yourref
     from " . $this->hhead . " as head left join " . $this->tablenum . " as num 
     on num.trno=head.trno " . $hjoin . " where head.doc=? and num.center=? and convert(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $addparams . " $filtersearch
     order by dateid desc,docno desc $limit";

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
    $step1 = $this->helpClass->getFields(['btnnew', 'customer', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
    $step2 = $this->helpClass->getFields(['btnedit', 'customer', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
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

    if ($this->companysetup->getisshowmanual($config['params'])) {
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => $config['params']['doc'], 'title' => strtoupper($this->modulename) . '_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
    }

    return $buttons;
  } // createHeadbutton

  public function createtab2($access, $config)
  {
    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrytransnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);

    $billshipdefault = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewbillingshipping']];
    $return['SHIPPING/BILLING ADDRESS'] = ['icon' => 'fa fa-map-marker-alt', 'customform' => $billshipdefault];

    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];
    return $return;
  }


  public function createTab($access, $config)
  {
    $companyid = $config['params']['companyid'];
    $action = 0;
    $itemdesc = 1;
    $isqty = 2;
    $uom = 3;
    $ref = 4;
    $stock_projectname = 5;
    $insurance = 6;
    $delcharge = 7;
    $itemname = 8;
    $barcode = 9;

    $gridcolumn = ['action', 'itemdescription', 'isqty', 'uom', 'ref', 'stock_projectname', 'insurance', 'delcharge', 'itemname', 'barcode'];
    $headgridbtns = ['itemvoiding', 'viewref', 'viewitemstockinfo', 'viewdiagram'];

    $tab = [
      $this->gridname => [
        'gridcolumns' => $gridcolumn,
        'computefield' => ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'disc' => 'disc', 'total' => 'ext'],
        'headgridbtns' => $headgridbtns
      ],
    ];

    $stockbuttons = ['save', 'delete', 'showbalance', 'iteminfo'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    if (!$access['changeamt']) {
      $obj[0]['inventory']['columns'][$isqty]['readonly'] = true;
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $obj[0]['inventory']['columns'][$action]['style'] = 'width: 180px;whiteSpace: normal;min-width:180px;max-width:180px';
      $obj[0]['inventory']['columns'][$stock_projectname]['label'] = "Item Group";
      $obj[0]['inventory']['columns'][$itemdesc]['type'] = 'textarea';
      $obj[0]['inventory']['columns'][$itemdesc]['readonly'] = true;
      $obj[0]['inventory']['columns'][$itemdesc]['style'] = 'text-align: left; width: 350px;whiteSpace: normal;min-width:350px;max-width:350px;';
    }

    $obj[0]['inventory']['columns'][$barcode]['type'] = 'hidden';
    $obj[0]['inventory']['columns'][$barcode]['label'] = '';
    $obj[0]['inventory']['columns'][$ref]['type'] = 'label';
    $insur = $this->othersClass->checkAccess($config['params']['user'], 3618);

    if ($insur) {
      $obj[0]['inventory']['columns'][$insurance]['readonly'] = false;
    }

    if ($companyid != 10) { //not afti
      $obj[0]['inventory']['columns'][$stock_projectname]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$insurance]['type'] = 'coldel';
    }

    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);

    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['pendingqts', 'additem', 'saveitem', 'deleteallitem'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];

    $fields = ['docno', 'client', 'clientname', 'dvattype'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.lookupclass', 'customer');
    data_set($col1, 'docno.label', 'Transaction#');

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      data_set($col1, 'clientname.type', 'textarea');
    }

    $fields = [['dateid', 'terms'], 'due', 'dwhname', 'dagentname'];
    $col2 = $this->fieldClass->create($fields);

    $fields = ['dbranchname', 'ddeptname', 'yourref', ['cur', 'forex']];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'dbranchname.required', true);
    data_set($col3, 'ddeptname.label', 'Department');
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      data_set($col3, 'yourref.label', 'Customer PO');
    } else {
      data_set($col3, 'yourref.label', 'PO#');
    }
    data_set($col3, 'yourref.required', true);


    $fields = ['rem'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'rem.required', false);

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
    $data[0]['agent'] = '';
    $data[0]['agentname'] = '';
    $data[0]['dagentname'] = '';
    $data[0]['branchcode'] = '';
    $data[0]['branchname'] = '';
    $data[0]['dbranchname'] = '';
    $data[0]['ddeptname'] = '';
    $data[0]['deptid'] = '0';
    $data[0]['dept'] = '';
    $data[0]['branch'] = 0;
    $data[0]['terms'] = '';
    $data[0]['forex'] = 1;
    $data[0]['cur'] = $this->companysetup->getdefaultcurrency($params);
    $data[0]['address'] = '';
    $data[0]['wh'] = $this->companysetup->getwh($params);
    $name = $this->coreFunctions->datareader("select clientname as value from client where client='" . $data[0]['wh'] . "'");
    $data[0]['whname'] = $name;
    $data[0]['shipcontactid'] = '0';
    $data[0]['billcontactid'] = '0';
    $data[0]['shipid'] = '0';
    $data[0]['billid'] = '0';
    $data[0]['dvattype'] = '';
    $data[0]['vattype'] = 'NON-VATABLE';
    $data[0]['tax'] = '0';
    return $data;
  }

  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
    if ($trno == 0) {
      $trno = $this->othersClass->readprofile('TRNO', $config);
      if ($trno == '') {
        $trno = $this->coreFunctions->datareader("select trno as value from " . $this->tablenum . " where doc=? and center=? order by trno desc limit 1", [$doc, $center]);
      }
      $config['params']['trno'] = $trno;
    } else {
      $this->othersClass->checkprofile('TRNO', $trno, $config);
    }
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
         left(head.dateid,10) as dateid, 
         head.clientname,
         head.address, 
         head.shipto, 
         date_format(head.createdate,'%Y-%m-%d') as createdate,
         head.rem,
         ifnull(head.agent, '') as agent, 
         ifnull(agent.clientname, '') as agentname,'' as dagentname,
         head.wh as wh,
         warehouse.clientname as whname,
         '' as dwhname, 
         left(head.due,10) as due, 
         head.projectid,ifnull(p.code,'') as projectcode,ifnull(p.name,'') as projectname,
         client.groupid,ifnull(b.client,'') as branchcode ,ifnull(b.clientname,'') as branchname, head.branch,'' as dbranchname,ifnull(d.client,'') as dept,ifnull(d.clientname,'') as deptname,head.deptid,'' as ddeptname,head.qtrno,head.billid,head.shipid,head.billcontactid,head.shipcontactid,
         head.vattype,head.tax";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as agent on agent.client = head.agent
        left join projectmasterfile as p on p.line = head.projectid
        left join client as b on b.clientid = head.branch
        left join client as d on d.clientid = head.deptid
        where head.trno = ? and num.center = ? 
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as agent on agent.client = head.agent
        left join projectmasterfile as p on p.line = head.projectid
        left join client as b on b.clientid = head.branch
        left join client as d on d.clientid = head.deptid
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
      return  ['head' => $head, 'griddata' => ['inventory' => $stock], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg];
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
    if ($isupdate) {
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      if ($this->coreFunctions->sbcinsert($this->head, $data)) {
        $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['client'] . ' - ' . $head['clientname']);
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

    $this->coreFunctions->execqry('delete from ' . $this->stock . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from stockinfotrans where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from headinfotrans where trno=?', 'delete', [$trno]);
    $this->othersClass->deleteattachments($config);

    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function


  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $qry = "select trno from " . $this->stock . " where trno=? and iss=0 limit 1";
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
      terms,rem,forex,yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,agent,wh,due,cur,branch,deptid,shipid,billid,shipcontactid,billcontactid,vattype,tax,qtrno,sgdrate)
      SELECT head.trno,head.doc, head.docno,head.client, head.clientname, head.address,head.shipto,
      head.dateid as dateid, head.terms, head.rem, head.forex,head.yourref, head.ourref,
      head.createdate,head.createby,head.editby,head.editdate, head.lockdate,head.lockuser,head.agent,head.wh,
      head.due,head.cur,head.branch,head.deptid,head.shipid,head.billid,head.shipcontactid,head.billcontactid,head.vattype,head.tax,head.qtrno,head.sgdrate FROM " . $this->head . " as head left join cntnum on cntnum.trno=head.trno
      where head.trno=? limit 1";
    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($posthead) {

      if (!$this->othersClass->postingheadinfotrans($config)) {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'An error occurred while posting head data.'];
      }

      if (!$this->othersClass->postingstockinfotrans($config)) {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'An error occurred while posting stock/s.'];
      }

      // for glstock
      $qry = "insert into " . $this->hstock . "(trno, line, uom, disc, rem, amt, isqty, isamt, iss, ext, qa, void, encodeddate, encodedby, editdate, editby, loc, expiry, fstatus, wh_currentqty, itemid, whid, stageid, ref, refx, linex, projectid,sgdrate,insurance,delcharge)
        SELECT trno, line, uom, disc, rem, amt, isqty, isamt, iss, ext, qa, void, encodeddate, encodedby, editdate, editby, loc, expiry, fstatus, wh_currentqty,  itemid, whid, stageid, ref, refx, linex, projectid,sgdrate ,insurance,delcharge
        FROM " . $this->stock . " where trno =?";
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        //update transnum
        $date = $this->othersClass->getCurrentTimeStamp();
        $data = ['postdate' => $date, 'postedby' => $config['params']['user']];
        $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
        $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from stockinfotrans where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from headinfotrans where trno=?", "delete", [$trno]);
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

    $sotrno = $this->coreFunctions->getfieldvalue($this->hhead, "sotrno", "trno=?", [$trno]);
    if ($sotrno) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, already served in Sales Order'];
    }

    $qry = "select trno from " . $this->hstock . " where trno=? and ((qa+sjqa+voidqty)>0 or void<>0)";
    $data = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($data)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, either already served or have item voided...'];
    }
    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    $qry = "insert into " . $this->head . "(trno,doc,docno,client,clientname,address,shipto,dateid,terms,rem,forex,
    yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,wh,due,cur,agent,branch,deptid,shipid,billid,billcontactid,shipcontactid,vattype,tax,qtrno,sgdrate)
    select head.trno, head.doc, head.docno, client.client, head.clientname, head.address, head.shipto,
    head.dateid as dateid, head.terms, head.rem, head.forex, head.yourref, head.ourref, head.createdate,
    head.createby, head.editby, head.editdate, head.lockdate, head.lockuser,head.wh,head.due,head.cur,head.agent,head.branch,head.deptid,
    head.shipid,head.billid,head.billcontactid,head.shipcontactid,head.vattype,head.tax,head.qtrno,head.sgdrate
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

      $qry = "insert into " . $this->stock . "(trno, line, uom, disc, rem, amt, isqty, isamt, iss, ext, qa, void, encodeddate, encodedby, editdate, editby, loc, expiry, fstatus, wh_currentqty,  itemid, whid, stageid, ref, refx, linex, projectid,sgdrate,insurance,delcharge)
      select trno, line, uom, disc, rem, amt, isqty, isamt, iss, ext, qa, void, encodeddate, encodedby, editdate, editby, loc, expiry, fstatus, wh_currentqty,itemid, whid, stageid, ref, refx, linex, projectid,sgdrate ,insurance,delcharge
      from " . $this->hstock . " where trno=?";
      //stock
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null where trno=?", 'update', [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from hstockinfotrans where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from hheadinfotrans where trno=?", "delete", [$trno]);
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
    $sqlselect = "select item.brand as brand,
    ifnull(mm.model_name,'') as model,
    item.itemid,
    stock.trno, 
    stock.line,
    item.barcode, 
    item.itemname,
    concat(item.itemname,'\\n',ifnull(brand.brand_desc,''),'\\r\\n',ifnull(mm.model_name,''),'\\r\\n',ifnull(i.itemdescription,'')) as itemdescription,
    stock.uom, 
    stock.iss,
    FORMAT(stock.isamt," . $this->companysetup->getdecimal('price', $config['params']) . ") as isamt,
    FORMAT(stock.isqty," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as isqty,
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext, 
    left(stock.encodeddate,10) as encodeddate,
    stock.disc, 
    case when stock.void=0 then 'false' else 'true' end as void,
    round((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
    stock.whid,
    warehouse.client as wh,
    warehouse.clientname as whname,
    stock.loc,stock.expiry,
    item.brand,
    stock.rem, 
    stock.ref,
    stock.refx,
    stock.linex,
    ifnull(uom.factor,1) as uomfactor,
    '' as bgcolor,
    case when stock.void=0 then '' else 'bg-red-2' end as errcolor,
    prj.name as stock_projectname,
    stock.projectid as projectid,stock.sgdrate,stock.insurance,stock.delcharge ";
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
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid
    where stock.trno =? 
    UNION ALL  
    " . $sqlselect . "  
    FROM $this->hstock as stock 
    left join item on item.itemid=stock.itemid 
    left join model_masterfile as mm on mm.model_id = item.model
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid 
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid
    where stock.trno =? order by line";

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
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid
    where stock.trno = ? and stock.line = ? ";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $stock;
  } // end function

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'createversion':
        $return = $this->posttrans($config);
        if ($return['status']) {
          return $this->othersClass->createversion($config);
        } else {
          return $return;
        }
        break;
      case 'additem':
        $return =  $this->additem('insert', $config);

        return $return;
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
      case 'deleteallitem':
        return $this->deleteallitem($config);
        break;
      case 'getopsummary':
        return $this->getopsummary($config);
        break;
      case 'getqtdetails':
        return $this->getqtdetails($config);
        break;
      case 'getqtsummary':
        return $this->getqtsummary($config);
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
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
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

    $qry = "select so.trno,so.docno,left(so.dateid,10) as dateid,
     CAST(concat('Total SO Amt: ',round(sum(s.ext),2)) as CHAR) as rem
     from hsohead as so 
     left join hsostock as s on s.trno = so.trno
     where so.trno = ? 
     group by so.trno,so.docno,so.dateid";
    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
    if (!empty($t)) {
      $startx = 550;
      $a = 0;
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
    CAST(concat('Total SJ Amt: ', round(sum(stock.ext),2), ' - ', 'Balance: ', round(ar.bal, 2)) as CHAR) as rem, 
    head.trno
    from glhead as head
    left join glstock as stock on head.trno = stock.trno
    left join arledger as ar on ar.trno = head.trno
    where stock.refx=?
    group by head.docno, head.dateid, head.trno, ar.bal
    union all 
    select head.docno,
    date(head.dateid) as dateid,
    CAST(concat('Total SJ Amt: ', round(sum(stock.ext),2), ' - ', 'Balance: ', round(sum(stock.ext),2)) as CHAR) as rem, 
    head.trno
    from lahead as head
    left join lastock as stock on head.trno = stock.trno
    where stock.refx=?
    group by head.docno, head.dateid, head.trno";
    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno'], $config['params']['trno']]);
    if (!empty($t)) {
      data_set(
        $nodes,
        'sj',
        [
          'align' => 'left',
          'x' => $startx,
          'y' => 100,
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
                'x' => $startx + 400,
                'y' => 100,
                'w' => 250,
                'h' => 80,
                'type' => $apvdata[$key2]->docno,
                'label' => $apvdata[$key2]->rem,
                'color' => 'red',
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
                'x' => $startx + 400,
                'y' => 200,
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

    $item = $this->coreFunctions->opentable("select item.itemid,item.amt,item.disc,'' as loc,'" . $wh . "' as wh, 1 as qty, uom from item where barcode=?", [$barcode]);
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
  public function additem($action, $config, $setlog = false)
  {
    $companyid = $config['params']['companyid'];
    $uom = $config['params']['data']['uom'];
    $itemid = $config['params']['data']['itemid'];
    $trno = $config['params']['trno'];
    $disc = $config['params']['data']['disc'];
    $wh = $config['params']['data']['wh'];
    $loc = $config['params']['data']['loc'];

    $insurance = 0;
    $delcharge = 0;
    $refx = 0;
    $linex = 0;
    $void = 'false';
    $rem = '';
    $expiry = '';
    $ref = '';
    $projectid = 0;
    $sgdrate = 0;

    if (isset($config['params']['data']['void'])) {
      $void = $config['params']['data']['void'];
    }

    if (isset($config['params']['data']['delcharge'])) {
      $delcharge = $config['params']['data']['delcharge'];
    }

    if (isset($config['params']['data']['ref'])) {
      $ref = $config['params']['data']['ref'];
    }

    if (isset($config['params']['data']['rem'])) {
      $rem = $config['params']['data']['rem'];
    }

    if (isset($config['params']['data']['expiry'])) {
      $expiry = $config['params']['data']['expiry'];
    }

    if (isset($config['params']['data']['refx'])) {
      $refx = $config['params']['data']['refx'];
    }
    if (isset($config['params']['data']['linex'])) {
      $linex = $config['params']['data']['linex'];
    }

    if (isset($config['params']['data']['projectid'])) {
      $projectid = $config['params']['data']['projectid'];
    }

    if (isset($config['params']['data']['insurance'])) {
      $insurance = $config['params']['data']['insurance'];
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
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

      if ($companyid == 10  || $companyid == 12) { //afti, afti usd
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

      if ($companyid == 10  || $companyid == 12) { //afti, afti usd
        $projectid = $config['params']['data']['projectid'];
      }
    }
    $amt = $this->othersClass->sanitizekeyfield('amt', $amt);
    $qty = $this->othersClass->sanitizekeyfield('qty', $qty);
    $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
    $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
    $factor = 1;
    if (!empty($item)) {
      $item[0]->factor = $this->othersClass->val($item[0]->factor);
      if ($item[0]->factor !== 0 ) $factor = $item[0]->factor;
    }
    $forex = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
    if ($companyid == 10) { //afti
      if ($disc != "") {
        $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor, 0, '', 0, 1, 1);
      } else {
        $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor);
      }
    } else {
      $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor);
    }

    $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);

    if (floatval($forex) == 0) {
      $forex = 1;
    }
    $hamt = $computedata['amt'] * $forex;
    if ($companyid == 10) { //afti
      if ($disc != "") {
        $hamt = number_format($computedata['amt'] * $forex, 2, '.', '');
      }
    }

    $hamt = $this->othersClass->sanitizekeyfield('amt', $hamt);

    $data = [
      'trno' => $trno,
      'line' => $line,
      'itemid' => $itemid,
      'isamt' => $amt,
      'amt' => $hamt,
      'isqty' => $qty,
      'iss' => $computedata['qty'],
      'ext' => $computedata['ext'],
      'disc' => $disc,
      'whid' => $whid,
      'loc' => $loc,
      'void' => $void,
      'uom' => $uom,
      'ref' => $ref,
      'expiry' => $expiry,
      'refx' => $refx,
      'linex' => $linex,
      'insurance' => $insurance,
      'delcharge' => $delcharge
    ];


    if ($companyid == 10  || $companyid == 12) { //afti, afti usd
      $data['projectid'] = $projectid;
      $data['sgdrate'] = $sgdrate;
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
      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
        $stockinfo_data = [
          'trno' => $trno,
          'line' => $line,
          'rem' => $rem
        ];
        $this->coreFunctions->sbcinsert('stockinfotrans', $stockinfo_data);

        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Uom:' . $uom . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:' . $computedata['ext'], $setlog ? $this->tablelogs : '');
        $row = $this->openstockline($config);
        return ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.']; //, 'reloaddata' => true
      } else {
        return ['status' => false, 'msg' => 'Add item Failed'];
      }
    } elseif ($action == 'update') {
      $return = true;
      $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
      if ($refx != 0) {
        if ($this->setserveditems($refx, $linex) === 0) {
          $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
          $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
          $this->setserveditems($refx, $linex);
          $return = false;
        }
      }
      return $return;
    }
  } // end function

  public function deleteallitem($config)
  {
    $isallow = true;
    $trno = $config['params']['trno'];

    $data = $this->coreFunctions->opentable('select refx,linex,stageid from ' . $this->stock . ' where trno=? and refx<>0', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from stockinfotrans where trno=?', 'delete', [$trno]);

    foreach ($data as $key => $value) {
      $this->setserveditems($data[$key]->refx, $data[$key]->linex);
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
    $qry = "delete from stockinfotrans where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);

    foreach ($data as $key => $value) {
      if ($data[$key]->refx != 0) {
        $this->setserveditems($data[$key]->refx, $data[$key]->linex);
      }
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

    $qry = "select docno,left(dateid,10) as dateid,round(amt,2) as amt,disc,uom,moq,mmoq from(select head.docno,head.dateid,
          stock.isamt as amt,stock.uom,stock.disc,item.moq,item.mmoq
          from lahead as head
          left join lastock as stock on stock.trno = head.trno
          left join cntnum on cntnum.trno=head.trno
          left join item on item.itemid = stock.itemid
          where head.doc = 'SJ' and cntnum.center = ?
          and item.barcode = ? and head.client = ?
          and stock.isamt <> 0
          UNION ALL
          select head.docno,head.dateid,stock.isamt as amt,
          stock.uom,stock.disc,item.moq,item.mmoq from glhead as head
          left join glstock as stock on stock.trno = head.trno
          left join item on item.itemid = stock.itemid
          left join client on client.clientid = head.clientid
          left join cntnum on cntnum.trno=head.trno 
          where head.doc = 'SJ' and cntnum.center = ?
          and item.barcode = ? and client.client = ?
          and stock.isamt <> 0
          order by dateid desc limit 5) as tbl order by dateid desc limit 1";
    $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $client, $center, $barcode, $client]);

    $usdprice = 0;
    $forex = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
    $cur = $this->coreFunctions->getfieldvalue($this->head, 'cur', 'trno=?', [$trno]);
    $dollarrate = $this->coreFunctions->getfieldvalue('forex_masterfile', 'dollartocur', 'cur=?', [$cur]);

    if (!empty($data)) {
      return ['status' => true, 'msg' => 'Found the latest purchase price...', 'data' => $data];
    } else {
      $qry = "select amt,disc,uom,moq,mmoq from item where barcode=?";
      $data = $this->coreFunctions->opentable($qry, [$barcode]);
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


  public function getposummaryqry($config)
  {
    return "
          select head.docno, head.client, head.clientname, head.address, head.rem, head.cur, head.forex, head.shipto, head.agent, head.terms, head.ourref, head.yourref, head.branch,
          item.itemid,stock.trno, stock.line, item.barcode,stock.uom, stock.amt,  stock.iss,stock.isamt as rrcost,head.vattype,head.tax,
          round(stock.iss/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
          stock.disc, ifnull(st.line,0) as stageid,stock.projectid,head.deptid,head.trno as qtrno,head.shipid,head.billid,head.billcontactid,head.shipcontactid,stock.sgdrate,
          head.deldate,item.insurance,head.sgdrate as qssgd,item.delcharge,ifnull(hi.taxdef,0) as taxdef,ifnull(head.termsdetails,'') as termsdetails
          FROM hqshead as head left join hqtstock as stock on stock.trno=head.trno left join transnum on transnum.trno=head.trno 
          left join hheadinfotrans as hi on hi.trno = head.trno 
          left join item on item.itemid=stock.itemid left join uom on uom.itemid=item.itemid and
          uom.uom=stock.uom left join stagesmasterfile as st on st.line = stock.stageid 
          where stock.trno = ? and transnum.center='" . $config['params']['center'] . "' and stock.iss<>stock.qa order by stock.sortline,stock.line";
  }


  public function getopsummary($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $center = $config['params']['center'];
    $rows = [];
    $optrno = 0;
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = $this->getposummaryqry($config);
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $center]);
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
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['projectid'] =  $data[$key2]->projectid;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->rrcost;
          $config['params']['data']['insurance'] = $data[$key2]->insurance;
          $config['params']['data']['delcharge'] = $data[$key2]->delcharge;
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
  }

  public function setserveditems($refx, $linex)
  {
    $qry1 = "select stock." . $this->hqty . " from srhead as head left join srstock as
      stock on stock.trno=head.trno where head.doc='SR' and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry1 = $qry1 . " union all select stock." . $this->hqty . " from hsrhead as head left join hsrstock as stock on stock.trno=
      head.trno where head.doc='SR' and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty === '') {
      $qty = 0;
    }
    return $this->coreFunctions->execqry("update hqtstock set qa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
  }

  public function getqtdetails($config)
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
        stock.disc,stock.loc,stock.expiry,stock.projectid,stock.sgdrate,item.insurance,head.sgdrate as qssgd,
        head.terms,head.billid,head.shipid,head.billcontactid, head.shipcontactid,head.vattype,head.tax,head.deldate,item.delcharge
        FROM hqshead as head left join hqtstock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and 
        uom.uom=stock.uom where stock.trno = ? and stock.line=? and stock.iss>stock.qa and stock.void=0 order by stock.sortline,stock.line
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
          $config['params']['data']['projectid'] =  $data[$key2]->projectid;
          $config['params']['data']['sgdrate'] =  $data[$key2]->sgdrate;
          $config['params']['data']['insurance'] =  $data[$key2]->insurance;
          $config['params']['data']['delcharge'] =  $data[$key2]->delcharge;
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
          //head
          $head = [];
          $head['sgdrate'] = $data[$key2]->qssgd;
          $head['qtrno'] = $data[$key2]->trno;
          $head['terms'] = $data[$key2]->terms;
          $head['tax'] = $data[$key2]->tax;
          $head['vattype'] = $data[$key2]->vattype;
          $head['billid'] = $data[$key2]->billid;
          $head['shipid'] = $data[$key2]->shipid;
          $head['billcontactid'] = $data[$key2]->billcontactid;
          $head['shipcontactid'] = $data[$key2]->shipcontactid;
          $head['due'] = $data[$key2]->deldate;

          $this->coreFunctions->sbcupdate($this->head, $head, ['trno' => $trno]);
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => $msg, 'reloadhead' => true];
  } //end function

  public function getqtsummary($config)
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
        stock.disc,stock.loc,stock.expiry,stock.sgdrate,item.insurance,head.sgdrate as qssgd,
        head.terms,head.billid,head.shipid,head.billcontactid, head.shipcontactid,head.vattype,head.tax,head.deldate,item.delcharge
        FROM hqshead as head left join hqtstock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and 
        uom.uom=stock.uom where stock.trno = ? and stock.iss>stock.qa and stock.void=0 order by stock.sortline,stock.line
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
          $config['params']['data']['sgdrate'] =  $data[$key2]->sgdrate;
          $config['params']['data']['insurance'] =  $data[$key2]->insurance;
          $config['params']['data']['delcharge'] =  $data[$key2]->delcharge;
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

          $head = [];
          $head['sgdrate'] = $data[$key2]->qssgd;
          $head['qtrno'] = $data[$key2]->trno;
          $head['terms'] = $data[$key2]->terms;
          $head['tax'] = $data[$key2]->tax;
          $head['vattype'] = $data[$key2]->vattype;
          $head['billid'] = $data[$key2]->billid;
          $head['shipid'] = $data[$key2]->shipid;
          $head['billcontactid'] = $data[$key2]->billcontactid;
          $head['shipcontactid'] = $data[$key2]->shipcontactid;
          $head['due'] = $data[$key2]->deldate;

          $this->coreFunctions->sbcupdate($this->head, $head, ['trno' => $trno]);
        } // end foreach

      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => $msg, 'reloadhead' => true];
  } //end function

  // report 

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
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
