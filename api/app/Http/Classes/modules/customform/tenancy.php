<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class tenancy
{
  private $fieldClass;
  private $tabClass;
  private $coreFunctions;
  private $companysetup;
  private $othersClass;
  private $warehousinglookup;

  public $modulename = 'Update Tenancy Status';
  public $gridname = 'tableentry';

  private $fields = [
    'status', 'effectdate', 'datefrom', 'dateto', 'clientid', 'monthsno', 'rem'
  ];
  private $table = 'tenancystatus';

  public $tablelogs = 'client_log';

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
    $attrib = array('load' => 4216, 'edit' => 4217, 'save' => 4217);
    return $attrib;
  }

  public function createHeadField($config)
  {
    $fields = ['status', 'monthsno', 'effectdate'];

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'monthsno.label', 'No. of Months');
    data_set($col1, 'effectdate.readonly', false);
    data_set($col1, 'status.type', 'lookup');
    data_set($col1, 'status.action', 'lookuprandom');
    data_set($col1, 'status.lookupclass', 'lookuptstatus');

    $fields = ['start', 'end', 'rem', 'refresh'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'start.name', 'datefrom');
    data_set($col2, 'end.name', 'dateto');
    data_set($col2, 'rem.readonly', false);
    data_set($col2, 'refresh.action', 'load');
    data_set($col2, 'refresh.label', 'Save');


    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    $data = $this->coreFunctions->opentable("
      select '' as status, '' as clientname, '' as client, '0' as clientid,null as effectdate,0 as monthsno,null as datefrom,null as dateto,'' as rem ");

    return $data;
  }

  public function getheaddata($config)
  {
    $companyid = $config['params']['companyid'];
    $doc = $config['params']['doc'];
    $clientid = $config['params']['clientid'];

    $qry = "select cl.client,cl.clientname,ts.status,ts.effectdate,ts.effectdate as dateeffect,ts.monthsno,ts.monthsno as months,ts.datefrom,ts.dateto,ts.datefrom as start,ts.dateto as end,ts.rem from tenancystatus as ts left join client as cl on ts.clientid = cl.clientid where cl.clientid = ?";

    return $this->coreFunctions->opentable($qry, [$clientid]);
  }

  public function data()
  {
    return [];
  }

  public function createTab($config)
  {
    $tab = [
      'tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrytenancy', 'label' => 'LIST']
    ];
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
    $clientid  = $config['params']['clientid'];


    $data = [];
    foreach ($this->fields as $fieldname) {
      $data[$fieldname] = $this->othersClass->sanitizekeyfield($fieldname, $config['params']['dataparams'][$fieldname]);
    }
    $data['clientid'] = $clientid;

    if ($data['status'] != '') {
      $this->coreFunctions->sbcinsert($this->table, $data);
    }

    $loaddata = $this->paramsdata($config);
    $hdata = $this->getheaddata($config);
    return ['status' => true, 'msg' => 'Successfully saved.', 'data' => $hdata, 'txtdata' => $loaddata, 'reloadtableentry' => true];
  }
}
