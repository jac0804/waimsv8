<?php

namespace App\Http\Classes\modules\tableentry;

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

class entrycomponent
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'COMPONENT';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'component';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $logger;
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['itemid', 'barcode', 'itemname', 'isqty', 'uom', 'qty', 'uomfactor', 'cost', 'amount', 'isloc'];
  public $showclosebtn = true;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = array(
      'load' => 0
    );
    return $attrib;
  }


  public function createTab($config)
  {
    $allow_update = $this->othersClass->checkAccess($config['params']['user'], 4870);
    $companyid = $config['params']['companyid'];

    $action = 0;
    $barcode = 1;
    $itemname = 2;
    $isqty = 3;
    $uom = 4;
    $cost = 5;
    $ext = 6;
    $isloc = 7;

    $itemid = $config['params']['tableid'];

    if ($config['params']['doc'] != 'BOM') {
      $item = $this->othersClass->getitemname($itemid);
      $this->modulename = $this->modulename . ' ~ ' . $item[0]->barcode . ' ~ ' . $item[0]->itemname;
    }

    $tab = [$this->gridname => ['gridcolumns' => ['action', 'barcode', 'itemname', 'isqty', 'uom', 'cost', 'ext', 'isloc']]];

    $stockbuttons = ['save', 'delete'];
    if ($companyid == 21) { // kinggeorge
      if (!$allow_update) {
        $stockbuttons = [];
      }
    }
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][$barcode]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
    $obj[0][$this->gridname]['columns'][$itemname]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$cost]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][$ext]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";

    // uom    
    $obj[0][$this->gridname]['columns'][$uom]['action'] = "lookupsetup";

    $obj[0][$this->gridname]['columns'][$barcode]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$itemname]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$itemname]['label'] = "ItemName";
    $obj[0][$this->gridname]['columns'][$ext]['label'] = "Amount";

    //
    $obj[0][$this->gridname]['columns'][$cost]['label'] = "Cost";
    $obj[0][$this->gridname]['columns'][$cost]['readonly'] = false;
    $obj[0][$this->gridname]['columns'][$ext]['readonly'] = true;

    $obj[0][$this->gridname]['columns'][$ext]['type'] = 'input';

    if ($companyid == 24) { //goodfound
      $obj[0][$this->gridname]['columns'][$isloc]['label'] = "Required Batch No";
    } else {
      $obj[0][$this->gridname]['columns'][$isloc]['type'] = "coldel";
    }

    if($companyid == 63){//ericco
      $obj[0][$this->gridname]['columns'][$cost]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$ext]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$isloc]['type'] = "coldel";
    }
    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $allow_update = $this->othersClass->checkAccess($config['params']['user'], 4870);
    $tbuttons = ['saveallentry', 'additemcomponent', 'masterfilelogs'];
    if ($config['params']['companyid'] == 21) { // kinggeorge
      if (!$allow_update) {
        $tbuttons = array_slice($tbuttons, 2, 1);
      }
    }
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }


  public function add($config)
  {
    $id = $config['params']['tableid'];
    $data = [];
    $data['itemid'] = $id;
    $data['line'] = 0;
    $data['uom'] = '';
    $data['barcode'] = '';
    $data['isqty'] = 0;
    $data['qty'] = 0;
    $data['cost'] = 0.00;
    $data['amount'] = 0.00;
    $data['ext'] = 0.00;
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  private function selectqry($params)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);

    $qry = "line";
    foreach ($this->fields as $key => $value) {
      switch ($value) {
        case 'cost':
          $qry = $qry . ",FORMAT(" . $value . "," . $decimalprice . ") as " . $value;
          break;
        case 'isqty':
          $qry = $qry . ",FORMAT(" . $value . "," . $decimalqty . ") as " . $value;
          break;
        case 'amount':
          $qry = $qry . ",FORMAT(" . $value . "," . $decimalcurr . ") as " . $value;
          break;
        case 'isloc':
          $qry = $qry . ",case when isloc=0 then 'false' else 'true' end as " . $value;
          break;
        default:
          $qry = $qry . ',' . $value;
          break;
      }
    }
    return $qry;
  }

  //A
  public function lookupsetup($config)
  {
    if ($config['params']['lookupclass2'] == 'additemcomponent') {
      return $this->lookupitem($config);
    } elseif ($config['params']['lookupclass2'] == 'uomstock') {
      return $this->lookupuom($config);
    } elseif ($config['params']['lookupclass2'] == "lookuplogs") {
      return $this->lookuplogs($config);
    }
    if ($config['params']['lookupclass2'] == 'saveallentry') {
      return $this->saveallentry($config);
    }
  } //end function


  //B
  public function lookupcallback($config)
  {
    switch ($config['params']['lookupclass2']) {
      case 'addtogrid':
        $itemid = $config['params']['tableid'];
        $row = $config['params']['row'];
        $data = [];
        $data['line'] = 0;
        $data['itemid'] = $itemid;
        $data['barcode'] = $row['barcode'];
        $data['itemname'] = $row['itemname'];
        $data['uom'] = $row['uom'];
        $data['isqty'] = 1;
        $data['qty'] = 1;
        $data['uomfactor'] = 1;
        $data['isloc'] = 'false';
        $data['bgcolor'] = 'bg-blue-2';

        if ($config['params']['companyid'] == 27 || $config['params']['companyid'] == 36) { //NTE,ROZLAB
          $qry = "select stock.rrcost as cost, head.dateid from lahead as head
                left join lastock as stock on stock.trno = head.trno
                left join cntnum on cntnum.trno=head.trno
                left join item on item.itemid = stock.itemid
                where head.doc in ('RR','CM','IS','AJ','TS') and cntnum.center = ?
                and item.barcode = ? and stock.cost<>0
                UNION ALL
                select stock.rrcost as cost, head.dateid from glhead as head
                left join glstock as stock on stock.trno = head.trno
                left join item on item.itemid = stock.itemid
                left join client on client.clientid = head.clientid
                left join cntnum on cntnum.trno=head.trno
                where head.doc in ('RR','CM','IS','AJ','TS') and cntnum.center = ?
                and item.barcode = ? and stock.cost<>0
                order by dateid desc limit 1";
          $latestcost = $this->coreFunctions->opentable($qry, [$config['params']['center'], $row['barcode'], $config['params']['center'], $row['barcode']]);
          if (empty($latestcost)) {
            $data['cost'] = 0.00;
          } else {
            $data['cost'] = $latestcost[0]->cost;
          }
        } else {
          $data['cost'] = 0.00;
        }
        $data['amount'] = 0.00;
        $data['ext'] = $data['cost'] * $data['isqty'];
        return ['status' => true, 'msg' => 'Item was successfully added.', 'data' => $data];
        break;
      case 'uomstock':

        break;
    }
  } // end function


  public function lookupitem($config)
  {
    $lookupsetup = array(
      'type' => 'singlesearch',
      'actionsearch' => 'searchitem',
      'title' => 'List of Products',
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'callback',
      'action' => 'addtogrid'
    );

    // lookup columns
    $cols = array();
    $col = array('name' => 'barcode', 'label' => 'Barcode', 'align' => 'left', 'field' => 'barcode', 'sortable' => true, 'style' => 'font-size:16px;');
    array_push($cols, $col);

    $col = array('name' => 'itemname', 'label' => 'Itemname', 'align' => 'left', 'field' => 'itemname', 'sortable' => true, 'style' => 'font-size:16px;');
    array_push($cols, $col);

    $col = array('name' => 'amt', 'label' => 'Amount', 'align' => 'right', 'field' => 'amt', 'sortable' => true, 'style' => 'font-size:16px;');
    array_push($cols, $col);

    return ['status' => true, 'msg' => 'ok', 'data' => [], 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  } //end function


  public function lookupuom($config)
  {
    //default
    $plotting = array('uom' => 'uom', 'uomfactor' => 'factor');
    $plottype = 'plotgrid';
    $title = 'List of Unit of Measurement';
    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );
    // lookup columns
    $cols = array();
    $col = array('name' => 'uom', 'label' => 'Unit of Measurement', 'align' => 'left', 'field' => 'uom', 'sortable' => true, 'style' => 'font-size:16px;');
    array_push($cols, $col);
    $barcode = $config['params']['row']['barcode'];
    $qry = "select uom,factor from uom where itemid=? and isinactive = 0";
    $itemid = $this->coreFunctions->getfieldvalue('item', 'itemid', 'barcode=?', [$barcode]);
    $data = $this->coreFunctions->opentable($qry, [$itemid]);
    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  } // end function

  public function saveallentry($config)
  {
    $data = [];
    $row = $config['params']['data'];
    $tableid = $config['params']['tableid'];
    $save = 0;
    foreach ($config['params']['data'] as $key => $row) {
      foreach ($this->fields as $key => $value) {
        $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
      }
      $data['qty'] = $data['isqty'] * $data['uomfactor'];
      if ($row['line'] == 0) {
        $line = $this->coreFunctions->insertGetId($this->table, $data);
        if ($line != 0) {
          $returnrow = $this->loaddataperrecord($config, $row['itemid'], $line);

          $params = $config;
          $params['params']['doc'] = strtoupper("entrycomponent_tab");
          $this->logger->sbcmasterlog(
            $tableid,
            $params,
            ' CREATE - LINE: ' . $line . ''
              . ', barcode: ' . $row['barcode']
              . ', uom: ' . $row['uom']
              . ', qty: ' . $row['isqty']
          );
          $save = 1;
        }
      } else {
        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];
        if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
          $returnrow = $this->loaddataperrecord($config, $row['itemid'], $row['line']);
          $save = 1;
        }
      }
    }
    if ($save == 1) {
      $returnrow = $this->loaddata($config);
      return ['status' => true, 'msg' => 'Successfully saved.', 'data' => $returnrow, 'reloadhead' => true];
    } else {
      return ['status' => false, 'msg' => 'Saving failed.'];
    }
  }

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    $tableid = $config['params']['tableid'];

    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    $data['qty'] = $data['isqty'] * $data['uomfactor'];
    if ($row['line'] == 0) {
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($config, $row['itemid'], $line);

        $params = $config;
        $params['params']['doc'] = strtoupper("entrycomponent_tab");
        $this->logger->sbcmasterlog(
          $tableid,
          $params,
          ' CREATE - LINE: ' . $line . ''
            . ', barcode: ' . $row['barcode']
            . ', uom: ' . $row['uom']
            . ', qty: ' . $row['isqty']
        );
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($config, $row['itemid'], $row['line']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow, 'reloadhead' => true];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  public function delete($config)
  {
    $row = $config['params']['row'];
    $qry = "delete from component where itemid=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['itemid'], $row['line']]);
    return ['status' => true, 'msg' => 'Successfully deleted.', 'reloadhead' => true];
  }


  private function loaddataperrecord($config, $itemid, $line)
  {
    $select = $this->selectqry($config);
    $select = $select . ",'' as bgcolor, format(isqty * cost, 2) as ext ";
    $qry = "select " . $select . " from component where itemid=? and line=?";
    $data = $this->coreFunctions->opentable($qry, [$itemid, $line]);
    return $data;
  }

  public function loaddata($config)
  {
    $tableid = $config['params']['tableid'];
    $center = $config['params']['center'];
    $select = $this->selectqry($config);
    $select = $select . ",'' as bgcolor, format(isqty * cost, 2) as ext ";
    $qry = "select " . $select . " from component where itemid=? order by line";
    $data = $this->coreFunctions->opentable($qry, [$tableid]);
    return $data;
  }

  public function lookuplogs($config)
  {
    $doc = strtoupper("entrycomponent_tab");
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'Logs',
      'style' => 'width:1000px;max-width:1000px;'
    );

    // lookup columns
    $cols = array(
      array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')

    );

    $trno = $config['params']['tableid'];

    $qry = "
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from " . $this->tablelogs . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "' and log.trno = '" . $trno . "'
    union all
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from  " . $this->tablelogs_del . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "' and log.trno = '" . $trno . "'";

    $qry = $qry . " order by dateid desc";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
  }
} //end class
