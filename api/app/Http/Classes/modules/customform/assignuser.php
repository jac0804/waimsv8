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
        $fields = ['username', 'clientname', 'title', 'lblrem', 'task', 'refresh'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'username.lookupclass', 'dylookupusers');
        data_set($col1, 'clientname.type', 'lookup');
        data_set($col1, 'clientname.lookupclass', 'lookupopentask');
        data_set($col1, 'clientname.action', 'lookupopentask');
        data_set($col1, 'clientname.addedparams', ['dyclient']);
        data_set($col1, 'clientname.label', 'Company');
        data_set($col1, 'refresh.label', 'Save');
        data_set($col1, 'lblrem.label', 'Task Details');
        data_set($col1, 'title.label', 'Task Title');
        data_set($col1, 'title.readonly', false);
        return array('col1' => $col1);
    }

    public function paramsdata($config)
    {
        $username = $config['params']['addedparams']['username'];
        $assignedid = $config['params']['addedparams']['assignedid'];
        $customerid = $config['params']['addedparams']['custid'];
        $checkerid = $config['params']['addedparams']['empid'];
        $notes = $config['params']['addedparams']['rem'];
        $trno = $config['params']['trno'];
        $userid = $config['params']['addedparams']['userid'];
        $catid = $config['params']['addedparams']['taskcatid'];
        $reseller = $config['params']['addedparams']['reseller'];
        $dyclient = $config['params']['addedparams']['client'];
        $tasktrno = $config['params']['addedparams']['tasktrno'];
        // $companyname = $config['params']['addedparams']['clientname'];
        // $companyid = $config['params']['addedparams']['custid'];

        return $this->coreFunctions->opentable("select  if('$username' != '', '$username', '') as username, '$assignedid' as assignedid,'$trno' as trno,
                                  '$customerid' as customerid,'$checkerid' as checkerid,'$notes' as notes,'$userid' as userid,'$catid' as catid,'$tasktrno' as tasktrno,
                                  '" . $reseller . "' as reseller, '" . $dyclient . "' as dyclient,
                                   '' as clientname, 0 as tmtrno, '' as tmclientid, '' as tmreseller, '' as tmclient, '' as title, '' as task");
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
        $tasktrno = $config['params']['dataparams']['tasktrno'];
        $dyclient = $config['params']['dataparams']['dyclient']; //client code sa dailytask
        $reseller = $config['params']['dataparams']['reseller'];


        $tmtrno = $config['params']['dataparams']['tmtrno'];
        $tmclientid = $config['params']['dataparams']['tmclientid'];
        $tmclient = $config['params']['dataparams']['tmclient']; //client code sa task setup
        $tmreseller = $config['params']['dataparams']['tmreseller'];

        //may 19 2026
        $title = $config['params']['dataparams']['title'];
        $taskdetails = $config['params']['dataparams']['task'];

        $createdby = $config['params']['adminid'];
        $email = $this->coreFunctions->getfieldvalue("client", "email", "clientid=? ", [$createdby]);
        $msg = '';

        $blnHeadChecker = false;
        $blnBothHeads = false;

        $projecthead = $this->coreFunctions->getfieldvalue("client", "clientid", "client.clientid in (3863,3866,3867,3865,3868,3870) and clientid=?", [$checkerid], '', true);
        if ($projecthead != 0) {

            // check kung parehong project head yung checker at nagcreate ng task
            if ($this->othersClass->isSBCProjectHead($createdby)) {
                $blnBothHeads = true;
            }

            AssignedHere:
            if ($assignedid != 0) {
                // DETERMINE KUNG MAG-IINSERT OR CREATE
                $useExisting = false;

                if ($tmclientid != 0) {
                    // same client + same reseller
                    if ($dyclient == $tmclient && $reseller == $tmreseller) {
                        $useExisting = true;
                    }

                    // same client but different reseller 
                    //pag hindi same yung reseller nung nilookup at nung dailytask pero same ng company 
                    // search kung meron open task na same ng company at same din ng reseller
                    elseif ($dyclient == $tmclient) {
                        $searchsame = $this->coreFunctions->datareader(
                            "select h.trno as value  from tmhead as h 
                    left join client as cl on cl.clientid=h.clientid 
                    where h.reseller=? and cl.client=? and h.status=1 
                    order by h.dateid asc limit 1",
                            [$reseller, $dyclient]
                        );

                        if (!empty($searchsame)) {
                            $tmtrno = $searchsame;
                            $useExisting = true;
                        }
                    }
                }

                $updateassignedid = $this->coreFunctions->sbcupdate('dailytask', ['assignedid' => $assignedid], ['trno' => $clientid]);

                if ($updateassignedid != 1) {
                    return ['status' => false, 'msg' => 'User assigning error. Please refresh the page.', 'closecustomform' => true, 'reloadhead' => true];
                }

                if ($title == '' || $taskdetails == '') {
                    return ['status' => false, 'msg' => 'Task title or task details  cannot be blank.', 'closecustomform' => false, 'reloadhead' => false];
                }

                // NEW: resolve what requestby/checkerid WOULD be, then block if assigned user collides with either
                $resolvedRequestby = $checkerid;
                $resolvedCheckerid = $userid;
                if ($blnHeadChecker) {
                    $resolvedRequestby = $createdby;
                    $resolvedCheckerid = 0;
                } elseif ($blnBothHeads) {
                    $resolvedRequestby = $checkerid;
                    $resolvedCheckerid = $createdby;
                }

                if ($assignedid == $resolvedRequestby) {
                    return ['status' => false, 'msg' => 'The assigned user cannot be the same as the Request by.', 'closecustomform' => false, 'reloadhead' => false];
                }
                if ($assignedid == $resolvedCheckerid && $resolvedCheckerid != 0) {
                    return ['status' => false, 'msg' => 'The assigned user cannot be the same as the Checker.', 'closecustomform' => false, 'reloadhead' => false];
                }


                // IF EXISTING  INSERT DETAIL
                if ($useExisting) {
                    $getline = $this->coreFunctions->getfieldvalue("tmdetail", "line", "trno=? order by line desc", [$tmtrno], '', true);
                    $lines = $getline + 1;
                    $detaildata = [
                        'trno' => $tmtrno,
                        'line' => $lines,
                        'userid' => $assignedid,
                        'encodedby' => $email,
                        'encodeddate' => $datenow,
                        'title' => $title,
                        'status' => 2,
                        'taskcatid' => $catid,
                        'task' => $taskdetails
                    ];
                    $this->coreFunctions->insertGetId('tmdetail', $detaildata);
                    $tmline = $this->coreFunctions->getfieldvalue("tmdetail", "line", "trno=?", [$tmtrno], '', true);
                    if ($tmline != 0) {
                        $url = 'App\Http\Classes\modules\taskmonitoring\\tm';
                        $this->othersClass->insertUpdatePendingapp($tmtrno, $lines, 'TM', [], $url, $config, $assignedid, false, true);
                        $assigned = $this->coreFunctions->getfieldvalue("client", "clientname", "clientid=?", [$assignedid]);
                        $config['params']['doc'] = 'ENTRYTASK';
                        $this->logger->sbcmasterlog($tmtrno, $config, ' Line: ' . $tmline . ' , This task has been assigned to ' . $assigned);
                    }
                    $msg = 'User assigned; task monitoring detail generated successfully.';
                }

                //CREATE NEW
                else {
                    $data = [
                        'clientid' => $customerid,
                        'systype' => 0,
                        'tasktype' => 2,
                        'rate' => 0,
                        'dateid' => $dateid,
                        'requestby' => $checkerid,
                        'createdate' => $datenow,
                        'createby' => $createby,
                        'rem' => '',
                        'status' => 1,
                        'checkerid' => $userid,
                        'reseller' => $reseller
                    ];

                    if ($blnHeadChecker) {
                        $data['checkerid'] = 0;
                        $data['requestby'] = $createdby;
                    } elseif ($blnBothHeads) {
                        $data['requestby'] = $checkerid;  // stays the project head assigned as checker
                        $data['checkerid'] = $createdby;  // creator becomes the checker
                    }

                    $generatetm = $this->coreFunctions->insertGetId('tmhead', $data);

                    if ($generatetm != 0) {
                        $data2 = [
                            'trno' => $generatetm,
                            'line' => 1,
                            'userid' => $assignedid,
                            'startdate' => $datenow,
                            'encodeddate' => $datenow,
                            'encodedby' => $email,
                            'title' => $title,
                            'status' => 2,
                            'acceptdate' => $datenow,
                            'taskcatid' => $catid,
                            'task' => $taskdetails
                        ];

                        $this->coreFunctions->insertGetId('tmdetail', $data2);
                        $checktmdetail = $this->coreFunctions->getfieldvalue("tmdetail", "trno", "trno=? and line=1", [$generatetm]);

                        if ($checktmdetail != 0) {
                            $url = 'App\Http\Classes\modules\taskmonitoring\\tm';
                            $this->othersClass->insertUpdatePendingapp($generatetm, 1, 'TM', [], $url, $config, $assignedid, false, true);

                            $assigned = $this->coreFunctions->getfieldvalue("client", "clientname", "clientid=?", [$assignedid]);

                            $config['params']['doc'] = 'ENTRYTASK';
                            $this->logger->sbcmasterlog($generatetm, $config, ' Line: 1 , This task has been assigned to ' . $assigned);
                        }

                        $msg = 'User assigned; task monitoring document generated successfully.';
                    }
                }

                return ['status' => true, 'msg' => $msg, 'closecustomform' => true, 'reloadhead' => true];
            }
        } else {
            if ($this->othersClass->isSBCProjectHead($createdby)) {
                $blnHeadChecker = true;
                goto AssignedHere;
            }
            return ['status' => false, 'msg' => 'Please select the designated project head as checker.', 'closecustomform' => false, 'reloadhead' => false];
        }
    }
}
