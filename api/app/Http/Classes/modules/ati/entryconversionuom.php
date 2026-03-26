<?php

namespace App\Http\Classes\modules\ati;

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
use App\Http\Classes\tableentryClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

class entryconversionuom
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Conversion UOM';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'uomlist';
  public $tablelogs = 'masterfile_log';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['uom', 'factor', 'isconvert'];
  public $showclosebtn = false;
  private $reporter;
  private $logger;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->reporter = new SBCPDF;
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = array(
      'load' => 4500
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $action = 0;
    $uom = 1;
    $factor = 2;

    $columns = ['action', 'uom', 'factor'];
    $tab = [$this->gridname => ['gridcolumns' => $columns]];

    $stockbuttons = ['save', 'delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$uom]['type'] = "input";
    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = ['addrecord', 'saveallentry', 'whlog'];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }

  public function add($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['uom'] = '';
    $data['factor'] = 0;
    $data['isconvert'] = 1;
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  public function save($config)
  {

    $data = [];
    $row = $config['params']['row'];
    $companyid = $config['params']['companyid'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value], '', $companyid);
    }

    if ($data['uom'] == '') {
      return ['status' => true, 'msg' => 'Please input valid UOM.'];
    }

    if ($row['line'] == 0) {
      $uom = $this->coreFunctions->getfieldvalue("uomlist", "uom", "uom=? and isconvert=1", [$data['uom']]);
      if (!empty($uom)) {
        return ['status' => false, 'msg' => 'A UOM for ' .  $uom . ' already exists.'];
      } else {
        $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['createby'] = $config['params']['user'];
        $line = $this->coreFunctions->insertGetId($this->table, $data);
        $this->logger->sbcmasterlog($line, $config, ' CREATE - ' . $data['uom'] . ' - Factor: ' . $data['factor']);
        $returnrow = $this->loaddataperrecord($line);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      }
    } else {
      $olduom = $this->coreFunctions->getfieldvalue("uomlist", "uom", "line=?", [$row['line']]);
      $uom = $this->coreFunctions->getfieldvalue("uomlist", "uom", "uom=? and isconvert=1", [$data['uom']]);
      if (!empty($uom) || $this->checkuomtransaction($olduom)) {
        return ['status' => false, 'msg' => 'Unable to modify ' . $olduom . ', already used in transactons or already exists.'];
      } else {
        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];

        if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
          $returnrow = $this->loaddataperrecord($row['line']);
          return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
        } else {
          return ['status' => false, 'msg' => 'Saving failed.'];
        }
      }
    }

    return ['status' => true, 'msg' => 'Successfully saved.'];
  }

  private function loaddataperrecord($line)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor, line ";
    $qry = "select " . $select . " from " . $this->table . " where line=? and isconvert=1 order by line";
    $data = $this->coreFunctions->opentable($qry, [$line]);
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

  public function delete($config)
  {
    $row = $config['params']['row'];
    $tableid = $config['params']['tableid'];

    if ($this->checkuomtransaction($row['uom'])) {
      return ['status' => false, 'msg' => 'Unable to delete, already used in transactons.'];
    } else {
      $qry = "delete from uomlist where line=?";
      $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
      $params = $config;
      $params['params']['doc'] = strtoupper("entryuomlist");
      $this->logger->sbcmasterlog(
        $tableid,
        $params,
        ' DELETE - LINE: ' . $row['line'] . '' . ', UOM: ' . $row['uom'] . ', FACTOR: ' . $row['factor']
      );

      return ['status' => true, 'msg' => 'Successfully deleted.'];
    }
  }

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    $datatest = [];
    $companyid = $config['params']['companyid'];

    foreach ($data as $key => $value) {
      foreach ($this->fields as $key2 => $value2) {
        $datatest[$value2] = $data[$key][$value2];
      }
      if ($datatest['factor'] == 0) {
        return ['status' => false, 'msg' => 'Invalid factor for UOM ' . $datatest['uom']];
      }
    }

    $msg = '';
    $status = true;
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2], '', $companyid);
        }

        if ($data[$key]['line'] == 0) {
          $uom = $this->coreFunctions->getfieldvalue("uomlist", "uom", "uom=? and isconvert=1", [$data[$key]['uom']]);
          if (!empty($uom)) {
            $msg = 'A UOM for ' .  $uom . ' already exists.';
            $status = false;
          } else {
            $data2['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data2['createby'] = $config['params']['user'];
            $line = $this->coreFunctions->insertGetId($this->table, $data2);
            $this->logger->sbcmasterlog($line, $config, ' CREATE - ' . $data[$key]['uom'] . ' - Factor: ' . $data[$key]['factor']);
          }
        } else {
          $olduom = $this->coreFunctions->getfieldvalue("uomlist", "uom", "line=? and isconvert =1", [$data[$key]['line']]);
          $uom = $this->coreFunctions->getfieldvalue("uomlist", "uom", "uom=? and isconvert=1", [$data[$key]['uom']]);
          if (!empty($uom) || $this->checkuomtransaction($olduom)) {
            $msg = 'Unable to modify ' .  $olduom . ', already used in transactons or already exists.';
            $status = false;
          } else {
            $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data2['editby'] = $config['params']['user'];
            $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
          }
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    if ($msg == '') {
      $msg = 'All saved successfully.';
    }
    return ['status' => $status, 'msg' => $msg, 'data' => $returndata];
  } // end function 

  public function loaddata($config)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor, line ";
    $qry = "select " . $select . " from " . $this->table . " where isconvert=1 order by line";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
      case 'whlog':
        return $this->lookuplogs($config);
        break;

      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
        break;
    }
  }

  public function lookuplogs($config)
  {
    $doc = 'ENTRYCONVERSIONUOM'; //$config['params']['doc'];
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'UOM Logs',
      'style' => 'width:1000px;max-width:1000px;'
    );

    // lookup columns
    $cols = array(
      array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
      // array('name' => 'doc', 'label' => 'Doc', 'align' => 'left', 'field' => 'doc', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')

    );

    $trno = $config['params']['tableid'];

    $qry = "
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from " . $this->tablelogs . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "'";

    $qry = $qry . " order by dateid desc";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
  }

  private function checkuomtransaction($uom)
  {
    $qry = "select stock.trno from cdstock as stock left join item on item.itemid=stock.itemid 
        left join stockinfotrans as info on info.trno=stock.trno and info.line=stock.line where info.uom2='" . $uom . "'
        union all
        select stock.trno from hcdstock as stock left join item on item.itemid=stock.itemid 
        left join hstockinfotrans as info on info.trno=stock.trno and info.line=stock.line where info.uom2='" . $uom . "'
        union all
        select stock.trno from cdstock as stock left join item on item.itemid=stock.itemid 
        left join stockinfotrans as info on info.trno=stock.trno and info.line=stock.line where info.uom3='" . $uom . "'
        union all
        select stock.trno from hcdstock as stock left join item on item.itemid=stock.itemid 
        left join hstockinfotrans as info on info.trno=stock.trno and info.line=stock.line where info.uom3='" . $uom . "'";
    $data = $this->coreFunctions->opentable($qry);
    if (!empty($data)) {
      return true;
    } else {
      return false;
    }
  }
} //end class
