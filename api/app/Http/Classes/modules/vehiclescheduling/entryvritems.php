<?php

namespace App\Http\Classes\modules\vehiclescheduling;

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
use App\Http\Classes\lookup\constructionlookup;

class entryvritems
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'ITEMS / CARGO';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'vritems';
  private $htable = 'hvritems';
  private $othersClass;
  public $style = 'width:100%;max-width: 100%';
  private $fields = ['trno', 'line', 'itemname', 'uom', 'qty'];
  public $showclosebtn = true;
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->constructionlookup = new constructionlookup;
    $this->sqlquery = new sqlquery;
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = ['load' => 0];
    return $attrib;
  }

  public function createTab($config)
  {

    $action = 0;
    $itemname = 1;
    $uom = 2;
    $qty = 3;

    $tab = [$this->gridname => ['gridcolumns' => ['action', 'itemname', 'uom', 'qty']]];

    $stockbuttons = ['save', 'delete'];
    if ($config['params']['doc'] == 'VL') {
      $stockbuttons = [];
    }
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$itemname]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;';
    $obj[0][$this->gridname]['columns'][$uom]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $obj[0][$this->gridname]['columns'][$qty]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';

    $obj[0][$this->gridname]['columns'][$itemname]['label'] = 'Itemname';
    $obj[0][$this->gridname]['columns'][$itemname]['readonly'] = false;
    $obj[0][$this->gridname]['columns'][$itemname]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][$uom]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][$uom]['label'] = 'Uom';
    $obj[0][$this->gridname]['columns'][$qty]['label'] = 'Quantity';

    if ($config['params']['doc'] == 'VL') {
      $obj[0][$this->gridname]['columns'][$action]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$itemname]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$uom]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$qty]['readonly'] = true;
    }

    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    $this->modulename = 'ITEMS / CARGO - ' . $config['params']['row']['clientname'];
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['addrecord', 'saveallentry'];
    if ($config['params']['doc'] == 'VL') {
      $tbuttons = [];
    }
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function add($config)
  {
    $data = [];
    $trno = $config['params']['tableid'];
    $row = $config['params']['row'];

    $data['pline'] = 0;
    $data['trno'] = $row['trno'];
    $data['line'] = $row['line'];
    $data['itemname'] = '';
    $data['qty'] = 0;
    $data['uom'] = '';
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  public function lookupsetup($config)
  {

    $lookupclass2 = $config['params']['lookupclass2'];

    switch ($lookupclass2) {
      case 'addrow':
        break;
      case 'lookupitem':
        return $this->lookupitem($config);
        break;
      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
        break;
    }
  }

  private function selectqry($config)
  {
    $decimalqty = $this->companysetup->getdecimal('qty', $config['params']);

    return "
    select vr.pline as pline, vr.line, vr.trno, vr.uom, vr.itemname, round(vr.qty," . $decimalqty . ") as qty";
  }

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    $data['trno'] = $config['params']['tableid'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    if ($row['pline'] == 0) {
      $pline = $this->coreFunctions->insertGetId($this->table, $data);
      if ($pline != 0) {
        $returnrow = $this->loaddataperrecord($config, $pline);
        $this->logger->sbcwritelog($row['trno'], $config, ' CREATE - ITEMS', $row['itemname']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['pline' => $row['pline']]) == 1) {
        $returnrow = $this->loaddataperrecord($config, $row['pline']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

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

        if ($data[$key]['pline'] != 0) {
          $this->coreFunctions->sbcupdate($this->table, $data2, ['pline' => $data[$key]['pline']]);
        } else {
          $data2['trno'] = $data[$key]['trno'];
          $line = $this->coreFunctions->insertGetId($this->table, $data2);
          $this->logger->sbcwritelog($data[$key]['trno'], $config, ' CREATE - ITEMS', $data[$key]['itemname']);
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
  } // end function $$

  public function delete($config)
  {
    $row = $config['params']['row'];
    $qry = "delete from " . $this->table . " where trno=? and line=? and pline=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['trno'], $row['line'], $row['pline']]);
    $this->logger->sbcdel_log($row['trno'], $config, 'REMOVE - ITEMS', $row['itemname']);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }

  private function loaddataperrecord($config, $pline)
  {
    $trno = $config['params']['row']['trno'];
    $line = $config['params']['row']['line'];

    $qry = $this->selectqry($config);
    $qry = $qry . " ,'' as bgcolor  from " . $this->table . " as vr
    left join item on vr.itemid = item.itemid 
    where vr.trno = ? and vr.line = ? and vr.pline = ?
    order by pline
    ";
    $data = $this->coreFunctions->opentable($qry, [$trno, $line, $pline]);
    return $data;
  }

  public function loaddata($config)
  {
    $trno = $config['params']['row']['trno'];
    $line = $config['params']['row']['line'];

    $qry = $this->selectqry($config);
    $qry = $qry . ", '' as bgcolor  from " . $this->table . " as vr
    left join item on vr.itemid = item.itemid 
    where vr.trno = ? and vr.line = ?
    order by pline
    ";
    $data = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $data;
  }
} //end class
