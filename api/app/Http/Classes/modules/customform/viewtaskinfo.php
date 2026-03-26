<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class viewtaskinfo
{
  private $fieldClass;
  private $tabClass;
  private $coreFunctions;
  private $companysetup;
  private $othersClass;
  private $logger;
  private $warehousinglookup;

  public $modulename = 'TASK INFO';
  public $gridname = 'inventory';
  private $fields = ['barcode', 'itemname'];
  private $table = 'stockrem';

  // public $tablelogs = 'table_log';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';

  public $style = 'width:100%;max-width:80%;';
  public $issearchshow = true;
  public $showclosebtn = true;

  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->coreFunctions = new coreFunctions;
    $this->companysetup = new companysetup;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
  }

  public function createHeadField($config)
  {
    $row = $config['params']['row'];
    $this->modulename = strtoupper($config['params']['row']['title']);
    $fields = ['lblrem', 'task'];
    
    if($config['params']['doc'] !='TK'){
       array_push($fields,'refresh');
     }
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'lblrem.label', 'TASK INFO');
   
    if($config['params']['doc'] !='TK'){
       data_set($col1, 'refresh.label', 'Submit');
      }else{
       data_set($col1, 'task.readonly', true);
      }

    return array('col1' => $col1);
  }

  public function paramsdata($config)
  {
    if (isset($config['params']['row'])) {
      $trno = $config['params']['row']['trno'];
      $line = $config['params']['row']['line'];
    } else {
      $trno = $config['params']['dataparams']['trno'];
      $line = $config['params']['dataparams']['line'];
    }

    return $this->getheaddata($trno, $line, $config['params']['doc'], $config);
  }

  public function getheaddata($trno, $line, $doc, $config)
  {
    $tablename = 'tmdetail';
    $tbl = '';
    $qry = "select trno, line,task, 0 as isnew from " . $tablename . " where trno=? and line=?";

    $data = $this->coreFunctions->opentable($qry, [$trno, $line]);
    if (!empty($data)) {
      return $data;
    } else {
      $data = [];
      $row['task'] = '';
      $row['trno'] = $trno;
      $row['line'] = $line;
      $row['isnew'] = 1;
      array_push($data, $row);
      return $data;
    }
  }

  public function data()
  {
    return [];
  }

  public function createTab($config)
  {
    $tab = [];
    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function loaddata($config)
  {
    $trno = $config['params']['dataparams']['trno'];
    $line = $config['params']['dataparams']['line'];
    $data2 = [];
    $isnew = $config['params']['dataparams']['isnew'];
      $task = $this->othersClass->sanitizekeyfield('task', $config['params']['dataparams']['task']);

      $data = [
        'trno' => $trno,
        'line' => $line,
        'task' => $task
      ];

      $tablename = 'tmdetail';
      $label='Successfully loaded.';

      if ($isnew) {

        if (!$this->checkdata($trno, $line, $tablename)) {
          $this->coreFunctions->sbcinsert($tablename, $data);
          $this->logger->sbcwritelog(
            $trno,
            $config,
            'TASKINFO',
            'ADD - Line:' . $line
              . ' Task:' . $task
          );
        } else {
          $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
          $data['editby'] = $config['params']['user'];
          $this->coreFunctions->sbcupdate($tablename, $data, ['trno' => $trno, 'line' => $line]);
        }
      } else {
        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];
        $this->coreFunctions->sbcupdate($tablename, $data, ['trno' => $trno, 'line' => $line]);
      }

    $txtdata = $this->paramsdata($config);
    // /$doc = $config['params']['doc'];
    // $modtype = $config['params']['moduletype'];
    // $path = 'App\Http\Classes\modules\\' . strtolower($modtype) . '\\' . strtolower($doc);
    // $config['params']['trno'] = $trno;
    // $stock = app($path)->openstock($trno, $config);
    return ['status' => true, 'msg' => $label, 'data' => []];// 'reloadgriddata' => ['inventory' => $stock]
  }

  private function checkdata($trno, $line, $tblname)
  {
    $qry = "select trno from " . $tblname . " where trno = ? and line = ?";
    $data = $this->coreFunctions->opentable($qry, [$trno, $line]);

    if (!empty($data)) {
      return true;
    } else {
      return false;
    }
  } // end fn

}
