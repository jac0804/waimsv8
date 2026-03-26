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

class entryescalation_tab
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'ESCALATION';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $logger;
  private $table = 'escalation';
  private $othersClass;
  public $style = 'width:100%;';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $fields = ['line', 'dateid', 'rate', 'clientid'];
  public $showclosebtn = false;
  private $reporter;


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
    $action = 0;
    $dateid = 1;
    $rate = 2;
    $remarks = 3;

    $tab = [$this->gridname => ['gridcolumns' => ['action', 'dateid', 'rate', 'remarks', 'isapplied']]];

    $stockbuttons = ['save', 'delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:100px;whiteSpace: normal;min-width:120px;";
    $obj[0][$this->gridname]['columns'][$dateid]['style'] = "width:100px;whiteSpace: normal;min-width:120px;";
    $obj[0][$this->gridname]['columns'][$rate]['style'] = "width:100px;whiteSpace: normal;min-width:120px;";
    $obj[0][$this->gridname]['columns'][$remarks]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";

    $obj[0][$this->gridname]['columns'][$rate]['label'] = "Rate";
    $obj[0][$this->gridname]['columns'][$remarks]['type'] = "label";

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
    $data['dateid'] = date('Y-m-d');
    $data['rate'] = '0';
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    $tableid = $config['params']['tableid'];
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }

        if ($data[$key]['line'] == 0) {
          $status = $this->coreFunctions->insertGetId($this->table, $data2);

          $params = $config;
          $params['params']['doc'] = "ENTRYESCALATION_TAB";
          $this->logger->sbcmasterlog(
            $tableid,
            $params,
            ' CREATE - LINE: ' . $status . ''
              . ', DATE: ' . $data[$key]['dateid']
              . ', RATE: ' . $data[$key]['rate']
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

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    $tableid = $config['params']['tableid'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }

    if ($row['line'] == 0) {
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($config, $line);

        $params = $config;
        $params['params']['doc'] = "ENTRYESCALATION_TAB";
        $this->logger->sbcmasterlog(
          $tableid,
          $params,
          ' CREATE - LINE: ' . $line . ''
            . ', DATE: ' . $row['dateid']
            . ', RATE: ' . $row['rate']
        );
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($config, $row['line']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
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
    $params['params']['doc'] = "ENTRYESCALATION_TAB";
    $this->logger->sbcmasterlog(
      $tableid,
      $params,
      ' DELETE - LINE: ' . $row['line'] . ''
        . ', DATE: ' . $row['dateid']
        . ', RATE: ' . $row['rate']
    );
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }


  private function loaddataperrecord($config, $line)
  {

    $tableid = $config['params']['tableid'];

    $qry = "
    select '' as bgcolor, escal.line, left(escal.dateid, 10) as dateid, escal.remarks, 
      escal.isapplied, escal.clientid, 
      escal.rate, escal.dateapplied, escal.oldrate
    from " . $this->table . " as escal
    where escal.clientid = '" . $tableid . "' and escal.line = '" . $line . "'
    order by escal.line";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  public function loaddata($config)
  {
    $tableid = $config['params']['tableid'];

    $qry = "
    select '' as bgcolor, escal.line, left(escal.dateid, 10) as dateid, escal.remarks, 
      escal.isapplied, escal.clientid, 
      escal.rate, escal.dateapplied, escal.oldrate
    from " . $this->table . " as escal
    where escal.clientid = '" . $tableid . "'
    order by escal.line";
    $data = $this->coreFunctions->opentable($qry);
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
    $doc = "ENTRYESCALATION_TAB";
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

  // -> Print Function
  public function reportsetup($config)
  {
    return [];
  }

  public function createreportfilter()
  {
    return [];
  }

  public function reportparamsdata($config)
  {
    return [];
  }

  public function reportdata($config)
  {
    return [];
  }
} //end class
