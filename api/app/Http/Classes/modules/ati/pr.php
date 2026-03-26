<?php

namespace App\Http\Classes\modules\ati;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;

use Datetime;
use DateInterval;

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
    'deptid',
    'sano',
    'svsno',
    'pono',
    'potype',
    'tax',
    'vattype',
    'isexpedite'
  ];
  private $otherfields = ['trno', 'proformainvoice', 'reqtypeid', 'trnxtype'];
  private $except = ['trno', 'dateid'];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  private $reporter;
  private $helpClass;

  public $rowperpage = 0;

  public $showfilterlabel = [
    ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
    ['val' => 'locked', 'label' => 'Locked', 'color' => 'red'],
    ['val' => 'posted', 'label' => 'Posted', 'color' => 'orange'],
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


  public function createdoclisting()
  {
    $action = 0;
    $liststatus = 1;
    $listdocument = 2;
    $listdate = 3;
    $listclientname = 4;
    $yourref = 5;
    $ourref = 6;
    $deptname = 7;
    $postdate = 8;
    $listpostedby = 9;
    $createdate = 10;
    $listcreateby = 11;
    $listeditby = 12;
    $listviewby = 13;

    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'yourref', 'ourref', 'deptname', 'postdate', 'listpostedby', 'createdate', 'listcreateby', 'listeditby', 'listviewby'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$listclientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';

    $cols[$ourref]['align'] = 'text-left';
    $cols[$postdate]['label'] = 'Post Date';
    $cols[$ourref]['label'] = 'Category';

    $cols[$yourref]['type'] = 'coldel';

    $cols = $this->tabClass->delcollisting($cols);
    return $cols;
  }

  public function paramsdatalisting($config)
  {
    $fields = [];
    $companyid = $config['params']['companyid'];
    switch ($companyid) {
      case 16:
        array_push($fields, 'uploadexcel');
        break;
    }

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'uploadexcel.label', 'UPLOAD MFILES');
    data_set($col1, 'uploadexcel.style', 'font-size:100%;');
    $data = [];

    return ['status' => true, 'data' => $data, 'txtfield' => ['col1' => $col1]];
  }

  public function loaddoclisting($config)
  {

    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));
    $itemfilter = $config['params']['itemfilter'];
    $doc = $config['params']['doc'];
    $adminid = $config['params']['adminid'];
    $center = $config['params']['center'];
    $condition = '';
    $limit = "limit 150";
    $searchfield = [];
    $filtersearch = "";
    $search = $config['params']['search'];

    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'head.clientname', 'head.yourref', 'head.ourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
      $limit = "";
    }

    $viewall = $this->othersClass->checkAccess($config['params']['user'], 3868);

    if (!$viewall) {
      $deptid = $this->coreFunctions->getfieldvalue("client", "deptid", "clientid=?", [$adminid]);
      $condition .= " and (head.deptid='" . $deptid . "' or head.createby='" . $config['params']['user'] . "') ";
    }

    $trnxx = '';
    $leftjoin = '';
    $hleftjoin = '';

    if ($adminid != 0) {
      $trnx = $this->coreFunctions->getfieldvalue("client", "deptcode", "clientid=?", [$adminid]);
      $trnxx .= " and info.trnxtype='" . $trnx . "' ";
      $leftjoin = "left join headinfotrans as info on info.trno=head.trno";
      $hleftjoin = "left join hheadinfotrans as info on info.trno=head.trno";
    }

    switch ($itemfilter) {
      case 'draft':
        $condition .= ' and num.postdate is null and head.lockdate is null ';
        break;

      case 'locked':
        $condition .= ' and num.postdate is null and head.lockdate is not null ';
        break;

      case 'posted':
        $condition .= ' and num.postdate is not null ';
        break;
    }
    $qry = "select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid,
    if(head.lockdate is not null,'LOCKED','DRAFT')  as status, date(num.postdate) as postdate,
    head.createby,head.editby,head.viewby,num.postedby,left(head.createdate,10)  as createdate,
     head.yourref, req.category as ourref, ifnull(dept.clientname,'') as deptname
     from " . $this->head . " as head left join " . $this->tablenum . " as num
     on num.trno=head.trno left join reqcategory as req on req.line=head.ourref left join client as dept on dept.clientid=head.deptid $leftjoin
     where head.doc=? and num.center=? and (CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? or CONVERT(head.createdate,DATE)>=? and CONVERT(head.createdate,DATE)<=?)" . $condition . " " . $filtersearch . " $trnxx
     union all
     select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid, 
     'POSTED' as status, date(num.postdate) as postdate,
     head.createby,head.editby,head.viewby, num.postedby,left(head.createdate,10)  as createdate,
      head.yourref, req.category as ourref, ifnull(dept.clientname,'') as deptname  
     from " . $this->hhead . " as head left join " . $this->tablenum . " as num
     on num.trno=head.trno left join reqcategory as req on req.line=head.ourref left join client as dept on dept.clientid=head.deptid $hleftjoin
     where head.doc=? and num.center=? and (CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? or CONVERT(head.createdate,DATE)>=? and CONVERT(head.createdate,DATE)<=?)" . $condition . " " . $filtersearch . " $trnxx
     order by dateid desc,docno desc " . $limit;


    $data = $this->coreFunctions->opentable($qry, [$doc, $center, $date1, $date2, $date1, $date2, $doc, $center, $date1, $date2, $date1, $date2,]);
    // $this->othersClass->logConsole(json_encode($data));
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
    return $buttons;
  } // createHeadbutton


  public function createtab2($access, $config)
  {
    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrytransnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);

    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];
    return $return;
  }


  public function createTab($access, $config)
  {
    $sq_makepo = $this->othersClass->checkAccess($config['params']['user'], 2873);
    $pr_btnvoid_access = $this->othersClass->checkAccess($config['params']['user'], 3601);
    $action = 0;
    $ctrlno = 1;
    $rrqty = 2;
    $uom = 3;
    $rrcost = 4;
    $ext = 5;
    $qa = 6;
    $voidqty = 7;
    $empname = 8;
    $stat = 9;
    $wh = 10;
    $requestorname = 11;
    $purpose = 12;
    $dateneeded = 13;
    $barcode = 14;
    $partno = 15;
    $itemdesc = 16;
    $itemdesc2 = 17;
    $specs = 18;
    $specs2 = 19;
    $unit = 20;
    $rem = 21;
    $duration = 22;
    $deadline = 23;
    $void = 24;
    $ismanual = 25;
    $isasset = 26;
    $itemname = 27;


    $headgridbtns = ['viewref', 'viewdiagram', 'itemqtyvoiding'];

    if ($pr_btnvoid_access == 0) {
      unset($headgridbtns[0]);
    }

    $tab = [
      $this->gridname => [
        'gridcolumns' => [
          'action',
          'ctrlno',
          'rrqty',
          'uom',
          'rrcost',
          'ext',
          'qa',
          'voidqty',
          'empname',
          'stat',
          'wh',
          'requestorname',
          'purpose',
          'dateneeded',
          'barcode',
          'partno',
          'itemdesc',
          'itemdesc2',
          'specs',
          'specs2',
          'unit',
          'rem',
          'duration',
          'deadline',
          'void',
          'ismanual',
          'isasset',
          'itemname'
        ],
        'computefield' => ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'disc' => 'disc', 'total' => 'ext'],
        'headgridbtns' => $headgridbtns,
        'rowperpage' => 0
      ]
    ];

    if ($this->othersClass->checkAccess($config['params']['user'], 4029)) {
      $tab['stockinfotab'] = ['action' => 'tableentry', 'lookupclass' => 'tabstockinfo', 'label' => 'UPDATE DETAILS', 'checkchanges' => 'tableentry'];
    }

    $tab['stathistorytab'] = ['action' => 'tableentry', 'lookupclass' => 'tabstathistory', 'label' => 'STATUS HISTORY', 'checkchanges' => 'tableentry'];

    $stockbuttons = ['save', 'delete', 'showbalance'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0]['inventory']['descriptionrow'] = ['itemname', 'partno', 'Itemname'];

    if (!$access['changeamt']) {
      $obj[0]['inventory']['columns'][$qa]['readonly'] = true;
      $obj[0]['inventory']['columns'][$rem]['readonly'] = true;
    }
    $obj[0]['inventory']['columns'][$rrqty]['style'] = 'width: 90px;whiteSpace: normal;min-width:90px;max-width:90px';
    $obj[0]['inventory']['columns'][$qa]['style'] = 'width: 90px;whiteSpace: normal;min-width:90px;max-width:90px';

    $obj[0]['inventory']['columns'][$itemdesc]['style'] = 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px';
    $obj[0]['inventory']['columns'][$itemname]['style'] = 'width: 0px;whiteSpace: normal;min-width:0px;max-width:0px';
    $obj[0]['inventory']['columns'][$purpose]['style'] = 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px';
    $obj[0]['inventory']['columns'][$dateneeded]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px';
    $obj[0]['inventory']['columns'][$rem]['style'] = 'width: 300px;whiteSpace: normal;min-width:150px;max-width:300px';

    $obj[0]['inventory']['columns'][$uom]['style'] = 'width: 50px;whiteSpace: normal;min-width:50px;max-width:50px';
    $obj[0]['inventory']['columns'][$barcode]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px';
    $obj[0]['inventory']['columns'][$empname]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px';
    $obj[0]['inventory']['columns'][$requestorname]['style'] = 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px';
    $obj[0]['inventory']['columns'][$stat]['style'] = 'width: 100px;whiteSpace: normal;min-width:150px;max-width:100px';

    $obj[0]['inventory']['columns'][$rrcost]['label'] = 'Cost';
    $obj[0]['inventory']['columns'][$ext]['label'] = 'Total Cost';

    $obj[0]['inventory']['columns'][$itemdesc]['type'] = 'textarea';
    $obj[0]['inventory']['columns'][$purpose]['type'] = 'textarea';
    $obj[0]['inventory']['columns'][$rem]['type'] = 'textarea';
    $obj[0]['inventory']['columns'][$specs]['type'] = 'textarea';
    //2023.11.22 - tempporary enable unit column
    //$obj[0]['inventory']['columns'][$unit]['type'] = 'label';
    $obj[0]['inventory']['columns'][$unit]['readonly'] = false;
    $obj[0]['inventory']['columns'][$partno]['type'] = 'label';

    $obj[0]['inventory']['columns'][$stat]['label'] = 'Status';
    $obj[0]['inventory']['columns'][$stat]['type'] = 'lookup';
    $obj[0]['inventory']['columns'][$stat]['action'] = 'lookupstatname';
    $obj[0]['inventory']['columns'][$stat]['lookupclass'] = 'lookupitemstatus';

    $obj[0]['inventory']['columns'][$duration]['type'] = 'lookup';
    $obj[0]['inventory']['columns'][$duration]['action'] = 'lookupduration';
    $obj[0]['inventory']['columns'][$duration]['lookupclass'] = 'lookupduration';

    $obj[0]['inventory']['columns'][$empname]['label'] = 'Assigned User';
    $obj[0]['inventory']['columns'][$empname]['type'] = 'lookup';
    $obj[0]['inventory']['columns'][$empname]['action'] = 'lookupclient';
    $obj[0]['inventory']['columns'][$empname]['lookupclass'] = 'lookupemployeepo';

    $obj[0]['inventory']['columns'][$itemdesc]['readonly'] = false;
    $obj[0]['inventory']['columns'][$specs]['readonly'] = false;

    $obj[0]['inventory']['columns'][$barcode]['type'] = 'lookup';
    $obj[0]['inventory']['columns'][$barcode]['action'] = 'lookupbarcode';
    $obj[0]['inventory']['columns'][$barcode]['lookupclass'] = 'gridbarcode';

    $obj[0]['inventory']['columns'][$unit]['label'] = 'Temp UOM';

    $obj[0]['inventory']['columns'][$deadline]['label'] = 'Deadline';
    $obj[0]['inventory']['columns'][$deadline]['type'] = 'label';

    // $obj[0]['inventory']['columns'][$rrcost]['type'] = 'coldel';
    // $obj[0]['inventory']['columns'][$ext]['type'] = 'coldel';

    $obj[0]['inventory']['columns'][$ismanual]['checkfield'] = 'ismanual2';

    // $obj[0]['inventory']['columns'][$requestorname]['readonly'] = false;
    // $obj[0]['inventory']['columns'][$requestorname]['type'] = 'input';

    $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['threshold', 'additem', 'quickadd', 'saveitem', 'deleteallitem', 'addrow'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $adminid = $config['params']['adminid'];

    $fields = ['docno', 'client', 'clientname', ['sadesc', 'svsdesc'], ['podesc', 'potype'], 'dwhname'];
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'client.lookupclass', 'customer'); //replookupdepartment
    data_set($col1, 'svsdesc.lookupclass', 'lookupsvsdesc');
    data_set($col1, 'podesc.lookupclass', 'lookuppodesc');
    data_set($col1, 'docno.label', 'Transaction#');
    data_set($col1, 'clientname.class', 'sbccsreadonly');
    data_set($col1, 'client.required', false);
    // data_set($col1, 'svsdesc.required', false);
    if ($adminid != 0) {
      $potype = $this->coreFunctions->getfieldvalue("client", "deptcode", "clientid=?", [$adminid]);
      if ($potype != '') {
        data_set($col1, 'potype.type', 'input');
        data_set($col1, 'potype.class', 'sbccsreadonly');
      }
    }

    $fields = [['dateid', 'tmpref'], 'dvattype', 'department', 'ddeptname', 'yourref', 'categoryname'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'department.label', 'Temp. Department');
    data_set($col2, 'ddeptname.label', 'Department');
    data_set($col2, 'yourref.class', 'sbccsreadonly');
    data_set($col2, 'yourref.label', 'Temp. Category');
    data_set($col2, 'yourref.type', 'input');
    data_set($col2, 'categoryname.type', 'lookup');
    data_set($col2, 'categoryname.action', 'lookupreqcategory');
    data_set($col2, 'categoryname.lookupclass', 'lookupreqcategory');
    data_set($col2, 'categoryname.label', 'Category');
    data_set($col2, 'tmpref.readonly', true);
    data_set($col2, 'tmpref.class', 'sbccsreadonly');

    data_set($col2, 'ddeptname.required', false);
    data_set($col2, 'categoryname.required', false);

    $fields = ['prepared', 'rem', 'reqtype', 'isexpedite'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'prepared.readonly', true);
    data_set($col3, 'prepared.class', 'sbccsreadonly');
    data_set($col3, 'reqtype.label', 'Request Type');

    data_set($col3, 'reqtype.required', false);

    $fields = ['updatepostedinfo'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'updatepostedinfo.label', 'UPDATE INFO');

    return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4];
  }

  public function createnewtransaction($docno, $params)
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['client'] = '';
    $data[0]['clientname'] = '';
    $data[0]['prepared'] = '';
    $data[0]['dept'] = '';
    $data[0]['deptname'] = '';
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
    $data[0]['wh'] = '';
    //pinaremove ni mam patricia to. nakadefault lang na empty. upon posting ang restriction.
    // $data[0]['wh'] = $this->companysetup->getwh($params);
    // $name = $this->coreFunctions->datareader("select clientname as value from client where client='" . $data[0]['wh'] . "'");
    // $data[0]['whname'] = $name;
    $data[0]['whname'] = '';
    $data[0]['address'] = '';
    $data[0]['purtype'] = '';
    $data[0]['budgetreqno'] = '';
    $data[0]['tmpref'] = '';
    $data[0]['deptid'] = 0;
    $data[0]['sano'] = 0;
    $data[0]['svsno'] = 0;
    $data[0]['pono'] = 0;
    $data[0]['sadesc'] = '';
    $data[0]['svsdesc'] = '';
    $data[0]['podesc'] = '';
    $data[0]['proformainvoice'] = '';
    $data[0]['categoryname'] = '';
    $data[0]['reqtype'] = '';
    $data[0]['reqtypeid'] = 0;
    $data[0]['potype'] = '';
    $data[0]['vattype'] = '';
    $data[0]['tax'] = 0;
    $data[0]['isexpedite'] = '0';
    $data[0]['trnxtype'] = '';

    if ($params['adminid'] != 0) {
      $data[0]['trnxtype'] = $this->coreFunctions->getfieldvalue("client", "deptcode", "clientid=?", [$params['adminid']]);
      $data[0]['potype'] =  $data[0]['trnxtype'];
    }
    return $data;
  }

  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
    $id = $config['params']['adminid'];

    $trnxx = '';
    if ($id != 0) {
      $trnx = $this->coreFunctions->getfieldvalue("client", "deptcode", "clientid=?", [$id]);
      $trnxx .= " and info.trnxtype='" . $trnx . "' ";
    }

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
         head.agent,
         head.purtype,
         head.requestor,
         req.clientname as requestorname,
         agent.clientname as agentname,
         ifnull(cat.category,'') as categoryname,
         head.wh as wh,
         warehouse.clientname as whname,
         '' as dwhname,
         head.tax,
         head.vattype,
         '' as dvattype,
         head.tax,
         left(head.due,10) as due,
         client.groupid,
         head.budgetreqno,
         head.deptid,
         ifnull(dept.client,'') as dept, ifnull(dept.clientname,'') as deptname,
         ifnull(info.prepared,'') as prepared, ifnull(info.department,'') as department, ifnull(info.tmpref,'') as tmpref,
         head.sano, ifnull(sa.sano,'') as sadesc,
         head.svsno,ifnull(svs.sano,'') as svsdesc,
         head.pono,ifnull(po.sano,'') as podesc, 
         ifnull(info.proformainvoice,'') as proformainvoice, head.potype, type.reqtype, info.reqtypeid,
         cast(ifnull(head.isexpedite,0) as char) as isexpedite,ifnull(info.trnxtype,'') as trnxtype";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as agent on agent.client = head.agent
        left join client as dept on dept.clientid = head.deptid
        left join client as req on req.clientid = head.requestor
        left join headinfotrans as info on info.trno=head.trno
        left join clientsano as sa on sa.line=head.sano
        left join clientsano as svs on svs.line=head.svsno
        left join clientsano as po on po.line=head.pono
        left join reqcategory as cat on cat.line=head.ourref
        left join reqcategory as type on type.line=info.reqtypeid
        where head.trno = ? and num.center = ? $trnxx
        union all 
        " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as agent on agent.client = head.agent
        left join client as dept on dept.clientid = head.deptid
        left join client as req on req.clientid = head.requestor
        left join hheadinfotrans as info on info.trno=head.trno
        left join clientsano as sa on sa.line=head.sano
        left join clientsano as svs on svs.line=head.svsno
        left join clientsano as po on po.line=head.pono
        left join reqcategory as cat on cat.line=head.ourref
        left join reqcategory as type on type.line=info.reqtypeid
          where head.trno = ? and num.center=? $trnxx";
    // $this->coreFunctions->LogConsole($qry);

    $head = $this->coreFunctions->opentable($qry, [$trno, $center, $trno, $center]);
    if (!empty($head)) {
      $stock = $this->openstock($trno, $config);
      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }

      $hideobj = ['updatepostedinfo' => !$isposted];

      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
      return  ['head' => $head, 'griddata' => ['inventory' => $stock], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg, 'hideobj' => $hideobj];
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
    $dataother = [];

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

    foreach ($this->otherfields as $key) {
      if (array_key_exists($key, $head)) {
        $dataother[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $dataother[$key] = $this->othersClass->sanitizekeyfield($key, $dataother[$key], '', $companyid);
        } //end if
      }
    }


    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    if ($isupdate) {
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
      $whid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$data['wh']]);
      $this->coreFunctions->sbcupdate($this->stock, ['whid' => $whid], ['trno' => $head['trno']]);
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head, $data);
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['client'] . ' - ' . $head['clientname']);
    }

    $infotransexist = $this->coreFunctions->getfieldvalue("headinfotrans", "trno", "trno=?", [$head['trno']]);
    if ($infotransexist == '') {
      $this->coreFunctions->sbcinsert("headinfotrans", ['trno' => $head['trno'], 'reqtypeid' => $head['reqtypeid'], 'trnxtype' => $head['trnxtype']]);
    } else {
      $this->coreFunctions->sbcupdate("headinfotrans", $dataother, ['trno' => $head['trno']]);
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
    $this->coreFunctions->execqry('delete from headinfotrans where trno=?', 'delete', [$trno]);
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
      return ['status' => false, 'msg' => 'Posting failed. Check carefully, some item/s have zero quantity.'];
    }
    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    if ($this->othersClass->isposted($config)) {
      return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
    }

    $client = $this->coreFunctions->getfieldvalue("prhead", "client", "trno=?", [$trno]);
    if ($client == '') {
      return ['status' => false, 'msg' => 'Posting failed. Please input valid Customer.'];
    }

    $wh = $this->coreFunctions->getfieldvalue("prhead", "wh", "trno=?", [$trno]);
    if ($wh == '') {
      return ['status' => false, 'msg' => 'Posting failed. Please input Warehouse.'];
    }

    $cldetails = $this->coreFunctions->datareader('select ifnull(cat.iscldetails,0) as value from ' . $this->head . ' as h left join reqcategory as cat on cat.line=h.ourref where trno=?', [$trno], '', true);
    if ($cldetails) {
      $isexpedite = $this->coreFunctions->getfieldvalue("prhead", "isexpedite", "trno=?", [$trno], '', true);
      if (!$isexpedite) {
        RequiredClientDetails:
        $qry = "select sano,svsno,pono from prhead where trno=?";
        $svs = $this->coreFunctions->opentable($qry, [$trno]);
        if ($svs[0]->svsno == 0) {
          return ['status' => false, 'msg' => 'Posting failed. SVS No. is required.'];
        }
        if ($svs[0]->sano == 0) {
          return ['status' => false, 'msg' => 'Posting failed. SA No. is required.'];
        }
        if ($svs[0]->pono == 0) {
          return ['status' => false, 'msg' => 'Posting failed. PO No. is required.'];
        }
      }
    }

    $deptid = $this->coreFunctions->getfieldvalue($this->head, "deptid", "trno=?", [$trno], '', true);
    if ($deptid == 0) {
      return ['status' => false, 'msg' => 'Posting failed. Department is required.'];
    }

    $ourref = $this->coreFunctions->getfieldvalue($this->head, "ourref", "trno=?", [$trno]);
    if ($ourref == '') {
      return ['status' => false, 'msg' => 'Posting failed. Category is required.'];
    }

    $reqtypeid = $this->coreFunctions->getfieldvalue("headinfotrans", "reqtypeid", "trno=?", [$trno], '', true);
    if ($reqtypeid == 0) {
      return ['status' => false, 'msg' => 'Posting failed. Request Type is required.'];
    }

    $dateneeded = $this->coreFunctions->opentable("select trno from stockinfotrans where trno=? and dateneeded is null", [$trno]);
    if (!empty($dateneeded)) {
      return ['status' => false, 'msg' => 'Posting failed. Date needed for all items is required.'];
    }

    $isasset = $this->coreFunctions->opentable("select trno from stockinfotrans where trno=? and isasset=''", [$trno]);
    if (!empty($isasset)) {
      return ['status' => false, 'msg' => 'Posting failed. Please indicate if the item is an asset or not.'];
    }

    $this->coreFunctions->execqry("UPDATE stockinfotrans AS s LEFT JOIN duration AS d ON d.line=s.durationid 
                                    SET s.deadline=DATE_ADD('" . $this->othersClass->getCurrentDate() . "', INTERVAL d.days DAY),
                                    s.editby='" . $user . "', s.editdate='" . $this->othersClass->getCurrentTimeStamp() . "'
                                    WHERE s.trno=" . $trno);

    //for glhead
    $qry = "insert into " . $this->hhead . "(trno,doc,docno,client,clientname,address,shipto,dateid,
      terms,rem,forex,yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,agent,wh,due,cur,purtype,requestor, 
      budgetreqno,deptid,sano,svsno,pono,potype,tax,vattype,isexpedite)
      SELECT head.trno,head.doc, head.docno,head.client, head.clientname, head.address,head.shipto,
      head.dateid as dateid, head.terms, head.rem, head.forex,head.yourref, head.ourref,
      head.createdate,head.createby,head.editby,head.editdate, head.lockdate,head.lockuser,head.agent,head.wh,
      head.due,head.cur,head.purtype,head.requestor,
      head.budgetreqno,head.deptid,head.sano,head.svsno,head.pono,head.potype,head.tax,head.vattype,head.isexpedite
      FROM " . $this->head . " as head left join cntnum on cntnum.trno=head.trno
      where head.trno=? limit 1";
    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($posthead) {
      // for glstock

      if (!$this->othersClass->postingheadinfotrans($config)) {
        return ['trno' => $trno, 'status' => false, 'msg' => 'An error occurred while posting head data.'];
      }

      if (!$this->othersClass->postingstockinfotrans($config)) {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'An error occurred while posting stock/s.'];
      }

      $qry = "insert into " . $this->hstock . "(trno,line,itemid,uom,
        whid,loc,ref,disc,cost,qty,void,rrcost,rrqty,ext,
        encodeddate,qa,encodedby,editdate,editby,refx,linex,cdqa,rem,status,suppid, oqqa, ismanual)
        SELECT trno, line, itemid, uom,whid,loc,ref,disc,cost, qty,void,rrcost, rrqty, ext,
        encodeddate,qa, encodedby,editdate,editby,refx,linex,cdqa,rem,status,suppid, oqqa, ismanual FROM " . $this->stock . " where trno =?";
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
      //if($posthead){
    } else {
      return ['status' => false, 'msg' => 'Error on Posting Head'];
    }
  } //end function

  public function unposttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $qry = "select trno from " . $this->hstock . " where trno=? and ((qa+cdqa)>0 or iscanvass=1)";
    $data = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($data)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, either already served...'];
    }
    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    $qry = "insert into " . $this->head . "(trno,doc,docno,client,clientname,address,shipto,dateid,terms,rem,forex,
          yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,wh,due,cur,purtype,requestor, budgetreqno, deptid,sano,svsno,pono,potype,tax,vattype,isexpedite)
          select head.trno, head.doc, head.docno, client.client, head.clientname, head.address, head.shipto,
          head.dateid as dateid, head.terms, head.rem, head.forex, head.yourref, head.ourref, head.createdate,
          head.createby, head.editby, head.editdate, head.lockdate, head.lockuser,head.wh,head.due,head.cur,head.purtype,head.requestor, 
          head.budgetreqno, head.deptid,head.sano, head.svsno,head.pono,head.potype,head.tax,head.vattype,head.isexpedite
          from (" . $this->hhead . " as head left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno)left join client on client.client=head.client
          where head.trno=? limit 1";
    //head
    if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
      if (!$this->othersClass->unpostingheadinfotrans($config)) {
        return ['trno' => $trno, 'status' => false, 'msg' => 'An error occurred while unposting head data.'];
      }

      if (!$this->othersClass->unpostingstockinfotrans($config)) {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Unposting failed. There are issues with inventory.'];
      }

      $qry = "insert into " . $this->stock . "(
      trno,line,itemid,uom,whid,loc,ref,disc,
      cost,qty,void,rrcost,rrqty,ext,rem,encodeddate,qa,encodedby,editdate,editby,refx,linex,cdqa,status,suppid,oqqa,ismanual)
      select trno, line, itemid, uom,whid,loc,ref,disc,cost, qty,void, rrcost, rrqty,
      ext,rem, encodeddate, qa, encodedby, editdate, editby,refx,linex,cdqa,status,suppid,oqqa,ismanual
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
    } else {
      return ['status' => false, 'msg' => 'Error on Unposting Head'];
    }
  } //end function

  private function getstockselect($config)
  {
    $sqlselect = "select item.brand as brand,
    ifnull(mm.model_name,'') as model,
    item.itemid,
    item.partno,
    stock.trno,
    stock.line,
    stock.refx,
    stock.linex,
    item.barcode,
    item.itemname,
    stock.uom,
    stock.cost,
    stock.qty as qty,
    FORMAT(stock.rrcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrcost,
    FORMAT(stock.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as rrqty,
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,
    left(stock.encodeddate,10) as encodeddate,
    stock.disc,
    case when stock.void=0 then 'false' else 'true' end as void,
    case when stock.ismanual=0 then 'false' else 'true' end as ismanual,
    'true' as ismanual2,
    round((stock.qty-(stock.qa+stock.voidqty))/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
    round(stock.voidqty/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as voidqty,
    stock.ref,
    stock.whid,
    warehouse.client as wh,
    warehouse.clientname as whname,
    stock.loc,
    item.brand,
    ifnull(info.rem,'') as rem,
    ifnull(uom.factor,1) as uomfactor,
    '' as bgcolor,
    case when stock.void=0 then '' else 'bg-red-2' end as errcolor,
    item.subcode, item.partno, round(item.dqty, " . $this->companysetup->getdecimal('qty', $config['params']) . ") as boxcount, 
    ifnull(info.itemdesc,'') as itemdesc, ifnull(info.itemdesc2,'') as itemdesc2, ifnull(info.unit,'') as unit, ifnull(info.specs,'') as specs, ifnull(info.specs2,'') as specs2, ifnull(info.purpose,'') as purpose,
    ifnull(info.requestorname,'') as requestorname, date(info.dateneeded) as dateneeded, stock.status, ifnull(stat.status,'') as stat, stock.suppid, ifnull(emp.clientname,'') as empname,
    info.durationid, ifnull(d.duration,'') as duration, date(info.deadline) as deadline, date(info.origdeadline) as origdeadline,info.isasset,info.ctrlno";


    return $sqlselect;
  }

  public function openstock($trno, $config)
  {
    $sqlselect = $this->getstockselect($config);

    $qry = $sqlselect . "
    FROM $this->stock as stock
    left join item on item.itemid=stock.itemid
    left join stockinfotrans as info on info.trno=stock.trno and info.line=stock.line left join model_masterfile as mm on mm.model_id = item.model
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom left join client as warehouse on warehouse.clientid=stock.whid 
    left join trxstatus as stat on stat.line=stock.status left join client as emp on emp.clientid=stock.suppid left join duration as d on d.line=info.durationid
    where stock.trno =?
    UNION ALL
    " . $sqlselect . "
    FROM $this->hstock as stock
    left join item on item.itemid=stock.itemid
    left join hstockinfotrans as info on info.trno=stock.trno and info.line=stock.line left join model_masterfile as mm on mm.model_id = item.model
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom left join client as warehouse on warehouse.clientid=stock.whid
    left join trxstatus as stat on stat.line=stock.status  left join client as emp on emp.clientid=stock.suppid left join duration as d on d.line=info.durationid
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
  left join stockinfotrans as info on info.trno=stock.trno and info.line=stock.line
   left join model_masterfile as mm on mm.model_id = item.model
  left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
  left join client as warehouse on warehouse.clientid=stock.whid 
  left join trxstatus as stat on stat.line=stock.status left join client as emp on emp.clientid=stock.suppid left join duration as d on d.line=info.durationid
  where stock.trno = ? and stock.line = ? ";

    $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $stock;
  } // end function

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'addrow':
        return $this->addrow($config);
        break;
      case 'additem':
        return $this->additem('insert', $config);
        break;
      case 'getthreshold':
        return $this->getthreshold($config);
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
      case 'updateitemvoid':
        return $this->updateitemvoid($config);
        break;
      case 'diagram':
        return $this->diagram($config);
        break;

      case 'uploadexcel':
        return $this->generatepr($config);
        break;

      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }


  private function generatepr_testing($config)
  {
    // new format with notes next to column name
    array_shift($config['params']['data']);
    $raw = $config['params']['data'];
    $refno = 0;
    $blnNoRef = false;
    $blnSVS = false;
    $prefix = '';
    $clientid = 0;
    $SANo = '';
    $SVSNo = '';

    if (!empty($raw)) {
      foreach ($raw as $key => $value) {

        $validrow = false;

        if (isset($value['Document No.'])) if (trim($value['Document No.']) != '') $validrow = true;
        if (isset($value['SVSPRF No.'])) if (trim($value['SVSPRF No.']) != '') $validrow = true;
        if (isset($value['Requisition Reference Type'])) if (trim($value['Requisition Reference Type']) != '') $validrow = true;
        if (isset($value['Item'])) if (trim($value['Item']) != '') $validrow = true;
        if (isset($value['Department of Requestor'])) if (trim($value['Department of Requestor']) != '') $validrow = true;
        if (isset($value['Prepared by'])) if (trim($value['Prepared by']) != '') $validrow = true;
        if (isset($value['Date Requested'])) if (trim($value['Date Requested']) != '') $validrow = true;
        if (isset($value['Category'])) if (trim($value['Category']) != '') $validrow = true;

        if (!$validrow) {
          unset($config['params']['data'][$key]);
          $this->othersClass->logConsole('--skip--not valid row---');
          continue;
        };

        if (isset($value['Requisition Reference Type'])) {
          if (isset($value['Document No.'])) {
            if ($value['Document No.'] != '') {
              if (is_numeric($value['Document No.'])) {
                $refno = $value['Document No.'];
                if (strlen($refno) > 11) {
                  return ['status' => false, 'msg' => 'Please input valid Document No, must not be longer than 11 chars'];
                }
                $blnNoRef = false;
              } else {
                if ($value['Document No.'] == '') {
                  $blnNoRef = true;
                } else {
                  return ['status' => false, 'msg' => 'Please input valid Document No, must not be longer than 11 chars'];
                }
              }
            }
          } else {
            if ($value['Requisition Reference Type'] == 'IRF') {
              $blnNoRef = true;
            } else {
              return ['status' => false, 'msg' => 'Please input valid Document No.'];
            }
          }
        } else {
          return ['status' => false, 'msg' => 'Please select valid Requisition Reference Type'];
        }

        switch ($value['Requisition Reference Type']) {
          case 'IRF':
            if ($blnNoRef) {
              $prefix = 'PR';
            } else {
              $prefix = 'PRM';
            }
            break;
          case 'SVS':
          case 'SA':
          case 'Consumables':
            if ($value['Requisition Reference Type'] == 'Consumables') {
              $prefix = 'CR';
            } else {
              $prefix = $value['Requisition Reference Type'];
            }
            if (isset($value['SVSPRF No.'])) {
              if (is_numeric($value['SVSPRF No.'])) {
                if ($prefix == 'SA') {
                  $SANo = $value['SVSPRF No.'];
                } elseif ($prefix == 'SVS') {
                  $SVSNo = $value['SVSPRF No.'];
                }

                $clientid = $this->coreFunctions->getfieldvalue("clientsano", "clientid", "sano=?", [$value['SVSPRF No.']], '', true);
                if ($clientid == 0) unset($config['params']['data'][$key]['SVSPRF No.']);
              } else {
                return ['status' => false, 'msg' => 'Invalid SVSPRF No ' . $value['SVSPRF No.']];
              }
            } else {
              return ['status' => false, 'msg' => 'Invalid SVSPRF No'];
            }


            break;
        }


        if (isset($value['Department of Requestor'])) {
          if ($value['Department of Requestor'] == '') {
            return ['status' => false, 'msg' => 'Please input valid department'];
          }
        } else {
          return ['status' => false, 'msg' => 'Please input valid Department of Requestor (missing field)'];
        }
        if (isset($value['Prepared by'])) {
          if ($value['Prepared by'] == '') {
            return ['status' => false, 'msg' => 'Please input valid prepared by'];
          }
        } else {
          return ['status' => false, 'msg' => 'Please input valid Prepared by (missing field)'];
        }
        if (isset($value['Item'])) {
          if ($value['Item'] == '') {
            return ['status' => false, 'msg' => 'Please input valid item'];
          }
        } else {
          return ['status' => false, 'msg' => 'Please input valid Item (missing field)'];
        }
        if (isset($value['Date Requested'])) {
          if ($value['Date Requested'] == '') {
            return ['status' => false, 'msg' => 'Please input valid Date Requested'];
          }
        } else {
          return ['status' => false, 'msg' => 'Please input valid Date Requested (missing field)'];
        }
        if (isset($value['Category'])) {
          if ($value['Category'] == '') {
            return ['status' => false, 'msg' => 'Please input valid Category'];
          }
        } else {
          return ['status' => false, 'msg' => 'Please input valid Category (missing field)'];
        }
      }

      if ($blnNoRef) {
        $lastseq = $this->othersClass->getlastseq("PR", $config, $this->tablenum);
        $newdocno = $this->othersClass->PadJ('PR' . $lastseq, $this->companysetup->documentlength);
        // }
      } else {
        $newdocno = $this->othersClass->PadJ($prefix . $refno, $this->companysetup->documentlength);
      }
      $trnoexist = $this->coreFunctions->getfieldvalue($this->tablenum, "trno", "docno=?", [$newdocno]);
      if ($trnoexist == '') {
        $this->othersClass->logConsole('refno:' . $refno);

        if ($clientid != 0) {
          $config['params']['client'] = $this->coreFunctions->getfieldvalue("client", "client", "clientid=?", [$clientid]);
          $config['params']['clientname'] = $this->coreFunctions->getfieldvalue("client", "clientname", "clientid=?", [$clientid]);
          if ($prefix == 'SA') $config['params']['sano'] = $this->coreFunctions->getfieldvalue("clientsano", "line", "clientid=? and sano=?", [$clientid, $SANo]);
          if ($prefix == 'SVS') $config['params']['svsno'] = $this->coreFunctions->getfieldvalue("clientsano", "line", "clientid=? and sano=?", [$clientid, $SVSNo]);
        }
        $result = $this->othersClass->generateShortcutTransaction($config, $refno, '', $prefix);
        if (!$result['status']) {
          $this->coreFunctions->execqry('delete from ' . $this->stock . " where trno=?", 'delete', [$result['trno']]);
          $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$result['trno']]);
          $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$result['trno']]);
          $this->coreFunctions->execqry('delete from headinfotrans where trno=?', 'delete', [$result['trno']]);
          $this->coreFunctions->execqry('delete from stockinfotrans where trno=?', 'delete', [$result['trno']]);
        }
        return $result;
      } else {
        return ['status' => false, 'msg' => 'IRF No. ' . $refno . ' was already created.'];
      }
    }

    return ['status' => true, 'msg' => 'Success.'];
  }

  private function generatepr($config)
  {
    // new format with notes next to column name
    array_shift($config['params']['data']);
    $raw = $config['params']['data'];
    $refno = 0;
    $blnNoRef = false;
    $blnSVS = false;
    $prefix = '';
    $clientid = 0;
    $SANo = '';
    $SVSNo = '';

    if (!empty($raw)) {
      foreach ($raw as $key => $value) {

        $validrow = false;

        if (isset($value['SA No.'])) if (trim($value['SA No.']) != '') $validrow = true;
        if (isset($value['SVS No.'])) if (trim($value['SVS No.']) != '') $validrow = true;
        if (isset($value['SVSPRF No.'])) if (trim($value['SVSPRF No.']) != '') $validrow = true;
        if (isset($value['IRF Reference No'])) if (trim($value['IRF Reference No']) != '') $validrow = true;
        if (isset($value['Item'])) if (trim($value['Item']) != '') $validrow = true;
        if (isset($value['Department of Requestor'])) if (trim($value['Department of Requestor']) != '') $validrow = true;
        if (isset($value['Prepared by'])) if (trim($value['Prepared by']) != '') $validrow = true;
        if (isset($value['Date Requested'])) if (trim($value['Date Requested']) != '') $validrow = true;
        if (isset($value['Category'])) if (trim($value['Category']) != '') $validrow = true;

        if (!$validrow) {
          unset($config['params']['data'][$key]);
          $this->othersClass->logConsole('--skip--not valid row---');
          continue;
        };

        if (isset($value['Date Requested'])) {
          $UNIX_DATE = ($value['Date Requested'] - 25569) * 86400;
          $yearrq = date('Y', strtotime(gmdate("Y-m-d", $UNIX_DATE)));
          $year = date('Y', strtotime($this->othersClass->getCurrentDate()));
          if ($yearrq != $year) {
            return ['status' => false, 'msg' => 'Invalid Date request ' . strtotime(gmdate("Y-m-d", $UNIX_DATE))];
          }
        }

        if (isset($value['SA No.'])) {
          if ($value['SA No.'] != '') {
            $SANo = $value['SA No.'];
            $prefix = 'SA';
            if (isset($value['SVSPRF No.'])) {
              if (is_numeric($value['SVSPRF No.'])) {
                $refno = $value['SVSPRF No.'];

                $clientid = $this->coreFunctions->getfieldvalue("clientsano", "clientid", "sano=?", [$value['SA No.']], '', true);
                if ($clientid == 0) unset($config['params']['data'][$key]['SA No.']);
              } else {
                return ['status' => false, 'msg' => 'Invalid SVSPRF No ' . $value['SVSPRF No.']];
              }
            } else {
              return ['status' => false, 'msg' => 'Invalid SVSPRF No'];
            }
          } else {
            return ['status' => false, 'msg' => 'Invaldid SA No'];
          }
        } elseif (isset($value['SVS No.'])) {
          if ($value['SVS No.'] != '') {
            $SVSNo = $value['SVS No.'];
            $prefix = 'SVS';
            if (isset($value['SVSPRF No.'])) {
              if (is_numeric($value['SVSPRF No.'])) {
                $refno = $value['SVSPRF No.'];

                $clientid = $this->coreFunctions->getfieldvalue("clientsano", "clientid", "sano=?", [$value['SVS No.']], '', true);
                if ($clientid == 0) unset($config['params']['data'][$key]['SVS No.']);
              } else {
                return ['status' => false, 'msg' => 'Invalid SVSPRF No ' . $value['SVSPRF No.']];
              }
            } else {
              return ['status' => false, 'msg' => 'Invalid SVSPRF No'];
            }
          } else {
            return ['status' => false, 'msg' => 'Invalid SVS No'];
          }
        } else {
          IRFHere:
          if (isset($value['IRF Reference No'])) {
            if (!is_numeric($value['IRF Reference No'])) {
              if ($value['IRF Reference No'] == '') {
                $blnNoRef = true;
              } else {
                return ['status' => false, 'msg' => 'Please input valid reference no'];
              }
            } else {
              $prefix = 'PRM';
              $refno = $value['IRF Reference No'];
              if (strlen($refno) > 11) {
                return ['status' => false, 'msg' => 'Please input valid IRF Reference No, must not be longer than 11 chars'];
              }
              $blnNoRef = false;
            }
          } else {
            $blnNoRef = true;
            return ['status' => false, 'msg' => 'Please input valid IRF Reference No'];
          }
        }


        if (isset($value['Department of Requestor'])) {
          if ($value['Department of Requestor'] == '') {
            return ['status' => false, 'msg' => 'Please input valid department'];
          }
        } else {
          return ['status' => false, 'msg' => 'Please input valid Department of Requestor (missing field)'];
        }
        if (isset($value['Prepared by'])) {
          if ($value['Prepared by'] == '') {
            return ['status' => false, 'msg' => 'Please input valid prepared by'];
          }
        } else {
          return ['status' => false, 'msg' => 'Please input valid Prepared by (missing field)'];
        }

        if (isset($value['Item'])) {
          if ($value['Item'] == '') {
            return ['status' => false, 'msg' => 'Please input valid item'];
          }
        } else {
          return ['status' => false, 'msg' => 'Please input valid Item (missing field)'];
        }
        if (isset($value['Date Requested'])) {
          if ($value['Date Requested'] == '') {
            return ['status' => false, 'msg' => 'Please input valid Date Requested'];
          }
        } else {
          return ['status' => false, 'msg' => 'Please input valid Date Requested (missing field)'];
        }
        if (isset($value['Category'])) {
          if ($value['Category'] == '') {
            return ['status' => false, 'msg' => 'Please input valid Category'];
          }
        } else {
          return ['status' => false, 'msg' => 'Please input valid Category (missing field)'];
        }
      }

      if ($blnNoRef) {
        // remove prefix date: 2023.10.17
        // if ($blnSVS) {
        //   $lastseq = $this->othersClass->getlastseq($value['SVS / SA'], $config, $this->tablenum, '', $value['Prefix Date']);
        //   $this->othersClass->logConsole('lastseq:' . $lastseq);

        //   $bref = $value['SVS / SA'] . $value['Prefix Date'];
        //   // $refno = $value['Prefix Date'] . str_pad($lastseq, $this->companysetup->documentlength - strlen($bref), "0", STR_PAD_LEFT);
        //   $refno = $lastseq;
        //   $newdocno = $value['SVS / SA'] . $refno;

        //   $this->othersClass->logConsole('Prefix Date:' . $value['Prefix Date']);
        //   $this->othersClass->logConsole('newdocno:' . $newdocno);
        // } else {
        $lastseq = $this->othersClass->getlastseq("PR", $config, $this->tablenum);
        $newdocno = $this->othersClass->PadJ('PR' . $lastseq, $this->companysetup->documentlength);
        // }
      } else {
        $newdocno = $this->othersClass->PadJ($prefix . $refno, $this->companysetup->documentlength);
      }
      $trnoexist = $this->coreFunctions->getfieldvalue($this->tablenum, "trno", "docno=?", [$newdocno]);
      if ($trnoexist == '') {
        $this->othersClass->logConsole('refno:' . $refno);
        // remove prefix date: 2023.10.17
        // $this->othersClass->logConsole('yr:' . ($blnSVS ? (isset($value['Prefix Date']) ? $value['Prefix Date'] : '') : ''));
        // $result = $this->othersClass->generateShortcutTransaction($config, $refno, '', $prefix, ($blnSVS ? (isset($value['Prefix Date']) ? $value['Prefix Date'] : 0) : 0));

        if ($clientid != 0) {
          $config['params']['client'] = $this->coreFunctions->getfieldvalue("client", "client", "clientid=?", [$clientid]);
          $config['params']['clientname'] = $this->coreFunctions->getfieldvalue("client", "clientname", "clientid=?", [$clientid]);
          if ($prefix == 'SA') $config['params']['sano'] = $this->coreFunctions->getfieldvalue("clientsano", "line", "clientid=? and sano=?", [$clientid, $SANo]);
          if ($prefix == 'SVS') $config['params']['svsno'] = $this->coreFunctions->getfieldvalue("clientsano", "line", "clientid=? and sano=?", [$clientid, $SVSNo]);
        }
        $result = $this->othersClass->generateShortcutTransaction($config, $refno, '', $prefix);
        if (!$result['status']) {
          $this->coreFunctions->execqry('delete from ' . $this->stock . " where trno=?", 'delete', [$result['trno']]);
          $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$result['trno']]);
          $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$result['trno']]);
          $this->coreFunctions->execqry('delete from headinfotrans where trno=?', 'delete', [$result['trno']]);
          $this->coreFunctions->execqry('delete from stockinfotrans where trno=?', 'delete', [$result['trno']]);
        }
        return $result;
      } else {
        return ['status' => false, 'msg' => 'IRF No. ' . $refno . ' was already created.'];
      }
    }

    return ['status' => true, 'msg' => 'Success.'];
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
    FORMAT(((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending
    from hprhead as head
    left join hprstock as stock on stock.trno = head.trno
    left join item on item.itemid=stock.itemid
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
    where head.trno = ? and stock.qty>stock.qa and stock.void=0 ";
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
    if ($config['params']['row']['line'] == 0) {
      $this->additem('insert', $config);
    } else {
      $this->additem('update', $config);
    }
    $data = $this->openstockline($config);
    return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
  }


  public function updateitem($config)
  {
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      if ($value['line'] == 0) {
        $this->additem('insert', $config);
      } else {
        $this->additem('update', $config);
      }
    }

    $data = $this->openstock($config['params']['trno'], $config);
    return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
  } //end function



  public function getthreshold($config)
  {
    $trno = $config['params']['trno'];
    $wh = $this->coreFunctions->getfieldvalue("prhead", "wh", "trno=?", [$trno]);
    $rows = [];

    foreach ($config['params']['rows'] as $key => $value) {

      $qry = "select item.itemid,item.barcode, item.itemname, item.uom,sum(rs.bal) as qty, ilevel.min, wh.client, wh.clientname, rs.whid
            from rrstatus as rs
            left join item on item.itemid = rs.itemid
            left join client as wh on wh.clientid=rs.whid
            left join itemlevel as ilevel on ilevel.itemid=rs.itemid and ilevel.center = wh.client
            where ifnull(ilevel.min,0)<>0 and rs.itemid=" . $value['itemid'] . " and rs.whid=" . $value['whid'] . "
            group by  item.itemid,item.barcode, item.itemname, item.uom, ilevel.min, wh.client, wh.clientname, rs.whid
            having sum(rs.bal)<>0 and sum(rs.bal) <= ilevel.min";

      $data = $this->coreFunctions->opentable($qry);
      $insert_success = true;

      if (!empty($data)) {
        foreach ($data as $key2 => $value) {

          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['wh'] = $wh;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['data']['disc'] = '';
          $config['params']['data']['amt'] = 0;
          $config['params']['data']['loc'] = '';
          $config['params']['data']['trno'] = $trno;
          $config['params']['data']['qty'] = $data[$key2]->qty;
          $return = $this->additem('insert', $config);

          if ($return['status']) {
            array_push($rows, $return['row'][0]);
          } else {
            $insert_success = false;
          }
        }
      }
    }

    return ['row' => $rows, 'status' => true];
  }


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

  public function addrow($config)
  {
    $data = [];
    $trno = $config['params']['trno'];

    $wh = $this->coreFunctions->getfieldvalue($this->head, "wh", "trno=?", [$trno]);
    $whid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$wh]);

    $data['line'] = 0;
    $data['trno'] = $trno;
    $data['itemid'] = 0;
    $data['rrqty'] = 1;
    $data['qty'] = 1;
    $data['uom'] = '';
    $data['rrcost'] = 0;
    $data['cost'] = 0;
    $data['ext'] = 0;
    $data['amt'] = 0;
    $data['void'] = 'false';
    $data['ismanual'] = 'true';
    $data['deadline'] = null;
    $data['duration'] = '';
    $data['specs'] = '';
    $data['disc'] = '';
    $data['loc'] = '';
    $data['itemname'] = '';
    $data['itemdesc'] = '';
    $data['barcode'] = '';
    $data['rem'] = '';
    $data['unit'] = '';
    $data['purpose'] = '';
    $data['wh'] = $wh;
    $data['whid'] = $whid;
    $data['qa'] = 0;
    $data['stat'] = '';
    $data['statid'] = 0;
    $data['suppid'] = 0;
    $data['empname'] = '';
    $data['partno'] = '';
    $data['requestorname'] = '';
    $data['isasset'] = '';
    $data['bgcolor'] = 'bg-blue-2';
    return ['row' => $data, 'status' => true, 'msg' => 'New row added'];
  }

  // insert and update item
  public function additem($action, $config)
  {
    $barcode = '';
    $uom = $config['params']['data']['uom'];
    $itemid = $config['params']['data']['itemid'];
    $trno = $config['params']['trno'];
    $disc = $config['params']['data']['disc'];
    $wh = $config['params']['data']['wh'];
    $loc = $config['params']['data']['loc'];
    $ctrlno = isset($config['params']['data']['ctrlno']) ? $config['params']['data']['ctrlno'] : '';
    $unit = isset($config['params']['data']['unit']) ? $config['params']['data']['unit'] : '';
    $ismanual = isset($config['params']['data']['ismanual']) ? $config['params']['data']['ismanual'] : 1;
    $void = 'false';

    if ($itemid == '') {
      $itemid = 0;
    }

    if (isset($config['params']['data']['void'])) {
      $void = $config['params']['data']['void'];
    }

    $rem = '';
    if (isset($config['params']['data']['rem'])) {
      $rem = $config['params']['data']['rem'];
    }
    $itemdesc =  (isset($config['params']['data']['itemdesc'])) ? $config['params']['data']['itemdesc'] : '';
    $specs =  (isset($config['params']['data']['specs'])) ? $config['params']['data']['specs'] : '';
    $purpose =  (isset($config['params']['data']['purpose'])) ? $config['params']['data']['purpose'] : '';
    $dateneeded =  (isset($config['params']['data']['dateneeded'])) ? $config['params']['data']['dateneeded'] : '';
    $status =  (isset($config['params']['data']['status'])) ? $config['params']['data']['status'] : 0;
    if ($status == 17) {
      $void = 'true';
    }
    $suppid =  (isset($config['params']['data']['suppid'])) ? $config['params']['data']['suppid'] : 0;
    $durationid =  (isset($config['params']['data']['durationid'])) ? $config['params']['data']['durationid'] : 0;
    $isasset =  (isset($config['params']['data']['isasset'])) ? $config['params']['data']['isasset'] : '';

    // $requestorname =  (isset($config['params']['data']['requestorname'])) ? $config['params']['data']['requestorname'] : '';

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

    $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
    $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
    $factor = 1;
    if (!empty($item)) {
      $barcode = $item[0]->barcode;
      $item[0]->factor = $this->othersClass->val($item[0]->factor);
      if ($item[0]->factor !== 0) $factor = $item[0]->factor;
    }
    $qty = round($qty, $this->companysetup->getdecimal('qty', $config['params']));
    $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor);
    // if ($ismanual) {
    $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh], '', true);
    // } else {
    //   $whid = 0;
    // }

    $data = [
      'trno' => $trno,
      'line' => $line,
      'itemid' => $itemid,
      'rrcost' => $amt,
      'cost' => $computedata['amt'],
      'rrqty' => $qty,
      'qty' => $computedata['qty'],
      'ext' => $computedata['ext'],
      'disc' => $disc,
      'whid' => $whid,
      'loc' => $loc,
      'uom' => $uom,
      'void' => $void,
      'status' => $status,
      'suppid' => $suppid,
      'ismanual' => $ismanual
      // 'rem' => $rem
    ];

    if ($durationid != 0) {
      $durationdays = $this->coreFunctions->getfieldvalue("duration", "days", "line=?", [$durationid]);
      $daterequested = $this->coreFunctions->getfieldvalue($this->head, "dateid", "trno=?", [$trno]);

      $newDate = new DateTime($daterequested);
      $days = new DateInterval('P' . $durationdays . 'D');
      $newDate->add($days);
      $duration = $newDate->format('Y-m-d');
    } else {
      $duration = null;
    }

    $datainfo = [
      'trno' => $trno,
      'line' => $line,
      'itemdesc' => $itemdesc,
      'specs' => $specs,
      'purpose' => $purpose,
      'rem' => $rem,
      'durationid' => $durationid,
      'deadline' =>  $duration,
      'origdeadline' =>  $duration,
      'dateneeded' => $dateneeded,
      'isasset' => $isasset,
      'unit' => $unit
    ];

    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }

    foreach ($datainfo as $key => $value) {
      $datainfo[$key] = $this->othersClass->sanitizekeyfield($key, $datainfo[$key]);
    }
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $data['editdate'] = $current_timestamp;
    $data['editby'] = $config['params']['user'];

    $datainfo['editdate'] = $current_timestamp;
    $datainfo['editby'] = $config['params']['user'];

    if ($action == 'insert') {
      $data['encodeddate'] = $current_timestamp;
      $data['encodedby'] = $config['params']['user'];

      $datainfo['ctrlno'] = $this->coreFunctions->getfieldvalue($this->tablenum, "seq", "trno=?", [$trno]) . '-' . $line;

      // $this->coreFunctions->LogConsole(json_encode($data));
      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
        switch ($this->companysetup->getsystemtype($config['params'])) {
          case 'AIMS':
          case 'ATI':
            $datainfo['itemdesc2'] = $itemdesc;
            $datainfo['specs2'] = $specs;
            $this->coreFunctions->sbcinsert('stockinfotrans', $datainfo);
            break;
        }

        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' barcode:' . $barcode . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:' . $computedata['ext'] . ' Uom:' . $uom);
        $row = $this->openstockline($config);
        return ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
      } else {
        return ['status' => false, 'msg' => 'Add item Failed'];
      }
    } elseif ($action == 'update') {
      $result = $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);

      $infotrno = $this->coreFunctions->getfieldvalue('stockinfotrans', "trno", "trno=?", [$trno]);
      if (empty($infotrno)) {
        $this->coreFunctions->sbcinsert('stockinfotrans', $datainfo);
      } else {
        $this->coreFunctions->sbcupdate('stockinfotrans', $datainfo, ['trno' => $trno, 'line' => $line]);
      }

      return $result;
    }

    // end function
  }

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
    //if(($data[0]->qa == $data[0]->qty)){
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = "delete from " . $this->stock . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
    $this->coreFunctions->execqry('delete from stockinfotrans where trno=? and line=?', 'delete', [$trno, $line]);
    // if ($data[0]->refx !== 0) {
    //   $this->setserveditems($data[0]->refx, $data[0]->linex);
    // }
    if ($line != 0) {
      $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' barcode:' . $data[0]->barcode . ' Qty:' . $data[0]->rrqty . ' Amt:' . $data[0]->rrcost . ' Disc:' . $data[0]->disc . ' wh:' . $data[0]->wh . ' ext:' . $data[0]->ext);
    }

    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
    //} else {
    //    return ['status'=>false,'msg'=>'Cannot delete, already served'];
    //}
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
    $this->logger->sbcviewreportlog($config);

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config['params']['dataid']);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
