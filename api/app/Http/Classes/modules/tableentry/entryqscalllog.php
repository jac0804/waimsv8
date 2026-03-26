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

class entryqscalllog
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'CALL LOG ENTRY';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'qscalllogs';
  private $htable = 'hqscalllogs';
  private $othersClass;
  public $style = 'width:100%;';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $fields = ['line', 'dateid', 'starttime', 'endtime', 'status', 'rem', 'calltype', 'contact', 'probability'];
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
      'load' => 2453,
      'save' => 2456,
      'delete' => 2458,
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $trno = $config['params']['tableid'];
    $isposted = $this->othersClass->isposted2($trno, "transnum");

    $action = 0;
    $contact = 1;
    $dateid = 2;
    $starttime = 3;
    $endtime = 4;
    $calltype = 5;
    $probability = 6;
    $status = 7;
    $rem = 8;

    $tab = [
      $this->gridname => [
        'gridcolumns' => ['action', 'contact', 'dateid', 'starttime', 'endtime', 'calltype', 'probability', 'status', 'rem']
      ]
    ];
    $stockbuttons = ['save', 'delete'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$contact]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$dateid]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$starttime]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$endtime]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$calltype]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$probability]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$rem]['style'] = "width:400px;whiteSpace: normal;min-width:400px;";

    $obj[0][$this->gridname]['columns'][$dateid]['type'] = "input";
    $obj[0][$this->gridname]['columns'][$dateid]['readonly'] = true;

    $obj[0][$this->gridname]['columns'][$calltype]['readonly'] = false;
    $obj[0][$this->gridname]['columns'][$probability]['readonly'] = false;
    $obj[0][$this->gridname]['columns'][$calltype]['required'] = false;
    $obj[0][$this->gridname]['columns'][$probability]['required'] = false;

    $obj[0][$this->gridname]['columns'][$status]['type'] = "lookup";
    $obj[0][$this->gridname]['columns'][$status]['label'] = "Inquiry Status";
    $obj[0][$this->gridname]['columns'][$status]['lookupclass'] = "inquirystat";
    $obj[0][$this->gridname]['columns'][$status]['action'] = "lookupsetup";


    if ($isposted) {
      $obj[0][$this->gridname]['columns'][$action]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$calltype]['type'] = 'input';
      $obj[0][$this->gridname]['columns'][$calltype]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$calltype]['align'] = 'right';
      $obj[0][$this->gridname]['columns'][$probability]['type'] = 'input';
      $obj[0][$this->gridname]['columns'][$probability]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$status]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$status]['type'] = 'input';
      $obj[0][$this->gridname]['columns'][$probability]['align'] = 'right';
      $obj[0][$this->gridname]['columns'][$rem]['readonly'] = true;
    }

    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = ['defaults', 'saveallentry'];

    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['label'] = "ADD NEW";
    $obj[1]['label'] = "END CALL";
    $obj[1]['icon'] = "phone";
    return $obj;
  }

  public function adddefaults($config)
  {
    $this->othersClass->setDefaultTimeZone();
    $trno = $config['params']['tableid'];
    $isposted = $this->othersClass->isposted2($trno, "transnum");
    if ($isposted) {
      $call = app('App\Http\Classes\modules\tableentry\entryqscalllog')->loaddata($config);
      return ['status' => false, 'msg' => 'Already Posted', 'data' => $call];
    }
    $data = [];
    $data['trno'] = $trno;
    $data['dateid'] = date("Y/m/d");
    $data['starttime'] = date("H:i:s");
    $data['endtime'] = '';
    $data['calltype'] = '';
    $data['probability'] = '';
    $data['rem'] = '';

    if ($this->checkcalllogs($trno) == true) {
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      $call = app('App\Http\Classes\modules\tableentry\entryqscalllog')->loaddata($config);
      return ['status' => true, 'msg' => 'Successfully saved.', 'data' => $call];
    } else {
      $call = app('App\Http\Classes\modules\tableentry\entryqscalllog')->loaddata($config);
      return ['status' => false, 'msg' => 'Please endcall before proceed to new transaction', 'data' => $call];
    }
  }


  private function selectqry()
  {
    $qry = "trno, line,left(dateid, 10) as dateid,starttime,endtime,rem,calltype, contact, probability,status";
    return $qry;
  }

  public function saveallentry($config)
  {
    $trno = $config['params']['tableid'];
    $isposted = $this->othersClass->isposted2($trno, "transnum");
    if ($isposted) {
      $call = app('App\Http\Classes\modules\tableentry\entryqscalllog')->loaddata($config);
      return ['status' => false, 'msg' => 'Already Posted', 'data' => $call];
    }
    $this->othersClass->setDefaultTimeZone();
    $data = $config['params']['data'];
    foreach ($data as $key => $value) {
      $data2 = [];
      foreach ($this->fields as $key2 => $value2) {
        $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2], $config['params']['doc'], $config['params']['companyid']);
      }
      if ($data2['endtime'] == '') {
        $data2['endtime'] = date("H:i:s");
      }

      if ($data2['calltype'] == '') {
        $returndata = $this->loaddata($config);
        return ['status' => false, 'msg' => 'Call type is required', 'data' => $returndata];
      }
      $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data2['editby'] = $config['params']['user'];
      $this->coreFunctions->sbcupdate($this->table, $data2, ['trno' => $trno, 'line' => $data[$key]['line']]);
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'Call ended successfully.', 'data' => $returndata];
  } // end function

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];

    if ($this->checkcalllogsperrow($row['trno'], $row['line']) == true) {
      $data = $this->loaddataperrecord($row['trno'], $row['line']);
      return ['status' => false, 'msg' => " this call entry is already has ended, you cant't make any changes."];
    }

    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value], $config['params']['doc'], $config['params']['companyid']);
    }
    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];

    if ($this->coreFunctions->sbcupdate($this->table, $data, ['trno' => $row['trno'], 'line' => $row['line']]) == 1) {
      $returnrow = $this->loaddataperrecord($row['trno'], $row['line']);
      return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
    } else {
      return ['status' => false, 'msg' => 'Saving failed.'];
    }
  } //end function

  public function delete($config)
  {
    $row = $config['params']['row'];

    if ($this->checkcalllogsperrow($row['trno'], $row['line']) == true) {
      $data = $this->loaddataperrecord($row['trno'], $row['line']);
      return ['status' => false, 'msg' => " this call entry is already have endtime you cant't delete this line "];
    }

    $qry = "delete from " . $this->table . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['trno'], $row['line']]);
    $this->logger->sbcdelmaster_log($row['trno'], $config, 'REMOVE - Line : ' . $row['line'] . 'CONTACT:' . $row['contact'], 0, 1);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }


  private function loaddataperrecord($trno, $line)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " where trno=? and line=?";
    $data = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $data;
  }

  public function loaddata($config)
  {

    $trno = $config['params']['tableid'];
    $tbl = $this->table;
    $isposted = $this->othersClass->isposted2($trno, "transnum");
    if ($isposted) {
      $tbl = $this->htable;
    }

    $select = $this->selectqry();
    $tableid = $config['params']['tableid'];
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $tbl . " where trno =?";
    $data = $this->coreFunctions->opentable($qry, [$tableid]);
    return $data;
  }

  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
      case 'calltype':
        return $this->lookupcalltype($config);
        break;

      case 'probability':
        return $this->lookupprobability($config);
        break;

      case 'inquirystat':
        return $this->lookupstatus($config);
        break;

      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under documents'];
        break;
    }
  }
  public function lookupstatus($config)
  {
    $rowindex = $config['params']['index'];
    $lookupclass2 = $config['params']['lookupclass2'];
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Status',
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'plotgrid',
      'plotting' => array(
        'status' => 'status',
      )
    );

    $cols = array(
      array('name' => 'status', 'label' => 'Status', 'align' => 'left', 'field' => 'status', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $qry = "select 'Active' as status
    union all
    select 'Inactive'";

    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $rowindex];
  }

  public function lookupcalltype($config)
  {
    $rowindex = $config['params']['index'];
    $lookupclass2 = $config['params']['lookupclass2'];
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Call Type',
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'plotgrid',
      'plotting' => array(
        'calltype' => 'calltype',
      )
    );

    $cols = array(
      array('name' => 'calltype', 'label' => 'Call Type', 'align' => 'left', 'field' => 'calltype', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $qry = "select 'Follow Up' as calltype
    union all
    select 'Prospecting'
    union all 
    select 'Others'";

    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $rowindex];
  }

  public function lookupprobability($config)
  {
    $rowindex = $config['params']['index'];
    $lookupclass2 = $config['params']['lookupclass2'];
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of probability',
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'plotgrid',
      'plotting' => array(
        'probability' => 'probability',
      )
    );

    $cols = array(
      array('name' => 'probability', 'label' => 'Probability', 'align' => 'left', 'field' => 'probability', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $qry = "
      select '0%' as probability
      union all 
      select '25%' as probability
      union all 
      select '50%' as probability
      union all 
      select '75%' as probability
      union all 
      select '90%' as probability
      union all 
      select '100%' as probability";

    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $rowindex];
  }

  private function checkcalllogs($trno)
  {
    $tbl = $this->table;
    $isposted = $this->othersClass->isposted2($trno, "transnum");
    if ($isposted) {
      $tbl = $this->htable;
    }

    $res = $this->coreFunctions->opentable('select endtime from ' . $tbl . ' where trno = ?', [$trno]);
    if (!empty($res)) {
      foreach ($res as $key => $value) {
        if ($value->endtime != '') {
          $status = true;
        } else {
          $status = false;
        }
      }
    } else {
      $status = true;
    }
    return $status;
  }

  private function checkcalllogsperrow($trno, $line)
  {
    $tbl = $this->table;
    $isposted = $this->othersClass->isposted2($trno, "transnum");
    if ($isposted) {
      $tbl = $this->htable;
    }

    $res = $this->coreFunctions->opentable('select endtime from ' . $tbl . ' where trno = ? and line = ?', [$trno, $line]);
    foreach ($res as $key => $value) {
      if ($value->endtime != '') {
        $status = true;
      } else {
        $status = false;
      }
    }
    return $status;
  }
} //end class
