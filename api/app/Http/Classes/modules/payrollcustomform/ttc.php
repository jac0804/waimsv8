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

use DateTime;

class ttc
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'PORTAL SCHEDULE';
    public $gridname = 'entrygrid';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    public $style = 'width:100%;max-width:100%;';
    public $issearchshow = false;
    public $showclosebtn = false;
    public $fields = ['daytype', 'shiftcode', 'schedin', 'schedbrkout', 'schedbrkin', 'schedout', 'reghrs', 'shiftid', 'isok'];

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
            'view' => 5162,
            'edit' => 5164,
            'save' => 5163,
            'saveallentry' => 5163,
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
        $column = [
            'dateid',
            'daytype',
            'shiftcode',
            'changetime',
            'schedin',
            'schedbrkout',
            'schedbrkin',
            'schedout',
            'actualin',
            'actualbrkout',
            'actualbrkin',
            'actualout',
            'reghrs',
            'absdays',
            'latehrs',
            'underhrs',
            'othrs',
            'ndiffhrs',
            'ndiffot'
        ];


        foreach ($column as $key => $value) {
            $$value = $key;
        }
        $tab = [$this->gridname => [
            'gridcolumns' => $column
        ]];

        $stockbuttons = [];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['descriptionrow'] = [];

        $obj[0][$this->gridname]['columns'][$dateid]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$daytype]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$shiftcode]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$schedin]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";
        $obj[0][$this->gridname]['columns'][$schedbrkout]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";
        $obj[0][$this->gridname]['columns'][$schedbrkin]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";
        $obj[0][$this->gridname]['columns'][$schedout]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";
        $obj[0][$this->gridname]['columns'][$actualin]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";
        $obj[0][$this->gridname]['columns'][$actualbrkout]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";
        $obj[0][$this->gridname]['columns'][$actualbrkin]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";
        $obj[0][$this->gridname]['columns'][$actualout]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";
        $obj[0][$this->gridname]['columns'][$reghrs]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
        $obj[0][$this->gridname]['columns'][$absdays]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
        $obj[0][$this->gridname]['columns'][$latehrs]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
        $obj[0][$this->gridname]['columns'][$underhrs]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
        $obj[0][$this->gridname]['columns'][$othrs]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
        $obj[0][$this->gridname]['columns'][$ndiffhrs]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
        $obj[0][$this->gridname]['columns'][$ndiffot]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";

        $obj[0][$this->gridname]['columns'][$dateid]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$schedin]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$schedbrkout]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$schedbrkin]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$schedout]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$actualin]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$actualbrkout]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$actualbrkin]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$actualout]['type'] = 'label';

        $obj[0][$this->gridname]['columns'][$dateid]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$absdays]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$latehrs]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$underhrs]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$othrs]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$ndiffhrs]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$ndiffot]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$shiftcode]['readonly'] = false;

        $obj[0][$this->gridname]['columns'][$reghrs]['type'] = 'input';
        $obj[0][$this->gridname]['columns'][$absdays]['type'] = 'input';
        $obj[0][$this->gridname]['columns'][$latehrs]['type'] = 'input';
        $obj[0][$this->gridname]['columns'][$underhrs]['type'] = 'input';
        $obj[0][$this->gridname]['columns'][$othrs]['type'] = 'input';
        $obj[0][$this->gridname]['columns'][$ndiffhrs]['type'] = 'input';
        $obj[0][$this->gridname]['columns'][$ndiffot]['type'] = 'input';
        $obj[0][$this->gridname]['columns'][$shiftcode]['type'] = 'lookup';

        switch ($config['params']['companyid']) {
            case 53: //camera
                $obj[0][$this->gridname]['columns'][$shiftcode]['type'] = 'coldel';
                $obj[0][$this->gridname]['columns'][$schedbrkout]['type'] = 'coldel';
                $obj[0][$this->gridname]['columns'][$schedbrkin]['type'] = 'coldel';
                $obj[0][$this->gridname]['columns'][$actualin]['type'] = 'coldel';
                $obj[0][$this->gridname]['columns'][$actualout]['type'] = 'coldel';
                $obj[0][$this->gridname]['columns'][$actualbrkout]['type'] = 'coldel';
                $obj[0][$this->gridname]['columns'][$actualbrkin]['type'] = 'coldel';


                $obj[0][$this->gridname]['columns'][$absdays]['type'] = 'coldel';
                $obj[0][$this->gridname]['columns'][$latehrs]['type'] = 'coldel';
                $obj[0][$this->gridname]['columns'][$underhrs]['type'] = 'coldel';
                $obj[0][$this->gridname]['columns'][$othrs]['type'] = 'coldel';
                $obj[0][$this->gridname]['columns'][$ndiffhrs]['type'] = 'coldel';
                $obj[0][$this->gridname]['columns'][$ndiffot]['type'] = 'coldel';


                $obj[0][$this->gridname]['columns'][$daytype]['action'] = 'lookupdaytype';
                $obj[0][$this->gridname]['columns'][$changetime]['action'] = 'lookupchangetimettc';
                break;
            case 58: //cdo
                $obj[0][$this->gridname]['columns'][$daytype]['type'] = 'label';
                $obj[0][$this->gridname]['columns'][$changetime]['type'] = 'coldel';
                break;
            default:
                $obj[0][$this->gridname]['columns'][$daytype]['action'] = 'lookupdaytype';
                $obj[0][$this->gridname]['columns'][$changetime]['type'] = 'coldel';
                break;
        }

        $obj[0][$this->gridname]['columns'][$shiftcode]['action'] = 'lookupshift';
        $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['saveallentry'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        //$obj[0]
        return $obj;
    }

    public function createHeadField($config)
    {
        $companyid = $config['params']['companyid'];

        $fields = ['empid', 'empcode', 'empname', ['start', 'end'], 'refresh'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, "empcode.style", "padding:0px");
        data_set($col1, "start.style", "padding:0px");
        data_set($col1, "end.style", "padding:0px");
        data_set($col1, "empname.style", "padding:0px");
        data_set($col1, 'company.type', 'input');
        data_set($col1, 'company.readonly', true);
        data_set($col1, 'empcode.lookupclass', 'employee');
        data_set($col1, 'empcode.action', 'lookupemployee');

        data_set($col1, "refresh.style", "margin-top:5px");
        data_set($col1, 'refresh.action', 'load');

        $fields = ['company', 'department', 'sectionname'];
        $col2 = $this->fieldClass->create($fields);

        data_set($col2, 'company.type', 'input');
        data_set($col2, 'company.readonly', true);
        data_set($col2, 'department.type', 'input');
        data_set($col2, 'department.label', 'Agency/Department');
        data_set($col2, 'sectionname.type', 'input');
        data_set($col2, 'sectionname.label', 'Section');
        data_set($col2, "company.style", "padding:0px");
        data_set($col2, "department.style", "padding:0px");
        data_set($col2, "sectionname.style", "padding:0px");
        $fields = [];
        $col3 = $this->fieldClass->create($fields);
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
      '' as empcode,'' as empname,0 as empid,'' as company,'' as sectionname,'' as department
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
            case "load":
                $msg = '';
                return $this->loaddetails($config, $msg);
                break;

            case 'saveallentry':
            case "update":
                $msgstat = $this->savechanges($config);
                return $this->loaddetails($config, $msgstat);
                break;
            default:
                return ['status' => false, 'msg' => 'Data is not yet setup in the headtablestatus.'];
                break;
        }
    }

    private function loaddetails($config, $msgstat)
    {
        $adminid = $config['params']['adminid'];
        $start = $config['params']['dataparams']['start'];
        $end = $config['params']['dataparams']['end'];
        $empid = $config['params']['dataparams']['empid'];
        $data = $this->getempschedule($adminid, $start, $end, $empid, $config);

        if ($empid == 0) {
            return ['status' => false, 'msg' => 'No employee Found.', 'action' => 'load', 'griddata' => ['entrygrid' => $data]];
        }
        if (isset($msgstat) && !empty($msgstat)) {
            $status = $msgstat['status'];
            if (!$msgstat['status']) {
                $status = false;
            }
            return ['status' => $status, 'msg' => $msgstat['msg'], 'action' => 'load', 'griddata' => ['entrygrid' => $data]];
        }
        return ['status' => true, 'msg' => 'Successfully loaded.', 'action' => 'load', 'griddata' => ['entrygrid' => $data]];
    }


    private function getempschedule($adminid, $start, $end, $empid, $config)
    {

        $start = date('Y-m-d', strtotime($start));
        $end = date('Y-m-d', strtotime($end));

        $approver = $this->othersClass->checkapproversetup($config, $adminid, 'PORTAL SCHEDULE', 'emp');

        $filter = "";
        $left = "";
        // if ($approver['filter'] != "") {
        //     $filter .= $approver['filter'];
        // }
        // if ($approver['leftjoin'] != "") {
        //     $left .= $approver['leftjoin'];
        // }

        $qry = "select t.empid, t.`daytype`,tm.shftcode as shiftcode,date_format(t.dateid,'%m-%d-%Y') as dateid,dayofweek(dateid) as dayn,
        date_format(t.schedin,'%m-%d-%Y %H:%i') as schedin, date_format(t.schedout,'%m-%d-%Y %H:%i') as schedout,
        date_format(t.schedbrkin,'%Y-%m-%d %H:%i') as schedbrkin, date_format(t.schedbrkout,'%Y-%m-%d %H:%i') as schedbrkout, 
        date_format(t.actualin,'%Y-%m-%d %H:%i') as actualin, date_format(t.actualout,'%Y-%m-%d %H:%i') as actualout, 
        date_format(t.actualbrkin,'%Y-%m-%d %H:%i') as actualbrkin, date_format(t.actualbrkout,'%Y-%m-%d %H:%i') as actualbrkout, 
        t.reghrs, t.absdays, t.latehrs, t.underhrs, t.othrs, t.ndiffhrs, t.ndiffot,t.shiftid,
        (case when t.`daytype`='RESTDAY' then 'bg-yellow-7' else '' end) as bgcolor
        from temptimecard as t
        left join employee as emp on emp.empid = t.empid
        left join tmshifts as tm on tm.line = t.shiftid
        $left
        where date(t.dateid)>=? and date(t.dateid)<=? and emp.empid = ? $filter
        order by t.dateid
        ";
        return $this->coreFunctions->opentable($qry, [$start, $end, $empid]);
    }


    private function savechanges($config)
    {
        $dateid = $this->othersClass->getCurrentDate();
        $rows = $config['params']['rows'];
        $data = [];
        $msg = '';
        $msg2 = '';
        $msg3 = '';
        foreach ($rows as $key => $val) {
            $update = true;

            if ($val["bgcolor"] != "") {
                if ($val["bgcolor"] == 'bg-blue-2') {
                    unset($val["bgcolor"]);
                    $val["isok"] = 0;
                    $valmonth = (int) date('m', strtotime($val["dateid"]));
                    $curmonth = (int) date('m', strtotime($dateid));

                    $date = DateTime::createFromFormat('m-d-Y', $val["dateid"]);
                    $val["dateid"] = $date->format('Y-m-d');
                    if ($valmonth <= $curmonth) { // month
                        if ($val["dateid"] <= $dateid) {
                            $msg .= " " . $val["dateid"] . ', ';
                            $update = false;
                        }
                    } else {

                        if ($config['params']['companyid'] != 58) { //not cdo
                            $firsttdate = new DateTime(date_format(date_create($val["dateid"]), "Y-m-01"));

                            $alloweditdate = $firsttdate->modify('-10 days');
                            if ($dateid < $alloweditdate) {
                                $update = false;
                                if (($valmonth - $curmonth) > 1) {
                                    $msg2 .= " " . date('F', strtotime($val["dateid"])) . ', ';
                                } else {
                                    $msg3 .= " " . $firsttdate . ', ';
                                }
                            }
                        }
                    }
                    foreach ($this->fields as $k) {
                        if (isset($val[$k])) {
                            $data[$k] = $this->othersClass->sanitizekeyfield($k, $val[$k]);
                            if ($k == 'dateid') {
                                $data[$k] = date_format(date_create($val[$k]), "Y-m-d");
                            }
                        }
                    }
                    if ($update) {
                        $result = $this->coreFunctions->sbcupdate("temptimecard", $data, ['empid' => $val["empid"], 'dateid' => $val["dateid"]]);
                        $data = [];
                    }
                }
            }
        }
        $this->othersClass->logConsole('message: ' . $msg);

        $msg4 = '';
        if ($msg != '') {
            $msg4 =  "Can't edit less than currendate" . $msg;
        }
        $msg5 = '';
        if ($msg2 != '') {
            $msg5 =  "Can't edit schedule of month" . $msg2;
        }
        $msg6 = '';
        if ($msg3 != '') {
            $msg6 =  "Can't edit less than 10 days before cutoff" . $msg3;
        }
        return ['status' => $update, 'msg' => $msg4 . $msg5 . $msg6];
    }
    public function stockstatus($config)
    {
        switch ($config['params']['action']) {

            default:
                return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
                break;
        }
    }
} //end class
