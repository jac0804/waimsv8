<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\modules\inventory\pc;
use App\Http\Classes\sqlquery;
use Exception;

use Datetime;
use Carbon\Carbon;

class assignuser
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;
    private $logger;
    private $sqlquery;

    public $modulename = "ASSIGN USER";
    public $gridname = 'inventory';
    private $fields = [];
    private $head = 'client';
    public $style = 'width:100%;max-width:60%;';
    public $issearchshow = false;
    public $showclosebtn = true;
    public $tablelogs = 'task_log';
    public $tablelogs_del = 'del_task_log';

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->coreFunctions = new coreFunctions;
        $this->companysetup = new companysetup;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
        $this->sqlquery = new sqlquery;
    }

    public function getAttrib()
    {
        $attrib = array('load' => 0);
        return $attrib;
    }

    public function createHeadField($config)
    {
        $fields = ['username', 'refresh'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'username.lookupclass', 'dylookupusers');
        data_set($col1, 'refresh.label', 'Save');
        return array('col1' => $col1);
    }

    public function paramsdata($config)
    {
        // var_dump($config['params']);
        $username = $config['params']['addedparams']['username'];
        $assignedid = $config['params']['addedparams']['assignedid'];
        $customerid = $config['params']['addedparams']['custid'];
        $checkerid = $config['params']['addedparams']['empid'];
        $notes = $config['params']['addedparams']['rem'];
        $trno = $config['params']['trno'];
        $userid = $config['params']['addedparams']['userid'];
        $catid = $config['params']['addedparams']['taskcatid'];
        return $this->coreFunctions->opentable("select  if('$username' != '', '$username', '') as username, '$assignedid' as assignedid,'$trno' as trno,
                                  '$customerid' as customerid,'$checkerid' as checkerid,'$notes' as notes,'$userid' as userid,'$catid' as catid");
    }


    public function data($config)
    {
        return [];
    }

    public function createTab($config)
    {
        $tab = [];
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
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
        // var_dump($config['params']);
        $datenow = $this->othersClass->getCurrentTimeStamp();
        $dateid = $this->othersClass->getCurrentDate();
        $clientid = $config['params']['clientid'];
        $assignedid = $config['params']['dataparams']['assignedid'];
        $customerid = $config['params']['dataparams']['customerid'];
        $checkerid = $config['params']['dataparams']['checkerid'];
        $createby = $config['params']['user'];
        $notes = $config['params']['dataparams']['notes'];
        $userid = $config['params']['dataparams']['userid'];
        $catid = $config['params']['dataparams']['catid'];

        if ($this->coreFunctions->execqry("update dailytask set assignedid='" . $assignedid . "' where trno=" . $clientid, "update")) {
            if ($clientid != 0) {
                $data = [
                    'clientid' => $customerid,
                    'systype' => 0,
                    'tasktype' => 2,
                    'rate' => 0,
                    'dateid' => $dateid,
                    'requestby' => $checkerid, //checker sa DY
                    'createdate' => $datenow,
                    'createby' => $createby, //user sa DY
                    'rem' => '',
                    'status' => 1, //open
                    'checkerid' => $userid
                ];
                $generatetm = $this->coreFunctions->insertGetId('tmhead', $data);
                if ($generatetm != 0) {
                    $data2 = [
                        'trno' => $generatetm,
                        'line' => 1,
                        'task' => '',
                        'userid' => $assignedid,
                        'startdate' => $datenow,
                        'encodeddate' => $datenow,
                        'encodedby' => $userid,
                        'title' => $notes,
                        'status' => 2, //
                        'acceptdate' => $datenow,
                        'taskcatid' => $catid

                    ];
                    $generatetmdetail = $this->coreFunctions->insertGetId('tmdetail', $data2);
                    $checktmdetail = $this->coreFunctions->getfieldvalue("tmdetail", "trno", "trno=? and line=1", [$generatetm]);
                    if ($checktmdetail != 0) {
                        $url = 'App\Http\Classes\modules\taskmonitoring\\' . 'tm';
                        $this->othersClass->insertUpdatePendingapp($generatetm, 1, 'TM', [], $url, $config, $assignedid, false, true); //create sa pendingapp 
                        $assigned = $this->coreFunctions->getfieldvalue("client", "clientname", "clientid=?", [$assignedid]);
                        $config['params']['doc'] = 'ENTRYTASK';
                        $this->logger->sbcmasterlog($generatetm, $config, ' Line: ' . $data2['line'] . ' , This task has been assigned to ' . $assigned);
                        $msg = 'User assigned; task monitoring document generated successfully.';
                    }
                }
            }


            return ['status' => true, 'msg' => $msg, 'closecustomform' => true, 'reloadhead' => true];
        }
        return ['status' => false, 'msg' => 'Error assigning user, Please try again.'];
        // return [];
    }
}
