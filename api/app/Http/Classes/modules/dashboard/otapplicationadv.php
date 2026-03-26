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

class otapplicationadv
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'OT APPLICATION';
    public $gridname = 'customformacctg';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $linkemail;
    public $tablelogs = 'payroll_log';
    public $style = 'width:1000px;max-width:1000px;';
    public $issearchshow = true;
    public $showclosebtn = true;
    public $fields = ['otstatus', 'otstatus2', 'remarks', 'apothrs', 'batchid', 'approvedby', 'approvedate', 'approvedate2', 'approvedby2', 'apothrsextra', 'apndiffothrs', 'disapprovedby', 'disapprovedate', 'disapprovedby2', 'disapprovedate2', 'disapproved_remarks2'];

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

    public function getAttrib()
    {
        return [];
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
        $url = 'App\Http\Classes\modules\payroll\\' . 'otapplicationadv';
        $approversetup = app($url)->approvers($config['params']);
        if (count($approversetup) > 1) {
            $firstapprover = $approversetup[0];
        }
        $lastapprover = $approversetup[count($approversetup) - 1];
        $admin = $config['params']['adminid'];
        $companyid = $config['params']['companyid'];
        $approveby = $config['params']['row']['approvedby2'];
        $fapprover = $this->coreFunctions->getfieldvalue("client", "clientname", "client=?", [$approveby]);
        $approver = $this->coreFunctions->getfieldvalue("employee", "isapprover", "empid=?", [$admin]);
        $supervisor = $this->coreFunctions->getfieldvalue("employee", "issupervisor", "empid=?", [$admin]);
        $lapprover = false;
        $bothapprover = false;

        if ($companyid == 58) { // cdohris
            if ($supervisor && $approver) {
                $bothapprover = true;
            }
        } else {
            if ($lastapprover == 'issupervisor' && $supervisor || $lastapprover == 'isapprover' && $approver) {
                $lapprover = true;
            }
        }
        $fields = ['clientname', 'lblmessage', 'rem'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'lblmessage.label', 'Employee Remarks: ');
        data_set($col1, 'rem.label', 'Remarks');
        data_set($col1, 'lblmessage.style', 'font-size:11px;font-weight:bold;');

        if ($companyid == 53) { //camera
            data_set($col1, 'lblmessage.label', 'Employee Reason');
            data_set($col1, 'rem.label', 'Reason');
        }
        $fields = [['createdate', 'daytype']];

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

        data_set($col2, 'daytype.readonly', true);
        data_set($col2, 'lblrem.label', 'First Approver: ' . $fapprover);
        data_set($col2, 'lblrem.style', 'font-size:11px;font-weight:bold;');
        if ($bothapprover) { //for cdohris
            data_set($col2, 'lblrem.label', 'Approver: ');
            data_set($col2, 'remarks.readonly', false);
        }

        data_set($col2, 'lblapproved.label', 'Second Approver: ');
        data_set($col2, 'lblapproved.style', 'font-size:11px;font-weight:bold;');
        data_set($col2, 'createdate.style', 'padding:0px;');

        $fields = ['schedin', 'schedout', 'lblrem', 'othrs', 'othrsextra', 'ndiffothrs'];
        if ($bothapprover) {
            goto app;
        } else {
            if ($lapprover) {
                app:
                $fields = ['schedin', 'schedout', ['lblrem', 'lblmessage'], ['othrs', 'apothrs'], ['othrsextra', 'apothrsextra'], ['ndiffothrs', 'apndiffothrs']]; //['ndiffhrs', 'apndiffhrs']
            }
        }

        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'othrs.type', 'input');
        data_set($col3, 'othrs.label', 'OT Hrs');
        // data_set($col3, 'batch.addedparams', ['divid']);
        data_set($col3, 'apothrs.label', 'Approved OT Hrs');

        if ($bothapprover) {
            goto edithrs;
        } else {
            if ($lapprover) {
                edithrs:
                data_set($col3, 'apothrs.readonly', false);
                // data_set($col3, 'apndiffhrs.readonly', false);
                data_set($col3, 'apothrsextra.readonly', false);
                data_set($col3, 'apndiffothrs.readonly', false);
            }
        }

        data_set($col3, 'lblrem.label', 'Computed Hours: ');
        data_set($col3, 'lblrem.style', 'font-weight:bold;font-size:11px;');
        data_set($col3, 'lblmessage.label', 'Approved Hours: ');
        data_set($col3, 'lblmessage.style', 'font-weight:bold;font-size:11px;');

        data_set($col3, 'schedin.type', 'input');
        data_set($col3, 'schedout.type', 'input');
        data_set($col3, 'schedin.label', 'OT Timein');
        data_set($col3, 'schedout.label', 'OT Timeout');

        $fields = [['refresh', 'disapproved']];
        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'refresh.label', 'APPROVE');
        data_set($col4, 'refresh.confirmlabel', 'Approved this OT application?');
        data_set($col4, 'disapproved.color', 'red');
        data_set($col4, 'refresh.color', 'blue');

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function paramsdata($config)
    {
        $divid = $config['params']['row']['divid'];
        return $this->coreFunctions->opentable(
            "select ot.line, client.client,client.clientid as empid, client.clientname, date(ot.dateid) as dateid, ot.othrs," . $divid . " as divid,
        ot.othrs as apothrs,ot.remarks,ot.disapproved_remarks2 as remark,ot.rem,ot.ndiffhrs, ot.ndiffhrs as apndiffhrs,ot.othrsextra,ot.othrsextra as apothrsextra,ot.ndiffothrs,
        ot.ndiffothrs as apndiffothrs,date(ot.createdate) as createdate,date(ot.scheddate) as scheddate,dayname(ot.scheddate) as dayname,
        time(ot.ottimein) as ottimein, time(ot.ottimeout) as ottimeout,
        date_format(ot.ottimein, '%Y-%m-%d %h:%i %p') as schedin, date_format(ot.ottimeout, '%Y-%m-%d %h:%i %p') as schedout,ot.daytype
        from otapplication as ot 
        left join client on client.clientid=ot.empid
        where ot.line=?",
            [$config['params']['row']['line']]
        );
    }

    public function data()
    {
        return [];
    }

    public function loaddata($config)
    {

        $action = $config['params']['action2'];
        $adminid = $config['params']['adminid'];
        $url = 'App\Http\Classes\modules\payroll\\' . 'otapplicationadv';
        $approversetup = app($url)->approvers($config['params']);
        if (isset($config['params']['dataparams'])) {
            $companylist = [44, 51]; // stonepro, ulitc


            $othrs = $config['params']['dataparams']['othrs'];
            $apothrs = $config['params']['dataparams']['apothrs'];
            // $ndiffhrs = $config['params']['dataparams']['ndiffhrs'];
            $othrsextra = $config['params']['dataparams']['othrsextra'];
            $apothrsextra = $config['params']['dataparams']['apothrsextra'];
            $ndiffothrs = $config['params']['dataparams']['ndiffothrs'];
            $dateid = $config['params']['dataparams']['dateid'];
            $empid = $config['params']['dataparams']['empid'];
            $ottimein = $config['params']['dataparams']['ottimein'];
            $otin = $config['params']['dataparams']['schedin'];
            $otout = $config['params']['dataparams']['schedout'];

            $empname = $config['params']['dataparams']['clientname'];
            $dayname = $config['params']['dataparams']['dayname'];
            $scheddate = $config['params']['dataparams']['scheddate'];
            $rem = $config['params']['dataparams']['rem'];
            $createdate = $config['params']['dataparams']['createdate'];


            if (isset($config['params']['dataparams']['line'])) {
                $line = $config['params']['dataparams']['line'];


                switch ($action) {
                    case 'ar':
                        $otstatus = 2;
                        $label = 'Approved ';
                        $status = $this->coreFunctions->datareader("select otstatus as value from otapplication where line=? and otstatus = 2 ", [$line]);
                        break;

                    default:
                        $otstatus = 3;
                        $label = 'Disapproved ';
                        $status = $this->coreFunctions->datareader("select otstatus as value from otapplication where line=? and otstatus = 3 ", [$line]);
                        break;
                }

                $companyid = $config['params']['companyid'];
                $approver = $this->coreFunctions->getfieldvalue("employee", "isapprover", "empid=?", [$config['params']['adminid']]);
                $supervisor = $this->coreFunctions->getfieldvalue("employee", "issupervisor", "empid=?", [$config['params']['adminid']]);
                $otapprover = $this->coreFunctions->getfieldvalue("employee", "isotapprover", "empid=?", [$config['params']['adminid']]);
                if (in_array($companyid, $companylist)) {
                    if ($apothrs <  8) {
                        if ($apothrsextra > $othrsextra) {
                            return ['status' => false, 'msg' => 'Approved OT > 8 Hours is greater than Applied OT > 8', 'data' => []];
                        }
                    }
                }
                if (!$status) {
                    $label_reason = 'Remarks';
                    if ($companyid == 53) { // camera
                        $label_reason = 'Reason';
                    }
                    $rem1 = $config['params']['dataparams']['remarks'];
                    $rem2 = $config['params']['dataparams']['remark'];
                    $bothapprover = false;
                    $lastapp = false;
                    $lstat = 0;
                    foreach ($approversetup as $key => $value) {
                        if (count($approversetup) > 1) {
                            if (($supervisor && $approver) || ($otapprover && $approver)) { // cdohris|camera
                                $bothapprover = true;
                                goto approved;
                            } else {

                                if ($key == 0) {

                                    if ($value == 'issupervisor' && $supervisor || $value == 'isapprover' && $approver || $value == 'isotapprover' && $otapprover) {
                                        $data = [
                                            'otstatus2' => $otstatus,
                                        ];
                                        if ($otstatus == 2) { // approved
                                            $data['approvedby2'] = $config['params']['user'];
                                            $data['approvedate2'] = $this->othersClass->getCurrentTimeStamp();
                                        } else { // disapproved

                                            if ($rem2 == '') {
                                                return ['status' => false, 'msg' => "Approver " . $label_reason . " is empty.", 'data' => []];
                                            }
                                            $data['disapprovedby2'] = $config['params']['user'];
                                            $data['disapprovedate2'] = $this->othersClass->getCurrentTimeStamp();
                                            $lastapp = true;
                                        }
                                        $data['disapproved_remarks2'] = $rem2;
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
                                $lastapp = true;
                                $lstat = $otstatus;
                                if ($otstatus == 2) { // approved
                                    $data = [
                                        'otstatus' => $otstatus,
                                        'remarks' => $config['params']['dataparams']['remarks'],
                                        'apothrs' => $config['params']['dataparams']['apothrs'],
                                        'approvedby' => $config['params']['user'],
                                        'approvedate' => $this->othersClass->getCurrentTimeStamp(),
                                        'apothrsextra' => $config['params']['dataparams']['apothrsextra'],
                                        'apndiffothrs' => $config['params']['dataparams']['apndiffothrs']
                                    ];
                                } else { //disapproved
                                    if ($rem1 == '') {
                                        return ['status' => false, 'msg' => "Approver " . $label_reason . " is empty.", 'data' => []];
                                    }
                                    $data = [
                                        'otstatus' => $otstatus,
                                        'remarks' => $rem1,
                                        'disapprovedby' => $config['params']['user'],
                                        'disapprovedate' => $this->othersClass->getCurrentTimeStamp()
                                    ];
                                }
                                if ($bothapprover) {
                                    $data['otstatus2'] = $otstatus;

                                    if ($otstatus == 2) {
                                        $data['approvedby2'] = $config['params']['user'];
                                        $data['approvedate2'] = $this->othersClass->getCurrentTimeStamp();
                                    } else {
                                        $data['disapprovedby2'] = $config['params']['user'];
                                        $data['disapprovedate2'] = $this->othersClass->getCurrentTimeStamp();
                                    }
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
                    $update = $this->coreFunctions->sbcupdate("otapplication", $tempdata, ['line' => $line]);
                    if ($update) {


                        if ($companyid == 53 || $companyid == 51) { //camera|ulitc
                            $appname = $this->coreFunctions->datareader("select clientname as value from client where email=?", [$config['params']['user']]);
                            $params['title'] = 'OT APPLICATION RESULT';
                            $params['clientname'] = $empname;
                            $params['line'] = $line;
                            $params['scheddate'] = $scheddate . " (" . $dayname . ")";
                            $params['ottimein'] = $otin;
                            $params['ottimeout'] = $otout;
                            $params['rem'] = $rem;
                            $params['reason1'] = $rem1;

                            $params['othrs'] = $othrs;
                            $params['othrsextra'] = $othrsextra;
                            $params['ndiffothrs'] = $ndiffothrs;
                            $params['daytype'] = $dayname;
                            $params['approvedstatus'] = $label;
                            $params['createdate'] = $createdate;
                            $params['companyid'] = $companyid;

                            // $params['appname'] = $appname;
                            $qry = "select
                            ifnull(app.clientname,disapp.clientname) as appname,ifnull(app1.clientname,disapp2.clientname) as appname2,
                            emp.approver1, emp.approver2,client.email as uname,emp.email
                            from otapplication as ot
						    left join employee as emp on emp.empid = ot.empid
                            left join client on client.clientid=ot.empid
                            left join client as app on app.email=ot.approvedby and app.email <> ''
                            left join client as disapp ON disapp.email=ot.disapprovedby and disapp.email <> ''
                            left join client as app1 on app1.email=ot.approvedby2 and app1.email <> ''
                            left join client as disapp2 on disapp2.email=ot.disapprovedby2 and disapp2.email <> ''
                            where ot.line= $line";
                            $data2 = $this->coreFunctions->opentable($qry);


                            $qry = "select cl.clientname,approver1,approver2,cl.email as uname,emp.email from employee as emp
                                    left join client as cl on cl.clientid = emp.empid
                                    where  emp.isapprover = 1 and (emp.empid = " . $data2[0]->approver1 . " or emp.empid = " . $data2[0]->approver2 . ")";
                            $approver_data = $this->coreFunctions->opentable($qry);
                            $ldata = $approver_data; // send last approver
                            if ($lastapp) {
                                $ldata = $data2; //employee
                            }
                            if (!empty($ldata)) {
                                foreach ($ldata as $key => $value) {
                                    if (!empty($value->email)) {
                                        $params['email'] = $value->email;
                                        $params['approver'] = $value->uname;
                                        if ($lastapp) {
                                            if ($lstat != 0) {
                                                $params['appname'] = $appname;
                                                $params['appname2'] = $value->appname2;
                                                if ($params['appname2'] == null) {
                                                    $params['appname2'] = '';
                                                }
                                            } else {
                                                $params['appname'] = $appname;
                                            }
                                        } else {
                                            $params['appname'] = $appname;
                                        }

                                        $emailresult = $this->linkemail->createOTEmail($params);
                                        if (!$emailresult['status']) {
                                            return ['status' => false, 'msg' => '' . $emailresult['msg']];
                                        }
                                    }
                                }
                            }


                            // if (!empty($data2[0]->email)) {
                            //     $params['approver'] = $appname;
                            //     $params['email'] = $data2[0]->email;
                            //     $emailresult = $this->linkemail->createOTEmail($params);
                            //     if (!$emailresult['status']) {
                            //         return ['status' => false, 'msg' => '' . $emailresult['msg']];
                            //     }
                            // }
                            // if ($otstatus == 2) {
                            //     $data = [
                            //         'otstatus' => 1,
                            //         'disapproved_remarks2' => '',
                            //         'apothrs' => 0,
                            //         'batchid' => 0,
                            //         'approvedby' => '',
                            //         'approvedate' => null,
                            //         'apothrsextra' => 0
                            //     ];
                            // } else {
                            //     $data = [
                            //         'otstatus' => 1,
                            //         'remarks' => '',
                            //         'disapprovedby' => '',
                            //         'disapprovedate' => null
                            //     ];
                            // }
                            // $this->coreFunctions->sbcupdate("otapplication", $data, ['line' => $line]);
                            // return ['status' => false, 'msg' => 'Sending email failed: email was empty.'];


                        }

                        $config['params']['doc'] = 'OTAPPLICATIONADV';
                        $this->logger->sbcmasterlog($line, $config, $label . ' (' . $config['params']['dataparams']['client'] . ') - ' . $config['params']['dataparams']['dateid'] .
                            ". Computed OT Hrs: " . $config['params']['dataparams']['othrs'] . " Approved OT Hrs: " . $config['params']['dataparams']['apothrs']);

                        $this->logger->sbcmasterlog($line, $config, $label . ' (' . $config['params']['dataparams']['client'] . ') - ' . $config['params']['dataparams']['dateid'] .
                            " Computed OT > 8: " . $config['params']['dataparams']['othrsextra'] . " Approved OT > 8: " . $config['params']['dataparams']['apothrsextra']);

                        $this->logger->sbcmasterlog($line, $config, $label . ' (' . $config['params']['dataparams']['client'] . ') - ' . $config['params']['dataparams']['dateid'] .
                            " Computed N-Diff OT Hrs: " . $config['params']['dataparams']['ndiffothrs'] . " Approved N-Diff OT Hrs: " . $config['params']['dataparams']['apndiffothrs']);

                        return ['status' => true, 'msg' => 'Successfully ' . $label . ' ', 'data' => [], 'reloadsbclist' => true, 'action' => 'otapplicationadv'];
                    }
                } else {
                    return ['status' => false, 'msg' => 'Already approved.', 'data' => []];
                }
            }
        }

        return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => []];
    }
} //end class
