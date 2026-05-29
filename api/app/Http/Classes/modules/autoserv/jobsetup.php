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

class jobsetup
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'JOB SETUP';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $sqlquery;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
    public $head = 'jobthead';
    public $detail = '';
    public $prefix = 'JS';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = '';


    private $fields = [
        'line',
        'jobtitle',
        'docno'
    ];
    private $except = ['clientid', 'client'];
    private $blnfields = [];
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
            'load' => 5830,
            'view' => 5831,
            'edit' => 5832,
            'new' => 5833,
            'save' => 5834,
            'delete' => 5835
        );
        return $attrib;
    }

    public function createdoclisting($config)
    {
        $getcols = ['action', 'listdocument', 'jobtitle'];
        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[1]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $cols[2]['style'] = 'width:500px;whiteSpace: normal;min-width:500px;';
        $cols[1]['label'] = 'Code';
        $cols[2]['label'] = 'Job Description';
        return $cols;
    }

    public function loaddoclisting($config)
    {
        $filtersearch = "";
        if (isset($config['params']['search'])) {
            $searchfield = ['line', 'docno', 'jobtitle'];
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
        }
        $qry = "select line as clientid, docno, jobtitle from jobthead 
        where 1=1 " . $filtersearch . " and left(docno,2)='JS'
        order by docno";
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
            'edit',
            'logs',
            'backlisting',
            'toggleup',
            'toggledown'
        );
        $buttons = $this->btnClass->create($btns);
        return $buttons;
    } // createHeadbutton

    public function createTab($access, $config) {}

    public function createtab2($access, $config)
    {
        $tab = [];
        $return = [];
        return $return;
    }

    public function createtabbutton($config)
    {

        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);

        return $obj;
    }

    public function createHeadField($config)
    {
        $fields = [
            'client',
            'jobtitle'
        ];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'client.class', 'csclient sbccsenablealways');
        data_set($col1, 'client.label', 'Code');
        data_set($col1, 'client.action', 'lookupledger');
        data_set($col1, 'client.lookupclass', 'lookupledgerjobtitle');

        data_set($col1, 'jobtitle.readonly', false);
        data_set($col1, 'jobtitle.required', true);
        data_set($col1, 'jobtitle.type', 'ctextarea');
        data_set($col1, 'jobtitle.label', 'Job Description');

        return array('col1' => $col1);
    }

    public function newclient($config)
    {
        $data = $this->resetdata($config['newclient']);
        return  ['head' => $data, 'islocked' => false, 'isposted' => false, 'status' => true, 'isnew' => true, 'msg' => 'Ready for New Job Setup.'];
    }

    private function resetdata($client = '')
    {
        $data = [];
        $data[0]['clientid'] = 0;
        $data[0]['client'] = $client;
        $data[0]['docno'] = '';
        $data[0]['jobtitle'] = '';

        return $data;
    }


    public function loadheaddata($config)
    {
        $doc = $config['params']['doc'];
        $clientid = $this->othersClass->val($config['params']['clientid']);
        $center = $config['params']['center'];
        $fields = "line as clientid, docno as client";
        foreach ($this->fields as $key => $value) {
            $fields = $fields . ',' . $value;
        }

        if ($clientid == 0) $clientid = $this->getlastclient();

        $qryselect = "select " . $fields;
        $qry = $qryselect . " from jobthead
        where line = ? ";

        $head = $this->coreFunctions->opentable($qry, [$clientid]);
        if (!empty($head)) {
            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }

            return  ['reloadtableentry' => true, 'head' => $head, 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => false, 'qq' => $config['params']['clientid']];
        } else {
            $head = $this->resetdata();

            return ['reloadtableentry' => true, 'status' => false, 'isnew' => true, 'head' => $head, 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified...'];
        }
    }

    public function updatehead($config, $isupdate)
    {
        $head = $config['params']['head'];
        $center = $config['params']['center'];
        $data = [];
        if ($isupdate) {
            unset($this->fields['docno']);
        } else {
            $data['docno'] = $head['client'];
            $head['docno'] = $head['client'];
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
        if ($isupdate) {
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['editby'] = $config['params']['user'];
            $this->coreFunctions->sbcupdate($this->head, $data, ['line' => $head['clientid']]);
            $clientid = $head['clientid'];
        } else {
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby']   = $config['params']['user'];
            $clientid = $this->coreFunctions->insertGetId($this->head, $data);
        }

        return ['status' => $msg == '' ? true : false, 'msg' => $msg, 'clientid' => $clientid];
    } // end function

    public function getlastclient($pref = '')
    {
        $length = strlen($pref);
        if ($length == 0) {
            $last_id = $this->coreFunctions->datareader("select docno as value from " . $this->head . " order by line DESC LIMIT 1");
        } else {
            $last_id = $this->coreFunctions->datareader("select docno as value from " . $this->head . " where left(docno,?)=? order by line DESC LIMIT 1", [$length, $pref]);
        }
        return $last_id;
    }

    public function deletetrans($config)
    {
        $clientid = $config['params']['clientid'];
        $docn = $this->coreFunctions->getfieldvalue('jobthead', 'docno', 'line=?', [$clientid]);
        $qry1 = "select job as value from personreq where job=? limit 1";
        $count = $this->coreFunctions->datareader($qry1, [$docn]);
        $qry1 = "select job as value from hpersonreq where job=? limit 1 ";
        $count1 = $this->coreFunctions->datareader($qry1, [$docn]);
        $qry1 = "select emptitle as value from joboffer where emptitle=? limit 1";
        $count2 = $this->coreFunctions->datareader($qry1, [$docn]);
        $qry1 = "select emptitle as value from hjoboffer where emptitle=? limit 1";
        $count3 = $this->coreFunctions->datareader($qry1, [$docn]);


        if ($count != '' || $count1 != '' || $count2 != '' || $count3 != '') {
            return ['clientid' => $clientid, 'status' => false, 'msg' => 'Already have transaction...'];
        }

        $this->coreFunctions->execqry('delete from ' . $this->head . ' where line=?', 'delete', [$clientid]);
        $this->coreFunctions->execqry('delete from jobtskills where trno=?', 'delete', [$clientid]);
        $this->coreFunctions->execqry('delete from jobtdesc where trno=?', 'delete', [$clientid]);

        return ['clientid' => 0, 'status' => true, 'msg' => 'Successfully deleted.'];
    } //end function

} //end class
