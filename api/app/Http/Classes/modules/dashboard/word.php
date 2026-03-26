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

class word
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'WORK ON REST DAY';
    public $gridname = 'customformacctg';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $linkemail;
    public $tablelogs = 'payroll_log';
    public $style = 'width:1000px;max-width:1000px;';
    public $issearchshow = true;
    public $showclosebtn = true;
    public $fields = ['status', 'disapproved_remarks', 'approvedby', 'approveddate', 'disapproveddate', 'disapprovedby'];


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
        $url = 'App\Http\Classes\modules\payroll\\' . 'otapplicationadv';
        $approversetup = app($url)->approvers($config['params']);
        $approveby = $config['params']['row']['approvedby2'];
        $fapprover = $this->coreFunctions->getfieldvalue("client", "clientname", "client=?", [$approveby]);
        $admin = $config['params']['adminid'];
        $approver = $this->coreFunctions->getfieldvalue("employee", "isapprover", "empid=?", [$admin]);
        $supervisor = $this->coreFunctions->getfieldvalue("employee", "issupervisor", "empid=?", [$admin]);

        $bothapprover = false;
        if ($companyid == 58) { //cdohris
            if ($supervisor && $approver) {
                $bothapprover = true;
            }
        }
        $fields = ['createdate', 'clientname', 'lblmessage', 'rem'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'rem.readonly', true);
        data_set($col1, 'createdate.label', 'Date Filed');
        data_set($col1, 'createdate.type', 'input');
        data_set($col1, 'rem.label', 'remarks');

        data_set($col1, 'lblmessage.label', 'Employee Remarks: ');
        data_set($col1, 'lblmessage.style', 'font-size:11px;font-weight:bold;');
        $fields = ['dateid', ['schedin', 'schedout']];
        if (count($approversetup) > 1) {

            if ($bothapprover) {
                array_push($fields, 'lblrem', 'remarks');
            } else {
                array_push($fields, 'lblrem', 'remark', 'lblapproved', 'remarks');
            }
        } else {
            array_push($fields, 'lblrem', 'remarks');
        }
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'type.type', 'input');
        data_set($col2, 'dateid.type', 'input');
        data_set($col2, 'dateid.label', 'Schedule Date');
        data_set($col2, 'schedin.type', 'input');
        data_set($col2, 'schedout.type', 'input');

        if (!empty($approveby)) {
            data_set($col2, 'remark.readonly', true);
            data_set($col2, 'remarks.readonly', false);
        } else {
            data_set($col2, 'remark.readonly', false);
            data_set($col2, 'remarks.readonly', true);
            if (count($approversetup) == 1) {
                data_set($col2, 'remarks.readonly', false);
            }
        }
        data_set($col2, 'lblrem.label', 'First Approver: ' . $fapprover);
        data_set($col2, 'lblrem.style', 'font-size:11px;font-weight:bold;');

        if ($bothapprover) { //for cdohris
            data_set($col2, 'lblrem.label', 'Approver: ');
            data_set($col2, 'remarks.readonly', false);
        }

        data_set($col2, 'lblapproved.label', 'Second Approver: ');
        data_set($col2, 'lblapproved.style', 'font-size:11px;font-weight:bold;');

        $fields = [['refresh', 'disapproved']];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'disapproved.label', 'DISAPPROVED');
        data_set($col3, 'disapproved.confirm', true);
        data_set($col3, 'disapproved.confirmlabel', "Disapproved this Change Shift Application");
        data_set($col3, 'disapproved.color', 'red');
        data_set($col3, 'refresh.label', 'APPROVED');
        data_set($col3, 'refresh.confirm', true);
        data_set($col3, 'refresh.confirmlabel', "Approved this Change Shift Application");

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
    }

    public function paramsdata($config)
    {
        $line = $config['params']['row']['line'];
        $query = "
                select cs.line, client.clientname,emp.email,date(cs.createdate) as createdate,date(cs.dateid) as dateid,
                cs.empid,'CHANGE SHIFT' as type,cs.rem,cs.disapproved_remarks as remarks,cs.disapproved_remarks2 as remark,time(cs.schedin) as schedin,time(cs.schedout) as schedout
                from changeshiftapp as cs 
                left join client on client.clientid=cs.empid
                left join employee as emp on emp.empid = cs.empid 
                where cs.status= 0 and cs.line = ? and cs.isword = 1";
        return $this->coreFunctions->opentable($query, [$line]);
    }

    public function data()
    {
        return [];
    }

    public function loaddata($config)
    {
        $admin = $config['params']['adminid'];
        $remark = $config['params']['dataparams']['remark'];
        $remarks = $config['params']['dataparams']['remarks'];

        $url = 'App\Http\Classes\modules\payroll\\' . 'changeshiftapplication';
        $approversetup = app($url)->approvers($config['params']);
        $action = $config['params']['action2'];
        $approver = $this->coreFunctions->getfieldvalue("employee", "isapprover", "empid=?", [$config['params']['adminid']]);
        $supervisor = $this->coreFunctions->getfieldvalue("employee", "issupervisor", "empid=?", [$config['params']['adminid']]);
        if (isset($config['params']['dataparams']['line'])) {
            $line = $config['params']['dataparams']['line'];
            switch ($action) {
                case 'ar':
                    $label = 'Approved';
                    $cstatus = 1;
                    $status = $this->coreFunctions->datareader("select approveddate as value from changeshiftapp where line=? and approveddate is not null ", [$line]);
                    break;

                default:
                    $label = 'Disapproved ';
                    $cstatus = 2;
                    $status = $this->coreFunctions->datareader("select disapproveddate as value from changeshiftapp where line=? and disapproveddate is not null ", [$line]);
                    break;
            }

            if (!$status) {
                $bothapprover = false;
                foreach ($approversetup as $key => $value) {

                    if (count($approversetup) > 1) {

                        if ($supervisor && $approver) {
                            $bothapprover = true;
                            goto approved;
                        } else {
                            if ($key == 0) {
                                if ($value == 'issupervisor' && $supervisor || $value == 'isapprover' && $approver) {
                                    $data = [
                                        'status2' => $cstatus,
                                        'disapproved_remarks2' => $remark
                                    ];

                                    if ($cstatus == 2) {
                                        if ($remark == '') {
                                            return ['status' => false, 'msg' => 'First Approver Remarks is empty.', 'data' => []];
                                        }
                                        $data['disapprovedby2'] = $config['params']['user'];
                                        $data['disapproveddate2'] = $this->othersClass->getCurrentTimeStamp();
                                    } else { // disapproved
                                        $data['approvedby2'] = $config['params']['user'];
                                        $data['approveddate2'] = $this->othersClass->getCurrentTimeStamp();
                                    }

                                    break;
                                }
                            } else {
                                if ($value == 'issupervisor' && $supervisor || $value == 'isapprover' && $approver) {
                                    if ((count($approversetup) - 1) == $key) {
                                        goto approved;
                                    }
                                }
                            }
                        }
                    } else {

                        if (count($approversetup) == 1) {
                            approved:
                            $data = [
                                'status' => $cstatus,
                                'disapproved_remarks' => $remark
                            ];

                            if ($cstatus == 1) { // approved
                                $data['approvedby'] = $config['params']['user'];
                                $data['approveddate'] = $this->othersClass->getCurrentTimeStamp();
                            } else { // disapproved
                                if ($remarks == '') {
                                    return ['status' => false, 'msg' => 'Last Approver Remarks is empty.', 'data' => []];
                                }
                                $data['disapprovedby'] = $config['params']['user'];
                                $data['disapproveddate'] = $this->othersClass->getCurrentTimeStamp();
                            }
                            if ($bothapprover) {
                                $data['status2'] = $cstatus;
                            }
                            break;
                        }
                    }
                }
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
            $update = $this->coreFunctions->sbcupdate('changeshiftapp', $data, ['line' => $line]);
            if ($update) {
                $config['params']['doc'] = 'WORD';
                $this->logger->sbcmasterlog($config['params']['dataparams']['line'], $config, $label . ' (' . $config['params']['dataparams']['clientname'] . ') - ' . $config['params']['dataparams']['dateid']);
                return ['status' => true, 'msg' => 'Successfully ' . $label . ' ', 'data' => [], 'reloadsbclist' => true, 'action' => 'word'];
            }
        }

        return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => []];
    }
} //end class
