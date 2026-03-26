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

class viewloanapplication
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'LOAN APPLICATION';
    public $gridname = 'customformacctg';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $linkemail;
    public $tablelogs = 'payroll_log';
    public $style = 'width:1000px;max-width:1000px;';
    public $issearchshow = true;
    public $showclosebtn = true;
    public $fields = ['status', 'status2', 'approvedby_disapprovedby', 'approvedby_disapprovedby2', 'date_approved_disapproved', 'date_approved_disapproved2', 'disapproved_remarks', 'disapproved_remarks2'];


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
        $url = 'App\Http\Classes\modules\payroll\\' . 'loanapplicationportal';
        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='LOAN'");
        if ($approversetup == '') {
          $approversetup = app($url)->approvers($config['params']);
        } else {
          $approversetup = explode(',', $approversetup);
        }
        $approveby = $config['params']['row']['approvedby2'];
        $fapprover = $this->coreFunctions->getfieldvalue("client", "clientname", "client=?", [$approveby]);
        $companyid = $config['params']['companyid'];
        $fields = ['clientname', 'lblmessage', 'remarks'];
        $col1 = $this->fieldClass->create($fields);

        data_set($col1, 'lblmessage.label', 'Employee Remarks: ');
        data_set($col1, 'lblmessage.style', 'font-size:11px;font-weight:bold;');
        $fields = ['dateid'];
        if (count($approversetup) > 1) {
            array_push($fields, 'lblrem', 'remark', 'lblapproved', 'rem');
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

        data_set($col2, 'dateid.type', 'input');
        data_set($col2, 'rem.label', 'Remarks');

        data_set($col2, 'lblrem.label', 'First Approver: ' . $fapprover);
        data_set($col2, 'lblrem.style', 'font-size:11px;font-weight:bold;');

        data_set($col2, 'lblapproved.label', 'Second Approver: ');
        data_set($col2, 'lblapproved.style', 'font-size:11px;font-weight:bold;');

        $fields = ['type', 'effectdate', 'amt', 'amortization'];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'type.type', 'input');
        data_set($col3, 'type.label', 'Loan Type');
        data_set($col3, 'amt.label', 'Loan Amount');
        data_set($col3, 'amortization.readonly', true);

        $fields = [['refresh', 'disapproved']];
        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'refresh.action', 'approve');
        data_set($col4, 'refresh.label', 'APPROVE');
        data_set($col4, 'refresh.confirm', true);
        data_set($col4, 'refresh.color', 'blue');
        data_set($col4, 'disapproved.confirm', true);
        data_set($col4, 'refresh.confirmlabel', 'Approved this loan application?');
        data_set($col4, 'disapproved.confirmlabel', 'Disapproved this loan application?');
        data_set($col4, 'disapproved.color', 'red');

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function paramsdata($config)
    {
        return $this->coreFunctions->opentable('select l.trno, client.client, client.clientname, FORMAT(l.amt,2) as amt, p.codename as type, date(l.effdate) as dateid,l.status,
        l.disapproved_remarks as rem,l.disapproved_remarks2 as remark,l.remarks,date(l.dateid) as createdate,format(l.amortization,2) as amortization,l.empid,emp.email,date(l.effdate) as effectdate,l.balance
        from loanapplication as l left join client on client.clientid=l.empid
        left join employee as emp on emp.empid = l.empid
        left join paccount as p on p.line=l.acnoid where l.trno=? and l.date_approved_disapproved is null order by client.clientname', [$config['params']['row']['trno']]);
    }

    public function data()
    {
        return [];
    }

    public function loaddata($config)
    {
        $data = [];
        if (isset($config['params']['dataparams']['trno'])) {
            $trno = $config['params']['dataparams']['trno'];
            $rem = $config['params']['dataparams']['rem'];
            $rem2 = $config['params']['dataparams']['remark'];


            $empname = $config['params']['dataparams']['clientname'];
            $loantype = $config['params']['dataparams']['type'];
            $effdate = $config['params']['dataparams']['dateid'];
            $remarks = $config['params']['dataparams']['remarks'];
            $amortization = $config['params']['dataparams']['amortization'];
            $amt = $config['params']['dataparams']['amt'];
            $email = $config['params']['dataparams']['email'];
            $balance = $config['params']['dataparams']['balance'];

            $url = 'App\Http\Classes\modules\payroll\\' . 'loanapplicationportal';
            $approversetup = app($url)->approvers($config['params']);
            $companyid = $config['params']['companyid'];
            $approver = $this->coreFunctions->getfieldvalue("employee", "isapprover", "empid=?", [$config['params']['adminid']]);
            $supervisor = $this->coreFunctions->getfieldvalue("employee", "issupervisor", "empid=?", [$config['params']['adminid']]);
            $action = $config['params']['action2'];
            switch ($action) {
                case 'approve':
                    $loanstatus = 'A';
                    $statys = 'Approved ';
                    $status = $this->coreFunctions->datareader("select status as value from loanapplication where trno = ? and status = 'A' ", [$trno]);
                    break;
                case 'disapproved': // disapproved
                    $loanstatus = 'D';
                    $statys = 'Disapproved';
                    $status = $this->coreFunctions->datareader("select status as value from loanapplication where trno = ? and status = 'D' ", [$trno]);
            }


            if (!$status) {

                foreach ($approversetup as $key => $value) {

                    if (count($approversetup) > 1) {
                        if ($key == 0) {
                            if ($value == 'issupervisor' && $supervisor || $value == 'isapprover' && $approver) {

                                if ($loanstatus != 'A') {
                                    if ($rem2 == '') {
                                        return ['status' => false, 'msg' => 'Approver Remarks is empty.', 'data' => []];
                                    }
                                }
                                $data = [
                                    'status2' => $loanstatus,
                                    'approvedby_disapprovedby2' => $config['params']['user'],
                                    'date_approved_disapproved2' => $this->othersClass->getCurrentTimeStamp(),
                                    'disapproved_remarks2' => $rem2
                                ];

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
                                'status' => $loanstatus,
                                'approvedby_disapprovedby' => $config['params']['user'],
                                'date_approved_disapproved' => $this->othersClass->getCurrentTimeStamp(),
                                'disapproved_remarks' => $rem
                            ];
                            if ($loanstatus != 'A') {
                                if ($rem == '') {
                                    return ['status' => false, 'msg' => 'Approver Remarks is empty.', 'data' => []];
                                }
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
            $update = $this->coreFunctions->sbcupdate("loanapplication", $tempdata, ['trno' => $trno]);
            if ($update) {
                if (isset($tempdata['status'])) {
                    if ($tempdata['status'] == 'A') {
                        $systemtype = $this->companysetup->getsystemtype($config['params']);

                        if ($systemtype == 'HRISPAYROLL' || $systemtype == 'PAYROLL') {

                            $qry = "insert into standardsetup (loantrno, docno, dateid, empid, remarks, acno, amt, paymode, w1, w2, w3, w4, w5, halt, priority, amortization, effdate, balance, acnoid )
                        select trno, docno, dateid, empid, remarks, acno, amt, paymode, w1, w2, w3, w4, w5, halt, priority, amortization, effdate, balance, acnoid 
                        from loanapplication where trno=" . $trno . " and status='A' and date_approved_disapproved is not null;";
                            if ($this->coreFunctions->execqry($qry)) {
                                $edTrno = $this->coreFunctions->getfieldvalue("standardsetup", "trno", "loantrno=?", [$trno], '', true);
                                if ($edTrno == 0) {
                                    $tempdata = [
                                        'status' => 'E',
                                        'approvedby_disapprovedby' => '',
                                        'date_approved_disapproved' => null,
                                        'disapproved_remarks' => ''
                                    ];
                                    $this->coreFunctions->sbcupdate("loanapplication", $tempdata, ['trno' => $trno]);

                                    return ['status' => false, 'msg' => 'Failed to approved this loan application, please inform your admin', 'data' => [], 'reloadsbclist' => true, 'action' => 'loanapplication'];
                                } else {
                                    $this->coreFunctions->sbcupdate("loanapplication", ['edtrno' => $edTrno], ["trno" => $trno]);
                                }
                            }
                        }
                    }
                }


                if ($companyid == 53) { // camera
                    if (!empty($email)) {
                        $params = [];
                        $params['title'] = 'LOAN APPLICATION RESULT';
                        $params['clientname'] = $empname;
                        $params['line'] = $trno;
                        $params['effdate'] = $effdate;
                        $params['acnoname'] = $loantype;
                        $params['amount'] = $amt;
                        $params['amortization'] = $amortization;
                        $params['remarks'] = $remarks;
                        $params['reason1'] = $rem;
                        $params['balance'] = $balance;

                        $qry = "select emp.email,cl.clientname from employee as emp
                                    left join client as cl on cl.clientid = emp.empid
                                    where cl.email = '" . $config['params']['user'] . "' ";
                        $data2 = $this->coreFunctions->opentable($qry);

                        $params['approver'] = $data2[0]->clientname;
                        $params['email'] = $email;
                        $params['approvedstatus'] = $statys;
                        $result = $this->linkemail->createLoanEmail($params);

                        if (!$result['status']) {
                            return ['status' => false, 'msg' => '' . $result['msg']];
                        }

                        // $data2 = [
                        //     'status' => 'E',
                        //     'approvedby_disapprovedby' => '',
                        //     'date_approved_disapproved' => null,
                        //     'disapproved_remarks' => ''
                        // ];

                        // $update = $this->coreFunctions->sbcupdate("loanapplication", $data2, ['trno' => $trno]);
                        // return ['status' => false, 'msg' => 'Sending email failed: email was empty.'];

                    }
                }

                $config['params']['doc'] = 'LOANAPPLICATION';
                $this->logger->sbcmasterlog($trno, $config, $statys . ' ' . $config['params']['dataparams']['type'] . ' (' . $config['params']['dataparams']['client'] . ') - ' . $config['params']['dataparams']['amt']);
                return ['status' => true, 'msg' => 'Successfully ' . $statys, 'data' => [], 'reloadsbclist' => true, 'action' => 'loanapplication'];
            }
        }

        return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => []];
    }
} //end class
