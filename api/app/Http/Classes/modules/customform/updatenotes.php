<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\modules\inventory\pc;
use App\Http\Classes\sqlquery;
use Exception;

use Datetime;
use Carbon\Carbon;

class updatenotes
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;
    private $logger;
    private $sqlquery;

    public $modulename = "UPDATE NOTES";
    public $gridname = 'inventory';
    private $fields = [];
    private $head = 'client';
    public $style = 'width:100%;max-width:60%;';
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
        $this->sqlquery = new sqlquery;
    }

    public function getAttrib()
    {
        $attrib = array('load' => 0);
        return $attrib;
    }

    public function createHeadField($config)
    {
        $fields = ['rem', 'refresh'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'rem.readonly', false);
        data_set($col1, 'refresh.label', 'Save');
        return array('col1' => $col1);
    }

    public function paramsdata($config)
    {
        return $this->coreFunctions->opentable("select '".$config['params']['addedparams']['rem']."' as rem");
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

    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);

        return $obj;
    }

    public function loaddata($config)
    {
        $clientid = $config['params']['clientid'];
        $rem = $this->othersClass->sanitize($config['params']['dataparams']['rem'], 'STRING');
        if ($this->coreFunctions->execqry("update client set rem='".$rem."' where clientid=".$clientid, "update")) {
            $status = true;
            $msg = 'Notes updated';
            return ['status' => true, 'msg' => 'Notes updated', 'closecustomform' => true, 'updateheaddatafield' => ['field' => 'rem', 'value' => $rem]];
        }
        return ['status' => false, 'msg' => 'Error updating notes, Please try again.'];
    }
}
