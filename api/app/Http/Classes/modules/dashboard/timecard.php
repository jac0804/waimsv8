<?php

namespace App\Http\Classes\modules\dashboard;

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
use Carbon\Carbon;

class timecard
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'TIMECARD';
    public $gridname = 'customformacctg';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    public $style = 'width:1000px;max-width:1000px;';
    public $issearchshow = true;
    public $showclosebtn = true;



    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
    }

    public function createTab($config)
    {
        $tab = [];
        $stockbuttons = [];
        $obj = [];
        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = [];
        return $obj;
    }

    public function createHeadField($config)
    {
        $label = $config['params']['data']['type'];
        $dateid = $config['params']['data']['datestart'];
        $daytype = $config['params']['data']['daytype'];
        $date = new DateTime($dateid);
        $day = $date->format("l");

        // $fields = ['dateid', 'scheddate', 'schedtime'];



        $fields = ['dateid', ['schedin', 'schedintime'], ['schedout', 'schedouttime']];
        $col1 = $this->fieldClass->create($fields);

        data_set($col1, 'dateid.style', 'width:150px;whiteSpace: normal;min-width:150px;');
        data_set($col1, 'dateid.style', 'padding:0px;');
        data_set($col1, 'dateid.label', 'Date Schedule');
        data_set($col1, 'dateid.type', 'input');
        data_set($col1, 'schedintime.style', 'padding:0px;');
        data_set($col1, 'schedin.style', 'padding:0px;');
        data_set($col1, 'schedin.type', 'input');



        data_set($col1, 'schedin.label', 'Date');
        data_set($col1, 'schedout.label', 'Date');
        data_set($col1, 'schedout.type', 'input');

        switch ($label) {
            case 'sched':
                $fields = [['lblrem', 'lblsource'], ['actualin', 'actualtimein'], ['actualout', 'actualtimeout']]; //
                break;
            case 'in':
                $fields = [['actualin', 'actualtimein']];
                break;
            case 'out':
                $fields = [['actualout', 'actualtimeout']];
                break;
            case 'o':
                $label = "OB Date";
                $fields = ['scheddate'];
                break;
            case 'l':
                $label = "Leave Date";
                $fields = ['scheddate'];
                break;
        }

        $col2 = $this->fieldClass->create($fields);

        data_set($col2, 'scheddate.style', 'padding:0px;');
        data_set($col2, 'scheddate.label', $label);
        data_set($col2, 'scheddate.type', 'input');

        data_set($col2, 'actualin.style', 'padding:0px;');
        data_set($col2, 'actualin.type', 'input');
        data_set($col2, 'actualout.style', 'padding:0px;');
        data_set($col2, 'actualout.type', 'input');
        if ($label == 'sched') {
            data_set($col2, 'lblrem.label', 'Day');
            data_set($col2, 'lblrem.style', 'font-weight:bold;text-align:center;');
            data_set($col2, 'lblsource.label', $day);
            data_set($col2, 'lblsource.style', 'font-weight:bold;text-align:center;');
            data_set($col2, 'daytype.type', 'label');
            // data_set($col2, 'daytype.label', 'Daytype: ');
        }


        $fields = [];
        if ($label == 'sched') {
            array_push($fields, 'lbllocation', 'ottimein', 'ottimeout');
        }
        $col3 = $this->fieldClass->create($fields);
        if ($label == 'sched') {
            data_set($col3, 'ottimein.label', 'Mins Late');
            data_set($col3, 'ottimein.type', 'input');
            data_set($col3, 'ottimeout.label', 'Mins Undertime');
            data_set($col3, 'ottimeout.type', 'input');
            data_set($col3, 'lbllocation.label', 'Day Type: ' . $daytype);
            data_set($col3, 'lbllocation.style', 'font-weight:bold;text-align:center;font-size:14px;');
            data_set($col3, 'ottimein.style', 'padding-top:4px;');
            data_set($col3, 'ottimeout.style', 'padding-top:4px;');
        }
        $fields = [];
        $col4 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function paramsdata($config)
    {

        $dateid = $this->othersClass->sbcdateformat($config['params']['data']['dateend']);
        $empid = $config['params']['data']['empid'];
        $label = $config['params']['data']['type'];
        $line = $config['params']['data']['line'];
        $addfield = "";
        switch ($label) {
            case 'sched':
                $label = "SCHEDULE";
                break;
            case 'in':
                $label = "ACTUAL IN";
                break;
            case 'out':
                $label = "ACTUAL OUT";
                break;
            case 'o':
                $label = "OB";
                $addfield = "
             ,(select date(ob.dateid) as dateid from obapplication as ob where date(ob.dateid) = '" . $dateid . "' 
		      and md5(concat(ob.line,ob.empid)) = '$line') as scheddate ";
                break;
            case 'l':
                $label = "LEAVE";
                $addfield = "
             ,(select date(lv.effectivity) as dateid from leavetrans as lv where date(lv.effectivity) = '" . $dateid . "' 
              and md5(concat(lv.trno,lv.line,lv.empid)) = '$line') as scheddate ";
                break;
        }
        // '%m-%d-%Y' 
        $query = "select date_format(dateid,'%m-%d-%Y') as dateid,

        schedin as schedintm, 

        date_format(schedin,'%m-%d-%Y') as schedin, 
        date_format(schedout,'%m-%d-%Y') as schedout, 
        schedin as schedintm,schedout as schedouttm,

        date_format(schedin,'%H:%i %p') as  schedintime,
        date_format(schedout,'%H:%i %p') as schedouttime, 

        date_format(actualin,'%m-%d-%Y') as actualin, 
		date_format(actualout,'%m-%d-%Y') as actualout,

        actualin as actualintm,actualout as actualouttm,

        date_format(actualout,'%H:%i %p') as actualtimeout,
        date_format(actualin,'%H:%i %p') as actualtimein,
        '0' as ottimein,'0' as ottimeout
        $addfield


        from timecard
        where empid = " . $empid . " and dateid = '" . $dateid . "' ";


        $this->modulename = $label;
        $data = $this->coreFunctions->opentable($query);



        if ($label == 'SCHEDULE') {
            //late
            $minlate = 0;
            if ($data[0]->actualintm > $data[0]->schedintm) {
                if ($data[0]->actualintm != null) {
                    $schedin = Carbon::parse($data[0]->schedintm);
                    $actualin = Carbon::parse($data[0]->actualintm);

                    if ($actualin != null) {
                        if ($actualin->greaterThan($schedin)) {
                            $minlate = $schedin->diffInMinutes($actualin);
                        }
                    }
                }
            }

            //undertime
            $minunder = 0;

            if ($data[0]->actualouttm < $data[0]->schedouttm) {
                if ($data[0]->actualouttm != null) {
                    $schedout = Carbon::parse($data[0]->schedouttm);
                    $actualout = Carbon::parse($data[0]->actualouttm);

                    if ($actualout != null) {
                        if ($actualout->lessThan($schedout)) {
                            $minunder = $actualout->diffInMinutes($schedout);
                        }
                    }
                }
            }

            foreach ($data as $key => $value) {
                $value->ottimein = $minlate . ' mins';
                $value->ottimeout = $minunder . ' mins';
            }
        }


        return $data;
    }

    public function data()
    {
        return [];
    }

    public function loaddata($config)
    {
        $config['params']['doc'] = 'timecard';
        return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => []];
    }
} //end class
