<?php

namespace App\Http\Classes\modules\payrollentry;

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

class entryedpayment
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'MANUAL PAYMENT';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'standardtrans';
  private $tablesetup = 'standardsetup';
  public $tablelogs = 'payroll_log';
  private $logger;
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['trno', 'line', 'empid', 'docno', 'dateid', 'cr', 'ismanual'];
  public $showclosebtn = true;
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
    $attrib = array('load' => 0);
    return $attrib;
  }

  public function createTab($config)
  {
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'docno', 'dateid', 'cr']]];

    $stockbuttons = ['save', 'delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][0]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";
    $obj[0][$this->gridname]['columns'][1]['align'] = "text-left";
    $obj[0][$this->gridname]['columns'][1]['style'] = 'width:130px;whiteSpace: normal;min-width:130px;';
    // $obj[0][$this->gridname]['columns'][2]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][2]['style'] = 'width:130px;whiteSpace: normal;min-width:130px;';
    $obj[0][$this->gridname]['columns'][3]['label'] = "Payment";
    $obj[0][$this->gridname]['columns'][3]['style'] = 'width:130px;whiteSpace: normal;min-width:130px;';
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
    $empid = $this->coreFunctions->getfieldvalue($this->tablesetup, "empid", "trno=?", [$config['params']['tableid']]);
    $data = [];
    $data['trno'] = $config['params']['tableid'];
    $data['line'] = 0;
    $data['empid'] = $empid;
    $data['docno'] = '';
    $data['dateid'] = date('Y-m-d');
    $data['cr'] = 0;
    $data['ismanual'] = '1';
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

    $bal = $this->coreFunctions->datareader("select balance as value from standardsetup where trno=" . $data['trno']);

    if ($data['cr'] < 0) {
      $data[0]['bgcolor'] = 'bg-red-2';
      $data[0]['line'] = $row['line'];
      return ['status' => false, 'msg' => 'Payment must have a value', 'row' => $data];
    }

    if ($data['cr'] <= $bal) {
      if ($row['line'] == 0) {
        $line = $this->coreFunctions->insertGetId($this->table, $data);
        if ($line) {

          $appliedamt = $this->coreFunctions->datareader("select ifnull(sum(cr),0) as value from standardtrans where trno=?", [$data['trno']]);
          // $this->coreFunctions->execqry("update standardsetup set balance = balance - " . $data['cr'] . " where trno=" . $data['trno']);
          $this->coreFunctions->execqry("update standardsetup set balance = amt - " . $appliedamt . " where trno=" . $data['trno']);
          $returnrow = $this->loaddataperrecord($data['trno'], $line, $config);

          $params = $config;
          $params['params']['doc'] = "EARNINGDEDUCTIONSETUP";
          $this->logger->sbcmasterlog(
            $data['trno'],
            $params,
            "ADD MANUAL PAYMENT - LINE: " . $line . " DOCUMENT#: " . $data['docno'] . ", DATE: " . $data['dateid'] . ", PAYMENT: " . $data['cr']
          );
          return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow, 'reloadhead' => true, 'trno' => $data['trno']];
        } else {
          return ['status' => false, 'msg' => 'Saving failed.'];
        }
      } else {
        // $prevamt = $this->coreFunctions->getfieldvalue($this->table, "cr", "trno=? and line=?", [$row['trno'], $row['line']], '', true);
        // $this->coreFunctions->execqry("update standardsetup set balance = balance + " . $prevamt . " where trno=" . $row['trno']);

        $appliedamt = $this->coreFunctions->datareader("select ifnull(sum(cr),0) as value from standardtrans where trno=?", [$row['trno']]);
        $this->coreFunctions->execqry("update standardsetup set balance = amt - " . $appliedamt . " where trno=" . $row['trno']);

        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];
        if ($this->coreFunctions->sbcupdate($this->table, $data, ['trno' => $row['trno'], 'line' => $row['line']]) == 1) {
          $this->coreFunctions->execqry("update standardsetup set balance = balance - " . $row['cr'] . " 
                  where trno=" . $row['trno'] . "");

          $returnrow = $this->loaddataperrecord($row['trno'], $data['line'], $config);
          return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow, 'reloadhead' => true];
        } else {
          return ['status' => false, 'msg' => 'Saving failed.'];
        }
      }
    } else {
      return ['status' => false, 'msg' => 'Check your balance.'];
    }
  } //end function

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }

        $bal = $this->coreFunctions->datareader("select balance as value from standardsetup where trno=" . $data[$key]['trno']);

        if ($data[$key]['cr'] < 0) {
          $data[$key]['bgcolor'] = 'bg-red-2';
          $data[$key]['line'] = $data[$key]['line'];
          return ['status' => false, 'msg' => 'Payment must have a value', 'row' => $data];
        }

        if ($data[$key]['cr'] <= $bal) {
          if ($data[$key]['line'] == 0) {
            $line = $this->coreFunctions->insertGetId($this->table, $data2);

            if ($line) {
              // $this->coreFunctions->execqry("update standardsetup set balance = balance - " . $data[$key]['cr'] . " where trno=" . $data[$key]['trno']);

              $appliedamt = $this->coreFunctions->datareader("select ifnull(sum(cr),0) as value from standardtrans where trno=?", [$data[$key]['trno']]);
              $this->coreFunctions->execqry("update standardsetup set balance = amt - " . $appliedamt . " where trno=" . $data[$key]['trno']);

              $params = $config;
              $params['params']['doc'] = "EARNINGDEDUCTIONSETUP";
              $this->logger->sbcmasterlog(
                $data[$key]['trno'],
                $params,
                "ADD MANUAL PAYMENT - LINE: " . $line . " DOCUMENT#: " . $data[$key]['docno'] . ", DATE: " . $data[$key]['dateid'] . ", PAYMENT: " . $data[$key]['cr']
              );
            } else {
              return ['status' => false, 'msg' => 'Saving failed.'];
            }
          } else {
            // $prevamt = $this->coreFunctions->getfieldvalue($this->table, "cr", "trno=? and line=?", [$data[$key]['trno'], $data[$key]['line']]);
            // $this->coreFunctions->execqry("update standardsetup set balance = balance + " . $prevamt . " where trno=" . $data[$key]['trno']);

            $appliedamt = $this->coreFunctions->datareader("select ifnull(sum(cr),0) as value from standardtrans where trno=?", [$data[$key]['trno']]);
            $this->coreFunctions->execqry("update standardsetup set balance = amt - " . $appliedamt . " where trno=" . $data[$key]['trno']);

            $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data2['editby'] = $config['params']['user'];
            if ($this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']])) {
              $this->coreFunctions->execqry("update standardsetup set balance = balance - " . $data[$key]['cr'] . " 
                  where trno=" . $data[$key]['trno'] . "");

              // $this->logger->sbcmasterlog(
              //   $data[$key]['trno'],
              //   $config,
              //   "UPDATE MANUAL PAYMENT - LINE: ". $data[$key]['line'] . " DOCUMENT#: ". $data[$key]['docno'] . ", DATE: " . $data[$key]['dateid']. ", PAYMENT: " . $data[$key]['cr'],
              //   1 // isedit
              // );
            }
          }
          $msg = 'All were successfully saved.';
          $status = true;
        } else {
          $msg = 'Check your balance.';
          $status = false;
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => $status, 'msg' => $msg, 'data' => $returndata, 'row' => $returndata, 'reloadhead' => true];
  }

  public function delete($config)
  {
    // $line = $config['params']['tableid'];
    $trno = $config['params']['row']['trno'];
    $line = $config['params']['row']['line'];

    $prevamt = $this->coreFunctions->getfieldvalue($this->table, "cr", "trno=? and line=?", [$trno, $line]);

    $qry = "delete from " . $this->table . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);

    // $this->coreFunctions->execqry("update standardsetup set balance = balance + " . $prevamt . " where trno=" . $trno);
    $appliedamt = $this->coreFunctions->datareader("select ifnull(sum(cr),0) as value from standardtrans where trno=?", [$trno]);
    $this->coreFunctions->execqry("update standardsetup set balance = amt - " . $appliedamt . " where trno=" . $trno);

    $config['params']['doc'] = "EARNINGDEDUCTIONSETUP";
    $this->logger->sbcmasterlog(
      $trno,
      $config,
      "DELETE MANUAL PAYMENT - LINE: " . $line . " DOCUMENT#: " . $config['params']['row']['docno'] . ", DATE: " . $config['params']['row']['dateid'] . ", PAYMENT: " . $config['params']['row']['cr']
    );

    return ['status' => true, 'msg' => 'Successfully deleted.', 'reloadhead' => true];
  }

  private function selectqry()
  {
    $qry = "line";
    foreach ($this->fields as $key => $value) {
      $qry = $qry . ',' . $value;
    }
    return $qry;
  }

  private function loaddataperrecord($trno, $line, $config)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor,  DATE_FORMAT(dateid, '%Y/%m/%d') as dateid ";
    $qry = "select " . $select . " from " . $this->table . " where trno=? and line=? and ismanual = ?";
    $data = $this->coreFunctions->opentable($qry, [$trno, $line, 1]);
    return $data;
  }

  public function loaddata($config)
  {
    $trno = $config['params']['tableid'];
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor,  DATE_FORMAT(dateid, '%Y/%m/%d') as dateid ";
    $qry = "select " . $select . " from " . $this->table . " where trno=? and ismanual = ?
        order by line";
    $data = $this->coreFunctions->opentable($qry, [$trno, 1]);
    return $data;
  }
} //end class
