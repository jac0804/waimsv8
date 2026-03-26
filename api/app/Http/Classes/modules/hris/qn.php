<?php

namespace App\Http\Classes\modules\hris;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

class qn
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'QUESTIONAIRE SETUP';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $sqlquery;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
    public $head = 'qnhead';
    public $stock = 'qnstock';
    public $prefix = 'QN';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = '';
    private $stockselect;

    private $fields = ['qid', 'docno', 'rem', 'instructions', 'qtype', 'runtime', 'gp', 'startdate'];

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
            'load' => 5189,
            'view' => 5190,
            'new' => 5192,
            'save' => 5193,
            'delete' => 5194,
            'print' => 5195,
            'edit' => 5191,
        );
        return $attrib;
    }

    public function createdoclisting($config)
    {

        $getcols = ['action', 'docno', 'dateid', 'qtype', 'rem', 'runtime'];

        foreach ($getcols as $key => $value) {
            $$value = $key;
        }

        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[$docno]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $cols[$qtype]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;';
        $cols[$rem]['style'] = 'width:500px;whiteSpace: normal;min-width:500px;';
        return $cols;
    }

    public function loaddoclisting($config)
    {
        $qry = 'select qid, qid as clientid, docno as client, docno, rem, qtype, date(startdate) as dateid, runtime from qnhead order by docno';
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
    }

    public function createTab($access, $config)
    {
        // $column = ['action', 'section', 'question', 'points', 'a', 'b', 'c', 'd', 'e', 'ans', 'answord'];
        // $tab = [$this->gridname => [
        //     'gridcolumns' => $column
        // ]];
        // $stockbuttons = ['delete'];
        // $obj = $this->tabClass->createtab($tab, $stockbuttons);
        // $obj[0][$this->gridname]['descriptionrow'] = [];
        // $obj[0][$this->gridname]['showtotal'] = true;
        // $obj[0][$this->gridname]['label'] = 'QUESTION LIST';
        // $obj[0][$this->gridname]['totalfield'] = '';

        $tab = [
            'tableentry' => ['action' => 'hrisentry', 'lookupclass' => 'viewquestions', 'label' => 'QUESTION LIST'],
            'tableentry2' => ['action' => 'hrisentry', 'lookupclass' => 'entryexaminees', 'label' => 'APPLICANT EXAMINEES'],
        ];
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['addquestion'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function createHeadField($config)
    {
        $fields = ['client', 'qtype', 'rem'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'client.class', 'csclient sbccsreadonly');
        data_set($col1, 'client.label', 'Reference No');
        data_set($col1, 'client.type', 'input');
        data_set($col1, 'rem.label', 'Remarks');
        data_set($col1, 'qtype.required', true);

        $fields = ['startdate', ['gp', 'runtime'], 'instructions'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'instructions.type', 'ctextarea');
        data_set($col2, 'instructions.class', 'scinstructions');
        data_set($col2, 'instructions.readonly', false);

        $fields = [];
        $col3 = $this->fieldClass->create($fields);

        $fields = ['objtype'];
        $col4 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
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
        $data[0]['rem'] = '';
        $data[0]['qtype'] = '';
        $data[0]['instructions'] = '';
        $data[0]['objtype'] = 0;
        $data[0]['gp'] = 0;
        $data[0]['runtime'] = 0;
        $data[0]['stardate'] = $this->othersClass->getCurrentDate();
        return $data;
    }

    public function getlastclient($pref = '')
    {
        $length = strlen($pref);
        if ($length == 0) {
            $last_id = $this->coreFunctions->datareader("select docno as value from " . $this->head . " order by qid DESC LIMIT 1");
        } else {
            $last_id = $this->coreFunctions->datareader("select docno as value from " . $this->head . " where left(docno,?)=? order by qid DESC LIMIT 1", [$length, $pref]);
        }
        return $last_id;
    }

    public function loadheaddata($config)
    {
        $clientid = $config['params']['clientid'];
        $qry = "
        select qid, qid as clientid, docno as client, docno, rem , instructions, qtype, startdate, gp, runtime, 0 as objtype
        from qnhead where qid = ?";

        $head = $this->coreFunctions->opentable($qry, [$clientid]);
        if (!empty($head)) {
            $stock = $this->openstock($clientid, $config);
            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }

            return  ['head' => $head, 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => false, 'qq' => $config['params']['clientid'], 'griddata' => ['inventory' => $stock], 'reloadtableentry' => true];
        } else {
            $head = $this->resetdata();
            return ['status' => false, 'isnew' => true, 'head' => $head, 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified...', 'reloadtableentry' => true];
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
            if (array_key_exists($key, $head)) {
                $data[$key] = $head[$key];
                if (!in_array($key, $this->except)) {
                    $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
                } //end if 
            }
        }
        if ($isupdate) {
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['editby'] = $config['params']['user'];
            $this->coreFunctions->sbcupdate($this->head, $data, ['qid' => $head['clientid']]);
            $clientid = $head['clientid'];
        } else {
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];
            $clientid = $this->coreFunctions->insertGetId($this->head, $data);
        }

        $stock = $this->openstock($clientid, $config);
        return ['status' => $msg == '' ? true : false, 'msg' => $msg, 'clientid' => $clientid, 'griddata' => ['inventory' => $stock]];
    } // end function

    public function openstock($trno, $config)
    {
        $qry = 'select qid, line, sortline, section, question, a, b, c, d, e, ans, answord, points from qnstock where qid=? order by section, sortline, line';
        return $this->coreFunctions->opentable($qry, [$trno]);
    }

    public function deletetrans($config)
    {
        $clientid = $config['params']['clientid'];

        $this->coreFunctions->execqry('delete from ' . $this->head . ' where qid=?', 'delete', [$clientid]);
        $this->coreFunctions->execqry('delete from ' . $this->stock . ' where qid=?', 'delete', [$clientid]);

        return ['clientid' => 0, 'status' => true, 'msg' => 'Successfully deleted.'];
    } //end function
}
