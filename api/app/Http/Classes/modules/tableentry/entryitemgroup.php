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

class entryitemgroup
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'ITEM GROUP';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $logger;
  private $table = 'itemgroup';
  private $othersClass;
  public $style = 'width:100%;';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $fields = ['clientid', 'projectid'];
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

    if (isset($config['params']['tableid'])) {
      $clientid = $config['params']['tableid'];
      $customername = $this->coreFunctions->datareader("select clientname as value from client where clientid = ? ", [$clientid]);
      $this->modulename = $this->modulename . ' - ' . $customername;
    }


    $action = 0;
    $project = 1;
    $hidden = 2;

    $tab = [
      $this->gridname => [
        'gridcolumns' => ['action', 'project', 'classid']
      ]
    ];

    $stockbuttons = ['save', 'delete'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:100px;whiteSpace: normal;min-width:120px;";
    $obj[0][$this->gridname]['columns'][$project]['style'] = "width:100px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$hidden]['style'] = "width:500px;whiteSpace: normal;min-width:500px;";
    $obj[0][$this->gridname]['columns'][$project]['action'] = "lookupsetup";
    $obj[0][$this->gridname]['columns'][$project]['lookupclass'] = "lookupproject";
    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = ['addrecord', 'saveallentry', 'masterfilelogs'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['type'] = "lookup";
    $obj[0]['action'] = "lookupsetup";
    $obj[0]['lookupclass'] = "addproject";
    $obj[0]['label'] = "ADD PROJECT";
    return $obj;
  }


  public function add($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['clientid'] = $config['params']['tableid'];
    $data['projectid'] = '';
    $data['project'] = '';
    $data['projectname'] = '';
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    $tableid = $config['params']['tableid'];
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }

        if ($data[$key]['line'] == 0) {
          $status = $this->coreFunctions->insertGetId($this->table, $data2);

          $params = $config;
          $params['params']['doc'] = strtoupper("entryitemgroup");
          $this->logger->sbcmasterlog(
            $tableid,
            $params,
            ' CREATE - LINE: ' . $status . ''
              . ', PROJECT NAME: ' . $data[$key]['project']
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
    $tableid = $config['params']['tableid'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }

    if ($row['line'] == 0) {
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($config, $line);
        $params = $config;
        $params['params']['doc'] = strtoupper("entryitemgroup");
        $this->logger->sbcmasterlog(
          $tableid,
          $params,
          ' CREATE - LINE: ' . $line . ''
            . ', PROJECT NAME: ' . $row['project']
        );
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
    $tableid = $config['params']['tableid'];
    $row = $config['params']['row'];
    $data = $this->loaddataperrecord($config, $row['line']);

    $qry = "delete from " . $this->table . " where line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);

    $params = $config;
    $params['params']['doc'] = strtoupper("entryitemgroup");
    $this->logger->sbcmasterlog(
      $tableid,
      $params,
      ' DELETE - LINE: ' . $row['line'] . ''
        . ', PROJECT NAME: ' . $row['project']
    );
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }


  private function loaddataperrecord($config, $line)
  {

    $tableid = $config['params']['tableid'];
    $qry = "select ig.line, ig.clientid, ig.projectid, pm.name as project, '' as bgcolor
    from " . $this->table . " as ig
    left join projectmasterfile as pm on pm.line = ig.projectid
    where ig.clientid = " . $tableid . " and ig.line = ? order by line";
    $data = $this->coreFunctions->opentable($qry, [$line]);
    return $data;
  }

  public function loaddata($config)
  {
    $tableid = $config['params']['tableid'];
    $qry = "select ig.line, ig.clientid, ig.projectid, pm.name as project, '' as bgcolor
    from " . $this->table . " as ig
    left join projectmasterfile as pm on pm.line = ig.projectid
    where ig.clientid = " . $tableid . " order by line";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }


  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
      case 'lookuplogs':
        return $this->lookuplogs($config);
        break;

      case 'addproject':
        return $this->addproject($config);  // to be follow
        break;

      case 'lookupproject':
        return $this->lookupproject($config);  // to be follow
        break;

      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup'];
        break;
    }
  }

  public function lookuplogs($config)
  {
    $doc = strtoupper("entryitemgroup");
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'Item Group Logs',
      'style' => 'width:1000px;max-width:1000px;'
    );

    // lookup columns
    $cols = array(
      array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $clientid = $config['params']['tableid'];

    $qry = "
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from " . $this->tablelogs . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "' and trno = $clientid
    union all
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from  " . $this->tablelogs_del . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "' and trno = $clientid ";

    $qry = $qry . " order by dateid desc";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
  }

  public function lookupproject($config)
  {
    $rowindex = $config['params']['index'];
    $lookupclass2 = $config['params']['lookupclass2'];

    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Project',
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'plotgrid',
      'plotting' => ['projectid' => 'line', 'project' => 'name']
    );

    $cols = array(
      array('name' => 'name', 'label' => 'Project Name', 'align' => 'left', 'field' => 'name', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $qry = "select line, code, name from projectmasterfile order by line";

    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $rowindex];
  }

  public function addproject($config)
  {

    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Project',
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'callback',
      'action' => 'addtogrid'
    );


    $cols = array(
      array('name' => 'project', 'label' => 'Project Name', 'align' => 'left', 'field' => 'project', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $qry = "select line, name as project from projectmasterfile order by line";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookupcallback($config)
  {
    $row = $config['params']['row'];

    switch ($config['params']['lookupclass2']) {
      case 'addtogrid':
        $data = [];
        $data['line'] = 0;
        $data['clientid'] = $config['params']['tableid'];
        $data['projectid'] = $row['line'];
        $line = $this->coreFunctions->insertGetId($this->table, $data);
        if ($line != 0) {
          $returnrow = $this->loaddataperrecord($config, $line);
          $params = $config;
          $params['params']['doc'] = strtoupper("entryitemgroup");
          $this->logger->sbcmasterlog(
            $config['params']['tableid'],
            $params,
            ' CREATE - LINE: ' . $line . ''
              . ', PROJECT NAME: ' . $row['project']
          );
          $data['project'] = $row['project'];
          return ['status' => true, 'msg' => 'Add stage success.', 'data' => $data];
        } else {
          return ['status' => false, 'msg' => 'Saving failed.'];
        }
        break;
    }
  }


  // -> Print Function
  public function reportsetup($config)
  {
    return [];
  }


  public function createreportfilter()
  {
    return [];
  }

  public function reportparamsdata($config)
  {
    return [];
  }

  public function reportdata($config)
  {
    return [];
  }
} //end class
