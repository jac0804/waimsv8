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
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;



class empprojectlogb
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Daily Deployment Record - B';
    public $gridname = 'entrygrid';
    private $companysetup;
    private $table = 'empprojdetail';
    public $getdata = [];
    private $coreFunctions;
    private $othersClass;
    public $style = 'width:100%;max-width:100%;';
    public $issearchshow = true;
    public $showclosebtn = false;

    public function __construct()
    {
        $this->btnClass = new buttonClass;
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 4603,
            'edititem' => 4604,
            'save' => 4604,
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
        $columns = ['action', 'emplast', 'empfirst', 'empmiddle', 'subamenityroxascode', 'tothrs', 'othrs',  'hours', 'qty'];
        foreach ($columns as $key => $value) {
            $$value = $key;
        }
        $tab = [$this->gridname => [
            'gridcolumns' => $columns
        ]];

        $stockbuttons = ['save', 'entryempprojectlogb'];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['descriptionrow'] = [];
        $obj[0][$this->gridname]['label'] = 'Employee List';

        $obj[0][$this->gridname]['columns'][$emplast]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$empfirst]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$empmiddle]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$subamenityroxascode]['action'] = 'lookupsubamenityroxascode';
        $obj[0][$this->gridname]['columns'][$subamenityroxascode]['field'] = 'subamenityroxascode';
        $obj[0][$this->gridname]['columns'][$tothrs]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$othrs]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$hours]['label'] = 'Entry Hours';
        $obj[0][$this->gridname]['columns'][$qty]['label'] = 'Entry OT';
        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['vieweempnotimeinout', 'viewempnodeployment'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function createHeadField($config)
    {
        $companyid = $config['params']['companyid'];
        $fields = ['dateid', 'compcode'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'dateid.readonly', false);
        data_set($col1, 'compcode.action', 'lookuprjroxas');
        data_set($col1, 'compcode.cleartxt', true);

        $fields = ['projectname', 'subprojectname'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'projectname.label', 'Project Code');
        data_set($col2, 'projectname.addedparams', ['compcode']);

        data_set($col2, 'projectname.cleartxt', true);
        data_set($col2, 'subprojectname.cleartxt', true);

        $fields = ['blocklotroxas', 'department']; //'amenity', 'subamenity', 
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'blocklotroxas.type', 'input');
        data_set($col3, 'blocklotroxas.class', 'sbccsreadonly');
        data_set($col3, 'blocklotroxas.cleartxt', true);
        data_set($col3, 'department.type', 'lookup');
        data_set($col3, 'department.lookupclass', 'lookupdepartmentroxascode');
        data_set($col3, 'department.action', 'lookupdepartmentroxascode');
        data_set($col3, 'department.addedparams', ['compcode']);

        // data_set($col3, 'amenity.cleartxt', true);
        // data_set($col3, 'subamenity.cleartxt', true);
        data_set($col3, 'department.cleartxt', true);

        // data_set($col3, 'subamenity.type', 'input');
        // data_set($col3, 'subamenity.class', 'sbccsreadonly');

        $fields = ['refresh'];
        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'refresh.action', 'load');

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function paramsdata($config)
    {
        $qry = "select date_format(concat(year(curdate()),'-',month(curdate()),'-01'),'%Y-%m-%d') as dateid
    ";
        $data = $this->coreFunctions->opentable($qry);

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
            case "load":
                return $this->loaddetails($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Data is not yet setup in the headtablestatus.'];
                break;
        }
    }
    private function selectqry($config)
    {
        $qry = "select d.dateid, d.empid, d.empfirst,d.line,d.emplast, d.empmiddle, d.dateno,  sum(log.tothrs) as tothrs, sum(log.othrs) as othrs, '' as  hours, '' as remarks, '' as qty, '' as bgcolor";
        return $qry;
    }
    private function loaddetails($config)
    {
        if (isset($config['params']['dataparams']['compcode'])) {
            $compcode = $config['params']['dataparams']['compcode'];
        }
        if (isset($config['params']['dataparams']['dateid'])) {
            $dateid = date('Y-m-d', strtotime($config['params']['dataparams']['dateid']));
        }
        if (isset($config['params']['headdata']['dateid'])) {
            $dateid = date('Y-m-d', strtotime($config['params']['headdata']['dateid']));
        }

        $data = $this->coreFunctions->opentable("
                select d.dateid,d.compcode, d.empid, d.empfirst, d.emplast, d.empmiddle, d.dateno,  FORMAT(sum(log.tothrs),2) as tothrs, sum(log.othrs) as othrs, 
                        0 as hours, 0 as qty, '' as remarks,'' as subamenityroxascode,'' as code, '' as bgcolor
                from (
                    select date('" . $dateid . "') as dateid,'" . $compcode . "' as compcode, emp.empid, emp.empfirst, emp.emplast, emp.empmiddle, DATE_FORMAT('" . $dateid . "' , '%Y%d%m') as dateno
                    from employee as emp
                    where  emp.isactive = 1
                    group by dateid, emp.empid, emp.empfirst, emp.emplast, emp.empmiddle) as d 
                    left join empprojdetail as log on log.empid=d.empid and log.dateid=d.dateid
                    left join subamenityroxas as subam  on subam.compcode=log.compcode and subam.code=log.subamenityroxascode
                    group by d.dateid, d.empid, d.empfirst, d.emplast, d.empmiddle, d.dateno,d.compcode
                    order by d.emplast,d.empfirst,d.empmiddle");

        return ['status' => true, 'msg' => 'Successfully loaded.', 'action' => 'load', 'griddata' => ['entrygrid' => $data]];
    }
    private function loaddataperrecord($config)
    {
        $compcode = $config['params']['row']['compcode'];
        $dateid = date('Y-m-d', strtotime($config['params']['row']['dateid']));
        $empid = $config['params']['row']['empid'];

        $data = $this->coreFunctions->opentable("
                select d.dateid, d.empid,d.compcode, d.empfirst, d.emplast, d.empmiddle, d.dateno, FORMAT(sum(log.tothrs),2) as tothrs,  
                sum(log.othrs) as othrs, 0 as hours,0 as qty, '' as remarks,'' as subamenityroxascode,'' as code
                from (
                    select date('" . $dateid . "') as dateid,'" . $compcode . "' as compcode, emp.empid, emp.empfirst, emp.emplast, emp.empmiddle, DATE_FORMAT('" . $dateid . "' , '%Y%d%m') as dateno
                    from employee as emp
                    where  emp.isactive = 1 and emp.empid=?
                    group by dateid, emp.empid, emp.empfirst, emp.emplast, emp.empmiddle) as d 
                    left join empprojdetail as log on log.empid=d.empid and log.dateid=d.dateid
                    left join subamenityroxas as subam  on subam.compcode=log.compcode and subam.code=log.subamenityroxascode
                    group by d.dateid, d.empid, d.empfirst, d.emplast, d.empmiddle, d.dateno,d.compcode
                    order by d.emplast,d.empfirst,d.empmiddle",  [$empid]);

        return $data;
    }

    public function stockstatus($config)
    {
        switch ($config['params']['action']) {

            case 'saveperitem':
                return $this->updateperitem($config);
                break;

            default:
                return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
                break;
        }
    }
    public function updateperitem($config)
    {
        return $this->additem('insert', $config);
    }
    public function additem($action, $config)
    {
        $empid =  $config['params']['row']['empid'];
        $dateid =  $config['params']['headdata']['dateid'];
        $hours =  $config['params']['row']['hours'];
        $othours =  $config['params']['row']['qty'];
        $dateno =  $config['params']['row']['dateno'];
        $subamenitycode = $config['params']['row']['code'];

        $compcode =  isset($config['params']['headdata']['compcode'])  ? $config['params']['headdata']['compcode'] : '';
        $projectcode =  isset($config['params']['headdata']['projectcode']) ? $config['params']['headdata']['projectcode'] : '';
        $subprojectcode =  isset($config['params']['headdata']['subprojcode']) ? $config['params']['headdata']['subprojcode'] : '';
        $blocklotcode =  isset($config['params']['headdata']['blocklotcode']) ? $config['params']['headdata']['blocklotcode'] : '';
        $departmentcode =  isset($config['params']['headdata']['deptcode']) ? $config['params']['headdata']['deptcode'] : '';

        if ($subamenitycode == "") {
            $subamenitycode = $this->coreFunctions->getfieldvalue('subamenityroxas', 'code', 'compcode=?', [$compcode]);
            $amenitycode = $this->coreFunctions->getfieldvalue('subamenityroxas', 'parent', 'compcode=?', [$compcode]);
        } else {
            $amenitycode = $config['params']['row']['amenityroxascode'];
        }


        if ($compcode == '') return ['status' => false, 'msg' => 'Please select valid company'];
        if ($projectcode == '') return ['status' => false, 'msg' => 'Please select valid project'];

        $data = [
            'dateid' => $dateid,
            'dateno' => $dateno,
            'empid' => $empid,
            'tothrs' => $hours,
            'othrs' => $othours,
            'compcode' => $compcode,
            'pjroxascode1' => $projectcode,
            'subpjroxascode' => $subprojectcode,
            'blotroxascode' =>  $blocklotcode,
            'departmentroxascode' =>  $departmentcode,
            'subamenityroxascode' => $subamenitycode,
            'amenityroxascode' => $amenitycode
        ];
        foreach ($data as $key => $value) {
            $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        }
        if ($action == 'insert') {
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];
            if ($this->coreFunctions->sbcinsert($this->table, $data) == 1) {
                $row = $this->loaddataperrecord($config);
                return ['row' => $row, 'data' => $data, 'status' => true, 'msg' => 'Successfully added.'];
            } else {
                return ['status' => false, 'msg' => 'Add failed  - Invalid input (' . $hours . ' )'];
            }
        }
    }
} //end class
