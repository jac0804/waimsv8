<?php

namespace App\Http\Classes\modules\dashboard;

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

class allapprovedapplication
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'APPLICATION APPROVED';
    public $gridname = 'customformacctg';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $linkemail;
    public $tablelogs = 'payroll_log';
    public $style = 'width:1000px;max-width:1000px;';
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
        'approvedby_disapprovedby'
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
        $leavelabel = $this->companysetup->getleavelabel($config['params']);

        $type = $config['params']['row']['type'];
        $companyid = $config['params']['companyid'];
        $seqcount = $config['params']['row']['seqcount'];
        $fields = ['createdate', 'clientname', 'lblmessage', 'remarks'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'remarks.label', 'Remarks');
        data_set($col1, 'lblmessage.label', 'Employee Remarks: ');
        data_set($col1, 'lblmessage.style', 'font-size:11px;font-weight:bold;');
        data_set($col1, 'createdate.style', 'padding:0px'); // remove buttom space
        switch ($type) {
            case 'SCHED':
                $fields = ['lblsource', 'atype', 'orgschedin', 'orgschedout'];
                $col2 = $this->fieldClass->create($fields);

                data_set($col2, 'orgschedin.type', 'input');
                data_set($col2, 'orgschedout.type', 'input');
                data_set($col2, 'atype.type', 'input');
                data_set($col2, 'atype.name', 'atype');
                data_set($col2, 'atype.readonly', true);
                data_set($col2, 'atype.label', 'Day Type');

                data_set($col2, 'lblsource.label', 'ORGINAL SCHEDULE:');
                data_set($col2, 'lblsource.style', 'font-size:11px;font-weight:bold;');
                $fields = ['lblcostuom', 'daytype', 'schedin', 'schedout'];
                if ($companyid == 53) {
                    if ($seqcount > 1) {
                        array_push($fields, 'lblforapproval', 'fempname', 'lblapproved', 'tempname');
                    } else {
                        array_push($fields, 'lblapproved', 'tempname');
                    }
                }
                $col3 = $this->fieldClass->create($fields);
                data_set($col3, 'schedin.type', 'input');
                data_set($col3, 'schedout.type', 'input');
                data_set($col3, 'daytype.readonly', true);
                data_set($col3, 'lblcostuom.label', 'CHANGE TO:'); // 'Shift Detail:'
                data_set($col3, 'lblcostuom.style', 'font-size:11px;font-weight:bold;');

                data_set($col3, 'lblforapproval.style', 'font-size:11px;font-weight:bold;');
                data_set($col3, 'lblapproved.style', 'font-size:11px;font-weight:bold;');

                data_set($col3, 'lblforapproval.label', 'HEAD DEPT. APPROVER');
                data_set($col3, 'lblapproved.label', 'HR/ PAYROLL APPROVER');

                data_set($col3, 'fempname.label', 'approver name');
                data_set($col3, 'tempname.label', 'approver name');


                break;
            case 'OT':
                $fields = ['daytype', 'schedin', 'schedout', 'clientname'];
                $col2 = $this->fieldClass->create($fields);
                data_set($col2, 'daytype.readonly', true);
                data_set($col2, 'schedin.type', 'input');
                data_set($col2, 'schedout.type', 'input');
                data_set($col2, 'schedin.label', 'OT Timein');
                data_set($col2, 'schedout.label', 'OT Timeout');

                $fields = [['lblrem', 'lblmessage'], ['othrs', 'apothrs'], ['othrsextra', 'apothrsextra'], ['ndiffhrs', 'apndiffhrs'], ['ndiffothrs', 'apndiffothrs']];
                if ($companyid == 53) {
                    if ($seqcount > 1) {
                        array_push($fields, 'lblforapproval', 'fempname', 'lblapproved', 'tempname');
                    } else {
                        array_push($fields, 'lblapproved', 'tempname');
                    }
                }
                $col3 = $this->fieldClass->create($fields);
                data_set($col3, 'othrs.type', 'input');
                data_set($col3, 'othrs.label', 'OT Hrs');
                data_set($col3, 'apothrs.label', 'Approved OT Hrs');

                data_set($col3, 'apothrs.readonly', true);
                data_set($col3, 'apndiffhrs.readonly', true);
                data_set($col3, 'apothrsextra.readonly', true);
                data_set($col3, 'apndiffothrs.readonly', true);

                data_set($col3, 'lblrem.label', 'Computed Hours: ');
                data_set($col3, 'lblrem.style', 'font-weight:bold;font-size:11px;');
                data_set($col3, 'lblmessage.label', 'Approved Hours: ');
                data_set($col3, 'lblmessage.style', 'font-weight:bold;font-size:11px;');

                data_set($col3, 'lblforapproval.style', 'font-size:11px;font-weight:bold;');
                data_set($col3, 'lblapproved.style', 'font-size:11px;font-weight:bold;');

                data_set($col3, 'lblforapproval.label', 'HEAD DEPT. APPROVER');
                data_set($col3, 'lblapproved.label', 'HR/ PAYROLL APPROVER');

                data_set($col3, 'fempname.label', 'approver name');
                data_set($col3, 'tempname.label', 'approver name');
                break;
            case 'OB':
                $fields = ['scheddate', 'schedin'];
                $col2 = $this->fieldClass->create($fields);
                data_set($col2, 'schedin.type', 'input');
                data_set($col2, 'schedin.label', 'Applied Date Time');
                data_set($col2, 'scheddate.style', 'padding:0px');
                $fields = ['atype', 'location'];
                if ($companyid == 53) {
                    if ($seqcount > 1) {
                        array_push($fields, 'lblforapproval', 'fempname', 'lblapproved', 'tempname');
                    } else {
                        array_push($fields, 'lblapproved', 'tempname');
                    }
                }
                $col3 = $this->fieldClass->create($fields);
                data_set($col3, 'atype.type', 'input');
                data_set($col3, 'atype.name', 'atype');
                data_set($col3, 'atype.readonly', true);
                data_set($col3, 'atype.label', 'Day Type');

                data_set($col3, 'lblforapproval.style', 'font-size:11px;font-weight:bold;');
                data_set($col3, 'lblapproved.style', 'font-size:11px;font-weight:bold;');

                data_set($col3, 'lblforapproval.label', 'HEAD DEPT. APPROVER');
                data_set($col3, 'lblapproved.label', 'HR/ PAYROLL APPROVER');

                data_set($col3, 'fempname.label', 'approver name');
                data_set($col3, 'tempname.label', 'approver name');

                break;
            case 'LEAVE':
                data_set($col1, 'createdate.label', 'Date Applied');
                $fields = ['acnoname', ['days', 'bal'], 'effectdate', 'hours'];
                $col2 = $this->fieldClass->create($fields);

                data_set($col2, 'hours.label', 'Leave ' . $leavelabel);
                data_set($col2, 'hours.readonly', true);
                data_set($col2, 'days.readonly', true);
                data_set($col2, 'acnoname.label', 'Leave Type');
                data_set($col2, 'acnoname.readonly', true);
                data_set($col2, 'effectdate.style', 'padding:0px');

                $fields = [];
                if ($companyid == 53) {
                    if ($seqcount > 1) {
                        array_push($fields, 'lblforapproval', 'fempname', 'lblapproved', 'tempname');
                    } else {
                        array_push($fields, 'lblapproved', 'tempname');
                    }
                }
                $col3 = $this->fieldClass->create($fields);

                data_set($col3, 'lblforapproval.style', 'font-size:11px;font-weight:bold;');
                data_set($col3, 'lblapproved.style', 'font-size:11px;font-weight:bold;');

                data_set($col3, 'lblforapproval.label', 'HEAD DEPT. APPROVER');
                data_set($col3, 'lblapproved.label', 'HR/ PAYROLL APPROVER');

                data_set($col3, 'fempname.label', 'approver name');
                data_set($col3, 'tempname.label', 'approver name');
                break;
            case 'LOAN':

                $fields = ['dateid', 'atype', 'effectdate'];
                $col2 = $this->fieldClass->create($fields);
                data_set($col2, 'dateid.type', 'input');
                data_set($col2, 'atype.type', 'input');
                data_set($col2, 'atype.name', 'atype');
                data_set($col2, 'atype.readonly', true);
                data_set($col2, 'effectdate.style', 'padding:0px');
                $fields = ['amt', 'amortization', 'bal'];
                if ($companyid == 53) {
                    if ($seqcount > 1) {
                        array_push($fields, 'lblforapproval', 'fempname', 'lblapproved', 'tempname');
                    } else {
                        array_push($fields, 'lblapproved', 'tempname');
                    }
                }
                $col3 = $this->fieldClass->create($fields);
                data_set($col3, 'amt.label', 'Loan Amount');
                data_set($col3, 'amortization.readonly', true);

                data_set($col3, 'lblforapproval.style', 'font-size:11px;font-weight:bold;');
                data_set($col3, 'lblapproved.style', 'font-size:11px;font-weight:bold;');

                data_set($col3, 'lblforapproval.label', 'HEAD DEPT. APPROVER');
                data_set($col3, 'lblapproved.label', 'HR/ PAYROLL APPROVER');

                data_set($col3, 'fempname.label', 'approver name');
                data_set($col3, 'tempname.label', 'approver name');
                break;
        }

        $fields = ['disapproved'];
        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'disapproved.label', 'DISAPPROVED');
        data_set($col4, 'disapproved.confirm', true);
        data_set($col4, 'disapproved.confirmlabel', "Disapproved this " . $type . " Application");
        data_set($col4, 'disapproved.color', 'red');

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function paramsdata($config)
    {
        $line = $config['params']['row']['line'];
        $type = $config['params']['row']['type'];

        switch ($type) {
            case 'OT':
                $query = "
                select ot.line, client.clientname,emp.email,date(ot.createdate) as createdate,date(ot.dateid) as dateid,
                ot.empid,'OT' as type,ot.rem as remarks,ot.remarks as remark,ot.othrs,ot.apothrs,ot.ndiffhrs, ot.apndiffhrs,
                ot.othrsextra,ot.apothrsextra,ot.ndiffothrs,ot.apndiffothrs,date(ot.scheddate) as scheddate,dayname(ot.scheddate) as dayname,
                date_format(ot.ottimein, '%Y-%m-%d %h:%i %p') as schedin, date_format(ot.ottimeout, '%Y-%m-%d %h:%i %p') as schedout,ot.daytype,
                app.clientname as tempname,app2.clientname as fempname
                from otapplication as ot 
                left join client on client.clientid=ot.empid
                left join batch on batch.line = ot.batchid
                left join employee as emp on emp.empid = ot.empid 
                left join client as app on app.email = ot.approvedby 
                left join client as app2 on app2.email = ot.approvedby2 
	            where ot.otstatus = 2 and ot.line = ? and 'OT' = '" . $type . "'";
                break;
            case 'OB':
                $query = "
                select ob.line, client.clientname,emp.email,date(ob.createdate) as createdate, date(ob.dateid) as dateid,
                ob.empid, 'OB' as type,ob.rem as remarks ,ob.approverem as remark,ob.type as atype,ob.location,date(ob.dateid) as datetime,
                date_format(ob.dateid, '%Y-%m-%d %h:%i %p') as schedin,date(ob.scheddate) as scheddate,dayname(ob.scheddate) as dayname,
                ob.disapproved_remarks2 as reason2,app.clientname as tempname,app2.clientname as fempname
                from obapplication as ob left join client on client.clientid=ob.empid
                left join employee as emp on emp.empid = ob.empid 
                left join client as app on app.email = ob.approvedby 
                left join client as app2 on app2.email = ob.approvedby2 
                where ob.status = 'A' and ob.line =? and 'OB' = '" . $type . "'";

                break;
            case 'LEAVE':
                $query = " 
                select l.trno , l.line ,client.clientname,emp.email, date(l.dateid) as createdate,date(l.dateid) as dateid,date(l.effectivity) as effectdate,
                l.empid,'LEAVE' as type, l.remarks,ls.days,ls.bal,p.codename as acnoname, l.adays as hours,l.disapproved_remarks as reason1,l.disapproved_remarks2 as reason2,
                app.clientname as tempname,app2.clientname as fempname
		        from leavetrans  as l
                left join leavesetup as ls on ls.trno = l.trno
                left join client on client.clientid=l.empid
                left join employee as emp on emp.empid = l.empid
                left join paccount as p on p.line=ls.acnoid
                left join client as app on app.email = l.approvedby_disapprovedby 
                left join client as app2 on app2.email = l.approvedby_disapprovedby2 
		        where l.status in ('A','P') and l.status2 in ('A','P') and concat(l.trno,'~',l.line) =? and 'LEAVE' = '" . $type . "'";

                break;
            case 'SCHED':
                $query = "
                select cs.line, client.clientname,emp.email,date(cs.createdate) as createdate,date(cs.dateid) as dateid,
                cs.empid,'SCHED' as type,cs.rem as remarks,cs.disapproved_remarks as remark,
                date_format(cs.schedin, '%Y-%m-%d %h:%i %p') as schedin,date_format(cs.schedout, '%Y-%m-%d %h:%i %p') as schedout,
                date_format(tm.schedin, '%Y-%m-%d %h:%i %p') as orgschedin,date_format(tm.schedout, '%Y-%m-%d %h:%i %p') as orgschedout,
                cs.daytype,cs.orgdaytype as atype,cs.disapproved_remarks as dremarks,app.clientname as tempname,app2.clientname as fempname
                from changeshiftapp as cs 
                left join timecard as tm on tm.empid = cs.empid and tm.dateid = date(cs.dateid)
                left join client on client.clientid=cs.empid
                left join employee as emp on emp.empid = cs.empid
                where cs.status= 1 and cs.line = ? and 'SCHED' = '" . $type . "'";
                break;
            case 'LOAN':
                $query = "
                select l.trno as line, client.clientname,emp.email,date(l.dateid) as createdate,date(l.dateid) as dateid,date(l.effdate) as effectdate,
                l.empid,'LOAN' as type,l.remarks, l.disapproved_remarks as remark, format(l.amt,2) as amt, pa.codename as atype,format(l.amortization,2) as amortization,l.balance as bal,
                app.clientname as tempname,app2.clientname as fempname
                from loanapplication as l left join client on client.clientid=l.empid
                left join employee as emp on emp.empid = l.empid
                left join paccount as pa on pa.line = l.acnoid
                left join client as app on app.email = l.approvedby_disapprovedby and app.email <> ''
                left join client as app2 on app2.email = l.approvedby_disapprovedby2 and app2.email <> ''
                where l.status='A' and l.trno = ?  and 'LOAN' = '" . $type . "'";

                break;
        }
        return $this->coreFunctions->opentable($query, [$line]);
    }

    public function data()
    {
        return [];
    }

    public function loaddata($config)
    {
        $companyid = $config['params']['companyid'];
        $type = $config['params']['dataparams']['type'];
        $dateid = $config['params']['dataparams']['createdate'];
        $empname = $config['params']['dataparams']['clientname'];
        $email = $config['params']['dataparams']['email'];
        $remark = $config['params']['dataparams']['remarks'];
        $action = $config['params']['action2'];

        if (isset($config['params']['dataparams']['line'])) {
            $line = $config['params']['dataparams']['line'];
            if ($action == 'disapproved') {
                $label = 'Disapproved';
                $condition = ['line' => $line,'status' => 'A'];
                switch ($type) {

                    case 'OT':
                        $table = 'otapplication';
                        $modulename = ' OT APPLICACATION';

                        $daytype = $config['params']['dataparams']['daytype'];
                        $ndiffhrs = $config['params']['dataparams']['ndiffhrs'];
                        $ndiffothrs = $config['params']['dataparams']['ndiffothrs'];
                        $othrs = $config['params']['dataparams']['othrs'];
                        $othrsextra = $config['params']['dataparams']['othrsextra'];
                        $schedin = $config['params']['dataparams']['schedin'];
                        $schedout = $config['params']['dataparams']['schedout'];
                        $scheddate = $config['params']['dataparams']['scheddate'];
                        $dayname = $config['params']['dataparams']['dayname'];
                        $reason = $config['params']['dataparams']['remark'];
                        $condition = ['line' => $line, 'otstatus' => 2];
                        $data = [
                            'otstatus' => 3,
                            'disapprovedby' => $config['params']['user'],
                            'disapprovedate' => $this->othersClass->getCurrentTimeStamp(),
                            // 'remarks' => $remark,
                        ];
                        break;
                    case 'OB':
                        $table = 'obapplication';
                        $modulename = ' OB APPLICACATION';

                        $scheddate = $config['params']['dataparams']['scheddate'];
                        $dayname = $config['params']['dataparams']['dayname'];
                        $date = $config['params']['dataparams']['dateid'];
                        $datetime = $config['params']['dataparams']['datetime'];
                        $atype = $config['params']['dataparams']['atype'];
                        $location = $config['params']['dataparams']['location'];
                        $reason1 = $config['params']['dataparams']['remark']; //reason1
                        $reason2 = $config['params']['dataparams']['reason2']; //reason2

                        $data = [
                            'status' => 'D',
                            'disapprovedby' => $config['params']['user'],
                            'disapprovedate' => $this->othersClass->getCurrentTimeStamp(),
                            // 'approverem' => $remark,
                        ];

                        break;
                    case 'LEAVE':
                        $table = 'leavetrans';
                        $modulename = ' LEAVE APPLICACATION';

                        $trno = $config['params']['dataparams']['trno'];
                        $condition = ['trno' => $trno, 'line' => $line, 'status' => 'A'];

                        $effectdate = $config['params']['dataparams']['effectdate'];
                        $reqleave = $config['params']['dataparams']['hours'];
                        $balance = $config['params']['dataparams']['bal'];
                        $entitled = $config['params']['dataparams']['days'];
                        $codename = $config['params']['dataparams']['acnoname'];
                        $reason1 = $config['params']['dataparams']['reason1']; //reason1
                        $reason2 = $config['params']['dataparams']['reason2']; //reason2
                        $data = [
                            'status' => 'D',
                            'date_disapproved' => $this->othersClass->getCurrentTimeStamp(),
                            'disapprovedby' => $config['params']['user'],
                            // 'disapproved_remarks' => $remark,
                        ];
                        break;
                    case 'LOAN':
                        $table = 'loanapplication';
                        $modulename = ' LOAN APPLICACATION';
                        $condition = ['trno' => $line, 'status' => 'A'];

                        $amount = $config['params']['dataparams']['amt'];
                        $amortization = $config['params']['dataparams']['amortization'];
                        $balance = $config['params']['dataparams']['bal'];
                        $effectdate = $config['params']['dataparams']['effectdate'];
                        $codename = $config['params']['dataparams']['atype'];
                        $reason = $config['params']['dataparams']['remark'];

                        $data = [
                            'status' => 'D',
                            'approvedby_disapprovedby' => $config['params']['user'],
                            'date_approved_disapproved' => $this->othersClass->getCurrentTimeStamp(),
                            // 'disapproved_remarks' => $remark
                        ];
                        break;
                    case 'SCHED':
                        $table = 'changeshiftapp';
                        $modulename = ' CHANGE SHIFT APPLICACATION';

                        $orgdaytype = $config['params']['dataparams']['atype'];
                        $daytype = $config['params']['dataparams']['daytype'];
                        $orgschedin = $config['params']['dataparams']['orgschedin'];
                        $orgschedout = $config['params']['dataparams']['orgschedout'];
                        $schedin = $config['params']['dataparams']['schedin'];
                        $schedout = $config['params']['dataparams']['schedout'];
                        $dremarks = $config['params']['dataparams']['dremarks'];
                        $remarks = $config['params']['dataparams']['remarks'];
                        $condition = ['line' => $line, 'status' => 1];
                        $data = [
                            'status' => 2,
                            // 'disapproved_remarks' => $remark,
                            'disapprovedby' => $config['params']['user'],
                            'disapproveddate' => $this->othersClass->getCurrentTimeStamp()
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
                    $params['muduletype'] = $type;
                    $params['companyid'] = $companyid;
                    $params['isapp'] = '';

                    switch ($type) {
                        case 'SCHED':
                            $params['schedin'] = $schedin;
                            $params['schedout'] = $schedout;
                            $params['orgschedin'] = $orgschedin;
                            $params['orgschedout'] = $orgschedout;
                            $params['daytype'] = $daytype;
                            $params['orgdaytype'] = $orgdaytype;
                            $params['dremarks'] = $dremarks;
                            $params['remarks'] = $remarks;

                            break;
                        case 'OT':
                            $params['scheddate'] = $scheddate . " (" . $dayname . ")";
                            $params['ottimein'] = $schedin;
                            $params['ottimeout'] = $schedout;
                            $params['rem'] = $remark;
                            $params['othrs'] = $othrs;
                            $params['othrsextra'] = $othrsextra;
                            $params['ndiffhrs'] = $ndiffhrs;
                            $params['ndiffothrs'] = $ndiffothrs;
                            $params['daytype'] = $dayname;
                            $params['approvedstatus'] = $label;
                            $params['createdate'] = $dateid;
                            $params['reason1'] = $reason;
                            break;
                        case 'OB':
                            $params['scheddate'] = $scheddate . " (" . $dayname . ")";
                            $params['dateid'] = $date;
                            $params['rem'] = $remark;
                            $params['datetime'] = $datetime;
                            $params['type'] = $atype;
                            $params['location'] = $location;
                            $params['approvedstatus'] = $label;
                            $params['reason1'] = $reason1;
                            $params['reason2'] = $reason2;

                            break;
                        case 'LEAVE':
                            $params['effdate'] = $effectdate;
                            $params['dateid'] = $dateid;
                            $params['adays'] = $reqleave;
                            $params['bal'] = $balance;
                            $params['entitled'] = $entitled;
                            $params['codename'] = $codename;
                            $params['remarks'] = $remark;
                            $params['companyid'] = $companyid;
                            $params['reason1'] = $reason1;
                            $params['reason2'] = $reason2;
                            break;
                        case 'LOAN':
                            $params['effdate'] = $effectdate;
                            $params['acnoname'] = $codename;
                            $params['amount'] = $amount;
                            $params['amortization'] = $amortization;
                            $params['remarks'] = $remark;
                            $params['balance'] = $balance;
                            $params['reason1'] = $reason;
                            break;
                    }
                    if (!empty($email)) {
                        $qry = "select clientname,email as approver from client where email = '" . $config['params']['user'] . "' ";
                        $data2 = $this->coreFunctions->opentable($qry);
                        $params['approver'] = $data2[0]->approver;
                        $params['appname'] = $data2[0]->clientname;
                        $params['email'] = $email;
                        $result = $this->linkemail->weblink($params);
                        if (!$result['status']) {
                            return ['status' => false, 'msg' => '' . $result['msg']];
                        }
                    }


                    // if (!$result['status']) {
                    // foreach ($tempdata as $key => $value) {
                    //     if (strpos($key, "date") !== false) { //find date = null
                    //         $tempdata[$key] = null;
                    //     } else { // status,dis/approved = ''
                    //         $tempdata[$key] = '';
                    //     }
                    // }
                    // $update = $this->coreFunctions->sbcupdate($table, $tempdata, $condition);
                    // return ['status' => false, 'msg' => 'Sending email failed: email was empty.'];
                    // }
                    $config['params']['doc'] = 'ALLAPPROVEDAPPLICATION';
                    $this->logger->sbcmasterlog($config['params']['dataparams']['line'], $config, $label . ' (' . $config['params']['dataparams']['clientname'] . ') - ' . $config['params']['dataparams']['dateid']);
                    return ['status' => true, 'msg' => 'Successfully ' . $label . ' ', 'data' => [], 'reloadsbclist' => true, 'action' => 'allapprovedapplication'];
                }
            }
        }

        return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => []];
    }
} //end class
