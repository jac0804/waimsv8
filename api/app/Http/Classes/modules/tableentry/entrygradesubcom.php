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

class entrygradesubcom
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'GRADE SUB-COMPONENT';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'en_gssubcomponent';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['trno', 'line', 'gcsubcode', 'gcsubtopic', 'gcsubnoofitems', 'compid'];
  public $showclosebtn = true;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
  }

  public function getAttrib()
  {
    $attrib = ['load' => 0];
    return $attrib;
  }

  public function createTab($config)
  {
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'gcsubcode', 'gcsubtopic', 'gcsubnoofitems']]];
    $stockbuttons = ['save', 'delete'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][0]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";
    $obj[0][$this->gridname]['columns'][1]['type'] = "input";
    $obj[0][$this->gridname]['columns'][3]['label'] = "Percent";
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['addrecord', 'saveallentry'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function add($config)
  {
    $trno = $config['params']['row']['trno'];
    $line = $config['params']['row']['line'];
    $data = [];
    $data['trno'] = $trno;
    $data['compid'] = $line;
    $data['line'] = 0;
    $data['gcsubcode'] = '';
    $data['gcsubtopic'] = '';
    $data['gcsubnoofitems'] = '';
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  private function selectqry()
  {
    return "select trno, line, gcsubcode, topic as gcsubtopic, noofitems as gcsubnoofitems, compid ";
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
        $line = $this->coreFunctions->datareader("select line as value from " . $this->table . " where trno=? and compid=? order by line desc limit 1", [$data2['trno'], $data2['compid']]);
        if ($line == '') $line = 0;
        $line += 1;
        $data3 = [
          'trno' => $data2['trno'],
          'line' => $line,
          'gcsubcode' => $data2['gcsubcode'],
          'topic' => $data2['gcsubtopic'],
          'noofitems' => $data2['gcsubnoofitems'],
          'compid' => $data2['compid']
        ];
        if ($data[$key]['line'] == 0) {
          $line = $this->coreFunctions->insertGetId($this->table, $data3);
        } else {
          unset($data3['trno']);
          unset($data3['compid']);
          unset($data3['line']);
          $this->coreFunctions->sbcupdate($this->table, $data3, ['line' => $data2['line'], 'trno' => $data2['trno'], 'compid' => $data2['compid']]);
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
    $data2 = [
      'trno' => $data['trno'],
      'line' => $data['line'],
      'gcsubcode' => $data['gcsubcode'],
      'topic' => $data['gcsubtopic'],
      'noofitems' => $data['gcsubnoofitems'],
      'compid' => $data['compid']
    ];
    if ($data2['line'] == 0) {
      $line = $this->coreFunctions->datareader("select line as value from " . $this->table . " where trno=? and compid=? order by line desc limit 1", [$data2['trno'], $data2['compid']]);
      if ($line == '') $line = 0;
      $line += 1;
      $data2['line'] = $line;
      if ($this->coreFunctions->sbcinsert($this->table, $data2) == 1) {
        $returnrow = $this->loaddataperrecord($data2['trno'], $data2['compid'], $data2['line']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      if ($this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $row['line'], 'trno' => $row['trno'], 'compid' => $row['compid']]) == 1) {
        $returnrow = $this->loaddataperrecord($row['trno'], $row['compid'], $row['line']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  public function delete($config)
  {
    $row = $config['params']['row'];
    $schedtrno = $this->coreFunctions->getfieldvalue("en_gshead", "schedtrno", "trno=?", [$row['trno']]);
    $qry = "select gr.gccode as value from en_gegrades as gr left join en_gehead as h on h.trno=gr.trno where h.schedtrno=".$schedtrno." and gr.gccode='".$row['gcsubcode']."'";
    $check = $this->coreFunctions->opentable($qry);
    if (!empty($check)) return ['status' => false, 'msg' => 'Unable to delete, already has reference...'];
    $qry = "delete from en_gssubcomponent where trno=? and line=? and compid=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['trno'], $row['line'], $row['compid']]);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }

  private function checkuomtransaction($itemid, $line, $uom)
  {
    $uom2 = $this->coreFunctions->getfieldvalue('uom', 'uom', 'itemid=? and line=?', [$itemid, $line]);
    $barcode = $this->coreFunctions->getfieldvalue('item', 'barcode', 'itemid=?', [$itemid]);
    $qry = "
         select stock.trno from lastock as stock left join item on item.itemid=stock.itemid where item.barcode='" . $barcode . "' and stock.uom='" . $uom2 . "' 
         union all
         select stock.trno from postock as stock left join item on item.itemid=stock.itemid where item.barcode='" . $barcode . "' and stock.uom='" . $uom2 . "' 
         union all
         select stock.trno from hpostock as stock left join item on item.itemid=stock.itemid where item.barcode='" . $barcode . "' and stock.uom='" . $uom2 . "' 
         union all
         select stock.trno from sostock as stock left join item on item.itemid=stock.itemid where item.barcode='" . $barcode . "' and stock.uom='" . $uom2 . "'  
         union all
         select stock.trno from hsostock as stock left join item on item.itemid=stock.itemid where item.barcode='" . $barcode . "' and stock.uom='" . $uom2 . "'  
         union all
         select stock.trno from glstock as stock  where stock.itemid=" . $itemid . " and stock.uom='" . $uom2 . "'                                   
     ";
    $data = $this->coreFunctions->opentable($qry);
    if (!empty($data)) {
      return true;
    } else {
      return false;
    }
  }

  private function loaddataperrecord($trno, $compid, $line)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = $select . " from en_gssubcomponent where trno=? and compid=? and line=?";
    $data = $this->coreFunctions->opentable($qry, [$trno, $compid, $line]);
    return $data;
  }

  public function loaddata($config)
  {
    $trno = $config['params']['row']['trno'];
    $line = $config['params']['row']['line'];
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = $select . " from en_gssubcomponent where trno=? and compid=? order by line";
    $data = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $data;
  }
} //end class
