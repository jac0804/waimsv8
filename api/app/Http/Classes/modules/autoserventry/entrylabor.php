<?php

namespace App\Http\Classes\modules\autoserventry;

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

class entrylabor
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Task / Labor';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'pttask';
  private $htable = 'hpttask';
  private $othersClass;
  public $style = 'width:100%;max-width:1200px;';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $fields = ['trno', 'jobline', 'laborline', 'cost', 'rate', 'rem', 'mecline']; // 'mecline',
  public $showclosebtn = true;
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
    $doc = $config['params']['doc'];
    $columns = ['action',  'jobcode', 'jobtitle', 'code', 'description', 'cost', 'mechanic', 'rem']; //mechanic
    $tab = [$this->gridname => ['gridcolumns' => $columns]];


    $stockbuttons = ['save', 'delete', 'entryparts'];
    if (!isset($config['params']['row']['description'])) {
      $stockbuttons = [];
    }
    foreach ($columns as $key => $value) {
      $$value = $key;
    }
    $tab = [$this->gridname => ['gridcolumns' => $columns]];
    $obj = $this->tabClass->createTab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
    $obj[0][$this->gridname]['columns'][$code]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
    $obj[0][$this->gridname]['columns'][$cost]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
    $obj[0][$this->gridname]['columns'][$description]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
    $obj[0][$this->gridname]['columns'][$rem]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";

    $obj[0][$this->gridname]['columns'][$code]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$description]['readonly'] = true;

    $obj[0][$this->gridname]['columns'][$cost]['label'] = "Cost";
    $obj[0][$this->gridname]['columns'][$cost]['readonly'] = false;


    $obj[0][$this->gridname]['columns'][$mechanic]['type'] = 'lookup';
    $obj[0][$this->gridname]['columns'][$mechanic]['lookupclass'] = 'lookupmechanic';
    $obj[0][$this->gridname]['columns'][$mechanic]['action'] = 'lookupsetup';
    $obj[0][$this->gridname]['columns'][$mechanic]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$mechanic]['style'] = "width:170px;whiteSpace: normal;min-width:170px;";

    $obj[0][$this->gridname]['columns'][$jobcode]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$jobtitle]['readonly'] = true;

    if (isset($config['params']['row']['description'])) {
      $this->modulename .= ' - ' . $config['params']['row']['description'];
      $obj[0][$this->gridname]['columns'][$jobcode]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$jobtitle]['type'] = 'coldel';
    } else {
      $obj[0][$this->gridname]['columns'][$cost]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$rem]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$mechanic]['type'] = 'input';
      $obj[0][$this->gridname]['columns'][$action]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$jobtitle]['label'] = 'Job Description';
      $obj[0][$this->gridname]['columns'][$jobtitle]['style'] = "width:170px;whiteSpace: normal;min-width:170px;";
    }

    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['addoutlet', 'saveallentry', 'whlog'];

    if (!isset($config['params']['row'])) $tbuttons = [];

    $obj = $this->tabClass->createtabbutton($tbuttons);
    if (isset($config['params']['row'])) {
      $obj[0]['lookupclass'] = 'addtasklabor';
      $obj[0]['action'] = 'lookupsetup';
    }

    return $obj;
  }

  private function selectqry()
  {
    $qry = "jt.line,jobtask.code, jobtask.description, jt.cost, jt.rate, jt.rem, 
            jt.mecline, jt.trno,jt.jobline,jt.laborline,jt.line as taskline,mech.clientname as mechanic";
    return $qry;
  }

  public function add($config)
  {
    $data = [];
    return $data;
  }

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    $trno = $config['params']['tableid'];
    $companyid = $config['params']['companyid'];

    $dateTables = ['pttask'];
    $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfieldFast($value2, $data[$key][$value2], $lookups);
        }
        $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data2['editby'] = $config['params']['user'];
        $this->coreFunctions->sbcupdate($this->table, $data2, ['trno' => $trno, 'line' => $data[$key]['line']]);
      }
    }
    $returndata = $this->loaddata($config);
    $data = $returndata;
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata, 'reloaddata' => true];
  }


  public function save($config)
  {
    $row = $config['params']['row'];
    $doc = $config['params']['doc'];
    $trno = $config['params']['tableid'];
    $companyid = $config['params']['companyid'];
    $data = [];
    $dateTables = ['pttask'];
    $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);
    foreach ($this->fields as $key2 => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfieldFast($value, $row[$value], $lookups);
    }
    if ($row['line'] == 0) { // insert
      $data['encodeddate'] = $this->othersClass->getCurrentTimeStamp();
      $data['encodedby'] = $config['params']['user'];
      $line = $this->coreFunctions->datareader("select line as value from " . $this->table . " where trno =? order by line desc limit 1", [$trno], '', true);
      if ($line != 0) {
        $line = $line + 1;
      } else {
        $line = 1;
      }
      $data['line'] = $line;
      if ($this->coreFunctions->sbcinsert($this->table, $data)) {
        $config['params']['doc'] = 'ENTRYLABOR';
        $job = $this->coreFunctions->opentable("select jobcode,description from jobtask where line =?", [$row['laborline']]);
        $this->logger->sbcmasterlog($trno, $config, ' ADD - Line: ' . $line . ' Code : ' . $job[0]->jobcode . ' ' . 'Task Desc : ' . $job[0]->description, 0, 0, $row['jobline']);
        $returnrow = $this->loaddataperrecord($config, $line);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else { // update
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      $update = $this->coreFunctions->sbcupdate($this->table, $data, ['trno' => $trno, 'line' => $row['line']]);
      if ($update) {
        $returnrow = $this->loaddataperrecord($config, $row['line']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Update failed.'];
      }
    }
  } // end function
  public function loaddata($config)
  {
    // $row = !empty($config['params']['row']) ? $config['params']['row'] : $config['params']['data'][0];
    // $trno = $config['params']['tableid']; //view
    $isview = false;
    if (!empty($config['params']['row'])) {
      $line = $config['params']['row']['line'];
      $trno = $config['params']['row']['trno'];
    } else {
      if (isset($config['params']['data'])) {
        $line = $config['params']['data'][0]['jobline'];
        $trno = $config['params']['data'][0]['trno'];
      } else {
        $trno = $config['params']['tableid'];
        $isview = true;
      }
    }
    $filter = "";
    $join = "left join ptjobs as jobs on jobs.line = jt.jobline and jt.trno = jobs.trno
             left join jobthead as jo on jo.line = jobs.jobid";
    $hjoin = "left join hptjobs as jobs on jobs.line = jt.jobline and jt.trno = jobs.trno
             left join jobthead as jo on jo.line = jobs.jobid";
    $fields = ",jo.docno as jobcode,jo.jobtitle";
    if (!$isview) {
      $filter = "and jt.jobline = " . $line . "";
      $join = "";
      $fields = "";
    }

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

    $select = $this->selectqry() . ", '' as bgcolor $fields ";
    $qry = "select " . $select . " from " . $this->table . " as jt 
           left join jobtask on jobtask.line=jt.laborline
           left join client as mech on mech.clientid = jt.mecline
           $join
           where  jt.trno = " . $trno . " $filter " . $filtersearch . "
           union all 
           select " . $select . " from " . $this->htable . " as jt 
           left join jobtask on jobtask.line=jt.laborline
           left join client as mech on mech.clientid = jt.mecline
           $hjoin
           where  jt.trno = " . $trno . " $filter " . $filtersearch . " 
           order by line";

    return $this->coreFunctions->opentable($qry);
  }
  private function loaddataperrecord($config, $line)
  {
    // $jobline = isset($config['params']['rows'][0]['jobline']) ? $config['params']['rows'][0]['jobline'] : $config['params']['row']['jobline'];
    $isview = false;

    if (isset($config['params']['rows'][0]['jobline'])) {
      $jobline = $config['params']['rows'][0]['jobline'];
      $trno = $config['params']['tableid'];
    } else {
      if (isset($config['params']['row']['jobline'])) {
        $jobline = $config['params']['row']['jobline'];
        $trno = $config['params']['row']['trno'];
      } else {
        $isview = true;
        $trno = $config['params']['tableid'];
      }
    }
    $filter = "";
    $join = "left join ptjobs as jobs on jobs.line = jt.jobline and jt.trno = jobs.trno
             left join jobthead as jo on jo.line = jobs.jobid";
    $hjoin = "left join hptjobs as jobs on jobs.line = jt.jobline and jt.trno = jobs.trno
             left join jobthead as jo on jo.line = jobs.jobid";
    $fields = ",jo.docno as jobcode,jo.jobtitle";
    if (!$isview) {
      $filter = " and jt.line= $line and jt.jobline = $jobline";
      $join = "";
      $fields = "";
    }
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor $fields ";
    $qry = "select " . $select . " from " . $this->table . " as jt
            left join jobtask on jobtask.line=jt.laborline
            left join client as mech on mech.clientid = jt.mecline  
            $join
            where jt.trno = $trno  $filter 
            union all 
            select " . $select . " from " . $this->htable . " as jt
            left join jobtask on jobtask.line=jt.laborline
            left join client as mech on mech.clientid = jt.mecline  
            $hjoin
            where jt.trno = $trno  $filter";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  public function delete($config)
  {
    $row = $config['params']['row'];
    $doc = $config['params']['doc'];
    $qry = "delete from " . $this->table . " where line=? and trno=? and jobline=?";

    $query = "select * from ptstock where trno = ? and taskline = ? and jobline = ? "; #check stock before delete
    $stock = $this->coreFunctions->opentable($query, [$row['trno'], $row['line'], $row['jobline']]);

    if (!empty($stock)) {
      return ['status' => false, 'msg' => "Cannot delete this task/labor; already have Part/Item."];
    }

    $this->coreFunctions->execqry($qry, 'delete', [$row['line'], $row['trno'], $row['jobline']]);
    $this->coreFunctions->sbcupdate('jobtask', ['istlabor' => 0], ['line' => $row['laborline']]);
    $config['params']['doc'] = 'ENTRYLABOR';
    $task = $this->coreFunctions->opentable("select code,description from jobtask where line =?", [$row['laborline']]);
    $this->logger->sbcdelmaster_log($row['line'], $config, 'REMOVE LINE: ' . $row['line'] . ' -Task Code: ' . $task[0]->code . ' ' . 'Task Desc : ' . $task[0]->description);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }

  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
      case 'whlog':
        return $this->lookuplogs($config);
        break;
      case 'addtasklabor':
        return $this->addtasklabor($config);
        break;
      case 'lookupmechanic':
        return $this->lookupmechanic($config);
        break;
      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
        break;
    }
  }


  public function addtasklabor($config)
  {
    $jobline = !empty($config['params']['row']) ? $config['params']['row']['line'] : $config['params']['data'][0]['jobline'];
    $lookupsetup = array(
      'type' => 'multi',
      'rowkey' => 'line',
      'title' => 'List of Tasks / Labor',
      'style' => 'width:800px;max-width:800px;'
    );

    $plotsetup = array(
      'plottype' => 'tableentry', //addtogrid
      'action' => 'addtasklabor'
    );

    // lookup columns
    $cols = [
      ['name' => 'code', 'label' => 'Code', 'align' => 'left', 'field' => 'code', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'description', 'label' => 'Description', 'align' => 'left', 'field' => 'description', 'sortable' => true, 'style' => 'font-size:16px;']
    ];
    $qry = "select line,code, description, $jobline as jobline from jobtask order by code";
    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }
  public function lookupmechanic($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'rowkey' => 'mecline',
      'title' => 'List of Mechanic',
      'style' => 'width:800px;max-width:800px;'
    );
    $plotting = array('mechanic' => 'mechanic', 'mecline' => 'mecline');
    $plotsetup = array(
      'plottype' => 'plotgrid',
      'plotting' => $plotting,
    );


    // lookup columns
    $cols = [
      ['name' => 'mechacode', 'label' => 'Code', 'align' => 'left', 'field' => 'mechacode', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'mechanic', 'label' => 'Description', 'align' => 'left', 'field' => 'mechanic', 'sortable' => true, 'style' => 'font-size:16px;']
    ];
    $qry = "select client as mechacode,clientname as mechanic,clientid as mecline from client where ismechanic = 1";
    $data = $this->coreFunctions->opentable($qry);

    $rowindex = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $rowindex];
  }

  public function lookupcallback($config)
  {
    $row = $config['params']['rows'];
    $returndata = [];
    $errors = [];
    $msg = 'Successfully added.';
    foreach ($row  as $key2 => $value) {
      $config['params']['row']['line'] = 0;
      $config['params']['row']['trno'] = $config['params']['tableid'];
      $config['params']['row']['jobline'] = $row[$key2]['jobline'];
      $config['params']['row']['laborline'] = $row[$key2]['line'];
      $config['params']['row']['cost'] = 0;
      $config['params']['row']['mecline'] = 0;
      $config['params']['row']['rate'] = 0;
      $config['params']['row']['rem'] = '';
      $config['params']['row']['bgcolor'] = 'bg-blue-2';
      $return = $this->save($config);
      if ($return['status']) {
        array_push($returndata, $return['row'][0]);
      } else {
        $errors[] = $return['msg'];
      }
    }

    $status = count($returndata) > 0;
    $msg = $status ? 'Successfully added.'  : 'No Tasks were saved. ';

    if (!empty($errors)) {
      $msg .= implode(', ', $errors) . ' task already exist.';
    }

    return ['status' => $status, 'msg' => $msg, 'data' => $returndata];
  } // end function

  public function lookuplogs($config)
  {
    $jobline = !empty($config['params']['row']) ? $config['params']['row']['line'] : $config['params']['data'][0]['jobline'];
    $doc = 'ENTRYLABOR';
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
    where log.doc = '" . $doc . "' and log.trno = '" . $trno . "' and log.trno2 = $jobline
    union all
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from  " . $this->tablelogs_del . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "' and log.trno = $trno  and log.trno2 = $jobline ";

    $qry = $qry . " order by dateid desc";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
  }
} //end class
