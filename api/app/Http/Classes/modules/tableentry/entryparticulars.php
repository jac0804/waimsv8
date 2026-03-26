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
use App\Http\Classes\SBCPDF;

class entryparticulars
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'PARTICULARS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'particulars';
  private $htable = 'hparticulars';
  private $othersClass;
  public $style = 'width:100%;';
  public $tablelogs = 'table_log';
  public $tablelogs_del = 'del_table_log';
  private $fields = ['trno', 'line', 'rem', 'amount', 'quantity'];
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
      'load' => 0
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $tab = [
      $this->gridname => [
        'gridcolumns' => ['action', 'rem', 'quantity', 'amount', 'itemname'],
        'computefield' => ['dqty' => '', 'damt' => '', 'total' => 'amount'],
      ]
    ];

    $stockbuttons = ['save', 'delete'];

    $trno = $config['params']['tableid'];
    $isposted = $this->othersClass->isposted2($trno, "cntnum");

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['showtotal'] = true;
    $obj[0][$this->gridname]['totalfield'] = 'amount';
    $obj[0][$this->gridname]['columns'][1]['label'] = "Particulars";
    $obj[0][$this->gridname]['columns'][1]['type'] = "textarea";
    $obj[0][$this->gridname]['columns'][1]['style'] = "width:400px;whiteSpace: normal;min-width:200px;max-width:450px";
    $obj[0][$this->gridname]['columns'][2]['style'] = "width:200px;whiteSpace: normal;min-width:200px;max-width:200px;";
    $obj[0][$this->gridname]['columns'][3]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";

    if ($isposted) {
      $obj[0][$this->gridname]['columns'][0]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][1]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][2]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][3]['readonly'] = true;
    }


    $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = ['addrecord', 'saveallentry'];
    $trno = $config['params']['tableid'];
    $isposted = $this->othersClass->isposted2($trno, "cntnum");
    if ($isposted) $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function add($config)
  {
    $trno = $config['params']['tableid'];

    $data['line'] = 0;
    $data['trno'] = $trno;
    $data['quantity'] = '';
    $data['rem'] = '';
    $data['amount'] = 0;
    $data['createby'] = $config['params']['user'];
    $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  private function selectqry()
  {
    $qry = "trno,line,quantity,rem,format(amount,2) as amount ";
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
              'ADD PARTICULARS',
              ' PARTICULAR: ' . $data[$key]['rem'] . ', QUANTITY: ' . $data[$key]['quantity'] . ', AMOUNT: ' . $data[$key]['amount']
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
        $returnrow = $this->loaddataperrecord($data['line'], $config);
        $this->logger->sbcwritelog(
          $data['trno'],
          $config,
          'ADD PARTICULARS',
          ' PARTICULAR: ' . $data['rem'] . ', QUANTITY: ' . $data['quantity'] . ', AMOUNT: ' . $data['amount']
        );
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      $returnrow = $this->loaddataperrecord($row['line'], $config);
      $current_timestamp = $this->othersClass->getCurrentTimeStamp();
      $data['editdate'] = $current_timestamp;
      $data['editby'] = $config['params']['user'];
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line'], 'trno' => $row['trno']]) == 1) {
        $returnrow = $this->loaddataperrecord($row['line'], $config);
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
      ' PARTICULAR: ' . $row['rem'] . ', QUANTITY: ' . $row['quantity'] . ', AMOUNT: ' . $row['amount']
    );
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }


  private function loaddataperrecord($line, $config)
  {
    $userid = $config['params']['adminid'];
    $trno = $config['params']['tableid'];

    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . "
    from " . $this->table . " 
    where trno = ? and line =? union all
    select " . $select . "
    from " . $this->htable . " 
    where trno = ? and line = ? ";
    $data = $this->coreFunctions->opentable($qry, [$trno, $line, $trno, $line]);
    return $data;
  }

  public function loaddata($config)
  {
    $userid = $config['params']['adminid'];
    $trno = $config['params']['tableid'];

    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . "
    from " . $this->table . "  
    where trno = ? union all
    select " . $select . "
    from " . $this->htable . "  
    where trno = ?
    order by line";
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

      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
        break;
    }
  }

  public function lookuplogs($config)
  {
    $doc = strtoupper($config['params']['lookupclass']);
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
    where log.doc = '" . $doc . "' and log.trno =" . $trno . "
    union all
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from  " . $this->tablelogs_del . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "' and log.trno =" . $trno;

    $qry = $qry . " order by dateid desc";
    $this->coreFunctions->LogConsole($qry);
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
  }

  private function getclientname($clientid)
  {
    $qry = "select clientname as value from client where clientid = ? limit 1";
    return $this->coreFunctions->datareader($qry, [$clientid]);
  }
} //end class
