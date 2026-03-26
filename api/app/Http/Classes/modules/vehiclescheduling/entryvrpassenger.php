<?php

namespace App\Http\Classes\modules\vehiclescheduling;

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
use App\Http\Classes\lookup\constructionlookup;

class entryvrpassenger
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'PASSENGERS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'vrpassenger';
  private $htable = 'hvrpassenger';
  private $othersClass;
  public $style = 'width:100%;max-width: 100%';
  private $fields = ['trno', 'line', 'passengerid'];
  public $showclosebtn = true;
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->constructionlookup = new constructionlookup;
    $this->sqlquery = new sqlquery;
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = ['load' => 0];
    return $attrib;
  }

  public function createTab($config)
  {

    $action = 0;
    $client = 1;
    $clientname = 2;

    $tab = [$this->gridname => ['gridcolumns' => ['action', 'client', 'clientname']]];
    $stockbuttons = ['save', 'delete'];
    if ($config['params']['doc'] == 'VL') {
      $stockbuttons = [];
    }
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$client]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';
    $obj[0][$this->gridname]['columns'][$clientname]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;';

    $obj[0][$this->gridname]['columns'][$client]['label'] = 'Passenger Code';
    $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Passenger Name';
    $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][$clientname]['readonly'] = true;

    $obj[0][$this->gridname]['columns'][$client]['type'] = 'lookup';
    $obj[0][$this->gridname]['columns'][$client]['action'] = 'lookupsetup';
    $obj[0][$this->gridname]['columns'][$client]['lookupclass'] = 'lookuppassenger';

    if ($config['params']['doc'] == 'VL') {
      $obj[0][$this->gridname]['columns'][$action]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$client]['type'] = 'input';
      $obj[0][$this->gridname]['columns'][$client]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$clientname]['readonly'] = true;
    }

    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    $this->modulename = 'PASSENGERS - ' . $config['params']['row']['clientname'];
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['additem', 'saveallentry'];
    if ($config['params']['doc'] == 'VL') {
      $tbuttons = [];
    }
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['action'] = "lookupsetup";
    $obj[0]['icon'] = "person_add";
    $obj[0]['label'] = "ADD PASSENGER";
    return $obj;
  }

  public function lookupsetup($config)
  {

    $lookupclass2 = $config['params']['lookupclass2'];

    switch ($lookupclass2) {
      case 'additem':
        return $this->lookupaddpassenger($config);
        break;

      case 'lookuppassenger':
        return $this->lookuppassenger($config);
        break;

      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
        break;
    }
  }

  public function lookupcallback($config)
  {
    switch ($config['params']['lookupclass2']) {
      case 'addtogrid':
        $data = [];
        $trno = $config['params']['tableid'];
        $row = $config['params']['row'];

        $data['pline'] = 0;
        $data['trno'] = $row['trno'];
        $data['line'] = $row['line'];
        $data['passengerid'] = $row['passengerid'];
        $data['clientname'] = $row['passengername'];
        $data['client'] = $row['passenger'];
        $data['bgcolor'] = 'bg-blue-2';

        return ['status' => true, 'msg' => 'Add Passenger success...', 'data' => $data];
        break;
    }
  }

  public function lookupaddpassenger($config)
  {
    $trno = $config['params']['tableid'];
    $line = $config['params']['row']['line'];

    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Passenger',
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'callback',
      'action' => 'addtogrid'
    );
    // lookup columns
    $cols = [];
    array_push($cols, array('name' => 'passenger', 'label' => 'Passenger Code', 'align' => 'left', 'field' => 'passenger', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'passengername', 'label' => 'Passenger Name', 'align' => 'left', 'field' => 'passengername', 'sortable' => true, 'style' => 'font-size:16px;'));

    $qry = "select '0' as pline, " . $trno . " as trno, " . $line . " as line, clientid as passengerid, client as passenger, clientname as passengername from client where ispassenger = 1 and isinactive = 0 limit 500";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  } //end function

  public function lookuppassenger($config)
  {
    //default
    $plotting = array('client' => 'client', 'clientname' => 'clientname', 'passengerid' => 'clientid');
    $plottype = 'plotgrid';
    $title = 'List of Passenger';
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
    $cols = array();
    array_push($cols, array('name' => 'client', 'label' => 'Passenger Code', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'clientname', 'label' => 'Passenger Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'));

    $qry = "select clientid, clientname, client from client where ispassenger = 1 and isinactive = 0 limit 500";
    $data = $this->coreFunctions->opentable($qry);
    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  } // end function


  private function selectqry($config)
  {
    $decimalqty = $this->companysetup->getdecimal('qty', $config['params']);

    return "
    select vr.pline as pline, vr.line, vr.trno, vr.passengerid, client.clientname, client.client";
  }

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    $data['trno'] = $config['params']['tableid'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    if ($row['pline'] == 0) {
      $pline = $this->coreFunctions->insertGetId($this->table, $data);
      if ($pline != 0) {
        $returnrow = $this->loaddataperrecord($config, $pline);
        $this->logger->sbcwritelog($data['trno'], $config, ' CREATE - PASSENGER', $row['clientname']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['pline' => $row['pline']]) == 1) {
        $returnrow = $this->loaddataperrecord($config, $row['pline']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  public function saveallentry($config)
  {
    $data = $config['params']['data'];

    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }

        $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data2['editby'] = $config['params']['user'];

        if ($data[$key]['pline'] != 0) {
          $this->coreFunctions->sbcupdate($this->table, $data2, ['pline' => $data[$key]['pline']]);
        } else {
          $data2['trno'] = $data[$key]['trno'];
          $line = $this->coreFunctions->insertGetId($this->table, $data2);
          $this->logger->sbcwritelog($data[$key]['trno'], $config, ' CREATE - PASSENGER', $data[$key]['clientname']);
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
  } // end function $$

  public function delete($config)
  {
    $row = $config['params']['row'];
    $qry = "delete from " . $this->table . " where trno=? and line=? and pline=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['trno'], $row['line'], $row['pline']]);
    $this->logger->sbcdel_log($row['trno'], $config, 'REMOVE - PASSENGER', $row['clientname']);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }

  private function loaddataperrecord($config, $pline)
  {

    $trno = $config['params']['row']['trno'];
    $line = $config['params']['row']['line'];

    $qry = $this->selectqry($config);
    $qry = $qry . ",'' as bgcolor  from " . $this->table . " as vr
    left join client on vr.passengerid = client.clientid 
    where vr.trno = ? and vr.line = ? and vr.pline = ?";
    $data = $this->coreFunctions->opentable($qry, [$trno, $line, $pline]);
    return $data;
  }

  public function loaddata($config)
  {

    $trno = $config['params']['row']['trno'];
    $line = $config['params']['row']['line'];

    $qry = $this->selectqry($config);
    $qry = $qry . ",'' as bgcolor  from " . $this->table . " as vr
    left join client on vr.passengerid = client.clientid 
    where vr.trno = ? and vr.line = ?";
    $data = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $data;
  }
} //end class
