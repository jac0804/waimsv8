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
use App\Http\Classes\common\commonsbc;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;

use Carbon\Carbon;

class leavebatchcreation
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'LEAVE BATCH CREATION';
  public $gridname = 'entrygrid';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $commonsbc;
  private $logger;
  public $style = 'width:100%;max-width:100%;';
  private $fields = [];
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
    $this->commonsbc = new commonsbc;
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = array(
      'load' => 2797,
      'view' => 2802,
      'edititem' => 2803,
      // 'new' => 24,
      'save' => 2799,
      'saveallentry' => 2799,
      // 'change' => 26,
      // 'delete' => 27,
      'print' => 2801
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $tab = [];

    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

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
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    //$obj[0]
    return $obj;
  }

  public function createHeadField($config)
  {

    $companyid = $config['params']['companyid'];

    switch ($companyid) {
      case 58; //cdo
        $fields = ['acno', 'acnoname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'acnoname.action', 'lookuppacno');
        data_set($col1, 'acnoname.lookupclass', 'lookuppacno');
        data_set($col1, 'acnoname.readonly', true);
        data_set($col1, 'acnoname.class', 'csacnoname sbccsreadonly');

        $fields = ['year', ['isconvert', 'isnopay']];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'isconvert.label', 'Convertible to Cash');
        break;

      default:
        $fields = ['acno', 'acnoname', 'tpaygroup',  'paymodeemp'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'acnoname.action', 'lookuppacno');
        data_set($col1, 'acnoname.lookupclass', 'lookuppacno');
        data_set($col1, 'acnoname.readonly', true);
        data_set($col1, 'acnoname.class', 'csacnoname sbccsreadonly');

        $fields = ['year', 'days', 'isnopay'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'days.type', 'cinput');
        data_set($col2, 'days.label', 'Entitled (Hours)');
        break;
    }

    $fields = ['refresh'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'refresh.action', 'load');
    data_set($col3, 'refresh.label', 'create / update');

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {

    $data = $this->coreFunctions->opentable("
      select 
      0 as acnoid,
      '' as acno,
      '' as acnoname,
      '' as tpaygroup,
      '' as paygroupid,
      '' as paymodeemp,
      '0' as days,
      left(now(), 4) as year,
      left(now(), 10) as prdstart,
      left(now(), 10) as prdend,
      '0' as isnopay,
      '0' as isconvert
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
        switch ($config['params']['companyid']) {
          case 58:
            return $this->loadData_CDO($config);
            break;
          default:
            return $this->loadData($config);
            break;
        }

        break;

      // case 'saveallentry':
      // case "update":
      //     return $this->saveentry($config);
      // break;

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
  }

  private function loadData($config)
  {
    $acnoid = $config['params']['dataparams']['acnoid'];
    $acno = $config['params']['dataparams']['acno'];
    $year = (int) $config['params']['dataparams']['year'];
    $days = (int) $config['params']['dataparams']['days'];
    $paymode = $config['params']['dataparams']['paymode'];
    $paygroup = $config['params']['dataparams']['paygroupid'];
    $isnopay = $config['params']['dataparams']['isnopay'];
    // $prdend = $config['params']['dataparams']['prdend'];
    // $prdstart = $config['params']['dataparams']['prdstart'];
    $leavebatch = $config['params']['dataparams']['acno'] . '-' . $year;

    $user = isset($config['params']['user']) ? $config['params']['user'] : '';

    if ($acnoid == 0) {
      return ['status' => false, 'msg' => 'Select valid leave account', 'action' => 'load', 'griddata' => ['entrygrid' => []]];
    }

    // if ($paygroup == '') {
    //   return ['status' => false, 'msg' => 'Please select valid Pay Group', 'action' => 'load'];
    // }

    $filtergrpup = '';
    if ($paygroup != '') {
      $filtergrpup = " and paygroup = '" . $paygroup . "'";
    }

    if ($paymode == '') {
      return ['status' => false, 'msg' => 'Please select valid Mode of Payment', 'action' => 'load'];
    }

    if ($days == 0) {
      return ['status' => false, 'msg' => 'Please input valid entitled hours', 'action' => 'load'];
    }

    $leavestart = $year . '-' . date('m-d', strtotime($this->companysetup->leavestart));
    $leaveend = $year . '-' . date('m-d', strtotime($this->companysetup->leaveend));

    $nopaylog = ' (with pay)';
    if ($isnopay == 1) {
      $nopaylog = ' (w/out pay)';
    }

    $this->logger->sbcmasterlog2(0, $config, 'Generate Leave ' . $acno . '. From ' . $leavestart . ' to ' . $leaveend . $nopaylog, 'masterfile_log', 1);

    $data = [
      'dateid' => date('Y-m-d'),
      'acnoid' => $acnoid,
      'days' => $days,
      'bal' => 0,
      'prdstart' => $leavestart,
      'prdend' => $leaveend,
      'leavebatch' => $leavebatch,
      'isnopay' => $isnopay,
      'editby' => $user,
      'editdate' => $this->othersClass->getCurrentTimeStamp()
    ];

    $qry = "
      select empid, paymode, paygroup
      from employee
      where paymode = '" . substr($paymode, 0, 1) . "' and isactive=1 " . $filtergrpup;
    $employee_data = $this->coreFunctions->opentable($qry);

    if (!empty($employee_data)) {
      foreach ($employee_data as $key => $value) {
        $data['empid'] = $value->empid;

        $bal = 0;
        $entitled = 0;

        if ($isnopay) {
          $bal = $days;
          $entitled = $days;
        } else {
          $now = $this->othersClass->getCurrentDate();

          if ($now > $leavestart) {
            // $leavestart = Carbon::parse($leavestart);
            // $now = Carbon::parse($now);

            // $entitled = (int) $leavestart->diffInMonths($now, false);

            $entitled = $this->commonsbc->sbcdiffInMonthsInt($leavestart, $now);

            if ($entitled < $days) {
              $bal = $entitled * 8;
            }
            if ($bal > $days) {
              $bal = $days;
            }
          }
        }

        if ($days > $entitled) {
          $data['days'] = ($entitled * 8);
        } else {
          $data['days'] = $days;
        }

        $exists = $this->coreFunctions->getfieldvalue("leavesetup", "trno", "empid=? and leavebatch=?", [$value->empid, $leavebatch]);
        if ($exists) {
          $this->othersClass->logConsole("trno: " . $exists);

          $applied = $this->coreFunctions->datareader("select ifnull(sum(adays),0) as value from leavetrans where trno=? and status='A'", [$exists]);
          if ($applied == '') {
            $applied = 0;
          }

          $this->othersClass->logConsole('Applied: ' . $applied);

          $data['bal'] = $bal - $applied;

          $this->othersClass->logConsole('bal: ' . $data['bal']);

          $data['prdstart'] = $leavestart;
          $data['prdend'] = $leaveend;
          $this->coreFunctions->sbcupdate('leavesetup', $data, ['trno' => $exists]);
          $this->voidprevious($data['empid'],  $data['dateid']);
        } else {
          if ($isnopay) {
            $data['bal'] = $bal;
          }
          $data['docno'] = $this->getlastDocno($config);
          $this->coreFunctions->sbcinsert('leavesetup', $data);
          $this->voidprevious($data['empid'],  $data['dateid']);
        }
      }
    }

    return ['status' => true, 'msg' => 'Successfully created', 'action' => 'load', 'griddata' => ['entrygrid' => []]];
  }

  private function loadData_CDO($config)
  {
    $acnoid = $config['params']['dataparams']['acnoid'];
    $year = (int)$config['params']['dataparams']['year'];
    $isnopay = $config['params']['dataparams']['isnopay'];
    $isconvert = $config['params']['dataparams']['isconvert'];
    $leavebatch = $config['params']['dataparams']['acno'] . '-' . $year;

    if ($acnoid == 0) {
      return ['status' => false, 'msg' => 'Select valid leave account', 'action' => 'load', 'griddata' => ['entrygrid' => []]];
    }

    if ($year == 0) {
      return ['status' => false, 'msg' => 'Please input valid year', 'action' => 'load', 'griddata' => ['entrygrid' => []]];
    }

    $leavestart = $year . '-' . date('m-d', strtotime($this->companysetup->leavestart));
    $leaveend = $year . '-' . date('m-d', strtotime($this->companysetup->leaveend));

    $curdate = $this->othersClass->getCurrentDate();

    $data = [
      'dateid' => date('Y-m-d'),
      'acnoid' => $acnoid,
      'days' => 0,
      'bal' => 0,
      'prdstart' => $leavestart,
      'prdend' => $leaveend,
      'leavebatch' => $leavebatch,
      'isnopay' => $isnopay,
      'isconvert' => $isconvert
    ];

    $qry = "select empid, hired, TIMESTAMPDIFF(YEAR, hired, '" . $curdate . "') AS yrs from employee where isactive=1 and hired is not null";
    $employee_data = $this->coreFunctions->opentable($qry);

    if (!empty($employee_data)) {
      foreach ($employee_data as $key => $value) {
        $data['empid'] = $value->empid;

        $days = 0;

        $this->othersClass->logConsole('yrs: ' . $value->yrs);

        if ($isconvert) {
          if ($value->yrs >= 1) {
            $days = 5;
          }
        } else {
          if ($value->yrs >= 6 && $value->yrs <= 8) {
            $days = 3;
          } else if ($value->yrs >= 9 && $value->yrs <= 11) {
            $days = 6;
          } else if ($value->yrs >= 12 && $value->yrs <= 14) {
            $days = 9;
          } else if ($value->yrs >= 15 && $value->yrs <= 20) {
            $days = 12;
          } else if ($value->yrs >= 21) {
            $days = 15;
          }
        }

        $data['days'] = $days;
        $data['bal'] = $days;

        $exists = $this->coreFunctions->getfieldvalue("leavesetup", "trno", "empid=? and leavebatch=?", [$value->empid, $leavebatch]);
        if ($exists) {
          $this->othersClass->logConsole("trno: " . $exists);

          $applied = $this->coreFunctions->datareader("select ifnull(sum(adays),0) as value from leavetrans where trno=? and status='A'", [$exists]);
          if ($applied == '') {
            $applied = 0;
          }

          $this->othersClass->logConsole('Applied: ' . $applied);

          $data['bal'] = $days - $applied;

          $this->othersClass->logConsole('bal: ' . $data['bal']);

          $data['prdstart'] = $leavestart;
          $data['prdend'] = $leaveend;
          $this->coreFunctions->sbcupdate('leavesetup', $data, ['trno' => $exists]);
          $this->voidprevious($data['empid'],  $data['dateid']);
        } else {
          $data['docno'] = $this->getlastDocno($config);
          $this->coreFunctions->sbcinsert('leavesetup', $data);
          $this->voidprevious($data['empid'],  $data['dateid']);
        }
      }
    }

    return ['status' => true, 'msg' => 'Successfully created', 'action' => 'load', 'griddata' => ['entrygrid' => []]];
  }

  private function voidprevious($empid, $year)
  {
    $this->coreFunctions->execqry("update leavesetup set bal=0 where empid=" . $empid . " and bal<>0 and dateid<'" . $year . "'");
  }

  private function getlastDocno($config)
  {
    $docnolength = $this->companysetup->getdocumentlength($config['params']);
    $pref = app("App\Http\Classes\modules\payrollsetup\\leavesetup")->prefix;

    $length = strlen($pref);
    if ($length == 0) {
      $last = $this->coreFunctions->datareader('select docno as value from leavesetup order by trno desc limit 1');
    } else {
      $last = $this->coreFunctions->datareader('select docno as value from leavesetup where left(docno,?)=? order by trno desc limit 1', [$length, $pref]);
    }
    $start = $this->othersClass->SearchPosition($last);
    $seq = substr($last, $start) + 1;
    $poseq = $pref . $seq;
    $newdocno = $this->othersClass->PadJ($poseq, $docnolength);

    return $newdocno;
  }
} //end class
