<?php

namespace App\Http\Classes\modules\inventory;

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
use Exception;

class da
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Daily Piece Rate';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false];
  public $tablenum = 'hrisnum';
  public $head = 'dahead';
  public $hhead = 'hdahead';
  public $stock = 'piecetrans';
  public $hstock = 'hpiecetrans';
  public $tablelogs = 'hrisnum_log';
  public $tablelogs_del = 'del_hrisnum_log';
  private $stockselect;

  private $fields = ['trno', 'docno', 'dateid', 'rem', 'type', 'whid'];
  private $except = ['trno', 'dateid'];
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

  public function __construct($params = null)
  {
    $this->btnClass = new buttonClass;
    $this->fieldClass = new txtfieldClass($params);
    $this->tabClass = new tabClass($params);;
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
      'view' => 6041,
      'edit' => 6042,
      'new' => 6043,
      'save' => 6044,
      'delete' => 6045,
      'print' => 6046,
      'lock' => 6047,
      'unlock' => 6048,
      'post' => 6049,
      'unpost' => 6050,
      'additem' => 6051,
      'edititem' => 6052,
      'deleteitem' => 6053
    );
    return $attrib;
  }


  public function createdoclisting($config)
  {
    $getcols = ['action', 'listdocument', 'liststatus', 'listdate',  'listcreateby', 'listeditby', 'listviewby'];
    foreach ($getcols as $key => $value) {
      $$value = $key;
    }
    $stockbuttons = ['view', 'diagram'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$liststatus]['name'] = 'statuscolor';
    $cols[$action]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';

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
    $limit = "limit 150";
    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
      $limit = "";
    }

    $dateid = "left(head.dateid,10) as dateid";
    if ($search != "") $limit = 'limit 150';

    switch ($itemfilter) {
      case 'draft':
        $condition = ' and num.postdate is null and head.lockdate is null ';
        break;

      case 'locked':
        $condition = ' and head.lockdate is not null and num.postdate is null ';;
        break;

      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;

      case 'all':
        $condition = ' ';
        break;
    }

    $lstatus = " case ifnull(head.lockdate,'') when '' then  'DRAFT' else 'LOCKED' end";
    $lscolor = "'red'";
    $dateid = "left(head.dateid,10) as dateid";
    $qry = "select head.trno,head.docno,$dateid,head.createby,head.editby,head.viewby, " . $lstatus . " as status, case ifnull(head.lockdate,'') when '' 
                  then $lscolor else 'green' end as statuscolor
     from " . $this->head . " as head 
     left join " . $this->tablenum . " as num on num.trno=head.trno 
     where head.doc=? and num.center=? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? "  . $condition . " " . $filtersearch . "
     union all
     select head.trno,head.docno,$dateid,head.createby,head.editby,head.viewby,'POSTED' as status,'grey' as statuscolor
     from " . $this->hhead . " as head 
     left join " . $this->tablenum . " as num on num.trno=head.trno 
     where head.doc=? and num.center=? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? "  . $condition . " " . $filtersearch . "

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
      'lock',
      'unlock',
      'post',
      'unpost',
      'logs',
      'edit',
      'backlisting',
      'toggleup',
      'toggledown',
      'help'
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

    return $buttons;
  } // createHeadbutton


  public function createTab($access, $config)
  {
    $column = [
      'action',
      'empcode',
      'empname',
      'category',
      'barcode',
      'itemname',
      'wh',
      'amt',
      'baseamt',
      'boxcount',
      'uom',
      'totaltp',
      'rate',
      'gprofit',
      'days',
      'dayn',
      'adays',
      'adjust',
      'payment',
      'fname',
      'foramt',
      'expensename',
      'budget',
      'actual',
      'paygroup'
    ];  //'uom', 
    foreach ($column as $key => $value) {
      $$value = $key;
    }

    $tab = [$this->gridname => ['gridcolumns' => $column]];
    $stockbuttons = ['save', 'delete'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['descriptionrow'] = [];

    $obj[0][$this->gridname]['columns'][$action]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $obj[0][$this->gridname]['columns'][$empcode]['type'] = 'lookup';
    $obj[0][$this->gridname]['columns'][$empcode]['lookupclass'] = 'lookupempgrid';
    $obj[0][$this->gridname]['columns'][$empcode]['action'] = 'lookupempgrid';
    $obj[0][$this->gridname]['columns'][$empcode]['label'] = 'Employee Code';
    $obj[0][$this->gridname]['columns'][$empcode]['align'] = 'left';
    $obj[0][$this->gridname]['columns'][$empcode]['style'] = 'width:180px;whiteSpace: normal;min-width:180px;';

    $obj[0][$this->gridname]['columns'][$empname]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$empname]['label'] = 'Employee Name';
    $obj[0][$this->gridname]['columns'][$empname]['align'] = 'left';
    $obj[0][$this->gridname]['columns'][$empname]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;';

    $obj[0][$this->gridname]['columns'][$category]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$category]['align'] = 'left';
    $obj[0][$this->gridname]['columns'][$category]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;';


    $obj[0][$this->gridname]['columns'][$barcode]['type'] = 'lookup';
    $obj[0][$this->gridname]['columns'][$barcode]['lookupclass'] = 'gridbarcode';
    $obj[0][$this->gridname]['columns'][$barcode]['action'] = 'lookupitem';
    $obj[0][$this->gridname]['columns'][$barcode]['label'] = 'Itemcode';
    $obj[0][$this->gridname]['columns'][$barcode]['align'] = 'left';
    $obj[0][$this->gridname]['columns'][$barcode]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';


    $obj[0][$this->gridname]['columns'][$itemname]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$itemname]['label'] = 'Item Description';
    $obj[0][$this->gridname]['columns'][$itemname]['align'] = 'left';
    $obj[0][$this->gridname]['columns'][$itemname]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;';

    $obj[0][$this->gridname]['columns'][$uom]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$uom]['label'] = 'UOM';
    $obj[0][$this->gridname]['columns'][$uom]['align'] = 'left';
    $obj[0][$this->gridname]['columns'][$uom]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';

    $obj[0][$this->gridname]['columns'][$rate]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$rate]['label'] = 'Rate';
    $obj[0][$this->gridname]['columns'][$rate]['align'] = 'left';
    $obj[0][$this->gridname]['columns'][$rate]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';

    $obj[0][$this->gridname]['columns'][$amt]['label'] = 'Amount';
    $obj[0][$this->gridname]['columns'][$amt]['align'] = 'left';
    $obj[0][$this->gridname]['columns'][$amt]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';
    $obj[0][$this->gridname]['columns'][$baseamt]['label'] = 'Pcs Work';
    $obj[0][$this->gridname]['columns'][$baseamt]['align'] = 'left';
    $obj[0][$this->gridname]['columns'][$baseamt]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';

    $obj[0][$this->gridname]['columns'][$boxcount]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$boxcount]['label'] = 'Box/Pcs';
    $obj[0][$this->gridname]['columns'][$boxcount]['align'] = 'left';
    $obj[0][$this->gridname]['columns'][$boxcount]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';


    $obj[0][$this->gridname]['columns'][$totaltp]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$totaltp]['label'] = 'Total Boxes';
    $obj[0][$this->gridname]['columns'][$totaltp]['align'] = 'left';
    $obj[0][$this->gridname]['columns'][$totaltp]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';

    $obj[0][$this->gridname]['columns'][$gprofit]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$gprofit]['label'] = 'Gross Rate';
    $obj[0][$this->gridname]['columns'][$gprofit]['align'] = 'left';
    $obj[0][$this->gridname]['columns'][$gprofit]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';

    $obj[0][$this->gridname]['columns'][$days]['label'] = 'Add Restday';
    $obj[0][$this->gridname]['columns'][$days]['align'] = 'left';
    $obj[0][$this->gridname]['columns'][$days]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';

    $obj[0][$this->gridname]['columns'][$dayn]['label'] = 'Add Night';
    $obj[0][$this->gridname]['columns'][$dayn]['align'] = 'left';
    $obj[0][$this->gridname]['columns'][$dayn]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';


    $obj[0][$this->gridname]['columns'][$adays]['label'] = 'Add Holiday';
    $obj[0][$this->gridname]['columns'][$adays]['align'] = 'left';
    $obj[0][$this->gridname]['columns'][$adays]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';


    $obj[0][$this->gridname]['columns'][$adjust]['label'] = 'Add Adjustment';
    $obj[0][$this->gridname]['columns'][$adjust]['align'] = 'left';
    $obj[0][$this->gridname]['columns'][$adjust]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';


    $obj[0][$this->gridname]['columns'][$payment]['label'] = 'Add 13th Mos';
    $obj[0][$this->gridname]['columns'][$payment]['readonly'] = false;
    $obj[0][$this->gridname]['columns'][$payment]['align'] = 'left';
    $obj[0][$this->gridname]['columns'][$payment]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';

    $obj[0][$this->gridname]['columns'][$fname]['label'] = 'Add Admin';
    $obj[0][$this->gridname]['columns'][$fname]['align'] = 'left';
    $obj[0][$this->gridname]['columns'][$fname]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';

    $obj[0][$this->gridname]['columns'][$foramt]['label'] = 'Less Canteen';
    $obj[0][$this->gridname]['columns'][$foramt]['align'] = 'left';
    $obj[0][$this->gridname]['columns'][$foramt]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';


    $obj[0][$this->gridname]['columns'][$expensename]['label'] = 'Less CA Canteen';
    $obj[0][$this->gridname]['columns'][$expensename]['align'] = 'left';
    $obj[0][$this->gridname]['columns'][$expensename]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';


    $obj[0][$this->gridname]['columns'][$budget]['label'] = 'Less CA Hahsy';
    $obj[0][$this->gridname]['columns'][$budget]['align'] = 'left';
    $obj[0][$this->gridname]['columns'][$budget]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';


    $obj[0][$this->gridname]['columns'][$actual]['label'] = 'Less Charge';
    $obj[0][$this->gridname]['columns'][$actual]['align'] = 'left';
    $obj[0][$this->gridname]['columns'][$actual]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';

    $obj[0][$this->gridname]['columns'][$paygroup]['label'] = 'Net Payroll';
    $obj[0][$this->gridname]['columns'][$paygroup]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$paygroup]['align'] = 'left';
    $obj[0][$this->gridname]['columns'][$paygroup]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['addrow', 'saveitem', 'deleteallitem'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['label'] = 'ADD';
    $obj[1]['label'] = 'SAVE ALL';
    $obj[2]['label'] = 'DELETE ALL';
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['docno', 'type'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'docno.label', 'Transaction#');
    data_set($col1, 'type.label', 'Type');
    data_set($col1, 'type.type', 'lookup');
    data_set($col1, 'type.action', 'lookuprandom');
    data_set($col1, 'type.lookupclass', 'lookup_shifttype');
    data_set($col1, 'type.class', 'sbccsreadonly');

    $fields = ['dateid', 'dwhname'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'dateid.label', 'Date of Request');

    $fields = ['rem'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'yourref.label', 'Reference No');

    $fields = ['updatenotes', 'updatetaskinfo'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'updatenotes.type', 'actionbtn');
    data_set($col4, 'updatenotes.label', 'GENERATE AJ');
    data_set($col4, 'updatenotes.confirm', true);
    data_set($col4, 'updatenotes.confirmlabel', 'Generate AJ?');
    data_set($col4, 'updatenotes.access', 'save');
    data_set($col4, 'updatenotes.lookupclass', 'stockstatusposted');
    data_set($col4, 'updatenotes.action', 'generateaj');


    data_set($col4, 'updatetaskinfo.type', 'actionbtn');
    data_set($col4, 'updatetaskinfo.label', 'GENERATE APV');
    data_set($col4, 'updatetaskinfo.confirm', true);
    data_set($col4, 'updatetaskinfo.confirmlabel', 'Generate APV?');
    data_set($col4, 'updatetaskinfo.access', 'save');
    data_set($col4, 'updatetaskinfo.lookupclass', 'stockstatusposted');
    data_set($col4, 'updatetaskinfo.action', 'generateapv');

    return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4];
  }



  public function createnewtransaction($docno, $params)
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['type'] = '';
    $data[0]['dwhname'] = '';
    $data[0]['wh'] = $this->companysetup->getwh($params);
    $name = $this->coreFunctions->datareader("select clientname as value from client where client='" . $data[0]['wh'] . "'");
    $data[0]['whname'] = $name;
    $data[0]['rem'] = '';
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
    $qryselect = "select num.center,head.trno, head.docno,head.type,head.rem,date(head.dateid) as dateid, 
                  head.whid, warehouse.clientname as whname,warehouse.client as wh  ";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client as warehouse on warehouse.clientid = head.whid
        where head.trno = ? and num.center = ? 
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client as warehouse on warehouse.clientid = head.whid
        where head.trno = ? and num.center = ? ";

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
      $hidetabbtn = [];
      $clickobj = [];
      $hideobj = [];

      $hideobj['updatenotes'] = $isposted ? false : true;
      $hideobj['updatetaskinfo'] = $isposted ? false : true;


      return  [
        'head' => $head,
        'griddata' => ['inventory' => $stock],
        'islocked' => $islocked,
        'isposted' => $isposted,
        'isnew' => false,
        'status' => true,
        'msg' => $msg,
        'clickobj' => $clickobj,
        'hidetabbtn' => $hidetabbtn,
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
    if ($isupdate) {
      unset($this->fields[1]);
      unset($head['docno']);
    }
    $dateTables = ['dahead'];
    $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);
    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfieldFast($key, $data[$key], $lookups);
        } //end if
      }
    }

    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    if ($isupdate) {
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
      //$this->autocreatestock($config, $data);
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $insert = $this->coreFunctions->sbcinsert($this->head, $data);
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno']);
      //$this->autocreatestock($config, $data);
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

    $this->deleteallitem($config);
    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully loaded.'];
  } //end function


  private function getstockselect($config)
  {
    // $decimal = $this->companysetup->getdecimal('price', $config['params']);
    $sqlselect = "select 
    stock.trno,
    stock.line, cl.client as empcode, cl.clientname as empname, stock.empid,
    it.itemid, it.barcode, it.itemname, it.uom, wh.client as wh, wh.clientid as whid,
    round(ifnull(stock.amount,0),2) as amt,
    round(ifnull(stock.pcswork,0),2) as baseamt,
    round(if(it.payqty > 0 and stock.pcswork > 0, stock.pcswork / it.payqty, 0), 2) as totaltp,
    round(ifnull(stock.amount,0),2) as gprofit,
    round(ifnull(stock.restday,0),2) as days,
    round(ifnull(stock.night,0),2) as dayn,
    round(ifnull(stock.holiday,0),2) as adays,
    round(ifnull(stock.adjustmnt,0),2) as adjust,
    round(ifnull(stock.thirteenmos,0),2) as payment,
    round(ifnull(stock.admin,0),2) as fname,
    round(ifnull(stock.canteen,0),2) as foramt,
    round(ifnull(stock.cacanteen,0),2) as expensename,
    round(ifnull(stock.cahahsy,0),2) as budget,
    round(ifnull(stock.charge,0),2) as actual,
    round(
        (ifnull(stock.restday,0) + ifnull(stock.night,0) + ifnull(stock.holiday,0)
        + ifnull(stock.adjustmnt,0) + ifnull(stock.thirteenmos,0) + ifnull(stock.admin,0))
        - (ifnull(stock.canteen,0) + ifnull(stock.cacanteen,0)
        + ifnull(stock.cahahsy,0) + ifnull(stock.charge,0))
    ,2) as paygroup,
    round(ifnull(it.payrate,0),4) as rate,round(ifnull(it.payqty,0),2) as boxcount,ifnull(icat.name,'') as category,
    it.defcost,
    '' as bgcolor";
    return $sqlselect;
  }

  public function openstock($trno, $config)
  {
    $sqlselect = $this->getstockselect($config);

    $qry = $sqlselect . "
    FROM $this->stock as stock
    left join client as cl on cl.clientid=stock.empid
    left join item as it on it.itemid=stock.itemid
    left join client as wh on wh.clientid=stock.whid
    left join itemcategory as icat on icat.line=it.category
    where stock.trno =?
      UNION ALL
    " . $sqlselect . "
    FROM $this->hstock as stock
    left join client as cl on cl.clientid=stock.empid
    left join item as it on it.itemid=stock.itemid
    left join client as wh on wh.clientid=stock.whid
    left join itemcategory as icat on icat.line=it.category
    where stock.trno =?";

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
      left join client as cl on cl.clientid=stock.empid
      left join item as it on it.itemid=stock.itemid
      left join client as wh on wh.clientid=stock.whid
      left join itemcategory as icat on icat.line=it.category
      where stock.trno = ? and stock.line = ? ";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $stock;
  } // end function



  public function addrow($config)
  {
    $trno = $config['params']['trno'];
    $headwh = $this->coreFunctions->getfieldvalue($this->head, "whid", "trno=?", [$trno]);
    $data = [];
    $trno = $config['params']['trno'];
    $data['line'] = 0;
    $data['trno'] = $trno;
    $data['empid'] = 0;
    $data['empcode'] = '';
    $data['empname'] = '';
    $data['itemid'] = 0;
    $data['itemname'] = '';
    $data['barcode'] = '';
    $data['uom'] = '';
    $data['category'] = '';

    if ($headwh == '' || $headwh == 0) {
      $data['whid'] = 0;
      $data['wh'] = '';
    } else {
      $data['whid'] = $headwh;
      $data['wh'] = $this->coreFunctions->datareader("select client as value from client where clientid=?", [$headwh]);
    }

    $data['amt'] = '0.00';
    $data['baseamt'] = '0.00';
    $data['days'] = '0.0000';
    $data['dayn'] = '0.0000';
    $data['adays'] = '0.0000';
    $data['adjust'] = '0.0000';
    $data['payment'] = '0.0000';
    $data['fname'] = '0.0000';
    $data['foramt'] = '0.0000';
    $data['expensename'] = '0.0000';
    $data['budget'] = '0.0000';
    $data['actual'] = '0.0000';


    $data['bgcolor'] = 'bg-blue-2';
    return ['row' => $data, 'status' => true, 'msg' => 'New row added'];
  }

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'addrow':
        return $this->addrow($config);
        break;
      case 'additem':
        return $this->additem('insert', $config);
        break;
      case 'addallitem': // save all item selected from lookup
        return $this->addallitem($config);
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
      case 'print1':
        return $this->reportsetup($config);
        //return ['status'=>true,'msg'=>'Please check stockstatusposted ('.$config['params']['action'].')'];
        break;
      case 'generateaj':
        return $this->generateaj($config);
        break;
      case 'generateapv':
        return $this->generateapv($config);
        break;
      default:
        return ['status' => false, 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }


  // Each DA line (piecetrans) becomes one AJ stock line:
  // refx = DA trno, linex = DA line, qty = pcswork / item.payqty (total boxes)
  public function generateaj($config)
  {
    $datrno = $config['params']['trno'];
    $user = $config['params']['user'];

    $doc = 'AJ';
    $path = 'App\Http\Classes\modules\inventory\\' . strtolower($doc);
    $defaultContra = 'IS1';
    $createdate = $this->othersClass->getCurrentTimeStamp();
    $dateid = $this->othersClass->getCurrentDate();

    //stop if an AJ (draft or posted) was already generated from this DA
    $existing = $this->coreFunctions->datareader("select trno as value from lastock where refx=? union all select trno as value from glstock where refx=? limit 1", [$datrno, $datrno]);
    if (!empty($existing)) {
      return ['status' => false, 'msg' => 'Failed to generate AJ, already existing.'];
    }

    //get the DA header
    $hdahead = $this->coreFunctions->opentable("select head.docno, ifnull(wh.client,'') as wh from " . $this->hhead . " as head left join client as wh on wh.clientid=head.whid where head.trno=?", [$datrno]);
    if (empty($hdahead)) {
      return ['status' => false, 'msg' => 'Failed to generate AJ, DA not found.'];
    }
    $dadocno = $hdahead[0]->docno;
    $headwh = $hdahead[0]->wh;

    //get the DA lines
    $qry = "select p.line, p.itemid, ifnull(wh.client,'') as wh,
                 it.barcode, it.itemname, it.uom, it.payqty, p.pcswork,
                 ifnull(it.defcost,0) as amt
          from " . $this->hstock . " as p
          left join item as it on it.itemid=p.itemid
          left join client as wh on wh.clientid=p.whid
          where p.trno=? order by p.line";
    $dalines = $this->coreFunctions->opentable($qry, [$datrno]);

    //compute qty, keep only valid lines, merge same item + uom + wh, list the skipped ones
    $rows = [];
    $skipped = [];
    foreach ($dalines as $d) {
      $linewh = $d->wh;
      if ($linewh == '') {
        $linewh = $headwh;
      }

      // BOX o BOXES ang uom ng item?
      $isbox = in_array(strtoupper(trim($d->uom)), ['BOX', 'BOXES']);

      // BOX/BOXES = total boxes, iba (PCS etc.) = as is, pcswork
      if ($isbox) {
        $d->qty = ($d->payqty > 0 && $d->pcswork > 0) ? round($d->pcswork / $d->payqty, 2) : 0;
      } else {
        $d->qty = round($d->pcswork, 2);
      }

      if ($d->qty <= 0) {
        array_push($skipped, 'Line ' . $d->line . ' (' . $d->itemname . ': no pcs work or box qty)');
      } elseif ($d->uom == '') {
        array_push($skipped, 'Line ' . $d->line . ' (' . $d->itemname . ': no UOM)');
      } elseif ($linewh == '') {
        array_push($skipped, 'Line ' . $d->line . ' (' . $d->itemname . ': no warehouse)');
      } else {
        $d->wh = $linewh;
        // kung pareho ang item + uom + wh, i-add ang qty
        $key = $d->itemid . '|' . $d->uom . '|' . $d->wh;
        if (isset($rows[$key])) {
          $rows[$key]->qty += $d->qty;
          $rows[$key]->parts[] = ['line' => $d->line, 'qty' => $d->qty]; //para sa pag update ng dqty
        } else {
          $d->parts = [['line' => $d->line, 'qty' => $d->qty]]; //para sa pag update ng dqty
          $rows[$key] = $d;
        }
      }
    }
    $rows = array_values($rows);

    if (empty($rows)) {
      $msg = 'Nothing to generate.';
      if (!empty($skipped)) {
        $msg = $msg . ' Skipped: ' . implode('; ', $skipped);
      }
      return ['status' => false, 'msg' => $msg];
    }

    $ajtrno = 0;
    $docno = '';
    $oldtablelogs = $config['docmodule']->tablelogs;

    try {
      // create the AJ document number
      $ajtrno = $this->othersClass->generatecntnum($config, "cntnum", 'AJ', 'AJ');
      if ($ajtrno == -1) {
        $ajtrno = 0;
        return ['status' => false, 'msg' => 'Failed to generate AJ document number.'];
      }
      $docno = $this->coreFunctions->getfieldvalue("cntnum", 'docno', "trno=?", [$ajtrno]);

      // create the AJ header
      $head = [
        'trno' => $ajtrno,
        'doc' => $doc,
        'docno' => $docno,
        'dateid' => $dateid,
        'wh' => $headwh,
        'yourref' => $dadocno,
        'ourref' => '',
        'rem' => 'Generated from ' . $dadocno,
        'contra' => $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', [$defaultContra]),
        'createdate' => $createdate,
        'createby' => $user
      ];
      $lahead = $this->coreFunctions->sbcinsert('lahead', $head);
      if (!$lahead) {
        throw new Exception('Failed to insert AJ header.');
      }
      $this->logger->sbcwritelog($ajtrno, $config, 'CREATE', 'AUTO-GENERATED ' . $docno . ' from ' . $dadocno, 'table_log');

      // add the stock lines using the AJ module (separate config copy)
      $ajconfig = $config;
      $ajconfig['params']['doc'] = $doc;
      $ajconfig['params']['trno'] = $ajtrno;

      // the AJ module writes its logs to docmodule->tablelogs, so point it to AJ's log table for now
      $config['docmodule']->tablelogs = 'table_log';

      foreach ($rows as $r) {
        $ajconfig['params']['barcode'] = $r->barcode;
        $ajconfig['params']['client'] = '';
        $ajconfig['params']['data']['itemid'] = $r->itemid;
        $ajconfig['params']['data']['uom'] = $r->uom;
        $ajconfig['params']['data']['disc'] = '';
        $ajconfig['params']['data']['qty'] = $r->qty;
        $ajconfig['params']['data']['wh'] = $r->wh;
        $ajconfig['params']['data']['loc'] = '';
        $ajconfig['params']['data']['expiry'] = '';
        $ajconfig['params']['data']['rem'] = '';
        $ajconfig['params']['data']['refx'] = $datrno;
        $ajconfig['params']['data']['linex'] = $r->line;
        $ajconfig['params']['data']['ref'] = $dadocno;
        $ajconfig['params']['data']['amt'] = $r->amt;

        $return = app($path)->additem('insert', $ajconfig);
        if (!$return['status']) {
          throw new Exception('Line ' . $r->line . ' (' . $r->itemname . '): ' . $return['msg']);
        }

        foreach ($r->parts as $p) {
          $served = $this->coreFunctions->execqry(
            "update " . $this->hstock . " set dqty=? where trno=? and line=?",
            'update',
            [$p['qty'], $datrno, $p['line']]
          );
          if ($served === false) {
            throw new Exception('DA line ' . $p['line'] . ' (' . $r->itemname . '): Failed to set served qty.');
          }
        }
      }

      // $this->coreFunctions->LogConsole('SETSERVE ' . $datrno . ' item=' . $r->itemid . ' whid=' . $return['row'][0]->whid);

      // post the AJ gamit ang AJ module
      $ajmodule = app($path);
      $postconfig = $config;
      $postconfig['docmodule'] = $ajmodule;
      $postconfig['params']['trno'] = $ajtrno;
      $postconfig['params']['doc'] = $doc;
      $post = $ajmodule->posttrans($postconfig);
      if (!$post['status']) {
        throw new Exception('Failed to post AJ. ' . (isset($post['msg']) ? $post['msg'] : ''));
      }

      // put back DA's own log table
      $config['docmodule']->tablelogs = $oldtablelogs;
    } catch (\Throwable $e) {

      // something failed: put back the log table, i-reset ang dqty, at alisin ang AJ na nagawa
      $config['docmodule']->tablelogs = $oldtablelogs;
      $this->coreFunctions->execqry("update " . $this->stock . " set dqty=0 where trno=?", 'update', [$datrno]);
      $this->coreFunctions->execqry("update " . $this->hstock . " set dqty=0 where trno=?", 'update', [$datrno]);
      if ($ajtrno != 0) {
        $this->deleteajdraft($ajtrno);
      }
      return ['status' => false, 'msg' => 'Failed to generate AJ. ' . $e->getMessage() . ' (line ' . $e->getLine() . ' in ' . basename($e->getFile()) . ')'];
    }

    $this->logger->sbcwritelog($datrno, $config, 'GENERATE AJ', 'AUTO-GENERATED ' . $docno, $this->tablelogs);

    $msg = 'Successfully generated and posted. AJ# ' . $docno;
    if (!empty($skipped)) {
      $msg = $msg . ' Skipped: ' . implode('; ', $skipped);
    }
    return ['trno' => $datrno, 'status' => true, 'msg' => $msg, 'reloadhead' => true];
  }

  // remove a half-made AJ
  private function deleteajdraft($ajtrno)
  {
    $this->coreFunctions->execqry("delete from lastock where trno=" . $ajtrno, 'delete');
    $this->coreFunctions->execqry("delete from costing where trno=" . $ajtrno, 'delete');
    $this->coreFunctions->execqry("delete from stockinfo where trno=" . $ajtrno, 'delete');
    $this->coreFunctions->execqry("delete from lahead where trno=" . $ajtrno, 'delete');
    $this->coreFunctions->execqry("delete from cntnum where trno=" . $ajtrno, 'delete');
  }

  // DEBIT : isang line kada empid, sum ng net payroll (kahit magkaiba ang itemid/whid), account alias 'EXES'
  // CREDIT: isang line, total ng lahat ng debit, account alias 'APES'
  public function generateapv($config)
  {
    $datrno = $config['params']['trno'];
    $user = $config['params']['user'];

    $doc = 'PV';
    $path = 'App\Http\Classes\modules\payable\pv';
    $createdate = $this->othersClass->getCurrentTimeStamp();
    $dateid = $this->othersClass->getCurrentDate();

    // DA header: docno at dateid
    $hdahead = $this->coreFunctions->opentable("select head.docno, date(head.dateid) as dateid from " . $this->hhead . " as head where head.trno=?", [$datrno]);

    $dadocno = $hdahead[0]->docno;
    $dadateid = $hdahead[0]->dateid; // date sa head ng DA, gamit sa postdate ng bawat line

    // stop kung may APV na (draft o posted) galing sa DA na ito
    $existing = $this->coreFunctions->datareader(
      "select trno as value from lahead where doc='PV' and yourref=?
       union all
       select trno as value from glhead where doc='PV' and yourref=? limit 1",
      [$dadocno, $dadocno],
      '',
      true
    );
    if ($existing != 0) {
      return ['status' => false, 'msg' => 'Failed to generate APV, already existing.'];
    }

    // accounts mula sa COA
    $exesacno = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['EXES']);
    $apesacno = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['APES']);
    if ($exesacno == '' || $apesacno == '') {
      return ['status' => false, 'msg' => "Please setup accounts with alias 'EXES' and 'APES' in COA."];
    }
    $exesname = $this->coreFunctions->getfieldvalue('coa', 'acnoname', 'acno=?', [$exesacno]);
    $apesname = $this->coreFunctions->getfieldvalue('coa', 'acnoname', 'acno=?', [$apesacno]);

    // net payroll kada linya (parehong formula ng 'Net Payroll' column / paygroup sa grid)
    $netpay = "(ifnull(p.restday,0) + ifnull(p.night,0) + ifnull(p.holiday,0)
              + ifnull(p.adjustmnt,0) + ifnull(p.thirteenmos,0) + ifnull(p.admin,0))
              - (ifnull(p.canteen,0) + ifnull(p.cacanteen,0)
              + ifnull(p.cahahsy,0) + ifnull(p.charge,0))";

    // isang row kada empid, sum ng lahat ng linya niya kahit iba ang itemid o whid
    $qry = "select p.empid, ifnull(cl.client,'') as empcode, ifnull(cl.clientname,'') as empname,
                   round(sum(" . $netpay . "),2) as netpay
            from " . $this->hstock . " as p
            left join client as cl on cl.clientid=p.empid
            where p.trno=?
            group by p.empid, cl.client, cl.clientname
            order by cl.clientname";
    $emps = $this->coreFunctions->opentable($qry, [$datrno]);

    // i-filter ang valid na employee
    $rows = [];
    $skipped = [];
    foreach ($emps as $e) {
      $e->netpay = round(floatval($e->netpay), 2);
      if ($e->empcode == '') {
        array_push($skipped, 'Empid ' . $e->empid . ' (no employee code)');
      } elseif ($e->netpay <= 0) {
        array_push($skipped, $e->empname . ' (net payroll ' . number_format($e->netpay, 2) . ')');
      } else {
        array_push($rows, $e);
      }
    }

    if (empty($rows)) {
      $msg = 'Nothing to generate.';
      if (!empty($skipped)) {
        $msg = $msg . ' Skipped: ' . implode('; ', $skipped);
      }
      return ['status' => false, 'msg' => $msg];
    }

    $pvtrno = 0;
    $docno = '';
    $totaldb = 0;
    $oldtablelogs = $config['docmodule']->tablelogs;

    try {
      // PV document number
      $pvtrno = $this->othersClass->generatecntnum($config, "cntnum", 'PV', 'PV');
      if ($pvtrno == -1) {
        $pvtrno = 0;
        return ['status' => false, 'msg' => 'Failed to generate APV document number.'];
      }
      $docno = $this->coreFunctions->getfieldvalue("cntnum", 'docno', "trno=?", [$pvtrno]);

      // PV header (walang vendor)
      $head = [
        'trno' => $pvtrno,
        'doc' => $doc,
        'docno' => $docno,
        'dateid' => $dadateid,
        'due' => $dadateid,
        'client' => '',
        'clientname' => '',
        'yourref' => $dadocno,
        'ourref' => '',
        'rem' => 'Generated from ' . $dadocno,
        'cur' => $this->companysetup->getdefaultcurrency($config['params']),
        'forex' => 1,
        'contra' => $apesacno,
        'createdate' => $createdate,
        'createby' => $user
      ];
      if (!$this->coreFunctions->sbcinsert('lahead', $head)) {
        throw new Exception('Failed to insert APV header.');
      }
      $this->logger->sbcwritelog($pvtrno, $config, 'CREATE', 'AUTO-GENERATED ' . $docno . ' from ' . $dadocno, 'table_log');

      // PV module config (hiwalay na kopya ng params)
      $pvconfig = $config;
      $pvconfig['params']['doc'] = $doc;
      $pvconfig['params']['trno'] = $pvtrno;

      // ang PV additem ay nagla-log sa docmodule->tablelogs, kaya ituro muna sa table_log
      $config['docmodule']->tablelogs = 'table_log';

      $pvmodule = app($path);

      // DEBIT: isang line kada employee, account EXES
      foreach ($rows as $r) {
        $pvconfig['params']['data'] = [
          'acno' => $exesacno,
          'acnoname' => $exesname,
          'client' => $r->empcode,
          'db' => $r->netpay,
          'cr' => 0,
          'fdb' => 0,
          'fcr' => 0,
          'postdate' => $dadateid, // date sa head ng DA
          'rem' => '',
          'ref' => $dadocno
        ];
        $return = $pvmodule->additem('insert', $pvconfig);
        if (!$return['status']) {
          throw new Exception($r->empname . ': ' . $return['msg']);
        }
        $totaldb += $r->netpay;
      }

      // CREDIT: isang line, total ng lahat ng debit, account APES
      $totaldb = round($totaldb, 2);
      $pvconfig['params']['data'] = [
        'acno' => $apesacno,
        'acnoname' => $apesname,
        'client' => '',
        'db' => 0,
        'cr' => $totaldb,
        'fdb' => 0,
        'fcr' => 0,
        'postdate' => $dadateid, // date sa head ng DA
        'rem' => '',
        'ref' => ''
      ];
      $return = $pvmodule->additem('insert', $pvconfig);
      if (!$return['status']) {
        throw new Exception('Credit line: ' . $return['msg']);
      }

      // i-post agad ang APV gamit ang PV module
      $postconfig = $config;
      $postconfig['docmodule'] = $pvmodule;
      $postconfig['params']['trno'] = $pvtrno;
      $postconfig['params']['doc'] = $doc;
      $post = $pvmodule->posttrans($postconfig);
      if (!$post['status']) {
        throw new Exception('Failed to post APV. ' . (isset($post['msg']) ? $post['msg'] : ''));
      }

      // ibalik ang log table ng DA
      $config['docmodule']->tablelogs = $oldtablelogs;
    } catch (\Throwable $e) {
      // may failed: ibalik ang log table at burahin ang kalahating APV
      $config['docmodule']->tablelogs = $oldtablelogs;
      if ($pvtrno != 0) {
        $this->deletepvdraft($pvtrno);
      }
      return ['status' => false, 'msg' => 'Failed to generate APV. ' . $e->getMessage() . ' (line ' . $e->getLine() . ' in ' . basename($e->getFile()) . ')'];
    }

    $this->logger->sbcwritelog($datrno, $config, 'GENERATE APV', 'AUTO-GENERATED ' . $docno, $this->tablelogs);

    $msg = 'Successfully generated and posted. APV# ' . $docno . ' (Total: ' . number_format($totaldb, 2) . ')';
    if (!empty($skipped)) {
      $msg = $msg . ' Skipped: ' . implode('; ', $skipped);
    }
    return ['trno' => $datrno, 'status' => true, 'msg' => $msg, 'reloadhead' => true];
  }

  // burahin ang kalahating APV
  private function deletepvdraft($pvtrno)
  {
    $this->coreFunctions->execqry("delete from ladetail where trno=?", 'delete', [$pvtrno]);
    $this->coreFunctions->execqry("delete from detailinfo where trno=?", 'delete', [$pvtrno]);
    $this->coreFunctions->execqry("delete from lahead where trno=?", 'delete', [$pvtrno]);
    $this->coreFunctions->execqry("delete from cntnum where trno=?", 'delete', [$pvtrno]);
  }

  public function updateperitem($config)
  {
    $trno = $config['params']['trno'];
    $row = $config['params']['row'];

    $line = $row['line'];
    $itemid = $row['itemid'];
    $empid = $row['empid'];
    $whid = isset($row['whid']) ? $row['whid'] : 0;

    // check before saving

    // no employee
    if (empty($empid)) {
      return ['row' => $row, 'status' => false, 'msg' => 'Employee is required.'];
    }

    // no item
    if (empty($itemid)) {
      return ['row' => $row, 'status' => false, 'msg' => 'Item is required.'];
    }

    // ame employee + same item + same warehouse already exists? (except my own line)
    $exist = $this->coreFunctions->getfieldvalue('piecetrans', 'line', "trno=? and empid=? and itemid=? and whid=? and line<>?", [$trno, $empid, $itemid, $whid, $line]);
    if (!empty($exist)) {
      return ['row' => $row, 'status' => false, 'msg' => 'Employee with this item and warehouse already exists.'];
    }

    // save
    $config['params']['data'] = $row;

    if ($line != 0) {
      $this->additem('update', $config);
      $data = $this->openstockline($config);
      return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    } else {
      $data = $this->additem('insert', $config);
      if ($data['status'] == true) {
        return ['row' => $data['data'], 'status' => true, 'msg' => 'Successfully saved.'];
      } else {
        return ['row' => $data['data'], 'status' => false, 'msg' => $data['msg']];
      }
    }
  }


  public function updateitem($config)
  {
    $trno = $config['params']['trno'];
    $pairlist = array();

    // check
    foreach ($config['params']['row'] as $value) {
      $line = $value['line'];
      $itemid = $value['itemid'];
      $empid = $value['empid'];
      $whid = isset($value['whid']) ? $value['whid'] : 0;

      if (empty($itemid) || empty($empid)) {
        return ['status' => false, 'msg' => 'Item and employee are required.'];
      }

      // $pair = $empid . '-' . $itemid;
      $pair = $empid . '-' . $itemid . '-' . $whid;
      $exist = $this->coreFunctions->getfieldvalue('piecetrans', 'line', "trno=? and empid=? and itemid=? and whid=? and line<>?", [$trno, $empid, $itemid, $whid, $line]);

      if (in_array($pair, $pairlist) || !empty($exist)) {
        return ['status' => false, 'msg' => 'Employee with this item and warehouse already exists.'];
      }
      $pairlist[] = $pair;
    }

    // Save
    foreach ($config['params']['row'] as $value) {
      $config['params']['data'] = $value;
      if ($value['line'] != 0) {
        $this->additem('update', $config);
      } else {
        $this->additem('insert', $config);
      }
    }

    // return
    $data = $this->openstock($trno, $config);
    return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
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

  // insert and update item
  public function additem($action, $config)
  {

    $trno = $config['params']['trno'];
    $companyid = $config['params']['companyid'];

    $line = $config['params']['data']['line'];
    $empid = $config['params']['data']['empid'];
    $empname = $config['params']['data']['empname'];
    $itemid = $config['params']['data']['itemid'];
    $whid = $config['params']['data']['whid'];
    $amount = $config['params']['data']['amt'];
    $pcswork = $config['params']['data']['baseamt'];

    $restday = $config['params']['data']['days'];
    $night = $config['params']['data']['dayn'];
    $holiday = $config['params']['data']['adays'];
    $adjustmnt = $config['params']['data']['adjust'];
    $thirteenmos = $config['params']['data']['payment'];
    $admin = $config['params']['data']['fname'];
    $canteen = $config['params']['data']['foramt'];
    $cacanteen = $config['params']['data']['expensename'];
    $cahahsy = $config['params']['data']['budget'];
    $charge = $config['params']['data']['actual'];


    $data = [
      'trno'  => $trno,
      'line' => $line,
      'empid' => $empid,
      'empname' => $empname,
      'itemid' => $itemid,
      'whid' => $whid,
      'amount' => $amount,
      'pcswork' => $pcswork,
      'restday' => $restday,
      'night' => $night,
      'holiday' => $holiday,
      'adjustmnt' => $adjustmnt,
      'thirteenmos' => $thirteenmos,
      'admin' => $admin,
      'canteen' => $canteen,
      'cacanteen' => $cacanteen,
      'cahahsy' => $cahahsy,
      'charge' => $charge

    ];

    $dateTables = ['piecetrans'];
    $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);

    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfieldFast($key, $data[$key], $lookups);
    }


    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $data['editdate'] = $current_timestamp;
    $data['editby'] = $config['params']['user'];

    if ($action == 'insert') {
      $data['encodeddate'] = $current_timestamp;
      $data['encodedby'] = $config['params']['user'];
      // dcode: NOT NULL, no default, unused here. Required on insert (strict mode), skip on update.
      $data['dcode'] = '';
      $data['dname'] = '';
      $qry = "select line as value from " . $this->stock . " where trno=? order by line desc limit 1";

      $line = $this->coreFunctions->datareader($qry, [$trno]);
      if ($line == '') {
        $line = 0;
      }
      $line = $line + 1;
      $data['line'] = $line;

      if ($this->coreFunctions->sbcinsert($this->stock, $data)) {

        $config['params']['line'] = $line;
        $data =  $this->openstockline($config);
        $itemname = $this->coreFunctions->getfieldvalue('item', "itemname", "itemid=?", [$itemid]);
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line: ' . $line . ' Employee Name: ' . $empname . ' Itemname: ' . $itemname);
        return ['status' => true, 'msg' => 'Successfully saved.', 'data' => $data];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.', 'data' => []];
      }
    } else if ($action == 'update') {
      return $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $data['line']]);
    }
  }


  public function deleteallitem($config)
  {
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
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = "delete from " . $this->stock . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
    $this->coreFunctions->execqry("delete from stockinfotrans where trno=? and line=?", 'delete', [$trno, $line]);

    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line: ' . $line . ' Employee Name: ' . $data[0]->empname . ' Itemname: ' . $data[0]->itemname);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
  } // end function


  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);
    if ($this->othersClass->isposted($config)) {
      return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
    }

    // head: dahead -> hdahead
    $qry = "insert into " . $this->hhead . " (trno, doc, docno, dateid, rem, type, whid,
                                           createdate, createby, editdate, editby,lockdate,lockuser, viewdate, viewby)
          select head.trno, head.doc, head.docno, head.dateid, head.rem, head.type, head.whid,
                 head.createdate, head.createby, head.editdate, head.editby,head.lockdate,head.lockuser, head.viewdate, head.viewby
          from " . $this->head . " as head
          where head.trno=? limit 1";
    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);

    if ($posthead) {
      // stock: piecetrans -> hpiecetrans
      $qry = "insert into " . $this->hstock . " (trno, line, empid, empname, itemid, whid, amount, pcswork,
                                              restday, night, holiday, adjustmnt, thirteenmos, admin,
                                              canteen, cacanteen, cahahsy, charge,
                                              dcode, dname, encodeddate, encodedby, editdate, editby)
            select trno, line, empid, empname, itemid, whid, amount, pcswork,
                   restday, night, holiday, adjustmnt, thirteenmos, admin,
                   canteen, cacanteen, cahahsy, charge,
                   dcode, dname, encodeddate, encodedby, editdate, editby
            from " . $this->stock . " where trno=?";
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        // update hrisnum
        $date = $this->othersClass->getCurrentTimeStamp();
        $data = ['postdate' => $date, 'postedby' => $config['params']['user'], 'statid' => 5];
        $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
        $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
        $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
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

    $qry = "select trno from " . $this->hstock . " where trno=? and dqty>0";
    $data = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($data)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, either already served or have item voided...'];
    }

    $dadocno = $this->coreFunctions->datareader('select docno as value from ' . $this->hhead . ' where trno=?', [$trno]);
    $apv = $this->coreFunctions->datareader(
      "select docno as value from lahead where doc='PV' and yourref=?
       union all
       select docno as value from glhead where doc='PV' and yourref=? limit 1",
      [$dadocno, $dadocno],
      '',
      true
    );

    if ($apv != 0) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, APV ' . $apv . ' already generated.'];
    }

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    // head: hdahead -> dahead
    $qry = "insert into " . $this->head . " (trno, doc, docno, dateid, rem, type, whid,
                                          createdate, createby, editdate, editby,lockdate,lockuser,viewdate, viewby)
          select head.trno, head.doc, head.docno, head.dateid, head.rem, head.type, head.whid,
                 head.createdate, head.createby, head.editdate, head.editby,head.lockdate,head.lockuser, head.viewdate, head.viewby
          from " . $this->hhead . " as head
          where head.trno=? limit 1";
    if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {

      // stock: hpiecetrans -> piecetrans
      $qry = "insert into " . $this->stock . " (trno, line, empid, empname, itemid, whid, amount, pcswork,
                                             restday, night, holiday, adjustmnt, thirteenmos, admin,
                                             canteen, cacanteen, cahahsy, charge,
                                             dcode, dname, encodeddate, encodedby, editdate, editby)
            select trno, line, empid, empname, itemid, whid, amount, pcswork,
                   restday, night, holiday, adjustmnt, thirteenmos, admin,
                   canteen, cacanteen, cahahsy, charge,
                   dcode, dname, encodeddate, encodedby, editdate, editby
            from " . $this->hstock . " where trno=?";
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null, postedby='', statid=0 where trno=?", 'update', [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
        $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
        return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
      } else {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, stock problems...'];
      }
    } else {
      return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, head problems...'];
    }
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
    $this->logger->sbcviewreportlog($config);

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
  // end

} //end class
