<?php

namespace App\Http\Classes\modules\tableentry;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\lookupClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\lookup\hrislookup;

class entrymultiapprover
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'APPROVER SETUP';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'multiapprover';
    public $tablelogs = 'payroll_log';
    private $logger;
    private $othersClass;
    private $lookupClass;
    private $hrislookup;
    public $style = 'width:100%;';
    private $fields = ['line', 'empid', 'approverid', 'doc', 'issupervisor', 'isapprover'];
    private $except = ['approvercode', 'approver'];
    public $showclosebtn = false;
    private $reporter;


    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->reporter = new SBCPDF;
        $this->logger = new Logger;
        $this->lookupClass = new lookupClass;
        $this->hrislookup = new hrislookup;
    }

    public function getAttrib()
    {
        $attrib = array(
            'load' => 0
        );
        return $attrib;
    }

    public function createTab($config)
    {
        $doc = $config['params']['doc'];
        // $action = 0;
        // $rolename = 1;

        $cols = ['action', 'approver', 'doc', 'isapprover', 'issupervisor'];

        foreach ($cols as $key => $value) {
            $$value = $key;
        }
        $tab = [$this->gridname => ['gridcolumns' => $cols]];


        $stockbuttons = ['delete'];


        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        // action
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:60px;whiteSpace: normal;min-width:60px;";

        // $obj[0][$this->gridname]['columns'][$empname]['label'] = "Employee";
        // $obj[0][$this->gridname]['columns'][$empname]['type'] = "lookup";
        // $obj[0][$this->gridname]['columns'][$empname]['action'] = "lookupsetup";
        // $obj[0][$this->gridname]['columns'][$empname]['lookupclass'] = "lookupemployee";

        // $obj[0][$this->gridname]['columns'][$clientname]['style'] = "width:60px;whiteSpace: normal;min-width:60px;";

        $obj[0][$this->gridname]['columns'][$approver]['label'] = "Approver";
        $obj[0][$this->gridname]['columns'][$approver]['readonly'] = true;
        // $obj[0][$this->gridname]['columns'][$approver]['action'] = "lookupsetup";
        // $obj[0][$this->gridname]['columns'][$approver]['lookupclass'] = "lookapprover";

        // $obj[0][$this->gridname]['columns'][$approver]['style'] = "width:60px;whiteSpace: normal;min-width:60px;";


        $obj[0][$this->gridname]['columns'][$isapprover]['readonly'] = false;
        $obj[0][$this->gridname]['columns'][$issupervisor]['readonly'] = false;


        $obj[0][$this->gridname]['columns'][$doc]['label'] = "Module Name";
        $obj[0][$this->gridname]['columns'][$doc]['type'] = "lookup";
        $obj[0][$this->gridname]['columns'][$doc]['action'] = "lookupsetup";
        $obj[0][$this->gridname]['columns'][$doc]['lookupclass'] = "lookupmodulelist";
        $obj[0][$this->gridname]['columns'][$doc]['required'] = true;




        $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        $tableid = $config['params']['tableid'];
        $clientname = $this->coreFunctions->datareader("select clientname as value from client where clientid = '" . $tableid . "'");
        $this->modulename = $this->modulename . ' - ' . $clientname;
        return $obj;
    }

    public function createtabbutton($config)
    {
        $doc = $config['params']['doc'];
        $tableid = $config['params']['tableid'];
        $adminid = $config['params']['adminid'];
        $tbuttons = ['additem', 'saveallentry', 'masterfilelogs'];

        // if ($adminid != $tableid) {
        //     $tbuttons = [];
        // }
        $obj = $this->tabClass->createtabbutton($tbuttons);

        $obj[0]['label'] = "ADD";
        $obj[0]['lookupclass'] = 'lookupemployee';
        $obj[0]['action'] = 'lookupsetup';
        return $obj;
    }


    public function add($config)
    {
        $data = [];
        $data['line'] = 0;
        $data['empid'] = 0;
        $data['approverid'] = 0;
        $data['doc'] = '';
        $data['isapprover'] = 'false';
        $data['issupervisor'] = 'false';
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    }

    private function selectqry()
    {
        $qry = "mul.line,mul.empid, mul.approverid,mul.doc, 
        (case mul.isapprover when 1 then 'true' else 'false' end) as isapprover,
        (case mul.issupervisor when 1 then 'true' else 'false' end) as issupervisor,
        app.clientname as approver ";

        return $qry;
    }

    public function saveallentry($config)
    {
        $data = $config['params']['data'];
        $tableid = $config['params']['tableid'];
        $data2 = [];
        foreach ($data as $key => $value) {
            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    if (!in_array($value2, $this->except)) {
                        $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                    }
                }
                if ($data[$key]['doc'] == "") {
                    return ['status' => false, 'msg' => 'Module name is empty'];
                }
                $isapprover = $this->coreFunctions->datareader("select isapprover as value from employee where empid = '" . $data[$key]['approverid'] . "' and isapprover = 1 ");
                $issupervisor = $this->coreFunctions->datareader("select issupervisor as value from employee where empid = '" . $data[$key]['approverid'] . "' and issupervisor = 1 ");

                if (!($isapprover && $issupervisor)) {
                    if ($isapprover) {
                        if ($data[$key]['issupervisor'] == 'true') {
                            return ['status' => false, 'msg' => "Oops! This employee is not tagged as a supervisor yet. Please update their role first."];
                        }
                    }
                    if ($issupervisor) {
                        if ($data[$key]['isapprover'] == 'true') {
                            return ['status' => false, 'msg' => "Oops! This employee is not tagged as a approver yet. Please update their role first."];
                        }
                    }
                }

                if ($data[$key]['line'] == 0) {
                    $line = $this->coreFunctions->datareader("select line as value from multiapprover where empid=" . $data[$key]['empid'] . " and approverid=" . $data[$key]['approverid'] . " and doc= '" . $data[$key]['doc'] . "'", [], '', true);
                    if ($line != 0) {
                        return ['status' => false, 'msg' => 'Employee already exists with same module approver or module name is empty.'];
                    }

                    $data2['encodedate'] = $this->othersClass->getCurrentTimeStamp();
                    $data2['encodedby'] = $config['params']['user'];
                    // $this->coreFunctions->sbcinsert($this->table, $data2);
                    $line = $this->coreFunctions->insertGetId($this->table, $data2);

                    $config['params']['doc'] = strtoupper('approver_setup');
                    $this->logger->sbcmasterlog(
                        $tableid,
                        $config,
                        'CREATE - LINE: ' . $line
                            . ' - APPROVER: ' . $data[$key]['approver']
                    );
                } else {
                    $checkup = $this->coreFunctions->opentable("select line,doc from multiapprover where empid=" . $data[$key]['empid'] . " and approverid=" . $data[$key]['approverid'] . "", [], '');

                    foreach ($checkup as $key3 => $value3) {
                        if ($value3->line != $data[$key]['line']) {
                            if ($value3->doc == $data[$key]['doc']) {
                                return ['status' => false, 'msg' => 'Employee already exists with same module.'];
                            }
                        }
                    }

                    $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                    $data2['editby'] = $config['params']['user'];
                    $this->coreFunctions->sbcupdate($this->table, $data2, ['empid' => $data[$key]['empid'], 'line' => $data[$key]['line']]);
                }
            } // end if
        } // foreach
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
    } // end function  

    public function save($config)
    {
        $data = [];
        // var_dump($config['params']['row']);
        $row = $config['params']['row'];
        $empid = $row['empid'];

        foreach ($this->fields as $key => $value) {
            $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
        }

        if ($row['line'] == 0) {
            // $line = $this->coreFunctions->datareader("select line as value from multiapprover where empid=" . $data['empid'] . " and approverid=" . $data['approverid'] . " and doc= '" . $data['doc'] . "'", [], '', true);
            // if ($line != 0) {
            //     return ['status' => false, 'msg' => 'Employee already exists with same module approver.'];
            // }

            return ['status' => true, 'msg' => 'Successfully saved.', 'row' =>  $row];
        }
    } //end function

    public function delete($config)
    {
        $row = $config['params']['row'];
        $qry = "delete from " . $this->table . " where doc=? and empid =? and approverid = ?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['doc'], $row['empid'], $row['approverid']]);
        $config['params']['doc'] = strtoupper('employee');
        $this->logger->sbcmasterlog(
            $row['line'],
            $config,
            'DELETE - LINE: ' . $row['line']
                . ' - APPROVER: ' . $row['line']
        );
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }


    private function loaddataperrecord($line, $config)
    {
        $adminid = $config['params']['adminid'];
        $select = $this->selectqry();
        $select = $select . ", '' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . " as mul
        left join client on client.clientid = mul.empid 
        left join client as app on app.clientid = mul.approverid
        where mul.approverid = ? and mul.line = ?";

        return $this->coreFunctions->opentable($qry, [$adminid, $line]);
    }

    public function loaddata($config)
    {
        $tableid = $config['params']['tableid'];
        $select = $this->selectqry();
        $select = $select . ", '' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . " as mul
        left join client on client.clientid = mul.empid 
        left join client as app on app.clientid = mul.approverid
        where mul.empid = ? ";
        return $this->coreFunctions->opentable($qry, [$tableid]);
    }

    public function lookupsetup($config)
    {
        $lookupclass2 = $config['params']['lookupclass2'];
        // var_dump($lookupclass2);
        switch ($lookupclass2) {
            case 'lookupmodulelist':
                return $this->lookupmodulelist($config);
                break;
            case 'lookupemployee':
                return $this->lookupemployee($config);
                break;
            case 'lookuplogs';
                return $this->lookuplogs($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
                break;
        }
    }
    public function lookupcallback($config)
    {
        $row = $config['params']['row'];
        $this->othersClass->logConsole(json_encode($row));
        $data = $this->save($config);
        if ($data['status']) {
            $returndata = $data['row'];
            return ['status' => true, 'msg' => 'Successfully added.', 'data' => $returndata, 'reloadtableentry' => $data['row']];
        } else {
            return ['status' => false, 'msg' => $data['msg']];
        }
    }

    public function lookuplogs($config)
    {
        $lookupsetup = array(
            'type' => 'show',
            'title' => 'List of Logs',
            'style' => 'width:1000px;max-width:1000px;'
        );

        $trno = $config['params']['tableid'];

        $cols = [
            ['name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'],
            ['name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'],
            ['name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;'],
            ['name' => 'editby', 'label' => 'Edited By', 'align' => 'left', 'field' => 'editby', 'sortable' => true, 'style' => 'font-size:16px;'],
            ['name' => 'editdate', 'label' => 'Edited Date', 'align' => 'left', 'field' => 'editdate', 'sortable' => true, 'style' => 'font-size:16px;'],
        ];

        $doc = strtoupper('approver_setup');
        $qry = "
      select trno, doc, task, dateid, user, editby, editdate
      from " . $this->tablelogs . "
      where doc = ? and trno = ?
      order by dateid desc
    ";

        $data = $this->coreFunctions->opentable($qry, [$doc, $trno]);

        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
    }

    public function lookupmodulelist($config)
    {
        $plottype = 'plotgrid';
        $plotting = ['doc' => 'doc', 'modulename' => 'modulename'];
        $index = $config['params']['index'];
        $lookupsetup = array(
            'type' => 'single',
            'title' => 'List of Modules',
            'style' => 'width:900px;max-width:900px;'
        );
        // $lookupsetup = ['type' => 'single', 'title' => $title, 'style' => 'width:500px;max-width:500px;height:600px'];
        $plotsetup = ['plottype' => $plottype, 'action' => '', 'plotting' => $plotting];
        $cols = array(
            array('name' => 'doc', 'label' => 'Module Name', 'align' => 'left', 'field' => 'doc', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'modulename', 'label' => 'Module Label', 'align' => 'left', 'field' => 'modulename', 'sortable' => true, 'style' => 'font-size:16px;')

        );
        switch ($config['params']['companyid']) {
            case 53: //camera
                $qry = "
                       select 'LEAVE' as doc,'LEAVE APPLICATION' as modulename
                       union all
                       select 'OB' as doc,'OB APPLICATION' as modulename
                       union all
                       select 'INITIALOB' as doc,'INITIAL OB APPLICATION' as modulename
                       union all
                       select 'OT' as doc,'OT APPLICATION' as modulename
                       union all
                       select 'LOAN' as doc,'LOAN APPLICATION' as modulename
                       union all
                       select 'CHANGESHIFT' as doc,'CHANGE SHIFT APPLICATION' as modulename
                       union all
                       select 'PORTAL SCHEDULE' as doc,'PORTAL SCHEDULE' as modulename";
                break;
            case 51: //ulitc
                $qry = "
                       select 'LEAVE' as doc,'LEAVE APPLICATION' as modulename
                       union all
                       select 'OB' as doc,'OB APPLICATION' as modulename
                       union all
                       select 'OT' as doc,'OT APPLICATION' as modulename
                       union all
                       select 'LOAN' as doc,'LOAN APPLICATION' as modulename";
                break;
            default:
                $qry = "select 'LEAVE' as doc,'LEAVE APPLICATION' as modulename
                       union all
                       select 'OB' as doc,'OB APPLICATION' as modulename
                       union all
                       select 'OT' as doc,'OT APPLICATION' as modulename
                       union all
                       select 'UNDERTIME' as doc,'UNDERTIME APPLICATION' as modulename
                       union all
                       select 'LOAN' as doc,'LOAN APPLICATION' as modulename
                       union all
                       select 'CHANGESHIFT' as doc,'CHANGE SHIFT APPLICATION' as modulename";
                break;
        }
        $data = $this->coreFunctions->opentable($qry);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
    }
    public function lookupemployee($config)
    {
        $adminid = $config['params']['adminid'];
        $tableid = $config['params']['tableid'];
        // $index = $config['params']['index'];
        $lookupsetup = array(
            'type' => 'single',
            'title' => 'List of Employee',
            'style' => 'width:900px;max-width:900px;'
        );

        // $plotting = ['empname' => 'empname', 'empcode' => 'empcode', 'empid' => 'empid'];
        // $plotsetup = ['plottype' => 'plotgrid', 'action' => '', 'plotting' => $plotting];

        $cols = array(
            array('name' => 'approvercode', 'label' => 'Code', 'align' => 'left', 'field' => 'approvercode', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'approver', 'label' => 'Name', 'align' => 'left', 'field' => 'approver', 'sortable' => true, 'style' => 'font-size:16px;')
        );

        // $plotsetup = [
        //     'action' => 'addtogrid',
        //     'plottype' => 'tableentry'
        // ];

        $plotsetup = array(
            'plottype' => 'callback',
            'action' => 'addtogrid'
        );
        // $emplvl = $this->othersClass->checksecuritylevel($config);

        $qry = "select 0 as line,'" . $tableid . "' as empid,e.empid as approverid,
        if(e.isapprover = 1,'true','false') as isapprover ,if(e.issupervisor = 1,'true','false') as issupervisor,'' as doc,'bg-blue-2' as bgcolor,
        client.client as approvercode,
        client.clientname as approver
        from employee as e 
        left join client on client.clientid=e.empid
        where e.isactive =1 and (e.isapprover = 1 or e.issupervisor = 1)
        order by e.emplast,e.empfirst,e.empmiddle 
        ";

        $data = $this->coreFunctions->opentable($qry);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
    }
}//end class
