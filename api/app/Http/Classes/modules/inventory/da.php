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
  public $stock = 'piecetrans';
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

  public $showfilterlabel = [];

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
    $qry = "select head.trno,head.docno,$dateid,head.createby,head.editby,head.viewby 
     from " . $this->head . " as head 
     left join " . $this->tablenum . " as num on num.trno=head.trno 
     where head.doc=? and num.center=? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $filtersearch . "
     order by dateid desc,docno desc " . $limit;

    $data = $this->coreFunctions->opentable($qry, [$doc, $center, $date1, $date2]);
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
      // 'lock',
      // 'unlock',
      // 'post',
      // 'unpost',
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
    $column = ['action', 'empcode', 'empname','barcode','itemname', 'uom', 'wh', 'amt', 'baseamt', 'totaltp', 
               'gprofit', 'days', 'dayn', 'adays', 'adjust', 'payment','fname', 'foramt', 'expensename',
               'budget', 'actual' ,'paygroup'];
    foreach ($column as $key => $value) {
      $$value = $key;
    }

    $tab = [$this->gridname => ['gridcolumns' => $column]];
    $stockbuttons = ['save', 'delete'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['descriptionrow'] = [];

    $obj[0][$this->gridname]['columns'][$action]['style'] = 'width:50px;whiteSpace: normal;min-width:50px;';
    $obj[0][$this->gridname]['columns'][$empcode]['type'] = 'lookup';
    $obj[0][$this->gridname]['columns'][$empcode]['lookupclass'] = 'lookupempgrid';
    $obj[0][$this->gridname]['columns'][$empcode]['action'] = 'lookupempgrid';
    $obj[0][$this->gridname]['columns'][$empcode]['label'] = 'Employee Code';
    $obj[0][$this->gridname]['columns'][$empname]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$empname]['label'] = 'Employee Name';

    $obj[0][$this->gridname]['columns'][$barcode]['type'] = 'lookup';
    $obj[0][$this->gridname]['columns'][$barcode]['lookupclass'] = 'gridbarcode';
    $obj[0][$this->gridname]['columns'][$barcode]['action'] = 'lookupitem';
    $obj[0][$this->gridname]['columns'][$barcode]['label'] = 'Itemcode';

    $obj[0][$this->gridname]['columns'][$itemname]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$itemname]['label'] = 'Item Description';

    $obj[0][$this->gridname]['columns'][$uom]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$uom]['label'] = 'UOM';

    // $obj[0][$this->gridname]['columns'][$rate]['type'] = 'label';
    // $obj[0][$this->gridname]['columns'][$rate]['label'] = 'Rate';

    $obj[0][$this->gridname]['columns'][$amt]['label'] = 'Amount';
    $obj[0][$this->gridname]['columns'][$baseamt]['label'] = 'Pcs Work';

    $obj[0][$this->gridname]['columns'][$totaltp]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$totaltp]['label'] = 'Total Boxes';

    $obj[0][$this->gridname]['columns'][$gprofit]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$gprofit]['label'] = 'Gross Rate';

    $obj[0][$this->gridname]['columns'][$days]['label'] = 'Add Restday';
    $obj[0][$this->gridname]['columns'][$dayn]['label'] = 'Add Night';
    $obj[0][$this->gridname]['columns'][$adays]['label'] = 'Add Holiday';
    $obj[0][$this->gridname]['columns'][$adjust]['label'] = 'Add Adjustment';

    $obj[0][$this->gridname]['columns'][$payment]['label'] = 'Add 13th Mos';
    $obj[0][$this->gridname]['columns'][$payment]['readonly'] = false;

    $obj[0][$this->gridname]['columns'][$fname]['label'] = 'Add Admin';

    $obj[0][$this->gridname]['columns'][$foramt]['label'] = 'Less Canteen';
    $obj[0][$this->gridname]['columns'][$expensename]['label'] = 'Less CA Canteen';
    $obj[0][$this->gridname]['columns'][$budget]['label'] = 'Less CA Hahsy';
    $obj[0][$this->gridname]['columns'][$actual]['label'] = 'Less Charge';

    $obj[0][$this->gridname]['columns'][$paygroup]['label'] = 'Net Payroll';
    $obj[0][$this->gridname]['columns'][$paygroup]['type'] = 'label';
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


    return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3];
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
    $table = $this->head;
    $tablenum = $this->tablenum;
    $qryselect = "select num.center,head.trno, head.docno,head.type,head.rem,date(head.dateid) as dateid, 
                  head.whid, warehouse.clientname as whname,warehouse.client as wh  ";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client as warehouse on warehouse.clientid = head.whid
        where head.trno = ? and num.center = ? ";

    $head = $this->coreFunctions->opentable($qry, [$trno, $center]);
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

      return  [
        'head' => $head,
        'griddata' => ['inventory' => $stock],
        // 'islocked' => $islocked,
        // 'isposted' => $isposted,
        'isnew' => false,
        'status' => true,
        'msg' => $msg,
        'clickobj' => $clickobj,
        'hidetabbtn' => $hidetabbtn
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
    $this->coreFunctions->execqry('delete from stockinfotrans where trno=?', 'delete', [$trno]);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully loaded.'];
  } //end function


  private function getstockselect($config)
  {
    $sqlselect = "select 
    stock.trno,
    stock.line, cl.client as empcode,cl.clientname as empname, stock.empid,
    it.itemid, it.barcode,it.itemname,it.uom,wh.client as wh, wh.clientid as whid,format(stock.amount,2) as amt,format(stock.pcswork,2) as baseamt,
    format(stock.pcswork/2, 2) as totaltp,format(stock.amount,2) as gprofit,
    format(stock.restday,2) as days, format(stock.night,2) as dayn,
    format(stock.holiday,2) as adays, format(stock.adjustmnt,2) as adjust,
    format(stock.thirteenmos,2) as payment, format(stock.admin,2) as fname,
    format(stock.canteen,2) as foramt, format(stock.cacanteen,2) as expensename,
    format(stock.cahahsy,2) as budget, format(stock.charge,2) as actual,
    format(
    (ifnull(stock.restday,0) + ifnull(stock.night,0) + ifnull(stock.holiday,0)
     + ifnull(stock.adjustmnt,0) + ifnull(stock.thirteenmos,0) + ifnull(stock.admin,0))
     - (ifnull(stock.canteen,0) + ifnull(stock.cacanteen,0)
       + ifnull(stock.cahahsy,0) + ifnull(stock.charge,0)),2) as paygroup,
  
  
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
    where stock.trno =?";

    $stock = $this->coreFunctions->opentable($qry, [$trno]);
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
      where stock.trno = ? and stock.line = ? ";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $stock;
  } // end function



  public function addrow($config)
  {
    $trno=$config['params']['trno'];
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

    if ($headwh == '' || $headwh == 0) {
      $data['whid'] = 0;
      $data['wh'] = '';
    } else {
      $data['whid'] = $headwh;
      $data['wh'] = $this->coreFunctions->datareader("select client as value from client where clientid=?",[$headwh] );
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
      default:
        return ['status' => false, 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }





  public function updateperitem($config)
  {
    $config['params']['data'] = $config['params']['row'];

    if ($config['params']['line'] != 0) {
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
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      if ($value['line'] != 0) {
        $this->additem('update', $config);
      } else {
        $this->additem('insert', $config);
      }
    }
    $data = $this->openstock($config['params']['trno'], $config);
    $data2 = json_decode(json_encode($data), true);
    $isupdate = true;
    $msg1 = '';
    $msg2 = '';
    // foreach ($data2 as $key => $value) {
    //   if ($data2[$key][$this->dqty] == 0) {
    //     $data[$key]->errcolor = 'bg-red-2';
    //     $isupdate = false;
    //   }
    // }
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
        //  $this->logger->sbcwritelog($trno,$config,'STOCK','ADD - Line: '.$line.' rrqty: '.$rrqty.' ref: '.$ref);
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
    $isallow = true;
    $trno = $config['params']['trno'];
    // $data = $this->coreFunctions->opentable('select refx,linex from ' . $this->stock . ' where trno=? and refx<>0', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from stockinfotrans where trno=?', 'delete', [$trno]);

    // foreach ($data as $key => $value) {
    //   if($data[$key]->refx!=0){
    //     $this->setserveditems($data[$key]->refx,$data[$key]->linex);
    //   }
    // }
    // $this->logger->sbcwritelog($trno,$config,'STOCK','DELETED ALL ITEMS');        
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
    $this->coreFunctions->execqry("delete from stockinfotrans where trno=? and line=?", 'delete', [$trno, $line]);
    //   if ($data[0]->refx !== 0) {
    //     $this->setserveditems($data[0]->refx, $data[0]->linex);
    //   }
    //  $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line: ' . $line . ' rrqty: ' . $data[0]->rrqty . ' ref: ' . $data[0]->ref );
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
    //} else {
    //    return ['status'=>false,'msg'=>'Cannot delete, already served'];
    //}
  } // end function



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

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config['params']['dataid']);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
  // end

} //end class
