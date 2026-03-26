<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class viewdeliverystatus
{
  private $fieldClass;
  private $tabClass;
  private $coreFunctions;
  private $companysetup;
  private $othersClass;
  private $warehousinglookup;

  public $modulename = 'Delivery Status';
  public $gridname = 'tableentry';
  private $fields = [];
  private $table = 'delstatus';

  public $tablelogs = 'table_log';
  public $tablelogs_del = 'del_table_log';

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
    $fields = ['modeofdelivery', 'driver', 'receiveby', 'receivedate', 'remarks', 'delcharge', 'refresh'];
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'driver.readonly', false);
    data_set($col1, 'receiveby.readonly', false);
    data_set($col1, 'receivedate.readonly', false);
    data_set($col1, 'remarks.readonly', false);
    data_set($col1, 'refresh.label', 'Save');
    data_set($col1, 'delcharge.label', 'Overall Expense');
    data_set($col1, 'delcharge.readonly', true);
    data_set($col1, 'delcharge.class', 'sbccsreadonly');

    $fields = ['couriername', 'trackingno',  'releasedate'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'trackingno.readonly', false);
    data_set($col2, 'releasedate.readonly', false);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    $trno = $config['params']['clientid'];
    $data = $this->getheaddata($config);

    if (empty($data)) {
      $data = $this->coreFunctions->opentable("
      select 
      '" . $trno . "' as trno, 
      '' as modeofdelivery, 
      '' as driver, 
      '' as receiveby, 
      '' as receivedate, 
      '' as remarks, 
      '' as couriername,
      '' as trackingno, 
      '' as releaseby,
      '' as releasedate,
      0 as delcharge");
    }

    return $data;
  }

  public function getheaddata($config)
  {
    $trno = $config['params']['clientid'];

    $qry = "select ifnull(trno,'') as trno,ifnull(modeofdelivery,'') as modeofdelivery,ifnull(driver,'') as driver,ifnull(receiveby,'') as receiveby,ifnull(receivedate,'') as receivedate,ifnull(remarks,'') as remarks,ifnull(couriername,'') as couriername,ifnull(trackingno,'') as trackingno,ifnull(releaseby,'') as releaseby,ifnull(releasedate,'') as releasedate, ifnull(delcharge,0) as delcharge  from delstatus
      where trno = ?";

    return $this->coreFunctions->opentable($qry, [$trno]);
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
    $tbl = "lahead";

    $isposted = $this->othersClass->isposted2($trno, "cntnum");

    if ($isposted) {
      $tbl = "glhead";
    }

    $duedate = $this->coreFunctions->getfieldvalue($tbl, "due", "trno=?", [$trno]);
    $terms = $this->coreFunctions->getfieldvalue($tbl, "terms", "trno=?", [$trno]);

    $data = [
      'trno' => $trno,
      'modeofdelivery' => $config['params']['dataparams']['modeofdelivery'],
      'driver' =>  $config['params']['dataparams']['driver'],
      'receiveby' => $config['params']['dataparams']['receiveby'],
      'remarks' => $config['params']['dataparams']['remarks'],
      'couriername' => $config['params']['dataparams']['couriername'],
      'trackingno' => $config['params']['dataparams']['trackingno'],
      'releaseby' => $config['params']['dataparams']['releaseby'],
      'delcharge' => $config['params']['dataparams']['delcharge']

    ];

    $due ='';

    if ($config['params']['dataparams']['receivedate'] != '') {
      $data['receivedate'] = $config['params']['dataparams']['receivedate'];
      $due = $this->othersClass->computeterms($data['receivedate'], $duedate, $terms);
    }

    if ($config['params']['dataparams']['releasedate'] != '') {
      $data['releasedate'] = $config['params']['dataparams']['releasedate'];
    }

    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    $qry = "select trno as value from delstatus where trno = ? LIMIT 1";
    $count = $this->coreFunctions->datareader($qry, [$trno]);

    if ($count != '') {
      $this->coreFunctions->sbcupdate("delstatus", $data, ['trno' => $trno]);
      if($due != ''){
        $this->coreFunctions->sbcupdate($tbl, ['due' => $due], ['trno' => $trno]);
      }
      
    } else {
      $this->coreFunctions->insertGetId("delstatus", $data);
      $receivedate_log = "";
      $releasedate_log = "";
      if ($config['params']['dataparams']['receivedate'] != '') {
        $receivedate_log = $config['params']['dataparams']['receivedate'];
        $due = $this->othersClass->computeterms($data['receivedate'], $duedate, $terms);
        $this->coreFunctions->sbcupdate($tbl, ['due' => $due], ['trno' => $trno]);
      }

      if ($config['params']['dataparams']['releasedate'] != '') {
        $releasedate_log = $config['params']['dataparams']['releasedate'];
      }
      $this->logger->sbcwritelog(
        $data['trno'],
        $config,
        'DELIVERY STATUS',
        ' MOP: ' . $data['modeofdelivery']
          . ', DRIVER: ' . $data['driver']
          . ', RECEIVED BY: ' . $data['receiveby']
          . ', REAMARKS: ' . $data['remarks']
          . ', COURIER: ' . $data['couriername']
          . ', TRACKING NUMBER: ' . $data['trackingno']
          . ', RELEASED BY: ' . $data['releaseby']
          . ', DELIVERY CHARGE: ' . $data['delcharge']
          . ', RECEIVED DATE: ' . $receivedate_log
          . ', RELEASED DATE: ' . $releasedate_log
      );
    }
    return ['status' => true, 'msg' => 'Successfully saved.', 'reloadhead' => true, 'trno' => $trno, 'data' => []];
  }
}
