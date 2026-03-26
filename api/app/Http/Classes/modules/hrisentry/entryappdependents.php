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

class entryappdependents
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'DEPENDENTS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'adependents';
  private $emptable = 'dependents';
  public $tablelogs = 'masterfile_log';
  private $logger;
  private $othersClass;
  public $style = 'width:1100px;max-width:1100px;';
  private $fields = ['empid', 'name', 'relation', 'bday', 'schoollevel', 'occupation'];
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
    $companyid = $config['params']['companyid'];
    if ($companyid == 58 &&  $config['params']['doc'] == 'EP') { //cdohris
      $this->modulename = 'FAMILY TREE';
    }

    $access = $this->othersClass->checkAccess($config['params']['user'], 5302);
    $doc = $config['params']['doc'];
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'name', 'relation', 'bday', 'schoollevel', 'occupation']]];

    if ($access) {
      $stockbuttons = ['save', 'delete'];
      $obj = $this->tabClass->createtab($tab, $stockbuttons);
      $obj[0][$this->gridname]['columns'][0]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
      $obj[0][$this->gridname]['columns'][1]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
      $obj[0][$this->gridname]['columns'][2]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
      $obj[0][$this->gridname]['columns'][3]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    } else {
      if ($doc == 'MYINFO') {
        unset($tab[$this->gridname]['gridcolumns'][0]); // action
        $tab[$this->gridname]['gridcolumns'] = array_values($tab[$this->gridname]['gridcolumns']);
      }

      $stockbuttons = [];
      if ($doc != 'MYINFO') {
        $stockbuttons = ['save', 'delete'];
      }

      $obj = $this->tabClass->createtab($tab, $stockbuttons);
      // action
      if ($doc == 'MYINFO') {
        $obj[0][$this->gridname]['columns'][0]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][1]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][2]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][0]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
      } else {
        $obj[0][$this->gridname]['columns'][0]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][1]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
        $obj[0][$this->gridname]['columns'][2]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
        $obj[0][$this->gridname]['columns'][3]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
      }
    }

    if ($companyid != 58 || $config['params']['doc'] != 'EP') { //cdohris
      $obj[0][$this->gridname]['columns'][4]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][5]['type'] = 'coldel';
    }
    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }


  public function createtabbutton($config)
  {
    $companyid = $config['params']['companyid'];
    $access = $this->othersClass->checkAccess($config['params']['user'], 5302);
    $doc = $config['params']['doc'];
    $tbuttons = [];
    $iswindows = $this->companysetup->getiswindowspayroll($config['params']);
    if ($iswindows) {
      $tbuttons = [];
    } else {
      if ($access) {
        $tbuttons = ['addrecord', 'saveallentry', 'masterfilelogs'];
      } else {
        if ($doc != 'MYINFO') {
          $tbuttons = ['addrecord', 'saveallentry', 'masterfilelogs'];
        }
      }
    }



    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function add($config)
  {
    $id = $config['params']['tableid'];

    switch (strtoupper($config['params']['doc'])) {
      case 'EMPLOYEE':
      case 'MYINFO':
      case 'EP';
        $id = $this->coreFunctions->datareader("select empid as value 
          from employee
          where empid = ? 
          LIMIT 1", [$id]);
        break;
      default:
        $id = $this->coreFunctions->datareader(
          "select empid as value 
        from app 
        where empid = ? LIMIT 1",
          [$id]
        );
        break;
    }

    $data = [];
    $data['empid'] = $id;
    $data['line'] = 0;
    $data['name'] = '';
    $data['relation'] = '';
    $data['schoollevel'] = '';
    $data['occupation'] = '';
    $data['bday'] = date('Y-m-d');
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  private function selectqry()
  {
    $qry = "line, empid, name, relation, left(bday,10) as bday,schoollevel, occupation";

    return $qry;
  }

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    // var_dump($row);
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }



    if ($row['line'] == 0) {
      switch (strtoupper($config['params']['doc'])) {
        case 'EMPLOYEE':
        case 'MYINFO':
        case 'EP';
          $line = $this->coreFunctions->insertGetId($this->emptable, $data);
          break;
        default:
          $line = $this->coreFunctions->insertGetId($this->table, $data);
          break;
      }

      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($row['empid'], $line, $config);
        // var_dump($returnrow);
        switch (strtoupper($config['params']['doc'])) {
          case 'EMPLOYEE':
          case 'MYINFO':
          case 'EP';
            $config['params']['doc'] = strtoupper('emp_dependents');
            break;
          default:
            $config['params']['doc'] = strtoupper('app_dependents');
            break;
        }


        $this->logger->sbcmasterlog(
          $line,
          $config,
          'CREATE DEPENDENTS - LINE: ' . $line
            . ' - NAME: ' . $data['name']
            . ' , RELATION: ' . $data['relation']
            . ' , BDAY: ' . $data['bday']
        );

        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      switch (strtoupper($config['params']['doc'])) {
        case 'EMPLOYEE':
        case 'MYINFO':
        case 'EP';
          $update = $this->coreFunctions->sbcupdate($this->emptable, $data, ['line' => $row['line']]);
          break;
        default:
          $update = $this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]);
          break;
      }

      if ($update == 1) {
        $returnrow = $this->loaddataperrecord($row['empid'], $row['line'], $config);

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
          switch (strtoupper($config['params']['doc'])) {
            case 'EMPLOYEE':
            case 'MYINFO':
            case 'EP';
              $line = $this->coreFunctions->insertGetId($this->emptable, $data2);
              break;
            default:
              $line = $this->coreFunctions->insertGetId($this->table, $data2);
              break;
          }

          $this->logger->sbcmasterlog(
            $line,
            $config,
            'CREATE DEPENDENTS - LINE: ' . $line
              . ' - NAME: ' . $data2['name']
              . ' , RELATION: ' . $data2['relation']
              . ' , BDAY: ' . $data2['bday']
          );
        } else {
          $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
          $data2['editby'] = $config['params']['user'];
          switch (strtoupper($config['params']['doc'])) {
            case 'EMPLOYEE':
            case 'EMP_DEPENDENTS':
            case 'MYINFO':
            case 'EP';
              $this->coreFunctions->sbcupdate($this->emptable, $data2, ['line' => $data[$key]['line']]);
              break;
            default:
              $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
              break;
          }
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'Saved all Successfully', 'data' => $returndata];
  } // end function  

  public function delete($config)
  {
    $row = $config['params']['row'];
    switch (strtoupper($config['params']['doc'])) {
      case 'EMPLOYEE':
      case 'MYINFO':
      case 'EP';
        $qry = "delete from dependents where empid=? and line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['empid'], $row['line']]);
        $config['params']['doc'] = strtoupper('emp_dependents');
        break;
      default:
        $qry = "delete from adependents where empid=? and line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['empid'], $row['line']]);
        $config['params']['doc'] = strtoupper('app_dependents');
        break;
    }

    $this->logger->sbcmasterlog(
      $row['line'],
      $config,
      'DELETE DEPENDENTS - LINE: ' . $row['line']
        . ' - NAME: ' . $row['name']
        . ' , RELATION: ' . $row['relation']
        . ' , BDAY: ' . $row['bday']
    );

    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }


  private function loaddataperrecord($empid, $line, $config)
  {
    $aplid = $empid;

    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    switch (strtoupper($config['params']['doc'])) {
      case 'EMP_DEPENDENTS':
      case 'EMPLOYEE':
      case 'MYINFO':
      case 'EP';
        $qry = "select " . $select . " from dependents where empid=? and line=?";
        break;
      default:
        $qry = "select " . $select . " from adependents where empid=? and line=?";
        break;
    }

    $data = $this->coreFunctions->opentable($qry, [$aplid, $line]);
    return $data;
  }

  public function loaddata($config)
  {
    $aplid = $config['params']['tableid'];
    $center = $config['params']['center'];
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";

    switch (strtoupper($config['params']['doc'])) {
      case 'EMP_DEPENDENTS':
      case 'EMPLOYEE':
      case 'MYINFO':
      case 'EP';
        $qry = "select " . $select . " from dependents where empid=? order by line";
        break;
      default:
        $qry = "select " . $select . " from adependents where empid=? order by line";
        break;
    }

    $data = $this->coreFunctions->opentable($qry, [$aplid]);
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

    switch (strtoupper($config['params']['doc'])) {
      case 'EMPLOYEE':
      case 'MYINFO':
      case 'EP';
        $doc = strtoupper('emp_dependents');
        break;
      default:
        $doc = strtoupper('app_dependents');
        break;
    }

    $qry = "
      select trno, doc, task, dateid as dateid, dateid as sort, user, editby, editdate
      from " . $this->tablelogs . "
      where doc = ?
      order by sort desc
    ";

    $data = $this->coreFunctions->opentable($qry, [$doc, $doc]);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
  }
} //end class
