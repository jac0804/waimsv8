<?php

namespace App\Http\Classes\modules\tableentry;

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

class pendingloanapplication
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'PENDING LOAN APPLICATIONS';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $linkemail;
    public $tablelogs = 'payroll_log';
    public $style = 'width:90%;max-width:90%;';
    public $issearchshow = true;
    public $showclosebtn = true;
    public $fields = ['status', 'status2', 'approvedby_disapprovedby', 'approvedby_disapprovedby2', 'date_approved_disapproved', 'date_approved_disapproved2', 'disapproved_remarks', 'disapproved_remarks2', 'amt', 'amortization', 'balance'];



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
        $attrib = array(
            'load' => 3630
        );
        return $attrib;
    }

    public function createTab($config)
    {
        $doc = $config['params']['row']['doc'];
        $isapp = $config['params']['row']['approver'];
        $companyid = $config['params']['companyid'];
        $approver = $this->coreFunctions->getfieldvalue("employee", "$isapp", "empid=?", [$config['params']['adminid']]);
        $url = 'App\Http\Classes\modules\payroll\\' . 'loanapplicationportal';
        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='LOAN'");
        $both = false;
        if ($approversetup == '') {
            $approversetup = app($url)->approvers($config['params']);
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

        $cols = ['action', 'lblforapp', 'clientname', 'dateid', 'purpose', 'codename', 'amt', 'apamt', 'amortization', 'apamortization', 'rem', 'rem2', 'contact', 'remarkslast'];
        foreach ($cols as $key => $value) {
            $$value = $key;
        }

        $tab = [$this->gridname => ['gridcolumns' => $cols]];
        $stockbuttons = ['approve', 'disapprove'];

        if ($companyid == 51) { //ulitc
            array_push($stockbuttons, 'viewloanattachment');

            if ($isapp == 'issupervisor') {
                array_push($stockbuttons, 'viewloandetail');
            }
        }
        $obj = $this->tabClass->createtab($tab, $stockbuttons);


        $label = "Remarks";
        if ($companyid == 53) {
            $label = "Reason";
        }
        $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Name';
        $obj[0][$this->gridname]['columns'][$clientname]['style'] = 'width:200px;min-width:200px;';
        $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$dateid]['style'] = 'width:150px;min-width:150px;';
        $obj[0][$this->gridname]['columns'][$dateid]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$rem]['label'] = $label;
        $obj[0][$this->gridname]['columns'][$rem]['type'] = 'label';

        $obj[0][$this->gridname]['columns'][$rem2]['type'] = 'input';
        $obj[0][$this->gridname]['columns'][$rem2]['label'] = 'First Approver Remarks';
        $obj[0][$this->gridname]['columns'][$lblforapp]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$amt]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$amt]['label'] = 'Applied Amount';
        $obj[0][$this->gridname]['columns'][$amortization]['label'] = 'Applied Amortization';

        $obj[0][$this->gridname]['columns'][$codename]['label'] = 'Loan Type';
        $obj[0][$this->gridname]['columns'][$codename]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$codename]['style'] = 'width:150px;min-width:150px;';

        $obj[0][$this->gridname]['columns'][$contact]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$contact]['label'] = 'First Approver';


        if (count($approversetup) == 1 || $both) {
            $obj[0][$this->gridname]['columns'][$rem2]['type'] = 'coldel';
            $obj[0][$this->gridname]['columns'][$remarkslast]['readonly'] = false;
            $obj[0][$this->gridname]['columns'][$contact]['type'] = 'coldel';
            $obj[0][$this->gridname]['columns'][$remarkslast]['label'] = 'Approver Remarks';
        } else {

            if (($approver && $approversetup[0] == $isapp)) {
                $obj[0][$this->gridname]['columns'][$rem2]['readonly'] = false;
                $obj[0][$this->gridname]['columns'][$remarkslast]['readonly'] = true;
            } else if (($approver && $approversetup[1] == $isapp)) {
                $obj[0][$this->gridname]['columns'][$rem2]['readonly'] = true;
                $obj[0][$this->gridname]['columns'][$remarkslast]['readonly'] = false;
            } else {
                $obj[0][$this->gridname]['columns'][$rem2]['readonly'] = true;
                $obj[0][$this->gridname]['columns'][$remarkslast]['readonly'] = true;
            }
        }
        if ($companyid == 51) { //ulitc
            $obj[0][$this->gridname]['columns'][$purpose]['label'] = 'Purpose of Loan';
            $obj[0][$this->gridname]['columns'][$purpose]['type'] = 'label';
            $obj[0][$this->gridname]['columns'][$purpose]['style'] = 'width:150px;min-width:150px;';

            $obj[0][$this->gridname]['columns'][$amortization]['type'] = 'label';
            $obj[0][$this->gridname]['columns'][$apamortization]['type'] = 'input';
            $obj[0][$this->gridname]['columns'][$apamortization]['readonly'] = false;
            $obj[0][$this->gridname]['columns'][$apamortization]['style'] = 'width:110px;min-width:110px;';
        } else {
            $obj[0][$this->gridname]['columns'][$amortization]['type'] = 'label';
            $obj[0][$this->gridname]['columns'][$amortization]['style'] = 'text-align: right;';
            $obj[0][$this->gridname]['columns'][$purpose]['type'] = 'coldel';
            $obj[0][$this->gridname]['columns'][$apamt]['type'] = 'coldel';
            $obj[0][$this->gridname]['columns'][$apamortization]['type'] = 'coldel';
        }
        if ($companyid == 53) { // camera
            if ($approversetup[0] == $isapp || $both) {
                $obj[0][$this->gridname]['columns'][$remarkslast]['type'] = 'coldel';
                $obj[0][$this->gridname]['columns'][$contact]['type'] = 'coldel';
                if ($both) {
                    if ($isapp == 'isapprover') {
                        $obj[0][$this->gridname]['columns'][$remarkslast]['label'] = 'Hr/Payroll Approver Reason';
                    } else {
                        $obj[0][$this->gridname]['columns'][$remarkslast]['label'] = 'Head Dept. Approver Reason';
                    }
                } else {
                    if ($approversetup[0] == 'isapprover') {
                        $obj[0][$this->gridname]['columns'][$rem2]['label'] = 'Hr/Payroll Approver Reason';
                    } else {
                        $obj[0][$this->gridname]['columns'][$rem2]['label'] = 'Head Dept. Approver Reason';
                    }
                }
            }
            if (isset($approversetup[1])) {
                if ($approversetup[1] == $isapp) {
                    if ($approversetup[1] == 'isapprover') {
                        $obj[0][$this->gridname]['columns'][$rem2]['label'] = 'Head Dept. Approver Reasons';
                        $obj[0][$this->gridname]['columns'][$contact]['label'] = 'Head Dept. Approver';
                        $obj[0][$this->gridname]['columns'][$remarkslast]['label'] = 'Hr/Payroll Approver Reason';
                    } else {
                        $obj[0][$this->gridname]['columns'][$rem2]['label'] = 'Hr/Payroll Approver Reasons';
                        $obj[0][$this->gridname]['columns'][$contact]['label'] = 'Hr/Payroll Approver';
                        $obj[0][$this->gridname]['columns'][$remarkslast]['label'] = 'Head Dept. Approver Reason';
                    }
                }
            }
        }

        $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }

    public function createtabbutton($config)
    {

        $obj = [];
        return $obj;
    }

    public function createHeadField($config)
    {
        return array('col1' => []);
    }

    public function paramsdata($config)
    {
        return [];
    }

    public function data($config)
    {
        return [];
    }

    public function loaddata($config)
    {
        $adminid = $config['params']['adminid'];
        $companyid = $config['params']['companyid'];
        $approver = $config['params']['row']['approver'];
        $labelapprover = "FOR APPROVER";
        $labelsupervisor = "FOR SUPERVISOR";
        if ($companyid == 53) {
            $labelapprover = "FOR HR/PAYROLL APPROVER";
            $labelsupervisor = "FOR HEAD DEPT. APPROVER";
        }

        $qry = "
        select l.trno, client.client, client.clientname,format(l.apamt,2) as amt,format(l.amt,2) as apamt, p.codename,p.code, date(l.effdate) as dateid,l.status,
        l.disapproved_remarks as remarkslast,l.disapproved_remarks2 as rem2,l.remarks as rem,date(l.dateid) as createdate,format(l.apamortization,2) as amortization,format(l.amortization,2) as apamortization,l.empid,emp.email,date(l.effdate) as effectdate,l.balance,
        case when app.approver = 'isapprover' then '$labelapprover' else '$labelsupervisor' end as lblforapp,app.approver,
        m.modulename as doc, m.sbcpendingapp,app2.clientname AS contact,if(p.code = 'PT119',l.purpose1,l.purpose) as purpose

        from loanapplication as l 
        left join client on client.clientid=l.empid
        left join pendingapp as app on app.trno=l.trno and app.doc='LOAN'
        left join moduleapproval as m on m.modulename=app.doc
        left join employee as emp on emp.empid = l.empid
        left join paccount as p on p.line=l.acnoid
        left join client as app2 ON app2.email = l.approvedby_disapprovedby2 and app2.email <> ''
        where app.clientid= " .  $adminid . " and app.approver = '$approver'
        order by l.dateid,client.clientname";

        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }

    private function checkapprover($config)
    {
        $approver = $this->coreFunctions->datareader("select isapprover as value from employee where empid=?", [$config['params']['adminid']]);
        if ($approver == "1") return true;
        return false;
    }


    private function checksupervisor($config)
    {
        $supervisor = $this->coreFunctions->datareader("select issupervisor as value from employee where empid=?", [$config['params']['adminid']]);
        if ($supervisor == "1") return true;
        return false;
    }

    public function updateapp($config, $status)
    {
        $row = $config['params']['row'];
        $doc = $row['doc'];
        $trno = $row['trno'];
        $rem2 = $row['rem2'];
        $remarkslast = $row['remarkslast'];
        $empname = $row['clientname'];
        $rem = $row['rem'];
        $effectdate = $row['effectdate'];
        $loantype = $row['codename'];
        $amortization = $row['amortization'];
        $apamortization = $row['apamortization'];
        $amt = $row['amt'];
        $apamt = $row['apamt'];
        $email = $row['email'];
        $balance = $row['balance'];


        $empid = $row['empid'];
        $isapp = $row['approver'];




        $admin = $config['params']['adminid'];
        $companyid = $config['params']['companyid'];
        $config['params']['doc'] = 'LOANAPPLICATIONPORTAL';
        $approver = $this->coreFunctions->getfieldvalue("employee", "$isapp", "empid=?", [$admin]);
        $url = 'App\Http\Classes\modules\payroll\\' . 'loanapplicationportal';
        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='" . $doc . "'");
        $both = false;
        if ($approversetup == '') {
            $approversetup = app($url)->approvers($config['params']);
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
            $loanstatus = 'A';
            $label = 'Approved ';
            $status = $this->coreFunctions->datareader("select status as value from loanapplication where trno = ? and status = 'A' ", [$trno]);
        } else {
            $loanstatus = 'D';
            $label = 'Disapproved ';
            $status = $this->coreFunctions->datareader("select status as value from loanapplication where trno = ? and status = 'D' ", [$trno]);
        }
        if (!$status) {
            $lastapp = false;
            $last_stat = '';
            foreach ($approversetup as $key => $value) {
                if (count($approversetup) > 1) {

                    if ($both) {
                        goto approved;
                    }
                    if ($key == 0) {
                        if ($value == $isapp && $approver) {

                            $data = [
                                'status2' => $loanstatus,
                                'approvedby_disapprovedby2' => $config['params']['user'],
                                'date_approved_disapproved2' => $this->othersClass->getCurrentTimeStamp(),
                                'disapproved_remarks2' => $rem2
                            ];
                            if ($companyid == 51) { //ulitc
                                $data['amt'] = $apamt;
                                $data['amortization'] = $apamortization;
                                $data['balance'] = $apamt;
                            }
                            if ($loanstatus != 'A') {
                                // $data['status'] = 'D'; //pag na disapproved update na din ito last status
                                $lastapp = true;
                                if ($rem2 == '') {
                                    return ['status' => false, 'msg' => 'Approver Remarks is empty.', 'data' => []];
                                }
                            }

                            break;
                        }
                    } else {
                        if ($value == $isapp && $approver) {
                            if ((count($approversetup) - 1) == $key) goto approved;
                        }
                    }
                } else {
                    if (count($approversetup) == 1) {
                        approved:
                        $lastapp = true;
                        $last_stat = $loanstatus;
                        $data = [
                            'status' => $loanstatus,
                            'approvedby_disapprovedby' => $config['params']['user'],
                            'date_approved_disapproved' => $this->othersClass->getCurrentTimeStamp(),
                            'disapproved_remarks' => $remarkslast
                        ];
                        if ($companyid == 51) { //ulitc
                            $data['amt'] = $apamt;
                            $data['amortization'] = $apamortization;
                            $data['balance'] = $apamt;
                        }
                        if ($loanstatus != 'A') {
                            if ($remarkslast == '') {
                                return ['status' => false, 'msg' => 'Approver Remarks is empty.', 'data' => []];
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
            $tempdata['empid'] = $empid;

            $pendingdata = $this->coreFunctions->opentable("select * from pendingapp where trno=" . $trno . " and doc='LOAN'");
            $this->coreFunctions->execqry("delete from pendingapp where doc='LOAN' and trno=" . $trno . " ", 'delete');
            switch ($companyid) {
                case 53: //camera
                case 51: //ulitc
                    $stat = true;
                    break;
                default:
                    $stat = false;
                    break;
            }
            $appstatus = ['status' => true];
            if (!$lastapp) {
                $appstatus = $this->othersClass->insertUpdatePendingapp($row['trno'], 0, 'LOAN', $tempdata, $url, $config, 0, true);
                // $appstatus = $this->othersClass->updatePendingapp($row['trno'], 0, 'LOAN', $tempdata, $url, $config, 0, $stat);
            }

            if (!$appstatus['status']) {
                if (!empty($pendingdata)) {
                    foreach ($pendingdata as $pd) {
                        $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid,approver) values(?, ?, ?, ?, ?)", 'insert', [$pd->trno, $pd->line, $pd->doc, $pd->clientid, $pd->approver]);
                    }
                }
                return ['status' => false, 'msg' => $appstatus['msg'], 'data' => []];
            } else {



                if (!$lastapp) {
                    $query = "select cl.email as username ,emp.email,app.doc,app.approver from pendingapp as app
		                              left join client as cl on cl.clientid = app.clientid
		                              left join employee as emp on emp.empid = app.clientid 
		                              where doc = 'LOAN' and trno = $trno ";
                    $l_data = $this->coreFunctions->opentable($query);

                    if (empty($l_data)) {
                        $this->logger->sbcmasterlog($trno, $config, $label . $loantype . ' (' . $row['clientname'] . ') - ' . $row['dateid'] . ' - Approver Setup not complete');
                        if (!empty($pendingdata)) {
                            foreach ($pendingdata as $pd) {
                                $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid,approver) values(?, ?, ?, ?, ?)", 'insert', [$pd->trno, $pd->line, $pd->doc, $pd->clientid, $pd->approver]);
                            }
                        }
                        return ['status' => false, 'msg' => 'Approver Setup not complete', 'data' => []];
                    }
                } else {
                    $query = "select emp.email,client.email as username,app2.clientname as appname2
                                          from loanapplication as l 
		                                  left join client on client.clientid=l.empid
                                          left join employee as emp on emp.empid = l.empid
                                          left join client as app2 on app2.email = l.approvedby_disapprovedby2 and app2.email <>''
                                          where emp.empid = $empid and l.trno = $trno
                                          order by l.dateid,client.clientname";
                    $l_data = $this->coreFunctions->opentable($query);
                }


                $update = $this->coreFunctions->sbcupdate('loanapplication', $tempdata, ['trno' => $trno]);
                if ($update) {
                    $config['params']['doc'] = 'LOANAPPLICATIONPORTAL';
                    if (isset($tempdata['status']) && $tempdata['status'] == 'A') {
                        $systemtype = $this->companysetup->getsystemtype($config['params']);
                        $this->coreFunctions->LogConsole("okie dito! " . $lastapp . $tempdata['status']);
                        if ($systemtype == 'HRISPAYROLL' || $systemtype == 'PAYROLL') {
                            $qry = "insert into standardsetup (loantrno, docno, dateid, empid, remarks, acno, amt, paymode, w1, w2, w3, w4, w5, halt, priority, amortization, effdate, balance, acnoid )
                                        select trno, docno, dateid, empid, remarks, acno, amt, paymode, w1, w2, w3, w4, w5, halt, priority, amortization, effdate, balance, acnoid 
                                        from loanapplication where trno=" . $trno . " and status='A' and date_approved_disapproved is not null";

                            $execute = $this->coreFunctions->execqry($qry);
                            if ($execute) {
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

                    $appname = $this->coreFunctions->datareader("select clientname as value from client where email=?", [$config['params']['user']]);
                    if ($companyid == 51 || $companyid == 53) {
                        $params = [];
                        $params['clientname'] = $empname;
                        $params['line'] = $trno;
                        $params['effdate'] = $effectdate;
                        $params['acnoname'] = $loantype;
                        $params['amount'] = $apamt;
                        $params['amortization'] = $apamortization;
                        $params['remarks'] = $rem;
                        $params['reason1'] = $rem2;
                        $params['remlast'] = $remarkslast;
                        $params['balance'] =  $apamt;
                        $params['companyid'] = $companyid;
                        $params['appstatus'] = $label;
                        $params['muduletype'] = 'LOAN';

                        $msg = "Success";
                        $re_status = true;
                        foreach ($l_data as $key => $value) {

                            if (!empty($value->email)) {
                                $params['email'] = $value->email;
                                $params['approver'] = $value->username;
                                $params['appname'] = $appname;
                                $params['title'] = 'LOAN APPLICATION';
                                $params['isapp'] = isset($value->approver) ? $value->approver : "";
                                if ($lastapp) {
                                    $params['title'] = 'LOAN APPLICATION RESULT';
                                    $params['approvedstatus'] = 'Application ' . $label;
                                    if ($last_stat != "") {
                                        if (isset($value->appname2) && $value->appname2 != null) {
                                            $params['appname2'] = $value->appname2;
                                        } else {
                                            $params['appname2'] = '';
                                        }
                                    }
                                }
                                // $result = $this->linkemail->createLoanEmail($params);
                                $result = $this->linkemail->weblink($params, $config);
                                if (!$result['status']) {
                                    $msg = $result['msg'];
                                    $re_status = false;
                                }
                            }
                        } //end foreach
                        if (!$re_status) {
                            return ['status' => false, 'msg' => $msg, 'data' => []];
                        }
                    }
                } else {
                    if (!empty($pendingdata)) {
                        foreach ($pendingdata as $pd) {
                            $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid,approver) values(?, ?, ?, ? , ?)", 'insert', [$pd->trno, $pd->line, $pd->doc, $pd->clientid, $pd->approver]);
                        }
                    }
                    return ['status' => false, 'msg' => 'Error Updating Record', 'data' => []];
                }
            }


            $this->logger->sbcmasterlog($trno, $config, $label . $loantype . ' (' . $row['clientname'] . ') - ' . $row['dateid'] . ' Approved Amount' . $apamt . ' - Approved Amortization ' . $apamortization . ' ' . $rem2 . ' - ' . $remarkslast);
            return ['status' => true, 'msg' => 'Successfully ' . $label, 'data' => [], 'reloadsbclist' => true, 'action' => 'gapplications', 'deleterow' => true];
        } else {
            return ['status' => false, 'msg' => 'Already approved.', 'data' => []];
        }
    }
} //end class
