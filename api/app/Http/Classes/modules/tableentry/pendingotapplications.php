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

class pendingotapplications
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'PENDING OT APPLICATIONS';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $linkemail;
    public $tablelogs = 'payroll_log';
    public $style = 'width:90%;max-width:90%;';
    public $issearchshow = true;
    public $showclosebtn = true;
    public $otfields = ['otstatus', 'otstatus2', 'remarks', 'apothrs', 'batchid', 'approvedby', 'approvedate', 'approvedate2', 'approvedby2', 'apothrsextra', 'apndiffothrs', 'disapprovedby', 'disapprovedate', 'disapprovedby2', 'disapprovedate2', 'disapproved_remarks2'];


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
            'load' => 4841
        );
        return $attrib;
    }

    public function createTab($config)
    {
        $companyid = $config['params']['companyid'];
        $doc = $config['params']['row']['doc'];
        $isapp = $config['params']['row']['approver'];

        $approver = $this->coreFunctions->getfieldvalue("employee", "$isapp", "empid=?", [$config['params']['adminid']]);
        $url = 'App\Http\Classes\modules\payroll\\' . 'otapplicationadv';
        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='OT'");
        $both = false;
        if ($approversetup == '') {
            $approversetup = app($url)->approvers($config['params']);
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
        $cols = ['action', 'lblforapp', 'clientname', 'createdate', 'scheddate', 'daytype', 'rem', 'schedstarttime', 'schedendtime', 'othrs', 'apothrs', 'othrsextra', 'apothrsextra', 'ndiffot', 'apndiffothrs', 'rem2', 'contact', 'remarkslast'];
        $tab = [$this->gridname => ['gridcolumns' => $cols]];
        foreach ($cols as $key => $value) {
            $$value = $key;
        }
        $label = "Remarks";
        if ($companyid == 53) {
            $label = "Reason";
        }
        $stockbuttons = ['approve', 'disapprove'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$lblforapp]['style'] = 'width:100px;min-width:100px;';
        $obj[0][$this->gridname]['columns'][$lblforapp]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Name';
        $obj[0][$this->gridname]['columns'][$clientname]['style'] = 'width:200px;min-width:200px;';
        $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$scheddate]['style'] = 'width:120px;min-width:120px;';
        $obj[0][$this->gridname]['columns'][$scheddate]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$othrs]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$othrsextra]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$ndiffot]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$daytype]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$createdate]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$rem]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$rem]['label'] = $label;
        // $obj[0][$this->gridname]['columns'][$rem2]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$rem2]['label'] = 'First Approver ' . $label;
        $obj[0][$this->gridname]['columns'][$remarkslast]['label'] = 'Approver Remarks';

        $obj[0][$this->gridname]['columns'][$schedstarttime]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$schedstarttime]['label'] = 'Date Time/ Time In';
        $obj[0][$this->gridname]['columns'][$schedstarttime]['style'] = 'width:120px;min-width:120px;';

        $obj[0][$this->gridname]['columns'][$schedendtime]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$schedendtime]['label'] = 'Date Time/ Time Out';
        $obj[0][$this->gridname]['columns'][$schedendtime]['align'] = 'text-left';
        $obj[0][$this->gridname]['columns'][$schedendtime]['style'] = 'width:120px;min-width:120px;';

        $obj[0][$this->gridname]['columns'][$contact]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$contact]['label'] = 'First Approver';
        $obj[0][$this->gridname]['columns'][$remarkslast]['label'] = 'Second Approver ' . $label;



        if (count($approversetup) == 1 || $both) {
            $obj[0][$this->gridname]['columns'][$remarkslast]['readonly'] = false;
            $obj[0][$this->gridname]['columns'][$contact]['type'] = 'coldel';
            $obj[0][$this->gridname]['columns'][$rem2]['type'] = 'coldel';
            $obj[0][$this->gridname]['columns'][$remarkslast]['label'] = 'Approver Remarks';
        } else {
            if (($approver && $approversetup[0] == $isapp)) {
                $obj[0][$this->gridname]['columns'][$rem2]['readonly'] = false;
                // $obj[0][$this->gridname]['columns'][$remarkslast]['readonly'] = true;

                $obj[0][$this->gridname]['columns'][$remarkslast]['type'] = 'coldel';
                $obj[0][$this->gridname]['columns'][$contact]['type'] = 'coldel';

                // if ($companyid != 53) {
                // $obj[0][$this->gridname]['columns'][$apndiffothrs]['type'] = 'coldel';
                // $obj[0][$this->gridname]['columns'][$apothrs]['type'] = 'coldel';
                // $obj[0][$this->gridname]['columns'][$apothrsextra]['type'] = 'coldel';
                // }
            } else if (($approver && $approversetup[1] == $isapp)) {
                $obj[0][$this->gridname]['columns'][$rem2]['readonly'] = true;
                $obj[0][$this->gridname]['columns'][$remarkslast]['readonly'] = false;
            } else {
                $obj[0][$this->gridname]['columns'][$rem2]['readonly'] = true;
                $obj[0][$this->gridname]['columns'][$remarkslast]['readonly'] = true;
            }
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
        $approver = $config['params']['row']['approver'];
        $companyid = $config['params']['companyid'];
        $labelapprover = "FOR APPROVER";
        $labelsupervisor = "FOR SUPERVISOR";
        if ($companyid == 53) {
            $labelapprover = "FOR HR/PAYROLL APPROVER";
            $labelsupervisor = "FOR HEAD DEPT. APPROVER";
        }


        $qry = "select ot.line, client.client,client.clientid as empid, client.clientname, date(ot.dateid) as dateid, ot.othrs,0 as divid,
            if(ot.apothrs <> 0,ot.apothrs,ot.othrs) as apothrs,ot.remarks as remarkslast,ot.disapproved_remarks2 as rem2,ot.rem,ot.ndiffhrs, ot.ndiffhrs as apndiffhrs,ot.othrsextra,if(ot.apothrsextra <> 0,ot.apothrsextra,ot.othrsextra) as apothrsextra,ot.ndiffothrs as ndiffot,
            if(ot.apndiffothrs <> 0,ot.apndiffothrs,ot.ndiffothrs) as apndiffothrs,date(ot.createdate) as createdate,date(ot.scheddate) as scheddate,dayname(ot.scheddate) as dayname,
            time(ot.ottimein) as ottimein, time(ot.ottimeout) as ottimeout, 'OT' as doc,
            date_format(ot.ottimein, '%Y-%m-%d %h:%i %p') as schedin, date_format(ot.ottimeout, '%Y-%m-%d %h:%i %p') as schedout,ot.daytype,
            m.modulename as doc, m.sbcpendingapp,date_format(ottimein, '%Y-%m-%d %h:%i %p') as schedstarttime, date_format(ottimeout, '%Y-%m-%d %h:%i %p') as schedendtime,
            case when p.approver = 'isapprover' then '$labelapprover' else '$labelsupervisor' end as lblforapp, p.approver,app.clientname as contact

            from otapplication as ot 
            left join client on client.clientid=ot.empid
            left join pendingapp as p on p.line=ot.line and p.doc='OT'
            left join moduleapproval as m on m.modulename=p.doc
            left join client as app on app.email = ot.approvedby2 and  app.email <> ''
            where p.clientid=" . $adminid . " and p.approver = '$approver' order by dateid, client.clientname";
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
        $line = $row['line'];
        $othrs = $row['othrs'];
        $apothrs = $row['apothrs'];

        $othrsextra = $row['othrsextra'];
        $apothrsextra = $row['apothrsextra'];


        $ndiffothrs = $row['ndiffot'];
        $apndiffothrs = $row['apndiffothrs'];


        $schedin = $row['schedin'];
        $schedout = $row['schedout'];
        $clientname = $row['clientname'];

        $dayname = $row['dayname'];
        $scheddate = $row['scheddate'];
        $rem = $row['rem'];
        $createdate = $row['createdate'];
        $rem2 = $row['rem2'];
        $remarkslast = $row['remarkslast'];
        $isapp = $row['approver'];
        if ($isapp == '' || $isapp == null) $isapp = $this->coreFunctions->datareader("select approver as value from pendingapp where doc='OT' and line=" . $row['line']);


        $doc = $row['doc'];
        $empid = $row['empid'];
        $admin = $config['params']['adminid'];
        $companyid = $config['params']['companyid'];
        $approver = $this->coreFunctions->getfieldvalue("employee", "$isapp", "empid=?", [$admin]);
        $url = 'App\Http\Classes\modules\payroll\\' . 'otapplicationadv';
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
                $otstatus = 2;
                $label = 'Approved ';
                $status = $this->coreFunctions->datareader("select otstatus as value from otapplication where line=? and otstatus=2", [$row['line']]);
                break;
            default:
                $otstatus = 3;
                $label = 'Disapproved ';
                $status = $this->coreFunctions->datareader("select otstatus as value from otapplication where line=? and otstatus=3", [$row['line']]);
                break;
        }
        if (!$status) {
            $label_reason = 'Remarks';
            $bothapprover = $lastapp = false;
            $lstat = 0;

            if ($companyid != 53) {
                if ($apothrs <  8) {
                    if ($apothrsextra > $othrsextra) {
                        return ['status' => false, 'msg' => 'Approved OT > 8 Hours is greater than Applied OT > 8', 'data' => []];
                    }
                } else {
                    if ($apothrsextra > $othrsextra) {
                        return ['status' => false, 'msg' => 'Approved OT > 8 Hours is greater than Applied OT > 8', 'data' => []];
                    }
                }
                if ($apothrs > $othrs) {
                    return ['status' => false, 'msg' => 'Approved OT is greater than Applied OT', 'data' => []];
                }
                if ($apndiffothrs > $ndiffothrs) {
                    return ['status' => false, 'msg' => 'Approved N-DIFF OT Hrs is greater than Applied N-DIFF OT Hrs', 'data' => []];
                }
            }

            foreach ($approversetup as $key => $value) {
                if (count($approversetup) > 1) {

                    if ($key == 0) {
                        if ($both) {
                            goto approved;
                        }
                        if ($value == $isapp && $approver) {
                            $data = [
                                'otstatus2' => $otstatus
                            ];

                            $data['apothrs'] = $row['apothrs'];
                            $data['apothrsextra'] = $row['apothrsextra'];
                            $data['apndiffothrs'] = $row['apndiffothrs'];

                            if ($otstatus == 2) {
                                $data['approvedby2'] = $config['params']['user'];
                                $data['approvedate2'] = $this->othersClass->getCurrentTimeStamp();
                            } else {
                                if ($row['rem2'] == '') {
                                    return ['status' => false, 'msg' => 'Approver ' . $label_reason . ' is empty.', 'data' => []];
                                }
                                $data['disapprovedby2'] = $config['params']['user'];
                                $data['disapprovedate2'] = $this->othersClass->getCurrentTimeStamp();
                                // $data['otstatus'] = 3;  //pag na disapproved update na din ito last status
                                $lastapp = true;
                            }
                            $data['disapproved_remarks2'] = $row['rem2'];
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
                        $lstat = $otstatus;
                        if ($otstatus == 2) {
                            $data = [
                                'otstatus' => $otstatus,
                                'remarks' => $row['remarkslast'],
                                'apothrs' => $row['apothrs'],
                                'approvedby' => $config['params']['user'],
                                'approvedate' => $this->othersClass->getCurrentTimeStamp(),
                                'apothrsextra' => $row['apothrsextra'],
                                'apndiffothrs' => $row['apndiffothrs']
                            ];
                        } else {
                            if ($row['remarkslast'] == '') {
                                return ['status' => false, 'msg' => 'Approver ' . $label_reason . ' is empty.', 'data' => []];
                            }
                            $data = [
                                'otstatus' => $otstatus,
                                'remarks' => $row['remarkslast'],
                                'disapprovedby' => $config['params']['user'],
                                'disapprovedate' => $this->othersClass->getCurrentTimeStamp()
                            ];
                        }
                        if ($bothapprover) $data['otstatus2'] = $otstatus;
                        break;
                    }
                }
            }
            $tempdata = [];
            foreach ($this->otfields as $key2) {
                if (isset($data[$key2])) {
                    $tempdata[$key2] = $data[$key2];
                    $tempdata[$key2] = $this->othersClass->sanitizekeyfield($key2, $tempdata[$key2]);
                }
            }
            $tempdata['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $tempdata['editby'] = $config['params']['user'];
            $tempdata['empid'] = $empid;
            $msg = "";
            $pendingdata = $this->coreFunctions->opentable("select * from pendingapp where line=" . $row['line'] . " and doc='OT'");
            $this->coreFunctions->execqry("delete from pendingapp where doc='OT' and line=" . $row['line'], 'delete');

            switch ($companyid) {
                case 53: //camera
                case 51: //ulitc
                    $stats = true;
                    break;
                default:
                    $stats = false;
                    break;
            }
            $appstatus = ['status' => true];
            if (!$lastapp && $otstatus == 2) $appstatus = $this->othersClass->insertUpdatePendingapp(0, $row['line'], 'OT', $tempdata, $url, $config, 0, $stats);
            if (!$appstatus['status']) {
                $msg = $appstatus['msg'];
                goto reinsertpendingapp;
                // return ['status' => false, 'msg' => $appstatus['msg'], 'data' => []];
            } else {
                $update = $this->coreFunctions->sbcupdate("otapplication", $tempdata, ['line' => $row['line']]);
                if ($update) {

                    //sending email
                    if ($companyid == 53 || $companyid == 51) { //camera|ulitc

                        if (!$lastapp) {
                            $query = "
                        	 select ot.line,ot.empid,app.approver,
    						 client.email as username,emp.email
    						 from otapplication as ot
    						 left join pendingapp as app ON app.line = ot.line
    						 left join client on client.clientid=app.clientid
    						 left join employee as emp on emp.empid = app.clientid
    						 where app.doc = 'OT' and app.line = " . $row['line'] . "";
                        } else { // last approved/disapproved to emp 
                            $query = "
                        select cl.email as username,emp.email,
                        ifnull(app1.clientname,disapp2.clientname) as appname2 from otapplication as ot 
                        left join employee as emp on emp.empid = ot.empid
                        left join client as cl on cl.clientid = emp.empid
                        left join client as app on app.clientid= ot.empid and ot.approvedby and cl.email <> ''
    				    left join client as disapp ON disapp.email=ot.disapprovedby and disapp.email <> ''
    					left join client as app1 on app1.email=ot.approvedby2 and app1.email <> ''
                        left join client as disapp2 on disapp2.email=ot.disapprovedby2 and disapp2.email <> ''
                        where emp.empid = $empid and ot.line = " . $row['line'] . "";
                        }
                        $this->coreFunctions->LogConsole($query);
                        $data = $this->coreFunctions->opentable($query);
                        $re_status = true;
                        if (!empty($data)) {
                            $config['params']['doc'] = 'OTAPPLICATIONADV';
                            $params['clientname'] = $clientname;
                            $params['line'] = $line;
                            $params['scheddate'] = $scheddate . " (" . $dayname . ")";
                            $params['ottimein'] = $schedin;
                            $params['ottimeout'] = $schedout;
                            $params['rem'] = $rem;
                            $params['reason1'] = $rem2;
                            $params['remlast'] = $remarkslast;

                            $params['othrs'] = $othrs;
                            $params['othrsextra'] = $othrsextra;
                            $params['ndiffothrs'] = $ndiffothrs;
                            $params['daytype'] = $dayname;
                            $params['appstatus'] = 'Application ' . $label;
                            $params['createdate'] = $createdate;
                            $params['companyid'] = $companyid;
                            $params['muduletype'] = 'OT';
                            $appname = $this->coreFunctions->datareader("select clientname as value from client where email=?", [$config['params']['user']]);
                            foreach ($data as $key => $value) {
                                if (!empty($data[$key]->email)) {
                                    $params['approver'] = $data[$key]->username;
                                    $params['email'] = $data[$key]->email;
                                    $params['title'] = 'OT APPLICATION';
                                    if ($lastapp) {
                                        $params['title'] = 'OT APPLICATION RESULT';
                                        $params['approvedstatus'] = 'Application ' . $label;
                                        $params['isapp'] = '';
                                        if ($lstat != 0) {
                                            $params['appname'] = $appname;
                                            if (isset($value->appname2) && $value->appname2 == null) {
                                                $params['appname2'] = '';
                                            } else {
                                                $params['appname2'] = $value->appname2;
                                            }
                                        } else {
                                            $params['appname'] = $appname;
                                        }
                                    } else {
                                        $params['isapp'] = $value->approver;
                                        $params['appname'] = $appname;
                                    }
                                    //$result = $this->linkemail->createOTEmail($params);
                                    $result = $this->linkemail->weblink($params, $config);
                                    if (!$result['status']) {
                                        $msg = $result['msg'];
                                        $re_status = false;
                                    }
                                }
                            }
                            if (!$re_status) {
                                return ['status' => $re_status, 'msg' => $msg, 'data' => []];
                            }
                        } else {
                            return ['row' => [], 'status' => false, 'msg' => 'Please advise admin that no approver is set.', 'backlisting' => true];
                        }
                    }

                    $config['params']['doc'] = 'OTAPPLICATIONADV';
                    $this->logger->sbcmasterlog($row['line'], $config, $label . ' (' . $row['clientname'] . ') - ' . $row['dateid'] . '. Computed OT Hrs: ' . $row['othrs'] . ' Approved OT Hrs: ' . $row['apothrs']);
                    $this->logger->sbcmasterlog($row['line'], $config, $label . ' (' . $row['clientname'] . ') - ' . $row['dateid'] . ' Computed OT > 8 Hrs: ' . $row['othrsextra'] . ' Approved OT > 8 Hrs: ' . $row['apothrsextra']);
                    $this->logger->sbcmasterlog($row['line'], $config, $label . ' (' . $row['clientname'] . ') - ' . $row['dateid'] . ' Computed N-Diff OT Hrs: ' . $ndiffothrs . ' Approved N-Diff OT Hrs: ' . $row['apndiffothrs'] . ' ' . $rem2 . ' - ' . $remarkslast);
                    return ['status' => true, 'msg' => 'Successfully ' . $label . ' ', 'data' => [], 'reloadsbclist' => true, 'action' => 'gapplications', 'deleterow' => true];
                } else {
                    reinsertpendingapp:
                    if ($msg == '') {
                        $msg = 'Error updating record, Please try again.';
                    }
                    $this->coreFunctions->execqry("delete from pendingapp where doc='OT' and line=" . $row['line'], 'delete');
                    if (!empty($pendingdata)) {
                        foreach ($pendingdata as $pd) {
                            $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid,approver) values(?, ?, ?, ?,?)", 'insert', [$pd->trno, $pd->line, $pd->doc, $pd->clientid, $pd->approver]);
                        }
                    }
                    return ['status' => false, 'msg' => $msg, 'data' => []];
                }
            }
        } else {
            return ['status' => false, 'msg' => 'Already approved.', 'data' => []];
        }
    }
} //end class
