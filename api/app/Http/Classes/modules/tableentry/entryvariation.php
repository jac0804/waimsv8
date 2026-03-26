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
use App\Http\Classes\sbcdb\trigger;
use App\Http\Classes\sbcdb\waims;
use App\Http\Classes\sbcdb\customersupport;

class entryvariation
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'VARIATIONS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'projectvar';
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  private $othersClass;
  private $logger;
  public $style = 'width:100%;';
  private $fields = ['variation', 'amount', 'projectid', 'trno'];
  public $showclosebtn = true;

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
    $attrib = array(
      'load' => 0
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'variation', 'amount']]];

    $stockbuttons = ['save', 'delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
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
    $trno = $config['params']['tableid'];
    $data['trno'] = $trno;
    $data['line'] = 0;
    $data['variation'] = '';
    $data['amount'] = 0;
    $data['projectid'] = $this->coreFunctions->getfieldvalue("pmhead", "projectid", "trno =?", [$trno]);
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  private function selectqry()
  {
    $qry = "line";
    foreach ($this->fields as $key => $value) {
      $qry = $qry . ',' . $value;
    }
    return $qry;
  }

  public function saveallentry($config)
  {
    $trno = $config['params']['tableid'];
    $data = $config['params']['data'];
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }
        if ($data[$key]['line'] == 0) {
          $line = $this->coreFunctions->insertGetId($this->table, $data2);
          $this->logger->sbcwritelog(
            $trno,
            $config,
            'VARIATION',
            ' CREATE - LINE: ' . $line . ''
              . ', VARIATAION: ' . $data[$key]['variation']
              . ', AMOUNT: ' . $data[$key]['amount']
          );
        } else {
          $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
          $data2['editby'] = $config['params']['user'];
          $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
  } // end function 

  public function save($config)
  {
    $trno = $config['params']['tableid'];
    $data = [];
    $row = $config['params']['row'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    if ($row['line'] == 0) {
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $this->logger->sbcwritelog(
          $trno,
          $config,
          'VARIATION',
          ' CREATE - LINE: ' . $line . ''
            . ', VARIATAION: ' . $data['variation']
            . ', AMOUNT: ' . $data['amount']
        );
        $returnrow = $this->loaddataperrecord($config, $line);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($config, $row['line']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  public function delete($config)
  {
    $trno = $config['params']['tableid'];
    $row = $config['params']['row'];

    $check = $this->coreFunctions->getfieldvalue("stages", "stage", "stage=?", [$row['line']]);
    if (strlen($check) > 0) {
      return ['status' => false, 'msg' => 'DELETE failed,already used...'];
    } else {
      $qry = "delete from " . $this->table . " where line=?";
      $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
      $this->logger->sbcwritelog(
        $trno,
        $config,
        'VARIATION',
        ' DELETE - LINE: ' . $row['line'] . ''
          . ', VARIATAION: ' . $row['variation']
          . ', AMOUNT: ' . $row['amount']
      );
      return ['status' => true, 'msg' => 'Successfully deleted.'];
    }
  }


  private function loaddataperrecord($config, $line)
  {
    $trno = $config['params']['tableid'];
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " where trno =? and line=?";
    $data = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $data;
  }

  public function loaddata($config)
  {
    $trno = $config['params']['tableid'];
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " where trno=? order by line";
    $data = $this->coreFunctions->opentable($qry, [$trno]);
    return $data;
  }
} //end class
