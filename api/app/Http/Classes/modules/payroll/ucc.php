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

class ucc
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'UNDERTIME CANCELLATION';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $linkemail;
    private $payrollcommon;
    private $logger;
    private $sqlquery;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
    public $head = 'undertime';
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
            'view' => 5176,
            'print' => 5177,
            'save' => 5178
        );

        return $attrib;
    }

    public function createdoclisting($config)
    {


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
        when ut.status = 'E' and ut.forapproval is null and ut.submitdate is not null then 'ENTRY'
        when ut.status = 'E' and ut.forapproval is not null and ut.submitdate is not null then 'FOR CANCELLATION'
        when ut.status = 'C' then 'CANCELLED'
        end";
        $filter = " and date(ut.dateid) between '$start' and '$end'";

        $sortby = 'ut.dateid desc';
        if ($config['params']['companyid'] == 58) { //cdo
            $sortby = 'ut.dateid';
        }

        switch ($option) {
            case 'draft':
                $filteroption = " ut.empid=" . $empid . " and ut.status='E' and ut.forapproval is null and ut.submitdate is not null";
                break;
            case 'forcancellation':
                $filteroption = " ut.empid=" . $empid . " and ut.status='E' and ut.forapproval is not null and ut.canceldate is null and ut.submitdate is not null";
                break;
            case 'cancelled':
                $filteroption = " ut.empid=" . $empid . " and ut.status='C' and ut.canceldate is not null ";
                break;
        }

        $qry = "
        select ut.line as trno,ut.line as clientid,date(ut.dateid) as dateid, 
        cl.clientname,
        " . $addcase . " as jstatus
        from undertime as ut
        left join client as cl on cl.clientid = ut.empid
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
        data_set($col1, 'itime.label', 'Time');
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
        select ut.line as clientid,ut.line as trno, ut.line,concat(emp.emplast,', ',emp.empfirst,' ',emp.empmiddle) as clientname,
        dept.clientname as deptname,ut.forapproval,date_format(ut.dateid, '%Y-%m-%d %h:%i %p') as dateid,ut.canceldate
        from undertime as ut
        left join employee as emp on emp.empid = ut.empid
        left join client as dept on dept.clientid = emp.deptid
        where ut.line= $line and ut.empid = $empid";

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

        $pendingdata = $this->coreFunctions->opentable("select * from pendingapp where doc='UNDERTIME' and line=".$trno);
        $this->coreFunctions->execqry("delete from pendingapp where doc='UNDERTIME' and line=".$trno, 'delete');
        $url = 'App\Http\Classes\modules\payroll\\' . 'undertime';
        $data = ['empid' => $config['params']['adminid']];
        $appstatus = $this->othersClass->insertUpdatePendingapp(0, $trno, 'UNDERTIMECANCELLATION', $data, $url, $config, 0, true, true);
        if (!$appstatus['status']) {
            goto reinsertpendingapp;
            $msg = $appstatus['msg'];
            $status = $appstatus['status'];
            return ['status' => false, 'msg' => $msg, 'data' => []];
        } else {
            $update = $this->coreFunctions->execqry("update undertime set  forapproval = '$forapproval' where line = " . $trno . "", 'update');
            if ($update) {
                $msg = 'Undertime Cancellation For Approval';
                $status = true;
            } else {
                $msg = 'Failed for Approval';
                $status = false;
                reinsertpendingapp:
                $this->coreFunctions->execqry("delete from pendingapp where doc='UNDERTIMECANCELLATION' and trno=".$trno, 'delete');
                if (!empty($pendingdata)) {
                    foreach ($pendingdata as $pd) {
                        $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid, approver) values(?, ?, ?, ?, ?)", 'insert', [$pd->trno, $pd->line, $pd->doc, $pd->clientid, $pd->approver]);
                    }
                }
                return ['status' => $status, 'msg' => $msg, 'data' => []];
            }
        }

        return ['row' => [], 'status' => $status, 'msg' => $msg, 'backlisting' => true];
    }
} //end class
