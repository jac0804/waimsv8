<?php

namespace App\Http\Classes\modules\hris;

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

class empndahistory
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'EMPLOYMENT NDA HISTORY';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $sqlquery;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
    public $head = 'eschange';
    public $hhead = 'heschange';
    public $tablenum = 'hrisnum';
    public $prefix = '';
    public $tablelogs = '';
    public $tablelogs_del = '';
    private $stockselect;

    private $fields = [
        'docno', 'empid', 'dateid', 'jobtitle', 'deptid', 'hired'
    ];
    private $except = ['clientid', 'client'];
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
            'view' => 1344,
            'load' => 1448,

        );
        return $attrib;
    }

    public function createdoclisting($config)
    {
        $getcols = ['action', 'empcode', 'empname', 'jobtitle', 'deptname', 'hired'];

        foreach ($getcols as $key => $value) {
            $$value = $key;
        }

        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[$empcode]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $cols[$empname]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;';
        $cols[$jobtitle]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
        $cols[$deptname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
        $cols[$hired]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$hired]['align'] = 'text-left';
        return $cols;
    }

    public function loaddoclisting($config)
    {
        $viewaccess = $this->othersClass->checkAccess($config['params']['user'], 5436);
        $id = $config['params']['adminid'];
        $date1 = date('Y-m-d', strtotime($config['params']['date1']));
        $date2 = date('Y-m-d', strtotime($config['params']['date2']));
        $itemfilter = $config['params']['itemfilter'];
        $doc = $config['params']['doc'];
        $center = $config['params']['center'];
        $condition = '';
        $searchfilter = $config['params']['search'];
        $filtersearch = "";
        if (isset($config['params']['search'])) {
            $searchfield = ['jt.docno', 'c.clientname', 'e.empid', 'client.client', 'c.client', 'e.empfirst', 'e.empmiddle', 'e.emplast', 'jt.jobtitle'];
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
        }


        if ($id != 0) {
            if ($viewaccess == '0') {
                $condition = " and e.empid=$id ";
            }
        }

        $qry = "
          select e.empid as clientid, client.client as empcode,
            concat(e.empfirst,' ',e.empmiddle, ' ', e.emplast) as empname,
            jt.docno as jobcode, jt.jobtitle,c.client as deptcode, c.clientname as deptname,
            jt.line as jobid,
            date(e.hired) as hired
          from employee as e
          left join client on client.clientid=e.empid
          left join app as a on e.aplid=a.empid
          left join client as c on c.clientid=e.deptid
          left join jobthead as jt on jt.line=e.jobid
          where 1=1 and e.isactive = 1 $condition " . $filtersearch . " 
          ";
        $data = $this->coreFunctions->opentable($qry);

        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
    }

    public function createHeadbutton($config)
    {
        $btns = array(
            'load',
            'backlisting',
            'toggleup',
            'toggledown'
        );
        $buttons = $this->btnClass->create($btns);
        return $buttons;
    } // createHeadbutton

    public function createTab($access, $config)
    {
        $tab = [];

        if ($config['params']['companyid'] == 58) { //cdo
            $tab = ['tableentry' => ['action' => 'hrisentry', 'lookupclass' => 'viewndahistorydetails', 'label' => 'DETAILS']];
        }

        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        return $obj;
    }

    public function createtab2($access, $config)
    {
        $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entryhrisnumpicture', 'label' => 'Attachment', 'access' => 'view']];
        $obj = $this->tabClass->createtab($tab, []);
        $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];
        return $return;
    }

    public function createtabbutton($config)
    {
        $tbuttons = [];
        if ($config['params']['companyid'] != 58) { //cdo
            $tbuttons = ['viewincidentmemo', 'viewnoticetoexplain', 'viewnoticedisciplinary'];
        }

        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function createHeadField($config)
    {
        $fields = ['client', 'empname', 'start', 'deptname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'client.class', 'csclient sbccsenablealways');
        data_set($col1, 'client.label', 'Code');
        data_set($col1, 'client.action', 'lookupledger');
        data_set($col1, 'client.lookupclass', 'lookupledgerempndahistory');

        data_set($col1, 'empname.type', 'input');
        data_set($col1, 'empname.readonly', true);

        data_set($col1, 'deptname.type', 'input');
        data_set($col1, 'deptname.readonly', true);
        data_set($col1, 'deptname.label', 'Department');

        data_set($col1, 'start.name', 'hired');
        data_set($col1, 'start.label', 'Date Hired');

        return array('col1' => $col1);
    }

    public function newclient($config)
    {
        $data = $this->resetdata($config['newclient']);
        return  ['head' => $data, 'islocked' => false, 'isposted' => false, 'status' => true, 'isnew' => true, 'msg' => 'Ready for New Ledger'];
    }

    private function resetdata($client = '')
    {
        $data = [];
        $data[0]['clientid'] = 0;
        $data[0]['client'] = $client;
        $data[0]['trno'] = '';
        $data[0]['docno'] = '';
        $data[0]['empid'] = '';
        $data[0]['dateid'] = '';
        $data[0]['jobtitle'] = '';

        return $data;
    }


    public function loadheaddata($config)
    {
        $doc = $config['params']['doc'];
        $clientid = $config['params']['clientid'];
        $center = $config['params']['center'];

        $qry = "
          select e.empid as clientid, client.client as client,
            concat(e.empfirst,' ',e.empmiddle, ' ', e.emplast) as empname,
            jt.docno as jobcode, jt.jobtitle,c.client as deptcode, c.clientname as deptname,
            jt.line as jobid,
            date(e.hired) as hired
          from employee as e
          left join client on client.clientid=e.empid
          left join app as a on e.aplid=a.empid
          left join client as c on c.clientid=e.deptid
          left join jobthead as jt on a.jobcode=jt.docno
          where e.empid = ?
        ";

        $head = $this->coreFunctions->opentable($qry, [$clientid]);
        if (!empty($head)) {
            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }
            return  ['head' => $head, 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => false, 'qq' => $config['params']['clientid']];
        } else {
            $head = $this->resetdata();
            return ['status' => false, 'isnew' => true, 'head' => $head, 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified...'];
        }
    }

    public function getlastclient($pref)
    {
        return '';
    }
} //end class
