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

class  tabitems
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'ITEMS / CARGO';
  public $tablenum = 'transnum';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'vritems';
  private $htable = 'hvritems';
  private $stock = 'vrstock';
  private $hstock = 'hvrstock';
  private $othersClass;
  public $style = 'width:100%;max-width: 100%';
  private $fields = ['trno', 'line', 'itemname', 'uom', 'qty'];
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
    $itemname = 1;
    $qty = 2;
    $uom = 3;
    $client = 4;
    $clientname = 5;

    $tab = [$this->gridname => ['gridcolumns' => ['action', 'itemname', 'qty', 'uom', 'client', 'clientname']]];

    $stockbuttons = ['save', 'delete'];
    if ($config['params']['doc'] == 'VL' || $hidebuttons) {
      $stockbuttons = [];
    }
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][$itemname]['style'] = 'width:230px;whiteSpace: normal;min-width:230px;';
    $obj[0][$this->gridname]['columns'][$uom]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $obj[0][$this->gridname]['columns'][$qty]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $obj[0][$this->gridname]['columns'][$client]['style'] = 'width:130px;whiteSpace: normal;min-width:130px;';
    $obj[0][$this->gridname]['columns'][$clientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';

    $obj[0][$this->gridname]['columns'][$itemname]['label'] = 'Itemname';
    $obj[0][$this->gridname]['columns'][$itemname]['readonly'] = false;
    $obj[0][$this->gridname]['columns'][$itemname]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][$uom]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][$uom]['label'] = 'Uom';
    $obj[0][$this->gridname]['columns'][$qty]['label'] = 'Quantity';

    $obj[0][$this->gridname]['columns'][$client]['label'] = 'Customer Code';
    $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Customer Name';
    $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][$clientname]['readonly'] = true;

    $obj[0][$this->gridname]['columns'][$client]['type'] = 'lookup';
    $obj[0][$this->gridname]['columns'][$client]['action'] = 'lookupsetup';
    $obj[0][$this->gridname]['columns'][$client]['lookupclass'] = 'lookupcustomer';

    if ($config['params']['doc'] == 'VL' || $hidebuttons) {
      $obj[0][$this->gridname]['columns'][$action]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$itemname]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$uom]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$qty]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$client]['type'] = 'input';
    }

    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['addrecord', 'saveallentry'];

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
    $obj[0]['label'] = "ADD ITEMS";
    return $obj;
  }

  public function add($config)
  {
    $data = [];
    $trno = $config['params']['tableid'];

    $data['pline'] = 0;
    $data['trno'] = $trno;
    $data['line'] = 0;
    $data['itemname'] = '';
    $data['qty'] = 0;
    $data['uom'] = '';
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  public function lookupsetup($config)
  {

    $lookupclass2 = $config['params']['lookupclass2'];

    switch ($lookupclass2) {
      case 'additem':
        return $this->lookupaddpassenger($config);
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
        $data['trno'] = $row['trno'];
        $data['line'] = $row['line'];
        $data['passengerid'] = $row['passengerid'];
        $data['clientname'] = $row['passengername'];
        $data['client'] = $row['passenger'];
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

    $qry = "select '0' as pline, " . $trno . " as trno, '0' as line, clientid as passengerid, client as passenger, clientname as passengername from client where ispassenger = 1 and isinactive = 0 limit 500";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  } //end function

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

    if ($row['qty'] == 0) {
      return ['status' => false, 'msg' => 'Please input valid qty for item ' . $row['itemname']];
    }

    $this->coreFunctions->sbcupdate("transnum", ['statid' => 0], ['trno' => $trno]);

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
    $msg = '';
    $trno = $config['params']['tableid'];
    $statid = $this->coreFunctions->getfieldvalue("transnum", "statid", "trno=?", [$trno]);
    switch ($statid) {
      case '10':
      case '11':
      case '15':
        return ['status' => false, 'msg' => 'Request was already approved.', 'data' => []];
        break;
    }

    $data = $config['params']['data'];

    $this->coreFunctions->sbcupdate("transnum", ['statid' => 0], ['trno' => $trno]);

    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }

        $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data2['editby'] = $config['params']['user'];

        if ($data[$key]['qty'] == 0) {
          $returndata = $this->loaddata($config);
          $msg .= 'Please input valid qty for ' . $data[$key]['itemname'];
          continue;
        }

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
    if ($msg == '') {
      $msg = 'All saved successfully.';
    }
    return ['status' => true, 'msg' => $msg, 'data' => $returndata, 'reloadhead' => true];
  } // end function $$

  public function delete($config)
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

    $row = $config['params']['row'];
    $qry = "delete from " . $this->table . " where trno=? and line=? and pline=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['trno'], $row['line'], $row['pline']]);
    $this->logger->sbcdel_log($row['trno'], $config, 'REMOVE - PASSENGER', $row['clientname']);
    return ['status' => true, 'msg' => 'Successfully deleted.', 'reloadhead' => true];
  }

  private function selectqry($config)
  {
    $decimalqty = $this->companysetup->getdecimal('qty', $config['params']);

    return "
    select vr.pline as pline, vr.line, vr.trno, vr.uom, vr.itemname, round(vr.qty," . $decimalqty . ") as qty,
    c.clientname, c.client
    ";
  }

  private function loaddataperrecord($config, $pline)
  {

    $trno = $config['params']['row']['trno'];
    $line = $config['params']['row']['line'];

    $qry = $this->selectqry($config);
    $qry = $qry . ", '' as bgcolor  from " . $this->table . " as vr
    left join " . $this->stock . " as s on vr.trno = s.trno and vr.line = s.line
    left join client as c on s.clientid = c.clientid
    where vr.trno = ? and vr.line = ? and vr.pline = ? order by pline
    ";

    $data = $this->coreFunctions->opentable($qry, [$trno, $line, $pline]);
    return $data;
  }

  public function loaddata($config)
  {
    $trno = $config['params']['tableid'];

    $selectqry = $this->selectqry($config);
    $qry = $selectqry . ", '' as bgcolor  from " . $this->table . " as vr
    left join " . $this->stock . " as s on vr.trno = s.trno and vr.line = s.line
    left join client as c on s.clientid = c.clientid
    where vr.trno = ? 
    union all 
    " . $selectqry . ", '' as bgcolor  from " . $this->htable . " as vr
    left join " . $this->hstock . " as s on vr.trno = s.trno and vr.line = s.line
    left join client as c on s.clientid = c.clientid
    where vr.trno = ? 
    order by pline
    ";
    $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $data;
  }
} //end class
