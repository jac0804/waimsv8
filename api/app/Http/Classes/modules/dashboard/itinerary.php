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
use Illuminate\Support\Facades\Storage;
use DateTime;
use DateInterval;
use DatePeriod;

class itinerary
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'TRAVEL APPLICATION';
    public $gridname = 'customformacctg';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $linkemail;
    public $tablelogs = 'payroll_log';
    public $style = 'width:90%;max-width:90%;';
    public $issearchshow = true;
    public $showclosebtn = true;
    public $fields = ['status', 'approvedby', 'approvedate', 'approvedrem', 'disapprovedby', 'disapprovedate'];


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
        $url = 'App\Http\Classes\modules\payroll\\' . 'itinerary';
        $approversetup = app($url)->approvers($config['params']);

        $fields = ['clientname', 'lblmessage', 'remarks'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'lblmessage.label', 'Employee Remarks: ');
        data_set($col1, 'rem.label', 'Remarks');
        data_set($col1, 'lblmessage.style', 'font-size:11px;font-weight:bold;');

        $fields = [['startdate', 'enddate'], 'lblrem', 'remark'];

        $col2 = $this->fieldClass->create($fields);

        data_set($col2, 'startdate.style', 'padding:0px');
        data_set($col2, 'enddate.style', 'padding:0px');
        data_set($col2, 'enddate.readonly', true);
        data_set($col2, 'lblrem.style', 'font-size:11px;font-weight:bold;');
        data_set($col2, 'lblrem.label', 'Approver Remarks:');
        $fields = [];
        $col3 = $this->fieldClass->create($fields);
        $fields = [['refresh', 'disapproved']];

        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'refresh.label', 'APPROVE');
        data_set($col4, 'refresh.confirm', true);
        data_set($col4, 'refresh.confirmlabel', 'Approved this Travel Application?');
        data_set($col4, 'refresh.disapproved', true);
        data_set($col4, 'refresh.color', 'blue');
        data_set($col4, 'disapproved.confirmlabel', 'Disapproved this Travel Application?');
        data_set($col4, 'disapproved.color', 'red');
        data_set($col4, 'refresh.style', 'width:100%;');

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function paramsdata($config)
    {
        $line = $config['params']['row']['line'];
        $query = "select it.trno, it.empid,client.clientname, date(it.startdate) as startdate,
        date(it.enddate) as enddate,it.remarks,it.approverem as remark
        from itinerary as it
        left join client on client.clientid=it.empid
        where it.trno = $line ";
        $result = $this->coreFunctions->opentable($query);
        return $result;
    }

    public function data()
    {
        return [];
    }

    public function loaddata($config)
    {
        $remarks = $config['params']['dataparams']['remarks']; // emp remarks
        $remark = $config['params']['dataparams']['remark']; // app remark
        $trno = $config['params']['dataparams']['trno'];
        $empid = $config['params']['dataparams']['empid'];
        $start = $config['params']['dataparams']['startdate'];
        $end = $config['params']['dataparams']['enddate'];
        $empname = $config['params']['dataparams']['clientname'];
        $action = $config['params']['action2'];
        $user = $config['params']['user'];

        $admin = $config['params']['adminid'];
        $issupervisor = $this->coreFunctions->getfieldvalue("employee", "issupervisor", "empid=?", [$admin]);
        $url = 'App\Http\Classes\modules\payroll\\' . 'itinerary';
        $approversetup = app($url)->approvers($config['params']);
        if (isset($config['params']['dataparams']['trno'])) {
            $trno = $config['params']['dataparams']['trno'];

            switch ($action) {
                case 'ar':
                    $itstatus = 'A';
                    $label = 'Approved ';
                    $status = $this->coreFunctions->datareader("select approvedate as value from itinerary where trno=? and approvedate is not null", [$trno]);
                    break;

                default:
                    $itstatus = 'D';
                    $label = 'Disapproved ';
                    $status = $this->coreFunctions->datareader("select disapprovedate as value from itinerary where trno=? and disapprovedate is not null", [$trno]);
                    break;
            }

            if (!$status) {

                $data = [
                    'status' =>  $itstatus,
                    'approverem' =>  $remark,

                ];
                $date = $this->othersClass->getCurrentTimeStamp();


                if ($itstatus == 'A') {
                    $data['approvedby'] = $user;
                    $data['approvedate'] = $date;
                } else {
                    $data['disapprovedby'] = $user;
                    $data['disapprovedate'] = $date;
                }

                $tempdata['editdate'] = $this->othersClass->getCurrentTimeStamp();
                $tempdata['editby'] = $config['params']['user'];

                $tempdata = [];
                foreach ($this->fields as $key2) {
                    if (isset($data[$key2])) {
                        $tempdata[$key2] = $data[$key2];
                        $tempdata[$key2] = $this->othersClass->sanitizekeyfield($key2, $tempdata[$key2]);
                    }
                }

                unset($tempdata['approverem']);
                unset($tempdata['editdate']);
                unset($tempdata['editby']);
                $tempdata['empid'] = $empid;
                $update = $this->coreFunctions->sbcupdate("itinerary", $tempdata, ['trno' => $trno]);
                if ($update) {
                    $tempdata['batchob'] = $trno;
                    $tempdata['isitinerary'] = 1;
                    $result = $this->generate_ob($config, $start, $end, $tempdata, $empid, $empname, $trno);
                    if (!$result['status']) {
                        // $result['msg'];
                        return ['status' => false, 'msg' => $result['msg'], 'data' => []];
                    }
                    $config['params']['doc'] = 'ITENERARY';
                    $this->logger->sbcmasterlog($config['params']['dataparams']['trno'], $config, $label . ' (' . $config['params']['dataparams']['clientname'] . ')');
                    return ['status' => true, 'msg' => 'Successfully approved.', 'data' => [], 'reloadsbclist' => true, 'action' => 'itinerary'];
                }
            } else {
                return ['status' => false, 'msg' => 'Already approved.', 'data' => []];
            }
        }

        return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => []];
    }
    public function generate_ob($config, $start, $end, $tempdata, $empid, $empname, $trno)
    {
        $start = new DateTime(date('Y-m-d', strtotime($start)));
        $end = new DateTime($end);
        $status = true;

        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start, $interval, $end->modify('+1 day'));

        $dates = [];
        $filter = "";
        $c = 0;
        foreach ($period as $date) {
            $query = "select time(schedin) as value,daytype from timecard where empid = $empid and dateid = '" . $date->format('Y-m-d') . "' and daytype = 'WORKING'";
            $tmcd_sched = $this->coreFunctions->opentable($query);
            if (!empty($tmcd_sched)) {
                if ($tmcd_sched[0]->daytype != 'RESTDAY') {
                    array_push($dates, $date->format('Y-m-d') . ' ' . $tmcd_sched[0]->value);
                    if ($c == 0) {
                        $filter .= "'" . $date->format('Y-m-d') . "'";
                    } else {
                        $filter .= ",'" . $date->format('Y-m-d') . "'";
                    }
                    $c++;
                }
            }
        }


        $query = "select count(line) as value  from timecard where empid = $empid and dateid in (" . $filter . ") and daytype = 'WORKING'";
        $tmcd_sched = $this->coreFunctions->datareader($query);
        if ($tmcd_sched != count($dates)) {
            return ['status' => false, 'msg' => 'failed to generate obapplication application schedule not match'];
        }

        $line = 0;
        $i = 0;
        $msg = '';
        $linelist = '';
        $sanitized_fields = ['dateid', 'createdate', 'scheddate'];
        $curdate = $this->othersClass->getCurrentTimeStamp();
        foreach ($dates as $date) {

            foreach ($tempdata as $key2 => $value) {

                if (strpos($key2, "dateid") !== false) {
                    $tempdata[$key2] = $date;
                }
                if (strpos($key2, "createdate") !== false) {
                    $tempdata[$key2] = $curdate;
                }
                if (strpos($key2, "scheddate") !== false) {
                    $tempdata[$key2] = $date;
                }
                if ($i == 0) {
                    $tempdata['dateid'] = $date;
                    $tempdata['createdate'] = $curdate;
                    $tempdata['scheddate'] = $date;
                }
                if (in_array($key2, $sanitized_fields)) {
                    $this->othersClass->sanitizekeyfield('dateid', $tempdata[$key2]);
                }
            }
            $lineob = $this->coreFunctions->insertGetId('obapplication', $tempdata);
            if ($lineob != 0) {
                if ($i != 0) {
                    $linelist .= ',' . $lineob;
                } else {
                    $line = $lineob;
                    $linelist .= $lineob;
                }
                $config['params']['doc'] = 'ITENERARY';
                $msg = "SUCCESSFULLY GENERATE OB ON TRIP";
                $this->logger->sbcmasterlog($lineob, $config, $msg . " NAME: $empname " . "," . "  Schedule Date : " . $start->format('Y-m-d') . " Schedule End: " . $end->format('Y-m-d') . "");
            } else {
                $status = false;
                $this->coreFunctions->execqry("delete from obapplication where line in (" . $linelist . ") ", "delete");
                $msg = "FAILED TO GENERATE OB ON TRIP";
                $this->logger->sbcmasterlog($lineob, $config, $msg . "," . "  Schedule Date : " . $start->format('Y-m-d') . " Schedule End: " . $end->format('Y-m-d') . "");
                break;
            }
            $i++;
        }

        return ['status' => $status, 'msg' => $msg];
    }
} //end class
