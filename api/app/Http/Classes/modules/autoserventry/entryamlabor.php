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

class entryamlabor
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Task / Labor';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'amtask';
  private $htable = 'hamtask';
  private $othersClass;
  public $style = 'width:1000px;max-width:1000px;';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $fields = ['trno', 'jobline', 'laborline', 'cost', 'rate', 'rem', 'mecline']; // 'mecline',
  public $showclosebtn = true;
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
    $descriptions = isset($config['params']['row']['description']) ? $config['params']['row']['description'] : '';

    $doc = $config['params']['doc'];
    $columns = ['jobcode', 'jobtitle', 'code', 'description', 'cost', 'mechanic', 'rate', 'rem']; //viewing

    if ($descriptions != '') {
      $columns = ['action', 'code', 'description', 'cost', 'mechanic', 'rate', 'rem']; //mechanic
    }
    $tab = [$this->gridname => ['gridcolumns' => $columns]];

    foreach ($columns as $key => $value) {
      $$value = $key;
    }

    $stockbuttons = []; //viewing
    if ($descriptions != '') {
      $stockbuttons = ['save', 'delete', 'entryamparts'];
    }
    $tab = [$this->gridname => ['gridcolumns' => $columns]];
    $obj = $this->tabClass->createTab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][$code]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
    $obj[0][$this->gridname]['columns'][$cost]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
    $obj[0][$this->gridname]['columns'][$rate]['style'] = "width:70px;whiteSpace: normal;min-width:70px;";
    $obj[0][$this->gridname]['columns'][$description]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$rem]['style'] = "width:400px;whiteSpace: normal;min-width:400px;";

    $obj[0][$this->gridname]['columns'][$code]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$description]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$cost]['readonly'] = false;
    $obj[0][$this->gridname]['columns'][$description]['label'] = 'Task Description';
    $obj[0][$this->gridname]['columns'][$code]['label'] = 'Task Code';
    $obj[0][$this->gridname]['columns'][$rem]['label'] = 'Task Notes';
    $obj[0][$this->gridname]['columns'][$cost]['label'] = 'Task Cost';
    $obj[0][$this->gridname]['columns'][$rate]['label'] = 'Task Rate';

    $obj[0][$this->gridname]['columns'][$mechanic]['type'] = 'lookup';
    $obj[0][$this->gridname]['columns'][$mechanic]['lookupclass'] = 'lookupmechanic';
    $obj[0][$this->gridname]['columns'][$mechanic]['action'] = 'lookupsetup';
    $obj[0][$this->gridname]['columns'][$mechanic]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$mechanic]['style'] = "width:170px;whiteSpace: normal;min-width:170px;";



    if ($descriptions != '') {
      $this->modulename .= ' - ' . $descriptions;
      $obj[0][$this->gridname]['columns'][1]['btns']['delete']['label'] = 'delete';
      $obj[0][$this->gridname]['columns'][$action]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
    } else {
      //for viewing
      $obj[0][$this->gridname]['columns'][$cost]['type'] = 'label';
      $obj[0][$this->gridname]['columns'][$rem]['type'] = 'label';
      $obj[0][$this->gridname]['columns'][$rate]['type'] = 'label';
      $obj[0][$this->gridname]['columns'][$jobtitle]['type'] = 'label';
      $obj[0][$this->gridname]['columns'][$jobcode]['type'] = 'label';

      $obj[0][$this->gridname]['columns'][$code]['type'] = 'label';
      $obj[0][$this->gridname]['columns'][$description]['type'] = 'label';
      $obj[0][$this->gridname]['columns'][$jobtitle]['label'] = 'Job Description';
      $obj[0][$this->gridname]['columns'][$description]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
      $obj[0][$this->gridname]['columns'][$cost]['style'] = 'width:80px;whiteSpace: normal;min-width:80px; text-align: right';
      $obj[0][$this->gridname]['columns'][$rate]['style'] = 'width:70px;whiteSpace: normal;min-width:70px; text-align: right';
      $obj[0][$this->gridname]['columns'][$jobtitle]['style'] = 'width:100px;whiteSpace: normal;min-width:100px; text-align: left';
      $obj[0][$this->gridname]['columns'][$jobcode]['style'] = 'width:80px;whiteSpace: normal;min-width:80px; text-align: left';

      $obj[0][$this->gridname]['columns'][$mechanic]['type'] = 'label';
    }

    $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $rows = isset($config['params']['row']) ? $config['params']['row'] : '';

    if ($rows != '') {
      $tbuttons = ['addoutlet', 'saveallentry', 'whlog'];
      $obj = $this->tabClass->createtabbutton($tbuttons);
      $obj[0]['lookupclass'] = 'addtasklabor';
      $obj[0]['action'] = 'lookupsetup';
    } else {
      //viewing
      $tbuttons = [];
      $obj = $this->tabClass->createtabbutton($tbuttons);
    }
    return $obj;
  }

  public function loaddata($config)
  {
    if (!empty($config['params']['row'])) {
      $jobline = $config['params']['row']['line'];
      $trno = $config['params']['row']['trno'];
    } else {
      $jobline = isset($config['params']['data'][0]['jobline']) ? $config['params']['data'][0]['jobline'] : 0;
      $trno = isset($config['params']['data'][0]['trno']) ? $config['params']['data'][0]['trno'] : 0;

      //viewing
      if ($jobline == 0 && $trno == 0) {
        $trno = $config['params']['tableid'];
      }
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

    $orderby = " order by line";
    if ($jobline != 0) {
      $jobline = "  and jt.jobline = $jobline";
    } else {
      //viewing
      $jobline = '';
      $orderby = " order by jobcode";
    }

    $select = $this->selectqry() . ", '' as bgcolor";
    $qry = "select " . $select . " from " . $this->table . " as jt 
           left join jobtask on jobtask.line=jt.laborline 
            left join amjobs as jo on jo.line=jt.jobline and jo.trno=jt.trno
           left join jobthead as joo on joo.line=jo.jobid
           left join client as mech on mech.clientid = jt.mecline
           where  jt.trno = ? $jobline
           " . $filtersearch . "
           
           union all 

           select " . $select . " from " . $this->htable . " as jt 
           left join jobtask on jobtask.line=jt.laborline 
           left join hamjobs as jo on jo.line=jt.jobline and jo.trno=jt.trno
           left join jobthead as joo on joo.line=jo.jobid
           left join client as mech on mech.clientid = jt.mecline
           where  jt.trno = ? $jobline
           " . $filtersearch . "

           $orderby";
    $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $data;
  }

  private function selectqry()
  {
    $qry = "jt.line,jobtask.code, jobtask.description, jt.cost, jt.rate, jt.rem, 
            jt.mecline, jt.trno,jt.jobline,jt.laborline,jt.line as taskline,joo.jobtitle, joo.docno as jobcode,mech.clientname as mechanic";
    return $qry;
  }

  public function add($config)
  {
    $data = [];
    return $data;
  }

  public function saveallentry($config)
  {
    $companyid = $config['params']['companyid'];
    $data = $config['params']['data'];
    $trno = $config['params']['tableid'];
    $msg = "All saved successfully.";
    $stat = true;
    $dateTables = ['amtask'];
    $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
         
          $data2[$value2] = $this->othersClass->sanitizekeyfieldFast($value2, $data[$key][$value2], $lookups);
        }
        if ($data[$key]['line'] == 0) {
          $data['encodeddate'] = $this->othersClass->getCurrentTimeStamp();
          $data['encodedby'] = $config['params']['user'];
          $taskcode = $this->coreFunctions->datareader("select code  as value from jobtask where line =?", [$data['tlline']]);
          $taskname = $this->coreFunctions->datareader("select description  as value from jobtask where line =?", [$data['tlline']]);

          $line = $this->coreFunctions->datareader("select line as value from " . $this->table . " where trno =? order by line desc limit 1", [$trno], '', true);
          if ($line == 0) {
            $line = 1;
          } else {
            $line = $line + 1;
          }
          $data['line'] = $line;
          $insert = $this->coreFunctions->sbcinsert($this->table, $data2);
          if ($insert) {
            $config['params']['doc'] = 'ENTRYAMTASK';
            $this->logger->sbcmasterlog($trno, $config, ' CREATE - Line: ' . $line . ' Code :' . $taskcode . ' ' . 'Task Desc : ' . $taskname);
          } else {
            $stat = false;
            $msg = "Saving failed for Line: " . $line . ' Code :' . $taskcode . ' ' . 'Task Desc : ' . $taskname;
          }
        } else {
          $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
          $data2['editby'] = $config['params']['user'];
          $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line'], 'trno' => $trno]);
        }
      }
    }
    $returndata = $this->loaddata($config);
    return ['status' => $stat, 'msg' => $msg, 'data' => $returndata];
  }

  public function save($config)
  {
    $trno = $config['params']['tableid'];
    $companyid = $config['params']['companyid'];
    $row = $config['params']['row'];
    $doc = $config['params']['doc'];
    $data = [];

    $dateTables = ['amtask'];
    $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);
    foreach ($this->fields as $key2 => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfieldFast($value, $row[$value], $lookups);
    }

    if ($row['line'] == 0) { // insert
      $data['encodeddate'] = $this->othersClass->getCurrentTimeStamp();
      $data['encodedby'] = $config['params']['user'];
      $line = $this->coreFunctions->datareader("select line as value from " . $this->table . " where trno =? order by line desc limit 1", [$trno], '', true);
      if ($line == 0) {
        $line = 1;
      } else {
        $line = $line + 1;
      }
      $data['line'] = $line;

      $trno2 = $data['jobline'];

      $taskcode = $this->coreFunctions->datareader("select code  as value from jobtask where line =?", [$row['laborline']]);
      $taskname = $this->coreFunctions->datareader("select description  as value from jobtask where line =?", [$row['laborline']]);
      if ($this->coreFunctions->sbcinsert($this->table, $data)) {
        $config['params']['doc'] = 'ENTRYAMTASK';
        $this->logger->sbcmasterlog($trno, $config, ' CREATE - Line: ' . $line . ' Code : ' . $taskcode . ' ' . 'Task Desc : ' . $taskname, 0, 0, $trno2);
        $returnrow = $this->loaddataperrecord($config, $line);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else { // update
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      $update = $this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line'], 'trno' => $trno]);
      if ($update) {
        $returnrow = $this->loaddataperrecord($config, $row['line']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Update failed.'];
      }
    }
  } // end function

  private function loaddataperrecord($config, $line)
  {
    $trno = $config['params']['tableid'];
    $jobline = isset($config['params']['sourcerow']['jobline']) ? $config['params']['sourcerow']['jobline'] : $config['params']['row']['jobline'];
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " as jt
            left join jobtask on jobtask.line=jt.laborline 
            left join amjobs as jo on jo.line=jt.jobline and jo.trno=jt.trno
            left join jobthead as joo on joo.line=jo.jobid
             left join client as mech on mech.clientid = jt.mecline 
            where jt.trno = $trno and jt.line=? and jt.jobline = $jobline
            
            union all

            select " . $select . " from " . $this->htable . " as jt
            left join jobtask on jobtask.line=jt.laborline 
            left join hamjobs as jo on jo.line=jt.jobline and jo.trno=jt.trno
            left join jobthead as joo on joo.line=jo.jobid
             left join client as mech on mech.clientid = jt.mecline 
            where jt.trno = $trno and jt.line=? and jt.jobline = $jobline ";
    // var_dump($qry);
    $data = $this->coreFunctions->opentable($qry, [$line, $line]);
    return $data;
  }

  public function delete($config)
  {
    $trno = $config['params']['tableid'];
    $row = $config['params']['row'];
    $doc = $config['params']['doc'];

    $exist = !empty($this->coreFunctions->getfieldvalue("lastock", "trno",  "trno=? and taskline=? and jobline=?",  [$trno, $row['line'], $row['jobline']]));

    if ($exist) {
      return ['status' => false, 'msg'   => 'Some parts are already existing in this task/job.'];
    } else {
      $qry = "delete from " . $this->table . " where line=? and trno=? and jobline=?";
      $this->coreFunctions->execqry($qry, 'delete', [$row['line'], $row['trno'], $row['jobline']]);
      $trno = $config['params']['tableid'];
      $config['params']['doc'] = 'ENTRYAMTASK';
      $task = $this->coreFunctions->opentable("select code,description from jobtask where line =?", [$row['laborline']]);
      $this->logger->sbcdelmaster_log($trno, $config, 'REMOVE LINE: ' . $row['line'] . ' -Task Code: ' . $task[0]->code . ' ' . 'Task Desc : ' . $task[0]->description, 0, $row['jobline']);
      return ['status' => true, 'msg' => 'Successfully deleted.'];
    }
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
      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
        break;
    }
  }


  public function addtasklabor($config)
  {
    // var_dump($config['params']);
    // break;
    // $jobline = $config['params']['row']['line'];
    $jobline = isset($config['params']['row']['line']) ? $config['params']['row']['line'] : $config['params']['data'][0]['jobline'];
    $lookupsetup = array(
      'type' => 'multi',
      'rowkey' => 'line',
      'title' => 'List of Tasks / Labor',
      'style' => 'width:800px;max-width:800px;'
    );

    $plotsetup = array(
      'plottype' => 'tableentry', //addtogrid multientry
      'action' => 'addtasklabor'
    );

    // lookup columns
    $cols = [
      ['name' => 'code', 'label' => 'Code', 'align' => 'left', 'field' => 'code', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'description', 'label' => 'Description', 'align' => 'left', 'field' => 'description', 'sortable' => true, 'style' => 'font-size:16px;']
    ];
    $qry = "select line,code, description, $jobline as jobline from jobtask  order by code";
    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
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

    // var_dump($config['params']);
    // var_dump($config['params']['row']);
    // break;

    $jobline = isset($config['params']['row']['line']) ? $config['params']['row']['line'] : $config['params']['data'][0]['jobline'];
    // $jobline = isset($config['params']['row']['line']) ? $config['params']['row']['line'] : 0;

    $doc = 'ENTRYAMTASK';
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
    select log.trno, log.doc, log.task, log.user, log.dateid, 
    if(u.pic='','blank_user.png',u.pic) as pic
    from " . $this->tablelogs . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "'  and log.trno= $trno and trno2=$jobline
    union all
    select log.trno, log.doc, log.task, log.user, log.dateid, 
    if(u.pic='','blank_user.png',u.pic) as pic
    from  " . $this->tablelogs_del . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "'  and log.trno= $trno and trno2=$jobline";

    $qry = $qry . " order by dateid desc";

    // var_dump($qry);
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
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
} //end class
