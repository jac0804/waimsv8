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
use App\Http\Classes\lookup\enrollmentlookup;

class entryshiftsetupdetails
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'SHIFT SETUP DETAILS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'shiftdetail';
  public $tablelogs = 'payroll_log';
  private $logger;
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['schedin', 'schedout', 'breakin', 'breakout', 'tothrs', 'brk1stin', 'brk1stout', 'brk2ndout', 'brk2ndin'];
  public $showclosebtn = false;
  private $enrollmentlookup;



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
    $companyid = $config['params']['companyid'];
    $cols = [
      'action',
      'dayn',
      'schedin',
      'breakout',
      'breakin',
      'schedout',
      'brk1stout',
      'brk1stin',
      'brk2ndout',
      'brk2ndin',
      'tothrs'
    ];

    foreach ($cols as $key => $value) {
      $$value = $key;
    }

    $tab = [$this->gridname => ['gridcolumns' => $cols]];

    $stockbuttons = ['save'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:30px;whiteSpace: normal;min-width:30px;";
    $obj[0][$this->gridname]['columns'][$dayn]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][$schedin]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][$schedout]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][$breakout]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][$breakin]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][$tothrs]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";

    $obj[0][$this->gridname]['columns'][$dayn]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$schedin]['type'] = "time";
    $obj[0][$this->gridname]['columns'][$schedout]['type'] = "time";
    $obj[0][$this->gridname]['columns'][$breakout]['type'] = "time";
    $obj[0][$this->gridname]['columns'][$breakin]['type'] = "time";

    $obj[0][$this->gridname]['columns'][$brk1stin]['type'] = "time";
    $obj[0][$this->gridname]['columns'][$brk1stout]['type'] = "time";
    $obj[0][$this->gridname]['columns'][$brk2ndout]['type'] = "time";
    $obj[0][$this->gridname]['columns'][$brk2ndin]['type'] = "time";

    switch ($companyid) {
      case 45:
      case 58:
        $obj[0][$this->gridname]['columns'][$brk1stin]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$brk1stout]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$brk2ndout]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$brk2ndin]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        break;
      default:
        $obj[0][$this->gridname]['columns'][$brk1stin]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$brk1stout]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$brk2ndout]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$brk2ndin]['type'] = 'coldel';
        break;
    }

    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = ['saveallentry'];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }

  public function add($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['schedin'] = null;
    $data['schedout'] = null;
    $data['breakout'] = null;
    $data['breakin'] = null;
    //pdpi
    $data['brk1stin'] = null;
    $data['brk1stout'] = null;
    $data['brk2ndout'] = null;
    $data['brk2ndin'] = null;

    $data['tothrs'] = '';
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  public function saveallentry($config)
  {
    $companyid = $config['params']['companyid'];
    $data = $config['params']['data'];
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }
        $data2['shiftsid'] = $config['params']['tableid'];
        if ($data[$key]['line'] == 0) {
          // $line =$this->coreFunctions->insertGetId($this->table,$data2);
        } else {
          // $this->coreFunctions->sbcupdate($this->table,$data2,['line'=>$data[$key]['line']]);
          $schedin = $data2['schedin'] <> "" ? "concat(date(schedin), ' ', '" . date('H:i', strtotime($data2['schedin'])) . "')" : 'NULL';
          $schedout = $data2['schedout'] <> "" ? "concat(date(schedin), ' ', '" . date('H:i', strtotime($data2['schedout'])) . "')" : 'NULL';
          $breakout = $data2['breakout'] <> "" ? "concat(date(schedin), ' ', '" . date("H:i", strtotime($data2['breakout'])) . "')" : 'NULL';
          $breakin = $data2['breakin'] <> "" ? "concat(date(schedin), ' ', '" . date("H:i", strtotime($data2['breakin'])) . "')" : 'NULL';

          $updatebreakinout = "";
          if ($companyid == 45 || $companyid == 58) { // pdpi / cdo
            $breakinam = $data2['brk1stin'] <> "" ? "concat(date(schedin), ' ', '" . date('H:i', strtotime($data2['brk1stin'])) . "')" : 'NULL';
            $breakoutam = $data2['brk1stout'] <> "" ? "concat(date(schedout), ' ', '" . date('H:i', strtotime($data2['brk1stout'])) . "')" : 'NULL';
            $breakinpm = $data2['brk2ndin'] <> "" ? "concat(date(schedin), ' ', '" . date("H:i", strtotime($data2['brk2ndin'])) . "')" : 'NULL';
            $breakoutpm = $data2['brk2ndout'] <> "" ? "concat(date(schedout), ' ', '" . date("H:i", strtotime($data2['brk2ndout'])) . "')" : 'NULL';
            $updatebreakinout = ",
            brk1stin =  $breakinam ,
            brk1stout =  $breakoutam,
            brk2ndin =  $breakinpm ,
            brk2ndout =  $breakoutpm ";
          }
          $editdate = $this->othersClass->getCurrentTimeStamp();
          $editby = $config['params']['user'];

          $qry = "update shiftdetail set 
            schedin = " . $schedin . ",
            schedout = " . $schedout . ",
            breakout = " . $breakout . ",
            breakin = " . $breakin . ",
            tothrs = " . $data2['tothrs'] . ",
            editdate = '" . $editdate . "', 
            editby = '" . $editby . "'
            $updatebreakinout
            where line = '" . $data[$key]['line'] . "' and 
            shiftsid = '" . $config['params']['tableid'] . "'";
          // $this->coreFunctions->LogConsole($qry);
          $this->coreFunctions->execqry($qry);
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'Saved all Successfully', 'data' => $returndata];
  } // end function 

  public function save($config)
  {
    $companyid = $config['params']['companyid'];
    $data = [];
    $row = $config['params']['row'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    $data['shiftsid'] = $config['params']['tableid'];
    if ($row['line'] == 0) {
    } else {
      $schedin = $data['schedin'] <> "" ? "concat(date(schedin), ' ', '" . date("H:i", strtotime($data['schedin'])) . "')" : 'NULL';
      $schedout = $data['schedout'] <> "" ? "concat(date(schedin), ' ', '" . date("H:i", strtotime($data['schedout'])) . "')" : 'NULL';
      $breakout = $data['breakout'] <> "" ? "concat(date(schedin), ' ', '" . date("H:i", strtotime($data['breakout'])) . "')" : 'NULL';
      $breakin = $data['breakin'] <> "" ? "concat(date(schedin), ' ', '" . date("H:i", strtotime($data['breakin'])) . "')" : 'NULL';
      $editdate = $this->othersClass->getCurrentTimeStamp();
      $editby = $config['params']['user'];
      $updatebreakinout = "";
      if ($companyid == 45) { // pdpi
        $breakinam = $data['brk1stin'] <> "" ? "concat(date(schedin), ' ', '" . $data['brk1stin'] . "')" : 'NULL';
        $breakoutam = $data['brk1stout'] <> "" ? "concat(date(schedout), ' ', '" . $data['brk1stout'] . "')" : 'NULL';
        $breakinpm = $data['brk2ndin'] <> "" ? "concat(date(schedin), ' ', '" . $data['brk2ndin'] . "')" : 'NULL';
        $breakoutpm = $data['brk2ndout'] <> "" ? "concat(date(schedout), ' ', '" . $data['brk2ndout'] . "')" : 'NULL';
        $updatebreakinout = ",
            brk1stin =  $breakinam ,
            brk1stout =  $breakoutam,
            brk2ndin =  $breakinpm ,
            brk2ndout =  $breakoutpm ";
      }

      $qry = "update shiftdetail set 
        schedin = " . $schedin . ", 
        schedout = " . $schedout . ", 
        breakout = " . $breakout . ", 
        breakin = " . $breakin . ", 
        tothrs = '" . $data['tothrs'] . "',
        editdate = '" . $editdate . "', 
        editby = '" . $editby . "'
        $updatebreakinout
        where line = '" . $row['line'] . "' and 
        shiftsid = '" . $config['params']['tableid'] . "'";

      $update_sql = $this->coreFunctions->execqry($qry, 'update');
      if ($update_sql) {
        $returnrow = $this->loaddataperrecord($data['shiftsid'], $row['line']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
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

    $qry =
      "line,
      case 
        when dayn = '1' then 'Monday'
        when dayn = '2' then 'Tuesday'
        when dayn = '3' then 'Wednesday'
        when dayn = '4' then 'Thursday'
        when dayn = '5' then 'Friday'
        when dayn = '6' then 'Saturday'
        when dayn = '7' then 'Sunday'
      end as dayn, dayn as dayn2,
      TIME(schedin) as schedin,
      TIME(schedout) as schedout, 
      TIME(breakin) as breakin, 
      TIME(breakout) as breakout,

      time(brk1stout) as brk1stout,
      time(brk1stin) as brk1stin,
      time(brk2ndin) as brk2ndin,
      time(brk2ndout) as brk2ndout,

      tothrs as tothrs
    ";

    return $qry;
  }

  private function loaddataperrecord($shiftsid, $line)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " where shiftsid=? and line=?";
    $data = $this->coreFunctions->opentable($qry, [$shiftsid, $line]);
    return $data;
  }

  public function loaddata($config)
  {
    $shiftsid = $config['params']['tableid'];
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " where shiftsid=? order by dayn2";
    $data = $this->coreFunctions->opentable($qry, [$shiftsid]);
    return $data;
  }

  public function lookupsetup($config)
  {
    $lookupclass = $config['params']['lookupclass2'];

    switch ($lookupclass) {
      case 'lookupdepartment':
      case 'lookupparentdepartment':
        return $this->enrollmentlookup->lookupdepartment($config);
        break;
      case 'lookupdean':

        break;
      case 'lookupcourse':
        return $this->enrollmentlookup->lookupcourse($config);
        break;
      case 'lookuplevel':
      case 'lookupdeptlevel':
        return $this->enrollmentlookup->lookuplevel($config);
        break;
    }
  }
} //end class
