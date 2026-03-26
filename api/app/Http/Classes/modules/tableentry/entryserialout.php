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

class entryserialout
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'ISSUE SERIAL';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'serialout';
  private $othersClass;
  public $style = 'width:80%;max-width:80%;';
  private $fields = ['serial', 'chassis', 'color', 'pnp', 'csr', 'rem'];
  public $showclosebtn = true;


  private $counter = 1;

  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
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
    $companyid = $config['params']['companyid'];

    $getcols = ['action', 'serial', 'chassis', 'color', 'pnp', 'csr', 'rem'];

    foreach ($getcols as $key => $value) {
      $$value = $key;
    }
    $tab = [
      $this->gridname => [
        'gridcolumns' => $getcols
      ]
    ];

    $stockbuttons = ['delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][0]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";
    $obj[0][$this->gridname]['columns'][1]['readonly'] = true;
    $obj[0]['params']['trno'] = $config['params']['row']['trno'];
    $obj[0]['params']['line'] = $config['params']['row']['line'];
    $obj[0]['params']['qty'] = $config['params']['row']['iss'];
    $obj[0]['params']['type'] = 'OUT';
    $obj[0]['params']['label'] = 'Enter Serial';

    if ($companyid == 40) { //cdo
      $this->modulename = 'ADD ENGINE/CHASSIS/COLOR: ' . $config['params']['row']['itemname'];
      $obj[0]['inventory']['columns'][$color]['type'] = 'input';
      $obj[0]['inventory']['columns'][$serial]['label'] = 'Engine #';
      $obj[0]['params']['label'] = 'Enter Engine#';
      $obj[0]['inventory']['columns'][$chassis]['readonly'] = true;
      $obj[0]['inventory']['columns'][$color]['readonly'] = true;
      $obj[0]['inventory']['columns'][$pnp]['readonly'] = true;
      $obj[0]['inventory']['columns'][$csr]['readonly'] = true;
      $obj[0]['inventory']['columns'][$serial]['style'] = 'text-align:left;width:100px;whiteSpace: normal;min-width:120px;';
      $obj[0]['inventory']['columns'][$chassis]['style'] = 'text-align:left;width:100px;whiteSpace: normal;min-width:120px;';
      $obj[0]['inventory']['columns'][$pnp]['style'] = 'text-align:left;width:100px;whiteSpace: normal;min-width:120px;';
      $obj[0]['inventory']['columns'][$csr]['style'] = 'text-align:left;width:100px;whiteSpace: normal;min-width:120px;';
      $obj[0]['inventory']['columns'][$rem]['style'] = 'text-align:left;width:180px;whiteSpace: normal;min-width:180px;';
      $obj[0]['inventory']['columns'][$rem]['type'] = 'input';
    } else {
      $obj[0]['inventory']['columns'][$color]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$chassis]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$pnp]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$csr]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$rem]['type'] = 'coldel';
    }

    $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);

    return $obj;
  }


  public function createtabbutton($config)
  {
    $row = $config['params']['row'];
    $doc = $config['params']['doc'];
    $companyid = $config['params']['companyid'];
    $isserial = $this->coreFunctions->getfieldvalue('item', 'isserial', 'itemid=?', [$row['itemid']]);
    if ($isserial != '' && $isserial != 0) {
      $tbuttons = ['addserial'];
      if ($companyid == 40) { //cdo
        $tbuttons = ['addserialinout', 'saveallentry'];
      }
    } else {
      $this->modulename = 'Not Serialized Item';
      $tbuttons = [];
    }
    if ($doc == 'RF') {
      $this->modulename = 'Remove Serial';
      $tbuttons = [];
    }
    $obj = $this->tabClass->createtabbutton($tbuttons);
    if ($companyid == 40) { //cdo
      $obj[0]['label'] = 'ADD ENGINE';
    }
    return $obj;
  }

  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    $doc = $config['params']['doc'];
    switch ($lookupclass2) {
      case 'lookupserial':
        if ($doc == 'AJ') {
          $iss = $config['params']['row']['rrqty'];

          if ($iss < 0) {
            return $this->lookupserial($config);
          } else {
            return ['msg' => 'Only allowed for negative quantity.', 'status' => false];
          }
        } else {
          return $this->lookupserial($config);
        }


        break;
    }
  }

  public function lookupserial($config)
  {
    $companyid = $config['params']['companyid'];
    $lookupclass = $config['params']['lookupclass'];
    $type = 'multi';
    $actioncallback = 'getlookupserial';
    $callbackfieldlookup = array();
    $style = 'width:900px;max-width:900px;';
    $title = 'List of Available Balance';

    $lookupsetup = array(
      'type' => 'multi',
      'rowkey' => 'sline',
      'title' =>  $title,
      'btns' => [],
      'style' => $style
    );

    $plotsetup = array(
      'plottype' => 'callback',
      'action' => $actioncallback,
      'callbackfieldlookup' => $callbackfieldlookup
    );

    switch ($companyid) {
      case 40: // cdocycles
        $cols = [
          ['name' => 'serial', 'label' => 'Engine#', 'align' => 'left', 'field' => 'serial', 'sortable' => true, 'style' => 'font-size:16px;'],
          ['name' => 'chassis', 'label' => 'Chassis#', 'align' => 'left', 'field' => 'chassis', 'sortable' => true, 'style' => 'font-size:16px;'],
          ['name' => 'color', 'label' => 'Color', 'align' => 'left', 'field' => 'color', 'sortable' => true, 'style' => 'font-size:16px;'],
          ['name' => 'pnp', 'label' => 'PNP#', 'align' => 'left', 'field' => 'pnp', 'sortable' => true, 'style' => 'font-size:16px;'],
          ['name' => 'csr', 'label' => 'CSR#', 'align' => 'left', 'field' => 'csr', 'sortable' => true, 'style' => 'font-size:16px']
        ];
        break;
      default:
        $cols = [
          ['name' => 'serial', 'label' => 'Serial', 'align' => 'left', 'field' => 'serial', 'sortable' => true, 'style' => 'font-size:16px;']
        ];
        break;
    }
    $trno = $config['params']['row']['trno'];
    $line = $config['params']['row']['line'];
    $loc = $config['params']['row']['loc'];
    $item = $this->coreFunctions->opentable("select itemid, whid, loc, expiry from lastock where trno=? and line=?", [$trno, $line]);
    if (!empty($item)) {
      $data = $this->coreFunctions->opentable("select '" . $trno . "' as trno, '" . $line . "' as line, serialin.sline, serialin.serial, serialin.chassis, serialin.color, serialin.pnp, serialin.csr
        from rrstatus
        left join serialin on serialin.trno=rrstatus.trno and serialin.line=rrstatus.line
        where rrstatus.itemid=? and rrstatus.whid=? and rrstatus.loc=? and rrstatus.expiry=? and serialin.outline=0
        order by serialin.sline limit 60000", [$item[0]->itemid, $item[0]->whid, $item[0]->loc, $item[0]->expiry]);
      return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
    } else {
      return ['status' => false, 'msg' => 'No record Found'];
    }
  } // end function



  public function addserial($config)
  {
    $dinsert = [];
    $trno = $config['params']['data']['trno'];
    $line = $config['params']['data']['line'];
    $serial = $config['params']['loc'];
    $companyid = $config['params']['companyid'];
    $doc = $config['params']['doc'];
    $trno = $this->othersClass->sanitizekeyfield('trno', $trno);
    $line = $this->othersClass->sanitizekeyfield('line', $line);
    $serial = $this->othersClass->sanitizekeyfield('serial', $serial);
    $qry = "select itemid,whid,loc,expiry from lastock where trno=? and line=?";
    $item = $this->coreFunctions->opentable($qry, [$trno, $line]);
    $msg = 'Successfully updated.';
    $status = true;
    if (!empty($item)) {
      $qry = "select serialin.sline,serialin.chassis,serialin.color,serialin.pnp,serialin.csr from rrstatus left join serialin on serialin.trno=rrstatus.trno and
            serialin.line=rrstatus.line where rrstatus.itemid=? and rrstatus.whid=? and rrstatus.loc=? and expiry=? and serialin.serial=? and serialin.outline=0";
      $sline = $this->coreFunctions->opentable($qry, [$item[0]->itemid, $item[0]->whid, $item[0]->loc, $item[0]->expiry, $serial]);
      if ($sline[0]->sline != '') {
        $dinsert['trno'] = $trno;
        $dinsert['line'] = $line;
        $dinsert['serial'] = $serial;
        $dinsert['chassis'] = $sline[0]->chassis;
        $dinsert['color'] = $sline[0]->color;
        $dinsert['pnp'] = $sline[0]->pnp;
        $dinsert['csr'] = $sline[0]->csr;
        $outline = $this->coreFunctions->insertGetId($this->table, $dinsert);
        if ($outline != 0) {
          $qry = "update serialin set outline=? where sline=? and outline=0";
          $this->coreFunctions->execqry($qry, 'update', [$outline, $sline[0]->sline]);
          if ($companyid == 40) { //cdo
            $qry = "update lastock set color=? where trno=? and line=? ";
            $this->coreFunctions->execqry($qry, 'update', [$sline[0]->color, $trno, $line]);
          }
        }
      } else {
        $status = false;
        $msg = 'Serial Not found or already issued...';
      }
    } else {
      $status = false;
      $msg = 'Updating failed.';
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
      case 'ST':
      case 'CI':
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

    $config['params']['trno'] = $trno;
    $stock = app($path)->openstock($trno, $config);

    return ['status' => $status, 'msg' => $msg, 'data' => $data, 'reloadgriddata' => ['inventory' => $stock]];
  }

  public function addmultipleserial($config)
  {
    $dinsert = [];
    $trno = $config['params']['data']['trno'];
    $line = $config['params']['data']['line'];
    $trno = $this->othersClass->sanitizekeyfield('trno', $trno);
    $line = $this->othersClass->sanitizekeyfield('line', $line);
    $dinsert['trno'] = $trno;
    $dinsert['line'] = $line;
    $dinsert['outline'] = 0;
    $serial = $config['params']['loc'];
    $doc = $config['params']['doc'];
    $companyid = $config['params']['companyid'];

    $msg = 'Successfully updated.';
    foreach ($serial as $key) {
      $dinsert['serial'] = $key['serial'];
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
      case 'CI':
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

    $config['params']['trno'] = $trno;
    $stock = app($path)->openstock($trno, $config);

    return ['status' => true, 'msg' => $msg, 'data' => $data, 'reloadgriddata' => ['inventory' => $stock]];
  } //end function



  private function selectqry()
  {
    $qry = "sline,trno,line,rftrno,rfline";
    foreach ($this->fields as $key => $value) {
      $qry = $qry . ',' . $value;
    }
    return $qry;
  }

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    if ($row['sline'] == 0) {
      $stockgrp_id = $this->coreFunctions->insertGetId($this->table, $data);
      if ($stockgrp_id != 0) {
        $returnrow = $this->loaddataperrecord($stockgrp_id);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['sline' => $row['sline']]) == 1) {
        $returnrow = $this->loaddataperrecord($row['sline']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  public function delete($config)
  {
    $row = $config['params']['row'];
    $doc = $config['params']['doc'];
    $trno = $config['params']['row']['trno'];
    $companyid = $config['params']['companyid'];

    if ($doc == 'RF') {
      $qry = "update serialout set rftrno=0,rfline =0 where rftrno=? and rfline = ?";
      $this->coreFunctions->execqry($qry, 'update', [$row['rftrno'], $row['rfline']]);
      $trno = $row['rftrno'];
    } else {
      if ($companyid == 40) { //cdo
        $rrtrno = $this->coreFunctions->getfieldvalue("serialin", "trno", "outline=?", [$row['sline']]);
        $rrline = $this->coreFunctions->getfieldvalue("serialin", "line", "outline=?", [$row['sline']]);


        if ($doc <> 'AJ') {
          $qry = "update lastock set color='',isqty = isqty-1,iss =iss -1 where trno=? and line=? ";
          $this->coreFunctions->execqry($qry, 'update', [$row['trno'], $row['line']]);
          $qry = "update lastock set ext = isqty*isamt where trno=? and line=? ";
          $this->coreFunctions->execqry($qry, 'update', [$row['trno'], $row['line']]);
          $this->coreFunctions->execqry("delete from costing where trno =? and line =? and refx=? and linex=? limit 1", 'line', [$row['trno'], $row['line'], $rrtrno, $rrline]);
        } else {
          $qry = "update lastock set iss =iss -1 where trno=? and line=? ";
          $this->coreFunctions->execqry($qry, 'update', [$row['trno'], $row['line']]);
          $this->coreFunctions->execqry("delete from costing where trno =? and line =? and refx=? and linex=? limit 1", 'line', [$row['trno'], $row['line'], $rrtrno, $rrline]);
        }
      }
      $qry = "update serialin set outline=0 where outline=?";
      $this->coreFunctions->execqry($qry, 'update', [$row['sline']]);
      $qry = "delete from " . $this->table . " where sline=?";
      $this->coreFunctions->execqry($qry, 'delete', [$row['sline']]);
    }


    $path = '';

    switch ($doc) {
      case 'SJ':
      case 'RF':
        $path = 'App\Http\Classes\modules\sales\\' . strtolower($doc);
        break;
      case 'AJ':
      case 'IS':
      case 'TS':
        $path = 'App\Http\Classes\modules\inventory\\' . strtolower($doc);
        break;
      case 'CI':
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

    $config['params']['trno'] = $trno;
    $stock = app($path)->openstock($trno, $config);

    return ['status' => true, 'msg' => 'Successfully deleted.', 'reloadgriddata' => ['inventory' => $stock]];
  }

  public function saveallentry($config)
  {
    $msg = '';
    $data = $config['params']['data'];
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        $data2['rem'] = $this->othersClass->sanitizekeyfield('rem', $data[$key]['rem']);

        $this->coreFunctions->sbcupdate($this->table, $data2, ['sline' => $data[$key]['sline']]);
      } // end if
    } // foreach
    ExitHere:
    $returndata = $this->loaddata($config);
    if ($msg == '') {
      $msg = 'All saved successfully.';
    }
    return ['status' => true, 'msg' => $msg, 'data' => $returndata];
  } // end function 


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
    $doc = $config['params']['doc'];
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    if ($doc == 'RF') {
      $qry = "select " . $select . " from " . $this->table . " where rftrno=? and rfline=? order by sline";
    } else {
      $qry = "select " . $select . " from " . $this->table . " where trno=? and line=? order by sline";
    }
    $data = $this->coreFunctions->opentable($qry, [$row['trno'], $row['line']]);
    return $data;
  }
} //end class
