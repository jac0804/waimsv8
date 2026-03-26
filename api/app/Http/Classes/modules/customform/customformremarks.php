<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class customformremarks
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;

    public $modulename = 'REMARKS';
    public $gridname = 'inventory';
    private $fields = [];
    public $tablenum = 'transnum';
    private $logger;

    public $tablelogs = 'transnum_log';

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
        $attrib = array('load' => 0);
        return $attrib;
    }

    public function createHeadField($config)
    {
        $this->modulename = "REMARKS";

        $fields = ['prref', 'rem', 'refresh'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'prref.type', 'lookup');
        data_set($col1, 'prref.readonly', true);
        data_set($col1, 'prref.action', 'lookupremarks');
        data_set($col1, 'prref.label', 'Type');
        data_set($col1, 'refresh.label', 'SAVE');
        data_set($col1, 'rem.readonly', false);
        data_set($col1, 'prref.readonly', true);

        return array('col1' => $col1);
    }

    public function paramsdata($config)
    {
        return $this->getheaddata($config);
    }

    public function getheaddata($config)
    {
        $trno = $config['params']['row']['trno'];
        $select = "select $trno as trno,'' as prref,'' as rem";

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
        $prref = $config['params']['dataparams']['prref'];

        if ($rem == '' && $prref == 'Others') {
            return ['status' => false, 'msg' => 'Please input valid remarks.', 'data' => []];
        }

        $data = [
            'trno' => $trno,
            'prref' => $prref,
            'rem' => $rem,
            'createby' => $config['params']['user'],
            'createdate' => $this->othersClass->getCurrentTimeStamp()
        ];

        $this->coreFunctions->sbcinsert("headprrem", $data);

        return ['status' => true, 'msg' => 'Successfully saved.', 'data' => [], 'reloadlisting' => true];
    }
}
