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

class entryminmax
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'MIN/MAX';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'itemlevel';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['center', 'itemid', 'branchid', 'min', 'max'];
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
    $attrib = array(
      'load' => 0
    );
    return $attrib;
  }

  public function createTab($config)
  {
    // $allow_update = $this->othersClass->checkAccess($config['params']['user'], 4872);
    // $companyid = $config['params']['companyid'];
    $itemid = $config['params']['tableid'];
    $item = $this->othersClass->getitemname($itemid);
    $this->modulename = $this->modulename . ' ~ ' . $item[0]->barcode . ' ~ ' . $item[0]->itemname;

    $tab = [
      $this->gridname => [
        'gridcolumns' => ['action', 'client', 'center', 'max', 'min']
      ]
    ];

    $stockbuttons = ['save', 'delete'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][0]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";

    // uom   
    $obj[0][$this->gridname]['columns'][1]['label'] = "Branch Code";
    $obj[0][$this->gridname]['columns'][1]['type'] = "label";
    $obj[0][$this->gridname]['columns'][2]['label'] = "Branch Name";
    $obj[0][$this->gridname]['columns'][2]['type'] = "label";
    $obj[0][$this->gridname]['columns'][3]['label'] = "Max Qty";
    $obj[0][$this->gridname]['columns'][4]['label'] = "Min Qty";

    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['addbranch'];
    // $allow_update = $this->othersClass->checkAccess($config['params']['user'], 4872);
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }

  public function add($config)
  {
    $id = $config['params']['tableid'];
    $data = [];
    $data['itemid'] = $id;
    $data['id'] = 0;
    $data['brandid'] = 0;
    $data['min'] = 0;
    $data['max'] = 0;
    $data['center'] = '';
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  private function selectqry()
  {
    $qry = "il.id, cl.client";
    foreach ($this->fields as $key => $value) {
      $qry = $qry . ', il.' . $value;
    }
    return $qry;
  }

  //A
  public function lookupsetup($config)
  {
    if ($config['params']['lookupclass2'] == 'addbranch') {
      return $this->lookupbranch($config);
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
        $data['id'] = 0;
        $data['itemid'] = $itemid;
        $data['branchid'] = $row['clientid'];
        $data['client'] = $row['client'];
        $data['center'] = $row['clientname'];
        $data['min'] = 0;
        $data['max'] = 0;
        $data['bgcolor'] = 'bg-blue-2';
        return ['status' => true, 'msg' => 'Add Branch success...', 'data' => $data];
        break;
    }
  } // end function

  public function lookupbranch($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Branch',
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'callback',
      'action' => 'addtogrid'
    );

    // lookup columns
    $cols = array();
    $col = array('name' => 'client', 'label' => 'Code', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;');
    array_push($cols, $col);

    $col = array('name' => 'clientname', 'label' => 'Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;');
    array_push($cols, $col);

    $qry = "select clientid,client,clientname from client where isbranch=1";
    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  } //end function

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    if ($row['id'] == 0) {
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($row['itemid'], $line);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['id' => $row['id']]) == 1) {
        $returnrow = $this->loaddataperrecord($row['itemid'], $row['id']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  public function delete($config)
  {
    $row = $config['params']['row'];
    $qry = "delete from itemlevel where itemid=? and id=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['itemid'], $row['id']]);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }

  private function loaddataperrecord($itemid, $line)
  {
    $select = $this->selectqry();
    $select = $select . ", '' as bgcolor";
    $qry = "select " . $select . " from itemlevel as il
    left join client as cl on il.branchid=cl.clientid
    where il.itemid=? and il.id=?";
    $data = $this->coreFunctions->opentable($qry, [$itemid, $line]);
    return $data;
  }

  public function loaddata($config)
  {
    $tableid = $config['params']['tableid'];
    // $center = $config['params']['center'];
    $select = $this->selectqry();
    $select = $select . ", '' as bgcolor";
    $qry = "select " . $select . " from itemlevel as il
    left join client as cl on il.branchid=cl.clientid
    where il.itemid=? order by id";

    $data = $this->coreFunctions->opentable($qry, [$tableid]);
    return $data;
  }
} //end class
