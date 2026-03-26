<?php

namespace App\Http\Classes\modules\pcf;

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

class px
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Project Costing Form';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $sqlquery;
  private $logger;
  public $expirystatus = ['readonly' => false, 'show' => true, 'showdate' => true];
  public $tablenum = 'transnum';
  public $head = 'pxhead';
  public $hhead = 'hpxhead';
  public $stock = 'pxstock';
  public $hstock = 'hpxstock';
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';
  private $stockselect;
  public $dqty = 'rrqty';
  public $damt = 'rrcost';
  private $fields = ['trno', 'docno', 'dateid', 'projectid','clientname', 'dtcno','pcfno', 'poref', 'aftistock', 'reason','rem', 'agentid', 'project', 'clientid', 'oandaphpusd', 'oandausdphp', 'osphpusd','potrno', 'fullcomm', 'commamt', 'remarks','terms','termsdetails','checkdate'];
  private $blnfields = ['aftistock'];
  private $except = ['trno', 'dateid','projectid'];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  private $reporter;
  private $helpClass;
  public $showfilterlabel = [
    ['val' => 'draft', 'label' => 'Draft', 'color' => 'Primary'],
    ['val' => 'locked', 'label' => 'Locked', 'color' => 'cyan'],
    ['val' => 'posted', 'label' => 'Posted', 'color' => 'Primary'],
    ['val' => 'all', 'label' => 'All', 'color' => 'Primary'],
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
    $this->sqlquery = new sqlquery;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 5376,
      'edit' => 5377,
      'new' => 5378,
      'save' => 5379,
      'delete' => 5380,

      'print' => 5381,
      'lock' => 5382,
      'unlock' => 5383,

      'post' => 5384,
      'unpost' => 5385,
      'deleteitem' => 5386,
      
      'additem' => 5387,
      'edititem' => 5388,

      'pcfadmin' => 5389,
      'changeamt' => 5388,

    );
    return $attrib;
  }
  
  


  public function createdoclisting($config)
  {
    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'rem'];

    foreach ($getcols as $key => $value) {
      $$value = $key;
    }
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$liststatus]['name'] = 'statuscolor';
    $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$listclientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
  
    $cols[$rem]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $cols = $this->tabClass->delcollisting($cols);
    return $cols;
  }

  public function paramsdatalisting($config)
  {
    $fields =[];
    $col1 =[];
    // $allownew = $this->othersClass->checkAccess($config['params']['user'], 2455);
    // if ($allownew == '1') {
    //   array_push($fields, 'pickpo');
    // }

    // array_push($fields, 'selectprefix', 'docno');
    // $col1 = $this->fieldClass->create($fields);
    // data_set($col1, 'pickpo.label', 'PICK QOUTATION');
    // data_set($col1, 'pickpo.action', 'pendingqspcfsummary');
    // data_set($col1, 'pickpo.lookupclass', 'pendingqspcfsummaryshortcut');
    // data_set($col1, 'pickpo.confirmlabel', 'Proceed to pick QOUTATION?');

    // data_set($col1, 'docno.type', 'input');
    // data_set($col1, 'docno.label', 'Search');
    // data_set($col1, 'selectprefix.label', 'Search by');
    // data_set($col1, 'selectprefix.type', 'lookup');
    // data_set($col1, 'selectprefix.lookupclass', 'lookupsearchby');
    // data_set($col1, 'selectprefix.action', 'lookupsearchby');

    $data = $this->coreFunctions->opentable("select '' as docno,'' as selectprefix");

    return ['status' => true, 'data' => $data[0], 'txtfield' => ['col1' => $col1]];
  }

  public function loaddoclisting($config)
  {

    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));
    $itemfilter = $config['params']['itemfilter'];
    $companyid = $config['params']['companyid'];
    $username = $config['params']['user'];
    $user = $config['params']['adminid'];
    $isadmin = $this->othersClass->checkAccess($config['params']['user'], 5389);
    $isphead = 0;
    switch ($companyid) {
      case 10: case 12: //afti
        if($isadmin == 0){
          $isphead = $this->coreFunctions->getfieldvalue("projectmasterfile","agentid","agentid = ?",[$user],'',true);
        }
        break;
    }

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
        $condition = ' and num.postdate is null and head.lockdate is null ';
        break;
      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
      case 'locked':
        $condition = ' and head.lockdate is not null ';
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
      $searchfield = ['head.docno','head.dtcno','head.pcfno', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }

    // $this->coreFunctions->LogConsole($isadmin. "-admin");
    // $this->coreFunctions->LogConsole($isphead. "-phead");

    if ($companyid == 10 || $companyid == 12) { //afti
      if ($isadmin == 0 && $isphead == 0){
        $addparams .= " and head.createby='".$username."'";
      }else{
        if($isphead != 0){
           $addparams .= " and pm.agentid = ".$user ." or head.createby='".$username."'";
        }
      } 
    }

    $dateid = "date_format(head.dateid,'%m-%d-%Y') as dateid";
    if ($searchfilter == "") $limit = '';
    $orderby =  "order by  dateid desc, docno desc";

    $qry = "select head.trno,head.docno,$dateid, case ifnull(head.lockdate,'') when '' then 'DRAFT' else 'LOCKED' end as status,
    head.createby,head.editby,head.viewby,num.postedby, date(num.postdate) as postdate,case ifnull(head.lockdate,'') when '' then 'red' else 'cyan' end as statuscolor,
      head.dtcno, head.pcfno,head.rem,cl.clientname
     from " . $this->head . " as head left join " . $this->tablenum . " as num
     on num.trno=head.trno " . $join . " left join client as cl on cl.clientid = head.clientid 
     left join projectmasterfile as pm on pm.code = head.project where num.center=? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $addparams . " " . $filtersearch . "
     union all
     select head.trno,head.docno,$dateid,'POSTED' as status,
     head.createby,head.editby,head.viewby, num.postedby, date(num.postdate) as postdate,'blue' as statuscolor,
       head.dtcno, head.pcfno,head.rem  ,cl.clientname
     from " . $this->hhead . " as head left join " . $this->tablenum . " as num
     on num.trno=head.trno  " . $hjoin . " left join client as cl on cl.clientid = head.clientid
     left join projectmasterfile as pm on pm.code = head.project   where  num.center=? and convert(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $addparams . " " . $filtersearch . "
    $orderby $limit";
    $this->coreFunctions->LogConsole($qry.$date1 .' '. $date2);
    $data = $this->coreFunctions->opentable($qry, [$center, $date1, $date2, $center, $date1, $date2]);
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
    $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrypcfexpenses', 'label' => 'Expenses', 'access' => 'view']];
    $particulars = $this->tabClass->createtab($tab, []);

    $return['Expenses'] = ['icon' => 'fa fa-envelope', 'tab' => $particulars];

    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrytransnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);

    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];
    

    if ($this->companysetup->getistodo($config['params'])) {
      $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrytransnumtodo', 'label' => 'To Do', 'access' => 'view']];
      $objtodo = $this->tabClass->createtab($tab, []);
      $return['To Do'] = ['icon' => 'fa fa-list', 'tab' => $objtodo];
    }
    $lost = ['customform' => ['action' => 'customform', 'lookupclass' => 'lost']];
    $return['LOST'] = ['icon' => 'fa fa-sticky-note', 'customform' => $lost];

    return $return;
  }


  public function createTab($access, $config)
  {
    $companyid = $config['params']['companyid'];
    $resellerid = $config['params']['resellerid'];
    $isexpiry = $this->companysetup->getisexpiry($config['params']);
    $viewtp = $this->othersClass->checkAccess($config['params']['user'], 5389);
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    $column = [
      'action',
      'barcode',
      'itemdescription',
      'rrqty',
      'rrcost',
      'ext',
      'srp',
      'totalsrp',
      'tp',
      'totaltp'
    ];

    $headgridbtns =  'viewitemstockinfo';

    $tab = [
      $this->gridname => [
        'gridcolumns' => $column,
        'computefield' => ['dqty' => 'rrqty',  'damt' => 'rrcost','total' => 'ext'],
        'headgridbtns' => $headgridbtns
      ],
    ];

    

    foreach ($column as $key => $value) {
      $$value = $key;
    }

    $stockbuttons = ['save', 'showbalance'];
    if ($this->companysetup->getiseditsortline($config['params'])) {
      array_push($stockbuttons, 'sortline');
    }

    array_push($stockbuttons, 'iteminfo');

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['totalfield'] = 'totalsrp';
    // 7 - ref
    $obj[0]['inventory']['columns'][$rrcost]['label'] = 'List';
    
    
    if ($viewtp == '0') {
      $obj[0]['inventory']['columns'][$tp]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$totaltp]['type'] = 'coldel';
    } 
    
    $obj[0]['inventory']['columns'][$barcode]['style'] = 'width: 1%;whiteSpace: normal;min-width:1%;max-width:1%';

    $obj[0]['inventory']['descriptionrow'] = [];
    $obj[0]['inventory']['columns'][$itemdescription]['type'] = 'textarea';
    $obj[0]['inventory']['columns'][$srp]['readonly'] = true;
    $obj[0]['inventory']['columns'][$itemdescription]['readonly'] = true;
    $obj[0]['inventory']['columns'][$itemdescription]['style'] = 'text-align: left; width: 350px;whiteSpace: normal;min-width:350px;max-width:350px;';

    $obj[0]['inventory']['columns'][$barcode]['type'] = 'hidden';
    $obj[0]['inventory']['columns'][$barcode]['label'] = '';
      
    $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['saveitem', 'deleteallitem'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $noeditdate = $this->othersClass->checkAccess($config['params']['user'], 5389);
    $fields = ['docno', 'client','clientname', 'projectname','dagentname','projectid'];

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'docno.label', 'Transaction#');
    data_set($col1, 'dagentname.label', 'Project Owner');
    data_set($col1, 'dagentname.type', 'input');
    data_set($col1, 'projectname.type', 'lookup');
    data_set($col1, 'projectname.class', 'sbccsreadonly');
    data_set($col1, 'projectname.readonly', true);
    data_set($col1, 'projectname.lookupclass', 'lookupproject');
    data_set($col1, 'projectname.action', 'lookupproject');
    data_set($col1, 'client.type', 'lookup');
    data_set($col1, 'clientname.class', 'sbccsreadonly');
    data_set($col1, 'client.lookupclass', 'pcfcustomer');
    data_set($col1, 'client.condition', ['checkstock']);

    data_set($col1, 'client.required', true);
    data_set($col1, 'clientname.required', true);
    data_set($col1, 'projectname.required', true);
    data_set($col1, 'dagentname.required', true);
    data_set($col1, 'projectid.required', true);

    $fields = ['dateid', 'pcfno','dtcno','poref',['terms','termsdetails'],'fullcomm'];

    if ($noeditdate == 0) {
      $fields = ['dateid', 'pcfno','dtcno',['terms','termsdetails'],'poref'];
    }

    $col2 = $this->fieldClass->create($fields);    

      if ($noeditdate == 0) {
        data_set($col2, 'dateid.class', 'sbccsreadonly');
        data_set($col2, 'pcfno.class', 'sbccsreadonly');
        data_set($col2, 'dtcno.class', 'sbccsreadonly');
        data_set($col2, 'fullcomm.class', 'sbccsreadonly');
      }

      data_set($col2, 'poref.type', 'lookup');
      data_set($col2, 'terms.label', 'Payment terms');
      data_set($col2, 'terms.lookupclass', 'termspcf');
      data_set($col2, 'termsdetails.type', 'lookup');
      data_set($col2, 'termsdetails.action', 'lookuprandom');
      data_set($col2, 'termsdetails.lookupclass', 'lookuptermsdet');
      data_set($col2, 'termsdetails.class', 'sbccsreadonly');
      data_set($col2, 'termsdetails.required', true);
      data_set($col2, 'terms.required', true);
      data_set($col2, 'poref.lookupclass', 'lookupyourrefrf');
      data_set($col2, 'poref.action', 'lookupyourrefrf');
      data_set($col2, 'poref.readonly', true);
      data_set($col2, 'poref.addedparams', ['client']);
      data_set($col2, 'poref.class', 'csporef sbccsreadonly');
    
      $fields = ['checkdate','rem','oandaphpusd','oandausdphp','osphpusd','aftistock'];
      // if ($noeditdate == 0) {
      //   $fields = ['rem'];
      // }
 
 
    if ($this->companysetup->getistodo($config['params'])) {
      array_push($fields, 'donetodo');
    }

    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'checkdate.label', 'Checking Date');
    data_set($col3, 'rem.label', 'Reason');
    data_set($col3, 'rem.type', 'lookup');
    data_set($col3, 'rem.lookupclass', 'lookupreasoncode');
    data_set($col3, 'rem.action', 'lookupreasoncode');
    data_set($col3, 'rem.class', 'sbccsreadonly');   
    data_set($col3, 'rem.required', true);
   
    $fields = ['remarks','qtno','lblrem'];
   
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'lblrem.label', 'LOST');
    data_set($col4, 'lblrem.style', 'font-weight:bold;font-size:30px;font-family:Century Gothic;color: red;');
    data_set($col4, 'qtno.type', 'input');
    data_set($col4, 'qtno.class', 'sbccsreadonly');
     
    return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4];
  }

  // if(state.headerdata["fullcomm"]==="A"){
  //         state.headercols.col4.rem.required = true
  //       }

  // if (payload.field !== undefined) {                
  //        if (payload.field === "address") {
  //          console.log("from backend1111111", payload)        
  //          if(state.headerdata["address"]==="A"){
  //            state.headercols.col4.rem.required = true
  //            state.headercols.col4.rem.readonly = true
  //          } else if(state.headerdata["address"]==="B"){
  //            state.headerdata["rem"] = "BB"
  //          } else if(state.headerdata["address"]==="C"){
  //            state.headerdata["rem"] = "CC"
  //          }
  //        }
  //        if (payload.field === "client") {
  //           console.log("client:" + payload.value)
  //        }
  //        if (payload.field === "clientname") {
  //           state.headerdata["rem"] = state.headerdata["clientname"]
  //        }
  //     } else {
  //       console.log("rem status", state.headercols.col4.rem.required)
  //       state.headercols.col4.rem.required = false
  //     }
   public function sbcscript($config){
    return '
      if(payload.field !== undefined){
        if(payload.field === "fullcomm"){
        
          state.headercols.col3.commamt.required = false
          console.log(state.headercols.col3.commamt.required)
          if(state.headerdata["fullcomm"] === "Fixed Comm")
          {
            console.log("setting field property test, if yes")
            console.log(state.headercols.col3.commamt.required)
            
            state.headercols.col3.commamt.required = true
            console.log(state.headercols.col3.commamt.required)

            state.headerdata["commamt"] = ""

          }else{
            console.log("setting field property test, if no")
            console.log(state.headercols.col3.commamt.required)
            
            state.headercols.col3.commamt.required = false
            console.log(state.headercols.col3.commamt.required)
          }
        }
      }
    ';
   }


  public function createnewtransaction($docno, $params)
  {
    $agent = "";
    $agentname = "";
    $agentid = 0;
    if ($params['adminid']!=0) {
      $salesperson_qry = "
      select 
        ifnull(ag.client, '') as agent, 
        ifnull(ag.clientname, '') as agentname, 
        ifnull(ag.clientid, 0) as agentid
      from client as ag
      where ag.clientid = ?";
      $salesperson_res = $this->coreFunctions->opentable($salesperson_qry, [$params['adminid']]);
      if (!empty($salesperson_res)) {
        $agent = $salesperson_res[0]->agent;
        $agentname = $salesperson_res[0]->agentname;
        $agentid = $salesperson_res[0]->agentid;
      }
    }

    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['checkdate'] = $this->othersClass->getCurrentDate();
    $data[0]['rem'] = '';
    $data[0]['project'] = '';
    $data[0]['projectname'] = '';
    $data[0]['projectid'] = '';
    $data[0]['agent'] = $agent;
    $data[0]['agentid'] = $agentid;
    $data[0]['agentname'] = $agentname;
    $data[0]['client'] = '';
    $data[0]['clientid'] = 0;
    $data[0]['potrno'] = 0;
    
    $data[0]['pcfno'] = '';
    $data[0]['dtcno'] = '';
    $data[0]['poref'] = '';
    $data[0]['terms'] = '';
    $data[0]['termsdetails'] = '';

    $data[0]['aftistock'] = '0';
    $data[0]['fullcomm'] = '';

    $date = date("Y-m-d",strtotime($this->othersClass->getCurrentDate()));
    $datacur = $this->coreFunctions->opentable("select oandaphpusd,oandausdphp from pcfcur where left(dateid,10)='".$date."' order by dateid desc limit 1");
    $osphpusd = $this->coreFunctions->datareader("select ifnull(osphpusd,0) as value from pcfcur where osphpusd <> 0 order by dateid desc limit 1",[],'','',true);

    if(empty($datacur)){
      $data[0]['oandaphpusd']=0;
      $data[0]['oandausdphp']=0;
    }else{
      $data[0]['oandaphpusd']=$datacur[0]->oandaphpusd;
      $data[0]['oandausdphp']=$datacur[0]->oandausdphp;
    }


    if(empty($osphpusd)){
      $data[0]['osphpusd'] = 0;
    }else{
      $data[0]['osphpusd'] = $osphpusd;
    }    
    return $data;
  }

  public function loadheaddata($config)
  {
    ini_set('memory_limit', '-1');

    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
    $tablenum = $this->tablenum;
    $companyid = $config['params']['companyid'];
    $username = $config['params']['user'];
    $user = $config['params']['adminid'];
    $isadmin = $this->othersClass->checkAccess($config['params']['user'], 5389);
    $isphead = 0;

    if($isadmin == 0){
      $isphead = $this->coreFunctions->getfieldvalue("projectmasterfile","agentid","agentid = ?",[$user],'',true);
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

    if ($this->companysetup->getistodo($config['params'])) {
      $this->othersClass->checkseendate($config, $tablenum);
    }

    $addparams ="";
    if ($companyid == 10 || $companyid == 12) { //afti
      if ($isadmin == 0 && $isphead == 0){
        $addparams .= " and head.createby='".$username."'";
      }else{
        if($isphead == 1){
           $addparams .= " and pm.agentid = ".$user;
        }
      } 
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
         left(head.dateid,10) as dateid,
         head.clientname,
         date_format(head.createdate,'%Y-%m-%d') as createdate,
         head.rem,
         agent.client as agent,
         agent.clientname as agentname,'' as dagentname,
         head.projectid,head.project as projectname,head.pcfno,head.dtcno,head.aftistock,head.fullcomm,
         head.oandausdphp,head.oandaphpusd,head.osphpusd,head.poref,head.potrno,head.commamt,head.remarks,head.terms,head.termsdetails,head.checkdate,head.islost,ifnull(qt.docno,'') as qtno
         ";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.clientid = client.clientid
        left join client as agent on agent.clientid = head.agentid
        left join projectmasterfile as p on p.code = head.project
        left join transnum as qt on qt.trno = head.potrno
        where head.trno = ? and num.center = ? ".$addparams ."
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.clientid = client.clientid
        left join client as agent on agent.clientid = head.agentid
        left join projectmasterfile as p on p.code = head.project
        left join transnum as qt on qt.trno = head.potrno
        where head.trno = ? and num.center=? " . $addparams;


    $head = $this->coreFunctions->opentable($qry, [$trno, $center, $trno, $center]);
    if (!empty($head)) {
      foreach ($this->blnfields as $key => $value) {
        if ($head[0]->$value) {
          $head[0]->$value = "1";
        } else
          $head[0]->$value = "0";
      }
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
      if($head[0]->islost){
        $hideobj['lblrem'] = false;
      }else {
        $hideobj['lblrem'] = true;
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
    $head['project'] = $head['projectname'];
    $blnrecompute = false;
    
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

    if ($isupdate) {
      $origcheckdate = $this->coreFunctions->datareader("select ifnull(checkdate,'') as value from ".$this->head." where trno = ".$head['trno']);
      if( date("Y-m-d",strtotime($origcheckdate)) <> date("Y-m-d",strtotime($data['checkdate']))){
        $date = date("Y-m-d",strtotime($head['checkdate']));
        $datacur = $this->coreFunctions->opentable("select oandaphpusd,oandausdphp from pcfcur where left(dateid,10)='".$date."' order by dateid desc limit 1");
        $osphpusd = $this->coreFunctions->datareader("select ifnull(osphpusd,0.000000) as value from pcfcur where osphpusd <> 0 order by dateid desc limit 1");
        $origosphpusd = $this->coreFunctions->datareader("select ifnull(osphpusd,0.000000) as value from ".$this->head." where trno = ".$head['trno']);

        if(empty($datacur)){
          $data['oandaphpusd']=0;
          $data['oandausdphp']=0;
        }else{
          $data['oandaphpusd']=$datacur[0]->oandaphpusd;
          $data['oandausdphp']=$datacur[0]->oandausdphp;
        }

        if($origosphpusd <> $osphpusd){
          $blnrecompute = true;
        }

        if(empty($osphpusd)){
          $data['osphpusd'] = 0;
          $head['osphpusd'] = 0;        
        }else{
          $data['osphpusd'] = $osphpusd;
          $head['osphpusd'] = $osphpusd;        
        }
       
      }
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
      $exist = $this->coreFunctions->datareader("select trno as value from ".$this->stock." where trno = ?",[$head['trno']],'',true);
      if(!$exist){
        $this->loadinv($config);
      }
      
      if($blnrecompute){
        $this->recomputehiokitp($config);       
      }
      
       //check duty
       $exist = $this->coreFunctions->getfieldvalue("pxchecking","line","trno=? and expenseid =94",[$head['trno']],'',true);      
       $line = $exist;
       $stock = $this->coreFunctions->datareader("select sum(totaltp) as value from pxstock where trno= ?",[$head['trno']]);
       if($stock != 0){
         $os = $this->coreFunctions->getfieldvalue("pxhead","oandausdphp","trno=?",[$head['trno']]);
         $stock = $stock * $os;
       
         $i['budget'] = round($stock * .02,2);
         $i['actual'] = round($stock * .02,2);
         $i['rem'] = '';

         if($exist !=0){
           $this->coreFunctions->sbcupdate('pxchecking', $i,["trno"=>$head['trno'], "line" => $line]);
         }else{
           $i['trno'] = $head['trno'];
           $line =$this->coreFunctions->getfieldvalue("pxchecking","max(line)","trno=?",[$head['trno']],'',true);
           $i['line'] = $line+1;
           $i['expenseid'] = 94;
           $this->coreFunctions->sbcinsert('pxchecking', $i);
         }
       }
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head, $data);      
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['clientname']);
      $this->loadinv($config);
    }
    
  } // end function

  public function recomputehiokitp($config,$osphpusd = 0)
  {
    $trno = $config['params']['trno'];
    $data = $this->openstock($trno, $config);
    $data2 = json_decode(json_encode($data), true);
    if($osphpusd == 0){
      $osphpusd = $this->coreFunctions->getfieldvalue($this->head,"osphpusd","trno=?",[$trno]);
    }
    
    $exec = true;
    foreach ($data2 as $key => $value) {
      $damt = $this->othersClass->sanitizekeyfield('amt', $data2[$key][$this->damt]);
      $dqty = round($this->othersClass->sanitizekeyfield('qty', $data2[$key][$this->dqty]), $this->companysetup->getdecimal('qty', $config['params']));
      if($data2[$key]['itemgrp'] == 'HIOKI'){
        $tp = ($damt*$osphpusd)*0.83;

        $exec = $this->coreFunctions->execqry("update ".$this->stock." set tp = " . $tp . ",totaltp = ". round($tp*$dqty,2)." where trno = " . $trno . " and line=" . $data[$key]->line, "update");
      }    
      
    }

   
    return $exec;
  }
  public function deletetrans($config)
  {
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];
    $table = $config['docmodule']->tablenum;
    $docno = $this->coreFunctions->datareader("select docno as value from " . $table . ' where trno=?', [$trno]);
    $qry = "select trno as value from " . $this->tablenum . " where doc=? and trno<? order by trno desc limit 1 ";
    $trno2 = $this->coreFunctions->datareader($qry, [$doc, $trno]);
    $poref = $this->coreFunctions->getfieldvalue($this->head,"potrno","trno=?",[$trno]);

    $this->coreFunctions->execqry('delete from ' . $this->stock . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry("delete from pxchecking where trno=?", 'delete', [$trno]);

    
    $isposted = $this->othersClass->isposted2($poref,'transnum');
    $qstbl = 'headinfotrans';
    if($isposted){
      $qstbl = 'hheadinfotrans';
    }
    
    $this->coreFunctions->execqry("update ".$qstbl." set dtctrno = 0 where trno=?", 'update', [$poref]);
    $this->othersClass->deleteattachments($config);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  //before posting, checking required pcf admin fields
  private function checkRequiredFields($config,$fields,$trno)
  {
    $status = false;
    $checkfield = $this->coreFunctions->opentable("select $fields from " . $this->head . " where trno=?", [$trno]);

    if(
        ($checkfield[0]->dateid == '') ||
        ($checkfield[0]->pcfno == '') ||
        ($checkfield[0]->dtcno == '') ||
        ($checkfield[0]->fullcomm  == '') 
        // ($checkfield[0]->oandaphpusd == 0) ||
        // ($checkfield[0]->oandausdphp == 0) ||
        // ($checkfield[0]->osphpusd == 0)
      )
    {
      $status = true;
    }


    return $status;
    
  }

  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    if ($this->othersClass->isposted($config)) {
      return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
    }

    $fieldsToCheck = "dateid, pcfno, dtcno, fullcomm";

    if($this->checkRequiredFields($config,$fieldsToCheck,$trno)){
      return ['status' => false, 'msg' => 'Posting failed. All Fields required before Posting.'];
    }

    // $fullcomm = $this->coreFunctions->datareader('select fullcomm as value from ' . $this->head . ' where trno=?', [$trno]);
    // if($fullcomm =='Fixed Comm'){
    //   $commamt = $this->coreFunctions->datareader('select commamt as value from ' . $this->head . ' where trno=?', [$trno],'',true);
    //   if($commamt == 0){
    //     return ['status' => false, 'msg' => 'Posting failed. Please enter Commission Amount.'];
    //   }
    // }

    
    $date = $this->coreFunctions->datareader('select checkdate as value from ' . $this->head . ' where trno=?', [$trno]);
    $date = date("Y-m-d",strtotime($date));
    $datacur = $this->coreFunctions->opentable("select oandaphpusd,oandausdphp from pcfcur where left(dateid,10)='".$date."' order by dateid desc limit 1");
    $osphpusd = $this->coreFunctions->datareader("select ifnull(osphpusd,0) as value from pcfcur where osphpusd <> 0 order by dateid desc limit 1",[],'','',true);
    $oandaphpusd = 0;
    $oandausdphp =0;

    if(!empty($datacur)){
      $oandaphpusd=$datacur[0]->oandaphpusd;
      $oandausdphp=$datacur[0]->oandausdphp;
    }else{
      return ['status' => false, 'msg' => 'Posting failed. Please setup conversion rates for this checking date.'];
    }

    if(empty($osphpusd)){
      return ['status' => false, 'msg' => 'Posting failed. Please setup conversion rates for OS PHP.'];
    }else{
      // /$this->recomputehiokitp($config,$osphpusd);
      //update duty
      $stock = $this->coreFunctions->datareader("select sum(totaltp) as value from pxstock where trno= ?",[$trno]);
      if($stock!=0){
        $os = $this->coreFunctions->getfieldvalue("pxhead","oandausdphp","trno=?",[$trno]);
        $stock = round(($stock * $os)*.02,2);
        $exist = $this->coreFunctions->getfieldvalue("pxchecking","line","trno=? and expenseid =94",[$trno],'',true);
        $this->coreFunctions->execqry("update pxchecking set budget = ".$stock.",actual =".$stock." where trno=? and line =?","update",[$trno,$exist]);      
      }
    }
    
    //for glhead
    $qry = "insert into " . $this->hhead . "(trno,doc,docno,clientid,clientname,project,projectid,dateid,
      rem,dtcno,pcfno,poref,createdate,createby,editby,editdate,lockdate,lockuser,aftistock,fullcomm,agentid,oandaphpusd,
      oandausdphp, osphpusd,percentage,viewby,viewdate,potrno,commamt,remarks,terms,termsdetails,checkdate)
      SELECT head.trno,head.doc,head.docno,head.clientid,head.clientname,head.project,head.projectid,'".$date."',
      head.rem,head.dtcno,head.pcfno,head.poref,head.createdate,head.createby,head.editby,head.editdate,head.lockdate,head.lockuser,
      head.aftistock,head.fullcomm,head.agentid,".$oandaphpusd.",".$oandausdphp.", ".$osphpusd.",head.percentage,head.viewby,head.viewdate,
      head.potrno,head.commamt,head.remarks,head.terms,head.termsdetails,head.checkdate
      FROM " . $this->head . " as head left join cntnum on cntnum.trno=head.trno
      where head.trno=? limit 1";
    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($posthead) {
      $qry = "insert into " . $this->hstock . "(trno,line,itemid,rrcost,rrqty,ext,srp,totalsrp,tp,totaltp,
        encodeddate,encodedby,editdate,editby,sortline)
        SELECT trno,line,itemid,rrcost,rrqty,ext,srp,totalsrp,tp,totaltp,
        encodeddate,encodedby,editdate,editby,sortline
        FROM " . $this->stock . " where trno =?";

      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
          $qry = "insert into hpxchecking(trno,line,budget,actual,rem,reftrno,editdate,editby,expenseid)
          SELECT trno,line,budget,actual,rem,reftrno,editdate,editby,expenseid
          FROM pxchecking where trno =?";
          if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
            //update transnum
            $date = $this->othersClass->getCurrentTimeStamp();
            $data = ['postdate' => $date, 'postedby' => $config['params']['user']];
            $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
            $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry("delete from pxchecking where trno=?", "delete", [$trno]);

            //update instruction  
            $poref = $this->coreFunctions->datareader('select potrno as value from ' . $this->hhead . ' where trno=?', [$trno],'',true);
            $data = $this->coreFunctions->opentable('select projectid,pcfno,dtcno from ' . $this->hhead . ' where trno=?', [$trno]);         
            $c=1;
            $dtcno = "";
            if($poref!=0){
              $dtcno = $data[0]->dtcno;
                $ins = "";
                $q = $this->coreFunctions->opentable("select p.actual, e.category as expensename  from hpxchecking as p left join reqcategory as e on e.line = p.expenseid where e.line <> 94 and p.trno = " . $trno);
                if (!empty($q)) {
                  if ($data[0]->projectid != "N/A") {
                    $ins  = "Project ID:" . $data[0]->projectid."\n";
                  }
                  foreach ($q as $x => $y) {
                    if ($data[0]->pcfno != "") {
                      if ($ins == "") {
                        $ins  = $data[0]->pcfno . "/" . $data[0]->dtcno . "\n" .
                          "Approved Expenses:\n" . $c . ". " . $q[$x]->expensename . "- PHP" . number_format($q[$x]->actual, 2);
                      } else {
                        if($c==1){
                          $ins .= "Approved Expenses:";
                        }
                        $ins  = $ins . "\n" . $c
                         . ". " . $q[$x]->expensename . "- PHP" . number_format($q[$x]->actual, 2);
                      }
                    } else {
                      if ($ins == "") {
                        $ins  = $data[0]['dtcno'] . "\n" .
                          "Approved Expenses:\n" . $c . ". " . $q[$x]->expensename . "- PHP" . number_format($q[$x]->actual, 2);
                      } else {
                        if($c==1){
                          $ins .= "Approved Expenses:";
                        }
                        $ins  = $ins . "\n" . $c . ". " . $q[$x]->expensename . "- PHP" . number_format($q[$x]->actual, 2);
                      }
                    }
                    $c += 1;
                  }
                }
          
                if($ins!=''){
                  $tbl= 'headinfotrans';
                  $isposted = $this->othersClass->isposted2($poref,'transnum');
                  if($isposted){
                    $tbl = 'hheadinfotrans';
                  }
                  $exist = $this->coreFunctions->getfieldvalue($tbl,"rem2","trno=?",[$poref]);
                  // if($exist !=""){
                  //   $ins .="\n\n".$exist;
                  // }
                  $this->coreFunctions->sbcupdate($tbl,["rem2" => $ins,"dtctrno" => $trno],["trno"=>$poref]);
                }
              
            }

            $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
            $this->othersClass->sbctransferlog($trno, $config, $this->htablelogs);
            return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];

          }else{
            $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
            return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting Checking'];
          }
       
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

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    $qry = "insert into " . $this->head . "(trno,doc,docno,clientid,clientname,project,projectid,dateid,
      rem,dtcno,pcfno,poref,createdate,createby,editby,editdate,lockdate,lockuser,aftistock,fullcomm,agentid,oandaphpusd,
      oandausdphp, osphpusd,percentage,viewby,viewdate,potrno,commamt,remarks,terms,termsdetails,checkdate)
      SELECT head.trno,head.doc,head.docno,head.clientid,head.clientname,head.project,head.projectid,head.dateid,
      head.rem,head.dtcno,head.pcfno,head.poref,head.createdate,head.createby,head.editby,head.editdate,head.lockdate,head.lockuser,
      head.aftistock,head.fullcomm,head.agentid,head.oandaphpusd,head.oandausdphp, head.osphpusd,head.percentage,head.viewby,head.viewdate,
      head.potrno,head.commamt,head.remarks,head.terms,head.termsdetails,head.checkdate
      FROM " . $this->hhead . " as head left join cntnum on cntnum.trno=head.trno
      where head.trno=? limit 1";
    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($posthead) {
      $qry = "insert into " . $this->stock . "(trno,line,itemid,rrcost,rrqty,ext,srp,totalsrp,tp,totaltp,
        encodeddate,encodedby,editdate,editby,sortline)
        SELECT trno,line,itemid,rrcost,rrqty,ext,srp,totalsrp,tp,totaltp,
        encodeddate,encodedby,editdate,editby,sortline
        FROM " . $this->hstock . " where trno =?";

      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
          $qry = "insert into pxchecking(trno,line,budget,actual,rem,reftrno,editdate,editby,expenseid)
          SELECT trno,line,budget,actual,rem,reftrno,editdate,editby,expenseid
          FROM hpxchecking where trno =?";
          if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
            //update transnum
            $date = $this->othersClass->getCurrentTimeStamp();
            $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null,statid=39 where trno=?", 'update', [$trno]);
            $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry("delete from hpxchecking where trno=?", "delete", [$trno]);
            $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
            return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];

          }else{
            $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", "delete", [$trno]);
            return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Unposting Checking'];
          }
       
      } else {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Unposting stock'];
      }
    } else {
      return ['status' => false, 'msg' => 'Error on Unposting Head'];
    }
  } //end function

  private function getstockselect($config)
  {
    $companyid = $config['params']['companyid'];
    $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);

    $sqlselect = "select item.brand as brand,
    ifnull(mm.model_name,'') as model,
    item.itemid,
    stock.trno,
    stock.line,
    stock.sortline,
    item.barcode,
    if(ifnull(sit.itemdesc,'')='',item.itemname,sit.itemdesc) as itemname,
    format(stock.srp," . $this->companysetup->getdecimal('price', $config['params']) . ") as srp,
    format(stock.totalsrp, " . $this->companysetup->getdecimal('price', $config['params']) . ") as totalsrp,
    format(stock.tp," . $this->companysetup->getdecimal('price', $config['params']) . ") as tp,format(stock.totaltp," . $this->companysetup->getdecimal('price', $config['params']) . ") as totaltp,
    FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . ") as " . $this->damt . ",
    FORMAT(stock." . $this->dqty . "," . $qty_dec . ")  as " . $this->dqty . ",
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,
    left(stock.encodeddate,10) as encodeddate,    
    item.brand,   
    '' as bgcolor,
    '' as errcolor, item.partno,p.name as itemgrp, 
    concat(item.itemname,'\\n',ifnull(brand.brand_desc,''),'\\r\\n',ifnull(mm.model_name,''),'\\r\\n',ifnull(i.itemdescription,'')) as itemdescription";
    return $sqlselect;
  }

  public function openstock($trno, $config)
  {
    ini_set('memory_limit', '-1');
    $companyid = $config['params']['companyid'];
    $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);
    if ($companyid == 10 || $companyid == 12) { //afti
      $qty_dec = 0;
    }

    $sqlselect = $this->getstockselect($config);

    $leftjoin = '';
    $rcgrpby = '';

    $qry = $sqlselect . "
    FROM $this->stock as stock
    left join item on item.itemid=stock.itemid
    left join model_masterfile as mm on mm.model_id = item.model
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid
    left join stockinfo as sit on sit.trno = stock.trno and sit.line=stock.line
    left join projectmasterfile as p on p.line = item.projectid
    where stock.trno =? group by item.brand,mm.model_name,item.itemid,stock.trno,
    stock.line,stock.sortline,item.barcode,item.itemname,stock.srp,stock.totalsrp,stock.tp,stock.totaltp,
    FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . ") ,
    stock." . $this->dqty." ,
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") ,stock.encodeddate,
    item.partno,brand.brand_desc,i.itemdescription,sit.itemdesc,p.name
    UNION ALL
    " . $sqlselect . "
    FROM $this->hstock as stock
    left join item on item.itemid=stock.itemid
    left join model_masterfile as mm on mm.model_id = item.model
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid
    left join hstockinfo as sit on sit.trno = stock.trno and sit.line=stock.line
    left join projectmasterfile as p on p.line = item.projectid
    where stock.trno =? group by item.brand,mm.model_name,item.itemid,stock.trno,
    stock.line,stock.sortline,item.barcode,item.itemname,stock.srp,stock.totalsrp,stock.tp,stock.totaltp,
    FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . ") ,
    stock." . $this->dqty."  ,
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") ,stock.encodeddate,
    item.partno,brand.brand_desc,i.itemdescription,sit.itemdesc,p.name
    order by sortline,line";
    
    $stock = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $stock;
  } //end function

  public function openstockline($config)
  {
    $companyid = $config['params']['companyid'];
    $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);

    if ($companyid == 10 || $companyid == 12) { //afti
      $qty_dec = 0;
    }

    $sqlselect = $this->getstockselect($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];

    $leftjoin = '';
    $rcgrpby = '';
    

    $qry = $sqlselect . "
    FROM $this->stock as stock
    left join item on item.itemid=stock.itemid
    left join model_masterfile as mm on mm.model_id = item.model
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid
    left join stockinfo as sit on sit.trno = stock.trno and sit.line=stock.line
    left join projectmasterfile as p on p.line = item.projectid
    where stock.trno = ? and stock.line = ? group by item.brand,mm.model_name,item.itemid,stock.trno,
    stock.line,stock.sortline,item.barcode,item.itemname,stock.srp,stock.totalsrp,stock.tp,stock.totaltp,
    FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . ") ,
    stock." . $this->dqty." ,
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") ,stock.encodeddate,
    item.partno,brand.brand_desc,i.itemdescription,sit.itemdesc,p.name";
    $this->coreFunctions->LogConsole('open stock line');
    $this->coreFunctions->LogConsole($qry);
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
     
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }


  public function stockstatusposted($config)
  {
    switch ($config['params']['action']) {
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

  public function getqssummaryqry($config)
  {
    return "
          select head.docno, head.client, head.clientname, head.yourref, client.clientid,
          item.itemid,stock.trno, stock.line, item.barcode,stock.uom, stock.iss,stock.amt as rrcost,
          round(stock.iss/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end,
          " . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,p.name as itemgrp
          FROM hqshead as head left join hqsstock as stock on stock.trno=head.trno left join transnum on transnum.trno=head.trno 
          left join item on item.itemid=stock.itemid left join uom on uom.itemid=item.itemid and
          uom.uom=stock.uom left join projectmasterfile as p on p.line = item.projectid 
          left join client on client.client = head.client left join client as agent on agent.client = head.agent
          where stock.trno = ? 
          union all
          select head.docno, head.client, head.clientname, head.yourref, client.clientid,
          item.itemid,stock.trno, stock.line, item.barcode,stock.uom, stock.iss,stock.amt as rrcost,
          round(stock.iss/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end,
          " . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,p.name as itemgrp
          FROM qshead as head left join qsstock as stock on stock.trno=head.trno left join transnum on transnum.trno=head.trno 
          left join item on item.itemid=stock.itemid left join uom on uom.itemid=item.itemid and
          uom.uom=stock.uom left join projectmasterfile as p on p.line = item.projectid 
          left join client on client.client = head.client left join client as agent on agent.client = head.agent
          where stock.trno = ? 
          union all
          select head.docno, head.client, head.clientname, head.yourref, client.clientid,
          item.itemid,stock.trno, stock.line, item.barcode,stock.uom, stock.iss,stock.amt as rrcost,
          round(stock.iss/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end,
          " . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,p.name as itemgrp
          FROM hqshead as head left join hqtstock as stock on stock.trno=head.trno left join transnum on transnum.trno=head.trno 
          left join item on item.itemid=stock.itemid left join uom on uom.itemid=item.itemid and
          uom.uom=stock.uom left join projectmasterfile as p on p.line = item.projectid 
          left join client on client.client = head.client left join client as agent on agent.client = head.agent
          where stock.trno = ?
          union all
          select head.docno, head.client, head.clientname, head.yourref, client.clientid,
          item.itemid,stock.trno, stock.line, item.barcode,stock.uom, stock.iss,stock.amt as rrcost,
          round(stock.iss/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end,
          " . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,p.name as itemgrp
          FROM qshead as head left join qtstock as stock on stock.trno=head.trno left join transnum on transnum.trno=head.trno 
          left join item on item.itemid=stock.itemid left join uom on uom.itemid=item.itemid and
          uom.uom=stock.uom left join projectmasterfile as p on p.line = item.projectid 
          left join client on client.client = head.client left join client as agent on agent.client = head.agent
          where stock.trno = ?  ";
  }

  public function loadinv($config)
  {
    $trno = $config['params']['trno'];
    $potrno = $this->coreFunctions->getfieldvalue($this->head,"potrno","trno=?",[$trno]);
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'Load Inventory');

    $data = $this->coreFunctions->opentable($this->getqssummaryqry($config),[$potrno,$potrno,$potrno,$potrno]);

    $itemcount = 0;
    if (!empty($data)) {
      foreach ($data as $key2 => $value) {
        $config['params']['data']['uom'] = $data[$key2]->uom;
        $config['params']['data']['itemid'] = $data[$key2]->itemid;
        $config['params']['trno'] = $trno;
        $config['params']['data']['qty'] = $data[$key2]->rrqty;
        $config['params']['data']['srp'] = $data[$key2]->rrcost;
        $config['params']['barcode'] =  $data[$key2]->barcode;
        $config['params']['data']['amt'] = 0;
        // if($data[$key2]->itemgrp == 'HIOKI'){
        //   $config['params']['data']['amt'] = $data[$key2]->rrcost;
        // }else{
        //   $config['params']['data']['amt'] = 0;
        // }
        
        $return = $this->additem('insert', $config);
        if ($return['status']) {
          $tbl = 'headinfotrans';
          $isposted = $this->othersClass->isposted2($data[$key2]->trno, 'transnum');
          if ($isposted) {
            $tbl = 'hheadinfotrans';
          }
          $this->coreFunctions->sbcupdate($tbl, ['dtctrno' => $trno], ['trno' => $data[$key2]->trno]);
        }else{
          return ['status'=>false,'msg' => 'Failed.'];
        }
      }
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
  public function additem($action, $config, $setlog = false)
  {
    $companyid = $config['params']['companyid'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $itemid = $config['params']['data']['itemid'];
    $trno = $config['params']['trno'];
    $osphpusd = $this->coreFunctions->getfieldvalue($this->head,"osphpusd","trno=?",[$trno]);

    $rrqty = 0;
    $amt = 0;
    $ext =0;

    $srp=0;
    $totalsrp=0;
    $tp = 0;
    $totaltp =0;

    if(isset($config['params']['data']['srp'])){
      $srp = $config['params']['data']['srp'];
    }

    
    $line = 0;
    $qry = "select item.barcode,item.itemname,item.amt,item.famt,p.name as itemgrp from item left join projectmasterfile as p on p.line = item.projectid where item.itemid=?";
    $item = $this->coreFunctions->opentable($qry, [$itemid]);
    if ($action == 'insert') {
      $qry = "select line as value from " . $this->stock . " where trno=? order by line desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$trno]);
      if ($line == '') {
        $line = 0;
      }
      $line = $line + 1;
      $config['params']['line'] = $line;
      $amt = $config['params']['data']['amt'];
      $rrqty = $config['params']['data']['qty'];      
      
      $factor = 1;
      if($amt != 0){
        if($item[0]->itemgrp == 'HIOKI'){
          $tp = ($amt*$osphpusd)*0.83;
        }else{
          $tp = $item[0]->famt;
        }    
      }
      
          
      // if (!empty($item)) {
      //   if($amt ==0){
      //     $amt = $amt;
      //   }
        
      //   if($item[0]->itemgrp == 'HIOKI'){
      //     $tp = ($amt*$osphpusd)*0.83;
      //   }else{
      //     $tp = $item[0]->famt;
      //   }        
      // }
    } elseif ($action == 'update') {
      $config['params']['line'] = $config['params']['data']['line'];
      $line = $config['params']['data']['line'];
      $amt = $config['params']['data'][$this->damt];
      $rrqty = $config['params']['data'][$this->dqty];    
      $config['params']['line'] = $line;      

      if(isset($config['params']['data']['srp'])){
        $srp = $config['params']['data']['srp'];
      }
  
      if(isset($config['params']['data']['tp'])){
        $tp = $config['params']['data']['tp'];
      }
    }
    
    $amt = $this->othersClass->sanitizekeyfield('amt', $amt);
    $rrqty = $this->othersClass->sanitizekeyfield('qty', $rrqty);  
    $rrqty = round($rrqty, $this->companysetup->getdecimal('qty', $config['params']));
    $computedata = $this->othersClass->computestock($amt, '', $rrqty, 1);
    $srp = $this->othersClass->sanitizekeyfield('amt', $srp);
    $tp = $this->othersClass->sanitizekeyfield('amt', $tp);

    $computedata2 = $this->othersClass->computestock($srp, '', $rrqty, 1);
    $computedata3 = $this->othersClass->computestock($tp, '', $rrqty, 1);

    $data = [
      'trno' => $trno,
      'line' => $line,
      'itemid' => $itemid,
      $this->damt => $amt,
      $this->dqty => $rrqty,
      'ext' => number_format($computedata['ext'], $this->companysetup->getdecimal('currency', $config['params']), '.', ''),
      'srp' => $srp,
      'totalsrp' => number_format($computedata2['ext'], $this->companysetup->getdecimal('currency', $config['params']), '.', ''),
      'tp' => $tp,
      'totaltp' => number_format($computedata3['ext'], $this->companysetup->getdecimal('currency', $config['params']), '.', ''),      
    ];

   // $totaltp = $computedata3['ext'];

    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }

    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $data['editdate'] = $current_timestamp;
    $data['editby'] = $config['params']['user'];

    if ($action == 'insert') {
      $data['encodeddate'] = $current_timestamp;
      $data['encodedby'] = $config['params']['user'];
      $data['sortline'] =  $data['line'];

      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' Barcode:' . $item[0]->barcode . ' Amt:' . $amt . ' Ext:' . $computedata['ext'], $setlog ? $this->tablelogs : '');
        $row = $this->openstockline($config);
        $msg = 'Item was successfully added.';
        
        return ['row' => $row, 'status' => true, 'msg' => $msg];
      } else {
        return ['status' => false, 'msg' => 'Add item Failed'];
      }
    } elseif ($action == 'update') {
      $return = true;
      $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);

       //update duty
       $totaltp = $this->coreFunctions->datareader("select sum(totaltp) as value from ".$this->stock." where trno= ?",[$trno]);
      if($totaltp !=0){
        $i = [];
        $isduty =  $this->coreFunctions->getfieldvalue("pxchecking","line","trno=? and expenseid =94",[$trno],'',true); 
        $os = $this->coreFunctions->getfieldvalue("pxhead","oandausdphp","trno=?",[$trno]);
        $stock = $totaltp * $os;
        if($isduty!=0){                          
              $i['budget'] = round($stock * .02,2);
              $i['actual'] = round($stock * .02,2);      
              $this->coreFunctions->sbcupdate("pxchecking", $i,["trno"=>$trno, "line" => $isduty]);              
        }else{
          $i['trno'] = $trno;
          $i['line'] = 1;
          $i['expenseid'] = 94;
          $i['budget'] = round($stock * .02,2);
          $i['actual'] = round($stock * .02,2);
          $i['rem'] = '';

          $this->coreFunctions->sbcinsert("pxchecking", $i);
        }
      }
    
      return $return;
    }
  } // end function

  public function deleteallitem($config)
  {
    $isallow = true;
    $trno = $config['params']['trno'];
    $poref = $this->coreFunctions->getfieldvalue($this->head,"potrno","trno=?",[$trno]);

    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);

    $isposted = $this->othersClass->isposted2($poref,'transnum');

    $qstbl = 'headinfotrans';
    if($isposted){
      $qstbl = 'hheadinfotrans';
    }
    
    $this->coreFunctions->execqry("update ".$qstbl." set dtctrno = 0 where trno=?", 'update', [$poref]);

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
    $data = json_decode(json_encode($data), true);
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' Barcode:' . $data[0]['barcode'] . ' Qty:' . $data[0]['rrqty'] . ' Amt:' . $data[0]['rrcost'] .  ' Ext:' . $data[0]['ext']);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
  } // end function

  public function getlatestprice($config)
  {
    $barcode = $config['params']['barcode'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
    $data = [];

    $filter = '';

    $qry = "select docno,left(dateid,10) as dateid,round(amt,2) as amt,'' as disc,uom from(select head.docno,head.dateid,
          (stock.rrcost*if(head.forex=0,1,head.forex)) as amt,stock.uom,stock.disc
          from lahead as head
          left join lastock as stock on stock.trno = head.trno
          left join cntnum on cntnum.trno=head.trno
          left join item on item.itemid = stock.itemid
          left join client as wh on wh.clientid=stock.whid
          where head.doc in ('RR','CM','IS','AJ','TS') and cntnum.center = ? " . $filter . "
          and item.barcode = ?
          and stock.rrcost <> 0 and cntnum.trno <>?
          UNION ALL
          select head.docno,head.dateid,(stock.rrcost*if(head.forex=0,1,head.forex)) as amt,
          stock.uom,stock.disc from glhead as head
          left join glstock as stock on stock.trno = head.trno
          left join item on item.itemid = stock.itemid
          left join client on client.clientid = head.clientid
          left join cntnum on cntnum.trno=head.trno
          left join client as wh on wh.clientid=stock.whid
          where head.doc in ('RR','CM','IS','AJ','TS') and cntnum.center = ? " . $filter . "
          and item.barcode = ? 
          and stock." . $this->damt . " <> 0 and cntnum.trno <>?
          order by dateid desc limit 5) as tbl order by dateid desc limit 1";
      $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $trno, $center, $barcode, $trno]);


    if (empty($data)) { // if walang data from filter ng rrcost latest transaction, requery sa cost na field 
      $qry = "select docno,left(dateid,10) as dateid,round(amt,2) as amt,'' as disc,uom from(select head.docno,head.dateid,
          stock.cost as amt,item.uom,stock.disc
          from lahead as head
          left join lastock as stock on stock.trno = head.trno
          left join cntnum on cntnum.trno=head.trno
          left join item on item.itemid = stock.itemid
          left join client as wh on wh.clientid=stock.whid
          where head.doc in ('RR','CM','IS','AJ','TS') and cntnum.center = ?
          and item.barcode = ? and cntnum.trno <>? and stock.cost<>0 " . $filter . "
          UNION ALL
          select head.docno,head.dateid,stock.cost as amt,
          item.uom,stock.disc from glhead as head
          left join glstock as stock on stock.trno = head.trno
          left join item on item.itemid = stock.itemid
          left join client on client.clientid = head.clientid
          left join cntnum on cntnum.trno=head.trno
          left join client as wh on wh.clientid=stock.whid
          where head.doc in ('RR','CM','IS','AJ','TS') and cntnum.center = ?
          and item.barcode = ? and  cntnum.trno <>?  and stock.cost<>0 " . $filter . "
          order by dateid desc limit 5) as tbl order by dateid desc limit 1";
      $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $trno, $center, $barcode, $trno]);
    }

    if (!empty($data)) {
      if ($this->companysetup->getisuomamt($config['params'])) {
        $data[0]->docno = 'UOM';
        $data[0]->amt = $this->coreFunctions->datareader("select ifnull(uom.amt,0) as value from item left join uom on uom.itemid=item.itemid and uom.uom=item.uom where item.barcode=?", [$barcode]);
      }
    }

    if (!empty($data)) {
      return ['status' => true, 'msg' => 'Found the latest purchase price...', 'data' => $data];
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
    $this->logger->sbcviewreportlog($config);
    // var_dump($companyid);
    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);

    // var_dump($data);

    $print = $config['params']['dataparams']['print'];

    // if ($companyid == 36 && $print == 'excel') { //rozlab
    //   $format = $config['params']['dataparams']['reporttype'];
    //   if ($format == '0') {
    //     $str = app($this->companysetup->getreportpath($config['params']))->reportplottingexcelPC($config, $data);
    //   } else {
    //     $str = app($this->companysetup->getreportpath($config['params']))->reportplottingexcelIR($config, $data);
    //   }
    // } else {
      $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
    // }

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
