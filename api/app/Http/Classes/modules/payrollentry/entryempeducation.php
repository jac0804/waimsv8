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

class entryempeducation
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'EDUCATION';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'education';
  public $tablelogs = 'client_log';
  public $tablelogs_del = 'del_client_log';
  private $logger;
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['empid', 'school', 'address', 'course', 'sy', 'gpa', 'honor'];
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
    $doc = $config['params']['doc'];
    $empaccess = $this->othersClass->checkAccess($config['params']['user'], 1292);
    $infoaccess = $this->othersClass->checkAccess($config['params']['user'], 5302);
    $iswindows = $this->companysetup->getiswindowspayroll($config['params']);
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'school', 'address', 'course', 'sy', 'gpa', 'honor']]];

    if (!$infoaccess) {
      if ($doc == 'MYINFO') {
        unset($tab[$this->gridname]['gridcolumns'][0]); // action
        $tab[$this->gridname]['gridcolumns'] = array_values($tab[$this->gridname]['gridcolumns']);
      }
    }

    $stockbuttons = [];

    switch ($config['params']['companyid']) {
      case 58: //cdo
        switch ($doc) {
          case 'MYINFO':
            if ($infoaccess) {
              goto access;
            } else {
              goto notaccess;
            }
            break;

          default:
            if ($empaccess) {
              goto access;
            } else {
              goto notaccess;
            }
            break;
        }
        break;

      default:
        if ($iswindows) {
          goto notaccess;
        }

        if ($empaccess) {
          access:
          $stockbuttons = ['save', 'delete'];
          $obj = $this->tabClass->createtab($tab, $stockbuttons);
          $obj[0][$this->gridname]['columns'][0]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
          $obj[0][$this->gridname]['columns'][1]['style'] = "width:250px;whiteSpace: normal;min-width:250px;";
          $obj[0][$this->gridname]['columns'][2]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
          $obj[0][$this->gridname]['columns'][3]['style'] = "width:250px;whiteSpace: normal;min-width:250px;";
          $obj[0][$this->gridname]['columns'][4]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
          $obj[0][$this->gridname]['columns'][5]['style'] = "width:250px;whiteSpace: normal;min-width:250px;";
        } else {
          notaccess:
          $obj = $this->tabClass->createtab($tab, $stockbuttons);
          $obj[0][$this->gridname]['columns'][0]['readonly'] = true;
          $obj[0][$this->gridname]['columns'][1]['readonly'] = true;
          $obj[0][$this->gridname]['columns'][2]['readonly'] = true;
          $obj[0][$this->gridname]['columns'][3]['readonly'] = true;
          $obj[0][$this->gridname]['columns'][4]['readonly'] = true;
          $obj[0][$this->gridname]['columns'][5]['readonly'] = true;
          $obj[0][$this->gridname]['columns'][0]['style'] = "width:250px;whiteSpace: normal;min-width:250px;";
          $obj[0][$this->gridname]['columns'][1]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
          $obj[0][$this->gridname]['columns'][2]['style'] = "width:250px;whiteSpace: normal;min-width:250px;";
          $obj[0][$this->gridname]['columns'][3]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
          $obj[0][$this->gridname]['columns'][4]['style'] = "width:250px;whiteSpace: normal;min-width:250px;";
        }
        break;
    }

    return $obj;
  }


  public function createtabbutton($config)
  {
    // $tbuttons = ['addrecord','saveallentry', 'masterfilelogs'];
    $empaccess = $this->othersClass->checkAccess($config['params']['user'], 1292);
    $infoaccess = $this->othersClass->checkAccess($config['params']['user'], 5302);
    $doc = $config['params']['doc'];
    $tbuttons = [];

    switch ($config['params']['companyid']) {
      case 58: //cdo
        switch ($doc) {
          case 'MYINFO':
            if ($infoaccess) {
              goto access;
            }
            break;
          default:
            if ($empaccess) {
              goto access;
            }
            break;
        }
        break;

      default:
        $iswindows = $this->companysetup->getiswindowspayroll($config['params']);
        if ($iswindows) {
          $tbuttons = [];
        } else {
          if ($empaccess) {
            access:
            $tbuttons = ['addrecord', 'saveallentry'];
          }
        }

        break;
    }

    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }

  public function add($config)
  {
    $id = $config['params']['tableid'];
    $data = [];
    $data['empid'] = $id;
    $data['line'] = 0;
    $data['name'] = '';
    $data['school'] = '';
    $data['address'] = '';
    $data['course'] = '';
    $data['sy'] = '';
    $data['gpa'] = '';
    $data['honor'] = '';
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
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($row['empid'], $line);

        $config['params']['doc'] = strtoupper('emp_education');
        $this->logger->sbcwritelog(
          $row['empid'],
          $config,
          'EDUCATION',
          'CREATE - LINE: ' . $line
            . ' - SCHOOL: ' . $data['school']
            . ' , ADDRESS: ' . $data['address']
            . ' , COURSE: ' . $data['course']
            . ' , SY: ' . $data['sy']
            . ' , GPA: ' . $data['gpa']
            . ' , HONOR: ' . $data['honor']
        );

        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
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

          $config['params']['doc'] = strtoupper('emp_education');
          $this->logger->sbcwritelog(
            $empid,
            $config,
            'EDUCATION',
            'ADD - LINE: ' . $line
              . ' - SCHOOL: ' . $data2['school']
              . ' , ADDRESS: ' . $data2['address']
              . ' , COURSE: ' . $data2['course']
              . ' , SY: ' . $data2['sy']
              . ' , GPA: ' . $data2['gpa']
              . ' , HONOR: ' . $data2['honor']
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

    $config['params']['doc'] = strtoupper('emp_education');
    $this->logger->sbcwritelog(
      $row['empid'],
      $config,
      'EDUCATION',
      'DELETE - LINE: ' . $row['line']
        . ' - SCHOOL: ' . $row['school']
        . ' , ADDRESS: ' . $row['address']
        . ' , COURSE: ' . $row['course']
        . ' , SY: ' . $row['sy']
        . ' , GPA: ' . $row['gpa']
        . ' , HONOR: ' . $row['honor']
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
    // $doc = $config['params']['doc'];

    $cols = [
      ['name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'editby', 'label' => 'Edited By', 'align' => 'left', 'field' => 'editby', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'editdate', 'label' => 'Edited Date', 'align' => 'left', 'field' => 'editdate', 'sortable' => true, 'style' => 'font-size:16px;'],
    ];

    $doc = strtoupper('emp_education');
    $qry = "
      select trno, doc, task, left(dateid,10) as dateid, user, editby, editdate
      from " . $this->tablelogs . "
      where doc = ?
      order by dateid desc
    ";

    $data = $this->coreFunctions->opentable($qry, [$doc, $doc]);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
  }
} //end class
