<?php

namespace App\Http\Classes\modules\hrisentry;

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
use App\Http\Classes\lookup\hrislookup;

class entryapptest
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'PRE-EMPLOYMENT TEST';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'apreemploy';
  public $tablelogs = 'masterfile_log';
  private $logger;
  private $othersClass;
  public $style = 'width:1100px;max-width:1100px;';
  private $fields = ['empid', 'preemptest', 'result', 'notes', 'pin', 'emptestid'];
  public $showclosebtn = false;
  private $hrislookup;



  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->hrislookup = new hrislookup;
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
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'pin', 'preemptest', 'result', 'notes']]];

    $stockbuttons = ['save', 'delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][0]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][1]['style'] = "width:250px;whiteSpace: normal;min-width:250px;";
    $obj[0][$this->gridname]['columns'][2]['style'] = "width:250px;whiteSpace: normal;min-width:250px;";
    $obj[0][$this->gridname]['columns'][3]['style'] = "width:250px;whiteSpace: normal;min-width:250px;";
    $obj[0][$this->gridname]['columns'][4]['style'] = "width:250px;whiteSpace: normal;min-width:250px;";

    $obj[0][$this->gridname]['columns'][1]['type'] = "label";
    $obj[0][$this->gridname]['columns'][2]['type'] = "label";

    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = ['addemppretest', 'saveallentry', 'masterfilelogs'];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }


  public function add($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['emptestid'] = 0;
    $data['preemptest'] = '';
    $data['notes'] = '';
    $data['pin'] = '';
    $data['result'] = '';
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  private function selectqry()
  {
    $qry = "line,empid,preemptest,result,notes,pin,emptestid";

    return $qry;
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
        if ($data[$key]['line'] == 0) {
          $line = $this->coreFunctions->insertGetId($this->table, $data2);
        } else {
          $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
          $data2['editby'] = $config['params']['user'];
          $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'Saved all Successfully', 'data' => $returndata];
  } // end function  

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    if ($row['line'] == 0) {
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($row['empid'], $line);

        $config['params']['doc'] = strtoupper('app_emptest');
        $this->logger->sbcmasterlog(
          $row['empid'],
          $config,
          'CREATE PRE EMPLOYMENT TEST - LINE: ' . $line
            . ' - CODE: ' . $row['pin']
            . ' , TEST: ' . $data['preemptest']
            . ' , RESULT: ' . $data['result']
            . ' , NOTES: ' . $data['notes']
        );

        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($row['empid'], $row['line']);

        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  public function delete($config)
  {
    $row = $config['params']['row'];
    $qry = "delete from " . $this->table . " where line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
    $config['params']['doc'] = strtoupper('app_emptest');
    $this->logger->sbcmasterlog(
      $row['empid'],
      $config,
      'DELETE PRE EMPLOYMENT TEST - LINE: ' . $row['line']
        . ' - CODE: ' . $row['pin']
        . ' , TEST: ' . $row['preemptest']
        . ' , RESULT: ' . $row['result']
        . ' , NOTES: ' . $row['notes']
    );
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }


  private function loaddataperrecord($empid, $line)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " where  empid=? and line=?";
    $data = $this->coreFunctions->opentable($qry, [$empid, $line]);
    return $data;
  }

  public function loaddata($config)
  {
    $tableid = $config['params']['tableid'];
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " where  empid=? order by line";
    $data = $this->coreFunctions->opentable($qry, [$tableid]);
    return $data;
  }

  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];

    switch ($lookupclass2) {
      case 'lookuplogs':
        return $this->lookuplogs($config);
        break;
      case 'addemppretest':
        return $this->hrislookup->lookuppreemptest_appledger($config);
        break;
      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
        break;
    }
  }

  public function lookupcallback($config)
  {
    $id = $config['params']['tableid'];
    $row = $config['params']['rows'];
    $data = [];
    foreach ($row  as $key2 => $value) {
      $config['params']['row']['line'] = 0;
      $config['params']['row']['empid'] = $id;
      $config['params']['row']['emptestid'] = $value['line'];
      $config['params']['row']['pin'] = $value['code'];
      $config['params']['row']['preemptest'] = $value['test'];
      $config['params']['row']['result'] = '';
      $config['params']['row']['notes'] = '';
      $config['params']['row']['bgcolor'] = 'bg-blue-2';
      $return = $this->save($config);
      if ($return['status']) {
        array_push($data, $return['row'][0]);
      }
    }
    return ['status' => true, 'msg' => 'Add requirements success...', 'data' => $data];
  } // end function

  public function lookuplogs($config)
  {
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'List of Logs',
      'style' => 'width:1000px;max-width:1000px;'
    );

    $trno = $config['params']['tableid'];

    $cols = [
      ['name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;'],
    ];

    $doc = $config['params']['doc'] = strtoupper('app_emptest');
    $qry = "
      select trno, doc, task, dateid as dateid, user
      from " . $this->tablelogs . "
      where doc = ?
      order by dateid desc
    ";

    $data = $this->coreFunctions->opentable($qry, [$doc, $doc]);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
  }
} //end class
