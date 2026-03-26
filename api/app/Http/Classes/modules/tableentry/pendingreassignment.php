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

class pendingreassignment
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'PENDING RE-ASSIGNMENT';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $linkemail;
    public $tablelogs = 'hrisnum_log';
    public $style = 'width:90%;max-width:90%;';
    public $issearchshow = true;
    public $showclosebtn = true;
    public $tablenum = 'hrisnum';
    public $head = 'designation';
    public $hhead = '';
    public $detail = '';
    public $hdetail = '';
    public $tablelogs_del = 'del_hrisnum_log';

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
            'load' => 5144
        );
        return $attrib;
    }
    public function createTab($config)
    {
        $doc = $config['params']['row']['doc'];
        $cols = ['action', 'clientname', 'leadfrom', 'leadto', 'effectivity', 'rolename', 'category', 'company', 'designation', 'department', 'supervisorname', 'section', 'rem'];
        foreach ($cols as $key => $value) {
            $$value = $key;
        }
        $tab = [$this->gridname => ['gridcolumns' => $cols]];
        $stockbuttons = ['approve'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0][$this->gridname]['columns'][$action]['btns']['approve']['label'] = 'UPDATE EMPLOYEE RECORD';

        $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Employee Name';
        $obj[0][$this->gridname]['columns'][$clientname]['style'] = 'width:130px;min-width:130px;';
        $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'label';

        $obj[0][$this->gridname]['columns'][$leadfrom]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$leadfrom]['label'] = 'From Destination';
        $obj[0][$this->gridname]['columns'][$leadfrom]['style'] = 'width:130px;min-width:130px;';

        $obj[0][$this->gridname]['columns'][$leadto]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$leadto]['label'] = 'To Destination';
        $obj[0][$this->gridname]['columns'][$leadto]['style'] = 'width:130px;min-width:130px;';

        $obj[0][$this->gridname]['columns'][$effectivity]['label'] = 'Date Effectivity';
        $obj[0][$this->gridname]['columns'][$effectivity]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$rolename]['style'] = 'width:130px;min-width:130px;';
        $obj[0][$this->gridname]['columns'][$rolename]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$category]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$category]['style'] = 'width:130px;min-width:130px;';

        $obj[0][$this->gridname]['columns'][$company]['type'] = 'label';

        $obj[0][$this->gridname]['columns'][$designation]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$designation]['label'] = 'New Designation';
        $obj[0][$this->gridname]['columns'][$designation]['style'] = 'width:130px;min-width:130px;';

        $obj[0][$this->gridname]['columns'][$department]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$department]['label'] = 'New Department';
        $obj[0][$this->gridname]['columns'][$department]['style'] = 'width:130px;min-width:130px;';



        $obj[0][$this->gridname]['columns'][$supervisorname]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$supervisorname]['label'] = 'Immediate Superior';
        $obj[0][$this->gridname]['columns'][$supervisorname]['style'] = 'width:130px;min-width:130px;';

        $obj[0][$this->gridname]['columns'][$section]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$section]['style'] = 'width:130px;min-width:130px;';

        $obj[0][$this->gridname]['columns'][$rem]['style'] = 'width:130px;min-width:130px;';
        $obj[0][$this->gridname]['columns'][$rem]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$rem]['label'] = 'Notation - Details';

        $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }
    public function createtabbutton($config)
    {
        $obj = [];
        return $obj;
    }
    public function loaddata($config)
    {
        $row = $config['params']['row'];
        $adminid = $config['params']['adminid'];
        $query = "select stock.trno,stock.line,stock.empid,concat(emp.emplast,',',emp.empfirst,' ',emp.empmiddle) as clientname,
                    ifnull(branch.clientname,'') as leadfrom,stock.branchid,
                    stock.tobranchid, tobranch.client as tobranchcode,concat(tobranch.client,'~',tobranch.clientname)  as leadto,
                    date(stock.tdate1) as effectivity,stock.roleid,role.name as rolename,
                    ifnull(cat.category,'') as category,ifnull(cat.line,'') as categoryline,
                    stock.divid,ifnull(division.divname, '') as company, stock.deptid,ifnull(d.clientname,'') as department,
                    superv.clientname as supervisorname,stock.ndesid,j.jobtitle as designation,
                    stock.sectid,ifnull(sect.sectname, '') as section,stock.rem,stock.locname,stock.supid,stock.froleid,loc.line as locid,modapp.sbcpendingapp
                from rasstock as stock
                left join pendingapp as app on app.trno=stock.trno and app.line=stock.line and app.doc = 'RS'
                left join moduleapproval as modapp on modapp.modulename=app.doc
                left join employee as emp on emp.empid=stock.empid
                left join client as branch on branch.clientid = stock.branchid
                left join client as tobranch on tobranch.clientid = stock.tobranchid
                left join rolesetup as role on role.line = stock.roleid
                left join reqcategory as cat on cat.line=stock.category
                left join division on division.divid = stock.divid
                left join client as d on d.clientid = stock.deptid
                left join client as superv on superv.clientid = stock.supid
                left join section as sect on sect.sectid = stock.sectid
                left join jobthead as j on j.line=stock.ndesid
                left join emploc as loc on loc.locname=stock.locname
                
                where app.clientid  = '" . $adminid  . "'";
        return $this->coreFunctions->opentable($query);
    }
    public function updateapp($config, $status)
    {
        $row = $config['params']['row'];
        if ($status == 'A') {

            $data = [
                'empid' => $row['empid'],
                'branchid' => $row['tobranchid'],
                'roleid' => $row['roleid'],
                'jobid' => $row['ndesid'],
                'emploc' => $row['locname'],
                'divid' => $row['divid'],
                'deptid' => $row['deptid'],
                'sectid' => $row['sectid'],
                'supervisorid' => $row['supid']
            ];
            foreach ($data as $key => $v) {
                $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
            }
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['editby'] = $config['params']['user'];


            $pendingdata = $this->coreFunctions->opentable("select * from pendingapp where trno=" . $row['trno'] . " and line = '" . $row['line'] . "' and  doc='RS'");
            $delete =  $this->coreFunctions->execqry("delete from pendingapp where trno=" . $row['trno'] . " and line = '" . $row['line'] . "' and  doc='RS'", 'delete');
            $msg = 'Successfully Updated.';
            $status = true;
            if ($delete) {

                if ($this->coreFunctions->sbcupdate('employee', $data, ['empid' => $row['empid']])) {
                    $this->coreFunctions->execqry("update designation set dateapplied = '" . $this->othersClass->getCurrentTimeStamp() . "' where trno=" . $row['trno'] . " and linex = '" . $row['line'] . "' and empid = " . $row['empid'] . " and dateapplied is null ", 'update');
                }
                $this->logger->sbcwritelog($row['trno'], $config, 'UPDATE', "UPDATE EMPLOYEE FROM RE-ASSIGNMENT: " . $row['clientname']);
                return ['status' => $status, 'msg' => $msg, 'data' => [], 'reloadsbclist' => true, 'action' => 'gapplications', 'deleterow' => true];
            } else {
                if (!empty($pendingdata)) {
                    foreach ($pendingdata as $pd) {
                        $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid, approver) values(?, ?, ?, ?, ?)", 'insert', [$pd->trno, $pd->line, $pd->doc, $pd->clientid, $pd->approver]);
                    }
                }
                $this->logger->sbcwritelog($row['trno'], $config, 'UPDATE', 'FAILED TO UPDATE EMPLOYEE' . $row['clientname']);
                $msg = 'Error in Updating.';
                $status = false;
                return ['status' => $status, 'msg' => $msg, 'data' => []];
            }
        }
    }
} //end class
