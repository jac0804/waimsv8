<?php

namespace App\Http\Classes\modules\kwhmonitoring;

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



class kwhratesetup
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  private $logger;
  public $modulename = 'Rate Setup';
  public $gridname = 'entrygrid';
  public $table = 'ratesetup';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  public $style = 'width:100%;max-width:100%;';
  public $issearchshow = false;
  public $showclosebtn = false;

  public function __construct()
  {
    $this->btnClass = new buttonClass;
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 4123,
      'edit' => 4123,
      'save' => 4123,
      'saveallentry' => 4123,
      'deleteitem' => 4140
    );
    return $attrib;
  }


  public function createHeadbutton($config)
  {
    $btns = []; //actionload - sample of adding button in header - align with form/module name
    $buttons = $this->btnClass->create($btns);
    return $buttons;
  }

  public function createTab($config)
  {
    $action = 0;
    $dateid = 1;
    $basicrate = 2;
    $createby  = 3;
    $createdate = 4;
    $itemname = 5;

    $tab = [$this->gridname => ['gridcolumns' => ['action', 'dateid', 'basicrate', 'createby', 'createdate', 'itemname']]];
    $stockbuttons = ['delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['descriptionrow'] = [];

    $obj[0][$this->gridname]['columns'][$dateid]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$basicrate]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$createby]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$createdate]['type'] = "label";

    $obj[0][$this->gridname]['columns'][$basicrate]['label'] = "Rate";

    $obj[0][$this->gridname]['columns'][$createdate]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$createby]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['lblsource', 'dateid', 'rate', 'create'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'create.action', 'add');
    data_set($col1, 'create.label', 'Add Rate');
    data_set($col1, 'dateid.readonly', false);
    data_set($col1, 'lblsource.label', 'SETUP');

    $fields = ['lblsource', 'start', 'end', 'refresh'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'refresh.action', 'load');
    data_set($col3, 'refresh.label', 'View Rates');
    data_set($col3, 'lblsource.label', 'VIEW RATES');

    $fields = [];
    $col2 = $this->fieldClass->create($fields);

    $fields = [];
    $col4 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function paramsdata($config)
  {
    $data = $this->coreFunctions->opentable("
    select date_format(curdate(),'%Y-%m-%d') as dateid, date_format(DATE_ADD(curdate(), INTERVAL -30 DAY),'%Y-%m-%d') as start, date_format(curdate(),'%Y-%m-%d') as end, '0.00' as rate, '" . $config['params']['doc'] . "' as doc");
    if (!empty($data)) {
      return $data[0];
    } else {
      return [];
    }
  }

  public function data($config)
  {
    return $this->paramsdata($config);
  }

  public function headtablestatus($config)
  {
    // should return action
    $action = $config['params']["action2"];

    switch ($action) {
      case "add":
        return $this->addrate($config);
        break;

      case "load":
        return $this->loaddetails($config);
        break;

      default:
        return ['status' => false, 'msg' => 'Data is not yet setup in the headtablestatus.'];
        break;
    }
  }

  public function stockstatus($config)
  {

    // should return action
    $action = $config['params']["action"];

    switch ($action) {
      case "deleteitem":
        return $this->delete($config);
        break;

      default:
        return ['status' => false, 'msg' => 'Data is not yet setup in the stockstatus.'];
        break;
    }
  }

  private function addrate($config)
  {
    $rate = $config['params']['dataparams']['rate'];

    if (!is_numeric($rate)) {
      return ['status' => false, 'msg' => 'Incorrect rate format', 'data' => []];
    } else {
      if (floatval($rate) == 0) {
        return ['status' => false, 'msg' => 'Please input valid rate', 'data' => []];
      }
    }

    $exists = $this->coreFunctions->datareader("select dateid as value from ratesetup where date(dateid)=?", [$this->othersClass->sbcdateformat($config['params']['dataparams']['dateid'])]);
    if ($exists != '') {
      return ['status' => false, 'msg' => 'Unable to add rate, there is an existing rate for date ' . $config['params']['dataparams']['dateid'], 'data' => []];
    }

    $head = [
      'dateid' => $config['params']['dataparams']['dateid'],
      'basicrate' => $rate,
      'doc' =>  $config['params']['doc'],
      'remarks' => '',
      'createby' => $config['params']['user'],
      'createdate' => $this->othersClass->getCurrentTimeStamp()
    ];

    foreach ($head as $key => $value) {
      $head[$key] = $this->othersClass->sanitizekeyfield($key, $value);
    }

    $msg = 'Successfully loaded.';
    $trno = $this->coreFunctions->insertGetId("ratesetup", $head);
    if ($trno != 0) {
      $this->logger->sbcmasterlog($trno, $config, ' ADD RATE: ' . $config['params']['dataparams']['dateid'] .  " - " . number_format($head['basicrate'], 2));

      $sql = "update pwhead as h left join pwstock as s on s.trno=h.trno set s.isamt=" . $head['basicrate'] . ", s.amt=" . $head['basicrate'] . " where h.dateid='" . $head['dateid'] . "'; ";
      $this->coreFunctions->execqry($sql);

      $sql = "update hpwhead as h left join hpwstock as s on s.trno=h.trno set s.isamt=" . $head['basicrate'] . ", s.amt=" . $head['basicrate'] . " where h.dateid='" . $head['dateid'] . "'; ";
      $this->coreFunctions->execqry($sql);
    } else {
      $msg = 'Failed to add rate';
    }

    $data = $this->getratesetup($config);

    return ['status' => true, 'msg' => $msg, 'action' => 'load', 'griddata' => ['entrygrid' => $data]];
  }

  private function loaddetails($config)
  {
    $data = $this->getratesetup($config);
    return ['status' => true, 'msg' => 'Successfully loaded.', 'action' => 'load', 'griddata' => ['entrygrid' => $data]];
  }


  private function getratesetup($config)
  {
    $action = $config['params']["action2"];
    $filter = '';
    if ($action == 'load') {
      $filter = " and date(dateid) between '" . $config['params']['dataparams']['start'] . "' and '" . $config['params']['dataparams']['end'] . "'";
    }
    $qry = "select trno, date(dateid) as dateid, basicrate, createby, createdate, '' as itemname from ratesetup where doc='" . $config['params']['doc'] . "' " . $filter . " order by trno desc";
    return $this->coreFunctions->opentable($qry);
  }

  public function delete($config)
  {
    $row = $config['params']['row'];

    $qry = "delete from " . $this->table . " where trno=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['trno']]);
    $this->logger->sbcdelmaster_log($row['trno'], $config, 'REMOVE RATE - ' . $row['basicrate']);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }
} //end class
