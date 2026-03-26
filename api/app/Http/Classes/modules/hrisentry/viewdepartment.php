<?php

namespace App\Http\Classes\modules\hrisentry;

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

class viewdepartment
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'LIST OF DEPARTMENT';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $linkemail;
    private $table = '';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    public $style = 'width:90%;max-width:90%;';
    public $issearchshow = false;
    public $showclosebtn = true;
    public $fields = [];


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
        $colums = ['action', 'department', 'counts'];

        foreach ($colums as $key => $value) {
            $$value = $key;
        }

        $tab = [$this->gridname => ['gridcolumns' => $colums]];
        $stockbuttons = ['viewposition'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
        $obj[0][$this->gridname]['columns'][$department]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$department]['style'] = "width:350px;whiteSpace: normal;min-width:350px;";

        $obj[0][$this->gridname]['columns'][$counts]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$counts]['label'] = "Existing";
        $obj[0][$this->gridname]['columns'][$counts]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";

        $this->modulename .= ' - ' . $config['params']['row']['branch'];
        return $obj;
    }


    public function createtabbutton($config)
    {
        $obj = [];
        return $obj;
    }

    public function createHeadField($config)
    {
        $fields = [];
        $col1 = $this->fieldClass->create($fields);
        return array('col1' => $col1);
    }

    public function loaddata($config)
    {
        $sectid = $config['params']['row']['sectid'];
        $divid = $config['params']['row']['divid'];
        $branchid = $config['params']['row']['branchid'];
        $query = "select count(emp.empid) as counts,dept.clientname as department,emp.branchid,emp.divid,emp.sectid,emp.deptid from employee as emp
        		left join client as dept on dept.clientid = emp.deptid
                where emp.isactive = 1 and emp.branchid = $branchid and emp.divid = $divid and emp.sectid = $sectid
                group by dept.clientname,emp.branchid,emp.divid,emp.sectid,emp.deptid";

        return $this->coreFunctions->opentable($query);
    }
} //end class
