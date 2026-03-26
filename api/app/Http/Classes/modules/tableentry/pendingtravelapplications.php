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
use DateTime;
use DateInterval;
use DatePeriod;
use Exception;

class pendingtravelapplications
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'PENDING TRAVEL APPLICATIONS';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $linkemail;
    public $tablelogs = 'payroll_log';
    public $style = 'width:90%;max-width:90%;';
    public $issearchshow = true;
    public $showclosebtn = true;
    public $fields = ['status', 'status2', 'approvedby', 'approvedate', 'approvedrem', 'approverem', 'disapprovedby', 'disapprovedate', 'approvedby2', 'approvedate2', 'approvedrem2', 'disapprovedby2', 'disapprovedate2'];


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
            'load' => 5155
        );
        return $attrib;
    }

    public function createTab($config)
    {
        $doc = $config['params']['row']['doc'];
        $approver = $this->coreFunctions->getfieldvalue("employee", "isapprover", "empid=?", [$config['params']['adminid']]);
        $supervisor = $this->coreFunctions->getfieldvalue("employee", "issupervisor", "empid=?", [$config['params']['adminid']]);
        $url = 'App\Http\Classes\modules\payroll\\' . 'restday';
        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='TRAVEL'");
        if ($approversetup == '') {
            $approversetup = app($url)->approvers($config['params']);
        } else {
            $approversetup = explode(',', $approversetup);
            foreach ($approversetup as $appkey => $appsetup) {
                if ($appsetup == 'Supervisor') {
                    $approversetup[$appkey] = 'issupervisor';
                } else {
                    $approversetup[$appkey] = 'isapprover';
                }
            }
        }
        $cols = ['action', 'clientname', 'dateid', 'startdate', 'enddate', 'remarks', 'rem1'];

        if ($config['params']['companyid'] == 58) { //cdo
            if ($config['params']['row']['approver'] == "isbudgetapprover") {
                $cols = ['action', 'clientname', 'dateid', 'startdate', 'enddate', 'amt4', 'amt5', 'amt6', 'amt7', 'ext'];
            }
        }

        foreach ($cols as $key => $value) {
            $$value = $key;
        }

        $tab = [$this->gridname => ['gridcolumns' => $cols]];
        $stockbuttons = ['approve', 'disapprove'];

        if ($config['params']['row']['approver'] == "isbudgetapprover") {
            $stockbuttons = ['approve'];
        }
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Name';
        $obj[0][$this->gridname]['columns'][$clientname]['style'] = 'width:200px;min-width:200px;';
        $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$dateid]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$startdate]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$enddate]['type'] = 'label';

        if ($config['params']['row']['approver'] != "isbudgetapprover") {
            $obj[0][$this->gridname]['columns'][$remarks]['type'] = 'label';
            $obj[0][$this->gridname]['columns'][$remarks]['label'] = 'Reason/Purpose';
            $obj[0][$this->gridname]['columns'][$rem1]['type'] = 'input';
            $obj[0][$this->gridname]['columns'][$rem1]['label'] = 'Approver Remarks';
            $obj[0][$this->gridname]['columns'][$rem1]['readonly'] = false;
            $obj[0][$this->gridname]['columns'][$rem1]['style'] = 'width:200px;min-width:200px;';
        }

        if ($config['params']['companyid'] == 58) { //cdo
            $obj[0][$this->gridname]['columns'][$dateid]['style'] = 'width:150px;min-width:150px;';
            $obj[0][$this->gridname]['columns'][$startdate]['style'] = 'width:150px;min-width:150px;';
            $obj[0][$this->gridname]['columns'][$enddate]['style'] = 'width:150px;min-width:150px;';

            if ($config['params']['row']['approver'] == "isbudgetapprover") {
                $obj[0][$this->gridname]['columns'][$ext]['style'] = 'width:100px;min-width:100px;';
                $obj[0][$this->gridname]['columns'][$ext]['label'] = 'Total Budget Needed';
                $obj[0][$this->gridname]['columns'][$ext]['type'] = 'label';
                $obj[0][$this->gridname]['columns'][$ext]['align'] = 'text-left';
                $obj[0][$this->gridname]['columns'][$amt4]['style'] = 'width:100px;min-width:100px;';
                $obj[0][$this->gridname]['columns'][$amt5]['style'] = 'width:100px;min-width:100px;';
                $obj[0][$this->gridname]['columns'][$amt6]['style'] = 'width:100px;min-width:100px;';
                $obj[0][$this->gridname]['columns'][$amt7]['style'] = 'width:100px;min-width:100px;';
                $obj[0][$this->gridname]['columns'][$amt4]['label'] = 'Meals';
                $obj[0][$this->gridname]['columns'][$amt5]['label'] = 'Transportation';
                $obj[0][$this->gridname]['columns'][$amt6]['label'] = 'Lodge';
                $obj[0][$this->gridname]['columns'][$amt7]['label'] = 'Miscellaneous';
                $obj[0][$this->gridname]['columns'][$amt4]['type'] = 'label';
                $obj[0][$this->gridname]['columns'][$amt5]['type'] = 'label';
                $obj[0][$this->gridname]['columns'][$amt6]['type'] = 'label';
                $obj[0][$this->gridname]['columns'][$amt7]['type'] = 'label';
                $obj[0][$this->gridname]['columns'][$amt4]['align'] = 'text-left';
                $obj[0][$this->gridname]['columns'][$amt5]['align'] = 'text-left';
                $obj[0][$this->gridname]['columns'][$amt6]['align'] = 'text-left';
                $obj[0][$this->gridname]['columns'][$amt7]['align'] = 'text-left';
            }
        }

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
        $modulename = $config['params']['row']['modulename'];
        $approver = $config['params']['row']['approver'];
        $amt = '';
        $transpo = '';
        $lodge = '';
        $misc = '';
        $total = '';
        $groupby = '';
        $condition = '';

        if ($modulename == 'Budget for Travel Applications') {
            $qry = "select it.trno, client.clientname,date(it.dateid) as dateid, emp.empid,
                        date(it.startdate) as startdate,date(it.enddate) as enddate,it.remarks,
                        m.sbcpendingapp, m.modulename as doc, p.approver, it.approvedrem as rem1,
                        case when p.approver = 'isapprover' then 'FOR APPROVER' else 'FOR SUPERVISOR' end as lblforapp, 
                        (it.mealamt * it.mealnum) as amt4 , (case when it.expensetype='Gasoline ' then 0 else it.texpense end) as amt5 ,
                        (it.lodgeexp * it.lengthstay) as amt6 ,it.misc as amt7 , it.ext
            from itinerary as it
            left join client on client.clientid=it.empid
            left join employee as emp on emp.empid = it.empid
            left join pendingapp as p on p.trno=it.trno and p.doc='TRAVEL'
            left join moduleapproval as m on m.modulename=p.doc
            where submitdate is not null and it.status in ('E','A') and p.clientid=" . $config['params']['adminid'] . " and p.approver = 'isbudgetapprover'
            group by it.trno, client.clientname,it.dateid, emp.empid,it.startdate,it.enddate,it.remarks,
                    m.sbcpendingapp, m.modulename, p.approver, it.approvedrem,it.mealamt,it.mealnum,it.texpense,
                    it.gas,it.lengthstay,it.lodgeexp,it.misc,it.expensetype,it.ext
            order by it.dateid, client.clientname";
        } else {
            if ($config['params']['companyid'] == 58) { //cdo
                $amt = ',(it.mealamt * it.mealnum) as amt4';
                $transpo = ', (case when it.expensetype="Gasoline" then 0 else it.texpense end) as amt5';
                $lodge = ',(it.lodgeexp * it.lengthstay) as amt6';
                $misc = ',it.misc as amt7';

                $total = ",  it.ext";
                $groupby = 'group by it.trno, client.clientname,it.dateid, emp.empid,it.startdate,it.enddate,it.remarks,
                             m.sbcpendingapp, m.modulename, p.approver, it.approvedrem,it.mealamt,it.mealnum,it.texpense,it.gas,it.lengthstay,
                             it.lodgeexp,it.misc,it.expensetype,it.ext';
                $condition = " and p.approver <> 'isbudgetapprover'";
            }
            $qry = "select it.trno, client.clientname,date(it.dateid) as dateid, emp.empid,
            date(it.startdate) as startdate,date(it.enddate) as enddate,it.remarks,
            m.sbcpendingapp, m.modulename as doc, p.approver, it.approvedrem as rem1,
            case when p.approver = 'isapprover' then 'FOR APPROVER' else 'FOR SUPERVISOR' end as lblforapp $amt $transpo $lodge $misc $total
            from itinerary as it
            left join client on client.clientid=it.empid
            left join employee as emp on emp.empid = it.empid 
            left join pendingapp as p on p.trno=it.trno and p.doc='TRAVEL'
            left join moduleapproval as m on m.modulename=p.doc
            where submitdate is not null and it.status='E' and p.approver='" . $approver . "' and p.clientid=" . $config['params']['adminid'] . " $condition
            $groupby
            order by it.dateid, client.clientname";
        }

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
        $admin = $config['params']['adminid'];
        $companyid = $config['params']['companyid'];
        $isapp = $row['approver'];
        if ($isapp == 'LATE FILLING') { //cdo
            $approver = 1;
            $isapp = "isapprover";
        } else {
            $approver = $this->coreFunctions->getfieldvalue("employee", $isapp, "empid=?", [$admin]);
        }

        $url = 'App\Http\Classes\modules\payroll\\' . 'itinerary';
        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='" . $doc . "'");
        if ($approversetup == '') {
            $approversetup = app($url)->approvers($config['params']);
        } else {
            $approversetup = explode(',', $approversetup);
            foreach ($approversetup as $appkey => $appsetup) {
                if ($appsetup == 'Supervisor') {
                    $approversetup[$appkey] = 'issupervisor';
                } else {
                    $approversetup[$appkey] = 'isapprover';
                }
            }
        }
        $trno = $row['trno'];
        $tstatus = $status;

        if ($isapp == 'isbudgetapprover') {
            if ($status == 'A') {
                $label = 'Approved';
                $status = $this->coreFunctions->datareader("select approvedbuddate as value from itinerary where trno=? and approvedbuddate is not null", [$trno]);
            }

            if (!$status) {
                $data = [];
                // $data['approvedbudrem'] = $row['rem1'];
                $data['approvedbudby'] = $config['params']['user'];
                $data['approvedbuddate'] = $this->othersClass->getCurrentTimeStamp();

                $pendingdata = $this->coreFunctions->opentable("select * from pendingapp where trno=" . $trno . " and doc='TRAVEL' and approver ='isbudgetapprover'");
                $this->coreFunctions->execqry("delete from pendingapp where doc='TRAVEL'  and approver ='isbudgetapprover' and trno=" . $trno, 'delete');
                $tempdata['empid'] = $row['empid'];
                $update = $this->coreFunctions->sbcupdate('itinerary', $data, ['trno' => $trno]);

                $config['params']['doc'] = 'ITINERARY';
                $this->logger->sbcmasterlog($trno, $config, $label . ' Budget : (' . $row['clientname'] . ') - ' . $row['dateid']);
                return ['status' => true, 'msg' => 'Successfully ' . $label, 'data' => [], 'reloadsbclist' => true, 'action' => 'gapplications', 'deleterow' => true];
            }
        } else {
            if ($status == 'A') {
                $label = 'Approved';
                $status = $this->coreFunctions->datareader("select approvedate as value from itinerary where trno=? and approvedate is not null", [$trno]);
            } else {
                $label = 'Disapproved';
                $status = $this->coreFunctions->datareader("select disapprovedate as value from itinerary where trno=? and disapprovedate is not null", [$trno]);
            }
            // not done yet
            if (!$status) {
                $data = [];
                $lastapp = false;
                if ($row['rem1'] == '') return ['status' => false, 'msg' => 'Approver Remarks is empty.', 'data' => []];
                foreach ($approversetup as $key => $value) {
                    if (count($approversetup) > 1) {
                        if ($key == 0) {
                            if ($value == $isapp && $approver) {
                                $data['status2'] = $tstatus;
                                if ($tstatus == 'A') {
                                    $data['approvedby2'] = $config['params']['user'];
                                    $data['approvedate2'] = $this->othersClass->getCurrentTimeStamp();
                                    $data['approvedrem2'] = $row['rem1'];
                                } else {
                                    $lastapp = true;
                                    $data['approverem2'] = $row['rem1'];
                                    $data['disapprovedby2'] = $config['params']['user'];
                                    $data['disapprovedate2'] = $this->othersClass->getCurrentTimeStamp();
                                }
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
                            $data['status'] = $tstatus;
                            if ($tstatus == 'A') {
                                $data['approvedrem'] = $row['rem1'];
                                $data['approvedby'] = $config['params']['user'];
                                $data['approvedate'] = $this->othersClass->getCurrentTimeStamp();
                            } else {
                                $data['approverem'] = $row['rem1'];
                                $data['disapprovedby'] = $config['params']['user'];
                                $data['disapprovedate'] = $this->othersClass->getCurrentTimeStamp();
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

                $pendingdata = $this->coreFunctions->opentable("select * from pendingapp where trno=" . $trno . " and doc='TRAVEL' and approver <> 'isbudgetapprover'");
                $this->coreFunctions->execqry("delete from pendingapp where doc='TRAVEL' and approver <> 'isbudgetapprover' and trno=" . $trno, 'delete');
                $tempdata['empid'] = $row['empid'];

                $appstatus = ['status' => true];
                if (!$lastapp) {
                    $appstatus = $this->othersClass->insertUpdatePendingapp($row['trno'], 0, 'TRAVEL', $tempdata, $url, $config, 0, true);
                }
                if (!$appstatus['status']) {
                    if (!empty($pendingdata)) {
                        foreach ($pendingdata as $pd) {
                            $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid,approver) values(?, ?, ?, ?, ?)", 'insert', [$pd->trno, $pd->line, $pd->doc, $pd->clientid, $pd->approver]);
                        }
                    }
                    return ['status' => false, 'msg' => $appstatus['msg'], 'data' => []];
                } else {
                    $update = $this->coreFunctions->sbcupdate('itinerary', $tempdata, ['trno' => $trno]);
                    if ($update) {
                        $tempdata['approverem'] = $row['rem1'];
                        unset($tempdata['approvedrem']);
                        unset($tempdata['editdate']);
                        unset($tempdata['editby']);
                        $tempdata['batchob'] = $trno;
                        $tempdata['isitinerary'] = 1;
                        $tempdata['type'] = 'In';

                        if ($lastapp) {
                            if ($tstatus == 'A') {
                                $result = $this->generate_ob($config, $row['startdate'], $row['enddate'], $tempdata, $row['empid'], $row['clientname'], $trno);
                                if (!$result['status']) {
                                    $this->coreFunctions->execqry("delete from pendingapp where doc='TRAVEL' and approver <> 'isbudgetapprover' and trno=" . $trno, 'delete');
                                    if (!empty($pendingdata)) {
                                        foreach ($pendingdata as $pd) {
                                            $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid, approver) values(?, ?, ?, ?, ?)", 'insert', [$pd->trno, $pd->line, $pd->doc, $pd->clientid, $pd->approver]);
                                        }
                                    }
                                    return ['status' => false, 'msg' => $result['msg'], 'data' => []];
                                }
                                $budgetapprover = $this->coreFunctions->getfieldvalue("employee", "empid", "isbudgetapprover=1");
                                if ($budgetapprover != 0) {
                                    $bapproverqry = $this->coreFunctions->opentable("select emp.empid, client.email 
                                    from employee as emp 
                                    left join client on client.clientid=emp.empid 
                                    where emp.isbudgetapprover=1 ");

                                    $total = $this->coreFunctions->getfieldvalue("itinerary", "ext", "trno=?", [$trno]);
                                    if ($total > 0) {
                                        if (!empty($bapproverqry)) {
                                            foreach ($bapproverqry as $bapprover) {
                                                $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid, approver) values(?, ?, ?, ?, ?)", 'insert', [$trno, 0, 'TRAVEL', $bapprover->empid, 'isbudgetapprover']);
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        $config['params']['doc'] = 'ITINERARY';
                        $this->logger->sbcmasterlog($trno, $config, $label . ' (' . $row['clientname'] . ') - ' . $row['startdate'] . ' to ' . $row['enddate']);
                        return ['status' => true, 'msg' => 'Successfully ' . $label, 'data' => [], 'reloadsbclist' => true, 'action' => 'gapplications', 'deleterow' => true];
                    } else {
                        // reinsertpendingapp:
                        if (!empty($pendingdata)) {
                            foreach ($pendingdata as $pd) {
                                $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid, approver) values(?, ?, ?, ?, ?)", 'insert', [$pd->trno, $pd->line, $pd->doc, $pd->clientid, $pd->approver]);
                            }
                        }
                        return ['status' => false, 'msg' => 'Error updating record, please try again.', 'data' => []];
                    }
                }
                return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => []];
            }
        }
    }

    public function generate_ob($config, $start, $end, $tempdata, $empid, $empname, $trno)
    {
        try {
            $companyid = $config['params']['companyid'];

            $start = new DateTime(date('Y-m-d', strtotime($start)));
            $end = new DateTime($end);
            $status = true;

            $interval = new DateInterval('P1D');
            $period = new DatePeriod($start, $interval, $end->modify('+1 day'));

            $dates = [];
            $filter = "";
            $c = 0;

            $notimecard = false;
            $defsched = [];
            if ($companyid == 58) {
                $notimecard = true;
                $defsched = $this->coreFunctions->opentable("select time(s.tschedin) as timein, time(s.tschedout) as timeout from employee as emp join tmshifts as s on s.line=emp.shiftid where empid=" . $empid);
                if (empty($defsched)) return ['status' => false, 'msg' => 'Failed to approved, please setup default shift for this employee'];
            }

            foreach ($period as $date) {
                if ($notimecard) {
                    $dayname = strtolower($date->format('D'));
                    // $this->coreFunctions->LogConsole($date->format('Y-m-d') . ' ' .  $dayname);
                    if ($dayname != 'sat' && $dayname != 'sun') {
                        array_push($dates, $date->format('Y-m-d') . ' ' . $defsched[0]->timein . '~' . $date->format('Y-m-d') . ' ' . $defsched[0]->timeout);
                    }
                } else {
                    $query = "select time(schedin) as value,schedout,daytype from timecard where empid = $empid and dateid = '" . $date->format('Y-m-d') . "' and daytype = 'WORKING'";
                    $tmcd_sched = $this->coreFunctions->opentable($query);
                    if (!empty($tmcd_sched)) {
                        foreach ($tmcd_sched as $key => $tmdata) {
                            if ($tmdata->daytype != 'RESTDAY') {
                                array_push($dates, $date->format('Y-m-d') . ' ' . $tmdata->value . '~' . $tmdata->schedout);
                                if ($c == 0) {
                                    $filter .= "'" . $date->format('Y-m-d') . "'";
                                } else {
                                    $filter .= ",'" . $date->format('Y-m-d') . "'";
                                }
                                $c++;
                            }
                        }
                    } else {
                        return ['status' => false, 'msg' => 'Failed to approved, schedule is not created for this period'];
                    }
                }
            }

            if (!$notimecard) {
                $query = "select count(line) as value  from timecard where empid = $empid and dateid in (" . $filter . ") and daytype = 'WORKING'";
                $tmcd_sched = $this->coreFunctions->datareader($query);
                if ($tmcd_sched != count($dates)) {
                    return ['status' => false, 'msg' => 'Failed to approved, schedule is not created for this period'];
                }
            }

            $line = 0;
            $i = 0;
            $msg = '';
            $linelist = '';
            $sanitized_fields = ['dateid', 'dateid2', 'createdate', 'scheddate'];
            $curdate = $this->othersClass->getCurrentTimeStamp();

            foreach ($dates as $date) {

                foreach ($tempdata as $key2 => $value) {
                    $tmdates = explode('~', $date);
                    $tempdata['dateid'] = $tmdates[0];
                    $tempdata['createdate'] = $curdate;
                    $tempdata['scheddate'] = $tmdates[0];
                    $tempdata['dateid2'] = $tmdates[1];

                    if (in_array($key2, $sanitized_fields)) {
                        $this->othersClass->sanitizekeyfield('dateid', $tempdata[$key2]);
                    }
                }

                $logSched = date('Y-m-d', strtotime($tempdata['scheddate']));

                $lineob = $this->coreFunctions->insertGetId('obapplication', $tempdata);
                if ($lineob != 0) {
                    if ($i != 0) {
                        $linelist .= ',' . $lineob;
                    } else {
                        $line = $lineob;
                        $linelist .= $lineob;
                    }
                    $config['params']['doc'] = 'ITINERARY';
                    $msg = "SUCCESSFULLY GENERATE OB ON TRIP";
                    // trno log ob change to trip 01-23-2026
                    $this->logger->sbcmasterlog($trno, $config, $msg . " NAME: $empname " . "," . "  Date : " . $logSched);
                } else {
                    $status = false;
                    if ($linelist != '') $this->coreFunctions->execqry("delete from obapplication where line in (" . $linelist . ") ", "delete");
                    $msg = "FAILED TO GENERATE OB ON TRIP";
                    $this->logger->sbcmasterlog($trno, $config, $msg . "," . " Date : " . $logSched);
                    break;
                }
                $i++;
            }
            return ['status' => $status, 'msg' => $msg];
        } catch (Exception $ex) {
            return ['status' => false, 'msg' => $ex->getMessage()];
        }
    }
} //end class
