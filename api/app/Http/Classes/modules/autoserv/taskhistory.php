<?php

namespace App\Http\Classes\modules\autoserv;

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
use App\Http\Classes\SBCPDF;
use App\Http\Classes\sbcscript\sbcscript;

class taskhistory
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'TASK HISTORY';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $sqlquery;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
    public $head = 'lahead';
    public $hhead = 'glhead';
    public $stock = 'lastock';
    public $hstock = 'glstock';
    public $detail = '';
    public $prefix = '';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = '';


    private $fields = [];
    private $except = [];
    public $showfilteroption = false;
    public $showfilter = false;
    public $showcreatebtn = false;
    private $reporter;


    public function __construct()
    {
        $this->btnClass = new buttonClass;
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
        $this->sqlquery = new sqlquery;
        $this->reporter = new SBCPDF;
    }

    public function getAttrib()
    {
        $attrib = array(
            'load' => 5914,
            'view' => 5915,
        );
        return $attrib;
    }

    public function createdoclisting($config)
    {
        $getcols = ['listdocument', 'dateid', 'code', 'description', 'client', 'clientname', 'vehicle', 'mileage', 'labor', 'amt', 'ext'];
        foreach ($getcols as $key => $value) {
            $$value = $key;
        }
        $stockbuttons = [];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[0]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $cols[$listdocument]['label'] = 'Invoice #';
        $cols[$code]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$code]['label'] = 'Task Code';
        $cols[$description]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
        $cols[$description]['label'] = 'Task Description';
        $cols[$client]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
        $cols[$client]['label'] = 'Code';
        $cols[$clientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
        $cols[$clientname]['label'] = 'Customer Name';
        $cols[$clientname]['type'] = 'label';
        $cols[$dateid]['style'] = 'width:80px;whiteSpace: normal;min-width:80px;';
        $cols[$labor]['style'] = 'width:80px;whiteSpace: normal;min-width:80px;';
        $cols[$amt]['label'] = 'Stocks';
        $cols[$amt]['style'] = 'width:80px;whiteSpace: normal;min-width:80px;';
        $cols[$ext]['style'] = 'width:80px;whiteSpace: normal;min-width:80px;';
        $cols[$ext]['label'] = 'Total Mechanic';
        $cols[$ext]['style'] = 'text-align:right;';
        return $cols;
    }

    public function loaddoclisting($config)
    {
        $filtersearch = "";
        if (isset($config['params']['search'])) {
            $searchfield = ['docno', 'code', 'client', 'description', 'clientname',  'vehicle', 'mileage'];
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
        }
        $fsearch = "";
        if ($filtersearch != "") {
            $fsearch = 'where 1=1 ' . $filtersearch;
        }
        $qry = "select trno,dateid,docno,code,description,client,clientname,vehicle,mileage,sum(labor) as labor,sum(stocks) as stocks from (
        select head.trno,head.docno,date(head.dateid) as dateid,jb.code,jb.description,client.client,client.clientname,cmake.carname as vehicle,'' as mileage,
        ifnull(sum(am.cost),0) as labor,
        ifnull((select sum(amt) from ptstock as s
        where s.trno = head.trno and s.jobline = jobs.line and s.taskline = am.line),0) as stocks
        from amjobs as jobs
        left join jobthead as jt on jt.line = jobs.jobid
        left join lahead as head on head.trno = jobs.trno
        left join client on client.client = head.client
        left join cmake on cmake.id=head.carid
        left join amtask as am on am.jobline = jobs.line
        left join jobtask as jb on jb.line = am.line
        where head.doc = 'AM'
        group by head.trno,head.docno,date(head.dateid),jb.code,jb.description,client.client,client.clientname,cmake.carname,jobs.line,am.line
        union all
        select head.trno,head.docno,date(head.dateid) as dateid,jb.code,jb.description,client.client,client.clientname,cmake.carname as vehicle,'' as mileage,
        ifnull(sum(am.cost),0) as labor,
        ifnull((select sum(amt) from ptstock as s
        where s.trno = head.trno and s.jobline = jobs.line and s.taskline = am.line),0) as stocks
        from hamjobs as jobs
        left join jobthead as jt on jt.line = jobs.jobid
        left join glhead as head on head.trno = jobs.trno
        left join client on client.clientid = head.clientid
        left join cmake on cmake.id=head.carid
        left join hamtask as am on am.jobline = jobs.line
        left join jobtask as jb on jb.line = am.line
        where head.doc = 'AM'
        group by head.trno,head.docno,date(head.dateid),jb.code,jb.description,client.client,client.clientname,cmake.carname,jobs.line,am.line
        ) as v $fsearch group by trno,dateid,docno,code,description,client,clientname,vehicle,mileage";

        $data = $this->coreFunctions->opentable($qry);

        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
    }

    public function createHeadbutton($config)
    {
        return [];
    }

    public function createTab($access, $config)
    {
        return [];
    }

    public function createtabbutton($config)
    {
        return [];
    }

    public function createHeadField($config)
    {
        return [];
    }
}// end class
