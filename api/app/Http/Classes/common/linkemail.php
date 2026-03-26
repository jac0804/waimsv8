<?php

namespace App\Http\Classes\common;

use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\Logger;
use App\Http\Classes\othersClass;

use Exception;

class linkemail
{

    private $coreFunctions;
    private $logger;
    private $companysetup;
    private $othersClass;
    private $host = '';
    public $tablelogs = 'payroll_log';

    public function __construct()
    {
        $this->coreFunctions = new coreFunctions;
        $this->logger = new Logger;
        $this->companysetup = new companysetup;
        $this->othersClass = new othersClass;
        $this->host = env('APP_URL');
    }

    public function linkemailfunc($params)
    {
        //functionname(md5), username(md5), transaction id(md5)
        // return '1111';

        switch ($params['func']) {
            case md5('approve_ob'):
                $params['status'] = 'A';
                return $this->approvedOB($params);
                break;
            case md5('disapprove_ob'):
                $params['status'] = 'D';
                return $this->approvedOB($params);
                break;
            case md5('approve_ot'):
                $params['status'] = 2;
                return $this->approvedOT($params);
                break;
            case md5('disapprove_ot'):
                $params['status'] = 3;
                return $this->approvedOT($params);
                break;
            case md5('approve_loan'):
                $params['status'] = 'A';
                return $this->approvedLoan($params);
                break;
            case md5('disapprove_loan'):
                $params['status'] = 'D';
                return $this->approvedLoan($params);
                break;
            case md5('approve_sched'):
                $params['status'] = 1;
                return $this->approvedSched($params);
                break;
            case md5('disapprove_sched'):
                $params['status'] = 2;
                return $this->approvedSched($params);
                break;
            case md5('approve_leave'):
                $params['status'] = 'A';
                return $this->approvedLeave($params);
                break;
            case md5('process_leave'):
                $params['status'] = 'P';
                return $this->approvedLeave($params);
                break;
            case md5('disapprove_leave'):
                $params['status'] = 'D';
                return $this->approvedLeave($params);
                break;
            case md5('approve_initialob'):
                $params['status'] = 'A';
                return $this->approvedInitalOB($params);
                break;
            case md5('disapprove_initialob'):
                $params['status'] = 'D';
                return $this->approvedInitalOB($params);
                break;
        }
    }
    private function approvedOB($params)
    {
        $status = $params['status'];
        $msg = '';
        $formailstatus = '';
        $params['companyid'] = $params['cid'];
        $isapp = $params['isapp'];
        $line = 0;
        $scheddate = null;
        $empcode = '';
        $empname = '';

        $config['params']['doc'] = 'OB';
        $config['params']['user'] = 'mail';
        $url = 'App\Http\Classes\modules\payroll\\' . 'obapplication';
        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='OB'");
        $both = false;
        if ($approversetup == '') {
            $approversetup = app($url)->approvers($params);
        } else {
            if (str_contains($approversetup, ' or ')) {
                $approversetup = explode(' or ', $approversetup);
                $both = true;
            } else {
                $approversetup = explode(',', $approversetup);
            }
            foreach ($approversetup as $appkey => $appsetup) {
                if ($appsetup == 'Supervisor') {
                    $approversetup[$appkey] = 'issupervisor';
                } else {
                    $approversetup[$appkey] = 'isapprover';
                }
            }
        }
        if ($status == 'A') {
            $msg = 'Application approved.';
            $formailstatus = 'Approved';
        } elseif ($status == 'D') {
            $msg = 'Application disapproved!';
            $formailstatus = 'Disapproved';
        }

        $datalogs = $this->coreFunctions->opentable("select ob.line,cl.client as empcode,cl.clientname as empname,date(ob.scheddate) as scheddate
                        from obapplication as ob 
                        left join client as cl on cl.clientid=ob.empid 
                        where md5(ob.line)='" . $params['id'] . "'");

        if (!empty($datalogs)) {
            $line = $datalogs[0]->line;
            $empcode = $datalogs[0]->empcode;
            $scheddate = $datalogs[0]->scheddate;
            $empname = $datalogs[0]->empname;
        }
        $remarkslog = app($url)->modulename . ' - ' .  $empname . ' - ' . $scheddate;
        $approver = $this->coreFunctions->opentable("select cl.email as appcode, cl.clientname as appname,emp.empid 
        from employee as emp left join client as cl on cl.clientid = emp.empid where md5(cl.email)='" . $params['uname'] . "'");
        if (!empty($approver)) {
            $isapprover = $this->coreFunctions->getfieldvalue("employee", "$isapp", "empid=?", [$approver[0]->empid]);
            $data = $this->coreFunctions->opentable("select ob.line, ob.status, ob.approvedby, ob.disapprovedby, cl.client as username,
                        ob.empid, emp.email,  cl.clientname, ob.dateid, app.clientname as appname, app2.clientname as dappname,
                        dayname(ob.scheddate) as dayname, date(ob.scheddate) as scheddate, ob.type, date(ob.createdate) as dateid, ob.rem,ob.dateid2 as datetime2, ob.dateid as datetime,ob.status2,
                        ob.location,ob.approverem,ob.disapproved_remarks2,ob.initial_remarks,appby2.clientname as appname2,disapp.clientname as disapp2
                        from obapplication as ob left join employee as emp on emp.empid=ob.empid 
                        left join client as cl on cl.clientid=emp.empid 
                        left join client as app on app.email=ob.approvedby and app.email <> ''
                        left join client as appby2 on appby2.email=ob.approvedby2 and appby2.email <> ''
                        left join client as app2 on app2.email=ob.disapprovedby and app2.email <> ''
                        left join client as disapp on disapp.email=ob.disapprovedby2 and disapp.email <> ''
                        where md5(ob.line)='" . $params['id'] . "'");

            if (!empty($data)) {
                $response = [];
                switch ($data[0]->status) {
                    case 'A':
                        $response = ['msg' => 'Already approved by ' . $data[0]->appname . '.', 'status' => 'F'];
                        break;
                    case 'D':
                        $response = ['msg' => 'Already disapproved by ' . $data[0]->dappname . '.', 'status' => 'F'];
                        break;
                    case 'E':
                        $lstatus = '';
                        $lastapp = false;

                        foreach ($approversetup as $key => $value) {
                            if (count($approversetup) > 1) {
                                if ($key == 0) {

                                    if ($value == $isapp && $isapprover) {


                                        switch ($data[0]->status2) {
                                            case 'A':
                                                $response = ['msg' => 'Already approved by ' . $data[0]->appname2 . '.', 'status' => 'F'];
                                                break;
                                            case 'D':
                                                $response = ['msg' => 'Already disapproved by ' . $data[0]->disapp2 . '.', 'status' => 'F'];
                                                break;
                                        }
                                        if ($both) {
                                            goto approved;
                                        }
                                        $appdata = ['status2' => $status];
                                        if ($status == 'A') {
                                            $appdata['approvedby2'] = $approver[0]->appcode;
                                            $appdata['approvedate2'] = $this->othersClass->getCurrentTimeStamp();
                                        } else {
                                            $appdata['disapprovedby2'] = $approver[0]->appcode;
                                            $appdata['disapprovedate2'] = $this->othersClass->getCurrentTimeStamp();
                                            // $appdata['status'] = 'D'; //pag na disapproved update na din ito last status
                                            $lastapp = true;
                                        }
                                        break; // loop
                                    }
                                } else {
                                    if ($value == $isapp && $isapprover) {
                                        if ((count($approversetup) - 1) == $key) {
                                            goto approved;
                                        }
                                    }
                                }
                            } else {
                                if (count($approversetup) == 1) {
                                    approved:
                                    $lastapp = true;
                                    $lstatus = $status;
                                    $appdata = ['status' => $status];
                                    if ($status == 'A') {
                                        $appdata['approvedby'] = $approver[0]->appcode;
                                        $appdata['approvedate'] = $this->othersClass->getCurrentTimeStamp();
                                    } else {
                                        $appdata['disapprovedby'] = $approver[0]->appcode;
                                        $appdata['disapprovedate'] = $this->othersClass->getCurrentTimeStamp();
                                    }
                                    break; // break loop
                                }
                            }
                        }

                        if (empty($response)) {

                            $data2['empid'] = $data[0]->empid;
                            $config['params']['doc'] = 'OBAPPLICATION';
                            $config['params']['user'] = $approver[0]->appcode;
                            $config['params']['adminid'] = $approver[0]->empid;
                            $config['params']['companyid'] = $params['companyid'];
                            $pendingdata = $this->coreFunctions->opentable("select * from pendingapp where line=" . $data[0]->line . " and doc='OB'");

                            $this->coreFunctions->execqry("delete from pendingapp where doc='OB' and line=" . $data[0]->line, 'delete');
                            $res_status = true;
                            switch ($params['companyid']) {
                                case 53: //camera
                                case 51: //ulitc
                                    $stats = true;
                                    break;
                                default:
                                    $stats = false;
                                    break;
                            }
                            $result = ['status' => true];
                            if (!$lastapp) {
                                $result = $this->othersClass->insertUpdatePendingapp(0, $data[0]->line, 'OB', $data2, $url, $config, 0,  $stats);
                            }
                            if (!$result['status']) {
                                if (!empty($pendingdata)) {
                                    foreach ($pendingdata as $pd) {
                                        $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid,approver) values(?, ?, ?, ?, ?)", 'insert', [$pd->trno, $pd->line, $pd->doc, $pd->clientid, $pd->approver]);
                                    }
                                }
                                $msg = $result['msg'];
                                $res_status = $result['status'];
                            } else {
                                if ($res_status) {

                                    $appdata['editdate'] = $this->othersClass->getCurrentTimeStamp();
                                    $appdata['editby'] = $approver[0]->appname;
                                    $update = $this->coreFunctions->sbcupdate('obapplication', $appdata, ['line' => $data[0]->line]);
                                    $msg = 'Successfully ' . $formailstatus;

                                    $response = ['msg' => $msg, 'status' => $status];
                                    if ($update) {

                                        $info = [];
                                        $info['subject'] = 'OB APPLICATION RESULT';
                                        $info['title'] = 'OB APPLICATION';

                                        $info['clientname'] = $data[0]->clientname;
                                        $info['line'] = $data[0]->line;
                                        $info['scheddate'] = $data[0]->scheddate . " (" . $data[0]->dayname . ")";
                                        $info['dateid'] = $data[0]->dateid;
                                        $info['rem'] = $data[0]->rem;
                                        $info['reason1'] = $data[0]->disapproved_remarks2;
                                        $info['reason2'] = $data[0]->approverem;
                                        $info['datetime'] = $data[0]->datetime;
                                        if (isset($data[0]->datetime2) && $data[0]->datetime2 != null) {
                                            $info['datetime2'] = $data[0]->datetime2;
                                        }
                                        $info['type'] = $data[0]->type;

                                        $info['email'] = $data[0]->email;
                                        $info['approver'] = $approver[0]->appname;
                                        $info['location'] = $data[0]->location;
                                        $info['companyid'] = $params['companyid'];
                                        $info['appstatus'] = $formailstatus;
                                        $info['muduletype'] = 'OB';

                                        $query = "select cl.email as username ,emp.email,app.doc,app.approver from pendingapp as app
		                                 left join client as cl on cl.clientid = app.clientid
		                                 left join employee as emp on emp.empid = app.clientid 
		                                 where doc = 'OB' and line = " . $data[0]->line . " ";

                                        $app_data = $this->coreFunctions->opentable($query);
                                        $l_data = $app_data;
                                        if ($lastapp) {
                                            $l_data = $data;
                                        }
                                        if (!empty($l_data)) {
                                            foreach ($l_data as $key => $value) {
                                                if (!empty($value->email)) {
                                                    $info['email'] = $value->email;
                                                    $info['approver'] = $value->username;

                                                    $info['isapp'] = isset($value->approver) ? $value->approver : "";
                                                    $info['appname'] = $approver[0]->appname;
                                                    if ($lastapp) {
                                                        $info['title'] = 'OB APPLICATION RESULT';
                                                        $info['approvedstatus'] = 'Application ' . $formailstatus;
                                                        if ($lstatus != "") {
                                                            $info['appname2'] = isset($value->appname2) ? $value->appname2 : '';
                                                        }
                                                    }
                                                    $mailresult = $this->weblink($info, $config);
                                                    if (!$mailresult['status']) {
                                                        $msg = $mailresult['msg'];
                                                        $res_status = false;
                                                    }
                                                }
                                            }
                                            if (!$res_status) {
                                                $response = ['msg' => $msg, 'status' => 'F'];
                                                $this->logger->sbcmasterlog2($line, $config, " MAIL - DATE: " . $scheddate . "" . $msg, app($url)->tablelogs);
                                            } else {
                                                $response = ['msg' => $msg, 'status' => $status];
                                                $this->logger->sbcmasterlog2($line, $config, " MAIL - DATE: " . $scheddate . "" . $msg, app($url)->tablelogs);
                                            }
                                        }
                                    } else {
                                        if (!empty($pendingdata)) {
                                            foreach ($pendingdata as $pd) {
                                                $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid,approver) values(?, ?, ?, ?, ?)", 'insert', [$pd->trno, $pd->line, $pd->doc, $pd->clientid, $pd->approver]);
                                            }
                                        }
                                        $response = ['msg' => 'Failed to approved Updating Record Error.', 'status' => 'F'];
                                        $this->logger->sbcmasterlog2($line, $config, " MAIL - DATE: " . $scheddate . " Failed to approved Updating Record Error", app($url)->tablelogs);
                                    }
                                }
                            }
                        }

                        break;
                }
            } else {
                $response = ['msg' => 'Application doesnt exist or has been deleted!', 'status' => 'F'];
                $this->logger->sbcmasterlog2($line, $config, " MAIL - DATE: " . $scheddate . " Application doesnt exist or has been deleted!", app($url)->tablelogs);
            }
        } else {
            $response = ['msg' => 'Invalid approver.', 'status' => 'F'];
            $this->logger->sbcmasterlog2($line, $config, " MAIL - DATE: " . $scheddate . " Invalid approver", app($url)->tablelogs);
        }
        $response['remarks'] = $remarkslog;
        return view('emails.approved', compact('response'));
    }
    private function approvedInitalOB($params)
    {
        $status = $params['status'];
        $msg = '';
        $formailstatus = '';
        $isapp = $params['isapp'];
        $params['companyid'] = $params['cid'];
        $config['params']['doc'] = 'INITIALOB';
        $config['params']['user'] = 'mail';

        $url = 'App\Http\Classes\modules\payroll\\' . 'obapplication';
        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='INITIALOB'");
        $both = false;
        if ($approversetup == '') {
            $approversetup = app($url)->approvers($params);
        } else {

            if (str_contains($approversetup, ' or ')) {
                $approversetup = explode(' or ', $approversetup);
                $both = true;
            } else {
                $approversetup = explode(',', $approversetup);
            }
            foreach ($approversetup as $appkey => $appsetup) {
                if ($appsetup == 'Supervisor') {
                    $approversetup[$appkey] = 'issupervisor';
                } else {
                    $approversetup[$appkey] = 'isapprover';
                }
            }
        }

        if ($status == 'A') {
            $msg = 'Initial Application approved.';
            $formailstatus = 'Approved';
        } elseif ($status == 'D') {
            $msg = 'Initial Application disapproved!';
            $formailstatus = 'Disapproved';
        }
        $line = 0;
        $empcode = '';
        $scheddate = null;
        $empname = '';
        $datalogs = $this->coreFunctions->opentable("select ob.line,cl.client as empcode,cl.clientname as empname,date(ob.scheddate) as scheddate
                        from obapplication as ob 
                        left join client as cl on cl.clientid=ob.empid 
                        where md5(ob.line)='" . $params['id'] . "'");

        if (!empty($datalogs)) {
            $line = $datalogs[0]->line;
            $empcode = $datalogs[0]->empcode;
            $scheddate = $datalogs[0]->scheddate;
            $empname = $datalogs[0]->empname;
        }

        $remarkslog = app($url)->modulename . ' - ' .  $empname . ' - ' . $scheddate;

        $approver = $this->coreFunctions->opentable("select cl.email as appcode, cl.clientname as appname,emp.empid from employee as emp left join client as cl on cl.clientid = emp.empid where md5(cl.email)='" . $params['uname'] . "'");
        if (!empty($approver)) {
            $isapprover = $this->coreFunctions->getfieldvalue("employee", "$isapp", "empid=?", [$approver[0]->empid]);
            $data = $this->coreFunctions->opentable("select appini.clientname as appname,appini.client as iniappcode, ob.line, ob.approvedby, ob.disapprovedby, ob.empid, emp.email,cl.email as username,  cl.clientname, ob.dateid, ob.dateid,
                        ob.initial_remarks2 as rem2,ob.initialstatus,ob.initialstatus2,appini2.clientname as appname2,
                        dayname(ob.scheddate) as dayname, date(ob.scheddate) as scheddate, ob.type, date(ob.dateid) as dateid, ob.rem, 
                        date_format(ob.dateid, '%Y-%m-%d %H:%i %s') as datetime,date_format(ob.dateid2, '%Y-%m-%d %H:%i %s') as datetime2,ob.location,
                        ob.initial_remarks as rem1,ob.disapproved_remarks2
                        from obapplication as ob left join employee as emp on emp.empid=ob.empid 
                        left join client as cl on cl.clientid=emp.empid
                        left join client as appini on appini.email = ob.initialapprovedby and appini.email <>''
                        left join client as appini2 on appini2.email = ob.initialapprovedby2 and appini2.email <>''

                        where md5(ob.line)='" . $params['id'] . "' and initialapp is not null ");

            if (!empty($data)) {
                $response = [];
                switch ($data[0]->initialstatus) {
                    case 'A':
                        $response = ['msg' => 'Initial Application Already approved ' . $approver[0]->appname, 'status' => 'F'];

                        break;
                    case 'D':
                        $response = ['msg' => 'Initial Application Already disapproved ' . $approver[0]->appname, 'status' => 'F'];
                        break;
                }
                if (empty($response)) {
                    $lastapp = false;
                    $stat = '';
                    foreach ($approversetup as $key => $value) {
                        if (count($approversetup) > 1) {

                            if ($key == 0) {
                                if ($both) {
                                    goto approved;
                                }
                                if ($value == $isapp && $isapprover) {

                                    $appdata = [
                                        'initialstatus2' => $status,
                                        'initialappdate2' => $this->othersClass->getCurrentTimeStamp(),
                                        'initialapprovedby2' => $approver[0]->appcode
                                    ];
                                    if ($status == 'D') {
                                        $lastapp = true;
                                    }
                                    break;
                                }
                            } else {
                                if ($value == $isapp && $isapprover) {
                                    if ((count($approversetup) - 1) == $key) goto approved;
                                }
                            }
                        } else {
                            if (count($approversetup) == 1) {
                                approved:
                                $stat = $status;
                                $lastapp = true;
                                $appdata = [
                                    'initialstatus' => $status,
                                    'initialappdate' => $this->othersClass->getCurrentTimeStamp(),
                                    'initialapprovedby' => $approver[0]->appcode
                                ];
                                break;
                            }
                        }
                    }
                    $appdata['empid'] = $data[0]->empid;
                    $appstatus = ['status' => true];
                    $config['params']['doc'] = 'INITIALOB';
                    $config['params']['user'] = $approver[0]->appcode;
                    $config['params']['adminid'] = $approver[0]->empid;
                    $config['params']['companyid'] = $params['companyid'];

                    $pendingdata = $this->coreFunctions->opentable("select * from pendingapp where line=" . $data[0]->line . " and doc='INITIALOB'");

                    $this->coreFunctions->execqry("delete from pendingapp where doc='INITIALOB' and line=" . $data[0]->line, 'delete');
                    if ($stat == "") {
                        if (!$lastapp) {
                            $appstatus = $this->othersClass->insertUpdatePendingapp(0, $data[0]->line, 'INITIALOB', $appdata, $url, $config, 0, true);
                        }
                    }

                    if (!$appstatus['status']) {
                        if (!empty($pendingdata)) {
                            foreach ($pendingdata as $pd) {
                                $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid,approver) values(?, ?, ?, ?, ?)", 'insert', [$pd->trno, $pd->line, $pd->doc, $pd->clientid, $pd->approver]);
                            }
                        }
                        return ['status' => false, 'msg' => $appstatus['msg'], 'data' => []];
                    } else {
                        $appdata['editdate'] = $this->othersClass->getCurrentTimeStamp();
                        $appdata['editby'] = $approver[0]->appname;
                        $update = $this->coreFunctions->sbcupdate('obapplication', $appdata, ['line' => $data[0]->line, 'empid' => $data[0]->empid]);
                        $obstatus = true;
                        if ($update) {
                            $response = ['msg' => $msg, 'status' => 'A'];
                        } else {
                            $response = ['msg' => 'Failed to Updating Record initial OB.', 'status' => 'F'];
                            $obstatus = false;
                        }
                        $query = "select cl.email as username, emp.email,app.approver from pendingapp as app 
                                  left join employee as emp on emp.empid = app.clientid
                                  left join client as cl on cl.clientid = emp.empid
                                  where app.doc = 'INITIALOB' and app.line =  " . $data[0]->line . "";


                        if ($obstatus) {
                            $response = ['msg' => $msg, 'status' => $status];
                            $info = [];
                            $info['subject'] = 'OB APPLICATION INITIAL';
                            $info['title'] = 'OB APPLICATION INITIAL';
                            $info['clientname'] = $data[0]->clientname;
                            $info['line'] = $data[0]->line;
                            $info['scheddate'] = $data[0]->scheddate . " (" . $data[0]->dayname . ")";
                            $info['dateid'] = $data[0]->dateid;
                            $info['rem'] = $data[0]->rem;
                            $info['reason1'] = $data[0]->rem1;
                            $info['reason2'] = $data[0]->rem2;
                            $info['datetime'] = $data[0]->datetime;
                            $info['datetime2'] = $data[0]->datetime2;
                            $info['type'] = $data[0]->type;
                            $info['location'] = $data[0]->location;
                            $info['appstatus'] = $formailstatus;
                            $info['companyid'] = $params['companyid'];
                            $info['muduletype'] = 'OB_INITIAL';

                            $appobdata = $this->coreFunctions->opentable($query);
                            $ldata = $appobdata;
                            if ($lastapp) {
                                $ldata = $data;
                            }


                            foreach ($ldata as $key => $value) {
                                if (isset($value->email)) {
                                    $info['email'] = $value->email;
                                    $info['appname'] = $approver[0]->appname;
                                    $info['approver'] = $value->username; //lastapp no need ng approver and isapp leave it empty
                                    $info['isapp'] = isset($value->approver) ? $value->approver : '';
                                    if ($lastapp) {
                                        $info['title'] = 'OB APPLICATION INITIAL RESULT';
                                        $info['approvedstatus'] = 'Application ' . $formailstatus;
                                        $info['appname2'] = isset($value->appname2) ? $value->appname2 : '';
                                    }

                                    $mailresult = $this->weblink($info, $config);
                                    if (!$mailresult['status']) {
                                        $obstatus = false;
                                    }
                                }
                            }
                            if (!$obstatus) {
                                $response = ['msg' => 'Sending email failed', 'status' => 'F'];
                                $this->logger->sbcmasterlog2($line, $config, " MAIL - DATE: " . $scheddate . "Sending email failed", app($url)->tablelogs);
                            } else {
                                $response = ['msg' => $msg, 'status' => $status];
                                $this->logger->sbcmasterlog2($line, $config, " MAIL - DATE: " . $scheddate . "" . $msg, app($url)->tablelogs);
                            }
                        } else {
                            if (!$obstatus) {
                                if (!empty($pendingdata)) {
                                    foreach ($pendingdata as $pd) {
                                        $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid,approver) values(?, ?, ?, ?,?)", 'insert', [$pd->trno, $pd->line, $pd->doc, $pd->clientid, $pd->approver]);
                                    }
                                    $response = ['msg' => 'Failed to approved Updating Record Error.', 'status' => 'F'];
                                    $this->logger->sbcmasterlog2($line, $config, " MAIL - DATE: " . $scheddate . " Failed to approved Updating Record Error", app($url)->tablelogs);
                                }
                            }
                        }
                    }
                }
            } else {
                $response = ['msg' => 'Application doesnt exist or has been deleted!', 'status' => 'F'];
                $this->logger->sbcmasterlog2($line, $config, " MAIL - DATE: " . $scheddate . " Application doesnt exist or has been deleted!", app($url)->tablelogs);
            }
        } else {
            $response = ['msg' => 'Invalid approver.', 'status' => 'F'];
            $this->logger->sbcmasterlog2($line, $config, " MAIL - DATE: " . $scheddate . " Invalid approver", app($url)->tablelogs);
        }
        $response['remarks'] = $remarkslog;
        return view('emails.approved', compact('response'));
    }
    private function approvedOT($params)
    {
        $status = $params['status'];
        $msg = '';
        $url = 'App\Http\Classes\modules\payroll\\' . 'otapplicationadv';
        $params['companyid'] = $params['cid'];
        $isapp = $params['isapp'];

        $line = 0;
        $scheddate = null;
        $empcode = '';
        $empname = '';

        $config['params']['doc'] = 'OT';
        $config['params']['user'] = 'mail';

        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='OT'");
        $both = false;
        if ($approversetup == '') {
            $approversetup = app($url)->approvers($params);
        } else {
            // $approversetup = explode(',', $approversetup);

            if (str_contains($approversetup, ' or ')) {
                $approversetup = explode(' or ', $approversetup);
                $both = true;
            } else {
                $approversetup = explode(',', $approversetup);
            }
            foreach ($approversetup as $appkey => $appsetup) {
                if ($appsetup == 'Supervisor') {
                    $approversetup[$appkey] = 'issupervisor';
                } else {
                    $approversetup[$appkey] = 'isapprover';
                }
            }
        }
        if ($status == 2) {
            $msg = 'Application approved!';
            $formailstatus = 'Approved';
        } elseif ($status == 3) {
            $msg = 'Application disapproved!';
            $formailstatus = 'Disapproved';
        }

        $datalogs = $this->coreFunctions->opentable("select ot.line,cl.client as empcode,cl.clientname as empname,date(ot.scheddate) as scheddate
                        from otapplication as ot 
                        left join client as cl on cl.clientid=ot.empid 
                        where md5(ot.line)='" . $params['id'] . "'");

        if (!empty($datalogs)) {
            $line = $datalogs[0]->line;
            $empcode = $datalogs[0]->empcode;
            $scheddate = $datalogs[0]->scheddate;
            $empname = $datalogs[0]->empname;
        }
        $remarkslog = app($url)->modulename . ' - ' .  $empname . ' - ' . $scheddate;
        $approver = $this->coreFunctions->opentable("select cl.email as appcode,cl.clientname as appname,emp.empid 
        from employee as emp 
        left join client as cl on cl.clientid = emp.empid where md5(cl.email)='" . $params['uname'] . "'");
        if (!empty($approver)) {

            $isapprover = $this->coreFunctions->getfieldvalue("employee", "$isapp", "empid=?", [$approver[0]->empid]);
            $data = $this->coreFunctions->opentable("select ot.empid,ot.line, ot.otstatus,ot.otstatus2, ot.approvedby, ot.disapprovedby, ot.empid, emp.email,cl.email as username, cl.clientname, ot.dateid,
                        ifnull(app.clientname,disapp.clientname) as appname,ifnull(app1.clientname,disapp2.clientname) as appname2,
                        ot.othrs,ot.rem,date_format(ot.ottimein, '%Y-%m-%d %h:%i %p') as ottimein,
                        date_format(ot.ottimeout, '%Y-%m-%d %h:%i %p') as ottimeout,
                        date(ot.scheddate) as scheddate,dayname(ot.scheddate) as dayname,ot.ndiffhrs,ot.othrsextra,
                        ot.ndiffothrs,ot.daytype,ot.createdate,ot.remarks as remlast,emp.approver1,emp.approver2,ot.disapproved_remarks2 as rem2,
                        ot.apothrs,ot.apothrsextra,ot.apndiffothrs
                        from otapplication as ot 
                        left join employee as emp on emp.empid=ot.empid
                        left join client as cl on cl.clientid=emp.empid
   
                        left join client as app on app.email=ot.approvedby and app.email <> ''
                        left join client as disapp ON disapp.email=ot.disapprovedby and disapp.email <> ''
                        left join client as app1 on app1.email=ot.approvedby2 and app1.email <> ''
                        left join client as disapp2 on disapp2.email=ot.disapprovedby2 and disapp2.email <> ''
                        where md5(ot.line) ='" . $params['id'] . "'");

            if (!empty($data)) {
                $response = [];
                switch ($data[0]->otstatus) {
                    case 2:
                        $response = ['msg' => 'Already approved by ' . $data[0]->appname . '.', 'status' => 'F'];
                        break;
                    case 3:
                        $response = ['msg' => 'Already disapproved by ' . $data[0]->appname . '.', 'status' => 'F'];
                        break;
                    case 1:

                        $lstatus = 0;
                        $lastapp = false;

                        foreach ($approversetup as $key => $value) {
                            if (count($approversetup) > 1) {
                                if ($key == 0) {
                                    if ($value == $isapp && $isapprover) {

                                        switch ($data[0]->otstatus2) {
                                            case 2:
                                                $response = ['msg' => 'Already approved by ' . $data[0]->appname2 . '.', 'status' => 'F'];
                                                break;
                                            case 3:
                                                $response = ['msg' => 'Already disapproved by ' . $data[0]->appname2 . '.', 'status' => 'F'];
                                                break;
                                        }
                                        if ($both) {
                                            goto approved;
                                        }
                                        $appdata = ['otstatus2' => $status];
                                        if ($params['companyid'] == 53) { //camera
                                            $appdata['apothrs'] = $data[0]->apothrs;
                                            $appdata['apothrsextra'] = $data[0]->apothrsextra;
                                            $appdata['apndiffothrs'] = $data[0]->apndiffothrs;
                                        }

                                        if ($status == 2) {
                                            $appdata['approvedby2'] = $approver[0]->appcode;
                                            $appdata['approvedate2'] = $this->othersClass->getCurrentTimeStamp();
                                        } else if ($status == 3) {
                                            $appdata['disapprovedby2'] = $approver[0]->appcode;
                                            $appdata['disapprovedate2'] = $this->othersClass->getCurrentTimeStamp();
                                            // $appdata['otstatus'] = $status; //pag na disapproved update na din ito last status
                                            $lastapp = true;
                                        }
                                        break; // loop
                                    }
                                } else {
                                    if ($value == $isapp && $isapprover) {
                                        if ((count($approversetup) - 1) == $key) {
                                            goto approved;
                                        }
                                    }
                                }
                            } else {
                                if (count($approversetup) == 1) {
                                    approved:
                                    $lastapp = true;
                                    $lstatus = $status;
                                    $appdata = ['otstatus' => $status];
                                    if ($status == 2) {
                                        $appdata['apothrs'] = $data[0]->othrs;
                                        $appdata['apothrsextra'] = $data[0]->othrsextra;
                                        $appdata['apndiffothrs'] = $data[0]->ndiffothrs;

                                        $appdata['approvedby'] = $approver[0]->appcode;
                                        $appdata['approvedate'] = $this->othersClass->getCurrentTimeStamp();
                                    } else if ($status == 3) {
                                        $appdata['disapprovedby'] = $approver[0]->appcode;
                                        $appdata['disapprovedate'] = $this->othersClass->getCurrentTimeStamp();
                                    }
                                    break; // break loop
                                }
                            }
                        }

                        if (empty($response)) {

                            $data2['empid'] = $data[0]->empid;

                            $config['params']['doc'] = 'OTAPPLICATIONADV';
                            $config['params']['user'] = $approver[0]->appcode;

                            $config['params']['adminid'] = $approver[0]->empid;
                            $config['params']['companyid'] = $params['companyid'];
                            $pendingdata = $this->coreFunctions->opentable("select * from pendingapp where line=" . $data[0]->line . " and doc='OT'");

                            $this->coreFunctions->execqry("delete from pendingapp where doc='OT' and line=" . $data[0]->line, 'delete');
                            $result = ['status' => true];
                            $res_status = true;
                            if (!$lastapp) {
                                switch ($params['companyid']) {
                                    case 53: //camera
                                    case 51: //ulitc
                                        $stats = true;
                                        break;
                                    default:
                                        $stats = false;
                                        break;
                                }
                                $result = $this->othersClass->insertUpdatePendingapp(0, $data[0]->line, 'OT', $data2, $url, $config, 0,  $stats);
                                if (!$result['status']) {
                                    if (!empty($pendingdata)) {
                                        foreach ($pendingdata as $pd) {
                                            $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid,approver) values(?, ?, ?, ?, ?)", 'insert', [$pd->trno, $pd->line, $pd->doc, $pd->clientid, $pd->approver]);
                                        }
                                    }
                                    $msg = $result['msg'];
                                    $res_status = $result['status'];
                                }
                            }

                            if ($res_status) {
                                $appdata['editdate'] = $this->othersClass->getCurrentTimeStamp();
                                $appdata['editby'] = $approver[0]->appcode;
                                $result = $this->coreFunctions->sbcupdate('otapplication', $appdata, ['line' => $data[0]->line]);
                                $status = $status == 2 ? 'A' : 'D';
                            }
                            $response = ['msg' => $msg, 'status' => $status];

                            $info = [];

                            $info['subject'] = 'OT APPLICATION RESULT';
                            $info['clientname'] = $data[0]->clientname;
                            $info['line'] = $data[0]->line;
                            $info['scheddate'] = $data[0]->scheddate . " (" . $data[0]->dayname . ")";
                            $info['ottimein'] = $data[0]->ottimein;
                            $info['ottimeout'] = $data[0]->ottimeout;
                            $info['rem'] = $data[0]->rem;
                            $info['reason1'] = $data[0]->rem2;
                            $info['remlast'] = $data[0]->remlast;

                            // break;
                            $info['othrs'] = $data[0]->othrs;
                            $info['othrsextra'] = $data[0]->othrsextra;
                            // $info['ndiffhrs'] = $data[0]->ndiffhrs;
                            $info['ndiffothrs'] = $data[0]->ndiffothrs;
                            $info['daytype'] = $data[0]->daytype;
                            $info['createdate'] = $data[0]->createdate;
                            // $info['appstatus'] = $formailstatus;
                            $info['muduletype'] = 'OT';

                            $info['companyid'] = $params['companyid'];

                            if ($res_status) {
                                $query = "select cl.clientname as appname,cl.email as username,app.approver,emp.email 
                                    from otapplication as ot 
									left join pendingapp as app on app.line = ot.line
									left join employee as emp on emp.empid = app.clientid
									left join client as cl on cl.clientid = app.clientid
                                    where app.doc = 'OT' and md5(app.line) ='" . $params['id'] . "'";

                                $approver_data = $this->coreFunctions->opentable($query);
                                $ldata = $approver_data; // send last approver
                                if ($lastapp) {
                                    $ldata = $data; //employee
                                }
                                $sendstatus = true;

                                foreach ($ldata as $key => $value) {
                                    if (!empty($value->email)) {
                                        $info['title'] = 'OT APPLICATION';
                                        $info['email'] = $value->email;
                                        $info['approver'] = $value->username;

                                        $info['isapp'] = isset($value->approver) ? $value->approver : '';
                                        $info['appstatus'] = $formailstatus;
                                        $info['appname'] = $approver[0]->appname;
                                        if ($lastapp) {
                                            $info['title'] = 'OT APPLICATION RESULT';
                                            $info['approvedstatus'] = 'Application ' . $formailstatus;
                                            $info['appname'] =  $approver[0]->appname;
                                            if ($lstatus != 0) {
                                                if ($value->appname2 == null) {
                                                    $info['appname2'] = '';
                                                } else {
                                                    $info['appname2'] = $value->appname2;
                                                }
                                            }
                                        }
                                        $mailresult = $this->weblink($info, $config);
                                        if (!$mailresult['status']) {
                                            $msg = $mailresult['msg'];
                                            $sendstatus = false;
                                        }
                                    }
                                }
                                if (!$sendstatus) {
                                    $response = ['msg' => $msg, 'status' => 'F'];
                                    $this->logger->sbcmasterlog2($line, $config, " MAIL - DATE: " . $scheddate . " " . $msg, app($url)->tablelogs);
                                } else {
                                    $this->logger->sbcmasterlog2($line, $config, " MAIL - DATE: " . $scheddate . " " . $msg, app($url)->tablelogs);
                                }
                            }
                        }


                        break;
                }
            } else {
                $response = ['msg' => 'Application doesnt exist or has been deleted!', 'status' => 'F'];
                $this->logger->sbcmasterlog2($line, $config, " MAIL - DATE: " . $scheddate . " Application doesnt exist or has been deleted!", app($url)->tablelogs);
            }
        } else {
            $response = ['msg' => 'Invalid approver.', 'status' => 'F'];
            $this->logger->sbcmasterlog2($line, $config, " MAIL - DATE: " . $scheddate . " Invalid approver", app($url)->tablelogs);
        }
        $response['remarks'] = $remarkslog;
        return view('emails.approved', compact('response'));
    }
    private function approvedLoan($params)
    {
        $status = $params['status'];
        $msg = '';
        $formailstatus = '';
        $params['companyid'] = $params['cid'];
        $isapp = $params['isapp'];
        $url = 'App\Http\Classes\modules\payroll\\' . 'loanapplicationportal';
        $trno = 0;
        $scheddate = null;
        $empcode = '';
        $empname = '';

        $config['params']['doc'] = 'LOAN';
        $config['params']['user'] = 'mail';
        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='LOAN'");
        $both = false;
        if ($approversetup == '') {
            $approversetup = app($url)->approvers($params);
        } else {
            if (str_contains($approversetup, ' or ')) {
                $approversetup = explode(' or ', $approversetup);
                $both = true;
            } else {
                $approversetup = explode(',', $approversetup);
            }
            foreach ($approversetup as $appkey => $appsetup) {
                if ($appsetup == 'Supervisor') {
                    $approversetup[$appkey] = 'issupervisor';
                } else {
                    $approversetup[$appkey] = 'isapprover';
                }
            }
        }
        if ($status == 'A') {
            $msg = 'Application approved.';
            $formailstatus = 'Approved';
        } elseif ($status == 'D') {
            $msg = 'Application disapproved!';
            $formailstatus = 'Disapproved';
        }
        $datalogs = $this->coreFunctions->opentable("select loan.trno,cl.client as empcode,cl.clientname as empname,date(loan.effdate) as scheddate
                        from loanapplication as loan 
                        left join client as cl on cl.clientid=loan.empid 
                        where md5(loan.trno)='" . $params['id'] . "'");

        if (!empty($datalogs)) {
            $trno = $datalogs[0]->trno;
            $empcode = $datalogs[0]->empcode;
            $scheddate = $datalogs[0]->scheddate;
            $empname = $datalogs[0]->empname;
        }
        $remarkslog = app($url)->modulename . ' - ' .  $empname . ' - ' . $scheddate;
        $approver = $this->coreFunctions->opentable("select cl.email as appcode, cl.clientname as appname,emp.empid
        from employee as emp 
        left join client as cl on cl.clientid = emp.empid 
        where md5(cl.email)='" . $params['uname'] . "'");
        if (!empty($approver)) {
            $isapprover = $this->coreFunctions->getfieldvalue("employee", "$isapp", "empid=?", [$approver[0]->empid]);
            $data = $this->coreFunctions->opentable("select s.status, s.trno,s.dateid, s.empid, s.remarks, pac.code as acno, format(s.amt,2) as amt, s.paymode,
                         w1,w2,w3,w4,w5,w13, format(s.amortization,2) as amortization,e.email,s.disapproved_remarks as remlast,s.disapproved_remarks2 as rem2,
                         date(s.effdate) as effdate,s.status2,
                         cl.clientname,cl.client as username, app2.clientname as appname2,app.clientname as appname,
                         pac.codename as acnoname,s.balance,
                         case 
                         when s.w1 = 1 then 'Week 1'
                         when s.w2 = 1 then 'Week 2' 
                         when s.w3 = 1 then 'Week 3'
                         when s.w4 = 1 then 'Week 4'
                         when s.w5 = 1 then 'Week 5'
                         else ''
                         end as week
                         from loanapplication as s
                         left join employee as e on s.empid = e.empid
                         left join client as cl on cl.clientid=e.empid
                         left join client as app on app.email=s.approvedby_disapprovedby and app.email <>''
                         left join client as app2 on app2.email=s.approvedby_disapprovedby2 and app2.email <>''
                         left join paccount as pac on pac.line = s.acnoid
                         where md5(s.trno) ='" . $params['id'] . "'");

            if (!empty($data)) {
                $response = [];
                switch ($data[0]->status) {
                    case 'A':
                        $response = ['msg' => 'Already approved by ' . $data[0]->appname . '.', 'status' => 'F'];
                        break;
                    case 'D':
                        $response = ['msg' => 'Already disapproved by ' . $data[0]->appname . '.', 'status' => 'F'];
                        break;
                    case 'E':
                        $lstatus = "";
                        $lastapp = false;
                        foreach ($approversetup as $key => $value) {
                            if (count($approversetup) > 1) {
                                if ($key == 0) {
                                    if ($value == $isapp && $isapprover) {
                                        switch ($data[0]->status2) {
                                            case 'A':
                                                $response = ['msg' => 'Already approved by ' . $data[0]->appname2 . '.', 'status' => 'F'];
                                                break;
                                            case 'D':
                                                $response = ['msg' => 'Already disapproved by ' . $data[0]->appname2 . '.', 'status' => 'F'];
                                                break;
                                        }
                                        if ($both) {
                                            goto approved;
                                        }
                                        $appdata = ['status2' => $status];
                                        $appdata['approvedby_disapprovedby2'] = $approver[0]->appcode;
                                        $appdata['date_approved_disapproved2'] = $this->othersClass->getCurrentTimeStamp();
                                        if ($status == 'D') {
                                            $lastapp = true;
                                        }
                                        break; // loop
                                    }
                                } else {
                                    if ($value == $isapp && $isapprover) {
                                        if ((count($approversetup) - 1) == $key) {
                                            goto approved;
                                        }
                                    }
                                }
                            } else {
                                if (count($approversetup) == 1) {
                                    approved:
                                    $lastapp = true;
                                    $lstatus = $status;
                                    $appdata = ['status' => $status];
                                    $appdata['approvedby_disapprovedby'] = $approver[0]->appcode;
                                    $appdata['date_approved_disapproved'] = $this->othersClass->getCurrentTimeStamp();
                                    break; // break loop
                                }
                            }
                        }
                        $data2 = ['empid' => $data[0]->empid];
                        $config['params']['doc'] = 'LOANAPPLICATIONPORTAL';
                        $config['params']['user'] = $approver[0]->appcode;
                        $config['params']['adminid'] = $approver[0]->empid;
                        $config['params']['companyid'] = $params['companyid'];
                        $pendingdata = $this->coreFunctions->opentable("select * from pendingapp where trno=" . $data[0]->trno . " and doc='LOAN'");

                        if (empty($response)) {
                            $this->coreFunctions->execqry("delete from pendingapp where doc='LOAN' and trno=" . $data[0]->trno, 'delete');
                            switch ($params['companyid']) {
                                case 53: //camera
                                case 51: //ulitc
                                    $stat = true;
                                    break;
                                default:
                                    $stat = false;
                                    break;
                            }
                            $appstatus = ['status' => true];
                            $res_status = true;

                            if (!$lastapp) {
                                $appstatus = $this->othersClass->insertUpdatePendingapp($data[0]->trno, 0, 'LOAN', $data2, $url, $config, 0, $stat);
                            }
                            if (!$appstatus['status']) {
                                if (!empty($pendingdata)) {
                                    foreach ($pendingdata as $pd) {
                                        $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid,approver) values(?, ?, ?, ?, ?)", 'insert', [$pd->trno, $pd->line, $pd->doc, $pd->clientid, $pd->approver]);
                                    }
                                }
                                $msg = $appstatus['msg'];
                                $res_status = $appstatus['status'];
                                $response = ['msg' => $msg, 'status' => 'F'];
                                $this->logger->sbcmasterlog2($trno, $config, " MAIL - DATE: " . $scheddate . " " . $msg, app($url)->tablelogs);
                            } else {

                                if ($res_status) {
                                    $appdata['editdate'] = $this->othersClass->getCurrentTimeStamp();
                                    $appdata['editby'] = $approver[0]->appcode;
                                    $result = $this->coreFunctions->sbcupdate('loanapplication', $appdata, ['trno' => $data[0]->trno]);
                                    if ($result) {
                                        $info = [];
                                        $info['subject'] = 'LOAN APPLICATION RESULT';
                                        $info['title'] = 'LOAN APPLICATION';
                                        $info['clientname'] = $data[0]->clientname;
                                        $info['line'] = $data[0]->trno;
                                        $info['effdate'] = $data[0]->effdate;
                                        $info['acnoname'] = $data[0]->acnoname;
                                        $info['amount'] = $data[0]->amt;
                                        $info['amortization'] = $data[0]->amortization;

                                        $info['reason1'] = $data[0]->rem2;
                                        $info['remlast'] = $data[0]->remlast;
                                        $info['remarks'] = $data[0]->remarks;
                                        $info['balance'] = $data[0]->balance;
                                        $info['week'] = $data[0]->week;

                                        $info['email'] = $data[0]->email;
                                        $info['appstatus'] = 'Application ' . $formailstatus;
                                        $info['companyid'] = $params['companyid'];
                                        $info['muduletype'] = 'LOAN';

                                        $query = "select cl.email as username ,emp.email,app.doc,app.approver from pendingapp as app
		                                 left join client as cl on cl.clientid = app.clientid
		                                 left join employee as emp on emp.empid = app.clientid 
		                                 where doc = 'LOAN' and trno = " . $data[0]->trno . " ";

                                        $app_data = $this->coreFunctions->opentable($query);
                                        $l_data = $app_data;
                                        if ($lastapp) {
                                            $l_data = $data;
                                        }

                                        if (!empty($l_data)) {
                                            foreach ($l_data as $key => $value) {
                                                if (!empty($value->email)) {
                                                    $info['email'] = $value->email;
                                                    $info['approver'] = $value->username;

                                                    $info['isapp'] = isset($value->approver) ? $value->approver : "";
                                                    $info['appname'] = $approver[0]->appname;
                                                    if ($lastapp) {
                                                        $info['title'] = 'LOAN APPLICATION RESULT';
                                                        $info['approvedstatus'] = 'Application ' . $formailstatus;
                                                        if ($lstatus != "") {
                                                            $info['appname2'] = isset($value->appname2) && $value->appname2 != null ? $value->appname2 : '';
                                                        }
                                                    }
                                                    $mailresult = $this->weblink($info, $config);
                                                    if (!$mailresult['status']) {
                                                        $msg = $mailresult['msg'];
                                                        $res_status = false;
                                                        $response = ['msg' => 'Failed to send email', 'status' => 'F'];
                                                        $this->logger->sbcmasterlog2($trno, $config, " MAIL - DATE: " . $scheddate . "'Failed to send email", app($url)->tablelogs);
                                                    }
                                                }
                                            }
                                        }
                                        $res_status = $res_status ? 'A' : 'F';
                                        $response = ['msg' => $msg, 'status' => $res_status];
                                    } else {
                                        if (!empty($pendingdata)) {
                                            foreach ($pendingdata as $pd) {
                                                $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid,approver) values(?, ?, ?, ?, ?)", 'insert', [$pd->trno, $pd->line, $pd->doc, $pd->clientid, $pd->approver]);
                                            }
                                        }
                                        $response = ['msg' => 'Failed to approved Updating Record Error.', 'status' => 'F'];
                                        $this->logger->sbcmasterlog2($trno, $config, " MAIL - DATE: " . $scheddate . " Failed to approved Updating Record Error. ", app($url)->tablelogs);
                                    }
                                }
                            }
                        }

                        break;
                }
            } else {
                $response = ['msg' => 'Application doesnt exist or has been deleted!', 'status' => 'F'];
                $this->logger->sbcmasterlog2($trno, $config, " MAIL - DATE: " . $scheddate . "Application doesnt exist or has been deleted! ", app($url)->tablelogs);
            }
        } else {
            $response = ['msg' => 'Invalid approver.', 'status' => 'F'];
            $this->logger->sbcmasterlog2($trno, $config, " MAIL - DATE: " . $scheddate . "Invalid approver.", app($url)->tablelogs);
        }
        $response['remarks'] = $remarkslog;
        return view('emails.approved', compact('response'));
    }
    private function approvedSched($params)
    {
        $status = $params['status'];
        $msg = '';
        $formailstatus = '';
        $isapp = $params['isapp'];

        $url = 'App\Http\Classes\modules\payroll\\' . 'changeshiftapplication';
        $params['companyid'] = $params['cid'];

        $line = 0;
        $scheddate = null;
        $empcode = '';
        $empname = '';

        $config['params']['doc'] = 'CHANGESHIFT';
        $config['params']['user'] = 'mail';

        $isapprover = $this->coreFunctions->datareader("select emp.$isapp as value from employee as emp 
        left join client on client.clientid = emp.empid where md5(client.email)='" . $params['uname'] . "'");

        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='CHANGESHIFT'");
        $both = false;
        if ($approversetup == '') {
            $approversetup = app($url)->approvers($params);
        } else {
            if (str_contains($approversetup, ' or ')) {
                $approversetup = explode(' or ', $approversetup);
                $both = true;
            } else {
                $approversetup = explode(',', $approversetup);
            }
            foreach ($approversetup as $appkey => $appsetup) {
                if ($appsetup == 'Supervisor') {
                    $approversetup[$appkey] = 'issupervisor';
                } else {
                    $approversetup[$appkey] = 'isapprover';
                }
            }
        }
        if ($status == 1) {
            $msg = 'Application approved.';
            $formailstatus = 'Approved';
        } elseif ($status == 2) {
            $msg = 'Application disapproved!';
            $formailstatus = 'Disapproved';
        }

        $datalogs = $this->coreFunctions->opentable("select csapp.line,cl.client as empcode,cl.clientname as empname,date(csapp.dateid) as scheddate
                        from changeshiftapp as csapp 
                        left join client as cl on cl.clientid=csapp.empid 
                        where md5(csapp.line) = '" . $params['id'] . "'");

        if (!empty($datalogs)) {
            $line = $datalogs[0]->line;
            $empcode = $datalogs[0]->empcode;
            $scheddate = $datalogs[0]->scheddate;
            $empname = $datalogs[0]->empname;
        }
        $remarkslog = app($url)->modulename . ' - ' .  $empname . ' - ' . $scheddate;
        $approver = $this->coreFunctions->opentable("select cl.email as appcode, cl.clientname as appname,cl.clientid from employee as emp left join client as cl on cl.clientid = emp.empid 
        where md5(cl.email)='" . $params['uname'] . "'");
        if (!empty($approver)) {

            $data = $this->coreFunctions->opentable("select csapp.line, cl.client,emp.email,cl.email as username,
                      cl.clientname,date(csapp.dateid) as dateid,csapp.schedin as schedin,csapp.empid,
                      csapp.schedout as schedout,csapp.rem,csapp.createdate,csapp.submitdate,csapp.daytype,
                      csapp.status,csapp.status2,ifnull(app.clientname,app2.clientname) as appname, ifnull(app3.clientname,app4.clientname) as appname2, csapp.approvedby, csapp.disapprovedby,csapp.orgdaytype,
                      date_format(tm.schedin, '%Y-%m-%d %h:%i %p') as orgschedin,
                      date_format(tm.schedout, '%Y-%m-%d %h:%i %p') as orgschedout,csapp.disapproved_remarks as rem1,
                      csapp.disapproved_remarks2 as rem2
                      from changeshiftapp as csapp
                      left join timecard as tm on tm.empid = csapp.empid and tm.dateid = date(csapp.dateid)
                      left join employee as emp on emp.empid = csapp.empid
                      left join client as cl on cl.clientid = emp.empid
                      left join client as app on app.email= csapp.approvedby and app.email <> ''
                      left join client as app2 on app2.email= csapp.disapprovedby and app2.email <> ''
                      left join client as app3 on app3.email= csapp.approvedby2 and app3.email <> ''
                      left join client as app4 on app4.email= csapp.disapprovedby2 and app4.email <> ''
                      where md5(csapp.line) ='" . $params['id'] . "'");
            $response = [];
            if (!empty($data)) {

                switch ($data[0]->status) {
                    case 1:
                        $response = ['msg' => 'Already approved by ' . $data[0]->appname . '.', 'status' => 'F'];
                        break;
                    case 2:
                        $response = ['msg' => 'Already disapproved by ' . $data[0]->appname2 . '.', 'status' => 'F'];
                        break;
                    case 0:

                        $stat = 0;
                        $lastapp = false;
                        foreach ($approversetup as $key => $value) {
                            if (count($approversetup) > 1) {
                                if ($key == 0) {
                                    if ($value == $isapp && $isapprover) {

                                        switch ($data[0]->status2) {
                                            case 1:
                                                $response = ['msg' => 'Already approved by ' . $data[0]->appname2 . '.', 'status' => 'F'];
                                                break;
                                            case 2:
                                                $response = ['msg' => 'Already disapproved by ' . $data[0]->appname2 . '.', 'status' => 'F'];
                                                break;
                                        }
                                        if ($both) {
                                            goto approved;
                                        }
                                        $data2 = [
                                            'status2' => $status,
                                            'disapproved_remarks2' => $data[0]->rem2
                                        ];
                                        if ($status == 1) {
                                            $data2['approvedby2'] = $approver[0]->appcode;
                                            $data2['approveddate2'] = $this->othersClass->getCurrentTimeStamp();
                                        } else {
                                            $data2['disapprovedby2'] =
                                                $data2['disapproveddate2'] = $this->othersClass->getCurrentTimeStamp();
                                            $lastapp = true;
                                        }

                                        break;
                                    }
                                } else {
                                    if ($value == $isapp && $isapprover) {
                                        if ((count($approversetup) - 1) == $key) goto approved;
                                    }
                                }
                            } else {
                                if (count($approversetup) == 1) {
                                    approved:
                                    $lastapp = true;
                                    $stat = $status;

                                    switch ($data[0]->status) {
                                        case 1:
                                            $response = ['msg' => 'Already approved by ' . $data[0]->appname . '.', 'status' => 'F'];
                                            break;
                                        case 2:
                                            $response = ['msg' => 'Already disapproved by ' . $data[0]->appname . '.', 'status' => 'F'];
                                            break;
                                    }
                                    $data2 = [
                                        'status' => $status,
                                        'disapproved_remarks' => ''
                                    ];

                                    if ($status == 1) { // approved
                                        $data2['approvedby'] = $approver[0]->appcode;
                                        $data2['approveddate'] = $this->othersClass->getCurrentTimeStamp();
                                    } else { // disapproved
                                        $data2['disapprovedby'] = $approver[0]->appcode;
                                        $data2['disapproveddate'] = $this->othersClass->getCurrentTimeStamp();
                                    }
                                    break;
                                }
                            }
                        }
                        $pendingdata = $this->coreFunctions->opentable("select * from pendingapp where trno=" . $data[0]->line . " and doc='CHANGESHIFT'");
                        $data2['empid'] =  $data[0]->empid;
                        $config['params']['doc'] = 'CHANGESHIFTAPPLICATION';
                        $config['params']['user'] = $approver[0]->appcode;
                        $config['params']['adminid'] = $approver[0]->clientid;
                        $config['params']['companyid'] = $params['companyid'];
                        if (empty($response)) {
                            $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                            $data2['editby'] =  $approver[0]->appcode;


                            $this->coreFunctions->execqry("delete from pendingapp where doc='CHANGESHIFT' and line=" . $data[0]->line, 'delete');

                            switch ($params['companyid']) {
                                case 53:
                                    $stats = true;
                                    break;
                                default:
                                    $stats = false;
                                    break;
                            }
                            $appstatus = ['status' => true];
                            if (!$lastapp) {
                                $appstatus = $this->othersClass->insertUpdatePendingapp(0, $data[0]->line, 'CHANGESHIFT', $data2, $url, $config, 0, $stat);
                            }

                            if (!$appstatus['status']) {
                                if (!empty($pendingdata)) {
                                    foreach ($pendingdata as $pd) {
                                        $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid,approver) values(?, ?, ?, ?, ?)", 'insert', [$pd->trno, $pd->line, $pd->doc, $pd->clientid, $pd->approver]);
                                    }
                                }
                                $msg = $appstatus['msg'];
                                $response = ['msg' => $msg, 'status' => 'F'];
                                $this->logger->sbcmasterlog2($line, $config, " MAIL - DATE: " . $scheddate . " Failed to approved Updating Record Error. ", app($url)->tablelogs);
                            } else {

                                $result = $this->coreFunctions->sbcupdate('changeshiftapp', $data2, ['line' => $data[0]->line]);
                                if ($result) {
                                    $status = $status == 1 ? 'A' : 'D';
                                    $response = ['msg' => $msg, 'status' => $status];
                                    $query = "select app.line, app.doc,app.clientid,emp.email,cl.email as username,cs.status,cl.clientname as appname,app.approver
                        from changeshiftapp as cs 
                        left join pendingapp as app on app.line = cs.line 
                        left join employee as emp on emp.empid = app.clientid
                        left join client as cl on cl.clientid = emp.empid
                        left join client on client.clientid = emp.empid
                        where app.doc = 'CHANGESHIFT' and md5(app.line) ='" . $params['id'] . "'";

                                    $aprover_data = $this->coreFunctions->opentable($query);

                                    $ldata  = $aprover_data;
                                    if ($lastapp) {
                                        $ldata = $data;
                                    }
                                    $info = [];
                                    $info['subject'] = 'CHANGE SHIFT APPLICATION';

                                    $info['clientname'] = $data[0]->clientname;
                                    $info['line'] = $data[0]->line;
                                    $info['dateid'] = $data[0]->dateid;
                                    $info['schedin'] = $data[0]->schedin;
                                    $info['schedout'] = $data[0]->schedout;
                                    $info['daytype'] = $data[0]->daytype;
                                    $info['remarks'] = $data[0]->rem;
                                    $info['remlast'] = $data[0]->rem1;
                                    $info['reason1'] = $data[0]->rem2;
                                    $info['orgdaytype'] = $data[0]->orgdaytype;
                                    $info['orgschedin'] = $data[0]->orgschedin;
                                    $info['orgschedout'] = $data[0]->orgschedout;
                                    $info['appstatus'] = $formailstatus;
                                    $info['companyid'] = $params['companyid'];
                                    $info['muduletype'] = 'SCHED';


                                    foreach ($ldata as $key => $value) {

                                        if (!empty($value->email)) {
                                            $info['approver'] = $value->username;
                                            $info['email'] = $value->email;
                                            $info['title'] = 'CHANGE SHIFT APPLICATION';
                                            $info['isapp'] = isset($value->approver) ? $value->approver : '';
                                            $info['appname'] = isset($approver[0]->appname) ? $approver[0]->appname : '';

                                            if ($lastapp) {
                                                $info['approvedstatus'] = 'Application ' . $formailstatus;
                                                $info['title'] = 'CHANGE SHIFT APPLICATION RESULT';
                                                if ($stat != 0) {
                                                    if (isset($value->appname2) == null) {
                                                        $info['appname2'] = '';
                                                    } else {
                                                        $info['appname2'] = $value->appname2;
                                                    }
                                                }
                                            }
                                        }
                                        $mailresult = $this->weblink($info, $config);
                                        if (!$mailresult['status']) {
                                            $response = ['msg' => 'Failed to send email.', 'status' => 'F'];
                                            $this->logger->sbcmasterlog2($line, $config, " MAIL - DATE: " . $scheddate . " Failed to send email.", app($url)->tablelogs);
                                        }
                                    }
                                } else {
                                    if (!empty($pendingdata)) {
                                        foreach ($pendingdata as $pd) {
                                            $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid,approver) values(?, ?, ?, ?, ?)", 'insert', [$pd->trno, $pd->line, $pd->doc, $pd->clientid, $pd->approver]);
                                        }
                                    }
                                    $response = ['msg' => 'Failed to approved Updating Record Error.', 'status' => 'F'];
                                    $this->logger->sbcmasterlog2($line, $config, " MAIL - DATE: " . $scheddate . " Failed to approved Updating Record Error. ", app($url)->tablelogs);
                                }
                            }
                        }
                        break;
                }
            } else {
                $response = ['msg' => 'Application doesnt exist or has been deleted!', 'status' => 'F'];
                $this->logger->sbcmasterlog2($line, $config, " MAIL - DATE: " . $scheddate . " Application doesnt exist or has been deleted! ", app($url)->tablelogs);
            }
        } else {
            $response = ['msg' => 'Invalid approver.', 'status' => 'F'];
            $this->logger->sbcmasterlog2($line, $config, " MAIL - DATE: " . $scheddate . " Invalid approver.", app($url)->tablelogs);
        }
        $response['remarks'] = $remarkslog;
        return view('emails.approved', compact('response'));
    }
    public function approvedLeave($params)
    {

        $url = 'App\Http\Classes\modules\payrollentry\\' . 'leaveapplicationportalapproval';
        $params['companyid'] = $params['cid'];
        $approversetup = app($url)->approvers($params);
        $status = $params['status'];
        $isapp = $params['isapp'];
        $msg = '';
        $formailstatus = '';

        $trno = 0;
        $scheddate = null;
        $empcode = '';
        $empname = '';

        $config['params']['doc'] = 'LEAVE';
        $config['params']['user'] = 'mail';

        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='LEAVE'");
        $both = false;
        if ($approversetup == '') {
            $approversetup = app($url)->approvers($params);
        } else {
            if (str_contains($approversetup, ' or ')) {
                $approversetup = explode(' or ', $approversetup);
                $both = true;
            } else {
                $approversetup = explode(',', $approversetup);
            }
            foreach ($approversetup as $appkey => $appsetup) {
                if ($appsetup == 'Supervisor') {
                    $approversetup[$appkey] = 'issupervisor';
                } else {
                    $approversetup[$appkey] = 'isapprover';
                }
            }
        }

        if ($status == 'A') {
            $msg = 'Application approved';
            $formailstatus = 'Approved';
        } elseif ($status == 'P') {
            $msg = 'Application approved';
            $formailstatus = 'Processed';
        } elseif ($status == 'D') {
            $msg = 'Application disapproved!';
            $formailstatus = 'Disapproved';
        }

        $datalogs = $this->coreFunctions->opentable("select lt.trno,cl.client as empcode,cl.clientname as empname,date(lt.effectivity) as scheddate
                        from leavetrans as lt 
                        left join client as cl on cl.clientid=lt.empid 
                        where md5(concat(lt.trno,'~',lt.line)) = '" . $params['id'] . "'");

        if (!empty($datalogs)) {
            $trno = $datalogs[0]->trno;
            $empcode = $datalogs[0]->empcode;
            $scheddate = $datalogs[0]->scheddate;
            $empname = $datalogs[0]->empname;
        }
        $remarkslog = app($url)->modulename . ' - ' .  $empname . ' - ' . $scheddate;
        $approver = $this->coreFunctions->opentable("select cl.email as appcode, cl.clientname as appname,emp.empid 
        from employee as emp left join client as cl on cl.clientid = emp.empid where md5(cl.email)='" . $params['uname'] . "'");
        if (!empty($approver)) {
            $isapprover = $this->coreFunctions->getfieldvalue("employee", "$isapp", "empid=?", [$approver[0]->empid]);
            $data = $this->coreFunctions->opentable("select lt.status,lt.status2,lt.trno,lt.line,concat(lt.trno,'~',lt.line) as trline, date(lt.dateid) as dateid, lt.daytype,lt.remarks,
                    lt.adays, date(lt.effectivity) as effdate,cl.clientname,ls.bal,ls.days as entitled,lt.empid,e.email,app.clientname as appname,app2.clientname as appname2,
                    lt.approvedby_disapprovedby,p.codename,e.supervisorid,lt.disapproved_remarks,lt.disapproved_remarks2,cl.email as username,lt.fillingtype
                    from leavetrans lt
                    left join leavesetup as ls on lt.trno = ls.trno
                    left join paccount as p on p.line=ls.acnoid
                    left join employee as e on e.empid=ls.empid
                    left join client as cl on cl.clientid=e.empid
                    left join batch as b on b.line=lt.batchid
                    left join client as app on app.email= lt.approvedby_disapprovedby and app.email <> ''
                    left join client as app2 on app2.email= lt.approvedby_disapprovedby2 and app2.email <> ''
                    where md5(concat(lt.trno,'~',lt.line)) = '" . $params['id'] . "'");

            if (!empty($data)) {


                $updatebal = false;
                $lastapp = false;
                $lstatus = '';
                $response = [];
                $status2 = "";
                switch ($data[0]->status) {
                    case 'A':
                        $response = ['msg' => 'Already approved by ' . $data[0]->appname . '.', 'status' => 'F'];
                        break;
                    case 'D':
                        $response = ['msg' => 'Already disapproved by ' . $data[0]->appname . '.', 'status' => 'F'];
                        break;
                    case 'P':
                        $response = ['msg' => 'Already approved without pay by ' . $data[0]->appname . '.', 'status' => 'F'];
                        break;
                    case 'E':

                        foreach ($approversetup as $key => $value) {
                            if (count($approversetup) > 1) {
                                if ($key == 0) {
                                    if ($value == $isapp && $isapprover) {
                                        switch ($data[0]->status2) {
                                            case 'A':
                                                $response = ['msg' => 'Already approved by ' . $data[0]->appname2 . '.', 'status' => 'F'];
                                                break;
                                            case 'D':
                                                $response = ['msg' => 'Already disapproved by ' . $data[0]->appname2 . '.', 'status' => 'F'];
                                                break;
                                            case 'P':
                                                $response = ['msg' => 'Already approved without pay by ' . $data[0]->appname2 . '.', 'status' => 'F'];
                                                break;
                                        }
                                        if ($both) {
                                            goto approved;
                                        }
                                        $appdata = ['status2' => $status];
                                        if ($status == 'D') {
                                            $lastapp = true;
                                        }
                                        $status2 = " and (status2 = 'A' or status2 = 'P') ";
                                        $appdata['approvedby_disapprovedby2'] = $approver[0]->appcode;
                                        $appdata['date_approved_disapproved2'] = $this->othersClass->getCurrentTimeStamp();
                                        break; // loop
                                    }
                                } else {
                                    if ($value == $isapp && $isapprover) {
                                        if ((count($approversetup) - 1) == $key) {

                                            goto approved;
                                        }
                                    }
                                }
                            } else {
                                if (count($approversetup) == 1) {
                                    approved:
                                    $lstatus = $status;
                                    $updatebal = true;
                                    $lastapp = true;

                                    if ($params['companyid'] == 53) {
                                        if ($data[0]->status2 == 'P' && $status == 'A') {
                                            $status = 'P';
                                        }
                                    }
                                    $appdata = ['status' => $status];
                                    $appdata['approvedby_disapprovedby'] = $approver[0]->appcode;
                                    $appdata['date_approved_disapproved'] = $this->othersClass->getCurrentTimeStamp();

                                    break; // break loop
                                }
                            }
                        }
                        $updatestatus = "";
                        if (empty($response)) {
                            $appdata['editdate'] = $this->othersClass->getCurrentTimeStamp();
                            $appdata['editby'] = $approver[0]->appcode;
                            $pendingdata = $this->coreFunctions->opentable("select * from pendingapp where trno=" . $data[0]->trno . " and line=" . $data[0]->line . " and doc='LEAVE'");
                            $this->coreFunctions->execqry("delete from pendingapp where doc = 'LEAVE' and trno= " . $data[0]->trno . " and line = " . $data[0]->line . " ", 'delete');
                            switch ($params['companyid']) {
                                case 53: //camera
                                case 51: //ulitc
                                    $stats = true;
                                    break;
                                default:
                                    $stats = false;
                                    break;
                            }
                            $data2 = ['empid' => $data[0]->empid];
                            $config['params']['doc'] = 'LEAVEAPPLICATIONPORTAL';
                            $config['params']['user'] = $approver[0]->appcode;
                            $config['params']['adminid'] = $approver[0]->empid;
                            $config['params']['companyid'] = $params['companyid'];

                            $appstatus = ['status' => true];
                            $res_status = true;
                            $msg = " Successfully " . $formailstatus;
                            if ($status == 'P') {
                                $msg = " Application Approved W/Out Pay";
                            }
                            if (!$lastapp) {
                                $appstatus = $this->othersClass->insertUpdatePendingapp($data[0]->trno, $data[0]->line, 'LEAVE', $data2, $url, $config, 0, $stats);
                            }
                            if (!$appstatus['status']) {
                                if (!empty($pendingdata)) {
                                    foreach ($pendingdata as $pd) {
                                        $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid,approver) values(?, ?, ?, ?, ?)", 'insert', [$pd->trno, $pd->line, $pd->doc, $pd->clientid, $pd->approver]);
                                    }
                                }

                                $msg = $appstatus['msg'];
                                $res_status = $appstatus['status'];
                                $response = ['msg' => $msg, 'status' => 'F'];
                            } else {
                                $update = $this->coreFunctions->sbcupdate('leavetrans', $appdata, ['trno' => $data[0]->trno, 'line' => $data[0]->line, 'empid' => $data[0]->empid]);
                                if ($update) {
                                    $statusdef = "('A','P')";
                                    if ($params['companyid'] == 53) { //camera
                                        $statusdef = "('A')";
                                        if ($lstatus == 'P') {
                                            $updatebal = false;
                                        }
                                    }
                                    if ($updatebal) {
                                        if ($lstatus != "") {
                                            if ($lstatus == 'A' || $lstatus == 'P') {
                                                $applied = $this->coreFunctions->datareader("select sum(adays) as value from leavetrans where status in $statusdef $status2 and empid =? and trno = ?", [$data[0]->empid, $data[0]->trno]);
                                                $bal = $data[0]->entitled - $applied;
                                                $this->coreFunctions->execqry("update leavesetup set bal='" . $bal . "' where trno =?", 'update', [$data[0]->trno]);
                                            }
                                        }
                                    }
                                    $query = "select cl.email as username ,emp.email,app.doc,app.approver from pendingapp as app
		                                 left join client as cl on cl.clientid = app.clientid
		                                 left join employee as emp on emp.empid = app.clientid 
		                                 where doc = 'LEAVE' and trno = " . $data[0]->trno . " and line = " . $data[0]->line . " ";

                                    $app_data = $this->coreFunctions->opentable($query);
                                    $l_data = $app_data;
                                    if ($lastapp) {
                                        $l_data = $data;
                                    }
                                    $response = ['msg' => $msg, 'status' => $status];
                                    if (!empty($l_data)) {
                                        $info['subject'] = 'LEAVE APPLICATION RESULT' . '- ' . $this->othersClass->getCurrentDate();
                                        $info['title'] = 'LEAVE APPLICATION';

                                        $info['clientname'] = $data[0]->clientname;
                                        $info['line'] = $data[0]->trline;
                                        $info['effdate'] = $data[0]->effdate;
                                        $info['dateid'] = $data[0]->dateid;
                                        $info['adays'] = $data[0]->adays;
                                        $info['bal'] = $data[0]->bal;
                                        $info['entitled'] = $data[0]->entitled;
                                        $info['codename'] = $data[0]->codename;
                                        $info['daytype'] = $data[0]->daytype;
                                        $info['remarks'] = $data[0]->remarks;
                                        $info['reason1'] = $data[0]->disapproved_remarks;
                                        $info['reason2'] = $data[0]->disapproved_remarks2;
                                        $info['companyid'] = $params['companyid'];
                                        $info['appstatus'] = $formailstatus;
                                        $info['fillingtype'] = $data[0]->fillingtype;
                                        $info['muduletype'] = 'LEAVE';

                                        foreach ($l_data as $key => $value) {

                                            if (!empty($value->email)) {
                                                $info['email'] = $value->email;
                                                $info['approver'] = $value->username;
                                                $info['isapp'] = isset($value->approver) ? $value->approver : "";
                                                $info['appname'] = $approver[0]->appname;

                                                if ($lastapp) {
                                                    $info['title'] = 'LEAVE APPLICATION RESULT';
                                                    if ($lstatus != "") {
                                                        $info['appname2'] = isset($value->appname2) ? $value->appname2 : "";
                                                    }
                                                    $info['approvedstatus'] = $formailstatus;
                                                    if ($lstatus == 'P') {
                                                        $info['approvedstatus'] = 'Approved';
                                                    }
                                                    if ($lstatus == 'A') {
                                                        $info['approvedstatus'] = 'Approved';
                                                    }
                                                }
                                                $mailresult = $this->weblink($info, $config);
                                                if (!$mailresult['status']) {
                                                    $msg = $mailresult['msg'];
                                                    $res_status = false;
                                                }
                                            }
                                        }
                                        if (!$res_status) {
                                            $response = ['msg' => $msg, 'status' => 'F'];
                                            $this->logger->sbcmasterlog2($trno, $config, " MAIL - DATE: " . $scheddate . " " . $msg, app($url)->tablelogs);
                                        } else {
                                            $this->logger->sbcmasterlog2($trno, $config, " MAIL - DATE: " . $scheddate . " " . $msg, app($url)->tablelogs);
                                        }
                                    }
                                } else {
                                    if (!empty($pendingdata)) {
                                        foreach ($pendingdata as $pd) {
                                            $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid,approver) values(?, ?, ?, ?, ?)", 'insert', [$pd->trno, $pd->line, $pd->doc, $pd->clientid, $pd->approver]);
                                        }
                                    }
                                    $response = ['msg' => 'Failed to approved Updating Record Error.', 'status' => 'F'];
                                    $this->logger->sbcmasterlog2($trno, $config, " MAIL - DATE: " . $scheddate . " Failed to approved Updating Record Error", app($url)->tablelogs);
                                }
                            }
                        }
                        break;
                }
            } else {
                $response = ['msg' => 'Application doesnt exist or has been deleted!', 'status' => 'F'];
                $this->logger->sbcmasterlog2($trno, $config, " MAIL - DATE: " . $scheddate . "Application doesnt exist or has been deleted!", app($url)->tablelogs);
            }
        } else {
            $response = ['msg' => 'Invalid approver.', 'status' => 'F'];
            $this->logger->sbcmasterlog2($trno, $config, " MAIL - DATE: " . $scheddate . "Invalid approver.", app($url)->tablelogs);
        }
        $response['remarks'] = $remarkslog;
        return view('emails.approved', compact('response'));
    }
    public function weblink($params, $config) 
    {
        $params['view'] = 'emails.firstnotice';
        $params['cc'] = '';
        // specified link
        switch ($params['companyid']) {
            case '51': // ulitc
                $params['weblink'] = "http://hris.ulitc.com/#/login";
                break;
            case '53': // camera
                $params['weblink'] = "http://www.extrahenrysemployeeportal.com/#/login";
                break;
            default:
                $params['weblink'] = "http://www.localhost/waimsv2_backend/spa/#/login";
                break;
        }


        switch ($params['muduletype']) {
            case 'OB':
                $params['subject'] = 'OB APPLICATION - ' . $params['clientname'] . ' - ' . $this->othersClass->getCurrentDate();
                $msg = $this->generateOBEmailMessage($params);
                break;
            case 'OB_INITIAL':
                $params['subject'] = 'OB APPLICATION INITIAL- ' . $params['clientname'] . ' - ' . $this->othersClass->getCurrentDate();
                $msg = $this->generateOBInitialMessage($params);
                break;
            case 'LEAVE':
                $params['subject'] = 'LEAVE APPLICATION - ' . $params['clientname'] . ' - ' . $this->othersClass->getCurrentDate();
                $msg = $this->generateLeaveEmail($params);
                break;
            case 'OT':
                $params['subject'] = 'OT APPLICATION - ' . $params['clientname'] . ' - ' . $this->othersClass->getCurrentDate();
                $msg = $this->generateOTEmailMessage($params);
                break;
            case 'LOAN':
                $params['subject'] = 'LOAN APPLICATION - ' . $params['clientname'] . ' - ' . $this->othersClass->getCurrentDate();
                $msg = $this->generateLoanEmailMessage($params);
                break;
            case 'SCHED':
                $params['subject'] = 'CHANGE SHIFT APPLICATION - ' . $params['clientname'] . ' - ' . $this->othersClass->getCurrentDate();
                $msg = $this->generateChangeSchedEmail($params);
                break;
        }

        $params['msg'] = $msg;
        return $this->createEmail($params, $config);
    }
    public function createEmail($params, $config)
    {
        $info = [];
        $info['subject'] = $params['subject'];
        $info['title'] = $params['title'];
        $info['view'] = 'emails.firstnotice';
        $info['msg'] = $params['msg'];
        $info['cc'] = $params['cc'];

        if (isset($params['hasattachment'])) {
            if ($params['hasattachment']) {
                $info['hasattachment'] = $params['hasattachment'];
                if (isset($params['attachments'])) {
                    $info['attachments'] = $params['attachments'];
                }
            }
        }

        if (strpos($params['line'], '~') !== false) {
            $params['line'] = explode('~', $params['line'])[0];
        }

        $info['email'] = $params['email'];

        try {
            $resutmail = $this->othersClass->sbcsendemail($params, $info);
            if (!$resutmail['status']) {
                $this->coreFunctions->create_Elog($resutmail['msg']);
                return ['status' => false, 'msg' => $resutmail['msg']];
                
            } else {
                $this->logger->sbcmasterlog2($params['line'], $config, "EMAIL SENT TO: " . $params['email'], 'payroll_log');
                return ['status' => true, 'msg' => 'Email sent'];
            }
        } catch (Exception $ex) {
            $errorLine = 'File:' . $ex->getFile() . ' Line:' . $ex->getLine() . " -> " . $ex->getMessage();
            $this->coreFunctions->create_Elog($errorLine);
            // $this->othersClass->logConsole($errorLine);
            return ['status' => false, 'msg' => 'Failed to create email.<br> ' . $errorLine];
        }
    }


    public function generateOBEmailMessage($params)
    {
        $host = $this->host;
        $approverparams = md5($params['approver']);
        $approvefunc = md5('approve_ob');
        $disapprovefunc = md5('disapprove_ob');
        $transid = md5($params['line']);
        $cid = $params['companyid'];
        $isapp = $params['isapp'];
        $weblink = isset($params['weblink']) ? $params['weblink'] : '';

        $url = 'App\Http\Classes\modules\payroll\\' . 'obapplication';
        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='OB'");
        $both = false;
        if ($approversetup == '') {
            $approversetup = app($url)->approvers($params);
        } else {
            if (str_contains($approversetup, ' or ')) {
                $approversetup = explode(' or ', $approversetup);
                $both = true;
            } else {
                $approversetup = explode(',', $approversetup);
            }
            foreach ($approversetup as $appkey => $appsetup) {
                if ($appsetup == 'Supervisor') {
                    $approversetup[$appkey] = 'issupervisor';
                } else {
                    $approversetup[$appkey] = 'isapprover';
                }
            }
        }
        if ($isapp != "") {
            if ($approversetup[0] == $isapp || $both) {
                if ($both) {
                    $action = ($approversetup[0] == $isapp) ? 'isfirstapp' : 'isfirstsup';
                } else {
                    $action = ($approversetup[0] == 'isapprover') ? 'isfirstapp' : 'isfirstsup';
                }
            } elseif (isset($approversetup[1]) && $approversetup[1] == $isapp) {
                $action = ($approversetup[1] == 'isapprover') ? 'islastapp' : 'islastsup';
            }
        } else {

            if ($both) {
                $action = ($approversetup[0] == 'isapprover') ? 'isfirstapp' : 'isfirstsup';
            } else {
                if (isset($approversetup[1])) {
                    $action = ($approversetup[1] == 'isapprover') ? 'islastapp' : 'islastsup';
                } else {
                    $action = ($approversetup[0] == 'isapprover') ? 'isfirstapp' : 'isfirstsup';
                }
            }
        }

        $html = '<html lang="en">' . $this->constructMessageHead($params);

        $buttons = '<div class="button-container">
                        <a href="' . $host . '/linkEmail?func=' . $approvefunc . '&uname=' . $approverparams . '&id=' . $transid . '&cid=' . $cid . '&isapp=' .  $isapp . '" class="approved">Approved</a>
                        <a href="' . $host . '/linkEmail?func=' . $disapprovefunc . '&uname=' . $approverparams . '&id=' . $transid . '&cid=' . $cid . '&isapp=' .  $isapp . '" class="disapproved">Disapproved</a>
                    </div>';

        $approvedstatus = '';
        $reason = '';
        $reasonapprover = 'Approver Remarks:';
        $reasonsupervisor = 'Supervisor Remarks:';

        $applabel = 'Approved by Approver';
        $suplabel = 'Approved by Supervisor';

        if ($cid == 53) { // camera
            $reasonapprover = 'Hr/Payroll Approver Reason:';
            $reasonsupervisor = 'Head Dept. Approver Reason:';

            $applabel = 'Hr/Payroll Approver';
            $suplabel = 'Head Dept. Approver';
        }
        $lastreason = $params['reason2'];
        if (count($approversetup) == 1) {
            $lastreason = $params['reason1'];
        }


        switch ($action) {
            case 'isfirstapp':
                // goto isfirstapp;
                $reason =
                    '<div>
                        <strong>' . $reasonapprover . '</strong>
                        <span>' . $lastreason . '</span>
                    </div>';
                break;
            case 'isfirstsup':

                $reason =
                    '<div>
                        <strong>' . $reasonsupervisor . '</strong>
                        <span>' . $lastreason . '</span>
                    </div>';
                // goto isfirstsup;
                break;
            case 'islastapp':
                // goto islastapp;
                $reason =
                    '<div>
                        <strong>' . $reasonsupervisor . '</strong>
                        <span>' . $params['reason1'] . '</span>
                    </div>
                    <div>
                        <strong>' . $reasonapprover . '</strong>
                        <span>' . $params['reason2'] . '</span>
                    </div>';

                break;
            case 'islastsup':
                // goto islastsup;
                $reason =
                    '<div>
                        <strong>' . $reasonapprover . '</strong>
                        <span>' . $params['reason1'] . '</span>
                    </div>
                    <div>
                        <strong>' . $reasonsupervisor . '</strong>
                        <span>' . $params['reason2'] . '</span>
                    </div>';
                break;
        }



        $appdate2 = '<div>
                       <strong>Applied Date Time:</strong>
                        <span>' . $params['datetime'] . '</span>
                    </div>';

        switch ($params['type']) {
            case 'Off-setting':
                $appdate2 = '
                    <div>
                       <strong>Applied Date Time In:</strong>
                        <span>' . $params['datetime'] . '</span>
                    </div>
                    <div>
                        <strong>Applied Date Time Out:</strong>
                        <span>' . $params['datetime2'] . '</span>
                    </div>';
                break;
            case 'Time-In':
            case 'Time-In at the Place Visited':
                $appdate2 = '<div>
                       <strong>Applied Date Time:</strong>
                        <span>' . $params['datetime'] . '</span>
                    </div>';
                break;
            case 'Time-Out':
            case 'Time-Out at the Place Visited':
                $appdate2 = '<div>
                       <strong>Applied Date Time:</strong>
                        <span>' . $params['datetime2'] . '</span>
                    </div>';
                break;
        }
        $void = "";
        if (isset($params['void_remarks'])) {
            $void = '<div>
                        <strong> Disapproved Reason </strong>
                        <span>' . $params['void_remarks'] . '</span>
                     </div>
            ';
        }



        if (isset($params['approvedstatus'])) {
            $buttons = '';
            if (isset($params['appname2']) && $params['appname2'] != "") {
                $approvedstatus = '
                        <div>
                            <strong>Status:</strong>
                            <span class="status">' . $params['approvedstatus'] . '</span>
                        </div>
                        <div>
                            <strong> ' . $suplabel . ' </strong>
                            <span>' . $params['appname2'] . '</span>
                        </div>
                        <div>
                            <strong> ' . $applabel . '</strong>
                            <span>' . $params['appname'] . '</span>
                        </div>
                        ';
            } else {
                $approvedstatus = '
                        <div>
                            <strong>Status:</strong>
                            <span class="status">' . $params['approvedstatus'] . '</span>
                        </div>
                        <div>
                            <strong>Approved/Disapproved by:</strong>
                            <span>' . $params['appname'] . '</span>
                        </div>
                        ';
            }
        } else {
            if (isset($params['appstatus'])) {
                if ($params['appstatus'] == 'Disapproved') {
                    $buttons = '';
                }
                $approvedstatus = '
                        <div>
                            <strong>Status:</strong>
                            <span class="status">' . $params['appstatus'] . '</span>
                        </div>
                        <div>
                            <strong>Approved/Disapproved by:</strong>
                            <span>' . $params['appname'] . '</span>
                        </div>
                        ';
            }
        }

        $body = '<body>
                <div class="ob-application">
                    <h3>' . $params['title'] . ' </h3>
                    
                    <div>
                        <strong>Name:</strong>
                        <span>' . $params['clientname'] . '</span>
                    </div>
                    <div>
                        <strong>Type:</strong>
                        <span>' . $params['type'] . '</span>
                    </div>
                    <div>
                        <strong>Schedule Date:</strong>
                        <span>' . $params['scheddate'] . '</span>
                    </div>
                    
                              ' . $appdate2 . '
                    
                    <div>
                        <strong>Location:</strong>
                        <span>' . $params['location'] . '</span>
                    </div>
                    <div>
                        <strong>Reason:</strong>
                        <span>' . $params['rem'] . '</span>
                    </div>

                    ' . $reason . $void . ' 
 

                    '  . $approvedstatus . $buttons . '
                        <div class="footer">
                            <p>This is a system-generated email. Please do not reply to this message.</p>
                             <a href="' . $weblink . '">GO TO PORTAL</a>
                        </div>
                </div>
            </body>';

        $final = $html . $body . '</html>';
        return $final;
    }
    public function generateOBInitialMessage($params)
    {
        $host = $this->host;
        $approverparams = md5($params['approver']);
        $approvefunc = md5('approve_initialob');
        $disapprovefunc = md5('disapprove_initialob');
        $transid = md5($params['line']);
        $isapp = $params['isapp'];
        $cid = $params['companyid'];
        $weblink = isset($params['weblink']) ? $params['weblink'] : '';


        $url = 'App\Http\Classes\modules\payroll\\' . 'obapplication';
        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='INITIALOB'");
        $both = false;
        if ($approversetup == '') {
            $approversetup = app($url)->approvers($params);
        } else {
            // $approversetup = explode(',', $approversetup);
            if (str_contains($approversetup, ' or ')) {
                $approversetup = explode(' or ', $approversetup);
                $both = true;
            } else {
                $approversetup = explode(',', $approversetup);
            }
            foreach ($approversetup as $appkey => $appsetup) {
                if ($appsetup == 'Supervisor') {
                    $approversetup[$appkey] = 'issupervisor';
                } else {
                    $approversetup[$appkey] = 'isapprover';
                }
            }
        }

        if ($isapp != "") {
            if ($approversetup[0] == $isapp || $both) {
                if ($both) {
                    $action = ($approversetup[0] == $isapp) ? 'isfirstapp' : 'isfirstsup';
                } else {
                    $action = ($approversetup[0] == 'isapprover') ? 'isfirstapp' : 'isfirstsup';
                }
            } elseif (isset($approversetup[1]) && $approversetup[1] == $isapp) {
                $action = ($approversetup[1] == 'isapprover') ? 'islastapp' : 'islastsup';
            }
        } else {
            if ($both) {
                $action = ($approversetup[0] == 'isapprover') ? 'isfirstapp' : 'isfirstsup';
            } else {
                if (isset($approversetup[1])) {
                    $action = ($approversetup[1] == 'isapprover') ? 'islastapp' : 'islastsup';
                } else {
                    $action = ($approversetup[0] == 'isapprover') ? 'isfirstapp' : 'isfirstsup';
                }
            }
        }
        $lastreason = $params['reason2'];
        if (count($approversetup) == 1 || $both) {
            $lastreason = $params['reason1'];
        }

        $html = '<html lang="en">' . $this->constructMessageHead($params);

        $buttons = '<div class="button-container">
                        <a href="' . $host . '/linkEmail?func=' . $approvefunc . '&uname=' . $approverparams . '&id=' . $transid . '&isapp=' . $isapp . '&cid=' . $cid . '" class="approved">Approved</a>
                        <a href="' . $host . '/linkEmail?func=' . $disapprovefunc . '&uname=' . $approverparams . '&id=' . $transid . '&isapp=' . $isapp . '&cid=' . $cid . '" class="disapproved">Disapproved</a>
                    </div>';

        $approvedstatus = '';



        $initialreason = '';
        $initialapprover = 'Initial Hr/Payroll Approver Reason:';
        $initialsupervisor = 'Initial Head Dept. Approver Reason:';
        switch ($action) {
            case 'isfirstapp':
                // goto isfirstapp;
                $initialreason =
                    '<div>
                        <strong>' . $initialapprover . '</strong>
                        <span>' . $lastreason . '</span>
                    </div>';
                break;
            case 'isfirstsup':

                $initialreason =
                    '<div>
                        <strong>' . $initialsupervisor . '</strong>
                        <span>' . $lastreason . '</span>
                    </div>';
                // goto isfirstsup;
                break;
            case 'islastapp':
                // goto islastapp;
                $initialreason =
                    '<div>
                        <strong>' . $initialsupervisor . '</strong>
                        <span>' . $params['reason2'] . '</span>
                    </div>
                    <div>
                        <strong>' . $initialapprover . '</strong>
                        <span>' . $params['reason1'] . '</span>
                    </div>';

                break;
            case 'islastsup':
                // goto islastsup;
                $initialreason =
                    '<div>
                        <strong>' . $initialapprover . '</strong>
                        <span>' . $params['reason2'] . '</span>
                    </div>
                    <div>
                        <strong>' . $initialsupervisor . '</strong>
                        <span>' . $params['reason1'] . '</span>
                    </div>';
                break;
        }



        $appdate2 = '<div>
                       <strong>Applied Date Times:</strong>
                        <span>' . $params['datetime'] . '</span>
                    </div>';

        switch ($params['type']) {
            case 'Off-setting':
                $appdate2 = '
                    <div>
                       <strong>Applied Date Time In:</strong>
                        <span>' . $params['datetime'] . '</span>
                    </div>
                    <div>
                        <strong>Applied Date Time Out:</strong>
                        <span>' . $params['datetime2'] . '</span>
                    </div>';
                break;
            case 'Time-In':
            case 'Time-In at the Place Visited':
                $appdate2 = '<div>
                       <strong>Applied Date Time:</strong>
                        <span>' . $params['datetime'] . '</span>
                    </div>';
                break;
            case 'Time-Out':
            case 'Time-Out at the Place Visited':
                $appdate2 = '<div>
                       <strong>Applied Date Time:</strong>
                        <span>' . $params['datetime2'] . '</span>
                    </div>';
                break;
        }




        if (isset($params['approvedstatus'])) {
            $buttons = '';
            if (isset($params['appname2']) && $params['appname2'] != "") {
                $approvedstatus = '
                        <div>
                            <strong>Status:</strong>
                            <span class="status">' . $params['approvedstatus'] . '</span>
                        </div>
                        <div>
                            <strong>Approved/Disapproved by:</strong>
                            <span>' . $params['appname2'] . '</span>
                        </div>
                          <div>
                            <strong>Approved/Disapproved by:</strong>
                            <span>' . $params['appname'] . '</span>
                        </div>';
            } else {
                $approvedstatus = '
                        <div>
                            <strong>Status:</strong>
                            <span class="status">' . $params['approvedstatus'] . '</span>
                        </div>
                          <div>
                            <strong>Approved/Disapproved by:</strong>
                            <span>' . $params['appname'] . '</span>     
                        </div>
                        ';
            }
        } else {
            if (isset($params['appstatus'])) {
                $approvedstatus = '
                        <div>
                            <strong>Status:</strong>
                            <span class="status">' . $params['appstatus'] . '</span>
                        </div>
                          <div>
                            <strong>Approved/Disapproved by:</strong>
                            <span>' . $params['appname'] . '</span>
                        </div>
                       ';
            }
        }

        $body = '<body>
                <div class="ob-application">
                    <h3>' . $params['title'] . ' </h3>
                    
                    <div>
                        <strong>Name:</strong>
                        <span>' . $params['clientname'] . '</span>
                    </div>
                    <div>
                        <strong>Schedule Date:</strong>
                        <span>' . $params['scheddate'] . '</span>
                    </div>
                    
                    <div>
                        <strong>Type:</strong>
                        <span>' . $params['type'] . '</span>
                    </div>
                             ' . $appdate2 . '
                    <div>
                        <strong>Location:</strong>
                        <span>' . $params['location'] . '</span>
                    </div>
                    <div>
                        <strong>Purpose/s:</strong>
                        <span>' . $params['rem'] . '</span>
                    </div>
                           

                    ' . $initialreason . $approvedstatus . $buttons . '
                        <div class="footer">
                            <p>This is a system-generated email. Please do not reply to this message.</p>
                            <a href="' . $weblink . '">GO TO PORTAL</a>
                        </div>
                </div>
            </body>';

        $final = $html . $body . '</html>';
        return $final;
    }
    public function generateOTEmailMessage($params)
    {
        $host = $this->host;
        $approverparams = md5($params['approver']);
        $approvefunc = md5('approve_ot');
        $disapprovefunc = md5('disapprove_ot');
        $transid = md5($params['line']);
        $cid = $params['companyid'];
        $isapp = $params['isapp'];
        $weblink = isset($params['weblink']) ? $params['weblink'] : '';
        $html = '<html lang="en">' . $this->constructMessageHead($params);

        $buttons = '<div class="button-container">
                        <a href="' . $host . '/linkEmail?func=' . $approvefunc . '&uname=' . $approverparams . '&id=' . $transid . '&cid=' . $cid . '&isapp=' . $isapp . '" class="approved">Approved</a>
                        <a href="' . $host . '/linkEmail?func=' . $disapprovefunc . '&uname=' . $approverparams . '&id=' . $transid . '&cid=' . $cid . '&isapp=' . $isapp . '" class="disapproved">Disapproved</a>
                    </div>';

        $approvedstatus = '';


        $url = 'App\Http\Classes\modules\payroll\\' . 'otapplicationadv';
        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='OT'");
        $both = false;
        if ($approversetup == '') {
            $approversetup = app($url)->approvers($params);
        } else {
            // $approversetup = explode(',', $approversetup);
            if (str_contains($approversetup, ' or ')) {
                $approversetup = explode(' or ', $approversetup);
                $both = true;
            } else {
                $approversetup = explode(',', $approversetup);
            }
            foreach ($approversetup as $appkey => $appsetup) {
                if ($appsetup == 'Supervisor') {
                    $approversetup[$appkey] = 'issupervisor';
                } else {
                    $approversetup[$appkey] = 'isapprover';
                }
            }
        }


        if ($isapp != "") {
            if ($approversetup[0] == $isapp || $both) {
                if ($both) {
                    $action = ($approversetup[0] == $isapp) ? 'isfirstapp' : 'isfirstsup';
                } else {
                    $action = ($approversetup[0] == 'isapprover') ? 'isfirstapp' : 'isfirstsup';
                }
            } elseif (isset($approversetup[1]) && $approversetup[1] == $isapp) {
                $action = ($approversetup[1] == 'isapprover') ? 'islastapp' : 'islastsup';
            }
        } else {

            if ($both) {
                $action = ($approversetup[0] == 'isapprover') ? 'isfirstapp' : 'isfirstsup';
            } else {
                if (isset($approversetup[1])) {
                    $action = ($approversetup[1] == 'isapprover') ? 'islastapp' : 'islastsup';
                } else {
                    $action = ($approversetup[0] == 'isapprover') ? 'isfirstapp' : 'isfirstsup';
                }
            }
        }

        $reason = '';
        $reasonapprover = 'Approver Remarks:';
        $reasonsupervisor = 'Supervisor Remarks:';

        $applabel = 'Approved by Approver';
        $suplabel = 'Approved by Supervisor';

        if ($cid == 53) { // camera
            $reasonapprover = 'Hr/Payroll Approver Reason:';
            $reasonsupervisor = 'Head Dept. Approver Reason:';

            $applabel = 'Hr/Payroll Approver';
            $suplabel = 'Head Dept. Approver';
        }
        $lastreason = $params['reason1'];
        if (count($approversetup) == 1 || $both) {
            $lastreason = $params['remlast'];
        }


        switch ($action) {
            case 'isfirstapp':
                // goto isfirstapp;
                $reason =
                    '<div>
                        <strong>' . $reasonapprover . '</strong>
                        <span>' . $lastreason . '</span>
                    </div>';
                break;
            case 'isfirstsup':

                $reason =
                    '<div>
                        <strong>' . $reasonsupervisor . '</strong>
                        <span>' . $lastreason . '</span>
                    </div>';
                // goto isfirstsup;
                break;
            case 'islastapp':
                // goto islastapp;
                $reason =
                    '<div>
                        <strong>' . $reasonsupervisor . '</strong>
                        <span>' . $params['reason1'] . '</span>
                    </div>
                    <div>
                        <strong>' . $reasonapprover . '</strong>
                        <span>' . $params['remlast'] . '</span>
                    </div>';

                break;
            case 'islastsup':
                // goto islastsup;
                $reason =
                    '<div>
                        <strong>' . $reasonapprover . '</strong>
                        <span>' . $params['reason1'] . '</span>
                    </div>
                    <div>
                        <strong>' . $reasonsupervisor . '</strong>
                        <span>' . $params['remlast'] . '</span>
                    </div>';
                break;
        }

        $void = "";
        if (isset($params['void_remarks'])) {
            $void = '<div>
                        <strong> Disapproved Reason </strong>
                        <span>' . $params['void_remarks'] . '</span>
                     </div>
            ';
        }

        if (isset($params['approvedstatus'])) {

            $buttons = '';
            if (isset($params['appname2'])) {
                if ($params['appname2'] != "") {
                    $approver = '
                        <div>
                            <strong> ' . $suplabel . '</strong>
                            <span>' . $params['appname2'] . '</span>
                        </div>
                        <div>
                            <strong> ' . $applabel . '</strong>
                            <span>' . $params['appname'] . '</span>
                        </div>';
                } else {
                    goto approver;
                }
            } else {
                approver:
                $approver = '
                        <div>
                            <strong> ' . $suplabel . '</strong>
                            <span>' . $params['appname'] . '</span>
                        </div>
                    ';
            }
            $approvedstatus = '
                       <div>
                            <strong>Status:</strong>
                            <span class="status">' . $params['approvedstatus'] . '</span>

                             
                        </div>
                        ' . $approver . '
                    ';
        } else {

            if (isset($params['appname'])) {
                if (isset($params['appstatus'])) {
                    $approvedstatus = '
                        <div>
                            <strong>Status:</strong>
                            <span class="status">' . $params['appstatus'] . '</span>
                        </div>
                        <div>
                            <strong> ' . $applabel . '</strong>
                            <span>' . $params['appname'] . '</span>
                        </div>
                        ';
                }
            }
        }
        $body = '<body>
                <div class="ob-application">
                    <h3>' . $params['title'] . ' </h3>
                    
                    <div>
                        <strong>Name:</strong>
                        <span>' . $params['clientname'] . '</span>
                    </div>
                    <div>
                        <strong>Create Date:</strong>
                        <span>' . $params['createdate'] . '</span>
                    </div>
                    <div>
                        <strong>Schedule Date:</strong>
                        <span>' . $params['scheddate'] . '</span>
                    </div>

                    <div>
                        <strong>OT Timein:</strong>
                        <span>' . $params['ottimein'] . '</span>
                    </div>
                    <div>
                        <strong>OT Timeout:</strong>
                        <span>' . $params['ottimeout'] . '</span>
                    </div>
                    <div>
                        <strong>Day Type:</strong>
                        <span>' . $params['daytype'] . '</span>
                    </div>
                    <div class="ot-container">
                        <div> <strong>COMPUTED OT HOURS</strong> </div>
                    </div>
                    <div>
                          <strong>OT Hours:</strong><span> ' . $params['othrs'] . ' </span> 
                      
                    </div>

                    <div>
                         <strong>OT > 8 Hours:</strong> <span>' . $params['othrsextra'] . '</span>  
                    </div>
                    <div>
                         <strong>N-Diff OT Hours: </strong> <span>' . $params['ndiffothrs'] . '</span>
                    </div>

                    <div>
                        <strong>Employee Reason:</strong>
                        <span>' . $params['rem'] . '</span>
                    </div>

                    ' . $reason . $void . $approvedstatus . $buttons . '

                    <div class="footer">
                            <p>This is a system-generated email. Please do not reply to this message.</p>
                            <a href="' . $weblink . '">GO TO PORTAL</a>
                    </div>
                </div>
            </body>';


        $final = $html . $body . '</html>';
        return $final;
    }
    public function generateLoanEmailMessage($params)
    {
        $host = $this->host;
        $approverparams = md5($params['approver']);
        $approvefunc = md5('approve_loan');
        $disapprovefunc = md5('disapprove_loan');
        $transid = md5($params['line']);
        $isapp = $params['isapp'];
        $cid = $params['companyid'];
        $weblink = isset($params['weblink']) ? $params['weblink'] : '';

        $url = 'App\Http\Classes\modules\payroll\\' . 'loanapplicationportal';
        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='LOAN'");
        $both = false;
        if ($approversetup == '') {
            $approversetup = app($url)->approvers($params);
        } else {
            if (str_contains($approversetup, ' or ')) {
                $approversetup = explode(' or ', $approversetup);
                $both = true;
            } else {
                $approversetup = explode(',', $approversetup);
            }
            foreach ($approversetup as $appkey => $appsetup) {
                if ($appsetup == 'Supervisor') {
                    $approversetup[$appkey] = 'issupervisor';
                } else {
                    $approversetup[$appkey] = 'isapprover';
                }
            }
        }
        $html = '<html lang="en">' . $this->constructMessageHead($params);

        $buttons = '<div class="button-container">
                        <a href="' . $host . '/linkEmail?func=' . $approvefunc . '&uname=' . $approverparams . '&id=' . $transid . '&isapp=' . $isapp . '&cid=' . $cid  . '" class="approved">Approved</a>
                        <a href="' . $host . '/linkEmail?func=' . $disapprovefunc . '&uname=' . $approverparams . '&id=' . $transid . '&isapp=' . $isapp . '&cid=' . $cid  . '" class="disapproved">Disapproved</a>
                    </div>';


        if ($isapp != "") {
            if ($approversetup[0] == $isapp || $both) {
                if ($both) {
                    $action = ($approversetup[0] == $isapp) ? 'isfirstapp' : 'isfirstsup';
                } else {
                    $action = ($approversetup[0] == 'isapprover') ? 'isfirstapp' : 'isfirstsup';
                }
            } elseif (isset($approversetup[1]) && $approversetup[1] == $isapp) {
                $action = ($approversetup[1] == 'isapprover') ? 'islastapp' : 'islastsup';
            }
        } else {

            if ($both) {
                $action = ($approversetup[0] == 'isapprover') ? 'isfirstapp' : 'isfirstsup';
            } else {
                if (isset($approversetup[1])) {
                    $action = ($approversetup[1] == 'isapprover') ? 'islastapp' : 'islastsup';
                } else {
                    $action = ($approversetup[0] == 'isapprover') ? 'isfirstapp' : 'isfirstsup';
                }
            }
        }

        $reason = '';
        $reasonapprover = 'Approver Remarks:';
        $reasonsupervisor = 'Supervisor Remarks:';
        $approver_hr = 'Approved by Approver';
        $approver_head = 'Approved by Supervior';
        if ($cid == 53) { // camera
            $reasonapprover = 'Hr/Payroll Approver Reason:';
            $reasonsupervisor = 'Head Dept. Approver Reason:';
            $approver_hr = 'Hr/Payroll Approver ';
            $approver_head = 'Head Dept. Approver';
        }

        $lastreason = $params['reason1'];
        if (count($approversetup) == 1) {
            $lastreason = $params['remlast'];
        }

        switch ($action) {
            case 'isfirstapp':
                // goto isfirstapp;
                $reason =
                    '<div>
                        <strong>' . $reasonapprover . '</strong>
                        <span>' . $lastreason . '</span>
                    </div>';
                break;
            case 'isfirstsup':

                $reason =
                    '<div>
                        <strong>' . $reasonsupervisor . '</strong>
                        <span>' . $lastreason . '</span>
                    </div>';
                // goto isfirstsup;
                break;
            case 'islastapp':
                // goto islastapp;
                $reason =
                    '<div>
                        <strong>' . $reasonsupervisor . '</strong>
                        <span>' . $params['reason1'] . '</span>
                    </div>
                    <div>
                        <strong>' . $reasonapprover . '</strong>
                        <span>' . $params['remlast'] . '</span>
                    </div>';

                break;
            case 'islastsup':
                // goto islastsup;
                $reason =
                    '<div>
                        <strong>' . $reasonapprover . '</strong>
                        <span>' . $params['reason1'] . '</span>
                    </div>
                    <div>
                        <strong>' . $reasonsupervisor . '</strong>
                        <span>' . $params['remlast'] . '</span>
                    </div>';
                break;
        }
        $void = "";
        if (isset($params['void_remarks'])) {
            $void = '<div>
                        <strong> Disapproved Reason </strong>
                        <span>' . $params['void_remarks'] . '</span>
                     </div>
            ';
        }

        $approvedstatus = '';

        if (isset($params['approvedstatus'])) {
            $buttons = '';
            if (isset($params['appname2']) && $params['appname2'] != "") {
                $approvedstatus = '
                        <div>
                            <strong>Status:</strong>
                            <span class="status">' . $params['approvedstatus'] . '</span>
                        </div>
                        <div>
                            <strong>' . $approver_hr . '</strong>
                            <span>' . $params['appname2'] . '</span>
                        </div>
                        <div>
                            <strong>' . $approver_head . '</strong>
                            <span>' . $params['appname'] . '</span>
                        </div>';
            } else {
                $approvedstatus = '
                        <div>
                            <strong>Status:</strong>
                            <span class="status">' . $params['approvedstatus'] . '</span>
                        </div>
                        <div>
                            <strong>Approved/Disapproed By:</strong>
                            <span>' . $params['appname'] . '</span>
                        </div>';
            }
        } else {

            if (isset($params['appstatus'])) {
                $approvedstatus = '
                        <div>
                            <strong>Status:</strong>
                            <span class="status">' . $params['appstatus'] . '</span>
                        </div>
                        <div>
                            <strong>Approved/Disapproved By:</strong>
                            <span>' . $params['appname'] . '</span>
                        </div>';
            }
        }
        $body = '<body>
                <div class="ob-application">
                    <h3> ' . $params['title'] . ' </h3>
                    
                    <div>
                        <strong>Name:</strong>
                        <span>' . $params['clientname'] . '</span>
                    </div>
                    <div>
                        <strong>Effectivity Date:</strong>
                        <span>' . $params['effdate'] . '</span>
                    </div>

                    <div>
                        <strong>Loan Type:</strong>
                        <span>' . $params['acnoname'] . '</span>
                    </div>
                    <div>
                        <strong>Loan Amount:</strong>
                        <span>' . $params['amount'] . '</span>
                    </div>
                    <div>
                        <strong>Amortazation:</strong>
                        <span>' . $params['amortization'] . '</span>
                    </div>

                    <div>
                        <strong>Balance:</strong>
                        <span>' . $params['balance'] . '</span>
                    </div>
                    <div>
                        <strong>Employee Reason:</strong>
                        <span>' . $params['remarks'] . '</span>
                    </div>
             

                     ' . $reason . $void . $approvedstatus . $buttons . '

                     <div class="footer">
                            <p>This is a system-generated email. Please do not reply to this message.</p>
                            <a href="' . $weblink . '">GO TO PORTAL</a>
                      </div>
                </div>
            </body>';

        $final = $html . $body . '</html>';
        return $final;
    }
    public function generateChangeSchedEmail($params)
    {
        $host = $this->host;
        $approverparams = md5($params['approver']);
        $approvefunc = md5('approve_sched');
        $disapprovefunc = md5('disapprove_sched');
        $transid = md5($params['line']);
        $cid = $params['companyid'];
        $weblink = isset($params['weblink']) ? $params['weblink'] : '';
        $isapp = $params['isapp'];

        $url = 'App\Http\Classes\modules\payroll\\' . 'changeshiftapplication';
        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='CHANGESHIFT'");
        $both = false;
        if ($approversetup == '') {
            $approversetup = app($url)->approvers($params);
        } else {
            if (str_contains($approversetup, ' or ')) {
                $approversetup = explode(' or ', $approversetup);
                $both = true;
            } else {
                $approversetup = explode(',', $approversetup);
            }
            foreach ($approversetup as $appkey => $appsetup) {
                if ($appsetup == 'Supervisor') {
                    $approversetup[$appkey] = 'issupervisor';
                } else {
                    $approversetup[$appkey] = 'isapprover';
                }
            }
        }

        $html = '<html lang="en">' . $this->constructMessageHead($params);

        $buttons = '<div class="button-container">
                        <a href="' . $host . '/linkEmail?func=' . $approvefunc . '&uname=' . $approverparams . '&id=' . $transid . '&cid=' . $cid . '&isapp=' . $isapp . '" class="approved">Approved</a>
                        <a href="' . $host . '/linkEmail?func=' . $disapprovefunc . '&uname=' . $approverparams . '&id=' . $transid . '&cid=' . $cid . '&isapp=' . $isapp . '" class="disapproved">Disapproved</a>
                    </div>';

        $approvedstatus = '';

        if ($isapp != "") {
            if ($approversetup[0] == $isapp || $both) {
                if ($both) {
                    $action = ($approversetup[0] == $isapp) ? 'isfirstapp' : 'isfirstsup';
                } else {
                    $action = ($approversetup[0] == 'isapprover') ? 'isfirstapp' : 'isfirstsup';
                }
            } elseif (isset($approversetup[1]) && $approversetup[1] == $isapp) {
                $action = ($approversetup[1] == 'isapprover') ? 'islastapp' : 'islastsup';
            }
        } else {

            if ($both) {
                $action = ($approversetup[0] == 'isapprover') ? 'isfirstapp' : 'isfirstsup';
            } else {
                if (isset($approversetup[1])) {
                    $action = ($approversetup[1] == 'isapprover') ? 'islastapp' : 'islastsup';
                } else {
                    $action = ($approversetup[0] == 'isapprover') ? 'isfirstapp' : 'isfirstsup';
                }
            }
        }

        $reason = '';
        $reasonapprover = 'Approver Remarks:';
        $reasonsupervisor = 'Supervisor Remarks:';

        $applabel = 'Approved by Approver';
        $suplabel = 'Approved by Supervisor';
        $label_rem = 'Remarks:';
        if ($cid == 53) { // camera
            $label_rem = 'Reason:';
            $reasonapprover = 'Hr/Payroll Approver Reason:';
            $reasonsupervisor = 'Head Dept. Approver Reason:';

            $applabel = 'Hr/Payroll Approver';
            $suplabel = 'Head Dept. Approver';
        }
        $lastreason = $params['reason1'];
        if (count($approversetup) == 1) {
            $lastreason = $params['remlast'];
        }


        switch ($action) {
            case 'isfirstapp':
                // goto isfirstapp;
                $reason =
                    '<div>
                        <strong>' . $reasonapprover . '</strong>
                        <span>' . $lastreason . '</span>
                    </div>';
                break;
            case 'isfirstsup':

                $reason =
                    '<div>
                        <strong>' . $reasonsupervisor . '</strong>
                        <span>' . $lastreason . '</span>
                    </div>';
                // goto isfirstsup;
                break;
            case 'islastapp':
                // goto islastapp;
                $reason =
                    '<div>
                        <strong>' . $reasonsupervisor . '</strong>
                        <span>' . $params['reason1'] . '</span>
                    </div>
                    <div>
                        <strong>' . $reasonapprover . '</strong>
                        <span>' . $params['remlast'] . '</span>
                    </div>';

                break;
            case 'islastsup':
                // goto islastsup;
                $reason =
                    '<div>
                        <strong>' . $reasonapprover . '</strong>
                        <span>' . $params['reason1'] . '</span>
                    </div>
                    <div>
                        <strong>' . $reasonsupervisor . '</strong>
                        <span>' . $params['remlast'] . '</span>
                    </div>';
                break;
        }
        $void = "";
        if (isset($params['void_remarks'])) {
            $void = '<div>
                        <strong> Disapproved Reason </strong>
                        <span>' . $params['void_remarks'] . '</span>
                     </div>
            ';
        }

        if (isset($params['approvedstatus'])) {
            $buttons = '';

            if (isset($params['appname2'])) {
                if ($params['appname2'] != "") {
                    $approver2 = $params['appname2'];
                    $approvedstatus = '
                        <div>
                            <strong> ' . $applabel . '</strong>
                            <span>' . $params['appname'] . '</span>
                        </div>
                        
                        <div>
                            <strong>' . $suplabel . '</strong>
                            <span>' . $approver2 . '</span>
                        </div>

                        <div>
                            <strong>Status:</strong>
                            <span class="status">' . $params['approvedstatus'] . '</span>
                        </div>
                        ';
                }
            } else {
                $approvedstatus = '
                        <div>
                            <strong> ' . $applabel . '</strong>
                            <span>' . $params['appname'] . '</span>
                        </div>
                        
                        <div>
                            <strong>Status:</strong>
                            <span class="status">' . $params['approvedstatus'] . '</span>
                        </div>
                        ';
            }
        } else {

            if (isset($params['appname'])) {

                if (isset($params['appstatus'])) {
                    $approvedstatus = '
                        <div>
                            <strong>Approved/Disapproved by:</strong>
                            <span>' . $params['appname'] . '</span>
                        </div>
                        <div>
                            <strong>Status:</strong>
                            <span class="status">' . $params['appstatus'] . '</span>
                        </div>';
                }
            }
        }
        $body = '<body>
                <div class="ob-application">
                    <h3> ' . $params['title'] . ' </h3>
                    
                    <div>
                        <strong>Name:</strong>
                        <span>' . $params['clientname'] . '</span>
                    </div>
                    <div>
                        <strong>Date:</strong>
                        <span>' . $params['dateid'] . '</span>
                    </div>
                    <div>
                        <strong>ORIGINAL SCHEDULE:</strong>
                    </div>
                    
                    <div>
                        <strong>Day Type</strong>
                        <span>' . $params['orgdaytype'] . '</span>
                    </div>

                    <div>
                        <strong>Schedule In</strong>
                        <span>' . $params['orgschedin'] . '</span>
                    </div>
                    <div>
                        <strong>Schedule Out</strong>
                        <span>' . $params['orgschedout'] . '</span>
                    </div>
                    <div>
                        <strong>CHANGE TO:</strong>
                    </div>
                    
                    <div>
                        <strong>Day Type</strong>
                        <span>' . $params['daytype'] . '</span>
                    </div>

                    <div>
                        <strong>Schedule In</strong>
                        <span>' . $params['schedin'] . '</span>
                    </div>
                    <div>
                        <strong>Schedule Out</strong>
                        <span>' . $params['schedout'] . '</span>
                    </div>
                    <div>
                        <strong>Employee ' . $label_rem . '</strong>
                        <span>' . $params['remarks'] . '</span>
                    </div>

                    ' . $reason . $void . $approvedstatus . $buttons . '                
                    <div class="footer">
                            <p>This is a system-generated email. Please do not reply to this message.</p>
                            <a href="' . $weblink . '">GO TO PORTAL</a>
                        </div>
                </div>
            </body>';

        $final = $html . $body . '</html>';
        return $final;
    }
    public function generateLeaveEmail($params)
    {
        $host = $this->host;
        $approverparams = md5($params['approver']);
        $approvefunc = md5('approve_leave');
        $processfunc = md5('process_leave');
        $disapprovefunc = md5('disapprove_leave');
        $transid = md5($params['line']);
        $cid = $params['companyid'];
        $isapp = $params['isapp'];
        $weblink = isset($params['weblink']) ? $params['weblink'] : '';

        $url = 'App\Http\Classes\modules\payrollentry\\' . 'leaveapplicationportalapproval';
        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='LEAVE'");
        $both = false;
        if ($approversetup == '') {
            $approversetup = app($url)->approvers($params);
        } else {
            if (str_contains($approversetup, ' or ')) {
                $approversetup = explode(' or ', $approversetup);
                $both = true;
            } else {
                $approversetup = explode(',', $approversetup);
            }
            foreach ($approversetup as $appkey => $appsetup) {
                if ($appsetup == 'Supervisor') {
                    $approversetup[$appkey] = 'issupervisor';
                } else {
                    $approversetup[$appkey] = 'isapprover';
                }
            }
        }

        $html = '<html lang="en">' . $this->constructMessageHead($params);

        $btnprocess = '<a href="' . $host . '/linkEmail?func=' . $processfunc . '&uname=' . $approverparams . '&id=' . $transid . '&cid=' . $cid . '&isapp=' . $isapp . '" class="process">Approved W/Out Pay</a>';
        $btnlabelwop = 'W/Out Pay';
        $btnlabelwp = 'With Pay';

        if ($cid == 51) { // ulitc
            $btnprocess = '';
            $btnlabelwop = '';
            $btnlabelwp = '';
        }
        $buttons = '<div class="button-container">
                        <a href="' . $host . '/linkEmail?func=' . $approvefunc . '&uname=' . $approverparams . '&id=' . $transid . '&cid=' . $cid . '&isapp=' . $isapp . '" class="approved">Approved ' . $btnlabelwp . '</a>
                        ' . $btnprocess . '
                        <a href="' . $host . '/linkEmail?func=' . $disapprovefunc . '&uname=' . $approverparams . '&id=' . $transid . '&cid=' . $cid . '&isapp=' . $isapp . '" class="disapproved">Disapproved</a>
                    </div>';
        $approvedstatus = '';

        $approver = '';
        if (isset($params['appstatus'])) {
            if ($params['appstatus'] == 'Processed') {
                $buttons = '<div class="button-container">
                        <a href="' . $host . '/linkEmail?func=' . $processfunc . '&uname=' . $approverparams . '&id=' . $transid . '&cid=' . $cid . '&isapp=' . $isapp . '" class="approved">Approved ' . $btnlabelwop . '</a>
                        <a href="' . $host . '/linkEmail?func=' . $disapprovefunc . '&uname=' . $approverparams . '&id=' . $transid . '&cid=' . $cid . '&isapp=' . $isapp . '" class="disapproved">Disapproved</a>
                    </div>';
            } else {
                $buttons = '<div class="button-container">
                        <a href="' . $host . '/linkEmail?func=' . $approvefunc . '&uname=' . $approverparams . '&id=' . $transid . '&cid=' . $cid . '&isapp=' . $isapp . '" class="approved">Approved ' . $btnlabelwp . '</a>
                        <a href="' . $host . '/linkEmail?func=' . $disapprovefunc . '&uname=' . $approverparams . '&id=' . $transid . '&cid=' . $cid . '&isapp=' . $isapp . '" class="disapproved">Disapproved</a>
                    </div>';
            }
        }

        if ($isapp != "") {
            if ($approversetup[0] == $isapp || $both) {
                if ($both) {
                    $action = ($approversetup[0] == $isapp) ? 'isfirstapp' : 'isfirstsup';
                } else {
                    $action = ($approversetup[0] == 'isapprover') ? 'isfirstapp' : 'isfirstsup';
                }
            } elseif (isset($approversetup[1]) && $approversetup[1] == $isapp) {
                $action = ($approversetup[1] == 'isapprover') ? 'islastapp' : 'islastsup';
            }
        } else {

            if ($both) {
                $action = ($approversetup[0] == 'isapprover') ? 'isfirstapp' : 'isfirstsup';
            } else {
                if (isset($approversetup[1])) {
                    $action = ($approversetup[1] == 'isapprover') ? 'islastapp' : 'islastsup';
                } else {
                    $action = ($approversetup[0] == 'isapprover') ? 'isfirstapp' : 'isfirstsup';
                }
            }
        }

        $reason = '';
        $reasonapprover = 'Approver Remarks:';
        $reasonsupervisor = 'Supervisor Remarks:';
        if ($cid == 53) { // camera
            $reasonapprover = 'Hr/Payroll Approver Reason:';
            $reasonsupervisor = 'Head Dept. Approver Reason:';
        }


        $lastreason = $params['reason2'];
        if (count($approversetup) == 1) {
            $lastreason = $params['reason1'];
        }


        switch ($action) {
            case 'isfirstapp':
                // goto isfirstapp;
                $reason =
                    '<div>
                        <strong>' . $reasonapprover . '</strong>
                        <span>' . $lastreason . '</span>
                    </div>';
                break;
            case 'isfirstsup':

                $reason =
                    '<div>
                        <strong>' . $reasonsupervisor . '</strong>
                        <span>' . $lastreason . '</span>
                    </div>';
                // goto isfirstsup;
                break;
            case 'islastapp':
                // goto islastapp;
                $reason =
                    '<div>
                        <strong>' . $reasonsupervisor . '</strong>
                        <span>' . $params['reason2'] . '</span>
                    </div>
                    <div>
                        <strong>' . $reasonapprover . '</strong>
                        <span>' . $params['reason1'] . '</span>
                    </div>';

                break;
            case 'islastsup':
                // goto islastsup;
                $reason =
                    '<div>
                        <strong>' . $reasonapprover . '</strong>
                        <span>' . $params['reason2'] . '</span>
                    </div>
                    <div>
                        <strong>' . $reasonsupervisor . '</strong>
                        <span>' . $params['reason1'] . '</span>
                    </div>';
                break;
        }
        $void = "";
        if (isset($params['void_remarks'])) {
            $void = '<div>
                        <strong> Disapproved Reason </strong>
                        <span>' . $params['void_remarks'] . '</span>
                     </div>
            ';
        }
        $filling = "";
        if (isset($params['fillingtype']) && $params['fillingtype'] != "") {
            $filling = '<div>
                        <strong> Filling Type </strong>
                        <span>' . $params['fillingtype'] . '</span>
                     </div>
            ';
        }
        if (isset($params['approvedstatus'])) {
            $buttons = '';
            switch ($params['approvedstatus']) {
                case 'Disapproved':
                    $params['approvedstatus'] = "Application Disapproved";
                    break;
                case 'Processed':
                    $params['approvedstatus'] = "Application W/Out Pay";
                    break;
                default:
                    $params['approvedstatus'] = "Application Approved";
                    break;
            }
            $approvedstatus = '
                            <div>
                                <strong>Status:</strong>
                                <span class="status">' . $params['approvedstatus'] . '</span>
                            </div>';
            if (isset($params['appname2']) && $params['appname2'] != "") {
                $approver = '
                            <div> 
                                <strong> ' . $reasonapprover . '</strong>
                                <span>' . $params['appname2'] . ' </span>
                            </div>
                            <div> 
                                <strong>' . $reasonsupervisor . '</strong>
                                <span>' . $params['appname'] . ' </span>
                            </div>';
            } else {
                if (isset($params['appname'])) {
                    $approver = ' 
                            <div> 
                                <strong>Approve by:</strong>
                                <span>' . $params['appname'] . ' </span>
                            </div>';
                }
            }
        } else {
            if (isset($params['appstatus'])) {
                switch ($params['appstatus']) {
                    case 'Disapproved':
                        $buttons = '';
                        $params['appstatus'] = 'Application ' . $params['appstatus'];
                        break;
                    case 'Processed':
                        $params['appstatus'] = 'Application Approved w/out pay';
                        break;
                    default:
                        $params['appstatus'] = $params['appstatus'];
                        break;
                }
                $approvedstatus = '
                            <div>
                                <strong>Status:</strong>
                                <span class="status">' . $params['appstatus'] . '</span>
                            </div>';
                $approver = ' 
                            <div> 
                                <strong>Approved By:</strong>
                                <span>' . $params['appname'] . ' </span>
                            </div>';
            }
        }
        $body = '<body>
                <div class="ob-application">
                    <h3> ' . $params['title'] . ' </h3>
                    
                    <div>
                        <strong>Name:</strong>
                        <span>' . $params['clientname'] . '</span>
                    </div>
                    <div>
                        <strong>Leave Type:</strong>
                        <span>' . $params['codename'] . '</span>
                    </div>
                    <div>
                        <strong>Applied Date:</strong>
                        <span>' . $params['dateid'] . '</span>
                    </div>

                    <div>
                        <strong>Effectivity Date:</strong>
                        <span>' . $params['effdate'] . '</span>
                    </div>
                    <div>
                        <strong>Entitled:</strong>
                        <span>' . $params['entitled'] . '</span>
                    </div>
                    <div>
                        <strong>Balance:</strong>
                        <span>' . $params['bal'] . '</span>
                    </div>
                    <div>
                        <strong>Applied Days</strong>
                        <span>' . $params['adays'] . '</span>
                    </div>
                       ' . $filling . '
                    <div>
                        <strong>Employee Reason:</strong>
                        <span>' . $params['remarks'] . '</span>
                    </div>


                    ' . $reason . $void . $approvedstatus . $approver . $buttons . '
                    <div class="footer">
                            <p>This is a system-generated email. Please do not reply to this message.</p>
                            <a href="' . $weblink . '">GO TO PORTAL</a>
                        </div>
                </div>
            </body>';

        $final = $html . $body . '</html>';
        return $final;
    }

    public function constructMessageHead($params)
    {
        return '<head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>' . $params['title'] . '</title>
                <style>
                    body {
                        margin: 0;
                        padding: 0;
                        height: 100vh;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        background-color: #f4f4f4; /* Light gray background for the page */
                        font-family: Arial, sans-serif;
                        font-size: 15px;
                    }

                    .ob-application {
                        border: 1px solid #ddd;
                        padding: 25px;
                        width: 500px;
                        background-color: #ffffff; /* White background for the div */
                        border-radius: 8px;
                        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                        text-align: left;
                    }

                    .ob-application h3 {
                        margin: 0 0 20px 0;
                        text-align: center;
                        color: #007BFF; /* Dark blue for the heading */
                        font-size: 20px;
                    }

                    .ob-application div {
                        margin-bottom: 15px;
                    }

                    .ob-application strong {
                        color: #34495e; /* Dark gray-blue for labels */
                        display: inline-block;
                        width: 120px; /* Fixed width for alignment */
                    }

                    .ob-application span {
                        color: #555; /* Gray for the content */
                    }

                    .button-container {
                        display: flex;
                        justify-content: space-between;
                        margin-top: 20px;
                    }

                    .button-container a {
                        text-decoration: none;
                        color: #fff;
                        padding: 10px 20px;
                        border-radius: 5px;
                        font-size: 14px;
                        text-align: center;
                        flex: 1;
                        margin: 0 5px;
                    }

                    .button-container a.approved {
                        background-color: #3893fc; /* blue for Approved */
                    }

                    .button-container a.disapproved {
                        background-color: #dc3545; /* Red for Disapproved */
                    }
                    .button-container a.process {
                        background-color: orange; /* orange for process */
                    }
                    .button-container a:hover {
                        opacity: 0.9;
                    }
                    .ot-container strong {
                        width: 200px;
                    }
                    .comlabel {
                        position: absolute;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%);
                    }
                    .status {
                        font-weight: bold;
                        color: #28a745;
                        font-size: 30px;
                    }
                    .footer {
                        margin-top: 20px;
                        font-size: 10px;
                        color: #777;
                    }
                        
                </style>
            </head>';
    }
}
