<?php

namespace App\Http\Classes\modules\enrollmententry;

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
use Symfony\Component\Translation\LoggingTranslator;

class entrysubject
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'EQUIVALENT SUBJECTS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'en_subjectequivalent';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'masterfile_log';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['line', 'subjectid', 'subjectmain'];
  public $showclosebtn = true;
  private $logger;


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
    $attrib = array(
      'load' => 0
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'subjectcode', 'subjectname', 'units', 'lecture']]];

    $stockbuttons = ['save', 'delete'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][0]['style'] = "width:40px;whiteSpace: normal;min-width:40px;"; //action
    $obj[0][$this->gridname]['columns'][1]['readonly'] = true; //subjectcode
    $obj[0][$this->gridname]['columns'][1]['style'] = 'width:120px;whiteSpace:normal;';
    $obj[0][$this->gridname]['columns'][2]['readonly'] = true; //subjectname
    $obj[0][$this->gridname]['columns'][2]['style'] = 'width:200px;whiteSpace:normal;';
    $obj[0][$this->gridname]['columns'][3]['readonly'] = true; //units
    $obj[0][$this->gridname]['columns'][3]['style'] = 'width:100px;whiteSpace:normal;';
    $obj[0][$this->gridname]['columns'][4]['readonly'] = true; //lecture
    $obj[0][$this->gridname]['columns'][4]['style'] = 'width:100px;whiteSpace:normal;';

    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['addsubject'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function lookupsetup($config)
  {
    $lookupclass = $config['params']['lookupclass2'];
    switch ($lookupclass) {
      case 'addsubject':
        return $this->enrollmentlookup->lookupsubject($config);
        break;
    }
  }

  public function lookupcallback($config)
  {
    $id = $config['params']['tableid'];
    $row = $config['params']['row'];
    $data = [];
    $data['line'] = 0;
    $data['subjectmain'] = $id;
    $data['subjectid'] = $row['trno'];
    $data['subjectcode'] = $row['subjectcode'];
    $data['subjectname'] = $row['subjectname'];
    $data['units'] = $row['units'];
    $data['lecture'] = $row['lecture'];
    $data['bgcolor'] = 'bg-blue-2';
    return ['status' => true, 'msg' => 'Add Subject success...', 'data' => $data];
  } // end function

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }

    if ($row['line'] == 0) {
      $check = $this->checkSubject($data, 'new');
      if ($check) {
        return ['status' => false, 'msg' => 'Duplicate entry for '.$row['subjectname'], 'row' => []];
      } else {
        $line = $this->coreFunctions->insertGetId($this->table, $data);
        $this->logger->sbcmasterlog($data['subjectmain'], $config, ' CREATE EQUIVALENT SUBJECT - ' . $row['subjectname']);
        if ($line != 0) {
          $returnrow = $this->loaddataperrecord($row['subjectmain'], $line);
          return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
        } else {
          return ['status' => false, 'msg' => 'Saving failed.'];
        }
      }
    } else {
      $check = $this->checkSubject($data);
      if ($check) {
        return ['status' => false, 'msg' => 'Duplicate entry for '.$row['subjectname'], 'row' => []];
      } else {
        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];
        if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
          $returnrow = $this->loaddataperrecord($row['subjectmain'], $row['line']);
          return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
        } else {
          return ['status' => false, 'msg' => 'Saving failed.'];
        }
      }
    }
  }

  public function checkSubject($data, $type = 'update')
  {
    $qry = "select line as value from en_subjectequivalent where subjectmain=".$data['subjectmain']." and subjectid=".$data['subjectid'];
    if ($type == 'update') {
      $qry = "select line as value from en_subjectequivalent where subjectmain=".$data['subjectmain']." and subjectid=".$data['subjectid']." and line<>".$data['line'];
    }
    $check = $this->coreFunctions->datareader($qry);
    if ($check != '') return true;
    return false;
  }

  public function delete($config)
  {
    $row = $config['params']['row'];
    $qry = "delete from " . $this->table . " where subjectmain=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['subjectmain'], $row['line']]);
    $this->logger->sbcdelmaster_log($row['subjectmain'], $config, 'REMOVE EQUIVALENT SUBJECT - ' . $row['subjectname']);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }

  private function loaddataperrecord($clientid, $line)
  {
    $select = $this->selectqry();
    $qry = $select . " where e.subjectmain=? and e.line=?";
    $data = $this->coreFunctions->opentable($qry, [$clientid, $line]);
    return $data;
  }

  private  function selectqry()
  {
    return "select e.line, e.subjectid, e.subjectmain, s.subjectcode, s.subjectname, s.units , s.lecture
    from en_subjectequivalent as e left join en_subject as s on s.trno=e.subjectid";
  }

  public function loaddata($config)
  {
    $tableid = $config['params']['tableid'];
    $select = $this->selectqry();
    $qry = $select . " where e.subjectmain=?";
    $data = $this->coreFunctions->opentable($qry, [$tableid]);
    return $data;
  }
} //end class
