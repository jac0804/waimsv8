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

class entryotherfees
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'OTHER FEES';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'en_sootherfees';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['trno', 'line', 'feesid', 'acnoid', 'isamt'];
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
    $attrib = array(
      'load' => 0
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $doc = $config['params']['doc'];
    switch ($doc) {
      case 'EA':
      case 'ED':
      case 'EI':
        $tab = [$this->gridname => ['gridcolumns' => ['action', 'feescode', 'rem', 'isamt', 'acno', 'acnoname']]];
        break;
      default:
        $tab = [$this->gridname => ['gridcolumns' => ['feescode', 'rem', 'isamt', 'acno', 'acnoname']]];
        break;
    }

    $stockbuttons = ['save', 'delete'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][0]['style'] = "width:40px;whiteSpace: normal;min-width:40px;"; //action
    $obj[0][$this->gridname]['columns'][1]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][2]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][4]['readonly'] = true;

    return $obj;
  }

  public function createtabbutton($config)
  {

    $doc = $config['params']['doc'];
    switch ($doc) {
      case 'EA':
      case 'ED':
      case 'EI':
        $tbuttons = ['addotherfees'];
        break;
      default:
        $tbuttons = [];
        break;
    }

    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function lookupsetup($config)
  {
    $lookupclass = $config['params']['lookupclass2'];
    switch ($lookupclass) {
      case 'lookupaddotherfees':
        return $this->enrollmentlookup->lookupaddotherfees($config);
        break;
    }
  }

  public function lookupcallback($config)
  {
    $id = $config['params']['tableid'];
    $row = $config['params']['rows'];
    $doc = $config['params']['doc'];
    switch ($doc) {
      case 'ER':
        $table = "en_sjotherfees";
        break;
      case 'EA':
      case 'EI':
        $table = "en_sootherfees";
        break;
      case 'ED':
        $table = "en_adotherfees";
        break;
    }
    $data = [];

    foreach ($row  as $key2 => $value) {

      $qry = "select line as value from " . $table . " where trno=? order by line desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$id]);
      $line  += 1;
      $config['params']['row']['line'] = $line;
      $config['params']['row']['trno'] = $id;
      $config['params']['row']['feesid'] = $value['line'];
      $config['params']['row']['acnoid'] = $value['acnoid'];
      $config['params']['row']['isamt'] = $value['amount'];
      $config['params']['row']['bgcolor'] = 'bg-blue-2';
      $return = $this->insertotherfees($config, $table);
      if ($return['status']) {
        array_push($data, $return['row'][0]);
      }
    }

    return ['status' => true, 'msg' => 'Successfully added.', 'data' => $data];
  } // end function

  public function insertotherfees($config, $table)
  {
    $data = [];
    $row = $config['params']['row'];

    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    $line = $this->coreFunctions->insertGetId($table, $data);
    if ($row['line'] != 0) {
      $returnrow = $this->loaddataperrecord($row['trno'], $row['line'], $table);
      return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
    } else {
      return ['status' => false, 'msg' => 'Insert failed.'];
    }
  }

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    $doc = $config['params']['doc'];

    switch ($doc) {
      case 'ER':
        $table = "en_sjotherfees";
        break;
      case 'EA':
      case 'EI':
        $table = "en_sootherfees";
        break;
      case 'ED':
        $table = "en_adotherfees";
        break;
    }

    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    if ($row['line'] == 0) {
      $line = $this->coreFunctions->insertGetId($table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($row['trno'], $line, $table);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Insert failed.'];
      }
    } else {
      if ($this->coreFunctions->sbcupdate($table, $data, ['trno' => $row['trno'], 'line' => $row['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($row['trno'], $row['line'], $table);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Update failed.'];
      }
    }
  }

  public function delete($config)
  {
    $row = $config['params']['row'];
    $doc = $config['params']['doc'];
    switch ($doc) {
      case 'ER':
        $table = "en_sjotherfees";
        break;
      case 'EA':
      case 'EI':
        $table = "en_sootherfees";
        break;
      case 'ED':
        $table = "en_adotherfees";
        break;
    }

    $qry = "delete from " . $table . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['trno'], $row['line']]);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }

  private function loaddataperrecord($trno, $line, $table)
  {

    $htable = "en_glotherfees";

    $select = $this->selectqry();
    $qry = $select . " FROM " . $table . " as stock left join transnum as num on num.trno=stock.trno left join en_fees as f on f.line=stock.feesid left join coa on coa.acnoid=stock.acnoid  where stock.trno=? and stock.line=? 
      union all 
      " . $select . " FROM " . $htable . " as stock left join transnum as num on num.trno=stock.trno left join en_fees as f on f.line=stock.feesid left join coa on coa.acnoid=stock.acnoid  where stock.trno=? and stock.line=?  ";

    $data = $this->coreFunctions->opentable($qry, [$trno, $line, $trno, $line]);
    return $data;
  }

  private  function selectqry()
  {
    return " select stock.trno,stock.line,f.feesdesc as rem,f.feescode,stock.feestype,coa.acno,coa.acnoname,stock.feesid,stock.acnoid,stock.isamt,
    '' as bgcolor,
    '' as errcolor  ";
  }

  public function loaddata($config)
  {

    $tableid = $config['params']['tableid'];
    $doc = $config['params']['doc'];
    $htable = "en_glotherfees";

    switch ($doc) {
      case 'ER':
        $table = "en_sjotherfees";
        break;
      case 'EA':
      case 'EI':
        $table = "en_sootherfees";
        break;
      case 'ED':
        $table = "en_adotherfees";
        break;
    }

    $select = $this->selectqry();
    $qry =  $select . " FROM " . $table . " as stock left join transnum as num on num.trno=stock.trno left join en_fees as f on f.line=stock.feesid left join coa on coa.acnoid=stock.acnoid  where stock.trno=? 
      union all 
      " . $select . " FROM " . $htable . " as stock left join transnum as num on num.trno=stock.trno left join en_fees as f on f.line=stock.feesid left join coa on coa.acnoid=stock.acnoid  where stock.trno=? ";
    $data = $this->coreFunctions->opentable($qry, [$tableid, $tableid]);
    return $data;
  }
} //end class
