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

class changeshiftapplication
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'CHANGE SHIFT APPLICATION';
    public $gridname = 'customformacctg';
    private $companysetup;
    private $coreFunctions;
    private $linkemail;
    private $othersClass;
    public $tablelogs = 'payroll_log';
    public $style = 'width:1000px;max-width:1000px;';
    public $issearchshow = true;
    public $showclosebtn = true;
    public $fields = [
        'status',
        'status2',
        'disapproved_remarks',
        'disapproved_remarks2',
        'approvedby2',
        'approvedby',
        'approveddate',
        'disapproveddate',
        'disapprovedby',
        'approveddate2',
        'disapprovedby2',
        'disapproveddate2'
    ];

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

        $url = 'App\Http\Classes\modules\payroll\\' . 'changeshiftapplication';
        $approversetup = app($url)->approvers($config['params']);
        $approveby = $config['params']['row']['approvedby2'];
        $companyid = $config['params']['companyid'];
        $fapprover = $this->coreFunctions->getfieldvalue("client", "clientname", "client=?", [$approveby]);
        $fields = ['clientname', 'lblmessage', 'rem'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'rem.label', 'Remarks');
        data_set($col1, 'lblmessage.label', 'Employee Remarks: ');
        data_set($col1, 'lblmessage.style', 'font-size:11px;font-weight:bold;');

        if($companyid == 53){ // camera
            data_set($col1, 'rem.label', 'Reason');
            data_set($col1, 'lblmessage.label', 'Employee Reason:');
        }

        $fields = ['createdate'];
        if (count($approversetup) > 1) {
            array_push($fields, 'lblrem', 'remark', 'lblapproved', 'remarks');
        } else {
            array_push($fields, 'lblrem', 'remarks');
        }
        $col2 = $this->fieldClass->create($fields);
        if (!empty($fapprover)) {
            data_set($col2, 'remark.readonly', true);
            data_set($col2, 'remarks.readonly', false);
        } else {
            data_set($col2, 'remark.readonly', false);
            data_set($col2, 'remarks.readonly', true);
            if (count($approversetup) == 1) {
                data_set($col2, 'remarks.readonly', false);
            }
        }
        if ($companyid == 53) { // camera
            data_set($col2, 'remarks.label', 'Reason');
        }
        data_set($col2, 'remarks.readonly', false);

        data_set($col2, 'createdate.style', 'padding:0px'); // remove buttom space

        data_set($col2, 'lblrem.label', 'First Approver: ' . $fapprover);
        data_set($col2, 'lblrem.style', 'font-size:11px;font-weight:bold;');

        data_set($col2, 'lblapproved.label', 'Second Approver: ');
        data_set($col2, 'lblapproved.style', 'font-size:11px;font-weight:bold;');

        $fields = ['lblsource', 'atype', 'orgschedin', 'orgschedout'];
        $col3 = $this->fieldClass->create($fields);

        data_set($col3, 'orgschedin.type', 'input');
        data_set($col3, 'orgschedout.type', 'input');
        data_set($col3, 'atype.type', 'input');
        data_set($col3, 'atype.name', 'atype');
        data_set($col3, 'atype.readonly', true);
        data_set($col3, 'atype.label', 'Day Type');

        data_set($col3, 'lblsource.label', 'ORGINAL SCHEDULE:');
        data_set($col3, 'lblsource.style', 'font-size:11px;font-weight:bold;');

        $fields = ['lblcostuom', 'daytype', 'schedin', 'schedout', ['refresh', 'disapproved']];
        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'refresh.label', 'APPROVE');
        data_set($col4, 'refresh.confirm', true);
        data_set($col4, 'refresh.color', 'blue');
        data_set($col4, 'refresh.confirmlabel', 'Approved this Change shift application?');
        data_set($col4, 'disapproved.confirm', true);
        data_set($col4, 'disapproved.confirmlabel', 'Disapproved this Change shift application?');
        data_set($col4, 'disapproved.color', 'red');

        data_set($col4, 'schedin.type', 'input');
        data_set($col4, 'schedout.type', 'input');
        data_set($col4, 'daytype.readonly', true);
        data_set($col4, 'lblcostuom.label', 'CHANGE TO:'); // 'Shift Detail:'
        data_set($col4, 'lblcostuom.style', 'font-size:11px;font-weight:bold;');

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function paramsdata($config)
    {
        $systemtype = $this->companysetup->getsystemtype($config['params']);
        $companyid = $config['params']['companyid'];
        $line = $config['params']['row']['line'];

        $sql = "
        select cs.line, client.client,client.clientname, date(cs.dateid) as dateid, cs.empid,
        date_format(cs.schedin, '%Y-%m-%d %h:%i %p') as schedin,date_format(cs.schedout, '%Y-%m-%d %h:%i %p') as schedout, cs.rem,cs.disapproved_remarks as remarks,cs.disapproved_remarks2 as remark,cs.status,cs.approvedby,cs.approveddate,cs.status2,cs.approvedby2,cs.approveddate2,date(cs.createdate) as createdate,
        date_format(tm.schedin, '%Y-%m-%d %h:%i %p') as orgschedin,date_format(tm.schedout, '%Y-%m-%d %h:%i %p') as orgschedout,tm.daytype as atype,cs.daytype
        from changeshiftapp as cs 
        left join timecard as tm on tm.empid = cs.empid and tm.dateid = date(cs.dateid)
        left join client on client.clientid=cs.empid
        where cs.line= $line
        order by dateid, client.clientname";

        return $this->coreFunctions->opentable($sql, [$config['params']['row']['line']]);
    }

    public function data()
    {
        return [];
    }


    private function checksupervisor($config)
    {
        $supervisor = $this->coreFunctions->datareader("select issupervisor as value from employee where empid=?", [$config['params']['adminid']]);
        if ($supervisor == "1") {
            return true;
        } else {
            return false;
        }
    }

    private function get_approved_data($field, $dateid, $empid, $condition = '')
    {
        return $this->coreFunctions->datareader(
            "select $field as value from changeshiftapp where date(dateid) = '" . $dateid . "' and empid = $empid "
        );
    }
    // uncomment kung need update timecard
    // private function update_timecard($dateid, $empid)
    // {
    //     $schedin =  $this->get_approved_data("schedin", $dateid, $empid);
    //     $schedout = $this->get_approved_data("schedout", $dateid, $empid);
    //     $update_timecard = $this->coreFunctions->sbcupdate("timecard", ['schedin' => $schedin, 'schedout' => $schedout, 'isok' => 0], ['empid' => $empid, 'dateid' => $dateid]);
    //     return $update_timecard;
    // }
    // add this inside the loop at last approver
    // if ($companyid != 53) { // camera
    //     $this->update_timecard($dateid, $empid);
    // }

    public function loaddata($config)
    {
        $rem = $config['params']['dataparams']['remarks'];
        $rem2 = $config['params']['dataparams']['remark'];
        $dateid = $config['params']['dataparams']['dateid'];
        $empid = $config['params']['dataparams']['empid'];
        $action = $config['params']['action2'];
        $companyid = $config['params']['companyid'];
        $update_timecard = 0;
        $url = 'App\Http\Classes\modules\payroll\\' . 'changeshiftapplication';
        $approversetup = app($url)->approvers($config['params']);
        $admin = $config['params']['adminid'];
        $approver = $this->coreFunctions->getfieldvalue("employee", "isapprover", "empid=?", [$admin]);
        $supervisor = $this->coreFunctions->getfieldvalue("employee", "issupervisor", "empid=?", [$admin]);

        if (isset($config['params']['dataparams']['line'])) {
            $line = $config['params']['dataparams']['line'];
            switch ($action) {
                case 'ar':
                    $cstatus = 1;
                    $label = 'Approved ';
                    $status = $this->coreFunctions->datareader("select approveddate as value from changeshiftapp where line=? and approveddate is not null ", [$line]);
                    break;

                default:
                    $cstatus = 2;
                    $label = 'Disapproved ';
                    $status = $this->coreFunctions->datareader("select disapproveddate as value from changeshiftapp where line=? and disapproveddate is not null ", [$line]);
                    break;
            }

            if (!$status) {

                foreach ($approversetup as $key => $value) {
                    if (count($approversetup) > 1) {
                        if ($key == 0) {
                            if ($value == 'issupervisor' && $supervisor || $value == 'isapprover' && $approver) {
                                $data = [
                                    'status2' => $cstatus,
                                    'disapproved_remarks2' => $rem2
                                ];
                                if ($cstatus == 1) { // approved
                                    $data['approvedby2'] = $config['params']['user'];
                                    $data['approveddate2'] = $this->othersClass->getCurrentTimeStamp();
                                } else { // disapproved
                                    if ($rem2 == '') {
                                        return ['status' => false, 'msg' => 'Approver Remarks is empty.', 'data' => []];
                                    }
                                    $data['disapprovedby2'] = $config['params']['user'];
                                    $data['disapproveddate2'] = $this->othersClass->getCurrentTimeStamp();
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
                    } else {
                        if (count($approversetup) == 1) {
                            approved:
                            $data = [
                                'status' => $cstatus,
                                'disapproved_remarks' => $rem
                            ];

                            if ($cstatus == 1) { // approved
                                $data['approvedby'] = $config['params']['user'];
                                $data['approveddate'] = $this->othersClass->getCurrentTimeStamp();
                            } else { // disapproved
                                if ($rem == '') {
                                    return ['status' => false, 'msg' => 'Approver Remarks is empty.', 'data' => []];
                                }
                                $data['disapprovedby'] = $config['params']['user'];
                                $data['disapproveddate'] = $this->othersClass->getCurrentTimeStamp();
                            }
                            break;
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
                $update = $this->coreFunctions->sbcupdate("changeshiftapp", $tempdata, ['line' => $line]);
                if ($update) {

                    if ($companyid == 53) { // camera
                        $query = "select cs.line,cl.clientname,date(cs.dateid) as dateid,cs.schedin,cs.schedout,cs.rem ,emp.email,cs.orgdaytype,cs.daytype,
                        date_format(tm.schedin, '%Y-%m-%d %h:%i %p') as orgschedin,
                        date_format(tm.schedout, '%Y-%m-%d %h:%i %p') as orgschedout,cs.disapproved_remarks as dremarks
                        from  changeshiftapp as cs
                        left join timecard as tm on tm.empid = cs.empid and tm.dateid = date(cs.dateid)
                        left join client as cl on cl.clientid = cs.empid
                        left join employee as emp on emp.empid = cs.empid
                        where cs.line = $line and cs.empid = $empid";
                        $shiftdata =  $this->coreFunctions->opentable($query);
                        $params = [];

                        if (!empty($shiftdata[0]->email)) {
                            $params['title'] = 'CHANGE SHIFT APPLICATION RESULT';
                            $params['clientname'] = $shiftdata[0]->clientname;
                            $params['line'] = $shiftdata[0]->line;
                            $params['dateid'] = $shiftdata[0]->dateid;
                            $params['schedin'] = $shiftdata[0]->schedin;
                            $params['schedout'] = $shiftdata[0]->schedout;
                            $params['orgschedin'] = $shiftdata[0]->orgschedin;
                            $params['orgschedout'] = $shiftdata[0]->orgschedout;
                            $params['dremarks'] = $shiftdata[0]->dremarks;
                            $params['remarks'] = $shiftdata[0]->rem;
                            $params['email'] = $shiftdata[0]->email;
                            $params['orgdaytype'] = $shiftdata[0]->orgdaytype;
                            $params['daytype'] = $shiftdata[0]->daytype;


                            $currentapp = $this->coreFunctions->getfieldvalue("client", "clientname", "clientid=?", [$admin]);
                            $params['approver'] = $currentapp;
                            $params['approvedstatus'] = $label;
                            $result = $this->linkemail->createChangeSchedEmail($params);

                            if (!$result['status']) {
                                return ['status' => false, 'msg' => '' . $result['msg']];
                            }
                        }


                        // if (!$result['status']) {
                        //     $data2 = [
                        //         'status' => 0,
                        //         'disapproved_remarks' => '',
                        //     ];
                        //     if ($cstatus == 1) {
                        //         $data2['approveddate'] = null;
                        //         $data2['approvedby'] = '';
                        //     } else {

                        //         $data2['disapproveddate'] = null;
                        //         $data2['disapprovedby'] = '';
                        //     }

                        //     $update = $this->coreFunctions->sbcupdate("changeshiftapp", $data2, ['line' => $line]);
                        //     return ['status' => false, 'msg' => 'Sending email failed: email was empty.'];
                        // }
                    }

                    $config['params']['doc'] = 'CHANGE SHIFT APPLICATION';
                    $this->logger->sbcmasterlog($config['params']['dataparams']['line'], $config, $label . $config['params']['dataparams']['remarks'] . ' (' . $config['params']['dataparams']['client'] . ') - ' . $config['params']['dataparams']['dateid']);
                    return ['status' => true, 'msg' => 'Successfully approved.', 'data' => [], 'reloadsbclist' => true, 'action' => 'changeshiftapplication'];
                }
            } else {
                return ['status' => false, 'msg' => 'Already approved.', 'data' => []];
            }
        }
    }
} //end class
