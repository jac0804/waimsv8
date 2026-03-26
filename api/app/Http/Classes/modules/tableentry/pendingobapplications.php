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

class pendingobapplications
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'PENDING OB APPLICATIONS';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $linkemail;
    public $tablelogs = 'payroll_log';
    public $style = 'width:90%;max-width:90%;';
    public $issearchshow = true;
    public $showclosebtn = true;
    public $obfields = ['status', 'status2', 'approverem', 'disapproved_remarks2', 'approvedby', 'approvedate', 'disapprovedby', 'disapprovedate', 'approvedby2', 'approvedate2', 'disapprovedby2', 'disapprovedate2'];


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
            'load' => 3627
        );
        return $attrib;
    }

    public function createTab($config)
    {
        $doc = $config['params']['row']['doc'];
        $companyid = $config['params']['companyid'];
        $isapp = $config['params']['row']['approver']; //issupervisor,isapprover

        if ($isapp == 'LATE FILLING') { //cdo
            $isapp = "isapprover";
        }

        $approver = $this->coreFunctions->getfieldvalue("employee", "$isapp", "empid=?", [$config['params']['adminid']]);
        $url = 'App\Http\Classes\modules\payroll\\' . 'obapplication';
        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='OB'");
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
        $cols = ['action', 'lblforapp', 'clientname', 'dateid', "dateid2", 'type', "trackingtype", 'rem', 'rem2', 'contact', 'remarkslast'];
        foreach ($cols as $key => $value) {
            $$value = $key;
        }
        $label = "Remarks";
        if ($companyid == 53) {
            $label = "Reason";
        }
        $tab = [$this->gridname => ['gridcolumns' => $cols]];
        $stockbuttons = ['approve', 'disapprove'];
        if ($companyid == 53 || $companyid == 51) { //camera , ulitc
            array_push($stockbuttons, 'viewobapplication');
        }
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Name';
        $obj[0][$this->gridname]['columns'][$clientname]['style'] = 'width:200px;min-width:200px;';
        $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$dateid]['style'] = 'width:150px;min-width:150px;';
        $obj[0][$this->gridname]['columns'][$dateid]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$type]['label'] = 'Type';
        $obj[0][$this->gridname]['columns'][$type]['style'] = 'width:80px;min-width:80px;';
        $obj[0][$this->gridname]['columns'][$type]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$rem]['label'] = 'Reason';
        $obj[0][$this->gridname]['columns'][$rem]['type'] = 'label';

        $obj[0][$this->gridname]['columns'][$rem2]['label'] = 'First Approver ' . $label;
        $obj[0][$this->gridname]['columns'][$remarkslast]['label'] = 'Approver Remarks';
        $obj[0][$this->gridname]['columns'][$contact]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$contact]['label'] = 'First Approver';
        $obj[0][$this->gridname]['columns'][$lblforapp]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$contact]['type'] = 'label';

        if (count($approversetup) == 1 || $both) {
            $obj[0][$this->gridname]['columns'][$rem2]['type'] = 'coldel';
            $obj[0][$this->gridname]['columns'][$remarkslast]['readonly'] = false;
            $obj[0][$this->gridname]['columns'][$contact]['type'] = 'coldel';
            $obj[0][$this->gridname]['columns'][$remarkslast]['label'] = 'Approver Remarks';
        } else {
            if (($approver && $approversetup[0] == $isapp)) {
                $obj[0][$this->gridname]['columns'][$rem2]['readonly'] = false;
                // $obj[0][$this->gridname]['columns'][$remarkslast]['readonly'] = true;
                $obj[0][$this->gridname]['columns'][$remarkslast]['type'] = 'coldel';
                $obj[0][$this->gridname]['columns'][$contact]['type'] = 'coldel';
            } else if (($approver && $approversetup[1] == $isapp)) {
                $obj[0][$this->gridname]['columns'][$rem2]['readonly'] = true;
                $obj[0][$this->gridname]['columns'][$remarkslast]['readonly'] = false;
            } else {
                $obj[0][$this->gridname]['columns'][$rem2]['readonly'] = true;
                $obj[0][$this->gridname]['columns'][$remarkslast]['readonly'] = true;
            }
        }
        switch ($companyid) {
            case 58: //cdohris
                $obj[0][$this->gridname]['columns'][$trackingtype]['type'] = 'label';
                $obj[0][$this->gridname]['columns'][$dateid2]['type'] = 'label';
                $obj[0][$this->gridname]['columns'][$dateid2]['label'] = 'Date Out';
                $obj[0][$this->gridname]['columns'][$dateid]['label'] = 'Date In';

                $obj[0][$this->gridname]['columns'][$trackingtype]['style'] = 'width:150px;min-width:150px;';
                $obj[0][$this->gridname]['columns'][$dateid2]['style'] = 'width:150px;min-width:150px;';

                $obj[0][$this->gridname]['columns'][$rem]['style'] = 'width:120px;min-width:120px;';
                $obj[0][$this->gridname]['columns'][$rem2]['style'] = 'width:120px;min-width:120px;';
                break;
            case 53: //camera
                $obj[0][$this->gridname]['columns'][$trackingtype]['type'] = 'coldel';
                $obj[0][$this->gridname]['columns'][$dateid2]['type'] = 'label';
                $obj[0][$this->gridname]['columns'][$dateid2]['label'] = 'Date Out';
                $obj[0][$this->gridname]['columns'][$dateid2]['style'] = 'width:150px;min-width:150px;';
                $obj[0][$this->gridname]['columns'][$dateid]['label'] = 'Date In';

                break;
            default:
                $obj[0][$this->gridname]['columns'][$dateid2]['type'] = 'coldel';
                $obj[0][$this->gridname]['columns'][$trackingtype]['type'] = 'coldel';
                break;
        }

        if ($companyid == 53) { // camera
            if ($approversetup[0] == $isapp || $both) {
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
        $qry = "select ob.line, client.clientname, ob.dateid,ob.dateid2, ob.type, ob.rem, '' as remarkslast,
            ob.disapproved_remarks2 as rem2, ob.approvedby2, emp.supervisorid, ob.ontrip,emp.email,ob.location,
            m.modulename as doc, m.sbcpendingapp,date(ob.scheddate) as scheddate,dayname(ob.scheddate) as dayname,
            date_format(ob.dateid, '%Y-%m-%d %H:%i %s') as datetime,date_format(ob.dateid2, '%Y-%m-%d %H:%i %s') as datetime2,ob.empid,
            case when p.approver = 'isapprover' then '$labelapprover' else '$labelsupervisor' end as lblforapp, p.approver,
            ifnull(app2.clientname,appdis.clientname) as contact,ob.initial_remarks,ob.trackingtype
            from obapplication as ob
            left join client on client.clientid=ob.empid
            left join pendingapp as p on p.line=ob.line and p.doc='OB'
            left join employee as emp on emp.empid = ob.empid
            left join moduleapproval as m on m.modulename=p.doc
            left join client as app2 on app2.email = ob.approvedby2 and app2.email <> ''
            left join client as appdis on appdis.email = ob.disapprovedby2 and appdis.email <> ''
            where p.clientid=" . $adminid . " and p.approver = '$approver'
            order by ob.dateid, client.clientname";
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

        $rem2 = $row['rem2'];
        $remarkslast = $row['remarkslast'];
        $empname = $row['clientname'];
        $scheddate = $row['scheddate'];
        $dayname = $row['dayname'];
        $datetime = $row['datetime'];
        $datetime2 = $row['datetime2'];
        $dateid = $row['dateid'];
        $type = $row['type'];
        $emprem = $row['rem'];
        $email = $row['email'];
        $location = $row['location'];
        $line = $row['line'];
        $empid = $row['empid'];
        $isapp = $row['approver']; //issupervisor,isapprover
        $initial_remarks = $row['initial_remarks'];

        if ($isapp == '' || $isapp == null) $isapp = $this->coreFunctions->datareader("select approver as value from pendingapp where doc='OB' and line=" . $row['line']);

        $admin = $config['params']['adminid'];
        $companyid = $config['params']['companyid'];

        if ($isapp == 'LATE FILLING') { //cdo
            $approver = 1;
            $isapp = "isapprover";
        } else {
            $approver = $this->coreFunctions->getfieldvalue("employee", "$isapp", "empid=?", [$admin]);
        }

        $url = 'App\Http\Classes\modules\payroll\\' . 'obapplication';
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
            $obstatus = 'A';
            $label = 'Approved ';
            $status = $this->coreFunctions->datareader("select approvedate as value from obapplication where line=? and approvedate is not null", [$row['line']]);
        } else {
            $obstatus = 'D';
            $label = 'Disapproved ';
            $status = $this->coreFunctions->datareader("select disapprovedate as value from obapplication where line=? and disapprovedate is not null", [$row['line']]);
        }
        if (!$status) {
            $label_reason = 'Remarks';
            $bothapprover = false;
            $lastapp = false;
            $stat = '';
            foreach ($approversetup as $key => $value) {
                if (count($approversetup) > 1) {

                    if ($key == 0) {

                        if ($both) {
                            goto approved;
                        }
                        if ($value == $isapp && $approver) {
                            $data = ['status2' => $obstatus, 'disapproved_remarks2' => $row['rem2']];
                            if ($obstatus == 'A') {
                                $data['approvedby2'] = $config['params']['user'];
                                $data['approvedate2'] = $this->othersClass->getCurrentTimeStamp();
                            } else {
                                if ($row['rem2'] == '') return ['status' => false, 'msg' => 'First Approver ' . $label_reason . ' is empty.', 'data' => []];
                                // $data['status'] = $obstatus; //pag na disapproved update na din ito last status
                                $data['disapprovedby2'] = $config['params']['user'];
                                $data['disapprovedate2'] = $this->othersClass->getCurrentTimeStamp();
                                $lastapp = true;
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
                        $stat = $obstatus;
                        $lastapp = true;
                        $data = ['status' => $obstatus, 'approverem' => $row['remarkslast']];
                        if ($obstatus == 'A') {
                            $data['approvedby'] = $config['params']['user'];
                            $data['approvedate'] = $this->othersClass->getCurrentTimeStamp();
                        } else {
                            if ($row['remarkslast'] == '') return ['status' => false, 'msg' => 'First Approver ' . $label_reason . ' is empty.', 'data' => []];
                            $data['disapprovedby'] = $config['params']['user'];
                            $data['disapprovedate'] = $this->othersClass->getCurrentTimeStamp();
                        }
                        if ($bothapprover) $data['status2'] = $obstatus;
                        break;
                    }
                }
            }
            $tempdata = [];
            foreach ($this->obfields as $key2) {
                if (isset($data[$key2])) {
                    $tempdata[$key2] = $data[$key2];
                    $tempdata[$key2] = $this->othersClass->sanitizekeyfield($key2, $tempdata[$key2]);
                }
            }
            $tempdata['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $tempdata['editby'] = $config['params']['user'];
            $pendingdata = $this->coreFunctions->opentable("select * from pendingapp where line=" . $row['line'] . " and doc='OB'");
            $this->coreFunctions->execqry("delete from pendingapp where doc='OB' and line=" . $row['line'], 'delete');
            $tempdata['empid'] = $row['empid'];
            $appstatus = ['status' => true];
            if (!$lastapp) {
                $appstatus = $this->othersClass->insertUpdatePendingapp(0, $row['line'], 'OB', $tempdata, $url, $config, 0, true);
            }
            if (!$appstatus['status']) {
                if (!empty($pendingdata)) {
                    foreach ($pendingdata as $pd) {
                        $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid,approver) values(?, ?, ?, ?,?)", 'insert', [$pd->trno, $pd->line, $pd->doc, $pd->clientid, $pd->approver]);
                    }
                }
                return ['status' => false, 'msg' => $appstatus['msg'], 'data' => []];
            } else {
                $update = $this->coreFunctions->sbcupdate('obapplication', $tempdata, ['line' => $row['line']]);
                $config['params']['doc'] = 'OBAPPLICATION';
                if ($update) {
                    $re_status = true;
                    $msg = "Successfully " . $label;
                    if ($companyid == 53 || $companyid == 51) { // camera| ulitc
                        $appname = $this->coreFunctions->datareader("select clientname as value from client where email=?", [$config['params']['user']]);

                        $query = "select ob.line,ob.empid,ifnull(app3.clientname,app4.clientname) as appname2,
						 client.email as username,emp.email
						 from obapplication as ob
						 left join client on client.clientid=ob.empid
                         left join client as app3 on app3.email=ob.approvedby2 and app3.email <> ''
                         left join client as app4 on app4.email=ob.disapprovedby2 and app4.email <> ''
						 left join employee as emp on emp.empid = ob.empid
						 where ob.empid = $empid and ob.line = $line ";
                        $empdata = $this->coreFunctions->opentable($query);



                        $query = "select app.line, app.doc,app.clientid,emp.email,cl.email as username,app.approver from pendingapp as app
                        left join employee as emp on emp.empid = app.clientid
                        left join client as cl on cl.clientid = emp.empid
                        where doc = 'OB' and app.line = $line ";
                        $app_data = $this->coreFunctions->opentable($query);
                        $config['params']['doc'] = 'OBAPPLICATION';
                        $params = [];
                        $params['title'] = 'OB APPLICATION';
                        $params['clientname'] = $empname;
                        $params['line'] = $line;
                        $params['scheddate'] = $scheddate . " (" . $dayname . ")";
                        $params['dateid'] = $dateid;
                        $params['rem'] = $emprem;
                        $params['reason1'] = $rem2;
                        $params['reason2'] = $remarkslast;

                        $params['datetime'] = $datetime;
                        $params['datetime2'] = $datetime2;
                        $params['type'] = $type;
                        $params['location'] = $location;
                        $params['companyid'] = $companyid;
                        $params['appstatus'] = $label;
                        $params['muduletype'] = 'OB';

                        $l_data = $app_data;
                        if ($lastapp) {
                            $l_data = $empdata;
                        }
                        // var_dump($l_data);
                        foreach ($l_data as $key => $value) {
                            if (!empty($l_data[$key]->email)) {
                                $params['approver'] = $l_data[$key]->username;
                                $params['email'] = $l_data[$key]->email;
                                $params['appname'] = $appname;
                                $params['isapp'] = isset($value->approver) ? $value->approver : '';
                                if ($lastapp) {
                                    $params['approvedstatus'] = 'Application ' . $label;
                                    if ($stat != "") {
                                        $params['title'] = 'OB APPLICATION RESULT';
                                        $params['appname2'] = isset($value->appname2) && $value->appname2 != null ? $value->appname2 : '';
                                    }
                                }
                                // $result = $this->linkemail->createOBEmail($params);
                                $result = $this->linkemail->weblink($params,$config);
                                if (!$result['status']) {
                                    $msg = $result['msg'];
                                    $re_status = false;
                                }
                            }
                        }
                        if (!$re_status) {
                            return ['status' => $re_status, 'msg' => $msg, 'data' => []];
                        }
                    }
                } else {
                    $this->coreFunctions->execqry("delete from pendingapp where doc='OB' and line=" . $row['line'], 'delete');
                    if (!empty($pendingdata)) {
                        foreach ($pendingdata as $pd) {
                            $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid,approver) values(?, ?, ?, ?,?)", 'insert', [$pd->trno, $pd->line, $pd->doc, $pd->clientid, $pd->approver]);
                        }
                    }
                    return ['status' => false, 'msg' => 'Error updating record, please try again.', 'data' => []];
                }
            }

            
            $this->logger->sbcmasterlog($row['line'], $config, $label . $row['type'] . ' (' . $row['clientname'] . ') - ' . $row['dateid'] . ' ' . $rem2 . ' - ' . $remarkslast);
            return ['status' => true, 'msg' => 'Successfully ' . $label, 'data' => [], 'reloadsbclist' => true, 'action' => 'gapplications', 'deleterow' => true];
        } else {
            return ['status' => false, 'msg' => 'Already approved.', 'data' => []];
        }
    }
} //end class
