<?php

namespace App\Http\Classes\modules\hrisentry;

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
use App\Http\Classes\sbcdb\trigger;
use App\Http\Classes\sbcdb\waims;
use App\Http\Classes\sbcdb\customersupport;

class viewsection
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'LIST OF SECTION';
    public $gridname = 'customformacctg';
    private $companysetup;
    private $coreFunctions;
    private $table = 'cntnum';
    private $othersClass;
    public $tablelogs = 'payroll_log';
    public $style = 'width:400px;max-width:400px;';
    public $issearchshow = false;
    public $showclosebtn = true;
    public $fields = ['status', 'canceldate', 'cancelby', 'reason'];

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
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

        $cols = ['action', 'sectname', 'counts'];
        foreach ($cols as $key => $value) {
            $$value = $key;
        }
        $stockbuttons = ['viewbranch'];
        $tab = [$this->gridname => ['gridcolumns' => $cols]];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$sectname]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$sectname]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";

        $obj[0][$this->gridname]['columns'][$counts]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$counts]['label'] = "Existing";
        $obj[0][$this->gridname]['columns'][$counts]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";

        $this->modulename .= ' - ' . $config['params']['row']['company'];

        return $obj;
    }
    public function createHeadField($config)
    {
        $fields = [];
        $col1 = $this->fieldClass->create($fields);
        return array('col1' => $col1);
    }
    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);

        return $obj;
    }
    public function loaddata($config)
    {
        $divid =  $config['params']['row']['divid'];
        $query  = "select count(emp.empid) as counts, emp.divid,sec.sectid,sec.sectname from employee as emp
				  left join section as sec on sec.sectid = emp.sectid 
                  where emp.isactive = 1 and emp.divid = $divid and emp.sectid <> 0
                  group by emp.divid,sec.sectid,sec.sectname";
        return $this->coreFunctions->opentable($query);
    }
} //end class
