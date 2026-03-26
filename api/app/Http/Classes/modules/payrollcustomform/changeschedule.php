<?php

namespace App\Http\Classes\modules\payrollcustomform;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\module\payrollcustomform;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use Exception;



class changeschedule
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Change Schedule';
    public $gridname = 'entrygrid';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $payrollprocess;
    public $style = 'width:100%;max-width:100%;';
    public $issearchshow = false;
    public $showclosebtn = false;
    public $fields = ['empid', 'dateid', 'daytype', 'schedin', 'schedout', 'schedbrkin', 'schedbrkout', 'actualin', 'actualout', 'actualbrkin', 'actualbrkout', 'brk1stin', 'brk1stout', 'brk2ndin', 'brk2ndout', 'abrk1stin', 'abrk1stout', 'abrk2ndin', 'abrk2ndout', 'reghrs', 'absdays', 'latehrs', 'underhrs', 'earlyothrs', 'othrs', 'ndiffhrs', 'ndiffot', 'ismactualin', 'ismactualout', 'isobactualin', 'isobactualout', 'ischangesched', 'ismbrkin', 'ismbrkout', 'ismlunchin', 'ismlunchout',     'logactualin',     'logactualout',     'loglunchin',     'loglunchout'];

    public function __construct()
    {
        $this->btnClass = new buttonClass;
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->payrollprocess = new payrollprocess;
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 5512,
            'save' => 5514,
            'saveallentry' => 5514,
            'edit' => 5515,
        );
        return $attrib;
    }


    public function createHeadbutton($config)
    {
        $btns = []; //actionload - sample of adding button in header - align with form/module name
        $buttons = $this->btnClass->create($btns);
        return $buttons;
    }

    public function createTab($config)
    {
        $companyid = $config['params']['companyid'];

        $columns = [];
        $tab = [];

        $stockbuttons = [];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        // $obj[0][$this->gridname]['obj'] = 'editgrid';
        // $obj[0][$this->gridname]['descriptionrow'] = [];

        // $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }

    public function createtabbutton($config)
    {
        $companyid = $config['params']['companyid'];
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        //$obj[0]
        return $obj;
    }

    public function createHeadField($config)
    {
        $companyid = $config['params']['companyid'];
        $fields = ['company', 'department'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'company.type', 'lookup');
        data_set($col1, 'company.lookupclass', 'lookupcompany');
        data_set($col1, 'company.action', 'lookupcompany');
        data_set($col1, 'company.readonly', true);
        data_set($col1, 'company.required', true);

        data_set($col1, 'department.type', 'lookup');
        data_set($col1, 'department.lookupclass', 'lookupdepartments');
        data_set($col1, 'department.action', 'lookupdepartments');
        data_set($col1, 'department.required', true);


        $fields = [['start', 'end'], ['shiftcode', 'shiftcode2']];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'start.style', 'padding:0px;');
        data_set($col2, 'end.style', 'padding:0px;');


        data_set($col2, 'shiftcode.label', 'Shift From:');
        data_set($col2, 'shiftcode.required', true);
        data_set($col2, 'shiftcode2.label', 'Shift To:');
        data_set($col2, 'shiftcode2.lookupclass', 'lookupshiftcode2');
        data_set($col2, 'shiftcode2.required', true);

        $fields = ['refresh', 'bltimecard'];

        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'refresh.action', 'create');
        data_set($col3, 'refresh.label', 'Create Schedule');

        data_set($col3, 'bltimecard.style', 'width:100%;height:100%;');
        data_set($col3, 'bltimecard.url', '/headtable/payrollcustomform/emptimecard');


        $fields = [];
        $col4 = $this->fieldClass->create($fields);


        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function paramsdata($config)
    {

        $data = $this->coreFunctions->opentable("
      select 
      date_format(concat(year(curdate()),'-',month(curdate()),'-01'),'%Y-%m-%d') as start,
      date_format(adddate(date(concat(year(curdate()),'-',month(curdate()),'-01')), 14),'%Y-%m-%d') as end,
      '' as empcode,
      '' as empname,
      0 as empid,
      0 as divid,
      0 as deptid,
      '' as shiftcode,
      0 as shiftid,
      '' as shiftcode2,
      0 as shiftid2,
      '0' as checkall
    ");
        if (!empty($data)) {
            return $data[0];
        } else {
            return [];
        }
    }

    public function data($config)
    {
        return $this->paramsdata($config);
    }

    public function headtablestatus($config)
    {
        // should return action
        $action = $config['params']["action2"];

        switch ($action) {
            case "create":
                return $this->create($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Data is not yet setup in the headtablestatus.'];
                break;
        }
    }

    private function create($config)
    {
        $empid = $config['params']['dataparams']['empid'];
        $start = $config['params']['dataparams']['start'];
        $end = $config['params']['dataparams']['end'];

        $shiftid = $config['params']['dataparams']['shiftid'];
        $shiftid2 = $config['params']['dataparams']['shiftid2'];
        $divid = $config['params']['dataparams']['divid'];
        $deptid = $config['params']['dataparams']['deptid'];


        if ($divid == 0) {
            return ['status' => false, 'msg' => 'Please select Company.'];
        }
        if ($deptid == 0) {
            return ['status' => false, 'msg' => 'Please select Department.'];
        }
        if ($shiftid == 0) {
            return ['status' => false, 'msg' => 'Please select Shift From.'];
        }
        if ($shiftid2 == 0) {
            return ['status' => false, 'msg' => 'Please select Shift To.'];
        }

        $created_schedule = $this->getallemployees($config, $start, $end, $shiftid, $shiftid2, $divid, $deptid);
        if (!$created_schedule['status']) {
            return ['status' => false, 'msg' => $created_schedule['msg']];
        }

        return ['status' => true, 'msg' => $created_schedule['msg'], 'action' => 'load'];
    }

    public function getallemployees($config, $start, $end, $shiftid, $shiftid2, $divid, $deptid)
    {
        $query = "select emp.empid,client.clientname from employee as emp
            left join client on client.clientid = emp.empid
            where emp.shiftid = $shiftid and emp.divid = $divid and emp.deptid = $deptid";

        $data_currentshift = $this->coreFunctions->opentable($query);
        if (!empty($data_currentshift)) {
            foreach ($data_currentshift as $key => $value) {

                try {
                    $update_sched = $this->payrollprocess->createSchedule($value->empid, $start, $end, $value->clientname, $config, $shiftid2);
                    if (!$update_sched) {
                        return ['status' => false, 'msg' => $update_sched['msg']]; //timecard
                    }
                } catch (Exception $e) {
                    return ['status' => false, 'msg' => 'Failed to create schedule ' . $e->getMessage()];
                }
            }
        } else {
            return ['status' => false, 'msg' => 'No employees found'];
        }

        return ['status' => true, 'msg' => 'Successfully created schedule'];
    }
} //end class
