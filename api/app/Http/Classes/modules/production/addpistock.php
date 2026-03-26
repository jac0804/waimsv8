<?php

namespace App\Http\Classes\modules\production;

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
use Symfony\Component\VarDumper\VarDumper;

class addpistock
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'ITEMS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'pistock';
  private $htable = 'hpistock';
  public $tablenum = 'transnum';
  public $head = 'pihead';
  public $hhead = 'hpihead';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['barcode', 'itemname', 'uom', 'qty', 'stageid'];
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
    $this->logger = new Logger;
  }

  public function getAttrib($config)
  {
    switch ($config['params']['doc']) {
      case 'PD':
        $attrib = ['load' => 3673];
        break;
      default:
        $attrib = ['load' => 3633];
        break;
    }
    return $attrib;
  }

  public function createTab($config)
  {
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'barcode', 'itemname', 'qty', 'uom', 'qa']]];
    $stockbuttons = ['save', 'delete'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][0]['style'] = 'width:70px;whiteSpace: normal;min-width:70px; max-width: 100px;';
    $obj[0][$this->gridname]['columns'][0]['btns']['save']['checkfield'] = 'isposted';
    $obj[0][$this->gridname]['columns'][0]['btns']['delete']['checkfield'] = 'isposted';

    $obj[0][$this->gridname]['columns'][1]['style'] = 'width:70px;whiteSpace: normal;min-width:70px; max-width: 70px;';

    $obj[0][$this->gridname]['columns'][2]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][2]['label'] = 'Item';
    $obj[0][$this->gridname]['columns'][2]['style'] = 'width:180px;whiteSpace: normal;min-width:70px; max-width: 180px;';

    $obj[0][$this->gridname]['columns'][3]['label'] = 'Qty';
    $obj[0][$this->gridname]['columns'][3]['type'] = 'voidinput';
    $obj[0][$this->gridname]['columns'][3]['checkfield'] = 'isposted';

    $obj[0][$this->gridname]['columns'][4]['lookupclass'] = 'lookupuom';
    $obj[0][$this->gridname]['columns'][4]['action'] = 'lookupsetup';
    $obj[0][$this->gridname]['columns'][4]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][4]['type'] = 'editlookup';
    $obj[0][$this->gridname]['columns'][4]['checkfield'] = 'isposted';

    $obj[0][$this->gridname]['columns'][5]['type'] = 'label';
    return $obj;
  }

  public function createtabbutton($config)
  {
    if ($config['params']['row']['line'] == 0) {
      $tbuttons = [];
    } else {
      $tbuttons = ['addsubitem', 'saveallentry'];
    }
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  private function selectqry()
  {
    $qry = "trno, line";
    foreach ($this->fields as $key => $value) {
      $qry = $qry . ',' . $value;
    }
    return $qry;
  }

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    $config['params']['trno'] = $config['params']['tableid'];
    $isposted = $this->othersClass->isposted($config);
    $islocked = $this->othersClass->islocked($config);
    $returndata = $this->loaddata($config);
    if ($isposted) {
      return ['status' => false, 'msg' => 'Document already posted.', 'data' => $returndata];
    } else {
      if ($islocked) {
        return ['status' => false, 'msg' => 'Document locked.', 'data' => $returndata];
      } else {
        foreach ($data as $key => $value) {
          $data2 = [];
          if ($data[$key]['bgcolor'] != '') {
            foreach ($this->fields as $key2 => $value2) {
              $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
            }
            $data2['trno'] = $data[$key]['trno'];
            $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data2['editby'] = $config['params']['user'];
            if ($data[$key]['line'] == 0) {
              $qry = "select line as value from " . $this->gettablename($config, 'table') . " order by line desc limit 1";
              $line = $this->coreFunctions->datareader($qry);
              if ($line == '') $line = 0;
              $line = $line + 1;
              $data2['line'] = $line;
              $data2['rrqty'] = $data2['qty'];
              $this->coreFunctions->sbcinsert($this->gettablename($config, 'table'), $data2);
              $this->logger->sbcwritelog($data2['trno'], $config, 'INSERT STOCK', $data2['barcode'] . '-' . $data2['itemname']);
            } else {
              $this->coreFunctions->sbcupdate($this->gettablename($config, 'table'), $data2, ['line' => $data[$key]['line']]);
            }
          } // end if
        } // foreach
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
      }
    }
  } // end function 

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    $data['trno'] = $row['trno'];
    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    $data['rrqty'] = $data['qty'];
    if ($row['line'] == 0) {
      $qry = "select line as value from " . $this->gettablename($config, 'table') . " where trno=? and stageid=? order by line desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$data['trno']]);
      if ($line == '') $line = 0;
      $line = $line + 1;
      $data['line'] = $line;
      if ($this->coreFunctions->sbcinsert($this->gettablename($config, 'table'), $data) == 1) {
        $returnrow = $this->loaddataperrecord($row['trno'], $row['stageid'], $line, $config);
        $this->logger->sbcwritelog($row['trno'], $config, 'INSERT STOCK', $row['barcode'] . '-' . $row['itemname'] . '-' . $row['qty'] . '-' . $row['uom']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      if ($this->coreFunctions->sbcupdate($this->gettablename($config, 'table'), $data, ['trno' => $row['trno'], 'line' => $row['line'], 'stageid' => $row['stageid']]) == 1) {
        $returnrow = $this->loaddataperrecord($row['trno'], $row['stageid'], $row['line'], $config);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  public function delete($config)
  {
    $config['params']['trno'] = $config['params']['tableid'];
    $islocked = $this->othersClass->islocked($config);
    $isposted = $this->othersClass->isposted($config);

    if ($isposted) {
      return ['status' => false, 'msg' => 'Cannot delete, document already posted.'];
    } else {
      if ($islocked) {
        return ['status' => false, 'msg' => 'Cannot delete, document already posted.'];
      } else {
        $row = $config['params']['row'];
        $qry = "delete from " . $this->gettablename($config, 'table') . " where trno=? and stageid=? and line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['trno'], $row['stageid'], $row['line']]);
        $this->logger->sbcwritelog($row['trno'], $config, 'DELETE STOCK', $row['barcode'] . '-' . $row['itemname']);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
      }
    }
  }


  private function loaddataperrecord($trno, $stageid, $line, $config)
  {
    $select = "s.trno, s.line, s.barcode, s.itemname, s.uom, format(s.qty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qty, s.stageid, i.itemid, '' as bgcolor, round(s.qty-s.qa," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa";
    $qry = "select " . $select . " from " . $this->gettablename($config, 'table') . " as s left join item as i on i.barcode=s.barcode where s.trno=? and s.stageid=? and s.line=? union all select " . $select . " from " . $this->gettablename($config, 'htable') . " as s left join item as i on i.barcode=s.barcode where s.trno=? and s.stageid=? and s.line=?";
    $data = $this->coreFunctions->opentable($qry, [$trno, $stageid, $line, $trno, $stageid, $line]);
    return $data;
  }

  public function loaddata($config)
  {
    $config['params']['trno'] = $config['params']['tableid'];
    $isposted = $this->othersClass->isposted($config);
    $islocked = $this->othersClass->islocked($config);
    $addfield = ",'false' as isposted";
    if ($isposted || $islocked) $addfield = ",'true' as isposted";
    $line = isset($config['params']['row']['line']) ? $config['params']['row']['line'] : $config['params']['sourcerow']['line'];
    if ($line == 0) {
      return [];
    } else {
      $trno = isset($config['params']['row']['trno']) ? $config['params']['row']['trno'] : $config['params']['sourcerow']['trno'];
      $stageid = isset($config['params']['row']['stageid']) ? $config['params']['row']['stageid'] : $config['params']['sourcerow']['stageid'];
      $select = "s.trno, s.line, s.barcode, s.itemname, s.uom, format(s.qty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qty, s.stageid, i.itemid, '' as bgcolor, round(s.qty-s.qa," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa" . $addfield;
      $qry = "select " . $select . " from " . $this->gettablename($config, 'table') . " as s left join item as i on i.barcode=s.barcode where s.trno=? and s.stageid=? union all select " . $select . " from " . $this->gettablename($config, 'htable') . " as s left join item as i on i.barcode=s.barcode where s.trno=? and s.stageid=? order by line";
      $data = $this->coreFunctions->opentable($qry, [$trno, $stageid, $trno, $stageid]);
      return $data;
    }
  }

  public function lookupsetup($config)
  {
    $config['params']['trno'] = $config['params']['tableid'];
    $islocked = $this->othersClass->islocked($config);
    $isposted = $this->othersClass->isposted($config);
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
      case 'addsubitem':
        if ($isposted) {
          return ['status' => false, 'msg' => 'Document already posted.'];
        } else {
          if ($islocked) {
            return ['status' => false, 'msg' => 'Document already posted.'];
          } else {
            return $this->lookupitem($config);
          }
        }
        break;
      case 'lookupuom':
        return $this->lookupuom($config);
        break;

      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
        break;
    }
  } //end function


  public function lookupcallback($config)
  {
    switch ($config['params']['lookupclass2']) {
      case 'addtogrid':
        $trno = $config['params']['tableid'];
        $stageid = $config['params']['sourcerow']['stageid'];
        $row = $config['params']['row'];
        $data = [];
        $data['line'] = 0;
        $data['barcode'] = $row['barcode'];
        $data['itemname'] = $row['itemname'];
        $data['uom'] = $row['uom'];
        $data['trno'] = $trno;
        $data['qty'] = '';
        $data['stageid'] = $stageid;
        $data['itemid'] = $row['itemid'];
        $data['bgcolor'] = 'bg-blue-2';
        $data['qa'] = 0;
        return ['status' => true, 'msg' => 'Item was successfully added.', 'data' => $data];
        break;
    }
  } // end function

  public function lookupuom($config)
  {
    $itemid = $config['params']['row']['itemid'];
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Unit of Measurement',
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => 'plotgrid',
      'plotting' => ['uom' => 'uom']
    );
    $cols = [['name' => 'uom', 'label' => 'Unit of Measurement', 'align' => 'left', 'field' => 'uom', 'sortable' => true, 'style' => 'font-size:16px;']];
    $qry = "select uom from uom where itemid=? and isinactive = 0";
    $data = $this->coreFunctions->opentable($qry, [$itemid]);
    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  }


  public function lookupitem($config)
  {
    $lookupsetup = ['type' => 'singlesearch', 'title' => 'List of Products', 'style' => 'width:900px;max-width:900px;', 'actionsearch' => 'searchitem'];
    $plotsetup = ['plottype' => 'callback', 'action' => 'addtogrid'];
    $cols = [
      ['name' => 'barcode', 'label' => 'Barcode', 'align' => 'left', 'field' => 'barcode', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'itemname', 'label' => 'Itemname', 'align' => 'left', 'field' => 'itemname', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'uom', 'label' => 'Unit Of Measurement', 'align' => 'left', 'field' => 'uom', 'sortable' => true, 'style' => 'font-size:16px;']
    ];
    $data = [];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  } //end function    


  public function gettablename($config, $type)
  {
    if ($type == 'table') {
      switch ($config['params']['doc']) {
        case 'PD':
          return 'pdstock';
          break;
        default:
          return $this->table;
          break;
      }
    } else {
      switch ($config['params']['doc']) {
        case 'PD':
          return 'hpdstock';
          break;
        default:
          return $this->htable;
          break;
      }
    }
  }
} //end class
