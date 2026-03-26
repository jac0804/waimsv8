<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class updateremreschedule
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;

    public $modulename = 'RE-SCHEDULE';
    public $gridname = 'inventory';
    private $fields = [];
    public $tablenum = 'cntnum';
    private $table = 'vrhead';
    private $htable = 'hvrhead';

    public $tablelogs = 'transnum_log';

    public $style = 'width:100%;max-width:70%;';
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
        $attrib = array('load' => 0);
        return $attrib;
    }

    public function createHeadField($config)
    {
        $trno = $config['params']['trno'];
        $doc = $config['params']['doc'];
        $isposted = $this->othersClass->isposted2($trno, "cntnum");

        $fields = ['rem', 'refresh'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'refresh.label', 'ADD REMARKS');
        data_set($col1, 'rem.readonly', false);

        $fields = [];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' =>  $col2);
    }

    public function paramsdata($config)
    {
        return $this->getheaddata($config);
    }

    public function getheaddata($config)
    {
        $doc = $config['params']['doc'];
        $trno = $config['params']['trno'];

        $select = "select '' as rem, " . $trno . " as trno";
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
        $trno = $config['params']['dataparams']['trno'];
        $rem = $config['params']['dataparams']['rem'];
        if ($rem == '') {
            return ['status' => false, 'msg' => 'Please input valid remarks', 'data' => []];
        }

        $data = [
            'trno' => $trno,
            'rem' => $rem,
            'createby' => $config['params']['user'],
            'createdate' => $this->othersClass->getCurrentTimeStamp(),
            'remtype' => 2
        ];

        $this->coreFunctions->sbcinsert('headrem', $data);

        $path = 'App\Http\Classes\modules\vehiclescheduling\vl';
        $config['params']['trno'] = $trno;
        $config['params']['canceltype'] = 'rescedule';
        app($path)->cancelrequest($config);

        return ['status' => true, 'msg' => 'Successfully saved.', 'data' => [], 'backlisting' => true];
    }
}
