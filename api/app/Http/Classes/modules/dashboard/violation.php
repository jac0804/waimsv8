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

class violation
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'VIOLATION';
    public $gridname = 'customformacctg';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    public $style = 'width:900px;max-width:900px;';
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

        $fields = ['dateid', 'remarks'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'dateid.style', 'width:150px;whiteSpace: normal;min-width:150px;');
        data_set($col1, 'remarks.label', 'Reason');
        $fields = [];
        $col2 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        return $this->coreFunctions->opentable('select dateid, remarks from violation where trno=?', [$config['params']['row']['line']]);
    }

    public function data()
    {
        return [];
    }

    public function loaddata($config)
    {
        return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => []];
    }
} //end class
