<?php

namespace App\Http\Classes\modules\payrollcustomform;

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
use App\Http\Classes\common\payrollcommon;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;

class payrollentry
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'PAYROLL ENTRY';
  public $gridname = 'entrygrid';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $payrollcommon;
  public $style = 'width:100%;max-width:100%;';
  private $fields = ['qty'];
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
    $this->payrollcommon = new payrollcommon;
  }

  public function getAttrib()
  {
    $attrib = array(
      'load' => 1650,
      'view' => 1651,
      'edititem' => 1354,
      // 'new' => 24,
      'save' => 1353,
      'saveallentry' => 1353,
      // 'change' => 26,
      // 'delete' => 27,
      'print' => 1356
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $companyid = $config['params']['companyid'];

    $tab = [
      $this->gridname => [
        'gridcolumns' => [
          'acno',
          'codename',
          'qty',
          'uom'
        ]
      ],
    ];

    if ($companyid == 30) {
      $tab['tableentry'] = ['action' => 'payrollentry', 'lookupclass' => 'entrytempdeduction', 'label' => 'DEDUCTIONS', 'addedparams' => ['empid', 'batch']];
    }

    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['label'] = 'ENTRY';
    $obj[0][$this->gridname]['descriptionrow'] = [];

    $obj[0][$this->gridname]['columns'][0]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][1]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][1]['label'] = 'Account Name';
    $obj[0][$this->gridname]['columns'][3]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][3]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][0]['style'] = 'width:20%;whiteSpace: normal;min-width:20%;font-size:12px;';
    $obj[0][$this->gridname]['columns'][1]['style'] = 'width:20%;whiteSpace: normal;min-width:20%;font-size:12px;';
    $obj[0][$this->gridname]['columns'][2]['style'] = 'width:20%;whiteSpace: normal;min-width:20%;font-size:12px;';
    $obj[0][$this->gridname]['columns'][3]['style'] = 'width:40%;whiteSpace: normal;min-width:40%;font-size:12px;';

    return $obj;
  }

  public function createHeadbutton($config)
  {
    $btns = []; //actionload - sample of adding button in header - align with form/module name
    $buttons = $this->btnClass->create($btns);
    return $buttons;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['saveallentry', 'viewpayrollsetupprocess'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    //$obj[0]
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = [['lastbatch', 'checkall'], ['start', 'end']];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'lastbatch.label', 'Batch');
    data_set($col1, 'lastbatch.name', 'batch');
    data_set($col1, 'lastbatch.action', 'lookuppayrollsetupbatch');
    data_set($col1, 'lastbatch.lookupclass', 'lookuppayrollsetupbatch');
    data_set($col1, 'checkall.label', 'All Employees');
    data_set($col1, 'start.name', 'startdate');
    data_set($col1, 'start.class', 'csstartdate sbccsreadonly');
    data_set($col1, 'end.name', 'enddate');
    data_set($col1, 'end.class', 'csend sbccsreadonly');

    $fields = ['empcode', 'empname'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'empcode.addedparams', ['checkall', 'batchid', 'batch']);

    $fields = [['cur', ''], 'withoutdeduction'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'cur.class', 'cscur');
    data_set($col3, 'cur.readonly', false);
    data_set($col3, 'cur.type', 'input');
    data_set($col3, 'cur.label', 'Other Non-Tax Earnings');

    $fields = ['refresh'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'refresh.action', 'load');

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function paramsdata($config)
  {

    $data = $this->coreFunctions->opentable("
      select 
      DATE_ADD(DATE_ADD(LAST_DAY(curdate()), INTERVAL 1 DAY), INTERVAL -1 MONTH) as startdate,
      adddate(DATE_ADD(DATE_ADD(LAST_DAY(curdate()), INTERVAL 1 DAY), INTERVAL -1 MONTH), 14) as enddate,
      '' as empcode,
      '' as empname,
      0 as empid,
      '' as cur,
      '' as paymode,
      '' as batch,
      0 as batchid,
      '0' as withoutdeduction,
      '0' as checkall,
      '' as fullwordpaymode,
      '' as tpaygroupname
    ");
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

  public function headtablestatus($config, $line = '')
  {

    $action = $config['params']["action2"];

    switch ($action) {
      case "load":
        return $this->loaddetails($config);
        break;

      case 'saveallentry':
      case "update":
        return $this->saveentry($config);
        break;

      case 'process':
        break;

      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }

  private function saveentry($config)
  {
    $data = $config['params']['rows'];
    foreach ($this->fields as $key => $value) {
      foreach ($data as $k => $v) {
        $data[$k][$value] = $this->othersClass->sanitizekeyfield($value, $data[$k][$value]);
      }
    }


    $arrEdit = [];
    $success = true;
    foreach ($data as $key => $value) {

      if ($value['bgcolor'] != '') {
        unset($value['bgcolor']);
        unset($value['psort']);
        array_push($arrEdit, $value);
      }

      unset($value['bgcolor']);
      unset($value['psort']);
      if ($this->coreFunctions->sbcupdate("timesheet", ['qty' => $value['qty']], ['empid' => $value['empid'], 'batchid' => $value['batchid'], 'acnoid' => $value['acnoid']]) == 1) {
      } else {
        $data[$key]['bgcolor'] = 'bg-red-2';
        $success = false;
      }
    }

    // $empid = $config['params']['dataparams']['empid'];
    // if($this->coreFunctions->datareader("select ismanualts as value from employee where empid=".$empid)){
    //   $config['params']['dataparams']['checkall'] = 0;
    // }

    $msg = '';
    $empid = $config['params']['dataparams']['empid'];
    $batch = $config['params']['dataparams']['batchid'];

    $user = $config['params']['user'];
    $batchcode = $config['params']['dataparams']['batch'];
    $start = $config['params']['dataparams']['startdate'];
    $end   = $config['params']['dataparams']['enddate'];

    $zerohrs = $this->coreFunctions->datareader("select ifnull(sum(t.qty),0) as value from timesheet as t where t.empid=? and t.batchid=?", [$empid, $batch], '', true);
    if ($zerohrs == 0) {
      $this->coreFunctions->execqry("delete from paytrancurrent where batchid=" . $batch . " and empid=" . $empid, "delete");
    } else {
      $config['params']['dataparams']['checkall'] = 0;

      switch ($config['params']['companyid']) {
        case 62: //onesky
          $paytran_success = $this->payrollcommon->computeemptimesheet_onesky($batch, $config['params']['dataparams']['enddate'], $empid, $start, $end, $user, $batchcode, $config['params'], true);
          if ($paytran_success['status']) {

            foreach ($arrEdit as $key2 => $value) {
              $this->coreFunctions->sbcupdate("timesheet", ['qty' => $arrEdit[$key2]['qty']], ['empid' => $arrEdit[$key2]['empid'], 'batchid' => $arrEdit[$key2]['batchid'], 'acnoid' => $arrEdit[$key2]['acnoid']]);
            }

            goto computepaytranhere;
          }
          break;
        default:
          computepaytranhere:
          $paytran_success = $this->payrollcommon->generatePayTranCurrent($config);
          break;
      }

      if (!$paytran_success['status']) {
        $this->coreFunctions->sbclogger("failed generatePayTranCurrent - " . $paytran_success['msg']);
        $msg = $paytran_success['msg'];
      }
    }
    return $this->loaddetails($config, $msg);
  }

  private function loaddetails($config, $custommsg = '')
  {
    $msg = '';
    if ($custommsg != '') {
      $msg = $custommsg;
    }
    $this->manualEntry = false;
    $empid = $config['params']['dataparams']['empid'];
    $empcode = $config['params']['dataparams']['empcode'];
    $batch = $config['params']['dataparams']['batchid'];
    $paymode = $config['params']['dataparams']['paymode'];
    $start = $config['params']['dataparams']['startdate'];
    $end   = $config['params']['dataparams']['enddate'];
    $others = $config['params']['dataparams']['cur'];

    if ($empid == 0) {
      return ['status' => false, 'msg' => 'Invalid Employee', 'data' => []];
    }

    $check = $this->checkTimesheet($config);
    if (!$check['status']) {
      return ['status' => false, 'msg' => $check['msg'], 'data' => []];
    }

    $qry = "
      select '1' as psort, pac.seq, date(ts.dateid) as dateid, pac.code as acno, pac.codename, ts.qty, ts.uom, ts.line, '' as 'bgcolor', ts.empid, ts.batchid, ts.acnoid
      from timesheet as ts left join paccount as pac on pac.line=ts.acnoid
      where ts.batchid=" . $batch . " and ts.empid=" . $empid . " and ts.qty<>0 
      union all
      select '2' as psort, pac.seq, date(ts.dateid) as dateid, pac.code as acno, pac.codename, ts.qty, ts.uom, ts.line, '' as 'bgcolor', ts.empid, ts.batchid, ts.acnoid
      from timesheet as ts left join paccount as pac on pac.line=ts.acnoid
      where ts.batchid=" . $batch . " and ts.empid=" . $empid . " and ts.qty=0 
      order by psort, seq";

    $data = $this->coreFunctions->opentable($qry);

    $others = $this->othersClass->val($others);
    if ($others != 0) {
      recomputepaytranhere:
      $config['params']['dataparams']['checkall'] = 0;
      $config['params']['dataparams']['cur'] = $others;
      $paytran_success = $this->payrollcommon->generatePayTranCurrent($config);
      if (!$paytran_success['status']) {
        $this->coreFunctions->sbclogger("failed generatePayTranCurrent - " . $paytran_success['msg']);
        $msg .= " " . $paytran_success['msg'];
      }
    } else {
      $ManualOtherEarnId = $this->coreFunctions->datareader("select line as value from paccount where code='PT91'");
      if ($ManualOtherEarnId == '') {
        $ManualOtherEarnId = 0;
      } else {
        $otherexist = $this->coreFunctions->opentable("select empid from paytrancurrent where empid=? and batchid=? and acnoid=?", [$empid, $batch, $ManualOtherEarnId]);
        if (!empty($otherexist)) {
          goto recomputepaytranhere;
        }
      }
    }

    if ($msg == '') {
      $msg = 'Successfully loaded.';
    }

    return ['status' => true, 'msg' => $msg, 'action' => 'load', 'griddata' => ['entrygrid' => $data]];
  }


  private function checkTimesheet($config)
  {
    $empid = $config['params']['dataparams']['empid'];
    $batch = $config['params']['dataparams']['batchid'];
    $batchdate = $config['params']['dataparams']['batchdate'];
    $start = $config['params']['dataparams']['startdate'];
    $end = $config['params']['dataparams']['enddate'];
    $empname = $config['params']['dataparams']['empname'];
    $user = $config['params']['user'];

    $qry = "select ifnull(count(*),0) as value from timecard where empid=" . $empid . " and dateid between date('" . $start . "') and date('" . $end . "')";
    if ($this->coreFunctions->datareader($qry) == 0) {
      $this->coreFunctions->execqry("update employee set ismanualts=1 where empid=" . $empid, "update");
    }

    $qry = "select ifnull(count(*),0) as value from timesheet where batchid=" . $batch . " and empid=" . $empid;
    if ($this->coreFunctions->datareader($qry) == 0) {
      // $result =  $this->payrollcommon->computeemptimesheet($batch, $batchdate, $empid, $start, $end, $user);

      $qry = "select " . $batch . " as batchid, " . $empid . " as empid, line as acnoid, '" . $batchdate . "' as dateid,
                alias, uom, qty as multiplier, code, seq from paccount where `type` = 'MDS' order by seq";
      $timesheet = $this->coreFunctions->opentable($qry);

      foreach ($timesheet as $key =>  $val) {
        $qty = 0;
        $this->payrollcommon->addTimeSheetAccount($empid, $batch, $val->acnoid, $val->dateid, $val->uom, $val->seq, $qty, $user, 0);
      }

      // if (!$result['status']) {
      //   $msg =  $empname . " failed. " . $result['msg'] . "...";
      //   return ['status' => false, 'msg' => $msg];
      // }
    }

    return ['status' => true, 'msg' => ''];
  }
} //end class
