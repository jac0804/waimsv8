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
use App\Http\Classes\SBCPDF;
use App\Http\Classes\builder\helpClass;

class te
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'TASK/ERRAND MODULE';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false];
  public $tablenum = 'transnum';
  public $head = 'tehead';
  public $hhead = 'htehead';
  public $stock = 'testock';
  public $hstock = 'htestock';
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';
  private $stockselect;
  public $dqty = 'rrqty';
  public $hqty = 'qty';
  public $damt = 'rrcost';
  public $hamt = 'cost';
  private $fields = [
    'trno', 'docno', 'clientid', 'dateid', 'datereq', 'dateneed', 'due',
    'assignid', 'errandtype', 'contactperson', 'companyaddress', 'rem',
    'company', 'contact'
  ];
  private $except = ['trno'];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  private $reporter;
  private $helpClass;

  public $showfilterlabel = [
    ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
    ['val' => 'locked', 'label' => 'Locked', 'color' => 'primary'],
    ['val' => 'posted', 'label' => 'Posted', 'color' => 'primary']
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
      'view' => 2673,
      'edit' => 2674,
      'new' => 2675,
      'save' => 2676,
      // 'change' => 2677, remove change doc
      'delete' => 2678,
      'print' => 2679,
      'lock' => 2680,
      'unlock' => 2681,
      'changeamt' => 2682,
      'post' => 2683,
      'unpost' => 2684,
      'additem' => 2685,
      'edititem' => 2686,
      'deleteitem' => 2687
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
    $errandtype = 5;
    $postdate = 6;

    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'errandtype', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
    $stockbuttons = ['view', 'diagram'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$listdocument]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $cols[$listclientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $cols[$errandtype]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';

    $cols[$postdate]['label'] = 'Post Date';
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
            $join = " left join testock on testock.trno = head.trno
            left join item on item.itemid = testock.itemid left join item as item2 on item2.itemid = testock.itemid
            left join model_masterfile as model on model.model_id = item.model 
            left join model_masterfile as model2 on model2.model_id = item2.model 
            left join frontend_ebrands as brand on brand.brandid = item.brand 
            left join frontend_ebrands as brand2 on brand2.brandid = item2.brand
            left join projectmasterfile as p on p.line = item.projectid 
            left join projectmasterfile as p2 on p2.line = item2.projectid ";

            $hjoin = " left join htestock on htestock.trno = head.trno
            left join item on item.itemid = htestock.itemid left join item as item2 on item2.itemid = htestock.itemid
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
      $searchfield = ['head.docno', 'head.errandtype', 'cl.clientname', 'head.createby', 'head.editby', 'head.viewby', 'num.postedby'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    } else {
      $limit = 'limit 25';
    }

    $qry = "select head.trno,head.docno, head.errandtype, head.clientid, cl.clientname,$dateid, " . $status . " as status,head.createby,head.editby,head.viewby,
    num.postedby, date(num.postdate) as postdate
    from " . $this->head . " as head 
    left join " . $this->tablenum . " as num on num.trno=head.trno 
    left join client as cl on cl.clientid = head.clientid
    " . $join . "
    where head.doc=? and num.center=? and date(head.dateid) >= ? and date(head.dateid) <= ? $filtersearch
    " . $condition . $addparams . "
    union all
    select head.trno,head.docno, head.errandtype, head.clientid, cl.clientname,$dateid,'POSTED' as status,head.createby,head.editby,head.viewby,
    num.postedby, date(num.postdate) as postdate
    from " . $this->hhead . " as head 
    left join " . $this->tablenum . " as num on num.trno=head.trno 
    left join client as cl on cl.clientid = head.clientid
    " . $hjoin . "
    where head.doc=? and num.center=? and date(head.dateid) >= ? and date(head.dateid) <= ? $filtersearch
    " . $condition . $addparams . "
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
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => $config['params']['doc'], 'title' => strtoupper($config['params']['doc']) . '_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
    }

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
    $companyid = $config['params']['companyid'];
    $action = 0;
    $itemname = 1;
    $qty = 2;
    $uom = 3;
    $brand = 4;
    $model = 5;
    $serialno = 6;
    $barcode = 7;

    $tab = [$this->gridname => ['gridcolumns' => ['action', 'itemname', 'qty', 'uom', 'brand', 'model', 'serialno', 'barcode']]];

    $stockbuttons = ['save', 'delete'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][$itemname]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][$qty]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][$uom]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][$brand]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][$model]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][$serialno]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";

    $obj[0][$this->gridname]['columns'][$itemname]['type'] = "input";
    $obj[0][$this->gridname]['columns'][$itemname]['label'] = "Item name";
    $obj[0][$this->gridname]['columns'][$itemname]['readonly'] = false;

    $obj[0][$this->gridname]['columns'][$qty]['label'] = "QTY";
    $obj[0][$this->gridname]['columns'][$uom]['type'] = "input";

    $obj[0][$this->gridname]['descriptionrow'] = [];
    $obj[0][$this->gridname]['showtotal'] = false;

    $obj[0][$this->gridname]['columns'][$barcode]['label'] = '';
    $obj[0][$this->gridname]['columns'][$barcode]['type'] = 'hidden';

    $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;

    // return [];
  }

  public function createtabbutton($config)
  {
    $companyid = $config['params']['companyid'];

    $tbuttons = ['addrow', 'saveitem', 'deleteallitem'];

    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;

    // return [];
  }

  public function createHeadField($config)
  {
    $fields = ['docno', 'client', 'clientname', 'dateid', 'errandtype', 'ppio'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'docno.label', 'Transaction#');
    data_set($col1, 'client.label', 'Requestor');
    data_set($col1, 'client.type', 'input');
    data_set($col1, 'clientname.readonly', true);
    data_set($col1, 'clientname.class', 'csclientname sbccsreadonly');
    data_set($col1, 'errandtype.condition', ['checkstock']);

    $fields = ['datereq', 'dateneed', 'due', 'assignto'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'datereq.label', 'Date Requested/Filed');
    data_set($col2, 'due.label', 'Date Due/Return');

    $fields = ['company', 'companyaddress', 'contact', 'contactperson'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'company.label', 'Company Name');
    data_set($col3, 'company.type', 'cinput');
    data_set($col3, 'contact.label', 'Contact No.');
    data_set($col3, 'contact.type', 'cinput');

    $fields = ['rem'];
    $col4 = $this->fieldClass->create($fields);

    return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4];
  }

  public function createnewtransaction($docno, $params)
  {
    $clientid = 0;
    $client = "";
    $clientname = "";

    if ($params['companyid'] == 10) { //afti
      $salesperson_qry = "
      select emp.clientid as clientid, emp.client as client, emp.clientname as clientname, 
      branch.clientid as branchid, branch.client as branchcode, branch.clientname as branchname,
      emp.tel2 as contactno
      from client as emp
      left join client as branch on branch.clientid = emp.branchid
      where emp.clientid = ?";
      $salesperson_res = $this->coreFunctions->opentable($salesperson_qry, [$params['adminid']]);
      if (!empty($salesperson_res)) {
        $clientid = $salesperson_res[0]->clientid;
        $client = $salesperson_res[0]->client;
        $clientname = $salesperson_res[0]->clientname;
      }
    }

    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['clientid'] = $clientid;
    $data[0]['client'] = $client;
    $data[0]['clientname'] = $clientname;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['datereq'] = $this->othersClass->getCurrentDate();
    $data[0]['dateneed'] = $this->othersClass->getCurrentDate();
    $data[0]['due'] = $this->othersClass->getCurrentDate();
    $data[0]['assignid'] = 0;
    $data[0]['assignto'] = '';
    $data[0]['errandtype'] = '';
    $data[0]['contactperson'] = '';
    $data[0]['companyaddress'] = '';
    $data[0]['rem'] = '';
    $data[0]['ppio'] = '';
    $data[0]['company'] = '';
    $data[0]['contact'] = '';

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
      head.clientid,
      cl.client,
      cl.clientname,
      head.dateid,
      head.datereq,
      head.dateneed,
      head.due,
      head.assignid,
      assign.clientname as assignto,
      head.errandtype,
      ppio.docno as ppio, head.contactperson, head.companyaddress, head.rem,
      head.company, head.contact";

    $qry = $qryselect . " 
      from $table as head
      left join $tablenum as num on num.trno = head.trno
      left join client as cl on head.clientid = cl.clientid
      left join client as assign on assign.clientid = head.assignid
      left join ppio_series as ppio on ppio.trno = head.trno
      where head.trno = ? and num.center = ?
      union all 
      " . $qryselect . " 
      from $htable as head
      left join $tablenum as num on num.trno = head.trno
      left join client as cl on head.clientid = cl.clientid
      left join client as assign on assign.clientid = head.assignid
      left join ppio_series as ppio on ppio.trno = head.trno
      where head.trno = ? and num.center = ?";

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
      $hideobj = ['rem' => false, 'clientname' => false];
      $hidetabbtn = ['btndeleteallitem' => false];
      $clickobj = ['button.btnadditem'];
      return  [
        'head' => $head, 'griddata' => ['inventory' => $stock], 'islocked' => $islocked,
        'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg,

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

    if ($head['errandtype'] === "PPIO") {
      $tablename = 'ppio_series';
      $checking_ppio = $this->coreFunctions->datareader("select docno as value from " . $tablename . " where trno = '" . $head['trno'] . "'");

      if ($checking_ppio == "") {
        $curyr = date("Ymd");
        $lastseq = $this->coreFunctions->datareader("select seq as value from " . $tablename . " where year = '" . $curyr . "' order by seq desc limit 1") + 1;
        $length = 3;

        if (floatval($lastseq) == 0) {
          $lastseq = 1;
        }
        $curseq = 'P' . $lastseq;
        $this->coreFunctions->LogConsole($curseq);
        $newinvno = str_replace('P', '', $curyr . '-' . $this->othersClass->Padj($curseq, $length));
        $doc = $config['params']['doc'];
        $trno = $head['trno'];
        $proformadate = date("Y-m-d");
        $this->coreFunctions->LogConsole($newinvno);
        $datas = [
          'trno' => $trno,
          'docno' => $newinvno,
          'dateid' => $proformadate,
          'seq' => $lastseq,
          'year' => $curyr,
        ];
        $this->coreFunctions->sbcinsert($tablename, $datas);
      }
    } else {
      $this->coreFunctions->execqry("delete from ppio_series  where trno=?", 'delete', [$head['trno']]);
    }

    $terms = 0;
    $data['due'] = $this->othersClass->computeterms($data['dateid'], $data['due'], $terms);
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
    $qry = "insert into " . $this->hhead . "(trno, doc, docno, clientid, errandtype, dateid, datereq, dateneed, due, 
      assignid, users, createdate, createby, editby, editdate, viewby, viewdate, lockdate,
      contactperson, companyaddress, rem, company, contact)
      SELECT head.trno, head.doc, head.docno, head.clientid, head.errandtype, head.dateid, head.datereq, head.dateneed, head.due, 
      head.assignid, head.users, head.createdate, head.createby, head.editby, head.editdate, head.viewby, head.viewdate, head.lockdate,
      head.contactperson, head.companyaddress, head.rem, head.company, head.contact
      FROM " . $this->head . " as head 
      left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno
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
        encodeddate,qa,encodedby,editdate,editby,sku,refx,linex,cdrefx,cdlinex,rem,stageid, projectid,itemname,brand, model, serialno)
        SELECT trno, line, itemid, uom,whid,loc,ref,disc,cost, qty,void,rrcost, rrqty, ext,
        encodeddate,qa, encodedby,editdate,editby,sku,refx,linex,cdrefx,cdlinex,rem,stageid, projectid,itemname ,brand, model, serialno
        FROM " . $this->stock . " where trno =?";
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
      //if($posthead){
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

    $qry = "insert into " . $this->head . "(trno, doc, docno, clientid, errandtype, dateid, datereq, dateneed, due, 
    assignid, users, createdate, createby, editby, editdate, viewby, viewdate, lockdate,
    contactperson, companyaddress, rem, company, contact)
    SELECT head.trno, head.doc, head.docno, head.clientid, head.errandtype, head.dateid, head.datereq, head.dateneed, head.due, 
    head.assignid, head.users, head.createdate, head.createby, head.editby, head.editdate, head.viewby, head.viewdate, head.lockdate,
    head.contactperson, head.companyaddress, head.rem, head.company, head.contact
    from " . $this->hhead . " as head 
    left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno
    where head.trno=? limit 1";
    //head
    if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {

      if (!$this->othersClass->unpostingstockinfotrans($config)) {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Unposting failed. There are issues with inventory.'];
      }

      $qry = "insert into " . $this->stock . "(
      trno,line,itemid,uom,whid,loc,ref,disc,
      cost,qty,void,rrcost,rrqty,ext,rem,encodeddate,qa,encodedby,editdate,editby,sku,refx,linex,cdrefx,cdlinex,stageid, projectid,itemname,brand, model, serialno)
      select trno, line, itemid, uom,whid,loc,ref,disc,cost, qty,void, rrcost, rrqty,
      ext,rem, encodeddate, qa, encodedby, editdate, editby,sku,refx,linex,cdrefx,cdlinex,stageid, projectid,itemname,brand, model, serialno
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
    $sqlselect = "select item.brand as brand,
    ifnull(mm.model_name,'') as model,
    item.itemid,
    stock.trno,
    stock.line,
    stock.refx,
    stock.linex,
    stock.cdrefx,
    stock.cdlinex,
    stock.sorefx,
    stock.solinex,
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
    case 
      when stock.void=0 
      then 'false' 
      else 'true' 
    end as void,
    round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0) = 0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
    stock.ref,
    stock.whid,
    warehouse.client as wh,
    warehouse.clientname as whname,
    stock.loc,
    item.brand,
    stock.rem, stock.stageid,st.stage,
    ifnull(uom.factor,1) as uomfactor,
    '' as bgcolor,
    case when stock.void=0 then '' else 'bg-red-2' end as errcolor,
    prj.name as stock_projectname,
    stock.projectid as projectid,
    item.subcode, item.partno, round(item.dqty, " . $this->companysetup->getdecimal('qty', $config['params']) . ") as boxcount ";
    return $sqlselect;
  }

  public function openstock($trno, $config)
  {
    $companyid = $config['params']['companyid'];
    $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $qty_dec = 0;
    }

    $qry = "select '' as bgcolor, trno, line, itemname, FORMAT(qty, " . $qty_dec . ") as qty, uom, brand, model, serialno
      from " . $this->stock . " 
      where trno = ?
      union all
      select '' as bgcolor, trno, line, itemname, FORMAT(qty, " . $qty_dec . ") as qty, uom, brand, model, serialno
      from " . $this->hstock . " 
      where trno = ? ";

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

    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = "select '' as bgcolor, trno, line, itemname, FORMAT(qty, " . $qty_dec . ") as qty, uom, brand, model, serialno
      from " . $this->stock . " 
      where trno = ? and line = ?
      union all
      select '' as bgcolor, trno, line, itemname, FORMAT(qty, " . $qty_dec . ") as qty, uom, brand, model, serialno
      from " . $this->hstock . " 
      where trno = ? and line = ?";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $line, $trno, $line]);
    return $stock;
  } // end function

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'additem':
        return $this->additem('insert', $config);
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
      case 'addrow':
        return $this->addrow($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function addrow($config)
  {
    $trno = $config['params']['trno'];

    $checking = $this->coreFunctions->datareader("select errandtype as value from tehead where trno = '" . $trno . "'");
    if ($checking != "PPIO") {
      return ['row' => [], 'status' => false, 'msg' => "Can't Add Item for Errand Transactions."];
    }

    $data = [];
    $data['trno'] = $trno;
    $data['line'] = 0;
    $data['itemname'] = '';
    $data['qty'] = 0;
    $data['uom'] = '';
    $data['brand'] = '';
    $data['model'] = '';
    $data['serialno'] = '';
    $data['bgcolor'] = 'bg-blue-2';

    return ['row' => $data, 'status' => true, 'msg' => 'New row added'];
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
        return ['status' => true, 'msg' => 'Successfully exported.', 'filename' => 'xxx', 'ext' => 'txt', 'csv' => 'abc' . "\t" . 'def' . "\t" . 'ghi' . "\t"];
        break;
      case 'print1':
        return $this->reportsetup($config);
        break;
      case 'navigation':
        return $this->othersClass->navigatedocno($config);
        break;
      default:
        return ['status' => false, 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
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
    $data = $this->additem('update', $config);

    if (!empty($data)) {
      return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    } else {
      return ['row' => $config['params']['row'], 'status' => false, 'msg' => 'Save Failed'];
    }
  }


  public function updateitem($config)
  {
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $this->addallitem('update', $config);
    }
    $data = $this->openstock($config['params']['trno'], $config);
    $data2 = json_decode(json_encode($data), true);
    $isupdate = true;
    $msg1 = '';
    $msg2 = '';

    if ($isupdate) {
      return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    } else {
      return ['inventory' => $data, 'status' => true, 'msg' => 'Please check, some items have zero qty (' . $msg1 . ' / ' . $msg2 . ')'];
    }
  } //end function

  // insert and update item
  public function additem($action, $config)
  {
    $companyid = $config['params']['companyid'];
    $trno = $config['params']['trno'];

    $data = [
      'trno' => $config['params']['trno'],
      'line' => $config['params']['data']['line'],
      'itemname' => $config['params']['data']['itemname'],
      'qty' => $config['params']['data']['qty'],
      'uom' => $config['params']['data']['uom'],
      'brand' => $config['params']['data']['brand'],
      'model' => $config['params']['data']['model'],
      'serialno' => $config['params']['data']['serialno'],
    ];

    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $data['editdate'] = $current_timestamp;
    $data['editby'] = $config['params']['user'];
    $itemname = $config['params']['data']['itemname'];
    $uom = $config['params']['data']['uom'];
    if ($data['line'] == 0) {
      $line = $this->getlastLine($trno) + 1;
      $data['encodeddate'] = $current_timestamp;
      $data['encodedby'] = $config['params']['user'];
      $data['line'] = $line;
      $insert = $this->coreFunctions->sbcinsert($this->stock, $data);
      if ($insert) {
        $config['params']['line'] = $line;
        $returnrow =  $this->openstockline($config);
        $this->logger->sbcwritelog($trno, $config, 'ITEM', 'ADD - Line:' . $line . ' Itemname:' . $itemname . ' Uom:' . $uom);
        return $returnrow;
      }
    } else {
      $update = $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $data['line']]);
      if ($update) {
        $returnrow =  $this->openstockline($config);
        $this->logger->sbcwritelog($trno, $config, 'ITEM', 'UPDATE - Line:' . $data['line'] . ' Itemname:' . $itemname . ' Uom:' . $uom);
        return $returnrow;
      }
    }
  } // end function

  public function addallitem($action, $config)
  {
    $companyid = $config['params']['companyid'];
    $trno = $config['params']['trno'];

    $data = [
      'trno' => $config['params']['trno'],
      'line' => $config['params']['data']['line'],
      'itemname' => $config['params']['data']['itemname'],
      'qty' => $config['params']['data']['qty'],
      'uom' => $config['params']['data']['uom'],
      'brand' => $config['params']['data']['brand'],
      'model' => $config['params']['data']['model'],
      'serialno' => $config['params']['data']['serialno'],
    ];

    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $data['editdate'] = $current_timestamp;
    $data['editby'] = $config['params']['user'];
    $itemname = $config['params']['data']['itemname'];
    if ($data['line'] == 0) {
      $line = $this->getlastLine($trno) + 1;
      $data['encodeddate'] = $current_timestamp;
      $data['encodedby'] = $config['params']['user'];
      $data['line'] = $line;
      $insert = $this->coreFunctions->sbcinsert($this->stock, $data);
      if ($insert) {
        $config['params']['line'] = $line;
        $returnrow =  $this->openstock($trno, $config);
        $this->logger->sbcwritelog($trno, $config, 'ITEM', 'ADD - Line:' . $line . ' Itemname:' . $itemname);
        return $returnrow;
      }
    } else {
      $update = $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $data['line']]);
      if ($update) {
        $returnrow =  $this->openstock($trno, $config);
        $this->logger->sbcwritelog($trno, $config, 'ITEM', 'UPDATE - Line:' . $data['line'] . ' Itemname:' . $itemname);
        return $returnrow;
      }
    }
  } // end function

  private function getlastLine($trno)
  {
    $last_line = $this->coreFunctions->datareader("
    select line as value 
    from " . $this->stock . " 
    where trno=? 
    union all 
    select line as value 
    from " . $this->hstock . " 
    where trno=? 
    order by value desc limit 1", [$trno, $trno]);

    return $last_line;
  }

  public function deleteallitem($config)
  {
    $trno = $config['params']['trno'];
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
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
    if ($line != 0) {
      $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' Itemname:' . $data[0]->itemname);
    }
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
        stock.itmeid left join uom on uom.itemid=item.itemid and
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


  public function getlatestprice($config)
  {
    $barcode = $config['params']['barcode'];
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
    $forex = $this->coreFunctions->getfieldvalue($this->head, "forex", "trno=?", [$trno]);
    if ($config['params']['companyid'] == 10) { //afti
      $qry = "select docno,left(dateid,10) as dateid,case " . $forex . " when 1 then round(amt,2) else amt4 end as amt,disc,uom from(select head.docno,head.dateid,
      stock.rrcost as amt,stock.uom,stock.disc,item.amt4
      from lahead as head
      left join lastock as stock on stock.trno = head.trno
      left join cntnum on cntnum.trno=head.trno
      left join item on item.itemid=stock.itemid
      where head.doc = 'RR' and cntnum.center = ?
      and item.barcode = ? and head.client = ?
      and stock.rrcost <> 0
      UNION ALL
      select head.docno,head.dateid,stock.rrcost as computeramt,
      stock.uom,stock.disc ,item.amt4 from glhead as head
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
      $qry = "select docno,left(dateid,10) as dateid,round(amt,2) as amt,disc,uom from(select head.docno,head.dateid,
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

    return $this->coreFunctions->execqry("update stages set po=" . $qty . " where projectid = " . $proj . " and subproject=" . $sub . " and stage=" . $stage, 'update');
  }

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
        (stock.iss-stock.poqa) as iss,stock.isamt,item.amt4 as tpdollar,
        round((stock.iss-stock.poqa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
        stock.disc,stock.loc,stock.expiry
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
    $rows = [];
    $msg = '';
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.amt,
        (stock.iss-stock.poqa) as iss,stock.isamt,
        round((stock.iss-stock.poqa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
        stock.disc,stock.loc,stock.expiry,item.amt4 as tpdollar
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
    $forex  = $this->coreFunctions->getfieldvalue($this->head, "forex", "trno=?", [$trno]);
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
      select concat(stock.trno,stock.line) as keyid, stock.trno, stock.line, stock.itemid, item.barcode, item.itemname, stock.uom, so.docno, date(head.dateid) as dateid,
      (stock.iss-(stock.qa+stock.sjqa+stock.poqa)) as iss,stock.isamt, stock.disc,stock.amt,stock.ext,
      FORMAT(((stock.iss-(stock.qa+stock.sjqa+stock.poqa))/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,item.amt4 as tpdollar
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
          if ($config['params']['companyid'] == 10) { //afti
            if (floatval($forex) != 1) {
              $config['params']['data']['amt'] = $data[$key2]->tpdollar;
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
    $forex  = $this->coreFunctions->getfieldvalue($this->head, "forex", "trno=?", [$trno]);
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
      select concat(stock.trno,stock.line) as keyid, stock.trno, stock.line, stock.itemid, item.barcode, item.itemname, stock.uom, so.docno, date(head.dateid) as dateid,
      (stock.iss-(stock.qa+stock.sjqa+stock.poqa)) as iss,stock.isamt, stock.disc,stock.amt,stock.ext,
      FORMAT(((stock.iss-(stock.qa+stock.sjqa+stock.poqa))/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,item.amt4 as tpdollar
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
          if ($config['params']['companyid'] == 10) { //afti
            if (floatval($forex) != 1) {
              $config['params']['data']['amt'] = $data[$key2]->tpdollar;
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
    if ($companyid == 10 || $companyid != 12) { //afti, not afti usd
    } else {
      $this->logger->sbcviewreportlog($config);
    }

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
  // end

} //end class
