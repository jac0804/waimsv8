<?php

namespace App\Http\Classes\modules\announcemententry;

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

class entrynotice
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'NOTICE';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $logger;
  private $table = 'waims_notice';
  private $othersClass;
  public $style = 'width:100%;';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $fields = ['dateid', 'title', 'rem', 'roleid', 'empid'];
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
      'load' => 1362
    );
    return $attrib;
  }

  public function createTab($config)
  {

    $column = ['action', 'dateid', 'title', 'rem', 'rolename', 'clientname'];

    foreach ($column as $key => $value) {
      $$value = $key;
    }
    $tab = [
      $this->gridname => [
        'gridcolumns' => $column
      ]
    ];

    $stockbuttons = ['save', 'delete', 'addattachments'];
    // $stockbuttons = ['save', 'delete', 'additemsubcat'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";
    // title 
    $obj[0][$this->gridname]['columns'][$title]['readonly'] = false;

    $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'lookup';
    $obj[0][$this->gridname]['columns'][$clientname]['lookupclass'] = 'lookupemployee';
    $obj[0][$this->gridname]['columns'][$clientname]['action'] = 'lookupsetup';
    $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Employee Name';
    $obj[0][$this->gridname]['columns'][$rolename]['label'] = 'User Level';

    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = ['addrecord', 'saveallentry', 'whlog'];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }


  public function add($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['dateid'] = date('Y-m-d');
    $data['title'] = '';
    $data['rem'] = '';
    $data['rolename'] = '';
    $data['roleid'] = 0;
    $data['clientname'] = '';
    $data['empid'] = 0;
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  private function selectqry()
  {
    $qry = "line";
    foreach ($this->fields as $key => $value) {
      if ($value == 'rem') {
        $qry = $qry . ',' . 'waims_notice.' . $value;
      } else if ($value == 'empid') {
        $qry = $qry . ',' . 'waims_notice.' . $value;
      } else {
        $qry = $qry . ',' . $value;
      }
    }
    return $qry;
  }

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    $maxLength = 100;
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }
        if ($data2['roleid'] != 0 && $data2['empid'] != 0) {
          return ['status' => false, 'msg' => "Can't save role and employee at once"];
        }

        if (strlen($data2['title']) > $maxLength) {
          $data2['title'] = substr($data2['title'], 0, $maxLength);
        }
        if ($data[$key]['line'] == 0) {
          $data2['encodeddate'] = $this->othersClass->getCurrentTimeStamp();
          $data2['encodedby'] = $config['params']['user'];
          $insert = $this->coreFunctions->insertGetId($this->table, $data2);
          if ($insert != 0) {
            $this->logger->sbcmasterlog($data[$key]['line'], $config, ' CREATE - ' . $data[$key]['title']);
          } else {
            return ['status' => false, 'msg' => 'Failed to save the record.', 'data' => []];
          }
        } else {
          $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
          $data['editby'] = $config['params']['user'];
          $update = $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
          if (!$update) {
            return ['status' => false, 'msg' => 'Failed to update the record', 'data' => []];
          }
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
    if ($row['roleid'] != 0 && $row['empid'] != 0) {
      return ['status' => false, 'msg' => "Can't save role and employee at once"];
    }
    if ($row['line'] == 0) {
      $data['encodeddate'] = $this->othersClass->getCurrentTimeStamp();
      $data['encodedby'] = $config['params']['user'];
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($line);
        $this->logger->sbcmasterlog($row['line'], $config, ' CREATE - ' . $data['title']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($row['line']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
      case 'whlog':
        return $this->lookuplogs($config);
        break;
      case 'role':
        return $this->lookuprole($config);
        break;
      case 'lookupemployee':
        return $this->lookupemployee($config);
        break;

      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
        break;
    }
  }

  public function lookuprole($config)
  {
    //default
    $plotting = array();
    $plottype = '';
    $title = 'List of User Level';
    $plotting = array('roleid' => 'userid', 'rolename' => 'username');
    $plottype = 'plotgrid';

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );
    // lookup columns
    $cols = [
      ['name' => 'username', 'label' => 'Role', 'align' => 'left', 'field' => 'username', 'sortable' => true, 'style' => 'font-size:16px;']
    ];

    $qry = "select idno as userid, username from users";


    $data = $this->coreFunctions->opentable($qry);
    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  } //end function

  public function lookuplogs($config)
  {
    $doc = $config['params']['doc'];
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'Notice Logs',
      'style' => 'width:1000px;max-width:1000px;'
    );

    // lookup columns
    $cols = array(
      array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $trno = $config['params']['tableid'];

    $qry = "
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from " . $this->tablelogs . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "'
    union all
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from  " . $this->tablelogs_del . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "'";

    $qry = $qry . " order by dateid desc";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
  }
  public function lookupemployee($config)
  {
    //default
    $plotting = array();
    $plottype = '';
    $title = 'List of Employees';
    $plotting = array('clientname' => 'clientname', 'empid' => 'empid');
    $plottype = 'plotgrid';

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );
    // lookup columns
    $cols = [
      ['name' => 'clientname', 'label' => 'Employee Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'client', 'label' => 'Employee Code', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;']
    ];

    $qry = "select '' as clientname, '' as client,0 as empid
    union all 
    select cl.clientname,cl.client,emp.empid from employee as emp left 
	     join client as cl on cl.clientid = emp.empid 
	     where cl.isemployee=1 and emp.isactive=1";

    $data = $this->coreFunctions->opentable($qry);
    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  }
  public function delete($config)
  {
    $row = $config['params']['row'];
    $qry = "delete from " . $this->table . " where line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
    $this->logger->sbcdelmaster_log($row['line'], $config, 'REMOVE - ' . $row['title']);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }


  private function loaddataperrecord($line)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor,ifnull(u.username,'') as rolename,cl.clientname";
    $qry = "select " . $select . " from " . $this->table . " 
    left join users as u on u.idno = waims_notice.roleid
    left join client as cl on cl.clientid =  waims_notice.empid where line=?";
    $data = $this->coreFunctions->opentable($qry, [$line]);
    return $data;
  }

  public function loaddata($config)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor,ifnull(u.username,'') as rolename,cl.clientname ";
    $qry = "select " . $select . " from " . $this->table . " 
    left join users as u on u.idno = waims_notice.roleid
    left join client as cl on cl.clientid =  waims_notice.empid order by line";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }
} //end class
