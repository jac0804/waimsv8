<?php

namespace App\Http\Classes\modules\hrisentry;

/*
use Session;*/

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Classes\stockClass;
use App\Http\Classes\othersClass;
use App\Http\Classes\clientClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\headClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\builder\lookupClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\mobile\modules\inventoryapp\inventory;
use Exception;
use Throwable;
use Session;

use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;


class employeeoverview
{
    private $othersClass;
    private $coreFunctions;
    private $headClass;
    private $logger;
    private $lookupClass;
    public $gridname = 'inventory';
    public $modulename = 'Employee Overview';
    private $companysetup;
    private $config = [];
    private $sqlquery;
    private $tabClass;
    private $fieldClass;
    public $style = 'width:100%;';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    public $showclosebtn = false;


    private $totalEmployees = 0;

    public function __construct()
    {
        $this->othersClass = new othersClass;
        $this->coreFunctions = new coreFunctions;
        $this->headClass = new headClass;
        $this->logger = new Logger;
        $this->lookupClass = new lookupClass;
        $this->companysetup = new companysetup;
        $this->sqlquery = new sqlquery;
        $this->tabClass = new tabClass;
        $this->fieldClass = new txtfieldClass;
    }

    public function getAttrib()
    {
        $attrib = array(
            'load' => 5593,
            'view' => 5594,
        );
        return $attrib;
    }
    public function load($config)
    {
        $qry = "select divcode,divname as divisionname,divid,picture from division";
        return $this->coreFunctions->opentable($qry);
    }
    public function createTab($config)
    {

        $column = ['action', 'picture', 'company', 'counts'];

        foreach ($column as $key => $value) {
            $$value = $key;
        }
        $tab = [$this->gridname => ['gridcolumns' => $column]];
        $stockbuttons = ['viewsection'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$company]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$company]['style'] = "width:350px;whiteSpace: normal;min-width:350px;";

        $obj[0][$this->gridname]['columns'][$counts]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$counts]['label'] = "Existing";
        $obj[0][$this->gridname]['columns'][$counts]['style'] = "width:100%;whiteSpace: normal;min-width:100%;";
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
        $qry = "
        select count(emp.empid) as counts,v.company,v.divid,v.picture from (
		  select divi.divname as company,divi.divid,divi.picture 
        from division as divi
		 ) as v
		 left join employee as emp on emp.divid = v.divid and emp.isactive = 1  
		group by v.company,v.divid,v.picture";
        return $this->coreFunctions->opentable($qry);
    }
}
