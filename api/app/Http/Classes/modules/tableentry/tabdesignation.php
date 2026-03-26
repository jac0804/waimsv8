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

class tabdesignation
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'DESIGNATION';
    public $tablenum = 'hrisnum';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'designation';
    private $htable = '';
    private $othersClass;
    public $style = 'width:100%;max-width: 100%';
    private $fields = [];
    public $showclosebtn = true;
    public $tablelogs = 'hrisnum_log';
    public $tablelogs_del = 'del_hrisnum_log';
    public $sqlquery;
    public $logger;

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->sqlquery = new sqlquery;
        $this->logger = new Logger;
    }

    public function getAttrib()
    {
        $attrib = ['load' => 0];
        return $attrib;
    }

    public function createTab($config)
    {
        $getcols = ['category', 'docno', 'dateeffect', 'rolename', 'company', 'branch', 'jobtitle', 'department', 'sectname', 'supervisorname', 'remarks', 'encodeddate'];

        foreach ($getcols as $key => $value) {
            $$value = $key;
        }

        $tab = [$this->gridname => ['gridcolumns' => $getcols]];
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0][$this->gridname]['columns'][$category]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$docno]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$dateeffect]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$rolename]['style'] = "width:130px;whiteSpace: normal;min-width:130px;";
        $obj[0][$this->gridname]['columns'][$company]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
        $obj[0][$this->gridname]['columns'][$branch]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
        $obj[0][$this->gridname]['columns'][$jobtitle]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
        $obj[0][$this->gridname]['columns'][$department]['style'] = "width:250px;whiteSpace: normal;min-width:250px;";
        $obj[0][$this->gridname]['columns'][$sectname]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
        $obj[0][$this->gridname]['columns'][$supervisorname]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
        $obj[0][$this->gridname]['columns'][$remarks]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";

        $obj[0][$this->gridname]['columns'][$category]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$docno]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$branch]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$rolename]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$company]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$department]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$sectname]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$supervisorname]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$jobtitle]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$dateeffect]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$remarks]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$encodeddate]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$sectname]['label'] = 'Section';
        $obj[0][$this->gridname]['columns'][$remarks]['label'] = 'Notation';

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
        if ($config['params']['doc'] == 'RS') {
            $empid = $config['params']['row']['empid'];
            $this->modulename = 'DESIGNATION HISTORY';
        } else {
            $empid = $config['params']['tableid'];
        }

        if ($config['params']['doc'] == 'RS') {
            $qry = "select d.line,branch.clientname as branch,role.name as rolename,c.divname as company,
                    dept.clientname as department,sect.sectname,s.clientname as supervisorname,job.jobtitle,loc.locname,
                    date(d.effectdate) as dateeffect,cat.category,d.notation as remarks,if(d.isrole=1,'ROLE',reh.docno) as docno, d.encodeddate
                    from designation as d
                    left join client as branch on branch.clientid=d.branchid
                    left join rolesetup as role on role.line=d.roleid
                    left join client as s on s.clientid=d.supervisorid
                    left join jobthead as job on job.line=d.jobid
                    left join emploc as loc on loc.line=d.locid
                    left join reqcategory as cat on cat.line=d.category
                    left join rasstock as re on re.trno=d.trno and re.line=d.linex
                    left join rashead as reh on reh.trno=re.trno
                    left join division as c on c.divid=d.divid
                    left join client as dept on dept.clientid=d.deptid
                    left join section as sect on sect.sectid=d.sectid
                    where d.empid= $empid
                    order by line desc";
        } else {
            $qry = "select d.line,branch.clientname as branch,role.name as rolename,c.divname as company,
                    dept.clientname as department,sect.sectname,s.clientname as supervisorname,job.jobtitle,loc.locname,
                    date(d.effectdate) as dateeffect,cat.category,d.notation as remarks,if(d.isrole=1,'ROLE',reh.docno) as docno, d.encodeddate
                from designation as d
                left join client as branch on branch.clientid=d.branchid
                left join rolesetup as role on role.line=d.roleid
                left join client as s on s.clientid=d.supervisorid
                left join jobthead as job on job.line=d.jobid
                left join emploc as loc on loc.line=d.locid
                left join reqcategory as cat on cat.line=d.category
                left join rasstock as re on re.trno=d.trno and re.line=d.linex
                left join rashead as reh on reh.trno=re.trno
                left join division as c on c.divid=d.divid
                left join client as dept on dept.clientid=d.deptid
                left join section as sect on sect.sectid=d.sectid
                where d.empid= $empid
                union all
                select 0 as line,branch.clientname as branch,role.name as rolename,c.divname as company,
                    dept.clientname as department,sect.sectname,s.clientname as supervisorname,job.jobtitle,
                    loc.locname,date(e.hired) as dateeffect,'' as category,'' as remarks,'Hired Status' as docno, date(e.hired) as encodeddate
                from employee as e
                left join client as branch on branch.clientid=e.branchid2
                left join rolesetup as role on role.line=e.roleid2
                left join division as c on c.divid=e.divid2
                left join client as dept on dept.clientid=e.deptid2
                left join section as sect on sect.sectid=e.sectid2
                left join client as s on s.clientid=e.supervisorid2
                left join jobthead as job on job.line=e.jobid2
                left join emploc as loc on loc.locname=e.emploc
                where e.empid= $empid 
                order by line desc";
        }

        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }
}
