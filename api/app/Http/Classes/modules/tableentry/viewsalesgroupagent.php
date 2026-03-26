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

class viewsalesgroupagent
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'AGENT';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'client';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['client', 'clientname'];
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
    $attrib = array('load' => 0);
    return $attrib;
  }


  public function createTab($config)
  {
    $client = 0;
    $clientname = 1;
    $tab = [$this->gridname => ['gridcolumns' => ['client', 'clientname']]];

    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][$client]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][$client]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$client]['label'] = "Agent Code";

    $obj[0][$this->gridname]['columns'][$clientname]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][$clientname]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$clientname]['label'] = "Agent Name";

    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }

  public function add($config)
  {
    $id = $config['params']['tableid'];
    $data = [];
    $data['clientid'] = 0;
    $data['client'] = '';
    $data['clientname'] = '';
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
        $returnrow = $this->loaddataperrecord($row['groupname']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($row['groupname']);
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

  private function loaddataperrecord($groupname)
  {
    $qry = "
      select cl.client, cl.clientname
      from client as cl
      where groupid = ?
    ";
    $data = $this->coreFunctions->opentable($qry, [$groupname]);
    return $data;
  }

  public function loaddata($config)
  {
    $tableid = $config['params']['row']['line'];
    $center = $config['params']['center'];
    $row = $config['params']['row'];

    $qry = "
      select cl.client, cl.clientname
      from client as cl
      where groupid = ?
    ";
    $data = $this->coreFunctions->opentable($qry, [$row['groupname']]);
    return $data;
  }

  public function lookupsetup($config)
  {
  }

  public function lookupcallback($config)
  {
    $row = $config['params']['rows'];
    $data = [];
    foreach ($row  as $key2 => $value) {
      $config['params']['row']['line'] = 0;
      $config['params']['row']['itemid'] = $value['itemid'];
      $config['params']['row']['cmodelid'] = $value['tableid'];
      $config['params']['row']['bgcolor'] = 'bg-blue-2';
      $return = $this->save($config);
      if ($return['status']) {
        array_push($data, $return['row'][0]);
      }
    }

    return ['status' => true, 'msg' => 'Successfully added.', 'data' => $data];
  } // end function

} //end class
