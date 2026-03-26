<?php

namespace App\Http\Classes\modules\crm;

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

class op
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'SALES ACTIVITY';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => true, 'show' => true, 'showdate' => false];
  public $tablenum = 'transnum';
  public $head = 'ophead';
  public $hhead = 'hophead';
  public $stock = 'opstock';
  public $hstock = 'hopstock';
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';
  private $stockselect;
  public $dqty = 'isqty';
  public $hqty = 'iss';
  public $damt = 'isamt';
  public $hamt = 'amt';
  public $fields = ['trno', 'docno', 'dateid', 'due', 'client', 'clientname', 'yourref', 'ourref', 'rem', 'terms', 'tel', 'forex', 'cur', 'wh', 'address', 'agent', 'creditinfo', 'branch', 'deptid', 'compname', 'designation', 'contactname', 'contactno', 'email', 'source', 'sourceid', 'industry', 'shipid', 'billid', 'shipcontactid', 'billcontactid', 'participantid'];
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
      'view' => 2564,
      'edit' => 2565,
      'new' => 2566,
      'save' => 2567,
      'change' => 2568,
      'delete' => 2569,
      'print' => 2570,
      'lock' => 2571,
      'unlock' => 2572,
      'changeamt' => 2573,
      'crlimit' => 2574,
      'post' => 2575,
      'unpost' => 2576,
      'additem' => 2577,
      'edititem' => 2578,
      'deleteitem' => 2579
    );
    return $attrib;
  }


  public function createdoclisting($config)
  {
    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'listsource', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
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
    $join = '';
    $hjoin = '';
    $addparams = '';

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

          if (isset($test)) {
            $join = " left join opstock on opstock.trno = head.trno
            left join item on item.itemid = opstock.itemid left join item as item2 on item2.itemid = opstock.itemid
            left join model_masterfile as model on model.model_id = item.model 
            left join model_masterfile as model2 on model2.model_id = item2.model 
            left join frontend_ebrands as brand on brand.brandid = item.brand 
            left join frontend_ebrands as brand2 on brand2.brandid = item2.brand
            left join projectmasterfile as p on p.line = item.projectid 
            left join projectmasterfile as p2 on p2.line = item2.projectid ";

            $hjoin = " left join hopstock as opstock on opstock.trno = head.trno
            left join item on item.itemid = opstock.itemid left join item as item2 on item2.itemid = opstock.itemid
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
      $searchfield = ['head.docno', 'head.clientname', 'head.source', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
      $limit = "";
    }


    $qry = "select head.dateid as date2,head.trno,head.docno,head.clientname,$dateid, 'DRAFT' as status,head.createby,head.editby,head.viewby,num.postedby  ,head.source
     from " . $this->head . " as head left join " . $this->tablenum . " as num 
     on num.trno=head.trno " . $join . " where head.doc=? and num.center=? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $addparams . " " . $filtersearch . "
     union all
     select head.dateid as date2,head.trno,head.docno,head.clientname,$dateid,'POSTED' as status,head.createby,head.editby,head.viewby, num.postedby  ,head.source
     from " . $this->hhead . " as head left join " . $this->tablenum . " as num 
     on num.trno=head.trno " . $hjoin . " where head.doc=? and num.center=? and convert(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $addparams . " " . $filtersearch . "
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
      'help',
      'others'
    );


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
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => $config['params']['doc'], 'title' => strtoupper($config['params']['doc']) . '_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
    }

    return $buttons;
  } // createHeadbutton


  public function createtab2($access, $config)
  {
    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrytransnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);

    $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrycalllog', 'label' => 'Call Log Entry']];
    $sku = $this->tabClass->createtab($tab, []);

    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];
    $return['Call Log Entry'] = ['icon' => 'fa fa-envelope', 'tab' => $sku];
    return $return;
  }

  public function createTab($access, $config)
  {
    $companyid   = $config['params']['companyid'];
    $iscreateversion = $this->companysetup->getiscreateversion($config['params']);
    $action = 0;
    $itemdesc = 1;
    $isqty = 2;
    $uom = 3;
    $isamt = 4;
    $disc = 5;
    $ext = 6;
    $markup = 7;
    $qa = 8;
    $void = 9;
    $itemname = 10;
    $ref = 11;
    $stock_projectname = 12;
    $barcode = 13;

    $column = ['action', 'itemdescription', 'isqty', 'uom', 'isamt', 'disc', 'ext', 'qa', 'void', 'ref', 'itemname', 'stock_projectname', 'barcode'];
    $sortcolumn = ['action', 'itemdescription', 'isqty', 'uom', 'isamt', 'disc', 'ext', 'qa', 'void', 'ref', 'itemname', 'stock_projectname', 'barcode'];

    $headgridbtns = ['itemvoiding', 'viewref', 'viewdiagram'];

    if ($this->companysetup->getisiteminfo($config['params'])) {
      array_push($headgridbtns, 'viewitemstockinfo');
    }

    $tab = [
      $this->gridname => [
        'gridcolumns' => $column, 'sortcolumns' => $sortcolumn,
        'computefield' => ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'disc' => 'disc', 'total' => 'ext'], 'headgridbtns' => $headgridbtns
      ],
    ];

    $stockbuttons = ['save', 'delete', 'showbalance'];

    array_push($stockbuttons, 'iteminfo');

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0]['inventory']['columns'][$action]['style'] = 'width:170px;whiteSpace: normal;min-width:170px;';

    if ($iscreateversion) {
    } else {
      $obj[0]['inventory']['columns'][$ref]['type'] = 'coldel';
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $obj[0]['inventory']['descriptionrow'] = [];
      $obj[0]['inventory']['columns'][$itemdesc]['type'] = 'textarea';
      $obj[0]['inventory']['columns'][$itemdesc]['readonly'] = true;
      $obj[0]['inventory']['columns'][$itemdesc]['style'] = 'text-align: left; width: 350px;whiteSpace: normal;min-width:350px;max-width:350px;';
    } else {
      $obj[0]['inventory']['columns'][$itemdesc]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$stock_projectname]['type'] = 'coldel';
    }

    if (!$access['changeamt']) {
      $obj[0]['inventory']['columns'][$isamt]['readonly'] = true;
      $obj[0]['inventory']['columns'][$disc]['readonly'] = true;
    }

    $obj[0]['inventory']['columns'][$barcode]['type'] = 'hidden';
    $obj[0]['inventory']['columns'][$barcode]['label'] = '';

    $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $companyid = $config['params']['companyid'];
    $iscreateversion = $this->companysetup->getiscreateversion($config['params']);
    if ($iscreateversion) {
      $tbuttons = ['pendingqt', 'additem', 'quickadd', 'saveitem', 'deleteallitem'];
    } else {
      switch ($companyid) {
        case 3: //conti
          $tbuttons = ['pendingqt', 'additem', 'quickadd', 'saveitem', 'deleteallitem'];
          break;
        case 10: //afti
        case 12: //afti usd
          $tbuttons = ['additem', 'saveitem', 'deleteallitem'];
          if ($this->othersClass->checkAccess($config['params']['user'], 2875)) {
            array_push($tbuttons, 'generateclient'); // CREATE PROFILE
          }
          break;
        default:
          $tbuttons = ['additem', 'quickadd', 'saveitem', 'deleteallitem'];
          break;
      }
    }


    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];
    $fields = ['docno', 'client', 'clientname'];

    if ($companyid != 10 && $companyid != 12) { //not afti & not afti usd
      array_push($fields, 'address');
    }else{
      array_push($fields, 'qtno');
    }

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.lookupclass', 'customer');
    data_set($col1, 'client.required', false);
    data_set($col1, 'clientname.label', 'Company Name');
    data_set($col1, 'docno.label', 'Transaction#');
    data_set($col1, 'address.type', 'textarea');
    data_set($col1, 'qtno.addedparams', ['client']);


    if ($companyid == 10 || $companyid == 12) { //afti,afti usd
      data_set($col1, 'clientname.type', 'textarea');
    }

    $fields = ['dateid', 'contactname', 'tel', 'ourref', 'designation', 'email'];
    $col2 = $this->fieldClass->create($fields);
    //data_set($col2, 'terms.required', true);
    data_set($col2, 'email.label', 'Email Address');
    data_set($col2, 'ourref.label', 'Department');
    data_set($col2, 'ourref.type', 'cinput');
    data_set($col2, 'ourref.maxlength', '100');
    data_set($col2, 'contactname.type', 'lookup');
    data_set($col2, 'contactname.lookupclass', 'contactpersonnumber');
    data_set($col2, 'contactname.action', 'lookupcustomercontact_op');
    data_set($col2, 'contactname.addedparams', ['client']);
    data_set($col2, 'tel.label', 'Contact Number');
    data_set($col2, 'designation.type', 'cinput');
    data_set($col2, 'designation.maxlength', '150');
    data_set($col2, 'email.type', 'cinput');
    data_set($col2, 'email.maxlength', '150');

    $fields = ['dbranchname', 'dagentname',  'source', 'sourcename', 'participant', 'industry'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'dbranchname.required', true);
    data_set($col3, 'dagentname.label', 'Sales Person');
    data_set($col3, 'industry.type', 'cinput');
    data_set($col3, 'industry.maxlength', '100');

    $fields = [['cur', 'forex'], 'rem'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'rem.required', true);

    return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4];
  }

  public function createnewtransaction($docno, $params)
  {
    $agent = "";
    $agentname = "";
    $branch = 0;
    $agentid = 0;
    $branchcode = "";
    $branchname = "";

    if ($params['companyid'] == 10) { //afti
      $salesperson_qry = "
      select ag.client as agent, ag.clientname as agentname, ag.clientid as agentid,
      branch.clientid as branchid, branch.client as branchcode, branch.clientname as branchname,
      ag.tel2 as contactno
      from client as ag
      left join client as branch on branch.clientid = ag.branchid
      where ag.clientid = ?";
      $salesperson_res = $this->coreFunctions->opentable($salesperson_qry, [$params['adminid']]);
      if (!empty($salesperson_res)) {
        $agent = $salesperson_res[0]->agent;
        $agentid = $salesperson_res[0]->agentid;
        $agentname = $salesperson_res[0]->agentname;
        $branch = $salesperson_res[0]->branchid;
        $branchcode = $salesperson_res[0]->branchcode;
        $branchname = $salesperson_res[0]->branchname;
      }
    }

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
    $data[0]['tel'] = '';

    $data[0]['agent'] = $agent;
    $data[0]['agentid'] = $agentid;
    $data[0]['agentname'] = $agentname;
    $data[0]['branch'] = $branch;
    $data[0]['branchcode'] = $branchcode;
    $data[0]['branchname'] = $branchname;

    $data[0]['terms'] = '';
    $data[0]['forex'] = 1;
    $data[0]['cur'] = $this->companysetup->getdefaultcurrency($params);
    $data[0]['address'] = '';
    $data[0]['creditinfo'] = '';
    $data[0]['wh'] = $this->companysetup->getwh($params);
    $name = $this->coreFunctions->datareader("select clientname as value from client where client='" . $data[0]['wh'] . "'");
    $data[0]['whname'] = $name;
    $data[0]['dagentname'] = '';
    $data[0]['dbranchname'] = '';
    $data[0]['ddeptname'] = '';
    $data[0]['deptid'] = '0';
    $data[0]['dept'] = '';
    $data[0]['compname'] = '';
    $data[0]['contactname'] = '';
    $data[0]['contactno'] = '';
    $data[0]['designation'] = '';
    $data[0]['email'] = '';
    $data[0]['source'] = '';
    $data[0]['sourceid'] = 0;
    $data[0]['sourcename'] = ' ';
    $data[0]['industry'] = '';
    $data[0]['shipcontactid'] = '0';
    $data[0]['billcontactid'] = '0';
    $data[0]['shipid'] = '0';
    $data[0]['billid'] = '0';
    $data[0]['participant'] = '';
    $data[0]['participantid'] = '0';
    $data[0]['qtno'] = '';
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
         head.tel, 
         head.compname, 
         head.contactname, 
         head.contactno, 
         head.designation, 
         head.email, 
         head.source, 
         head.sourceid, 
         case
          when head.source = 'Exhibit' then ex.title
          when head.source = 'Seminar' then sem.title
          when head.source = 'Others' then ifnull(source.description, ' ')
          when head.source = 'Principal Leads' then projectx.name
          else ' '
        end as sourcename,
         date_format(head.createdate,'%Y-%m-%d') as createdate,
         head.rem,
         ifnull(head.agent, '') as agent, ifnull(agent.clientid,0) as agentid,
         ifnull(agent.clientname, '') as agentname,'' as dagentname,
         head.wh as wh,
         warehouse.clientname as whname,
         '' as dwhname, 
         left(head.due,10) as due, 
         client.groupid,head.creditinfo,
         head.projectid,ifnull(project.code,'') as projectcode,
         ifnull(project.name,'') as projectname,
         '' as dprojectname,ifnull(b.client,'') as branchcode ,ifnull(b.clientname,'') as branchname, head.branch,'' as dbranchname,ifnull(d.client,'') as dept,ifnull(d.clientname,'') as deptname,head.deptid,'' as ddeptname,
         head.industry,head.billid,head.shipid,head.billcontactid,head.shipcontactid,
         head.participantid,
         case 
          when att.clientid = 0 then ifnull(att.companyname, ' ')
          else ifnull(partici.clientname, ' ')
         end as participant , (select docno from qshead where qshead.optrno=head.trno
                               union all
                               select docno from hqshead where hqshead.optrno=head.trno) as qtno,

                              (select trno from qshead where qshead.optrno=head.trno
                               union all
                               select trno from hqshead where hqshead.optrno=head.trno) as quotation";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as agent on agent.client = head.agent
        left join client as b on b.clientid = head.branch
        left join client as d on d.clientid = head.deptid
        left join projectmasterfile as project on project.line=head.projectid
        left join exhibit as ex on head.sourceid = ex.line
        left join seminar as sem on head.sourceid = sem.line
        left join projectmasterfile as projectx on projectx.line = head.sourceid
        left join source as source on source.line = head.sourceid
        left join attendee as att on head.participantid = att.line
        left join client as partici on partici.clientid = att.clientid
        left join qshead as quotation on quotation.optrno=head.trno 
        where head.trno = ? and num.center = ? 
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as agent on agent.client = head.agent
        left join client as b on b.clientid = head.branch
        left join client as d on d.clientid = head.deptid
        left join projectmasterfile as project on project.line=head.projectid
        left join exhibit as ex on head.sourceid = ex.line
        left join seminar as sem on head.sourceid = sem.line
        left join projectmasterfile as projectx on projectx.line = head.sourceid
        left join source as source on source.line = head.sourceid
        left join attendee as att on head.participantid = att.line
        left join client as partici on partici.clientid = att.clientid
        left join hqshead as quotation on quotation.optrno=head.trno
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
    $data = [];

    if ($isupdate) {
      unset($this->fields[1]);
      unset($head['docno']);
    }

    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key], '', $config['params']['companyid']);
        } //end if    
      }
    }

    // $data['due'] = $this->othersClass->computeterms($data['dateid'], $data['due'], $data['terms']);
    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    if ($isupdate) {
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
      if ($head['sourceid'] != 0) {
        $this->coreFunctions->sbcupdate("attendee", ["optrno" => 0], ['optrno' => $head['trno']]);
        $this->coreFunctions->sbcupdate("attendee", ["optrno" => $head['trno'],'salesid'=>$head['agentid'],'salesperson'=>$head['agentname']], ['exhibitid' => $head['sourceid'], 'line' => $head['participantid']]);
      }



     if($head['quotation'] != 0) { //may trno na , trno nung unang nainsert na docno
            //check kung posted o unposted yung qs na kinuha sa lookup
          $unposted_olddocno = $this->coreFunctions->getfieldvalue("qshead", "docno", "trno=?", [$head['quotation']]);
       
          if($unposted_olddocno){ //unposted yung qs
           if($unposted_olddocno != $head['qtno']){ //update yung old na unposted
            $updateold= $this->coreFunctions->execqry("update qshead set optrno = 0 where docno = ?",  'update', [$unposted_olddocno]);
            if($updateold){
              //check kung yung bagong galing sa lookup ay posted o unposted
              $unposted_newdocno = $this->coreFunctions->getfieldvalue("qshead", "docno", "docno=?", [$head['qtno']]);
              if($unposted_newdocno){
              $this->coreFunctions->execqry("update qshead set optrno = ? where docno = ?",  'update', [$head['trno'], $head['qtno']]);
              }else{//posted yung bagong galing sa lookup
              $this->coreFunctions->execqry("update hqshead set optrno = ? where docno = ?",  'update', [$head['trno'], $head['qtno']]);
              }
            }
           }
          }else{ //posted yung qs
            $posted_olddocno = $this->coreFunctions->getfieldvalue("hqshead", "docno", "trno=?", [$head['quotation']]);
            if($posted_olddocno != $head['qtno']){
            $updateold= $this->coreFunctions->execqry("update hqshead set optrno = 0 where docno = ?",  'update', [$posted_olddocno]);
            //check kung yung bagong docno na galing sa lookup ay posted o unposted

            $posted_newdocno = $this->coreFunctions->getfieldvalue("hqshead", "docno", "docno=?", [$head['qtno']]);
            if($posted_newdocno){ //posted
            $this->coreFunctions->execqry("update hqshead set optrno = ? where docno = ?",  'update', [$head['trno'], $head['qtno']]);
            }else{//unposted 
            $this->coreFunctions->execqry("update qshead set optrno = ? where docno = ?",  'update', [$head['trno'], $head['qtno']]);
            }
           }
          }

     }else{
           if ($head['qtno'] != '') {
            //check kung posted o unposted yung galing sa lookup
            $unposted_olddocno = $this->coreFunctions->getfieldvalue("qshead", "docno", "docno=?", [$head['qtno']]);
            if($unposted_olddocno){
             $this->coreFunctions->execqry("update qshead set optrno = ? where docno = ?",  'update', [$head['trno'], $head['qtno']]);
            }else{
            $posted_olddocno = $this->coreFunctions->getfieldvalue("hqshead", "docno", "docno=?", [$head['qtno']]);
            if($posted_olddocno){
              $this->coreFunctions->execqry("update hqshead set optrno = ? where docno = ?",  'update', [$head['trno'], $head['qtno']]);
            }
            }
          }
     }


    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $insert = $this->coreFunctions->sbcinsert($this->head, $data);
      if ($head['sourceid'] != 0) {
        $this->coreFunctions->sbcupdate("attendee", ["optrno" => $head['trno'],'salesid'=>$head['agentid'],'salesperson'=>$head['agentname']], ['exhibitid' => $head['sourceid'], 'line' => $head['participantid']]);
      }


      
    if ($head['qtno'] != '') {
           $unposted_qs = $this->coreFunctions->getfieldvalue("qshead", "docno", "docno=?", [$head['qtno']]);
           if($unposted_qs){
            $this->coreFunctions->execqry("update qshead set optrno = ? where docno = ?",  'update', [$head['trno'], $head['qtno']]);
           }else{
             $posted_qs = $this->coreFunctions->getfieldvalue("hqshead", "docno", "docno=?", [$head['qtno']]);
             if($posted_qs){
              $this->coreFunctions->execqry("update hqshead set optrno = ? where docno = ?",  'update', [$head['trno'], $head['qtno']]);
             }
           }
          
      }

      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['client'] . ' - ' . $head['clientname']);
      //calllogs
      if ($insert) {
        $call = [];
        $call['dateid'] = date("Y/m/d");
        $call['starttime'] = date_format(date_create($data['createdate']), "H:i:s");
        $call['trno'] = $head['trno'];
        $call['contact']=$head['contactname'];
        $call['status']= 'Active';
         $call['rem']= $head['rem'];
        $call['rem']= $head['rem'];
      
        if ($head['source'] == 'Call - Inbound') {
             $call['calltype'] = 'Inbound';
          } elseif ($head['source'] == 'Call - Outbound') {
              $call['calltype'] = 'Outbound';
          } else {
              $call['calltype'] = '';
          }
        $this->coreFunctions->sbcinsert('calllogs', $call);
        //end call logs
        $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', 'CREATE CALL LOG');
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
    $this->coreFunctions->execqry('delete from stockinfotrans where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from calllogs where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry("update attendee set optrno = 0,salesid =0,salesperson ='' where optrno=?", "update", [$trno]);
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

    $client = $this->coreFunctions->getfieldvalue($this->head, "client", "trno=?", [$trno]);

    if ($client == '') {
      return ['status' => false, 'msg' => 'Posting failed. Please create customer profile first.'];
    }

    if (!empty($isitemzeroqty)) {
      return ['status' => false, 'msg' => 'Posting failed. Check carefully, some items have zero quantity.'];
    }
    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    if ($this->othersClass->isposted($config)) {
      return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
    }

    
    //for glhead
    $qry = "insert into " . $this->hhead . "(trno,doc,docno,client,clientname,address,shipto,dateid,
      terms,rem,forex,yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,agent,wh,due,cur,creditinfo,crline,overdue, projectid,branch,deptid,tel,compname,designation,contactname,contactno,email,source,sourceid,industry,
      billid,shipid,shipcontactid,billcontactid, participantid)
      SELECT head.trno,head.doc, head.docno,head.client, head.clientname, head.address,head.shipto,
      head.dateid as dateid, head.terms, head.rem, head.forex,head.yourref, head.ourref,
      head.createdate,head.createby,head.editby,head.editdate, head.lockdate,head.lockuser,head.agent,head.wh,
      head.due,head.cur,head.creditinfo,head.crline,head.overdue, head.projectid,head.branch,head.deptid, head.tel,
      head.compname,head.designation,head.contactname,head.contactno,head.email,head.source,head.sourceid,head.industry,
      head.billid,head.shipid,head.shipcontactid,head.billcontactid, head.participantid
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
        whid,loc,expiry,disc,iss,void,isamt,amt,isqty,ext,
        encodeddate,encodedby,editdate,editby,refx,linex,ref,projectid,sgdrate)
        SELECT trno, line, itemid, uom,whid,loc,expiry,disc, iss,void,isamt,amt, isqty, ext,
        encodeddate, encodedby,editdate,editby,refx,linex,ref,projectid,sgdrate FROM " . $this->stock . " where trno =?";
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
  yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,wh,due,cur,creditinfo,crline,overdue,agent, projectid,branch,deptid, tel, compname,designation,contactname,contactno,email,source,sourceid,industry,
  billid, shipid,shipcontactid,billcontactid, participantid)
  select head.trno, head.doc, head.docno, client.client, head.clientname, head.address, head.shipto,
  head.dateid as dateid, head.terms, head.rem, head.forex, head.yourref, head.ourref, head.createdate,
  head.createby, head.editby, head.editdate, head.lockdate, head.lockuser,head.wh,head.due,head.cur,head.creditinfo,
  head.crline,head.overdue,head.agent,head.projectid,head.branch,head.deptid, head.tel,head.compname,head.designation,
  head.contactname,head.contactno,head.email,head.source,head.sourceid,head.industry,
  head.billid,head.shipid,head.shipcontactid,head.billcontactid, head.participantid
  from (" . $this->hhead . " as head left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno)left join client on client.client=head.client
  where head.trno=? limit 1";
    //head
    if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {

      if (!$this->othersClass->unpostingstockinfotrans($config)) {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Unposting failed. There are issues with inventory.'];
      }

      $qry = "insert into " . $this->stock . "(
      trno,line,itemid,uom,whid,loc,expiry,disc,
      amt,iss,void,isamt,isqty,ext,rem,encodeddate,encodedby,editdate,editby,refx,linex,ref,projectid,sgdrate)
      select trno, line, itemid, uom,whid,loc,expiry,disc,amt, iss,void, isamt, isqty,
      ext,ifnull(rem,''), encodeddate,encodedby, editdate, editby,refx,linex,ref,projectid,sgdrate
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
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $qty_dec = 0;
    }

    $sqlselect = "select item.brand as brand,
      ifnull(mm.model_name,'') as model,
      item.itemid,
      stock.trno, 
      stock.line,
      item.barcode, 
      item.itemname,
      stock.uom, 
      stock.iss,
      FORMAT(stock.isamt," . $this->companysetup->getdecimal('price', $config['params']) . ") as isamt,
      FORMAT(stock.isqty," . $qty_dec . ")  as isqty,
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
      stock.rem, stock.refx,stock.linex,stock.ref,
      ifnull(uom.factor,1) as uomfactor,
      '' as bgcolor,
      case when stock.void=0 then (case when stock.isqty <>0 then '' else 'bg-red-2' end) else 'bg-red-2' end as errcolor,
    prj.name as stock_projectname,
    stock.projectid,    
    concat(item.itemname,'\\n',ifnull(brand.brand_desc,''),'\\r\\n',ifnull(mm.model_name,''),'\\r\\n',ifnull(i.itemdescription,'')) as itemdescription
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
      where stock.trno =? ";

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
      case 'navigation':
        return $this->othersClass->navigatedocno($config);
        break;

      case 'generateclient':
        return $this->createclient($config);
        break;

      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }

  private function createclient($config)
  {
    $trno = $config['params']['trno'];
    $center = $config['params']['center'];
    $data = [];
    $attendee = $this->coreFunctions->getfieldvalue($this->head, "participantid", "trno=?", [$trno]);
    $sourceid = $this->coreFunctions->getfieldvalue($this->head, "sourceid", "trno=?", [$trno]);

    $clientcode = $this->getnewclient($config); // create customer

    $clientid = $this->coreFunctions->opentable("select clientname from client where client=?", [$clientcode]);
    if ($clientid) {
      return ['status' => false, 'msg' => $clientcode . ' already used by ' . $clientid[0]->clientname . '. Failed to generate code.'];
    }

    $qry = "select clientname, address, tel, terms, agent,email,contactname, industry from " . $this->head . " where trno = ? limit 1 ";
    $res = $this->coreFunctions->opentable($qry, [$trno]);

    $exist = $this->coreFunctions->getfieldvalue("client", "client", "clientname = ? and iscustomer =1", [$res[0]->clientname]);
    if (strlen(($exist)) != 0) {
      return ['status' => false, 'msg' => 'Customer already exist.', 'reloadhead' => true];
    }

    $data['client'] = $clientcode;
    $data['clientname'] = $res[0]->clientname;
    $data['addr'] = $res[0]->address;
    $data['tel'] = $res[0]->tel;
    $data['terms'] = $res[0]->terms;
    $data['agent'] = $res[0]->agent;
    $data['status'] = 'ACTIVE';
    $data['start'] = $this->othersClass->getCurrentTimeStamp();
    $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['createby'] = $config['params']['user'];
    $data['iscustomer'] = 1;
    $data['center'] = $center;
    $data['email'] = $res[0]->email;
    $data['contact'] = $res[0]->contactname;
    $data['industry'] = $res[0]->industry;

    // create client
    $clientid = $this->coreFunctions->insertGetId('client', $data);
    $this->logger->sbcwritelog($clientid, $config, 'CREATE', 'SALES ACTIVITY - ' . $clientid . ' - ' . $clientcode . ' - ' . $res[0]->clientname);

    // update hlead
    $this->coreFunctions->execqry('update ' . $this->head . " set client = ? where trno = ?", 'update', [$clientcode, $trno]);
    if ($attendee != 0) {
      $this->coreFunctions->execqry('update attendee set clientid = ? where exhibitid= ? and line =?', 'update', [$clientid, $sourceid, $attendee]);
    }
    return ['status' => true, 'msg' => 'Successfully fetched.', 'reloadhead' => true];
  }

  private function getnewclient($config)
  {
    $pref = 'C';
    $docnolength =  $this->companysetup->getclientlength($config['params']);
    $last = $this->othersClass->getlastclient($pref, 'customer');
    $start = $this->othersClass->SearchPosition($last);
    $seq = substr($last, $start) + 1;
    $poseq = $pref . $seq;
    $newclient = $this->othersClass->PadJ($poseq, $docnolength);
    return $newclient;
  }

  public function diagram($config)
  {

    $data = [];
    $nodes = [];
    $links = [];
    $data['width'] = 1500;
    $startx = 100;

    $qry = "select head.trno,head.docno,left(head.dateid,10) as dateid,
       CAST(concat('Total QS Amt: ',round(sum(s.ext),2)) as CHAR) as rem,s.refx
       from hophead as head
       left join hopstock as s on s.trno = head.trno
       where head.trno = ?
       group by head.trno,head.docno,head.dateid,s.refx";
    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
    if (!empty($t)) {
      $startx = 550;
      $a = 0;
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
        $qry = "select head.docno,left(head.dateid,10) as dateid,
            CAST(concat('Total OP Amt: ',round(sum(s.ext),2)) as CHAR) as rem
            from qshead as head 
            left join qsstock as s on s.trno = head.trno
            where s.refx = ?
            group by head.docno,head.dateid
            union all
            select head.docno,left(head.dateid,10) as dateid,
            CAST(concat('Total OP Amt: ',round(sum(s.ext),2)) as CHAR) as rem
            from hqshead as head 
            left join hqsstock as s on s.trno = head.trno
            where s.refx = ?
            group by head.docno,head.dateid";
        $x = $this->coreFunctions->opentable($qry, [$config['params']['trno'], $config['params']['trno']]);
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
          where s.refx = ?
          group by head.docno,head.dateid
          union all
          select head.docno,left(head.dateid,10) as dateid,
          CAST(concat('Total SO Amt: ',round(sum(s.ext),2)) as CHAR) as rem
          from hsqhead as head
          left join hqshead as qthead on qthead.sotrno = head.trno
          left join hqsstock as s on s.trno = qthead.trno
          where s.refx = ?
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
    left join glstock as sjstock on sjstock.refx = stock.trno and sjstock.linex = stock.line
    left join glhead as sjhead on sjhead.trno = sjstock.trno
    left join arledger as ar on ar.trno = sjhead.trno
    where stock.refx = ? and sjhead.docno is not null
    group by sjhead.docno, sjhead.dateid, ar.bal, sjhead.trno
    union all 
    select sjhead.docno,
    date(sjhead.dateid) as dateid,
    CAST(concat('Total SJ Amt: ', round(sum(sjstock.ext),2), ' - ', 'Balance: ', round(sum(sjstock.ext),2)) as CHAR) as rem, 
    sjhead.trno
    from hqshead as head
    left join hqsstock as stock on stock.trno = head.trno
    left join lastock as sjstock on sjstock.refx = stock.trno and sjstock.linex = stock.line
    left join lahead as sjhead on sjhead.trno = sjstock.trno
    where stock.refx = ? and sjhead.docno is not null
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

  public function updateperitem($config)
  {
    $config['params']['data'] = $config['params']['row'];
    $result = $this->additem('update', $config);
    $data = $this->openstockline($config);
    return ['row' => $data, 'status' => true, 'msg' => $result['msg']];
  }


  public function updateitem($config)
  {
    $msg = '';
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $result = $this->additem('update', $config);
      if ($msg == '') {
        $msg = $result['msg'];
      } else {
        $msg = $msg . "\n" . $result['msg'];
      }
    }
    $data = $this->openstock($config['params']['trno'], $config);
    return ['inventory' => $data, 'status' => true, 'msg' => $msg];
  } //end function

  public function addallitem($config)
  {
    $msg = '';
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $result = $this->additem('insert', $config);
      if ($msg == '') {
        $msg = $result['msg'];
      } else {
        $msg = $msg . "\n" . $result['msg'];
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
  public function additem($action, $config)
  {
    $companyid = $config['params']['companyid'];
    $uom = $config['params']['data']['uom'];
    $itemid = $config['params']['data']['itemid'];
    $trno = $config['params']['trno'];
    $disc = $config['params']['data']['disc'];
    $wh = $config['params']['data']['wh'];
    $loc = $config['params']['data']['loc'];
    $void = 'false';
    $rem = '';
    $ref = '';
    $expiry = '';
    $refx = 0;
    $linex = 0;
    $projectid  = 0;
    $moq = 0;
    $mmoq = 0;
    $sgdrate = 0;

    if (isset($config['params']['data']['void'])) {
      $void = $config['params']['data']['void'];
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
    if (isset($config['params']['data']['ref'])) {
      $ref = $config['params']['data']['ref'];
    }

    if (isset($config['params']['data']['projectid'])) {
      $projectid = $config['params']['data']['projectid'];
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
      $projectid   = $config['params']['data']['projectid'];
    }
    $amt = $this->othersClass->sanitizekeyfield('amt', $amt);
    $qty = $this->othersClass->sanitizekeyfield('qty', $qty);
    $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor,item.moq,item.mmoq from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
    $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
    $factor = 1;
    if (!empty($item)) {
      $item[0]->factor = $this->othersClass->val($item[0]->factor);
      if ($item[0]->factor !== 0) $factor = $item[0]->factor;
      $moq = $item[0]->moq;
      $mmoq = $item[0]->mmoq;
    }
    $forex = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
    $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor);
    $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);
    $sgdrate = $this->othersClass->getexchangerate('PHP', 'SGD');

    if (floatval($forex) == 0) {
      $forex = 1;
    }

    $data = [
      'trno' => $trno,
      'line' => $line,
      'itemid' => $itemid,
      'isamt' => $amt,
      'amt' => $computedata['amt'] * $forex,
      'isqty' => $qty,
      'iss' => $computedata['qty'],
      'ext' => $computedata['ext'],
      'disc' => $disc,
      'whid' => $whid,
      'loc' => $loc,
      'void' => $void,
      'uom' => $uom,
      'refx' => $refx,
      'linex' => $linex,
      'expiry' => $expiry,
      'ref' => $ref,
      'projectid' => $projectid,
      'sgdrate' => $sgdrate
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
      $msg = 'Item was successfully added.';
      $data['encodeddate'] = $current_timestamp;
      $data['encodedby'] = $config['params']['user'];
      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
        $stockinfo_data = [
          'trno' => $trno,
          'line' => $line,
          'rem' => $rem
        ];
        $this->coreFunctions->sbcinsert('stockinfotrans', $stockinfo_data);

        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:' . $computedata['ext'] . ' Uom:' . $uom);
        $row = $this->openstockline($config);
        $this->loadheaddata($config);

        if ($this->setserveditems($refx, $linex) == 0) {
          $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
          $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
          $this->setserveditems($refx, $linex);
          $return = false;
          $msg = "(" . $item[0]->barcode . ") SO Qty is Greater than Qoutation Qty.";
        }

        //checkingmoq
        if ($moq != 0 && $mmoq != 0) {
          if ($qty < $moq) {
            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
            $this->setserveditems($refx, $linex);
            $return = false;
            $msg = "(" . $item[0]->barcode . ") Quantity ordered less than the minimum order required.";
          }

          if ($qty > $moq && (($qty % $mmoq) != 0)) {
            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
            $this->setserveditems($refx, $linex);
            $return = false;
            $msg = "(" . $item[0]->barcode . ") Invalid quantity, multiple order required is " . $mmoq . ".";
          }
        }

        return ['row' => $row, 'status' => true, 'msg' => $msg, 'reloaddata' => true];
      } else {
        return ['status' => false, 'msg' => 'Add item Failed'];
      }
    } elseif ($action == 'update') {
      $return = true;
      $msg = 'Update item successfully.';
      $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
      if ($this->setserveditems($refx, $linex) == 0) {
        $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
        $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
        $this->setserveditems($refx, $linex);
        $return = false;
        $msg = "(" . $item[0]->barcode . ") SO Qty is Greater than Qoutation Qty.";
      }

      //checkingmoq
      if ($moq != 0 && $mmoq != 0) {
        if ($qty < $moq) {
          $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
          $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
          $this->setserveditems($refx, $linex);
          $return = false;
          $msg = "(" . $item[0]->barcode . ") Quantity ordered less than the minimum order required.";
        }

        if ($qty > $moq && (($qty % $mmoq) != 0)) {
          $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
          $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
          $this->setserveditems($refx, $linex);
          $return = false;
          $msg = "(" . $item[0]->barcode . ") Invalid quantity, multiple order required is " . $mmoq . ".";
        }
      }
      return ['status' => $return, 'msg' => $msg];
    }
  } // end function

  public function deleteallitem($config)
  {
    $isallow = true;
    $trno = $config['params']['trno'];
    $data = $this->coreFunctions->opentable('select refx,linex from ' . $this->stock . ' where trno=? and refx<>0 ', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from stockinfotrans where trno=?', 'delete', [$trno]);

    foreach ($data as $key => $value) {
      if (floatval($data[$key]->refx) != 0) {
        $this->setserveditems($data[$key]->refx, $data[$key]->linex);
      }
    }
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
    if (floatval($data[0]->refx) !== 0) {
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

    $usdprice = 0;
    $forex = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
    $cur = $this->coreFunctions->getfieldvalue($this->head, 'cur', 'trno=?', [$trno]);
    $dollarrate = $this->coreFunctions->getfieldvalue('forex_masterfile', 'dollartocur', 'cur=?', [$cur]);

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
  } // end function

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
        stock.disc,stock.loc,stock.expiry
        FROM hqthead as head left join hqtstock as stock on stock.trno=head.trno left join item on item.itemid=
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
        stock.disc,stock.loc,stock.expiry
        FROM hqthead as head left join hqtstock as stock on stock.trno=head.trno left join item on item.itemid=
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

  public function setserveditems($refx, $linex)
  {
    if ($refx == 0) {
      return 1;
    }
    $qry1 = "select stock." . $this->hqty . " from " . $this->head . " as head left join " . $this->stock . " as 
    stock on stock.trno=head.trno where head.doc='SO' and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry1 = $qry1 . " union all select stock." . $this->hqty . " from " . $this->hhead . " as head left join " . $this->hstock . " as stock on stock.trno=
    head.trno where head.doc='SO' and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty == '') {
      $qty = 0;
    }
    return $this->coreFunctions->execqry("update hqtstock set qa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
  }

  //printout

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
    if ($companyid != 10 || $companyid != 12) { //not afti & not afti usd
      $this->logger->sbcviewreportlog($config);
    }
    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config['params']['dataid']);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  private function report_default_query($trno)
  {

    $query = "select head.rtype,head.rdate,cust.tel2,cust.email,head.docno,head.trno, head.clientname, head.address, 
      date(head.dateid) as dateid,head.terms, head.rem,head.agent,head.wh,
      item.barcode, item.itemname, stock.isamt as gross, stock.amt as netamt, stock.isqty as qty,
      stock.uom, stock.disc, stock.ext, stock.line,item.brand,client.clientname as whname,
      item.sizeid,m.model_name as model
      from ophead as head left join opstock as stock on stock.trno=head.trno 
      left join item on item.itemid=stock.itemid
      left join model_masterfile as m on m.model_id = item.model
      left join client on client.client=head.wh
      left join client as cust on cust.client = head.client
      where head.doc='op' and head.trno='$trno'
      union all
      select head.rtype,head.rdate,cust.tel2,cust.email,head.docno,head.trno, head.clientname, head.address, 
      date(head.dateid) as dateid, head.terms, head.rem,head.agent,head.wh,
      item.barcode, item.itemname, stock.isamt as gross, stock.amt as netamt, stock.isqty as qty,
      stock.uom, stock.disc, stock.ext, stock.line,item.brand,client.clientname as whname,
      item.sizeid,m.model_name as model
      from hophead as head 
      left join hopstock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid 
      left join model_masterfile as m on m.model_id = item.model
      left join client on client.client=head.wh
      left join client as cust on cust.client = head.client
      where head.doc='op' and head.trno='$trno' order by line";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn  


} //end class
