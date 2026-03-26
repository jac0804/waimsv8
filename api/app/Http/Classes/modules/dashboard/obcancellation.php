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

class obcancellation
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'OB CANCELLATION';
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
        $fields = ['client', 'clientname', 'dateid', 'deptname'];
        if ($companyid == 58) { //cdo
            array_push($fields, 'ontrip');
        }
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'dateid.label', 'Schedule Date');
        data_set($col1, 'dateid.type', 'input');
        data_set($col1, 'client.label', 'Code');
        data_set($col1, 'client.type', 'hidden');
        data_set($col1, 'dateid.style', 'padding:0px');
        if ($companyid == 58) { //cdo
            data_set($col1, 'ontrip.type', 'input');
        }

        $fields = ['atype', 'reason'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'atype.type', 'input');
        data_set($col2, 'atype.readonly', true);
        data_set($col2, 'reason.label', 'Reason');
        data_set($col2, 'reason.readonly', false);
        data_set($col2, 'acnoname.readonly', true);

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
        select ob.line,ob.line as trno,concat(emp.emplast,', ',emp.empfirst,' ',emp.empmiddle) as clientname,
        dept.clientname as deptname,ob.forapproval,date_format(ob.dateid, '%Y-%m-%d %h:%i %p') as dateid,
        ob.reason,ob.empid,ob.type,ob.ontrip
        from obapplication as ob
        left join employee as emp on emp.empid = ob.empid
        left join client as dept on dept.clientid = emp.deptid
        where ob.status = 'E' and ob.line =? ";
        return $this->coreFunctions->opentable($query, [$line]);
    }

    public function data()
    {
        return [];
    }

    public function loaddata($config)
    {
        $empid = $config['params']['dataparams']['empid'];
        $reason = $config['params']['dataparams']['reason'];

        $url = 'App\Http\Classes\modules\payroll\\' . 'obapplication';
        $approversetup = app($url)->approvers($config['params']);

        $action = $config['params']['action2'];
        if (isset($config['params']['dataparams']['line'])) {
            $line = $config['params']['dataparams']['line'];
            switch ($action) {
                case 'ar':
                    $label = 'Approved';
                    $lstatus = 'C';
                    $status = $this->coreFunctions->datareader("select line as value from obapplication where line = ? and status = 'C' and canceldate is not null and forapproval is not null", [$line]);
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
            $update = $this->coreFunctions->sbcupdate('obapplication', $tempdata, ['line' => $line, 'empid' => $empid]);
            if ($update) {
                $config['params']['doc'] = 'OBCANCELLATION';
                $this->logger->sbcmasterlog($config['params']['dataparams']['line'] . '' . $config['params']['dataparams']['trno'], $config, $label . ' (' . $config['params']['dataparams']['clientname'] . ') - ' . $config['params']['dataparams']['dateid']);
                return ['status' => true, 'msg' => 'Successfully ' . $label . ' ', 'data' => [], 'reloadsbclist' => true, 'action' => 'obcancellation'];
            }
        }

        return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => []];
    }
} //end class
