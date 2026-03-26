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

class entryflraddons
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'FLOOR ADDONS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'qtaddons';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  public $head = 'qthead';
  public $hhead = 'hqthead';
  private $logger;
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['trno', 'line', 'addons'];
  public $showclosebtn = false;


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
    $attrib = array('load' => 0);
    return $attrib;
  }

  public function createTab($config)
  {
    $companyid = $config['params']['companyid'];
    $trno = $config['params']['tableid'];
    $config['params']['trno'] = $trno;
    $isposted = $this->othersClass->isposted2($trno, 'transnum');
    $islocked = $this->othersClass->islocked($config);
    $gridcolumns = ['action', 'addons'];
    $stockbuttons = ['save', 'delete'];
    if ($isposted || $islocked) {
      $gridcolumns = ['addons'];
      $stockbuttons = [];
    }
    $tab = [$this->gridname => ['gridcolumns' => $gridcolumns]];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    if ($isposted || $islocked) {
      $obj[0][$this->gridname]['columns'][0]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][0]['type'] = 'input';
    } else {
      $obj[0][$this->gridname]['columns'][0]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";
    }
    return $obj;
  }


  public function createtabbutton($config)
  {
    $config['params']['trno'] = $config['params']['tableid'];
    $isposted = $this->othersClass->isposted2($config['params']['tableid'], 'transnum');
    $islocked = $this->othersClass->islocked($config);
    $tbuttons = [];
    if (!$isposted && !$islocked) $tbuttons = ['addrecord', 'saveallentry'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }


  public function add($config)
  {
    $id = $config['params']['tableid'];
    $data = [];
    $data['line'] = 0;
    $data['trno'] = $id;
    $data['bgcolor'] = 'bg-blue-2';
    $data['addons'] = '';
    return $data;
  }

  private function selectqry()
  {
    $qry = "line, trno";
    foreach ($this->fields as $key => $value) {
      $qry = $qry . ',' . $value;
    }
    return $qry;
  }

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    $tableid = $config['params']['tableid'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    $data['addontype'] = 0;
    if ($row['line'] == 0) {
      $data['createby'] = $config['params']['user'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($row['trno'], $line);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($row['trno'], $row['line']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    $tableid = $config['params']['tableid'];
    $msg = '';

    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }
        $data2['addontype'] = 0;
        if ($data[$key]['line'] == 0) {
          $line = $this->coreFunctions->insertGetId($this->table, $data2);
        } else {
          $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    if ($msg == '') {
      $msg = 'All saved successfully.';
    }
    return ['status' => true, 'msg' => $msg, 'data' => $returndata, 'row' => $returndata];
  }

  public function delete($config)
  {
    $companyid = $config['params']['companyid'];
    $row = $config['params']['row'];
    $tableid = $config['params']['tableid'];
    $qry = "delete from uom where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['trno'], $row['line']]);

    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }

  private function loaddataperrecord($trno, $line)
  {
    $qry = "select line, trno, addons, '' as bgcolor from qtaddons where trno=? and line=? and addontype=0 union all select line, trno, addons, '' as bgcolor from hqtaddons where trno=? and line=? and addontype=0";
    $data = $this->coreFunctions->opentable($qry, [$trno, $line, $trno, $line]);
    return $data;
  }

  public function loaddata($config)
  {
    $tableid = $config['params']['tableid'];
    $qry = "select line, trno, addons, '' as bgcolor from qtaddons where trno=? and addontype=0 union all select line, trno, addons, '' as bgcolor from hqtaddons where trno=? and addontype=0 order by line";
    $data = $this->coreFunctions->opentable($qry, [$tableid, $tableid]);
    return $data;
  }

  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
      case 'addons':
        $rowindex = $config['params']['index'];
        $lookupsetup = [
          'type' => 'single',
          'title' => 'Floor Addons',
          'style' => 'width:900px;max-width:900px;'
        ];
        $plotsetup = [
          'plottype' => 'plotgrid',
          'plotting' => ['addons' => 'addons']
        ];
        $cols = [
          ['name' => 'addons', 'label' => 'Floor Addons', 'align' => 'left', 'field' => 'addons', 'sortable' => true, 'style' => 'font-size:16px;']
        ];
        $qry = "select distinct addons from qtaddons where addontype=0 order by addons";
        $data = $this->coreFunctions->opentable($qry);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $rowindex];
        break;
      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
        break;
    }
  }
} //end class
