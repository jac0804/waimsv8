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
use App\Http\Classes\sbcdb\trigger;
use App\Http\Classes\sbcdb\waims;
use App\Http\Classes\sbcdb\customersupport;

class entryserialin
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'ADD SERIAL - IN';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'serialin';
  private $othersClass;
  public $style = 'width:80%;max-width:100%;';
  private $fields = ['serial', 'chassis', 'color', 'pnp', 'csr', 'remarks'];
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
    $attrib = array(
      'load' => 2999
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $action = 0;
    $serial = 1;
    $chassis = 2;
    $color = 3;
    $pnp = 4;
    $csr = 5;
    $remarks = 6;

    $tab = [
      $this->gridname => [
        'gridcolumns' => ['action', 'serial', 'chassis', 'color', 'pnp', 'csr', 'remarks']
      ]
    ];

    $stockbuttons = ['save', 'delete'];

    $companyid = $config['params']['companyid'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][0]['style'] = "width:90px;whiteSpace: normal;min-width:90px;";
    $obj[0]['params']['trno'] = $config['params']['row']['trno'];
    $obj[0]['params']['line'] = $config['params']['row']['line'];
    $obj[0]['params']['qty'] = $config['params']['row']['qty'];
    $obj[0]['params']['type'] = 'IN';
    $obj[0]['params']['label'] = 'Enter Serial';

    if ($companyid == 40) { //cdo
      $this->modulename = 'ADD ENGINE/CHASSIS/COLOR: ' . $config['params']['row']['itemname'];
      $obj[0]['inventory']['columns'][$color]['type'] = 'lookup';
      $obj[0]['inventory']['columns'][$color]['lookupclass'] = 'lookupcolor';
      $obj[0]['inventory']['columns'][$color]['lookupclass2'] = 'lookupcolor';
      $obj[0]['inventory']['columns'][$color]['action'] = 'lookupsetup';
      $obj[0]['inventory']['columns'][$color]['readonly'] = false;
      $obj[0]['inventory']['columns'][$color]['class'] = "sbccsenablealways";
      $obj[0]['inventory']['columns'][$serial]['label'] = 'Engine #';
      $obj[0]['params']['label'] = 'Enter Engine#';
      $obj[0]['inventory']['columns'][$serial]['style'] = 'text-align:left;width:100px;whiteSpace: normal;min-width:100px;';
      $obj[0]['inventory']['columns'][$chassis]['style'] = 'text-align:left;width:90px;whiteSpace: normal;min-width:90px;';
      $obj[0]['inventory']['columns'][$color]['style'] = 'text-align:left;width:90px;whiteSpace: normal;min-width:90px;';
      $obj[0]['inventory']['columns'][$pnp]['style'] = 'text-align:left;width:90px;whiteSpace: normal;min-width:90px;';
      $obj[0]['inventory']['columns'][$csr]['style'] = 'text-align:left;width:90px;whiteSpace: normal;min-width:90px;';
      $obj[0]['inventory']['columns'][$remarks]['style'] = 'text-align:left;width:90px;whiteSpace: normal;min-width:90px;';
    } else {
      $obj[0]['inventory']['columns'][$color]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$chassis]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$pnp]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$csr]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$remarks]['type'] = 'coldel';
    }

    $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);


    return $obj;
  }


  public function createtabbutton($config)
  {
    $row = $config['params']['row'];
    $companyid = $config['params']['companyid'];
    $isserial = $this->coreFunctions->getfieldvalue('item', 'isserial', 'itemid=?', [$row['itemid']]);
    if ($isserial != '' && $isserial != 0) {
      $tbuttons = ['addserial', 'saveallentry'];
    } else {
      $this->modulename = 'Not Serialized Item';
      $tbuttons = [];
    }

    $obj = $this->tabClass->createtabbutton($tbuttons);
    if ($companyid == 40) { //cdo
      $obj[0]['label'] = 'ADD ENGINE';
    }

    return $obj;
  }


  public function addserial($config)
  {
    $dinsert = [];
    $trno = $config['params']['data']['trno'];
    $line = $config['params']['data']['line'];
    $doc = $config['params']['doc'];
    $serial = $config['params']['loc'];
    $trno = $this->othersClass->sanitizekeyfield('trno', $trno);
    $line = $this->othersClass->sanitizekeyfield('line', $line);
    $serial = $this->othersClass->sanitizekeyfield('serial', $serial);
    $dinsert['trno'] = $trno;
    $dinsert['line'] = $line;
    $dinsert['serial'] = $serial;
    $dinsert['outline'] = 0;
    $this->coreFunctions->insertGetId($this->table, $dinsert);
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " where trno=? and line=? order by sline";
    $data = $this->coreFunctions->opentable($qry, [$trno, $line]);

    $path = '';

    switch ($doc) {
      case 'SJ':
        $path = 'App\Http\Classes\modules\sales\\' . strtolower($doc);
        break;
      case 'AJ':
      case 'IS':
      case 'TS':
        $path = 'App\Http\Classes\modules\inventory\\' . strtolower($doc);
        break;
      default:
        $path = 'App\Http\Classes\modules\purchase\\' . strtolower($doc);
        break;
    }

    $config['params']['trno'] = $trno;
    $stock = app($path)->openstock($trno, $config);

    return ['status' => true, 'msg' => 'Ready For New Record', 'data' => $data, 'reloadgriddata' => ['inventory' => $stock]];
  }

  public function addmultipleserial($config)
  {
    $dinsert = [];
    $trno = $config['params']['data']['trno'];
    $line = $config['params']['data']['line'];
    $companyid = $config['params']['companyid'];
    $trno = $this->othersClass->sanitizekeyfield('trno', $trno);
    $line = $this->othersClass->sanitizekeyfield('line', $line);
    $dinsert['trno'] = $trno;
    $dinsert['line'] = $line;
    $dinsert['outline'] = 0;
    $serial = $config['params']['loc'];
    $doc = $config['params']['doc'];

    foreach ($serial as $key) {
      $dinsert['serial'] = $key['serial'];
      if ($companyid == 40) { //cdo
        $dinsert['chassis'] = $key['chassis'];
        $dinsert['color'] = $key['color'];
      }

      $this->coreFunctions->insertGetId($this->table, $dinsert);
    }
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " where trno=? and line=? order by sline";
    $data = $this->coreFunctions->opentable($qry, [$trno, $line]);

    $path = '';

    switch ($doc) {
      case 'SJ':
        $path = 'App\Http\Classes\modules\sales\\' . strtolower($doc);
        break;
      case 'AJ':
      case 'IS':
      case 'TS':
        $path = 'App\Http\Classes\modules\inventory\\' . strtolower($doc);
        break;
      default:
        $path = 'App\Http\Classes\modules\purchase\\' . strtolower($doc);
        break;
    }

    $config['params']['trno'] = $trno;
    $stock = app($path)->openstock($trno, $config);

    return ['status' => true, 'msg' => 'Ready For New Record', 'data' => $data, 'reloadgriddata' => ['inventory' => $stock]];
  } //end function

  private function selectqry()
  {
    $qry = "sline";
    foreach ($this->fields as $key => $value) {
      $qry = $qry . ',' . $value;
    }
    return $qry;
  }

  public function save($config)
  {
    $data = [];
    $doc = $config['params']['doc'];
    $companyid = $config['params']['companyid'];
    $row = $config['params']['row'];
    $trno = $config['params']['tableid'];

    $path = '';

    switch ($doc) {
      case 'AJ':
      case 'IS':
      case 'TS':
        $path = 'App\Http\Classes\modules\inventory\\' . strtolower($doc);
        break;
      case 'ST':
        if ($companyid == 40) { //cdo
          $path = 'App\Http\Classes\modules\cdo\\' . strtolower($doc);
        } else {
          $path = 'App\Http\Classes\modules\inventory\\' . strtolower($doc);
        }
        break;
      default:
        $path = 'App\Http\Classes\modules\purchase\\' . strtolower($doc);
        break;
    }

    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    if ($row['sline'] == 0) {
      $stockgrp_id = $this->coreFunctions->insertGetId($this->table, $data);
      if ($stockgrp_id != 0) {
        $returnrow = $this->loaddataperrecord($stockgrp_id);
        $config['params']['trno'] = $trno;
        $stock = app($path)->openstock($trno, $config);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow, 'reloadgriddata' => ['inventory' => $stock]];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['sline' => $row['sline']]) == 1) {
        $returnrow = $this->loaddataperrecord($row['sline']);
        $config['params']['trno'] = $trno;
        $stock = app($path)->openstock($trno, $config);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow, 'reloadgriddata' => ['inventory' => $stock]];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    $doc = $config['params']['doc'];
    $companyid = $config['params']['companyid'];
    $trno = $config['params']['data']['trno'];

    $path = '';

    switch ($doc) {
      case 'AJ':
      case 'IS':
      case 'TS':
        $path = 'App\Http\Classes\modules\inventory\\' . strtolower($doc);
        break;
      case 'ST':
        if ($companyid == 40) { //cdo
          $path = 'App\Http\Classes\modules\cdo\\' . strtolower($doc);
        } else {
          $path = 'App\Http\Classes\modules\inventory\\' . strtolower($doc);
        }
        break;
      default:
        $path = 'App\Http\Classes\modules\purchases\\' . strtolower($doc);
        break;
    }

    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }
        if ($data[$key]['sline'] == 0) {
          $line = $this->coreFunctions->insertGetId($this->table, $data2);
        } else {
          $this->coreFunctions->sbcupdate($this->table, $data2, ['sline' => $data[$key]['sline']]);
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    $config['params']['trno'] = $trno;
    $stock = app($path)->openstock($trno, $config);
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata, 'reloadgriddata' => ['inventory' => $stock]];
  } //end function

  public function delete($config)
  {
    $row = $config['params']['row'];
    $doc = $config['params']['doc'];
    $trno = $config['params']['tableid'];

    $qry = "select serial as value from " . $this->table . " where sline=? and outline<>0";
    $count = $this->coreFunctions->datareader($qry, [$row['sline']]);

    if ($count != '') {
      return ['clientid' => $row['sline'], 'status' => false, 'msg' => 'Already have transaction...'];
    }

    $qry = "delete from " . $this->table . " where sline=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['sline']]);

    $path = '';

    switch ($doc) {
      case 'SJ':
        $path = 'App\Http\Classes\modules\sales\\' . strtolower($doc);
        break;
      case 'AJ':
      case 'IS':
      case 'TS':
        $path = 'App\Http\Classes\modules\inventory\\' . strtolower($doc);
        break;
      default:
        $path = 'App\Http\Classes\modules\purchase\\' . strtolower($doc);
        break;
    }

    $config['params']['trno'] = $trno;
    $stock = app($path)->openstock($trno, $config);

    return ['status' => true, 'msg' => 'Successfully deleted.', 'reloadgriddata' => ['inventory' => $stock]];
  }


  private function loaddataperrecord($stockgrp_id)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " where sline=?";
    $data = $this->coreFunctions->opentable($qry, [$stockgrp_id]);
    return $data;
  }

  public function loaddata($config)
  {
    $row = $config['params']['row'];
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " where trno=? and line=? order by sline";
    $data = $this->coreFunctions->opentable($qry, [$row['trno'], $row['line']]);
    return $data;
  }

  public function tableentrystatus($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
      case 'lookupcolor':
        return $this->lookupcolor($config);
        break;
    }
  }

  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
      case 'lookupcolor':
        return $this->lookupcolor($config);
        break;
    }
  }

  public function lookupcolor($config)
  {
    //default
    $plotting = array('color' => 'color');
    $plottype = 'plotgrid';
    $title = 'List of Color';

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
    array_push($cols, array('name' => 'color', 'label' => 'Color', 'align' => 'left', 'field' => 'color', 'sortable' => true, 'style' => 'font-size:16px;'));

    $itemid = $this->coreFunctions->datareader("select s.itemid as value from lastock as s left join serialin as ss on ss.trno = s.trno and ss.line = s.line where s.trno = " . $config['params']['tableid'] . " and ss.sline = " . $config['params']['row']['sline']);
    $qry = "select distinct color from (select s.color from serialin as s left join lastock as stock on stock.trno = s.trno and stock.line = s.line where stock.itemid = $itemid
     union all
    select s.color from serialin as s left join glstock as stock on stock.trno = s.trno and stock.line = s.line where stock.itemid = $itemid) as a";

    $data = $this->coreFunctions->opentable($qry);
    $index = $config['params']['index'];
    $table = isset($config['params']['table']) ? $config['params']['table'] : "";
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index, 'rowindex' => $index, 'table' => $table];
  }
} //end class
