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

class entrytraining
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'TRAINING';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'traininghead';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $logger;
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['empid', 'venue', 'title', 'dateid','istraining'];
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
    $attrib = array('load' => 0);
    return $attrib;
  }


  public function createTab($config)
  {
    $doc = $config['params']['doc'];
    $cols = ['action', 'venue', 'title', 'dateid'];
    foreach ($cols as $key => $value) {
      $$value = $key;
    }

    $tab = [$this->gridname => ['gridcolumns' => $cols]];
  
    $stockbuttons = ['save', 'delete'];
      
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][$venue]['label'] = "Training School";
    $obj[0][$this->gridname]['columns'][$title]['label'] = "Training/Seminar Attended";
    $obj[0][$this->gridname]['columns'][$dateid]['label'] = "Date Attended";

    $obj[0][$this->gridname]['columns'][$venue]['readonly'] = false;
    $obj[0][$this->gridname]['columns'][$title]['readonly'] = false;
    return $obj;
  }


  public function createtabbutton($config)
  {
    // $tbuttons = ['addrecord','saveallentry', 'masterfilelogs'];
    
    $tbuttons = ['addrecord', 'saveallentry','masterfilelogs'];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }

  public function add($config)
  {
    $id = $config['params']['tableid'];
    $data = [];
    $data['empid'] = $id;
    $data['trno'] = 0;
    $data['venue'] = '';
    $data['title'] = '';
    $data['dateid'] = '';
    $data['istraining'] = 1;
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  private function selectqry()
  {
    $qry = "trno, empid, venue, title, left(dateid,10) as dateid,istraining";
    return $qry;
  }

  public function save($config)
  {
    $companyid = $config['params']['companyid'];
    $dateTables = [$this->table];
    $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);

    $data = [];
    $row = $config['params']['row'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfieldFast($value, $row[$value],$lookups);
    }
    if ($row['trno'] == 0) {
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($row['empid'], $line);

        $config['params']['doc'] = strtoupper('emp_training2');
        $this->logger->sbcwritelog(
          $row['empid'],
          $config,
          'TRAINING',
          'CREATE - LINE: ' . $line
            . ' - VENUE: ' . $data['venue']
            . ' , TITLE: ' . $data['title']
            . ' , DATE ATTENDED: ' . $data['dateid']
        );

        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['trno' => $row['trno'],'empid'=>$row['empid']]) == 1) {
        $returnrow = $this->loaddataperrecord($row['empid'], $row['trno']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  public function saveallentry($config)
  {
    $companyid = $config['params']['companyid'];
    $dateTables = [$this->table];
    $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);

    $empid = $config['params']['tableid'];
    $data = $config['params']['data'];
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfieldFast($value2, $data[$key][$value2],$lookups);
        }
        if ($data[$key]['trno'] == 0) {

          $data2['createdate'] = $this->othersClass->getCurrentTimeStamp();
          $data2['createby'] = $config['params']['user'];
          $line = $this->coreFunctions->insertGetId($this->table, $data2);

          $config['params']['doc'] = strtoupper('emp_training2');
          $this->logger->sbcwritelog(
            $empid,
            $config,
            'TRAINING',
            'CREATE - LINE: ' . $line
              . ' - VENUE: ' . $data2['venue']
              . ' , TITLE: ' . $data2['title']
              . ' , DATE ATTENDED: ' . $data2['dateid']
          );
        } else {
          $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
          $data2['editby'] = $config['params']['user'];
          $this->coreFunctions->sbcupdate($this->table, $data2, ['trno' => $data[$key]['trno']]);
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'Saved all Successfully', 'data' => $returndata];
  } // end function

  public function delete($config)
  {
    $row = $config['params']['row']; 
    $qry = "delete from " . $this->table . " where empid=? and trno=? and istraining =1";
    $this->coreFunctions->execqry($qry, 'delete', [$row['empid'], $row['trno']]);

    $config['params']['doc'] = strtoupper('emp_training2');
    $this->logger->sbcwritelog(
      $row['empid'],
      $config,
      'TRAINING',
      'DELETE - LINE: ' . $row['trno']
        . ' - VENUE: ' . $row['venue']
        . ' , TITLE: ' . $row['title']
        . ' , DATE ATTENDED: ' . $row['dateid']
    );

    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }


  private function loaddataperrecord($empid, $line)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " where empid=? and trno=? and istraining=1";
    $data = $this->coreFunctions->opentable($qry, [$empid, $line]);
    return $data;
  }

  public function loaddata($config)
  {
    $tableid = $config['params']['tableid'];
    $center = $config['params']['center'];
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " where empid=? and istraining=1
    order by trno";
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
    $empid=$config['params']['tableid'];

    $lookupsetup = array(
      'type' => 'show',
      'title' => 'List of Logs',
      'style' => 'width:1000px;max-width:1000px;'
    );

    $cols = [
      ['name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;']
    ];

    $doc = strtoupper('emp_training2');
    $qry = "
      select trno, doc, task, left(dateid,10) as dateid, user, editby, editdate
      from " . $this->tablelogs . "
      where doc = ? and trno = ?
      order by dateid desc";
    $data = $this->coreFunctions->opentable($qry, [$doc,$empid]);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
  }
} //end class
