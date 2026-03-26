<?php

namespace App\Http\Classes\modules\payrollcustomform;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;
use Exception;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\common\payrollcommon;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;

use Carbon\Carbon;

class payrollprocess
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'PAYROLL PROCESS';
  public $gridname = 'entrygrid';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $payrollcommon;
  private $logger;
  private $btnClass;
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
    $this->payrollcommon = new payrollcommon;
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 2481,
      'edit' => 2484,
      'create' => 1359,
      'postinout' => 1360
    );
    return $attrib;
  }

  public function createHeadbutton($config)
  {
    $timekeeping = $this->companysetup->istimekeeping($config['params']);
    if ($timekeeping) {
      $this->modulename = 'TIMEKEEPING PROCESS';
    }
    $btns = [];
    $buttons = $this->btnClass->create($btns);
    return $buttons;
  }

  public function createTab($config)
  {
    $companyid = $config['params']['companyid'];
    $timekeeping = $this->companysetup->istimekeeping($config['params']);

    $fields = ['lastbatch', ['sdate1', 'sdate2'], 'tpaygroupname', ['paymode', 'fullwordpaymode'], 'computetimesheet', 'blpayrollentry'];

    switch ($companyid) {
      case 58: //cdo
        $fields = ['lastbatch', ['sdate1', 'sdate2'], "divname", "branchname", "tpaygroupname", ['paymode', 'fullwordpaymode'], 'computetimesheet', 'blpayrollentry'];
        break;
    }

    if ($timekeeping) {
      $fields = [];
    }

    $col1 = $this->fieldClass->create($fields);
    if (!$timekeeping) {
      data_set($col1, 'lastbatch.label', 'Batch');
      data_set($col1, 'lastbatch.name', 'batch');
      data_set($col1, 'lastbatch.action', 'lookuppayrollsetupbatch');
      data_set($col1, 'lastbatch.lookupclass', 'lookuppayrollsetupbatch');
      data_set($col1, 'tpaygroupname.label', 'Pay Group');
      data_set($col1, 'tpaygroupname.type', 'input');
      data_set($col1, 'paymode.type', 'input');
      data_set($col1, 'computetimesheet.style', 'width:100%');
      data_set($col1, 'blpayrollentry.style', 'width:100%;height:100%; text-decoration: underline');
      data_set($col1, 'sdate1.readonly', true);
      data_set($col1, 'sdate2.readonly', true);
    }

    if ($companyid == 58) { //cdo
      data_set($col1, 'branchname.type', 'input');
      data_set($col1, 'divname.label', 'Company');
    }

    $fields = ['dlexcelmbtctxtfile'];
    // if ($companyid == 58) { //cdo
    //   array_push($fields, 'dlexcelbpitxtfile');
    // }
    if ($timekeeping) {
      $fields = [];
    }
    $col2 = $this->fieldClass->create($fields);
    if (!$timekeeping) {
      data_set($col2, 'dlexcelmbtctxtfile.style', 'width:100%');
    }

    $fields = [];
    $col3 = $this->fieldClass->create($fields);

    $fields = ['payrollclosing', 'payrollunclosing'];
    if ($timekeeping) {
      $fields = [];
    }
    $col4 = $this->fieldClass->create($fields);
    if (!$timekeeping) {
      data_set($col4, 'payrollclosing.style', 'width:100%');
      data_set($col4, 'payrollunclosing.style', 'width:100%');
    }

    $fields = ['lblrem', ['start', 'end'], ['create', 'postinout'], 'computetimecard'];
    $col5 = $this->fieldClass->create($fields);
    data_set($col5, 'lblrem.label', 'Date Period:');
    data_set($col5, 'lblrem.style', 'font-weight:bold;font-size:15px');
    data_set($col5, 'start.name', 'startdate');
    data_set($col5, 'end.name', 'enddate');
    data_set($col5, 'postinout.style', 'width:100%');
    data_set($col5, 'create.style', 'width:100%');
    data_set($col5, 'create.label', 'Create Schedule');
    data_set($col5, 'computetimecard.style', 'width:100%');

    $fields = ['bltimecard', 'blotapproval'];
    $col6 = $this->fieldClass->create($fields);
    data_set($col6, 'bltimecard.style', 'width:100%;height:100%; text-decoration: underline; font-size:16px');
    data_set($col6, 'bltimecard.url', '/headtable/payrollcustomform/emptimecard');
    data_set($col6, 'blotapproval.style', 'width:100%;height:100%; text-decoration: underline; font-size:16px');
    data_set($col6, 'blotapproval.url', '/headtable/payrollentry/entryotapproval');

    $fields = [];
    $col7 = $this->fieldClass->create($fields);

    $fields = [];
    $col8 = $this->fieldClass->create($fields);

    $tab = [
      'multiinput1' => ['inputcolumn' => ['col1' => $col5, 'col2' => $col6, 'col3' => $col7, 'col4' => $col8], 'label' => 'TIMEKEEPING'],
      'multiinput2' => ['inputcolumn' => ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4], 'label' => 'TIME SHEET PROCESS']
    ];
    if ($timekeeping) {
      $tab['multiinput2']['label'] = '';
    }

    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['readfile']; // 'createschedule'
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['label'] = 'READ TXTFILE FROM BIOMETRIC';
    $obj[0]['access'] = 'view';
    return $obj;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];

    switch ($companyid) {
      case 58: //cdo
        $fields = ["empdivname", "empbranchname", 'empid', 'empcode', 'empname', 'checkall'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'checkall.label', 'All Employees');
        data_set($col1, 'empdivname.lookupclass', 'lookupempdivisionprocess');
        data_set($col1, 'empbranchname.lookupclass', 'lookupempdivisionprocess');
        data_set($col1, 'empcode.addedparams', ['empdivid', 'empbranchid']);

        $fields = ['tpaygroup',  'paymodeemp'];

        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'tpaygroup.label', 'Pay Group');
        data_set($col2, 'tpaygroup.action', 'paygrouplookup');

        $fields = [];
        $col3 = $this->fieldClass->create($fields);

        $fields = [];
        $col4 = $this->fieldClass->create($fields);
        break;

      case 62: //onesky
        $fields = ['empdivname', 'empid', 'empcode', 'empname', 'checkall'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'checkall.label', 'All Employees');
        data_set($col1, 'empdivname.lookupclass', 'lookupempdivisionprocess');
        data_set($col1, 'empcode.addedparams', ['empdivid']);

        $fields = ['tpaygroup',  'paymodeemp'];

        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'tpaygroup.label', 'Pay Group');
        data_set($col2, 'tpaygroup.action', 'paygrouplookup');

        $fields = [];
        $col3 = $this->fieldClass->create($fields);

        $fields = [];
        $col4 = $this->fieldClass->create($fields);
        break;

      default:
        $fields = ['empid', 'empcode', 'empname', 'checkall'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'checkall.label', 'All Employees');

        $fields = ['tpaygroup',  'paymodeemp'];

        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'tpaygroup.label', 'Pay Group');
        data_set($col2, 'tpaygroup.action', 'paygrouplookup');

        $fields = [];
        $col3 = $this->fieldClass->create($fields);

        $fields = [];
        $col4 = $this->fieldClass->create($fields);
        break;
    }



    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function stockstatusposted($config)
  {
    $companyid = $config['params']['companyid'];
    $start = $config['params']['dataparams']['startdate'];
    $end = $config['params']['dataparams']['enddate'];

    // $start = $this->othersClass->sbcdateformat($start);
    // $end = $this->othersClass->sbcdateformat($end);

    $start =  date('Y-m-d', strtotime($start));
    $end =  date('Y-m-d', strtotime($end));

    switch ($config['params']['action']) {

      case 'readfile':
        $csv = $config['params']['csv'];

        switch ($companyid) {
          case 43: //mighty
            $arrcsv = explode("\r\n", $csv);

            $counter = 1;
            foreach ($arrcsv as $arr) {
              if ($counter > 1) {
                // $this->othersClass->logConsole(json_encode($arr));
                $newarr = explode("\t", $arr);

                // $this->othersClass->logConsole(json_encode($newarr));

                if (count($newarr) == 1) {
                  goto exithere;
                }

                $id = trim($newarr[0]);
                $dateid = date('Y-m-d', strtotime(trim($newarr[3])));
                $clockin = trim($newarr[11]);
                $clockout = trim($newarr[12]);
                $terminal = trim($newarr[14]);

                $timein = '';
                $timeout = '';

                if ($clockin != '') $timein = date('Y-m-d H:i:s', strtotime($dateid . ' ' . $clockin));
                if ($clockout != '') $timeout = date('Y-m-d H:i:s', strtotime($dateid . ' ' . $clockout));

                $this->othersClass->logConsole($dateid . '   ' . $id . ' ' . $timein . ' ' . $timeout);
                // $this->othersClass->logConsole($dateid . '  ' . $start . '  ' . $end);

                if (($dateid >= $start) && ($dateid <= $end)) {
                  if ($timein != '') {
                    $chkexist = $this->coreFunctions->datareader("select userid as value from timerec where userid='" . $id . "' and timeinout='" . $timein . "'");
                    if ($chkexist == "") {
                      $qry = "insert into timerec (machno,userid,timeinout,mode,curdate) values ('" . $terminal . "','" . $id . "','" . $timein . "','IN','" . $dateid . "') ";
                      $this->coreFunctions->execqry($qry);
                    }
                  }

                  if ($timeout != '') {
                    $chkexist2 = $this->coreFunctions->datareader("select userid as value from timerec where userid='" . $id . "' and timeinout='" . $timeout . "'");
                    if ($chkexist2 == "") {
                      $qry = "insert into timerec (machno,userid,timeinout,mode,curdate) values ('" . $terminal . "','" . $id . "','" . $timeout . "','OUT','" . $dateid . "') ";
                      $this->coreFunctions->execqry($qry);
                    }
                  }
                }

                // $this->othersClass->logConsole($id . ' - ' . $dateid . ' - ' . $clockin . ' - ' . $clockout);
              }

              $counter += 1;
            }
            break;


          default: //XComp
            $arrcsv = explode("\r\n", $csv);

            $counter = 1;
            foreach ($arrcsv as $arr) {
              $name = '';
              $time = '';
              $machno = '';
              $type = '';

              $newarr = explode("\t", $arr);
              // $this->othersClass->logConsole($counter . ' - ' . json_encode($newarr));
              if (count($newarr) == 1) {
                goto exithere;
              }

              // list($name, $time, $na1, $type, $machno) = $newarr;
              $name = trim($newarr[0]);
              $time = date('Y-m-d H:i:s', strtotime(trim($newarr[1])));
              $type = trim($newarr[2]);
              $machno = trim($newarr[3]);

              if ($type == "0") {
                $type = "IN";
              } else {
                $type = "OUT";
              }

              $cdate = date('Y-m-d', strtotime($time));

              // $this->othersClass->logConsole('time - ' . $cdate . ' -> ' . $start . ' to ' . $end);

              if ($companyid == 58) goto SkipDateHere; //cdo 

              if (($cdate >= $start) && ($cdate <= $end)) {
                SkipDateHere:
                $chkexist = $this->coreFunctions->datareader("select userid as value from timerec where userid='" . $name . "' and timeinout='" . $time . "'");
                if ($chkexist == "") {
                  $qry = "insert into timerec (machno,userid,timeinout,mode,curdate) values (" . $machno . ",'" . $name . "','" . $time . "','" . $type . "','" . $cdate . "') ";
                  $this->coreFunctions->execqry($qry);
                }
              }

              $counter = $counter + 1;
            }
            break;
        }

        exithere:
        return ['status' => true, 'msg' => 'Readfile Successfully', 'data' => $arrcsv];

        break;

      case 'dlexcelmbtctxtfile':
        $companyid = $config['params']['companyid'];
        if ($config['params']['dataparams']['batchid'] == 0) {
          return ['status' => false, 'msg' => 'Please select valid batch'];
        }

        $filterbank = '';
        if ($companyid == 58) { //cdo
          $filterbank = " and e.bank='METROBANK'";
        }

        $data = $this->coreFunctions->opentable("select emplast as `Last Name`, empfirst as `First Name`, empmiddle as `Middle Name`, bankacct as `Employee Account Number`, format(p.db,2) as `Amount`
            from paytrancurrent as p left join paccount as a on a.line=p.acnoid left join employee as e on e.empid=p.empid where p.batchid=" . $config['params']['dataparams']['batchid'] . " and a.alias='PPBLE' and e.isactive=1 " . $filterbank . " order by e.idbarcode");
        return ['status' => true, 'msg' => 'Txt file ready to Download', 'name' => 'MBTC', 'data' => $data];
        break;

      case 'dlexcelbpitxtfile':
        if ($config['params']['dataparams']['batchid'] == 0) {
          return ['status' => false, 'msg' => 'Please select valid batch'];
        }

        $data = $this->coreFunctions->opentable("select 'D' as `DETAIL CONSTANT`, concat(empfirst,' ',emplast) as `EMPLOYEE NAME`, bankacct as `Employee Account`, format(p.db,2) as `Amount`, '' as `REMARKS`
            from paytrancurrent as p left join paccount as a on a.line=p.acnoid left join employee as e on e.empid=p.empid where p.batchid=" . $config['params']['dataparams']['batchid'] . " and a.alias='PPBLE' and e.isactive=1 and e.bank='BPI' order by e.idbarcode");

        $total = 0;

        foreach ($data as $key => $val) {
          $total += floatval(str_replace(',', '', $val->Amount));
        }

        $newdata = json_decode(json_encode($data), true);

        $header = ['H' => '', 'Payroll Date' => '', $this->othersClass->getCurrentDate() => '', 'Payroll Time' => '', ' ' => '', 'Total Amount' => '', number_format($total, 2) => '', 'Total Count' => '', count($data) => '', 'FUNDING ACCOUNT' => '', '9323017007' => ''];

        $merged = array_merge($header, ['employees' => $newdata]);

        return ['status' => true, 'msg' => 'Txt file ready to Download', 'name' => 'BPI', 'data' => $merged, 'filename' => 'BPI SUMMARY CONVERTER'];
        break;
    }
  }


  public function paramsdata($config)
  {
    $data = $this->coreFunctions->opentable("
      select 
      date(now()) as startdate,
      date(now()) as enddate,
      null as sdate1,
      null as sdate2,
      '' as empcode,
      '' as empname,
      0 as empid,
      '0' as checkall,
      '' as batch,
      0 as batchid,
      0 as is13,
      '' as 13start,
      '' as 13end,
      '' as tpaygroupname,
      '' as pgroup,
      '' as paygroup,
      '' as tpaygroup,
      null as batchdate,
      '0' as adjustm,
      '' as timecard,
      '' as payrollentry,
      '' as paymodeemp,
      '' as paymode,
      '' as fullwordpaymode,
      0 as divid,
      '' as divname,
      0 as branchid,
      '' as branchname,
      '' as empdivname,
      0 as empdivid,
      '' as empbranchname,
      0 as empbranchid
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

  public function headtablestatus($config)
  {
    // should return action
    $action = $config['params']["action2"];
    $companyid = $config['params']['companyid'];

    switch ($action) {
      case 'create':
        return $this->create($config);
        break;

      case 'computetimecard':
        switch ($companyid) {
          case 58: //cdo
            return $this->computetimecard_cdo($config);
            break;
          case 62: //onesky
            return $this->computetimecard_onesky($config);
            break;
          default:
            return $this->computetimecard($config);
            break;
        }
        break;

      case 'postinout':
        return $this->postactualinout($config);
        break;

      case 'computetimesheet':
        $timesheet = $this->computetimesheet($config);
        if ($timesheet['status']) {
          // if ($companyid == 62) return $timesheet;
          return $this->payrollcommon->generatePayTranCurrent($config);
        } else {
          return $timesheet;
        }

        break;

      case 'payrollclosing':
        return $this->payrollcommon->postPayroll($config);
        break;

      case 'payrollunclosing':
        return $this->payrollcommon->postPayroll($config, 1);
        break;

      default:
        return ['status' => false, 'msg' => $action . ' not yet setup in the headtablestatus.'];
        break;
    }
  }

  public function create($config)
  {
    $user  = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $start = $config['params']['dataparams']['startdate'];
    $end = $config['params']['dataparams']['enddate'];
    $all = $config['params']['dataparams']['checkall'];
    $empid = $config['params']['dataparams']['empid'];
    $empname = isset($config['params']['dataparams']['empname']) ? $config['params']['dataparams']['empname'] : '';
    $emplvl = $this->othersClass->checksecuritylevel($config);
    if ($all == "1") {

      $empdivid = $config['params']['dataparams']['empdivid'];
      $empbranchid = $config['params']['dataparams']['empbranchid'];

      $addonfilter = '';

      switch ($companyid) {
        case 58; //cdo
          if ($empdivid == 0) {
            return ['status' => false, 'msg' => 'Please select valid Company'];
          }

          if ($empdivid != 0) $addonfilter .= " and e.divid=" . $empdivid;
          if ($empbranchid != 0) $addonfilter .= " and e.branchid=" . $empbranchid;
          break;

        case 62: //onesky
          if ($empdivid == 0) {
            return ['status' => false, 'msg' => 'Please select valid Company'];
          }
          $addonfilter = " and e.divid=" . $empdivid;
          break;
      }

      $this->logger->sbcmasterlog2(0, $config, 'Create schedule for all employees. From ' . $start . '  to ' . $end, 'payroll_log', 1);

      $qry = "select e.empid, client.clientname, e.paygroup 
      from employee as e left join client on client.clientid=e.empid where e.isactive=1 and e.hired<='" . $end . "' " . $addonfilter . " and e.level in $emplvl";
      $client = $this->coreFunctions->opentable($qry);
      $result = [];
      if (!empty($client)) {
        foreach ($client as $key => $val) {
          $result = $this->createSchedule($val->empid, $start, $end, $val->clientname, $config);
          if (!$result['status']) {
            return ['status' => false, 'msg' => 'Failed to create schedule. ' . $result['msg']];
          }
        }
      } else {
        return ['status' => false, 'msg' => 'No employees exist on current select company and branch.'];
      }

      return $result;
    } else {
      if ($empid != 0) {

        $this->logger->sbcmasterlog2(0, $config, 'Create schedule for ' . $config['params']['dataparams']['empname'] . '. From ' . $start . '  to ' . $end, 'payroll_log', 1);

        return $this->createSchedule($empid, $start, $end, $empname, $config);
      } else {
        return ['status' => false, 'msg' => 'Select valid employee' . $empname, 'action' => 'load'];
      }
    }

    return ['status' => true, 'msg' => 'Successfully loaded.', 'action' => 'load'];
  }

  public function createSchedule($empid, $start, $end, $empname, $config, $shiftid = 0)
  {
    $start = date('Y-m-d', strtotime($start));
    $end = date('Y-m-d', strtotime($end));

    $qry = "delete from timecard where empid=? and dateid between '" . $start . "' and '" . $end . "'";
    $this->coreFunctions->execqry($qry, 'delete', [$empid]);

    if ($shiftid != 0) {
      $qry = "select s.line, time(s.tschedin) as timein, time(s.tschedout) as timeout
        from tmshifts as s 
        where s.line=?";
      $shift = $this->coreFunctions->opentable($qry, [$shiftid]);
    } else {
      $qry = "select s.line, time(s.tschedin) as timein, time(s.tschedout) as timeout
        from employee as e join tmshifts as s on s.line=e.shiftid
        where e.empid=?";
      $shift = $this->coreFunctions->opentable($qry, [$empid]);
    }

    if (!empty($shift)) {
      $days = $this->getDays($start, $end);

      $timesched = $this->getTimeSched($shift[0]->line);
      $databreak = $this->getBreakSched($shift[0]->line);

      foreach ($days as $key => $val) {
        $data = [];
        $data['empid'] = $empid;
        $data['dateid'] = $val->dateid;
        $data['shiftid'] = $shift[0]->line;

        $timein = $this->getBreakSchedTime($timesched, $val->dayname);
        // $data['schedin'] = $val->dateid . " " . $shift[0]->timein;
        // $data['schedout'] = $val->dateid . " " .  $shift[0]->timeout;

        if ($timein->schedin != null) {
          $data['schedin'] = $val->dateid . " " . $timein->schedin;
        } else {
          $data['schedin'] = $val->dateid . " " . $shift[0]->timein;
        }

        if ($timein->schedout != null) {
          $data['schedout'] = $val->dateid . " " . $timein->schedout;
        } else {
          $data['schedout'] = $val->dateid . " " . $shift[0]->timeout;
        }

        $s_schedin = Carbon::parse($data['schedin']);
        $s_schedout = Carbon::parse($data['schedout']);

        if ($s_schedin > $s_schedout) {
          $data['schedout'] = $s_schedout->addDays(1)->format('Y-m-d H:i:s');
        }

        $break = $this->getBreakSchedTime($databreak, $val->dayname);
        $data['schedbrkin'] =  $break->breakin == null ? null : $val->dateid . " " . $break->breakin;
        $data['schedbrkout'] = $break->breakout == null ? null : $val->dateid . " " . $break->breakout;

        $data['brk1stout'] = $break->brk1stout == null ? null : $val->dateid . " " . $break->brk1stout;
        $data['brk1stin'] = $break->brk1stin == null ? null : $val->dateid . " " . $break->brk1stin;

        $data['brk2ndout'] = $break->brk2ndout == null ? null : $val->dateid . " " . $break->brk2ndout;
        $data['brk2ndin'] = $break->brk2ndin == null ? null : $val->dateid . " " . $break->brk2ndin;

        if ($data['schedbrkin'] < $data['schedin']) {
          if ($data['schedbrkin'] != null) {
            $s_schedbrkin = Carbon::parse($data['schedbrkin']);
            $data['schedbrkin'] = $s_schedbrkin->addDays(1)->format('Y-m-d H:i:s');
          }
        }

        if ($data['schedbrkout'] < $data['schedin']) {
          if ($data['schedbrkout'] != null) {
            $s_schedbrkout = Carbon::parse($data['schedbrkout']);
            $data['schedbrkout'] = $s_schedbrkout->addDays(1)->format('Y-m-d H:i:s');
          }
        }

        // brk1stout

        $data['daytype'] = $break->tothrs != 0 ? "WORKING" : "RESTDAY";
        $data['reghrs'] = $break->tothrs;

        // // default time in/out
        if ($data['daytype'] != 'RESTDAY') {
          $data['actualin'] = $data['schedin'];
          $data['actualout'] = $data['schedout'];

          $data['actualbrkin'] = $data['schedbrkin'];
          $data['actualbrkout'] = $data['schedbrkout'];
        } else {
          $data['actualin'] = null;
          $data['actualout'] = null;

          $data['actualbrkin'] = null;
          $data['actualbrkout'] = null;
        }

        if ($config['params']['companyid'] == 45) {
          $data['actualin'] = null;
          $data['actualout'] = null;
          $data['absdays'] = $break->tothrs;
        }

        $this->coreFunctions->sbcinsert('timecard', $data);
      }

      return ['status' => true, 'msg' => 'Success', 'action' => 'load'];
    } else {
      return ['status' => false, 'msg' => 'Shift missing. Please setup shift on employee ledger. ' . $empname, 'action' => 'load'];
    }
  }


  private  function getDays($start, $end)
  {
    $start = date('Y-m-d', strtotime($start));
    $end = date('Y-m-d', strtotime($end));

    $qry = "select a.Date as dateid, dayname(a.Date) as dayname
        from (
            select '" . $end . "' - INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY as Date
            from (select 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) as a
            cross join (select 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) as b
            cross join (select 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) as c
        ) a 
        where a.Date between '" . $start . "' and '" . $end . "' order by a.Date";

    return $this->coreFunctions->opentable($qry);
  }

  private  function getBreakSched($shift)
  {
    $qry = "select time(breakin) as breakin, time(breakout) as breakout, tothrs, time(brk1stin) as brk1stin, time(brk1stout) as brk1stout, time(brk2ndin) as brk2ndin, time(brk2ndout) as brk2ndout from shiftdetail where shiftsid=? order by dayn";

    return $this->coreFunctions->opentable($qry, [$shift]);
  }

  private  function getTimeSched($shift)
  {
    $qry = "select time(schedin) as schedin, time(schedout) as schedout, tothrs from shiftdetail where shiftsid=? order by dayn";

    return $this->coreFunctions->opentable($qry, [$shift]);
  }

  private function getBreakSchedTime($data, $day)
  {

    $index = 0;
    switch (strtoupper($day)) {
      case "MONDAY":
        $index = 0;
        break;
      case "TUESDAY":
        $index = 1;
        break;
      case "WEDNESDAY":
        $index = 2;
        break;
      case "THURSDAY":
        $index = 3;
        break;
      case "FRIDAY":
        $index = 4;
        break;
      case "SATURDAY":
        $index = 5;
        break;
      case "SUNDAY":
        $index = 6;
        break;
    }

    return $data[$index];
  }


  public function computetimecard($config, $blnExtract = false)
  {
    ini_set('max_execution_time', 0);
    set_time_limit(0);
    ini_set('memory_limit', '-1');

    $empid = $config['params']['dataparams']['empid'];
    $start = $config['params']['dataparams']['startdate'];
    $end = $config['params']['dataparams']['enddate'];
    $checkall = $config['params']['dataparams']['checkall'] == "1" ? true : false;

    $emplog = 'all employees';

    if ($checkall) {
      $empid = 0;
    } else {
      $emplog = $config['params']['dataparams']['empname'];
    }

    $start = date('Y-m-d', strtotime($start));
    $end = date('Y-m-d', strtotime($end));

    $filteremplvl = '';
    if ($blnExtract) {
    } else {
      $emplvl = $this->othersClass->checksecuritylevel($config);
      $filteremplvl = " and e.level in $emplvl";
    }

    $this->logger->sbcmasterlog2(0, $config, 'Compute timecard for ' . $emplog . '. From ' . $start . '  to ' . $end, 'payroll_log', 1);


    $this->coreFunctions->LogConsole('->computetimecard-resetdays');
    //RESET DAYTYPE
    $qry = "update timecard as t left join employee as e on e.empid = t.empid 
      set t.daytype ='WORKING' 
      where t.daytype not in('WORKING','RESTDAY') and date(t.DateID) between '" . $start . "' and '"  . $end . "' and e.isactive=1 " . $filteremplvl;

    if (!$checkall) {
      $qry .= " and e.empid=" . $empid;
    }

    $this->coreFunctions->execqry($qry);

    $this->coreFunctions->LogConsole('->computetimecard-holiday');
    $qry = "select date(dateid) as dateid,daytype,divcode from holiday where date(dateid) between '" . $start . "' and '" . $end . "' order by dateid";
    $holiday = $this->coreFunctions->opentable($qry);

    if (!empty($holiday)) {
      $this->coreFunctions->LogConsole('->computetimecard-holidays');
      foreach ($holiday as $k => $val) {
        $qry = "update timecard  as t left join employee as e on e.empid = t.empid ";
        $qry .= "set t.daytype = case when t.daytype = 'WORKING' then '" . $val->daytype . "' ";
        if ($val->daytype == 'LEG') {
          $qry .= "when daytype = 'RESTDAY' then 'LEG' ";
        }
        $qry .= "else t.daytype end ";

        $qry .= "where t.dateid='" . $val->dateid . "' and e.isactive=1 " . $filteremplvl;
        if (!$checkall) {
          $qry .= "and t.empid=" . $empid;
        }
        $this->coreFunctions->execqry($qry);
      }
    }

    $this->coreFunctions->LogConsole('->computetimecard-resetOT');
    //reset OT
    $qry = "update timecard as t left join employee as e on e.empid = t.empid 
      set otapproved = 0, ndiffapproved = 0, isprevwork =0, RDapprvd=0, 
      RDOTapprvd=0, LEGapprvd=0, LEGOTapprvd=0, SPapprvd=0, SPOTapprvd=0, ndiffsapprvd=0 
      where t.dateid between '" . $start . "' and '" . $end . "' and e.isactive=1 " . $filteremplvl;
    if (!$checkall) {
      $qry .= "and e.empid=" . $empid;
    }
    $this->coreFunctions->execqry($qry);

    $qry = "select schedin, schedout from timecard as t";
    if (!$checkall) {
      $qry .= "and e.empid=" . $empid;
    }

    $this->coreFunctions->LogConsole('->computetimecard-getschedule');
    $data = $this->getempschedule($empid, $start, $end);

    if (empty($data)) {
      $this->coreFunctions->LogConsole('No schedule');
    }

    foreach ($data as $key => $val) {

      $shift = $this->payrollcommon->getShiftDetails($val->empid);

      unset($val->bgcolor);

      $absent = 0;
      $late = 0;
      $late2 = 0;
      $breakoutlate = 0;
      $breakinlate = 0;
      $undertime = 0;
      $overtime = 0;
      $ndiffot = 0;
      $ndiff = 0;
      $ambreak = 0;

      $blnCheckOT = false;
      $blnWorkingDay = true;

      $schedin = $val->schedin == null ? $val->schedin : Carbon::parse($val->schedin);
      $actualin = $val->actualin == null ? $val->actualin : Carbon::parse($val->actualin);

      $schedout = $val->schedout == null ? $val->schedout : Carbon::parse($val->schedout);
      $actualout = $val->actualout == null ? $val->actualout : Carbon::parse($val->actualout);

      $schedbrkout = $val->schedbrkout == null ? $val->schedbrkout : Carbon::parse($val->schedbrkout);
      $actualbrkout = $val->actualbrkout == null ? $val->actualbrkout : Carbon::parse($val->actualbrkout);

      $schedbrkin = $val->schedbrkin == null ? $val->schedbrkin : Carbon::parse($val->schedbrkin);
      $actualbrkin = $val->actualbrkin == null ? $val->actualbrkin : Carbon::parse($val->actualbrkin);

      $brk1stin = $val->brk1stin == null ? $val->brk1stin : Carbon::parse($val->brk1stin);
      $brk1stout = $val->brk1stout == null ? $val->brk1stout : Carbon::parse($val->brk1stout);

      if ($brk1stin  != null) {
        $ambreak = $brk1stout->diffInMinutes($brk1stin, false);
        if ($ambreak > 0) {
          $ambreak = $ambreak / 60;
        }
      }

      switch ($val->daytype) {
        case 'RESTDAY':
        case 'LEG':
        case 'SP':
          if ($val->actualin == null && $val->actualout == null) {
            $val->reghrs = 0;
          } else {
            $val->reghrs = $schedin->diffInMinutes($schedout, false);
            if ($val->reghrs > 0) {
              $val->reghrs = round($val->reghrs / 60, 2);
            } else {
              $val->reghrs = 0;
            }
          }
          $blnWorkingDay = false;
          if ($val->daytype == 'LEG') {
            $val->isprevwork = $this->checkvalidleghrs($val->empid, $val->dateid);
          }
          break;
      }

      if ($val->actualin == null && $val->actualout == null) {
        $absent = $val->reghrs;
      } else {
        // $actualin =  $actualin_gtin->addMinute($shift[0]->gtin * -1);
        $late = $schedin->diffInMinutes($actualin, false);

        if ($shift[0]->gtin != 0) {
          if ($late > 0) {
            $late -= $shift[0]->gtin;
          }
        }

        if ($late > 0) {
          $late = $schedin->diffInMinutes($actualin, false);
        }

        if ($schedbrkout != null) {
          if ($actualin >= $schedbrkout && $actualin <= $schedbrkin) {
            $late = $schedin->diffInMinutes($schedbrkout, false);
          }
        }

        if ($late > 0) {

          if ($config['params']['companyid'] == 43) { //mighty
            if ($late > 0 && $late <= 5) {
              $late2 = 0.08;
            } elseif ($late > 5 && $late <= 10) {
              $late2 = 0.5;
            } elseif ($late > 10 && $late <= 45) {
              $late2 = 1;
            } elseif ($late > 45 && $late <= 120) {
              $late2 = 2;
            }
          }

          $late = round($late / 60, 2);
        } else {
          $late = 0;
          $late2 = 0;
        }

        //EARLY BREAKOUT
        if ($actualbrkout != null) {
          $breakoutlate = $actualbrkout->diffInMinutes($schedbrkout, false);
          if ($breakoutlate > 0) {
            $breakoutlate = round($breakoutlate / 60, 2);
          } else {
            $breakoutlate = 0;
          }
          $late += $breakoutlate;
        }

        //LATE BREAKIN
        if ($actualbrkin != null && $schedbrkin != null) {
          $breakinlate = $schedbrkin->diffInMinutes($actualbrkin, false);
          if ($breakinlate > 0) {
            $breakinlate = round($breakinlate / 60, 2);
          } else {
            $breakinlate = 0;
          }
          $late += $breakinlate;
        }

        //not include AM break
        if ($brk1stin  != null) {
          if ($actualin > $brk1stout) {
            $late -= $ambreak;
          }
        }

        //UNDERTIME
        $blnActualOutInBrk = false;
        if ($schedbrkin != null) {
          if ($actualout != null) {
            if (date('Y-m-d H:i', strtotime($actualout)) <= date('Y-m-d H:i', strtotime($schedbrkin))) {
              $blnActualOutInBrk = true;

              if (date('Y-m-d H:i', strtotime($actualout)) <= date('Y-m-d H:i', strtotime($schedbrkout))) {
                $undertime = $actualout->diffInMinutes($schedout, false);
                $undertime = $undertime - 60;
              } else {
                $undertime = $schedbrkin->diffInMinutes($schedout, false);
              }

              if ($undertime > 0) {
                $undertime = round($undertime / 60, 2);
              } else {
                $undertime = 0;
              }
            }
          }
        }

        if ($actualout != null) {
          if (!$blnActualOutInBrk) {
            $undertime = $actualout->diffInMinutes($schedout, false);
            if ($undertime > 0) {
              $undertime = round($undertime / 60, 2);
            } else {
              $undertime = 0;
            }
          }
        }

        $startndifftime = date('Y-m-d', strtotime($val->schedin)) . " " . date('H:i', strtotime($shift[0]->ndifffrom));
        $endif = Carbon::parse($val->schedout);
        $endndifftime = date('Y-m-d', strtotime($endif)) . " " . date('H:i', strtotime($shift[0]->ndiffto));

        // when schedule is set between night diff hrs
        $actualin = Carbon::parse($val->actualin);
        if (date('Y-m-d H:i', strtotime($actualin)) >= $startndifftime) {
          computendiffhere:
          $startndifftime = Carbon::parse($startndifftime);

          if ($actualout < $endndifftime) {
            if ($actualout != null) {
              $ndiff = $startndifftime->diffInMinutes($actualout, false);
            }
          } else {
            $ndiff = $startndifftime->diffInMinutes($endndifftime, false);
            goto computendiffothere;
          }

          $blnCheckOT = true;
        } elseif (date('Y-m-d H:i', strtotime($actualout)) >= $startndifftime) {
          goto  computendiffhere;
        } else {

          computendiffothere:
          // checking of night diff OT
          $blnCheckOT = true;
          if (date('Y-m-d H:i', strtotime($actualout)) > $startndifftime) {

            if ($schedout <  $startndifftime) {
              $endot = $startndifftime;
              $overtime = $schedout->diffInMinutes($endot, false);

              $endif = Carbon::parse($val->schedout);
              $endif->addDays(1);
              $endndifftime = date('Y-m-d', strtotime($endif)) . " " . date('H:i', strtotime($shift[0]->ndiffto));

              if ($actualout <= $endndifftime) {
                $startndifftime = Carbon::parse($startndifftime);
                $ndiffot = $startndifftime->diffInMinutes($actualout, false);
              }

              if ($ndiffot > 0) {
                $ndiffot = round($ndiffot / 60, 2);
              } else {
                $ndiffot = 0;
              }
              $blnCheckOT = false;
            }
          }
        }

        if ($actualout != null) {
          if ($blnCheckOT) {
            $schedout->addMinute($shift[0]->gbrkin);
            $overtime = $schedout->diffInMinutes($actualout, false);

            if ($overtime > 0) {
              $schedout = Carbon::parse($val->schedout);
              $overtime = $schedout->diffInMinutes($actualout, false);
            }
          }
        }

        if ($overtime > 0) {
          $overtime = round($overtime / 60, 2);
        } else {
          $overtime = 0;
        }

        if ($ndiff > 0) {
          $ndiff = round($ndiff / 60, 2);
        } else {
          $ndiff = 0;
        }
      }

      // ACTUAL-IN AFTER THE SCHEDULED BREAK-IN
      if ($schedbrkin != null && $schedbrkout != null) {
        if ($actualin > $schedbrkin) {
          $breakhrs = $schedbrkout->diffInMinutes(Carbon::parse($schedbrkin), false);
          if ($breakhrs > 0) {
            $breakhrs = round($breakhrs / 60, 2);
            $late -= $breakhrs;
          }
        }
      }


      // from OT application
      $sql = "select apothrs, apndiffhrs, apndiffothrs, apothrsextra, daytype, ottimein, ottimeout from otapplication where empid=" . $val->empid . " and otstatus=2 and date(scheddate)='" . date('Y-m-d', strtotime($val->dateid)) . "'";
      $dataOT = $this->coreFunctions->opentable($sql);
      if (!empty($dataOT)) {

        switch ($dataOT[0]->daytype) {
          case 'LEG':
          case 'SP':
            $val->actualin = $dataOT[0]->ottimein;
            $val->actualout = $dataOT[0]->ottimeout;
            if ($dataOT[0]->apothrsextra != 0) {
              $overtime = $dataOT[0]->apothrsextra;
              if ($dataOT[0]->daytype == 'LEG') $val->legotapprvd = 1;
              if ($dataOT[0]->daytype == 'SP') $val->spotapprvd = 1;
            }
            if ($dataOT[0]->daytype == 'LEG') $val->legapprvd = 1;
            if ($dataOT[0]->daytype == 'SP') $val->spapprvd = 1;

            break;
          default:
            $overtime = $dataOT[0]->apothrs;
            $val->otapproved = 1;
            break;
        }

        if ($dataOT[0]->apndiffothrs != 0) {
          $ndiffot = $dataOT[0]->apndiffothrs;
          $val->Ndiffapproved = 1;
        }
      }



      if ($blnWorkingDay) {
        $val->latehrs = $late;
        $val->latehrs2 = $late2;
        $val->underhrs = $undertime;
        $val->absdays = $absent;
        $val->othrs = $overtime;
        $val->ndiffot = $ndiffot;
        $val->ndiffhrs = $ndiff;
      } else {
        if ($val->reghrs > 8) {
          $val->reghrs = 8;
        }
        $val->reghrs = $val->reghrs - $late - $undertime;
        $val->latehrs = 0;
        $val->latehrs2 = 0;
        $val->underhrs = 0;
        $val->absdays = $absent;
        $val->othrs = $overtime;
        $val->ndiffot = $ndiffot;
        $val->ndiffhrs = $ndiff;
      }

      unset($val->issupervisor);
      unset($val->isapprover);
      unset($val->classrate);

      $val = json_decode(json_encode($val), true);

      $totloghrs = 0;
      if ($config['params']['companyid'] == 45) { //pdpi payroll
        $pdpi_ot = 0;

        //copy working hrs and ot from deployment record
        if ($val["reghrs"] > 0) {
          $totloghrs = $this->coreFunctions->datareader("select ifnull(sum(tothrs),0) as value from empprojdetail where empid=" . $val["empid"] . " and date(dateid)='" . $val["dateid"] . "'", [], '', true);
          $pdpi_ot = $this->coreFunctions->datareader("select ifnull(sum(othrs),0) as value from empprojdetail where empid=" . $val["empid"] . " and date(dateid)='" . $val["dateid"] . "'", [], '', true);
          if ($totloghrs >= 8) {
            // 2024.10.14 - temporary remove set to 8 hrs, some logs are more than 8hrs
            // $val["reghrs"] = 8;

            $val["reghrs"] = $totloghrs;

            $val["absdays"] = 0;
          } else {
            goto noLogshere;
          }

          if ($pdpi_ot > 0) {
            $val["othrs"] = $pdpi_ot;
            $val["otapproved"] = 1;
          }
        } else {
          noLogshere:

          // must remove in live
          // 2024.09.16 - temporary only, not all employees have logs in tablet
          $totloghrs = $this->coreFunctions->datareader("select ifnull(sum(tothrs),0) as value from empprojdetail where empid=" . $val["empid"] . " and date(dateid)='" . $val["dateid"] . "'", [], '', true);
          if ($totloghrs > 0) {
            if ($val["absdays"] > 0) {
              $val["absdays"] = $val["absdays"] - $totloghrs;
            }
            if ($val["latehrs"] > 0) {
              $val["latehrs"] = $val["reghrs"] - $totloghrs;
            }
          }
          // end of must remove in live
        }
      }

      $val["dateid"] = $this->othersClass->sanitizekeyfield('dateonly', $val["dateid"]);

      // $this->coreFunctions->LogConsole(json_encode($val));

      $this->coreFunctions->sbcupdate("timecard", $val, ['empid' => $val["empid"], 'dateid' => $val["dateid"]]);

      if ($config['params']['companyid'] == 45) { //pdpi payroll
        if ($val['reghrs'] > 0) {
          // $totloghrs = $this->coreFunctions->datareader("select ifnull(sum(tothrs),0) as value from empprojdetail where empid=" . $val["empid"] . " and date(dateid)='" . $val["dateid"] . "'", [], '', true);
          if ($totloghrs > 0) {
            $emprate = $this->coreFunctions->opentable("select basicrate, `type` from ratesetup where empid=" . $val["empid"] . " and date('" . $end . "') between date(dateeffect) and date(dateend) order by dateend desc limit 1");
            $rate = 0;
            $daysInMonth = $this->companysetup->getpayroll_daysInMonth($config['params']);
            if (!empty($emprate)) {
              switch ($emprate[0]->type) {
                case "M":
                  $rate = round($emprate[0]->basicrate / $daysInMonth, 2);
                  break;
                case "S":
                  $rate = round(($emprate[0]->basicrate / $daysInMonth) / 2, 2);
                  break;
                default;
                  $rate = round($emprate[0]->basicrate / 8, 2);
                  break;
              }
            }
            $this->coreFunctions->execqry("update empprojdetail set achrs=round((tothrs/" . $totloghrs . ")*" . $val['reghrs'] . ",2),rate=" . $rate . " where empid=" . $val["empid"] . " and date(dateid)='" . $val["dateid"] . "'");
          }
        }
      }
    }

    return ['status' => true, 'msg' => 'Compute Success', 'action' => 'load'];
  }

  public function computetimecard_cdo($config, $blnExtract = false)
  {
    ini_set('max_execution_time', -1);
    ini_set('memory_limit', '-1');

    $empid = $config['params']['dataparams']['empid'];
    $start = $config['params']['dataparams']['startdate'];
    $end = $config['params']['dataparams']['enddate'];
    $empdivid = $config['params']['dataparams']['empdivid'];
    $empbranchid = $config['params']['dataparams']['empbranchid'];

    $checkall = $config['params']['dataparams']['checkall'] == "1" ? true : false;
    if ($checkall) {
      $empid = 0;

      if ($empdivid == 0) {
        return ['status' => false, 'msg' => 'Please select valid Company'];
      }
    }

    $start = date('Y-m-d', strtotime($start));
    $end = date('Y-m-d', strtotime($end));

    $filteremplvl = '';
    if ($blnExtract) {
    } else {
      $emplvl = $this->othersClass->checksecuritylevel($config);
      $filteremplvl = " and e.level in $emplvl";
    }

    $this->coreFunctions->LogConsole('->computetimecard-resetdays');
    //RESET DAYTYPE
    $qry = "update timecard as t left join employee as e on e.empid = t.empid 
      set t.daytype ='WORKING' 
      where t.daytype not in('WORKING','RESTDAY') and date(t.DateID) between '" . $start . "' and '"  . $end . "' and e.isactive=1 and e.divid=" . $empdivid . " and e.branchid=" . $empbranchid  . $filteremplvl;

    if (!$checkall) {
      $qry .= " and e.empid=" . $empid;
    }

    $this->coreFunctions->execqry($qry);

    $this->coreFunctions->LogConsole('->computetimecard-holiday');
    $qry = "select date(dateid) as dateid,daytype,divcode from holiday where date(dateid) between '" . $start . "' and '" . $end . "' order by dateid";
    $holiday = $this->coreFunctions->opentable($qry);

    if (!empty($holiday)) {
      $this->coreFunctions->LogConsole('->computetimecard-holidays');
      foreach ($holiday as $k => $val) {

        $holidayloc = $this->coreFunctions->opentable("select date(dateid) as dateid,daytype,branchid from holidayloc where date(dateid)='" . $val->dateid . "' and branchid=" . $empbranchid . " order by dateid");

        $qry = "update timecard  as t left join employee as e on e.empid = t.empid ";

        if (!empty($holidayloc)) { //checking of holidayloc
          $qry .= "set t.daytype = case when t.daytype = 'WORKING' then '" . $holidayloc[0]->daytype . "' ";
          if ($holidayloc[0]->daytype == 'LEG') $qry .= "when daytype = 'RESTDAY' then 'LEG' ";
        } else {
          $qry .= "set t.daytype = case when t.daytype = 'WORKING' then '" . $val->daytype . "' ";
          if ($val->daytype == 'LEG') $qry .= "when daytype = 'RESTDAY' then 'LEG' ";
        }
        $qry .= "else t.daytype end ";

        $qry .= "where t.dateid='" . $val->dateid . "' and e.isactive=1 and e.isnobio=0 and e.divid=" . $empdivid . " and e.branchid=" . $empbranchid . $filteremplvl;
        if (!$checkall) {
          $qry .= "and t.empid=" . $empid . " and e.isnobio=0";
        }
        $this->coreFunctions->execqry($qry);
      }
    }

    $filteremployee = '';
    if (!$checkall) {
      $filteremployee = "and empid=" . $empid;
    }
    $this->coreFunctions->LogConsole('->computetimecard-restday-work on rest day');
    $qry = "select date(dateid) as dateid, isword, isrestday,empid from changeshiftapp where status=1 and date(dateid) between '" . $start . "' and '" . $end . "' $filteremployee order by dateid";
    $word = $this->coreFunctions->opentable($qry);
    foreach ($word as $k => $val) {
      $qry = "update timecard  as t left join employee as e on e.empid = t.empid ";
      $qry .= "set t.daytype = ";
      if ($val->isrestday) $qry .= "'RESTDAY'";
      if ($val->isword)  $qry .= "'WORKING'";
      $qry .= "where t.dateid='" . $val->dateid . "' and t.empid= " . $val->empid . " and e.isactive=1 and e.divid=" . $empdivid . " and e.branchid=" . $empbranchid . $filteremplvl;

      $this->coreFunctions->execqry($qry);
    }

    $this->coreFunctions->LogConsole('->computetimecard-resetOT');
    //reset OT
    $qry = "update timecard as t left join employee as e on e.empid = t.empid 
      set otapproved = 0, ndiffapproved = 0, isprevwork =0, RDapprvd=0, 
      RDOTapprvd=0, LEGapprvd=0, LEGOTapprvd=0, SPapprvd=0, SPOTapprvd=0, ndiffsapprvd=0, lateoffset=0, sphrs=0
      where t.dateid between '" . $start . "' and '" . $end . "' and e.isactive=1 and e.divid=" . $empdivid . " and e.branchid=" . $empbranchid . $filteremplvl;
    if (!$checkall) {
      $qry .= "and e.empid=" . $empid;
    }
    $this->coreFunctions->execqry($qry);

    $qry = "select schedin, schedout from timecard as t";
    if (!$checkall) {
      $qry .= "and e.empid=" . $empid;
    }

    $this->coreFunctions->LogConsole('->computetimecard-getschedule');
    $data = $this->getempschedule($empid, $start, $end, $empbranchid, $empdivid);

    if (empty($data)) {
      // $this->coreFunctions->LogConsole('No schedule');
      return ['status' => false, 'msg' => 'No existing schedule for selected company and branch'];
    }

    foreach ($data as $key => $val) {

      $shift = $this->payrollcommon->getShiftDetails($val->empid);

      // $val["dateid"]

      unset($val->bgcolor);

      $absent = 0;
      $late = 0;
      $late2 = 0;
      $breakoutlate = 0;
      $breakinlate = 0;
      $undertime = 0;
      $overtime = 0;
      $ndiffot = 0;
      $ndiff = 0;
      $ambreak = 0;

      $blnCheckOT = false;
      $blnWorkingDay = true;
      $blnNoDeduct = false;

      $schedin = $val->schedin == null ? $val->schedin : Carbon::parse($val->schedin);
      $actualin = $val->actualin == null ? $val->actualin : Carbon::parse($val->actualin);

      $schedout = $val->schedout == null ? $val->schedout : Carbon::parse($val->schedout);
      $actualout = $val->actualout == null ? $val->actualout : Carbon::parse($val->actualout);

      $schedbrkout = $val->schedbrkout == null ? $val->schedbrkout : Carbon::parse($val->schedbrkout);
      $actualbrkout = $val->actualbrkout == null ? $val->actualbrkout : Carbon::parse($val->actualbrkout);

      $schedbrkin = $val->schedbrkin == null ? $val->schedbrkin : Carbon::parse($val->schedbrkin);
      $actualbrkin = $val->actualbrkin == null ? $val->actualbrkin : Carbon::parse($val->actualbrkin);

      $brk1stin = $val->brk1stin == null ? $val->brk1stin : Carbon::parse($val->brk1stin);
      $brk1stout = $val->brk1stout == null ? $val->brk1stout : Carbon::parse($val->brk1stout);

      // actualbrkout

      if ($brk1stin  != null) {
        $ambreak = $brk1stout->diffInMinutes($brk1stin, false);
        if ($ambreak > 0) {
          $ambreak = $ambreak / 60;
        }
      }

      switch ($val->daytype) {
        case 'RESTDAY':
        case 'LEG':
          if ($val->actualin == null && $val->actualout == null) {
            $val->reghrs = 0;
          } else {
            $val->reghrs = $schedin->diffInMinutes($schedout, false);
            if ($val->reghrs > 0) {
              $val->reghrs = round($val->reghrs / 60, 2);
            } else {
              $val->reghrs = 0;
            }
          }
          $blnWorkingDay = false;
          if ($val->daytype == 'LEG') {
            $val->isprevwork = $this->checkvalidleghrs($val->empid, $val->dateid);
          }
          break;
        case 'SP': //compute working hrs based on timein not schedule
          if ($val->actualin == null && $val->actualout == null) {
            $val->reghrs = 0;
          } else {
            $val->reghrs = $actualin->diffInMinutes($actualout, false);
            if ($val->reghrs > 0) {
              $val->reghrs = round($val->reghrs / 60, 2);

              if ($val->classrate == 'D') {
                if ($val->reghrs >= 6 && $val->reghrs < 8) {
                  $val->reghrs = 8;
                  $blnNoDeduct = true;
                } else if ($val->reghrs >= 8) {
                  $val->reghrs = 8;
                  $val->reghrs = 8;
                  $val->sphrs = $val->reghrs;
                  $blnNoDeduct = true;
                }
              } else {
                if ($val->reghrs >= 6) {
                  $val->reghrs = 8;
                  $blnNoDeduct = true;
                }
              }
            } else {
              $val->reghrs = 0;
            }
          }
          break;
      }

      if ($val->actualin == null && $val->actualout == null) {
        $absent = $val->reghrs;
      } else {
        // $actualin =  $actualin_gtin->addMinute($shift[0]->gtin * -1);
        $late = $schedin->diffInMinutes($actualin, false);
        $this->coreFunctions->LogConsole('late: ' . $late);

        if ($shift[0]->gtin != 0) {
          if ($late > 0) {
            $late -= $shift[0]->gtin;
          }
        }

        if ($late > 0) {
          $late = $schedin->diffInMinutes($actualin, false);
        } else {
          $late = 0;
        }

        if ($schedbrkout != null) {
          if ($actualin >= $schedbrkout && $actualin <= $schedbrkin) {
            $late = $schedin->diffInMinutes($schedbrkout, false);
            $this->coreFunctions->LogConsole('late out: ' . $late);
          }
        }

        if ($late > 0) {
          $late2 = $late;
          $late = round($late / 60, 2);
        } else {
          $late = 0;
          $late2 = 0;
        }

        //EARLY BREAKOUT
        if ($actualbrkout != null) {
          $breakoutlate = $actualbrkout->diffInMinutes($schedbrkout, false);
          if ($breakoutlate > 0) {
            $late2 += $breakoutlate;
            $breakoutlate = round($breakoutlate / 60, 2);
            $late += $breakoutlate;
          } else {
            $breakoutlate = 0;
          }
          $this->coreFunctions->LogConsole('breakoutlate: ' . $breakoutlate);
        }

        //LATE BREAKIN
        if ($actualbrkin != null) {
          $breakinlate = $schedbrkin->diffInMinutes($actualbrkin, false);
          if ($breakinlate > 0) {
            $late2 += $breakinlate;
            $breakinlate = round($breakinlate / 60, 2);
            $late += $breakinlate;
          } else {
            $breakinlate = 0;
          }
          $this->coreFunctions->LogConsole('breakinlate: ' . $breakinlate);
        }

        //not include AM break
        if ($brk1stin  != null) {
          if ($actualin > $brk1stout) {
            $late -= $ambreak;
          }
        }

        //UNDERTIME
        $blnActualOutInBrk = false;
        if ($schedbrkin != null) {
          if ($actualout != null) {
            if (date('Y-m-d H:i', strtotime($actualout)) <= date('Y-m-d H:i', strtotime($schedbrkin))) {
              $blnActualOutInBrk = true;

              if (date('Y-m-d H:i', strtotime($actualout)) <= date('Y-m-d H:i', strtotime($schedbrkout))) {
                $undertime = $actualout->diffInMinutes($schedout, false);
                $undertime = $undertime - 60;
              } else {
                $undertime = $schedbrkin->diffInMinutes($schedout, false);
              }

              if ($undertime > 0) {
                $undertime = round($undertime / 60, 2);
              } else {
                $undertime = 0;
              }
            }
          }
        }

        if ($actualout != null) {
          if (!$blnActualOutInBrk) {
            $undertime = $actualout->diffInMinutes($schedout, false);
            if ($undertime > 0) {
              $undertime = round($undertime / 60, 2);
            } else {
              $undertime = 0;
            }
          }
        }

        $startndifftime = date('Y-m-d', strtotime($val->schedin)) . " " . date('H:i', strtotime($shift[0]->ndifffrom));
        $endif = Carbon::parse($val->schedout);
        $endndifftime = date('Y-m-d', strtotime($endif)) . " " . date('H:i', strtotime($shift[0]->ndiffto));

        // when schedule is set between night diff hrs
        $actualin = Carbon::parse($val->actualin);
        if (date('Y-m-d H:i', strtotime($actualin)) >= $startndifftime) {
          computendiffhere:
          $startndifftime = Carbon::parse($startndifftime);

          if ($actualout < $endndifftime) {
            if ($actualout != null) {
              $ndiff = $startndifftime->diffInMinutes($actualout, false);
            }
          } else {
            $ndiff = $startndifftime->diffInMinutes($endndifftime, false);
            goto computendiffothere;
          }

          $blnCheckOT = true;
        } elseif (date('Y-m-d H:i', strtotime($actualout)) >= $startndifftime) {
          goto  computendiffhere;
        } else {

          computendiffothere:
          // checking of night diff OT
          $blnCheckOT = true;
          if (date('Y-m-d H:i', strtotime($actualout)) > $startndifftime) {

            if ($schedout <  $startndifftime) {
              $endot = $startndifftime;
              $overtime = $schedout->diffInMinutes($endot, false);

              $endif = Carbon::parse($val->schedout);
              $endif->addDays(1);
              $endndifftime = date('Y-m-d', strtotime($endif)) . " " . date('H:i', strtotime($shift[0]->ndiffto));

              if ($actualout <= $endndifftime) {
                $startndifftime = Carbon::parse($startndifftime);
                $ndiffot = $startndifftime->diffInMinutes($actualout, false);
              }

              if ($ndiffot > 0) {
                $ndiffot = round($ndiffot / 60, 2);
              } else {
                $ndiffot = 0;
              }
              $blnCheckOT = false;
            }
          }
        }

        if ($actualout != null) {
          if ($blnCheckOT) {
            $schedout->addMinute($shift[0]->gbrkin);
            $overtime = $schedout->diffInMinutes($actualout, false);

            if ($overtime > 0) {
              $schedout = Carbon::parse($val->schedout);
              $overtime = $schedout->diffInMinutes($actualout, false);
            }
          }
        }

        if ($overtime > 0) {
          $overtime = round($overtime / 60, 2);
        } else {
          $overtime = 0;
        }

        if ($ndiff > 0) {
          $ndiff = round($ndiff / 60, 2);
        } else {
          $ndiff = 0;
        }
      }

      // // ACTUAL-IN AFTER THE SCHEDULED BREAK-IN
      // if ($schedbrkin != null && $schedbrkout != null) {
      //   if ($actualin > $schedbrkin) {
      //     $breakhrs = $schedbrkout->diffInMinutes(Carbon::parse($schedbrkin), false);
      //     if ($breakhrs > 0) {
      //       $breakhrs = round($breakhrs / 60, 2);
      //       $late -= $breakhrs;

      //       $this->coreFunctions->LogConsole('breakhrs: ' . $breakhrs);
      //     }
      //   }
      // }

      if ($undertime > 3) {
        $undertime = 0;
        $absent = 4;
      }

      $overtime = 0;
      $ndiffot = 0;

      $sql = "select apothrs, apndiffhrs, apndiffothrs, apothrsextra, daytype, ottimein, ottimeout from otapplication where empid=" . $val->empid . " and otstatus=2 and date(scheddate)='" . date('Y-m-d', strtotime($val->dateid)) . "'";
      $dataOT = $this->coreFunctions->opentable($sql);
      if (!empty($dataOT)) {

        switch ($dataOT[0]->daytype) {
          case 'LEG':
          case 'SP':
            $val->actualin = $dataOT[0]->ottimein;
            $val->actualout = $dataOT[0]->ottimeout;
            $val->reghrs = $dataOT[0]->apothrs;
            if ($dataOT[0]->apothrsextra != 0) {
              $overtime = $dataOT[0]->apothrsextra;
              if ($dataOT[0]->daytype == 'LEG') $val->legotapprvd = 1;
              if ($dataOT[0]->daytype == 'SP') $val->spotapprvd = 1;
            }
            if ($dataOT[0]->daytype == 'LEG') $val->legapprvd = 1;
            if ($dataOT[0]->daytype == 'SP') $val->spapprvd = 1;

            break;
          default:
            $overtime = $dataOT[0]->apothrs;
            $val->otapproved = 1;
            break;
        }

        if ($dataOT[0]->apndiffothrs != 0) {
          $ndiffot = $dataOT[0]->apndiffothrs;
          $val->Ndiffapproved = 1;
        }
      }


      //compute lates from breaks
      if ($actualbrkin != null && $actualbrkout != null) {
        $lunchlate = $actualbrkout->diffInMinutes($actualbrkin, false);
        $lunchlate -= 60;
        if ($lunchlate > 0) {
          $late2 += $lunchlate;
          $lunchlate = round($lunchlate / 60, 2);
          $late += $lunchlate;
        }
      }

      if ($shift[0]->flexit != 0) {
        if ($actualin != null && $actualout != null) {
          $actualhrs = $actualin->diffInMinutes($actualout, false);
          if ($actualhrs > 0) {
            $actualhrs = round($actualhrs / 60, 2);

            if ($actualhrs >= 8) {
              $late = 0;
              $late2 = 0;
              $undertime = 0;
              $absent = 0;
            }
            // $this->coreFunctions->LogConsole('flexi hrs: ' . $actualhrs);
          }
        }
      }

      if ($shift[0]->isonelog != 0) {
        if ($actualin != null || $actualout != null) {
          $late = 0;
          $late2 = 0;
          $undertime = 0;
          $absent = 0;
          $this->coreFunctions->LogConsole('isonelog hrs: ' . $val->reghrs);
        }
      }

      if ($blnWorkingDay) {
        $val->latehrs = $late;
        $val->latehrs2 = $late2;
        $val->underhrs = $undertime;
        $val->absdays = $absent;
        $val->othrs = $overtime;
        $val->ndiffot = $ndiffot;
        $val->ndiffhrs = $ndiff;
      } else {
        if ($val->reghrs > 8) {
          $val->reghrs = 8;
        }
        $val->reghrs = $val->reghrs - $late - $undertime;
        $val->latehrs = 0;
        $val->latehrs2 = 0;
        $val->underhrs = 0;
        $val->absdays = $absent;
        $val->othrs = $overtime;
        $val->ndiffot = $ndiffot;
        $val->ndiffhrs = $ndiff;
      }

      // $this->coreFunctions->LogConsole(json_encode($val));

      if ($val->latehrs2 > 59) { // auto half day if late is greater than 59 mins
        // $this->coreFunctions->LogConsole('Auto half day applied for empid: ' . $val->empid . ' dateid: ' . $val->dateid . ' late: ' . $val->latehrs . ' late2 mins: ' . $val->latehrs2);
        $val->absdays =  $val->reghrs / 2;
        $val->latehrs = 0;
        $val->latehrs2 = 0;
      }


      //checking of late offset
      $allowlatemins = 15;
      if ($val->issupervisor || $val->isapprover) $allowlatemins = 30;

      if ($val->latehrs != 0) {
        $offsetlate = $this->getOffsetLate($val->empid, $val->dateid);
        $allowlatemins = $allowlatemins - $offsetlate;

        // $this->coreFunctions->LogConsole('allowlatemins: ' . $allowlatemins . ' late mins: ' . ($val->latehrs * 60) . ' late2 mins: ' . $val->latehrs2);

        if ($allowlatemins > 0) {
          if ($allowlatemins >= $val->latehrs2) {
            $val->lateoffset = $val->latehrs2;
            $val->latehrs = 0;
            $val->latehrs2 = 0;
          } else {
            $totallate = $val->latehrs2 - $allowlatemins;
            $val->lateoffset = $allowlatemins;
            $val->latehrs = round($totallate / 60, 2);
            $val->latehrs2 = $totallate;
          }
        }
      }

      if ($blnNoDeduct) { //used in SP
        $val->latehrs = 0;
        $val->latehrs2 = 0;
        $val->underhrs = 0;
        $val->absdays = 0;
      }

      unset($val->issupervisor);
      unset($val->isapprover);
      unset($val->classrate);

      $val = json_decode(json_encode($val), true);

      $val["dateid"] = $this->othersClass->sanitizekeyfield('dateonly', $val["dateid"]);

      // $this->coreFunctions->LogConsole(json_encode($val));

      $this->coreFunctions->sbcupdate("timecard", $val, ['empid' => $val["empid"], 'dateid' => $val["dateid"]]);
    }

    return ['status' => true, 'msg' => 'Compute Success', 'action' => 'load'];
  }

  public function computetimecard_onesky($config, $blnExtract = false)
  {
    ini_set('max_execution_time', -1);
    ini_set('memory_limit', '-1');

    $empid = $config['params']['dataparams']['empid'];
    $start = $config['params']['dataparams']['startdate'];
    $end = $config['params']['dataparams']['enddate'];
    $empdivid = $config['params']['dataparams']['empdivid'];
    $checkall = $config['params']['dataparams']['checkall'] == "1" ? true : false;

    if ($checkall) {
      $empid = 0;

      if ($empdivid == 0) {
        return ['status' => false, 'msg' => 'Please select valid Company'];
      }
    }

    $start = date('Y-m-d', strtotime($start));
    $end = date('Y-m-d', strtotime($end));

    $filteremplvl = '';
    if ($blnExtract) {
    } else {
      $emplvl = $this->othersClass->checksecuritylevel($config);
      $filteremplvl = " and e.level in $emplvl";
    }

    $this->coreFunctions->LogConsole('->computetimecard-resetdays');
    //RESET DAYTYPE
    $qry = "update timecard as t left join employee as e on e.empid = t.empid 
      set t.daytype ='WORKING' 
      where t.daytype not in('WORKING','RESTDAY') and date(t.DateID) between '" . $start . "' and '"  . $end . "' and e.isactive=1 " . $filteremplvl;

    if (!$checkall) {
      $qry .= " and e.empid=" . $empid;
    }

    $this->coreFunctions->execqry($qry);

    $this->coreFunctions->LogConsole('->computetimecard-holiday');
    $qry = "select date(dateid) as dateid,daytype,divcode from holiday where date(dateid) between '" . $start . "' and '" . $end . "' order by dateid";
    $holiday = $this->coreFunctions->opentable($qry);

    if (!empty($holiday)) {
      $this->coreFunctions->LogConsole('->computetimecard-holidays');
      foreach ($holiday as $k => $val) {
        $qry = "update timecard  as t left join employee as e on e.empid = t.empid ";
        $qry .= "set t.daytype = case when t.daytype = 'WORKING' then '" . $val->daytype . "' ";
        if ($val->daytype == 'LEG') {
          $qry .= "when daytype = 'RESTDAY' then 'LEG' ";
        }
        $qry .= "else t.daytype end ";

        $qry .= "where t.dateid='" . $val->dateid . "' and e.isactive=1 " . $filteremplvl;
        if (!$checkall) {
          $qry .= "and t.empid=" . $empid;
        }
        $this->coreFunctions->execqry($qry);
      }
    }

    $this->coreFunctions->LogConsole('->computetimecard-resetOT');
    //reset OT
    $qry = "update timecard as t left join employee as e on e.empid = t.empid 
      set otapproved = 0, ndiffapproved = 0, isprevwork =0, RDapprvd=0, 
      RDOTapprvd=0, LEGapprvd=0, LEGOTapprvd=0, SPapprvd=0, SPOTapprvd=0, ndiffsapprvd=0, earlyotapproved=0 
      where t.dateid between '" . $start . "' and '" . $end . "' and e.isactive=1 " . $filteremplvl;
    if (!$checkall) {
      $qry .= "and e.empid=" . $empid;
    }
    $this->coreFunctions->execqry($qry);

    $qry = "select schedin, schedout from timecard as t";
    if (!$checkall) {
      $qry .= "and e.empid=" . $empid;
    }

    $this->coreFunctions->LogConsole('->computetimecard-getschedule');
    $data = $this->getempschedule($empid, $start, $end, 0, 0, $filteremplvl);

    if (empty($data)) {
      $this->coreFunctions->LogConsole('No schedule');
    }

    foreach ($data as $key => $val) {

      unset($val->issupervisor);
      unset($val->isapprover);
      unset($val->classrate);

      $shift = $this->payrollcommon->getShiftDetails($val->empid);

      unset($val->bgcolor);

      $absent = 0;
      $late = 0;
      $late2 = 0;
      $breakoutlate = 0;
      $breakinlate = 0;
      $undertime = 0;
      $overtime = 0;
      $earylot = 0;
      $ndiffot = 0;
      $ndiff = 0;
      $ambreak = 0;

      $blnCheckOT = false;
      $blnWorkingDay = true;
      $blnNoLate = false;
      $blnNoOTRestDay = false;

      $schedin = $val->schedin == null ? $val->schedin : Carbon::parse($val->schedin);
      $actualin = $val->actualin == null ? $val->actualin : Carbon::parse($val->actualin);

      $schedout = $val->schedout == null ? $val->schedout : Carbon::parse($val->schedout);
      $actualout = $val->actualout == null ? $val->actualout : Carbon::parse($val->actualout);

      $schedbrkout = $val->schedbrkout == null ? $val->schedbrkout : Carbon::parse($val->schedbrkout);
      $actualbrkout = $val->actualbrkout == null ? $val->actualbrkout : Carbon::parse($val->actualbrkout);

      $schedbrkin = $val->schedbrkin == null ? $val->schedbrkin : Carbon::parse($val->schedbrkin);
      $actualbrkin = $val->actualbrkin == null ? $val->actualbrkin : Carbon::parse($val->actualbrkin);

      $brk1stin = $val->brk1stin == null ? $val->brk1stin : Carbon::parse($val->brk1stin);
      $brk1stout = $val->brk1stout == null ? $val->brk1stout : Carbon::parse($val->brk1stout);

      if ($brk1stin  != null) {
        $ambreak = $brk1stout->diffInMinutes($brk1stin, false);
        if ($ambreak > 0) {
          $ambreak = $ambreak / 60;
        }
      }

      // early OT calculation
      if ($actualin != null) {
        $earylot = $actualin->diffInMinutes($schedin, false);

        if ($earylot > 0)
          $earylot =   $this->convertOneskyOT($earylot);
        else
          $earylot = 0;
      }

      // Logger("Timecard: " . $schedin . " - " . $val->actualin);
      // Logger("Early OT: " .  $earylot);

      switch ($val->daytype) {
        case 'RESTDAY':
        case 'LEG':
        case 'SP':
          if ($val->actualin == null && $val->actualout == null) {
            $val->reghrs = 0;
          } else {
            $val->reghrs = $actualin->diffInMinutes($actualout, false);
            if ($val->reghrs > 0) {
              $val->reghrs = round($val->reghrs / 60, 2);
              if ($val->reghrs <= 8) {
                $blnNoOTRestDay = true;
              }
            } else {
              $val->reghrs = 0;
            }
            $blnNoLate = true;
          }
          $blnWorkingDay = false;
          if ($val->daytype == 'LEG') {
            $val->isprevwork = $this->checkvalidleghrs($val->empid, $val->dateid);
          }
          break;
      }

      if ($val->actualin == null && $val->actualout == null) {
        $absent = $val->reghrs;
      } else {
        // $actualin =  $actualin_gtin->addMinute($shift[0]->gtin * -1);
        $late = $schedin->diffInMinutes($actualin, false);

        if ($shift[0]->gtin != 0) {
          if ($late > 0) {
            $late -= $shift[0]->gtin;
          }
        }

        if ($late > 0) {
          $late = $schedin->diffInMinutes($actualin, false);
        }

        if ($schedbrkout != null && $schedbrkin != null) {
          if ($actualin >= $schedbrkout && $actualin <= $schedbrkin) {
            $late = $schedin->diffInMinutes($schedbrkout, false);
          }
        }

        if ($late > 0) {
          $late = round($late / 60, 2);
          $late2 = $late;
        } else {
          $late = 0;
          $late2 = 0;
        }

        //EARLY BREAKOUT
        if ($actualbrkout != null) {
          if ($schedbrkout != null) {
            $breakoutlate = $actualbrkout->diffInMinutes($schedbrkout, false);
            if ($breakoutlate > 0) {
              $breakoutlate = round($breakoutlate / 60, 2);
            } else {
              $breakoutlate = 0;
            }
            $late += $breakoutlate;
          }
        }

        //LATE BREAKIN
        if ($actualbrkin != null) {
          if ($schedbrkin != null) {
            $breakinlate = $schedbrkin->diffInMinutes($actualbrkin, false);
            if ($breakinlate > 0) {
              $breakinlate = round($breakinlate / 60, 2);
            } else {
              $breakinlate = 0;
            }
            $late += $breakinlate;
          }
        }

        //not include AM break
        if ($brk1stin  != null) {
          if ($actualin > $brk1stout) {
            $late -= $ambreak;
          }
        }

        //UNDERTIME
        $blnActualOutInBrk = false;
        if ($schedbrkin != null) {
          if ($actualout != null) {
            if (date('Y-m-d H:i', strtotime($actualout)) <= date('Y-m-d H:i', strtotime($schedbrkin))) {
              $blnActualOutInBrk = true;

              if (date('Y-m-d H:i', strtotime($actualout)) <= date('Y-m-d H:i', strtotime($schedbrkout))) {
                $undertime = $actualout->diffInMinutes($schedout, false);
                $undertime = $undertime - 60;
              } else {
                $undertime = $schedbrkin->diffInMinutes($schedout, false);
              }

              if ($undertime > 0) {
                $undertime = round($undertime / 60, 2);
              } else {
                $undertime = 0;
              }
            }
          }
        }

        if ($actualout != null) {
          if (!$blnActualOutInBrk) {
            $undertime = $actualout->diffInMinutes($schedout, false);
            if ($undertime > 0) {
              $undertime = round($undertime / 60, 2);
            } else {
              $undertime = 0;
            }
          }
        }

        $startndifftime = date('Y-m-d', strtotime($val->schedin)) . " " . date('H:i', strtotime($shift[0]->ndifffrom));
        $endif = Carbon::parse($val->schedout);
        $endndifftime = date('Y-m-d', strtotime($endif)) . " " . date('H:i', strtotime($shift[0]->ndiffto));

        if ($blnNoOTRestDay) goto skipOTHere;

        // when schedule is set between night diff hrs
        $actualin = Carbon::parse($val->actualin);
        if (date('Y-m-d H:i', strtotime($actualin)) >= $startndifftime) {
          computendiffhere:
          $startndifftime = Carbon::parse($startndifftime);

          if ($actualout < $endndifftime) {
            if ($actualout != null) {
              $ndiff = $startndifftime->diffInMinutes($actualout, false);
            }
          } else {
            $ndiff = $startndifftime->diffInMinutes($endndifftime, false);
            goto computendiffothere;
          }

          $blnCheckOT = true;
        } elseif (date('Y-m-d H:i', strtotime($actualout)) >= $startndifftime) {
          goto  computendiffhere;
        } else {

          computendiffothere:
          // checking of night diff OT
          $blnCheckOT = true;
          if (date('Y-m-d H:i', strtotime($actualout)) > $startndifftime) {

            if ($schedout <  $startndifftime) {
              // $endot = $startndifftime;
              // $overtime = $schedout->diffInMinutes($endot, false);

              $endif = Carbon::parse($val->schedout);
              $endif->addDays(1);
              $endndifftime = date('Y-m-d', strtotime($endif)) . " " . date('H:i', strtotime($shift[0]->ndiffto));

              if ($actualout <= $endndifftime) {
                $startndifftime = Carbon::parse($startndifftime);
                $ndiffot = $startndifftime->diffInMinutes($actualout, false);
              }

              if ($ndiffot > 0) {
                $ndiffot = round($ndiffot / 60, 2);
              } else {
                $ndiffot = 0;
              }
              // $blnCheckOT = false;
            }
          }
        }

        if ($actualout != null) {
          if ($blnCheckOT) {
            $schedout->addMinute($shift[0]->gbrkin);
            $overtime = $schedout->diffInMinutes($actualout, false);

            if ($overtime > 0) {
              $schedout = Carbon::parse($val->schedout);
              $overtime = $schedout->diffInMinutes($actualout, false);

              $overtime =   $this->convertOneskyOT($overtime);
            } else {
              $overtime = 0;
            }

            // Logger("Timecard: " . $val->schedout . " - " . $val->actualout);
            // Logger("Overtime: " . $overtime);
          }
        }


        if ($ndiff > 0) {
          $ndiff = round($ndiff / 60, 2);
        } else {
          $ndiff = 0;
        }
      }

      skipOTHere:
      // ACTUAL-IN AFTER THE SCHEDULED BREAK-IN
      if ($schedbrkin != null && $schedbrkout != null) {
        if ($actualin > $schedbrkin) {
          $breakhrs = $schedbrkout->diffInMinutes(Carbon::parse($schedbrkin), false);
          if ($breakhrs > 0) {
            $breakhrs = round($breakhrs / 60, 2);
            $late -= $breakhrs;
          }
        }
      }


      if ($shift[0]->isfixhrs) {
        switch ($val->daytype) {
          case 'RESTDAY':
          case 'LEG':
          case 'SP':
            if ($val->reghrs != 0) {
              $val->reghrs = 8;
              $overtime = 4;
              $late = 0;
              $late2 = 0;
              $undertime = 0;
              $ndiff = 0;
              $ndiffot = 0;
            }
            break;
          case 'WORKING':
            if ($absent == 0) {
              $val->reghrs = 8;
              $overtime = 4;
              $late = 0;
              $late2 = 0;
              $undertime = 0;
              $ndiff = 0;
              $ndiffot = 0;
            }
            break;
        }
      } else {
        if ($blnNoLate) {
          $late = 0;
          $late2 = 0;
        }
      }

      if ($blnWorkingDay) {
        $val->latehrs = $late;
        $val->latehrs2 = $late2;
        $val->underhrs = $undertime;
        $val->absdays = $absent;
        $val->othrs = $overtime;
        $val->earlyothrs = $earylot;
        $val->ndiffot = $ndiffot;
        $val->ndiffhrs = $ndiff;
      } else {
        if ($val->reghrs > 8) {
          $val->reghrs = 8;
        }
        $val->reghrs = $val->reghrs - $late - $undertime;
        $val->latehrs = 0;
        $val->latehrs2 = 0;
        $val->underhrs = 0;
        $val->earlyothrs = 0;
        $val->absdays = $absent;
        $val->othrs = $overtime;
        $val->ndiffot = $ndiffot;
        $val->ndiffhrs = $ndiff;
      }

      $val = json_decode(json_encode($val), true);

      $val["dateid"] = $this->othersClass->sanitizekeyfield('dateonly', $val["dateid"]);

      if (!$this->coreFunctions->sbcupdate("timecard", $val, ['empid' => $val["empid"], 'dateid' => $val["dateid"]])) {
        return ['status' => false, 'msg' => 'Failed to compute timecard', 'action' => 'load'];
      }
    }

    return ['status' => true, 'msg' => 'Compute Success', 'action' => 'load'];
  }

  private function convertOneskyOT($totalOT)
  {
    if ($totalOT  > 0) {
      //rounding of OT to nearest hour (50mins up to 1hr)
      $a = 0;
      $b = 60;
      $c = 0;
      $d = 0;

      recheckOTHere:
      if ($a <= $totalOT  && $b > $totalOT) {
        $d = $totalOT  - $a;
        if ($d > 49) {
          $c = $a + 60;
        } else {
          $c = $a;
        }
      } else {
        $a += 60;
        $b += 60;
        goto recheckOTHere;
      }

      $overtime = round($c / 60, 2);
    } else {
      $overtime = 0;
    }

    return $overtime;
  }

  public function convertOT($totalOT, $rouding)
  {
    if ($totalOT  > 0) {
      $a = 0;
      $b = $rouding;
      $c = 0;

      recheckOTHere:
      if ($a <= $totalOT  && $b > $totalOT) {
        $c = $a;
      } else {
        $a += $rouding;
        $b += $rouding;
        goto recheckOTHere;
      }

      $overtime = $c;
    } else {
      $overtime = 0;
    }

    return $overtime;
  }

  private function getOffsetLate($empid, $dateid)
  {
    $start = $dateid;
    $end = $dateid;

    $year = date('Y', strtotime($dateid));
    $month = date('n', strtotime($dateid));

    $workday = date('d', strtotime($dateid));
    if ($workday >= 1 && $workday <= 15) {
      $prevmon = $month - 1;
      if ($year == 1) $year = $year - 1;
      $start = date('Y-m-d', strtotime("$year-$prevmon-16"));
    } else {
      $start = date('Y-m-d', strtotime("$year-$month-1"));
    }

    $qry = "select sum(lateoffset) as value from timecard where empid=" . $empid . " and date(dateid) between '" . $start . "' and '" . $end . "'";
    // $this->coreFunctions->LogConsole($qry);
    $offsetlate = $this->coreFunctions->datareader($qry, [], '', true);

    return $offsetlate;
  }

  private function checkvalidleghrs($empid, $dateid)
  {
    $dateid = date('Y-m-d', strtotime($dateid));

    $valid = $this->coreFunctions->datareader("SELECT reghrs AS value FROM timecard WHERE empid=" . $empid . " AND dateid<'" . $dateid . "' AND daytype='WORKING' ORDER BY dateid DESC LIMIT 1", [], '', true);
    if ($valid != 0) {
      return 1;
    } else {
      $valid = $this->coreFunctions->datareader("SELECT reghrs AS value FROM timecard WHERE empid=" . $empid . " AND dateid>'" . $dateid . "' AND daytype='WORKING' ORDER BY dateid ASC LIMIT 1", [], '', true);
      if ($valid != 0) {
        return 1;
      }
    }
    return 0;
  }

  private function getempschedule($empid, $start, $end, $branchid = 0, $divid = 0, $filteremplvl = '')
  {

    $start = date('Y-m-d', strtotime($start));
    $end = date('Y-m-d', strtotime($end));

    $filter = "";
    if ($empid != 0) {
      $filter = " and t.empid=" . $empid;
    } else {
      if ($divid != 0) $filter .= " and e.divid=" . $divid;
      if ($branchid != 0) $filter .= " and e.branchid=" . $branchid;
    }

    $qry = "select t.empid, t.`daytype`, date(t.dateid) as dateid, 
      date_format(t.schedin,'%Y-%m-%d %H:%i') as schedin, date_format(t.schedout,'%Y-%m-%d %H:%i') as schedout,
      date_format(t.schedbrkin,'%Y-%m-%d %H:%i') as schedbrkin, date_format(t.schedbrkout,'%Y-%m-%d %H:%i') as schedbrkout, 
      date_format(t.actualin,'%Y-%m-%d %H:%i') as actualin, date_format(t.actualout,'%Y-%m-%d %H:%i') as actualout, 
      date_format(t.actualbrkin,'%Y-%m-%d %H:%i') as actualbrkin, date_format(t.actualbrkout,'%Y-%m-%d %H:%i') as actualbrkout, 
      t.reghrs, t.absdays, t.latehrs, t.underhrs, t.othrs, t.ndiffhrs, t.ndiffot, 0 as isprevwork,
      date_format(t.brk1stin,'%Y-%m-%d %H:%i') as brk1stin,date_format(t.brk1stout,'%Y-%m-%d %H:%i') as brk1stout,
      date_format(t.brk2ndin,'%Y-%m-%d %H:%i') as brk2ndin,date_format(t.brk2ndout,'%Y-%m-%d %H:%i') as brk2ndout,
      t.otapproved, t.ndiffsapprvd, t.Ndiffapproved, t.legapprvd, t.legotapprvd,  t.spapprvd, t.spotapprvd, e.issupervisor, e.isapprover, t.earlyothrs,e.classrate,
      t.sphrs, '' as bgcolor
      from timecard as t left join employee as e on e.empid=t.empid
      where date(t.dateid)>='" . $start . "' and date(t.dateid)<='" . $end . "'" . $filter  . $filteremplvl . " order by t.dateid";
    // $this->coreFunctions->LogConsole($qry);
    return $this->coreFunctions->opentable($qry);
  }

  private function computetimesheet($config)
  {
    ini_set('max_execution_time', 0);
    set_time_limit(0);

    $user = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $checkall = $config['params']['dataparams']['checkall'] == "1" ? true : false;
    $empid = $config['params']['dataparams']['empid'];

    $batch = $config['params']['dataparams']['batch'];
    $paygroup = $config['params']['dataparams']['pgroup'];
    $epaygroup = $config['params']['dataparams']['paygroup'];

    if ($batch == '') {
      return ['status' => false, 'msg' => 'Please select valid Batch.', 'action' => 'load'];
    }

    $emplog = '';

    if (!$checkall) {
      if ($empid == 0) {
        return ['status' => false, 'msg' => 'Please select valid Employee.', 'action' => 'load'];
      }

      if (isset($config['params']['dataparams']['empname'])) {
        if ($config['params']['dataparams']['empname'] == '') {
          return ['status' => false, 'msg' => 'Please select valid Employee.', 'action' => 'load'];
        }
      } else {
        return ['status' => false, 'msg' => 'Please select valid Employee.', 'action' => 'load'];
      }

      $emplog =  $config['params']['dataparams']['empname'];
    } else {
      if ($paygroup == "0") $paygroup = '';
      if ($epaygroup == "0") $epaygroup = '';
      if ($paygroup != "" && $paygroup != $epaygroup) {
        return ['status' => false, 'msg' => $epaygroup . ' pay group of selected employee doesn`t match in batch pay group ' . $epaygroup, 'action' => 'load'];
      }

      $emplog = 'all employees';
    }

    $batchid = $config['params']['dataparams']['batchid'];
    $batchdate = $config['params']['dataparams']['batchdate'];
    $batchcode = $config['params']['dataparams']['batch'];

    $start = $config['params']['dataparams']['sdate1'];
    $end = $config['params']['dataparams']['sdate2'];

    $start = date('Y-m-d', strtotime($start));
    $end = date('Y-m-d', strtotime($end));


    $this->logger->sbcmasterlog2(0, $config, 'Compute timesheet for ' . $emplog . '. From ' . $start . '  to ' . $end, 'payroll_log', 1);

    $msg = '';

    $this->coreFunctions->execqry("update employee set ismanualts=0 where empid=" . $empid, "update");

    if ($checkall) {
      $employee = $this->payrollcommon->getAllowEmployees($config);
      foreach ($employee as $key =>  $val) {
        switch ($companyid) {
          case 58: //cdo
            $result = $this->payrollcommon->computeemptimesheet_cdo($batchid, $batchdate, $val->empid, $start, $end, $user, $batch, $config['params']);
            break;
          case 62: //onesky
            $result = $this->payrollcommon->computeemptimesheet_onesky($batchid, $batchdate, $val->empid, $start, $end, $user, $batch, $config['params'], false);
            break;
          default:
            $result = $this->payrollcommon->computeemptimesheet($batchid, $batchdate, $val->empid, $start, $end, $user, $batch, $config['params']);
            break;
        }

        if (!$result['status']) {
          $msg .= $val->empname . " failed. " . $result['msg'] . ".";
        }
      }
      $status = true;
      if ($msg != '') {
        $status = false;
      }
      return ['status' =>  $status, 'msg' => 'Compute timesheet finished. ' . $msg, 'action' => 'load'];
    } else {
      $empname = $config['params']['dataparams']['empname'];
      switch ($companyid) {
        case 58: //cdo
          $result =  $this->payrollcommon->computeemptimesheet_cdo($batchid, $batchdate, $empid, $start, $end, $user, $batch, $config['params']);
          break;
        case 62: //onesky
          $result =  $this->payrollcommon->computeemptimesheet_onesky($batchid, $batchdate, $empid, $start, $end, $user, $batch, $config['params'], false);
          break;
        default:
          $result =  $this->payrollcommon->computeemptimesheet($batchid, $batchdate, $empid, $start, $end, $user, $batch, $config['params']);
          break;
      }

      if (!$result['status']) {
        $msg =  $empname . " failed. " . $result['msg'] . ".";
      }
      $status = true;
      if ($msg != '') {
        $status = false;
      }
      return ['status' => $status, 'msg' => 'Compute timesheet finished. ' . $msg, 'action' => 'load'];
    }
  }

  public function postactualinout($config)
  {
    ini_set('max_execution_time', -1);
    ini_set('memory_limit', '-1');

    $companyid = $config['params']['companyid'];

    $status = true;
    $msg = '';
    $checkall = $config['params']['dataparams']['checkall'] == "1" ? true : false;
    $empid = $config['params']['dataparams']['empid'];

    $start = $config['params']['dataparams']['startdate'];
    $end = $config['params']['dataparams']['enddate'];

    $start = date('Y-m-d', strtotime($start));
    $end = date('Y-m-d', strtotime($end));

    $paymode = $config['params']['dataparams']['paymodeemp'];
    $paygroup = $config['params']['dataparams']['paygroup'];

    $paymode = substr($paymode, 0, 1);

    $empdivid = $config['params']['dataparams']['empdivid'];
    $empbranchid = $config['params']['dataparams']['empbranchid'];

    if ($companyid == 58 || $companyid == 62) { //cdo, onesky
      if ($empdivid == 0) {
        return ['status' => false, 'msg' => 'Please select valid Company'];
      }
    }

    // if ($paygroup == '') {
    //   return ['status' => false, 'msg' => 'Select valid Pay Group. ', 'action' => 'load'];
    // }

    // if ($paymode == '') {
    //   return ['status' => false, 'msg' => 'Select valid Mode of Payment. ', 'action' => 'load'];
    // }

    $config['params']['dataparams']['paymode'] = $paymode;
    $config['params']['dataparams']['pgroup'] = $paygroup;

    $emplog = '';

    if (!$checkall) {
      if ($empid == 0) {
        return ['status' => false, 'msg' => 'Select valid employee. ', 'action' => 'load'];
      }
      $emplog = $config['params']['dataparams']['empname'];
    } else {
      $emplog = 'all employees.';
    }

    $this->logger->sbcmasterlog2(0, $config, 'Post Actual In/Out ' . $emplog, 'payroll_log', 1);

    $employee = $this->payrollcommon->getAllowEmployees($config, 0, 'POSTINOUT');

    if ($employee) {
      if ($checkall) {
        foreach ($employee as $key =>  $val) {
          switch ($companyid) {
            case 58: //cdo
              $result = $this->payrollcommon->postactualinout_cdo($config, $val->empid, $start, $end, $checkall, $config['params']['dataparams']['pgroup'], $config['params']['dataparams']['paymode']);
              break;
            default:
              $result = $this->payrollcommon->postactualinout($config, $val->empid, $start, $end, $checkall, $config['params']['dataparams']['pgroup'], $config['params']['dataparams']['paymode']);
              break;
          }

          if (!$result['status']) {
            $msg .=  $val->empname . " failed. " . $result['msg'] . "...";
            $status = false;
          }
        }
      } else {
        $empname = $config['params']['dataparams']['empname'];
        switch ($companyid) {
          case 58: //cdo
            $result = $this->payrollcommon->postactualinout_cdo($config, $empid, $start, $end, $checkall, $config['params']['dataparams']['pgroup'], $config['params']['dataparams']['paymode']);
            break;
          default:
            $result = $this->payrollcommon->postactualinout($config, $empid, $start, $end, $checkall, $config['params']['dataparams']['pgroup'], $config['params']['dataparams']['paymode']);
            break;
        }

        if (!$result['status']) {
          $msg .=  $empname . " failed. " . $result['msg'] . "...";
          $status = false;
        }
      }
    } else {
      $msg = "No employee has this setup of PayGroup: " . $config['params']['dataparams']['tpaygroup'] . " and ModeOfPayment: " . $config['params']['dataparams']['paymodeemp'];
      $status = false;
    }

    if ($msg == '') {
      $msg = 'Posting actual IN/OUT finished. ';
    }

    return ['status' => $status, 'msg' => $msg, 'action' => 'load'];
  }
} //end class
