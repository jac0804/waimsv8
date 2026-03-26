<?php

namespace App\Http\Classes\modules\e4c3fe3674108174825a187099e7349f6;

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
use App\Http\Classes\headClass;
use App\Http\Classes\builder\helpClass;
use App\Http\Classes\sbcscript\sbcscript;
use Exception;


class ch
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'CONSIGN INVOICE';
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
  public $stock = 'sistock';
  public $hstock = 'hsistock';
  public $detail = 'ladetail';
  public $hdetail = 'gldetail';

  public $infohead = 'cntnuminfo';
  public $hinfohead = 'hcntnuminfo';
  public $infostock = 'stockinfo';
  public $hinfostock = 'hstockinfo';

  public $tablelogs = 'table_log';
  public $htablelogs = 'htable_log';
  public $tablelogs_del = 'del_table_log';
  public $dqty = 'isqty';
  public $hqty = 'iss';
  public $damt = 'isamt';
  public $hamt = 'amt';
  public $defaultContra = 'AR1';
  private $stockselect;
  private $fields = ['trno', 'docno', 'dateid', 'due', 'client', 'clientname', 'yourref', 'ourref', 'rem', 'terms', 'forex', 'cur', 'wh', 'address', 'contra', 'tax', 'vattype', 'agent', 'amount'];
  private $except = ['trno', 'dateid', 'due'];
  private $acctg = [];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  private $reporter;
  private $helpClass;
  private $headClass;
  public $sbcscript;


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
    $this->sqlquery = new sqlquery;
    $this->logger = new Logger;
    $this->reporter = new SBCPDF;
    $this->helpClass = new helpClass;
    $this->headClass = new headClass;
    $this->sbcscript = new sbcscript;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 5517,
      'edit' => 5518,
      'new' => 5519,
      'save' => 5520,
      'delete' => 5521,
      'print' => 5522,
      'lock' => 5523,
      'unlock' => 5524,
      'post' => 5525,
      'unpost' => 5526,
      'changeamt' => 5527,
      'additem' => 5529,
      'edititem' => 5530,
      'deleteitem' => 5531,

    );
    return $attrib;
  }


  public function createdoclisting($config)
  {
    $userid = $config['params']['adminid'];
    $dept = '';
    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'yourref', 'ourref', 'total', 'rem', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];

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
    $cols[$total]['label'] = 'Total Amount';
    $cols[$total]['align'] = 'text-left';
    $cols[$liststatus]['name'] = 'statuscolor';

    $cols = $this->tabClass->delcollisting($cols);
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
    $limit = '';
    $lstat = "'DRAFT'";
    $gstat = "'POSTED'";
    $lstatcolor = "'blue'";
    $gstatcolor = "'grey'";

    $rem = '';
    $addparams = '';

    switch ($itemfilter) {
      case 'draft':
        $condition = ' and head.lockdate is null and num.postdate is null ';
        break;
      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
      case 'locked':
        $condition = ' and head.lockdate is not null and num.postdate is null ';
        break;
    }

    $dateid = "left(head.dateid,10) as dateid";
    $orderby = "order by dateid desc, docno desc";

    if ($searchfilter == "") $limit = 'limit 150';
    $lstat = "case ifnull(head.lockdate,'') when '' then 'DRAFT' else 'LOCKED' end";
    $lstatcolor = "case ifnull(head.lockdate,'') when '' then 'red' else 'green' end";


    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'head.clientname', 'head.yourref', 'head.ourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby', 'head.rem'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }

    $qry = "select head.dateid as date2,head.trno,head.docno,head.clientname,$dateid, $lstat as status, $lstatcolor as statuscolor,head.rem as rem,
    head.createby,head.editby,head.viewby,num.postedby,
    head.yourref, head.ourref,head.amount
    from " . $this->head . " as head left join " . $this->tablenum . " as num on num.trno=head.trno 
    left join trxstatus as stat on stat.line=num.statid
    where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $addparams . " " . $filtersearch . "
    union all
    select head.dateid as date2,head.trno,head.docno,head.clientname,$dateid,$gstat as status,$gstatcolor as statuscolor,head.rem as rem,
    head.createby,head.editby,head.viewby, num.postedby,
    head.yourref, head.ourref ,head.amount
    from " . $this->hhead . " as head left join " . $this->tablenum . " as num on num.trno=head.trno 
    left join trxstatus as stat on stat.line=num.statid
    where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $addparams . " " . $filtersearch . "
    $orderby $limit";
    $data = $this->coreFunctions->opentable($qry, [$doc, $center, $date1, $date2, $doc, $center, $date1, $date2]);
    return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
  }

  public function paramsdatalisting($config)
  {
    $data = $this->coreFunctions->opentable("select '' as docno, '' as selectprefix");

    return ['status' => true, 'data' => $data[0], 'txtfield' => []];
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
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => 'sj', 'title' => 'SJ_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
    }

    return $buttons;
  } // createHeadbutton

  public function createtab2($access, $config)
  {
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
    $viewcost = $this->othersClass->checkAccess($config['params']['user'], 368);
    $viewacctg = $this->othersClass->checkAccess($config['params']['user'], 5528);
  
    if($viewacctg){
      $headgridbtns = ['viewdistribution', 'viewref', 'viewdiagram', 'viewitemstockinfo'];
    }else{
      $headgridbtns = ['viewref', 'viewdiagram', 'viewitemstockinfo'];
    }
    

    $column = ['action', 'itemdescription',  'isqty', 'uom',  'isamt',  'ext',  'wh', 'whname',  'rem', 'itemname', 'barcode'];
    $sortcolumn = ['action', 'itemdescription',  'isqty', 'uom',  'isamt',  'ext',   'wh', 'whname', 'rem', 'itemname', 'barcode'];

    foreach ($column as $key => $value) {
      $$value = $key;
    }

    $computefield = ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt];

    $tab = [
      $this->gridname => [
        'gridcolumns' => $column,
        'sortcolumns' => $sortcolumn,
        'computefield' => $computefield,
        'headgridbtns' => $headgridbtns
      ]
    ];

    $stockbuttons = ['save','delete','showbalance'];
    
    if ($this->companysetup->getiseditsortline($config['params'])) {
      array_push($stockbuttons, 'sortline');
    }

    $obj = $this->tabClass->createtab($tab, $stockbuttons);


    if (!$access['changeamt']) {
      $obj[0]['inventory']['columns'][$isamt]['readonly'] = true;
      $obj[0]['inventory']['columns'][$true]['readonly'] = false;
    }
    $obj[0]['inventory']['columns'][$isamt]['label'] = 'Unit Price';
    $obj[0]['inventory']['columns'][$itemdescription]['type'] = 'coldel';
    $obj[0]['inventory']['columns'][$whname]['type'] = 'coldel';

    $obj[0]['inventory']['columns'][$barcode]['type'] = 'hidden';
    $obj[0]['inventory']['columns'][$barcode]['label'] = '';
    $obj[0]['inventory']['columns'][$ext]['readonly'] = false;
    $obj[0]['inventory']['columns'][$ext]['type'] = 'input';
    $obj[0]['inventory']['columns'][$isamt]['readonly'] = true;
    $obj[0]['inventory']['columns'][$wh]['type'] = 'label';
    $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {

    $tbuttons = ['multiitem', 'quickadd', 'saveitem', 'deleteallitem'];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }

  public function createHeadField($config)
  {
    $inv = $this->companysetup->isinvonly($config['params']);

    $fields = ['docno', 'client', 'clientname','address'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.lookupclass', 'customer');
    data_set($col1, 'client.required', false);
    data_set($col1, 'docno.label', 'Transaction#');
    
    $fields = [['dateid', 'terms'], 'due', 'dacnoname', 'dwhname'];


    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'dacnoname.label', 'AR Account');
    data_set($col2, 'dacnoname.lookupclass', 'AR');

    $fields = [['yourref', 'ourref'], ['cur', 'forex'], 'dvattype', 'dagentname'];

    $col3 = $this->fieldClass->create($fields);

    $fields = ['amount','rem'];

    if ($this->companysetup->getistodo($config['params'])) {
      array_push($fields, 'donetodo');
    }
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
    $data[0]['yourref'] = '';
    $data[0]['address'] = '';
    $data[0]['ourref'] = '';
    $data[0]['rem'] = '';
    $data[0]['terms'] = '';
    $data[0]['forex'] = 1;
    $data[0]['cur'] = $this->companysetup->getdefaultcurrency($params);
    $data[0]['tax'] = 12;
    $data[0]['dagentname'] = '';
    $data[0]['dvattype'] = '';
    $data[0]['dacnoname'] = '';
    $data[0]['agent'] = '';
    $data[0]['agentname'] = '';
    $data[0]['vattype'] = 'VATABLE';
    $data[0]['contra'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', [$this->defaultContra]);
    $data[0]['acnoname'] = $this->coreFunctions->getfieldvalue('coa', 'acnoname', 'acno=?', [$data[0]['contra']]);
    $data[0]['wh'] = $this->companysetup->getwh($params);
    $name = $this->coreFunctions->getfieldvalue('client', 'clientname', 'client=?', [$data[0]['wh']]);
    $data[0]['whname'] = $name;
    $data[0]['dwhname'] = '';
    $data[0]['deldate'] = $this->othersClass->getCurrentDate();
    $data[0]['amount'] = 0;
    return $data;
  }

  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $trno = $config['params']['trno'];
    $center = $config['params']['center'];
    $tablenum = $this->tablenum;
    if ($trno == 0) {
      $trno = $this->othersClass->readprofile('TRNO', $config);
      if ($trno == '') {
        $trno = $this->coreFunctions->datareader("select trno as value 
        from " . $this->tablenum . " 
        where doc=? and center=? 
        order by trno desc limit 1", [$doc, $center]);
      }
      $config['params']['trno'] = $trno;
    } else {
      $this->othersClass->checkprofile('TRNO', $trno, $config);
    }
    $center = $config['params']['center'];

    if ($this->companysetup->getistodo($config['params'])) {
      $this->othersClass->checkseendate($config, $tablenum);
    }

    $head = [];
    $islocked = $this->othersClass->islocked($config);
    $isposted = $this->othersClass->isposted($config);
    $table = $this->head;
    $htable = $this->hhead;
    $info = $this->infohead;
    $hinfo = $this->hinfohead;
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
      ifnull(agent.client,'') as agent,
      ifnull(agent.clientname,'') as agentname,'' as dagentname,
      head.tax,
      head.vattype,
      '' as dvattype,
      warehouse.client as wh,
      warehouse.clientname as whname,
      '' as dwhname,
      left(head.due,10) as due,
      date(head.deldate) as deldate,
      head.amount
    ";

    $qry = $qryselect . " from $table as head
      left join $tablenum as num on num.trno = head.trno
      left join client on head.client = client.client
      left join client as warehouse on warehouse.client = head.wh
      left join client as agent on agent.client = head.agent
      left join coa on coa.acno=head.contra
      where head.trno = ? and num.doc=? and num.center = ? 
      union all " . $qryselect . " from $htable as head
      left join $tablenum as num on num.trno = head.trno
      left join client on head.clientid = client.clientid
      left join client as warehouse on warehouse.clientid = head.whid
      left join client as agent on agent.clientid = head.agentid
      left join coa on coa.acno=head.contra
      where head.trno = ? and num.doc=? and num.center=? 
    ";

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
    $this->coreFunctions->execqry('delete from cntnuminfo where trno=?', 'delete', [$trno]);
    $this->othersClass->deleteattachments($config);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
   
    $checkacct = $this->othersClass->checkcoaacct(['AR1', 'SD1', 'TX2',]);
      if ($checkacct != '') {
        return ['trno' => $trno, 'status' => false, 'msg' => 'Accounts not yet setup:' . $checkacct];
      }

      if (!$this->createdistribution($config)) {
        return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. Problems in creating accounting entries.'];
      } else {
        $return = $this->othersClass->posttranstock($config);
        return $return;
      }

    if ($this->othersClass->postcntnuminfo($config, true)) {
      $this->coreFunctions->execqry('delete from cntnuminfo where trno=?', 'delete', [$trno]);
    }
  } //end function

  public function unposttrans($config)
  {
    $trno = $config['params']['trno'];
    return $this->othersClass->unposttranstock($config);
  } //end function

  private function getstockselect($config)
  {
    $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);
    
    $sqlselect = "select 
    item.itemid,
    stock.trno,
    stock.line,
    stock.sortline,
    item.barcode,
    stock.uom,
    stock." . $this->hamt . ",
    stock.iss as iss,
    FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . ") as isamt,
    stock.isqty  as isqty,
    FORMAT(stock." . $this->dqty . "," . $qty_dec . ")  as qty,
    item.itemname,
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,
    left(stock.encodeddate,10) as encodeddate,
    stock.disc,
    stock.void,
    stock.whid,
    warehouse.client as wh,
    warehouse.clientname as whname,
    stock.rem,
    ifnull(uom.factor,1) as uomfactor,
    '' as bgcolor,
    '' as errcolor,
    case when stock.noprint=0 then 'false' else 'true' end as noprint,
    item.itemname as itemdescription
    ";
    return $sqlselect;
  }

  public function openstock($trno, $config)
  {
    $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);
    $leftjoin = '';
    $hleftjoin = '';
    $stockinfogroup = '';
    $sqlselect = $this->getstockselect($config);

    $qry = $sqlselect . "
    FROM $this->stock as stock
    left join $this->head as head on head.trno = stock.trno
    left join item on item.itemid=stock.itemid
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid
    left join cntnum as num on num.trno=head.trno
    where num.trno =?    
    group by item.itemid,stock.trno,stock.line,stock.sortline,
    item.barcode,item.itemname, stock.uom,stock.isqty,
    stock." . $this->hamt . ",stock." . $this->hqty . ",
    stock." . $this->damt .",
    stock.isqty,stock.ext ,uom.factor,
    stock.encodeddate,stock.disc,stock.void,stock.whid,warehouse.client,
    warehouse.clientname,stock.rem,stock.noprint
    UNION ALL
    " . $sqlselect . "
    FROM $this->hstock as stock
    left join $this->hhead as head on head.trno = stock.trno
    left join item on item.itemid=stock.itemid
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
    left join client as warehouse on warehouse.clientid=stock.whid
    left join cntnum as num on num.trno=head.trno
    where num.trno =?
    group by item.itemid,stock.trno,stock.line,stock.sortline,
    item.barcode,item.itemname, stock.uom,stock.isqty,
    stock." . $this->hamt . ",stock." . $this->hqty . ",
    stock." . $this->damt .",
    stock.isqty,stock.ext ,uom.factor,
    stock.encodeddate,stock.disc,stock.void,stock.whid,warehouse.client,
    warehouse.clientname,stock.rem,stock.noprint order by sortline, line";

    $stock = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $stock;
  } //end function

  public function openstockline($config)
  {
    $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);
    $leftjoin = '';
    $stockinfogroup = '';

    $sqlselect = $this->getstockselect($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = $sqlselect . "
    FROM $this->stock as stock
    left join $this->head as head on head.trno = stock.trno
    left join item on item.itemid=stock.itemid
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid
    left join cntnum as num on num.trno=head.trno
    where stock.trno = ? and stock.line = ? 
    group by item.itemid,stock.trno,stock.line,stock.sortline,
    item.barcode,item.itemname, stock.uom,stock.isqty,
    stock." . $this->hamt . ",stock." . $this->hqty . ",
    stock." . $this->damt .",
    stock.isqty,stock.ext ,uom.factor,
    stock.encodeddate,stock.disc,stock.void,stock.whid,warehouse.client,
    warehouse.clientname,stock.rem,stock.noprint";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $stock;
  } // end function

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'additem':
        $return =  $this->additem('insert', $config);
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
      case 'getitem':
        return $this->othersClass->getmultiitem($config);
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
    $data['width'] = 1650;
    $startx = 100;
    $a = 0;

    $qry = "select so.trno,so.docno,left(so.dateid,10) as dateid,
     CAST(concat('Total SO Amt: ',round(sum(s.ext),2)) as CHAR) as rem
     from hsohead as so
     left join hsostock as s on s.trno = so.trno
     left join glstock as sstock on sstock.refx = s.trno and sstock.linex = s.line
     where sstock.trno = ?
     group by so.trno,so.docno,so.dateid";
    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
    if (!empty($t)) {
      $startx = 550;
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
    CAST(concat('Total SJ Amt: ', round(sum(stock.ext),2), if(head.ms_freight<>0,concat('\rOther Charges: ',round(head.ms_freight,2)),''),'\r\r', 'Balance: ', round(ar.bal, 2)) as CHAR) as rem,
    head.trno
    from glhead as head
    left join glstock as stock on head.trno = stock.trno
    left join arledger as ar on ar.trno = head.trno
    where head.trno=?
    group by head.docno, head.dateid, head.trno, ar.bal, head.ms_freight";
    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
    if (!empty($t)) {
      data_set(
        $nodes,
        'sj',
        [
          'align' => 'left',
          'x' => $startx,
          'y' => 100,
          'w' => 400,
          'h' => 80,
          'type' => $t[0]->docno,
          'label' => $t[0]->rem,
          'color' => 'green',
          'details' => [$t[0]->dateid]
        ]
      );

      foreach ($t as $key => $value) {
        //CR
        $sjtrno = $t[$key]->trno;
        $crqry = "
        select  head.docno, date(head.dateid) as dateid, head.trno,
        CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
        from glhead as head
        left join gldetail as detail on head.trno = detail.trno
        where detail.refx = ? and head.doc = 'CR'
        union all
        select  head.docno, date(head.dateid) as dateid, head.trno,
        CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
        from lahead as head
        left join ladetail as detail on head.trno = detail.trno
        where detail.refx = ? and head.doc = 'CR'";
        $crdata = $this->coreFunctions->opentable($crqry, [$sjtrno, $sjtrno]);
        if (!empty($crdata)) {
          foreach ($crdata as $key2 => $value2) {
            data_set(
              $nodes,
              'cr',
              [
                'align' => 'left',
                'x' => $startx + 400,
                'y' => 100,
                'w' => 250,
                'h' => 80,
                'type' => $crdata[$key2]->docno,
                'label' => $crdata[$key2]->rem,
                'color' => 'red',
                'details' => [$crdata[$key2]->dateid]
              ]
            );
            array_push($links, ['from' => 'sj', 'to' => 'cr']);
            $a = $a + 100;
          }
        }

        //CM
        $cmqry = "
        select head.docno as docno,left(head.dateid,10) as dateid,
        CAST(concat('Total CM Amt: ', round(sum(stock.ext), 2)) as CHAR) as rem
        from glhead as head
        left join glstock as stock on stock.trno=head.trno
        left join item on item.itemid = stock.itemid
        where stock.refx=? and head.doc = 'CM'
        group by head.docno, head.dateid
        union all
        select head.docno as docno,left(head.dateid,10) as dateid,
        CAST(concat('Total CM Amt: ', round(sum(stock.ext), 2)) as CHAR) as rem
        from lahead as head
        left join lastock as stock on stock.trno=head.trno
        left join item on item.itemid=stock.itemid
        where stock.refx=? and head.doc = 'CM'
        group by head.docno, head.dateid";
        $cmdata = $this->coreFunctions->opentable($cmqry, [$sjtrno, $sjtrno]);
        if (!empty($cmdata)) {
          foreach ($cmdata as $key2 => $value2) {
            data_set(
              $nodes,
              $cmdata[$key2]->docno,
              [
                'align' => 'left',
                'x' => $startx + 400,
                'y' => 200,
                'w' => 250,
                'h' => 80,
                'type' => $cmdata[$key2]->docno,
                'label' => $cmdata[$key2]->rem,
                'color' => 'red',
                'details' => [$cmdata[$key2]->dateid]
              ]
            );
            array_push($links, ['from' => 'sj', 'to' => $cmdata[$key2]->docno]);
            $a = $a + 100;
          }
        }
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
      case 'navigation':
        return $this->othersClass->navigatedocno($config);
        break;
      case 'donetodo':
        $tablenum = $this->tablenum;
        return $this->othersClass->donetodo($config, $tablenum);
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

    }

    $data = $this->openstock($config['params']['trno'], $config);
    $data2 = json_decode(json_encode($data), true);
    $status = true;

    foreach ($data2 as $key => $value) {
      if ($data2[$key][$this->dqty] == 0) {
        $data[$key]->errcolor = 'bg-red-2';
        $status = false;
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
  public function additem($action, $config, $setlog = false)
  {
    $uom = $config['params']['data']['uom'];

    $itemid = $config['params']['data']['itemid'];
    $trno = $config['params']['trno'];
    $wh = $config['params']['data']['wh'];
    // $noprint = 'false';
    $rem = '';
    $amt =0;

    // if (isset($config['params']['data']['noprint'])) {
    //   $noprint = $config['params']['data']['noprint'];
    // }

    if (isset($config['params']['data']['rem'])) {
      $rem = $config['params']['data']['rem'];
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

      $ext = $config['params']['data']['amt'];//total amt ung input nila
      $qty = $config['params']['data']['qty'];    

    } elseif ($action == 'update') {
      $config['params']['line'] = $config['params']['data']['line'];
      $line = $config['params']['data']['line'];
      $ext = $config['params']['data']['ext'];
      $amt = $config['params']['data'][$this->damt];
      $qty = $config['params']['data'][$this->dqty];
      $config['params']['line'] = $line;
    }

    $ext = $this->othersClass->sanitizekeyfield('amt', $ext);
    $qty = $this->othersClass->sanitizekeyfield('qty', $qty);
    $amt = $this->othersClass->sanitizekeyfield('amt', $ext);

    $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor,item.isnoninv from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
    $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
    $factor = 1;
    if (!empty($item)) {
      $item[0]->factor = $this->othersClass->val($item[0]->factor);
      if ($item[0]->factor !== 0) $factor = $item[0]->factor;
    }

    $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);

    $qty = round($qty, $this->companysetup->getdecimal('qty', $config['params']));
    $computedata = $this->othersClass->computestock($amt,'',$qty,$factor);
   
    //compute reverse
    $amt = $ext/$qty;    
    $hamt = $amt/$factor;

    $hamt = $this->othersClass->sanitizekeyfield('amt', $hamt);
    $amt = $this->othersClass->sanitizekeyfield('amt', $amt);

    $data = [
      'trno' => $trno,
      'line' => $line,
      'itemid' => $itemid,
      $this->damt => $amt,
      $this->hamt => $hamt,
      $this->dqty => $qty,
      $this->hqty => $computedata['qty'],
      'ext' => number_format($ext, $this->companysetup->getdecimal('currency', $config['params']), '.', ''),
      'whid' => $whid,
      'rem' => $rem,
      'uom' => $uom
      // 'noprint' => $noprint
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

    //insert item
    if ($action == 'insert') {

      $sjitemlimit = $this->companysetup->getsjitemlimit($config['params']);
      if ($sjitemlimit != 0) {

        $qry = "select ifnull(count(stock.trno),0) as itmcnt from lahead as head
              left join lastock as stock on stock.trno=head.trno
              where head.doc='sj' and head.trno=?";
        $count = $this->coreFunctions->opentable($qry, [$trno]);

        if ($count[0]->itmcnt >= $sjitemlimit) {
          return ['status' => false, 'msg' => 'Item Records Limit Reached(' . $sjitemlimit . 'max)'];
        }
      }


      $data['encodeddate'] = $current_timestamp;
      $data['encodedby'] = $config['params']['user'];
      if (isset($config['params']['data']['sortline'])) {
        $data['sortline'] =  $config['params']['data']['sortline'];
      } else {
        $data['sortline'] =  $data['line'];
      }

      $trno = $this->othersClass->val($trno);
      if ($trno == 0) {
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ZERO TRNO (SJ)');
        return ['status' => false, 'msg' => 'Add item Failed. Zero trno generated'];
      }

      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
        $havestock = true;
        $msg = 'Item was successfully added.';

        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Uom:' . $uom . ' Qty' . $qty . ' Amt:' . $amt .' wh:' . $wh . ' ext:' . $computedata['ext'], $setlog ? $this->tablelogs : '');
        $row = $this->openstockline($config);
        return ['row' => $row, 'status' => true, 'msg' => $msg];
      } else {
        return ['status' => false, 'msg' => 'Add item Failed'];
      }
    } elseif ($action == 'update') {
      $return = true;
      $msg = '';
      $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
      return ['status' => $return, 'msg' => $msg];
    }
  } // end function

  public function deleteallitem($config)
  {
    $trno = $config['params']['trno'];
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from stockinfo where trno=?', 'delete', [$trno]);
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
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' barcode:' . $data[0]->barcode . ' Qty:' . $data[0]->isqty . ' Amt:' . $data[0]->isamt . ' Disc:' . $data[0]->disc . ' wh:' . $data[0]->wh . ' ext:' . $data[0]->ext);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
  } // end function

  public function getlatestprice($config)
  {
    $barcode = $config['params']['barcode'];
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
    $pricegrp = '';
    $data = [];

    $qry = "select docno,left(dateid,10) as dateid,round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as defamt,disc,uom from(select head.docno,head.dateid,
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
          order by dateid desc limit 5) as tbl order by dateid desc";

        $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $client, $trno, $center, $barcode, $client, $trno]);

    if (!empty($data)) {
      return ['status' => true, 'msg' => 'Found the latest price...', 'data' => $data[0]];
    } else {
      return ['status' => false, 'msg' => 'No Latest price found...', 'data' => $data];
    }
  } // end function

  public function createdistribution($config)
  {
    $companyid = $config['params']['companyid'];
    $trno = $config['params']['trno'];
    $status = true;
    $totalar = 0;
    $isvatexsales = $this->companysetup->getvatexsales($config['params']);
    $amount = $this->coreFunctions->getfieldvalue($this->head, "amount", "trno=?", [$trno],'',true);
    
    $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);

    if ($amount == 0) {
      $qry = 'select head.dateid,head.client,head.tax,head.contra,head.cur,head.forex,stock.ext,wh.client as wh,ifnull(item.asset,"") as asset,ifnull(item.revenue,"") as revenue,
      item.expense,stock.isamt,stock.disc,stock.isqty,stock.iss,head.projectid,client.rev,head.amount
          from ' . $this->head . ' as head left join ' . $this->stock . ' as stock on stock.trno=head.trno
          left join item on item.itemid=stock.itemid left join client on client.client = head.client left join client as wh on wh.clientid = stock.whid where head.trno=?';
  
      $this->coreFunctions->LogConsole($qry);
      $stock = $this->coreFunctions->opentable($qry, [$trno]);
      $tax = 0;
      if (!empty($stock)) {
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
          $params = [];
  
          if ($vat != 0) {
            $tax = number_format(($stock[$key]->ext / $tax1), 2, '.', '');
            $tax = number_format($stock[$key]->ext - $tax, 2, '.', '');
            $totalar = $totalar + number_format($stock[$key]->ext, 2, '.', '');
          }
  
          if ($stock[$key]->revenue != '') {
            $revacct = $stock[$key]->revenue;
          } else {
            if ($stock[$key]->rev != '' && $stock[$key]->rev != '\\') {
              $revacct = $stock[$key]->rev;
            }
          }
  
          $expense = isset($stock[$key]->expense) ? $stock[$key]->expense : '';
  
          $params = [
            'client' => $stock[$key]->client,
            'acno' => $stock[$key]->contra,
            'ext' => number_format($stock[$key]->ext, 2, '.', ''),
            'ar' => number_format($stock[$key]->ext, 2, '.', ''),
            'wh' => $stock[$key]->wh,
            'date' => $stock[$key]->dateid,
            'revenue' => $revacct,
            'expense' => $expense,
            'tax' =>  $tax,
            'discamt' => 0,
            'cur' => $stock[$key]->cur,
            'forex' => $stock[$key]->forex,
            'projectid' => 0
          ];
  
          $this->distribution($params, $config);
        }
      }
    }else{//amount
      $qry = 'select head.dateid,head.client,head.tax,head.contra,head.cur,head.forex,client.rev,head.amount
          from ' . $this->head . ' as head  left join client on client.client = head.client where head.trno=?';
  
      $this->coreFunctions->LogConsole($qry);
      $stock = $this->coreFunctions->opentable($qry, [$trno]);
      if (!empty($stock)) {
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
          $params = [];
  
          if ($vat != 0) {
            $tax = number_format(($stock[$key]->amount / $tax1), 2, '.', '');
            $tax = number_format($stock[$key]->amount - $tax, 2, '.', '');
            $totalar = $totalar + number_format($stock[$key]->amount, 2, '.', '');
          }
  
          if ($stock[$key]->rev != '' && $stock[$key]->rev != '\\') {
            $revacct = $stock[$key]->rev;
          }
  
          $params = [
            'client' => $stock[$key]->client,
            'acno' => $stock[$key]->contra,
            'ext' => number_format($stock[$key]->amount, 2, '.', ''),
            'ar' => number_format($stock[$key]->amount, 2, '.', ''),
            'date' => $stock[$key]->dateid,
            'revenue' => $revacct,
            'tax' =>  $tax,
            'discamt' => 0,
            'cur' => $stock[$key]->cur,
            'forex' => $stock[$key]->forex,
            'projectid' => 0
          ];
  
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
    $periodic = $this->companysetup->getisperiodic($config['params']);
    $entry = [];
    $forex = $params['forex'];
    $cur = $params['cur'];
    $sales = 0;
    if (floatval($forex) == 0) {
      $forex = 1;
    }
    //AR
    if (floatval($params['ar']) != 0) {
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['acno']]);
      $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'db' => ($params['ar'] * $forex), 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => floatval($forex) == 1 ? 0 : $params['ar'], 'fcr' => 0, 'projectid' => $params['projectid']];

      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }

    if (floatval($params['tax']) != 0) {
      //sales
      $sales = ($params['ext'] - $params['tax']);
      $sales  = $sales;
      if (floatval($sales) != 0) {
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['revenue']]);
        $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'cr' => ($sales * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : $sales, 'fdb' => 0, 'projectid' => $params['projectid']];

        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }


      // output tax
      $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['TX2']);
      $entry = ['acnoid' => $input, 'client' => $params['client'], 'cr' => ($params['tax'] * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : ($params['tax']), 'fdb' => 0, 'projectid' => $params['projectid']];

      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    } else {
      //sales
      $sales = ($params['ext']);
      $sales = round($sales, 2);
      if (floatval($sales) != 0) {
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['revenue']]);
        $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'cr' => ($sales * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : $sales, 'fdb' => 0, 'projectid' => $params['projectid']];

        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }
    }
  } //end function

  public function reportsetup($config)
  {

    $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
    $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);

    $modulename = $this->modulename;
    $data = [];

    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false, 'reloadhead' => true];
  }

  public function reportdata($config)
  {
    $dataparams = $config['params']['dataparams'];
    $this->logger->sbcviewreportlog($config);
    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);


    if (isset($dataparams['checked'])) $this->othersClass->writeSignatories($config, 'checked', $dataparams['checked']);
    if (isset($dataparams['approved'])) $this->othersClass->writeSignatories($config, 'approved', $dataparams['approved']);
    if (isset($dataparams['received'])) $this->othersClass->writeSignatories($config, 'received', $dataparams['received']);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'reloadhead' => true];
  }


  public function recomputestock($head, $config)
  {
    $data = $this->openstock($head['trno'], $config);
    $data2 = json_decode(json_encode($data), true);
    $exec = true;
    $deci = $this->companysetup->getdecimal('price', $config['params']);
    foreach ($data2 as $key => $value) {
      $damt = $this->othersClass->sanitizekeyfield('amt', $data2[$key][$this->damt]);
      $dqty = $this->othersClass->sanitizekeyfield('qty', round($data2[$key][$this->dqty], $this->companysetup->getdecimal('qty', $config['params'])));
      $computedata = $this->othersClass->computestock(
        $damt * $head['forex'],
        $data[$key]->disc,
        $dqty,
        $data[$key]->uomfactor,
        0
      );

      $computedata['amt']  = number_format($computedata['amt'], $deci, '.', '');
      $computedata['amt'] = $this->othersClass->sanitizekeyfield('amt', $computedata['amt']);

      $exec = $this->coreFunctions->execqry("update lastock set amt = " . $computedata['amt'] . " where trno = " . $head['trno'] . " and line=" . $data[$key]->line, "update");
    }
    return $exec;
  }

  public function sbcscript($config)
  {
    $companyid = $config['params']['companyid'];
    return $this->sbcscript->ch($config);
  }
} //end class