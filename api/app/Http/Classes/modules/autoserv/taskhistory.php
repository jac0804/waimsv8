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
        $cols[$code]['label'] = 'Task Code';
        $cols[$description]['label'] = 'Task Description';
        $cols[$client]['label'] = 'Code';
        $cols[$clientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
        $cols[$clientname]['label'] = 'Customer Name';
        $cols[$clientname]['type'] = 'label';
        $cols[$dateid]['style'] = 'width:80px;whiteSpace: normal;min-width:80px;';
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
            $searchfield = ['docno', 'code', 'client', 'clientname',  'vehicle'];
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
        }
        $qry = "SELECT trno,dateid,jobcode,jobtitle,client,clientname,vehicle,mileage,sum(labor) as labor,sum(stocks) as stocks FROM (
select head.trno,head.docno,date(head.dateid) as dateid,jt.docno as jobcode,jt.jobtitle,client.client,client.clientname,cmake.carname as vehicle,'' as mileage,
ifnull(sum(am.cost),0) as labor,
ifnull((select SUM(amt) FROM lastock AS s 
WHERE s.trno = head.trno AND s.jobline = jobs.line AND s.taskline = am.line),0) AS stocks
FROM amjobs as jobs 
left JOIN jobthead AS jt ON jt.line = jobs.jobid
left JOIN lahead AS head ON head.trno = jobs.trno
left JOIN client ON client.client = head.client
left join cmake on cmake.id=head.carid
LEFT JOIN amtask as am ON am.jobline = jobs.line
WHERE head.doc = 'AM'
GROUP BY head.trno,head.docno,date(head.dateid),jt.docno,jt.jobtitle,client.client,client.clientname,cmake.carname,jobs.line,am.line
UNION ALL 
select head.trno,head.docno,date(head.dateid) as dateid,jt.docno as jobcode,jt.jobtitle,client.client,client.clientname,cmake.carname as vehicle,'' as mileage,
ifnull(sum(am.cost),0) as labor,
ifnull((select SUM(amt) FROM lastock AS s 
WHERE s.trno = head.trno AND s.jobline = jobs.line AND s.taskline = am.line),0) AS stocks
FROM hamjobs as jobs 
left JOIN jobthead AS jt ON jt.line = jobs.jobid
left JOIN glhead AS head ON head.trno = jobs.trno
left JOIN client ON client.clientid = head.clientid
left join cmake on cmake.id=head.carid
LEFT JOIN hamtask as am ON am.jobline = jobs.line
where head.doc = 'AM'
group BY head.trno,head.docno,date(head.dateid),jt.docno,jt.jobtitle,client.client,client.clientname,cmake.carname,jobs.line,am.line
) AS v group BY trno,dateid,jobcode,jobtitle,client,clientname,vehicle,mileage";
        $data = $this->coreFunctions->opentable($qry);

        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
    }
}// end class
