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

class entryrcremarks
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'REMARKS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'en_srcremarks';
  private $htable = 'en_glsrcremarks';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['trno', 'line', 'clientid', 'quarterid', 'remarks', 'semid', 'ischinese'];
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
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'quartername', 'term', 'remarks', 'ischinese']]];
    $stockbuttons = ['save', 'delete'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['label'] = 'REMARKS';
    $obj[0][$this->gridname]['descriptionrow'] = [];
    $obj[0][$this->gridname]['showtotal'] = false;
    $obj[0][$this->gridname]['columns'][1]['style'] = 'width:120px;min-width:120px;whiteSpace:normal;';
    $obj[0][$this->gridname]['columns'][1]['action'] = 'lookupsetup';
    $obj[0][$this->gridname]['columns'][2]['type'] = 'lookup';
    $obj[0][$this->gridname]['columns'][2]['label'] = 'Semester';
    $obj[0][$this->gridname]['columns'][2]['lookupclass'] = 'lookupsemester';
    $obj[0][$this->gridname]['columns'][2]['action'] = 'lookupsetup';
    $obj[0][$this->gridname]['columns'][2]['style'] = 'width:120px;min-width:120px;whiteSpace:normal;';
    $obj[0][$this->gridname]['columns'][3]['style'] = 'width:200px;min-width:200px;whiteSpace:normal;';
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['addrecord', 'saveallentry'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function lookupsetup($config)
  {
    $lookupclass = $config['params']['lookupclass2'];
    $config['params']['trno'] = $config['params']['tableid'];
    switch ($lookupclass) {
      case 'addbooks':
        return $this->enrollmentlookup->lookupbooks($config);
        break;
      case 'lookupquarter':
        return $this->enrollmentlookup->lookupquarter($config);
        break;
      case 'lookupsemester':
        return $this->enrollmentlookup->lookupsemester($config);
        break;
    }
  }


  public function add($config)
  {
    $data = [];
    $data['trno'] = $config['params']['tableid'];
    $data['line'] = 0;
    $data['clientid'] = $config['params']['row']['clientid'];
    $data['quarterid'] = 0;
    $data['quartername'] = '';
    $data['remarks'] = '';
    $data['semid'] = 0;
    $data['term'] = '';
    $data['ischinese'] = 'false';
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  private function selectqry()
  {
    $sqlselect = "select stock.trno, stock.line, stock.itemid, item.barcode, item.itemname as description, 
      stock.uom, stock.isqty, stock.disc, stock.isamt, stock.amt, stock.ext, '' as bgcolor, '' as errcolor ";
    return $sqlselect;
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
          'clientid' => $data2['clientid'],
          'quarterid' => $data2['quarterid'],
          'semid' => $data2['semid'],
          'remarks' => $data2['remarks'],
          'ischinese' => $data2['ischinese']
        ];
        if ($data3['ischinese'] == "true") {
          $data3['ischinese'] = 1;
        } else {
          $data3['ischinese'] = 0;
        }
        if ($data3['line'] == 0) {
          $line = $this->coreFunctions->insertGetId($this->table, $data3);
        } else {
          $this->coreFunctions->sbcupdate($this->table, $data3, ['line' => $data3['line']]);
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];


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
    $data2 = [
      'trno' => $data['trno'],
      'line' => $data['line'],
      'clientid' => $data['clientid'],
      'quarterid' => $data['quarterid'],
      'semid' => $data['semid'],
      'remarks' => $data['remarks'],
      'ischinese' => $data['ischinese']
    ];
    if ($data2['ischinese'] == "true") {
      $data2['ischinese'] = 1;
    } else {
      $data2['ischinese'] = 0;
    }
    if ($data['line'] != 0) {
      if ($this->coreFunctions->sbcupdate($this->table, $data2, ['trno' => $data['trno'], 'line' => $data['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($data['trno'], $data['line']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      $line = $this->coreFunctions->insertGetId($this->table, $data2);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($data['trno'], $line);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  private function loaddataperrecord($trno, $line)
  {
    $qryselect = "select rem.trno, rem.line, rem.clientid, rem.quarterid, quarter.name as quartername, rem.remarks, rem.semid, sem.term, rem.ischinese, '' as bgcolor";
    $qry = $qryselect . " from " . $this->table . " as rem left join en_quartersetup as quarter on quarter.line=rem.quarterid left join en_term as sem on sem.line=rem.semid where rem.trno=? and rem.line=? union all " . $qryselect . " from " . $this->htable . " as rem left join en_quartersetup as quarter on quarter.line=rem.quarterid left join en_term as sem on sem.line=rem.semid where rem.trno=? and rem.line=?";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $line, $trno, $line]);
    if (!empty($stock)) {
      if ($stock[0]->ischinese == 0) {
        $stock[0]->ischinese = 'false';
      } else {
        $stock[0]->ischinese = 'true';
      }
    }
    return $stock;
  }

  public function loaddata($config)
  {
    $trno = $config['params']['row']['trno'];
    $clientid = $config['params']['row']['clientid'];
    $qryselect = "select rem.trno, rem.line, rem.clientid, rem.quarterid, quarter.name as quartername, rem.remarks, rem.semid, sem.term, rem.ischinese, '' as bgcolor";

    $qry = $qryselect . " from " . $this->table . " as rem left join en_quartersetup as quarter on quarter.line=rem.quarterid left join en_term as sem on sem.line=rem.semid where rem.trno=? and rem.clientid=? union all " . $qryselect . " from " . $this->htable . " as rem left join en_quartersetup as quarter on quarter.line=rem.quarterid left join en_term as sem on sem.line=rem.semid where rem.trno=? and rem.clientid=?";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $clientid, $trno, $clientid]);
    if (!empty($stock)) {
      foreach ($stock as $s) {
        if ($s->ischinese == 0) {
          $s->ischinese = 'false';
        } else {
          $s->ischinese = 'true';
        }
      }
    }
    return $stock;
  }

  public function delete($config)
  {
    $row = $config['params']['row'];
    $qry = "delete from " . $this->table . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['trno'], $row['line']]);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }
} //end class
