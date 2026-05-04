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

class viewbranch
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'LIST OF BRANCH';
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
        $colums = ['action', 'branch', 'oqty', 'itemname', 'counts', 'clientquota'];

        foreach ($colums as $key => $value) {
            $$value = $key;
        }

        $tab = [$this->gridname => ['gridcolumns' => $colums]];
        $stockbuttons = ['viewdepartment'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$branch]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$branch]['style'] = "width:350px;whiteSpace: normal;min-width:350px;";

        $obj[0][$this->gridname]['columns'][$counts]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$counts]['label'] = "Existing";
        $obj[0][$this->gridname]['columns'][$counts]['style'] = "text-align:left;width:100px;whiteSpace: normal;min-width:100px;";

        $obj[0][$this->gridname]['columns'][$oqty]['label'] = "Allocation";
        $obj[0][$this->gridname]['columns'][$oqty]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$oqty]['style'] = "text-align:right;width:175px;whiteSpace: normal;min-width:175px;";

        $obj[0][$this->gridname]['columns'][$clientquota]['label'] = "Lacking";
        $obj[0][$this->gridname]['columns'][$clientquota]['style'] = "text-align:right;width:125px;whiteSpace: normal;min-width:125px;";

        $obj[0][$this->gridname]['columns'][$itemname]['style'] = "text-align:right;width:150px;whiteSpace: normal;min-width:150px;";


        $this->modulename .= ' (' . $config['params']['row']['company'] . ' - ' . $config['params']['row']['sectname'] . ' - ' . $config['params']['row']['area'] . ')';
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
        $area = $config['params']['row']['area'];
        $sectname = $config['params']['row']['sectname'];
        $company = $config['params']['row']['company'];
        $qry = "select count(emp.empid) as counts, bran.clientname as branch,emp.divid,emp.sectid,emp.branchid,'$area' as area,'$sectname' as sectname,
               '$company' as company,0 as oqty, 0 as clientquota from employee as emp
        		left join client as bran on bran.clientid = emp.branchid and bran.area = '$area'
                where emp.isactive = 1 and emp.divid = $divid and emp.sectid = $sectid and bran.area = '$area'
                group by bran.clientname,emp.divid,emp.sectid,emp.branchid";

        $data = $this->coreFunctions->opentable($qry);
        foreach ($data as $key => $value) {

            $data_branch = $this->checkbranchjob($value->branchid, $area, $divid, $sectid);
            if ($data_branch != 0) {
                $value->oqty = $data_branch;
            }

            $value->clientquota = $value->oqty - $value->counts;
        }
        return $data;
    }
    public function checkbranchjob($branchid, $area, $divid, $sectid)
    {

        $job = $this->coreFunctions->opentable("select emp.jobid,emp.branchid from employee as emp 
            left join client as bran on bran.clientid = emp.branchid
            where emp.divid = $divid and emp.sectid = $sectid and bran.area = '$area' and bran.clientid = $branchid 
            and (emp.jobid <> 0 and emp.branchid <> 0) and emp.isactive = 1
            group by emp.branchid,emp.jobid");

        $qty = 0;
        foreach ($job as $key => $value) {
            $oqty = $this->coreFunctions->datareader("select sum(jobs.qty) as value from cljobs as jobs 
                left join client as bran on bran.clientid = jobs.clientid
                where  jobs.jobid = $value->jobid and bran.area = '$area' and jobs.clientid = $branchid", [], '', true);
            if ($oqty != 0) {
                $qty += $oqty;
            }
        }
        return $qty;
    }
} //end class
