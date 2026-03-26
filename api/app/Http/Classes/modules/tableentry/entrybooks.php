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

class entrybooks
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'BOOKS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'en_ccbooks';
  private $htable = 'en_glbooks';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['trno', 'line', 'cline', 'itemid', 'isqty', 'uom', 'isamt', 'disc', 'ext', 'amt'];
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
    $tab = [$this->gridname => [
      'gridcolumns' => [
        'action', 'barcode', 'description', 'isqty', 'uom', 'isamt', 'disc', 'ext'
      ],
    ]];
    $stockbuttons = ['save', 'delete'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['label'] = 'BOOKS';
    $obj[0][$this->gridname]['descriptionrow'] = ['itemname', 'barcode',  'Subject'];
    $obj[0][$this->gridname]['showtotal'] = false;

    $obj[0][$this->gridname]['columns'][2]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][3]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][5]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][6]['readonly'] = true;

    $obj[0][$this->gridname]['columns'][7]['action'] = 'lookupsetup';
    $obj[0][$this->gridname]['columns'][8]['action'] = 'lookupsetup';
    $obj[0][$this->gridname]['columns'][9]['action'] = 'lookupsetup';
    $obj[0][$this->gridname]['columns'][10]['action'] = 'lookupsetup';
    $obj[0][$this->gridname]['columns'][11]['action'] = 'lookupsetup';

    $obj[0][$this->gridname]['columns'][0]['style'] = 'width:60px;whiteSpace: normal;min-width:60px;'; //action
    $obj[0][$this->gridname]['columns'][11]['readonly'] = true; //action
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['addbooks', 'saveallentry'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['label'] = 'Add';
    $obj[0]['lookupclass'] = 'addbooks';
    $obj[0]['action2'] = 'lookupsetup';
    $obj[0]['action'] = 'lookupsetup';
    return $obj;
  }

  public function lookupsetup($config)
  {
    $lookupclass = $config['params']['lookupclass2'];
    $config['params']['trno'] = $config['params']['tableid'];
    $config['params']['table'] = 'item';
    switch ($lookupclass) {
      case 'addbooks':
        return $this->enrollmentlookup->lookupbooks($config);
        break;
    }
  }

  public function lookupcallback($config)
  {
    $trno = $config['params']['tableid'];
    $cline = $config['params']['rows'][0]['line'];

    if ($cline == 0) {
      return ['status' => false, 'msg' => 'Cannot add Books save Year/Grade and Sem first...'];
    }

    $row = $config['params']['rows'];
    $doc = $config['params']['doc'];
    $data = [];
    foreach ($row as $key2 => $value) {
      $line = $this->coreFunctions->datareader("select line as value from " . $this->table . " where trno=? and cline=? order by line desc limit 1", [$trno, $cline]);
      if ($line == '') $line = 0;
      $line += 1;
      $config['params']['row']['line'] = $line;
      $config['params']['row']['trno'] = $trno;
      $config['params']['row']['cline'] = $cline;
      $config['params']['row']['itemid'] = $value['itemid'];
      $config['params']['row']['uom'] = $value['uom'];
      $config['params']['row']['isqty'] = 1;
      $config['params']['row']['isamt'] = $value['amt'];
      $config['params']['row']['disc'] = $value['disc'];
      $amt  = $this->othersClass->Discount($value['amt'], $value['disc']);
      $config['params']['row']['amt'] = $amt;
      $config['params']['row']['ext'] = $amt * 1;

      $return = $this->insertbooks($config);
      if ($return['status']) {
        array_push($data, $return['row'][0]);
      }
    }
    return ['status' => true, 'msg' => 'Successfully added.', 'data' => $data];
  }

  public function insertbooks($config)
  {
    $data = [];
    $row = $config['params']['row'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    if ($data['line'] != 0) {
      if ($this->coreFunctions->sbcinsert($this->table, $data) == 1) {
        $returnrow = $this->loaddataperrecord($data['trno'], $data['line'], $data['cline'], $this->table);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Insert failed.'];
      }
    } else {
      return ['status' => false, 'msg' => 'Insert failed.'];
    }
  }

  public function add($config)
  {
    $trno = $config['params']['row']['trno'];
    $line = $config['params']['row']['line'];
    $data = [];
    $data['trno'] = $trno;
    $data['compid'] = $line;
    $data['line'] = 0;
    $data['gcsubcode'] = '';
    $data['gcsubtopic'] = '';
    $data['gcsubnoofitems'] = '';
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
    if ($data['line'] != 0) {
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['trno' => $data['trno'], 'line' => $data['line'], 'cline' => $data['cline']]) == 1) {
        $returnrow = $this->loaddataperrecord($data['trno'], $data['line'], $data['cline']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  public function delete($config)
  {
    $row = $config['params']['row'];
    $qry = "delete from $this->table where trno=? and line=? and cline=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['trno'], $row['line'], $row['cline']]);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }

  private function loaddataperrecord($trno, $line, $cline)
  {
    $sqlselect = "select stock.trno, stock.line, stock.itemid, item.barcode, item.itemname as description, 
    stock.uom, stock.isqty, stock.disc, stock.isamt, stock.amt, stock.ext, stock.cline, '' as bgcolor, '' as errcolor  ";
    $qry = $sqlselect . " FROM " . $this->table . " as stock left join item on item.itemid=stock.itemid
      where stock.trno = ? and stock.line = ? and stock.cline =?";
    $data = $this->coreFunctions->opentable($qry, [$trno, $line, $cline]);
    return $data;
  }

  public function loaddata($config)
  {
    $trno = $config['params']['row']['trno'];
    $line = $config['params']['row']['line'];

    $sqlselect = "select stock.trno, stock.line, stock.itemid, item.barcode, item.itemname as description, 
    stock.uom, stock.isqty, stock.disc, stock.isamt, stock.amt, stock.ext, stock.cline, '' as bgcolor, '' as errcolor  ";

    $qry = $sqlselect . " FROM " . $this->table . " as stock left join item on item.itemid=stock.itemid
    where stock.trno = ? and stock.cline =?
    order by item.barcode";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $stock;
  }
} //end class
