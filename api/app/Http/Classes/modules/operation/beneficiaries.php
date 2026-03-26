<?php

namespace App\Http\Classes\modules\operation;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;

class  beneficiaries
{
  private $tabClass;
  public $modulename = 'BENEFICIARIES';
  public $tablenum = 'transnum';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $logger;
  private $table = 'beneficiary';
  private $othersClass;
  public $style = 'width:100%;max-width: 100%';
  private $fields = ['trno', 'name', 'age', 'address', 'relation'];
  public $showclosebtn = true;
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';


  public function __construct()
  {
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = ['load' => 4040];
    return $attrib;
  }

  public function createTab($config)
  {

    $hidebuttons = false;
    $config['params']['trno'] = $config['params']['tableid'];
    if ($this->othersClass->isposted($config)) {
      $hidebuttons = true;
    }

    if (strtoupper($config['params']['doc']) == 'CP') {
      $hidebuttons = true;
    }

    $cols = ['action', 'name', 'age', 'address', 'relation'];
    foreach ($cols as $key => $value) {
      $$value = $key;
    }

    $tab = [$this->gridname => ['gridcolumns' => $cols]];
    // $stockbuttons = ['save','delete','addcustomer'];
    $stockbuttons = ['save', 'delete'];
    if ($hidebuttons) {
      $stockbuttons = [];
    }
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][$name]['style'] = "width:400px;whiteSpace: normal;min-width:300px;";
    $obj[0][$this->gridname]['columns'][$age]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][$address]['style'] = "width:400px;whiteSpace: normal;min-width:300px;";
    $obj[0][$this->gridname]['columns'][$relation]['style'] = "width:250px;whiteSpace: normal;min-width:250px;";
    $obj[0][$this->gridname]['columns'][$address]['type'] = "textarea";
    $obj[0][$this->gridname]['columns'][$address]['maxlength'] = 150;
    if ($hidebuttons) {
      $obj[0][$this->gridname]['columns'][$action]['type'] = "coldel";

      $obj[0][$this->gridname]['columns'][$name]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$age]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$address]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$relation]['readonly'] = true;
    }

    if (strtoupper($config['params']['doc']) == 'CP') {
      $obj[0][$this->gridname]['columns'][$action]['type'] = "coldel";

      $obj[0][$this->gridname]['columns'][$name]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$age]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$address]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$relation]['readonly'] = true;
    }

    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['addrecord', 'saveallentry'];

    $hidebuttons = false;

    $config['params']['trno'] = $config['params']['tableid'];
    if ($this->othersClass->isposted($config)) {
      $hidebuttons = true;
    }

    if ($hidebuttons) {
      $tbuttons = [];
    }

    if (strtoupper($config['params']['doc']) == 'CP') {
      $tbuttons = [];
    }

    $obj = $this->tabClass->createtabbutton($tbuttons);
    // $obj[0]['action'] = "addrow";
    $obj[0]['icon'] = "person_add";
    $obj[0]['label'] = "ADD BENEFICIARIES";
    return $obj;
  }

  public function lookupsetup($config)
  {
    return [];
  }

  public function add($config)
  {
    $data = [];
    $trno = $config['params']['tableid'];
    $row = $config['params']['row'];

    $data['line'] = 0;
    $data['trno'] = $trno;
    $data['name'] = '';
    $data['age'] = 0;
    $data['address'] = '';
    $data['relation'] = '';
    $data['bgcolor'] = 'bg-blue-2';

    if ($config['params']['tableid'] == 0) {
      return ['status' => false, 'msg' => 'Save Header First'];
    } else {
      return $data;
    }
  }


  public function save($config)
  {
    $trno = $config['params']['tableid'];
    $data = [];
    $row = $config['params']['row'];
    $data['trno'] = $config['params']['tableid'];

    $config['params']['trno'] = $config['params']['tableid'];
    if ($this->othersClass->isposted($config)) {
      return ['status' => false, 'msg' => 'Transaction Already Posted...'];
    }

    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }

    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];



    if ($row['line'] == 0) {

      $qry = "select line as value from " . $this->table . " where trno=? order by line desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$trno]);
      if ($line == '') {
        $line = 0;
      }
      $data['line'] = $line + 1;

      if ($this->coreFunctions->sbcinsert($this->table, $data)) {
        $returnrow = $this->loaddataperrecord($config, $data['line']);
        $this->logger->sbcwritelog($data['trno'], $config, ' CREATE - BENEFICIARIES', $row['name']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow, 'reloadhead' => true];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line'], 'trno' => $row['trno']]) == 1) {
        $returnrow = $this->loaddataperrecord($config, $row['line']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow, 'reloadhead' => true];
      } else {
        return ['status' => false, 'msg' => 'Updating failed.'];
      }
    }
  } //end function

  public function saveallentry($config)
  {

    $trno = $config['params']['tableid'];
    $data = $config['params']['data'];
    $config['params']['trno'] = $config['params']['tableid'];
    if ($this->othersClass->isposted($config)) {
      return ['status' => false, 'msg' => 'Transaction Already Posted...'];
    }

    if ($trno == 0) {
      return ['status' => false, 'msg' => 'Save Header First'];
    }

    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }

        $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data2['editby'] = $config['params']['user'];

        if ($data[$key]['line'] != 0) {
          $this->coreFunctions->sbcupdate($this->table, $data2, ['trno' => $trno, 'line' => $data[$key]['line']]);
        } else {
          $data2['trno'] = $data[$key]['trno'];
          $qry = "select line as value from " . $this->table . " where trno=? order by line desc limit 1";
          $line = $this->coreFunctions->datareader($qry, [$data2['trno']]);
          if ($line == '') {
            $line = 0;
          }
          $data2['line'] = $line + 1;
          $this->coreFunctions->sbcinsert($this->table, $data2);
          $this->logger->sbcwritelog($data[$key]['trno'], $config, ' CREATE - BENEFICIARIES', $data[$key]['name']);
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata, 'reloadhead' => true];
  } // end function $$

  public function delete($config)
  {
    $trno = $config['params']['tableid'];
    $row = $config['params']['row'];

    $config['params']['trno'] = $config['params']['tableid'];
    if ($this->othersClass->isposted($config)) {
      return ['status' => false, 'msg' => 'Transaction Already Posted...'];
    }

    $qry = "delete from " . $this->table . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['trno'], $row['line']]);
    $this->logger->sbcdel_log($row['trno'], $config, 'REMOVE - BENEFICIARIES', $row['name']);
    return ['status' => true, 'msg' => 'Successfully deleted.', 'reloadhead' => true];
  }

  private function selectqry($config)
  {
    $decimalqty = $this->companysetup->getdecimal('qty', $config['params']);
    return " select bf.line, bf.trno, bf.name, bf.age, bf.address,
    bf.relation ";
  }

  private function loaddataperrecord($config, $pline)
  {

    $trno = $config['params']['row']['trno'];
    $line = $config['params']['row']['line'];

    $sqlselect = $this->selectqry($config);
    $qry = $sqlselect . ",'' as bgcolor from " . $this->table . " as bf
    where bf.trno = ? and bf.line = ? order by line";
    $data = $this->coreFunctions->opentable($qry, [$trno, $pline]);
    return $data;
  }

  public function loaddata($config)
  {

    $trno = $config['params']['tableid'];

    if (strtoupper($config['params']['doc']) == 'CP') {

      $trno = $this->coreFunctions->datareader("select aftrno as value from lahead where trno = ?
                                                union all 
                                                select aftrno as value from glhead where trno = ?", [$trno, $trno]);
    }

    $sqlselect = $this->selectqry($config);
    $qry = $sqlselect . ",'' as bgcolor from " . $this->table . " as bf
    where bf.trno = ? 
    order by line";
    $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $data;
  }
} //end class
