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
use App\Http\Classes\builder\lookupclass;

class entrypiprocess
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'PROCESS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'piprocess';
  public $tablenum = 'transnum';
  public $head = 'pihead';
  public $hhead = 'hpihead';
  private $htable = 'hpiprocess';
  private $stock = 'pistock';
  private $hstock = 'hpistock';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['stageid', 'percentage', 'itemid'];
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  public $showclosebtn = false;
  private $enrollmentlookup;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->lookupclass = new lookupclass;
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
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'stage', 'itemname', 'percentage']]];
    $stockbuttons = ['save', 'delete', 'addpistock'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][0]['btns']['addpistock']['checkfield'] = 'newtrans';
    $obj[0][$this->gridname]['columns'][0]['btns']['save']['checkfield'] = 'isposted';
    $obj[0][$this->gridname]['columns'][0]['btns']['delete']['checkfield'] = 'isposted';
    $obj[0][$this->gridname]['columns'][0]['style'] = 'width:100px;white-space: normal;min-width:100px;max-width:100px;';
    $obj[0][$this->gridname]['columns'][0]['headerStyle'] = 'max-width:100px;min-width:100px;';

    $obj[0][$this->gridname]['columns'][1]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][1]['label'] = 'Process';
    $obj[0][$this->gridname]['columns'][3]['type'] = 'label';

    switch ($config['params']['doc']) {
      case 'PD':
        $obj[0][$this->gridname]['columns'][1]['style'] = 'width:150px;white-space:normal;min-width:150px;max-width:150px;';
        $obj[0][$this->gridname]['columns'][1]['headerStyle'] = 'min-width:150px;max-width:150px;';

        $obj[0][$this->gridname]['columns'][2]['label'] = 'Semi Finished Goods Item';
        $obj[0][$this->gridname]['columns'][2]['type'] = "lookup";
        $obj[0][$this->gridname]['columns'][2]['action'] = "lookupsetup";
        $obj[0][$this->gridname]['columns'][2]['lookupclass'] = "lookupitem";
        $obj[0][$this->gridname]['columns'][2]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][2]['style'] = "width:200px;white-space: normal;min-width:200px;max-width:200px;";
        $obj[0][$this->gridname]['columns'][2]['headerStyle'] = 'min-width:200px;max-width:200px;';

        $obj[0][$this->gridname]['columns'][3]['style'] = 'width:100px;white-space:normal;min-width:100px;max-width:100px;';
        $obj[0][$this->gridname]['columns'][3]['headerStyle'] = 'min-width:100px;max-width:100px;';
        break;
      default:
        $obj[0][$this->gridname]['columns'][1]['style'] = "width:500px;white-space: normal;min-width:500px;";
        $obj[0][$this->gridname]['columns'][1]['headerStyle'] = 'min-width:500px;';
        $obj[0][$this->gridname]['columns'][2]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][3]['type'] = 'coldel';
        break;
    }


    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['addpiprocess', 'saveallentry'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  private function selectqry()
  {
    $qry = " s.trno, s.line, s.stageid, s.itemid, s.percentage, st.stage, '' as bgcolor, 'false' as newtrans, i.itemname ";
    return $qry;
  }

  public function loaddata($config)
  {
    $trno = $config['params']['tableid'];
    $config['params']['trno'] = $trno;
    $isposted = $this->othersClass->isposted($config);
    $islocked = $this->othersClass->islocked($config);
    $addfield = ",'false' as isposted ";
    if ($isposted || $islocked) $addfield = ",'true' as isposted";
    $select = $this->selectqry();
    $select = $select . $addfield;
    $qry = "select " . $select . " from " . $this->table . " as s left join stagesmasterfile as st on st.line=s.stageid left join item as i on i.itemid=s.itemid where s.trno=? union all select " . $select . " from " . $this->htable . " as s left join stagesmasterfile as st on st.line=s.stageid left join item as i on i.itemid=s.itemid where s.trno=?";
    return $this->coreFunctions->opentable($qry, [$trno, $trno]);
  }

  private function loaddataperrecord($trno, $line)
  {
    $select = $this->selectqry();
    $qry = "select " . $select . " from " . $this->table . " as s left join stagesmasterfile as st on st.line=s.stageid left join item as i on i.itemid=s.itemid where s.trno=? and s.line=? union all select " . $select . " from " . $this->htable . " as s left join stagesmasterfile as st on st.line=s.stageid left join item as i on i.itemid=s.itemid where s.trno=? and s.line=?";
    $data = $this->coreFunctions->opentable($qry, [$trno, $line, $trno, $line]);
    return $data;
  }

  public function delete($config)
  {
    $config['params']['trno'] = $config['params']['tableid'];
    $islocked = $this->othersClass->islocked($config);
    $isposted = $this->othersClass->isposted($config);
    if ($isposted) {
      return ['status' => false, 'msg' => 'Cannot delete, document already posted.'];
    } else {
      if ($islocked) {
        return ['status' => false, 'msg' => 'Cannot delete, document locked.'];
      } else {
        $this->coreFunctions->execqry("delete from " . $this->table . " where trno=? and line=?", 'delete', [$config['params']['trno'], $config['params']['row']['line']]);
        $this->coreFunctions->execqry("delete from " . $this->getstocktbl($config, 'table') . " where trno=? and stageid=?", 'delete', [$config['params']['trno'], $config['params']['row']['stageid']]);
        $this->logger->sbcwritelog($config['params']['row']['trno'], $config, 'DELETE PROCESS', $config['params']['row']['stage'] . '-' . $config['params']['row']['percentage']);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
      }
    }
  }

  public function lookupcallback($config)
  {
    $data = [];
    switch ($config['params']['lookupclass2']) {
      case 'addtogrid':
        $row = $config['params']['row'];
        $data = [];
        $data['line'] = 0;
        $data['trno'] = $config['params']['tableid'];
        $data['stageid'] = $row['stageid'];
        $data['itemid'] = 0;
        $data['itemname'] = '';
        $data['percentage'] = 0;
        $data['stage'] = $row['stage'];
        $data['bgcolor'] = 'bg-blue-2';
        $data['newtrans'] = 'true';
        return ['status' => true, 'msg' => 'Add Process success...', 'data' => $data];
        break;
    }
  } // end function

  public function lookupsetup($config)
  {
    $config['params']['trno'] = $config['params']['tableid'];
    switch ($config['params']['lookupclass2']) {
      case 'addprocess':
        $islocked = $this->othersClass->islocked($config);
        $isposted = $this->othersClass->isposted($config);
        if ($isposted) {
          return ['status' => false, 'msg' => 'Document posted', 'data' => []];
        } else {
          if ($islocked) {
            return ['status' => false, 'msg' => 'Document locked', 'data' => []];
          } else {
            if ($config['params']['trno'] == 0) {
              return ['status' => false, 'msg' => 'Invalid document', 'data' => []];
            } else {
              return $this->lookupprocess($config);
            }
          }
        }
        break;
      case 'lookupitem':
        return $this->lookupitem($config);
        break;
      default:
        return ['status' => false, 'msg' => 'Invalid Lookup setup', 'data' => []];
        break;
    }
  }

  public function lookupitem($config)
  {
    $lookupsetup = ['type' => 'singlesearch', 'title' => 'List of Products', 'style' => 'width:900px;max-width:900px;', 'actionsearch' => 'searchitem'];
    $plotsetup = ['plottype' => 'plotgrid', 'action' => '', 'plotting' => ['itemname' => 'itemname', 'itemid' => 'itemid']];
    $cols = [
      ['name' => 'barcode', 'label' => 'Barcode', 'align' => 'left', 'field' => 'barcode', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'itemname', 'label' => 'Itemname', 'align' => 'left', 'field' => 'itemname', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'uom', 'label' => 'Unit Of Measurement', 'align' => 'left', 'field' => 'uom', 'sortable' => true, 'style' => 'font-size:16px;']
    ];
    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => [], 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  } //end function    

  public function lookupprocess($config)
  {
    $trno = $config['params']['tableid'];
    $lookupsetup = array(
      'type' => 'single',
      'rowkey' => 'stageid',
      'title' => 'List of Process',
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => 'callback',
      'action' => 'addtogrid'
    );
    $cols = [
      ['name' => 'stage', 'label' => 'Process', 'align' => 'left', 'field' => 'stage', 'sortable' => true, 'style' => 'font-size:16px;']
    ];
    $qry = "select line as stageid, stage, description, " . $trno . " as trno from stagesmasterfile order by stage";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function saveallentry($config)
  {
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
        $data = $config['params']['data'];
        foreach ($data as $key => $value) {
          $data2 = [];
          if ($data[$key]['bgcolor'] != '') {
            foreach ($this->fields as $key2 => $value2) {
              $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
            }
            $data2['trno'] = $data[$key]['trno'];
            if ($data[$key]['line'] == 0) {
              $check = $this->coreFunctions->datareader("select line as value from piprocess where trno=? and stageid=? limit 1", [$data2['trno'], $data2['stageid']]);
              if ($check == '') {
                $qry = "select line as value from piprocess where trno=? order by line desc limit 1";
                $line = $this->coreFunctions->datareader($qry, [$data2['trno']]);
                if ($line == '') $line = 0;
                $line = $line + 1;
                $data2['line'] = $line;
                if ($this->coreFunctions->sbcinsert($this->table, $data2) == 1) {
                  $this->logger->sbcwritelog($data2['trno'], $config, 'INSERT PROCESS', $data[$key]['stage'] . '-' . $data2['percentage']);
                }
              }
            } else {
              $this->coreFunctions->sbcupdate($this->table, $data2, ['trno' => $data[$key]['trno'], 'line' => $data[$key]['line'], 'stageid' => $data[$key]['stageid']]);
              $this->logger->sbcwritelog($data2['trno'], $config, 'UPDATE PROCESS', $data[$key]['stage'] . '-' . $data2['percentage']);
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
    $data['line'] = $row['line'];
    $check = $this->coreFunctions->datareader("select line as value from piprocess where trno=? and stageid=? limit 1", [$row['trno'], $row['stageid']]);
    if ($check == '') {
      if ($row['line'] == 0) {
        $qry = "select line as value from piprocess where trno=? order by line desc limit 1";
        $line = $this->coreFunctions->datareader($qry, [$data['trno']]);
        if ($line == '') $line = 0;
        $line = $line + 1;
        $data['line'] = $line;
        if ($this->coreFunctions->sbcinsert($this->table, $data) == 1) {
          $returnrow = $this->loaddataperrecord($row['trno'], $line);
          $this->logger->sbcwritelog($data['trno'], $config, 'INSERT PROCESS', $row['stage'] . '-' . $row['percentage']);
          return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
        } else {
          return ['status' => false, 'msg' => 'Saving failed.'];
        }
      } else {
        if ($this->coreFunctions->sbcupdate($this->table, $data, ['trno' => $row['trno'], 'line' => $row['line'], 'stageid' => $row['stageid']]) == 1) {
          $returnrow = $this->loaddataperrecord($row['trno'], $row['line']);
          $this->logger->sbcwritelog($data['trno'], $config, 'UPDATE PROCESS', $row['stage'] . '-' . $row['percentage']);
          return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
        } else {
          return ['status' => false, 'msg' => 'Saving failed.'];
        }
      }
    } else {
      return ['status' => false, 'msg' => 'Saving failed. Stage had already been used.'];
    }
  }

  public function getstocktbl($config, $type)
  {
    if ($type == 'table') {
      switch ($config['params']['doc']) {
        case 'PD':
          return 'pdstock';
          break;
        default:
          return $this->stock;
          break;
      }
    } else {
      switch ($config['params']['doc']) {
        case 'PD':
          return 'hpdstock';
          break;
        default:
          return $this->hstock;
          break;
      }
    }
  }
} //end class
