<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class leaseprovision_depositcharge_tab
{
  private $fieldClass;
  private $tabClass;
  private $coreFunctions;
  private $companysetup;
  private $othersClass;
  private $warehousinglookup;

  public $modulename = 'DEPOSIT AND OTHER CHARGES';
  public $gridname = 'tableentry';


  //   Security Deposit - secdep

  // Sec. Deposit # of Months - secdepmos

  // Electric & Water Charges - ewcharges

  // Construction Charges - concharges

  // Est. Cost of Plywood Fencing - fencecharge

  // Est. Power Charges -powercharges

  // Est. Water Charges - watercharges

  // Housekeeping/Debris Hauling - housekeeping

  // Documentary Stamp Tax - docstamp

  // Construction bond - consbond

  // Electric Meter Deposit - emeterdep

  // Service Bill Deposit - servicedep

  // Remarks - rem

  private $fields = [
    'leaserate', 'acrate', 'cusarate', 'ewcharges', 'concharges', 'fencecharge', 'powercharges',
    'watercharges', 'housekeeping', 'docstamp', 'consbond', 'emeterdep', 'servicedep', 'secdep', 'rem', 'secdepmos'
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
      'leaserate', 'acrate', 'cusarate', 'ewcharges', 'concharges', 'fencecharge',
      'powercharges', 'watercharges', 'housekeeping', 'refresh'
    ];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'refresh.label', 'SAVE');

    $fields = ['secdep', 'secdepmos', 'docstamp', 'consbond', 'emeterdep', 'servicedep'];
    $col2 = $this->fieldClass->create($fields);

    $fields = ['rem'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'rem.readonly', false);

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
          '0' as ewcharges,
          '0' as concharges,
          '0' as fencecharge,
          '0' as powercharges,
          '0' as watercharges,
          '0' as housekeeping,
          '0' as docstamp,
          '0' as consbond,
          '0' as emeterdep,
          '0' as servicedep,
          '0' as secdep,
          '' as rem,
          '0' as secdepmos
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
    tinfo.clientid, tinfo.leaserate, tinfo.acrate, tinfo.cusarate, tinfo.ewcharges, tinfo.concharges, tinfo.fencecharge, tinfo.powercharges,
    tinfo.watercharges, tinfo.housekeeping, tinfo.docstamp, tinfo.consbond, tinfo.consbond, tinfo.emeterdep, tinfo.servicedep,
    tinfo.secdep, tinfo.rem, tinfo.secdepmos
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
