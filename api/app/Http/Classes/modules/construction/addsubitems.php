<?php

namespace App\Http\Classes\modules\construction;

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
use App\Http\Classes\sbcdb\trigger;
use App\Http\Classes\sbcdb\waims;
use App\Http\Classes\sbcdb\customersupport;
use Symfony\Component\VarDumper\VarDumper;

class addsubitems
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'ITEMS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'subitems';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['itemid', 'substage', 'qty', 'amt', 'subactivity', 'stage'];
  public $showclosebtn = true;
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';

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

    if ($config['params']['doc'] == 'PM') {
      $tab = [$this->gridname => ['gridcolumns' => ['barcode', 'item', 'qty']]];
      $stockbuttons = [];

      $obj = $this->tabClass->createtab($tab, $stockbuttons);
      $obj[0][$this->gridname]['columns'][2]['label'] = "Quantity";
      $obj[0][$this->gridname]['columns'][0]['style'] = 'width:70px;whiteSpace: normal;min-width:70px; max-width: 100px;';
      $obj[0][$this->gridname]['columns'][1]['style'] = 'width:170px;whiteSpace: normal;min-width:70px; max-width: 180px;';
      $obj[0][$this->gridname]['columns'][2]['style'] = 'width:70px;whiteSpace: normal;min-width:70px; max-width: 70px;';
      $obj[0][$this->gridname]['columns'][3]['style'] = 'width:70px;whiteSpace: normal;min-width:70px; max-width: 70px;';
    } else {
      $tab = [$this->gridname => ['gridcolumns' => ['action', 'barcode', 'item', 'qty']]];
      $stockbuttons = ['save', 'delete'];

      $obj = $this->tabClass->createtab($tab, $stockbuttons);
      $obj[0][$this->gridname]['columns'][3]['label'] = "Quantity";
      $obj[0][$this->gridname]['columns'][1]['style'] = 'width:70px;whiteSpace: normal;min-width:70px; max-width: 100px;';
      $obj[0][$this->gridname]['columns'][2]['style'] = 'width:170px;whiteSpace: normal;min-width:70px; max-width: 180px;';
      $obj[0][$this->gridname]['columns'][3]['style'] = 'width:70px;whiteSpace: normal;min-width:70px; max-width: 70px;';
      $obj[0][$this->gridname]['columns'][4]['style'] = 'width:70px;whiteSpace: normal;min-width:70px; max-width: 70px;';
    }

    return $obj;
  }


  public function createtabbutton($config)
  {
    if ($config['params']['doc'] == 'PM') {
      $tbuttons = [];
    } else {
      $tbuttons = ['addsubitem', 'saveallentry', 'whlog'];
    }

    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }

  public function add($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['itemid'] = 0;
    $data['barcode'] = '';
    $data['itemname'] = '';
    $data['amt'] = 0;
    $data['qty'] = 0;
    $data['substage'] = $config['params']['sourcerow']['substage'];
    $data['stage'] = $config['params']['sourcerow']['stage'];
    $data['subactivity'] = $config['params']['sourcerow']['line'];
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  private function selectqry()
  {
    $qry = "line";
    foreach ($this->fields as $key => $value) {
      $qry = $qry . ',' . $value;
    }
    return $qry;
  }

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }
        $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data2['editby'] = $config['params']['user'];
        if ($data[$key]['line'] == 0) {
          $line = $this->coreFunctions->insertGetId($this->table, $data2);
          $this->logger->sbcmasterlog($data[$key]['subactivity'], $config, ' CREATE - ' . $this->getbarcode($data[$key]['itemid']) . '~' . $this->getitemname($data[$key]['itemid']), 0, 1);
        } else {
          $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
  } // end function 

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    if ($row['line'] == 0) {
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($line, $row['stage'], $row['substage'], $row['subactivity']);
        $this->logger->sbcmasterlog($row['subactivity'], $config, ' CREATE - ' . $this->getbarcode($row['itemid']) . '~' . $this->getitemname($row['itemid']), 0, 1);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($row['line'], $row['stage'], $row['substage'], $row['subactivity']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  public function delete($config)
  {
    $row = $config['params']['row'];
    $check = $this->coreFunctions->getfieldvalue("sostock", "trno", "substage=? and subactivity =? and itemid =?", [$row['substage'], $row['subactivity'], $row['itemid']]);
    if (strlen($check) > 0) {
      return ['status' => false, 'msg' => 'DELETE failed,already used.'];
    } else {
      $check = $this->coreFunctions->getfieldvalue("hsostock", "trno", "substage=? and subactivity =? and itemid =?", [$row['substage'], $row['subactivity'], $row['itemid']]);
      if (strlen($check) > 0) {
        return ['status' => false, 'msg' => 'DELETE failed,already used.'];
      } else {
        $qry = "delete from " . $this->table . " where line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
        $this->logger->sbcdelmaster_log($row['subactivity'], $config, 'REMOVE - ' . $this->getbarcode($row['itemid']) . '~' . $this->getitemname($row['itemid']), 1);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
      }
    }
  }


  private function loaddataperrecord($stockgrp_id, $stage, $substage, $subactivity)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select item.barcode,item.itemid,item.itemname as item,s.line,s.substage,s.qty,s.amt,'' as bgcolor,s.subactivity,s.stage from " . $this->table . " as s left join item on item.itemid = s.itemid 
       where s.stage =? and s.substage =? and s.subactivity = ? and s.line = ? order by s.line";
    $data = $this->coreFunctions->opentable($qry, [$stage, $substage, $subactivity, $stockgrp_id]);
    return $data;
  }

  public function loaddata($config)
  {
    $line = isset($config['params']['row']['line']) ? $config['params']['row']['line'] : $config['params']['sourcerow']['line'];
    $substage = isset($config['params']['row']['substage']) ? $config['params']['row']['substage'] : $config['params']['sourcerow']['substage'];
    $stage = isset($config['params']['row']['stage']) ? $config['params']['row']['stage'] : $config['params']['sourcerow']['stage'];
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select item.barcode,item.itemid,item.itemname as item,s.line,s.substage,s.qty,s.amt ,'' as bgcolor,s.subactivity,s.stage from " . $this->table . " as s left join item on item.itemid = s.itemid 
      where s.stage =? and s.substage =? and s.subactivity = ? order by s.line";
    $data = $this->coreFunctions->opentable($qry, [$stage, $substage, $line]);
    return $data;
  }

  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
      case 'whlog':
        return $this->lookuplogs($config);
        break;

      case 'addsubitem':
        return $this->lookupitem($config);
        break;

      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
        break;
    }
  } //end function


  public function lookupcallback($config)
  {
    switch ($config['params']['lookupclass2']) {
      case 'addtogrid':
        $itemid = $config['params']['sourcerow']['line'];
        $row = $config['params']['row'];
        $data = [];
        $data['line'] = 0;
        $data['itemid'] = $row['itemid'];
        $data['barcode'] = $row['barcode'];
        $data['item'] = $row['itemname'];
        $data['amt'] = 0;
        $data['qty'] = 1;
        $data['substage'] = $config['params']['sourcerow']['substage'];
        $data['stage'] = $config['params']['sourcerow']['stage'];
        $data['subactivity'] = $itemid;
        $data['bgcolor'] = 'bg-blue-2';
        return ['status' => true, 'msg' => 'Item was successfully added.', 'data' => $data];
        break;
    }
  } // end function


  public function lookupitem($config)
  {
    $lookupsetup = array(
      'type' => 'single',
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

    $col = array('name' => 'uom', 'label' => 'Unit Of Measurement', 'align' => 'left', 'field' => 'uom', 'sortable' => true, 'style' => 'font-size:16px;');
    array_push($cols, $col);

    $qry = "select itemid,barcode,itemname,uom from item where isinactive =0";
    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  } //end function



  public function lookuplogs($config)
  {
    $doc = strtoupper($config['params']['lookupclass']);
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'Entry Sub Activity Items Logs',
      'style' => 'width:1000px;max-width:1000px;'
    );

    // lookup columns
    $cols = array(
      array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')

    );

    $trno = strtoupper($config['params']['sourcerow']['line']);

    $qry = "
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from " . $this->tablelogs . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "' and trno = $trno
    union all
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from  " . $this->tablelogs_del . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "' and trno = $trno ";

    $qry = $qry . " order by dateid desc";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
  }


  private function getitemname($itemid)
  {
    $qry = "select itemname as value from item where itemid = ?";
    return $this->coreFunctions->datareader($qry, [$itemid]);
  }

  private function getbarcode($itemid)
  {
    $qry = "select barcode as value from item where itemid = ?";
    return $this->coreFunctions->datareader($qry, [$itemid]);
  }
} //end class
