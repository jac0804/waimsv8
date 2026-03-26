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

class  tabpassenger
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'PASSENGERS';
  public $tablenum = 'transnum';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'vrpassenger';
  private $htable = 'hvrpassenger';
  private $stock = 'vrstock';
  private $hstock = 'hvrstock';
  private $othersClass;
  public $style = 'width:100%;max-width: 100%';
  private $fields = ['trno', 'line', 'passengerid', 'dropoff'];
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

    $hidebuttons = false;
    $statid = $this->coreFunctions->getfieldvalue("transnum", "statid", "trno=?", [$config['params']['tableid']]);
    switch ($statid) {
      case '10':
      case '11':
      case '15':
        $hidebuttons = true;
        break;
    }

    $config['params']['trno'] = $config['params']['tableid'];
    if ($this->othersClass->isposted($config)) {
      $hidebuttons = true;
    }

    $action = 0;
    $dropoff = 1;
    $passengername = 2;
    $clientname = 3;

    $tab = [$this->gridname => ['gridcolumns' => ['action', 'dropoff', 'passengername', 'clientname']]];
    $stockbuttons = ['save', 'delete'];
    if ($config['params']['doc'] == 'VL' || $hidebuttons) {
      $stockbuttons = [];
    }
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][$dropoff]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
    $obj[0][$this->gridname]['columns'][$passengername]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $obj[0][$this->gridname]['columns'][$clientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $obj[0][$this->gridname]['columns'][$passengername]['label'] = 'Passenger Name';
    $obj[0][$this->gridname]['columns'][$passengername]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][$passengername]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Customer Name';
    $obj[0][$this->gridname]['columns'][$clientname]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'lookup';
    $obj[0][$this->gridname]['columns'][$clientname]['action'] = 'lookupsetup';
    $obj[0][$this->gridname]['columns'][$clientname]['lookupclass'] = 'lookupcustomer';

    if ($config['params']['doc'] == 'VL' || $hidebuttons) {
      $obj[0][$this->gridname]['columns'][$action]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$dropoff]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$clientname]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$passengername]['readonly'] = true;
    }
    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['additem', 'saveallentry'];

    $hidebuttons = false;
    $statid = $this->coreFunctions->getfieldvalue("transnum", "statid", "trno=?", [$config['params']['tableid']]);
    switch ($statid) {
      case '10':
      case '11':
      case '15':
        $hidebuttons = true;
        break;
    }

    $config['params']['trno'] = $config['params']['tableid'];
    if ($this->othersClass->isposted($config)) {
      $hidebuttons = true;
    }

    if ($config['params']['doc'] == 'VL' || $hidebuttons) {
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
      case 'lookupcustomer':
        return $this->lookupcustomer($config);
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
        $data['dropoff'] = 'false';
        $data['trno'] = $row['trno'];
        $data['line'] = 0;
        $data['passengerid'] = $row['passengerid'];
        $data['passengername'] = $row['passengername'];
        $data['passenger'] = $row['passenger'];
        $data['clientname'] = '';
        $data['client'] = '';
        $data['bgcolor'] = 'bg-blue-1';

        return ['status' => true, 'msg' => 'Add Passenger success...', 'data' => $data];
        break;
    }
  }

  public function lookupaddpassenger($config)
  {
    $trno = $config['params']['tableid'];

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

    $qry = "select '0' as pline, " . $trno . " as trno, '0' as line, clientid as passengerid, client as passenger, clientname as passengername from client where ispassenger = 1 and isinactive = 0";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  } //end function

  public function lookuppassenger($config)
  {
    //default
    $plotting = array('passenger' => 'client', 'passengername' => 'clientname', 'passengerid' => 'clientid');
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

  public function lookupcustomer($config)
  {
    //default
    $trno = $config['params']['tableid'];
    $plotting = array('client' => 'client', 'clientname' => 'clientname', 'line' => 'line');
    $plottype = 'plotgrid';
    $title = 'List of Customer';
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
    array_push($cols, array('name' => 'address', 'label' => 'Address', 'align' => 'left', 'field' => 'address', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'purpose', 'label' => 'Purpose', 'align' => 'left', 'field' => 'purpose', 'sortable' => true, 'style' => 'font-size:16px;'));

    $qry = "select i.trno as trno, i.line as line, c.clientid as clientid, c.client as client, c.clientname as clientname,
            p.purpose as purpose
            from vrstock as i 
            left join client as c on i.clientid = c.clientid
            left join purpose_masterfile as p on p.line = i.purposeid
            where i.trno = ?
            ";
    $data = $this->coreFunctions->opentable($qry, [$trno]);
    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  } // end function

  public function save($config)
  {
    $trno = $config['params']['tableid'];
    $statid = $this->coreFunctions->getfieldvalue("transnum", "statid", "trno=?", [$trno]);
    switch ($statid) {
      case '10':
      case '11':
      case '15':
        return ['status' => false, 'msg' => 'Request was already approved.', 'data' => []];
        break;
    }

    $this->coreFunctions->sbcupdate("transnum", ['statid' => 0], ['trno' => $trno]);

    $data = [];
    $row = $config['params']['row'];
    $data['trno'] = $config['params']['tableid'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }

    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];

    if ($row['line'] == 0) {
      return ['status' => false, 'msg' => 'Please select customer'];
    }

    if ($row['pline'] == 0) {
      $pline = $this->coreFunctions->insertGetId($this->table, $data);
      if ($pline != 0) {
        $returnrow = $this->loaddataperrecord($config, $pline);
        $this->logger->sbcwritelog($data['trno'], $config, ' CREATE - PASSENGER', $row['clientname']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow, 'reloadhead' => true];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['pline' => $row['pline']]) == 1) {
        $returnrow = $this->loaddataperrecord($config, $row['pline']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow, 'reloadhead' => true];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  public function saveallentry($config)
  {

    $trno = $config['params']['tableid'];
    $statid = $this->coreFunctions->getfieldvalue("transnum", "statid", "trno=?", [$trno]);
    switch ($statid) {
      case '10':
      case '11':
      case '15':
        return ['status' => false, 'msg' => 'Request was already approved.', 'data' => []];
        break;
    }

    $this->coreFunctions->sbcupdate("transnum", ['statid' => 0], ['trno' => $trno]);

    $data = $config['params']['data'];

    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }

        $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data2['editby'] = $config['params']['user'];

        if ($data[$key]['line'] == 0) {
          $returndata = $this->loaddata($config);
          return ['status' => false, 'msg' => 'Please select customer', 'data' => $returndata];
        }

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
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata, 'reloadhead' => true];
  } // end function $$

  public function delete($config)
  {
    $trno = $config['params']['tableid'];
    $statid = $this->coreFunctions->getfieldvalue("transnum", "statid", "trno=?", [$trno]);
    switch ($statid) {
      case '10':
      case '11':
      case '15':
        return ['status' => false, 'msg' => 'Request was already approved.', 'data' => [], 'reloadhead' => true];
        break;
    }

    $this->coreFunctions->sbcupdate("transnum", ['statid' => 0], ['trno' => $trno]);

    $row = $config['params']['row'];
    $qry = "delete from " . $this->table . " where trno=? and line=? and pline=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['trno'], $row['line'], $row['pline']]);
    $this->logger->sbcdel_log($row['trno'], $config, 'REMOVE - PASSENGER', $row['clientname']);
    return ['status' => true, 'msg' => 'Successfully deleted.', 'reloadhead' => true];
  }

  private function selectqry($config)
  {
    $decimalqty = $this->companysetup->getdecimal('qty', $config['params']);
    return " select vr.pline as pline, vr.line, vr.trno, vr.passengerid, p.clientname as passengername, p.client as passenger,
    c.clientname, c.client, case when vr.dropoff=0 then 'false' else 'true' end as dropoff ";
  }

  private function loaddataperrecord($config, $pline)
  {

    $trno = $config['params']['row']['trno'];
    $line = $config['params']['row']['line'];

    $sqlselect = $this->selectqry($config);
    $qry = $sqlselect . ",'' as bgcolor from " . $this->table . " as vr
    left join client as p on vr.passengerid = p.clientid
    left join " . $this->stock . " as s on vr.trno = s.trno and vr.line = s.line
    left join client as c on s.clientid = c.clientid
    where vr.trno = ? and vr.line = ? and vr.pline = ? order by pline";

    $data = $this->coreFunctions->opentable($qry, [$trno, $line, $pline]);
    return $data;
  }

  public function loaddata($config)
  {
    $trno = $config['params']['tableid'];

    $sqlselect = $this->selectqry($config);
    $qry = $sqlselect . ",'' as bgcolor from " . $this->table . " as vr
    left join client as p on vr.passengerid = p.clientid
    left join " . $this->stock . " as s on vr.trno = s.trno and vr.line = s.line
    left join client as c on s.clientid = c.clientid
    where vr.trno = ? 
    union all ";
    $qry = $qry . " " . $sqlselect . ",'' as bgcolor from " . $this->htable . " as vr
    left join client as p on vr.passengerid = p.clientid
    left join " . $this->hstock . " as s on vr.trno = s.trno and vr.line = s.line
    left join client as c on s.clientid = c.clientid
    where vr.trno = ? 
    order by pline";
    $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $data;
  }
} //end class
