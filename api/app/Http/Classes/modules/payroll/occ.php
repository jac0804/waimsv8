<?php

namespace App\Http\Classes\modules\payroll;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;
use App\Http\Classes\common\linkemail;
use App\Http\Classes\common\payrollcommon;
use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;
use GuzzleHttp\Psr7\Query;

class occ
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'OB CANCELLATION';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $linkemail;
    private $payrollcommon;
    private $logger;
    private $sqlquery;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
    public $head = 'obapplication';
    public $prefix = '';
    public $tablelogs = 'payroll_log';
    public $tablelogs_del = '';
    private $stockselect;

    private $fields = [];
    private $except = ['clientid', 'client'];
    private $blnfields = [];
    public $showfilteroption = true;
    public $showfilter = true;
    public $showcreatebtn = false;
    private $reporter;
    private $isexist = 0;

    public $showfilterlabel = [
        ['val' => 'draft', 'label' => 'Entry', 'color' => 'primary'],
        ['val' => 'forcancellation', 'label' => 'For Cancellation', 'color' => 'primary'],
        ['val' => 'cancelled', 'label' => 'Cancelled', 'color' => 'primary']
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
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 5197,
            'print' => 5198,
            'save' => 5199
        );

        return $attrib;
    }

    public function createdoclisting($config)
    {
        switch ($config['params']['companyid']) {
            case 58: //cdo
                $this->modulename = 'TRACKING CANCELLATION';
                break;
        }

        $getcols = ['action', 'dateid', 'listappstatus', 'clientname', 'reason'];


        foreach ($getcols as $key => $value) {
            $$value = $key;
        }
        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[$dateid]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$listappstatus]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;text-align:left;';
        $cols[$clientname]['label'] = 'Name';
        $cols[$clientname]['style'] = 'width:200px;whiteSpace:normal;min-width:200px;';
        $cols[$listappstatus]['type'] = 'label';
        $cols[$reason]['label'] = 'Reason';

        $this->tabClass->delcollisting($cols);
        return $cols;
    }

    public function loaddoclisting($config)
    {
        $empid = $config['params']['adminid'];
        $filteroption = '';
        $option = $config['params']['itemfilter'];
        $start = $config['params']['date1'];
        $end = $config['params']['date2'];


        $addcase = " 
        case 
        when ob.status = 'E' and ob.forapproval is null and ob.submitdate is not null then 'ENTRY'
        when ob.status = 'E' and ob.forapproval is not null and ob.submitdate is not null then 'FOR CANCELLATION'
        when ob.status = 'C' then 'CANCELLED'
        end";
        $filter = " and date(ifnull(ob.dateid,ob.dateid2)) between '$start' and '$end'";
        $sortby = 'ob.dateid desc';
        if ($config['params']['companyid'] == 58) { //cdo
            $sortby = 'ob.dateid';
        }
        switch ($option) {
            case 'draft':
                $filteroption = " ob.empid=" . $empid . " and ob.status='E' and ob.forapproval is null and ob.submitdate is not null";
                break;
            case 'forcancellation':
                $filteroption = " ob.empid=" . $empid . " and ob.status='E' and ob.forapproval is not null and ob.canceldate is null and ob.submitdate is not null";
                break;
            case 'cancelled':
                $filteroption = " ob.empid=" . $empid . " and ob.status='C' and ob.canceldate is not null ";
                break;
        }

        $qry = "
        select ob.line as trno,ob.line as clientid,date(ifnull(ob.dateid,ob.dateid2)) as dateid, 
        cl.clientname,ob.reason,
        " . $addcase . " as jstatus
        from obapplication as ob
        left join client as cl on cl.clientid = ob.empid
        where " . $filteroption . " $filter
        order by " . $sortby;

        $data = $this->coreFunctions->opentable($qry, [$empid]);
        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
    }

    public function createHeadbutton($config)
    {
        $btns = array(
            'load',
            'save',
            'print',
            'logs',
            'backlisting',
            'toggleup',
            'toggledown'
        );
        $buttons = $this->btnClass->create($btns);
        return $buttons;
    } // createHeadbutton

    public function createTab($access, $config)
    {
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

    public function createHeadField($config)
    {

        $fields = ['client', 'clientname', 'dateid', 'deptname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'dateid.label', 'Date Applied');
        data_set($col1, 'client.label', 'Code');
        data_set($col1, 'client.type', 'hidden');
        data_set($col1, 'dateid.type', 'input');

        $fields = ['reason'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'reason.label', 'Reason');
        $fields = ['forcancellation', 'lblsubmit', 'lblrem'];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'lblsubmit.label', 'For Cancellation');
        data_set($col3, 'lblrem.label', 'Cancelled');
        data_set($col3, 'lblrem.style', 'font-weight:bold;font-size:15px;font-family:Century Gothic;color: green;');
        $fields = [];
        $col4 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    function getheadqry($config, $line)
    {
        $empid = $config['params']['adminid'];
        $qry = "
        select ob.line as clientid,ob.line as trno, ob.line,concat(emp.emplast,', ',emp.empfirst,' ',emp.empmiddle) as clientname,
        dept.clientname as deptname,ob.forapproval,date_format(ob.dateid, '%Y-%m-%d %h:%i %p') as dateid,ob.canceldate,ob.reason
        from obapplication as ob
        left join employee as emp on emp.empid = ob.empid
        left join client as dept on dept.clientid = emp.deptid
        where ob.line= $line and ob.empid = $empid";

        return $qry;
    }


    public function loadheaddata($config)
    {
        $line = $config['params']['clientid'];

        if ($line == 0) {
            if (isset($config['params']['adminid'])) {
                $line = $config['params']['adminid'];
            }
        }

        $head = $this->coreFunctions->opentable($this->getheadqry($config, $line));
        if (!empty($head)) {
            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }
            $hideobj = [];
            $cancel = $head[0]->forapproval != null ? true : false;
            if ($cancel) {
                $hideobj['forcancellation'] = true;
                $hideobj['lblsubmit'] = false;
                $hideobj['lblrem'] = true;
                if ($head[0]->canceldate != null) {
                    $hideobj['forcancellation'] = true;
                    $hideobj['lblsubmit'] = true;
                    $hideobj['lblrem'] = false;
                }
            } else {
                $hideobj['forcancellation'] = false;
                $hideobj['lblsubmit'] = true;
                $hideobj['lblrem'] = true;
            }
            $config['params']['clientid'] = $line;
            return  ['head' => $head, 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => false, 'qq' => $config['params']['clientid'], 'hideobj' => $hideobj, 'action' => 'backlisting'];
        }
    }


    public function openstock($trno, $config)
    {
        $qry = "";
        return $this->coreFunctions->opentable($qry);
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

    public function stockstatusposted($config)
    {

        $action = $config['params']['action'];
        switch ($action) {
            case 'forcancellation':
                return  $this->forcancellation($config);
                break;
        }
    }

    public function forcancellation($config)
    {

        $trno = $config['params']['trno'];
        $forapproval = $this->othersClass->getCurrentDate();
        $update = $this->coreFunctions->execqry("update obapplication set  forapproval = '$forapproval' where line = " . $trno . "", 'update');

        if ($update) {
            $pendingdata = $this->coreFunctions->opentable("select * from pendingapp where doc='OB' and line=" . $trno);
            $this->coreFunctions->execqry("delete from pendingapp where line=? and doc='OB'", 'delete', [$trno]);
            $url = 'App\Http\Classes\modules\payroll\\' . 'obapplication';
            $data = ['empid' => $config['params']['adminid']];
            $appstatus = $this->othersClass->insertUpdatePendingapp(0, $trno, 'OBCANCELLATION', $data, $url, $config, 0, true, true);
            if (!$appstatus['status']) {
                $this->coreFunctions->execqry("update obapplication set forapproval=null where line=" . $trno, 'update');
                if (!empty($pendingdata)) {
                    foreach ($pendingdata as $pd) {
                        $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid, approver) values(?, ?, ?, ?, ?)", 'insert', [$pd->trno, $pd->line, $pd->doc, $pd->clientid, $pd->approver]);
                    }
                }
                $msg = $appstatus['msg'];
                $status = $appstatus['status'];
            } else {
                $msg = 'OB Cancellation For Approval';
                $status = true;
            }
        } else {
            $msg = 'Failed for Approval';
            $status = false;
        }

        return ['row' => [], 'status' => $status, 'msg' => $msg, 'backlisting' => true];
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
} //end class
