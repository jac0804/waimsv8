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

class pendingobcancelapplications
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'PENDING OB CANCELLATIONS';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $linkemail;
    public $tablelogs = 'payroll_log';
    public $style = 'width:90%;max-width:90%;';
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

    public function getAttrib()
    {
        $attrib = array(
            'load' => 5200
        );
        return $attrib;
    }

    public function createTab($config)
    {
        $doc = $config['params']['row']['doc'];
        $approver = $this->coreFunctions->getfieldvalue("employee", "isapprover", "empid=?", [$config['params']['adminid']]);
        $supervisor = $this->coreFunctions->getfieldvalue("employee", "issupervisor", "empid=?", [$config['params']['adminid']]);
        $url = 'App\Http\Classes\modules\payroll\\' . 'occ';
        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='OBCANCELLATION'");
        if ($approversetup == '') {
            $approversetup = app($url)->approvers($config['params']);
        } else {
            $approversetup = explode(',', $approversetup);
            foreach ($approversetup as $appkey => $appsetup) {
                if ($appsetup == 'Supervisor') {
                    $approversetup[$appkey] = 'issupervisor';
                } else {
                    $approversetup[$appkey] = 'isapprover';
                }
            }
        }
        $cols = ['action', 'clientname', 'dateid', 'type', 'ontrip', 'rem1'];
        $tab = [$this->gridname => ['gridcolumns' => $cols]];
        $stockbuttons = ['approve'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][1]['label'] = 'Name';
        $obj[0][$this->gridname]['columns'][1]['style'] = 'width:200px;min-width:200px;';
        $obj[0][$this->gridname]['columns'][1]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][2]['style'] = 'width:150px;min-width:150px;';
        $obj[0][$this->gridname]['columns'][2]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][3]['label'] = 'Type';
        $obj[0][$this->gridname]['columns'][3]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][4]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][5]['type'] = 'input';
        $obj[0][$this->gridname]['columns'][5]['label'] = 'Reason';
        $obj[0][$this->gridname]['columns'][5]['readonly'] = false;
        $obj[0][$this->gridname]['columns'][5]['style'] = 'width:250px;min-width:250px;';
        return $obj;
    }

    public function createtabbutton($config)
    {

        $obj = [];
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
        $qry = "select ob.line as trno,ob.line,cl.clientname, emp.empid,
            dept.clientname as deptname,ob.forapproval,date(ob.dateid) as dateid,ob.reason, ob.type,ob.ontrip,
            m.modulename as doc, m.sbcpendingapp
            from obapplication as ob
            left join employee as emp on emp.empid = ob.empid
            left join client as cl on cl.clientid = ob.empid
            left join client as dept on dept.clientid = emp.deptid
            left join pendingapp as p on p.line=ob.line and p.doc='OBCANCELLATION'
            left join moduleapproval as m on m.modulename=p.doc
            where ob.status = 'E' and ob.forapproval is not null and canceldate is null
            and p.clientid=" . $config['params']['adminid'] . "
            order by ob.dateid, cl.clientname";
        $data = $this->coreFunctions->opentable($qry);
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
        $doc = $row['doc'];
        $admin = $config['params']['adminid'];
        $approver = $this->coreFunctions->getfieldvalue("employee", "isapprover", "empid=?", [$admin]);
        $supervisor = $this->coreFunctions->getfieldvalue("employee", "issupervisor", "empid=?", [$admin]);
        $url = 'App\Http\Classes\modules\payroll\\' . 'obapplication';
        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='" . $doc . "'");
        if ($approversetup == '') {
            $approversetup = app($url)->approvers($config['params']);
        } else {
            $approversetup = explode(',', $approversetup);
            foreach ($approversetup as $appkey => $appsetup) {
                if ($appsetup == 'Supervisor') {
                    $approversetup[$appkey] = 'issupervisor';
                } else {
                    $approversetup[$appkey] = 'isapprover';
                }
            }
        }

        $line = $row['line'];
        if ($status == 'A') {
            $label = 'Approved';
            $lstatus = 'C';
        $status = $this->coreFunctions->datareader("select line as value from obapplication where line=? and status='C' and canceldate is not null and forapproval is not null", [$line]);
        }

        $date = $this->othersClass->getCurrentTimeStamp();
        $user = $config['params']['user'];
        if (!$status) {
            $data = ['reason' => $row['reason'], 'status' => $lstatus, 'canceldate' => $date, 'cancelby' => $user];
            $tempdata = [];
            foreach ($this->fields as $key2) {
                if (isset($data[$key2])) {
                    $tempdata[$key2] = $data[$key2];
                    $tempdata[$key2] = $this->othersClass->sanitizekeyfield($key2, $tempdata[$key2]);
                }
            }
            $tempdata['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $tempdata['editby'] = $config['params']['user'];
            $update = $this->coreFunctions->sbcupdate("obapplication", $tempdata, ['line' => $line, 'empid' => $row['empid']]);
            if ($update) {
                $this->coreFunctions->execqry("delete from pendingapp where doc='OBCANCELLATION' and line=" . $line, 'delete');
                $config['params']['doc'] = 'OBCANCELLATION';
                $this->logger->sbcmasterlog($line, $config, $label . ' (' . $row['clientname'] . ') - ' . $row['dateid']);
                return ['status' => true, 'msg' => 'Successfully ' . $label, 'data' => [], 'reloadsbclist' => true, 'action' => 'gapplications', 'deleterow' => true];
            }
            return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => []];
        } else {
            return ['status' => false, 'msg' => 'Cannot update; already Cancelled', 'data' => []];
        }
    }
} //end class
