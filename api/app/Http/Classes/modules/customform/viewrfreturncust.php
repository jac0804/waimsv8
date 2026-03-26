<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class viewrfreturncust
{
  private $fieldClass;
  private $tabClass;
  private $coreFunctions;
  private $companysetup;
  private $othersClass;
  private $warehousinglookup;

  public $modulename = 'Return to Customer';
  public $gridname = 'tableentry';
  private $fields = [];
  private $table = 'rfhead';
  private $htable = 'hrfhead';

  public $tablelogs = 'table_log';

  public $style = 'width:100%;max-width:70%;';
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

  public function getAttrib()
  {
    $attrib = array('load' => 0);
    return $attrib;
  }

  public function createHeadField($config)
  {
    $trno = $config['params']['clientid'];
    $doc = $config['params']['doc'];
    $isposted = $this->othersClass->isposted2($trno, "transnum");

    $qry = "
    select dateclose as value from rfhead where trno = ? 
    union all
    select dateclose as value from hrfhead where trno = ? 
    LIMIT 1";
    $check_dateclose = $this->coreFunctions->datareader($qry, [$trno, $trno]);

    $fields = ['returndate_cust', 'returndate_custby', 'dateclose'];

    if ($check_dateclose == "") {
      array_push($fields, ['refresh', 'close']);
    }

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'refresh.label', 'Save');

    if ($check_dateclose != "") {
      data_set($col1, 'returndate_cust.readonly', true);
      data_set($col1, 'returndate_custby.readonly', true);
      data_set($col1, 'dateclose.readonly', true);
    }

    return array('col1' => $col1, 'col2' => []);
  }

  public function paramsdata($config)
  {
    return $this->getheaddata($config);
  }

  public function getheaddata($config)
  {
    $doc = $config['params']['doc'];
    $trno = $config['params']['clientid'];

    switch ($doc) {
      case 'RF':
        $tbl = strtolower($doc) . 'head';
        $htbl = 'h' . strtolower($doc) . 'head';
        break;
    }

    $select = "
    select ifnull(rf.trno, 0) as trno,
    ifnull(date(rf.returndate_cust),'') as returndate_cust, 
    ifnull(rf.returndate_custby, '') as returndate_custby,ifnull(date(dateclose),'') as dateclose
    ";

    $qry = "" . $select . "
      from " . $tbl . " as rf
      left join client on client.clientid = rf.supplierid
      where rf.trno = ?
      union all
      " . $select . "
      from " . $htbl . "  as rf
      left join client on client.clientid = rf.supplierid
      where rf.trno = ?";

    $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);

    if (empty($data)) {
      $data = $this->coreFunctions->opentable("
        select 
        '" . $trno . "' as trno, 
        '' as returndate_cust, 
        '' as returndate_custby,'' as dateclose, 
        ");
    }
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
    $isposted = $this->othersClass->isposted2($trno, "transnum");

    $msg = '';

    $qry = "
    select dateclose as value from rfhead where trno = ? 
    union all
    select dateclose as value from hrfhead where trno = ? 
    LIMIT 1";

    if ($this->coreFunctions->datareader($qry, [$trno, $trno])) {
      $config['params']['clientid'] = $trno;
      $txtdata = $this->paramsdata($config);
      return ['status' => false, 'msg' => 'Already close date', 'data' => [], 'txtdata' => $txtdata];
    }

    switch ($config['params']['action2']) {
      case 'close':
        $data = [
          'trno' => $trno,
          'dateclose' => $this->othersClass->sanitizekeyfield('dateclose', $config['params']['dataparams']['dateclose']),
        ];
        $msg = 'The transaction has been closed.';

        break;

      default;
        $data = [
          'trno' => $trno,
          'returndate_cust' =>  $config['params']['dataparams']['returndate_cust'],
          'returndate_custby' => $config['params']['dataparams']['returndate_custby']
        ];

        break;
    }
    $qry = "
        select trno as value 
        from rfhead where trno = ? 
        union all
        select trno as value 
        from hrfhead where trno = ? 
        LIMIT 1";
    $count = $this->coreFunctions->datareader($qry, [$trno, $trno]);

    $table = $isposted == false ? "rfhead" : "hrfhead";

    if ($count != '') {
      $this->coreFunctions->sbcupdate($table, $data, ['trno' => $trno]);
    } else {
      $this->coreFunctions->insertGetId($table, $data);
    }
    return ['status' => true, 'msg' => 'Successfully saved.', 'data' => []];
  }
}
