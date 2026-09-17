<?php

namespace App\Http\Classes\modules\customform;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;
use App\Http\Classes\common\linkemail;
use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use Illuminate\Support\Facades\Storage;

class clientpendingtask
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'LIST OF PENDING TASK';
    public $gridname = 'multigrid2';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $linkemail;
    public $tablelogs = 'task_log';
    public $style = 'width:90%;max-width:90%;';
    public $issearchshow = true;
    public $showclosebtn = true;
    public $tablelogs_del = 'del_task_log';

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
        $this->linkemail = new linkemail;
    }

    public function getAttrib()
    {
        $attrib = array(
            'load' => 0
        );
        return $attrib;
    }

    public function createTab($config)
    {
        $clientname = isset($config['params']['row']['clientname']) ? $config['params']['row']['clientname'] : '';
        $this->modulename = 'LIST OF PENDING TASK - ' . $clientname;
        $tab = [
            $this->gridname => ['action' => 'tableentry', 'lookupclass' => 'viewclientpendingtask', 'label' => 'LIST OF TASKS']
        ];

        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        return $obj;
    }

    public function createtabbutton($config)
    {
        $obj = [];
        return $obj;
    }

    public function createHeadField($config)
    {
        return [];
    }

    public function paramsdata($config)
    {
        // $dateid = $this->othersClass->getCurrentDate();
        // // $trno = $config['params']['row']['trno'];
        // $paramstr = "select '" . $dateid . "' as dateid";
        return $this->getheaddata($config);
        // return [];
    }

    public function getheaddata($config)
    {
        $clientid = isset($config['params']['row']['clientid']) ? $config['params']['row']['clientid'] : 0;
        $item = isset($config['params']['row']['item']) ? $config['params']['row']['item'] : 0;

        $qry = "select '$clientid' as clientid, '$item' as item";

        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }

    public function data($config)
    {
        return [];
    }



    public function loaddata($config)
    {
        return [];
    }


    public function updateapp($config)
    {
        return [];
    }
} //end class
