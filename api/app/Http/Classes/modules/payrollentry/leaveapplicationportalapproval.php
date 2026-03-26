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
use App\Http\Classes\SBCPDF;



class leaveapplicationportalapproval
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'LEAVE APPLICATION PORTAL APPROVAL';
  public $gridname = 'entrygrid';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $style = 'width:100%;max-width:100%;';
  public $issearchshow = false;
  public $showclosebtn = false;
  public $reporter;
  public $tablelogs = 'payroll_log';

  public function __construct()
  {
    $this->btnClass = new buttonClass;
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->reporter = new SBCPDF;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 2865,
      'save' => 2866,
      'saveallentry' => 2866,
      'print' => 2867
    );
    return $attrib;
  }


  public function createHeadbutton($config)
  {
    return [];
  }

  public function createTab($config)
  {
    $companyid = $config['params']['companyid'];
    $company_list = [44, 53, 58];

    if ($companyid == 58) { //cdo-hris
      $this->modulename = 'LEAVE APPLICATION PORTAL STATUS';
    }

    $leavelabel = $this->companysetup->getleavelabel($config['params']);

    $colums = [
      'statusapproved',
      'docno',
      'empname',
      'status',
      'supervisorstatus',
      'dateid',
      'effectivity',
      'adays',
      'daytype',
      'remarks',
      'approvedby_disapprovedby',
      'date_approved_disapproved',
      'disapproved_remarks'
    ];

    foreach ($colums as $key => $value) {
      $$value = $key;
    }

    $tab = [$this->gridname => ['gridcolumns' => $colums]];

    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // $obj[0][$this->gridname]['obj'] = 'editgrid';
    $obj[0][$this->gridname]['descriptionrow'] = [];

    $obj[0][$this->gridname]['columns'][$empname]['style'] = "width:150px;whiteSpace: normal;min-width:150px; text-align:left;";
    $obj[0][$this->gridname]['columns'][$status]['style'] = "width:120px;whiteSpace: normal;min-width:120px; text-align:left;";
    $obj[0][$this->gridname]['columns'][$dateid]['style'] = "width:120px;whiteSpace: normal;min-width:120px; text-align:left;";
    $obj[0][$this->gridname]['columns'][$remarks]['style'] = "width:150px;whiteSpace: normal;min-width:150px; text-align:left;";
    $obj[0][$this->gridname]['columns'][$disapproved_remarks]['style'] = "width:200px;whiteSpace: normal;min-width:200px; text-align:left;";

    $obj[0][$this->gridname]['columns'][$docno]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$empname]['type'] = "label";

    $obj[0][$this->gridname]['columns'][$status]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$status]['align'] = "left";

    $obj[0][$this->gridname]['columns'][$dateid]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$effectivity]['type'] = "label";

    $obj[0][$this->gridname]['columns'][$adays]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$adays]['align'] = "left";

    $obj[0][$this->gridname]['columns'][$daytype]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$remarks]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$approvedby_disapprovedby]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$date_approved_disapproved]['type'] = "label";

    $obj[0][$this->gridname]['columns'][$dateid]['label'] = "Date Applied";
    $obj[0][$this->gridname]['columns'][$effectivity]['label'] = "Effectivity";
    $obj[0][$this->gridname]['columns'][$adays]['label'] = $leavelabel;
    $obj[0][$this->gridname]['columns'][$statusapproved]['label'] = "";

    $obj[0][$this->gridname]['columns'][$disapproved_remarks]['type'] = "input";

    $obj[0][$this->gridname]['columns'][$status]['label'] = 'Approver';

    if (in_array($companyid, $company_list)) {
      $obj[0][$this->gridname]['columns'][$status]['label'] = 'First Approver';
      $obj[0][$this->gridname]['columns'][$supervisorstatus]['type'] = 'label';
      $obj[0][$this->gridname]['columns'][$supervisorstatus]['label'] = 'Last Approver';
    } else {
      $obj[0][$this->gridname]['columns'][$supervisorstatus]['type'] = 'coldel';
    }




    if ($companyid == 58) { //cdo-hris
      $obj[0][$this->gridname]['columns'][$statusapproved]['type'] = 'coldel';
    }


    $obj[0][$this->gridname]['label'] = 'DETAILS';
    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $companyid = $config['params']['companyid'];

    if ($companyid == 58) { //cdo-hris
      $tbuttons = [];
      $obj = $this->tabClass->createtabbutton($tbuttons);
    } else {
      $tbuttons = ['saveallentry'];
      $obj = $this->tabClass->createtabbutton($tbuttons);
      $obj[1]['label'] = "SAVE";
    }
    return $obj;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];

    if ($companyid == 58) { //cdo-hris
      $fields = ['start', 'end', 'empstat', ['refresh', 'print']];
      $col1 = $this->fieldClass->create($fields);
      data_set($col1, 'start.label', 'From');
      data_set($col1, 'end.label', 'To');
      data_set($col1, 'refresh.action', 'load');
      data_set($col1, 'refresh.label', 'Search');

      data_set($col1, 'empstat.action', 'lookupleavestatus');
      data_set($col1, 'empstat.lookupclass', 'lookupleavestatus');
      data_set($col1, 'empstat.name', 'status');
      data_set($col1, 'empstat.label', 'Search Status');
      data_set($col1, 'empstat.required', true);

      $fields = [];
      $col2 = $this->fieldClass->create($fields);
    } else {
      $fields = ['start', 'end', 'empstat', ['refresh', 'print']];
      $col1 = $this->fieldClass->create($fields);
      data_set($col1, 'start.label', 'From');
      data_set($col1, 'end.label', 'To');
      data_set($col1, 'refresh.action', 'load');
      data_set($col1, 'refresh.label', 'Search');

      data_set($col1, 'empstat.action', 'lookupleavestatus');
      data_set($col1, 'empstat.lookupclass', 'lookupleavestatus');
      data_set($col1, 'empstat.name', 'status');
      data_set($col1, 'empstat.label', 'Search Status');
      data_set($col1, 'empstat.required', true);

      $fields = ['setempstat', ['create']];
      $col2 = $this->fieldClass->create($fields);
      data_set($col2, 'create.label', 'Mark All');
      data_set($col2, 'create.action', 'mark');
    }



    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {

    $data = $this->coreFunctions->opentable("
      select 
      adddate(left(now(),10),-30) as start,
      left(now(),10) as end,
      '' as status,
      '' as setempstat,
      '' as checkall
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
    $action = $config['params']["action2"];

    switch ($action) {
      case 'print':
        return $this->setupreport($config);

        break;

      case 'saveallentry':
      case 'update':
        if (empty($config['params']['rows'])) {
          return ['status' => true, 'msg' => 'No Data', 'data' => []];
        } else {
          if ($config['params']['dataparams']['setempstat'] == '') {
            return ['status' => false, 'msg' => 'Select Set Status', 'data' => []];
          }

          $result = $this->save($config);
          $custommsg = '';
          if (!$result['status']) {
            $custommsg = $result['msg'];
          }
          return $this->loadgrid($config, 0, $custommsg);
        }
        break;

      case 'load':
        return $this->loadgrid($config);
        break;

      case 'mark':
        return $this->loadgrid($config, 1);
        break;

      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $action . ')'];
        break;
    } // end switch
  }

  private function loadgrid($config, $mark = 0, $custommsg = '')
  {
    $center = $config['params']['center'];
    $empstat  = $config['params']['dataparams']['status'];
    $from = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $to = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
    $companyid  = $config['params']['companyid'];
    $adminid = $config['params']['adminid'];

    if ($empstat == '') {
      return ['status' => false, 'msg' => 'Select Search Status', 'data' => []];
    }

    $filter = '';
    $status = '';
    $statusapprover = " and lt.status = '" . substr($empstat, 0, 1) . "'";
    $issupervisor = $this->coreFunctions->getfieldvalue("employee", "issupervisor", "empid=?", [$adminid]);
    $isapprover = $this->coreFunctions->getfieldvalue("employee", "isapprover", "empid=?", [$adminid]);

    $status2 = "  case
          when lt.status2 = 'A' then 'APPROVED'
          when lt.status2 = 'E' then 'ENTRY'
          when lt.status2 = 'O' then 'ON-HOLD'
          when lt.status2 = 'P' then 'PROCESSED'
        end as supervisorstatus, ";
    $status = " case
          when lt.status = 'A' then 'APPROVED'
          when lt.status = 'E' then 'ENTRY'
          when lt.status = 'O' then 'ON-HOLD'
          when lt.status = 'P' then 'PROCESSED'
        end as status, ";
    switch ($companyid) {
      case 44: //stonepro
        if ($issupervisor == 1) {
          $statusapprover = " and lt.status2 = '" . substr($empstat, 0, 1) . "' ";
          $filter .= "and lt.status = 'E' and lt.status2 = 'E' and lt.date_approved_disapproved2 is null and e.supervisorid = '$adminid'";
        } else if ($isapprover == 1) {
          $statusapprover = " and lt.status = '" . substr($empstat, 0, 1) . "' ";
          $filter .= "and lt.status2 = 'A' and lt.date_approved_disapproved2 is not null ";
        }
        break;
      case 58: //cdo-hris
        $filter .= " and ls.empid = '$adminid'";
        if ($empstat == '') {
          $statusapprover = "";
        }
        break;
      case 53: //CAMERA SOUND
        if ($issupervisor == 1) {
          $statusapprover = " and lt.status = '" . substr($empstat, 0, 1) . "' ";
          $filter .= "and lt.status2 = 'A' and lt.date_approved_disapproved2 is not null and e.supervisorid = '$adminid'";
        } else if ($isapprover == 1) {
          $statusapprover = " and lt.status = '" . substr($empstat, 0, 1) . "' ";
          $filter .= "and lt.status = 'E' and lt.status2 = 'E' and lt.date_approved_disapproved2 is null  ";
        }
        break;
    }

    if ($mark) {
      $qry = "select lt.trno, lt.line, left(lt.dateid, 10) as dateid, lt.daytype,ls.docno,
        'true' as statusapproved, $status $status2
        lt.remarks,
        lt.adays, left(lt.effectivity, 10) as effectivity, ifnull(b.batch,'') as batch,
        lt.approvedby_disapprovedby,
        left(lt.date_approved_disapproved, 10) as date_approved_disapproved,
        lt.disapproved_remarks,
        CONCAT(e.emplast,', ',e.empfirst,' ',e.empmiddle) AS empname,
        p.codename as daytype, ls.acnoid, p.code as acno, 'bg-color' as bgcolor
        from leavetrans as lt
        left join leavesetup as ls on lt.trno = ls.trno
        left join employee as e on e.empid=ls.empid
        left join paccount as p on p.line=ls.acnoid
        left join batch as b on b.line=lt.batchid
        where  date(lt.effectivity) between '" . $from . "' and '" . $to . "'  $filter $statusapprover 
        order by lt.effectivity";
    } else {
      $qry = "select lt.trno, lt.line, left(lt.dateid, 10) as dateid, lt.daytype,ls.docno,
        case
          when lt.status = 'A' then 'true'
          when lt.status = 'E' then 'false'
          when lt.status = 'O' then 'false'
          when lt.status = 'P' then 'false'
        end as statusapproved, $status $status2
        lt.remarks,
        lt.adays, left(lt.effectivity, 10) as effectivity, ifnull(b.batch,'') as batch,
        lt.approvedby_disapprovedby,
        date(lt.date_approved_disapproved) as date_approved_disapproved,
        lt.disapproved_remarks,
        CONCAT(e.emplast,', ',e.empfirst,' ',e.empmiddle) AS empname,
        p.codename as daytype, ls.acnoid, p.code as acno
        from leavetrans as lt
        left join leavesetup as ls on lt.trno = ls.trno
        left join employee as e on e.empid=ls.empid
        left join paccount as p on p.line=ls.acnoid
        left join batch as b on b.line=lt.batchid
        where date(lt.effectivity) between '" . $from . "' and '" . $to . "' $filter  $statusapprover
        order by lt.effectivity";
    }

    // Logger($qry);

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => $custommsg != '' ? $custommsg : 'Successfully loaded.', 'action' => 'load', 'griddata' => ['entrygrid' => $data]];
  }


  private function save($config)
  {
    $setempstat  = $config['params']['dataparams']['setempstat'];
    $rows = $config['params']['rows'];
    $user = $config['params']['user'];
    $companyid  = $config['params']['companyid'];
    $adminid = $config['params']['adminid'];
    $status = true;
    $msg = '';
    $issupervisor = $this->coreFunctions->getfieldvalue("employee", "issupervisor", "empid=?", [$adminid]);
    $isapprover = $this->coreFunctions->getfieldvalue("employee", "isapprover", "empid=?", [$adminid]);

    foreach ($rows as $key => $val) {
      if ($val['statusapproved'] == "true") {
        $data = [];
        $data['status'] = substr($setempstat, 0, 1);

        $blnApplied = true;

        if ($data['status'] == "A") {
          $bal = $this->coreFunctions->getfieldvalue("leavesetup", "bal", "trno=?", [$val['trno']], '', true);
          if ($val['adays'] > $bal) {
            $blnApplied = false;
            $data['disapproved_remarks'] = 'Insufficient balance. Remaining bal: ' . number_format($bal, 2);
            $data['status'] = 'D';
            $msg .= $val['empname'] . ' - ' . $data['disapproved_remarks'] . '.  ';
          }
        }

        $approversetup = $this->approvers($config['params']);
        $array_status = ['O', 'P', 'E'];
        foreach ($approversetup as $key2 => $value) {
          if (count($approversetup) > 1) {
            if ($key2 == 0) {
              if ($value == 'isapprover' && $isapprover || $value == 'issupervisor' && $issupervisor) {
                $data['status2'] = substr($setempstat, 0, 1);
                if (in_array($data['status2'], $array_status)) {
                  $data['status'] = 'E';
                  $data['date_approved_disapproved2'] = null;
                  $data['approvedby_disapprovedby2'] = '';
                } else {
                  $data['status'] = 'E';
                  $data['date_approved_disapproved2'] = $this->othersClass->getCurrentTimeStamp();
                  $data['approvedby_disapprovedby2'] = $user;
                }
                break;
              }
            } else {
              if ($value == 'isapprover' && $isapprover || $value == 'issupervisor' && $issupervisor) {
                if ((count($approversetup) - 1) == $key2) {
                  goto defaultapprovededate;
                }
              }
            }
          } else {
            if (count($approversetup) == 1) {
              defaultapprovededate:
              $data['approvedby_disapprovedby'] = $user;
              $data['date_approved_disapproved'] = $this->othersClass->getCurrentTimeStamp();
            }
          }
        }

        if ($blnApplied) {
          $data['disapproved_remarks'] = $val['disapproved_remarks'];
        }

        $oldstatus = $this->coreFunctions->getfieldvalue("leavetrans", "status", "trno=? and line=?", [$val['trno'], $val['line']]);
        // Logger('oldstatus : ' . $oldstatus);

        if ($this->coreFunctions->sbcupdate("leavetrans", $data, ['trno' => $val['trno'], 'line' => $val['line']])) {
          $this->coreFunctions->execqry("delete from pendingapp where trno=" . $val['trno'] . " and line=" . $val['line'], 'delete');
          if ($data['status'] == "A") {
            if ($companyid == 58) {
              $url = 'App\Http\Classes\modules\payrollentry\\' . 'leaveapplicationportalapproval';
              $this->othersClass->updatePendingapp($val['trno'], $val['line'], 'LEAVE', $data, $url, $config);
            }
            $this->coreFunctions->execqry("update leavesetup set bal=bal-" . $val['adays'] . " where trno =?", 'update', [$val['trno']]);
          } else {
            if ($oldstatus == 'A') {
              $this->coreFunctions->execqry("update leavesetup set bal=bal+" . $val['adays'] . " where trno =?", 'update', [$val['trno']]);
            }
          }
        }
      }
    }

    return ['status' => $status, 'msg' => $msg];
  }


  public function setupreport($config)
  {
    $txtfield = $this->createreportfilter($config);
    $txtdata = $this->reportparamsdata($config);

    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false, 'action' => 'print'];
  }

  public function createreportfilter($config)
  {
    $fields = ['radioprint', 'start', 'end', 'status', 'prepared', 'approved', 'received', 'print'];

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'status.type', 'hidden');
    data_set($col1, 'start.type', 'hidden');
    data_set($col1, 'end.type', 'hidden');
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    $status = $config['params']['dataparams']['status'];
    $start = $config['params']['dataparams']['start'];
    $end = $config['params']['dataparams']['end'];
    return $this->coreFunctions->opentable(
      "select
        'default' as print,
        '" . $start . "' as start,
        '" . $end . "' as end,
        '' as prepared,
        '' as approved,
        '' as received,
        '" . $status . "' as status
    "
    );
  }

  public function default_query($config)
  {
    $companyid  = $config['params']['companyid'];
    $adminid  = $config['params']['adminid'];

    $empstat  = $config['params']['dataparams']['status'];
    $from = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $to = date('Y-m-d', strtotime($config['params']['dataparams']['end']));

    $filters = " and lt.status = '" . substr($empstat, 0, 1) . "' ";
    if ($companyid == 58) { //cdo-hris
      $filters = " and ls.empid = " . $adminid;
    }

    $qry = "select lt.trno, lt.line, left(lt.dateid, 10) as dateid, lt.daytype,
        case
          when lt.status = 'A' then 'true'
          when lt.status = 'E' then 'false'
          when lt.status = 'O' then 'false'
          when lt.status = 'P' then 'false'
        end as statusapproved,
        case
          when lt.status = 'A' then 'APPROVED'
          when lt.status = 'E' then 'ENTRY'
          when lt.status = 'O' then 'ON-HOLD'
          when lt.status = 'P' then 'PROCESSED'
        end as status,
        lt.remarks,
        lt.adays, left(lt.effectivity, 10) as effectivity, ifnull(b.batch,'') as batch,
        lt.approvedby_disapprovedby,
        date(lt.date_approved_disapproved) as date_approved_disapproved,
        lt.disapproved_remarks,
        CONCAT(e.emplast,', ',e.empfirst,' ',e.empmiddle) AS empname,
        p.codename as daytype, ls.acnoid, p.code as acno
        from leavetrans as lt
        left join leavesetup as ls on lt.trno = ls.trno
        left join employee as e on e.empid=ls.empid
        left join paccount as p on p.line=ls.acnoid
        left join batch as b on b.line=lt.batchid
        where date(lt.dateid) between '" . $from . "' and '" . $to . "' $filters
        order by lt.effectivity";

    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }
  public function reportdata($config)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $empstat  = $config['params']['dataparams']['status'];
    $from = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $to = date('Y-m-d', strtotime($config['params']['dataparams']['end']));

    $data = $this->default_query($config);

    $str = "";
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('STATUS:', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col($empstat, '750', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('FROM:', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col($from, '750', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TO:', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col($to, '750', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= "<br>";

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Employee Name', '200', null, false, $border, 'BTRL', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('Date Applied', '100', null, false, $border, 'BTRL', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('Effectivity', '100', null, false, $border, 'BTRL', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('Hour(s)', '100', null, false, $border, 'BTRL', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('Day Type', '150', null, false, $border, 'BTRL', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('Remarks', '200', null, false, $border, 'BTRL', 'C', $font, $fontsize, 'B', '', '3px');

    foreach ($data as $key => $val) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($val->empname, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($val->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($val->effectivity, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($val->adays, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($val->remarks, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '800', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'directprint' => false, 'action' => 'print'];
  }
  public function approvers($params)
  {

    $companyid = $params['companyid'];

    switch ($companyid) {
      case 53: // camera
        $approvers = ['isapprover', 'issupervisor'];
        break;
      case 44: // stonepro
      case 58: // cdohris
      case 51: // ulitc
        $approvers = ['issupervisor', 'isapprover'];
        break;
      default:
        $approvers = ['isapprover'];
        break;
    }
    return $approvers;
  }
} //end class
