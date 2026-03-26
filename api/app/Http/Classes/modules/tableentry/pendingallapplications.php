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
use PhpParser\Node\Stmt\Break_;

class pendingallapplications
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'PENDING APPLICATIONS';
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
        'otstatus',
        'status',
        'status2',
        'remarks',
        'disapprovedby',
        'disapprovedate',
        'approverem',
        'date_approved',
        'approvedby',
        'disapproved_remarks',
        'disapproveddate',
        'date_disapproved',
        'date_approved_disapproved',
        'approvedby_disapprovedby',
        'void_remarks',
        'void_date',
        'void_by'

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
            'load' => 0
        );
        return $attrib;
    }

    public function createTab($config)
    {

        $doc = $config['params']['row']['moduletype'];
        $companyid = $config['params']['companyid'];
        $paths = $config['params']['row']['paths'];
        $folder = 'payroll';
        if ($doc == 'LEAVE') {
            $folder = 'payrollentry';
        }
        $leavelabel = $this->companysetup->getleavelabel($config['params']);
        if ($leavelabel == 'Days') {
            $leavelabel = 'Day';
        }
        $url = 'App\Http\Classes\modules\\' . $folder . '\\' . $paths;
        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='$doc'");
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
        $cols = [];
        switch ($doc) {
            case 'OB':
                $cols = ['action', 'createdate', 'clientname', 'remarks', 'scheddate', 'schedin', 'semtime', 'daytype', 'location'];
                break;
            case 'LEAVE':
                $cols = ['action', 'createdate', 'clientname', 'remarks', 'acnoname', 'days', 'bal', 'effectivity', 'hours'];
                break;
            case 'LOAN':
                $cols = ['action', 'createdate', 'clientname', 'remarks', 'dateid', 'acnoname', 'effectivity', 'amt', 'amortization', 'bal'];
                break;
            case 'OT':
                $cols = ['action', 'createdate', 'clientname', 'remarks', 'daytype', 'schedin', 'schedout', 'othrs', 'apothrs', 'othrsextra', 'apothrsextra', 'ndiffothrs', 'apndiffothrs']; #check
                break;
            case 'CHANGESHIFT':
                $cols = ['action', 'createdate', 'clientname', 'remarks', 'daytype', 'orgdaytype', 'orgschedin', 'orgschedout', 'schedin', 'schedout'];
                break;
        }
        if ($companyid == 53) { //camera
            if (count($approversetup) == 1 || $both) {
                array_push($cols, 'tempname', 'void_remarks');
            } else {
                array_push($cols,  'fempname', 'tempname', 'void_remarks');
            }
        }


        foreach ($cols as $key => $value) {
            $$value = $key;
        }
        $tab = [$this->gridname => ['gridcolumns' => $cols]];
        $stockbuttons = ['disapprove'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        // $obj[0][$this->gridname]['columns'][0]['btns']['view']['action'] = 'customform';
        $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Name';
        $obj[0][$this->gridname]['columns'][$clientname]['style'] = 'width:120px;min-width:120px;';
        $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$createdate]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$createdate]['label'] = 'Date Applied';
        $obj[0][$this->gridname]['columns'][$remarks]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$remarks]['label'] = 'Employee Reason';
        $obj[0][$this->gridname]['columns'][$void_remarks]['label'] = 'Reason';

        switch ($doc) {
            case 'OB':

                $obj[0][$this->gridname]['columns'][$scheddate]['type'] = 'label';
                $obj[0][$this->gridname]['columns'][$schedin]['type'] = 'label';
                $obj[0][$this->gridname]['columns'][$schedin]['label'] = 'Applied Date';
                $obj[0][$this->gridname]['columns'][$schedin]['style'] = 'width:80px;min-width:80px;';
                $obj[0][$this->gridname]['columns'][$daytype]['type'] = 'label';
                $obj[0][$this->gridname]['columns'][$location]['type'] = 'label';
                $obj[0][$this->gridname]['columns'][$semtime]['type'] = 'label';
                $obj[0][$this->gridname]['columns'][$semtime]['style'] = 'width:80px;min-width:80px;';

                break;
            case 'LEAVE':
                $obj[0][$this->gridname]['columns'][$acnoname]['type'] = 'label';
                $obj[0][$this->gridname]['columns'][$acnoname]['label'] = 'Leave Type';
                $obj[0][$this->gridname]['columns'][$acnoname]['style'] = 'width:120px;min-width:120px;';
                $obj[0][$this->gridname]['columns'][$days]['type'] = 'label';
                $obj[0][$this->gridname]['columns'][$days]['label'] = 'Entitle Leave';
                $obj[0][$this->gridname]['columns'][$days]['style'] = 'text-align:right';

                $obj[0][$this->gridname]['columns'][$bal]['type'] = 'label';
                $obj[0][$this->gridname]['columns'][$effectivity]['type'] = 'label';
                $obj[0][$this->gridname]['columns'][$effectivity]['style'] = 'width:120px;min-width:120px;';
                $obj[0][$this->gridname]['columns'][$hours]['type'] = 'label';
                $obj[0][$this->gridname]['columns'][$hours]['label'] = 'Leave ' . $leavelabel;
                $obj[0][$this->gridname]['columns'][$hours]['style'] = 'text-align:right';
                $obj[0][$this->gridname]['columns'][$hours]['style'] = 'width:80px;min-width:80px;';

                break;
            case 'OT':
                $obj[0][$this->gridname]['columns'][$daytype]['type'] = 'label';
                $obj[0][$this->gridname]['columns'][$schedin]['type'] = 'label';
                $obj[0][$this->gridname]['columns'][$schedout]['type'] = 'label';
                $obj[0][$this->gridname]['columns'][$othrs]['type'] = 'label';
                $obj[0][$this->gridname]['columns'][$apothrs]['type'] = 'label';

                $obj[0][$this->gridname]['columns'][$othrsextra]['type'] = 'label';
                $obj[0][$this->gridname]['columns'][$apothrsextra]['type'] = 'label';

                $obj[0][$this->gridname]['columns'][$apothrsextra]['type'] = 'label';


                $obj[0][$this->gridname]['columns'][$ndiffothrs]['type'] = 'label';
                $obj[0][$this->gridname]['columns'][$apndiffothrs]['type'] = 'label';


                break;
            case 'LOAN':
                $obj[0][$this->gridname]['columns'][$acnoname]['type'] = 'label';
                $obj[0][$this->gridname]['columns'][$acnoname]['label'] = 'Loan Types';
                $obj[0][$this->gridname]['columns'][$acnoname]['style'] = 'width:140px;min-width:140px;'; #codename
                $obj[0][$this->gridname]['columns'][$amt]['label'] = 'Loan Amount';
                $obj[0][$this->gridname]['columns'][$amt]['type'] = 'label';
                $obj[0][$this->gridname]['columns'][$effectivity]['label'] = 'Effectivity Date';
                $obj[0][$this->gridname]['columns'][$effectivity]['type'] = 'label';
                $obj[0][$this->gridname]['columns'][$dateid]['type'] = 'label';
                $obj[0][$this->gridname]['columns'][$amortization]['type'] = 'label';
                $obj[0][$this->gridname]['columns'][$amortization]['style'] = 'text-align:right';
                $obj[0][$this->gridname]['columns'][$bal]['type'] = 'label';

                break;
            case 'CHANGESHIFT':

                $obj[0][$this->gridname]['columns'][$daytype]['type'] = 'label';
                $obj[0][$this->gridname]['columns'][$orgdaytype]['type'] = 'label';
                $obj[0][$this->gridname]['columns'][$orgschedin]['type'] = 'label';
                $obj[0][$this->gridname]['columns'][$orgschedout]['type'] = 'label';
                $obj[0][$this->gridname]['columns'][$schedin]['type'] = 'label';
                $obj[0][$this->gridname]['columns'][$schedout]['type'] = 'label';

                $obj[0][$this->gridname]['columns'][$schedin]['label'] = 'Change to Shedule In';
                $obj[0][$this->gridname]['columns'][$schedout]['label'] = 'Change to Shedule Out';

                break;
            case 'UNDERTIME':
                break;
        }

        if (count($approversetup) == 1 || $both) {
            $obj[0][$this->gridname]['columns'][$tempname]['type'] = 'label';
            $obj[0][$this->gridname]['columns'][$tempname]['style'] = 'width:120px;min-width:120px;';
            $obj[0][$this->gridname]['columns'][$tempname]['label'] = 'Approver';
            if ($companyid == 53) { //camera
                if ($approversetup[0] == 'isapprover') {
                    $obj[0][$this->gridname]['columns'][$tempname]['label'] = 'HR/ PAYROLL APPROVER';
                } else {
                    $obj[0][$this->gridname]['columns'][$tempname]['label'] = 'HEAD DEPT. APPROVER';
                }
            }
        } else {

            $obj[0][$this->gridname]['columns'][$fempname]['type'] = 'label';
            $obj[0][$this->gridname]['columns'][$fempname]['style'] = 'width:120px;min-width:120px;';

            $obj[0][$this->gridname]['columns'][$tempname]['type'] = 'label';
            $obj[0][$this->gridname]['columns'][$tempname]['style'] = 'width:120px;min-width:120px;';

            if ($companyid == 53) {
                if ($approversetup[0] == 'isapprover') {
                    $obj[0][$this->gridname]['columns'][$fempname]['label'] = 'HR/ PAYROLL APPROVER';
                    $obj[0][$this->gridname]['columns'][$tempname]['label'] = 'HEAD DEPT. APPROVER';
                } else {
                    $obj[0][$this->gridname]['columns'][$fempname]['label'] = 'HEAD DEPT. APPROVER';
                    $obj[0][$this->gridname]['columns'][$tempname]['label'] = 'HR/ PAYROLL APPROVER';
                }
            } else {
                $obj[0][$this->gridname]['columns'][$fempname]['label'] = 'First Approver';
                $obj[0][$this->gridname]['columns'][$tempname]['label'] = 'Last Approved';
            }
        }
        return $obj;
    }

    public function createtabbutton($config)
    {
        $modulename = $config['params']['row']['modulename'];
        $obj = [];
        $this->modulename  .= ' - ' . $modulename;
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
        $doc = $config['params']['row']['moduletype'];
        $adminid = $config['params']['adminid'];
        $checking = $this->othersClass->checkapproversetup($config, $adminid, $doc, 'emp', true);
        $filter = "";
        $leftjoin = "";

        if ($checking['leftjoin'] != "") {
            $leftjoin .= $checking['leftjoin'];

            if ($checking['filter'] != "") {
                $filter .= $checking['filter'];
            }
        }
        switch ($doc) {
            case 'OT':

                $checking = $this->othersClass->checkapproversetup($config, $adminid, 'OT', 'emp', true);
                $query = "
                select ot.line, client.clientname,emp.email,date(ot.createdate) as createdate,date(ot.dateid) as dateid,
                ot.empid,'OT' as type,ot.rem as remarks,ot.disapproved_remarks2 as reason1,ot.remarks as remlast,ot.othrs,ot.apothrs,ot.ndiffhrs, ot.apndiffhrs,
                ot.othrsextra,ot.apothrsextra,ot.ndiffothrs,ot.apndiffothrs,date(ot.scheddate) as scheddate,dayname(ot.scheddate) as dayname,
                date_format(ot.ottimein, '%Y-%m-%d %h:%i %p') as schedin, date_format(ot.ottimeout, '%Y-%m-%d %h:%i %p') as schedout,ot.daytype,
                app.clientname as tempname,app2.clientname as fempname,ot.void_remarks,ot.void_date,void.clientname as void_by
                from otapplication as ot 
                left join client on client.clientid=ot.empid
                left join batch on batch.line = ot.batchid
                left join employee as emp on emp.empid = ot.empid 
                left join client as app on app.email = ot.approvedby and app.email <>''
                left join client as app2 on app2.email = ot.approvedby2 and app2.email <> ''
                left join client as void on void.email = ot.void_by and void.email <> ''

                $leftjoin
	            where ot.otstatus = 2 and  datediff(date(now()),ot.approvedate) <= 7 $filter ";
                break;
            case 'OB':
                $query = "
                select ob.line, client.clientname,emp.email,date(ob.createdate) as createdate, date(ob.dateid) as dateid,
                ob.empid, 'OB' as type,ob.rem as remarks ,ob.approverem as remark,ob.type as daytype,ob.location,date(ob.dateid) as datetime,
                date_format(ob.dateid, '%Y-%m-%d') as schedin,date(ob.dateid) as schedin,
                if(ob.type = 'Off-setting',concat(date_format(ob.dateid, '%h:%i %p'),' - ',date_format(ob.dateid2, '%h:%i %p')),date_format(ob.dateid, '%h:%i %p')) as semtime,
                date(ob.scheddate) as scheddate,dayname(ob.scheddate) as dayname,
                ob.disapproved_remarks2 as reason2,app.clientname as tempname,app2.clientname as fempname,ob.void_remarks,ob.void_date,void.clientname as void_by
                from obapplication as ob left join client on client.clientid=ob.empid
                left join employee as emp on emp.empid = ob.empid 
                left join client as app on app.email = ob.approvedby and app.email <> ''
                left join client as app2 on app2.email = ob.approvedby2 and app2.email <> ''
                left join client as void on void.email = ob.void_by and void.email <> ''
                $leftjoin 
                where ob.status = 'A' and  datediff(date(now()),ob.approvedate) <= 7 $filter ";

                break;
            case 'LEAVE':

                $query = " 
                select lt.trno, lt.line,client.clientname,emp.email, date(lt.dateid) as createdate,date(lt.dateid) as dateid,date(lt.effectivity) as effectivity,
                lt.empid,'LEAVE' as type, lt.remarks,ls.days,ls.bal,p.codename as acnoname, lt.adays as hours,lt.disapproved_remarks as remlast,lt.disapproved_remarks2 as reason2,
                app.clientname as tempname,app2.clientname as fempname,lt.status,lt.void_remarks,lt.void_date,lt.void_by
		        from leavetrans  as lt
                left join leavesetup as ls on ls.trno = lt.trno
                left join client on client.clientid=lt.empid
                left join employee as emp on emp.empid = lt.empid
                left join paccount as p on p.line=ls.acnoid
                left join client as app on app.email = lt.approvedby_disapprovedby and app.email <> ''
                left join client as app2 on app2.email = lt.approvedby_disapprovedby2 and app2.email <> ''
                left join client as void on void.email = lt.void_by and void.email <> ''
             
                $leftjoin
		        where lt.status in ('A','P') and lt.status2 in ('A','P') and datediff(date(now()),lt.date_approved_disapproved) <= 7 $filter ";

                break;
            case 'CHANGESHIFT':
                $query = "
                select csapp.line, client.clientname,emp.email,date(csapp.createdate) as createdate,date(csapp.dateid) as dateid,
                csapp.empid,'CHANGESHIFT' as type,csapp.rem as remarks,csapp.disapproved_remarks2 as reason1,
                date_format(csapp.schedin, '%Y-%m-%d %h:%i %p') as schedin,date_format(csapp.schedout, '%Y-%m-%d %h:%i %p') as schedout,
                date_format(tm.schedin, '%Y-%m-%d %h:%i %p') as orgschedin,date_format(tm.schedout, '%Y-%m-%d %h:%i %p') as orgschedout,
                csapp.daytype,csapp.orgdaytype,csapp.disapproved_remarks as remlast,app.clientname as tempname,app2.clientname as fempname,
                csapp.void_remarks,csapp.void_date,void.clientname as void_by
                from changeshiftapp as csapp
                left join timecard as tm on tm.empid = csapp.empid and tm.dateid = date(csapp.dateid)
                left join client on client.clientid=csapp.empid
                left join employee as emp on emp.empid = csapp.empid

                left join client as app on app.email = csapp.approvedby and app.email <> ''
                left join client as app2 on app2.email = csapp.approvedby2 and app2.email <> ''
                left join client as void on void.email = csapp.void_by and void.email <> ''
                $leftjoin
                where csapp.status= 1 and datediff(date(now()),csapp.approveddate) <= 7 $filter";
                break;
            case 'LOAN':
                $query = "
                select loan.trno as line, client.clientname,emp.email,date(loan.dateid) as createdate,date(loan.dateid) as dateid,date(loan.effdate) as effectivity,
                loan.empid,'LOAN' as type,loan.remarks, loan.disapproved_remarks as remlast,loan.disapproved_remarks2 as reason1, format(loan.amt,2) as amt, pa.codename as acnoname,format(loan.amortization,2) as amortization,loan.balance as bal,
                app.clientname as tempname,app2.clientname as fempname,loan.void_remarks,void.clientname as void_by,loan.void_date
                from loanapplication as loan left join client on client.clientid=loan.empid
                left join employee as emp on emp.empid = loan.empid
                left join paccount as pa on pa.line = loan.acnoid
                left join client as app on app.email = loan.approvedby_disapprovedby and app.email <> ''
                left join client as app2 on app2.email = loan.approvedby_disapprovedby2 and app2.email <> ''
                left join client as void on void.email = loan.void_by and void.email <> ''
                $leftjoin
                where loan.status='A' and datediff(date(now()),loan.date_approved_disapproved) <= 7 $filter";

                break;
        }
        $data = $this->coreFunctions->opentable($query);
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
        $doc = $row['type'];
        $admin = $config['params']['adminid'];
        $companyid = $config['params']['companyid'];
        // break;

        $dateid = $row['createdate'];
        $empname = $row['clientname'];
        $email = $row['email'];
        $remark = $row['remarks'];
        $void_remark = $row['void_remarks'];
        $void_date = $row['void_date'];
        $void_by = $row['void_by'];
        $isupdate = false;

        if ($void_remark == "") {
            return ['status' => false, 'msg' => 'remarks is empty', 'data' => []];
        }

        if (isset($row['line'])) {
            $line = $row['line'];
            if ($status == 'D') {
                $label = 'Disapproved';
                $condition = ['line' => $line];
                switch ($doc) {

                    case 'OT':
                        $table = 'otapplication';
                        $modulename = ' OT APPLICACATION';
                        $data = [
                            'otstatus' => 3,
                            'void_by' => $config['params']['user'],
                            'void_date' => $this->othersClass->getCurrentTimeStamp(),
                            'void_remarks' => $void_remark
                        ];
                        break;
                    case 'OB':
                        $table = 'obapplication';
                        $modulename = ' OB APPLICACATION';


                        $data = [
                            'status' => 'D',
                            'void_by' => $config['params']['user'],
                            'void_date' => $this->othersClass->getCurrentTimeStamp(),
                            'void_remarks' => $void_remark
                        ];

                        break;
                    case 'LEAVE':
                        $table = 'leavetrans';
                        $modulename = ' LEAVE APPLICACATION';
                        $trno = $row['trno'];
                        $condition = ['trno' => $trno, 'line' => $line];
                        $data = [
                            'status' => 'D',
                            'void_date' => $this->othersClass->getCurrentTimeStamp(),
                            'void_by' => $config['params']['user'],
                            'void_remarks' => $void_remark
                        ];
                        if ($row['status'] == 'A') {
                            $entitled = $this->coreFunctions->datareader("select sum(adays) as value from leavetrans where status in ('A') and empid=" . $row['empid'] . " and trno= " . $row['trno'] . "");
                            $bal = ($row['days'] - $entitled) + $row['hours'];
                            $isupdate =  $this->coreFunctions->execqry("update leavesetup set bal='" . $bal . "' where trno=?", 'update', [$row['trno']]);
                        }
                        break;
                    case 'LOAN':
                        $table = 'loanapplication';
                        $modulename = ' LOAN APPLICACATION';
                        $condition = ['trno' => $line];
                        $data = [
                            'status' => 'D',
                            'void_date' => $this->othersClass->getCurrentTimeStamp(),
                            'void_by' => $config['params']['user'],
                            'void_remarks' => $void_remark
                        ];
                        break;
                    case 'CHANGESHIFT':
                        $table = 'changeshiftapp';
                        $modulename = ' CHANGE SHIFT APPLICACATION';
                        $data = [
                            'status' => 2,
                            'void_by' => $config['params']['user'],
                            'void_date' => $this->othersClass->getCurrentTimeStamp(),
                            'void_remarks' => $void_remark
                        ];
                        break;
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
                $update = $this->coreFunctions->sbcupdate($table, $tempdata, $condition);

                if ($update) {
                    $params = [];

                    $params['title'] =  $modulename . " WITHIN A WEEK ";
                    $params['clientname'] = $empname;
                    $params['line'] = $line;
                    $params['remarks'] = $remark;
                    $params['dateid'] = $dateid;
                    $params['approvedstatus'] = $label;
                    $params['muduletype'] = $doc;
                    $params['companyid'] = $companyid;
                    $params['isapp'] = '';

                    switch ($doc) {
                        case 'CHANGESHIFT':
                            $params['muduletype'] = 'SCHED';
                            $params['schedin'] = $row['schedin'];
                            $params['schedout'] = $row['schedout'];
                            $params['orgschedin'] = $row['orgschedin'];
                            $params['orgschedout'] = $row['orgschedout'];
                            $params['daytype'] = $row['daytype'];
                            $params['orgdaytype'] = $row['orgdaytype'];

                            $params['reason1'] = $row['reason1'];
                            $params['remlast'] = $row['remlast'];
                            $params['void_remarks'] = $void_remark;



                            break;
                        case 'OT':
                            $params['scheddate'] = $row['scheddate'] . " (" . $row['dayname'] . ")";
                            $params['ottimein'] = $row['schedin'];
                            $params['ottimeout'] = $row['schedout'];
                            $params['rem'] = $remark;
                            $params['othrs'] = $row['othrs'];
                            $params['othrsextra'] = $row['othrsextra'];
                            $params['ndiffhrs'] = $row['ndiffhrs'];
                            $params['ndiffothrs'] = $row['ndiffothrs'];
                            $params['daytype'] = $row['dayname'];
                            $params['approvedstatus'] = $label;
                            $params['createdate'] = $dateid;
                            $params['reason1'] = $row['reason1'];
                            $params['remlast'] = $row['remlast'];
                            $params['void_remarks'] = $void_remark;
                            break;
                        case 'OB':
                            $params['scheddate'] = $row['scheddate'] . " (" . $row['dayname'] . ")";
                            $params['dateid'] = $row['dateid'];
                            $params['rem'] = $remark;
                            $params['datetime'] = $row['datetime'];
                            $params['type'] = $row['daytype'];
                            $params['location'] = $row['location'];
                            $params['approvedstatus'] = $label;
                            $params['reason1'] = $row['remark'];
                            $params['reason2'] = $row['reason2'];
                            $params['void_remarks'] = $void_remark;

                            break;
                        case 'LEAVE':
                            $params['effdate'] = $row['effectivity'];
                            $params['dateid'] = $dateid;
                            $params['adays'] = $row['hours'];
                            $params['bal'] = $row['bal'];
                            $params['entitled'] =  $row['days'];
                            $params['codename'] = $row['acnoname'];
                            $params['remarks'] = $remark;
                            $params['reason1'] = $row['remlast'];
                            $params['reason2'] = $row['reason2'];
                            $params['void_remarks'] = $void_remark;
                            break;
                        case 'LOAN':
                            $params['amount'] =  $row['amt'];
                            $params['effdate'] =  $row['effectivity'];
                            $params['acnoname'] = $row['acnoname'];
                            $params['amortization'] = $row['amortization'];
                            $params['remarks'] = $row['remarks'];
                            $params['balance'] = $row['bal'];
                            $params['reason1'] = $row['reason1'];
                            $params['remlast'] = $row['remlast'];
                            $params['void_remarks'] = $void_remark;

                            break;
                    }
                    if (!empty($email)) {
                        $qry = "select clientname,email as approver from client where email = '" . $config['params']['user'] . "' ";
                        $data2 = $this->coreFunctions->opentable($qry);
                        $params['approver'] = $data2[0]->approver;
                        $params['appname'] = $data2[0]->clientname;
                        $params['email'] = $email;
                        $result = $this->linkemail->weblink($params,$config);
                        if (!$result['status']) {
                            return ['status' => false, 'msg' => '' . $result['msg']];
                        }
                    }

                    $config['params']['doc'] = 'PENDINGALLAPPLICATIONS';
                    $this->logger->sbcmasterlog($row['line'], $config, $label . ' (' . $row['clientname'] . ') - ' . $row['dateid']);
                    return ['status' => true, 'msg' => 'Successfully ' . $label . ' ', 'data' => [], 'reloadsbclist' => true, 'action' => 'getpendingallapplication', 'deleterow' => true];
                } else {
                    if ($doc == 'LEAVE') {
                        if ($isupdate) {
                            if ($row['status'] == 'A') {
                                $entitle = $this->coreFunctions->datareader("select sum(adays) as value from leavetrans where status in ('A') and empid=? and trno=?", [$row['empid'], $row['trno']]);
                                $bal = ($row['days']  - $entitle) + $row['hours'];
                                $this->coreFunctions->execqry("update leavesetup set bal='" . $bal . "' where trno=?", 'update', [$row['trno']]);
                            }
                        }
                        $row['line'] = $row['trno'];
                    }
                    $this->logger->sbcmasterlog($row['line'], $config, $label . ' Update record failed ' . ' (' . $row['clientname'] . ') - ' . $row['dateid']);
                    return ['status' => false, 'msg' => 'Update record failed', 'data' => [], 'reloadsbclist' => true, 'action' => 'getpendingallapplication'];
                }
            }
        }

        return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => []];
    }
} //end class
