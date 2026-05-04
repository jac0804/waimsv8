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

class viewposition
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'POSITION LIST';
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
        $colums = ['action', 'position', 'oqty', 'itemname', 'counts', 'clientquota'];

        foreach ($colums as $key => $value) {
            $$value = $key;
        }

        $tab = [$this->gridname => ['gridcolumns' => $colums]];
        $stockbuttons = ['viewemployee'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$position]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$position]['style'] = "width:350px;whiteSpace: normal;min-width:350px;";

        $obj[0][$this->gridname]['columns'][$counts]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$counts]['label'] = "Existing";
        $obj[0][$this->gridname]['columns'][$counts]['style'] = "text-align:left;width:100px;whiteSpace: normal;min-width:100px;";

        $obj[0][$this->gridname]['columns'][$oqty]['label'] = "Allocation";
        $obj[0][$this->gridname]['columns'][$oqty]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$oqty]['style'] = "text-align:right;width:175px;whiteSpace: normal;min-width:175px;";

        $obj[0][$this->gridname]['columns'][$clientquota]['label'] = "Lacking";
        $obj[0][$this->gridname]['columns'][$clientquota]['style'] = "text-align:right;width:125px;whiteSpace: normal;min-width:125px;";
        $obj[0][$this->gridname]['columns'][$itemname]['style'] = "text-align:right;width:150px;whiteSpace: normal;min-width:150px;";


        $this->modulename .= ' (' . $config['params']['row']['company'] . ' - ' . $config['params']['row']['sectname'] . ' - ' .
            $config['params']['row']['area'] . ' - ' . $config['params']['row']['branch'] . ' - ' . $config['params']['row']['department'] . ')';
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
        $deptid = $config['params']['row']['deptid'];
        $area = $config['params']['row']['area'];

        $area = $config['params']['row']['area'];
        $sectname = $config['params']['row']['sectname'];
        $company = $config['params']['row']['company'];
        $department = $config['params']['row']['department'];
        $branch = $config['params']['row']['branch'];

        $qry = "select jt.jobtitle as position,jobs.qty as oqty,$sectid as sectid, $divid as divid, $branchid as branchid, $deptid as deptid,'$area' as area,
                '$sectname' as sectname,'$company' as company,'$branch' as branch,'$department' as department,jobs.jobid,
				(select count(emp.empid) as empid from employee as emp
				left join client as b on b.clientid = emp.branchid  and b.area = '$area'
				where jobs.jobid = emp.jobid and emp.isactive = 1 and emp.divid = $divid and emp.sectid = $sectid and emp.branchid = $branchid and emp.deptid = $deptid and b.area = '$area') as counts, 0 as clientquota
				from client as bran
				left join cljobs as jobs on jobs.clientid = bran.clientid
				left join jobthead as jt on jt.line = jobs.jobid
				where bran.clientid = $branchid and bran.area = '$area' and jobs.deptid = $deptid
				group by jt.jobtitle,jobs.qty,jobs.jobid";
        $data = $this->coreFunctions->opentable($qry);

        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $value->clientquota = $value->oqty - $value->counts;
            }
        }

        return $data;
    }
} //end class
