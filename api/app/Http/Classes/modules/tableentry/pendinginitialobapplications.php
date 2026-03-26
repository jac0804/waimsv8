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

class pendinginitialobapplications
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'PENDING INITIAL OB APPLICATIONS';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $linkemail;
    public $tablelogs = 'payroll_log';
    public $style = 'width:90%;max-width:90%;';
    public $issearchshow = true;
    public $showclosebtn = true;
    public $obfields = ['initialstatus', 'initialappdate', 'initialapprovedby', 'initial_remarks', 'initialstatus2', 'initialappdate2', 'initialapprovedby2', 'initial_remarks2'];

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
        $isapp = $config['params']['row']['approver'];
        $companyid = $config['params']['companyid'];
        $approver = $this->coreFunctions->getfieldvalue("employee", "$isapp", "empid=?", [$config['params']['adminid']]);
        $url = 'App\Http\Classes\modules\payroll\\' . 'obapplication';
        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename= '$doc'");
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
        $cols = ['action', 'lblforapp', 'clientname', 'dateid', 'dateid2', 'type', 'rem', 'rem2',  'contact', 'remarkslast'];
        foreach ($cols as $key => $value) {
            $$value = $key;
        }
        $tab = [$this->gridname => ['gridcolumns' => $cols]];
        $stockbuttons = ['approve', 'disapprove'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Name';
        $obj[0][$this->gridname]['columns'][$clientname]['style'] = 'width:200px;min-width:200px;';
        $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$dateid]['style'] = 'width:150px;min-width:150px;';
        $obj[0][$this->gridname]['columns'][$dateid]['type'] = 'label';

        $obj[0][$this->gridname]['columns'][$type]['label'] = 'Type';
        $obj[0][$this->gridname]['columns'][$type]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$rem]['label'] = 'Reason';
        $obj[0][$this->gridname]['columns'][$rem]['type'] = 'label';

        $obj[0][$this->gridname]['columns'][$rem2]['type'] = 'input';
        $obj[0][$this->gridname]['columns'][$rem2]['label'] = 'First Approver Reason';
        $obj[0][$this->gridname]['columns'][$lblforapp]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$contact]['type'] = 'label';

        $obj[0][$this->gridname]['columns'][$dateid2]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$dateid2]['label'] = 'Time Out';
        $obj[0][$this->gridname]['columns'][$dateid2]['style'] = 'width:150px;min-width:150px;';
        $obj[0][$this->gridname]['columns'][$dateid]['label'] = 'Time In';

        if (count($approversetup) == 1 || $both) {
            $obj[0][$this->gridname]['columns'][$contact]['type'] = 'coldel';
            if ($both) {
                $obj[0][$this->gridname]['columns'][$rem2]['type'] = 'coldel';
                $obj[0][$this->gridname]['columns'][$remarkslast]['readonly'] = false;
            } else {
                $obj[0][$this->gridname]['columns'][$rem2]['readonly'] = false;
                $obj[0][$this->gridname]['columns'][$remarkslast]['type'] = 'coldel';
            }
        } else {

            if (($approver && $approversetup[0] == $isapp)) {
                $obj[0][$this->gridname]['columns'][$rem2]['readonly'] = false;
                // $obj[0][$this->gridname]['columns'][$remarkslast]['readonly'] = true;
                $obj[0][$this->gridname]['columns'][$remarkslast]['type'] = 'coldel';
                $obj[0][$this->gridname]['columns'][$contact]['type'] = 'coldel';
            } else if (($approver && count($approversetup) > 1)) {
                if ($approversetup[1] == $isapp) {
                    $obj[0][$this->gridname]['columns'][$rem2]['readonly'] = true;
                    $obj[0][$this->gridname]['columns'][$remarkslast]['readonly'] = false;
                }
            } else {
                $obj[0][$this->gridname]['columns'][$rem2]['readonly'] = true;
                $obj[0][$this->gridname]['columns'][$remarkslast]['readonly'] = true;
            }
        }


        if ($companyid == 53) {
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
        $companyid  = $config['params']['companyid'];
        $approver = $config['params']['row']['approver'];
        $labelapp = 'FOR APPROVER';
        $labelsupp = 'FOR SUPERVISOR';
        if ($companyid == 53) { //camera
            $labelapp = 'FOR HR/PAYROLL APPROVER';
            $labelsupp = 'FOR HEAD DEPT. APPROVER';
        }
        $qry = "select ob.line, client.clientname, date_format(ob.dateid, '%Y-%m-%d %h:%i %p') as dateid,date_format(ob.dateid2, '%Y-%m-%d %h:%i %p') as dateid2,
            ob.type, ob.rem, ob.initial_remarks2 as rem2,
            ob.initial_remarks as remarkslast, ob.approvedby2, emp.supervisorid, ob.ontrip,emp.email,ob.location,
            m.modulename as doc, m.sbcpendingapp,date(ob.scheddate) as scheddate,dayname(ob.scheddate) as dayname, 
            date_format(ob.dateid, '%Y-%m-%d %h:%i %s') as datetime,date_format(ob.dateid2, '%Y-%m-%d %h:%i %s') as datetime2,ob.empid,
            iniapp.clientname as contact,date(ob.createdate) as createdate,
            case when p.approver = 'isapprover' then '$labelapp' else '$labelsupp' end as lblforapp, p.approver
            from obapplication as ob
            left join client on client.clientid=ob.empid
            left join pendingapp as p on p.line=ob.line and p.doc='INITIALOB'
            left join employee as emp on emp.empid = ob.empid
            left join moduleapproval as m on m.modulename=p.doc
            left join client as iniapp on iniapp.email=ob.initialapprovedby2 and iniapp.email <> ''
            where p.clientid=" .  $adminid . " and p.approver  = '$approver'
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
        $isapp = $row['approver'];
        $appname2 = $row['contact'];



        $admin = $config['params']['adminid'];
        $companyid = $config['params']['companyid'];
        $approver = $this->coreFunctions->getfieldvalue("employee", "$isapp", "empid=?", [$admin]);
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
            $status = $this->coreFunctions->datareader("select approvedate as value from obapplication where line=? and initialappdate is not null", [$row['line']]);
        } else {
            $obstatus = 'D';
            $label = 'Disapproved ';
            $status = $this->coreFunctions->datareader("select disapprovedate as value from obapplication where line=? and initialappdate is not null", [$row['line']]);
        }
        if (!$status) {
            $lastapp = false;
            $stat = '';
            foreach ($approversetup as $key => $value) {
                if (count($approversetup) > 1) {
                    if ($both) {
                        goto approved;
                    }

                    if ($key == 0) {
                        if ($value == $isapp && $approver) {
                            if ($obstatus == 'D') {
                                if ($rem2 == '') {
                                    return ['status' => false, 'msg' => "Initial Reason is empty.", 'data' => []];
                                }
                            }
                            $data = [
                                'initialstatus2' => $obstatus,
                                'initialappdate2' => $this->othersClass->getCurrentTimeStamp(),
                                'initialapprovedby2' => $config['params']['user'],
                                'initial_remarks2' => $rem2
                            ];
                            if ($obstatus == 'D') {
                                $data['initialstatus'] = 'D';
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
                        $data = [
                            'initialstatus' => $obstatus,
                            'initialappdate' => $this->othersClass->getCurrentTimeStamp(),
                            'initialapprovedby' => $config['params']['user'],
                            'initial_remarks' => $remarkslast
                        ];
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
            $tempdata['empid'] = $empid;

            $pendingdata = $this->coreFunctions->opentable("select * from pendingapp where line=" . $row['line'] . " and doc='INITIALOB'");
            $this->coreFunctions->execqry("delete from pendingapp where doc='INITIALOB' and line=" . $row['line'], 'delete');

            $stat = '';
            $appstatus = ['status' => true];
            if ($stat == "") {
                if (!$lastapp) {
                    $appstatus = $this->othersClass->insertUpdatePendingapp(0, $line, 'INITIALOB', $tempdata, $url, $config, 0, true);
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
                $update = $this->coreFunctions->sbcupdate('obapplication', $tempdata, ['line' => $row['line']]);
                if ($update) {

                    $params = [];

                    if ($companyid == 53) { //camera|ulitc
                        $appname = $this->coreFunctions->datareader("select clientname as value from client where email=?", [$config['params']['user']]);

                        $query = "select emp.empid,emp.email,client.email as username,'' as approver
                    from employee  as emp
                    left join client on client.clientid = emp.empid
                    where clientid = $empid ";

                        if (!$lastapp) {
                            $query = "select cl.email as username, emp.email,app.approver from pendingapp as app 
                    left join employee as emp on emp.empid = app.clientid
                    left join client as cl on cl.clientid = emp.empid 
                    where app.line = $line and app.doc = 'INITIALOB'";
                        }

                        $l_data = $this->coreFunctions->opentable($query);


                        $params['title'] = 'INITIAL OB APPLICATIONS';
                        $params['clientname'] = $empname;
                        $params['line'] = $line;
                        $params['scheddate'] = $scheddate . " (" . $dayname . ")";
                        $params['dateid'] = $dateid;
                        $params['rem'] = $emprem;
                        $params['reason1'] = $remarkslast;
                        $params['reason2'] = $rem2;

                        $params['datetime'] = $datetime;
                        $params['datetime2'] = $datetime2;

                        $params['type'] = $type;
                        $params['location'] = $location;
                        $params['companyid'] = $companyid;
                        $params['muduletype'] = 'OB_INITIAL';
                        $params['appstatus'] = 'Application ' . $label;

                        foreach ($l_data as $key => $value) {
                            if (!empty($value->email)) {

                                $params['approver'] = $value->username;
                                $params['email'] = $value->email;
                                $params['appname'] = $appname;
                                $params['isapp'] = isset($value->approver) ? $value->approver : '';
                                if ($lastapp) {
                                    $params['title'] = 'INITIAL OB APPLICATIONS RESULT';
                                    $params['approvedstatus'] = 'Application ' . $label;
                                    $params['appname2'] = $appname2 != "" ? $appname2 : '';
                                }
                                // $result = $this->linkemail->createOBInitialEmail($params);
                                $result = $this->linkemail->weblink($params,$config);
                                if (!$result['status']) {
                                    return ['status' => false, 'msg' => '' . $result['msg']];
                                }
                            }
                        }
                    }
                } else {
                    if (!empty($pendingdata)) {
                        foreach ($pendingdata as $pd) {
                            $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid,approver) values(?, ?, ?, ?,?)", 'insert', [$pd->trno, $pd->line, $pd->doc, $pd->clientid, $pd->approver]);
                        }
                    }
                    return ['status' => false, 'msg' => 'Error updating record, Please try again.', 'data' => []];
                }
            }




            $config['params']['doc'] = 'INITIALOB';
            $this->logger->sbcmasterlog($row['line'], $config, $label . $row['type'] . ' (' . $row['clientname'] . ') - ' . $row['dateid'] . ' ' . $rem2 . ' - ' . $remarkslast);
            return ['status' => true, 'msg' => 'Successfully ' . $label, 'data' => [], 'reloadsbclist' => true, 'action' => 'gapplications', 'deleterow' => true];
        } else {
            return ['status' => false, 'msg' => 'Already approved.', 'data' => []];
        }
    }
} //end class
