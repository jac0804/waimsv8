<?php

namespace App\Http\Classes\modules\cbbsi;

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


class sm
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Supplier Invoice';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false];
  public $tablenum = 'cntnum';
  public $head = 'lahead';
  public $hhead = 'glhead';
  public $stock = 'snstock';
  public $hstock = 'hsnstock';
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
  public $acctg = [];

  private $fields = [
    'trno', 'docno', 'dateid', 'due', 'client', 'clientname', 'yourref', 'ourref', 'rem', 'terms', 'forex', 'cur',
    'wh', 'address', 'contra', 'tax', 'vattype', 'projectid', 'subproject', 'waybill', 'ewt', 'ewtrate', 'trnxtype'
  ];

  private $otherfields = ['trno', 'freight'];

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
      'view' => 4530,
      'edit' => 4531,
      'new' => 4532,
      'save' => 4533,
      'delete' => 4534,
      'print' => 4535,
      'lock' => 4536,
      'unlock' => 4537,
      'acctg' => 4540,
      'post' => 4538,
      'unpost' => 4539,
      'additem' => 4541,
      'deleteitem' => 4542,
      'edititem' => 4572
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
    $yourref = 5;
    $ourref = 6;
    $total = 7;
    $postdate = 8;
    $listpostedby = 9;
    $listcreateby = 10;
    $listeditby = 11;
    $listviewby = 12;

    $getcols = ['action', 'lblstatus', 'listdocument', 'listdate', 'listclientname', 'yourref', 'ourref', 'total',  'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
    $stockbuttons = ['view', 'diagram'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$listclientname]['style'] = 'width:350px;whiteSpace: normal;min-width:350px;';
    $cols[$yourref]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
    $cols[$yourref]['align'] = 'text-left';
    $cols[$ourref]['align'] = 'text-left';
    $cols[$total]['label'] = 'Grand Total';

    $cols[$postdate]['label'] = 'Post Date';

    $cols = $this->tabClass->delcollisting($cols);
    return $cols;
  }

  public function paramsdatalisting($config)
  {
    return ['status' => true, 'data' => [], 'txtfield' => ['col1' => []]];
  }

  public function loaddoclisting($config)
  {

    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));
    $itemfilter = $config['params']['itemfilter'];
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $condition = '';
    $status = "";
    $ustatus = "";
    $limit = '';

    $searchfield = [];
    $filtersearch = "";
    $search = $config['params']['search'];

    $join = '';
    $hjoin = '';
    $addparams = '';

    $ustatus = "DRAFT";
    $dateid = "left(head.dateid,10) as dateid";
    $status = "stat.status";
    if ($search != "") $limit = 'limit 150';
    $orderby = "order by dateid desc, docno desc";

    $leftjoin = "";
    $leftjoin_posted = "";
    switch ($itemfilter) {
      case 'draft':
        $condition = ' and num.postdate is null and head.lockdate is null ';
        break;

      case 'locked':
        $condition = ' and head.lockdate is not null and num.postdate is null ';
        $status = "'LOCKED'";
        $ustatus = "LOCKED";
        break;

      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
    }


    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'head.clientname', 'head.yourref', 'head.ourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];


      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }


    $qry = "select head.trno,head.docno,head.clientname,$dateid,case ifnull(head.lockdate,'') when '' then '" . $ustatus . "' else 'Locked' end as stat,head.createby,head.editby,head.viewby,num.postedby, date(num.postdate) as postdate,head.yourref, head.ourref, head.rem, (select format(sum(ext), " . $this->companysetup->getdecimal('price', $config['params']) . ") from " . $this->stock . " where trno=head.trno) as total 
     from " . $this->head . " as head 
     left join " . $this->tablenum . " as num on num.trno=head.trno 
     left join trxstatus as stat on stat.line=num.statid 
     " . $leftjoin . "
     " . $join . "
     where head.doc=? and num.center=? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $addparams . " " . $filtersearch . "
     group by head.trno, head.docno, head.clientname, head.dateid, stat.status,
          head.createby, head.editby, head.viewby, num.postedby,
          num.postdate, head.yourref, head.ourref,stat.line,head.lockdate, head.rem
     union all
     select head.trno,head.docno,head.clientname,$dateid," . $status . " as stat,head.createby,head.editby,head.viewby, num.postedby, date(num.postdate) as postdate,head.yourref, head.ourref, head.rem, (select format(sum(ext), " . $this->companysetup->getdecimal('price', $config['params']) . ") from " . $this->hstock . " where trno=head.trno) as total 
     from " . $this->hhead . " as head 
     left join " . $this->tablenum . " as num on num.trno=head.trno 
     left join trxstatus as stat on stat.line=num.statid 
     " . $leftjoin_posted . "
     " . $hjoin . "
     where head.doc=? and num.center=? and convert(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $addparams . " " . $filtersearch . "
     group by head.trno, head.docno, head.clientname, head.dateid, stat.status, head.createby, head.editby, head.viewby, num.postedby, num.postdate, head.yourref, head.ourref,stat.line ,head.lockdate, head.rem
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

    $buttons['others']['items']['first'] =  ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']];
    $buttons['others']['items']['prev'] =  ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']];
    $buttons['others']['items']['next'] = ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']];
    $buttons['others']['items']['last'] = ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']];

    if ($this->companysetup->getisshowmanual($config['params'])) {
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => 'po', 'title' => 'PO_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
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
    $action = 0;
    $rrqty = 1;
    $uom = 2;
    $wh = 3;
    $lcost = 4;
    $rrcost = 5;
    $freight = 6;
    $cost = 7;
    $ext = 8;
    $ref = 9;
    $barcode = 10;
    $itemname = 11;

    $column = [
      'action', 'rrqty', 'uom',  'wh', 'lastcost', 'rrcost', 'charges', 'cost', 'ext', 'ref', 'barcode', 'itemname'
    ];

    $sortcolumn = [
      'action', 'rrqty', 'uom',  'wh', 'lastcost', 'rrcost', 'charges', 'cost', 'ext', 'ref', 'barcode', 'itemname'
    ];

    $headgridbtns = ['viewdistribution'];

    $tab = [
      $this->gridname => [
        'gridcolumns' => $column,
        'sortcolumns' => $sortcolumn,
        'computefield' => ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt,   'disc' => 'disc', 'total' => 'ext'],
        'headgridbtns' => $headgridbtns
      ]
    ];

    $stockbuttons = ['save', 'delete', 'showbalance'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0]['inventory']['columns'][$rrqty]['label'] = 'Quantity';
    $obj[0]['inventory']['columns'][$rrcost]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px';
    $obj[0]['inventory']['columns'][$rrcost]['label'] = 'Supp. Cost';
    $obj[0]['inventory']['columns'][$cost]['label'] = 'Landed Cost';
    $obj[0]['inventory']['columns'][$cost]['readonly'] = true;
    $obj[0]['inventory']['columns'][$ext]['label'] = 'Amount';
    $obj[0]['inventory']['columns'][$lcost]['readonly'] = true;
    $obj[0]['inventory']['columns'][$barcode]['type'] = 'hidden';
    $obj[0]['inventory']['columns'][$barcode]['label'] = '';

    $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['additem', 'pendingrrsn', 'saveitem', 'deleteallitem'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[1]['action'] = 'pendingrrsmsummary';
    $obj[1]['lookupclass'] = 'pendingrrsmsummary';
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['docno', 'client', 'clientname', 'address', 'freight'];

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.label', 'Vendor');
    data_set($col1, 'docno.label', 'Transaction#');

    $fields = [['dateid', 'terms'], ['due', 'dvattype'], 'dacnoname', 'dexpacnoname', 'dwhname'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'dacnoname.label', 'AP Account');
    data_set($col2, 'dwhname.condition', ['checkstock']);
    data_set($col2, 'dexpacnoname.lookupclass', 'CG');

    $fields = [['yourref', 'ourref'], ['cur', 'forex'], 'dprojectname', 'dewt', 'trnxtype'];

    $col3 = $this->fieldClass->create($fields);


    $fields = ['rem'];
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

    $data[0]['expacnoname'] = '';
    $data[0]['contra2'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['CG1']);
    $data[0]['waybill'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['CG1']);
    $data[0]['acnoname2'] = $this->coreFunctions->getfieldvalue('coa', 'acnoname', 'acno=?', [$data[0]['contra2']]);
    $data[0]['freight'] = '';
    $data[0]['trnxtype'] = '';

    $data[0]['projectid'] = '0';
    $data[0]['dprojectname'] = '';

    $data[0]['projectname'] = '';
    $data[0]['projectcode'] = '';
    $data[0]['subproject'] = '0';
    $data[0]['subprojectname'] = '';
    $data[0]['address'] = '';



    $ewtrate = $this->coreFunctions->getfieldvalue('ewtlist', 'rate', 'rate=1');
    $ewt = $this->coreFunctions->getfieldvalue('ewtlist', 'code', 'rate=1');

    $data[0]['ewtrate'] = $ewtrate;
    $data[0]['ewt'] = $ewt;
    if ($ewtrate == 0 && $ewt == '') {
      $data[0]['ewtrate'] = 0;
      $data[0]['ewt'] = '';
    }

    return $data;
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
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        } //end if    
      }
    }

    $dataother = [];
    foreach ($this->otherfields as $key) {
      $dataother[$key] = $head[$key];
      $dataother[$key] = $this->othersClass->sanitizekeyfield($key, $dataother[$key]);
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

    $infotransexist = $this->coreFunctions->getfieldvalue("cntnuminfo", "trno", "trno=?", [$head['trno']]);
    if ($infotransexist == '') {
      $this->coreFunctions->sbcinsert("cntnuminfo", $dataother);
    } else {
      $dataother['editby'] = $config['params']['user'];
      $dataother['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $this->coreFunctions->sbcupdate("cntnuminfo", $dataother, ['trno' => $head['trno']]);
    }
  } // end function

  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $trno = $config['params']['trno'];
    $center = $config['params']['center'];
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
    $center = $config['params']['center'];
    $head = [];
    $islocked = $this->othersClass->islocked($config);
    $isposted = $this->othersClass->isposted($config);
    $table = $this->head;
    $htable = $this->hhead;
    $tablenum = $this->tablenum;

    $qryselect = "select num.center,head.trno, head.docno,client.client,head.terms,head.cur,head.forex,head.yourref,head.ourref,head.contra,coa.acnoname,'' as dacnoname,head.waybill,coa2.acnoname as expacnoname,coa2.acnoname as acnoname2,coa2.acno as contra2,'' as dexpacnoname,left(head.dateid,10) as dateid, head.clientname,head.address, head.shipto, date_format(head.createdate,'%Y-%m-%d') as createdate,head.rem,head.tax,head.vattype,'' as dvattype,warehouse.client as wh,warehouse.clientname as whname, '' as dwhname,head.projectid,'' as dprojectname,left(head.due,10) as due, client.groupid,ifnull(p.code,'') as projectcode,ifnull(p.name,'') as projectname,ifnull(s.line,0) as subproject,ifnull(s.subproject,'') as subprojectname,
    head.ewt,head.ewtrate,'' as dewt,head.trnxtype, numinfo.freight";

    $qry = $qryselect . " from $table as head
    left join $tablenum as num on num.trno = head.trno
    left join client on head.client = client.client
    left join client as warehouse on warehouse.client = head.wh
    left join coa on coa.acno=head.contra
    left join coa as coa2 on coa2.acno=head.waybill
    left join projectmasterfile as p on p.line=head.projectid         
    left join subproject as s on s.line = head.subproject
    left join cntnuminfo as numinfo on numinfo.trno=head.trno
    where head.trno = ? and num.doc=? and num.center = ? " . $projectfilter . "
    union all " . $qryselect . " from $htable as head
    left join $tablenum as num on num.trno = head.trno
    left join client on head.clientid = client.clientid
    left join client as warehouse on warehouse.clientid = head.whid
    left join coa on coa.acno=head.contra 
    left join coa as coa2 on coa2.acno=head.waybill
    left join projectmasterfile as p on p.line=head.projectid         
    left join subproject as s on s.line = head.subproject
    left join hcntnuminfo as numinfo on numinfo.trno=head.trno
    where head.trno = ? and num.doc=? and num.center=? " . $projectfilter;


    $head = $this->coreFunctions->opentable($qry, [$trno, $doc, $center, $trno, $doc, $center]);
    if (!empty($head)) {
      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }
      $stock = $this->openstock($trno, $config);
      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
      return  ['head' => $head, 'griddata' => ['inventory' => $stock], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg];
    } else {
      $head[0]['trno'] = 0;
      $head[0]['docno'] = '';
      return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => []], 'msg' => 'Data Head Fetched Failed'];
    }
  }

  public function deletetrans($config)
  {
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];
    $table = $config['docmodule']->tablenum;
    $docno = $this->coreFunctions->datareader("select docno as value from " . $table . ' where trno=?', [$trno]);
    $qry = "select trno as value from " . $this->tablenum . " where doc=? and trno<? order by trno desc limit 1 ";
    $trno2 = $this->coreFunctions->datareader($qry, [$doc, $trno]);

    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);

    $this->othersClass->deleteattachments($config);

    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $trnxtype = $this->coreFunctions->getfieldvalue($this->head, "trnxtype", "trno=?", [$trno]);
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    $checkacct = $this->othersClass->checkcoaacct(['AP1', 'IN1', 'PD1', 'TX1']);

    if ($checkacct != '') {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Accounts not yet setup:' . $checkacct];
    }

    if (!$this->createdistribution($config)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. Problems in creating accounting entries.'];
    } else {
      $return =  $this->othersClass->posttranstock($config);
      if (strtoupper($trnxtype) == 'REGULAR') {
        if ($return['status']) {
          $this->updateitemcost($config);
        }
      }
      return $return;
    }
  } //end function    

  private function updateitemcost($config)
  {
    $trno = $config['params']['trno'];
    $qry = "select stock.line,
        item.itemid,        
        stock.cost        
        FROM cntnum left join
        $this->hstock as stock on stock.trno=cntnum.trno
        left join item on item.itemid=stock.itemid where stock.trno =?  and stock.cost<>0  order by line";
    $data = $this->coreFunctions->opentable($qry, [$trno]);

    if (!empty($data)) {
      foreach ($data as $k => $v) {
        if ($data[$k]->cost != 0) {
          $this->coreFunctions->execqry("update item set amt8 = amt9 where itemid =" . $data[$k]->itemid);
          $this->coreFunctions->execqry("update item set amt9 = " . $data[$k]->cost . " where itemid =" . $data[$k]->itemid);
        }
      }
    }
  }

  public function createdistribution($config)
  {
    $trno = $config['params']['trno'];
    $status = true;
    $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);

    $headexp = $this->coreFunctions->datareader("select ifnull(coa.acno,'') as value from lahead as h left join coa on coa.acno=h.waybill where trno=?", [$trno]);
    if ($headexp == '') {
      $headexp = " ifnull(item.expense,'')";
    } else {
      $headexp = "'\\" . $headexp . "'";
    }

    $qry = 'select head.dateid,client.client,head.tax, head.contra, head.cur,head.forex,stock.ext,wh.client as wh,
        case head.waybill when "" then ifnull(item.expense,"") else head.waybill end as expense,
        stock.rrcost,stock.cost,stock.disc,stock.rrqty,stock.qty,head.projectid,head.subproject,0 as stageid, head.ewt, head.ewtrate,head.rem
        from ' . $this->head . ' as head 
        left join ' . $this->stock . ' as stock on stock.trno=head.trno
        left join client as wh on wh.clientid=stock.whid 
        left join cntnum on cntnum.trno=head.trno
        left join item on item.itemid=stock.itemid 
        left join client on client.client=head.client  
        where head.trno =?';

    $stock = $this->coreFunctions->opentable($qry, [$trno]);

    $tax = 0;
    if (!empty($stock)) {
      $invacct = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['CG1']);

      $vat = intval($this->coreFunctions->datareader("select tax as value from lahead where trno = ?
            union all
            select tax as value from glhead where trno = ?
            ", [$trno, $trno]));

      $tax1 = 0;
      $tax2 = 0;
      $ewtvalue = 0;
      if ($vat != 0) {
        $tax1 = 1 + ($vat / 100);
        $tax2 = $vat / 100;
      }
      foreach ($stock as $key => $value) {
        $params = [];
        $disc = $stock[$key]->rrcost - ($this->othersClass->discount($stock[$key]->rrcost, $stock[$key]->disc));
        if ($vat != 0) {
          $tax = round(($stock[$key]->ext / $tax1), 2);
          $tax = round($stock[$key]->ext - $tax, 2);
        }

        if ($value->ewt != '') {
          if ($vat != 0) {
            $amt = round(($stock[$key]->ext / $tax1), 2);
            $ewtvalue = ($amt * ($stock[$key]->ewtrate / 100));
            $this->coreFunctions->LogConsole($stock[$key]->ext . '-' . $ewtvalue);
          } else {
            $amt = round(($stock[$key]->ext), 2);
            $ewtvalue = ($amt * ($stock[$key]->ewtrate / 100));
            $this->coreFunctions->LogConsole($stock[$key]->ext . '-' . $ewtvalue);
          }
        }

        $params = [
          'client' => $stock[$key]->client,
          'acno' => $stock[$key]->contra,
          'ext' => $stock[$key]->ext,
          'wh' => $stock[$key]->wh,
          'date' => $stock[$key]->dateid,
          'inventory' => $stock[$key]->expense !== '' ? $stock[$key]->expense : $invacct,
          'tax' =>  $tax,
          'discamt' => $disc * $stock[$key]->rrqty,
          'cur' => $stock[$key]->cur,
          'forex' => $stock[$key]->forex,
          'cost' => $stock[$key]->ext - $tax, //$stock[$key]->cost * $stock[$key]->qty,
          'projectid' => $stock[$key]->projectid,
          'subproject' => $stock[$key]->subproject,
          'stageid' => $stock[$key]->stageid,
          'ewt' => $stock[$key]->ewt,
          'ewtrate' => $stock[$key]->ewtrate,
          'ewtvalue' => $ewtvalue,
          'rem' => $stock[$key]->rem
        ];


        $this->distribution($params, $config);
      }
    }

    $freight = $this->coreFunctions->getfieldvalue("cntnuminfo", "freight", "trno=?", [$trno]);
    if ($freight == '') {
      $freight = 0;
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
    $entry = [];
    $forex = $params['forex'];
    if ($forex == 0) {
      $forex = 1;
    }
    $suppinvoice = $this->companysetup->getsupplierinvoice($config['params']);

    $cur = $params['cur'];
    $invamt = $params['cost']; //round(($params['ext']-$params['tax']) + $params['discamt'],2);
    $ap = floatval($params['ext']);

    //AP
    if (floatval($params['ext']) != 0) {
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['acno']]);
      $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'db' => 0, 'cr' => ($ap * $forex), 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => 0, 'fcr' => floatval($forex) == 1 ? 0 : $ap, 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid'], 'rem' => $params['rem']];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }

    //disc      
    if (floatval($params['discamt']) != 0) {
      $inputid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['PD1']);
      $entry = ['acnoid' => $inputid, 'client' => $params['client'], 'cr' => ($params['discamt'] * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => 0, 'fcr' => floatval($forex) == 1 ? 0 : ($params['discamt']), 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }

    if (floatval($params['tax']) != 0) {
      // input tax
      $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['TX1']);
      $entry = ['acnoid' => $input, 'client' => $params['client'], 'cr' => 0, 'db' => ($params['tax'] * $forex), 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($params['tax']), 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }

    //INV
    if (floatval($invamt) != 0) {
      if (floatval($params['discamt']) != 0) {
        $invamt  = $invamt + ($params['discamt'] * $forex);
      }
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['inventory']]);
      $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'db' => ($invamt), 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($invamt / $forex), 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }

    if (floatval($params['ewtvalue']) != 0) {
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['WT1']);
      $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'db' => 0, 'cr' => ($params['ewtvalue'] * $forex), 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => 0, 'fcr' => floatval($forex) == 1 ? 0 : $params['ewtvalue'], 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['acno']]);
      $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'cr' => 0, 'db' => ($params['ewtvalue'] * $forex), 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : $params['ewtvalue'], 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'rem' => 'EWT', 'stageid' => $params['stageid']];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }
  } //end function

  public function unposttrans($config)
  {
    return $this->othersClass->unposttranstock($config);
  } //end function

  private function getstockselect($config)
  {
    $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);

    $sqlselect = "select item.brand as brand,
    ifnull(mm.model_name,'') as model,
    item.itemid,
    item.itemname,
    stock.trno,
    stock.line,
    stock.refx,
    stock.linex,
    item.barcode,
    stock.uom,
    FORMAT(stock.cost*uom.factor,2) as cost,
    stock.qty as qty,
    FORMAT(stock.rrcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrcost,
    FORMAT(stock.rrqty," . $qty_dec . ")  as rrqty,
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,
    warehouse.client as wh,
    warehouse.clientname as whname,
    item.brand,
    ifnull(uom.factor,1) as uomfactor,
    stock.disc,
    '' as bgcolor,
    format(stock.lastcost*uom.factor,2) as lastcost,
    item.amt9,
    stock.charges,stock.ref,
    item.subcode, item.partno, round(item.dqty, " . $this->companysetup->getdecimal('qty', $config['params']) . ") as boxcount,
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
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid   
    left join model_masterfile as mm on mm.model_id = item.model
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid
    where stock.trno =?
    UNION ALL
    " . $sqlselect . "
    FROM $this->hstock as stock
    left join item on item.itemid=stock.itemid
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
    left join client as warehouse on warehouse.clientid=stock.whid
    left join model_masterfile as mm on mm.model_id = item.model
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
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid
    left join model_masterfile as mm on mm.model_id = item.model
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid
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
      case 'deleteitem':
        $return = $this->deleteitem($config);
        $this->computefreight($config);
        return ['status' => $return['status'], 'msg' => $return['msg'], 'reloadhead' => true];
        break;
      case 'saveitem': //save all item edited
        $return = $this->updateitem($config);
        $this->computefreight($config);
        return ['status' => $return['status'], 'msg' => $return['msg'], 'reloadhead' => true];
        break;
      case 'saveperitem':
        $return = $this->updateperitem($config);
        $this->computefreight($config);
        return ['row' => $return['row'], 'status' => $return['status'], 'msg' => $return['msg'], 'reloadhead' => true];
        break;
      case 'deleteallitem':
        return $this->deleteallitem($config);
        break;
      case 'getrrsummary':
        $return = $this->getrrsummary($config);
        $this->computefreight($config);
        //$row = $this->openstock($config['params']['trno'],$config);
        return ['status' => $return['status'], 'msg' => $return['msg'], 'reloadhead' => true];
        break;
      case 'getrrdetails':
        $return = $this->getrrdetails($config);
        $this->computefreight($config);
        //$row = $this->openstock($config['params']['trno'],$config);
        return ['status' => $return['status'], 'msg' => $return['msg'], 'reloadhead' => true];
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
    $data2 = json_decode(json_encode($data), true);

    $msg1 = '';
    $msg2 = '';
    foreach ($data2 as $key => $value) {
      if ($data2[$key][$this->dqty] == 0) {
        $data[$key]->errcolor = 'bg-red-2';
        $isupdate = false;
        if ($data[$key]->refx != 0) {
          $msg1 = ' Qty Invoice is Greater than RR Qty ';
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
    $this->computefreight($config);
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
          $msg2 = ' Qty Invoice is Greater than RR Qty ';
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
    return ['inventory' => $data, 'status' => true, 'msg' => $msg];
  } //end function

  // insert and update item
  public function additem($action, $config)
  {
    $isproject = $this->companysetup->getisproject($config['params']);
    $uom = $config['params']['data']['uom'];
    $itemid = $config['params']['data']['itemid'];
    $trno = $config['params']['trno'];
    $disc = $config['params']['data']['disc'];
    $wh = $config['params']['data']['wh'];
    $charges = '';
    $ref = '';
    $lastcost = 0;
    $refx = 0;
    $linex = 0;
    $ext = 0;

    if (isset($config['params']['data']['ref'])) {
      $ref = $config['params']['data']['ref'];
    }

    if (isset($config['params']['data']['lastcost'])) {
      $lastcost = $config['params']['data']['lastcost'];
    }

    if (isset($config['params']['data']['charges'])) {
      $charges = $config['params']['data']['charges'];
    }

    if (isset($config['params']['data']['refx'])) {
      $refx = $config['params']['data']['refx'];
    }
    if (isset($config['params']['data']['linex'])) {
      $linex = $config['params']['data']['linex'];
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

    $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";

    $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
    $factor = 1;
    if (!empty($item)) {
      $item[0]->factor = $this->othersClass->val($item[0]->factor);
      if ($item[0]->factor !== 0) $factor = $item[0]->factor;
    }

    $forex = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
    $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);
    $qty = round($qty, $this->companysetup->getdecimal('qty', $config['params']));
    $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor);
    $ext = number_format($computedata['ext'], $this->companysetup->getdecimal('currency', $config['params']), '.', '');
    $cost = number_format($this->othersClass->Discount(($computedata['amt'] * $forex), $charges), 6, '.', '');

    $data = [
      'trno' => $trno,
      'line' => $line,
      'itemid' => $itemid,
      'rrcost' => $amt,
      'cost' => $cost,
      'rrqty' => $qty,
      'qty' => $computedata['qty'],
      'ext' => $ext,
      'disc' => $disc,
      'whid' => $whid,
      'wh' => $wh,
      'uom' => $uom,
      'refx' => $refx,
      'linex' => $linex,
      'lastcost' => $lastcost,
      'charges' => $charges,
      'ref' => $ref
    ];


    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }
    if ($uom == '') {
      $msg = 'UOM cannot be blank -' . $item[0]->barcode;
      return ['status' => false, 'msg' => $msg];
    }

    if ($action == 'insert') {
      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {

        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' Barcode:' . $item[0]->barcode . ' Amt:' . $amt . ' Disc:' . $disc . ' WH:' . $wh . ' Ext:' . $computedata['ext'] . ' Uom:' . $uom);

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

      return $return;
    }
  } // end function

  public function deleteallitem($config)
  {
    $isallow = true;
    $trno = $config['params']['trno'];
    $data = $this->coreFunctions->opentable('select refx,linex from ' . $this->stock . ' where trno=? and refx<>0 ', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);

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

    if (!empty($data)) {
      if ($data[0]->refx !== 0) {
        $this->setserveditems($data[0]->refx, $data[0]->linex);
      }
    }
    $data = json_decode(json_encode($data), true);
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' Barcode:' . $data[0]['barcode'] . ' Qty:' . $data[0]['rrqty'] . ' Amt:' . $data[0]['rrcost'] . ' Disc:' . $data[0]['disc'] . ' WH:' . $data[0]['wh'] . ' Ext:' . $data[0]['ext']);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
  } // end function

  public function getrrsummary($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.cost,
        (stock.qty-rrstatus.qa2) as qty,stock.rrcost,item.amt9,wh.client as wh,
        round((stock.qty-rrstatus.qa2)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty, stock.disc
        FROM glhead as head left join glstock as stock on stock.trno=head.trno
        left join item on item.itemid=stock.itemid
        left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
        left join rrstatus on rrstatus.trno=stock.trno and rrstatus.line=stock.line
        left join hstockinfo as info on info.trno = stock.trno and info.line = stock.line
        left join cntnum as num on num.trno = head.trno
        left join client as wh on wh.clientid = stock.whid
        where stock.trno = ? and info.isbo<>1 and rrstatus.qty>rrstatus.qa2 and stock.void=0 
    ";
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno']]);
      if (!empty($data)) {
        foreach ($data as $key2 => $value) {
          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['qty'] = $data[$key2]->rrqty;
          $config['params']['data']['wh'] = $data[$key2]->wh;
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['amt'] = $data[$key2]->rrcost;
          $config['params']['data']['lastcost'] = $data[$key2]->amt9;
          $config['params']['data']['ref'] = $data[$key2]->docno;

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
    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "select head.docno, item.itemid,stock.trno,stock.line, item.barcode,stock.uom, stock.cost,
                    (rrstatus.qty-rrstatus.qa2) as qty,stock.rrcost,wh.client as wh,
                    round((rrstatus.qty-rrstatus.qa2)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end,
                    " . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
                    stock.disc
              FROM glhead as head left join glstock as stock on stock.trno=head.trno
              left join item on item.itemid=stock.itemid
              left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
              left join rrstatus on rrstatus.trno=stock.trno and rrstatus.line=stock.line
              left join hstockinfo as info on info.trno = stock.trno and info.line = stock.line
              left join client as wh on wh.clientid = stock.whid
              where stock.trno = ? and stock.line=? and info.isbo<>1 and rrstatus.qty>rrstatus.qa2 and stock.void=0";
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line']]);
      if (!empty($data)) {
        foreach ($data as $key2 => $value) {

          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['qty'] = $data[$key2]->rrqty;
          $config['params']['data']['wh'] = $data[$key2]->wh;
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['amt'] = $data[$key2]->rrcost;
          $config['params']['data']['ref'] = $data[$key2]->docno;

          $return = $this->additem('insert', $config);
          if ($return['status']) {
            if ($this->setserveditems($data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setserveditems($data[$key2]->trno, $data[$key2]->line);
              $this->computefreight($config);
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

  public function computefreight($config)
  {
    $trno = $config['params']['trno'];
    $freight = $this->coreFunctions->getfieldvalue("cntnuminfo", "freight", "trno=?", [$trno]);
    $forex = $this->coreFunctions->getfieldvalue($this->head, "forex", "trno=?", [$trno]);
    $tax = $this->coreFunctions->getfieldvalue($this->head, "tax", "trno=?", [$trno]);
    $f = 0;
    $data = $this->openstock($trno, $config);
    $data2 = json_decode(json_encode($data), true);
    $exec = true;
    $total = 0;
    $this->coreFunctions->LogConsole('freight: ' . $freight);
    if ($freight <> 0) {
      if (!empty($data2)) {
        foreach ($data2 as $key => $value) {
          $damt = $this->othersClass->sanitizekeyfield('amt', $data2[$key][$this->damt]);
          $dqty = $this->othersClass->sanitizekeyfield('qty', $data2[$key][$this->dqty]);

          $total = $total + ($damt * $dqty);
        }
        $this->coreFunctions->LogConsole('total: ' . $total);
        if ($total <> 0) {
          $f = "+" . round(($freight / $total) * 100, 4) . "%";
        }

        foreach ($data2 as $key => $value) {
          $damt = $this->othersClass->sanitizekeyfield('amt', $data2[$key][$this->damt]);
          $dqty = $this->othersClass->sanitizekeyfield('qty', $data2[$key][$this->dqty]);
          $computedata = $this->othersClass->computestock($damt * $forex, $data[$key]->disc, $dqty, $data[$key]->uomfactor, $tax, 'P', 0, 1, 1);
          $cost = number_format($this->othersClass->Discount(($computedata['amt'] * $forex), $f), 6, '.', '');
          $cost = $this->othersClass->sanitizekeyfield('cost', $cost);
          $exec = $this->coreFunctions->execqry("update " . $this->stock . " set charges = '" . $f . "', cost = " . $cost . " where trno = " . $trno . " and line=" . $data[$key]->line, "update");
        }
      }
    }
  }

  public function setserveditems($refx, $linex)
  {
    if ($refx == 0) {
      return 1;
    }
    $qry1 = "select stock." . $this->hqty . " from lahead as head left join snstock as
    stock on stock.trno=head.trno where head.doc='SM' and stock.refx=" . $refx . " and stock.linex=" . $linex;
    $qry1 = $qry1 . " union all select hsnstock." . $this->hqty . " from glhead left join hsnstock on hsnstock.trno=
    glhead.trno where glhead.doc='SM' and hsnstock.refx=" . $refx . " and hsnstock.linex=" . $linex;
    $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty == '') {
      $qty = 0;
    }
    return $this->coreFunctions->execqry("update rrstatus set qa2=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
  }

  // start
  public function reportsetup($config)
  {
    $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
    $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);

    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false, 'showemailbtn' => false];
  }

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

  public function reportdata($config)
  {
    $this->logger->sbcviewreportlog($config);

    $dataparams = $config['params']['dataparams'];
    if (isset($dataparams['approved'])) $this->othersClass->writeSignatories($config, 'approved', $dataparams['approved']);
    if (isset($dataparams['received'])) $this->othersClass->writeSignatories($config, 'received', $dataparams['received']);

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config, $config['params']['dataid']);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
  // end

} //end class
