<?php

namespace App\Http\Classes\modules\construction;

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
use App\Http\Classes\sbcdb\trigger;
use App\Http\Classes\sbcdb\waims;
use App\Http\Classes\sbcdb\customersupport;
use Symfony\Component\VarDumper\VarDumper;

class entryprojectactivity
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'ACTIVITY';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'activity';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['stageid', 'subproject', 'stage', 'trno', 'line'];
  public $showclosebtn = true;
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';

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
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'substage']]];
    $stockbuttons = ['delete', 'addpsubactivity'];
    if ($config['params']['doc'] != 'PM') {
      $stockbuttons = ['addpsubactivity'];
    }
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][1]['style'] = 'width:100%;whiteSpace: normal;min-width:180px;';
    $obj[0][$this->gridname]['columns'][1]['readonly'] = true;

    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = ['addsubactivity', 'saveallentry'];
    if ($config['params']['doc'] != 'PM') {
      $tbuttons = [];
    }
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }

  public function add($config)
  {
    $data = [];
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

  public function saveallentry($config)
  {
    $trno = $config['params']['tableid'];
    $data = $config['params']['data'];
    $msg = '';
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }
        $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data2['editby'] = $config['params']['user'];
        $exist = $this->coreFunctions->getfieldvalue($this->table, "line", "line=? and subproject=? and stage=?", [$data2['line'], $data2['subproject'], $data2['stage']]);
        if (empty($exist)) {
          $this->coreFunctions->sbcinsert($this->table, $data2);
          $this->logger->sbcwritelog($trno, $config, 'ACTIVITY', ' CREATE - ' . $data[$key]['substage']);
          $msg = 'All saved successfully.';
        } else {
          $msg = $msg . ' ' . $data[$key]['substage'] . ' Already exist.';
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
  } // end function 


  public function delete($config)
  {
    $row = $config['params']['row'];
    $check = $this->coreFunctions->getfieldvalue("psubactivity", "substage", "trno=? and subproject=? and stage =? and substage =?", [$row['trno'], $row['subproject'], $row['stage'], $row['substageline']]); //check if with subactvity
    if (floatval($check) > 0) {
      return ['status' => false, 'msg' => 'DELETE failed,already have subactivities.'];
    } else {
      $qry = "select sohead.trno as value from sohead left join sostock on sostock.trno = sohead.trno where sohead.projectid = " . $row['trno'] . " and 
      sohead.subproject = " . $row['subproject'] . " and sostock.substage = " . $row['substageline'] . " and sostock.stageid = " . $row['stage'] . "  limit 1";
      $check = $this->coreFunctions->datareader($qry);
      if (floatval($check) > 0) {
        return ['status' => false, 'msg' => 'DELETE failed,already have BOQ...'];
      } else {
        $qry = "select sohead.trno as value from hsohead as sohead left join hsostock as sostock on sostock.trno = sohead.trno where sohead.projectid = " . $row['trno'] . " and 
        sohead.subproject = " . $row['subproject'] . " and sostock.substage = " . $row['substageline'] . " and sostock.stageid = " . $row['stage'] . "  limit 1";
        $check = $this->coreFunctions->datareader($qry);
        if (floatval($check) > 0) {
          return ['status' => false, 'msg' => 'DELETE failed,already have posted BOQ.'];
        } else {
          $qry = "select bahead.trno as value from bahead left join bastock on bastock.trno = bahead.trno where bahead.projectid = " . $row['trno'] . " and 
          bahead.subproject = " . $row['subproject'] . " and bastock.activity = " . $row['substageline'] . " and bastock.stage = " . $row['stage'] . " limit 1";

          $check = $this->coreFunctions->datareader($qry);
          if (floatval($check) > 0) {
            return ['status' => false, 'msg' => 'DELETE failed,already have Billing Accomplishment.'];
          } else {
            $qry = "select bahead.trno as value from hbahead as bahead left join hbastock as bastock on bastock.trno = bahead.trno where bahead.projectid = " . $row['trno'] . " and 
            bahead.subproject = " . $row['subproject'] . " and bastock.activity = " . $row['substageline'] . " and bastock.stage = " . $row['stage'] . "  limit 1";

            $check = $this->coreFunctions->datareader($qry);
            if (floatval($check) > 0) {
              return ['status' => false, 'msg' => 'DELETE failed,already have posted Billing Accomplishment.'];
            } else {
              $qry = "delete from " . $this->table . " where trno =? and  line=? and subproject =?";
              $this->coreFunctions->execqry($qry, 'delete', [$row['trno'], $row['line'], $row['subproject']]);
              $this->logger->sbcwritelog($row['trno'], $config, 'ACTIVITY', ' REMOVE - ' . $row['substage']);
              return ['status' => true, 'msg' => 'Successfully deleted.'];
            }
          }
        }
      }
    }
  }

  public function loaddata($config)
  {
    $stage = isset($config['params']['row']['stage']) ? $config['params']['row']['stage'] : $config['params']['sourcerow']['stage'];
    $trno = isset($config['params']['row']['trno']) ? $config['params']['row']['trno'] : $config['params']['sourcerow']['trno'];
    $subproject = isset($config['params']['row']['subproject']) ? $config['params']['row']['subproject'] : $config['params']['sourcerow']['subproject'];

    $qry = "select a.trno,a.line,a.subproject,a.stage,s.substage,s.line as substageline,a.stageid, '' as bgcolor from activity as a left join substages as s on s.line = a.line
      where a.trno = ? and a.stage = ? and a.subproject =? order by s.line";
    $data = $this->coreFunctions->opentable($qry, [$trno, $stage, $subproject]);
    return $data;
  }

  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];

    switch ($lookupclass2) {
      case 'whlog':
        return $this->lookuplogs($config);
        break;

      case 'addsubactivity':
        return $this->lookupitem($config);
        break;

      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
        break;
    }
  }

  public function lookupcallback($config)
  {

    switch ($config['params']['lookupclass2']) {
      case 'addtogrid':
        $trno = $config['params']['sourcerow']['trno'];
        $row = $config['params']['row'];
        $data = [];
        $data['line'] = $row['line'];;
        $data['trno'] = $trno;
        $data['subproject'] = $config['params']['sourcerow']['subproject'];
        $data['stageid'] = $config['params']['sourcerow']['line'];
        $data['stage'] = $config['params']['sourcerow']['stage'];
        $data['substage'] = $row['substage'];
        $data['bgcolor'] = 'bg-blue-2';
        return ['status' => true, 'msg' => 'Item was successfully added.', 'data' => $data];
        break;
    }
  } // end function


  public function lookupitem($config)
  {
    $stage = $config['params']['sourcerow']['stage'];
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Activity',
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'callback',
      'action' => 'addtogrid'
    );

    // lookup columns
    $cols = array();
    $col = array('name' => 'substage', 'label' => 'Activity', 'align' => 'left', 'field' => 'substage', 'sortable' => true, 'style' => 'font-size:16px;');
    array_push($cols, $col);

    $qry = "select line,substage from substages where stage = " . $stage;
    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  } //end function


  public function lookuplogs($config)
  {

    $doc = strtoupper($config['params']['lookupclass']);
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'List of Logs',
      'style' => 'width:1000px;max-width:1000px;'
    );

    // lookup columns
    $cols = array(
      array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')

    );

    $trno = $config['params']['sourcerow']['line'];

    $qry = "
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from " . $this->tablelogs . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "' and trno = $trno 
    union all
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from  " . $this->tablelogs_del . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "' and trno = $trno ";

    $qry = $qry . " order by dateid desc";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
  }
} //end class
