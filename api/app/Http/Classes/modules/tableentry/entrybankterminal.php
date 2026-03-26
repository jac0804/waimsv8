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
use App\Http\Classes\posClass;

class entrybankterminal
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Bank Terminal';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'branchbank';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['clientid', 'terminalid', 'bank', 'isinactive', 'acnoid'];
  public $showclosebtn = true;
  private $posClass;
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  public $logger;

  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->posClass = new posClass;
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
    $action = 0;
    $acno = 1;
    $terminalid = 2;
    $bank = 3;
    $isinactive = 4;
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'acno', 'terminalid', 'bank', 'isinactive', 'dlock']]];

    $stockbuttons = ['save', 'delete', 'bankcharges'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$acno]['type'] = 'lookup';
    $obj[0][$this->gridname]['columns'][$acno]['action'] = 'lookupsetup';

    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = ['addrecord', 'saveallentry', 'masterfilelogs'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }


  public function add($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['clientid'] = $config['params']['tableid'];
    $data['acno'] = '';
    $data['acnoid'] = 0;
    $data['terminalid'] = '';
    $data['bank'] = '';
    $data['dlock'] = '';
    $data['isinactive'] = 'false';
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    $msg = 'All saved successfully.';

    $tableid = $config['params']['tableid'];
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }
        $data2['dlock'] = $this->othersClass->getCurrentTimeStamp();
        if ($data[$key]['line'] == 0) {
          $line = $this->coreFunctions->insertGetId($this->table, $data2);

          $params = $config;
          $params['params']['doc'] = strtoupper("entrybankterminal_tab");
          $this->logger->sbcmasterlog(
            $tableid,
            $params,
            ' CREATE - LINE: ' . $line . ''
              . ', ACCOUNT #: ' . $data[$key]['acno']
              . ', TERMINAL ID: ' . $data[$key]['terminalid']
              . ', BANK: ' . $data[$key]['bank']
              . ', ACTIVE: ' . $data[$key]['isinactive']
          );
        } else {
          $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
          $data2['editby'] = $config['params']['user'];
          $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' =>  $msg, 'data' => $returndata];
  } // end function 

  public function save($config)
  {
    $data = [];
    $msg = 'Successfully saved.';

    $tableid = $config['params']['tableid'];
    $row = $config['params']['row'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    $data['dlock'] = $this->othersClass->getCurrentTimeStamp();
    if ($row['line'] == 0) {
      $line = $this->coreFunctions->insertGetId($this->table, $data);

      $params = $config;
      $params['params']['doc'] = strtoupper("entrybankterminal_tab");
      $this->logger->sbcmasterlog(
        $tableid,
        $params,
        ' CREATE - LINE: ' . $line . ''
          . ', ACCOUNT #: ' . $row['acno']
          . ', TERMINAL ID: ' . $row['terminalid']
          . ', BANK: ' . $row['bank']
          . ', ACTIVE: ' . $row['isinactive']
      );
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($config, $line);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($config, $row['line']);
        return ['status' => true, 'msg' => $msg, 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  public function delete($config)
  {
    $tableid = $config['params']['tableid'];
    $row = $config['params']['row'];
    $data = $this->loaddataperrecord($config, $row['line']);

    $qry = "delete from " . $this->table . " where line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);

    $params = $config;
    $params['params']['doc'] = strtoupper("entrybankterminal_tab");
    $this->logger->sbcmasterlog(
      $tableid,
      $params,
      ' DELETE - LINE: ' . $row['line'] . ''
        . ', ACCOUNT #: ' . $row['acno']
        . ', TERMINAL ID: ' . $row['terminalid']
        . ', BANK: ' . $row['bank']
        . ', ACTIVE: ' . $row['isinactive']
    );
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }


  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
      case 'lookuplogs':
        return $this->lookuplogs($config);
        break;
      default:
        return $this->lookupacno($config);
        break;
    }
  }

  public function lookupacno($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Accounts',
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'plotgrid',
      'plotting' => array('acno' => 'acno', 'acnoid' => 'acnoid')
    );

    $cols = [
      ['name' => 'acno', 'label' => 'Account #', 'align' => 'left', 'field' => 'acno', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'acnoname', 'label' => 'Account Name', 'align' => 'left', 'field' => 'acnoname', 'sortable' => true, 'style' => 'font-size:16px;'],
    ];

    $qry = "select acnoid, acno, acnoname from coa";
    $data = $this->coreFunctions->opentable($qry);

    $index = $config['params']['index'];
    $table = isset($config['params']['table']) ? $config['params']['table'] : "";

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index, 'rowindex' => $index, 'table' => $table];
  }

  public function lookuplogs($config)
  {
    $doc = strtoupper("entrybankterminal_tab");
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'Bank Terminal Logs',
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

  private function loaddataperrecord($config, $line)
  {
    $tableid = $config['params']['tableid'];
    $qry = "select line, clientid, terminalid, bank, c.dlock, coa.acno,c.acnoid, isok, charges,
    (case c.isinactive when 1 then 'true' else 'false' end) as isinactive,'' as bgcolor 
    from " . $this->table . " as c left join coa on coa.acnoid = c.acnoid where clientid =? and line=? order by line";
    $data = $this->coreFunctions->opentable($qry, [$tableid, $line]);
    return $data;
  }

  public function loaddata($config)
  {
    $tableid = $config['params']['tableid'];
    $qry = "select line, clientid, terminalid, bank, c.dlock,coa.acno ,c.acnoid, isok, charges,
    (case c.isinactive when 1 then 'true' else 'false' end) as isinactive,'' as bgcolor 
    from " . $this->table . " as c left join coa on coa.acnoid = c.acnoid where clientid =? order by line";
    $data = $this->coreFunctions->opentable($qry, [$tableid]);
    return $data;
  }
} //end class
