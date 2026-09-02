<?php

namespace App\Http\Classes\modules\payrollentry;

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

class entryempcitation
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'CITATION';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'traininghead';
  public $tablelogs = 'masterfile_log';
  private $logger;
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['empid', 'title', 'dateid','iscitation'];
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
      'load' => 5950
    );
    return $attrib;
  }


  public function createTab($config)
  {
    $doc = $config['params']['doc'];
    $iswindows = $this->companysetup->getiswindowspayroll($config['params']);
    $stockbuttons = [];
    $tab = [
      $this->gridname => [
        'gridcolumns' => ['action', 'title', 'dateid']
      ]
    ];

    // if ($doc == 'MYINFO') {
    //   unset($tab[$this->gridname]['gridcolumns'][0]); // action
    //   $tab[$this->gridname]['gridcolumns'] = array_values($tab[$this->gridname]['gridcolumns']);
    // }

    // if ($iswindows) {
    //   $stockbuttons = [];
    // } else {
    //   if ($doc != 'MYINFO') {
    $stockbuttons = ['save', 'delete'];
    //   }
    // }


    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    // if ($doc == 'MYINFO') {
    //   $obj[0][$this->gridname]['columns'][0]['readonly'] = true;
    //   $obj[0][$this->gridname]['columns'][1]['readonly'] = true;
    //   $obj[0][$this->gridname]['columns'][2]['readonly'] = true;
    //   $obj[0][$this->gridname]['columns'][3]['readonly'] = true;
    //   $obj[0][$this->gridname]['columns'][0]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    // } else {
    $obj[0][$this->gridname]['columns'][0]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";
    $obj[0][$this->gridname]['columns'][1]['label'] = "Citations/Commendations";
    $obj[0][$this->gridname]['columns'][1]['readonly'] = False;
    // }
    return $obj;
  }


  public function createtabbutton($config)
  {
    // $tbuttons = ['addrecord','saveallentry', 'masterfilelogs'];
    $iswindows = $this->companysetup->getiswindowspayroll($config['params']);
    $doc = $config['params']['doc'];
    $tbuttons = [];

    // if ($iswindows) {
    //   $tbuttons = [];
    // } else {
    //   if ($doc != 'MYINFO') {
    $tbuttons = ['addrecord', 'saveallentry'];
    //   }
    // }

    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }

  public function add($config)
  {
    $id = $config['params']['tableid'];
    $data = [];
    $data['empid'] = $id;
    $data['trno'] = 0;
    $data['title'] = '';
    $data['dateid'] = '';
    $data['iscitation'] = 1;
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  private function selectqry()
  {
    $qry = "trno, empid, title, date(dateid) as dateid, iscitation";

    #'empid','contractn','descr','datefrom','dateto'
    // foreach ($this->fields as $key => $value) {
    //   $qry = $qry.','.$value;
    // }
    return $qry;
  }



  //createby/date not yet done
  public function save($config)
  {
    $companyid = $config['params']['companyid'];
    $dateTables = [$this->table];
    $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);

    $data = [];
    $row = $config['params']['row'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfieldFast($value, $row[$value],$lookups);
    }
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
    if ($row['trno'] == 0) {
      $trno = $this->coreFunctions->insertGetId($this->table, $data);
      if ($trno != 0) {
        $returnrow = $this->loaddataperrecord($row['empid'], $trno);
// 'title', 'dateid','iscitation'
        $config['params']['doc'] = strtoupper('CITATION');
        $this->logger->sbcwritelog(
          $row['empid'],
          $config,
          'CONTRACT',
          'CREATE - TRNO: ' . $trno
            . ' - TITLE#: ' . $data['title']
            . ' , DATE: ' . $data['dateid']
        );

        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['trno' => $row['trno']]) == 1) {
        $returnrow = $this->loaddataperrecord($row['empid'], $row['trno']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  public function saveallentry($config)
  {
    $companyid = $config['params']['companyid'];
    $dateTables = [$this->table];
    $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);

    $empid = $config['params']['tableid'];
    $data = $config['params']['data'];
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfieldFast($value2, $data[$key][$value2],$lookups);
        }
        
        $data2['createdate'] = $this->othersClass->getCurrentTimeStamp();
        $data2['createby'] = $config['params']['user'];
        if ($data[$key]['trno'] == 0) {
          $trno = $this->coreFunctions->insertGetId($this->table, $data2);

          $config['params']['doc'] = strtoupper('emp_contract');
          $this->logger->sbcwritelog(
            $empid,
            $config,
            'CONTRACT',
            'CREATE - TRNO: ' . $trno
            . ' - TITLE#: ' . $data2['title']
            . ' , DATE: ' . $data2['dateid']
          );
        } else {
          $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
          $data2['editby'] = $config['params']['user'];
          $this->coreFunctions->sbcupdate($this->table, $data2, ['trno' => $data[$key]['trno']]);
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'Saved all Successfully', 'data' => $returndata];
  } // end function

  public function delete($config)
  {
    $row = $config['params']['row'];
    $qry = "delete from " . $this->table . " where empid=? and trno=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['empid'], $row['trno']]);

    $config['params']['doc'] = strtoupper('emp_contract');
    $this->logger->sbcwritelog(
      $row['empid'],
      $config,
      'CONTRACT',
      'DELETE - TRNO: ' . $row['trno']
            . ' - TITLE#: ' . $row['title']
            . ' , DATE: ' . $row['dateid']
    );

    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }


  private function loaddataperrecord($empid, $trno)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " where empid=? and trno=?";
    $data = $this->coreFunctions->opentable($qry, [$empid, $trno]);
    return $data;
  }

  public function loaddata($config)
  {
    $tableid = $config['params']['tableid'];
    $center = $config['params']['center'];
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " where empid=? and iscitation=1";
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
      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
        break;
    }
  }

  public function lookuplogs($config)
  {
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'List of Logs',
      'style' => 'width:1000px;max-width:1000px;'
    );

    $trno = $config['params']['tableid'];
    // $doc = $config['params']['doc'];

    $cols = [
      ['name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'editby', 'label' => 'Edited By', 'align' => 'left', 'field' => 'editby', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'editdate', 'label' => 'Edited Date', 'align' => 'left', 'field' => 'editdate', 'sortable' => true, 'style' => 'font-size:16px;'],
    ];

    $doc = strtoupper('emp_contract');
    $qry = "
      select trno, doc, task, left(dateid,10) as dateid, user, editby, editdate
      from " . $this->tablelogs . "
      where doc = ?
      order by dateid desc
    ";

    $data = $this->coreFunctions->opentable($qry, [$doc, $doc]);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
  }
} //end class
