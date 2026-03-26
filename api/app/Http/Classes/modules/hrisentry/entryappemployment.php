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

class entryappemployment
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'EMPLOYMENT HISTORY';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'aemployment';
  public $tablelogs = 'masterfile_log';
  private $logger;
  private $othersClass;
  public $style = 'width:1100px;max-width:1100px;';
  private $fields = ['empid', 'company', 'address', 'jobtitle', 'salary', 'period', 'reason'];
  public $showclosebtn = false;


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
    $action = 0;
    $company = 1;
    $address = 2;
    $jobtitle = 3;
    $salary = 4;
    $period = 5;
    $reason = 6;

    $tab = [$this->gridname => ['gridcolumns' => ['action', 'company', 'address', 'jobtitle', 'salary', 'period', 'reason']]];

    $stockbuttons = ['save', 'delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][$company]['style'] = "width:350px;whiteSpace: normal;min-width:350px;";
    $obj[0][$this->gridname]['columns'][$address]['style'] = "width:350px;whiteSpace: normal;min-width:350px;";
    $obj[0][$this->gridname]['columns'][$jobtitle]['style'] = "width:350px;whiteSpace: normal;min-width:350px;";
    $obj[0][$this->gridname]['columns'][$salary]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
    $obj[0][$this->gridname]['columns'][$period]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
    $obj[0][$this->gridname]['columns'][$reason]['style'] = "width:350px;whiteSpace: normal;min-width:350px;";

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
    $data = [];
    $data['empid'] = $id;
    $data['line'] = 0;
    $data['company'] = '';
    $data['address'] = '';
    $data['jobtitle'] = '';
    $data['salary'] = 0;
    $data['period'] = '';
    $data['reason'] = '';
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

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    if ($row['line'] == 0) {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($row['empid'], $line);

        $config['params']['doc'] = strtoupper('app_employment');
        $this->logger->sbcmasterlog(
          $row['empid'],
          $config,
          'CREATE EMPLOYEMENT - LINE: ' . $line
            . ' - COMPANY: ' . $data['company']
            . ' , ADDRESS: ' . $data['address']
            . ' , JOBTITLE: ' . $data['jobtitle']
            . ' , SALARY: ' . $data['salary']
            . ' , PERIOD: ' . $data['period']
            . ' , REASON: ' . $data['reason']
        );

        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($row['empid'], $row['line']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  public function saveallentry($config)
  {
    $empid = $config['params']['tableid'];
    $data = $config['params']['data'];
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }
        if ($data[$key]['line'] == 0) {
          $line = $this->coreFunctions->insertGetId($this->table, $data2);

          $config['params']['doc'] = strtoupper('app_employment');
          $this->logger->sbcmasterlog(
            $empid,
            $config,
            'CREATE EMPLOYMENT - LINE: ' . $line
              . ' - COMPANY: ' . $data2['company']
              . ' , ADDRESS: ' . $data2['address']
              . ' , JOBTITLE: ' . $data2['jobtitle']
              . ' , SALARY: ' . $data2['salary']
              . ' , PERIOD: ' . $data2['period']
              . ' , REASON: ' . $data2['reason']
          );
        } else {
          $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
          $data2['editby'] = $config['params']['user'];
          $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'Saved all Successfully', 'data' => $returndata];
  } // end function

  public function delete($config)
  {
    $row = $config['params']['row'];
    $qry = "delete from " . $this->table . " where empid=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['empid'], $row['line']]);

    $config['params']['doc'] = strtoupper('app_employment');
    $this->logger->sbcmasterlog(
      $row['empid'],
      $config,
      'DELETE EMPLOYEMENT - LINE: ' . $row['line']
        . ' - COMPANY: ' . $row['company']
        . ' , ADDRESS: ' . $row['address']
        . ' , JOBTITLE: ' . $row['jobtitle']
        . ' , SALARY: ' . $row['salary']
        . ' , PERIOD: ' . $row['period']
        . ' , REASON: ' . $row['reason']
    );

    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }


  private function loaddataperrecord($empid, $line)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " where empid=? and line=?";
    $data = $this->coreFunctions->opentable($qry, [$empid, $line]);
    return $data;
  }

  public function loaddata($config)
  {
    $tableid = $config['params']['tableid'];
    $center = $config['params']['center'];
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " where empid=? order by line";
    $data = $this->coreFunctions->opentable($qry, [$tableid]);
    return $data;
  }

  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
      case 'lookuplogs':
        return $this->lookuplogs($config);
        break;
      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
        break;
    }
  }

  public function lookuplogs($config)
  {
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'List of Logs',
      'style' => 'width:1000px;max-width:1000px;'
    );

    $trno = $config['params']['tableid'];

    $cols = [
      ['name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;'],
    ];

    $doc = strtoupper('app_employment');
    $qry = "
      select trno, doc, task, dateid as dateid,dateid as sort, user
      from " . $this->tablelogs . "
      where doc = ?
      order by sort desc
    ";

    $data = $this->coreFunctions->opentable($qry, [$doc, $doc]);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
  }
} //end class
