<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use Exception;

class viewnotes
{
  private $fieldClass;
  private $tabClass;
  private $coreFunctions;
  private $companysetup;
  private $othersClass;
  private $warehousinglookup;
  private $sqlquery;
  private $logger;

  public $modulename = 'ADD NOTES';
  public $gridname = 'customformacctg';
  private $fields = ['trno', 'line', 'rem', 'createby', 'createdate'];
  private $table = 'detailrems';

  public $tablelogs = 'table_log';

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
    $this->sqlquery = new sqlquery;
  }

  public function createHeadField($config)
  {
    $doc = $config['params']['doc'];
    $companyid = $config['params']['companyid'];
    $fields = ['lblrem', 'rem', 'refresh'];

    $col1 = $this->fieldClass->create($fields);



    data_set($col1, 'refresh.label', 'update');
    data_set($col1, 'rem.class', 'csrem');
    data_set($col1, 'rem.readonly', false);


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

    return $this->getheaddata($config, $trno, $line, $config['params']['doc']);
  }

  public function getheaddata($config, $trno, $line, $doc)
  {

    $data = [];
    $row['rem'] = '';
    $row['trno'] = $trno;
    $row['line'] = $line;
    $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['createby'] = $config['params']['user'];
    array_push($data, $row);
    return $data;
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
    $rem = $this->othersClass->sanitizekeyfield('rem', $config['params']['dataparams']['rem']);
    $createby = $config['params']['user'];
    $createdate = $this->othersClass->getCurrentTimeStamp();;


    $data = [
      'trno' => $trno,
      'line' => $line,
      'rem' => $rem,
      'createby' => $createby,
      'createdate' => $createdate
    ];


    foreach ($data as $key => $v) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }
    $this->coreFunctions->sbcinsert($this->table, $data);

    return ['status' => true, 'msg' => 'Successfully loaded.', 'reloadhead' => true];
  }
}
