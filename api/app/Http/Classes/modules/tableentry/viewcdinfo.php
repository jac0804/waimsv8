<?php

namespace App\Http\Classes\modules\tableentry;

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
use App\Http\Classes\lookup\enrollmentlookup;
use Exception;

class viewcdinfo
{

    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;
    private $logger;


    public $modulename = 'View Info';
    public $gridname = 'inventory';
    private $fields = ['uom'];
    private $table = 'uom';

    public $tablelogs = 'table_log';

    public $style = 'width:100%;max-width:70%;';
    public $showclosebtn = true;
    public $issearchshow = true;


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
        $fields = [];
        $col1 = $this->fieldClass->create($fields);
        return array('col1' => $col1,);
    }
    public function loaddata($config)
    {

        $qry = "select '" . $config['params']['row']['trno'] . "' as trno,'" . $config['params']['row']['line'] . "' as line";

        $data = $this->coreFunctions->opentable($qry, [$config['params']['row']['trno'], $config['params']['row']['line']]);
        $this->modulename = $data[0]->modulename;
        return $data;
    }
    public function data()
    {
        return [];
    }
    public function createTab($config)
    {
        $columns = ['action', 'uom', 'factor', 'unit'];
        $stockbuttons = [];
        $tab = [$this->gridname => ['gridcolumns' => $columns]];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }
}
