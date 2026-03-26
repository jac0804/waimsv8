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
use App\Http\Classes\builder\lookupclass;

class entrybankcharges
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'BANK CHARGES';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'bankcharges';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $othersClass;
  private $logger;
  public $style = 'width:100%;';
  private $fields = ['rate', 'type', 'inactive', 'ewt'];
  public $showclosebtn = true;
  private $lookupclass;


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
    $type = 1;
    $rate = 2;
    $ewt = 3;
    $inactive = 4;
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'type', 'rate', 'ewt', 'inactive']]];

    $stockbuttons = ['save', 'delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][$rate]['style'] = "text-align:left;width:100px;whiteSpace: normal;min-width:100px;";

    $obj[0][$this->gridname]['columns'][$rate]['label'] = "Rate";
    $obj[0][$this->gridname]['columns'][$rate]['align'] = "text-left";

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
    $id = $config['params']['sourcerow']['clientid'];
    $terminalid = $config['params']['sourcerow']['terminalid'];
    $acno = $config['params']['sourcerow']['acno'];
    $acnoid = $this->coreFunctions->datareader("select acnoid as value from coa where acno = ?", [$acno]);

    $data = [];
    $data['line'] = 0;
    $data['clientid'] = $id;
    $data['terminalid'] = $terminalid;
    $data['acnoid'] = $acnoid;
    $data['type'] = '';
    $data['rate'] = '';
    $data['ewt'] = '';
    $data['inactive'] = 'false';
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    foreach ($this->fields as $key => $value) {
      switch ($value) {
        case 'rate':
          $data[$value] = $row[$value];
          break;
        default:
          $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
          break;
      }
    }

    $data['clientid'] = $row['clientid'];
    $data['terminalid'] = $row['terminalid'];
    $data['acnoid'] = $row['acnoid'];

    $data['dlock'] = $this->othersClass->getCurrentTimeStamp();

    if ($row['line'] == 0) {
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $params = $config;
        $params['params']['doc'] = strtoupper("entrybankcharges");
        $this->logger->sbcmasterlog(
          $line,
          $params,
          ' CREATE - '
            . ' TYPE: ' . $row['type']
            . ' RATE: ' . $row['rate']
            . ' EWT: ' . $row['ewt']
            . ' INACTIVE: ' . $row['inactive']
        );
        $returnrow = $this->loaddataperrecord($line);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
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
  } //end function

  public function delete($config)
  {
    $row = $config['params']['row'];
    $qry = "delete from " . $this->table . " where line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
    $this->logger->sbcmasterlog(
      $row['line'],
      $config,
      ' REMOVE - '
        . ' TYPE: ' . $row['type']
        . ' RATE: ' . $row['rate']
        . ' EWT: ' . $row['ewt']
        . ' INACTIVE: ' . $row['inactive']
    );
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }

  private function loaddataperrecord($line)
  {
    $select = "line, clientid, acnoid, terminalid, rate, type, dlock, ewt,
    case when inactive = 1 then 'true' else 'false' end as inactive";
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . "
    from " . $this->table . "
    where line = '" . $line . "'";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  public function loaddata($config)
  {
    $center = $config['params']['center'];
    $line = isset($config['params']['row']['line']) ? $config['params']['row']['line'] : $config['params']['sourcerow']['line'];
    $terminalid = isset($config['params']['row']['terminalid']) ? $config['params']['row']['terminalid'] : $config['params']['sourcerow']['terminalid'];
    // $clientid = isset($config['params']['row']['clientid']) ? $config['params']['row']['clientid'] : $config['params']['sourcerow']['clientid'];
    $acno = isset($config['params']['row']['acno']) ? $config['params']['row']['acno'] : $config['params']['sourcerow']['acno'];
    $acnoid = $this->coreFunctions->datareader("select acnoid as value from coa where acno = ?", [$acno]);

    $select = "line, clientid, acnoid, terminalid, rate, type, dlock, ewt,
    case when inactive = 1 then 'true' else 'false' end as inactive";
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . "
    from " . $this->table . "
    where terminalid = '" . $terminalid . "'";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  public function saveallentry($config)
  {
    $params = $config;
    $data = $config['params']['data'];
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          switch ($value2) {
            case 'rate':
              $data2[$value2] = $data[$key][$value2];
              break;
            default:
              $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
              break;
          }
        }

        $data2['clientid'] = $data[$key]['clientid'];
        $data2['terminalid'] = $data[$key]['terminalid'];
        $data2['acnoid'] = $data[$key]['acnoid'];

        $data2['dlock'] = $this->othersClass->getCurrentTimeStamp();

        if ($data[$key]['line'] == 0) {
          $data2['createdate'] = $this->othersClass->getCurrentTimeStamp();
          $data2['createby'] = $config['params']['user'];
          $line = $this->coreFunctions->insertGetId($this->table, $data2);

          $params['params']['doc'] = strtoupper("entrybankcharges");
          $this->logger->sbcmasterlog(
            $line,
            $params,
            ' CREATE - '
              . ' TYPE: ' . $data[$key]['type']
              . ' RATE: ' . $data[$key]['rate']
              . ' EWT: ' . $data[$key]['ewt']
              . ' INACTIVE: ' . $data[$key]['inactive']
          );
        } else {
          $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
          $data2['editby'] = $config['params']['user'];
          $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
  } // end function

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
    $doc = strtoupper("entrybankcharges");
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'Item Sub Category Master Logs',
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
