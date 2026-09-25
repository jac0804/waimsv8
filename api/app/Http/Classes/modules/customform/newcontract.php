<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class newcontract
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;

    public $modulename = 'NEW CONTRACT';
    public $gridname = 'accounting';
    private $logger;

    public $tablelogs = 'contracts_log';

    public $style = 'width:30%;max-width:500px;';
    public $issearchshow = false;
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
        $attrib = array('load' => 0);
        return $attrib;
    }

    public function createHeadField($config)
    {
        $fields = array('startdate', 'enddate', 'rem', 'refresh');
        $col1 = $this->fieldClass->create($fields);

        data_set($col1, 'startdate.label', 'Start Date');
        data_set($col1, 'startdate.type', 'date');

        data_set($col1, 'enddate.label', 'End Date');
        data_set($col1, 'enddate.type', 'date');
        data_set($col1, 'enddate.readonly', true);

        data_set($col1, 'rem.label', 'Notes');
        data_set($col1, 'rem.type', 'ctextarea');

        data_set($col1, 'refresh.label', 'NEW CONTRACT');

        return array('col1' => $col1);
    }

    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);

        return $obj;
    }

    public function paramsdata($config)
    {
        $result = $this->getheaddata($config);
        return $result;
    }


    public function getheaddata($config)
    {
        $empid = isset($config['params']['addedparams']['clientid']) ? $config['params']['addedparams']['clientid'] : 0;

        $qry = "select line, empid, contractn, descr as rem, datefrom as startdate, dateto as enddate
        from contracts
        where empid = ?
        order by line desc
        limit 1";

        $data = $this->coreFunctions->opentable($qry, array($empid));

        if (empty($data)) {
            $qry2 = "select 0 as line, $empid as empid, '' as rem, null as startdate, null as enddate";
            $data = $this->coreFunctions->opentable($qry2);
        }

        return $data;
    }

    public function loaddata($config)
    {
        $empid = isset($config['params']['clientid']) ? $config['params']['clientid'] : 0;
        $user = $config['params']['user'];

        $data = array(
            'empid'    => $empid,
            'descr'    => '',
            'datefrom' => null,
            'dateto'   => null,
            'editby'   => $user,
            'editdate' => $this->othersClass->getCurrentTimeStamp()
        );

        $line = $this->coreFunctions->insertGetId('contracts', $data);

        $config['params']['doc'] = 'CONTRACT';
        $this->logger->sbcwritelog($line, $config, 'CREATE', 'New Contract - blank record started');

        return array('status' => true, 'msg' => 'New contract started successfully.', 'closecustomform' => true, 'reloadhead' => true);
    }

    public function data($config)
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
}
