<?php

namespace App\Http\Classes\modules\hrisentry;

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
use App\Http\Classes\lookup\hris;
use App\Http\Classes\lookup\hrislookup;

class entryturnoveritem
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'TURN OVER OF ITEMS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'turnoveritemdetail';
  private $htable = 'hturnoveritemdetail';
  public $tablelogs = 'hrisnum_log';
  public $tablelogs_del = 'del_hrisnum_log';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['itemname', 'amt', 'rem'];
  public $showclosebtn = false;
  private $hrislookup;
  private $logger;



  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->hrislookup = new hrislookup;
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = array('load' => 0);
    return $attrib;
  }

  public function createTab($config)
  {
    $action = 0;
    $itemname = 1;
    $amt = 2;
    $rem = 3;
    $ref = 4;

    if (strtolower($config['params']['doc']) == 'hr') {
      $tab = [$this->gridname => ['gridcolumns' => ['action', 'itemname', 'amt', 'rem', 'ref']]];
    } else {
      $tab = [$this->gridname => ['gridcolumns' => ['action', 'itemname', 'amt', 'rem']]];
    }

    $stockbuttons = ['save', 'delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";

    $obj[0][$this->gridname]['columns'][$itemname]['type'] = "input";
    $obj[0][$this->gridname]['columns'][$itemname]['label'] = "Description";
    $obj[0][$this->gridname]['columns'][$itemname]['readonly'] = false;
    $obj[0][$this->gridname]['columns'][$itemname]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";

    $obj[0][$this->gridname]['columns'][$amt]['label'] = "Estimated Value";

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
    $data = [];
    $data['trno'] = $config['params']['tableid'];
    $data['line'] = 0;
    $data['itemname'] = '';
    $data['rem'] = '';
    $data['amt'] = '0.00';
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
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
        $data2['trno'] = $config['params']['tableid'];


        if ($data[$key]['line'] == 0) {
          $line = $this->coreFunctions->insertGetId($this->table, $data2);
          $this->logger->sbcmasterlog(
            $config['params']['tableid'],
            $config,
            'CREATE DETAILS - ' . ' LINE: ' .
              $line . ' - ' .
              $data[$key]['itemname'] . ' - ' . $data[$key]['amt'] . ' - ' . $data[$key]['rem']
          );
        } else {
          $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
          $this->logger->sbcmasterlog(
            $config['params']['tableid'],
            $config,
            'UPDATE DETAILS - ' . ' LINE: ' .
              $data[$key]['line'] . ' - ' .
              $data[$key]['itemname'] . ' - ' . $data[$key]['amt'] . ' - ' . $data[$key]['rem']
          );
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'Saved all Successfully', 'data' => $returndata];
  } // end function 

  public function save($config)
  {

    $data = [];
    $row = $config['params']['row'];
    $id = $config['params']['tableid'];
    $doc = $config['params']['doc'];

    $checking = $this->coreFunctions->datareader("select count(postdate) as value 
    from hrisnum where trno = '$id' and postdate is not null and doc = '$doc'");
    if ($checking > 0) {
      return ['status' => false, 'msg' => "Transaction Already Posted!", 'data' => []];
    }

    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    $data['trno'] = $config['params']['tableid'];
    if ($row['line'] == 0) {
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

  public function delete($config)
  {
    $row = $config['params']['row'];
    $trno = $config['params']['tableid'];
    $doc = $config['params']['doc'];

    $checking = $this->coreFunctions->datareader("select count(postdate) as value 
    from hrisnum where trno = '$trno' and postdate is not null and doc = '$doc'");
    if ($checking > 0) {
      return ['status' => false, 'msg' => "Transaction Already Posted!", 'data' => []];
    }

    $qry = "delete from " . $this->table . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $row['line']]);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }

  private function selectqry()
  {
    $qry = "line,trno";
    foreach ($this->fields as $key => $value) {
      $qry = $qry . ',' . $value;
    }
    return $qry;
  }

  private function loaddataperrecord($trno, $line)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " where trno=? and line=?";
    $data = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $data;
  }

  public function loaddata($config)
  {
    $trno = $config['params']['tableid'];
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " where trno=? 
        union all select " . $select . " from " . $this->htable . " where trno=?
        order by line";
    $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $data;
  }
} //end class
