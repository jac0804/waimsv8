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

class entryuom
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'UNIT OF MEASUREMENT';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'uom';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $logger;
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['itemid', 'uom', 'factor', 'amt', 'amt2', 'famt', 'isinactive', 'isdefault', 'isdefault2', 'issales', 'issalesdef','printuom'];
  public $showclosebtn = false;


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
      'load' => 0
    );
    return $attrib;
  }


  public function createTab($config)
  {
    $companyid = $config['params']['companyid'];
    $itemid = $config['params']['tableid'];
    $allow_update = $this->othersClass->checkAccess($config['params']['user'], 4865);
    $item = $this->othersClass->getitemname($itemid);
    $this->modulename = $this->modulename . ' ~ ' . $item[0]->barcode . ' ~ ' . $item[0]->itemname;


    $column = ['action', 'uom', 'factor', 'amt', 'amt2', 'famt', 'isdefault', 'isdefault2', 'isinactive', 'issales', 'issalesdef','printuom'];
    $tab = [$this->gridname => ['gridcolumns' => $column]];
    foreach ($column as $key => $value) {
      $$value = $key;
    }
    $stockbuttons = ['save', 'delete'];
    if ($companyid == 21) { // kinggeorge
      if (!$allow_update) {
        $stockbuttons = [];
      }
    }

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";
    $obj[0][$this->gridname]['columns'][$uom]['type'] = "input";

    $obj[0][$this->gridname]['columns'][$amt2]['label'] = "Wholesale";
    $obj[0][$this->gridname]['columns'][$famt]['label'] = "Others";

    if (!$this->companysetup->getisuomamt($config['params'])) {
      $obj[0][$this->gridname]['columns'][$amt]['type'] = "coldel";

      switch ($companyid) {
        case 37: //mega crystal
          break;
        default:
          $obj[0][$this->gridname]['columns'][$amt2]['type'] = "coldel";
          $obj[0][$this->gridname]['columns'][$famt]['type'] = "coldel";
          break;
      }
    }

    if ($companyid != 21) { //not kinggeorge
      $obj[0][$this->gridname]['columns'][$issales]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$issalesdef]['type'] = "coldel";
    }

    switch ($companyid) {
      case 11: //summit
        $obj[0][$this->gridname]['columns'][$isdefault]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$printuom]['type'] = "coldel";
        break;
      case 10: //afti
      case 12: //afti usd
      case 14: //MAJESTY
      case 27: //NTE
      case 36: //ROZLAB
        $obj[0][$this->gridname]['columns'][$isinactive]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$isdefault2]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$printuom]['type'] = "coldel";
        break;
      case 24: //goodfound
        $obj[0][$this->gridname]['columns'][$isdefault]['label'] = "Default IN";
        $obj[0][$this->gridname]['columns'][$isdefault2]['label'] = "Default OUT";
        $obj[0][$this->gridname]['columns'][$isinactive]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$printuom]['type'] = "coldel";
        break;
      default:
        if (!$this->companysetup->getisdefaultuominout($config['params'])) {
          $obj[0][$this->gridname]['columns'][$isdefault]['type'] = "coldel";
          $obj[0][$this->gridname]['columns'][$isdefault2]['type'] = "coldel";
          $obj[0][$this->gridname]['columns'][$isinactive]['type'] = "coldel";
        } else {
          $obj[0][$this->gridname]['columns'][$isdefault]['label'] = "Default IN";
          $obj[0][$this->gridname]['columns'][$isdefault2]['label'] = "Default OUT";
        }
        if($companyid!=63){
          $obj[0][$this->gridname]['columns'][$printuom]['type'] = "coldel";
        }

        break;
    }

    if ($companyid == 11) { //summit
      $obj[0][$this->gridname]['columns'][$amt]['type'] = "coldel";
    }

    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }


  public function createtabbutton($config)
  {
    $allow_update = $this->othersClass->checkAccess($config['params']['user'], 4865);
    $tbuttons = ['addrecord', 'saveallentry', 'masterfilelogs'];
    if ($config['params']['companyid'] == 21) { // kinggeorge
      if (!$allow_update) {
        $tbuttons = array_slice($tbuttons, 2, 1);
      }
    }
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }


  public function add($config)
  {
    $id = $config['params']['tableid'];
    $data = [];
    $data['itemid'] = $id;
    $data['line'] = 0;
    $data['uom'] = '';
    $data['printuom'] = '';
    $data['factor'] = 1;
    $data['bgcolor'] = 'bg-blue-2';
    $data['amt'] = 0.00;
    $data['amt2'] = 0.00;
    $data['famt'] = 0.00;
    $data['isinactive'] = 'false';
    $data['isdefault'] = 'false';
    $data['isdefault2'] = 'false';
    $data['issales'] = 'false';
    $data['issalesdef'] = 'false';
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

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    $tableid = $config['params']['tableid'];

    $alloweditfactor = $this->othersClass->checkAccess($config['params']['user'], 3689);

    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }

    if ($row['uom'] == '') {
      return ['status' => false, 'msg' => 'Saving failed; UOM is empty.'];
    }

    if ($row['printuom'] == '') {
      $row['printuom'] = $row['uom'];
    }

    if ($row['line'] == 0) {
      if ($row['isdefault'] == 'true') {
        $defexist = $this->coreFunctions->getfieldvalue($this->table, "itemid", "isdefault =1 and itemid =?", [$row['itemid']]);
        if (strlen($defexist) != 0) {
          return ['status' => false, 'msg' => 'Saving failed; there can be only 1 default UOM.'];
        }
      }

      if ($row['isdefault2'] == 'true') {
        $defexist = $this->coreFunctions->getfieldvalue($this->table, "itemid", "isdefault2 =1 and itemid =?", [$row['itemid']]);
        if (strlen($defexist) != 0) {
          return ['status' => false, 'msg' => 'Saving failed; there can be only 1 default OUT UOM.'];
        }
      }


      $uomexist = $this->coreFunctions->getfieldvalue($this->table, "itemid", "uom =? and itemid =?", [$row['uom'], $row['itemid']]);
      if (strlen($uomexist) != 0) {
        return ['status' => false, 'msg' => 'Saving failed; UOM already exists.'];
      }


      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($row['itemid'], $line);
        $params = $config;
        $params['params']['doc'] = strtoupper("entryuom_tab");
        $this->logger->sbcmasterlog(
          $tableid,
          $params,
          ' CREATE - LINE: ' . $line . ''
            . ', UOM: ' . $row['uom']
            . ', FACTOR: ' . $row['factor']
        );

        $this->coreFunctions->sbcupdate("item", ['dlock' => $this->othersClass->getCurrentTimeStamp()], ['itemid' => $row['itemid']]);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      if ($this->othersClass->checkuomtransaction($row['itemid'], $row['uom'], $row['line'])) {
        if ($alloweditfactor) {
          unset($data['uom']);
          goto updateUOMHere;
        }
        unset($data['uom']);
        unset($data['factor']);
        $this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]);
        $returnrow = $this->loaddataperrecord($row['itemid'], $row['line']);
        return ['status' => true, 'msg' => 'UOM and FACTOR cannot be updated, Already have transaction...', 'row' => $returnrow];
      } else {
        updateUOMHere:
        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];
        if ($row['isdefault'] == 'true') {
          $defexist = $this->coreFunctions->getfieldvalue($this->table, "itemid", "isdefault =1 and itemid =?", [$row['itemid']]);
          if (strlen($defexist) != 0) {
            return ['status' => false, 'msg' => 'Saving failed; there can be only 1 default UOM.'];
          }
        }
        if ($row['isdefault2'] == 'true') {
          $defexist = $this->coreFunctions->getfieldvalue($this->table, "itemid", "isdefault2 =1 and itemid =?", [$row['itemid']]);
          if (strlen($defexist) != 0) {
            return ['status' => false, 'msg' => 'Saving failed; there can be only 1 default OUT UOM'];
          }
        }
        if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
          $this->coreFunctions->sbcupdate("item", ['dlock' => $this->othersClass->getCurrentTimeStamp()], ['itemid' => $row['itemid']]);
          $returnrow = $this->loaddataperrecord($row['itemid'], $row['line']);
          return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
        } else {
          return ['status' => false, 'msg' => 'Saving failed.'];
        }
      }
    }
  } //end function

  public function saveallentry($config)
  {
    $alloweditfactor = $this->othersClass->checkAccess($config['params']['user'], 3689);

    $data = $config['params']['data'];
    $tableid = $config['params']['tableid'];
    $msg = '';

    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }
        if ($data[$key]['uom'] == '') {
          return ['status' => false, 'msg' => 'Saving failed; UOM is empty.'];
        }

        if ($data[$key]['printuom'] == '') {
          $$data[$key]['printuom'] = $data[$key]['uom'];
        }
        
        if ($data[$key]['line'] == 0) {
          if ($data2['isdefault'] == 1) {
            $defexist = $this->coreFunctions->getfieldvalue($this->table, "itemid", "isdefault =1 and itemid =?", [$tableid]);
            if (strlen($defexist) != 0) {
              return ['status' => false, 'msg' => 'Saving failed; there can be only 1 default UOM.'];
            }
          }

          if ($data2['isdefault2'] == 1) {
            $defexist = $this->coreFunctions->getfieldvalue($this->table, "itemid", "isdefault2 =1 and itemid =?", [$tableid]);
            if (strlen($defexist) != 0) {
              return ['status' => false, 'msg' => 'Saving failed; there can be only 1 default OUT UOM.'];
            }
          }

          $uomexist = $this->coreFunctions->getfieldvalue($this->table, "itemid", "uom =? and itemid =?", [$data2['uom'], $tableid]);
          if (strlen($uomexist) != 0) {
            return ['status' => false, 'msg' => 'Saving failed; UOM already exists.'];
          }

          $uomexist = $this->coreFunctions->getfieldvalue($this->table, "itemid", "uom =? and itemid =? and factor", [$data2['uom'], $tableid]);

          $data2['ismirror'] = 0;
          $line = $this->coreFunctions->insertGetId($this->table, $data2);

          $params = $config;
          $params['params']['doc'] = strtoupper("entryuom_tab");
          $this->logger->sbcmasterlog(
            $tableid,
            $params,
            ' CREATE - LINE: ' . $line . ''
              . ', UOM: ' . $data2['uom']
              . ', FACTOR: ' . $data2['factor']
          );
        } else {

          if ($this->othersClass->checkuomtransaction($tableid, $data2['uom'], $data[$key]['line'])) {
            if ($alloweditfactor) {
              unset($data2['uom']);
              goto updateUOMHere;
            }
            $msg .= "UOM and FACTOR cannot be updated, UOM " . $data2['uom'] . " already have transaction...";
            unset($data2['uom']);
            unset($data2['factor']);
            $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
          } else {
            updateUOMHere:
            $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data2['editby'] = $config['params']['user'];
            $data2['ismirror'] = 0;

            if ($data2['isdefault'] == 1) {
              $defexist = $this->coreFunctions->getfieldvalue($this->table, "itemid", "isdefault =1 and itemid =? and line<>?", [$tableid, $data[$key]['line']]);
              if (strlen($defexist) != 0) {
                return ['status' => false, 'msg' => 'Saving failed; there can be only 1 default UOM.'];
              }
            }

            if ($data2['isdefault2'] == 1) {
              $defexist = $this->coreFunctions->getfieldvalue($this->table, "itemid", "isdefault2 =1 and itemid =? and line<>?", [$tableid, $data[$key]['line']]);
              if (strlen($defexist) != 0) {
                return ['status' => false, 'msg' => 'Saving failed; there can be only 1 default OUT UOM.'];
              }
            }

            $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);

            $this->coreFunctions->sbcupdate("item", ['dlock' => $this->othersClass->getCurrentTimeStamp()], ['itemid' => $tableid]);
          }
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    if ($msg == '') {
      $msg = 'All saved successfully.';
    }
    return ['status' => true, 'msg' => $msg, 'data' => $returndata, 'row' => $returndata];
  }

  public function delete($config)
  {
    $companyid = $config['params']['companyid'];
    $row = $config['params']['row'];
    $tableid = $config['params']['tableid'];

    if ($this->othersClass->checkuomtransaction($row['itemid'], $row['uom'], $row['line'])) {
      return ['status' => false, 'msg' => 'Delete failed. Already have transaction.'];
    } else {
      if ($companyid == 14) { //majesty
        return ['status' => false, 'msg' => 'Delete failed. UOM may already synced in POS.'];
      } else {
        $qry = "delete from uom where itemid=? and line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['itemid'], $row['line']]);

        $params = $config;
        $params['params']['doc'] = strtoupper("entryuom_tab");
        $this->logger->sbcmasterlog(
          $tableid,
          $params,
          ' DELETE - LINE: ' . $row['line'] . ''
            . ', UOM: ' . $row['uom']
            . ', FACTOR: ' . $row['factor']
        );
        return ['status' => true, 'msg' => 'Successfully deleted.'];
      }
    }
  }

  private function loaddataperrecord($itemid, $line)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from uom where itemid=? and line=?";
    $data = $this->coreFunctions->opentable($qry, [$itemid, $line]);

    if (!empty($data)) {
      if ($data[0]->isinactive == 1) {
        $data[0]->isinactive = 'true';
      } else {
        $data[0]->isinactive = 'false';
      }

      if ($data[0]->isdefault == 1) {
        $data[0]->isdefault = 'true';
      } else {
        $data[0]->isdefault = 'false';
      }

      if ($data[0]->issales == 1) {
        $data[0]->issales = 'true';
      } else {
        $data[0]->issales = 'false';
      }
    }
    return $data;
  }

  public function loaddata($config)
  {
    $tableid = $config['params']['tableid'];
    $center = $config['params']['center'];
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from uom where itemid=? order by line";
    $data = $this->coreFunctions->opentable($qry, [$tableid]);
    if (!empty($data)) {
      foreach ($data as $d) {
        if ($d->isinactive == 1) {
          $d->isinactive = 'true';
        } else {
          $d->isinactive = 'false';
        }

        if ($d->isdefault == 1) {
          $d->isdefault = 'true';
        } else {
          $d->isdefault = 'false';
        }

        if ($d->isdefault2 == 1) {
          $d->isdefault2 = 'true';
        } else {
          $d->isdefault2 = 'false';
        }

        if ($d->issales == 1) {
          $d->issales = 'true';
        } else {
          $d->issales = 'false';
        }

        if ($d->issalesdef == 1) {
          $d->issalesdef = 'true';
        } else {
          $d->issalesdef = 'false';
        }
      }
    }
    return $data;
  }

  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
      case 'lookuplogs':
        return $this->lookuplogs($config);
        break;
      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup'];
        break;
    }
  }

  public function lookuplogs($config)
  {
    $doc = strtoupper("entryuom_tab");
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'Logs',
      'style' => 'width:1000px;max-width:1000px;'
    );

    // lookup columns
    $cols = array(
      array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $trno = $config['params']['tableid'];

    $qry = "
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from " . $this->tablelogs . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "' and log.trno = '$trno'
    union all
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from  " . $this->tablelogs_del . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "' and log.trno = '$trno' ";

    $qry = $qry . " order by dateid desc";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
  }
} //end class
