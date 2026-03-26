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
use App\Http\Classes\builder\lookupclass;

class stockcardcompatible
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'COMPATIBLE';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'itemcmodels';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['itemid', 'cmodelid'];
  public $showclosebtn = true;
  private $lookupclass;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->lookupclass = new lookupclass;
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
    $allow_update = $this->othersClass->checkAccess($config['params']['user'], 4874);
    $companyid = $config['params']['companyid'];
    $itemid = $config['params']['tableid'];
    $item = $this->othersClass->getitemname($itemid);
    $this->modulename = $this->modulename . ' ~ ' . $item[0]->barcode . ' ~ ' . $item[0]->itemname;

    $tab = [$this->gridname => ['gridcolumns' => ['action', 'brand', 'model', 'classification']]];

    $stockbuttons = ['delete'];
    if ($companyid == 21) { // kinggeorge
      if (!$allow_update) {
        $stockbuttons = [];
      }
    }

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][0]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";

    $obj[0][$this->gridname]['columns'][1]['type'] = "label";
    $obj[0][$this->gridname]['columns'][2]['type'] = "label";
    $obj[0][$this->gridname]['columns'][3]['type'] = "label";

    return $obj;
  }

  public function createtabbutton($config)
  {
    $allow_update = $this->othersClass->checkAccess($config['params']['user'], 4874);
    $tbuttons = ['pickcompatible'];
    if ($config['params']['companyid'] == 21) { // kinggeorge
      if (!$allow_update) {
        $tbuttons = [];
      }
    }
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }

  public function add($config)
  {
    $id = $config['params']['tableid'];
    $data = [];
    $data['line'] = 0;
    $data['itemid'] = $id;
    $data['cmodelid'] = '0';
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    if ($row['line'] == 0) {
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($row['itemid'], $line);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($row['itemid'], $row['line']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  public function delete($config)
  {
    $row = $config['params']['row'];
    $qry = "delete from " . $this->table . " where itemid=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['itemid'], $row['line']]);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }

  private function loaddataperrecord($itemid, $line)
  {
    $select = "i.line, i.itemid, c.line as cmodelid, c.brand, c.model, c.classification";
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " 
    from itemcmodels as i
    left join cmodels as c on i.cmodelid = c.line
    where i.itemid=? and i.line=?";
    $data = $this->coreFunctions->opentable($qry, [$itemid, $line]);
    return $data;
  }

  public function loaddata($config)
  {
    $tableid = $config['params']['tableid'];
    $center = $config['params']['center'];
    $select = "i.line, i.itemid, c.line as cmodelid, c.brand, c.model, c.classification";
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " 
    from itemcmodels as i
    left join cmodels as c on i.cmodelid = c.line
    where i.itemid=? order by i.line";
    $data = $this->coreFunctions->opentable($qry, [$tableid]);
    return $data;
  }

  public function lookupsetup($config)
  {
    return $this->lookupclass->pickcompatible($config);
  }

  public function lookupcallback($config)
  {
    $id = $config['params']['tableid'];
    $row = $config['params']['rows'];
    $data = [];
    foreach ($row  as $key2 => $value) {
      $config['params']['row']['line'] = 0;
      $config['params']['row']['itemid'] = $id;
      $config['params']['row']['cmodelid'] = $value['line'];
      $config['params']['row']['bgcolor'] = 'bg-blue-2';
      $return = $this->save($config);
      if ($return['status']) {
        array_push($data, $return['row'][0]);
      }
    }

    return ['status' => true, 'msg' => 'Successfully added.', 'data' => $data];
  } // end function


} //end class
