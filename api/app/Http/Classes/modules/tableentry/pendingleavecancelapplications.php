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

class pendingleavecancelapplications
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'PENDING LEAVE CANCELLATIONS';
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
            'load' => 5158
        );
        return $attrib;
    }

    public function createTab($config)
    {
        $doc = $config['params']['row']['doc'];
        $approver = $this->coreFunctions->getfieldvalue("employee", "isapprover", "empid=?", [$config['params']['adminid']]);
        $supervisor = $this->coreFunctions->getfieldvalue("employee", "issupervisor", "empid=?", [$config['params']['adminid']]);
        $url = 'App\Http\Classes\modules\payroll\\' . 'lcc';
        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='LEAVECANCELLATION'");
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
        $cols = ['action', 'clientname', 'codename', 'dateid', 'dateeffect', 'rem1'];
        $tab = [$this->gridname => ['gridcolumns' => $cols]];
        $stockbuttons = ['approve'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][1]['label'] = 'Name';
        $obj[0][$this->gridname]['columns'][1]['style'] = 'width:200px;min-width:200px;';
        $obj[0][$this->gridname]['columns'][1]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][2]['style'] = 'width:150px;min-width:150px;';
        $obj[0][$this->gridname]['columns'][2]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][3]['label'] = 'Date Applied';
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
        $qry = "select date(lt.dateid) as dateid,cl.clientname,lt.trno,lt.line,lt.empid,
            dept.clientname AS deptname,p.codename,date(lt.effectivity) as dateeffect,
            m.modulename as doc, m.sbcpendingapp, lt.reason as rem1
            from leavetrans as lt
            left join leavesetup as ls on ls.trno = lt.trno
            left join paccount as p on p.line=ls.acnoid
            left join employee as emp on emp.empid = lt.empid
            left join client as cl on cl.clientid = lt.empid
            left join client as dept on dept.clientid = emp.deptid 
            left join pendingapp as papp on papp.trno=ls.trno and papp.line=lt.line and papp.doc='LEAVECANCELLATION'
            left join moduleapproval as m on m.modulename=papp.doc
            where lt.status = 'E' and lt.forapproval is not null and canceldate is null
            and papp.clientid=" . $config['params']['adminid'] . "
            order by lt.dateid, cl.clientname";
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
        $trno = $row['trno'];
        if ($status == 'A') {
            $label = 'Approved';
            $lstatus = 'C';
            $status = $this->coreFunctions->datareader("select date_approved_disapproved as value from leavetrans where trno=? and line=? and status='C' and canceldate is not null", [$trno, $line]);
        }
        $date = $this->othersClass->getCurrentTimeStamp();
        $user = $config['params']['user'];
        if (!$status) $data = ['reason' => $row['rem1'], 'status' => $lstatus, 'canceldate' => $date, 'cancelby' => $user];
        $tempdata = [];
        foreach ($this->fields as $key2) {
            if (isset($data[$key2])) {
                $tempdata[$key2] = $data[$key2];
                $tempdata[$key2] = $this->othersClass->sanitizekeyfield($key2, $tempdata[$key2]);
            }
        }
        $tempdata['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $tempdata['editby'] = $config['params']['user'];
        $update = $this->coreFunctions->sbcupdate("leavetrans", $tempdata, ['line' => $line, 'trno' => $trno, 'empid' => $row['empid']]);
        if ($update) {
            $this->coreFunctions->execqry("delete from pendingapp where doc='LEAVECANCELLATION' and trno=" . $trno . " and line=" . $line, 'delete');
            $config['params']['doc'] = 'LEAVEAPPLICATIONPORTAL';
            $this->logger->sbcmasterlog($trno, $config, $label . ' (' . $row['clientname'] . ') - ' . $row['dateid']);
            return ['status' => true, 'msg' => 'Successfully' . $label, 'data' => [], 'reloadsbclist' => true, 'action' => 'gapplications', 'deleterow' => true];
        }
        return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => []];
    }
} //end class
