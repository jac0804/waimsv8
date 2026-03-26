<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class tenant_profile_tab
{
  private $fieldClass;
  private $tabClass;
  private $coreFunctions;
  private $companysetup;
  private $othersClass;
  private $warehousinglookup;

  public $modulename = 'PROFILE';
  public $gridname = 'tableentry';
  private $fields = [
    'leaserate', 'acrate', 'cusarate', 'billtype', 'rentcat', 'mcharge', 'percentsales',
    'tenanttype', 'emulti', 'elecrate', 'penalty', 'eratecat', 'wratecat', 'classification', 'selecrate',
    'wmulti', 'waterrate'
  ];
  private $table = 'tenantinfo';

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
    $attrib = array('load' => 22, 'edit' => 23);
    return $attrib;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];
    $doc = $config['params']['doc'];

    $fields = [
      'leaserate', 'acrate', 'cusarate', 'tenanttype', 'area', 'emulti',
      'emeter', 'elecrate', 'refresh'
    ];

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'area.label', 'SQM');
    data_set($col1, 'area.type', 'input');
    data_set($col1, 'area.readonly', true);
    data_set($col1, 'emeter.readonly', true);

    data_set($col1, 'refresh.label', 'SAVE');

    $fields = ['billtype', 'rentcat', 'mcharge', 'percentsales', 'msales', 'semulti', 'semeter', 'selecrate'];

    $col2 = $this->fieldClass->create($fields);

    $fields = ['penalty', 'eratecatname', 'wratecatname', 'classification', 'wmulti', 'wmeter', 'waterrate'];

    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'penalty.label', 'Penalty Percentage');
    data_set($col3, 'penalty.class', 'cspenalty');
    data_set($col3, 'penalty.readonly', false);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    $data = $this->getheaddata($config);
    if (empty($data)) {
      $data = $this->coreFunctions->opentable("
        select
          '0' as leaserate,
          '0' as acrate,
          '0' as cusarate,
          '' as billtype,
          '' as rentcat,
          '0' as mcharge,
          '0' as percentsales,
          '' as tenanttype,
          '0' as penalty,
          '0' as eratecat,
          '' as eratecatname,
          '0' as wratecat,
          '' as wratecatname,
          '' as msales,
          '' as classification,
          '1' as emulti,
          '0' as elecrate,
          '1' as semulti,
          '0' as selecrate,
          '1' as wmulti,
          '0' as waterrate
      ");
    }

    return $data;
  }

  public function getheaddata($config)
  {
    $companyid = $config['params']['companyid'];
    $doc = $config['params']['doc'];
    $clientid = $config['params']['clientid'];

    $qry = "select 
    tinfo.clientid, tinfo.leaserate, tinfo.acrate, tinfo.cusarate, tinfo.drent, tinfo.dac, tinfo.dcusa, tinfo.billtype,
    tinfo.rentcat, tinfo.emulti, tinfo.semulti, tinfo.wmulti, tinfo.penalty, tinfo.mcharge, tinfo.percentsales,
    tinfo.msales, tinfo.elecrate, tinfo.selecrate, tinfo.waterrate, tinfo.classification, tinfo.eratecat,
    tinfo.wratecat, tinfo.secdep, tinfo.secdepmos, tinfo.ewcharges, tinfo.concharges, tinfo.fencecharge,
    tinfo.powercharges, tinfo.watercharges, tinfo.housekeeping, tinfo.docstamp, tinfo.consbond, tinfo.emeterdep, tinfo.servicedep, tinfo.rem,
    tinfo.tenanttype, loc.area, loc.emeter, loc.semeter, loc.wmeter,
    elect.category as eratecatname, water.category as wratecatname
    from tenantinfo as tinfo
    left join client as cl on cl.clientid = tinfo.clientid
    left join loc as loc on loc.line = cl.locid
    left join ratecategory as elect on elect.line = tinfo.eratecat
    left join ratecategory as water on water.line = tinfo.wratecat
    where tinfo.clientid=?";

    return $this->coreFunctions->opentable($qry, [$clientid]);
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
    $clientid  = $config['params']['clientid'];

    $data = [];
    foreach ($this->fields as $fieldname) {
      $data[$fieldname] = $this->othersClass->sanitizekeyfield($fieldname, $config['params']['dataparams'][$fieldname]);
    }
    $data['clientid'] = $clientid;

    if ($data['billtype'] != "Daily") {
      $data['mcharge'] = 0;
    }

    if ($data['rentcat'] != "% of Sales" && $data['rentcat'] != "Monthly Rent + % of Sales") {
      $data['percentsales'] = 0;
    } else {
      if ($data['percentsales'] == 0) {
        return ['status' => false, 'msg' => 'Lease rate percentage is required.', 'data' => []];
      }
    }


    $checking = $this->coreFunctions->datareader("select clientid as value from " . $this->table . " where clientid = ? limit 1", [$clientid]);
    if ($checking == "") {
      // insert
      $this->coreFunctions->sbcinsert($this->table, $data);
    } else {
      // update
      unset($data['clientid']);
      $this->coreFunctions->sbcupdate($this->table, $data, ['clientid' => $clientid]);
    }

    $loaddata = $this->getheaddata($config);
    return ['status' => true, 'msg' => 'Successfully saved.', 'data' => $loaddata];
  }
}
