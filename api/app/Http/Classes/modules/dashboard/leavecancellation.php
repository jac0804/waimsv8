<?php

namespace App\Http\Classes\modules\dashboard;

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

class leavecancellation
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'LEAVE CANCELLATION';
    public $gridname = 'customformacctg';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $linkemail;
    public $tablelogs = 'payroll_log';
    public $style = 'width:1000px;max-width:1000px;';
    public $issearchshow = true;
    public $showclosebtn = true;
    public $fields = ['status', 'canceldate', 'cancelby', 'reason'];


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

    public function createtabbutton($config)
    {

        $obj = [];
        return $obj;
    }

    public function createHeadField($config)
    {
        $companyid = $config['params']['companyid'];
        $leavelabel = $this->companysetup->getleavelabel($config['params']);

        $fields = ['clientname', 'lblmessage', 'remarks'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'remarks.readonly', true);

        data_set($col1, 'lblmessage.label', 'Employee Remarks: ');
        data_set($col1, 'lblmessage.style', 'font-size:11px;font-weight:bold;');

        $fields = [['dateid', 'effectdate'], 'lblrem', 'reason'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'dateid.type', 'input');
        data_set($col2, 'dateid.label', 'Date Applied');
        data_set($col2, 'reason.label', 'Reason');
        data_set($col2, 'reason.readonly', false);
        data_set($col2, 'lblrem.style', 'font-size:11px;font-weight:bold;');
        data_set($col2, 'lblrem.label', 'Reason:');
        data_set($col2, 'effectdate.style', 'padding:0px');


        $fields = ['acnoname', ['days', 'bal'], 'hours', 'status'];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'hours.label', 'Leave ' . $leavelabel);
        data_set($col3, 'hours.readonly', true);

        data_set($col3, 'days.readonly', true);
        data_set($col3, 'acnoname.label', 'Leave Type');
        data_set($col3, 'acnoname.readonly', true);

        $fields = [['refresh']];
        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'refresh.label', 'APPROVED');
        data_set($col4, 'refresh.confirm', true);
        data_set($col4, 'refresh.confirmlabel', "Approved this Cancellation Application");

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function paramsdata($config)
    {
        $line = $config['params']['row']['line'];
        $trno = $config['params']['row']['trno'];
        $query = "
                select lt.line,lt.trno,lt.empid,concat(emp.emplast,', ',emp.empfirst,' ',emp.empmiddle) as clientname,
                date(lt.dateid) as dateid, lt.reason, dept.clientname as deptname,p.codename as acnoname,lt.reason,
                case
                when lt.status = 'E' then 'ENTRY'
                end as status,
                date(lt.effectivity) as effectdate, lt.adays as hours, lt.remarks,ls.bal,ls.days
                from leavetrans as lt
                left join leavesetup as ls on ls.trno = lt.trno
                left join paccount as p on p.line=ls.acnoid
                left join employee as emp on emp.empid = lt.empid
                left join client as dept on dept.clientid = emp.deptid
                where lt.status= 'E' and lt.line = ? and lt.trno = ?";
        return $this->coreFunctions->opentable($query, [$line, $trno]);
    }

    public function data()
    {
        return [];
    }

    public function loaddata($config)
    {
        $empid = $config['params']['dataparams']['empid'];
        $dateid = $config['params']['dataparams']['dateid'];
        $reason = $config['params']['dataparams']['reason'];

        $url = 'App\Http\Classes\modules\payrollentry\\' . 'leaveapplicationportalapproval';
        $approversetup = app($url)->approvers($config['params']);

        $action = $config['params']['action2'];
        if (isset($config['params']['dataparams']['line'])) {
            $line = $config['params']['dataparams']['line'];
            $trno = $config['params']['dataparams']['trno'];
            switch ($action) {
                case 'ar':
                    $label = 'Approved';
                    $lstatus = 'C';
                    $status = $this->coreFunctions->datareader("select date_approved_disapproved as value from leavetrans where trno = ? and line=? and status = 'C' and canceldate is not null", [$trno, $line]);
                    break;
            }
            $date = $this->othersClass->getCurrentTimeStamp();
            $user = $config['params']['user'];
            if (!$status) {
                $data = [
                    'reason' => $reason,
                    'status' => $lstatus,
                    'canceldate' => $date,
                    'cancelby' => $user
                ];
            }
            $tempdata = [];
            foreach ($this->fields as $key2) {
                if (isset($data[$key2])) {
                    $tempdata[$key2] = $data[$key2];
                    $tempdata[$key2] = $this->othersClass->sanitizekeyfield($key2, $tempdata[$key2]);
                }
            }


            $tempdata['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $tempdata['editby'] = $config['params']['user'];
            $url = 'App\Http\Classes\modules\payroll\\' . 'leaveapplicationportal';
            $data2 = ['empid' => $row['empid']];
            $pendingdata = $this->coreFunctions->opentable("select * from pendingapp where doc='LEAVE' and trno=" . $trno . " and line=" . $line);
            $this->coreFunctions->execqry("delete from pendingapp where doc='LEAVE' and trno=" . $trno . " and line=" . $line, 'delete');
            $appstatus = $this->othersClass->insertUpdatePendingapp($trno, $line, 'LEAVECANCELLATION', $data2, $url, $config, 0, true, true);
            if (!$appstatus['status']) {
                goto reinsertpendingapp;
                $msg = $appstatus['msg'];
                $status = $appstatus['status'];
                return ['status' => false, 'msg' => $msg, 'data' => []];
            } else {
                $update = $this->coreFunctions->sbcupdate('leavetrans', $tempdata, ['line' => $line, 'trno' => $trno, 'empid' => $empid]);
                if ($update) {
                    $config['params']['doc'] = 'LEAVECANCELLATION';
                    $this->logger->sbcmasterlog($config['params']['dataparams']['trno'], $config, $label . ' (' . $config['params']['dataparams']['clientname'] . ') - ' . $config['params']['dataparams']['dateid']);
                    return ['status' => true, 'msg' => 'Successfully ' . $label . ' ', 'data' => [], 'reloadsbclist' => true, 'action' => 'leavecancellation'];
                } else {
                    reinsertpendingapp:
                    $this->coreFunctions->execqry("delete from pendingapp where doc='LEAVECANCELLATION' and tnro=" . $trno . " and line=" . $line, 'delete');
                    if (!empty($pendingdata)) {
                        foreach ($pendingdata as $pd) {
                            $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid, approver) values(?, ?, ?, ?, ?)", 'insert', [$pd->trno, $pd->line, $pd->doc, $pd->clientid, $pd->approver]);
                        }
                    }
                    return ['status' => false, 'msg' => 'Error updating record, please try again.', 'data' => []];
                }
            }
        }

        return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => []];
    }
} //end class
