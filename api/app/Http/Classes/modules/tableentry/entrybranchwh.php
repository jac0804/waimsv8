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
use App\Http\Classes\posClass;


class entrybranchwh
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Warehouse';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'branchwh';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['clientid', 'whid', 'isdefault', 'isinactive'];
  public $showclosebtn = true;
  private $posClass;

  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->posClass = new posClass;
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
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'wh', 'whname', 'isdefault', 'isinactive']]];

    $stockbuttons = ['save'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns']['1']['readonly'] = true;
    $obj[0][$this->gridname]['columns']['1']['type'] = 'input';
    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = ['addrecord', 'saveallentry', 'syncall'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['action'] = 'lookupsetup';

    return $obj;
  }


  public function add($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['clientid'] = $config['params']['tableid'];
    $data['whid'] = 0;
    $data['wh'] = '';
    $data['whname'] = '';
    $data['isdefault'] = 0;
    $data['isinactive'] = 0;
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  private function selectqry()
  {
    $qry = "line,wh,whname,isdefault,isinactive,clientid,whid";
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
        $data2['dlock'] = $this->othersClass->getCurrentTimeStamp();
        if ($data[$key]['line'] == 0) {
          $exist = $this->coreFunctions->getfieldvalue("branchwh", "whid", "clientid=? and whid=? and isinactive =0 ", [$data[$key]['clientid'], $data[$key]['whid']]);
          if ($exist == '') {
            $line = $this->coreFunctions->insertGetId($this->table, $data2);
          }
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
    $line = 0;
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    $data['dlock'] = $this->othersClass->getCurrentTimeStamp();
    if ($row['line'] == 0) {
      $exist = $this->coreFunctions->getfieldvalue("branchwh", "whid", "clientid=? and whid=? and isinactive =0 ", [$data['clientid'], $data['whid']]);
      if ($exist == '') {
        $line = $this->coreFunctions->insertGetId($this->table, $data);
      }

      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($config, $line);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.',  'row' => []];
      }
    } else {
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($config, $row['line']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.', 'row' => []];
      }
    }
  } //end function

  public function delete($config)
  {
    $row = $config['params']['row'];

    $check = $this->coreFunctions->getfieldvalue("stages", "stage", "stage=?", [$row['line']]);
    if (strlen($check) > 0) {
      return ['status' => false, 'msg' => 'DELETE failed,already used...'];
    } else {
      $qry = "delete from " . $this->table . " where line=?";
      $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
      return ['status' => true, 'msg' => 'Successfully deleted.'];
    }
  }

  public function lookupsetup($config)
  {
    return $this->lookupwh($config);
  }


  public function lookupwh($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Warehouse',
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'callback',
      'action' => 'addtogrid'
    );

    $cols = array(
      array('name' => 'client', 'label' => 'Code', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'clientname', 'label' => 'Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $qry = "select client.clientid, client.client, 
        client.clientname
        from client where iswarehouse =1";

    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookupcallback($config)
  {
    switch ($config['params']['lookupclass2']) {
      case 'addtogrid':
        $data = [];
        $trno = $config['params']['tableid'];
        $row = $config['params']['row'];
        $exist = $this->coreFunctions->getfieldvalue("branchwh", "whid", "clientid=? and whid=? and isinactive =0 ", [$trno, $row['clientid']]);
        if (strlen($exist) == 0) {
          $data['line'] = 0;
          $data['clientid'] = $config['params']['tableid'];
          $data['whid'] = $row['clientid'];
          $data['wh'] = $row['client'];
          $data['whname'] = $row['clientname'];
          $data['isdefault'] = 'false';
          $data['isinactive'] = 'false';
          $data['bgcolor'] = 'bg-blue-2';
          return ['status' => true, 'msg' => 'Add Warehouse success...', 'data' => $data];
        } else {
          return [];
        }

        break;
    }
  }

  private function loaddataperrecord($config, $line)
  {
    $tableid = $config['params']['tableid'];
    $qry = "select b.line,b.clientid,b.whid,c.client as wh,c.clientname as whname,case b.isinactive when 1 then 'true' else 'false' end as isinactive,case b.isdefault when 1 then 'true' else 'false' end as isdefault,'' as bgcolor from " . $this->table . " as b left join client as c on c.clientid = b.whid where b.clientid =? and b.line=?";
    $data = $this->coreFunctions->opentable($qry, [$tableid, $line]);
    return $data;
  }

  public function loaddata($config)
  {
    $tableid = $config['params']['tableid'];
    $qry = "select b.line,b.clientid,b.whid,c.client as wh,c.clientname as whname,case b.isinactive when 1 then 'true' else 'false' end as isinactive,case b.isdefault when 1 then 'true' else 'false' end as isdefault,'' as bgcolor from " . $this->table . " as b left join client as c on c.clientid = b.whid where b.clientid =? order by line";
    $data = $this->coreFunctions->opentable($qry, [$tableid]);
    return $data;
  }



  public function tableentrystatus($config)
  {
    switch ($config['params']['action2']) {
      case 'syncallentry':
        $tableid = $config['params']['tableid'];
        $qry = "select station from branchstation where clientid =?";
        $station = $this->coreFunctions->opentable($qry, [$tableid]);
        $branchcode = $this->coreFunctions->getfieldvalue('client', 'client', 'clientid=?', [$tableid]);
        $qry = "select b.line,b.whid,b.clientid,c.client as wh,c.clientname as whname,b.isinactive as isinactive,b.isdefault as isdefault from " . $this->table . " as b left join client as c on c.clientid = b.whid where b.clientid =?";
        $data = $this->coreFunctions->opentable($qry, [$tableid]);
        $csv = $this->posClass->createcsv($data, 1);
        foreach ($station as $key => $value) {
          $this->posClass->ftpcreatefile($csv, $branchcode, $value->station, 'download', 'wh');
        }
        return ['status' => true, 'msg' => 'File created...', 'data' => $config['params']['data']];
        break;
      default:
        if (isset($config['params']['data'])) {
          return ['status' => true, 'msg' => 'No function yet', 'data' => $config['params']['data']];
        } else {
          return ['status' => true, 'msg' => 'No function yet', 'data' => []];
        }
        break;
    }
  } //end function


} //end class
