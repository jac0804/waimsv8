<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class customformappstat
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;

    public $modulename = 'APPLICANT STATUS';
    public $gridname = 'inventory';
    private $fields = ['jstatus'];
    public $tablenum = '';
    private $table = 'app';
    private $htable = '';
    private $logger;

    public $tablelogs = 'masterfile_log';

    public $style = 'width:30%;max-width:70%;';
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
        $attrib = array('load' => 5175);
        return $attrib;
    }

    public function createHeadField($config)
    {
        $this->modulename = "SET STATUS";

        $fields = ['empname', 'jstatus', 'remarks', 'refresh'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'empname.type', 'input');
        data_set($col1, 'refresh.label', 'SAVE');
        data_set($col1, 'jstatus.readonly', true);
        data_set($col1, 'remarks.readonly', false);
        data_set($col1, 'jstatus.lookupclass', 'lookupappstat');

        return array('col1' => $col1);
    }

    public function paramsdata($config)
    {
        return $this->getheaddata($config);
    }

    public function getheaddata($config)
    {
        $empid = $config['params']['row']['empid'];
        $select = "select empid,empcode,concat(emplast,', ',empfirst,' ',empmiddle) as empname,jstatus,remarks,hqtrno
                 from app where empid = $empid";
        $data = $this->coreFunctions->opentable($select);
        return $data;
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
        $app = $config['params']['dataparams'];
        $clientid = $config['params']['dataparams']['empid'];
        $jstat = $config['params']['dataparams']['jstatus'];
        $remarks = $config['params']['dataparams']['remarks'];
        
        $data = [
            'jstatus' => $jstat,
            'jdateid' => $this->othersClass->getCurrentDate(),
            'remarks' => $remarks,
            'editdate' => $this->othersClass->getCurrentTimeStamp(),
            'editby' => $config['params']['user']
        ];

        $oldstatus = $this->coreFunctions->getfieldvalue("app", "jstatus", "empid=?", [$clientid]);

        foreach ($this->fields as $key) {
            $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        }
        if ($this->coreFunctions->sbcupdate("app", $data, ['empid' => $clientid])) {
            $result = $this->updatehqqa($config);
            if (!$result['status']) {
                $data['jstatus'] = $oldstatus;
                $this->coreFunctions->sbcupdate("app", $data, ['empid' => $clientid]);
                return ['status' => false, 'msg' => $result['msg'], 'data' => [], 'reloadlisting' => false];
            }
        }

        return ['status' => true, 'msg' => 'Successfully saved.', 'data' => [], 'reloadlisting' => true];
    }

    public function updatehqqa($config)
    {
        $msg = '';
        $status = true;
        $data = $config['params']['dataparams'];
        if ($data['hqtrno'] != 0) {
            $applied = $this->coreFunctions->datareader("select count(empid) as value from app where hqtrno=" . $data['hqtrno'] . " and jstatus='JOB OFFER'", [], '', true);
            if ($this->coreFunctions->execqry("update hpersonreq set qa=" . $applied . " where trno=" . $data['hqtrno'])) {
                $msg = 'Successfully updated.';
            } else {
                $status = false;
                $msg = 'Error updating request personnel';
            }
        }

        return ['status' => $status, 'msg' => $msg];
    }
}
