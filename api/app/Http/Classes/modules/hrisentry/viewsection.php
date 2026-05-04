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
    public $modulename = 'SECTION LIST';
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

        $cols = ['action', 'sectname', 'oqty', 'itemname', 'counts', 'clientquota'];
        foreach ($cols as $key => $value) {
            $$value = $key;
        }
        $stockbuttons = ['viewarea'];
        $tab = [$this->gridname => ['gridcolumns' => $cols]];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$sectname]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$sectname]['style'] = "width:350px;whiteSpace: normal;min-width:350px;";

        $obj[0][$this->gridname]['columns'][$counts]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$counts]['label'] = "Existing";
        $obj[0][$this->gridname]['columns'][$counts]['style'] = "text-align:left;width:100px;whiteSpace: normal;min-width:100px;";

        $obj[0][$this->gridname]['columns'][$oqty]['label'] = "Allocation";
        $obj[0][$this->gridname]['columns'][$oqty]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$oqty]['style'] = "text-align:right;width:175px;whiteSpace: normal;min-width:175px;";

        $obj[0][$this->gridname]['columns'][$clientquota]['label'] = "Lacking";
        $obj[0][$this->gridname]['columns'][$clientquota]['style'] = "text-align:right;width:125px;whiteSpace: normal;min-width:125px;";

        $obj[0][$this->gridname]['columns'][$itemname]['style'] = "text-align:right;width:150px;whiteSpace: normal;min-width:150px;";

        $this->modulename .= ' (' . $config['params']['row']['company'] . ' )';

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
        $company = $config['params']['row']['company'];
        $query  = "select count(emp.empid) as counts, emp.divid,sec.sectid,sec.sectname,0 as oqty, 0 as clientquota,'$company' as company from employee as emp
				  left join section as sec on sec.sectid = emp.sectid
                  where emp.isactive = 1 and emp.divid = $divid
                  group by emp.divid,sec.sectid,sec.sectname";

        $data = $this->coreFunctions->opentable($query);

        foreach ($data as $key => $value) {

            $jobcount =  $this->checksectionjob($value->divid, $value->sectid);
            if ($jobcount != 0) {
                $value->oqty = $jobcount;
            }
            $value->clientquota = $value->oqty - $value->counts;
        }

        return $data;
    }
    public function checksectionjob($divid, $sectid)
    {
        $job = $this->coreFunctions->opentable("select emp.jobid,emp.branchid from employee as emp  
            where emp.divid = $divid and emp.sectid = $sectid and (emp.jobid <> 0 and emp.branchid <> 0) and emp.isactive = 1 
            group by emp.branchid ,emp.jobid");

        $qty = 0;
        foreach ($job as $key => $value) {
            $oqty = $this->coreFunctions->datareader("select ifnull(sum(jobs.qty),0) as value from cljobs as jobs
                where jobs.jobid = $value->jobid and jobs.clientid = $value->branchid", [], '', true);

            if ($oqty != 0) {
                $qty += $oqty;
            }
        }
        return $qty;
    }
} //end class
