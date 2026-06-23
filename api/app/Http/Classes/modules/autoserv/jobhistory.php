<?php

namespace App\Http\Classes\modules\autoserv;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

class jobhistory
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'JOB HISTORY';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $sqlquery;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
    public $head = 'lahead';
    public $hhead = 'glehad';
    public $stock = 'lastock';
    public $hstock = 'glstock';
    public $prefix = '';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = '';


    private $fields = [];

    private $blnfields = [];
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
            'load' => 5910,
            'view' => 5911,
        );
        return $attrib;
    }

    public function createdoclisting($config)
    {
        $getcols = ['listdocument', 'dateid', 'jobcode', 'jobtitle', 'client', 'clientname', 'vehicle', 'mileage', 'labor', 'amt', 'ext', 'rem', 'carem'];

        foreach ($getcols as $key => $value) {
            $$value = $key;
        }
        $stockbuttons = [];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[$listdocument]['label'] = 'Invoice #';
        $cols[$dateid]['label'] = 'Date';
        $cols[$jobcode]['label'] = 'Job Code';
        $cols[$jobcode]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
        $cols[$jobtitle]['label'] = 'Job Description';
        $cols[$jobtitle]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
        $cols[$client]['label'] = 'Code';
        $cols[$client]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
        $cols[$clientname]['label'] = 'Customer Name';
        $cols[$clientname]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
        $cols[$carem]['label'] = 'Recommendation';
        $cols[$amt]['style'] = "width:120px;whiteSpace: normal;min-width:120px;text-align:right;";
        $cols[$amt]['label'] = "Stocks";
        $cols[$labor]['style'] = "width:120px;whiteSpace: normal;min-width:120px;text-align:right;";
        $cols[$ext]['style'] = "width:120px;whiteSpace: normal;min-width:120px;text-align:right;";
        $cols[$rem]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
        $cols[$rem]['label'] = "Customer Notes";
        $cols[$rem]['label'] = "Recommendation";

        return $cols;
    }

    public function loaddoclisting($config)
    {
        $filtersearch = "";
        if (isset($config['params']['search'])) {
            $searchfield = ['docno', 'jobcode', 'jobtitle', 'client', 'clientname', 'vehicle', 'mileage'];
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
        }
        $fsearch = "";
        if ($filtersearch != "") {
            $fsearch = 'where 1=1 ' . $filtersearch;
        }
        $qry = "select trno,dateid,docno,jobcode,jobtitle,client,clientname,vehicle,mileage,format(sum(labor),2) as labor,format(sum(stocks),2) as amt from (
            select head.trno,head.docno,date(head.dateid) as dateid,jt.docno as jobcode,jt.jobtitle,client.client,client.clientname,cmake.carname as vehicle,
            cvh.mileage,
            ifnull(sum(am.cost),0) as labor,
            ifnull((select sum(amt) from lastock as s 
            where s.trno = head.trno and s.jobline = jobs.line and s.taskline = am.line),0) as stocks
            from amjobs as jobs 
            left join jobthead as jt on jt.line = jobs.jobid
            left join lahead as head on head.trno = jobs.trno
            left join client on client.client = head.client
            left join cmake on cmake.id=head.carid
            left join amtask as am on am.jobline = jobs.line
            left join cvehicle as cvh on cvh.clientid = client.clientid
            where head.doc = 'AM' 
            group by head.trno,head.docno,date(head.dateid),jt.docno,jt.jobtitle,client.client,client.clientname,cmake.carname,cvh.mileage,jobs.line,am.line
            union all 
            select head.trno,head.docno,date(head.dateid) as dateid,jt.docno as jobcode,jt.jobtitle,client.client,client.clientname,cmake.carname as vehicle,
            cvh.mileage,
            ifnull(sum(am.cost),0) as labor,
            ifnull(
            (select sum(amt) from glstock as s 
            where s.trno = head.trno and s.jobline = jobs.line and s.taskline = am.line),0) as stocks
            from hamjobs as jobs 
            left join jobthead as jt on jt.line = jobs.jobid
            left join glhead as head on head.trno = jobs.trno
            left join client on client.clientid = head.clientid
            left join cmake on cmake.id=head.carid
            left join hamtask as am on am.jobline = jobs.line
            left join cvehicle as cvh on cvh.clientid = client.clientid
            where head.doc = 'AM' 
            group by head.trno,head.docno,date(head.dateid),jt.docno,jt.jobtitle,client.client,client.clientname,cmake.carname,cvh.mileage,jobs.line,am.line
            ) as  v $fsearch group by trno,dateid,docno,jobcode,jobtitle,client,clientname,vehicle,mileage order by dateid";
        $data = $this->coreFunctions->opentable($qry);

        foreach ($data as $key => $value) {
            $value->ext = number_format($value->labor + $value->amt, 2);
        }

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
} //end class
