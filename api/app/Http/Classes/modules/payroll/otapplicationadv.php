<?php

namespace App\Http\Classes\modules\payroll;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;
use App\Http\Classes\common\payrollcommon;
use App\Http\Classes\common\linkemail;
use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\modules\payrollcustomform\payrollprocess;
use DateTime;
use Carbon\Carbon;

class otapplicationadv
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'OT APPLICATION';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $payrollprocess;
    private $othersClass;
    private $linkemail;
    private $payrollcommon;
    private $logger;
    private $sqlquery;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
    public $head = 'otapplication';
    public $prefix = '';
    public $tablelogs = 'payroll_log';
    public $tablelogs_del = '';
    private $stockselect;

    private $fields = [
        'dateid',
        'dateid2',
        'scheddate',
        'othrs',
        'ndiffhrs',
        'ottimein',
        'ottimeout',
        'isadv',
        'othrsextra',
        'rem',
        'daytype'

    ];
    private $except = ['clientid', 'client'];
    private $blnfields = [];
    public $showfilteroption = true;
    public $showfilter = true;
    public $showcreatebtn = true;
    private $reporter;
    private $isexist = 0;

    public $showfilterlabel = [
        ['val' => 'draft', 'label' => 'Entry', 'color' => 'primary'],
        ['val' => 'forapproval', 'label' => 'For Approval', 'color' => 'primary'],
        ['val' => 'approved', 'label' => 'Approved', 'color' => 'primary'],
        ['val' => 'disapproved', 'label' => 'Disapproved', 'color' => 'primary']
    ];


    public function __construct()
    {
        $this->btnClass = new buttonClass;
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
        $this->sqlquery = new sqlquery;
        $this->reporter = new SBCPDF;
        $this->linkemail = new linkemail;
        $this->payrollcommon = new payrollcommon;
        $this->payrollprocess = new payrollprocess;
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 4839,
            'edit' => 4839,
            'new' => 4839,
            'save' => 4839,
            'delete' => 5029,
            'print' => 5450

        );
        return $attrib;
    }
    public function createHeadbutton($config)
    {
        $btns = array(
            'load',
            'new',
            'save',
            'print',
            'delete',
            'cancel',
            'logs',
            'edit',
            'backlisting',
            'toggleup',
            'toggledown'
        );
        $buttons = $this->btnClass->create($btns);
        return $buttons;
    }
    public function createdoclisting($config)
    {
        $companyid = $config['params']['companyid'];

        $approver = $this->payrollcommon->checkapprover($config);
        $supervisor =  $this->payrollcommon->checksupervisor($config);
        if ($approver || $supervisor) {
            array_push($this->showfilterlabel, ['val' => 'approvedemp', 'label' => 'Approved Employees', 'color' => 'primary']);
        }
        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='OT'");
        if ($approversetup == '') {
            $approversetup = $this->approvers($config['params']);
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

        $getcols = ['action', 'createdate', 'clientname', 'scheddate', 'othrs', 'daytype', 'schedstarttime', 'schedendtime', 'rem', 'listappstatus2', 'date_approved_disapprovedsup', 'approvedby_disapprovedbysup', 'rem2', 'listappstatus', 'apothrs', 'date_approved_disapproved', 'approvedby_disapprovedby', 'remarks'];

        // if ($companyid == 53) { //camera
        //     $getcols = ['action', 'createdate', 'clientname', 'scheddate', 'othrs', 'daytype', 'schedstarttime', 'schedendtime', 'rem', 'listappstatus2', 'date_approved_disapprovedsup', 'approvedby_disapprovedbysup', 'rem1', 'listappstatus', 'apothrs', 'date_approved_disapproved', 'approvedby_disapprovedby', 'remarks'];
        // }
        // if ($companyid == 51) {
        //     $getcols = ['action', 'createdate', 'clientname', 'scheddate', 'othrs', 'othrsextra', 'ndiffothrs', 'daytype', 'schedstarttime', 'schedendtime', 'rem', 'apothrs', 'apothrsextra', 'apndiffothrs', 'listappstatus2', 'date_approved_disapprovedsup', 'approvedby_disapprovedbysup', 'rem1',  'listappstatus', 'date_approved_disapproved', 'approvedby_disapprovedby', 'remarks'];
        // }

        if ($companyid == 58) { //cdo
            $empid = isset($config['params']['adminid']) ? $config['params']['adminid'] : 0;
            if ($empid == 0) {
                // $this->showcreatebtn = false;
            }
        }
        if ($companyid == 53) { //camera
            array_push($getcols, 'void_date', 'void_approver', 'void_remarks');
        }

        foreach ($getcols as $key => $value) {
            $$value = $key;
        }
        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[$listappstatus]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;text-align:left;';
        $cols[$rem]['label'] = 'Remarks';
        $cols[$rem]['style'] = 'width:300px;whiteSpace:normal;min-width:300px;';
        $cols[$listappstatus2]['type'] = 'label';
        $cols[$listappstatus2]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;text-align:left;';

        $cols[$apothrs]['label'] = 'Approved OT Hours';
        $cols[$othrs]['style'] = 'width:80px;whiteSpace:normal;min-width:100px;';
        $cols[$listappstatus]['type'] = 'label';
        $cols[$createdate]['label'] = 'Applied Date';
        $cols[$createdate]['style'] = 'width:120px;whiteSpace:normal;min-width:120px;';
        $cols[$rem2]['style'] = 'width:120px;whiteSpace:normal;min-width:120px;';
        $cols[$rem2]['label'] = 'Remarks';
        $cols[$clientname]['label'] = 'Name';
        $cols[$clientname]['style'] = 'width:150px;whiteSpace:normal;min-width:150px;';
        switch ($companyid) {
            case 53:; //camera
                $cols[$rem]['label'] = 'Reason';
                $cols[$rem]['style'] = 'width:400px;whiteSpace:normal;min-width:400px;';
                $cols[$date_approved_disapproved]['label'] = 'Date Approved/Disapproved (Hr/Payroll Approver)';
                $cols[$approvedby_disapprovedby]['label'] = 'Approved/Disapproved By (Hr/Payroll Approver)';

                $cols[$schedstarttime]['label'] = 'Date Time/ Time In';
                $cols[$schedendtime]['label'] = 'Date Time/ Time Out';
                $cols[$schedendtime]['style'] = 'text-align:left;';
                $cols[$remarks]['label'] = 'Hr/Payroll Approver Reason';
                $cols[$listappstatus2]['label'] = 'Head Dept. Approver Status';
                $cols[$listappstatus]['label'] = 'Hr/Payroll Approver Status';

                $cols[$scheddate]['style'] = 'width:120px;whiteSpace:normal;min-width:150px;';
                $cols[$rem2]['label'] = 'Head Dept. Approver Reason';
                $cols[$date_approved_disapprovedsup]['label'] = 'Date Approved/Disapproved Head Dept Approver';
                $cols[$date_approved_disapprovedsup]['style'] = 'width:200px;whiteSpace:normal;min-width:200px;';
                $cols[$approvedby_disapprovedbysup]['label'] = 'Approved/Disapproved By Head Dept Approver';

                $cols[$void_remarks]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
                $cols[$void_remarks]['label'] = 'Reason';
                $cols[$void_date]['type'] = 'label';
                $cols[$void_approver]['type'] = 'label';

                break;
            case 51:; //ulitc
                $cols[$date_approved_disapproved]['label'] = 'Date Approved/Disapproved';
                $cols[$approvedby_disapprovedby]['label'] = 'Approved/Disapproved';

                $cols[$schedstarttime]['label'] = 'Date Time/ Time In';
                $cols[$schedendtime]['label'] = 'Date Time/ Time Out';
                $cols[$schedendtime]['style'] = 'text-align:left;';
                $cols[$remarks]['label'] = 'Approved Remarks';
                $cols[$scheddate]['style'] = 'width:120px;whiteSpace:normal;min-width:150px;';
                break;
            default:
                $cols[$clientname]['style'] = 'width:200px;whiteSpace:normal;min-width:200px;';
                $cols[$schedstarttime]['type'] = 'coldel';
                $cols[$schedendtime]['type'] = 'coldel';

                break;
        }
        $cols[$apothrs]['type'] = 'coldel';
        if (count($approversetup) == 1) {
            $cols[$listappstatus2]['type'] = 'coldel';
            $cols[$date_approved_disapprovedsup]['type'] = 'coldel';
            $cols[$approvedby_disapprovedbysup]['type'] = 'coldel';
            $cols[$rem2]['type'] = 'coldel';
        }
        $cols = $this->tabClass->delcollisting($cols);
        return $cols;
    }

    public function loaddoclisting($config)
    {
        $id = $config['params']['adminid'];
        $user = $config['params']['user'];
        $date1 = date('Y-m-d', strtotime($config['params']['date1']));
        $date2 = date('Y-m-d', strtotime($config['params']['date2']));
        $companyid = $config['params']['companyid'];
        $filteroption = '';
        $option = $config['params']['itemfilter'];

        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='OT'");
        $both = false;
        if ($approversetup == '') {
            $approversetup = $this->approvers($config['params']);
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
        $entry = "and ot.otstatus=1";
        $approver = " and ot.otstatus=2"; //default
        $svapprover = "";
        $disapp = "and ot.otstatus=3";
        if ($config['params']['companyid'] == 44) { //stonepro || CAMERA
            $entry = "and (ot.otstatus=1 and ot.otstatus2=1)"; //pending
            $svapprover = " and ot.otstatus2 = 2"; //suppervisor
            $approver = " and ot.otstatus = 2 "; // approver
            $disapp = " and (ot.otstatus=3 or ot.otstatus2 = 3) "; //disapprove
        }

        switch ($option) {
            case 'draft':
                $filteroption = "where ot.empid=" . $id . " " . $entry . " and ot.submitdate is null";
                if ($companyid == 44) { //stonepro
                    $filteroption = "where ot.empid=" . $id . " " . $entry . " and ot.submitdate is null";
                }
                break;
            case 'forapproval':
                if ($companyid == 53 || $companyid == 44) { //camera and stonepro
                    $filteroption = "where ot.empid=" . $id . " and (ot.otstatus = 1 and (ot.otstatus2 = 1 or ot.otstatus2 = 2))   and ot.submitdate is not null";
                } else {
                    $filteroption = "where ot.empid=" . $id . " " . $entry . " and ot.submitdate is not null";
                }

                break;
            case 'approved':
                $filteroption = "where ot.empid=$id $approver $svapprover";
                break;
            case 'disapproved':
                if ($companyid == 53) { //camera
                    $filteroption = "where ot.empid=$id and (ot.otstatus=3 or ot.otstatus2 = 3)";
                } else {
                    $filteroption = "where ot.empid=$id $disapp";
                }
                break;
            default:
                $isapprover = $this->coreFunctions->getfieldvalue("employee", "isapprover", "empid=?", [$id]);
                $issupervisor = $this->coreFunctions->getfieldvalue("employee", "issupervisor", "empid=?", [$id]);
                if ($isapprover == 1) {
                    $filteroption = "where ot.otstatus = 2 and ot.approvedby='" . $user . "'";
                } else if ($issupervisor == 1) {
                    $filteroption = " where ot.otstatus2 = 2 and ot.approvedby2='" . $user . "'";
                } else {
                    return ['data' => [], 'status' => false, 'msg' => 'This feature is for approvers only.'];
                }
                break;
        }
        $filtersearch = "";
        if (isset($config['params']['search'])) {
            $searchfield = ['ot.empid', 'client.client', 'client.clientname', 'ot.othrs', 'ot.apothrs', 'ot.otstatus'];

            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
            $limit = "";
        }
        $leftjion = "";
        $addfields = "";

        $status2 = "";
        switch ($config['params']['companyid']) {
            case 44: // stonepro
                $casestat = ", case
                    when ot.otstatus = 1 && ot.otstatus2 = 1 then 'ENTRY'
                    when ot.otstatus = 1 && ot.otstatus2 = 2 then 'APPROVED (Supervisor)'
                    when ot.otstatus = 2 && ot.otstatus2 = 2  then 'APPROVED'
                    when ot.otstatus2 = 3 then 'DISAPPROVED (Supervisor)'
                    when ot.otstatus = 3 then 'DISAPPROVED (Approver)'
                    END as jstatus";
                $status2 = ", case
                    when ot.otstatus = 1 and ot.otstatus2 = 1 and ot.submitdate is null then 'ENTRY'
                    when ot.otstatus = 1 and ot.otstatus2 = 1 and ot.submitdate is not null then 'FOR APPROVAL'
                    when ot.otstatus2 = 2 then 'APPROVED'
                    when ot.otstatus2 = 3 then 'DISAPPROVED' end as status2 ";
                break;
            case 53: // camera
                $casestat = ", case
                    when ot.otstatus = 1 && ot.submitdate is null then 'ENTRY'
                    when ot.otstatus = 1 && ot.otstatus2 = 2 && ot.submitdate is not null then 'FOR APPROVAL'
                    when ot.otstatus = 2 && ot.otstatus2 = 2  then 'APPROVED'
                    when ot.otstatus = 3 then 'DISAPPROVED' end as jstatus";

                $status2 = ", case
                    when ot.otstatus2 = 1 && ot.submitdate is null then 'ENTRY'
                    when ot.otstatus2 = 1 && ot.otstatus = 1 && ot.submitdate is not null then 'FOR APPROVAL'
                    when ot.otstatus2 = 2 then 'APPROVED'
                    when ot.otstatus2 = 3 then 'DISAPPROVED' end as status2 ";
                $leftjion = "left join client as void on void.email = ot.void_by and void.email <>''";
                $addfields = ",void.clientname as void_approver,ot.void_date,ot.void_remarks ";
                break;
            default:
                if (count($approversetup) > 1 || $both) {
                    $casestat = ", case
                    when ot.otstatus = 1 && ot.submitdate is null then 'ENTRY'
                    when ot.otstatus = 1 && ot.submitdate is not null then 'FOR APPROVAL'
                    when ot.otstatus = 2 then 'APPROVED'
                    when ot.otstatus = 3 then 'DISAPPROVED' end as jstatus";
                } else {
                    $casestat = "
                    , case
                    when ot.otstatus = 1 && ot.submitdate is null then 'ENTRY'
                    when ot.otstatus = 1 && ot.submitdate is not null then 'FOR APPROVAL'
                    when ot.otstatus = 2 then 'APPROVED'
                    when ot.otstatus = 3 then 'DISAPPROVED' end as jstatus,
                    case
                    when ot.otstatus2 = 1 && ot.submitdate is null then 'ENTRY'
                    when ot.otstatus2 = 1 && ot.submitdate is not null then 'FOR APPROVAL'
                    when ot.otstatus2 = 2 then 'APPROVED'
                    when ot.otstatus2 = 3 then 'DISAPPROVED' end as status2 ";
                }


                break;
        }

        $qry = "select client.clientid as empid,client.clientname,date_format(ot.dateid, '%m-%d-%y') as dateid,ot.line as trno,ot.line as clientid,ot.othrs,ot.apothrs,client.client,
        ot.ndiffhrs as ndiffot,ot.apndiffhrs,ot.othrsextra,ot.apothrsextra,ot.ndiffothrs,ot.apndiffothrs,date_format(ot.createdate, '%m-%d-%y') as createdate,ot.rem,ot.remarks,
        ot.disapproved_remarks2 as rem2,
        date_format(ottimein, '%m-%d-%y %h:%i %p') as schedstarttime, date_format(ottimeout, '%m-%d-%y %h:%i %p') as schedendtime,
        ifnull(appdis.clientname,app.clientname) as approvedby_disapprovedby,date_format(ifnull(ot.approvedate,ot.disapprovedate), '%m-%d-%y') as date_approved_disapproved,
        date_format(ifnull(ot.approvedate2,ot.disapprovedate2), '%m-%d-%y') as date_approved_disapprovedsup,ifnull(appdis2.clientname,app2.clientname) as approvedby_disapprovedbysup,
        date_format(ot.scheddate, '%m-%d-%y') as scheddate, ot.submitdate,ot.daytype $status2
        $casestat $addfields
    from $this->head as ot
    left join client on client.clientid=ot.empid
    left join client as app on app.email = ot.approvedby and app.email <> ''
    left join client as appdis on appdis.email = ot.disapprovedby and appdis.email <> ''

    left join client as app2 on app2.email = ot.approvedby2 and app2.email <> ''
    left join client as appdis2 on appdis2.email = ot.disapprovedby2 and appdis2.email <> ''
    $leftjion
    " . $filteroption . " and date(dateid) between '$date1' and '$date2'  $filtersearch
    order by scheddate desc";
        $data = $this->coreFunctions->opentable($qry);


        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
    }

    public function createTab($access, $config)
    {
        $companyid = $config['params']['companyid'];
        $fields = [];

        $col1 = $this->fieldClass->create($fields);
        $tab = [];

        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function createtab2($access, $config)
    {
        //testing for cdohris approver list tab
        $companyid = $config['params']['companyid'];

        if ($companyid == 58) { //cdohris
            $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entryapproverlist', 'label' => 'Approver List', 'access' => 'view']];
            $obj = $this->tabClass->createtab($tab, []);
            $return['APPROVER LIST'] = ['icon' => 'fa fa-users', 'tab' => $obj];
        } else {
            $return = [];
        }
        return $return;
    }

    public function createHeadField($config)
    {
        $companyid = $config['params']['companyid'];
        $fields = ['client', 'scheddate', ['dateid', 'ottimein',], ['dateid2', 'ottimeout']];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'client.label', 'Code');
        data_set($col1, 'client.type', 'hidden');
        data_set($col1, 'client.required', false);
        data_set($col1, 'othrs.readonly', false);
        data_set($col1, 'apothrs.readonly', true);
        data_set($col1, 'dateid.label', 'Date In');
        data_set($col1, 'dateid2.label', 'Date Out');
        data_set($col1, 'scheddate.required', true);
        data_set($col1, 'ottimein.required', false);
        data_set($col1, 'ottimein.class', 'csottimein');


        $fields = [['status', 'daytype'], 'rem'];
        $col2 = $this->fieldClass->create($fields);
        if ($companyid == 53) { //camera
            data_set($col2, 'rem.label', 'Reason');
        }
        data_set($col2, 'rem.required', true);
        $fields = [];
        $col3 = $this->fieldClass->create($fields);

        $fields = [['lblrem', 'lblmessage'], ['othrs', 'apothrs'], ['othrsextra', 'apothrsextra'], ['ndiffot', 'apndiffothrs'], 'lblsubmit', 'submit'];
        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'ndiffot.label', 'N-Diff OT Hours');
        data_set($col4, 'ndiffhrs.label', 'N-Diff Hours');
        data_set($col4, 'ndiff.label', 'Computed Hours: ');
        data_set($col4, 'othrs.label', 'OT Hours (Working)');
        data_set($col4, 'othrs.readonly', false);
        data_set($col4, 'othrs.class', 'csothrs sbccsreadonly');
        data_set($col4, 'othrsextra.readonly', false);
        data_set($col4, 'othrsextra.class', 'csothrsextra sbccsreadonly');
        data_set($col4, 'lblrem.label', 'Computed Hours: ');
        data_set($col4, 'lblrem.style', 'font-weight:bold;font-size:11px;');
        data_set($col4, 'lblmessage.label', 'Approved Hours: ');
        data_set($col4, 'lblmessage.style', 'font-weight:bold;font-size:11px;');
        data_set($col4, 'lblsubmit.style', 'font-weight:bold;font-size:15px;font-family:Century Gothic;color: green;');
        data_set($col4, 'apothrs.label', 'OT Hours');
        data_set($col4, 'apndiffhrs.label', 'N-Diff Hours');
        data_set($col4, 'apothrsextra.label', 'OT > 8 Hours');
        data_set($col4, 'apndiffothrs.label', 'N-Diff OT Hours');
        data_set($col4, 'lblsubmit.label', 'FOR APPROVAL');
        data_set($col4, 'submit.label', 'FOR APPROVAL');

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }
    public function newclient($config)
    {
        $data = $this->resetdata($config['newclient'], $config['params']);
        $hideobj = [];
        $companyid = $config['params']['companyid'];
        $hideobj['submit'] = true;
        $hideobj['lblsubmit'] = true;
        return  ['head' => $data, 'islocked' => false, 'isposted' => false, 'status' => true, 'isnew' => true, 'msg' => 'Ready for New Ledger', 'hideobj' => $hideobj];
    }
    private function resetdata($client = '', $config)
    {
        $data = [];
        $data[0]['clientid'] = 0;
        $data[0]['client'] = $client;
        $data[0]['dateid'] = $this->othersClass->getCurrentDate();
        $data[0]['dateid2'] = $this->othersClass->getCurrentDate();
        $data[0]['othrs'] = 0;
        $data[0]['apothrs'] = 0;
        $data[0]['status'] = 'ENTRY';
        $data[0]['rem'] = '';
        $data[0]['isadv'] = 1;

        $data[0]['ottimein'] = '00:00';
        $data[0]['ottimeout'] = '00:00';
        $data[0]['ndiffhrs'] = '0.0';
        $data[0]['apndiffhrs'] = '0.0';
        $data[0]['othrsextra'] = 0;
        $data[0]['apothrsextra'] = 0;
        $data[0]['apndiffothrs'] = '0.0';
        $data[0]['ndiffot'] = '0.0';
        $data[0]['daytype'] = '';
        $data[0]['createdate'] = $this->othersClass->getCurrentDate();
        $data[0]['scheddate'] = $this->othersClass->getCurrentDate();
        $data[0]['submitdate'] = null;
        $data[0]['divid'] = 0;
        $data[0]['emploc'] = '';
        // $data[0]['forapproval'] = null;
        // $data[0]['lblforapproval'] = '';
        $data[0]['initialapp'] = null;
        if ($config['companyid'] == 53) { //camera
            $data[0]['divid'] = $this->coreFunctions->datareader("select divid as value from employee where empid=?", [$config['adminid']]);
        }
        return $data;
    }

    public function loadheaddata($config)
    {
        $trno = $config['params']['clientid'];
        $companyid = $config['params']['companyid'];
        $adminid = $config['params']['adminid'];


        $casestat = ", case
            when otadv.otstatus = 1 and otadv.otstatus2 = 1 and otadv.submitdate is null then 'ENTRY' 
            when otadv.otstatus = 1 and (otadv.otstatus2 = 1 or otadv.otstatus2 = 2) and otadv.submitdate is not null then 'FOR APPROVAL'
            when otadv.otstatus = 2 then 'APPROVED'
            when otadv.otstatus = 3 or otadv.otstatus2 = 3 then 'DISAPPROVED'
            end as status";


        $query = "select otadv.line as clientid, otadv.line as trno,otadv.othrs,otadv.apothrs,date(otadv.dateid) as dateid,date(otadv.dateid2) as dateid2,
        cl.client,cl.clientid as empid,otadv.rem,time(otadv.ottimein) as ottimein, time(otadv.ottimeout) as ottimeout,otadv.scheddate,emp.divid,
        otadv.ndiffhrs,otadv.apndiffhrs,otadv.createdate,otadv.othrsextra,otadv.apothrsextra,otadv.ndiffothrs as ndiffot,otadv.apndiffothrs,otadv.daytype,otadv.submitdate,otadv.approvedate,otadv.approvedate2,otadv.disapprovedate,otadv.disapprovedate2,
        emp.emploc
        $casestat
        from $this->head as otadv
        left join employee as emp on emp.empid = otadv.empid
        left join client as cl on cl.clientid = emp.empid
        where otadv.line = $trno and otadv.empid = '$adminid' ";
        $head =  $this->coreFunctions->opentable($query);
        if (!empty($head)) {
            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }
            $hideobj = [];

            $submitdate = $head[0]->submitdate != null ? true : false;
            $approvedate2 = $head[0]->approvedate2 != null ? true : false;
            $approvedate = $head[0]->approvedate != null ? true : false;

            $disapprovedate = $head[0]->disapprovedate != null ? true : false;
            $disapprovedate2 = $head[0]->disapprovedate2 != null ? true : false;

            if ($submitdate || $approvedate || $approvedate2 || $disapprovedate || $disapprovedate2) {
                // hide true / show false
                $hideobj['submit'] = true;
                switch ($head[0]->status) {
                    case 'ENTRY':
                        $hideobj['submit'] = false;
                        $hideobj['lblsubmit'] = true;
                        break;
                    case 'FOR APPROVAL':
                        $hideobj['lblsubmit'] = false;
                        break;
                    default:
                        $hideobj['lblsubmit'] = true;
                        break;
                }
            } else {
                $hideobj['submit'] = false;
                $hideobj['lblsubmit'] = true;
            }

            if ($head[0]->status == 'APPROVED') {
                $hideobj['lblsubmit'] = true;
            }



            return  ['head' => $head, 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => false, 'qq' => $config['params']['adminid'], 'hideobj' => $hideobj, 'action' => 'backlisting'];
        } else {
            $msg = 'Data Fetched Failed, either somebody already deleted the transaction or modified.';
            $head = $this->resetdata('', $config['params']);
            return ['status' => false, 'isnew' => true, 'head' => $head, 'msg' => $msg, 'action' => 'backlisting'];
        }
    }
    public function updatehead($config, $isupdate)
    {
        $head = $config['params']['head'];
        $empid = $config['params']['adminid'];
        $companyid = $config['params']['companyid'];
        $data = [];
        if ($companyid == 58) { //cdo
            $chkrestriction = $this->payrollcommon->checkportalrestrict($head, $config);
            if (!empty($chkrestriction['msg'])) {
                $msg = $chkrestriction['msg'];
                return ['status' => false, 'msg' => $msg];
            }
        }

        if (isset($head['trno'])) {

            $submitdate = $this->coreFunctions->datareader("select submitdate as value from $this->head where line=? ", [$head['trno']], '', true);
            if ($companyid == 53) { //camera
                if ($submitdate) {
                    $url = 'App\Http\Classes\modules\payroll\\' . 'otapplicationadv';
                    $result = $this->payrollcommon->checkapplicationstatus($config, $head['trno'], $url, $submitdate);
                    if (!$result['status']) {
                        return ['status' => false, 'msg' => $result['msg'], 'clientid' => $config['params']['adminid']];
                    }
                }
            } else {
                $approved = $this->coreFunctions->datareader("select approvedate as value from $this->head where line=? and approvedate is not null", [$head['trno']]);
                $svapproved = $this->coreFunctions->datareader("select approvedate2 as value from $this->head where line=? and approvedate2 is not null", [$head['trno']]);
                if ($approved || $svapproved) {
                    return ['status' => false, 'msg' => 'Cannot update; already Approved.', 'clientid' => $config['params']['adminid']];
                }
                $disapproved = $this->coreFunctions->datareader("select disapprovedate as value from $this->head where line=? and disapprovedate is not null", [$head['trno']]);
                $disapproved2 = $this->coreFunctions->datareader("select disapprovedate2 as value from $this->head where line=? and disapprovedate2 is not null", [$head['trno']]);
                if ($disapproved || $disapproved2) {
                    return ['status' => false, 'msg' => 'Cannot update; already Disapproved.', 'clientid' => $config['params']['adminid']];
                }
                if ($submitdate) {
                    return ['status' => false, 'msg' => 'Cannot update; already For approval.', 'clientid' => $config['params']['clientid']];
                }
            }
        }

        $msg = '';
        foreach ($this->fields as $key) {
            if (array_key_exists($key, $head)) {
                $data[$key] = $head[$key];
                if (!in_array($key, $this->except)) {
                    $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
                } //end if 
            }
        }
        $data['dateid'] = date('Y-m-d', strtotime($data['dateid']));
        $data['dateid2'] = date('Y-m-d', strtotime($data['dateid2']));
        $scheddate =  date('Y-m-d', strtotime($data['scheddate']));

        if ($scheddate != $data['dateid']) {
            $schedule = $this->coreFunctions->opentable("select date(schedin) as schedin,date(schedout) as schedout,date_format(schedin, '%Y-%m-%d %h:%i %p') as showin,date_format(schedout, '%Y-%m-%d %h:%i %p') as showout from timecard where empid='" . $empid . "' and dateid = '" . $scheddate . "'");

            if (!empty($schedule)) {
                if ($schedule[0]->schedout != $data['dateid']) {
                    return ['status' => false, 'msg' => "OT application must be in range of the schedule <br> (" . $schedule[0]->showin . " to " . $schedule[0]->showout . ")"];
                }
            }
        }
        if ($companyid != 53) {
            if ($head['submitdate'] != null) { // foremail
                return ['status' => false, 'msg' => 'Cannot update; already submitted.'];
            }
        }
        if ($companyid == 53) { // camera
            $checkcutoffdate = $this->payrollcommon->checkbatchsched($scheddate, $head['divid']);
            if (!empty($checkcutoffdate['msg'])) {
                $msg = $checkcutoffdate['msg'];
                return ['status' => false, 'msg' => $msg];
            }
        }

        $empname = $this->coreFunctions->getfieldvalue("client", "clientname", "clientid='" . $empid . "'");

        $ottimein = new DateTime($data['dateid'] . $data['ottimein']);
        $ottimeout = new DateTime($data['dateid2'] . $data['ottimeout']);

        if ($data['dateid2'] == $data['dateid']) {
            if ($ottimeout < $ottimein) {
                return ['status' => false, 'msg' => 'OT Out must be greater than OT In'];
            }
        }

        $daytype = $this->coreFunctions->datareader("select daytype as value from holidayloc where date_format(dateid, '%Y-%m-%d') = ? and description = '" . $head['emploc'] . "'", [date('Y-m-d', strtotime($data['dateid']))]);
        if (empty($daytype)) {
            $daytype = $this->coreFunctions->datareader("select daytype as value from holiday where date_format(dateid, '%Y-%m-%d') = ?", [date('Y-m-d', strtotime($data['dateid']))]);
            if (empty($daytype)) {
                $daytype = $this->coreFunctions->datareader("select daytype as value from timecard where empid=? and date_format(dateid, '%Y-%m-%d') = ?", [$empid, date('Y-m-d', strtotime($data['dateid']))]);
            }
        }

        $data['daytype'] = $daytype;
        if ($companyid == 51) { //ulitc
            if ($ottimein->format('l') == 'Saturday') {
                if ($daytype == 'RESTDAY') {
                    $daytype = 'WORKING';
                    $data['daytype'] = $daytype;
                    $data['issat'] = 1;
                }
            }
        }
        // $this->coreFunctions->sbcupdate('otapplication', $datatype, ['line' => $head['clientid']]);

        switch ($companyid) {
            case 44:
                $computedot = $this->computeot_stonepro($config, $data, $empid);
                break;
            case 53:
                $computedot = $this->computeot_camera($config, $data, $empid);
                break;
            default:
                $computedot = $this->computeot($config, $data, $empid);
                break;
        }

        if (!empty($computedot['msg'])) {
            $msg = $computedot['msg'];
            goto ext;
        }

        $data['ottimein'] = $ottimein;
        $data['ottimeout'] = $ottimeout;
        // $data['ndiffhrs'] = $computedot['ndiffhrs'];
        $data['othrs'] = $computedot['othrs'];
        $data['ndiffothrs'] = $computedot['ndiffothrs'];
        $data['othrsextra'] = $computedot['othrsextra'];
        $loghrs = "OT HOURS: " . $data['othrs'] . " NDIFF OT HOURS " . $data['ndiffothrs'];
        $date = date("Y-m-d", strtotime($data['scheddate']));
        if ($isupdate) {
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['editby'] = $config['params']['user'];
            if (!empty($this->checking($config, $date))) {
                $msg = "Cannot update, application already exist.";
                $head['clientid'] = 0;
            } else {
                $this->coreFunctions->sbcupdate($this->head, $data, ['line' => $head['clientid']]);
                $this->logger->sbcmasterlog(
                    $head['trno'],
                    $config,
                    "UPDATE: $empname, DATE: " . $data['dateid'] . " " . $loghrs
                );
            }
        } else {
            $data['empid'] =  $empid;
            $data['otstatus'] =  1;
            $data['otstatus2'] =  1;

            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];

            if (!empty($this->checking($config, $date))) {
                $msg = "Cannot create, application already exist.";
                $head['clientid'] = 0;
            } else {
                $line = $this->coreFunctions->insertGetId($this->head, $data);
                // if ($companyid == 58) {
                //     $url = 'App\Http\Classes\modules\payroll\\' . 'otapplicationadv';
                //     $appstatus = $this->othersClass->insertPendingapp(0, $line, 'OT', $data, $url, $config, 0, true);
                //     if (!$appstatus['status']) {
                //         $this->coreFunctions->execqry("delete from otapplication where line=".$line, 'delete');
                //         $msg = $appstatus['msg'];
                //         $status = $appstatus['status'];
                //     } else {
                //         goto log;
                //     }
                // } else {
                //     log:
                // }
                $head['clientid']  = $line;
                $this->logger->sbcmasterlog(
                    $line,
                    $config,
                    "CREATE - NAME: $empname, DATE: " . $data['dateid'] . " " . $loghrs
                );
            }
        }
        ext:
        $status = true;
        if ($msg == '') {

            $msg = 'Successfully saved.';
        } else {
            $status = false;
        }
        return ['status' => $status, 'msg' => $msg, 'clientid' => $head['clientid']];
    }
    public function getlastclient()
    {
        $last_id = $this->coreFunctions->datareader("select line as value 
        from " . $this->head . " 
        order by line DESC LIMIT 1");

        return $last_id;
    }
    public function deletetrans($config)
    {
        $clientid = $config['params']['clientid'];
        $res = $this->approved_dis($config);
        if ($res['status']) {
            $msg = 'Cannot update; already approved.';
            if ($res['istatus'] == 3) {
                $msg = 'Cannot update; already disapproved.';
            }
            return ['status' => false, 'msg' => $msg, 'clientid' => $clientid];
        } else {
            $submitdate = $this->coreFunctions->datareader("select submitdate as value from $this->head where line=? and submitdate is not null", [$clientid]);
            if ($submitdate) {
                return ['status' => false, 'msg' => 'Cannot delete; already For approval.', 'clientid' => $clientid];
            }
            $this->coreFunctions->execqry('delete from otapplication where line=?', 'delete', [$clientid]);
            $this->coreFunctions->execqry("delete from pendingapp where line=? and doc='OT'", 'delete', [$clientid]);
            $this->logger->sbcdel_log($clientid, $config, $this->modulename);
            return ['clientid' => $clientid, 'status' => true, 'msg' => 'Successfully deleted.'];
        }
    }

    // -> print function
    public function reportsetup($config)
    {
        $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
        $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);

        $modulename = $this->modulename;
        $data = [];
        $style = 'width:500px;max-width:500px;';
        return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
    }

    public function reportdata($config)
    {
        $this->logger->sbcviewreportlog($config);
        $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }
    public function approvers($params)
    {
        $companyid = $params['companyid'];

        switch ($companyid) {
            case 44: // stonepro
            case 58: // cdohris
                $approvers = ['issupervisor', 'isapprover'];
                break;
            case 53: // camera
                $approvers = ['isotapprover', 'isapprover'];
                break;
            default:
                $approvers = ['isapprover'];
                break;
        }
        return $approvers;
    }
    public function computeot($config, $data = [], $empid)
    {

        $companyid = $config['params']['companyid'];
        $tenpm = "22:00"; // 10:00 PM
        $otndiff = new DateTime(date('Y-m-d', strtotime($data['scheddate'])) . $tenpm);

        $noon = "12:00"; // 12:00 PM
        $lunchtime = new DateTime(date('Y-m-d', strtotime($data['scheddate'])) . $noon);

        $scheddate = $data['scheddate'];
        $endndiff = new DateTime(date('Y-m-d', strtotime($scheddate)) . '06:00'); // 06:00 AM
        $endndiff = $endndiff->modify('+1 day');

        $schedout = "";
        $schedin = "";

        $schedule = $this->payrollcommon->checktimecard($empid, $data['scheddate']);
        $status = true;
        $msg = "";
        $ndiffhrs = 0;
        $ndiffothrs = 0;
        $othrsextra = 0;
        $othrs = 0;
        if (empty($schedule)) {
            $msg = 'No schedule set up on this date.';
            $status = false;
            goto ext;
        }

        $ottimein = new DateTime($data['dateid'] . ' ' . $data['ottimein']);
        $ottimeout = new DateTime($data['dateid2'] . ' ' . $data['ottimeout']);
        if ($companyid == 53) { // camera
            $result_validity = $this->validity($config, $ottimein, $ottimeout, $scheddate);
            if (!$result_validity['status']) {
                $msg = $result_validity['msg'];
                $status = false;
                goto ext;
            }
        }

        if ($data['daytype'] != 'WORKING') {
            $schedin =  $ottimein;
            $schedout =  $ottimein;
        } else {
            $schedin =  new DateTime($schedule[0]->schedin);
            $schedout =  new DateTime($schedule[0]->schedout);
        }

        $this->othersClass->logConsole($schedin->format('Y-m-d H:i:s') . ' = ' . $schedout->format('Y-m-d H:i:s'));

        $qry = "select isok as value from timecard where empid = ? and dateid = ? and '" . $otndiff->format('Y-m-d H:i:s') . "' between ? and ? ";
        $isok = $this->coreFunctions->datareader($qry, [$empid, $data['dateid'], $schedin->format('Y-m-d H:i:s'), $schedout->format('Y-m-d H:i:s')]);

        if ($status) {
            $this->othersClass->logConsole('status');



            $this->othersClass->logConsole('timeout: ' . $ottimeout->format('Y-m-d H:i:s') . ' = 10pm: ' . $otndiff->format('Y-m-d H:i:s'));


            //night diff ot
            if ($ottimeout > $otndiff) {
                $this->othersClass->logConsole('timeout: ' . $ottimeout->format('Y-m-d H:i:s') . ' end ndiff: ' . $endndiff->format('Y-m-d H:i:s'));
                if ($ottimeout > $endndiff) {
                    $tempndiff =  $ottimein->diff($endndiff);
                } else {
                    if ($ottimein > $otndiff) {
                        $tempndiff =  $ottimein->diff($ottimeout);
                    } else {
                        $tempndiff =  $otndiff->diff($ottimeout);
                    }
                }
                $ndiffothrs = $tempndiff->h;
                if ($ndiffothrs < 0) $ndiffothrs = 0;

                if ($tempndiff->i > 0) {
                    $compmins = $tempndiff->i / 60;
                    $ndiffothrs = $tempndiff->h + $compmins;
                }
                if ($ndiffothrs > 8) {
                    $ndiffothrs = 8;
                }
            }

            //do not remove
            // // schedule set night diff
            // if ($schedout >= $otndiff) {
            //     if ($ottimeout > $schedout) {
            //         $stinterval =  $schedout->diff($ottimeout);
            //         $ntempdiff = $otndiff->diff($schedout);
            //         $ndiffhrs = $ntempdiff->h;
            //         $this->othersClass->logConsole('ndiffot hours: ' . $ndiffhrs);
            //         if ($stinterval->h > 0) {
            //             $compminsndiffot = $stinterval->i / 60;
            //             $ndiffhrs = $ndiffhrs + $compminsndiffot;
            //         }
            //         $this->othersClass->logConsole('ndiffot hours/min: ' . $ndiffhrs);
            //     }
            // }

            $tempOT =  $ottimein->diff($ottimeout);
            $this->othersClass->logConsole('ndiff hours: ' . $ndiffhrs);

            $othrs = $tempOT->h;
            if ($tempOT->i > 0) {
                $compminsot = $tempOT->i / 60;
                $othrs = $tempOT->h + $compminsot;
            }

            $this->othersClass->logConsole('hours: ' . $othrs . ' minutes: ' . $tempOT->i);

            if ($companyid == 51) { //ulitc
                if ($data['daytype'] != 'WORKING') {
                    if ($othrs >= 9) {
                        $othrs = $othrs - 1;
                    }
                }
            }

            if ($othrs > 8) {
                $othrsextra = ($othrs - 8);
                $othrs  = 8;
            }
            // not use
            if ($ndiffhrs > 8) {
                $ndiffhrs = 8;
            }
        }
        ext:
        return ['msg' => $msg, 'othrs' => $othrs, 'ndiffhrs' => $ndiffhrs, 'ndiffothrs' => $ndiffothrs, 'othrsextra' => $othrsextra];
    }
    public function stockstatusposted($config)
    {
        $line = $config['params']['trno'];
        $companyid = $config['params']['companyid'];
        $msg = 'Success';
        switch ($companyid) {
            case 53: //camera
            case 51: //ulitc
                if ($companyid == 53) { //camera
                    $checkothrs = $this->coreFunctions->datareader("select othrs as value from otapplication where line=? ", [$line]);
                    if ($checkothrs < 1) {
                        return ['status' => false, 'msg' => 'OT Hours (Working) is ' . $checkothrs];
                    }
                }
                $submitdate = $this->coreFunctions->datareader("select submitdate as value from otapplication where line=? and submitdate is not null", [$line]);
                if (!empty($submitdate)) {
                    return ['row' => [], 'status' => false, 'msg' => 'Already Submitted.', 'backlisting' => false];
                }
                $update = $this->coreFunctions->sbcupdate($this->head, ['submitdate' => $this->othersClass->getCurrentTimeStamp()], ['line' => $line]);
                if ($update) {
                    $query = "select otadv.line as trno,otadv.othrs,otadv.empid,emp.otsupervisorid,
                    cl.clientname,otadv.rem,date_format(otadv.ottimein, '%Y-%m-%d %h:%i %p') as ottimein,
                    date_format(otadv.ottimeout, '%Y-%m-%d %h:%i %p') as ottimeout,
                    date(otadv.scheddate) as scheddate,dayname(otadv.scheddate) as dayname,otadv.ndiffhrs,otadv.othrsextra,
                    otadv.ndiffothrs,otadv.daytype,date(otadv.createdate) as createdate,otadv.remarks as remlast,otadv.disapproved_remarks2 as rem2
                    from otapplication as otadv 
                    left join client as cl on cl.clientid = otadv.empid
                    left join employee as emp on emp.empid = otadv.empid
                    where otadv.line = $line ";
                    $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
                    // testing
                    $data2['empid'] = $data[0]['empid'];
                    $url = 'App\Http\Classes\modules\payroll\\' . 'otapplicationadv';
                    // $result = $this->othersClass->insertPendingapp(0, $line, 'OT', $data2, $url, $config, 0, true);
                    $result = $this->othersClass->insertUpdatePendingapp(0, $line, 'OT', $data2, $url, $config, 0, true, true);
                    if (!$result['status']) {
                        $this->coreFunctions->sbcupdate($this->head, ['submitdate' => null], ['line' => $line]);
                        return ['row' => [], 'status' => $result['status'], 'msg' => $result['msg'], 'backlisting' => true];
                    }
                    $params = [];
                    if ($result['status']) {
                        // check approver kung ilan ang approver
                        $params['title'] = $this->modulename;
                        $params['clientname'] = $data[0]['clientname'];
                        $params['line'] = $data[0]['trno'];
                        $params['scheddate'] = $data[0]['scheddate'] . " (" . $data[0]['dayname'] . ")";
                        $params['ottimein'] = $data[0]['ottimein'];
                        $params['ottimeout'] = $data[0]['ottimeout'];
                        $params['rem'] = $data[0]['rem'];
                        $params['reason1'] = $data[0]['rem2'];
                        $params['remlast'] = $data[0]['remlast'];

                        $params['othrs'] = $data[0]['othrs'];
                        $params['othrsextra'] = $data[0]['othrsextra'];
                        // $params['ndiffhrs'] = $data[0]->ndiffhrs;
                        $params['ndiffothrs'] = $data[0]['ndiffothrs'];
                        $params['daytype'] = $data[0]['daytype'];
                        $params['createdate'] = $data[0]['createdate'];
                        $params['companyid'] = $companyid;
                        $params['muduletype'] = 'OT';

                        $query = "select app.line, app.doc,app.clientid,emp.email,cl.email as username,app.approver as isapp from pendingapp as app
                            left join employee as emp on emp.empid = app.clientid
                            left join client as cl on cl.clientid = emp.empid
                            where doc = 'OT' and app.line = $line ";
                        $approver_data = $this->coreFunctions->opentable($query);

                        if (empty($approver_data)) {
                            $this->coreFunctions->sbcupdate($this->head, ['submitdate' => null], ['line' => $line]);
                            return ['row' => [], 'status' => false, 'msg' => 'Please advice admin to set up approver.', 'backlisting' => true];
                        }
                        $status = true;
                        foreach ($approver_data as $key => $value) {
                            if (!empty($value->email)) {
                                $params['approver'] = $value->username;
                                $params['email'] = $value->email;
                                $params['isapp'] = $value->isapp;
                                // $res =  $this->linkemail->createOTEmail($params);
                                $res =  $this->linkemail->weblink($params, $config);
                                if (!$res['status']) {
                                    $msg = $res['msg'];
                                    $status = false;
                                }
                            }
                        }

                        if (!$status) {
                            $this->coreFunctions->execqry("delete from pendingapp where doc='OT' and line=" . $line, 'delete');
                            $this->coreFunctions->sbcupdate($this->head, ['submitdate' => null], ['line' => $line]);
                        }
                    }
                }
                break;
            default:
                $update = $this->coreFunctions->sbcupdate($this->head, ['submitdate' => $this->othersClass->getCurrentTimeStamp()], ['line' => $line]);
                if ($update) {
                    $url = 'App\Http\Classes\modules\payroll\\' . 'otapplicationadv';
                    $empid = $this->coreFunctions->getfieldvalue($this->head, "empid", "line=?", [$line]);
                    $data = ['empid' => $empid];
                    $appstatus = $this->othersClass->insertUpdatePendingapp(0, $line, 'OT', $data, $url, $config, 0, true, true);
                    if (!$appstatus['status']) {
                        $this->coreFunctions->sbcupdate($this->head, ['submitdate' => null], ['line' => $line]);
                        $status = $appstatus['status'];
                        $msg = $appstatus['msg'];
                        return ['status' => $status, 'msg' => $msg];
                    }
                } else {
                    return ['status' => false, 'msg' => 'Error updating record'];
                }
                break;
        }
        $submitdate = $this->othersClass->getCurrentTimeStamp();
        $this->logger->sbcmasterlog($line, $config, "SUBMIT DATE : " . $submitdate);
        return ['status' => true, 'msg' => $msg, 'backlisting' => true];
    }
    public function checking($config, $date)
    {
        $head = $config['params']['head'];
        $empid = $config['params']['adminid'];
        $line = $head['clientid'];

        $qry = "select otstatus,otstatus2,line from $this->head where empid = $empid and 
        date(dateid) = '" . $date . "' and (otstatus <> 3 and otstatus2 <> 3) order by line desc";
        $data =  $this->coreFunctions->opentable($qry);
        if (!empty($data)) {

            if ($data[0]->line == $line) {
                return [];
            }
            return $data;
        }
        return [];
    }
    public function computeot_stonepro($config, $data = [], $empid)
    {

        $companyid = $config['params']['companyid'];
        $tenpm = "22:00"; // 10:00 PM
        $otndiff = new DateTime($data['dateid'] . $tenpm);

        $noon = "12:00"; // 12:00 PM
        $lunchtime = new DateTime($data['dateid'] . $noon);

        $scheddate = $data['scheddate'];
        $endndiff = new DateTime(date('Y-m-d', strtotime($scheddate)) . '06:00'); // 06:00 AM
        $endndiff = $endndiff->modify('+1 day');

        $schedout = "";
        $schedin = "";

        $schedule = $this->payrollcommon->checktimecard($empid, $data['dateid']);
        $status = true;
        $msg = "";
        $ndiffhrs = 0;
        $ndiffothrs = 0;
        $othrsextra = 0;
        $othrs = 0;
        if (empty($schedule)) {
            $msg = 'No schedule set up on this date.';
            $status = false;
            goto ext;
        }

        $ottimein = new DateTime($data['dateid'] . ' ' . $data['ottimein']);
        $ottimeout = new DateTime($data['dateid2'] . ' ' . $data['ottimeout']);

        if ($data['daytype'] != 'WORKING') {
            $schedin =  $ottimein;
            $schedout =  $ottimein;
        } else {
            $schedin =  new DateTime($schedule[0]->schedin);
            $schedout =  new DateTime($schedule[0]->schedout);
        }

        if ($ottimeout < $ottimein) {

            $msg = "OT time out " . $ottimeout->format('Y-m-d h:i:s A') . " must not be less than OT time in  " . $ottimein->format('Y-m-d h:i:s A ') . ".";
            $status = false;
            goto ext;
        }

        $ndiffhrs = 0;

        if ($status) {
            $this->othersClass->logConsole('status');

            $blnLunch = false;
            if ($ottimein < $lunchtime && $ottimeout > $lunchtime) {
                $this->othersClass->logConsole('LUNCH');
                $blnLunch = true;
            }

            $this->othersClass->logConsole('timeout: ' . $ottimeout->format('Y-m-d H:i:s') . ' = 10pm: ' . $otndiff->format('Y-m-d H:i:s'));
            // ndiffothrs 
            if ($ottimeout > $otndiff) {
                if ($ottimeout > $endndiff) {
                    $tempndiff =  $ottimein->diff($endndiff);
                } else {
                    if ($ottimein > $otndiff) {
                        $tempndiff =  $ottimein->diff($ottimeout);
                    } else {
                        $tempndiff =  $otndiff->diff($ottimeout);
                    }
                }
                $ndiffothrs = $tempndiff->h;
                $this->othersClass->logConsole('ndiff hours: ' . $ndiffothrs);
                if ($ndiffothrs < 0) $ndiffothrs = 0;

                if ($tempndiff->i > 0) {
                    $compmins = $tempndiff->i / 60;
                    $ndiffothrs = $tempndiff->h + $compmins;
                }


                if ($ndiffothrs > 8) {
                    $ndiffothrs = 8;
                }
            }

            $this->othersClass->logConsole('timein: ' . $ottimein->format('Y-m-d H:i:s'));
            //ndiffhrs
            // if ($schedout >= $otndiff) {
            //     if ($ottimeout > $schedout) {
            //         $stinterval =  $schedout->diff($ottimeout);
            //         if ($stinterval->h > 0) {
            //             $compminsndiffot = $stinterval->i / 60;
            //             $ndiffhrs = $ndiffhrs + $compminsndiffot;
            //         }
            //     }
            //     if ($ndiffhrs > 8) {
            //         $ndiffhrs = 8;
            //     }
            // }

            $tempOT =  $ottimein->diff($ottimeout);
            $this->othersClass->logConsole('++++++++++');
            $this->othersClass->logConsole('ndiff hours: ' . $ndiffothrs);

            $othrs = $tempOT->h;
            if ($tempOT->i > 0) {
                $compminsot = $tempOT->i / 60;
                $othrs = $tempOT->h + $compminsot;
            }

            if ($blnLunch) $othrs = $othrs - 1;



            if ($ndiffothrs >= 1) {
                $othrs = $othrs - 1;
            }

            $this->othersClass->logConsole('hours: ' . $othrs);


            if ($othrs > 8) {
                $othrsextra = ($othrs - 8);
                $othrs  = 8;
            }


            if ($ndiffhrs > 8) {
                $ndiffhrs = 8;
            }

            $qry = "select gbrkin as value from tmshifts as tm left join timecard as tc on tc.shiftid = tm.line where empid = $empid and dateid = '" . $data['dateid'] . "'";
            $graceperiod = $this->coreFunctions->datareader($qry);

            if ($graceperiod != 0) {
                $graceperiod = $graceperiod / 60;
            }

            $sig = 0.25; //0.25 = 15mins
            if ($othrs >= $graceperiod) {
                //othrs
                $multi = (int)($othrs / $sig);
                $othrs =  $multi * $sig;
                $this->othersClass->logConsole('hours: ' . $othrs);
                if ($ndiffothrs >= $graceperiod) {
                    $ndiffmulti = (int)($ndiffothrs / $sig);
                    $ndiffothrs = $ndiffmulti * $sig;
                } else {
                    $ndiffothrs = 0;
                }
                if ($othrsextra >= $graceperiod) {
                    $othrsextramulti = (int)($othrsextra / $sig);
                    $othrsextra = $othrsextramulti * $sig;
                } else {
                    $othrsextra = 0;
                }
            } else {
                $othrs = 0;
            }



            $this->othersClass->logConsole('othrs: ' . $othrs . ' grace period: ' . $graceperiod);
        }
        ext:
        return ['msg' => $msg, 'othrs' => $othrs, 'ndiffhrs' => $ndiffhrs, 'ndiffothrs' => $ndiffothrs, 'othrsextra' => $othrsextra];
    }
    public function approved_dis($config)
    {
        $clientid = $config['params']['clientid'];
        $qry = "select otstatus as status, otstatus2 as status2 from otapplication where line = ?";
        $status = $this->coreFunctions->opentable($qry, [$clientid]);
        $array_stat = [2, 3];

        if (in_array($status[0]->status2, $array_stat)) {
            return ['status' => true, 'istatus' => $status[0]->status2];
        }

        if (in_array($status[0]->status, $array_stat)) {
            return ['status' => true, 'istatus' => $status[0]->status];
        }

        return ['status' => false];
    }



    public function validity($config, $otin, $otout, $sheddate)
    {
        $head = $config['params']['head'];
        $empid = $config['params']['adminid'];
        $status = true;
        $msg = "";
        $qry = "select date(actualin) as  actualn ,date(actualout) as actualot,actualin,actualout,schedout,
         date_format(actualin,'%Y-%m-%d %h:%i %p') as actualindis, date_format(actualout,'%Y-%m-%d %h:%i %p') as actualoutdis,
         date_format(schedout,'%Y-%m-%d %h:%i %p') as schedoutdis,date_format(schedin,'%Y-%m-%d %h:%i %p') as schedindis
         from timecard where empid = ? and dateid = date('" . $sheddate . "')";
        $data = $this->coreFunctions->opentable($qry, [$empid]);
        if (!empty($data)) {
            if ($data[0]->actualn != null) {
                if ($head['daytype'] != 'WORKING') { // restday
                    $actualin = new DateTime($data[0]->actualin);
                    if ($otin < $actualin) { // >=
                        $status = false;
                    }
                } else {
                    $schedout = new DateTime($data[0]->schedout);
                    if ($otin < $schedout) { // working // >=
                        $status = false;
                    }
                }
            }
            if ($data[0]->actualot != null) {
                $actualout = new DateTime($data[0]->actualout);
                if ($otout > $actualout) { // <=
                    $status = false;
                }
            }
        }
        if (!$status) {
            $msg = "Please check your request. Your recorded clock-in time <br> Schedule: " . $data[0]->schedindis . " - " . $data[0]->schedoutdis .
                "<br> Actual: " . $data[0]->actualindis . "- " . $data[0]->actualoutdis . ".";
        }

        return ['status' => $status, 'msg'  => $msg];
    }


    public function computeot_camera($config, $data = [], $empid)
    {

        $companyid = $config['params']['companyid'];
        $head = $config['params']['head'];
        $tenpm = "22:00"; // 10:00 PM
        $otndiff = new DateTime(date('Y-m-d', strtotime($data['scheddate'])) . $tenpm);

        $noon = "12:00"; // 12:00 PM
        $lunchtime = new DateTime(date('Y-m-d', strtotime($data['scheddate'])) . $noon);

        $scheddate = $data['scheddate'];
        $endndiff = new DateTime(date('Y-m-d', strtotime($scheddate)) . '06:00'); // 06:00 AM
        $endndiff = $endndiff->modify('+1 day');

        $am12 = new DateTime(date('Y-m-d', strtotime($scheddate)) . '00:00'); // 00:00 12AM
        $schedout = "";
        $schedin = "";

        $schedule = $this->payrollcommon->checktimecard($empid, $data['scheddate']);
        $status = true;
        $msg = "";
        $ndiffhrs = 0;
        $ndiffothrs = 0;
        $amdiffhrs = 0;
        $othrsextra = 0;
        $othrs = 0;
        $amcompmins = 0;
        $compmins = 0;
        $lessbreak = false;
        if (empty($schedule)) {
            $msg = 'No schedule set up on this date.';
            $status = false;
            goto ext;
        }

        $ottimein = new DateTime($data['dateid'] . ' ' . $data['ottimein']);
        $ottimeout = new DateTime($data['dateid2'] . ' ' . $data['ottimeout']);
        if ($companyid == 53) { // camera
            $result_validity = $this->validity($config, $ottimein, $ottimeout, $scheddate);
            if (!$result_validity['status']) {
                $msg = $result_validity['msg'];
                $status = false;
                goto ext;
            }
        }

        if ($data['daytype'] != 'WORKING') {
            $schedin =  $ottimein;
            $schedout =  $ottimein;
        } else {
            $schedin =  new DateTime($schedule[0]->schedin);
            $schedout =  new DateTime($schedule[0]->schedout);
        }

        $this->othersClass->logConsole($schedin->format('Y-m-d H:i:s') . ' = ' . $schedout->format('Y-m-d H:i:s'));

        $qry = "select isok as value from timecard where empid = ? and dateid = ? and '" . $otndiff->format('Y-m-d H:i:s') . "' between ? and ? ";
        $isok = $this->coreFunctions->datareader($qry, [$empid, $data['dateid'], $schedin->format('Y-m-d H:i:s'), $schedout->format('Y-m-d H:i:s')]);

        if ($status) {
            $this->othersClass->logConsole('timeout: ' . $ottimeout->format('Y-m-d H:i:s') . ' = 10pm: ' . $otndiff->format('Y-m-d H:i:s'));
            $tempOT =  $ottimein->diff($ottimeout);
            if ($tempOT->d > 0) {
                $tempOT->h = ($tempOT->d * 24) + $tempOT->h;
            }

            $this->othersClass->logConsole('24 hours: ' . $tempOT->h . ' min: ' . $tempOT->i);

            $othrs = $tempOT->h;
            if ($tempOT->i > 0) {
                $compminsot = 0;
                $schedot = $this->coreFunctions->datareader("select schedout as value from timecard where empid = $empid and dateid between '" . $data['dateid'] . "' and '" . $data['dateid2'] . "' ");
                if ($schedot != null) {
                    if ($ottimeout > $schedot) {
                        $h =  $ottimein->diff($ottimeout);
                        $this->othersClass->logConsole('computed hours: ' . $h->h);
                        if ($h->h >= 1) {
                            $tempOT->i = $this->payrollprocess->convertOT($tempOT->i, 30);
                            $compminsot = $tempOT->i / 60;
                        }
                    }
                }
                $this->othersClass->logConsole('compute minutes: ' . $compminsot);
                $othrs = $tempOT->h + $compminsot;
            }

            $this->othersClass->logConsole('hours: ' . $othrs . ' minutes: ' . $tempOT->i);


            if ($othrs > 8) {
                $othrs = $othrs - 1;
                $othrsextra = ($othrs - 8);
                $othrs  = 8;
                $lessbreak = true;
            }
            // not use
            if ($ndiffhrs > 8) {
                $ndiffhrs = 8;
            }

            if ($ottimeout > $otndiff) {
                if ($ottimeout > $endndiff) { // next day
                    $tempndiff =  $ottimein->diff($endndiff);
                } else {
                    $endndiff = $endndiff->modify('-1 day'); // same day
                    amndiff:
                    if ($ottimeout > $ottimein) {
                        if ($ottimein < $endndiff) { // amndiff
                            $amndiff =  $ottimein->diff($endndiff);
                            $amdiffhrs = $amndiff->h;
                            if ($amndiff->h > 0) {
                                $amndiff->i = $this->payrollprocess->convertOT($amndiff->i, 30);
                                $amcompmins = $amndiff->i / 60;
                                $amdiffhrs = $amndiff->h + $amcompmins;
                            }
                            $this->othersClass->logConsole('amndif computed hours: ' . $amndiff->h . ' amndif compute minute: ' . $amcompmins);
                        }

                        if ($ottimein > $otndiff) {
                            $tempndiff =  $ottimein->diff($ottimeout);
                        } else {
                            if ($ottimeout > $otndiff) {
                                $tempndiff =  $otndiff->diff($ottimeout);
                            } else {
                                goto comndiff;
                            }
                        }
                    } else {
                        goto comndiff;
                    }
                }
                $ndiffothrs = $tempndiff->h;
                if ($ndiffothrs < 0) $ndiffothrs = 0;

                if ($ndiffothrs > 0) {
                    if ($tempndiff->i > 0) {
                        $tempndiff->i = $this->payrollprocess->convertOT($tempndiff->i, 30);
                        $compmins = $tempndiff->i / 60;
                        $this->othersClass->logConsole('ndif computed hours: ' . $tempndiff->h . ' ndif compute minute: ' . $compmins . 'ndif-min:' . $tempndiff->i);
                        $ndiffothrs = $tempndiff->h + $compmins;
                    }
                }
            } else {
                if (date('Y-m-d', strtotime($data['scheddate'])) == $data['dateid']) {
                    $endndiff = $endndiff->modify('-1 day');
                    if ($ottimein < $endndiff) {
                        goto amndiff;
                    }
                }
            }
            comndiff:
            if ($othrs >= 1) {
                $ndiffothrs += $amdiffhrs;
                if ($ndiffothrs > 8) {
                    $ndiffothrs = 8;
                }
            } else {
                $ndiffothrs = 0;
            }

            if ($data['daytype'] == 'WORKING') {
                if ($lessbreak) {
                    $othrs += 1;
                }
                $othrs += $othrsextra;
                $othrsextra = 0;
            }
        }
        ext:
        return ['msg' => $msg, 'othrs' => $othrs, 'ndiffhrs' => $ndiffhrs, 'ndiffothrs' => $ndiffothrs, 'othrsextra' => $othrsextra];
    }
} //end class
