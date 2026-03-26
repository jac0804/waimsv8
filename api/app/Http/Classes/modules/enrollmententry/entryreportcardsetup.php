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

class entryreportcardsetup
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'REPORT CARD SETUP';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'en_rcdetail';
  public $tablenum = 'transnum';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = [];
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
  }

  public function getAttrib()
  {
    $attrib = array('load' => 0);
    return $attrib;
  }

  public function createTab($config)
  {
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'subjectcode', 'subjectname', 'rctitle']]];
    $stockbuttons = ['save'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][0]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";
    $obj[0][$this->gridname]['columns'][1]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][1]['style'] = 'width:100px;whiteSpace:normal;min-width:100px;';
    $obj[0][$this->gridname]['columns'][2]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][2]['style'] = 'width:150px;whiteSpace:normal;min-width:150px;';
    $obj[0][$this->gridname]['columns'][3]['style'] = 'width:100px;whiteSpace:normal;min-width:100px;';
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['saveallentry'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function saveallentry($config)
  {
    $config['params']['trno'] = $config['params']['tableid'];
    $isposted = $this->othersClass->isposted($config);
    if ($isposted) {
      $table = "en_glsubject";
    } else {
      $table = "en_scsubject";
    }
    $data = $config['params']['data'];
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        $data2 = ['rctrno' => $data[$key]['rctrno'], 'rcline' => $data[$key]['rcline']];
        $this->coreFunctions->sbcupdate($table, $data2, ['line' => $data[$key]['line'], 'trno' => $data[$key]['trno']]);
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
  } // end function 

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    $config['params']['trno'] = $row['trno'];
    $isposted = $this->othersClass->isposted($config);
    if ($isposted) {
      $table = "en_glsubject";
    } else {
      $table = "en_scsubject";
    }
    $data = ['rctrno' => $row['rctrno'], 'rcline' => $row['rcline']];
    if ($this->coreFunctions->sbcupdate($table, $data, ['trno' => $row['trno'], 'line' => $row['line']]) == 1) {
      $returnrow = $this->loaddataperrecord($table, $row['courseid'], $row['trno'], $row['line']);
      return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
    } else {
      return ['status' => false, 'msg' => 'Saving failed.'];
    }
  } //end function

  public function delete($config)
  {
    $row = $config['params']['row'];
    $qry = "delete from " . $this->table . " where line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }

  private function selectqry()
  {
    $qry = "line";
    foreach ($this->fields as $key => $value) {
      $qry = $qry . ',' . $value;
    }
    return $qry;
  }

  private function loaddataperrecord($table, $courseid, $trno, $line)
  {
    $qry = "select " . $courseid . " as courseid, sub.subjectcode, sub.subjectname, sc.trno, sc.line, sc.rctrno, sc.rcline, rc.title as rctitle, '' as bgcolor from " . $table . " as sc left join en_rcdetail as rc on rc.trno=sc.rctrno and rc.line=sc.rcline left join en_subject as sub on sub.trno=sc.subjectid where sc.trno=? and sc.line=?";
    $data = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $data;
  }

  public function loaddata($config)
  {
    $trno = $config['params']['tableid'];
    $doc = $config['params']['doc'];
    $config['params']['trno'] = $config['params']['tableid'];
    $isposted = $this->othersClass->isposted($config);
    if ($isposted) {
      $head = "en_glhead";
      $stock = "en_glsubject";
    } else {
      $head = "en_schead";
      $stock = "en_scsubject";
    }
    $courseid = $this->coreFunctions->getfieldvalue($head, "courseid", "trno=? and doc=?", [$trno, $doc]);

    $qry = "select " . $courseid . " as courseid, sub.subjectcode, sub.subjectname, sc.trno, sc.line, sc.rctrno, sc.rcline, rc.title as rctitle, '' as bgcolor from " . $stock . " as sc left join en_rcdetail as rc on rc.trno=sc.rctrno and rc.line=sc.rcline left join en_subject as sub on sub.trno=sc.subjectid where sc.trno=?";
    $data = $this->coreFunctions->opentable($qry, [$trno]);
    return $data;
  }

  public function lookupsetup($config)
  {
    $lookupclass = $config['params']['lookupclass2'];

    switch ($lookupclass) {
      case 'lookupreportcardsetup':
        return $this->enrollmentlookup->lookupreportcardsetup($config);
        break;
    }
  }
} //end class
