<?php

namespace App\Http\Classes\modules\fams;

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

class  tabapproveduser
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'APPROVEDUSERS';
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

    $hidebuttons = false;


    $config['params']['trno'] = $config['params']['tableid'];
    if ($this->othersClass->isposted($config)) {
      $hidebuttons = true;
    }

    $action = 0;
    $passengername = 1;
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'passengername']]];

    $stockbuttons = ['delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";


    $obj[0][$this->gridname]['columns'][$passengername]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';


    $obj[0][$this->gridname]['columns'][$passengername]['label'] = 'User';
    $obj[0][$this->gridname]['columns'][$passengername]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][$passengername]['readonly'] = true;


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
    $obj[0]['label'] = "ADD USER";
    return $obj;
  }

  public function lookupsetup($config)
  {

    $lookupclass2 = $config['params']['lookupclass2'];

    switch ($lookupclass2) {
      case 'additem':
        $config['params']['trno'] = $config['params']['tableid'];
        if ($this->othersClass->isposted($config)) {
          return ['status' => false, 'msg' => 'Transaction has already been posted.'];
        } else {
          return $this->lookupadduser($config);
        }

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
        $data['line'] = 0;
        $data['passengerid'] = $row['passengerid'];
        $data['passengername'] = $row['passengername'];
        $data['passenger'] = $row['passenger'];

        $data['bgcolor'] = 'bg-blue-1';

        return ['status' => true, 'msg' => 'Add Passenger success...', 'data' => $data];
        break;
    }
  }

  public function lookupadduser($config)
  {
    $trno = $config['params']['tableid'];
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of User',
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

    $qry = "select '0' as pline, " . $trno . " as trno, '0' as line, clientid as passengerid, client as passenger, clientname as passengername from client where isemployee = 1 and isinactive = 0";
    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
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
    $config['params']['trno'] = $config['params']['tableid'];
    if ($this->othersClass->isposted($config)) {
      return ['status' => false, 'msg' => 'Transaction has already been posted.'];
    } else {

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


          if ($data[$key]['pline'] != 0) {
            $this->coreFunctions->sbcupdate($this->table, $data2, ['pline' => $data[$key]['pline']]);
          } else {
            $data2['trno'] = $data[$key]['trno'];
            $line = $this->coreFunctions->insertGetId($this->table, $data2);
            $this->logger->sbcwritelog($data[$key]['trno'], $config, ' CREATE - USER', $data[$key]['passengerid']);
          }
        } // end if
      } // foreach
      $returndata = $this->loaddata($config);
      return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata, 'reloadhead' => true];
    }
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
    $config['params']['trno'] = $config['params']['tableid'];
    if ($this->othersClass->isposted($config)) {
      return ['status' => false, 'msg' => 'Transaction has already been posted.'];
    } else {

      $this->coreFunctions->sbcupdate("transnum", ['statid' => 0], ['trno' => $trno]);

      $row = $config['params']['row'];
      $qry = "delete from " . $this->table . " where trno=? and line=? and pline=?";
      $this->coreFunctions->execqry($qry, 'delete', [$row['trno'], $row['line'], $row['pline']]);
      $this->logger->sbcdel_log($row['trno'], $config, 'REMOVE - PASSENGER', $row['clientname']);
      return ['status' => true, 'msg' => 'Successfully deleted.', 'reloadhead' => true];
    }
  }

  private function selectqry($config)
  {
    $decimalqty = $this->companysetup->getdecimal('qty', $config['params']);
    return " select vr.pline as pline, vr.line, vr.trno, vr.passengerid, p.clientname as passengername, p.client as passenger,
    c.clientname, c.client";
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
