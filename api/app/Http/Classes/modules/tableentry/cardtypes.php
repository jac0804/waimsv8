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
use App\Http\Classes\tableentryClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

class cardtypes
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'CARD TYPES';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'cardtype';
  public $tablelogs = 'masterfile_log';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['cardtype', 'isinactive', 'dlock'];
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
      'load' => 2635
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $cardtype = 0;
    $dlock = 1;
    $isinactive = 2;

    $columns = ['cardtype', 'dlock', 'isinactive'];
    $tab = [$this->gridname => ['gridcolumns' => $columns]];

    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$cardtype]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$dlock]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][$isinactive]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = ['syncall', 'addrecord', 'saveallentry', 'whlog'];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }

  public function add($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['cardtype'] = '';
    $data['dlock'] = '';
    $data['isinactive'] = 'false';
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  private function selectqry()
  {
    $qry = "line";
    foreach ($this->fields as $key => $value) {
      if ($value == 'isinactive') {
        $qry = $qry . ",(case when isinactive=1 then 'true' else 'false' end) as isinactive";
      } else {
        $qry = $qry . ',' . $value;
      }
    }
    return $qry;
  }

  public function saveallentry($config)
  {
    $data = $config['params']['data'];

    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $data[$key][$value2];
        }

        if ($data2['isinactive'] == 'false') {
          $data2['isinactive'] = 0;
        } else {
          $data2['isinactive'] = 1;
        }
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $data2['dlock'] = $current_timestamp;
        if ($data[$key]['line'] == 0) {
          $line = $this->coreFunctions->insertGetId($this->table, $data2);
          $this->logger->sbcmasterlog($line, $config, ' CREATE - ' . $data[$key]['cardtype']);
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

  public function loaddata($config)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . ", cardtype "
      . " from " . $this->table . " 
            order by line";
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
    $doc = $config['params']['doc'];
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'Card Type Logs',
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
    where log.doc = '" . $doc . "'";

    $qry = $qry . " order by dateid desc";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
  }

  public function tableentrystatus($config)
  {
    switch ($config['params']['action2']) {
      case 'syncallentry':
        $this->coreFunctions->execqry("update cardtype set dlock='" . $this->othersClass->getCurrentTimeStamp() . "'");
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'Card type dlock updated.', 'data' => $returndata];
        break;
      default:
        if (isset($config['params']['data'])) {
          return ['status' => true, 'msg' => 'No function yet', 'data' => $config['params']['data']];
        } else {
          return ['status' => true, 'msg' => 'No function yet', 'data' => []];
        }
        break;
    }
  } //end function
} //end class
