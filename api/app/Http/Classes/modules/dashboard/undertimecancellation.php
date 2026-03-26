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

class undertimecancellation
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'UNDERTIME CANCELLATION';
    public $gridname = 'customformacctg';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $linkemail;
    public $tablelogs = 'payroll_log';
    public $style = 'width:1000px;max-width:1000px;';
    public $issearchshow = true;
    public $showclosebtn = true;
    public $fields = ['status', 'canceldate', 'cancelby', 'reason'];


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
        $companyid = $config['params']['companyid'];
        $fields = ['client', 'clientname', 'deptname', 'lblmessage', 'remarks'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'itime.label', 'Time');
        data_set($col1, 'client.label', 'Code');
        data_set($col1, 'client.type', 'hidden');

        data_set($col1, 'remarks.readonly', true);

        data_set($col1, 'lblmessage.label', 'Employee Remarks: ');
        data_set($col1, 'lblmessage.style', 'font-size:11px;font-weight:bold;');


        $fields = ['dateid', 'lblrem', 'reason'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'acnoname.readonly', true);

        data_set($col2, 'dateid.label', 'Date Applied');
        data_set($col2, 'dateid.type', 'input');
        data_set($col2, 'dateid.style', 'padding:0px');

        data_set($col2, 'reason.label', 'Reason');
        data_set($col2, 'reason.readonly', false);

        data_set($col2, 'lblrem.style', 'font-size:11px;font-weight:bold;');
        data_set($col2, 'lblrem.label', 'Reason:');

        $fields = [['refresh']];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'refresh.label', 'APPROVED');
        data_set($col3, 'refresh.confirm', true);
        data_set($col3, 'refresh.confirmlabel', "Approved this Cancellation Application");

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
    }

    public function paramsdata($config)
    {
        $line = $config['params']['row']['line'];
        $query = "
        select ut.line,ut.line as trno,concat(emp.emplast,', ',emp.empfirst,' ',emp.empmiddle) as clientname,
        dept.clientname as deptname,ut.forapproval,date_format(ut.dateid, '%Y-%m-%d %h:%i %p') as dateid,jt.jobtitle,ut.rem,ut.empid,ut.reason
        from undertime as ut
        left join employee as emp on emp.empid = ut.empid
        left join jobthead as jt on jt.line = emp.jobid
        left join client as dept on dept.clientid = emp.deptid
        where ut.status = 'E' and ut.line =? ";
        return $this->coreFunctions->opentable($query, [$line]);
    }

    public function data()
    {
        return [];
    }

    public function loaddata($config)
    {
        $empid = $config['params']['dataparams']['empid'];
        $dateid = $config['params']['dataparams']['dateid'];
        $reason = $config['params']['dataparams']['reason'];

        $url = 'App\Http\Classes\modules\payroll\\' . 'undertime';
        $approversetup = app($url)->approvers($config['params']);

        $action = $config['params']['action2'];
        if (isset($config['params']['dataparams']['line'])) {
            $line = $config['params']['dataparams']['line'];
            switch ($action) {
                case 'ar':
                    $label = 'Approved';
                    $lstatus = 'C';
                    $status = $this->coreFunctions->datareader("select approvedate as value from undertime where line = ? and status = 'C' and canceldate is not null and disapprovedate is not null", [$line]);
                    break;
            }
            $date = $this->othersClass->getCurrentTimeStamp();
            $user = $config['params']['user'];
            if (!$status) {
                $data = [
                    'reason' => $reason,
                    'status' => $lstatus,
                    'canceldate' => $date,
                    'cancelby' => $user
                ];
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
            $update = $this->coreFunctions->sbcupdate('undertime', $tempdata, ['line' => $line, 'empid' => $empid]);
            if ($update) {
                $config['params']['doc'] = 'UNDERTIMECANCELLATION';
                $this->logger->sbcmasterlog($config['params']['dataparams']['line'] . '' . $config['params']['dataparams']['trno'], $config, $label . ' (' . $config['params']['dataparams']['clientname'] . ') - ' . $config['params']['dataparams']['dateid']);
                return ['status' => true, 'msg' => 'Successfully ' . $label . ' ', 'data' => [], 'reloadsbclist' => true, 'action' => 'undertimecancellation'];
            }
        }

        return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => []];
    }
} //end class
