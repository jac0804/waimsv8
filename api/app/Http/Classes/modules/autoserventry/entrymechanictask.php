<?php

namespace App\Http\Classes\modules\autoserventry;

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

class entrymechanictask
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'TASK';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = '';
    private $othersClass;
    public $style = 'width:100%;';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    private $fields = [];
    public $showclosebtn = false;
    private $enrollmentlookup;
    private $logger;

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
    }

    public function getAttrib()
    {
        $attrib = array('load' => 0);
        return $attrib;
    }

    public function createTab($config)
    {
        // var_dump($config['params']);
        // break;
        $columns = ['docno', 'jobcode', 'description', 'code', 'task', 'rem', 'vehicle', 'mileage', 'labor'];
        $tab = [$this->gridname => ['gridcolumns' => $columns]];

        foreach ($columns as $key => $value) {
            $$value = $key;
        }

        $stockbuttons = [];
        $tab = [$this->gridname => ['gridcolumns' => $columns]];
        $obj = $this->tabClass->createTab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$docno]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$docno]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";
        $obj[0][$this->gridname]['columns'][$jobcode]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";
        $obj[0][$this->gridname]['columns'][$jobcode]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$description]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$description]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$description]['label'] = 'Job Description';


        $obj[0][$this->gridname]['columns'][$code]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$code]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$code]['label'] = 'Task Code';
        $obj[0][$this->gridname]['columns'][$task]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$task]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$task]['label'] = 'Task Description';

        $obj[0][$this->gridname]['columns'][$rem]['style'] = "width:250px;whiteSpace: normal;min-width:250px;";
        $obj[0][$this->gridname]['columns'][$rem]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$mileage]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$labor]['readonly'] = true;

        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }
    public function selectqry()
    {
        $query = "head.trno, head.docno,date(head.dateid) as dateid,
                  cvh.cmake as vehicle, cvh.mileage,
                  jt.docno as jobcode, jt.jobtitle as description,am.cost as labor,
                  am.rem, jobs.jobcode as code,jobs.description as task";
        return $query;
    }
    public function loaddata($config)
    {
        $filtersearch = "";
        $searchfield  = ['head.docno', 'date(head.dateid)', 'jt.docno', 'jt.jobtitle', 'jobs.jobcode', 'jobs.description', 'cvh.mileage'];

        if (isset($config['params']['filter'])) {
            $search = $config['params']['filter'];
            foreach ($searchfield as $sfield) {
                if ($filtersearch == "") {
                    $filtersearch .= " and (" . $sfield . " like '%" . $search . "%'";
                } else {
                    $filtersearch .= " or " . $sfield . " like '%" . $search . "%'";
                }
            }
            $filtersearch .= ")";
        }

        $select = $this->selectqry() . ", '' as bgcolor";
        $qry = "select " . $select . "
  
        from lahead as head
        left join client on client.client = head.client
        left join cvehicle as cvh on cvh.clientid = client.clientid and cvh.line = head.carid
        left join amjobs as pt on pt.trno = head.trno
        left join jobthead as jt on jt.line = pt.jobid
        left join amtask as am on am.trno = head.trno and am.jobline = pt.line
        left join jobtask as jobs on jobs.line = am.laborline
        where head.doc = 'AM'

        union all

        select $select
        from glhead as head
        left join client on client.clientid = head.clientid
        left join cvehicle as cvh on cvh.clientid = client.clientid and cvh.line = head.carid
        left join amjobs as pt on pt.trno = head.trno
        left join jobthead as jt on jt.line = pt.jobid
        left join amtask as am on am.trno = head.trno and am.jobline = pt.line
        left join jobtask as jobs on jobs.line = am.laborline
        where head.doc = 'AM'";
        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }
} //end class
