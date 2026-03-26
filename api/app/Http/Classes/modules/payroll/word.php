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

class word
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'WORK ON REST DAY FORM';
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
        'daytype',
        'orgdaytype',
        'isword',
        'reason'
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
        $this->payrollcommon = new payrollcommon;
        $this->linkemail = new linkemail;
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 5148,
            'new' => 5150,
            'edit' => 5149,
            'save' => 5151,
            'delete' => 5152,
            'print' => 5154

        );
        return $attrib;
    }

    public function createdoclisting($config)
    {
        $companyid = $config['params']['companyid'];

        if ($companyid == 58) { //cdo
            $empid = $config['params']['adminid'];
            if ($empid == 0) $this->showcreatebtn = false;
        }
        $approver = $this->payrollcommon->checkapprover($config);
        $supervisor =  $this->payrollcommon->checksupervisor($config);
        if ($approver || $supervisor) {
            array_push($this->showfilterlabel, ['val' => 'approvedemp', 'label' => 'Approved Employees', 'color' => 'primary']);
        }
        $getcols = ['action', 'dateid', 'listappstatus2', 'listappstatus', 'clientname', 'rem'];

        foreach ($getcols as $key => $value) {
            $$value = $key;
        }

        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[$dateid]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        // $cols[$type]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$listappstatus]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;text-align:left;';
        $cols[$listappstatus2]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;text-align:left;';
        $cols[$rem]['style'] = 'width:400px;whiteSpace: normal;min-width:400px;';

        $cols[$clientname]['label'] = 'Name';
        $cols[$clientname]['style'] = 'width:200px;whiteSpace:normal;min-width:200px;';

        $cols[$listappstatus]['type'] = 'label';

        if ($companyid == 58) { //cdo
            $cols[$listappstatus]['label'] = 'Status (HR)';
        } else {
            $cols[$listappstatus2]['type'] = 'coldel';
        }


        return $cols;
    }

    public function loaddoclisting($config)
    {
        $id = $config['params']['adminid'];
        $user = $config['params']['user'];
        $companyid = $config['params']['companyid'];

        $date1 = date('Y-m-d', strtotime($config['params']['date1']));
        $date2 = date('Y-m-d', strtotime($config['params']['date2']));

        $filteroption = '';
        $option = $config['params']['itemfilter'];
        $draft = " and csapp.status= 0 ";
        $approved = " and csapp.status= 1 ";
        $disapproved = " and csapp.status= 2 ";

        if ($companyid == 58) {
            $disapproved = " and (csapp.status= 2 or csapp.status2= 2) ";
        }

        switch ($option) {
            case 'draft':
                $filteroption = " and csapp.empid=" . $id . " and csapp.status = 0 and csapp.submitdate is null ";
                break;
            case 'forapproval':
                $filteroption = " and csapp.empid=" . $id . " and csapp.status = 0 and csapp.status2 != '2' and csapp.submitdate is not null";
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


        $qry = "
        select csapp.line as trno,
        cl.client, cl.clientname, cl.clientid as empid, csapp.line as clientid,
        date(csapp.dateid) as dateid, csapp.rem,csapp.originalin,csapp.originalout,case
        when csapp.status = 0 then 'ENTRY'
        when csapp.status = 1 then 'APPROVED'
        when csapp.status = 2 then 'DISAPPROVED'
        END as jstatus,case when csapp.status2 = '0' then 'ENTRY'
               when csapp.status2 = '1' then 'APPROVED'
               when csapp.status2 = '2' then 'DISAPPROVED' end as status2
        from changeshiftapp as csapp
        left join employee as emp on emp.empid = csapp.empid
        left join client as cl on cl.clientid = emp.empid
        where csapp.isword = 1 and date(csapp.dateid) between '$date1' and '$date2'
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

    public function createTab($access, $config) {
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
        // $companyid = $config['params']['companyid'];
        $fields = ['client', 'createdate', 'dateid']; //['schedin', 'schedout']
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'client.label', 'Code');
        data_set($col1, 'client.action', 'lookupledger');
        data_set($col1, 'client.type', 'hidden');
        // data_set($col1, 'schedin.type', 'time');
        // data_set($col1, 'schedin.class', 'csschedin sbccsreadonly');
        // data_set($col1, 'schedout.type', 'time');
        // data_set($col1, 'schedout.class', 'csschedout sbccsreadonly');

        data_set($col1, 'createdate.label', 'Date Filed');
        data_set($col1, 'dateid.required', true);

        data_set($col1, 'dateid.label', 'Date of Schedule Duty');
        // data_set($col1, 'dateid.type', 'lookup');
        // data_set($col1, 'dateid.lookupclass', 'lookuprestdaywork');
        // data_set($col1, 'dateid.action', 'lookuprestdaywork');
        // data_set($col1, 'dateid.class', 'csdateid sbccsreadonly');

        $fields = ['reason']; //'daytype'
        $col2 = $this->fieldClass->create($fields);
        // data_set($col2, 'daytype.type', 'input');
        // data_set($col2, 'daytype.readonly', true);
        // data_set($col2, 'dateid.label', 'Date Filed');
        data_set($col2, 'reason.label', 'Reason');
        $fields = ['rem'];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'rem.label', 'Remarks');
        $fields = ['lblsubmit', 'submit'];
        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'lblsubmit.label', 'FOR APPROVAL');
        data_set($col4, 'submit.label', 'FOR APPROVAL');

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function newclient($config)
    {
        $data = $this->resetdata($config['newclient']);
        $hideobj = [];
        $hideobj['submit'] = true;
        $hideobj['lblsubmit'] = true;
        return  ['head' => $data, 'islocked' => false, 'isposted' => false, 'status' => true, 'isnew' => true, 'msg' => 'Ready for New Ledger', 'hideobj' => $hideobj];
    }

    private function resetdata($client = '')
    {
        $data = [];
        $data[0]['clientid'] = 0;
        $data[0]['client'] = $client;
        $data[0]['dateid'] = null;
        $data[0]['schedin'] = '00:00';
        $data[0]['schedout'] = '00:00';
        $data[0]['rem'] = '';
        $data[0]['status'] = 0;
        $data[0]['createdate'] = $this->othersClass->getCurrentDate();
        $data[0]['daytype'] = 'WORKING';
        $data[0]['orgdaytype'] = '';
        $data[0]['shiftcode'] = '';
        $data[0]['reason'] = '';
        $data[0]['isword'] = '1';
        $data[0]['submit'] = null;
        $data[0]['lblsubmit'] = '';
        $data[0]['submitdate'] = null;
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
        csapp.rem,csapp.createdate,csapp.submitdate,emp.divid,csapp.daytype,csapp.orgdaytype,csapp.shftcode as shiftcode,csapp.isrestday,
        csapp.status,csapp.reason
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
            $submitdate = $head[0]->submitdate != null ? true : false;
            if ($submitdate) {
                $hideobj['submit'] = true;
                if ($head[0]->status == '0') {
                    $hideobj['lblsubmit'] = false;
                }
            } else {
                $hideobj['submit'] = false;
                $hideobj['lblsubmit'] = true;
            }

            if ($head[0]->status == '1') {
                $hideobj['lblsubmit'] = true;
            }
            return  ['head' => $head, 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => false, 'qq' => $config['params']['clientid'], 'hideobj' => $hideobj, 'action' => 'backlisting'];
        } else {
            $msg = 'Data Fetched Failed, either somebody already deleted the transaction or modified...';

            if ($this->isexist == 1) {
                $msg = "Already Exist";
            }

            $head = $this->resetdata();
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

        if ($companyid == 58) { //cdo
            $chkrestriction = $this->payrollcommon->checkportalrestrict($head, $config);
            if (!empty($chkrestriction['msg'])) {
                $msg = $chkrestriction['msg'];
                return ['status' => false, 'msg' => $msg];
            }
        }

        if (isset($head['trno'])) {
            $approved = $this->coreFunctions->datareader("select approveddate as value from " . $this->head . " where line=? and approveddate is not null", [$head['trno']]);
            $approved2 = $this->coreFunctions->datareader("select approveddate2 as value from " . $this->head . " where line=? and approveddate2 is not null", [$head['trno']]);
            if ($approved || $approved2) {
                return ['status' => false, 'msg' => 'Cannot update; already approved.', 'clientid' => $config['params']['adminid']];
            }

            $submitdate = $this->coreFunctions->datareader("select submitdate as value from $this->head where line=? and submitdate is not null", [$head['trno']]);
            if ($submitdate) {
                return ['status' => false, 'msg' => 'Cannot update; already For approval.', 'clientid' => $config['params']['adminid']];
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

        // $data['schedin'] = $this->othersClass->sanitizekeyfield('schedin', $head['dateid'] . " " . $head['schedin']);
        // $data['schedout'] = $this->othersClass->sanitizekeyfield('schedout', $head['dateid'] . " " . $head['schedout']);

        $data['shftcode'] =  $this->othersClass->sanitizekeyfield('shiftcode', $head['shiftcode']);
        $empname = $this->coreFunctions->datareader("select cl.clientname as value 
        from employee as e
        left join client as cl on cl.clientid = e.empid
        where e.empid = ?", [$config['params']['adminid']]);

        if ($isupdate) {
            $date = date('Y-m-d', strtotime($data['dateid']));
            if (!empty($this->checking($config, $date))) {
                $msg = "Already Exist";
                $clientid = 0;
            } else {

                $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
                $data['editby'] = $config['params']['user'];
                $this->coreFunctions->sbcupdate($this->head, $data, ['line' => $head['clientid']]);
                $clientid = $head['clientid'];

                $this->logger->sbcmasterlog(
                    $clientid,
                    $config,
                    "UPDATE - NAME: $empname, SCHEDULE DATE: " . $data['dateid'] . ", REASON: " . $data['reason'] . "  REMARKS: " . $data['rem'] . " "
                );
            }
        } else {
            $daytype = $this->coreFunctions->datareader("select daytype as value from timecard where empid=? and date_format(dateid, '%Y-%m-%d') = ?", [$empid, date('Y-m-d', strtotime($data['dateid']))]);
            if ($data['daytype'] == "") {
                $data['daytype'] = $daytype;
            }
            $data['orgdaytype'] = $daytype;
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];
            $data['empid'] =  $config['params']['adminid'];
            $date = date('Y-m-d', strtotime($data['dateid']));
            if (!empty($this->checking($config, $date))) {
                $msg = "Already Exist";
                $clientid = 0;
            } else {

                $clientid = $this->coreFunctions->insertGetId($this->head, $data);
                // if ($config['params']['companyid'] == 58) {
                //     $url = 'App\Http\Classes\modules\payroll\\' . 'word';
                //     $appstatus = $this->othersClass->insertPendingapp(0, $clientid, 'WORKONRESTDAY', $data, $url, $config, 0, true);
                //     if (!$appstatus['status']) {
                //         $this->coreFunctions->execqry("delete from changeshiftapp where line=".$clientid, 'delete');
                //         $msg = $appstatus['msg'];
                //         $status = $appstatus['status'];
                //     } else {
                //         goto log;
                //     }
                // } else {
                //     log:
                // }
                $this->logger->sbcmasterlog(
                    $clientid,
                    $config,
                    "CREATE - NAME: $empname, SCHEDULE DATE: " . $data['dateid'] . ", 
                      REASON: " . $data['reason'] . "  REMARKS: " . $data['rem'] . ""
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

        $qry = "select status,status2,line from " . $this->head . ' where empid = "' . $empid . '" and date(dateid) = "' . $date . '" and status <> 2 and status2 <> 2 and isword = 1';
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

    public function stockstatusposted($config)
    {
        $line = $config['params']['trno'];
        $update = $this->coreFunctions->sbcupdate($this->head, ['submitdate' => $this->othersClass->getCurrentTimeStamp()], ['line' => $line]);
        if ($update) {
            $url = 'App\Http\Classes\modules\payroll\\' . 'word';
            $empid = $this->coreFunctions->getfieldvalue($this->head, "empid", "line=?", [$line]);
            $data = ['empid' => $empid];
            $appstatus = $this->othersClass->insertUpdatePendingapp(0, $line, 'WORKONRESTDAY', $data, $url, $config, 0, true, true);
            if (!$appstatus['status']) {
                $this->coreFunctions->sbcupdate($this->head, ['submitdate' => null], ['line' => $line]);
                $msg = $appstatus['msg'];
                $status = $appstatus['status'];
                return ['status' => $status, 'msg' => $msg];
            }
            return ['status' => true, 'msg' => 'Success', 'backlisting' => true];
        }
        return ['status' => false, 'msg' => 'Error updating record'];
    }

    public function deletetrans($config)
    {

        $clientid = $config['params']['clientid'];

        $approved = $this->coreFunctions->datareader("select approveddate as value from changeshiftapp where line=? and approveddate is not null", [$clientid]);
        if ($approved) {
            return ['status' => false, 'msg' => 'Cannot update; already approved.', 'clientid' => $clientid];
        }

        $submitdate = $this->coreFunctions->datareader("select approveddate as value from changeshiftapp where line=? and submitdate is not null", [$clientid]);
        if ($submitdate) {
            return ['status' => false, 'msg' => 'Cannot update; already For approval.', 'clientid' => $clientid];
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
                $approvers = ['issupervisor', 'isapprover'];
                break;
            default:
                $approvers = ['isapprover'];
                break;
        }
        return $approvers;
    }
} //end class
