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
use App\Http\Classes\SBCPDF;

class entrytabrole
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'SET ROLE';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'emprole';
  public $tablelogs = 'payroll_log';
  private $logger;
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['empid', 'roleid'];
  public $showclosebtn = false;
  private $reporter;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->reporter = new SBCPDF;
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
    // $action = 0;
    // $rolename = 1;

    $cols = ['action', 'rolename', 'divname', 'deptname', 'sectname'];

    foreach ($cols as $key => $value) {
      $$value = $key;
    }
    $tab = [$this->gridname => ['gridcolumns' => $cols]];


    $stockbuttons = ['save', 'delete'];


    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:60px;whiteSpace: normal;min-width:60px;";
    $obj[0][$this->gridname]['columns'][$rolename]['style'] = "width:60px;whiteSpace: normal;min-width:60px;";
    $obj[0][$this->gridname]['columns'][$divname]['style'] = "width:60px;whiteSpace: normal;min-width:60px;";
    $obj[0][$this->gridname]['columns'][$divname]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$sectname]['style'] = "width:60px;whiteSpace: normal;min-width:60px;";
    $obj[0][$this->gridname]['columns'][$sectname]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$deptname]['style'] = "width:60px;whiteSpace: normal;min-width:60px;";
    $obj[0][$this->gridname]['columns'][$deptname]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$deptname]['label'] = "Department Name";

    if ($this->isapprover($config) == 0) {
      $obj[0][$this->gridname]['columns'][$action]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$rolename]['type'] = "label";
    }



    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  private function isapprover($config)
  {
    $checking = $this->coreFunctions->datareader("select isapprover as value from employee where empid=? order by empid desc limit 1", [$config['params']['tableid']], '', true);
    if ($checking == 0) {
      $checking = $this->coreFunctions->datareader("select issupervisor as value from employee where empid=? order by empid desc limit 1", [$config['params']['tableid']], '', true);
    }
    return $checking;
  }


  public function createtabbutton($config)
  {
    $doc = $config['params']['doc'];
    $tbuttons = ['addrecord', 'saveallentry', 'masterfilelogs'];

    if ($this->isapprover($config) == 0) {
      $tbuttons = [];
    }

    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }


  public function add($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['empid'] = $config['params']['tableid'];
    $data['roleid'] = 0;
    $data['rolename'] = '';
    $data['divname'] = '';
    $data['sectname'] = '';
    $data['deptname'] = '';
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  private function selectqry()
  {
    $qry = "r.line, r.empid, r.roleid";

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
        if ($data[$key]['line'] == 0) {
          $line = $this->coreFunctions->insertGetId($this->table, $data2);

          $config['params']['doc'] = strtoupper('emp_rolesetup');
          $this->logger->sbcmasterlog(
            $line,
            $config,
            'CREATE - LINE: ' . $line
              . ' - ROLE: ' . $data[$key]['rolename']
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
    $data = [];
    $row = $config['params']['row'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }

    if ($row['line'] == 0) {
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($line, $config);

        $config['params']['doc'] = strtoupper('emp_rolesetup');
        $this->logger->sbcmasterlog(
          $line,
          $config,
          'CREATE - LINE: ' . $line
            . ' - ROLE: ' . $data['rolename']
        );

        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($row['line'], $config);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  public function delete($config)
  {
    $row = $config['params']['row'];
    $qry = "delete from " . $this->table . " where line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }


  private function loaddataperrecord($line, $config)
  {
    $tableid = $config['params']['tableid'];
    $select = $this->selectqry();
    $select = $select . ", l.name as rolename,divi.divname,sec.sectname,dept.clientname as deptname, '' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " as r
    left join rolesetup as l on r.roleid = l.line
    left join division as divi on divi.divid = l.divid 
    left join section as sec on sec.sectid = l.sectionid
    left join client as dept on dept.clientid = l.deptid
    where r.line = ? and r.empid = ?
    order by r.line";
    $data = $this->coreFunctions->opentable($qry, [$line, $tableid]);
    return $data;
  }

  public function loaddata($config)
  {
    $tableid = $config['params']['tableid'];
    $select = $this->selectqry();
    $select = $select . ", l.name as rolename,divi.divname,sec.sectname,dept.clientname as deptname, '' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " as r
    left join rolesetup as l on r.roleid = l.line
    left join division as divi on divi.divid = l.divid 
    left join section as sec on sec.sectid = l.sectionid
    left join client as dept on dept.clientid = l.deptid
    where r.empid = ?
    order by r.line";
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
      case 'role':
        return $this->lookuprole($config);
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
      ['name' => 'editby', 'label' => 'Edited By', 'align' => 'left', 'field' => 'editby', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'editdate', 'label' => 'Edited Date', 'align' => 'left', 'field' => 'editdate', 'sortable' => true, 'style' => 'font-size:16px;'],
    ];

    $doc = strtoupper('emp_rolesetup');
    $qry = "
      select trno, doc, task, dateid, user, editby, editdate
      from " . $this->tablelogs . "
      where doc = ?
      order by dateid desc
    ";

    $data = $this->coreFunctions->opentable($qry, [$doc, $doc]);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
  }

  public function lookuprole($config)
  {
    $rowindex = $config['params']['index'];
    $lookupclass2 = $config['params']['lookupclass2'];
    $empid = $config['params']['tableid'];
    $companyid = $config['params']['companyid'];

    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Role',
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'plotgrid',
      'plotting' => array(
        'roleid' => 'line',
        'rolename' => 'name',
        'divname' => 'divname',
        'sectname' => 'sectname',
        'deptname' => 'deptname',
        'supervisorid' => 'supervisorid',
      )
    );

    $cols = array(
      array('name' => 'name', 'label' => 'Role Name', 'align' => 'left', 'field' => 'name', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'divname', 'label' => 'Division Name', 'align' => 'left', 'field' => 'divname', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'deptname', 'label' => 'Depart Name', 'align' => 'left', 'field' => 'deptname', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'sectname', 'label' => 'Sectname Name', 'align' => 'left', 'field' => 'sectname', 'sortable' => true, 'style' => 'font-size:16px;'),
    );
    $rolelist = [];
    $filter = "";

    $query = "select roleid from emprole where empid = $empid";
    $selectedrole = $this->coreFunctions->opentable($query);

    foreach ($selectedrole as $role) {
      array_push($rolelist, $role->roleid);
    }
    $roleid = !empty($rolelist) ? implode(",", $rolelist) : 0;
    $filter = " where rol.line not in (" . $roleid . ")";



    // $qry = "select line, name, supervisorid from rolesetup order by line";
    $qry = "select line, name, supervisorid,divi.divname,sec.sectname,dept.clientname as deptname from rolesetup as rol
            left join division as divi on divi.divid = rol.divid 
            left join section as sec on sec.sectid = rol.sectionid
            left join client as dept on dept.clientid = rol.deptid
            $filter
            order by line";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $rowindex];
  }
} //end class
