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

class pf
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'PURCHASE ORDER';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false];
  public $tablenum = 'transnum';
  public $head = 'pfhead';
  public $hhead = 'hpfhead';
  public $stock = 'pfstock';
  public $hstock = 'hpfstock';
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';
  private $stockselect;
  public $dqty = 'rrqty';
  public $hqty = 'qty';
  public $damt = 'rrcost';
  public $hamt = 'cost';
  private $fields = [
    'trno', 'docno', 'dateid', 'due', 'client', 'clientname', 'yourref', 'ourref', 'rem', 'terms',
    'forex', 'cur', 'wh', 'address', 'projectid', 'subproject', 'branch', 'deptid', 'tax', 'vattype', 'empid', 'sotrno', 'billid', 'shipid', 'billcontactid', 'shipcontactid',
    'revision', 'rqtrno', 'deldate', 'deladdress'
  ];
  private $except = ['trno', 'dateid', 'due'];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  private $reporter;
  private $helpClass;

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
    $this->logger = new Logger;
    $this->reporter = new SBCPDF;
    $this->helpClass = new helpClass;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 2947,
      'edit' => 2948,
      'new' => 2949,
      'save' => 2950,
      // 'change' => 67, remove change doc
      'delete' => 2951,
      'print' => 2952,
      'lock' => 2953,
      'unlock' => 2954,
      'changeamt' => 2955,
      'post' => 2956,
      'unpost' => 2957,
      'additem' => 2958,
      'edititem' => 2959,
      'deleteitem' => 2960,
      'viewamt' => 2961,
      'prbutton' => 2962
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
    $listpostedby = 8;
    $listcreateby = 8;
    $listeditby = 10;
    $listviewby = 11;

    $getcols = ['action', 'lblstatus', 'listdocument', 'listdate', 'listclientname', 'yourref', 'ourref', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
    $stockbuttons = ['view', 'diagram'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$listclientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $cols[$yourref]['align'] = 'text-left';
    $cols[$ourref]['align'] = 'text-left';

    $cols[$postdate]['label'] = 'Post Date';

    if ($companyid == '10' or $companyid == '12') { //afti, afti usd
      $cols[$yourref]['label'] = 'PO#';
    }
    return $cols;
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
    $limit = "limit 150";

    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'head.clientname', 'head.createby', 'head.editby', 'head.viewby', 'num.postedby', 'head.yourref', 'head.ourref'];

      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
      $limit = "";
    }

    $status = "'DRAFT'";
    switch ($itemfilter) {
      case 'draft':
        $condition = ' and num.postdate is null and head.lockdate is null ';
        break;

      case 'locked':
        $condition = ' and head.lockdate is not null and num.postdate is null ';
        $status = "'LOCKED'";
        break;

      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
    }
    $qry = "select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid,'Pending' as stat,head.createby,head.editby,head.viewby,num.postedby, date(num.postdate) as postdate,head.yourref, head.ourref  
     from " . $this->head . " as head left join " . $this->tablenum . " as num on num.trno=head.trno left join trxstatus as stat on stat.line=num.statid 
     where head.doc=? and num.center=? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " $filtersearch
     union all
     select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid,stat.status as stat,head.createby,head.editby,head.viewby, num.postedby, date(num.postdate) as postdate,head.yourref, head.ourref  
     from " . $this->hhead . " as head left join " . $this->tablenum . " as num on num.trno=head.trno left join trxstatus as stat on stat.line=num.statid 
     where head.doc=? and num.center=? and convert(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " $filtersearch
     order by dateid desc,docno desc " . $limit;

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
    if ($config['params']['companyid'] == 10 || $config['params']['companyid'] == 12) { //afti,afti usd
      array_push($btns, 'others');
    }

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

    if ($config['params']['companyid'] == 10 || $config['params']['companyid'] == 12) { //afti, afti usd
      $buttons['others']['items'] = [
        'aftech' => ['label' => 'Create F File', 'todo' => ['type' => 'exportcsv', 'action' => 'exportcsv', 'lookupclass' => 'exportcsv', 'access' => 'view']],
        'dfile' => ['label' => 'Create D File', 'todo' => ['type' => 'exportcsv', 'action' => 'exportcsvd', 'lookupclass' => 'exportcsvd', 'access' => 'view']],
      ];
    }

    return $buttons;
  } // createHeadbutton

  public function createtab2($access, $config)
  {
    $companyid = $config['params']['companyid'];

    $billshipdefault = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewbillingshipping']];
    $instructiontab = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewinstructiontab']];

    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrytransnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);


    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $return['INSTRUCTION'] = ['icon' => 'fa fa-info', 'customform' => $instructiontab];
      $return['SHIPPING/BILLING ADDRESS'] = ['icon' => 'fa fa-map-marker-alt', 'customform' => $billshipdefault];
    }
    return $return;
  }


  public function createTab($access, $config)
  {
    $companyid = $config['params']['companyid'];
    $viewrrcost = $this->othersClass->checkAccess($config['params']['user'], 843);
    $isproject = $this->companysetup->getisproject($config['params']);

    $action = 0;
    $rrqty = 1;
    $uom = 2;
    $rrcost = 3;
    $disc = 4;
    $ext = 5;
    $wh = 6;
    $qa = 7;
    $rem = 8;
    $ref = 9;
    $poref = 10;
    $stage = 11;
    $void = 12;
    $itemname = 13;
    $barcode = 14;
    $stock_projectname = 15;
    $partno = 16;
    $subcode = 17;
    $boxcount = 18;
    $loc = 19;

    $column = [
      'action', 'rrqty', 'uom', 'rrcost', 'disc', 'ext', 'wh', 'qa', 'rem',
      'ref', 'poref', 'stage', 'void', 'itemname', 'barcode', 'stock_projectname', 'partno', 'subcode', 'boxcount', 'loc'
    ];

    $sortcolumn = [
      'action', 'rrqty', 'uom', 'rrcost', 'disc', 'ext', 'wh', 'qa', 'rem',
      'ref', 'poref', 'stage', 'void', 'itemname', 'barcode', 'stock_projectname', 'partno', 'subcode', 'boxcount', 'loc'
    ];

    $tab = [
      $this->gridname => [
        'gridcolumns' => $column, 'sortcolumns' => $sortcolumn,
        'computefield' => ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'disc' => 'disc', 'total' => 'ext'],
        'headgridbtns' => ['itemvoiding', 'viewref', 'viewitemstockinfo', 'viewdiagram']
      ]

    ];

    $stockbuttons = ['save', 'delete', 'showbalance'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0]['inventory']['columns'][$barcode]['type'] = 'hidden';
    $obj[0]['inventory']['columns'][$barcode]['label'] = '';

    $obj[0]['inventory']['columns'][$uom]['type'] = 'label';

    $obj[0]['inventory']['columns'][$rrqty]['checkfield'] = 'void';

    if (!$isproject) {
      $obj[0]['inventory']['columns'][$stage]['type'] = 'coldel';
    }

    if ($viewrrcost == 0) {
      $obj[0]['inventory']['columns'][$rrcost]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$disc]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$ext]['type'] = 'coldel';
    }

    // 9- ref
    $obj[0]['inventory']['columns'][$ref]['lookupclass'] = 'refpo';
    if (!$access['changeamt']) {
      $obj[0]['inventory']['columns'][$rrcost]['readonly'] = true;
      $obj[0]['inventory']['columns'][$disc]['readonly'] = true;
    }

    if ($companyid != 10 && $companyid != 12) { //not afti and afti usd
      $obj[0]['inventory']['columns'][$stock_projectname]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$poref]['type'] = 'coldel';
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

    $obj[0]['inventory']['columns'][$loc]['label'] = 'Brand';
    if ($companyid != 8) { // not maxipro
      $obj[0]['inventory']['columns'][$loc]['type'] = 'coldel';
    }

    $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $companyid = $config['params']['companyid'];
    $isversion = $this->companysetup->getiscreateversion($config['params']);
    $pr_access = $this->othersClass->checkAccess($config['params']['user'], 2548);

    switch ($companyid) {
      case 2: //mis
      case 3: //conti
        $tbuttons = ['pendingcd', 'pendingpr', 'additem', 'quickadd', 'saveitem', 'deleteallitem'];
        if ($pr_access == 0) {
          unset($tbuttons[1]);
        }
        break;
      case 1: //vitaline
      case 6: //mitsukoshi
        $tbuttons = ['additem', 'quickadd', 'saveitem', 'deleteallitem'];
        if ($companyid == 6) { //mitsukoshi
          array_push($tbuttons, 'criticalstocks');
        }
        break;
      case 10: //afti
        $tbuttons = ['pendingos', 'pendingpr', 'pendingsqpo', 'additem', 'saveitem', 'deleteallitem'];
        if ($pr_access == 0) {
          unset($tbuttons[1]);
        }
        break;

      default:
        $tbuttons = ['pendingpr', 'additem', 'quickadd', 'saveitem', 'deleteallitem'];
        if ($isversion) {
          $tbuttons = ['pendingpr', 'pendingso', 'additem', 'quickadd', 'saveitem', 'deleteallitem'];
        }
        if ($pr_access == 0) {
          unset($tbuttons[0]);
        }
        break;
    }

    $obj = $this->tabClass->createtabbutton($tbuttons);

    switch ($this->companysetup->getsystemtype($config['params'])) {
      case 'CAIMS':
        $obj[0]['action'] = "pendingprdetail";
        $obj[0]['lookupclass'] = "pendingprdetail_pr";
        break;
    }

    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['docno', 'client', 'clientname', 'address'];
    if ($config['params']['companyid'] == 10 || $config['params']['companyid'] == 12) { //afti, afti usd
      unset($fields[3]); // remove address for afti
    }
    switch ($this->companysetup->getsystemtype($config['params'])) {
      case 'CAIMS':
        array_push($fields, 'deldate', 'deladdress');
        break;
    }
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.label', 'Vendor');
    data_set($col1, 'docno.label', 'Transaction#');
    if ($config['params']['companyid'] == 10 || $config['params']['companyid'] == 12) { //afti, afti usd
      data_set($col1, 'empname.label', 'Contact Person');
      data_set($col1, 'empname.lookupclass', 'lookupemployeepo');
      data_set($col1, 'empname.action', 'lookupclient');
      data_set($col1, 'empname.type', 'lookup');
      data_set($col1, 'tel2.label', 'Contact No.');
      data_set($col1, 'tel2.class', 'cstel2 sbccsreadonly');
    }

    if ($this->companysetup->getisproject($config['params'])) {
      $fields = [['dateid', 'terms'], 'due', 'dwhname', 'dprojectname'];
      $col2 = $this->fieldClass->create($fields);
      data_set($col2, 'dprojectname.required', true);
      data_set($col2, 'dprojectname.lookupclass', 'projectcode');
      data_set($col2, 'dprojectname.condition', ['checkstock']);
      data_set($col2, 'dprojectname.addedparams', []);
    } else {

      if ($config['params']['companyid'] == 10 || $config['params']['companyid'] == 12) { //afti, afti usd
        $fields = [['dateid', 'terms'], ['due', 'dvattype']];
        array_push($fields, 'dbranchname');
        array_push($fields, 'ddeptname');
      } else {
        $fields = [['dateid', 'terms'], 'due', 'whname'];
      }
      $col2 = $this->fieldClass->create($fields);
      data_set($col2, 'whname.required', true);
      data_set($col2, 'whname.type', 'lookup');
      data_set($col2, 'whname.action', 'lookupclient');
      data_set($col2, 'whname.lookupclass', 'wh');
      if ($config['params']['companyid'] == 10 || $config['params']['companyid'] == 12) { //afti, afti usd
        data_set($col2, 'ddeptname.label', 'Department');
      }
    }

    if ($this->companysetup->getisproject($config['params'])) {
      $fields = [['yourref', 'ourref'], ['cur', 'forex'], 'subprojectname'];
      $col3 = $this->fieldClass->create($fields);
      data_set($col3, 'subprojectname.required', true);
    } else {
      if ($config['params']['companyid'] == 10 || $config['params']['companyid'] == 12) { //afti, afti usd
        $fields = ['yourref', ['cur', 'forex'], 'sodocno'];
      } else {
        $fields = [['yourref', 'ourref'], ['cur', 'forex']];
      }
      $col3 = $this->fieldClass->create($fields);
      if ($config['params']['companyid'] == 10 || $config['params']['companyid'] == 12) { //afti, afti usd
        data_set($col3, 'yourref.label', 'PO #');
      }
    }

    if ($config['params']['companyid'] == 8) { //maxipro
      data_set($col3, 'yourref.type', 'lookup');
      data_set($col3, 'yourref.class', 'csyourref sbccsreadonly');
      data_set($col3, 'yourref.required', true);
      data_set($col3, 'yourref.lookupclass', 'pendingpr_yourref');
      data_set($col3, 'yourref.action', 'pendingpr_yourref');
      data_set($col3, 'yourref.addedparams', ['projectid', 'subproject']);
    }

    $fields = ['rem'];
    if ($config['params']['companyid'] == 8) { //maxipro
      array_push($fields, 'revision');
    }
    $col4 = $this->fieldClass->create($fields);
    if ($config['params']['companyid'] == 8) { //maxipro
      data_set($col4, 'rem.type', 'textarea');
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
    $data[0]['address'] = '';
    $data[0]['ourref'] = '';
    $data[0]['rem'] = '';
    $data[0]['terms'] = '';
    $data[0]['forex'] = 1;
    $data[0]['cur'] = $this->companysetup->getdefaultcurrency($params);
    $data[0]['projectcode'] = '';
    $data[0]['projectname'] = '';
    $data[0]['projectid'] = 0;
    $data[0]['subprojectname'] = '';
    $data[0]['subproject'] = 0;
    $data[0]['dwhname'] = '';
    $data[0]['dbranchname'] = '';
    $data[0]['branch'] = 0;
    $data[0]['branchcode'] = '';
    $data[0]['ddeptname'] = '';
    $data[0]['deptid'] = '0';
    $data[0]['dept'] = '';
    $data[0]['tax'] = 0;
    $data[0]['vattype'] = 'NON-VATABLE';
    $data[0]['wh'] = $this->companysetup->getwh($params);
    $name = $this->coreFunctions->datareader("select clientname as value from client where client='" . $data[0]['wh'] . "'");
    $data[0]['whname'] = $name;

    $data[0]['empid'] = '0';
    $data[0]['empname'] = '';
    $data[0]['empcode'] = '';
    $data[0]['tel2'] = '';
    $data[0]['dvattype'] = '';
    $data[0]['sotrno'] = '0';
    $data[0]['sodocno'] = '';
    $data[0]['billid'] = '0';
    $data[0]['shipid'] = '0';
    $data[0]['shipcontactid'] = '0';
    $data[0]['billcontactid'] = '0';
    $data[0]['revision'] = '';
    $data[0]['rqtrno'] = '0';
    $data[0]['deldate'] = $this->othersClass->getCurrentDate();
    $data[0]['deladdress'] = '';

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

    switch ($this->companysetup->getsystemtype($config['params'])) {
      case 'CAIMS':
        $addedfield = ", rqhead.docno as yourref, head.rqtrno, left(head.deldate, 10) as deldate, head.deladdress";
        $addedjoin = "left join hprhead as rqhead on rqhead.trno = head.rqtrno";
        break;
      default:
        $addedfield = ", head.yourref";
        $addedjoin = "";
        break;
    }
    $qryselect = "select
         num.center,
         head.trno,
         head.docno,
         client.client,
         head.terms,
         head.cur,
         head.forex,
         head.ourref,
         left(head.dateid,10) as dateid,
         head.clientname,
         head.address,
         head.shipto,
         date_format(head.createdate,'%Y-%m-%d') as createdate,
         head.rem,
         head.tax,
         head.billid,
         head.shipid,
         head.billcontactid,
         head.shipcontactid,
         head.vattype,
         '' as dvattype,
         head.agent,
         agent.clientname as agentname,
         head.wh as wh,
         warehouse.clientname as whname,
         '' as dwhname,
         left(head.due,10) as due,
         client.groupid,head.projectid,ifnull(p.code,'') as projectcode,ifnull(p.name,'') as projectname,
         s.line as subproject,s.subproject as subprojectname,head.branch,ifnull(b.clientname,'') as branchname,
         ifnull(b.client,'') as branchcode,'' as dbranchname,ifnull(d.client,'') as dept,
         ifnull(d.clientname,'') as deptname,head.deptid,'' as ddeptname, head.empid, e.clientname as empname,
         e.client as empcode, e.tel2,head.sotrno,ifnull(so.docno,'') as sodocno,
         head.revision,
         head.rqtrno, 
         left(head.deldate, 10) as deldate, 
         head.deladdress
         " . $addedfield . "  ";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as agent on agent.client = head.agent
        left join client as b on b.clientid = head.branch
        left join client as d on d.clientid = head.deptid
        left join client as e on e.clientid = head.empid
        left join projectmasterfile as p on p.line = head.projectid
        left join subproject as s on s.line = head.subproject
        left join hsqhead as so on so.trno = head.sotrno
        " . $addedjoin . "
        where head.trno = ? and num.center = ?
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as agent on agent.client = head.agent
        left join client as b on b.clientid = head.branch
        left join client as d on d.clientid = head.deptid
        left join client as e on e.clientid = head.empid
        left join projectmasterfile as p on p.line = head.projectid
        left join subproject as s on s.line = head.subproject
        left join hsqhead as so on so.trno = head.sotrno
        " . $addedjoin . "
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
      $hidetabbtn = ['btndeleteallitem' => false];
      $clickobj = ['button.btnadditem'];
      if ($config['params']['companyid'] == 10 || $config['params']['companyid'] == 12) { //afti, afti usd
        $clickobj = [];
      }
      return  [
        'head' => $head, 'griddata' => ['inventory' => $stock], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg,
        'clickobj' => $clickobj, 'hidetabbtn' => $hidetabbtn
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

    $this->deleteallitem($config);
    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from stockinfotrans where trno=?', 'delete', [$trno]);
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
      terms,rem,forex,yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,agent,wh,due,cur,projectid,subproject,branch,deptid,sotrno,billid,shipid,vattype,tax,empid,billcontactid,shipcontactid,
      revision, rqtrno, deldate, deladdress)
      SELECT head.trno,head.doc, head.docno,head.client, head.clientname, head.address,head.shipto,
      head.dateid as dateid, head.terms, head.rem, head.forex,head.yourref, head.ourref,
      head.createdate,head.createby,head.editby,head.editdate, head.lockdate,head.lockuser,head.agent,head.wh,
      head.due,head.cur,head.projectid,head.subproject,head.branch,head.deptid,head.sotrno,head.billid,head.shipid,
      head.vattype,head.tax,head.empid,head.billcontactid,head.shipcontactid,
      head.revision, head.rqtrno, head.deldate, head.deladdress
      FROM " . $this->head . " as head left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno
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
        encodeddate,qa,encodedby,editdate,editby,sku,refx,linex,cdrefx,cdlinex,rem,stageid, projectid,sorefx,solinex,osrefx,oslinex,sgdrate,poref)
        SELECT trno, line, itemid, uom,whid,loc,ref,disc,cost, qty,void,rrcost, rrqty, ext,
        encodeddate,qa, encodedby,editdate,editby,sku,refx,linex,cdrefx,cdlinex,rem,stageid, projectid ,sorefx,solinex,osrefx,oslinex,sgdrate,poref
        FROM " . $this->stock . " where trno =?";
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        //update transnum
        $date = $this->othersClass->getCurrentTimeStamp();
        $data = ['postdate' => $date, 'postedby' => $config['params']['user'], 'statid' => 5];
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
    yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,wh,due,cur,projectid,subproject,branch,deptid,sotrno,billid,shipid,vattype,tax,empid,billcontactid,shipcontactid,
    revision, rqtrno, deldate, deladdress)
    select head.trno, head.doc, head.docno, client.client, head.clientname, head.address, head.shipto,
    head.dateid as dateid, head.terms, head.rem, head.forex, head.yourref, head.ourref, head.createdate,
    head.createby, head.editby, head.editdate, head.lockdate, head.lockuser,head.wh,head.due,head.cur,head.projectid,head.subproject,head.branch,head.deptid,head.sotrno,head.billid,head.shipid,head.vattype,head.tax,head.empid,head.billcontactid,head.shipcontactid,
    head.revision, head.rqtrno, head.deldate, head.deladdress
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
      cost,qty,void,rrcost,rrqty,ext,rem,encodeddate,qa,encodedby,editdate,editby,sku,refx,linex,cdrefx,cdlinex,stageid, projectid,sorefx,solinex,osrefx,oslinex,sgdrate,poref)
      select trno, line, itemid, uom,whid,loc,ref,disc,cost, qty,void, rrcost, rrqty,
      ext,rem, encodeddate, qa, encodedby, editdate, editby,sku,refx,linex,cdrefx,cdlinex,stageid, projectid,sorefx,solinex,osrefx,oslinex,sgdrate,poref
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
    $sqlselect = "select 
    brand.brand_desc as brand,
    ifnull(mm.model_name,'') as model,
    genitem.line as itemid,
    stock.trno,
    stock.line,
    stock.refx,
    stock.linex,
    genitem.barcode,
    genitem.itemname,
    stock.uom,
    stock.cost,
    stock.qty as qty,
    FORMAT(stock.rrcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrcost,
    FORMAT(stock.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as rrqty,
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,
    left(stock.encodeddate,10) as encodeddate,
    stock.disc,
    case when stock.void=0 then 'false' else 'true' end as void,
    round((stock.qty-stock.qa)/ 1," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
    stock.ref,
    stock.whid,
    stock.loc,
    stock.rem,
    1 as uomfactor,
    '' as bgcolor,
    case when stock.void=0 then '' else 'bg-red-2' end as errcolor,
    prj.name as stock_projectname,
    stock.projectid as projectid,
    0 as boxcount,
    wh.client as wh,
    wh.clientname as whname";
    return $sqlselect;
  }

  public function openstock($trno, $config)
  {
    $sqlselect = $this->getstockselect($config);

    $qry = $sqlselect . "
    FROM $this->stock as stock
    left join generalitem as genitem on genitem.line=stock.itemid
    left join model_masterfile as mm on mm.model_id = genitem.modelid
    left join frontend_ebrands as brand on brand.brandid = genitem.brandid
    left join stockgrp_masterfile as groups on groups.stockgrp_id = genitem.groupid
    left join item_class as classi on classi.cl_id = genitem.classid
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join client as wh on wh.clientid = stock.whid
    where stock.trno =?
    UNION ALL
    " . $sqlselect . "
    FROM $this->hstock as stock
    left join generalitem as genitem on genitem.line=stock.itemid
    left join model_masterfile as mm on mm.model_id = genitem.modelid
    left join frontend_ebrands as brand on brand.brandid = genitem.brandid
    left join stockgrp_masterfile as groups on groups.stockgrp_id = genitem.groupid
    left join item_class as classi on classi.cl_id = genitem.classid
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join client as wh on wh.clientid = stock.whid
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
    left join generalitem as genitem on genitem.line=stock.itemid
    left join model_masterfile as mm on mm.model_id = genitem.modelid
    left join frontend_ebrands as brand on brand.brandid = genitem.brandid
    left join stockgrp_masterfile as groups on groups.stockgrp_id = genitem.groupid
    left join item_class as classi on classi.cl_id = genitem.classid
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join client as wh on wh.clientid = stock.whid
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
      case 'getcdsummary':
        return $this->getcdsummary($config);
        break;
      case 'getcddetails':
        return $this->getcddetails($config);
        break;
      case 'getprsummary':
        return $this->getprsummary($config);
        break;
      case 'getprdetails':
        return $this->getprdetails($config);
        break;
      case 'getsosummary':
        return $this->getsosummary($config);
        break;
      case 'getsodetails':
        return $this->getsodetails($config);
        break;
      case 'getsqsummary':
        return $this->getsqposummary($config);
        break;
      case 'getsqdetails':
        return $this->getsqdetails($config);
        break;
      case 'getcriticalstocks':
        return $this->getcriticalstocks($config);
        break;
      case 'getossummary':
        return $this->getossummary($config);
        break;
      case 'getosdetails':
        return $this->getosdetails($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }


  public function stockstatusposted($config)
  {
    switch ($config['params']['action']) {
      case 'updateitemvoid':
        return $this->updateitemvoid($config);
        break;
      case 'diagram':
        return $this->diagram($config);
        break;
      case 'exportcsv':
        return $this->exportcsv($config, 'F');
        break;
      case 'exportcsvd':
        return $this->exportcsv($config, 'D');
        break;
      case 'print1':
        return $this->reportsetup($config);
        break;
      default:
        return ['status' => false, 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function exportcsv($config, $type)
  {
    $trno = $config['params']['trno'];
    $str = "";
    $separator = "@@@";
    $nextline = "###";
    $filename = '';

    switch ($type) {
      case 'F':
        $qry = "select head.trno as exportid, head.yourref as ponum,right(head.docno,5) as erp,ifnull(date_format(qtn.dateid,'%m%/%d%/%Y'),'') as qtndate,
        ifnull(qtn.clientname,'') as customername, ifnull(concat(conbill.fname,' ', conbill.mname, ' ',conbill.lname),'') as contactperson,
        ifnull(qtn.terms, '') as terms, 'Exworks' as fob,
        0 as zero1, 0 as zero2,ifnull(date_format(so.dateid,'%m%/%d%/%Y'),'') as custpodate, ifnull(date_format(qtn.deldate,'%m%/%d%/%Y'),'') as deliverydate,
        date_format(head.dateid,'%m%/%d%/%Y') as podate,
        'URGENT' as priority,ifnull(concat(conbill.fname, ' ', conbill.mname, ' ',conbill.lname),'') as billname,
        ifnull(bill.addrline1,'') as billaddr1,ifnull(bill.addrline2,'') as billaddr2,ifnull(bill.city,'') as billcity,
        ifnull(bill.country,'') as billcountry,ifnull(bill.contactno,'') as billcontactno,ifnull(bill.fax,'') as billfax,
        ifnull(conbill.email,'') as billemail,
        ifnull(concat(conship.fname, ' ', conship.mname, ' ',conship.lname),'') as shipname,
        ifnull(ship.addrline1,'') as shipaddr1,ifnull(ship.addrline2,'') as shipaddr2,ifnull(ship.city,'') as shipcity,
        ifnull(ship.country,'') as shipcountry,ifnull(ship.contactno,'') as shipcontactno,ifnull(ship.fax,'') as shipfax,
        ifnull(conship.email,'') as shipemail,head.rem,date_format(curdate(),'%m%/%d%/%Y') as dategenerated
        from pohead as head
        left join hsqhead as so on so.trno=head.sotrno
        left join hqshead as qtn on qtn.sotrno=so.trno
        left join client on client.client=qtn.client
        left join contactperson as conbill on conbill.line=qtn.billcontactid
        left join contactperson as conship on conship.line=qtn.shipcontactid
        left join billingaddr as bill on bill.line = qtn.billid and bill.clientid = client.clientid
        left join billingaddr as ship on ship.line = qtn.shipid and ship.clientid = client.clientid
        where head.doc='po' and head.trno=" . $trno . "
        union all
        select head.trno as exportid, head.yourref as ponum,right(head.docno,5) as erp,ifnull(date_format(qtn.dateid,'%m%/%d%/%Y'),'') as qtndate,
        ifnull(qtn.clientname,'') as customername, ifnull(concat(conbill.fname, ' ', conbill.mname, ' ',conbill.lname),'') as contactperson,
        ifnull(qtn.terms, '') as terms, '???' as fob,
        0 as zero1, 0 as zero2,ifnull(date_format(so.dateid,'%m%/%d%/%Y'),'') as custpodate, ifnull(date_format(qtn.deldate,'%m%/%d%/%Y'),'') as deliverydate,
        date_format(head.dateid,'%m%/%d%/%Y') as podate,
        'URGENT' as priority,ifnull(concat(conbill.fname, ' ', conbill.mname, ' ',conbill.lname),'') as billname,
        ifnull(bill.addrline1,'') as billaddr1,ifnull(bill.addrline2,'') as billaddr2,ifnull(bill.city,'') as billcity,
        ifnull(bill.country,'') as billcountry,ifnull(bill.contactno,'') as billcontactno,ifnull(bill.fax,'') as billfax,
        ifnull(conbill.email,'') as billemail,
        ifnull(concat(conship.fname, ' ', conship.mname, ' ',conship.lname),'') as shipname,
        ifnull(ship.addrline1,'') as shipaddr1,ifnull(ship.addrline2,'') as shipaddr2,ifnull(ship.city,'') as shipcity,
        ifnull(ship.country,'') as shipcountry,ifnull(ship.contactno,'') as shipcontactno,ifnull(ship.fax,'') as shipfax,
        ifnull(conship.email,'') as shipemail,head.rem,date_format(curdate(),'%m%/%d%/%Y') as dategenerated
        from hpohead as head
        left join hsqhead as so on so.trno=head.sotrno
        left join hqshead as qtn on qtn.sotrno=so.trno
        left join client on client.client=qtn.client
        left join contactperson as conbill on conbill.line=qtn.billcontactid
        left join contactperson as conship on conship.line=qtn.shipcontactid
        left join billingaddr as bill on bill.line = qtn.billid and bill.clientid = client.clientid
        left join billingaddr as ship on ship.line = qtn.shipid and ship.clientid = client.clientid
        where head.doc='po' and head.trno=" . $trno;

        $data = $this->coreFunctions->opentable($qry);
        if (!empty($data)) {
          if ($data[0]->rem == "") {
            $rem = ' ';
          } else {
            $rem = $data[0]->rem;
          }

          $str = $data[0]->erp . $separator . $data[0]->ponum . $separator . $data[0]->erp . $separator . $data[0]->podate . $separator . $data[0]->customername . $separator . $data[0]->contactperson;
          $str = $str . $separator . $data[0]->terms . $separator . $data[0]->fob . $separator . $data[0]->fob . $separator . '0' . $separator . $data[0]->podate . $separator . $data[0]->podate . $separator . $data[0]->deliverydate . $separator . $data[0]->dategenerated . $separator . $data[0]->priority;
          $str = $str . $separator . $data[0]->customername . $separator . $data[0]->billaddr1 . ' ' . $data[0]->billaddr2 . ' City: ' . $data[0]->billcity . ' Country: ' . $data[0]->billcountry . ' Phone: ' . $data[0]->billcontactno . "\t" . 'Fax: ' . $data[0]->billfax . ' Email: ' . $data[0]->billemail;
          $str = $str . $separator . $data[0]->shipaddr1 . ' ' . $data[0]->shipaddr2 . ' City: ' . $data[0]->shipcity . ' Country: ' . $data[0]->shipcountry . ' Phone: ' . $data[0]->shipcontactno . "\t" . 'Fax: ' . $data[0]->shipfax . ' Email: ' . $data[0]->shipemail;
          $str = $str . $separator . $data[0]->billname . $separator . $data[0]->shipaddr1 . ' ' . $data[0]->shipaddr2 . ' City: ' . $data[0]->shipcity . ' Country: ' . $data[0]->shipcountry . ' Phone: ' . $data[0]->shipcontactno . "\t" . 'Fax: ' . $data[0]->shipfax . ' Email: ' . $data[0]->shipemail . $separator . $data[0]->shipname . $separator . $rem;
          $str = $str . $separator . "0" . $separator . $data[0]->erp . "####";
          $filename = 'POCPHP-F-' . $data[0]->erp . date("dmyHi");
        }
        break;

      case 'D':
        $qry = "select right(head.docno,5) as erp, stock.line, head.yourref as ponum, stock.rrqty as pocustqty, stock.uom,
        head.cur as currency, round(stock.rrcost,4) as poprice, stock.rrqty as pobalance,
        date_format(head.dateid,'%m%/%d%/%Y') as podate,date_format(qtn.deldate,'%m%/%d%/%Y') as deliverydate,date_format(curdate(),'%m%/%d%/%Y') as dategenerated,brand.brand_desc,
        item.itemname, model.model_name,iteminfo.itemdescription,iteminfo.accessories,stockinfo.rem as itemremarks,
        brand.brand_desc as crossmfr, model.model_name as crossmfritemno,
        0 as weight, 0 as ssm1, 0 as price1, 0 as ssm2, 0 as price2, 0 as ssm3, 0 as price3, 0 as ssm4, 0 as price4,
        0 as ssm5, 0 as price5, 0 as ssm6, 0 as price6,p.name as project
        from pohead as head
        left join postock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join item on item.itemid = stock.itemid
        left join stockgrp_masterfile as grp on grp.stockgrp_id = item.groupid
        left join part_masterfile as part on part.part_id = item.part
        left join frontend_ebrands as brand on brand.brandid = item.brand
        left join model_masterfile as model on model.model_id = item.model
        left join projectmasterfile as p on p.line = item.projectid
        left join iteminfo on iteminfo.itemid = item.itemid
        left join stockinfotrans as stockinfo on stockinfo.trno = head.trno and stockinfo.line = stock.line
        left join hsqhead as so on so.trno=head.sotrno
        left join hqshead as qtn on qtn.sotrno=so.trno
        where head.doc='po' and head.trno=" . $trno . "
        union all
        select right(head.docno,5) as erp, stock.line, head.yourref as ponum, stock.rrqty as pocustqty, stock.uom,
        head.cur as currency, round(stock.rrcost,4) as poprice, stock.rrqty as pobalance,
        date_format(head.dateid,'%m%/%d%/%Y') as podate,date_format(qtn.deldate,'%m%/%d%/%Y') as deliverydate,date_format(curdate(),'%m%/%d%/%Y') as dategenerated,brand.brand_desc,
        item.itemname, model.model_name,iteminfo.itemdescription,iteminfo.accessories,stockinfo.rem as itemremarks,
        brand.brand_desc as crossmfr, model.model_name as crossmfritemno,
        0 as weight, 0 as ssm1, 0 as price1, 0 as ssm2, 0 as price2, 0 as ssm3, 0 as price3, 0 as ssm4, 0 as price4,
        0 as ssm5, 0 as price5, 0 as ssm6, 0 as price6,p.name as project
        from hpohead as head
        left join hpostock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join item on item.itemid = stock.itemid
        left join stockgrp_masterfile as grp on grp.stockgrp_id = item.groupid
        left join part_masterfile as part on part.part_id = item.part
        left join frontend_ebrands as brand on brand.brandid = item.brand
        left join model_masterfile as model on model.model_id = item.model
        left join projectmasterfile as p on p.line = item.projectid
        left join iteminfo on iteminfo.itemid = item.itemid
        left join stockinfotrans as stockinfo on stockinfo.trno = head.trno and stockinfo.line = stock.line
        left join hsqhead as so on so.trno=head.sotrno
        left join hqshead as qtn on qtn.sotrno=so.trno
        where head.doc='po' and head.trno=" . $trno;

        $data = $this->coreFunctions->opentable($qry);
        if (!empty($data)) {
          foreach ($data as $key => $val) {
            if ($data[0]->accessories == "") {
              $accessories = ' ';
            } else {
              $accessories = $data[0]->accessories;
            }

            if ($data[0]->itemdescription == "") {
              $itemdescription = ' ';
            } else {
              $itemdescription = $data[0]->itemdescription;
            }

            $str = $str . $data[$key]->erp . $separator . $data[$key]->line . $separator . $data[$key]->ponum . $separator . round($data[$key]->pocustqty, 0) . $separator . $data[$key]->uom . $separator . $data[$key]->currency;
            $str = $str . $separator . $data[$key]->poprice . $separator . round($data[$key]->pobalance, 0) . $separator . $data[$key]->podate . $separator . $data[$key]->deliverydate . $separator . $data[$key]->dategenerated . $separator . $data[$key]->project . $separator . $data[$key]->itemname . $separator . $data[$key]->model_name;
            $str = $str . $separator . $itemdescription . $separator . $accessories . $separator;
            $str = $str . $separator . $separator . $separator . '0' . $separator . $data[$key]->currency . $separator . round($data[$key]->pocustqty, 0) . $separator . $data[$key]->poprice . $separator . '0' . $separator . '0' . $separator . '0' . $separator . '0' . $separator . '0' . $separator . '0' . $separator . '0' . $separator . '0' . $separator . '0' . $separator . '0' . $separator;
            $str = $str . "####";
          }

          $filename = 'POCPHP-D-' . $data[0]->erp . date("dmyHi");
        }

        break;
    }


    return ['status' => true, 'msg' => 'Successfully exported.', 'filename' => $filename, 'ext' => 'txt', 'csv' => $str];
  }

  public function diagram($config)
  {
    $companyid = $config['params']['companyid'];
    $data = [];
    $nodes = [];
    $links = [];
    $data['width'] = 1500;
    $startx = 100;

    $qry = "select po.trno,po.docno,left(po.dateid,10) as dateid,
       CAST(concat('Total PO Amt: ',round(sum(s.ext),2)) as CHAR) as rem,s.refx
       from hpohead as po
       left join hpostock as s on s.trno = po.trno
       where po.trno = ?
       group by po.trno,po.docno,po.dateid,s.refx
       union all
       select po.trno,po.docno,left(po.dateid,10) as dateid,
       CAST(concat('Total PO Amt: ',round(sum(s.ext),2)) as CHAR) as rem,s.refx
       from pohead as po
       left join postock as s on s.trno = po.trno
       where po.trno = ?
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
            'color' => 'blue',
            'details' => [$t[$key]->dateid]
          ]
        );
        array_push($links, ['from' => $t[$key]->docno, 'to' => 'rr']);
        $a = $a + 100;

        if ($companyid == 6) { // mitsukoshi
          // PL
          $qry = "select head.docno,left(head.dateid,10) as dateid,
          CAST(concat('Total PL Amt: ',round(sum(s.ext),2)) as CHAR) as rem
          from hplhead as head 
          left join hplstock as s on s.trno = head.trno
          left join hpostock as postock on postock.trno = s.refx and postock.line = s.linex
          left join hpohead as pohead on pohead.trno = postock.trno
          where pohead.trno = ?
          group by head.docno,head.dateid";
          $x = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
          $poref = $t[$key]->docno;
          if (!empty($x)) {
            foreach ($x as $key2 => $value) {
              data_set(
                $nodes,
                $x[$key2]->docno,
                [
                  'align' => 'left',
                  'x' => 300,
                  'y' => 250,
                  'w' => 250,
                  'h' => 80,
                  'type' => $x[$key2]->docno,
                  'label' => $x[$key2]->rem,
                  'color' => 'yellow',
                  'details' => [$x[$key2]->dateid]
                ]
              );
              array_push($links, ['from' => $x[$key2]->docno, 'to' => $poref]);
            }
          }
        }

        if (floatval($t[$key]->refx) != 0) {
          //pr
          $qry = "select po.docno,left(po.dateid,10) as dateid,
            CAST(concat('Total PR Amt: ',round(sum(s.ext),2)) as CHAR) as rem
            from hprhead as po left join hprstock as s on s.trno = po.trno
            where po.trno = ?
            group by po.docno,po.dateid";
          $x = $this->coreFunctions->opentable($qry, [$t[$key]->refx]);
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
                  'color' => 'yellow',
                  'details' => [$x[$key2]->dateid]
                ]
              );
              array_push($links, ['from' => $x[$key2]->docno, 'to' => $poref]);
              $a = $a + 100;
            }
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
      where stock.refx=? and head.doc = 'RR'
      group by head.docno, head.dateid, head.trno, ap.bal
      union all
      select head.docno,
      date(head.dateid) as dateid,
      CAST(concat('Total RR Amt: ', round(sum(stock.ext),2), ' - ', 'Balance: ', round(sum(stock.ext),2)) as CHAR) as rem,
      head.trno
      from lahead as head
      left join lastock as stock on head.trno = stock.trno
      where stock.refx=? and head.doc = 'RR'
      group by head.docno, head.dateid, head.trno";
    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno'], $config['params']['trno']]);
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
          'color' => 'green',
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
        where detail.refx = ? and head.doc = 'AP'
        union all
        select  head.docno, date(head.dateid) as dateid, head.trno,
        CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
        from lahead as head
        left join ladetail as detail on head.trno = detail.trno
        where detail.refx = ? and head.doc = 'AP'";
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
                'color' => 'red',
                'details' => [$apvdata[$key2]->dateid]
              ]
            );
            array_push($links, ['from' => 'rr', 'to' => 'apv']);
            $a = $a + 100;
          }
        }

        //CV
        if (!empty($apvdata)) {
          $apv_rr_links = "apv";
          $apvtrno = $apvdata[0]->trno;
        } else {
          $apvtrno = $rrtrno;
          $apv_rr_links = "rr";
        }
        $cvqry = "
        select head.docno, date(head.dateid) as dateid, head.trno,
        CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
        from glhead as head
        left join gldetail as detail on head.trno = detail.trno
        where detail.refx = ? and head.doc = 'CV'
        union all
        select head.docno, date(head.dateid) as dateid, head.trno,
        CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
        from lahead as head
        left join ladetail as detail on head.trno = detail.trno
        where detail.refx = ? and head.doc = 'CV'";
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
            array_push($links, ['from' => $apv_rr_links, 'to' => $cvdata[$key2]->docno]);
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
        where stock.refx=? and head.doc = 'DM'
        group by head.docno, head.dateid
        union all
        select head.docno as docno,left(head.dateid,10) as dateid,
        CAST(concat('Total DM Amt: ', round(sum(stock.ext), 2)) as CHAR) as rem
        from lahead as head
        left join lastock as stock on stock.trno=head.trno
        left join item on item.itemid=stock.itemid
        where stock.refx=? and head.doc = 'DM'
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
            array_push($links, ['from' => 'rr', 'to' => $dmdata[$key2]->docno]);
            $a = $a + 100;
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
          $msg2 = ' Qty Received is Greater than PR Qty ';
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
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $this->additem('insert', $config);
    }
    $data = $this->openstock($config['params']['trno'], $config);
    return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
  } //end function

  public function quickadd($config)
  {
    $barcode = $config['params']['barcode'];
    $wh = $config['params']['wh'];
    $item = $this->coreFunctions->opentable("select 
    line as itemid, 0 as amt, '' as disc, '' as loc,'" . $wh . "' as wh, 1 as qty, uom, 0 as famt 
    from generalitem 
    where barcode=?", [$barcode]);

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
      return ['status' => false, 'msg' => 'Barcode not found.'];
    }
  }

  // insert and update item
  public function additem($action, $config)
  {
    $companyid = $config['params']['companyid'];
    $isproject = $this->companysetup->getisproject($config['params']);
    $uom = $config['params']['data']['uom'];
    $itemid = $config['params']['data']['itemid'];
    $trno = $config['params']['trno'];
    $disc = $config['params']['data']['disc'];
    $wh = $config['params']['data']['wh'];
    $loc = $config['params']['data']['loc'];
    $ref = '';
    $void = 'false';
    if (isset($config['params']['data']['void'])) {
      $void = $config['params']['data']['void'];
    }
    if (isset($config['params']['data']['ref'])) {
      $ref = $config['params']['data']['ref'];
    }

    $refx = 0;
    $linex = 0;
    $cdrefx = 0;
    $cdlinex = 0;
    $sorefx = 0;
    $solinex = 0;
    $osrefx = 0;
    $oslinex = 0;
    $rem = '';
    $stageid = 0;
    $projectid = 0;
    $poref = '';
    $sgdrate = 0;

    if (isset($config['params']['data']['rem'])) {
      $rem = $config['params']['data']['rem'];
    }
    if (isset($config['params']['data']['refx'])) {
      $refx = $config['params']['data']['refx'];
    }
    if (isset($config['params']['data']['linex'])) {
      $linex = $config['params']['data']['linex'];
    }
    if (isset($config['params']['data']['cdrefx'])) {
      $cdrefx = $config['params']['data']['cdrefx'];
    }
    if (isset($config['params']['data']['cdlinex'])) {
      $cdlinex = $config['params']['data']['cdlinex'];
    }

    if (isset($config['params']['data']['stageid'])) {
      $stageid = $config['params']['data']['stageid'];
    }

    if (isset($config['params']['data']['solinex'])) {
      $solinex = $config['params']['data']['solinex'];
    }

    if (isset($config['params']['data']['sorefx'])) {
      $sorefx = $config['params']['data']['sorefx'];
    }

    if (isset($config['params']['data']['oslinex'])) {
      $oslinex = $config['params']['data']['oslinex'];
    }

    if (isset($config['params']['data']['osrefx'])) {
      $osrefx = $config['params']['data']['osrefx'];
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

      if ($companyid == 10 || $companyid == 12) { //afti, afti usd
        $projectid = $this->coreFunctions->getfieldvalue("item", 'projectid', 'itemid=?', [$itemid]);
      }
    } elseif ($action == 'update') {
      $config['params']['line'] = $config['params']['data']['line'];
      $line = $config['params']['data']['line'];
      $amt = $config['params']['data'][$this->damt];
      $qty = $config['params']['data'][$this->dqty];
      $config['params']['line'] = $line;

      if ($companyid == 10 || $companyid == 12) { //afti, afti usd
        $projectid = $config['params']['data']['projectid'];
      }
    }
    $amt = $this->othersClass->sanitizekeyfield('amt', $amt);
    $qty = $this->othersClass->sanitizekeyfield('qty', $qty);

    $qry = "select barcode, itemname
    from generalitem
    where line=?";
    $item = $this->coreFunctions->opentable($qry, [$itemid]);
    $factor = 1;

    $forex = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
    $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);
    $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor);

    $data = [
      'trno' => $trno,
      'line' => $line,
      'itemid' => $itemid,
      'rrcost' => $amt,
      'cost' => $computedata['amt'] * $forex,
      'rrqty' => $qty,
      'qty' => $computedata['qty'],
      'ext' => $computedata['ext'],
      'disc' => $disc,
      'whid' => $whid,
      'loc' => $loc,
      'uom' => $uom,
      'void' => $void,
      'refx' => $refx,
      'linex' => $linex,
      'cdrefx' => $cdrefx,
      'cdlinex' => $cdlinex,
      'sorefx' => $sorefx,
      'solinex' => $solinex,
      'osrefx' => $osrefx,
      'oslinex' => $oslinex,
      'rem' => $rem,
      'ref' => $ref,
      'stageid' => $stageid
    ];

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $data['projectid'] = $projectid;
      $data['poref'] = $poref;
      $data['sgdrate'] = $sgdrate;
    }

    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $data['editdate'] = $current_timestamp;
    $data['editby'] = $config['params']['user'];

    if ($action == 'insert') {
      $data['encodeddate'] = $current_timestamp;
      $data['encodedby'] = $config['params']['user'];

      if ($isproject) {
        if ($data['stageid'] == 0) {
          $msg = 'Stage cannot be blank -' . $item[0]->barcode;
          return ['status' => false, 'msg' => $msg];
        }
      }

      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
        switch ($this->companysetup->getsystemtype($config['params'])) {
          case 'AIMS':
            if ($companyid == 0 || $companyid == 10 || $companyid == 12) { //main,afti, afti usd
              $stockinfo_data = [
                'trno' => $trno,
                'line' => $line,
                'rem' => $rem
              ];
              $this->coreFunctions->sbcinsert('stockinfotrans', $stockinfo_data);
            }
            break;
        }

        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:' . $computedata['ext']);
        $this->loadheaddata($config);
        $row = $this->openstockline($config);
        return ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.', 'line' => $line, 'reloaddata' => true];
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
      if ($cdrefx != 0) {
        if ($this->setservedcanvassitems($cdrefx, $cdlinex) === 0) {
          $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
          $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
          $this->setservedcanvassitems($cdrefx, $cdlinex);
          $return = false;
        }
      }

      if ($sorefx != 0) {
        if ($this->setservedsoitems($sorefx, $solinex) === 0) {
          $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
          $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
          $this->setservedsoitems($sorefx, $solinex);
          $return = false;
        }
      }
      if ($sorefx != 0) {
        if ($this->setservedsqitems($sorefx, $solinex) === 0) {
          $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
          $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
          $this->setservedsqitems($sorefx, $solinex);
          $return = false;
        }
      }

      if ($osrefx != 0) {
        if ($this->setservedositems($osrefx, $oslinex) === 0) {
          $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
          $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
          $this->setservedositems($osrefx, $oslinex);
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
    $data = $this->coreFunctions->opentable('select refx,linex,cdrefx,cdlinex,stageid,sorefx,solinex,osrefx,oslinex from ' . $this->stock . ' where trno=? and (refx<>0 or cdrefx<>0 or sorefx<>0 or osrefx<>0)', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from stockinfotrans where trno=?', 'delete', [$trno]);

    foreach ($data as $key => $value) {
      if ($data[$key]->refx != 0) {
        $this->setserveditems($data[$key]->refx, $data[$key]->linex);
      }
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
      $this->setserveditems($data[0]->refx, $data[0]->linex);
    }
    $data = json_decode(json_encode($data), true);
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' barcode:' . $data[0]['barcode'] . ' Qty:' . $data[0]['rrqty'] . ' Amt:' . $data[0]['rrcost'] . ' Disc:' . $data[0]['disc'] . ' wh:' . $data[0]['wh'] . ' ext:' . $data[0]['ext']);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
  } // end function


  public function getcdsummary($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $center = $config['params']['center'];
    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.cost,
        (stock.qty-stock.qa) as qty,stock.rrcost,
        round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
        stock.disc
        FROM hcdhead as head left join hcdstock as stock on stock.trno=head.trno
        left join transnum on transnum.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom where stock.trno = ? and transnum.center=? and stock.qty>stock.qa and stock.void=0 and stock.status=1
    ";
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
          $config['params']['data']['refx'] = 0;
          $config['params']['data']['linex'] = 0;
          $config['params']['data']['cdrefx'] = $data[$key2]->trno;
          $config['params']['data']['cdlinex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->rrcost;
          $return = $this->additem('insert', $config);
          if ($return['status']) {
            if ($this->setservedcanvassitems($data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setservedcanvassitems($data[$key2]->trno, $data[$key2]->line);
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



  public function getprsummary($config)
  {
    $companyid = $config['params']['companyid'];
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $center = $config['params']['center'];
    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.cost,
        (stock.qty-(stock.qa+stock.cdqa)) as qty,stock.rrcost,
        round((stock.qty-(stock.qa+stock.cdqa))/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
        stock.disc,st.line as stageid
        FROM hprhead as head left join hprstock as stock on stock.trno=head.trno left join transnum on transnum.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom left join stagesmasterfile as st on st.line = stock.stageid where stock.trno = ? and transnum.center=? and stock.qty>(stock.qa+stock.cdqa) and stock.void=0
    ";
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
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['cdrefx'] = 0;
          $config['params']['data']['cdlinex'] = 0;
          $config['params']['data']['stageid'] =  $data[$key2]->stageid;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->rrcost;
          $return = $this->additem('insert', $config);
          if ($return['status']) {
            if ($companyid == 8) { //maxipro
              $this->coreFunctions->sbcupdate($this->head, ['yourref' => $data[0]->docno], ['trno' => $trno]);
            }
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


  public function getcddetails($config)
  {
    $companyid = $config['params']['companyid'];
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $center = $config['params']['center'];
    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.cost,
        (stock.qty-stock.qa) as qty,stock.rrcost,
        round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
        stock.disc
        FROM hcdhead as head left join hcdstock as stock on stock.trno=head.trno left join transnum on transnum.trno=head.trno
        left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom where stock.trno = ? and stock.line=? and transnum.center=? and stock.qty>stock.qa and stock.void=0 and stock.status=1
    ";
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line'], $center]);
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
          $config['params']['data']['refx'] = 0;
          $config['params']['data']['linex'] = 0;
          $config['params']['data']['cdrefx'] = $data[$key2]->trno;
          $config['params']['data']['cdlinex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->rrcost;
          $return = $this->additem('insert', $config);
          if ($return['status']) {
            if ($companyid == 8) { //maxipro
              $this->coreFunctions->sbcupdate($this->head, ['yourref' => $data[0]->docno], ['trno' => $trno]);
            }
            if ($this->setservedcanvassitems($data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setservedcanvassitems($data[$key2]->trno, $data[$key2]->line);
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



  public function getprdetails($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $center = $config['params']['center'];
    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.cost,
        (stock.qty-stock.qa) as qty,stock.rrcost,
        round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
        stock.disc,st.line as stageid
        FROM hprhead as head left join hprstock as stock on stock.trno=head.trno
        left join transnum on transnum.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom left join stagesmasterfile as st on st.line = stock.stageid where stock.trno = ? and stock.line=? and transnum.center=? and stock.qty>(stock.qa+stock.cdqa) and stock.void=0
    ";
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line'], $center]);
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
          $config['params']['data']['stageid'] =  $data[$key2]->stageid;
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

  public function setserveditems($refx, $linex)
  {
    $qry1 = "select stock." . $this->hqty . " from pohead as head left join postock as
    stock on stock.trno=head.trno where head.doc='PO' and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry1 = $qry1 . " union all select hpostock." . $this->hqty . " from hpohead left join hpostock on hpostock.trno=
    hpohead.trno where hpohead.doc='PO' and hpostock.refx=" . $refx . " and hpostock.linex=" . $linex;

    $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty === '') {
      $qty = 0;
    }
    return $this->coreFunctions->execqry("update hprstock set qa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
  }

  public function setservedcanvassitems($cdtrno, $cdline)
  {
    $qty = 0;
    $prqty = 0;
    $qry1 = "select stock." . $this->hqty . " from pohead as head left join postock as
    stock on stock.trno=head.trno where head.doc='PO' and stock.cdrefx=" . $cdtrno . " and stock.cdlinex=" . $cdline;

    $qry1 = $qry1 . " union all select hpostock." . $this->hqty . " from hpohead left join hpostock on hpostock.trno=
    hpohead.trno where hpohead.doc='PO' and hpostock.cdrefx=" . $cdtrno . " and hpostock.cdlinex=" . $cdline;

    $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty === '') {
      $qty = 0;
    }
    $prtrno = 0;
    $prline = 0;
    $prtrno = $this->coreFunctions->getfieldvalue('hcdstock', 'refx', 'trno=? and line=?', [$cdtrno, $cdline]);
    if ($prtrno === '') {
      $prtrno = 0;
    }

    if ($prtrno != 0) {
      $prline = $this->coreFunctions->getfieldvalue('hcdstock', 'linex', 'trno=? and line=?', [$cdtrno, $cdline]);
      $qry1 = "select stock." . $this->hqty . " from pohead as head left join postock as
    stock on stock.trno=head.trno where head.doc='PO' and stock.refx=" . $prtrno . " and stock.linex=" . $prline;

      $qry1 = $qry1 . " union all select hpostock." . $this->hqty . " from hpohead left join hpostock on hpostock.trno=
    hpohead.trno where hpohead.doc='PO' and hpostock.refx=" . $prtrno . " and hpostock.linex=" . $prline;

      $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
      $prqty = $this->coreFunctions->datareader($qry2);
      if ($prqty === '') {
        $prqty = 0;
      }
      if ($this->coreFunctions->execqry("update hprstock set cdqa=" . $qty . ",qa=" . $prqty . " where trno=" . $prtrno . " and line=" . $prline, 'update') == 1) {
        return $this->coreFunctions->execqry("update hcdstock set qa=" . $qty . " where trno=" . $cdtrno . " and line=" . $cdline, 'update');
      } else {
        return 0;
      }
    } else {
      return $this->coreFunctions->execqry("update hcdstock set qa=" . $qty . " where trno=" . $cdtrno . " and line=" . $cdline, 'update');
    }
  } //end func

  public function setservedsoitems($refx, $linex)
  {
    $qry1 = "select stock." . $this->hqty . " from pohead as head left join postock as
    stock on stock.trno=head.trno where head.doc='PO' and stock.sorefx=" . $refx . " and stock.solinex=" . $linex;

    $qry1 = $qry1 . " union all select hpostock." . $this->hqty . " from hpohead left join hpostock on hpostock.trno=
    hpohead.trno where hpohead.doc='PO' and hpostock.sorefx=" . $refx . " and hpostock.solinex=" . $linex;

    $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty === '') {
      $qty = 0;
    }
    return $this->coreFunctions->execqry("update hsostock set poqa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
  }

  public function setservedsqitems($refx, $linex)
  {
    if ($refx == 0) {
      return 1;
    }
    $qryso = "select stock.iss from lahead as head left join lastock as
  stock on stock.trno=head.trno where head.doc='SJ' and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qryso = $qryso . " union all select glstock.iss from glhead left join glstock on glstock.trno=
  glhead.trno where glhead.doc='SJ' and glstock.refx=" . $refx . " and glstock.linex=" . $linex;

    $qry = "select ifnull(sum(iss),0) as value from (" . $qryso . ") as t";
    $qtysj = $this->coreFunctions->datareader($qry);
    if ($qtysj == '') {
      $qtysj = 0;
    }

    $qrypo = "select stock." . $this->hqty . " from pohead as head left join postock as
  stock on stock.trno=head.trno where head.doc='PO' and stock.sorefx=" . $refx . " and stock.solinex=" . $linex;

    $qrypo = $qrypo . " union all select stock." . $this->hqty . " from hpohead as head left join hpostock as
  stock on stock.trno=head.trno where head.doc='PO' and stock.sorefx=" . $refx . " and stock.solinex=" . $linex;

    $qry = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qrypo . ") as t";
    $qtypo = $this->coreFunctions->datareader($qry);

    if ($qtypo == '') {
      $qtypo = 0;
    }

    return $this->coreFunctions->execqry("update hqsstock set poqa=" . ($qtypo + $qtysj) . " where trno=" . $refx . " and line=" . $linex, 'update');
  }

  public function setservedositems($refx, $linex)
  {
    $qry1 = "select stock." . $this->hqty . " from pohead as head left join postock as
    stock on stock.trno=head.trno where head.doc='PO' and stock.osrefx=" . $refx . " and stock.oslinex=" . $linex;

    $qry1 = $qry1 . " union all select hpostock." . $this->hqty . " from hpohead left join hpostock on hpostock.trno=
    hpohead.trno where hpohead.doc='PO' and hpostock.osrefx=" . $refx . " and hpostock.oslinex=" . $linex;

    $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if (floatval($qty) == 0) {
      $qty = 0;
    }
    return $this->coreFunctions->execqry("update hosstock set qa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
  }


  public function getlatestprice($config)
  {
    $barcode = $config['params']['barcode'];
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
    $forex = $this->coreFunctions->getfieldvalue($this->head, "forex", "trno=?", [$trno]);
    if ($config['params']['companyid'] == 10 || $config['params']['companyid'] == 12) { //afti, afti usd
      $qry = "select docno,left(dateid,10) as dateid,case " . $forex . " when 1 then round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") else famt end as amt,disc,uom from(select head.docno,head.dateid,
      stock.rrcost as amt,stock.uom,stock.disc,item.famt
      from lahead as head
      left join lastock as stock on stock.trno = head.trno
      left join cntnum on cntnum.trno=head.trno
      left join item on item.itemid=stock.itemid
      where head.doc = 'RR' and cntnum.center = ?
      and item.barcode = ? and head.client = ?
      and stock.rrcost <> 0
      UNION ALL
      select head.docno,head.dateid,stock.rrcost as computeramt,
      stock.uom,stock.disc ,item.famt from glhead as head
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
    } else {
      $qry = "select docno,left(dateid,10) as dateid,round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,disc,uom from(select head.docno,head.dateid,
    stock.rrcost as amt,stock.uom,stock.disc
    from lahead as head
    left join lastock as stock on stock.trno = head.trno
    left join cntnum on cntnum.trno=head.trno
    left join item on item.itemid=stock.itemid
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
    }
  } // end function


  private function updateprojmngmt($config, $stage)
  {
    $trno = $config['params']['trno'];
    $data = $this->openstock($trno, $config);
    $proj = $this->coreFunctions->getfieldvalue($this->head, "projectid", "trno=?", [$trno]);
    $sub = $this->coreFunctions->getfieldvalue($this->head, "subproject", "trno=?", [$trno]);

    $qry1 = "select stock.ext from " . $this->head . " as head left join " . $this->stock . " as
    stock on stock.trno=head.trno where head.doc='PO' and head.projectid = " . $proj . " and head.subproject = " . $sub . " and stock.stageid=" . $stage;

    $qry1 = $qry1 . " union all select stock.ext from " . $this->hhead . " as head left join " . $this->hstock . " as stock on stock.trno=
      head.trno where head.doc='PO' and head.projectid = " . $proj . " and head.subproject = " . $sub . " and stock.stageid=" . $stage;

    $qry2 = "select ifnull(sum(ext),0) as value from (" . $qry1 . ") as t";

    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty === '') {
      $qty = 0;
    }

    $editdate = $this->othersClass->getCurrentTimeStamp();
    $editby = $config['params']['user'];

    return $this->coreFunctions->execqry("update stages set po=" . $qty . ", editdate = '" . $editdate . "', editby = '" . $editby . "' where projectid = " . $proj . " and subproject=" . $sub . " and stage=" . $stage, 'update');
  }

  public function getsosummary($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $companyid = $config['params']['companyid'];
    $rows = [];
    $msg = '';
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.amt,
        (stock.iss-stock.poqa) as iss,stock.isamt,item.famt as tpdollar,
        round((stock.iss-stock.poqa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
        stock.disc,stock.loc,stock.expiry,head.yourref
        FROM hsohead as head left join hsostock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom where stock.trno = ? and stock.iss>stock.poqa and stock.void=0
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
          $config['params']['data']['amt'] = 0;
          $config['params']['data']['sorefx'] = $data[$key2]->trno;
          $config['params']['data']['solinex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          if ($companyid == 10 || $companyid == 12) { //afti, afti usd
            $config['params']['data']['poref'] = $data[$key2]->yourref;
          }

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

  public function getsodetails($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $companyid = $config['params']['companyid'];
    $rows = [];
    $msg = '';
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.amt,
        (stock.iss-stock.poqa) as iss,stock.isamt,
        round((stock.iss-stock.poqa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
        stock.disc,stock.loc,stock.expiry,item.famt as tpdollar,head.yourref
        FROM hsohead as head left join hsostock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom where stock.trno = ? and stock.line=? and stock.iss>stock.poqa and stock.void=0
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
          $config['params']['data']['amt'] = 0;
          $config['params']['data']['sorefx'] = $data[$key2]->trno;
          $config['params']['data']['solinex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          if ($companyid == 10 || $companyid == 12) { //afti, afti usd
            $config['params']['data']['poref'] = $data[$key2]->yourref;
          }
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

  public function getsqposummary($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    $msg = '';
    $sotrno = 0;
    $forex  = $this->coreFunctions->getfieldvalue($this->head, "forex", "trno=?", [$trno]);
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
      select concat(stock.trno,stock.line) as keyid, stock.trno, stock.line, stock.itemid, item.barcode, item.itemname, stock.uom, so.docno, date(head.dateid) as dateid,
      (stock.iss-(stock.qa+stock.sjqa+stock.poqa)) as iss,stock.isamt, stock.disc,stock.amt,stock.ext,so.trno as sotrno,
      FORMAT(((stock.iss-(stock.qa+stock.sjqa+stock.poqa))/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
      item.famt as tpdollar,item.amt as tpphp
      from hsqhead as so left join hqshead as head on head.sotrno=so.trno left join hqsstock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid
      left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
      left join transnum on transnum.trno = head.trno
      where so.doc='SQ' and stock.iss > (stock.qa+stock.sjqa+stock.poqa) and stock.void = 0 and stock.iscanvass=0 and stock.trno=?
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
          $config['params']['data']['loc'] = '';
          $config['params']['data']['expiry'] = '';
          $config['params']['data']['rem'] = '';
          $config['params']['data']['amt'] = 0;
          if ($config['params']['companyid'] == 10 || $config['params']['companyid'] == 12) { //afti, afti usd
            if (floatval($forex) != 1) {
              $config['params']['data']['amt'] = $data[$key2]->tpdollar;
            } else {
              $config['params']['data']['amt'] = $data[$key2]->tpphp;
            }
          }
          $config['params']['data']['sorefx'] = $data[$key2]->trno;
          $config['params']['data']['solinex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
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
          $sotrno = $data[$key2]->sotrno;
        } // end foreach
        $this->coreFunctions->sbcupdate($this->head, ['sotrno' => $sotrno], ['trno' => $trno]);
      } //end if
    } //end foreach
    $this->loadheaddata($config);
    return ['row' => $rows, 'status' => true, 'msg' => $msg, 'reloaddata' => true];
  } //end function

  public function getsqdetails($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    $msg = '';
    $forex  = $this->coreFunctions->getfieldvalue($this->head, "forex", "trno=?", [$trno]);
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
      select concat(stock.trno,stock.line) as keyid, stock.trno, stock.line, stock.itemid, item.barcode, item.itemname, stock.uom, so.docno, date(head.dateid) as dateid,
      (stock.iss-(stock.qa+stock.sjqa+stock.poqa)) as iss,stock.isamt, stock.disc,stock.amt,stock.ext,
      FORMAT(((stock.iss-(stock.qa+stock.sjqa+stock.poqa))/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,item.famt as tpdollar,head.yourref
      from hsqhead as so left join hqshead as head on head.sotrno=so.trno left join hqsstock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid
      left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
      left join transnum on transnum.trno = head.trno
      where so.doc='SQ' and stock.iss > (stock.qa+stock.sjqa+stock.poqa) and stock.void = 0 and stock.iscanvass=0 and stock.trno=? and stock.line=?
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
          $config['params']['data']['amt'] = 0;
          if ($config['params']['companyid'] == 10 || $config['params']['companyid'] == 12) { //afti, afti usd
            if (floatval($forex) != 1) {
              $config['params']['data']['amt'] = $data[$key2]->tpdollar;
            }
            $config['params']['data']['poref'] = $data[$key2]->yourref;
          }
          $config['params']['data']['sorefx'] = $data[$key2]->trno;
          $config['params']['data']['solinex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
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

  public function getcriticalstocks($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    $msg = '';

    $data = $config['params']['rows'];
    foreach ($data as $key => $value) {
      $latestcost = $this->othersClass->getlatestcostTS($config, $value['barcode'], '', $config['params']['center'], $trno);
      if ($latestcost['status']) {
        $amt = $latestcost['data'][0]->amt;
      } else {
        $amt = 0;
      }

      $config['params']['data']['uom'] = $value['uom'];
      $config['params']['data']['itemid'] = $value['itemid'];
      $config['params']['trno'] = $trno;
      $config['params']['data']['disc'] = '';
      $config['params']['data']['amt'] = $amt;
      $config['params']['data']['qty'] = $value['reorder'] + $value['sobal'] - $value['pobal'];
      $config['params']['data']['wh'] = $wh;
      $config['params']['data']['rem'] = '';
      $config['params']['data']['ref'] = '';
      $config['params']['data']['loc'] = '';
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

    return ['row' => $rows, 'status' => true, 'msg' => $msg];
  }

  private function autocreatestock($config, $data)
  {
    $trno = $config['params']['trno'];
    $sotrno = $data['sotrno'];
    $wh = $data['wh'];
    $rows = [];
    $msg = '';
    $forex  = $this->coreFunctions->getfieldvalue($this->head, "forex", "trno=?", [$trno]);
    $qry = "select concat(stock.trno,stock.line) as keyid, stock.trno, stock.line, stock.itemid, item.barcode, item.itemname, stock.uom, so.docno, date(head.dateid) as dateid,
      (stock.iss-(stock.qa+stock.sjqa+stock.poqa)) as iss,stock.isamt, stock.disc,stock.amt,stock.ext,
      FORMAT(((stock.iss-(stock.qa+stock.sjqa+stock.poqa))/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,item.famt as tpdollar,head.yourref
      from hsqhead as so left join hqshead as head on head.sotrno=so.trno left join hqsstock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid
      left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
      left join transnum on transnum.trno = head.trno
      where so.doc='SQ' and stock.iss > (stock.qa+stock.sjqa+stock.poqa) and stock.void = 0 and stock.iscanvass=0 and so.trno=?
    ";
    $data2 = $this->coreFunctions->opentable($qry, [$sotrno]);
    if (!empty($data2)) {
      foreach ($data2 as $key2 => $value) {
        $config['params']['data']['uom'] = $data2[$key2]->uom;
        $config['params']['data']['itemid'] = $data2[$key2]->itemid;
        $config['params']['trno'] = $trno;
        $config['params']['data']['disc'] = $data2[$key2]->disc;
        $config['params']['data']['qty'] = $data2[$key2]->isqty;
        $config['params']['data']['wh'] = $wh;
        $config['params']['data']['loc'] = '';
        $config['params']['data']['expiry'] = '';
        $config['params']['data']['rem'] = '';
        $config['params']['data']['amt'] = 0;
        if ($config['params']['companyid'] == 10 || $config['params']['companyid'] == 12) { //afti, afti usd
          if (floatval($forex) != 1) {
            $config['params']['data']['amt'] = $data2[$key2]->tpdollar;
          }
          $config['params']['data']['poref'] = $data2[$key2]->yourref;
        }
        $config['params']['data']['sorefx'] = $data2[$key2]->trno;
        $config['params']['data']['solinex'] = $data2[$key2]->line;
        $config['params']['data']['ref'] = $data2[$key2]->docno;
        $return = $this->additem('insert', $config);

        if ($msg = '') {
          $msg = $return['msg'];
        } else {
          $msg = $msg . $return['msg'];
        }

        if ($return['status']) {
          if ($this->setservedsqitems($data2[$key2]->trno, $data2[$key2]->line) == 0) {
            $datax = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
            $line = $return['row'][0]->line;
            $config['params']['trno'] = $trno;
            $config['params']['line'] = $line;
            $this->coreFunctions->sbcupdate($this->stock, $datax, ['trno' => $trno, 'line' => $line]);
            $this->setservedsqitems($data2[$key2]->trno, $data2[$key2]->line);
            $row = $this->openstockline($config);
            $return = ['row' => $row, 'status' => true, 'msg' => $msg];
          }
          array_push($rows, $return['row'][0]);
        }
      } // end foreach
      return ['row' => $rows, 'status' => true, 'msg' => 'Item was successfully added.', 'reloaddata' => true];
    } //end if

  }

  public function getossummary($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    $msg = '';
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.cost,
        (stock.qty-stock.qa) as qty,stock.rrcost,item.famt as tpdollar,
        round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
        stock.disc,stock.loc
        FROM hoshead as head left join hosstock as stock on stock.trno=head.trno left join item on item.itemid=
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
          $config['params']['data']['loc'] = $data[$key2]->loc;
          $config['params']['data']['rem'] = '';
          $config['params']['data']['amt'] = $data[$key2]->rrcost;
          $config['params']['data']['osrefx'] = $data[$key2]->trno;
          $config['params']['data']['oslinex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;

          $return = $this->additem('insert', $config);

          if ($msg = '') {
            $msg = $return['msg'];
          } else {
            $msg = $msg . $return['msg'];
          }

          if ($return['status']) {
            if ($this->setservedositems($data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setservedositems($data[$key2]->trno, $data[$key2]->line);
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

  public function getosdetails($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    $msg = '';
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.cost,
        (stock.qty-stock.qa) as qty,stock.rrcost,
        round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
        stock.disc,stock.loc,item.famt as tpdollar
        FROM hoshead as head left join hosstock as stock on stock.trno=head.trno left join item on item.itemid=
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
          $config['params']['data']['loc'] = $data[$key2]->loc;
          $config['params']['data']['rem'] = '';
          $config['params']['data']['amt'] = $data[$key2]->rrcost;
          $config['params']['data']['osrefx'] = $data[$key2]->trno;
          $config['params']['data']['oslinex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $return = $this->additem('insert', $config);
          if ($msg = '') {
            $msg = $return['msg'];
          } else {
            $msg = $msg . $return['msg'];
          }
          if ($return['status']) {
            if ($this->setservedositems($data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setservedositems($data[$key2]->trno, $data[$key2]->line);
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

  // start
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

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
  // end

} //end class
