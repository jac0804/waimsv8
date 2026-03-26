<?php

namespace App\Http\Classes\modules\hmsentry;

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

class roomtype
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'ROOM TYPE SETUP';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $sqlquery;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
    public $head = 'tblroomtype';
    public $stock = 'tblrates';
    public $prefix = '';

    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'masterfile_log';
    private $stockselect;

    private $fields = [
        'roomtype', 'inactive', 'category', 'additional', 'maxadult', 'beds', 'issmoking'
    ];
    private $except = ['client', 'clientid'];
    private $blnfields = ['inactive', 'issmoking'];
    private $acctg = [];
    public $showfilteroption = false;
    public $showfilter = false;
    public $showcreatebtn = true;
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
            'view' => 32,
            'edit' => 33,
            'new' => 34,
            'save' => 35,
            'change' => 36,
            'delete' => 37,
            'print' => 38
        );
        return $attrib;
    }

    public function createdoclisting($config)
    {
        $getcols = ['action', 'listroomtype', 'listcategory'];
        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        return $cols;
    }

    public function loaddoclisting($config)
    {
        $filtersearch = "";
        if (isset($config['params']['search'])) {
            $searchfield = ['id', 'roomtype', 'category'];
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
        }

        $qry = "select id as clientid, roomtype, category from tblroomtype 
        where 1=1 " . $filtersearch . "
        order by roomtype";

        $data = $this->coreFunctions->opentable($qry);
        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
    }

    public function createHeadbutton($config)
    {
        $btns = array(
            'load',
            'new',
            'save',
            'delete',
            'cancel',
            'print',
            'logs',
            'edit',
            'backlisting',
            'toggleup',
            'toggledown'
        );
        $buttons = $this->btnClass->create($btns);
        return $buttons;
    } // createHeadbutton

    public function createTab($access, $config)
    {
        $tab = [
            'tableentry' => ['action' => 'hmsentry', 'lookupclass' => 'entryroomlist', 'label' => 'RATES']

        ];
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['viewroomrates', 'addroom']; //'viewroomrates',
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function createHeadField($config)
    {
        $fields = ['client', 'vesselstatus'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, "client.label", "Room Type");
        data_set($col1, "client.type", "input");
        data_set($col1, "client.class", "csclient");
        data_set($col1, "vesselstatus.action", "lookuproomcategory");
        data_set($col1, "vesselstatus.label", "Category");
        data_set($col1, "vesselstatus.name", "category");

        $fields = ['additional', ['maxadult', 'beds']];
        $col2 = $this->fieldClass->create($fields);

        $fields = ['inactive', 'issmoking'];
        $col3 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
    }


    public function newclient($config)
    {
        $data = $this->resetdata();
        return  ['head' => $data, 'islocked' => false, 'isposted' => false, 'status' => true, 'isnew' => true, 'msg' => 'Ready for New Ledger'];
    }

    private function resetdata($client = '')
    {
        $data = [];
        $data[0]['clientid'] = 0;
        $data[0]['client'] = $client;
        $data[0]['category'] = 'DAILY';
        $data[0]['additional'] = '0.00';
        $data[0]['maxadult'] = 0;
        $data[0]['beds'] = 0;
        $data[0]['inactive'] = '0';
        $data[0]['issmoking'] = '0';
        return $data;
    }


    public function loadheaddata($config)
    {
        $doc = $config['params']['doc'];
        $clientid = $config['params']['clientid'];

        if ($clientid == 0) {
            $clientid = $this->coreFunctions->datareader("select id as value from " . $this->head . " where inactive=0 order by id desc limit 1");
            $config['params']['clientid'] = $clientid;
        }

        $qry = "select id as clientid, roomtype as client, rate1, maxadult, maxchild, maxinfant, beds, additional, issmoking, 
        category, rate2, createdate, createby, editdate, editby, bfast, inactive, asset, revenue, rem, typename from tblroomtype  
        where id = ? ";

        $head = $this->coreFunctions->opentable($qry, [$clientid]);
        if (!empty($head)) {
            foreach ($this->blnfields as $key => $value) {
                if ($head[0]->$value) {
                    $head[0]->$value = "1";
                } else
                    $head[0]->$value = "0";
            }
            $viewdate = $this->othersClass->getCurrentTimeStamp();
            $viewby = $config['params']['user'];
            // $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['clientid' => $clientid]);
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
        return "";
    }

    public function updatehead($config, $isupdate)
    {
        $head = $config['params']['head'];
        $data = [];
        if ($isupdate) {
            unset($this->fields['client']);
        } else {
            $data['roomtype'] = strtoupper($head['client']);
        }
        $clientid = 0;
        $msg = '';
        foreach ($this->fields as $key) {

            if (isset($head[$key])) {
                $data[$key] = $head[$key];
                if (!in_array($key, $this->except)) {
                    $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
                } //end if 
            }
        }
        $data['createdate'] = date('Y-m-d');
        $data['createby'] = $config['params']['user'];

        if ($isupdate) {
            $data['editdate'] = date('Y-m-d');
            $data['editby'] = $config['params']['user'];
            $this->coreFunctions->sbcupdate($this->head, $data, ['id' => $head['clientid']]);
            $clientid = $head['clientid'];
        } else {
            $clientid = $this->coreFunctions->insertGetId($this->head, $data);
            $this->logger->sbcmasterlog($clientid, $config, ' CREATE ROOM TYPE - ' . $data['roomtype']);

        }
        return ['status' => $msg == '' ? true : false, 'msg' => $msg, 'clientid' => $clientid];
        
    } // end function   


    public function deletetrans($config)
    {
        $clientid = $config['params']['clientid'];
        $roomtype = $this->coreFunctions->datareader("select roomtype as value from " . $this->head . " where id=?", [$clientid]);
        $this->coreFunctions->execqry('delete from ' . $this->head . ' where id=?', 'delete', [$clientid]);
        $this->logger->sbcwritelog($clientid, $config, 'DELETE', $roomtype);
        return ['clientid' => $clientid, 'status' => true, 'msg' => 'Successfully deleted.'];
    } //end function


    public function reportsetup($config)
    {
        

        $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
        $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);

        $modulename = $this->modulename;
        $data = [];
        $style = 'width:500px;max-width:500px;';

        return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
    }

    public function reportdata($config)
    {
        // $data = $this->report_default_query($config['params']['dataid']);
        // $str = $this->reportplotting($config, $data);

        $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }
} //end class
