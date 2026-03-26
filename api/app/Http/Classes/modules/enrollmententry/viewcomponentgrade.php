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

class viewcomponentgrade
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'COMPONENT GRADE';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'en_gecomponentgrade';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['trno', 'line', 'gcscoregrade', 'gcpercentgrade'];
  public $showclosebtn = true;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
  }

  public function getAttrib()
  {
    $attrib = ['load' => 0];
    return $attrib;
  }

  public function createTab($config)
  {
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'name', 'gcsubcode', 'gcsubtopic', 'gcscoregrade', 'gcsubnoofitems']]];
    $stockbuttons = ['save'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][1]['type'] = "label";
    $obj[0][$this->gridname]['columns'][1]['style'] = 'width:120px;whiteSpace:normal;min-width:120px;';
    $obj[0][$this->gridname]['columns'][2]['type'] = "label";
    $obj[0][$this->gridname]['columns'][2]['style'] = 'width:80px;whiteSpace:normal;min-width:80px;';
    $obj[0][$this->gridname]['columns'][3]['type'] = "label";
    $obj[0][$this->gridname]['columns'][3]['style'] = 'width:100px;whiteSpace:normal;min-width:100px;';
    $obj[0][$this->gridname]['columns'][5]['style'] = 'width:100px;whiteSpace:normal;min-width:100px;';
    $obj[0][$this->gridname]['columns'][5]['type'] = 'label';
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
    $data = $config['params']['data'];
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }
        $data3 = [
          'trno' => $data2['trno'],
          'line' => $data2['line'],
          'scoregrade' => $data2['gcscoregrade'],
          'percentgrade' => $data2['gcpercentgrade']
        ];
        $decimal  = $this->coreFunctions->getfieldvalue('profile', 'pvalue', "doc='ENG' and psection='GCC'");
        if ($decimal == '') $decimal = 0;
        $percent  = $this->coreFunctions->getfieldvalue($this->table, 'gcpercent', "trno=? and line=?", [$data3['trno'], $data3['line']]);
        if ($percent == '') $percent = 100;
        $scoregrade = $this->coreFunctions->getfieldvalue($this->table, 'scoregrade', "trno=? and line=?", [$data3['trno'], $data3['line']]);
        $totalgrade = $this->coreFunctions->getfieldvalue($this->table, 'totalgrade', "trno=? and line=?", [$data3['trno'], $data3['line']]);
        if ($scoregrade != $data3['scoregrade']) {
          $percent = $percent / 100;
          $totalpercent = (($data3['scoregrade'] / $totalgrade) * 100) * $percent;
          $data3['percentgrade'] = round($totalpercent, $decimal);
        }
        if ($data[$key]['line'] != 0) {
          $this->coreFunctions->sbcupdate($this->table, $data3, ['line' => $data[$key]['line']]);
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
    $data2 = [
      'trno' => $data['trno'],
      'line' => $data['line'],
      'scoregrade' => $data['gcscoregrade'],
      'percentgrade' => $data['gcpercentgrade']
    ];
    $decimal  = $this->coreFunctions->getfieldvalue('profile', 'pvalue', "doc='ENG' and psection='GCC'");
    if ($decimal == '') $decimal = 0;
    $percent  = $this->coreFunctions->getfieldvalue($this->table, 'gcpercent', "trno=? and line=?", [$data2['trno'], $data2['line']]);
    if ($percent == '') $percent = 100;
    $scoregrade = $this->coreFunctions->getfieldvalue($this->table, 'scoregrade', "trno=? and line=?", [$data2['trno'], $data2['line']]);
    $totalgrade = $this->coreFunctions->getfieldvalue($this->table, 'totalgrade', "trno=? and line=?", [$data2['trno'], $data2['line']]);
    if ($scoregrade != $data2['scoregrade']) {
      $percent = $percent / 100;
      $totalpercent = (($data2['scoregrade'] / $totalgrade) * 100) * $percent;
      $data2['percentgrade'] = round($totalpercent, $decimal);
    }
    if ($data2['line'] == 0) {
      return ['status' => false, 'msg' => 'Saving failed.'];
    } else {
      if ($this->coreFunctions->sbcupdate($this->table, $data2, ['trno' => $data2['trno'], 'line' => $data2['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($data2['trno'], $data2['line']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  private function selectqry()
  {
    $decimal  = $this->coreFunctions->getfieldvalue('profile', 'pvalue', "doc='ENG' and psection='GCC'");
    if ($decimal == '') $decimal = 0;
    return "select cg.trno, cg.line, cg.clientid, client.clientname as name, cg.componentcode as gcsubcode, gc.topic as gcsubtopic, cg.scoregrade as gcscoregrade, cg.totalgrade as gcsubnoofitems, round(cg.percentgrade," . $decimal . ") as gcpercentgrade ";
  }

  private function loaddataperrecord($trno, $line)
  {
    $select = $this->selectqry();
    $select = $select . ", '' as bgcolor ";
    $qry = $select . " from en_gecomponentgrade as cg left join en_gssubcomponent as gc on gc.gccode=cg.componentcode left join client on client.clientid=cg.clientid where cg.trno=? and cg.line=?";
    $data = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $data;
  }

  public function loaddata($config)
  {
    $trno = $config['params']['tableid'];
    $select = $this->selectqry();
    $select = $select . ", '' as bgcolor";
    $qry = $select . " from en_gecomponentgrade as cg left join en_gssubcomponent as gc on gc.trno=cg.trno and gc.gccode=cg.componentcode left join client on client.clientid=cg.clientid where cg.trno=? order by cg.clientid";

    $data = $this->coreFunctions->opentable($qry, [$trno]);
    return $data;
  }
} //end class
