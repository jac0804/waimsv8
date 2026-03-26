<?php

namespace App\Http\Classes\modules\vehiclescheduling;


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
use App\Http\Classes\SBCPDF;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class vehiclesched
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Vehicle Schedule';
    public $gridname = 'entrygrid';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
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
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 2996,
            'edit' => 2997,
            'save' => 2997
        );
        return $attrib;
    }

    public function createHeadbutton($config)
    {
        $btns = []; //actionload - sample of adding button in header - align with form/module name
        $buttons = $this->btnClass->create($btns);
        return $buttons;
    }

    public function createHeadField($config)
    {
        $fields = [['year', 'month'], 'refresh'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'refresh.action', 'load');
        data_set($col1, 'refresh.label', 'Refresh List');

        $fields = ['headcount', 'create'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'headcount.label', 'Vehicle Count');
        data_set($col2, 'headcount.readonly', true);
        data_set($col2, 'create.label', 'Create/Update Schedule');

        $fields = [];
        $col3 = $this->fieldClass->create($fields);

        $fields = [];
        $col4 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function paramsdata($config)
    {

        $data = $this->coreFunctions->opentable("select count(clientid) as headcount, year(curdate()) as year, month(curdate()) as month, 0 as amcount, 0 as pmcount from client where istrucking=1 and isinactive=0");
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

    public function createTab($config)
    {
        $dateid = 0;
        $dayn = 1;
        $amcount = 2;
        $pmcount = 3;
        $amused = 4;
        $pmused = 5;
        $tab = [$this->gridname => [
            'gridcolumns' => ['dateid', 'dayn', 'amcount', 'pmcount', 'amused', 'pmused']
        ]];

        $stockbuttons = [];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['descriptionrow'] = [];

        $obj[0][$this->gridname]['columns'][$dateid]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$dayn]['type'] = 'label';

        $obj[0][$this->gridname]['columns'][$dayn]['label'] = 'Day Name';
        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function headtablestatus($config)
    {
        // should return action
        $action = $config['params']["action2"];

        switch ($action) {
            case "create":
                return $this->createschedule($config);
                break;

            case 'load':
                $year = $config['params']['dataparams']['year'];
                $month = $config['params']['dataparams']['month'];

                if ($year == 0) {
                    return ['status' => false, 'msg' => 'Please input valid year', 'action' => 'load', 'griddata' => ['entrygrid' => []]];
                }

                if ($month == 0) {
                    return ['status' => false, 'msg' => 'Please input valid month', 'action' => 'load', 'griddata' => ['entrygrid' => []]];
                }

                if ($month < 1 || $month > 12) {
                    return ['status' => false, 'msg' => 'Please input month between 1-12', 'action' => 'load', 'griddata' => ['entrygrid' => []]];
                }

                $data = $this->loaddetails($config);
                return ['status' => true, 'msg' => 'Successfully loaded.', 'action' => 'load', 'griddata' => ['entrygrid' => $data]];

            default:
                return ['status' => false, 'msg' => 'Data is not yet setup in the headtablestatus.'];
                break;
        }
    }

    public function loaddetails($config)
    {
        $year = $config['params']['dataparams']['year'];
        $month = $config['params']['dataparams']['month'];
        return $this->coreFunctions->opentable("select date(dateid) as dateid, amcount, pmcount, amused, pmused, dayname(dateid) as dayn from vehiclesched where year(dateid)=" . $year . " and month(dateid)=" . $month . " order by dateid");
    }

    public function createschedule($config)
    {
        $year = $config['params']['dataparams']['year'];
        $month = $config['params']['dataparams']['month'];

        if ($year == 0) {
            return ['status' => false, 'msg' => 'Please input valid year', 'action' => 'load', 'griddata' => ['entrygrid' => []]];
        }

        if ($month == 0) {
            return ['status' => false, 'msg' => 'Please input valid month', 'action' => 'load', 'griddata' => ['entrygrid' => []]];
        }

        if ($month < 1 || $month > 12) {
            return ['status' => false, 'msg' => 'Please input month between 1-12', 'action' => 'load', 'griddata' => ['entrygrid' => []]];
        }

        $tempdate =  date("Y-m-t", strtotime($year . "-" . $month . "-01"));

        $qry = "select a.Date as dateid, dayname(a.Date) as dayname
        from (
            select '" . $tempdate . "' - INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY as Date
            from (select 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) as a
            cross join (select 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) as b
            cross join (select 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) as c
        ) a 
        where year(a.Date)=" . $year . " and month(a.Date)=" . $month . " order by a.Date";

        $days = $this->coreFunctions->opentable($qry);
        foreach ($days as $key => $val) {
            $day = strtolower(substr($val->dayname, 0, 3));

            $amcount = $this->coreFunctions->datareader("select count(d.clientid) as value from daysched as d left join client on client.clientid=d.clientid where d.is" . $day . "=1 and d.is" . $day . "_am=1 and client.isinactive=0");
            $pmcount = $this->coreFunctions->datareader("select count(d.clientid) as value from daysched as d left join client on client.clientid=d.clientid where d.is" . $day . "=1 and d.is" . $day . "_pm=1 and client.isinactive=0");

            if ($amcount == '') {
                $amcount = 0;
            }

            if ($pmcount == '') {
                $pmcount = 0;
            }

            $data = [
                'dateid' => $val->dateid,
                'amcount' => $amcount,
                'pmcount' => $pmcount
            ];

            $exist = $this->coreFunctions->opentable("select dateid from vehiclesched where date(dateid)='" . $val->dateid . "'");
            if (empty($exist)) {
                $this->coreFunctions->sbcinsert('vehiclesched', $data);
            } else {
                if ($val->dateid >= date('Y-m-d')) {
                    $this->coreFunctions->sbcupdate('vehiclesched', $data, ['dateid' => $val->dateid]);
                }
            }
        }

        $data = $this->loaddetails($config);
        return ['status' => true, 'msg' => 'Successfully loaded.', 'action' => 'load', 'griddata' => ['entrygrid' => $data]];
    }
}
