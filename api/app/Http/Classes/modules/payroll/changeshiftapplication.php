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
use DateTime;
use Carbon\Carbon;

class changeshiftapplication
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'CHANGE SHIFT APPLICATION';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $linkemail;
    private $othersClass;
    private $logger;
    private $payrollcommon;
    private $sqlquery;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
    public $head = 'changeshiftapp';
    public $prefix = '';
    public $tablelogs = 'payroll_log';
    public $tablelogs_del = '';
    private $stockselect;

    private $fields = [
        'line',
        'empid',
        'dateid',
        'rem',
        'status',
        'status2',
        'daytype',
        'orgdaytype',
        'reghrs'
    ];
    // 'remarks','acno','days','bal',
    private $except = ['clientid', 'client'];
    private $blnfields = [];
    public $showfilteroption = true;
    public $showfilter = true;
    public $showcreatebtn = true;
    private $reporter;
    private $isexist = 0;

    public $showfilterlabel = [
        ['val' => 'draft', 'label' => 'Entry', 'color' => 'primary'],
        ['val' => 'approved', 'label' => 'Approved', 'color' => 'primary'],
        ['val' => 'disapproved', 'label' => 'Disapproved', 'color' => 'primary'],
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
        $this->payrollcommon = new payrollcommon;
        $this->linkemail = new linkemail;
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 4596,
            'new' => 4598,
            'edit' => 4597,
            'save' => 4599,
            'delete' => 4600,

        );
        return $attrib;
    }

    public function createdoclisting($config)
    {
        $companyid = $config['params']['companyid'];
        // $getcols = ['action', 'dateid', 'listappstatus', 'clientname', 'rem'];

        $this->showfilterlabel = [
            ['val' => 'draft', 'label' => 'Entry', 'color' => 'primary'],
            ['val' => 'forapproval', 'label' => 'For Approval', 'color' => 'primary'],
            ['val' => 'approved', 'label' => 'Approved', 'color' => 'primary'],
            ['val' => 'disapproved', 'label' => 'Disapproved', 'color' => 'primary']
        ];
        $url = 'App\Http\Classes\modules\payroll\\' . 'changeshiftapplication';
        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='CHANGESHIFT'");
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

        $getcols = ['action', 'createdate', 'clientname', 'scheddate', 'daytype', 'schedtime', 'rem', 'listappstatus2', 'approvedby_disapprovedbysup', 'date_approved_disapprovedsup', 'rem2', 'listappstatus', 'date_approved_disapproved', 'approvedby_disapprovedby', 'remarks'];
        if ($companyid == 53) { //camera
            array_push($getcols, 'void_date', 'void_approver', 'void_remarks');
        }

        foreach ($getcols as $key => $value) {
            $$value = $key;
        }

        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';

        // $cols[$type]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$listappstatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;text-align:left;';
        $cols[$rem]['style'] = 'width:400px;whiteSpace: normal;min-width:400px;';
        $cols[$scheddate]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;text-align:center;';
        $cols[$schedtime]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;text-align:center;';
        $cols[$listappstatus2]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;text-align:left;';

        $cols[$listappstatus]['type'] = 'label';

        $cols[$clientname]['label'] = 'Name';
        $cols[$clientname]['style'] = 'width:200px;whiteSpace:normal;min-width:200px;';

        if ($companyid == 53) { // camera
            $cols[$createdate]['label'] = 'Date Applied';
            $cols[$rem]['label'] = 'Reason';
            $cols[$date_approved_disapproved]['label'] = 'Date Approved/Disapproved (Hr/Payroll Approver)';
            $cols[$approvedby_disapprovedby]['label'] = 'Approved/Disapproved By (Hr/Payroll Approver)';
            $cols[$remarks]['label'] = 'Hr/Payroll Approver Reason';
            $cols[$scheddate]['style'] = 'width:120px;whiteSpace: normal;min-width:130px;text-align:center;';
            $cols[$schedtime]['style'] = 'width:120px;whiteSpace: normal;min-width:130px;text-align:center;';
            $cols[$schedtime]['type'] = 'label';
            $cols[$schedtime]['label'] = 'Schedule';
            $cols[$listappstatus]['label'] = 'Hr/Payroll Approver Status';
            $cols[$listappstatus2]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;text-align:left;';

            $cols[$date_approved_disapprovedsup]['label'] = 'Date Approved/Disapproved (Head Dept. Approver)';
            $cols[$listappstatus2]['label'] = 'Head Dept. Approver Status';
            $cols[$approvedby_disapprovedbysup]['label'] = 'Approved/Disapproved by (Head Dept. Approver)';


            $cols[$rem2]['label'] = 'Head Dept. Approver Reason';
            $cols[$void_remarks]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
            $cols[$void_remarks]['label'] = 'Reason';
            $cols[$void_date]['type'] = 'label';
            $cols[$void_approver]['type'] = 'label';
        }
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
        $companyid = $config['params']['companyid'];

        $date1 = date('Y-m-d', strtotime($config['params']['date1']));
        $date2 = date('Y-m-d', strtotime($config['params']['date2']));
        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='CHANGESHIFT'");
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

        $filteroption = '';
        $option = $config['params']['itemfilter'];
        $draft = " and submitdate is null and csapp.status= 0 ";
        $forapproval = " and submitdate is not null and (csapp.status = 0 and csapp.status2 <> 2)";
        $approved = " and submitdate is not null and csapp.status= 1 ";
        $disapproved = " and submitdate is not null and (csapp.status= 2 or csapp.status2= 2) ";

        switch ($companyid) {
            case '44':
                //stonepro
                $draft = " and csapp.status = 0 and csapp.submitdate is null";
                $forapproval = " and csapp.status = 0 and csapp.status2 = 0 and submitdate is not null ";
                $approved = " and (csapp.status= 1 and csapp.status2 = 1) ";
                $disapproved = " and csapp.status= 2 or csapp.status2 = 2 ";

                break;
            case '53':
                //camera ((ot.otstatus = 1 and ot.otstatus2 = 2) or ot.otstatus2 = 1)
                $draft = " and submitdate is null and csapp.status2 = 0 ";
                $forapproval = " and ((csapp.status = 0 and csapp.status2 = 0 ) or (csapp.status = 0 and csapp.status2 = 1)) and submitdate is not null ";
                $approved = " and (csapp.status= 1 and csapp.status2 = 1) ";
                $disapproved = " and (csapp.status= 2 or csapp.status2 = 2) ";

                break;
        }


        switch ($option) {
            case 'draft':
                $filteroption = " and csapp.empid=" . $id . " $draft ";
                break;
            case 'forapproval':
                $filteroption = " and csapp.empid=" . $id . " $forapproval ";
                break;
            case 'approved':
                $filteroption = " and csapp.empid=" . $id . " $approved ";
                break;
            case 'disapproved':
                $filteroption = " and csapp.empid=" . $id . " $disapproved ";
                break;
            default:
                $isapprover = $this->coreFunctions->getfieldvalue("employee", "isapprover", "empid=?", [$id]);
                if ($isapprover == 1) {
                    $filteroption = " and csapp.status = 2 and approvedby='" . $user . "' ";
                } else {
                    return ['data' => [], 'status' => false, 'msg' => 'This feature is for approvers only.'];
                }
                break;
        }
        $filtersearch = "";
        if (isset($config['params']['search'])) {
            $searchfield = ['cl.clientid', 'cl.client', 'cl.clientname', 'csapp.type', 'csapp.rem'];

            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
            $limit = "";
        }
        $leftjoin = "";
        $addfields = "";

        $casestat2 = "";
        switch ($companyid) {
            case 44: //stonepro
                $casestat = ", case
        when csapp.status = 0 and csapp.status2=0 then 'ENTRY'
        when csapp.status = 1 and csapp.status2 = 1 then 'APPROVED'
        when csapp.status = 2 or csapp.status2 = 2 then 'DISAPPROVED'
        END as jstatus";

                $casestat2 = ", case
        when csapp.status2 = 0 and csapp.submitdate is null then 'ENTRY'
        when csapp.status2 = 0 and csapp.submitdate is not null then 'FOR APPROVAL'
        when csapp.status2 = 1 then 'APPROVED (Supervisor)'
        when csapp.status2 = 2 then 'DISAPPROVED'
        END as status2";
                break;
            case 53: //camera
                $addfields = ",void.clientname as void_approver,csapp.void_date,csapp.void_remarks";
                $casestat = ", case
        when csapp.status = 0 and csapp.status2 = 0 then 'ENTRY'
        when csapp.status = 0 and csapp.status2 = 1 then 'FOR APPROVAL'
        when csapp.status = 1 and csapp.status2 = 1 then 'APPROVED'
        when csapp.status = 2 then 'DISAPPROVED'
        END as jstatus";

                $casestat2 = ", case
        when csapp.status2 = 0 and submitdate is null then 'ENTRY'
        when csapp.status2 = 0 and submitdate is not null then 'FOR APPROVAL'
        when csapp.status2 = 1 and csapp.status = 1 then 'APPROVED'
        when csapp.status2 = 2 then 'DISAPPROVED'
        END as status2";
                $leftjoin = " left join client as void on void.email = csapp.void_by and void.email <> ''";
                break;
            default:
                if (count($approversetup) > 1 || $both) {
                    $casestat = ", case
        when csapp.status = 0 then 'ENTRY'
        when csapp.status = 1 then 'APPROVED'
        when csapp.status2 = 1 then 'APPROVED (Supervisor)'
        when csapp.status = 2 then 'DISAPPROVED'
        END as jstatus ";
                } else {
                    $casestat = ", case
        when csapp.status = 0 then 'ENTRY'
        when csapp.status = 1 then 'APPROVED'
        when csapp.status2 = 1 then 'APPROVED (Supervisor)'
        when csapp.status = 2 then 'DISAPPROVED'
        END as jstatus,
        case
        when csapp.status2 = 0 then 'ENTRY'
        when csapp.status2 = 1 then 'APPROVED'
        when csapp.status2 = 2 then 'DISAPPROVED'
        END as status2 ";
                }
                break;
        }
        $qry = "
        select csapp.line as trno,
        cl.client, cl.clientname, cl.clientid as empid, csapp.line as clientid,
        date(csapp.dateid) as dateid, csapp.rem,csapp.originalin,csapp.originalout,date_format(csapp.dateid, '%m-%d-%y') as scheddate,date_format(csapp.createdate, '%m-%d-%y') as createdate,
        concat(date_format(csapp.schedin, '%H:%i'),'-',date_format(csapp.schedout, '%H:%i')) as schedtime,csapp.disapproved_remarks as remarks,csapp.daytype,ifnull(disapp.clientname,app.clientname) as approvedby_disapprovedby,
        date_format(ifnull(csapp.disapproveddate,csapp.approveddate), '%m-%d-%y') as date_approved_disapproved,ifnull(disapp2.clientname,app2.clientname) as approvedby_disapprovedbysup, 
        date_format(ifnull(csapp.disapproveddate2,csapp.approveddate2), '%m-%d-%y') as date_approved_disapprovedsup,csapp.disapproved_remarks2 as rem2
        
        $casestat $addfields $casestat2
        from changeshiftapp as csapp
        left join employee as emp on emp.empid = csapp.empid
        left join client as cl on cl.clientid = emp.empid
        left join client as app on app.email = csapp.approvedby and app.email <> ''
        left join client as disapp on disapp.email = csapp.disapprovedby and disapp.email <> ''

        left join client as app2 on app2.email = csapp.approvedby2 and app2.email <> ''
        left join client as disapp2 on disapp2.email = csapp.disapprovedby2 and disapp2.email <> ''
        $leftjoin
        where date(csapp.dateid) between '$date1' and '$date2'
        " . $filteroption . " $filtersearch 
        order by csapp.approveddate";
        $data = $this->coreFunctions->opentable($qry);

        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
    }

    public function createHeadbutton($config)
    {
        $btns = array(
            'load',
            'new',
            'save',
            'delete',
            'cancel',
            'print',
            'logs',
            'edit',
            'backlisting',
            'toggleup',
            'toggledown'
        );
        $buttons = $this->btnClass->create($btns);
        return $buttons;
    } // createHeadbutton

    public function createTab($access, $config)
    {
        return [];
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
        $fields = ['client', 'dateid', 'shiftcode', ['schedin', 'schedout']];
        if ($companyid == 53) { //camera
            $fields = ['client', 'dateid', 'shiftcode', 'changetime', ['schedin', 'schedout']];
        }
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'client.label', 'Code');
        data_set($col1, 'client.action', 'lookupledger');
        data_set($col1, 'client.type', 'hidden');
        data_set($col1, 'client.required', false);
        data_set($col1, 'schedin.type', 'time');
        data_set($col1, 'schedin.class', 'csschedin sbccsreadonly');
        data_set($col1, 'schedout.type', 'time');
        data_set($col1, 'schedout.class', 'csschedout sbccsreadonly');
        if ($companyid == 53) {
            data_set($col1, 'shiftcode.type', 'input');
            data_set($col1, 'shiftcode.class', 'csshiftcode sbccsreadonly');
        } else {
            data_set($col1, 'shiftcode.class', 'csshiftcode sbccsreadonly');
            data_set($col1, 'shiftcode.action', 'lookuptimeshift');
            data_set($col1, 'shiftcode.addedparams', ['dateid']);
            data_set($col1, 'shiftcode.required', true);
        }


        $fields = ['createdate', 'daytype', 'rem'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'daytype.type', 'lookup');
        data_set($col2, 'daytype.lookupclass', 'lookupdaytype');
        data_set($col2, 'daytype.action', 'lookupdaytype');

        $fields = ['lblsubmit', 'submit'];
        $label = 'Remarks';
        if ($companyid == 53) { // camera
            data_set($col2, 'rem.label', $label);
        }
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'lblsubmit.type', 'label');
        data_set($col3, 'lblsubmit.label', 'SUBMITTED');
        data_set($col3, 'lblsubmit.style', 'font-weight:bold;font-size:15px;font-family:Century Gothic;color: green;');
        $fields = [];
        $col4 = $this->fieldClass->create($fields);

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
        $data[0]['schedin'] = '00:00';
        $data[0]['schedout'] = '00:00';
        $data[0]['rem'] = '';
        $data[0]['status'] = 0;
        $data[0]['status2'] = 0;
        $data[0]['createdate'] = $this->othersClass->getCurrentDate();
        $data[0]['lblsubmit'] = '';
        $data[0]['submitdate'] = null;
        $data[0]['daytype'] = '';
        $data[0]['orgdaytype'] = '';
        $data[0]['shiftcode'] = '';
        $data[0]['shiftid'] = 0;
        $data[0]['dayn'] = 0;
        $data[0]['divid'] = 0;
        $data[0]['reghrs'] = 0.0;
        if ($config['companyid'] == 53) { //camera
            $data[0]['divid'] = $this->coreFunctions->datareader("select divid as value from employee where empid=?", [$config['adminid']]);
            $data[0]['shiftcode'] = $this->coreFunctions->datareader("select shftcode as value from employee as emp 
            left join tmshifts as tm on tm.line = emp.shiftid where emp.empid=?", [$config['adminid']]);
        }
        return $data;
    }

    function getheadqry($config, $trno)
    {
        $adminid = $config['params']['adminid'];
        return "
        select csapp.line as trno, cl.client,
        cl.clientname, cl.clientid as empid,csapp.line as clientid,
        date(csapp.dateid) as dateid,dayname(csapp.dateid) as dayname,
        time(csapp.schedin) as schedin,csapp.schedin as schediin,
        time(csapp.schedout) as schedout,csapp.schedout as schedoutt,
        csapp.rem,csapp.createdate,csapp.submitdate,emp.divid,csapp.daytype,csapp.orgdaytype,csapp.shftcode as shiftcode,'Change Time' as changetime,
        case
        when csapp.status = 0 and csapp.status2=0 and submitdate is null then 'ENTRY'
        when csapp.status = 0 and (csapp.status2 = 0 or csapp.status2=1) and submitdate is not null then 'FOR APPROVAL'
        when csapp.status = 1 and csapp.status2 = 1 then 'APPROVED'
        when csapp.status = 2 or csapp.status2 = 2 then 'DISAPPROVED'
        end as status,

        csapp.reghrs,0 as dayn, 0 as shiftid,csapp.status2
        from changeshiftapp as csapp
        left join employee as emp on emp.empid = csapp.empid
        left join client as cl on cl.clientid = emp.empid
        where csapp.line = '$trno' and csapp.empid = '$adminid'";
    }


    public function loadheaddata($config)
    {

        $trno = $config['params']['clientid'];
        $companyid = $config['params']['companyid'];


        $head = $this->coreFunctions->opentable($this->getheadqry($config, $trno));

        if (!empty($head)) {
            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }
            $hideobj = [];

            $submit = $head[0]->submitdate != null ? true : false;
            if ($submit) {
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

            return  ['head' => $head, 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => false, 'qq' => $config['params']['clientid'], 'hideobj' => $hideobj, 'action' => 'backlisting'];
        } else {
            $msg = 'Data Fetched Failed, either somebody already deleted the transaction or modified...';

            if ($this->isexist == 1) {
                $msg = "Already Exist";
            }

            $head = $this->resetdata('', $config['params']);
            return ['status' => false, 'isnew' => true, 'head' => $head, 'msg' => $msg, 'action' => 'backlisting'];
        }
    }

    public function updatehead($config, $isupdate)
    {
        $head = $config['params']['head'];


        $center = $config['params']['center'];
        $companyid = $config['params']['companyid'];
        $empid = $config['params']['adminid'];
        $data = [];
        $clientid = 0;
        $msg = '';
        if (isset($head['trno'])) {
            $submitdate = $this->coreFunctions->datareader("select submitdate as value from changeshiftapp where line=? ", [$head['trno']], '', true);
            if ($companyid == 53) { //camera
                if ($submitdate) {
                    $url = 'App\Http\Classes\modules\payroll\\' . 'changeshiftapplication';
                    $result = $this->payrollcommon->checkapplicationstatus($config, $head['trno'], $url, $submitdate);
                    if (!$result['status']) {
                        return ['status' => false, 'msg' => $result['msg'], 'clientid' => $head['trno']];
                    }
                }
            } else {
                $approved = $this->coreFunctions->datareader("select approveddate as value from " . $this->head . " where line=? and approveddate is not null", [$head['trno']]);
                $approved2 = $this->coreFunctions->datareader("select approveddate2 as value from " . $this->head . " where line=? and approveddate2 is not null", [$head['trno']]);
                if ($approved || $approved2) {
                    return ['status' => false, 'msg' => 'Cannot update; already Approved.', 'clientid' => $config['params']['adminid']];
                }
                $disapproved = $this->coreFunctions->datareader("select disapproveddate as value from " . $this->head . " where line=? and disapproveddate is not null", [$head['trno']]);
                $disapproved2 = $this->coreFunctions->datareader("select disapproveddate2 as value from " . $this->head . " where line=? and disapproveddate2 is not null", [$head['trno']]);
                if ($disapproved || $disapproved2) {
                    return ['status' => false, 'msg' => 'Cannot update; already Disapproved.', 'clientid' => $config['params']['adminid']];
                }
            }
        }


        foreach ($this->fields as $key) {
            if (array_key_exists($key, $head)) {
                $data[$key] = $head[$key];
                if (!in_array($key, $this->except)) {
                    $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
                } //end if 
            }
        }
        if ($companyid != 53) { //not camera 
            if ($head['submitdate'] != null) { //foremail
                return ['status' => false, 'msg' => 'Cannot update already submitted.'];
            }
        }
        if ($companyid == 53) { // camera
            $checkcutoffdate = $this->payrollcommon->checkbatchsched($data['dateid'], $head['divid']);
            if (!empty($checkcutoffdate['msg'])) {
                $msg = $checkcutoffdate['msg'];
                return ['status' => false, 'msg' => $msg];
            }
        }

        $schedule = $this->payrollcommon->checktimecard($empid, $data['dateid']);
        if (empty($schedule[0]->dateid)) {
            $msg = 'No schedule on this date.';
            return ['status' => false, 'msg' => $msg];
        }

        $data['schedin'] = $this->othersClass->sanitizekeyfield('schedin', $head['dateid'] . " " . $head['schedin']);
        $data['schedout'] = $this->othersClass->sanitizekeyfield('schedout', $head['dateid'] . " " . $head['schedout']);


        $daytype = $this->coreFunctions->datareader("select daytype as value from timecard where empid=? and date_format(dateid, '%Y-%m-%d') = ?", [$empid, date('Y-m-d', strtotime($data['dateid']))]);
        if ($data['daytype'] == "") {
            $data['daytype'] = $daytype;
        }

        $shifthrs = $this->getday($config, $head['dayn'], $head['shiftid'], $data, $data['daytype']);
        if (!empty($shifthrs)) {
            $data['reghrs'] = $shifthrs[0]->tothrs;
        } else {
            $data['reghrs'] = 0;
        }

        // $this->getnextday($config, $head['dateid'], $head['schedin'], $head['schedout']);
        $data['shftcode'] =  $this->othersClass->sanitizekeyfield('shiftcode', $head['shiftcode']);
        $empname = $this->coreFunctions->datareader("select cl.clientname as value 
        from employee as e
        left join client as cl on cl.clientid = e.empid
        where e.empid = ?", [$config['params']['adminid']]);

        if ($isupdate) {
            $date = date('Y-m-d', strtotime($data['dateid']));
            if (!empty($this->checking($config, $date))) {
                $msg = "Cannot update, application already exist.";
                $clientid = 0;
            } else {

                $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
                $data['editby'] = $config['params']['user'];
                $this->coreFunctions->sbcupdate($this->head, $data, ['line' => $head['clientid']]);
                $clientid = $head['clientid'];

                $this->logger->sbcmasterlog(
                    $clientid,
                    $config,
                    "UPDATE - NAME: $empname, DAYTYPE: " . $data['daytype'] . ",SHIFT " . $head['shiftcode'] . " ,REMARKS: " . $data['rem'] . ""
                );
            }
        } else {
            $data['orgdaytype'] = $daytype;
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];
            $data['empid'] =  $config['params']['adminid'];
            $date = date('Y-m-d', strtotime($data['dateid']));
            if (!empty($this->checking($config, $date))) {
                $msg = "Cannot create, application already exist.";
                $clientid = 0;
            } else {

                $clientid = $this->coreFunctions->insertGetId($this->head, $data);
                $this->logger->sbcmasterlog(
                    $clientid,
                    $config,
                    "CREATE - NAME: $empname, DAYTYPE: " . $data['daytype'] . ",SHIFT " . $head['shiftcode'] . " ,REMARKS: " . $data['rem'] . ""
                );
            }
        }
        ext:
        $status = true;
        if ($msg == '') {
            $msg = 'Successfully saved';
        } else {
            $status = false;
        }
        return ['status' => $status, 'msg' => $msg, 'clientid' => $clientid];
    } // end function

    public function checking($config, $date)
    {
        $head = $config['params']['head'];
        $empid = $config['params']['adminid'];
        $line = $head['clientid'];

        $qry = "select status,status2,line from " . $this->head . ' where empid = "' . $empid . '" and date(dateid) = "' . $date . '" and status <> 2 and status2 <> 2';
        $data =  $this->coreFunctions->opentable($qry);
        if (!empty($data)) {
            if ($line == $data[0]->line) {
                return [];
            }
            return  $data;
        }
        return [];
    }

    public function getlastclient()
    {
        $last_id = $this->coreFunctions->datareader("select line as value 
        from " . $this->head . " 
        order by line DESC LIMIT 1");

        return $last_id;
    }

    public function openstock($trno, $config)
    {
        $qry = "";
        return $this->coreFunctions->opentable($qry);
    }

    public function deletetrans($config)
    {

        $clientid = $config['params']['clientid'];
        $res = $this->approved_dis($config);
        if ($res['status']) {
            $msg = 'Cannot update; already approved.';
            if ($res['istatus'] == 2) {
                $msg = 'Cannot update; already disapproved.';
            }
            return ['status' => false, 'msg' => $msg, 'clientid' => $clientid];
        }

        $qry = "select line as value from changeshiftapp where line = '$clientid' and status <> 0 ";
        $count = $this->coreFunctions->datareader($qry);

        if ($count != "") {
            return ['clientid' => '0', 'status' => false, 'msg' => "Transaction cannot be deleted."];
        }

        $this->coreFunctions->execqry('delete from changeshiftapp where line=?', 'delete', [$clientid]);
        return ['clientid' => $clientid, 'status' => true, 'msg' => 'Successfully deleted.', 'action' => 'backlisting'];
    } //end function


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
            default:
                $approvers = ['isapprover'];
                break;
        }
        return $approvers;
    }
    public function stockstatusposted($config)
    {
        $trno = $config['params']['trno'];
        $empid = $config['params']['adminid'];
        $companyid = $config['params']['companyid'];
        $submitdate = $this->coreFunctions->datareader("select submitdate as value from changeshiftapp where line=? and submitdate is not null", [$trno]);
        if (!empty($submitdate)) {
            return ['row' => [], 'status' => false, 'msg' => 'Already Submitted', 'backlisting' => false];
        }
        $update = $this->coreFunctions->sbcupdate($this->head, ['submitdate' => $this->othersClass->getCurrentTimeStamp()], ['line' => $trno]);

        if ($update) {

            $query = "
            select csapp.line as trno,
            cl.clientname,csapp.empid,
            date(csapp.dateid) as dateid,
            date_format(csapp.schedin, '%Y-%m-%d %h:%i %p') as schedin,
            date_format(csapp.schedout, '%Y-%m-%d %h:%i %p') as schedout,
            date_format(tm.schedin, '%Y-%m-%d %h:%i %p') as orgschedin,
            date_format(tm.schedout, '%Y-%m-%d %h:%i %p') as orgschedout,
            csapp.rem,csapp.daytype,csapp.orgdaytype,csapp.rem as remarks,
            csapp.disapproved_remarks2 as rem2,csapp.disapproved_remarks as rem1
            from changeshiftapp as csapp
            left join timecard as tm on tm.empid = csapp.empid and tm.dateid = date(csapp.dateid)
            left join employee as emp on emp.empid = csapp.empid
            left join client as cl on cl.clientid = emp.empid
            where csapp.line = " . $trno;

            $shiftdata = json_decode(json_encode($this->coreFunctions->opentable($query)), true);

            $data2 = [];

            if (!empty($shiftdata)) {
                $row = $shiftdata[0];
                foreach ($this->fields as $key) {
                    if (array_key_exists($key, $row)) {
                        $data2[$key] = $row[$key];
                        if (!in_array($key, $this->except)) {
                            $data2[$key] = $this->othersClass->sanitizekeyfield($key, $data2[$key]);
                        } //end if 
                    }
                }
            }
            switch ($companyid) {
                case 53: //camera
                case 51: //ulitc
                    $stats = true;
                    break;
                default:
                    $stats = false;
                    break;
            }
            $data2['empid'] = $shiftdata[0]['empid'];
            $url = 'App\Http\Classes\modules\payroll\\' . 'changeshiftapplication';
            $result = $this->othersClass->insertUpdatePendingapp(0, $trno, 'CHANGESHIFT', $data2, $url, $config, 0, $stats, true);
            // sending email
            if ($companyid == 53 || $companyid == 51) { //camera|ulitc
                $params = [];
                $params['title'] = $this->modulename;
                $params['clientname'] = $shiftdata[0]['clientname'];
                $params['line'] = $shiftdata[0]['trno'];
                $params['dateid'] = $shiftdata[0]['dateid'];
                $params['schedin'] = $shiftdata[0]['schedin'];
                $params['schedout'] = $shiftdata[0]['schedout'];
                $params['orgschedin'] = $shiftdata[0]['orgschedin'];
                $params['orgschedout'] = $shiftdata[0]['orgschedout'];
                $params['daytype'] = $shiftdata[0]['daytype'];
                $params['remarks'] = $shiftdata[0]['rem'];
                $params['remlast'] = $shiftdata[0]['rem1'];
                $params['reason1'] = $shiftdata[0]['rem2'];
                $params['companyid'] = $companyid;
                $params['orgdaytype'] = $shiftdata[0]['orgdaytype'];
                $params['muduletype'] = 'SCHED';

                $query = "
                    select app.line, app.doc,app.clientid,emp.email,cl.email as username,app.approver as isapp from pendingapp as app
                    left join employee as emp on emp.empid = app.clientid
                    left join client as cl on cl.clientid = emp.empid
                    where doc = 'CHANGESHIFT' and app.line = $trno ";

                $data = $this->coreFunctions->opentable($query);
                $status = true;
                if (empty($data)) {
                    $this->coreFunctions->sbcupdate($this->head, ['submitdate' => null], ['line' => $trno]);
                    return ['row' => [], 'status' => false, 'msg' => 'Please advice admin to set up approver. ' . $result['msg'], 'backlisting' => true];
                }
                foreach ($data as $key => $value) {
                    if (!empty($data[$key]->email)) {
                        $params['approver'] = $data[$key]->username;
                        $params['isapp'] = $value->isapp;
                        $params['email'] = $data[$key]->email;
                        // $res = $this->linkemail->createChangeSchedEmail($params);
                        $res = $this->linkemail->weblink($params, $config);
                        if (!$res['status']) {
                            $status = false;
                            $msg = $res['msg'];
                        }
                    }
                }
                if (!$status) {
                    $this->coreFunctions->execqry("delete from pendingapp where doc='CHANGESHIFT' and line=" . $trno, 'delete');
                    $this->coreFunctions->sbcupdate($this->head, ['submitdate' => null], ['line' => $trno]);
                    return ['row' => [], 'status' => false, 'msg' => $msg, 'backlisting' => true];
                }
                // $query = "
                // select emp.approver1,emp.approver2 from employee as emp
                // left join client as cl on cl.clientid = emp.empid
                // where emp.empid = " . $empid . " ";
                // $emp_app = $this->coreFunctions->opentable($query);

                // $qry = "select emp.email,cl.email as username from employee as emp
                // left join client as cl on cl.clientid = emp.empid
                // where emp.isapprover = 1 and (emp.empid = " . $emp_app[0]->approver1 . " or emp.empid = " . $emp_app[0]->approver2 . ")";

                // $data = $this->coreFunctions->opentable($qry);
                // foreach ($data as $key => $value) {
                //     if (!empty($data[$key]->email)) {
                //         $params['approver'] = $data[$key]->username;
                //         $params['email'] = $data[$key]->email;
                //         $this->linkemail->createChangeSchedEmail($params);
                //     }
                // }
            }
        }

        $submitdate = $this->othersClass->getCurrentTimeStamp();
        $this->logger->sbcmasterlog($trno, $config, "SUBMIT DATE : " . $submitdate);
        return ['row' => [], 'status' => true, 'msg' => 'Submit Success', 'backlisting' => true];
    }
    public function approved_dis($config)
    {
        $clientid = $config['params']['clientid'];
        $qry = "select status, status2 from changeshiftapp where line = ?";
        $status = $this->coreFunctions->opentable($qry, [$clientid]);
        $array_stat = [1, 2];

        if (in_array($status[0]->status2, $array_stat)) {
            return ['status' => true, 'istatus' => $status[0]->status2];
        }

        if (in_array($status[0]->status, $array_stat)) {
            return ['status' => true, 'istatus' => $status[0]->status];
        }

        return ['status' => false];
    }
    public function getday($config, $dayn, $shiftid, $sdata, $daytype)
    {

        $companyid = $config['params']['companyid'];

        switch ($companyid) {
            case 53: //camera
                $query = "select 0 as tothrs,'" . $sdata['schedin'] . "' as schedin,timestamp(date_add(date('" . $sdata['schedin'] . "'),interval case when date(date_add('" . $sdata['schedin'] . "',INTERVAL 9 HOUR)) > date('" . $sdata['schedin'] . "') then 1 else 0 end day),TIME(date_add('" . $sdata['schedin'] . "', interval 9 hour))) as schedout";
                $data = $this->coreFunctions->opentable($query);
                if ($sdata['daytype'] == 'WORKING') {
                    $data[0]->tothrs = 9;
                }
                break;

            default:
                $query = "select tothrs from shiftdetail where shiftsid = " . $shiftid . " and dayn = " . $dayn . "";
                $data = $this->coreFunctions->opentable($query);

                if ($sdata['daytype'] != '') {
                    if ($sdata['daytype'] == 'WORKING') {
                        goto compute;
                    }
                } else {
                    if ($daytype == 'WORKING') {
                        compute:
                        if ($data[0]->tothrs == 0) {
                            $schedin = Carbon::parse($sdata['schedin']);
                            $schedout = Carbon::parse($sdata['schedout']);

                            $mins = $schedin->diffInMinutes($schedout);
                            $hrs = $mins / 60;
                            $data[0]->tothrs = $hrs - 1;
                        }
                    }
                }
                break;
        }

        return $data;
    }
    public function getnextday($config, $dateid, $timein, $timeout)
    {

        $n_time = '00:00';
        if ($timeout >= $n_time && $timeout < '06:00') {
            $date = new DateTime("2025-01-01 $timeout");
            $date->modify('+1 day');
        }
        $date1 = $date->format("Y-m-d H:i:s");
        // var_dump($data1);
        $schedin = $this->othersClass->sanitizekeyfield('schedin', ".$date1.");
        $schedout = $this->othersClass->sanitizekeyfield('schedout', $date);
        // var_dump($schedout);

        return ['schedin' => $schedin, 'schedin' => $schedout];
    }
} //end class
