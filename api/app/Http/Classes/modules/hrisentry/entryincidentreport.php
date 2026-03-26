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
use App\Http\Classes\lookup\hrislookup;

class entryincidentreport
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'PERSONS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'incidentdtail';
  private $htable = 'hincidentdtail';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['line', 'empid', 'jobid'];
  public $showclosebtn = true;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->hrislookup = new hrislookup;
  }

  public function getAttrib()
  {
    $attrib = array('load' => 0);
    return $attrib;
  }

  public function createTab($config)
  {
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'empcode', 'empname', 'jobtitle']]];

    $stockbuttons = ['delete'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][0]['style'] = "width:80px;whiteSpace: normal;min-width:80px;"; //action
    $obj[0][$this->gridname]['columns'][3]['readonly'] = true; //jobtitle

    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['addempgrid', 'deleteallitem'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[1]['label'] = 'Delete all';
    $obj[1]['lookupclass'] = 'loaddata';
    return $obj;
  }

  public function lookupsetup($config)
  {
    $lookupclass = $config['params']['lookupclass2'];
    switch ($lookupclass) {
      case 'addempgrid':
        return $this->hrislookup->lookupempgrid($config);
        break;
    }
  }

  public function lookupcallback($config)
  {
    $id = $config['params']['tableid'];
    $row = $config['params']['rows'];
    $data = [];
    foreach ($row  as $key2 => $value) {
      $config['params']['data']['line'] = 0;
      $config['params']['data']['trno'] = $id;
      $config['params']['data']['empid'] = $value['clientid'];
      $config['params']['data']['empcode'] = $value['client'];
      $config['params']['data']['empname'] = $value['clientname'];
      $config['params']['data']['jobid'] = $value['jobid'];
      $config['params']['data']['jobtitle'] = $value['jobtitle'];
      $config['params']['data']['bgcolor'] = 'bg-blue-2';
      $return = $this->save($config);
      if ($return['status']) {
        array_push($data, $return['row'][0]);
      }
    }

    return ['status' => true, 'msg' => 'Successfully added.', 'data' => $data];
  } // end function

  public function save($config)
  {
    $data = [];
    $row = $config['params']['data'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    $data['trno'] = $config['params']['tableid'];
    if ($row['line'] == 0) {
      $qry = "select line as value from " . $this->table . " where trno=? order by line desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$data['trno']]);
      if (!$line) {
        $line = 0;
      }
      $line = $line + 1;
      $data["line"] = $line;
      if ($this->coreFunctions->sbcinsert($this->table,  $data)) {

        $returnrow = $this->loaddataperrecord($data['trno'], $line, $config);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  public function delete($config)
  {
    $row = $config['params']['row'];
    $id = $config['params']['tableid'];
    $doc = $config['params']['doc'];

    $checking = $this->coreFunctions->datareader("select count(postdate) as value 
    from hrisnum where trno = '$id' and postdate is not null and doc = '$doc'");
    if ($checking > 0) {
      return ['status' => false, 'msg' => "Transaction Already Posted!", 'data' => []];
    }

    $qry = "delete from " . $this->table . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['trno'], $row['line']]);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }

  public function deleteallitem($config)
  {
    $trno = $config['params']['tableid'];
    $doc = $config['params']['doc'];

    $checking = $this->coreFunctions->datareader("select count(postdate) as value 
    from hrisnum where trno = '$trno' and postdate is not null and doc = '$doc'");
    if ($checking > 0) {
      $data = $this->loaddata($config);
      return ['status' => false, 'msg' => "Transaction Already Posted!", 'data' => $data];
    }

    $qry = "delete from " . $this->table . " where trno=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno]);
    return ['status' => true, 'msg' => 'Successfully deleted.', 'data' => []];
  }

  private function loaddataperrecord($trno, $line)
  {
    $select = $this->selectqry();
    $qry = $select . " 
        from " . $this->table . " as i left join client as c on c.clientid=i.empid left join jobthead as j on j.line=i.jobid 
        where i.trno=? and i.line=?";
    $data = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $data;
  }

  private  function selectqry()
  {
    return "select i.trno, i.line, i.empid, c.client as empcode, c.clientname as empname, i.jobid, j.jobtitle";
  }

  public function loaddata($config)
  {
    $trno = $config['params']['tableid'];
    $select = $this->selectqry();
    $qry = $select . " 
        from " . $this->table . " as i left join client as c on c.clientid=i.empid left join jobthead as j on j.line=i.jobid where i.trno=?
        union all " . $select . " 
        from " . $this->htable . " as i left join client as c on c.clientid=i.empid left join jobthead as j on j.line=i.jobid where i.trno=?";
    $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $data;
  }
} //end class
