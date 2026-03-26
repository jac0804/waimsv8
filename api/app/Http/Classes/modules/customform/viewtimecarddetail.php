<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class viewtimecarddetail
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $logger;
    private $warehousinglookup;

    public $modulename = 'TIMECARD PENALTIES';
    public $gridname = 'tableentry';
    private $fields = ['isnologin', 'isnombrkout', 'isnombrkin', 'isnolunchout', 'isnolunchin', 'isnopbrkout', 'isnopbrkin', 'isnologout', 'isnologpin', 'isnologunder'];
    private $table = 'whdoc';

    public $tablelogs = 'client_log';

    public $style = 'width:100%;max-width:100%;';
    public $issearchshow = true;
    public $showclosebtn = true;

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->coreFunctions = new coreFunctions;
        $this->companysetup = new companysetup;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
    }

    public function getAttrib()
    {
        $attrib = array('load' => 22, 'edit' => 23);
        return $attrib;
    }

    public function createHeadField($config)
    {
        $fields = ['clientname', 'dateid', ['isnologin', 'itime1'], ['isnombrkout', 'itime2'], ['isnombrkin', 'itime3'], ['isnolunchout', 'itime4'], ['isnolunchin', 'itime5'], ['isnopbrkout', 'itime6'], ['isnopbrkin', 'itime7'], ['isnologout', 'itime8'], ['isnologpin', 'itime9'], ['isnologunder', 'itime10'], 'refresh'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'refresh.label', 'ADD PENALTIES');

        $fields = [];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {

        return $this->getheaddata($config);
    }

    public function getheaddata($config)
    {
        if (isset($config['params']['dataparams']['empid'])) {
            $empid = $config['params']['dataparams']['empid'];
            $dateid = $config['params']['dataparams']['dateid'];
        } else {
            $empid = $config['params']['row']['empid'];
            $dateid = $config['params']['row']['dateid'];
        }

        $qry = "select tc.empid, tc.dateid, client.clientname, cast(tc.isnologin as char) as isnologin, cast(tc.isnologout as char) as isnologout, cast(tc.isnombrkout as char) as isnombrkout, cast(tc.isnombrkin as char) as isnombrkin, 
        cast(tc.isnolunchout as char) as isnolunchout, cast(tc.isnolunchin as char) as isnolunchin, cast(tc.isnopbrkout as char) as isnopbrkout, cast(tc.isnopbrkin as char) as isnopbrkin, cast(tc.isnologpin as char) as isnologpin, cast(tc.isnologunder as char) as isnologunder,
        time(actualin) as itime1, time(brk1stout) as itime2, time(brk1stin) as itime3, time(actualbrkout) as itime4, time(actualbrkin) as itime5,  time(brk2ndout)  as itime6, time(brk2ndin) as itime7, time(actualout) as itime8, '00:00' as itime9, '00:00' as itime10
        from timecard as tc left join client on client.clientid=tc.empid where tc.empid=? and tc.dateid=?";

        return $this->coreFunctions->opentable($qry, [$empid, $dateid]);
    }

    public function data()
    {
        return [];
    }

    public function createTab($config)
    {
        $tab = [];
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);

        return $obj;
    }

    public function loaddata($config)
    {
        $data = [];
        $head = $config['params']['dataparams'];
        foreach ($this->fields as $key) {
            if (array_key_exists($key, $head)) {
                $data[$key] = $head[$key];
            }
        }

        $data['actualin'] = null;
        $data['actualbrkout'] = null;
        $data['actualbrkin'] = null;
        $data['brk1stout'] = null;
        $data['brk1stin'] = null;
        $data['brk2ndout'] = null;
        $data['brk2ndin'] = null;

        if ($head['isnologin']) {
            $data['actualin'] = $head['dateid'] . ' ' . $head['itime1'];
        } else {
            unset($data['actualin']);
        }

        if ($head['isnombrkout']) {
            $data['brk1stout'] = $head['dateid'] . ' ' . $head['itime2'];
        } else {
            unset($data['brk1stout']);
        }

        if ($head['isnombrkin']) {
            $data['brk1stin'] = $head['dateid'] . ' ' . $head['itime3'];
        } else {
            unset($data['brk1stin']);
        }

        if ($head['isnolunchout']) {
            $data['actualbrkout'] = $head['dateid'] . ' ' . $head['itime4'];
        } else {
            unset($data['actualbrkout']);
        }

        if ($head['isnolunchin']) {
            $data['actualbrkin'] = $head['dateid'] . ' ' . $head['itime5'];
        } else {
            unset($data['actualbrkin']);
        }

        if ($head['isnopbrkout']) {
            $data['brk2ndout'] = $head['dateid'] . ' ' . $head['itime6'];
        } else {
            unset($data['brk2ndout']);
        }

        if ($head['isnopbrkin']) {
            $data['brk2ndin'] = $head['dateid'] . ' ' . $head['itime7'];
        } else {
            unset($data['brk2ndin']);
        }

        $this->coreFunctions->sbcupdate("timecard", $data, ['empid' => $head['empid'], 'dateid' => $head['dateid']]);

        $data = [];
        return ['status' => true, 'msg' => 'Successfully updated.', 'data' => $data];
    }
}
