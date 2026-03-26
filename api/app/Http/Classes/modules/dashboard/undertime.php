<?php

namespace App\Http\Classes\modules\dashboard;

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

class undertime
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'UNDERTIME APPLICATION';
    public $gridname = 'customformacctg';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    public $tablelogs = 'payroll_log';
    public $style = 'width:900px;max-width:900px;';
    public $issearchshow = true;
    public $showclosebtn = true;
    public $fields = ['status', 'status2', 'approverem', 'approvedby', 'approvedby2', 'approvedate', 'approvedate2', 'disapprovedby', 'disapprovedby2', 'disapprovedate', 'disapprovedate2', 'catid'];

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
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
        $url = 'App\Http\Classes\modules\payroll\\' . 'undertime';
        $approversetup = app($url)->approvers($config['params']);
        $companyid = $config['params']['companyid'];
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

        $fields = ['clientname', 'lblmessage', 'rem'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'lblmessage.label', 'Employee Remarks: ');
        data_set($col1, 'rem.label', 'Remarks');
        data_set($col1, 'lblmessage.style', 'font-size:11px;font-weight:bold;');

        $fields = ['createdate'];


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
        data_set($col2, 'createdate.style', 'padding:0px;');
        if ($companyid == 58) { // cdohris
            $fields = ['category', 'dateid', ['ftime', 'ttime'], ['refresh', 'disapproved']];
            if (empty($approveby)) {
                unset($fields[0]);
            }
        } else {
            $fields = [['dateid', 'ttime'], ['refresh', 'disapproved']];
        }
        $col3 = $this->fieldClass->create($fields);

        data_set($col3, 'dateid.type', 'input');
        data_set($col3, 'ttime.type', 'input');
        data_set($col3, 'ttime.readonly', true);
        data_set($col3, 'ttime.label', 'Time');
        data_set($col3, 'refresh.label', 'APPROVE');
        data_set($col3, 'refresh.confirmlabel', 'Approved this Undertime Application?');
        data_set($col3, 'disapproved.color', 'red');
        if ($companyid == 58) { // cdohris
            data_set($col3, 'category.lookupclass', 'dashboard');
            data_set($col3, 'category.doc', 'leaveapplication');

            data_set($col3, 'category.action', 'lookupleavecategory');
            data_set($col3, 'category.required', true);
            data_set($col3, 'category.label', 'Leave Category');
            data_set($col3, 'category.style', 'padding:0px;');

            data_set($col3, 'ttime.type', 'input');
            data_set($col3, 'ttime.readonly', true);
            data_set($col3, 'ttime.label', 'Time End');

            data_set($col3, 'ftime.type', 'input');
            data_set($col3, 'ftime.readonly', true);
            data_set($col3, 'ftime.label', 'Time Start');
        }

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
    }

    public function paramsdata($config)
    {
        $catid = $config['params']['row']['catid'];
        $query = "select under.line,  client.client, client.clientname, date(under.dateid) as dateid,
        time(dateid) as ttime, under.type,under.approverem as remarks,
        under.rem,disapproved_remarks2 as remark,date(under.createdate) as createdate,
        (case when $catid <> 0 then $catid else '0' end) as catid,time(dateid2) as ftime
        from undertime as under left join client on client.clientid=under.empid
        where approvedate is null and disapprovedate is null and under.line=?";
        return $this->coreFunctions->opentable($query, [$config['params']['row']['line']]);
    }

    public function data()
    {
        return [];
    }

    public function loaddata($config)
    {
        $url = 'App\Http\Classes\modules\payroll\\' . 'undertime';
        $approversetup = app($url)->approvers($config['params']);
        $rem = $config['params']['dataparams']['remarks'];
        $rem2 = $config['params']['dataparams']['remark'];
        $action = $config['params']['action2'];

        $companyid = $config['params']['companyid'];
        $admin = $config['params']['adminid'];
        $catid = $config['params']['dataparams']['catid'];
        $approver = $this->coreFunctions->getfieldvalue("employee", "isapprover", "empid=?", [$admin]);
        $supervisor = $this->coreFunctions->getfieldvalue("employee", "issupervisor", "empid=?", [$admin]);
        if (isset($config['params']['dataparams']['line'])) {
            $line = $config['params']['dataparams']['line'];

            switch ($action) {
                case 'ar':
                    $utdstatus = 'A';
                    $label = 'Approved ';
                    $status = $this->coreFunctions->datareader("select approvedate as value from undertime where line=? and approvedate is not null", [$line]);
                    break;

                default:
                    $utdstatus = 'D';
                    $label = 'Disapproved ';
                    $status = $this->coreFunctions->datareader("select disapprovedate as value from undertime where line=? and disapprovedate is not null", [$line]);
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
                                        'status2' => $utdstatus,
                                        'disapproved_remarks2' => $rem2,
                                    ];

                                    if ($utdstatus == 'A') { // approved
                                        $data['approvedby2'] = $config['params']['user'];
                                        $data['approvedate2'] = $this->othersClass->getCurrentTimeStamp();
                                    } else { // disapproved
                                        if ($rem2 == '') {
                                            return ['status' => false, 'msg' => 'First Approver Remarks is empty.', 'data' => []];
                                        }
                                        $data['disapprovedby2'] = $config['params']['user'];
                                        $data['disapprovedate2'] = $this->othersClass->getCurrentTimeStamp();
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
                                'status' => $utdstatus,
                                'approverem' => $rem
                            ];

                            if ($utdstatus == 'A') { // approved
                                $data['approvedby'] = $config['params']['user'];
                                $data['approvedate'] = $this->othersClass->getCurrentTimeStamp();
                            } else { // disapproved
                                if ($rem == '') {
                                    return ['status' => false, 'msg' => 'Approver Remarks is empty.', 'data' => []];
                                }
                                $data['disapprovedby'] = $config['params']['user'];
                                $data['disapprovedate'] = $this->othersClass->getCurrentTimeStamp();
                            }
                            if ($bothapprover) {
                                $data['status2'] = $utdstatus;
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
                $update = $this->coreFunctions->sbcupdate("undertime", $tempdata, ['line' => $line]);
                $update = 1;
                if ($update) {
                    $config['params']['doc'] = 'UNDERTIME';
                    $this->logger->sbcmasterlog($config['params']['dataparams']['line'], $config, $label . ' (' . $config['params']['dataparams']['client'] . ') - ' . $config['params']['dataparams']['dateid']);
                    return ['status' => true, 'msg' => 'Successfully ' . $label, 'data' => [], 'reloadsbclist' => true, 'action' => 'undertime'];
                }
            } else {
                return ['status' => false, 'msg' => 'Already approved.', 'data' => []];
            }
        }

        return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => []];
    }
} //end class
