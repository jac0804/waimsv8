<?php

namespace App\Http\Classes\modules\enrollmententry;

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
use App\Http\Classes\lookup\enrollmentlookup;

class entryroom
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'ROOM';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'en_rooms';
  private $othersClass;
  public $style = 'width:100%;';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'masterfile_log';
  private $fields = ['roomcode', 'roomname'];
  public $showclosebtn = false;
  private $enrollmentlookup;



  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->enrollmentlookup = new enrollmentlookup;
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
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'roomcode', 'roomname']]];

    $stockbuttons = ['save', 'delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][0]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";

    $obj[0][$this->gridname]['columns'][1]['type'] = "input";
    $obj[0][$this->gridname]['columns'][1]['readonly'] = false;
    $obj[0][$this->gridname]['columns'][1]['style'] = 'width:250px;whiteSpace:normal;min-width:250px;';

    $obj[0][$this->gridname]['columns'][2]['type'] = "input";
    $obj[0][$this->gridname]['columns'][2]['readonly'] = false;
    $obj[0][$this->gridname]['columns'][2]['style'] = 'width:250px;whiteSpace:normal;min-width:250px;';

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
    $id = $config['params']['tableid'];
    if ($id != 0) {
      $data = [];
      $data['line'] = 0;
      $data['bldgid'] = $id;
      $data['roomcode'] = '';
      $data['roomname'] = '';
      $data['bgcolor'] = 'bg-blue-2';
      return $data;
    }
    return ['status' => false, 'msg' => 'Please save head first.'];
  }

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    $msg = '';
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }
        $data2['bldgid'] = $config['params']['tableid'];
        if ($data[$key]['line'] == 0) {
          $check = $this->checkRoom($data2, 'new');
          if ($check) {
            $msg .= "\n Duplicate Room entry for ".$data2['roomcode'].' - '.$data2['roomname'];
          } else {
            $line = $this->coreFunctions->insertGetId($this->table, $data2);
            $this->logger->sbcmasterlog($data2['bldgid'], $config, ' CREATE ROOM - ' . $data[$key]['roomname']);
          }
        } else {
          $check = $this->checkRoom($data2);
          if ($check) {
            $msg .= "\n Duplicate Room entry for ".$data2['roomcode'].' - '.$data2['roomname'];
          } else {
            $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data2['editby'] = $config['params']['user'];
            $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
          }
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved successfully.'.$msg, 'data' => $returndata];
  } // end function 

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    $data['bldgid'] = $config['params']['tableid'];
    if ($row['line'] == 0) {
      $check = $this->checkRoom($data, 'new');
      if ($check) {
        return ['status' => false, 'msg' => 'Duplicate Room entry for '.$data['roomcode'].' - '.$data['roomname'], 'row' => []];
      } else {
        $line = $this->coreFunctions->insertGetId($this->table, $data);
        if ($line != 0) {
          $returnrow = $this->loaddataperrecord($data['bldgid'], $line);
          $this->logger->sbcmasterlog($data['bldgid'], $config, ' CREATE ROOM - ' . $row['roomname']);
          return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
        } else {
          return ['status' => false, 'msg' => 'Saving failed.'];
        }
      }
    } else {
      $check = $this->checkRoom($data);
      if ($check) {
        return ['status' => false, 'msg' => 'Duplicate Room entry for '.$data['roomcode'].' - '.$data['roomname'], 'row' => []];
      } else {
        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];
        if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
          $returnrow = $this->loaddataperrecord($data['bldgid'], $row['line']);
          return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
        } else {
          return ['status' => false, 'msg' => 'Saving failed.'];
        }
      }
    }
  } //end function

  public function checkRoom($data, $type = 'update')
  {
    $qry = "select line as value from en_rooms where roomcode='".$data['roomcode']."' and roomname='".$data['roomname']."'";
    if ($type == 'update') {
      $qry = "select line as value from en_rooms where roomcode='".$data['roomcode']."' and roomname='".$data['roomname']."' and line<>".$data['line'];
    }
    $check = $this->coreFunctions->datareader($qry);
    if ($check != '') return true;
    return false;
  }

  public function delete($config)
  {
    $row = $config['params']['row'];

    $line = $row['line'];
    $qry = "select v.trno as val, 'Bldg' as t  from 
            (select trno from en_scsubject where roomid= ?
            union all
            select trno from en_glsubject where roomid= ?
            union all
            select trno from en_sjsubject where roomid= ?) as v";
    $exist = $this->coreFunctions->opentable($qry, [$line, $line, $line]);

    if (!empty($exist)) {
      return ['line' => $line, 'status' => false, 'msg' => 'Unable to delete, it was already used as ' . $exist[0]->t];
    } else {
      $qry = "delete from " . $this->table . " where line=?";
      $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
      $this->logger->sbcdelmaster_log($row['bldgid'], $config, 'REMOVE ROOM - ' . $row['roomname']);
      return ['status' => true, 'msg' => 'Successfully deleted.'];
    }
  }

  private function selectqry()
  {
    $qry = "line, bldgid";
    foreach ($this->fields as $key => $value) {
      $qry = $qry . ',' . $value;
    }
    return $qry;
  }

  private function loaddataperrecord($bldgid, $line)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " where bldgid=? and line=?";
    $data = $this->coreFunctions->opentable($qry, [$bldgid, $line]);
    return $data;
  }

  public function loaddata($config)
  {
    $bldgid = $config['params']['tableid'];
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " where bldgid=? order by line";
    $data = $this->coreFunctions->opentable($qry, [$bldgid]);
    return $data;
  }

  public function lookupsetup($config)
  {
    $lookupclass = $config['params']['lookupclass2'];

    switch ($lookupclass) {
      case 'lookupdepartment':
      case 'lookupparentdepartment':
        return $this->enrollmentlookup->lookupdepartment($config);
        break;
      case 'lookupdean':
        return $this->enrollmentlookup->lookupdean($config);
        break;
      case 'lookupcourse':
        return $this->enrollmentlookup->lookupcourse($config);
        break;
      case 'lookuplevel':
      case 'lookupdeptlevel':
        return $this->enrollmentlookup->lookuplevel($config);
        break;
    }
  }
} //end class
