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

class pendingchangeshiftapplication
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'PENDING CHANGESHIFT APPLICATIONS';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $linkemail;
    public $tablelogs = 'payroll_log';
    public $style = 'width:90%;max-width:90%;';
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

    public function getAttrib()
    {
        $attrib = array(
            'load' => 5036
        );
        return $attrib;
    }

    public function createTab($config)
    {
        $doc = $config['params']['row']['doc'];
        $isapp = $config['params']['row']['approver'];
        $companyid = $config['params']['companyid'];
        $approver = $this->coreFunctions->getfieldvalue("employee", "$isapp", "empid=?", [$config['params']['adminid']]);
        $url = 'App\Http\Classes\modules\payroll\\' . 'changeshiftapplication';
        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='CHANGESHIFT'");
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
        $cols = ['action', 'lblforapp', 'clientname', 'dateid', 'daytype', 'schedin', 'schedout', 'rem', 'rem2', 'contact', 'remarkslast'];
        foreach ($cols as $key => $value) {
            $$value = $key;
        }
        $label = "Remarks";
        if ($companyid == 53) {
            $label = "Reason";
        }
        $tab = [$this->gridname => ['gridcolumns' => $cols]];
        $stockbuttons = ['approve', 'disapprove'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Name';
        $obj[0][$this->gridname]['columns'][$clientname]['style'] = 'width:200px;min-width:200px;';
        $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$dateid]['style'] = 'width:150px;min-width:150px;';
        $obj[0][$this->gridname]['columns'][$dateid]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$daytype]['label'] = 'Day Type';
        $obj[0][$this->gridname]['columns'][$daytype]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$rem]['label'] = $label;
        $obj[0][$this->gridname]['columns'][$rem]['type'] = 'label';

        $obj[0][$this->gridname]['columns'][$rem2]['type'] = 'input';
        $obj[0][$this->gridname]['columns'][$rem2]['label'] = 'First Approver ' . $label;
        $obj[0][$this->gridname]['columns'][$lblforapp]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$schedin]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$schedout]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$contact]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$contact]['label'] = 'First Approver';
        $obj[0][$this->gridname]['columns'][$remarkslast]['label'] = 'Second Approver ' . $label;

        if (count($approversetup) == 1) {
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
        if ($companyid == 53) { // camera
            if ($approversetup[0] == $isapp) {
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
        $approver = $config['params']['row']['approver'];
        $companyid = $config['params']['companyid'];
        $labelapprover = "FOR APPROVER";
        $labelsupervisor = "FOR SUPERVISOR";
        if ($companyid == 53) {
            $labelapprover = "FOR HR/PAYROLL APPROVER";
            $labelsupervisor = "FOR HEAD DEPT. APPROVER";
        }

        $query = "select cs.line, client.client,client.clientname, date(cs.dateid) as dateid, cs.empid,emp.email,cl.clientname as contact,
        date_format(cs.schedin, '%Y-%m-%d %h:%i %p') as schedin,date_format(cs.schedout, '%Y-%m-%d %h:%i %p') as schedout,
        cs.rem,cs.disapproved_remarks as remarkslast,cs.disapproved_remarks2 as rem2,cs.status,cs.status2,
        date_format(tm.schedin, '%Y-%m-%d %h:%i %p') as orgschedin,date_format(tm.schedout, '%Y-%m-%d %h:%i %p') as orgschedout,cs.orgdaytype,cs.daytype,p.doc,
        case when p.approver = 'isapprover' then '$labelapprover' else '$labelsupervisor' end as lblforapp,p.approver,
        m.modulename as doc, m.sbcpendingapp
        from changeshiftapp as cs 
        left join timecard as tm on tm.empid = cs.empid and tm.dateid = date(cs.dateid)
        left join pendingapp as p on p.line=cs.line and p.doc='CHANGESHIFT'
        left join client on client.clientid=cs.empid
        left join employee as emp on emp.empid = cs.empid
        left join moduleapproval as m on m.modulename=p.doc
        left join client as cl on cl.email = cs.approvedby2 and cl.email <> ''
        where p.clientid=" .  $adminid . " and p.approver = '$approver'
        order by dateid, client.clientname";

        $data = $this->coreFunctions->opentable($query);
        return $data;
    }
    public function updateapp($config, $status)
    {
        $row = $config['params']['row'];
        $doc = $row['doc'];

        $remarkslast = $row['remarkslast'];
        $rem2 = $row['rem2'];
        $line = $row['line'];
        $empname = $row['clientname'];
        $dateid = $row['dateid'];
        $schedin = $row['schedin'];
        $schedout = $row['schedout'];
        $orgschedin = $row['orgschedin'];
        $orgschedout = $row['orgschedout'];

        $emprem = $row['rem'];
        $email = $row['email'];
        $orgdaytype = $row['orgdaytype'];
        $daytype = $row['daytype'];

        $empid = $row['empid'];
        $isapp = $row['approver'];


        $admin = $config['params']['adminid'];
        $companyid = $config['params']['companyid'];
        $approver = $this->coreFunctions->getfieldvalue("employee", "$isapp", "empid=?", [$admin]);
        $url = 'App\Http\Classes\modules\payroll\\' . 'changeshiftapplication';
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
        switch ($status) {
            case 'A':
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
            $lastapp = false;
            $stat = 0;
            foreach ($approversetup as $key => $value) {
                if (count($approversetup) > 1) {
                    if ($both) {
                        goto approved;
                    }
                    if ($key == 0) {
                        if ($value == $isapp && $approver) {
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
                                // $data['status'] = 2;  //pag na disapproved update na din ito last status
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
                        $lastapp = true;
                        $stat = $cstatus;
                        $data = [
                            'status' => $cstatus,
                            'disapproved_remarks' => $remarkslast
                        ];

                        if ($cstatus == 1) { // approved
                            $data['approvedby'] = $config['params']['user'];
                            $data['approveddate'] = $this->othersClass->getCurrentTimeStamp();
                        } else { // disapproved
                            if ($remarkslast == '') {
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
            $tempdata['empid'] = $empid;

            $pendingdata = $this->coreFunctions->opentable("select * from pendingapp where line=" . $row['line'] . " and doc='CHANGESHIFT'");
            $this->coreFunctions->execqry("delete from pendingapp where doc='CHANGESHIFT' and line=" . $row['line'], 'delete');

            switch ($companyid) {
                case 53: //camera
                case 51: //ulitc
                    $checklvl = true;
                    break;
                default:
                    $checklvl = false;
                    break;
            }
            $appstatus = ['status' => true];
            if (!$lastapp) {
                $appstatus = $this->othersClass->insertUpdatePendingapp(0, $row['line'], 'CHANGESHIFT', $tempdata, $url, $config, 0,  $checklvl, false);
            }
            $config['params']['doc'] = 'CHANGESHIFT';
            if (!$appstatus['status']) {
                if (!empty($pendingdata)) {
                    foreach ($pendingdata as $pd) {
                        $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid,approver) values(?, ?, ?, ?,?)", 'insert', [$pd->trno, $pd->line, $pd->doc, $pd->clientid, $pd->approver]);
                    }
                }
                return ['status' => false, 'msg' => $appstatus['msg'], 'data' => []];
            } else {
                // sending email
                $update = $this->coreFunctions->sbcupdate('changeshiftapp', $tempdata, ['line' => $row['line']]);
                if ($update) {
                    if ($companyid == 53 || $companyid == 51) { //camera|ulitc
                        $qry = " select emp.email,client.email as username,app.approver
                             from changeshiftapp as cs
							 left join pendingapp as app on app.line = cs.line
							 left join employee as emp on emp.empid = app.clientid
							 left join client on client.clientid=app.clientid
							 left join client as app1 on app1.email=cs.approvedby2 and app1.email <> ''
							 left join client as app2 on app2.email=cs.disapprovedby2 and app2.email <> ''
							 where app.doc = 'CHANGESHIFT' and app.line = " . $row['line'] . " ";


                        $appname = $this->coreFunctions->datareader("select clientname as value from client where email=?", [$config['params']['user']]);
                        // direct to employee
                        $query = "select emp.email,client.email as username,ifnull(app1.clientname,app2.clientname) as appname2
                             from changeshiftapp as cs
							 left join employee as emp on emp.empid = cs.empid
							 left join client on client.clientid=cs.empid
							 left join client as app1 on app1.email=cs.approvedby2 and app1.email <> ''
							 left join client as app2 on app2.email=cs.disapprovedby2 and app2.email <> ''
							 where  cs.empid = $empid and cs.line = " . $row['line'] . "";



                        $l_query = $qry;
                        if ($lastapp) {
                            $l_query = $query;
                        }
                        $data = $this->coreFunctions->opentable($l_query);
                        $params = [];
                        $params['clientname'] = $empname;
                        $params['line'] = $line;
                        $params['dateid'] = $dateid;
                        $params['schedin'] = $schedin;
                        $params['schedout'] = $schedout;
                        $params['orgschedin'] = $orgschedin;
                        $params['orgschedout'] = $orgschedout;

                        $params['remlast'] = $remarkslast;
                        $params['reason1'] = $rem2;

                        $params['remarks'] = $emprem;
                        $params['orgdaytype'] = $orgdaytype;
                        $params['daytype'] = $daytype;
                        $params['appstatus'] = $label;
                        $params['companyid'] = $companyid;
                        $params['muduletype'] = 'SCHED';

                        foreach ($data as $key => $value) {
                            if (!empty($data[$key]->email)) {
                                $params['approver'] = $data[$key]->username;
                                $params['email'] = $data[$key]->email;
                                $params['title'] = 'CHANGE SHIFT APPLICATION';
                                $params['appname'] = $appname;
                                $params['isapp'] = isset($value->approver) ? $value->approver : '';
                                if ($lastapp) {
                                    $params['approvedstatus'] = 'Application ' . $label;
                                    $params['title'] = 'CHANGE SHIFT APPLICATION RESULT';
                                    if ($stat != 0) {
                                        if (isset($data[$key]->appname2) == null) {
                                            $params['appname2'] = '';
                                        } else {
                                            $params['appname2'] = $data[$key]->appname2;
                                        }
                                    }
                                }

                                // $result = $this->linkemail->createChangeSchedEmail($params);
                                $result = $this->linkemail->weblink($params,$config);
                                if (!$result['status']) {
                                    return ['status' => false, 'msg' => '' . $result['msg']];
                                }
                            }
                        }
                    }
                } else {
                    $this->coreFunctions->execqry("delete from pendingapp where doc='CHANGESHIFT' and line=" . $row['line'], 'delete');
                    if (!empty($pendingdata)) {
                        foreach ($pendingdata as $pd) {
                            $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid,approver) values(?, ?, ?, ?,?)", 'insert', [$pd->trno, $pd->line, $pd->doc, $pd->clientid, $pd->approver]);
                        }
                    }
                    return ['status' => false, 'msg' => 'Error updating record, please try again.', 'data' => []];
                }
            }



            $this->logger->sbcmasterlog($row['line'], $config, $label . $daytype . ' (' . $row['clientname'] . ') - ' . $row['dateid'] . ' ' . $rem2 . ' ' . $remarkslast);
            return ['status' => true, 'msg' => 'Successfully ' . $label, 'data' => [], 'reloadsbclist' => true, 'action' => 'gapplications', 'deleterow' => true];
        } else {
            return ['status' => false, 'msg' => 'Already approved.', 'data' => []];
        }
    }
} //end class
