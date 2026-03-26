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

class entryppbranch
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Branch Lists';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'ppbranch';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['trno', 'clientid'];
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
    $cols = ['action', 'client', 'clientname', 'sync'];

    foreach ($cols as $key => $value) {
      $$value = $key;
    }

    $tab = [$this->gridname => ['gridcolumns' =>  $cols]];
    $stockbuttons = ['delete'];

    $trno = $config['params']['tableid'];
    $isposted = $this->othersClass->isposted2($trno, "transnum");

    if ($isposted) {
      $stockbuttons = [];
    }

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
    $obj[0][$this->gridname]['columns'][$client]['label'] = "Branch Code";
    $obj[0][$this->gridname]['columns'][$client]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$clientname]['label'] = "Branch Name";
    $obj[0][$this->gridname]['columns'][$clientname]['type'] = "label";

    if ($isposted) {
      $obj[0][$this->gridname]['columns'][$action]['type'] = "coldel";
    }
    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['addbranch'];
    $trno = $config['params']['tableid'];
    $isposted = $this->othersClass->isposted2($trno, "transnum");

    if ($isposted) {
      $tbuttons = [];
    }

    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function add($config)
  {
    $trno = $config['params']['tableid'];
    $data = [];
    $data['trno'] = $trno;
    $data['clientid'] = 0;
    $data['isok'] = 0;
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  private function selectqry()
  {
    $qry = "cl.client, cl.clientname, if(p.isok=1,'YES','NO') as sync";
    foreach ($this->fields as $key => $value) {
      $qry = $qry . ', p.' . $value;
    }
    return $qry;
  } //end function

  public function lookupsetup($config)
  {
    if ($config['params']['lookupclass2'] == 'addbranch') {
      return $this->lookupbranch($config);
    }
  } //end function

  public function lookupcallback($config)
  {
    $row = $config['params']['row'];
    $data = $this->save($config);

    if ($data['status']) {
      return ['status' => true, 'msg' => $data['msg'], 'data' => $data['data'][0]];
    } else {
      return ['status' => false, 'msg' => $data['msg']];
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
    $col = array('name' => 'client', 'label' => 'Branch Code', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;');
    array_push($cols, $col);

    $col = array('name' => 'clientname', 'label' => 'Branch Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;');
    array_push($cols, $col);

    $qry = "select " . $config['params']['tableid'] . " as trno, clientid,client,clientname from client where isbranch=1";
    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  } //end function

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    $trno = $config['params']['tableid'];

    $data = [
      'trno' => $trno,
      'clientid' => $row['clientid']
    ];
    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }

    $exist = $this->coreFunctions->getfieldvalue("ppbranch", "trno", "trno=? and clientid=?", [$data['trno'], $data['clientid']], '', true);
    if ($exist == 0) {
      if ($this->coreFunctions->sbcinsert($this->table, $data)) {
        $returnrow = $this->loaddataperrecord($data['trno'], $data['clientid']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'data' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      return ['status' => false, 'msg' => 'Branch ' . $row['clientname'] . ' was already added.'];
    }
  } //end function

  public function delete($config)
  {
    $row = $config['params']['row'];
    $qry = "delete from ppbranch where trno=? and clientid=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['trno'], $row['clientid']]);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  private function loaddataperrecord($trno, $clientid)
  {
    $select = $this->selectqry();
    $select = $select . ", '' as bgcolor";
    $qry = "select " . $select . " from ppbranch as p
    left join client as cl on p.clientid=cl.clientid
    where p.trno=? and p.clientid=?";
    $data = $this->coreFunctions->opentable($qry, [$trno, $clientid]);
    return $data;
  } //end function

  public function loaddata($config)
  {
    $tableid = $config['params']['tableid'];
    $select = $this->selectqry();
    $select = $select . ", '' as bgcolor";
    $qry = "select " . $select . " from ppbranch as p
    left join client as cl on p.clientid=cl.clientid
    where p.trno=? order by clientid";
    $data = $this->coreFunctions->opentable($qry, [$tableid]);
    return $data;
  } //end function
} //end class
