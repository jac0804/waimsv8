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
use Symfony\Component\VarDumper\VarDumper;

class entrypvitem
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'ITEMS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'pvitem';
  private $htable = 'hpvitem';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['trno', 'line', 'itemid', 'amt', 'refx', 'linex', 'poref', 'ref'];
  public $showclosebtn = true;
  public $tablelogs = 'table_log';
  public $tablelogs_del = 'del_table_log';

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
      'load' => 3591
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $column = [
      'action', 'itemdescription', 'amt', 'poref', 'ref', 'stock_projectname', 'itemname'
    ];

    $tab = [$this->gridname => ['gridcolumns' => $column]];
    $stockbuttons = ['save', 'delete'];

    $trno = $config['params']['tableid'];
    $isposted = $this->othersClass->isposted2($trno, "cntnum");

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][5]['label'] = 'Item Group';
    $obj[0][$this->gridname]['columns'][1]['type'] = 'textarea';
    $obj[0][$this->gridname]['columns'][1]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][1]['style'] = 'text-align: left; width: 380px;whiteSpace: normal;min-width:350px;max-width:400px;';
    $obj[0][$this->gridname]['columns'][4]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][5]['type'] = 'label';
    if ($isposted) {
      $obj[0][$this->gridname]['columns'][0]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][2]['readonly'] = true;
    }
    $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = ['additem', 'saveallentry', 'whlog'];
    $trno = $config['params']['tableid'];
    $isposted = $this->othersClass->isposted2($trno, "cntnum");

    if ($isposted) {
      $tbuttons = ['whlog'];
      $obj = $this->tabClass->createtabbutton($tbuttons);
    } else {
      $obj = $this->tabClass->createtabbutton($tbuttons);
      $obj[0]['lookupclass'] = 'addpvitem';
      $obj[0]['action'] = 'lookupsetup';
    }
    return $obj;
  }

  public function add($config)
  {
    $data = [];
    return $data;
  }

  private function selectqry()
  {
    $qry = "line";
    foreach ($this->fields as $key => $value) {
      $qry = $qry . ',' . $value;
    }
    return $qry;
  }

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        if ($data[$key]['line'] == 0) {
          $qry = "select line as value from " . $this->table . " where trno=? order by line desc limit 1";
          $line = $this->coreFunctions->datareader($qry, [$data[$key]['trno']]);
          if ($line == '') {
            $line = 0;
          }
          $line = $line + 1;
          $data[$key]['line'] = $line;
          $data[$key]['createby'] = $config['params']['user'];
          $data[$key]['createdate'] = $this->othersClass->getCurrentTimeStamp();
          array_push($this->fields, 'createby');
          array_push($this->fields, 'createdate');
          foreach ($this->fields as $key2 => $value2) {
            $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
          }
          $insert = $this->coreFunctions->sbcinsert($this->table, $data2);
          if ($insert) {
            $this->logger->sbcwritelog(
              $data[$key]['trno'],
              $config,
              'ADD ITEMS',
              ' PO REF: ' . $data[$key]['poref'] . ', AMOUNT: ' . $data[$key]['amt'] . ', TRNO: ' . $data[$key]['refx'] . ', LINE: ' . $data[$key]['linex']
            );
          }
        } else {
          $data[$key]['editby'] = $config['params']['user'];
          $data[$key]['editdate'] = $this->othersClass->getCurrentTimeStamp();
          array_push($this->fields, 'editby');
          array_push($this->fields, 'editdate');
          foreach ($this->fields as $key2 => $value2) {
            $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
          }
          $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line'], 'trno' => $data[$key]['trno']]);
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
  } // end function 

  public function save($config)
  {
    $userid = $config['params']['adminid'];
    $data = [];
    $row = $config['params']['row'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    if ($row['line'] == 0) {
      $qry = "select line as value from " . $this->table . " where trno=? order by line desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$data['trno']]);
      if ($line == '') {
        $line = 0;
      }
      $line = $line + 1;
      $data['line'] = $line;
      $data['createby'] = $config['params']['user'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $insert = $this->coreFunctions->sbcinsert($this->table, $data);
      if ($insert != 0) {
        $returnrow = $this->loaddataperrecord($data['trno'], $data['line']);
        $this->logger->sbcwritelog(
          $data['trno'],
          $config,
          'ADD ITEMS',
          ' PO REF: ' . $data['poref'] . ', AMOUNT: ' . $data['amt'] . ', TRNO: ' . $data['refx'] . ', LINE: ' . $data['linex']
        );
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      $current_timestamp = $this->othersClass->getCurrentTimeStamp();
      $data['editdate'] = $current_timestamp;
      $data['editby'] = $config['params']['user'];
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line'], 'trno' => $row['trno']]) == 1) {
        $returnrow = $this->loaddataperrecord($row['trno'], $row['line']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  public function delete($config)
  {
    $row = $config['params']['row'];
    $qry = "delete from " . $this->table . " where trno =? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['trno'], $row['line']]);
    $this->logger->sbcwritelog(
      $row['trno'],
      $config,
      'REMOVE',
      ' PO REF: ' . $row['poref'] . ', AMOUNT: ' . $row['amt']
    );
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }


  private function loaddataperrecord($trno, $line)
  {
    $qry = "select item.itemid,
    stock.trno,
    stock.line,
    stock.refx,
    stock.linex,stock.amt,
    prj.name as stock_projectname,
    stock.poref,stock.ref,concat(item.itemname,'\\n',ifnull(brand.brand_desc,''),'\\r\\n',ifnull(mm.model_name,''),'\\r\\n',ifnull(i.itemdescription,'')) as itemdescription,'' as bgcolor,
    '' as errcolor from pvitem as stock left join item on item.itemid = stock.itemid
    left join iteminfo as i on i.itemid = item.itemid
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join model_masterfile as mm on mm.model_id = item.model
    left join projectmasterfile as prj on prj.line = item.projectid where stock.trno =? and stock.line =?";
    $data = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $data;
  }

  public function loaddata($config)
  {
    $trno = $config['params']['tableid'];
    $qry = "select item.itemid,
    stock.trno,
    stock.line,
    stock.refx,
    stock.linex,stock.amt,
    prj.name as stock_projectname,
    stock.poref,stock.ref,concat(item.itemname,'\\n',ifnull(brand.brand_desc,''),'\\r\\n',ifnull(mm.model_name,''),'\\r\\n',ifnull(i.itemdescription,'')) as itemdescription,'' as bgcolor,
    '' as errcolor from pvitem as stock left join item on item.itemid = stock.itemid
    left join iteminfo as i on i.itemid = item.itemid
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join model_masterfile as mm on mm.model_id = item.model
    left join projectmasterfile as prj on prj.line = item.projectid where stock.trno =?
    union all 
    select item.itemid,
    stock.trno,
    stock.line,
    stock.refx,
    stock.linex,stock.amt,
    prj.name as stock_projectname,
    stock.poref,stock.ref,concat(item.itemname,'\\n',ifnull(brand.brand_desc,''),'\\r\\n',ifnull(mm.model_name,''),'\\r\\n',ifnull(i.itemdescription,'')) as itemdescription,'' as bgcolor,
    '' as errcolor from hpvitem as stock left join item on item.itemid = stock.itemid
    left join iteminfo as i on i.itemid = item.itemid
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join model_masterfile as mm on mm.model_id = item.model
    left join projectmasterfile as prj on prj.line = item.projectid where stock.trno = ?";
    $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $data;
  }

  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
      case 'whlog':
        return $this->lookuplogs($config);
        break;

      case 'addpvitem':
        return $this->lookupitem($config);
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
        $row = $config['params']['row'];
        $data = [];
        $data['trno'] = $config['params']['tableid'];
        $data['line'] = 0;
        $data['itemid'] = $row['itemid'];
        $data['itemdescription'] = $row['itemdescription'];
        $data['amt'] = 0;
        $data['refx'] = $row['trno'];
        $data['linex'] = $row['line'];
        $data['poref'] = $row['yourref'];
        $data['ref'] = $row['docno'];
        $data['stock_projectname'] = $row['stock_projectname'];
        $data['qty'] = $row['qty'];
        $data['bgcolor'] = 'bg-blue-2';
        return ['status' => true, 'msg' => 'Item was successfully added.', 'data' => $data];
        break;
    }
  } // end function


  public function lookupitem($config)
  {
    $lookupsetup = array(
      'type' => 'singlesearch',
      'actionsearch' => 'searchpvitem',
      'title' => 'List of Items',
      'style' => 'width:900px;max-width:900px;'
    );

    // lookup columns
    $cols = array(
      array('name' => 'docno', 'label' => 'Document#', 'align' => 'left', 'field' => 'docno', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'yourref', 'label' => 'PO#', 'align' => 'left', 'field' => 'yourref', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'barcode', 'label' => 'Barcode', 'align' => 'left', 'field' => 'barcode', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'itemname', 'label' => 'Itemname', 'align' => 'left', 'field' => 'itemname', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'rrqty', 'label' => 'Quantity', 'align' => 'left', 'field' => 'rrqty', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $data = [];

    $plotsetup = array(
      'plottype' => 'callback',
      'action' => 'addtogrid'
    );
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  } //end function   

  public function lookuplogs($config)
  {
    $doc = strtoupper($config['params']['lookupclass']);
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'Entry Sub Activity Items Logs',
      'style' => 'width:1000px;max-width:1000px;'
    );

    // lookup columns
    $cols = array(
      array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')

    );

    $trno = strtoupper($config['params']['sourcerow']['line']);

    $qry = "
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from " . $this->tablelogs . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "' and trno = $trno
    union all
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from  " . $this->tablelogs_del . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "' and trno = $trno ";

    $qry = $qry . " order by dateid desc";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
  }


  private function getitemname($itemid)
  {
    $qry = "select itemname as value from item where itemid = ?";
    return $this->coreFunctions->datareader($qry, [$itemid]);
  }

  private function getbarcode($itemid)
  {
    $qry = "select barcode as value from item where itemid = ?";
    return $this->coreFunctions->datareader($qry, [$itemid]);
  }
} //end class
