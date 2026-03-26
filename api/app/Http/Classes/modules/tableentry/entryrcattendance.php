<?php

namespace App\Http\Classes\modules\tableentry;

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

class entryrcattendance
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'ATTENDANCE';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  public $tablenum = 'transnum';
  // private $table = 'en_srcattendance';
  // private $htable = 'en_glsrcattendance';
  public $head = 'en_srchead';
  public $hhead = 'en_glhead';
  private $table = 'en_atstudents';
  private $htable = 'en_glstudents';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['trno', 'line', 'rcdayspresent', 'rcmonthjan', 'rcmonthfeb', 'rcmonthmar', 'rcmonthapr', 'rcmonthmay', 'rcmonthjun', 'rcmonthjul', 'rcmonthaug', 'rcmonthsep', 'rcmonthoct', 'rcmonthnov', 'rcmonthdec'];
  public $showclosebtn = true;


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
    $attrib = ['load' => 0];
    return $attrib;
  }

  public function createTab($config)
  {
    $tab = [$this->gridname => ['gridcolumns' => ['rcmonthjan', 'tjan', 'rcmonthfeb', 'tfeb', 'rcmonthmar', 'tmar', 'rcmonthapr', 'tapr', 'rcmonthmay', 'tmay', 'rcmonthjun', 'tjun', 'rcmonthjul', 'tjul', 'rcmonthaug', 'taug', 'rcmonthsep', 'tsep', 'rcmonthoct', 'toct', 'rcmonthnov', 'tnov', 'rcmonthdec', 'tdec']]];
    $stockbuttons = ['save'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['label'] = 'ATTENDANCE';
    $obj[0][$this->gridname]['descriptionrow'] = [];
    $obj[0][$this->gridname]['showtotal'] = false;
    $obj[0][$this->gridname]['columns'][0]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][1]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][2]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][3]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][4]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][5]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][6]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][7]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][8]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][9]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][10]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][11]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][12]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][13]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][14]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][15]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][16]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][17]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][18]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][19]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][20]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][21]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][22]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][23]['readonly'] = true;
    // $obj[0][$this->gridname]['columns'][14]['readonly'] = true;
    // $obj[0][$this->gridname]['columns'][14]['style'] = 'width:100px;min-width:100px;max-width:100px;';
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }
        if ($data2['line'] != 0) {
          $this->coreFunctions->sbcupdate($this->table, $data2, ['trno' => $data2['trno'], 'line' => $data2['line'], 'cline' => $data2['cline']]);
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
    $total = $data['rcmonthjan'] + $data['rcmonthfeb'] + $data['rcmonthmar'] + $data['rcmonthapr'] + $data['rcmonthmay'] + $data['rcmonthjun'] + $data['rcmonthjul'] + $data['rcmonthaug'] + $data['rcmonthsep'] + $data['rcmonthoct'] + $data['rcmonthnov'] + $data['rcmonthdec'];
    $data2 = [
      'trno' => $data['trno'], 'line' => $data['line'], 'dayspresent' => $total, 'jan' => $data['rcmonthjan'], 'feb' => $data['rcmonthfeb'],
      'mar' => $data['rcmonthmar'], 'apr' => $data['rcmonthapr'], 'may' => $data['rcmonthmay'], 'jun' => $data['rcmonthjun'], 'jul' => $data['rcmonthjul'],
      'aug' => $data['rcmonthaug'], 'sep' => $data['rcmonthsep'], 'oct' => $data['rcmonthoct'], 'nov' => $data['rcmonthnov'], 'dec' => $data['rcmonthdec']
    ];
    if ($data['line'] != 0) {
      if ($this->coreFunctions->sbcupdate($this->table, $data2, ['trno' => $data['trno'], 'line' => $data['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($data['trno'], $data['line']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  private function selectqry()
  {
    $sqlselect = "select trno, line, `jan` as rcmonthjan, `feb` as rcmonthfeb, `mar` as rcmonthmar, `apr` as rcmonthapr,
          `may` as rcmonthmay, `jun` as rcmonthjun, `jul` as rcmonthjul, `aug` as rcmonthaug, `sep` as rcmonthsep, `oct` as rcmonthoct,
          `nov` as rcmonthnov, `dec` as rcmonthdec, '' as bgcolor, tjan, tfeb, tmar, tapr, tmay, tjun, tjul, taug, tsep, toct, tnov, tdec ";
    return $sqlselect;
  }

  private function loaddataperrecord($trno, $line)
  {
    $selectqry = $this->selectqry();
    $qry = $selectqry . " from " . $this->table . " where trno=? and line=?";
    $data = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $data;
  }

  public function loaddata($config)
  {
    $trno = $config['params']['row']['trno'];
    $clientid = $config['params']['row']['clientid'];
    $config['params']['trno'] = $trno;
    $selectqry = $this->selectqry();
    $isposted = $this->othersClass->isposted($config);
    $schedtrno = $this->coreFunctions->getfieldvalue($this->head, 'schedtrno', 'trno=?', [$trno]);
    if ($isposted) {
      $schedtrno = $this->coreFunctions->getfieldvalue($this->hhead, 'schedtrno', 'trno=?', [$trno]);
    }
    $qry = $selectqry." from ".$this->htable." where schedtrno=? and clientid=?";
    $stock = $this->coreFunctions->opentable($qry, [$schedtrno, $clientid]);
    return $stock;
  }
} //end class
