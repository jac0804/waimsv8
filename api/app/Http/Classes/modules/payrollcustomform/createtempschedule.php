<?php

namespace App\Http\Classes\modules\payrollcustomform;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;
use Exception;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\common\payrollcommon;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;

use Carbon\Carbon;

class createtempschedule
{
    private $fieldClass;
    private $tabClass;
    public $modulename = "CREATE SCHEDULE";
    public $gridname = 'entrygrid';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $payrollcommon;
    private $btnClass;
    public $style = 'width:100%;max-width:100%;';
    public $issearchshow = false;
    public $showclosebtn = false;
    public function __construct()
    {
        $this->btnClass = new buttonClass;
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->payrollcommon = new payrollcommon;
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 5082,
            'save' => 5083,
            'saveallentry' => 5083,
            'edit' => 5084

        );
        return $attrib;
    }

    public function createHeadbutton($config)
    {
        $btns = [];
        $buttons = $this->btnClass->create($btns);
        return $buttons;
    }

    public function createTab($config)
    {

        $column = [
            'ispicked',
            'client',
            'clientname',
            'shiftcode',

        ];

        foreach ($column as $key => $value) {
            $$value = $key;
        }
        $tab = [$this->gridname => [
            'gridcolumns' => $column
        ]];

        $stockbuttons = [];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        // $obj[0][$this->gridname]['obj'] = 'editgrid';
        $obj[0][$this->gridname]['descriptionrow'] = [];


        $obj[0][$this->gridname]['columns'][$client]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$shiftcode]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$client]['label'] = "Employee Code";
        $obj[0][$this->gridname]['columns'][$clientname]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$clientname]['label'] = "Employee";

        $obj[0][$this->gridname]['columns'][$client]['style'] = "width:10px;min-width:10px;";
        $obj[0][$this->gridname]['columns'][$clientname]['style'] = "width:10px;min-width:10px;";
        $obj[0][$this->gridname]['columns'][$ispicked]['style'] = "width:10px;min-width:10px;";
        $obj[0][$this->gridname]['columns'][$shiftcode]['style'] = "width:100px;min-width:100px;";
        $obj[0][$this->gridname]['label'] = "Employee List";
        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['saveallentry']; // 'createschedule'
        $obj = $this->tabClass->createtabbutton($tbuttons);
        $obj[0]['label'] = "CREATE SCHEDULE";

        $obj[0]['icon'] = 'calendar_month';
        $obj[0]['confirmlabel'] = "Are you sure, you want to create schedule?";
        return $obj;
    }

    public function createHeadField($config)
    {
        $companyid = $config['params']['companyid'];
        $fields = [['start', 'end']];
        if ($companyid == 53) { //camera
            array_push($fields, ['create', 'unmarkall']);
        }
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.name', 'startdate');
        data_set($col1, 'end.name', 'enddate');

        if ($companyid == 53) { //camera
            data_set($col1, 'create.style', 'width:100%;high:100%;');
            data_set($col1, 'create.label', 'Mark All');
            data_set($col1, 'create.action', 'mark');

            data_set($col1, 'unmarkall.style', 'width:100%;high:100%;');
            data_set($col1, 'unmarkall.label', 'Unmark All');
        }
        $fields = ['refresh'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'refresh.action', 'load');
        data_set($col2, 'refresh.style', 'width:50%;high:50%;');


        $fields = [];
        $col3 = $this->fieldClass->create($fields);

        $fields = [];
        $col4 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function stockstatusposted($config)
    {
        $companyid = $config['params']['companyid'];
        $start = $config['params']['dataparams']['startdate'];
        $end = $config['params']['dataparams']['enddate'];

        // $start = $this->othersClass->sbcdateformat($start);
        // $end = $this->othersClass->sbcdateformat($end);

        $start =  date('Y-m-d', strtotime($start));
        $end =  date('Y-m-d', strtotime($end));
    }


    public function paramsdata($config)
    {
        $data = $this->coreFunctions->opentable("
      select 
      date(now()) as startdate,
      date(now()) as enddate,
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
            case 'saveallentry':
                return $this->create($config);
                break;
            case "load":
                return $this->loaddetails($config);
                break;
            case "mark":
                return $this->loaddetails($config, 1);
                break;
            case 'unmarkall':
                return $this->loaddetails($config, 0);
                break;
            default:
                return ['status' => false, 'msg' => $action . ' not yet setup in the headtablestatus.'];
                break;
        }
    }

    public function loaddetails($config, $mark = 0)
    {

        $adminid  = $config['params']['adminid'];
        $companyid  = $config['params']['companyid'];
        $checkall = $config['params']['dataparams']['checkall'];
        $approver = $this->othersClass->checkapproversetup($config, $adminid, 'PORTAL SCHEDULE', 'emp');
        $emplvl = $this->othersClass->checksecuritylevel($config);
        $ispickup = " 'true' as ispicked,";
        if ($companyid == 53) { //camera
            if (!$mark) {
                $ispickup = " 'false' as ispicked,";
            }
        }

        $filter = "";
        $left = "";
        if ($approver['ishowall']) {
            if ($approver['exist']) {
                $left = "";
                $filter = "";
            }
        } else {
            if ($approver['filter'] != "") {
                $filter .= $approver['filter'];
            }
            if ($approver['leftjoin'] != "") {
                $left .= $approver['leftjoin'];
            }
        }


        $qry = "select client.client, client.clientname,'bg-white-1' as bgcolor,ts.shftcode as shiftcode, $ispickup
        emp.paygroup,emp.emploc,emp.empid from employee as emp
        left join client on client.clientid = emp.empid
        left join tmshifts as ts on ts.line = emp.shiftid 
        $left
        where emp.isactive = 1 and emp.level in $emplvl $filter ";
        $data = $this->coreFunctions->opentable($qry);
        return ['status' => true, 'msg' => 'Successfully loaded.', 'action' => 'load', 'griddata' => ['entrygrid' => $data]];
    }

    public function create($config)
    {
        $user  = $config['params']['user'];
        $start = $config['params']['dataparams']['startdate'];
        $end = $config['params']['dataparams']['enddate'];
        $rows = $config['params']['rows'];
        $adminid  = $config['params']['adminid'];
        $adminame = $this->coreFunctions->getfieldvalue("client", "clientname", "clientid = ?", [$adminid]);
        if ($rows != "") {
            $result = [];
            foreach ($rows as $val) {
                if ($val['ispicked'] == 'true') {
                    $result = $this->createSchedule($val['empid'], $start, $end, $val['clientname'], $val['emploc'], $config);
                    if (!$result['status']) {
                        return ['status' => false, 'msg' => 'Failed to create schedule. ' . $result['msg']];
                    }
                }
            }
            return $result;
        }
        return ['status' => false, 'msg' => 'No employee is under supervisor of ' . $adminame, 'action' => 'load'];
    }

    private function createSchedule($empid, $start, $end, $empname, $emploc, $config)
    {
        $curdate = $this->othersClass->getCurrentDate();
        $start = date('Y-m-d', strtotime($start));
        $end = date('Y-m-d', strtotime($end));

        $qry = "delete from temptimecard where empid=? and dateid between '" . $start . "' and '" . $end . "'";
        $this->coreFunctions->execqry($qry, 'delete', [$empid]);

        $qry = "select s.line, time(s.tschedin) as timein, time(s.tschedout) as timeout
        from employee as e left join tmshifts as s on s.line=e.shiftid
        where e.empid=? and s.line is not null ";

        $shift = $this->coreFunctions->opentable($qry, [$empid]);

        if ($start <= $curdate) {
            return ['status' => false, 'msg' => 'Start date must be greater than currentdate ', 'action' => 'load'];
        }
        if ($end <= $curdate) {
            return ['status' => false, 'msg' => 'End date must be greater than currentdate ', 'action' => 'load'];
        }

        if (!empty($shift)) {
            $days = $this->getDays($start, $end);

            $timesched = $this->getTimeSched($shift[0]->line);
            $databreak = $this->getBreakSched($shift[0]->line);

            foreach ($days as $key => $val) {
                $data = [];
                $data['empid'] = $empid;
                $data['dateid'] = $val->dateid;
                $data['shiftid'] = $shift[0]->line;

                $timein = $this->getBreakSchedTime($timesched, $val->dayname);
                // $data['schedin'] = $val->dateid . " " . $shift[0]->timein;
                // $data['schedout'] = $val->dateid . " " .  $shift[0]->timeout;

                if ($timein->schedin != null) {
                    $data['schedin'] = $val->dateid . " " . $timein->schedin;
                } else {
                    $data['schedin'] = $val->dateid . " " . $shift[0]->timein;
                }

                if ($timein->schedout != null) {
                    $data['schedout'] = $val->dateid . " " . $timein->schedout;
                } else {
                    $data['schedout'] = $val->dateid . " " . $shift[0]->timeout;
                }

                $break = $this->getBreakSchedTime($databreak, $val->dayname);
                $data['schedbrkin'] =  $break->breakin == null ? null : $val->dateid . " " . $break->breakin;
                $data['schedbrkout'] = $break->breakout == null ? null : $val->dateid . " " . $break->breakout;

                $data['brk1stout'] = $break->brk1stout == null ? null : $val->dateid . " " . $break->brk1stout;
                $data['brk1stin'] = $break->brk1stin == null ? null : $val->dateid . " " . $break->brk1stin;



                $daytype = $this->coreFunctions->datareader("select daytype as value from holidayloc where date_format(dateid, '%Y-%m-%d') = ? and description = '" . $emploc . "'", [date('Y-m-d', strtotime($data['dateid']))]);
                if (empty($daytype)) {
                    $daytype = $this->coreFunctions->datareader("select daytype as value from holiday where date_format(dateid, '%Y-%m-%d') = ?", [date('Y-m-d', strtotime($data['dateid']))]);
                }
                if (!empty($daytype)) {
                    $data['daytype'] = $daytype;
                } else {
                    $data['daytype'] = $break->tothrs != 0 ? "WORKING" : "RESTDAY";
                }

                // brk1stout
                // $data['daytype'] = $break->tothrs != 0 ? "WORKING" : "RESTDAY";
                $data['reghrs'] = $break->tothrs;

                // // default time in/out
                if ($data['daytype'] != 'RESTDAY') {
                    $data['actualin'] = $data['schedin'];
                    $data['actualout'] = $data['schedout'];

                    $data['actualbrkin'] = $data['schedbrkin'];
                    $data['actualbrkout'] = $data['schedbrkout'];
                } else {
                    $data['actualin'] = null;
                    $data['actualout'] = null;

                    $data['actualbrkin'] = null;
                    $data['actualbrkout'] = null;
                }

                if ($config['params']['companyid'] == 45) {
                    $data['actualin'] = null;
                    $data['actualout'] = null;
                    $data['absdays'] = $break->tothrs;
                }

                $this->coreFunctions->sbcinsert('temptimecard', $data);
            }

            return ['status' => true, 'msg' => 'Successfully Creating Schedules', 'action' => 'load'];
        } else {
            return ['status' => false, 'msg' => 'Shift missing. Please setup shift on employee ledger. ' . $empname, 'action' => 'load'];
        }
    }


    private  function getDays($start, $end)
    {
        $start = date('Y-m-d', strtotime($start));
        $end = date('Y-m-d', strtotime($end));

        $qry = "select a.Date as dateid, dayname(a.Date) as dayname
        from (
            select '" . $end . "' - INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY as Date
            from (select 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) as a
            cross join (select 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) as b
            cross join (select 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) as c
        ) a 
        where a.Date between '" . $start . "' and '" . $end . "' order by a.Date";

        return $this->coreFunctions->opentable($qry);
    }

    private  function getBreakSched($shift)
    {
        $qry = "select time(breakin) as breakin, time(breakout) as breakout, tothrs, time(brk1stin) as brk1stin, time(brk1stout) as brk1stout from shiftdetail where shiftsid=? order by dayn";

        return $this->coreFunctions->opentable($qry, [$shift]);
    }

    private  function getTimeSched($shift)
    {
        $qry = "select time(schedin) as schedin, time(schedout) as schedout, tothrs from shiftdetail where shiftsid=? order by dayn";

        return $this->coreFunctions->opentable($qry, [$shift]);
    }

    private function getBreakSchedTime($data, $day)
    {

        $index = 0;
        switch (strtoupper($day)) {
            case "MONDAY":
                $index = 0;
                break;
            case "TUESDAY":
                $index = 1;
                break;
            case "WEDNESDAY":
                $index = 2;
                break;
            case "THURSDAY":
                $index = 3;
                break;
            case "FRIDAY":
                $index = 4;
                break;
            case "SATURDAY":
                $index = 5;
                break;
            case "SUNDAY":
                $index = 6;
                break;
        }

        return $data[$index];
    }
} //end class
