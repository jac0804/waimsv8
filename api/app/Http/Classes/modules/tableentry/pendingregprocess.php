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

class pendingregprocess
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'PENDING REGULARIZATION PROCESS';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $linkemail;
    public $tablelogs = 'payroll_log';
    public $style = 'width:90%;max-width:90%;';
    public $issearchshow = true;
    public $showclosebtn = true;
    public $fields = ['empname', 'hired', 'expiration'];


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
            'load' => 3627
        );
        return $attrib;
    }

    public function createTab($config)
    {
        $this->modulename = 'PENDING REGULARIZATION PROCESS - ' . $config['params']['row']['modulename'];
        $cols = ['action', 'empname', 'hired', 'expiry2'];
        foreach ($cols as $key => $value) {
            $$value = $key;
        }
        $tab = [$this->gridname => ['gridcolumns' => $cols]];
        $stockbuttons = ['approve', 'jumpmodule'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][2]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][3]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][0]['style'] = 'width:100px;max-width:100px;min-width:100px;';
        $obj[0][$this->gridname]['columns'][1]['style'] = 'width:350px;max-width:350px;min-width:350px;';
        $obj[0][$this->gridname]['columns'][2]['style'] = 'width:200px;max-width:200px;min-width:200px;';
        $obj[0][$this->gridname]['columns'][2]['align'] = 'left';
        $obj[0][$this->gridname]['columns'][3]['style'] = 'width:200px;max-width:200px;min-width:200px;';
        $obj[0][$this->gridname]['columns'][0]['btns']['approve']['label'] = 'Evaluate';
        $obj[0][$this->gridname]['columns'][0]['btns']['approve']['lookupclass'] = 'pendingregprocess';
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
        $row = $config['params']['row'];
        $adminid = $config['params']['adminid'];
        $regname = $row['modulename'];
        $qry = "select client.clientname as empname, emp.empid, date(emp.hired) as hired,reg.regid, reg.line, date(reg.expiration) as expiry2, 'CONTRACTMONITORING' as doc, 'amodule/ahris/' as url, 0 as trno, 'pendingregprocess' as sbcpendingapp
                    from pendingapp as p
                    left join regprocess as reg on reg.line=p.line
                    left join client on client.clientid=reg.empid
                    left join employee as emp on emp.empid=client.clientid
                    left join regularization as regu on regu.line=reg.regid
                    where p.clientid=$adminid and p.doc='CONTRACTMONITORING' and regu.description='" . $regname . "'";
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
        $userid = $config['params']['adminid'];
        $date = $this->othersClass->getCurrentTimeStamp();
        $result = $this->coreFunctions->sbcupdate("cmevaluate", ['dateevaluated' => $date], ['trno' => $row['line'], 'empid' => $userid]);
        if ($result) {
            $this->coreFunctions->execqry("delete from pendingapp where line=" . $row['line'] . " and clientid=" . $userid . " and doc='CONTRACTMONITORING'", 'delete');
            return ['status' => true, 'msg' => 'Successfully Evaluated', 'data' => [], 'reloadsbclist' => true, 'action' => 'gapplications', 'deleterow' => true];
        } else {
            return ['status' => false, 'msg' => 'Failed to evaluate.'];
        }
    }
} //end class
