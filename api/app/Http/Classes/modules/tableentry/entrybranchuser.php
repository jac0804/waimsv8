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

class entrybranchuser
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Users';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'branchusers';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['clientid', 'username', 'type', 'name', 'password', 'pincode', 'pincode2', 'isinactive'];
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
    $action = 0;
    $username = 1;
    $type = 2;
    $name = 3;
    $password = 4;
    $pincode = 5;
    $pincode2 = 6;
    $isinactive = 7;
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'username', 'type', 'name', 'password', 'pincode', 'pincode2', 'isinactive']]];

    $stockbuttons = ['save'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$username]['type'] = 'editlookup';
    $obj[0][$this->gridname]['columns'][$username]['lookupclass'] = 'lookupuser';
    $obj[0][$this->gridname]['columns'][$username]['action'] = 'lookupsetup';
    $obj[0][$this->gridname]['columns'][$username]['readonly'] = false;
    $obj[0][$this->gridname]['columns'][$username]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px';

    $obj[0][$this->gridname]['columns'][$type]['type'] = 'lookup';
    $obj[0][$this->gridname]['columns'][$type]['lookupclass'] = 'lookupusertype';
    $obj[0][$this->gridname]['columns'][$type]['action'] = 'lookupsetup';
    $obj[0][$this->gridname]['columns'][$type]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px';

    $obj[0][$this->gridname]['columns'][$password]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px';
    $obj[0][$this->gridname]['columns'][$pincode]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px';
    $obj[0][$this->gridname]['columns'][$pincode2]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px';
    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = ['addrecord', 'saveallentry', 'syncall'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }


  public function add($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['clientid'] = $config['params']['tableid'];
    $data['username'] = '';
    $data['name'] = '';
    $data['type'] = 'Cashier';
    $data['password'] = '';
    $data['pincode'] = '';
    $data['pincode2'] = '';
    $data['isinactive'] = 'false';
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    $msg = 'All saved successfully.';
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }
        $data2['dlock'] = $this->othersClass->getCurrentTimeStamp();
        if ($data[$key]['line'] == 0) {
          $exist = $this->coreFunctions->getfieldvalue("branchusers", "username", "clientid=? and username=? and isinactive =0 ", [$data[$key]['clientid'], $data[$key]['username']]);
          if ($exist == '') {
            $this->coreFunctions->insertGetId($this->table, $data2);
          } else {
            $msg = $data[$key]['username'] . ' already exists';
          }
        } else {
          unset($data2['username']);
          $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' =>  $msg, 'data' => $returndata];
  } // end function 

  public function save($config)
  {
    $data = [];
    $msg = 'Successfully saved.';

    $row = $config['params']['row'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    $data['dlock'] = $this->othersClass->getCurrentTimeStamp();
    if ($row['line'] == 0) {
      $exist = $this->coreFunctions->getfieldvalue("branchusers", "username", "clientid=? and username=? and isinactive =0 ", [$data['clientid'], $data['username']]);
      if ($exist == '') {
        $line = $this->coreFunctions->insertGetId($this->table, $data);
      } else {
        $msg = $data['username'] . ' already exists';
      }
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($config, $line);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      unset($data2['username']);
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($config, $row['line']);
        return ['status' => true, 'msg' => $msg, 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function


  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
      case 'lookupusertype':
        return $this->lookupusertype($config);
        break;
      default:
        return $this->lookupuser($config);
        break;
    }
  }

  public function lookupusertype($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'User Types',
      'style' => 'width:400px;max-width:400px;'
    );

    $plotsetup = array(
      'plottype' => 'plotgrid',
      'plotting' => array('type' => 'type')
    );

    // lookup columns
    $cols = array(
      array('name' => 'type', 'label' => 'Type', 'align' => 'left', 'field' => 'type', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $qry = "select 'Administrator' as type union all select 'Supervisor' union all select 'Cashier' ";
    $data = $this->coreFunctions->opentable($qry);
    $index = $config['params']['index'];
    $table = isset($config['params']['table']) ? $config['params']['table'] : "";

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index, 'rowindex' => $index, 'table' => $table];
  }

  public function lookupuser($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Users',
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'plotgrid',
      'plotting' => array('username' => 'username', 'name' => 'name')
    );

    $cols = [
      ['name' => 'username', 'label' => 'Username', 'align' => 'left', 'field' => 'username', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'name', 'label' => 'Name', 'align' => 'left', 'field' => 'name', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'type', 'label' => 'Type', 'align' => 'left', 'field' => 'type', 'sortable' => true, 'style' => 'font-size:16px;']
    ];

    $qry = "select username, name from (
      select username, name from branchusers union all 
      select email as username, clientname as name from client where email<>'' and isagent=1 union all 
      select username, name from useraccess) as u group by username, name order by username";
    $data = $this->coreFunctions->opentable($qry);

    $index = $config['params']['index'];
    $table = isset($config['params']['table']) ? $config['params']['table'] : "";

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index, 'rowindex' => $index, 'table' => $table];
  }

  public function lookupcallback($config)
  {
    switch ($config['params']['lookupclass2']) {
      case 'addtogrid':
        $data = [];
        $trno = $config['params']['tableid'];
        $row = $config['params']['row'];
        $exist = $this->coreFunctions->getfieldvalue("branchusers", "userid", "clientid=? and userid=? and isinactive =0", [$trno, $row['userid']]);
        if (strlen($exist) == 0) {
          $data['line'] = 0;
          $data['clientid'] = $config['params']['tableid'];
          $data['userid'] = $row['userid'];
          $data['username'] = $row['username'];
          $data['isinactive'] = 0;
          $data['type'] = '';
          $data['bgcolor'] = 'bg-blue-2';
          return ['status' => true, 'msg' => 'Add User success...', 'data' => $data];
        } else {
          return [];
        }

        break;
    }
  }

  private function loaddataperrecord($config, $line)
  {
    $tableid = $config['params']['tableid'];
    $qry = "select line, clientid, username, (case isinactive when 1 then 'true' else 'false' end) as isinactive, dlock, type, userid, name, password, pincode, pincode2,'' as bgcolor 
    from " . $this->table . " where clientid =? and line=? order by line";
    $data = $this->coreFunctions->opentable($qry, [$tableid, $line]);
    return $data;
  }

  public function loaddata($config)
  {
    $tableid = $config['params']['tableid'];
    $qry = "select line, clientid, username, (case isinactive when 1 then 'true' else 'false' end) as isinactive, dlock, type, userid, name, password, pincode, pincode2,'' as bgcolor 
    from " . $this->table . " where clientid =? order by line";
    $data = $this->coreFunctions->opentable($qry, [$tableid]);
    return $data;
  }




  public function tableentrystatus($config)
  {
    switch ($config['params']['action2']) {
      case 'syncallentry':
        $tableid = $config['params']['tableid'];
        $qry = "select station from branchstation where clientid =? and isinactive=0";
        $station = $this->coreFunctions->opentable($qry, [$tableid]);
        $branchcode = $this->coreFunctions->getfieldvalue('client', 'client', 'clientid=?', [$tableid]);
        $qry = "SELECT line as userid,username,CASE WHEN type='Administrator' THEN 1 WHEN type='Supervisor' THEN 3 ELSE 2 END AS accessid,password,name,pincode,pincode2,isinactive AS inactive,branchusers.dlock FROM branchusers WHERE clientid=?";
        $data = $this->coreFunctions->opentable($qry, [$tableid]);
        $csv = $this->posClass->createcsv($data, 1);
        foreach ($station as $key => $value) {
          $this->posClass->ftpcreatefile($csv, $branchcode, $value->station, 'download', 'users');
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
