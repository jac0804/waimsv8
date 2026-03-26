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
use Exception;

class pendingleaveapplications
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'PENDING LEAVE APPLICATIONS';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $linkemail;
    public $tablelogs = 'payroll_log';
    public $style = 'width:90%;max-width:90%;';
    public $issearchshow = true;
    public $showclosebtn = true;
    public $leavefields = ['status', 'status2', 'date_approved_disapproved', 'date_approved_disapproved2', 'approvedby_disapprovedby', 'approvedby_disapprovedby2', 'disapproved_remarks', 'disapproved_remarks2', 'catid'];

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
            'load' => 2375
        );
        return $attrib;
    }

    public function createTab($config)
    {
        $doc = $config['params']['row']['doc'];
        $isapp = $config['params']['row']['approver'];

        $companyid = $config['params']['companyid'];
        $approver = $this->coreFunctions->getfieldvalue("employee", "$isapp", "empid=?", [$config['params']['adminid']]);
        $url = 'App\Http\Classes\modules\payrollentry\\' . 'leaveapplicationportalapproval';
        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='LEAVE'");


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
        $cols = [
            'action',
            'lblforapp',
            'clientname',
            'codename',
            'fillingtype',
            'entitled',
            'balance',
            'dateid',
            'effectivity',
            'hours',
            'remarks',
            'stat',
            'date_approved_disapprovedsup',
            'rem2',
            'contact',
            'remarkslast',
            'category'
        ];

        foreach ($cols as $key => $value) {
            $$value = $key;
        }
        $tab = [$this->gridname => ['gridcolumns' => $cols]];
        $stockbuttons = ['approve', 'disapprove'];

        if ($companyid == 53) { // camera
            if ($isapp != 'issupervisor') {
                array_push($stockbuttons, 'process');
            }
        }

        if ($companyid == 58) { // cdo
            array_push($stockbuttons, 'save');
        }
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Name';
        $obj[0][$this->gridname]['columns'][$clientname]['style'] = 'width:200px;min-width:200px;';
        $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$codename]['style'] = 'width:150px;min-width:150px;';
        $obj[0][$this->gridname]['columns'][$codename]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$codename]['label'] = 'Account Name';
        $obj[0][$this->gridname]['columns'][$dateid]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$dateid]['label'] = 'Date Applied';
        $obj[0][$this->gridname]['columns'][$effectivity]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$hours]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$hours]['label'] = 'Day';
        $obj[0][$this->gridname]['columns'][$hours]['style'] = 'text-align:left;';
        $obj[0][$this->gridname]['columns'][$remarks]['type'] = 'label';
        // $obj[0][$this->gridname]['columns'][$rem2]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$rem2]['label'] = 'First Approver Remarks';
        $obj[0][$this->gridname]['columns'][$remarkslast]['label'] = 'Approver Remarks';
        $obj[0][$this->gridname]['columns'][$lblforapp]['type'] = 'label';

        $obj[0][$this->gridname]['columns'][$contact]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$contact]['label'] = 'First Approver';
        $obj[0][$this->gridname]['columns'][$entitled]['label'] = 'Entitle Leave';
        $obj[0][$this->gridname]['columns'][$entitled]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$balance]['label'] = 'Balance';
        $obj[0][$this->gridname]['columns'][$balance]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$stat]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$stat]['label'] = 'First Approver Status';
        $obj[0][$this->gridname]['columns'][$stat]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';



        if ($companyid == 58) { //cdo
            $obj[0][$this->gridname]['columns'][$category]['type'] = "lookup";
            $obj[0][$this->gridname]['columns'][$category]['action'] = "lookupsetup";
            $obj[0][$this->gridname]['columns'][$category]['lookupclass'] = "lookupleavecategory";
            $obj[0][$this->gridname]['columns'][$category]['label'] = "Category";
            $obj[0][$this->gridname]['columns'][$category]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
            $obj[0][$this->gridname]['columns'][$fillingtype]['type'] = 'coldel';
        } else {
            $obj[0][$this->gridname]['columns'][$category]['type'] = 'coldel';
            $obj[0][$this->gridname]['columns'][$fillingtype]['type'] = 'label';
            if ($companyid != 51) {
                $obj[0][$this->gridname]['columns'][$fillingtype]['type'] = 'coldel';
            }
        }

        if (count($approversetup) == 1 || $both) {
            $obj[0][$this->gridname]['columns'][$rem2]['type'] = 'coldel';
            $obj[0][$this->gridname]['columns'][$remarkslast]['readonly'] = false;
            $obj[0][$this->gridname]['columns'][$remarkslast]['label'] = 'Approver Remarks';
            $obj[0][$this->gridname]['columns'][$contact]['type'] = 'coldel';
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

        if ($companyid == 53) { // camera
            $obj[0][$this->gridname]['columns'][$date_approved_disapprovedsup]['type'] = 'label';
            $obj[0][$this->gridname]['columns'][$date_approved_disapprovedsup]['label'] = 'Date Approved/Disapproved Hr/Payroll Approver';
            if ($approversetup[0] == $isapp || $both) {
                $obj[0][$this->gridname]['columns'][$stat]['type'] = 'coldel';
                $obj[0][$this->gridname]['columns'][$remarkslast]['type'] = 'coldel';
                $obj[0][$this->gridname]['columns'][$contact]['type'] = 'coldel';
                $obj[0][$this->gridname]['columns'][$date_approved_disapprovedsup]['type'] = 'coldel';
                $obj[0][$this->gridname]['columns'][0]['btns']['process']['color'] = 'orange';
                $obj[0][$this->gridname]['columns'][0]['btns']['process']['label'] = 'Approved w/out Pay';
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
            } else {
                if (isset($approversetup[1])) {
                    if ($approversetup[1] == $isapp) {
                        if ($approversetup[1] == 'isapprover') {
                            $obj[0][$this->gridname]['columns'][$stat]['label'] = 'Head Dept. Approver Status';
                            $obj[0][$this->gridname]['columns'][$rem2]['label'] = 'Head Dept. Approver Reasons';
                            $obj[0][$this->gridname]['columns'][$contact]['label'] = 'Head Dept. Approver';
                            $obj[0][$this->gridname]['columns'][$remarkslast]['label'] = 'Hr/Payroll Approver Reason';
                        } else {
                            $obj[0][$this->gridname]['columns'][$stat]['label'] = 'Hr/Payroll Approver Status';
                            $obj[0][$this->gridname]['columns'][$rem2]['label'] = 'Hr/Payroll Approver Reasons';
                            $obj[0][$this->gridname]['columns'][$contact]['label'] = 'Hr/Payroll Approver';
                            $obj[0][$this->gridname]['columns'][$remarkslast]['label'] = 'Head Dept. Approver Reason';
                        }
                    }
                }
            }
        } else {
            $obj[0][$this->gridname]['columns'][$stat]['type'] = 'coldel';
            $obj[0][$this->gridname]['columns'][$contact]['type'] = 'coldel';
            $obj[0][$this->gridname]['columns'][$date_approved_disapprovedsup]['type'] = 'coldel';
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



        $qry = "select ls.trno, l.line, client.clientname, date(l.dateid) as dateid, date(l.effectivity) as effectivity, 
            l.adays as hours, l.remarks,if(acc.code = 'PT122','',ls.days)  as entitled,l.empid,l.approvedby_disapprovedby2 as approvedby2,ifnull(lv.line,0) as catid,
            acc.codename,emp.supervisorid,cl.clientname as contact, l.disapproved_remarks2 as rem2,'' as remarkslast, 'LEAVE' as doc,
            if(acc.code = 'PT122','',ls.bal)  as balance,acc.code as accode,
            case when p.approver = 'isapprover' then '$labelapprover' else '$labelsupervisor' end as lblforapp,
            case
              when l.status = 'A' then 'APPROVED'
              when l.status = 'E' then 'ENTRY'
              when l.status = 'O' then 'ON-HOLD'
              when l.status = 'P' then 'PROCESSED'
              when l.status = 'D' then 'DISAPPROVED'
            end as status, 

            case 
            when l.status2 = 'A' then 'APPROVED'
            when l.status2 = 'P' then 'APPROVED W/OUT PAY'
            when l.status2 = 'D' then 'DISAPPROVED'
            end as stat,
            
            m.modulename as doc, m.sbcpendingapp, p.approver,lv.category,l.date_approved_disapproved2 as date_approved_disapprovedsup,l.fillingtype
            from leavetrans as l 
            left join leavesetup as ls on ls.trno = l.trno
            left join client on client.clientid=l.empid 
            left join paccount as acc on acc.line=ls.acnoid
            left join leavecategory as lv on lv.line = l.catid
            left join employee as emp on emp.empid = l.empid
            left join pendingapp as p on p.trno=ls.trno and p.line=l.line and p.doc='LEAVE'
            left join moduleapproval as m on m.modulename=p.doc
            left join client as cl on cl.email = l.approvedby_disapprovedby2 and cl.email <> ''
            where p.clientid=" . $adminid . " and p.approver = '$approver'
            order by client.clientname, l.effectivity";
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
        $companyid = $config['params']['companyid'];
        $row = $config['params']['row'];
        $doc = $row['doc'];
        $empname = $row['clientname'];
        $dateid = $row['dateid'];
        $effectdate = $row['effectivity'];
        $reqleave = $row['hours'];
        $balance = $row['balance'];
        $entitled = $row['entitled'];
        $ismaternity = false;

        switch ($companyid) {
            case 58: //cdohris
                if ($row['accode'] == 'PT113') { //maternity leave
                    $ismaternity = true;
                }
                break;
            case 53: //camera
                if ($row['accode'] == 'PT122') {
                    $rowbal = $this->coreFunctions->opentable("select days,bal from leavesetup where trno = ?", [$row['trno']]);
                    $balance = $rowbal[0]->bal;
                    $entitled = $rowbal[0]->days;
                    $row['balance'] = $balance;
                    $row['entitled'] = $entitled;
                }
                break;
        }

        $isapp = $row['approver'];

        $previous_status = $row['stat'];

        if ($isapp == '' || $isapp == null) $isapp = $this->coreFunctions->datareader("select approver from pendingapp where doc='LEAVE' and trno=" . $row['trno'] . " and line=" . $row['line']);
        $admin = $config['params']['adminid'];
        $approver = $this->coreFunctions->getfieldvalue("employee", $isapp, "empid=?", [$admin]);
        $url = 'App\Http\Classes\modules\payrollentry\\' . 'leaveapplicationportalapproval';
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
        if (isset($row['trno']) && isset($row['line'])) {
            $trno = $row['trno'];
            $line = $row['line'];
            $leavestatus = $status;
            switch ($status) {
                case 'A':
                    $label = 'Approved';
                    break;
                case 'D':
                    $label = 'Disapproved';
                    break;
                case 'P':
                    $label = 'Processed';
                    break;
            }
            $status = $this->coreFunctions->datareader("select status as value from leavetrans where trno=? and line=? and status='" . $status . "'", [$trno, $line]);
            if (!$status) {
                $label_reason = 'Remarks';

                if ($companyid == 53) { //camera
                    if ($leavestatus == 'A') {
                        if ($row['hours'] > $row['balance']) {
                            $this->logger->sbcmasterlog($trno, $config, 'Request Leave: ' . $row['hours'] . ' Leave Balance: ' . $row['balance'] . ' ' . $row['status'] . ' (' . $row['clientname'] . ') - ' . $row['dateid']);
                            return ['status' => false, 'msg' => 'Request Leave: ' . $row['hours'] . ' Leave Balance: ' . $row['balance'], 'data' => []];
                        }
                    }
                } else {
                    if ($leavestatus == 'A' || $leavestatus == 'P') {
                        if ($row['hours'] > $row['balance']) {
                            $this->logger->sbcmasterlog($trno, $config, 'Request Leave: ' . $row['hours'] . ' Leave Balance: ' . $row['balance'] . ' ' . $row['status'] . ' (' . $row['clientname'] . ') - ' . $row['dateid']);
                            return ['status' => false, 'msg' => 'Request Leave: ' . $row['hours'] . ' Leave Balance: ' . $row['balance'], 'data' => []];
                        }
                    }
                }
                $previous_status = $this->coreFunctions->datareader("select status2 as value from leavetrans where trno=? and line=? ", [$trno, $line]);
                $lastapp = false;
                $updatebal = false;
                $status2 = $la_status = '';
                foreach ($approversetup as $key => $value) {
                    if (count($approversetup) > 1) {
                        if ($both) {
                            goto approved;
                        }
                        if ($key == 0) {
                            if ($value == $isapp && $approver) {
                                if (($leavestatus != 'A' && $leavestatus != 'P')) {
                                    if ($row['rem2'] == '') return ['status' => false, 'msg' => 'Approver ' . $label_reason . ' is empty.', 'data' => []];
                                }
                                $data = [
                                    'status2' => $leavestatus,
                                    'date_approved_disapproved2' => $this->othersClass->getCurrentTimeStamp(),
                                    'approvedby_disapprovedby2' => $config['params']['user'],
                                    'disapproved_remarks2' => $row['rem2']
                                ];
                                if ($leavestatus == 'D') {
                                    // $data['status'] = 'D'; //pag na disapproved update na din ito last status
                                    $lastapp = true;
                                }
                                $status2 = " and (status2 = 'A' or status2 = 'P') ";
                                break;
                            }
                        } else {
                            if ($value == $isapp && $approver) {
                                if ((count($approversetup) - 1) == $key) {
                                    goto approved;
                                }
                            }
                        }
                    } else {
                        if (count($approversetup) == 1) {
                            approved:
                            $lastapp = true;
                            $la_status = $leavestatus;
                            $updatebal = true;
                            if ($leavestatus != 'A' && $leavestatus != 'P') {
                                if ($row['remarkslast'] == '') return ['status' => false, 'msg' => 'Approver ' . $label_reason . ' is empty.', 'data' => []];
                            }
                            if ($previous_status == 'P') {
                                if ($leavestatus == 'A') {
                                    $leavestatus = 'P';
                                }
                            }
                            $data = [
                                'status' => $leavestatus,
                                'date_approved_disapproved' => $this->othersClass->getCurrentTimeStamp(),
                                'approvedby_disapprovedby' => $config['params']['user'],
                                'disapproved_remarks' => $row['remarkslast']
                            ];
                            break;
                        }
                    }
                }
                $data['catid'] = $row['catid'];
                $tempdata = [];
                foreach ($this->leavefields as $key2) {
                    if (isset($data[$key2])) {
                        $tempdata[$key2] = $this->othersClass->sanitizekeyfield($key2, $data[$key2]);
                    }
                }
                $tempdata['editdate'] = $this->othersClass->getCurrentTimeStamp();
                $tempdata['editby'] = $config['params']['user'];
                $tempdata['empid'] = $row['empid'];
                $appstatus = ['status' => true];
                $pendingdata = $this->coreFunctions->opentable("select * from pendingapp where trno=" . $row['trno'] . " and line=" . $row['line'] . " and doc='LEAVE'");

                $appstatus = ['status' => true];
                if (!$ismaternity) {
                    $this->coreFunctions->execqry("delete from pendingapp where doc='LEAVE' and trno=" . $row['trno'] . " and line=" . $row['line'], 'delete');
                    if (!$lastapp) {
                        $this->coreFunctions->LogConsole('LAST APP');
                        $appstatus = $this->othersClass->insertUpdatePendingapp($row['trno'], $row['line'], 'LEAVE', $tempdata, $url, $config, 0, true);
                    }
                }

                $array_stat = ['A', 'P'];
                if (!$appstatus['status']) {
                    if (!empty($pendingdata)) {
                        foreach ($pendingdata as $pd) {
                            $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid,approver) values(?, ?, ?, ?, ?)", 'insert', [$pd->trno, $pd->line, $pd->doc, $pd->clientid, $pd->approver]);
                        }
                    }
                    return ['status' => false, 'msg' => $appstatus['msg'], 'data' => []];
                } else {
                    $config['params']['doc'] = 'LEAVEAPPLICATIONPORTAL';
                    switch ($companyid) {
                        case 58: //cdohris
                            if ($row['accode'] == 'PT113') { //maternity leave
                                $update = true;
                            } else {
                                $update = $this->coreFunctions->sbcupdate("leavetrans", $tempdata, ['trno' => $row['trno'], 'line' => $row['line'], 'status' => 'E']);
                            }
                            break;
                        default:
                            $update = $this->coreFunctions->sbcupdate("leavetrans", $tempdata, ['trno' => $row['trno'], 'line' => $row['line'], 'status' => 'E']);
                            break;
                    }

                    if ($update) {
                        $statusl = "('A', 'P')";
                        if ($companyid == 53) { //camera
                            $statusl = "('A')";
                            if ($la_status == 'P') {
                                $updatebal = false;
                            }
                        }
                        if ($ismaternity) {
                            $result = $this->updatematernity_leave($config, $row, $tempdata, $updatebal, $lastapp);

                            if (!$result['status']) {
                                $msg = $result['msg'];
                                return ['status' => false, 'msg' => $msg, 'data' => []];
                            }
                        } else {
                            if ($lastapp && $updatebal) {
                                if ($lastapp && $la_status != "") {
                                    if (in_array($la_status, $array_stat)) {
                                        $lastapprover = $approversetup[count($approversetup) - 1];
                                        if ($lastapprover == $isapp && $approver) {
                                            $applied = $this->coreFunctions->datareader("select sum(adays) as value from leavetrans where status in $statusl $status2 and empid=? and trno=?", [$row['empid'], $row['trno']], '', true);
                                            $bal = $row['entitled'] - $applied;
                                            $st = $this->coreFunctions->execqry("update leavesetup set bal='" . $bal . "' where trno=?", 'update', [$row['trno']]);
                                        }
                                    }
                                }
                            }
                        }

                        if ($companyid == 53 || $companyid == 51) { // camera|ulitc
                            $qry = "select trno,line,doc,app.clientid,cl.clientname,cl.email as username,emp.email,app.approver from pendingapp as app
                    				left join client as cl on cl.clientid = app.clientid
                    				left join employee as emp on emp.empid = app.clientid
                    				where app.doc ='LEAVE' and app.trno = " . $row['trno'] . " and app.line = " . $row['line'] . " ";
                            $data2 = $this->coreFunctions->opentable($qry);

                            $query = "select lt.trno, lt.line, concat(lt.trno,'~',lt.line) as trline,emp.supervisorid,date(lt.dateid) as dateid,lt.remarks,lt.status,
                                    lt.adays, date(lt.effectivity) as effdate,emp.email,app2.clientname as appname2,
                                    lt.approvedby_disapprovedby2,p.codename,lt.disapproved_remarks,lt.disapproved_remarks2,cl.email as username,lt.fillingtype
                                    from leavetrans lt
                                    left join leavesetup as ls on lt.trno = ls.trno
                                    left join paccount as p on p.line=ls.acnoid
                                    left join employee as emp on emp.empid = lt.empid
                                    left join client as cl on cl.clientid = emp.empid
                                    left join client as app2 on app2.email= lt.approvedby_disapprovedby2 and app2.email <> ''
                                    where lt.trno = $trno and lt.line = $line ";
                            $leave = $this->coreFunctions->opentable($query);
                            $currentapp = $this->coreFunctions->getfieldvalue("client", "clientname", "clientid=?", [$admin]);
                            $l_data = $data2;
                            if ($lastapp) {
                                $l_data = $leave; // direct to employee
                            }

                            $params['clientname'] = $empname;
                            $params['line'] = $leave[0]->trline;
                            $params['effdate'] = $effectdate;
                            $params['dateid'] = $dateid;
                            $params['adays'] = $reqleave;
                            $params['bal'] = $balance;
                            $params['entitled'] = $entitled;
                            $params['codename'] = $leave[0]->codename;
                            $params['remarks'] = $leave[0]->remarks;
                            $params['reason1'] = $leave[0]->disapproved_remarks;
                            $params['reason2'] = $leave[0]->disapproved_remarks2;
                            $params['companyid'] = $companyid;
                            $params['fillingtype'] = $leave[0]->fillingtype;
                            $params['muduletype'] = 'LEAVE';

                            $params['appstatus'] = $label;
                            $status = true;
                            foreach ($l_data as $key => $value) {
                                if (!empty($l_data[$key]->email)) {
                                    $params['approver'] = $l_data[$key]->username;
                                    $params['email'] = $l_data[$key]->email;
                                    $params['title'] = 'LEAVE APPLICATION';
                                    $params['isapp'] = isset($value->approver) ? $value->approver : "";
                                    $params['appname'] = $currentapp;
                                    if ($lastapp) {
                                        $params['approvedstatus'] = 'Application ' . $label;
                                        $params['title'] = 'LEAVE APPLICATION RESULT ';
                                        if ($la_status != "") {
                                            $params['appname'] = $currentapp;
                                            $params['appname2'] = $value->appname2;
                                        }
                                    }
                                    // $res =  $this->linkemail->createLeaveEmail($params);
                                    $res =  $this->linkemail->weblink($params, $config);
                                    if (!$res['status']) {
                                        $msg = $res['msg'];
                                        $status = true;
                                    }
                                }
                            }
                            if (!$status) {
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

                    $this->logger->sbcmasterlog($row['trno'], $config, $label . $row['status'] . ' (' . $row['clientname'] . ') - ' . $row['dateid'] . ' - ' . $row['line'] . '' . $row['rem2'] . ' ' . $row['remarkslast']);

                    if ($ismaternity) {
                        $returndata = $this->loaddata($config);
                        return ['status' => true, 'msg' => 'Successfully ' . $label . ' ', 'data' => [], 'reloadsbclist' => true, 'action' => 'gapplications', 'deleterow' => true, 'tableentrydata' => $returndata];
                    } else {
                        return ['status' => true, 'msg' => 'Successfully ' . $label . ' ', 'data' => [], 'reloadsbclist' => true, 'action' => 'gapplications', 'deleterow' => true];
                    }
                }
            } else {
                return ['status' => false, 'msg' => 'Already ' . $label . '.', 'data' => []];
            }
        }
        return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => []];
    }

    public function lookupsetup($config)
    {
        $lookupclass = $config['params']['lookupclass2'];
        switch ($lookupclass) {
            case 'lookupleavecategory':
                return $this->lookupleavecategory($config);
                break;
        }
    }


    public function lookupleavecategory($config)
    {
        $plotting = array();
        $title = 'List of Leave Category';
        $plotting = array('category' => 'category', 'catid' => 'line');
        $plottype = 'plotgrid';

        $lookupsetup = array(
            'type' => 'single',
            'title' => $title,
            'style' => 'width:500px;max-width:500px;'
        );
        $plotsetup = array(
            'plottype' => $plottype,
            'action' => '',
            'plotting' => $plotting
        );

        $cols = array(
            array('name' => 'category', 'label' => 'Category Name', 'align' => 'left', 'field' => 'category', 'sortable' => true, 'style' => 'font-size:16px;')
        );
        $qry = "select line,category from leavecategory";
        $data = $this->coreFunctions->opentable($qry);
        $index = $config['params']['index'];
        $table = isset($config['params']['table']) ? $config['params']['table'] : "";
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index, 'rowindex' => $index, 'table' => $table];
    }


    public function save($config)
    {
        $row = $config['params']['row'];
        $up =  $this->coreFunctions->execqry("update leavetrans set catid=? where trno=? and line=? and empid=?", 'update', [$row['catid'], $row['trno'], $row['line'], $row['empid']]);
        if ($up == 1) {
            $returnrow = $this->loaddata($config);
            return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
        } else {
            return ['status' => false, 'msg' => 'Saving failed.'];
        }
    } //end function

    public function updatematernity_leave($config, $row, $data, $updatebal, $lastapp)
    {
        $url = 'App\Http\Classes\modules\payrollentry\\' . 'leaveapplicationportalapproval';
        $effectivity = null;
        $appstatus = ['status' => true];
        $pendingdata = "";
        $trno = 0;


        $qry = "select lt.trno, lt.line,lt.empid , date(effectivity) as dateid
            from leavesetup as ls
            left join leavetrans as lt on lt.trno = ls.trno 
            left join paccount as acc on acc.line=ls.acnoid
            where acc.code = 'PT113' and lt.trno = " . $row['trno'] . " and (lt.status = 'E' and lt.status2 <> 'D') order by effectivity";
        $maternityLeaves = $this->coreFunctions->opentable($qry);
        try {
            foreach ($maternityLeaves as $leave) {

                $this->coreFunctions->execqry("delete from pendingapp where doc='LEAVE' and trno=" . $leave->trno . " and line=" . $leave->line, 'delete');
                if (!$lastapp) {
                    $appstatus = $this->othersClass->insertUpdatePendingapp($leave->trno, $leave->line, 'LEAVE', $data, $url, $config, 0, true);
                }
                if ($appstatus['status']) {
                    $update = $this->coreFunctions->sbcupdate("leavetrans", $data, ['trno' => $leave->trno, 'line' => $leave->line, 'empid' => $leave->empid]);
                    $this->coreFunctions->LogConsole('Updating Maternity Leave: ' . $leave->trno . ' - ' . $leave->line);
                    if (!$update) {
                        $effectivity = $leave->dateid;
                        $trno = $leave->trno;
                        $pendingdata = $this->coreFunctions->opentable("select * from pendingapp where trno=" . $leave->trno . " and line=" . $leave->line . " and doc='LEAVE'");
                        throw new Exception("Update failed: Maternity Leave");
                    }
                }
            }
        } catch (Exception $e) {
            $this->logger->sbcmasterlog($trno, $config, $row['status'] . ' effectivity:  ' . $effectivity . ' Employee Name: ' . $row['clientname]'] . 'Error: ' . $e->getMessage());
            if (!empty($pendingdata)) {
                foreach ($pendingdata as $pd) {
                    $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid,approver) values(?, ?, ?, ?, ?)", 'insert', [$pd->trno, $pd->line, $pd->doc, $pd->clientid, $pd->approver]);
                }
            }
            return  ['status' => false, 'msg' => $e->getMessage()];
        }
        if (isset($data['status'])) {
            if ($data['status'] == 'A') {
                if ($updatebal && $lastapp) {
                    $applied = $this->coreFunctions->datareader("select sum(lt.adays) as value from leavetrans as lt
                left join leavesetup as ls on ls.trno = lt.trno
                left join paccount as acc on acc.line=ls.acnoid
                where lt.status in ('A') and acc.code = 'PT113' and lt.empid=? and lt.trno=?", [$row['empid'], $row['trno']], '', true);
                    $bal = $row['entitled'] - $applied;
                    $this->coreFunctions->execqry("update leavesetup set bal='" . $bal . "' where trno=?", 'update', [$row['trno']]);
                }
            }
        }

        return ['status' => true, 'msg' => 'Successfully updated.'];
    }
} //end class
