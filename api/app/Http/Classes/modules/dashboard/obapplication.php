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
use Illuminate\Support\Facades\Storage;

class obapplication
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'OB APPLICATION';
    public $gridname = 'customformacctg';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $linkemail;
    public $tablelogs = 'payroll_log';
    public $style = 'width:90%;max-width:90%;';
    public $issearchshow = true;
    public $showclosebtn = true;
    public $fields = ['status', 'status2', 'approverem', 'disapproved_remarks2', 'approvedby', 'approvedate', 'disapprovedby', 'disapprovedate', 'approvedby2', 'approvedate2', 'disapprovedby2', 'disapprovedate2'];


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
        $url = 'App\Http\Classes\modules\payroll\\' . 'obapplication';
        $approversetup = app($url)->approvers($config['params']);
        $approveby = $config['params']['row']['approvedby2'];
        $companyid = $config['params']['companyid'];
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
        if ($companyid == 53) { //camera
            data_set($col1, 'rem.label', 'Reason');
            data_set($col1, 'lblmessage.label', 'Employee Reason');
        }

        $fields = [['createdate', 'scheddate']];

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

        if ($companyid == 53) { //camera
            data_set($col2, 'remark.label', 'Reason');
            data_set($col2, 'remarks.label', 'Reason');
        }
        data_set($col2, 'createdate.style', 'padding:0px');
        data_set($col2, 'scheddate.style', 'padding:0px');

        data_set($col2, 'lblrem.label', 'First Approver: ' . $fapprover);
        data_set($col2, 'lblrem.style', 'font-size:11px;font-weight:bold;');

        if ($bothapprover) { //for cdohris
            data_set($col2, 'lblrem.label', 'Approver: ');
            data_set($col2, 'remarks.readonly', false);
        }
        data_set($col2, 'lblapproved.label', 'Second Approver: ');
        data_set($col2, 'lblapproved.style', 'font-size:11px;font-weight:bold;');


        switch ($companyid) {
            case 58: //cdo
                $fields = ['ontrip', 'schedin', 'type'];
                break;
            default:
                $fields = ['schedin', 'type'];
                if ($companyid == 53) { //camera
                    array_push($fields, 'location');
                }
                break;
        }

        $col3 = $this->fieldClass->create($fields);
        if ($companyid == 58) { //cdo
            data_set($col3, 'ontrip.type', 'input');
        }
        data_set($col3, 'type.type', 'input');
        data_set($col3, 'schedin.type', 'input');
        data_set($col3, 'schedin.label', 'Applied Date Time');

        $fields = ['picture', ['refresh', 'disapproved']];

        if ($companyid != 53) { // not camera
            $fields = [['refresh', 'disapproved']];
        }
        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'refresh.label', 'APPROVE');
        data_set($col4, 'refresh.confirm', true);
        data_set($col4, 'refresh.confirmlabel', 'Approved this OB application?');
        data_set($col4, 'refresh.disapproved', true);
        data_set($col4, 'refresh.color', 'blue');
        data_set($col4, 'disapproved.confirmlabel', 'Disapproved this OB application?');
        data_set($col4, 'disapproved.color', 'red');
        if ($companyid == 53) { // camera
            data_set($col4, 'picture.style', 'height: 300px; max-width: 100%;');
        }
        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function paramsdata($config)
    {
        $data = [];
        $result = $this->coreFunctions->opentable("select ob.line,  client.client, client.clientname, date(ob.dateid) as dateid, date(ob.dateid) as datetime,
        ob.type,ob.approverem as remarks,ob.rem,ob.disapproved_remarks2 as remark,date(ob.createdate) as createdate,
        date(ob.scheddate) as scheddate,dayname(ob.scheddate) as dayname,emp.email,date_format(ob.dateid, '%Y-%m-%d %h:%i %p') as schedin,ob.location,
        ob.ontrip,if(ob.picture = '','/images/employee/default_emp_portal.png',ob.picture) as picture
        from obapplication as ob 
        left join client on client.clientid=ob.empid
        left join employee as emp on emp.empid = client.clientid
        where approvedate is null and disapprovedate is null and ob.line=?", [$config['params']['row']['line']]);

        foreach ($result as $key => $value) {
            if ($value->picture != '') {
                Storage::disk('public')->url($value->picture);
            }
        }
        return $result;
    }

    public function data()
    {
        return [];
    }

    public function loaddata($config)
    {
        $rem = $config['params']['dataparams']['remarks'];
        $rem2 = $config['params']['dataparams']['remark'];
        $empname = $config['params']['dataparams']['clientname'];
        $scheddate = $config['params']['dataparams']['scheddate'];
        $dayname = $config['params']['dataparams']['dayname'];
        $datetime = $config['params']['dataparams']['datetime'];
        $dateid = $config['params']['dataparams']['dateid'];
        $type = $config['params']['dataparams']['type'];
        $emprem = $config['params']['dataparams']['rem'];
        $email = $config['params']['dataparams']['email'];
        $location = $config['params']['dataparams']['location'];

        $action = $config['params']['action2'];

        $companyid = $config['params']['companyid'];
        $admin = $config['params']['adminid'];
        $approver = $this->coreFunctions->getfieldvalue("employee", "isapprover", "empid=?", [$admin]);
        $supervisor = $this->coreFunctions->getfieldvalue("employee", "issupervisor", "empid=?", [$admin]);
        $url = 'App\Http\Classes\modules\payroll\\' . 'obapplication';
        $approversetup = app($url)->approvers($config['params']);
        if (isset($config['params']['dataparams']['line'])) {
            $line = $config['params']['dataparams']['line'];

            switch ($action) {
                case 'ar':
                    $obstatus = 'A';
                    $label = 'Approved ';
                    $status = $this->coreFunctions->datareader("select approvedate as value from obapplication where line=? and approvedate is not null", [$line]);
                    break;

                default:
                    $obstatus = 'D';
                    $label = 'Disapproved ';
                    $status = $this->coreFunctions->datareader("select disapprovedate as value from obapplication where line=? and disapprovedate is not null", [$line]);
                    break;
            }

            if (!$status) {
                $label_reason = 'Remarks';
                if ($companyid == 53) { // camera
                    $label_reason = 'Reason';
                }
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
                                        'status2' => $obstatus,
                                        'disapproved_remarks2' => $rem2
                                    ];

                                    if ($obstatus == 'A') { // approved
                                        $data['approvedby2'] = $config['params']['user'];
                                        $data['approvedate2'] = $this->othersClass->getCurrentTimeStamp();
                                    } else { // disapproved
                                        if ($rem2 == '') {
                                            return ['status' => false, 'msg' => "First Approver " . $label_reason . " is empty.", 'data' => []];
                                        }
                                        $data['status'] = $obstatus;
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
                                'status' => $obstatus,
                                'approverem' => $rem
                            ];

                            if ($obstatus == 'A') { // approved
                                $data['approvedby'] = $config['params']['user'];
                                $data['approvedate'] = $this->othersClass->getCurrentTimeStamp();
                            } else { // disapproved
                                if ($rem == '') {
                                    return ['status' => false, 'msg' => "First Approver " . $label_reason . " is empty.", 'data' => []];
                                }
                                $data['disapprovedby'] = $config['params']['user'];
                                $data['disapprovedate'] = $this->othersClass->getCurrentTimeStamp();
                            }
                            if ($bothapprover) {
                                $data['status2'] = $obstatus;
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
                $update = $this->coreFunctions->sbcupdate("obapplication", $tempdata, ['line' => $line]);
                if ($update) {

                    if ($companyid == 53 || $companyid == 51) { // camera | ulitc
                        $params = [];



                        $blnSuccess = true;
                        if (!empty($email)) {

                            $params['title'] = $this->modulename;
                            $params['clientname'] = $empname;
                            $params['line'] = $line;
                            $params['scheddate'] = $scheddate . " (" . $dayname . ")";
                            $params['dateid'] = $dateid;
                            $params['rem'] = $emprem;
                            $params['reason1'] = $rem2;
                            $params['reason2'] = $rem;
                            $params['datetime'] = $datetime;
                            $params['type'] = $type;
                            $params['location'] = $location;
                            $params['companyid'] = $companyid;
                            
                            $params['approvedstatus'] = 'Application ' . $label;

                            $data = $this->coreFunctions->opentable("select app.clientname as appname, app2.clientname as dappname
                                from obapplication as ob left join employee as emp on emp.empid=ob.empid 
                                left join client as app on app.email=ob.approvedby and app.email <> ''
                                left join client as app2 on app2.email=ob.disapprovedby and app2.email <> ''
                                where ob.line= $line ");
                            if ($obstatus == 'A') {
                                $appname = $data[0]->appname;
                            } else {
                                $appname = $data[0]->dappname;
                            }
                            
                            $params['approver'] = $appname;
                            $params['email'] = $email;
                            $result = $this->linkemail->createOBEmail($params);
                            if (!$result['status']) {
                                return ['status' => false, 'msg' => '' . $result['msg']];
                            } 
                        }


                        // if (!$blnSuccess) {
                        //     $data = [
                        //         'status' => 'E',
                        //         'approverem' => ''
                        //     ];
                        //     if ($obstatus == 'A') {
                        //         $data['approvedby'] = '';
                        //         $data['approvedate'] = null;
                        //     } else {
                        //         $data['disapprovedby'] = '';
                        //         $data['disapprovedate'] = null;
                        //     }
                        //     $update = $this->coreFunctions->sbcupdate("obapplication", $data, ['line' => $line]);
                        //     return ['status' => false, 'msg' => 'Sending email failed: email was empty.'];
                        // }
                    }


                    $config['params']['doc'] = 'OBAPPLICATION';
                    $this->logger->sbcmasterlog($config['params']['dataparams']['line'], $config, $label . $config['params']['dataparams']['type'] . ' (' . $config['params']['dataparams']['client'] . ') - ' . $config['params']['dataparams']['dateid']);
                    return ['status' => true, 'msg' => 'Successfully approved.', 'data' => [], 'reloadsbclist' => true, 'action' => 'obapplication'];
                }
            } else {
                return ['status' => false, 'msg' => 'Already approved.', 'data' => []];
            }
        }

        return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => []];
    }
} //end class
