<?php

namespace App\Http\Classes\modules\customform;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;
use App\Http\Classes\common\linkemail;
use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;

class viewleaveapplication
{
  private $fieldClass;
  private $tabClass;
  private $logger;
  public $modulename = 'LEAVE APPLICATION';
  public $gridname = 'customformacctg';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $linkemail;
  public $tablelogs = 'payroll_log';
  public $style = 'width:1200px;max-width:1200px;';
  public $issearchshow = true;
  public $showclosebtn = true;
  public $fields = ['status', 'status2', 'date_approved_disapproved', 'date_approved_disapproved2', 'approvedby_disapprovedby', 'approvedby_disapprovedby2', 'disapproved_remarks', 'disapproved_remarks2', 'catid'];
  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->linkemail = new linkemail;
  }

  public function createTab($config)
  {
    $obj = [];
    return $obj;
  }

  public function getAttrib()
  {
    return [];
  }

  public function createtabbutton($config)
  {
    $obj = [];
    return $obj;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];
    $url = 'App\Http\Classes\modules\payrollentry\\' . 'leaveapplicationportalapproval';
    $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='LEAVE'");
    if ($approversetup == '') {
      $approversetup = app($url)->approvers($config['params']);
    } else {
      $approversetup = explode(',', $approversetup);
    }
    $leavelabel = $this->companysetup->getleavelabel($config['params']);
    $approveby = $config['params']['row']['approvedby2'];
    $status = $config['params']['row']['status2'];
    $admin = $config['params']['adminid'];
    $fapprover = $this->coreFunctions->getfieldvalue("client", "clientname", "client=?", [$approveby]);
    $approver = $this->coreFunctions->getfieldvalue("employee", "isapprover", "empid=?", [$admin]);
    $supervisor = $this->coreFunctions->getfieldvalue("employee", "issupervisor", "empid=?", [$admin]);
    $bothapprover = false;
    if ($companyid == 58) { //cdohris
      if ($supervisor && $approver) {
        $bothapprover = true;
      }
    }
    $fields = ['clientname', 'lblmessage', 'remarks'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'remarks.readonly', true);


    data_set($col1, 'lblmessage.label', 'Employee Remarks');

    if ($companyid == 53) { // camera
      data_set($col1, 'remarks.label', 'Reason');
      data_set($col1, 'lblmessage.label', 'Employee Reason');
    }
    data_set($col1, 'lblmessage.style', 'font-size:11px;font-weight:bold;');

    $fields = ['dateid'];
    if (count($approversetup) > 1) {
      if ($bothapprover) {
        array_push($fields, 'lblrem', 'rem');
      } else {
        array_push($fields, 'lblrem', 'remark', 'lblapproved', 'rem');
      }
    } else {
      array_push($fields, 'lblrem', 'rem');
    }
    $col2 = $this->fieldClass->create($fields);
    if (!empty($approveby)) {
      data_set($col2, 'remark.readonly', true);
      data_set($col2, 'rem.readonly', false);
    } else {
      data_set($col2, 'remark.readonly', false);
      data_set($col2, 'rem.readonly', true);
      if (count($approversetup) == 1) {
        data_set($col2, 'rem.readonly', false);
      }
    }
    data_set($col2, 'remark.style', 'padding:0px');
    data_set($col2, 'dateid.type', 'input');
    data_set($col2, 'dateid.label', 'Date Applied');
    data_set($col2, 'rem.label', 'Remarks');

    data_set($col2, 'lblrem.label', 'First Approver: ' . $fapprover);
    data_set($col2, 'lblrem.style', 'font-size:11px;font-weight:bold;');

    if ($bothapprover) { //for cdohris
      data_set($col2, 'lblrem.label', 'Approver: ');
      data_set($col2, 'remarks.readonly', false);
    }
    data_set($col2, 'lblapproved.label', 'Second Approver: ');
    data_set($col2, 'lblapproved.style', 'font-size:11px;font-weight:bold;');

    if ($companyid == 53) { // camera
      data_set($col2, 'remark.label', 'Reason');
      data_set($col2, 'rem.label', 'Reason');
    }

    $fields = ['acnoname', ['days', 'bal'], 'effectdate', 'hours', 'status',];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'hours.label', 'Leave ' . $leavelabel);
    data_set($col3, 'hours.readonly', true);
    data_set($col3, 'days.readonly', true);
    data_set($col3, 'acnoname.label', 'Leave Type');
    data_set($col3, 'acnoname.readonly', true);
    data_set($col3, 'effectdate.style', 'padding:0px');

    $fields = ['refresh', 'post', 'disapproved'];
    switch ($companyid) {
      case 53: //camera
        if ($status == 'P') $fields = ['post', 'disapproved'];
        break;
      case 44: //stonepro
        if (empty($fapprover)) $fields = [['refresh', 'disapproved']];
        break;
      case 58: //cdo
        $fields = ['category', ['refresh', 'disapproved'], 'post'];

        if (!$bothapprover) {
          if (empty($approveby)) {
            unset($fields[0]);
            unset($fields[2]);
          }
        }
        break;
    }
    $col4 = $this->fieldClass->create($fields);

    data_set($col4, 'refresh.label', 'APPROVED');
    data_set($col4, 'disapproved.label', 'DISAPPROVED');
    data_set($col4, 'post.label', 'PROCESSED');
    data_set($col4, 'disapproved.color', 'red');
    data_set($col4, 'post.color', 'orange');
    data_set($col4, 'refresh.color', 'blue');
    data_set($col4, 'refresh.confirm', true);
    data_set($col4, 'post.confirm', true);
    data_set($col4, 'disapproved.confirm', true);
    data_set($col4, 'hold.confirm', true);
    data_set($col4, 'refresh.confirmlabel', 'Approved this Leave application?');
    data_set($col4, 'post.confirmlabel', 'Processed this Leave application?');
    data_set($col4, 'disapproved.confirmlabel', 'Disapproved this Leave application?');
    data_set($col4, 'onhold.confirmlabel', 'Hold this Leave application?');

    if ($companyid == 53) { // camera
      data_set($col4, 'refresh.label', 'APPROVED WITH PAY');
      data_set($col4, 'post.label', 'APPROVED W/OUT PAY');
      data_set($col4, 'refresh.confirmlabel', 'Approved with pay this Leave application?');
      data_set($col4, 'post.confirmlabel', 'Approved w/out pay this Leave application?');
    }
    if ($companyid == 58) { // cdohris
      data_set($col4, 'category.lookupclass', 'dashboard');
      data_set($col4, 'category.doc', 'leaveapplication');

      data_set($col4, 'category.action', 'lookupleavecategory');
      data_set($col4, 'category.required', true);
      data_set($col4, 'category.label', 'Leave Category');
    }
    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function paramsdata($config)
  {
    $catid = isset($config['params']['row']['catid']) ? $config['params']['row']['catid'] : 0;
    return $this->coreFunctions->opentable("select client.client, l.trno, l.line,l.empid,ls.days  as entitled,ls.bal  as balance,p.codename as acnoname, 
    (case when $catid <> 0 then $catid else '0' end) as catid,
    case
      when l.status = 'A' then 'APPROVED'
      when l.status = 'E' then 'ENTRY'
      when l.status = 'O' then 'ON-HOLD'
      when l.status = 'P' then 'PROCESSED'
      when l.status = 'D' then 'DISAPPROVED'
    end as status, client.clientname, date(l.dateid) as dateid, date(l.effectivity) as effectdate, l.adays as hours, l.remarks,l.disapproved_remarks as rem,l.disapproved_remarks2 as remark,ls.bal,ls.days
    from leavetrans as l 
    left join client on client.clientid=l.empid
    left join leavesetup as ls on ls.trno = l.trno
    left join paccount as p on p.line=ls.acnoid
    where l.status = 'E' and l.trno = ? and l.line=?", [$config['params']['row']['trno'], $config['params']['row']['line']]);
  }

  public function data()
  {
    return [];
  }

  public function loaddata($config)
  {
    $trno = $config['params']['dataparams']['trno'];
    $rem = $config['params']['dataparams']['rem'];
    $rem2 = $config['params']['dataparams']['remark'];
    $x = $config['params']['dataparams']['status'];
    $companyid = $config['params']['companyid'];
    $admin = $config['params']['adminid'];
    $empid = $config['params']['dataparams']['empid'];
    $reqleave = $config['params']['dataparams']['hours'];
    $balance = $config['params']['dataparams']['balance'];
    $entitled = $config['params']['dataparams']['entitled'];
    $catid = $config['params']['dataparams']['catid'];

    $dateid = $config['params']['dataparams']['dateid'];
    $empname = $config['params']['dataparams']['clientname'];
    $effectdate = $config['params']['dataparams']['effectdate'];
    $action = $config['params']['action2'];
    $url = 'App\Http\Classes\modules\payrollentry\\' . 'leaveapplicationportalapproval';
    $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='LEAVE'");


    if ($approversetup == '') {
      $approversetup = app($url)->approvers($config['params']);
    } else {
      $approversetup = explode(',', $approversetup);
      foreach ($approversetup as $appkey => $appsetup) {
        if ($appsetup == 'Supervisor') {
          $approversetup[$appkey] = 'issupervisor';
        } else {
          $approversetup[$appkey] = 'isapprover';
        }
      }
    }
    $approver = $this->coreFunctions->getfieldvalue("employee", "isapprover", "empid=?", [$admin]);
    $supervisor = $this->coreFunctions->getfieldvalue("employee", "issupervisor", "empid=?", [$admin]);
    if (isset($config['params']['dataparams']['trno']) && isset($config['params']['dataparams']['line'])) {
      $trno = $config['params']['dataparams']['trno'];
      $line = $config['params']['dataparams']['line'];
      switch ($action) {
        case 'ar':
          $leavestatus = 'A';
          $label = 'Approved';
          $status = $this->coreFunctions->datareader("select status as value from leavetrans where trno = ? and line=? and status = 'A' ", [$trno, $line]);
          break;
        case 'onhold': // on-hold
          $leavestatus = 'O';
          $label = 'On-Hold';
          $status = $this->coreFunctions->datareader("select status as value from leavetrans where trno = ? and line=? and status = 'O'  ", [$trno, $line]);
          break;
        case 'disapproved': // disapproved
          $leavestatus = 'D';
          $label = 'Disapproved';
          $status = $this->coreFunctions->datareader("select status as value from leavetrans where trno = ? and line=?  and status = 'D' ", [$trno, $line]);
          break;
        case 'post': // processed
          $leavestatus = 'P';
          $label = 'Processed';
          $status = $this->coreFunctions->datareader("select status as value from leavetrans where trno = ? and line=?  and status = 'P' ", [$trno, $line]);
          break;
      }
      if (!$status) {
        $label_reason = 'Remarks';
        if ($companyid == 53) { // camera
          $label_reason = 'Reason';
        }
        if ($leavestatus == 'A' || $leavestatus == 'P') {
          if ($reqleave > $balance) {
            $this->logger->sbcmasterlog($config['params']['dataparams']['line'], $config, "Request Leave: " . $reqleave . " Leave Balance: " . $balance . " " . $x . ' (' . $config['params']['dataparams']['client'] . ') - ' . $config['params']['dataparams']['dateid']);
            return ['status' => false, 'msg' => "Request Leave: " . $reqleave . " Leave Balance: " . $balance . "", 'data' => []];
          }
        }
        $lastapp = false;
        $status2 = "";
        $la_status = "";
        $bothapprover = false;
        foreach ($approversetup as $key => $value) {
          if (count($approversetup) > 1) {
            if ($supervisor && $approver) {
              $bothapprover = true;
              goto approved;
            } else {
              if ($key == 0) {
                if ($value == 'issupervisor' && $supervisor || $value == 'isapprover' && $approver) {
                  if (($leavestatus != 'A' && $leavestatus != 'P')) {
                    if ($rem2 == '') {
                      return ['status' => false, 'msg' => "Approver " . $label_reason . " is empty.", 'data' => []];
                    }
                  }
                  $data = [
                    'status2' => $leavestatus,
                    'date_approved_disapproved2' => $this->othersClass->getCurrentTimeStamp(),
                    'approvedby_disapprovedby2' => $config['params']['user'],
                    'disapproved_remarks2' => $rem2
                  ];
                  if ($leavestatus == 'D') {
                    $lastapp = true;
                  }
                  break;
                }
              } else {
                if ($value == 'issupervisor' && $supervisor || $value == 'isapprover' && $approver) {
                  if ((count($approversetup) - 1) == $key) {
                    $status2 = " and (status2 = 'A' or status2 = 'P') ";
                    $lastapp = true;
                    goto approved;
                  }
                }
              }
            }
          } else {
            if (count($approversetup) == 1) {
              approved:
              $la_status = $leavestatus;
              if (($leavestatus != 'A' && $leavestatus != 'P')) {
                if ($rem == '') {
                  return ['status' => false, 'msg' => "Approver " . $label_reason . " is empty.", 'data' => []];
                }
              }
              $data = [
                'status' => $leavestatus,
                'date_approved_disapproved' => $this->othersClass->getCurrentTimeStamp(),
                'approvedby_disapprovedby' => $config['params']['user'],
                'disapproved_remarks' => $rem,
              ];
              if ($bothapprover) {
                $data['status2'] = $leavestatus;
              }
              break;
            }
          }
        }
        if ($catid <> 0) {
          $data['catid'] = $catid;
        }
        $tempdata = [];
        foreach ($this->fields as $key2) {
          if (isset($data[$key2])) {
            $tempdata[$key2] = $this->othersClass->sanitizekeyfield($key2, $data[$key2]);
          }
        }
        $tempdata['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $tempdata['editby'] = $config['params']['user'];
        $update = $this->coreFunctions->sbcupdate("leavetrans", $tempdata, ['trno' => $trno, 'line' => $line]);
        if ($update) {
          if ($companyid == 53 || $companyid == 51) { // camera|ulitc
            $query = "select lt.trno, lt.line, concat(lt.trno,'~',lt.line) as trline,emp.supervisorid,date(lt.dateid) as dateid,lt.remarks,lt.status,
            lt.adays, date(lt.effectivity) as effdate,emp.email,app.clientname as appname,app2.clientname as appname2,
            lt.approvedby_disapprovedby2,p.codename,lt.disapproved_remarks,lt.disapproved_remarks2,cl.email as username
            from leavetrans lt
            left join leavesetup as ls on lt.trno = ls.trno
            left join paccount as p on p.line=ls.acnoid
            left join employee as emp on emp.empid = lt.empid
            left join client as cl on cl.clientid = emp.empid
            left join client as app on app.email= lt.approvedby_disapprovedby and app.email <> ''
            left join client as app2 on app2.email= lt.approvedby_disapprovedby2 and app2.email <> ''
            where lt.trno = $trno and lt.line = $line ";
            $leave = $this->coreFunctions->opentable($query);

            $qry = "select emp.isapprover,emp.email,cl.email as username,cl.clientname as appname from employee as emp left join client as cl on cl.clientid = emp.empid where emp.issupervisor = 1 and emp.empid = " . $leave[0]->supervisorid . "";
            $getapp = $this->coreFunctions->opentable($qry);
            $currentapp = $this->coreFunctions->getfieldvalue("client", "clientname", "clientid=?", [$admin]);
            $ldata = $getapp;
            if ($lastapp) $ldata = $leave;
            $params = [];
            if (!empty($ldata)) {
              $send = false;
              foreach ($ldata as $key => $value) {
                if (!empty($value->email)) {
                  $params['clientname'] = $empname . '-' . $this->othersClass->getCurrentDate();
                  $params['line'] = $leave[0]->trline;;
                  $params['effdate'] = $effectdate;
                  $params['dateid'] = $dateid;
                  $params['adays'] = $reqleave;
                  $params['bal'] = $balance;
                  $params['entitled'] = $entitled;
                  $params['codename'] = $leave[0]->codename;
                  $params['remarks'] = $leave[0]->remarks;
                  $params['reason1'] = $leave[0]->disapproved_remarks;
                  $params['reason2'] = $leave[0]->disapproved_remarks2;
                  $params['companyid'] = $companyid;
                  $params['approver'] = $value->username;
                  $params['email'] = $value->email;
                  $params['approvedstatus'] = $label;
                  if ($lastapp) {
                    $params['title'] = 'LEAVE APPLICATION RESULT ';
                    if ($la_status != "") {
                      $params['appname'] = $currentapp;
                      $params['appname2'] = $value->appname2;
                    } else {
                      $params['appname'] = $currentapp;
                    }
                  } else {
                    $params['title'] = 'LEAVE APPLICATION';
                    $params['appname'] = $currentapp;
                  }
                  $emailresult = $this->linkemail->createLeaveEmail($params);
                  $send = $emailresult['status'];
                  if (!$emailresult['status']) return ['status' => false, 'msg' => '' . $emailresult['msg']];
                }
              }
              if ($send) {
                if ($lastapp) goto update;
              }
            }
          } else {
            update:
            $this->coreFunctions->execqry("delete from pendingapp where trno=" . $trno . " and line=" . $line, 'delete');
            if ($action == "ar" || $action == "post") {
              // getlast approver
              if ($companyid == 58) {
                $this->othersClass->updatePendingapp($trno, $line, 'LEAVE', $tempdata, $url, $config);
              }
              $lastapprover = $approversetup[count($approversetup) - 1];
              if ($lastapprover == 'issupervisor' && $supervisor || $lastapprover == 'isapprover' && $approver) {
                $applied = $this->coreFunctions->datareader("select sum(adays) as value from leavetrans where status in ('A','P') $status2 and empid =? and trno = ?", [$empid, $trno]);
                $bal =  $entitled - $applied;
                $this->coreFunctions->execqry("update leavesetup set bal='" . $bal . "' where trno =?", 'update', [$trno]);
              }
            }
          }
          $config['params']['doc'] = 'LEAVEAPPLICATION';
          $this->logger->sbcmasterlog($config['params']['dataparams']['line'], $config, $label . $config['params']['dataparams']['status'] . ' (' . $config['params']['dataparams']['client'] . ') - ' . $config['params']['dataparams']['dateid']);
          return ['status' => true, 'msg' => 'Successfully ' . $label . ' ', 'data' => [], 'reloadsbclist' => true, 'action' => 'gapplications', 'closecustomform' => true];
        }
      } else {
        return ['status' => false, 'msg' => 'Already ' . $label . '.', 'data' => []];
      }
    }
    return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => []];
  }
} //end class
