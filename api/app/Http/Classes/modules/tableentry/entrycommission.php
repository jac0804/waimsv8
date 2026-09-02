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

class entrycommission
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'SPECIAL COMMISSION RATE BRACKET';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'specialcomm';
  private $othersClass;
  public $style = 'width:100%;';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $fields = ['itemid', 'startamt', 'endamt', 'commrate'];
  public $showclosebtn = false;
  private $enrollmentlookup;
  private $logger;

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
    $attrib = array('load' => 0);
    return $attrib;
  }

  public function createTab($config)
  {
    $columns = ['action', 'startamt', 'endamt', 'commrate'];
    $tab = [$this->gridname => ['gridcolumns' => $columns]];

    foreach ($columns as $key => $value) {
      $$value = $key;
    }

    $stockbuttons = ['save', 'delete'];

    $tab = [$this->gridname => ['gridcolumns' => $columns]];

    $obj = $this->tabClass->createTab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$startamt]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
    $obj[0][$this->gridname]['columns'][$endamt]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
    $obj[0][$this->gridname]['columns'][$commrate]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";

    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['addrecord', 'saveallentry', 'whlog'];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }

  public function loaddata($config)
  {
    $filtersearch = "";
    $searchfield  = $this->fields;

    if (isset($config['params']['filter'])) {
      $search = $config['params']['filter'];
      foreach ($searchfield as $sfield) {
        if ($filtersearch == "") {
          $filtersearch .= " and (" . $sfield . " like '%" . $search . "%'";
        } else {
          $filtersearch .= " or " . $sfield . " like '%" . $search . "%'";
        }
      }
      $filtersearch .= ")";
    }

    $select = $this->selectqry() . ", '' as bgcolor";
    $qry    = "select " . $select . " from " . $this->table .
      " where 1=1 " . $filtersearch . " order by line";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  private function selectqry()
  {
    $qry = "line,itemid,startamt, endamt, commrate";
    return $qry;
  }

  public function add($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['itemid'] = $config['params']['tableid'];
    $data['startamt'] = 0;
    $data['endamt'] = 0;
    $data['commrate'] = 0;
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  public function saveallentry($config)
  {
    $companyid = $config['params']['companyid'];
    $data = $config['params']['data'];

    $dateTables = ['specialcomm'];
    $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);

    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfieldFast($value2, $data[$key][$value2], $lookups);
        }

        if (trim($data[$key]['startamt'] == 0)) {
          return ['status' => false, 'msg' => 'Start amount cannot be zero.'];
        }

        if ($data[$key]['line'] == 0) {
          $data['encodeddate'] = $this->othersClass->getCurrentTimeStamp();
          $data['encodedby'] = $config['params']['user'];
          $line = $this->coreFunctions->insertGetId($this->table, $data2);
          $config['params']['doc'] = 'ENTRYCOMMISSION';
          $this->logger->sbcmasterlog($line, $config, ' CREATE - Start Amount' . $data[$key]['startamt'] . ' ' . ' End Amount: ' . $data[$key]['endamt']
            . ' ' . 'Commision Rate: ' . $data[$key]['commrate']);
        } else {
          $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
          $data2['editby'] = $config['params']['user'];
          $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
        }
      }
    }
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
  }

  public function save($config)
  {
    $companyid = $config['params']['companyid'];
    $row = $config['params']['row'];
    $data = [];

    $dateTables = ['specialcomm'];
    $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);

    foreach ($this->fields as $key2 => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfieldFast($value, $row[$value], $lookups);
    }

    if (trim($row['startamt'] == 0)) {
      return ['status' => false, 'msg' => 'Start amount cannot be zero.'];
    }

    if ($row['line'] == 0) { // insert
      $data['encodeddate'] = $this->othersClass->getCurrentTimeStamp();
      $data['encodedby'] = $config['params']['user'];
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $config['params']['doc'] = 'ENTRYCOMMISSION';
        $this->logger->sbcmasterlog($line, $config, ' CREATE - Start Amount: ' . $data['startamt'] . ' ' . ' End Amount: ' . $data['startamt']
          . ' ' . 'Commision Rate: ' . $data['commrate']);
        $returnrow = $this->loaddataperrecord($line);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else { // update
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      $update = $this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]);
      if ($update) {
        $returnrow = $this->loaddataperrecord($row['line']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Update failed.'];
      }
    }
  } // end function

  private function loaddataperrecord($line)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " where line=?";
    $data = $this->coreFunctions->opentable($qry, [$line]);
    // var_dump($data);
    return $data;
  }

  public function delete($config)
  {
    $row = $config['params']['row'];
    $qry = "delete from " . $this->table . " where line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
    $config['params']['doc'] = 'ENTRYCOMMISSION';
    $this->logger->sbcdelmaster_log($row['line'], $config, 'REMOVE LINE: ' . $row['line'] . ' - Start Amount' . $row['startamt'] . ' - End Amount' . $row['endamt'] . ' - Commission Rate' . $row['commrate']);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
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
    $doc =  'ENTRYCOMMISSION';
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
    where log.doc = '" . $doc . "'
    union all
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from  " . $this->tablelogs_del . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "'";

    $qry = $qry . " order by dateid desc";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
  }
} //end class
