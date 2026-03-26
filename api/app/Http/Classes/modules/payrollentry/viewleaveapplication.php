<?php

namespace App\Http\Classes\modules\payrollentry;

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
use App\Http\Classes\lookup\hrislookup;

class viewleaveapplication
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'DETAILS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'leavetrans';
  public $tablelogs = 'payroll_log';
  private $logger;
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['dateid', 'daytype', 'status', 'adays', 'effectivity'];
  public $showclosebtn = false;
  private $enrollmentlookup;
  private $hrislookup;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->hrislookup = new hrislookup;
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = array(
      'load' => 0
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $companyid = $config['params']['companyid'];
    $leavelabel = $this->companysetup->getleavelabel($config['params']);
    $addattachments = $this->othersClass->checkAccess($config['params']['user'], 1730);

    switch (strtolower($config['params']['doc'])) {
      case 'leaveapplicationportal':
        $stockbuttons = ['delete'];
        if ($addattachments) {
          if ($companyid == 53) { //camera
            array_push($stockbuttons, 'addattachments');
          }
        }
        // $columns = ['action','dateid', 'daytype',
        //   'adays', 'status', 'effectivity', 'remarks',
        //   'approvedby_disapprovedby', 'date_approved_disapproved',
        //   'disapproved_remarks'
        // ];
        break;
      default:
        $stockbuttons = ['save', 'delete'];
        // $columns = ['action','dateid', 'effectivity', 'adays', 'leavestatus'];
        break;
    }

    if ($companyid == 53 || $companyid = 51) { // camera| ulitc
      $columns = [
        'action',
        'dateid',
        'effectivity',
        'adays',
        'remarks',
        'supervisorstatus',
        'approvedby_disapprovedbysup',
        'date_approved_disapprovedsup',
        'disapproved_remarks2',
        'status',
        'approvedby_disapprovedby',
        'date_approved_disapproved',
        'disapproved_remarks',
        'batch',
        'category',
        'daytype',
        'cancelrem',
        'canceldate'
      ];

      if ($companyid == 53) {
        array_push($columns, 'void_date', 'void_approver', 'void_remarks');
      }
    } else {
      $columns = [
        'action',
        'dateid',
        'effectivity',
        'adays',
        'supervisorstatus',
        'status',
        'category',
        'daytype',
        'remarks',
        'approvedby_disapprovedby',
        'date_approved_disapproved',
        'disapproved_remarks',
        'approvedby_disapprovedbysup',
        'date_approved_disapprovedsup',
        'disapproved_remarks2',
        'batch'
      ];
    }
    $url = 'App\Http\Classes\modules\payrollentry\leaveapplicationportalapproval';
    $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='LEAVE'");
    if ($approversetup == '') {
      $approversetup = app($url)->approvers($config['params']);
    } else {
      $approversetup = explode(',', $approversetup);
    }
    foreach ($columns as $key => $value) {
      $$value = $key;
    }
    $tab = [$this->gridname => ['gridcolumns' => $columns]];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][$dateid]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$effectivity]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$adays]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$status]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$daytype]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$remarks]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$approvedby_disapprovedby]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$date_approved_disapproved]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$approvedby_disapprovedbysup]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$date_approved_disapprovedsup]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$disapproved_remarks]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$batch]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$supervisorstatus]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$category]['type'] = 'label';

    $obj[0][$this->gridname]['columns'][$action]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $obj[0][$this->gridname]['columns'][$dateid]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $obj[0][$this->gridname]['columns'][$effectivity]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $obj[0][$this->gridname]['columns'][$adays]['style'] = 'width:60px;whiteSpace: normal;min-width:60px;';
    $obj[0][$this->gridname]['columns'][$supervisorstatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $obj[0][$this->gridname]['columns'][$status]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $obj[0][$this->gridname]['columns'][$daytype]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $obj[0][$this->gridname]['columns'][$remarks]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
    $obj[0][$this->gridname]['columns'][$date_approved_disapprovedsup]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $obj[0][$this->gridname]['columns'][$disapproved_remarks]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $obj[0][$this->gridname]['columns'][$category]['style'] = 'width:170px;whiteSpace: normal;min-width:170px;';

    $obj[0][$this->gridname]['columns'][$dateid]['label'] = 'DATE APPLIED';
    $obj[0][$this->gridname]['columns'][$dateid]['align'] = 'text-left';
    $obj[0][$this->gridname]['columns'][$effectivity]['align'] = 'text-left';
    $obj[0][$this->gridname]['columns'][$adays]['align'] = 'text-left';
    $obj[0][$this->gridname]['columns'][$status]['align'] = 'text-left';
    $obj[0][$this->gridname]['columns'][$daytype]['align'] = 'text-left';
    $obj[0][$this->gridname]['columns'][$remarks]['align'] = 'text-left';

    $obj[0][$this->gridname]['columns'][$effectivity]['label'] = 'EFFECTIVITY';
    $obj[0][$this->gridname]['columns'][$adays]['label'] = $leavelabel;
    $obj[0][$this->gridname]['columns'][$status]['label'] = 'STATUS';
    $obj[0][$this->gridname]['columns'][$daytype]['label'] = 'DAY TYPE';
    $obj[0][$this->gridname]['columns'][$remarks]['label'] = 'REMARKS';

    $obj[0][$this->gridname]['columns'][$approvedby_disapprovedby]['label'] = 'APPROVED/DISAPPROVED BY';
    $obj[0][$this->gridname]['columns'][$date_approved_disapproved]['label'] = 'DATE APPROVED/DISAPPROVED';
    $obj[0][$this->gridname]['columns'][$approvedby_disapprovedbysup]['label'] = 'APPROVED/DISAPPROVED BY (SUPERVISOR)';
    $obj[0][$this->gridname]['columns'][$date_approved_disapprovedsup]['label'] = 'DATE APPROVED/DISAPPROVED (SUPERVISOR)';
    $obj[0][$this->gridname]['columns'][$disapproved_remarks]['label'] = 'APPROVER REMARKS';
    $obj[0][$this->gridname]['columns'][$disapproved_remarks2]['label'] = 'FIRST APPROVER REMARKS';
    $obj[0][$this->gridname]['columns'][$disapproved_remarks2]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;';
    $obj[0][$this->gridname]['columns'][$disapproved_remarks2]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$supervisorstatus]['label'] = 'STATUS (SUPPERVISOR)';

    $companylist = [44, 53, 58, 51]; //stone, camera, cdo,ulitc
    switch (strtolower($config['params']['doc'])) {
      case 'leaveapplicationportal':

        if (in_array($companyid, $companylist)) {
          $labelstat1 = 'LAST STATUS';
          $labelstat2 = 'FIRST STATUS';
          if ($companyid == 58) { //cdo
            $labelstat1 = 'STATUS (HR)';
            $labelstat2 = 'STATUS (Supervisor)';
          }
          $obj[0][$this->gridname]['columns'][$status]['label'] = $labelstat1;
          $obj[0][$this->gridname]['columns'][$supervisorstatus]['label'] = $labelstat2;
          if ($companyid == 53) { //camera

            $obj[0][$this->gridname]['columns'][$remarks]['label'] = 'REASON';
            $obj[0][$this->gridname]['columns'][$date_approved_disapprovedsup]['label'] = 'DATE APPROVED/DISAPPROVED (HR/PAYROLL APPROVER)';
            $obj[0][$this->gridname]['columns'][$supervisorstatus]['style'] = 'width:170px;whiteSpace: normal;min-width:170px;text-align:center;';
            $obj[0][$this->gridname]['columns'][$supervisorstatus]['label'] = 'HR/PAYROLL APPROVER STATUS';
            $obj[0][$this->gridname]['columns'][$disapproved_remarks2]['label'] = 'HR/PAYROLL APPROVER REASON';
            $obj[0][$this->gridname]['columns'][$approvedby_disapprovedbysup]['label'] = 'APPROVED/DISAPPROVED BY (HR/PAYROLL APPROVER)';


            $obj[0][$this->gridname]['columns'][$disapproved_remarks]['label'] = 'HEAD DEPT. APPROVER REASON';
            $obj[0][$this->gridname]['columns'][$status]['label'] = 'HEAD DEPT. STATUS';
            $obj[0][$this->gridname]['columns'][$approvedby_disapprovedby]['label'] = 'APPROVED/DISAPPROVED BY: HEAD DEPT APPROVER';
            $obj[0][$this->gridname]['columns'][$approvedby_disapprovedby]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;';
            $obj[0][$this->gridname]['columns'][$date_approved_disapproved]['label'] = 'DATE APPROVED/DISAPPROVED (HEAD DEPT APPROVER)';

            $obj[0][$this->gridname]['columns'][$daytype]['type'] = 'coldel';
            $obj[0][$this->gridname]['columns'][$category]['type'] = 'coldel';

            $obj[0][$this->gridname]['columns'][$void_remarks]['type'] = 'label';
            $obj[0][$this->gridname]['columns'][$void_remarks]['label'] = 'Reason';
            $obj[0][$this->gridname]['columns'][$void_remarks]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
            $obj[0][$this->gridname]['columns'][$void_date]['type'] = 'label';
            $obj[0][$this->gridname]['columns'][$void_approver]['type'] = 'label';

            $obj[0][$this->gridname]['columns'][$action]['btns']['addattachments']['lookupclass'] = 'payrollattachments';
          }
          if ($companyid == 51) {
            $obj[0][$this->gridname]['columns'][$approvedby_disapprovedbysup]['label'] = 'APPROVED/DISAPPROVED BY';
            $obj[0][$this->gridname]['columns'][$date_approved_disapprovedsup]['label'] = 'DATE APPROVED/DISAPPROVED';
            $obj[0][$this->gridname]['columns'][$daytype]['type'] = 'coldel';
            $obj[0][$this->gridname]['columns'][$category]['type'] = 'coldel';
          }
        } else {
          $obj[0][$this->gridname]['columns'][$supervisorstatus]['type'] = 'coldel';
          $obj[0][$this->gridname]['columns'][$approvedby_disapprovedbysup]['type'] = 'coldel';
          $obj[0][$this->gridname]['columns'][$date_approved_disapprovedsup]['type'] = 'coldel';
        }

        if ($companyid == 58) { //cdo
          $obj[0][$this->gridname]['columns'][$daytype]['type'] = 'coldel';
        } else {
          $obj[0][$this->gridname]['columns'][$category]['type'] = 'coldel';
        }
        break;
      default:
        $obj[0][$this->gridname]['columns'][$dateid]['label'] = 'CREATED DATE';
        $obj[0][$this->gridname]['columns'][$status]['type'] = 'lookup';
        $obj[0][$this->gridname]['columns'][$status]['lookupclass'] = 'lookupgridleavestatus';
        $obj[0][$this->gridname]['columns'][$status]['action'] = 'lookupsetup';

        $obj[0][$this->gridname]['columns'][$remarks]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$daytype]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$approvedby_disapprovedby]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$date_approved_disapproved]['type'] = 'coldel';
        //$obj[0][$this->gridname]['columns'][$disapproved_remarks]['type'] = 'coldel';
        if (!in_array($companyid, $companylist)) {
          $obj[0][$this->gridname]['columns'][$supervisorstatus]['type'] = 'coldel';
          $obj[0][$this->gridname]['columns'][$approvedby_disapprovedbysup]['type'] = 'coldel';
          $obj[0][$this->gridname]['columns'][$date_approved_disapprovedsup]['type'] = 'coldel';
        }

        if ($companyid == 58) { //cdo
          $obj[0][$this->gridname]['columns'][$daytype]['type'] = 'coldel';
        } else {
          $obj[0][$this->gridname]['columns'][$category]['type'] = 'coldel';
        }
        break;
    }
    if (count($approversetup) == 1) {
      $obj[0][$this->gridname]['columns'][$supervisorstatus]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$approvedby_disapprovedbysup]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$date_approved_disapprovedsup]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$disapproved_remarks2]['type'] = 'coldel';
    }

    if (isset($cancelrem)) if ($companyid != 51) $obj[0][$this->gridname]['columns'][$cancelrem]['type'] = 'coldel';
    if (isset($canceldate)) if ($companyid != 51) $obj[0][$this->gridname]['columns'][$canceldate]['type'] = 'coldel';


    $leavelbl = $this->companysetup->getleavelabel($config['params']);
    $obj[0][$this->gridname]['columns'][$adays]['label'] = $leavelbl;

    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }

  private function selectqry($config)
  {
    $companylist = [44, 53, 58, 51];
    $statussuppervisor = '';
    $companyid = $config['params']['companyid'];
    $statuslabel = 'PROCESSED';
    if ($companyid == 53) {
      $statuslabel = 'LEAVE W/OUT PAY';
    }

    if (in_array($companyid, $companylist)) { //stonepro | CAMERA SOUND | ulitc
      $statussuppervisor = " case
      when lt.status2 = 'A' then 'APPROVED'
      when lt.status2 = 'E' then 'ENTRY'
      when lt.status2 = 'O' then 'ON-HOLD'
      when lt.status2 = 'P' then '$statuslabel'
      when lt.status2 = 'D' then 'DISAPPROVED'
    end as supervisorstatus, ";
    }


    $qry = "lt.trno, lt.line, date_format(lt.dateid, '%m-%d-%y') as dateid, lt.daytype,
    case
      when lt.status = 'A' then 'APPROVED'
      when lt.status = 'E' then 'ENTRY'
      when lt.status = 'O' then 'ON-HOLD'
      when lt.status = 'P' then '$statuslabel'
      when lt.status = 'D' then 'DISAPPROVED'
      when lt.status = 'C' then 'CANCELLED'
      when lt.status = 'X' then 'ADJUSMENT'
    end as status,
    $statussuppervisor
    lt.remarks,
    lt.adays,date_format(lt.effectivity, '%m-%d-%y') as effectivity, ifnull(b.batch,'') as batch,
    app.clientname as approvedby_disapprovedby,
    date_format(lt.date_approved_disapproved, '%m-%d-%y') as date_approved_disapproved,
    app2.clientname as approvedby_disapprovedbysup,
    date_format(lt.date_approved_disapproved2, '%m-%d-%y') as date_approved_disapprovedsup,cancelrem,canceldate,
    (case when lt.iswindows = '1' then 'bg-yellow-7' else '' end) as bgcolor";
    return $qry;
  }

  public function loaddata($config)
  {
    $empid = $config['params']['tableid'];
    $companyid = $config['params']['companyid'];
    $select = $this->selectqry($config);
    $select = $select . "";

    $leftjoin = "";

    switch ($companyid) {
      case 58: // cdohris
        $select .= ",cat.category,lt.disapproved_remarks";
        $leftjoin = " left join leavecategory as cat on cat.line=lt.catid";
        break;
      case 53: // camera
        $select .= ",lt.void_remarks,void.clientname as void_approver,lt.void_date ";
        $leftjoin = " left join client as void on void.email = lt.void_by and void.email <> ''";
        break;
      default:
        $select .= ",lt.disapproved_remarks,lt.disapproved_remarks2";
        break;
    }
    $qry = "select " . $select . "
    from " . $this->table . " as lt
    left join leavesetup as ls on lt.trno = ls.trno
    left join employee as e on e.empid=ls.empid
    left join batch as b on b.line=lt.batchid
    left join client as app on app.email = lt.approvedby_disapprovedby and app.email <> ''
    left join client as app2 on app2.email = lt.approvedby_disapprovedby2 and app2.email <> ''
    $leftjoin
    where ls.trno=?
    order by lt.effectivity";
    return  $this->coreFunctions->opentable($qry, [$empid]);
  }

  public function delete($config)
  {
    $row = $config['params']['row'];

    $qry = "select status as value from " . $this->table . " where trno=? and line=?";
    $status = $this->coreFunctions->opentable($qry, [$row['trno'], $row['line']]);
    $res = $this->isapproved($row['trno'], $row['line']);
    if ($res['status']) {
      $msg = "Cannot delete APPROVED Leave.";
      if ($res['istatus'] == 'D') {
        $msg = "Cannot delete DISAPPROVED Leave.";
      }
      return ['clientid' => $row['trno'], 'status' => false, 'msg' => $msg];
    }
    $enhrs = $this->coreFunctions->getfieldvalue($this->table, "adays", "trno=? and line=?", [$row['trno'], $row['line']]);

    switch ($status[0]->value) {
      case "A":
        $batchid = $this->coreFunctions->getfieldvalue("leavetrans", "batchid", "trno=? and line=?", [$row['trno'], $row['line']], '', true);
        if ($batchid != 0) {
          return ['status' => false, 'msg' => "Can't delete,  already applied in payroll.", 'reloadhead' => true];
        }

        $qry = "update leavesetup set bal=bal+" . $enhrs . " where trno=?";
        $result = $this->coreFunctions->execqry($qry, 'update', [$row['trno']]);

        $qry = "delete from " . $this->table . " where trno=? and line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['trno'], $row['line']]);
        break;

      case "E":
        $qry = "delete from " . $this->table . " where trno=? and line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['trno'], $row['line']]);

        $this->coreFunctions->execqry("delete from pendingapp where doc='LEAVE' and trno=? and line=?", 'delete', [$row['trno'], $row['line']]);
        break;

      default:
        $labeldesc = '';
        switch ($status[0]->value) {
          case 'D':
            $labeldesc = 'disapproved';
            break;
          case 'P':
            $labeldesc = 'processed';
            break;
          case 'O':
            $labeldesc = 'on-hold';
            break;
          case 'C':
            $labeldesc = 'cancelled';
            break;
        }
        return ['status' => false, 'msg' => "Can't delete " . $labeldesc . " application", 'reloadhead' => true];
        break;
    }

    $this->logger->sbcmasterlog(
      $row['trno'],
      $config,
      "DELETE DETAILS - LINE: " . $row['line'] . " DATE: " . $row['dateid'] . ", EFFECTIVITY: " . $row['effectivity'] . ", HOURS: " . $row['adays'] . ", STATUS: " . $row['status']
    );
    return ['status' => true, 'msg' => 'Successfully deleted.', 'reloadhead' => true];
  }

  public function lookupsetup($config)
  {
    $lookupclass = $config['params']['lookupclass2'];

    switch ($lookupclass) {
      case 'lookupgridleavestatus':
        return $this->hrislookup->lookupleavestatus($config);
        break;
    }
  }

  public function save($config)
  {
    $trno = $config['params']['row']['trno'];
    $line = $config['params']['row']['line'];

    $leavelabel = $this->companysetup->getleavelabel($config['params']);


    $msg = '';
    $success = true;
    $res = $this->isapproved($trno, $line);
    if ($res['status']) {
      if ($res['batchid'] != 0) {
        $success = false;
        $msg = 'Cannot modify APPROVED Leave.';
      } else {
        goto continueEditHere;
      }
      if ($res['istatus'] == 'D') {
        $msg = 'Cannot modify DISAPPROVED Leave.';
      }

      goto returnHere;
    }

    continueEditHere:
    $status = $config['params']['row']['status'];

    $row = $config['params']['row'];
    $row['status'] = substr($status, 0, 1);

    $prevstatus = $this->coreFunctions->getfieldvalue($this->table, "`status`", "trno=? and line=?", [$trno, $line]);

    $bal = $this->coreFunctions->datareader("select bal as value from leavesetup where trno=" . $trno);

    if (substr($status, 0, 1) == 'E') {
      $bal = $bal + $config['params']['row']['adays'];
    }

    $editdate = $this->othersClass->getCurrentTimeStamp();
    $editby = $config['params']['user'];

    if ($config['params']['row']['adays'] <= $bal) {
      $qry = "update " . $this->table . " set `status`='" . $row['status'] . "', editdate = '" . $editdate . "', editby = '" . $editby . "' where trno=? and line=?";
      $this->coreFunctions->execqry($qry, 'update', [$trno, $line]);

      if (substr($status, 0, 1) == 'A') {
        if ($config['params']['row']['adays'] != "") {
          $this->coreFunctions->execqry("update leavesetup set bal=bal-'" . $config['params']['row']['adays'] . "' where trno =?", 'update', [$trno]);
        }
      } else {
        if (substr($prevstatus, 0, 1) == 'A') {
          if ($config['params']['row']['adays'] != "") {
            $this->coreFunctions->execqry("update leavesetup set bal=bal+'" . $config['params']['row']['adays'] . "' where trno =?", 'update', [$trno]);
          }
        }
      }
    } else {
      if ($bal == 0) {
        $msg = 'You have ' . (float) $bal . ' remaining ' . $leavelabel;
      } else {
        $msg = 'You only have ' . (float) $bal . ' remaining ' . $leavelabel;
      }
      return ['status' => false, 'msg' => $msg];
    }

    // $this->logger->sbcmasterlog(
    //   $trno,
    //   $config,
    //   "UPDATE DETAILS - LINE: ". $line . " DATE: ". $row['dateid'] . ", EFFECTIVITY: " . $row['effectivity']. ", HOURS: " . $row['adays'] . ", STATUS: " . $status,
    //   1 // isedit
    // );

    $msg = 'Successfully saved.';

    returnHere:
    $row = $this->openstockline($config);
    return ['row' => $row, 'status' => $success, 'msg' => $msg, 'reloaddata' => true];
  }

  function openstockline($config)
  {
    $trno = $config['params']['row']['trno'];
    $line = $config['params']['row']['line'];

    $select = $this->selectqry($config);
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . "
    from " . $this->table . " as lt
    left join leavesetup as ls on lt.trno = ls.trno
    left join employee as e on e.empid=ls.empid
    left join batch as b on b.line=lt.batchid
    left join client as app on app.email = lt.approvedby_disapprovedby and app.email <> ''
    left join client as app2 on app2.email = lt.approvedby_disapprovedby2 and app2.email <> ''    
    where ls.trno=? and lt.line=?";

    return  $this->coreFunctions->opentable($qry, [$trno, $line]);
  }

  function isapproved($trno, $line)
  {
    $qry = "select status, status2, batchid from " . $this->table . " where trno = ? AND line = ?";
    $status = $this->coreFunctions->opentable($qry, [$trno, $line]);

    $array_stat = ["A", "D", "X"];


    if (in_array($status[0]->status2, $array_stat)) {
      return ['status' => true, 'istatus' => $status[0]->status2, 'batchid' => $status[0]->batchid];
    }

    if (in_array($status[0]->status, $array_stat)) {
      return ['status' => true, 'istatus' => $status[0]->status, 'batchid' => $status[0]->batchid];
    }


    return ['status' => false];
  }
} //end class
