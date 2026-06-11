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

class entryamjobs
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Job Details';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'amjobs';
  private $htable = 'hamjobs';
  private $othersClass;
  public $style = 'width:100%;';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $fields = ['trno', 'jobid', 'rem']; //, 'packageline'
  public $showclosebtn = false;
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
    // var_dump($config['params']);
    // break;
    $columns = ['action', 'code', 'description', 'rem']; // 'packname'
    $tab = [$this->gridname => ['gridcolumns' => $columns]];

    foreach ($columns as $key => $value) {
      $$value = $key;
    }

    $stockbuttons = ['save', 'addtask', 'delete'];
    $tab = [$this->gridname => ['gridcolumns' => $columns]];
    $obj = $this->tabClass->createTab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
    $obj[0][$this->gridname]['columns'][$code]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
    $obj[0][$this->gridname]['columns'][$description]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][$rem]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
    // $obj[0][$this->gridname]['columns'][$packname]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
    // $obj[0][$this->gridname]['columns'][$packname]['label'] = 'Package';

    $obj[0][$this->gridname]['columns'][$code]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$description]['readonly'] = true;

    $obj[0][$this->gridname]['columns'][$action]['btns']['addtask']['name'] = 'multigrid';
    $obj[0][$this->gridname]['columns'][$action]['btns']['addtask']['action'] = 'autoserventry';
    $obj[0][$this->gridname]['columns'][$action]['btns']['addtask']['lookupclass'] = 'entryamlabor';
    // $obj[0][$this->gridname]['columns'][$action]['btns']['addtask']['access'] = 'edititem';
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['addoutlet', 'saveallentry', 'whlog'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['lookupclass'] = 'addjob';
    $obj[0]['action'] = 'lookupsetup';
    return $obj;
  }

  public function loaddata($config)
  {
    $trno = $config['params']['tableid'];
    $filtersearch = "";
    $searchfield = ['jd.trno', 'jd.jobid', 'jd.rem'];

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

    $select = $this->selectqry() . ", '' as bgcolor";
    $qry = "select " . $select . " from " . $this->table . " as jd 
           left join jobthead as jb on jb.line=jd.jobid where jd.trno=$trno " . $filtersearch . " order by line";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  private function selectqry()
  {
    $qry = "jd.trno,jd.line,jd.packageline,jd.jobid, jd.rem,jb.docno as code, jb.jobtitle as description";
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
    $msg = "All saved successfully.";
    $stat = true;
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }
        if ($data[$key]['line'] == 0) {
          $data['encodeddate'] = $this->othersClass->getCurrentTimeStamp();
          $data['encodedby'] = $config['params']['user'];
          $jobname = $this->coreFunctions->datareader("select jobtitle  as value from jobthead where line =?", [$data['jobid']]);
          $jobcode = $this->coreFunctions->datareader("select docno  as value from jobthead where line =?", [$data['jobid']]);
          $line = $this->coreFunctions->datareader("select line as value from " . $this->table . " where trno =? order by line desc limit 1", [$trno], '', true);
          if ($line == 0) {
            $line = 1;
          } else {
            $line = $line + 1;
          }
          $data['line'] = $line;

          $qry = "select jobid  from amjobs where trno = $trno";
          $resultt = $this->coreFunctions->opentable($qry);


          foreach ($resultt as $res) {
            if ($res->jobid == $data['jobid']) {
              return ['status' => false, 'msg' => '( ' . $jobname . ' )'];
            }
          }
          $insert = $this->coreFunctions->sbcinsert($this->table, $data2);
          if ($insert) {
            $config['params']['doc'] = 'ENTRYJOBDETAILS';
            $this->logger->sbcmasterlog($trno, $config, ' CREATE - Line: ' . $line . ' Code :' . $jobcode . ' ' . 'Job Desc : ' . $jobname);
          } else {
            $stat = false;
            $msg = "Saving failed for Line: " . $line . ' Code :' . $jobcode . ' ' . 'Job Desc : ' . $jobname;
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
    $row = $config['params']['row'];
    $trno = $config['params']['tableid'];
    $data = [];
    foreach ($this->fields as $key2 => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
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
      $jobname = $this->coreFunctions->datareader("select jobtitle  as value from jobthead where line =?", [$row['jobid']]);
      $jobcode = $this->coreFunctions->datareader("select docno  as value from jobthead where line =?", [$row['jobid']]);
      $qry = "select jobid  from amjobs where trno = $trno";
      $resultt = $this->coreFunctions->opentable($qry);
      foreach ($resultt as $res) {
        if ($res->jobid == $row['jobid']) {
          return ['status' => false, 'msg' => $jobname, 'data' => [$resultt]];
        }
      }
      if ($this->coreFunctions->sbcinsert($this->table, $data)) {
        $config['params']['doc'] = 'ENTRYJOBDETAILS';
        $this->logger->sbcmasterlog($trno, $config, ' CREATE - Line: ' . $line . ' Code : ' . $jobcode . ' ' . 'Job Desc : ' . $jobname);
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
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " as jd 
           left join jobthead as jb on jb.line=jd.jobid
           where jd.trno=$trno and jd.line=?";
    $data = $this->coreFunctions->opentable($qry, [$line]);
    return $data;
  }

  public function delete($config)
  {
    $row = $config['params']['row'];
    $trno = $config['params']['tableid'];
    $qry = "delete from " . $this->table . " where line=? and trno=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['line'], $row['trno']]);
    $config['params']['doc'] = 'ENTRYJOBDETAILS';
    $jobname = $this->coreFunctions->datareader("select jobtitle  as value from jobthead where line =?", [$row['jobid']]);
    $jobcode = $this->coreFunctions->datareader("select docno  as value from jobthead where line =?", [$row['jobid']]);
    $this->logger->sbcdelmaster_log($trno, $config, 'REMOVE LINE: ' . $row['line'] . ' - Code: ' . $jobcode . ' ' . 'Job Desc : ' . $jobname);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }

  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
      case 'whlog':
        return $this->lookuplogs($config);
        break;
      case 'addjob':
        return $this->addjob($config);
        break;
      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
        break;
    }
  }


  public function addjob($config)
  {
    $lookupsetup = array(
      'type' => 'multi',
      'rowkey' => 'jobline',
      'title' => 'List of Jobs',
      'style' => 'width:800px;max-width:800px;'
    );

    $plotsetup = array(
      'plottype' => 'tableentry',
      'action' => 'addtogrid'
    );

    // lookup columns
    $cols = [
      ['name' => 'code', 'label' => 'Code', 'align' => 'left', 'field' => 'code', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'jobdesc', 'label' => 'Job description', 'align' => 'left', 'field' => 'jobdesc', 'sortable' => true, 'style' => 'font-size:16px;']
    ];
    $qry = "select line as jobline,docno as code, jobtitle as jobdesc from jobthead  order by jobtitle";
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
      $config['params']['row']['jobid'] = $row[$key2]['jobline'];
      $config['params']['row']['rem'] = '';
      $config['params']['row']['packageline'] = 0;
      $config['params']['row']['bgcolor'] = 'bg-blue-2';
      $return = $this->save($config);
      if ($return['status']) {
        array_push($returndata, $return['row'][0]);
      } else {
        $errors[] = $return['msg'];
      }
    }

    $status = count($returndata) > 0;
    $msg = $status ? 'Successfully added.'  : 'No jobs were saved. ';

    if (!empty($errors)) {
      $msg .= implode(', ', $errors) . ' job  already exist.';
    }

    return ['status' => $status, 'msg' => $msg, 'data' => $returndata];
  } // end function

  public function lookuplogs($config)
  {
    $doc =  'ENTRYJOBDETAILS';
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
    where log.doc = '" . $doc . "' and log.trno= $trno
    union all
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from  " . $this->tablelogs_del . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "' and log.trno = $trno";

    $qry = $qry . " order by dateid desc";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
  }
} //end class
