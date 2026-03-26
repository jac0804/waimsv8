<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

use Datetime;

class updateremrevision
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;
    private $logger;

    public $modulename = 'REVISION';
    public $gridname = 'inventory';
    private $fields = [];
    public $tablenum = 'cntnum';
    private $table = 'vrhead';
    private $htable = 'hvrhead';

    public $tablelogs = 'transnum_log';

    public $style = 'width:100%;max-width:70%;';
    public $issearchshow = true;
    public $showclosebtn = true;

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->coreFunctions = new coreFunctions;
        $this->companysetup = new companysetup;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
    }

    public function getAttrib()
    {
        $attrib = array('load' => 0);
        return $attrib;
    }

    public function createHeadField($config)
    {
        $trno = $config['params']['trno'];
        $doc = $config['params']['doc'];
        $isposted = $this->othersClass->isposted2($trno, "cntnum");

        if ($doc == 'PRLISTING') {
            $this->modulename = 'UPDATE STATUS';
            $fields = ['statname', 'subcategorystat', 'requestorstat', 'deadline', 'rem', 'prref', 'refresh'];
        } else {
            $fields = ['rem', 'refresh'];
        }


        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'refresh.label', 'ADD REMARKS');
        data_set($col1, 'rem.readonly', false);

        if ($doc == 'PRLISTING') {
            data_set($col1, 'statname.label', 'Status');
            data_set($col1, 'statname.lookupclass', 'statitemreqm');
            data_set($col1, 'deadline.readonly', false);
            data_set($col1, 'prref.readonly', false);
            data_set($col1, 'refresh.label', 'UPDATE');
        }

        $fields = [];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' =>  $col2);
    }

    public function paramsdata($config)
    {
        return $this->getheaddata($config);
    }

    public function getheaddata($config)
    {
        $doc = $config['params']['doc'];

        if ($doc == 'PRLISTING') {
            $trno = isset($config['params']['addedparams']['trno']) ? $config['params']['addedparams']['trno'] : '';
            $lines = isset($config['params']['addedparams']['line']) ? $config['params']['addedparams']['line'] : '';

            $select = "select  '' as  rem," . $trno . " as trno, " . $lines . " as line, 
               '' as statname, '' as subcategorystat,'' as deadline, '' as  prref, '' as requestorstat";
        } else {
            $trno = $config['params']['trno'];
            $select = "select '' as rem, " . $trno . " as trno";
        }
        $data = $this->coreFunctions->opentable($select);


        return $data;
    }


    public function data()
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

        $trno = $config['params']['dataparams']['trno'];
        $rem = $config['params']['dataparams']['rem'];
        if ($rem == '') {
            return ['status' => false, 'msg' => 'Please input valid remarks', 'data' => []];
        }

        $doc = $config['params']['doc'];

        switch ($doc) {
            case 'PRLISTING':
                $statname = 0;
                $subcatstat = 0;
                $requestorstat = 0;

                if ($config['params']['dataparams']['statname'] != "") {
                    $statname = $config['params']['dataparams']['statid'];
                }
                if ($config['params']['dataparams']['subcategorystat'] != "") {
                    $subcatstat = $config['params']['dataparams']['substatid'];
                }

                if ($config['params']['dataparams']['requestorstat'] != "") {
                    $requestorstat = $config['params']['dataparams']['requestorstatid'];
                }

                $line = $config['params']['dataparams']['line'];
                $data = [
                    'trno' => $trno,
                    'reqline' => $line,
                    'rem' => $rem,
                    'createby' => $config['params']['user'],
                    'createdate' => $this->othersClass->getCurrentTimeStamp(),
                    'remtype' => 0,
                    'reqstat' => $statname,
                    'reqstat2' => $subcatstat,
                    'reqstat3' => $requestorstat,
                    'prref' => $config['params']['dataparams']['prref'],
                    'deadline2' => $config['params']['dataparams']['deadline']
                ];

                $datastock = [
                    'trno' => $trno,
                    'rem' => $rem,
                    'editby' => $config['params']['user'],
                    'editdate' => $this->othersClass->getCurrentTimeStamp(),
                    'reqstat' => $statname,
                    'reqstat2' => $subcatstat,
                    'reqstat3' => $requestorstat,
                    'deadline2' => $config['params']['dataparams']['deadline']
                ];

                foreach ($data as $key => $value) {
                    $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
                }

                foreach ($datastock as $key => $value) {
                    $datastock[$key] = $this->othersClass->sanitizekeyfield($key, $datastock[$key]);
                }

                $update = $this->coreFunctions->sbcupdate('hprstock', $datastock, ['trno' => $trno, 'line' => $line]);
                if ($update == 1) {
                    $this->coreFunctions->sbcinsert("headprrem", $data);
                }
                break;

            case 'OQ':
                $data = [
                    'trno' => $trno,
                    'rem' => $rem,
                    'createby' => $config['params']['user'],
                    'createdate' => $this->othersClass->getCurrentTimeStamp(),
                    'remtype' => 1
                ];

                $statid = 16;
                $this->coreFunctions->sbcinsert('headrem', $data);
                $config['params']['trno'] = $trno;
                $laststatid = $this->othersClass->getstatid($config, "transnum");
                //Status - 0 -> 10(for approval) -> 36(approved) -> 47(for oracle receiving) -> 46(for so) -> 39(for posting)
                switch ($laststatid) {
                    case 39:
                        $statid = 46;
                        break;
                    case 46:
                        // $statid = 47;
                        $statid = 36;
                        break;
                    case 47:
                        $statid = 36;
                        break;
                    case 36:
                        $statid = 10;
                        break;
                    case 10:
                        $statid = 0;
                        break;
                }

                // if ($this->othersClass->getstatid($config, "transnum") == 39) {
                //     $newstat = 36;
                //     $type = $this->coreFunctions->datareader("select inspo as value from headinfotrans where trno=?", [$trno]);
                //     if ($type == 'FOR PR/PO') {
                //         $newstat = 37;
                //     }
                //     $this->coreFunctions->execqry("update transnum set statid=" . $newstat . " where trno=?", 'update', [$trno]);
                // } else {
                //     $this->coreFunctions->execqry("update transnum set statid=16 where trno=?", 'update', [$trno]);
                // }

                $this->coreFunctions->execqry("update transnum set statid=" . $statid . " where trno=?", 'update', [$trno]);
                $this->coreFunctions->execqry("update oqhead set lockuser='', lockdate=null where trno=?", 'update', [$trno]);
                $this->coreFunctions->sbcupdate('headinfotrans', ['instructions' => $statid == 0 ? '' : 'For Revision'], ['trno' => $trno]);

                $status = $this->coreFunctions->getfieldvalue('trxstatus', 'status', 'line=?', [$statid]);
                $this->logger->sbcstatlog($config['params']['trno'], $config, 'HEAD', 'FOR REVISION ->' . $status, 'transnum_stat');
                // $this->logger->sbcwritelog($config['params']['trno'], $config, 'HEAD', 'FOR REVISION', 'transnum_log');
                break;

            case 'VL':
                $data = [
                    'trno' => $trno,
                    'rem' => $rem,
                    'createby' => $config['params']['user'],
                    'createdate' => $this->othersClass->getCurrentTimeStamp(),
                    'remtype' => 1
                ];

                $this->coreFunctions->sbcinsert('headrem', $data);


                $path = 'App\Http\Classes\modules\vehiclescheduling\vl';
                $config['params']['trno'] = $trno;
                $config['params']['canceltype'] = 'forrevision';
                app($path)->cancelrequest($config);
                break;

            case 'RR':
            case 'CV':
                if ($this->othersClass->isposted2($trno, 'cntnum')) {
                    return ['trno' => $trno, 'status' => false, 'msg' => 'Transaction has already been posted.'];
                }
                if ($doc == 'CV') {
                    $access = $this->othersClass->checkAccess($config['params']['user'], 4004);
                    if (!$access) {
                        return ['status' => false, 'msg' => 'Please advice your administrator regarding this restriction.'];
                    }
                }

                $line = $this->coreFunctions->datareader("select ifnull(count(line),0)+1 as value from particulars where trno=?", [$trno]);

                $data = [
                    'trno' => $trno,
                    'rem' => $rem,
                    'createby' => $config['params']['user'],
                    'createdate' => $this->othersClass->getCurrentTimeStamp(),
                    'line' => $line
                ];

                $this->coreFunctions->sbcinsert('particulars', $data);

                $statid = 16;
                switch ($doc) {
                    case "CV":
                        $salestype = $this->coreFunctions->getfieldvalue("lahead", "salestype", "trno=?", [$trno]);
                        $config['params']['trno'] = $trno;
                        $laststatid = $this->othersClass->getstatid($config, "cntnum");
                        switch ($salestype) {
                            case "COD Cash":
                                // COD Cash: 0-10-57-58-42-50-48-59/60-61(FAMS)-62-63-64-39
                                switch ($laststatid) {
                                    case 39: //For Posting
                                        $statid = 64; //For Checking
                                        break;
                                    case 64: //For Checking
                                        $this->coreFunctions->execqry("delete from cntnumtodo where trno=? and donedate is null", "update", [$trno]);
                                        $statid = 63; //Forwarded to Accounting
                                        break;
                                    case 63: //Forwarded to Accounting
                                        $statid = 62; //For Liquidation
                                        break;
                                    case 62: //For Liquidation
                                        $path = 'App\Http\Classes\modules\ati\cv';
                                        $resultgeneric = app($path)->checkisgeneric($trno);
                                        if ($resultgeneric['status']) {
                                            $statid = 61; //Forwarded to Asset Management
                                        } else {
                                            $statid = 60; //Forwarded to warehouse
                                        }
                                        break;
                                    case 61: // Forwarded to Asset Management
                                        $statid = 60; // Forwarded to Warehouse
                                        break;
                                    case 60: //Forwarded to Warehouse
                                        $this->coreFunctions->execqry("update cntnuminfo set termsyear=termsyear-1 where trno=?", "update", [$trno]);
                                        $statid = 59; //Forwarded to Encoder
                                        break;
                                    case 59: //Forwarded to Encoder
                                        $this->coreFunctions->execqry("update cntnuminfo set termsyear=termsyear-1 where trno=?", "update", [$trno]);
                                        $statid = 48; //Items Collected
                                        break;
                                    case 48: //Items Collected
                                        $statid = 50; //Payment Released
                                        break;
                                    case 50: //Payment Released
                                        $this->coreFunctions->sbcupdate('lahead', ['lockuser' => '', 'lockdate' => null], ['trno' => $trno]);
                                        $this->coreFunctions->sbcupdate('cntnuminfo', ['releasedate' => null], ['trno' => $trno]);

                                        $this->coreFunctions->execqry("update cvitems as cv 
                                            left join hstockinfotrans as prs on prs.trno=cv.reqtrno and prs.line=cv.reqline set prs.payreleased=null where cv.trno=" . $trno, 'update');

                                        $this->coreFunctions->execqry("update ladetail as cv left join glstock as rr on rr.trno=cv.refx
                                            left join hstockinfotrans as prs on prs.trno=rr.reqtrno and prs.line=rr.reqline set prs.payreleased=null where cv.refx<>0 and cv.trno=" . $trno, 'update');

                                        $statid = 42; //Released
                                        break;
                                    case 42: //Released
                                        $statid = 58; //For Final Checking
                                        break;
                                    case 58: //For Final Checking
                                        // 2024.02.26 - remove initial checking
                                        // $statid = 57; //For Initial Checking
                                        $this->coreFunctions->sbcupdate('ladetail', ['isnoedit' => 0], ['trno' => $trno]);
                                        $statid = 10; //For Approval
                                        break;
                                    case 57: //For Initial Checking
                                        $this->coreFunctions->sbcupdate('ladetail', ['isnoedit' => 0], ['trno' => $trno]);
                                        $statid = 10; //For Approval
                                        break;
                                    case 10: //For Approval
                                        $statid = 0;
                                        break;
                                }
                                break;
                            case 'COD Cheque':
                                // COD Cheque: 0-10-36-63-49-65-50-66-48-60/59-61(FAMS)-62-63-57-58-69-39
                                switch ($laststatid) {
                                    case 39:
                                        $statid = 69;
                                        break;
                                    case 69:
                                        $statid = 58;
                                        break;
                                    case 58:
                                        $statid = 57;
                                        break;
                                    case 57:
                                        $statid = 63;
                                        break;
                                    case 63:
                                        $checkissued = $this->coreFunctions->getfieldvalue("cntnuminfo", "ischqreleased", "trno=?", [$trno]);
                                        if ($checkissued == "1") {
                                            $statid = 62;
                                        } else {
                                            $statid = 36;
                                        }

                                        break;
                                    case 62:
                                        $path = 'App\Http\Classes\modules\ati\cv';
                                        $resultgeneric = app($path)->checkisgeneric($trno);
                                        if ($resultgeneric['status']) {
                                            $statid = 61;
                                        } else {
                                            $statid = 60;
                                        }
                                        break;
                                    case 61:
                                        $statid = 60;
                                        break;
                                    case 60:
                                        $this->coreFunctions->execqry("update cntnuminfo set termsyear=termsyear-1 where trno=?", "update", [$trno]);
                                        $statid = 59;
                                        break;
                                    case 59:
                                        $this->coreFunctions->execqry("update cntnuminfo set termsyear=termsyear-1 where trno=?", "update", [$trno]);
                                        $statid = 48;
                                        break;
                                    case 48:
                                        $statid = 66;
                                        break;
                                    case 66:
                                        $statid = 50;
                                        break;
                                    case 50:
                                        $this->coreFunctions->sbcupdate('lahead', ['lockuser' => '', 'lockdate' => null], ['trno' => $trno]);
                                        $statid = 65;
                                        break;
                                    case 65:
                                        $this->coreFunctions->sbcupdate("cntnuminfo", ['ischqreleased' => 0], ['trno' => $trno]);
                                        $statid = 49;
                                        break;
                                    case 49:
                                        $statid = 63;
                                        break;
                                    case 36:
                                        $this->coreFunctions->sbcupdate('ladetail', ['isnoedit' => 0], ['trno' => $trno]);
                                        $statid = 10;
                                        break;
                                    case 10:
                                        $statid = 0;
                                        break;
                                }
                                break;

                            case 'Terms':
                                // Terms: 0-10-36-63-49-68-64-50-66-39
                                switch ($laststatid) {
                                    case 39:
                                        $statid = 66;
                                        break;
                                    case 66:
                                        $statid = 50;
                                        break;
                                    case 50:
                                        $this->coreFunctions->sbcupdate('lahead', ['lockuser' => '', 'lockdate' => null], ['trno' => $trno]);
                                        $statid = 64;
                                        break;
                                    case 64:
                                        $statid = 68;
                                        break;
                                    case 68:
                                        $statid = 49;
                                        break;
                                    case 49:
                                        $statid = 63;
                                        break;
                                    case 63:
                                        $statid = 36;
                                        break;
                                    case 36:
                                        $this->coreFunctions->sbcupdate('ladetail', ['isnoedit' => 0], ['trno' => $trno]);
                                        $statid = 10;
                                        break;
                                    case 10:
                                        $statid = 0;
                                        break;
                                }
                                break;
                        }
                        break;
                    case 'RR':
                        $config['params']['trno'] = $trno;
                        $laststatid = $this->othersClass->getstatid($config, "cntnum");
                        switch ($laststatid) {
                            case 73: //Acknowledged
                                $statid = 45;
                                break;
                            case 45: //For Checking
                                $statid = 71;
                                break;
                            // case 72: //Intransit
                            //     $statid = 71;
                            //     break;
                            case 71: //For Final Receiving
                                $statid = 70;
                                break;
                            case 70: //For Initial Receiving
                                $statid = 0;
                                break;
                        }

                        break;
                }

                $this->coreFunctions->sbcupdate('cntnum', ['statid' => $statid], ['trno' => $trno]);
                $cntnuminfo = ['instructions' => $statid == 0 ? '' : 'For Revision'];
                if ($statid == 10 || $statid == 0) {
                    $cntnuminfo['checkerdone'] = null;
                }

                $status = $this->coreFunctions->getfieldvalue('trxstatus', 'status', 'line=?', [$statid]);
                $this->coreFunctions->sbcupdate('cntnuminfo', $cntnuminfo, ['trno' => $trno]);
                // $this->logger->sbcwritelog($trno, $config, 'HEAD', 'TAGGED FOR REVISION', 'table_log');
                $this->logger->sbcstatlog($trno, $config, 'HEAD', 'TAGGED FOR REVISION -> ' . $status, 'cntnum_stat');
                break;

            case 'CD':
                if ($this->othersClass->isposted2($trno, 'cntnum')) {
                    return ['trno' => $trno, 'status' => false, 'msg' => 'Transaction has already been posted.'];
                }

                $access = $this->othersClass->checkAccess($config['params']['user'], 4102);
                if (!$access) {
                    return ['status' => false, 'msg' => 'Please advice your administrator regarding this restriction'];
                }

                $data = [
                    'trno' => $trno,
                    'rem' => $rem,
                    'createby' => $config['params']['user'],
                    'createdate' => $this->othersClass->getCurrentTimeStamp(),
                    'remtype' => 1
                ];

                $this->coreFunctions->sbcinsert('headrem', $data);
                $config['params']['trno'] = $trno;
                $statid = 16;
                $this->coreFunctions->execqry("update transnum set statid=$statid where trno=?", 'update', [$trno]);
                // $this->logger->sbcwritelog($config['params']['trno'], $config, 'HEAD', 'FOR REVISION', 'transnum_log');

                $status = $this->coreFunctions->getfieldvalue('trxstatus', 'status', 'line=?', [$statid]);
                $this->logger->sbcstatlog($config['params']['trno'], $config, 'HEAD', 'FOR REVISION -> ' . $status, 'transnum_stat');
                break;

            case 'SO':
                $data = [
                    'trno' => $trno,
                    'rem' => $rem,
                    'createby' => $config['params']['user'],
                    'createdate' => $this->othersClass->getCurrentTimeStamp(),
                    'remtype' => 1
                ];

                $this->coreFunctions->sbcinsert('headrem', $data);
                $config['params']['trno'] = $trno;
                $this->coreFunctions->execqry("update transnum set statid=16 where trno=?", 'update', [$trno]);
                $this->coreFunctions->execqry("update sohead set lockuser='', lockdate=null where trno=?", 'update', [$trno]);
                $this->logger->sbcwritelog($config['params']['trno'], $config, 'HEAD', 'FOR REVISION', 'transnum_log');
                break;
            case 'PO':
                $adminid =  $config['params']['adminid'];
                if ($adminid == 0) {
                    return ['trno' => $trno, 'status' => false, 'msg' => 'Please set up employee on your user account.'];
                }

                if ($this->othersClass->isposted2($trno, 'cntnum')) {
                    return ['trno' => $trno, 'status' => false, 'msg' => 'Transaction has already been posted.'];
                }

                $data = [
                    'trno' => $trno,
                    'rem' => $rem,
                    'createby' => $config['params']['user'],
                    'createdate' => $this->othersClass->getCurrentTimeStamp(),
                    'remtype' => 1
                ];

                $this->coreFunctions->sbcinsert('headrem', $data);
                $config['params']['trno'] = $trno;
                $laststatid = $this->othersClass->getstatid($config, "transnum");
                $statid = 0;
                switch ($laststatid) {
                    case 10:
                        $todo = $this->coreFunctions->opentable("select trno, line, clientid, donedate from transnumtodo where trno=? order by line desc", [$trno]);
                        if (!empty($todo)) {
                            foreach ($todo as $key => $value) {
                                if ($value->donedate == null) {
                                    continue;
                                } else {
                                    $this->coreFunctions->execqry("update transnumtodo set donedate=null where trno=? and line=?", 'update', [$value->trno, $value->line]);
                                    $appuser = $this->coreFunctions->getfieldvalue("client", "email", "clientid=?", [$value->clientid]);
                                    $this->coreFunctions->execqry("update transnum set appuser='" . $appuser . "' where trno=?", 'update', [$value->trno]);
                                    $statid = 10;

                                    $this->coreFunctions->execqry("delete from transnumtodo where trno=? and donedate is null", 'delete', [$trno]);
                                }
                            }
                        } else {
                            $this->coreFunctions->execqry("delete from transnumtodo where trno=?", 'delete', [$trno]);
                        }
                        break;
                    case 36:
                        $todo = $this->coreFunctions->opentable("select trno, line, clientid, donedate from transnumtodo where trno=? order by line desc", [$trno]);
                        if (!empty($todo)) {
                            foreach ($todo as $key => $value) {
                                $this->coreFunctions->execqry("update transnumtodo set donedate=null where trno=? and line=?", 'update', [$value->trno, $value->line]);
                                $appuser = $this->coreFunctions->getfieldvalue("client", "email", "clientid=?", [$value->clientid]);
                                $this->coreFunctions->execqry("update transnum set appuser='" . $appuser . "' where trno=?", 'update', [$value->trno]);
                                $statid = 10;
                                break;
                            }
                        }
                        break;
                    case 39:
                        $statid = 36;
                        break;
                }

                $this->coreFunctions->execqry("update transnum set statid=" . $statid . " where trno=?", 'update', [$trno]);
                $headinfotrans = ['instructions' => 'For Revision'];
                $this->coreFunctions->sbcupdate('headinfotrans', $headinfotrans, ['trno' => $trno]);
                // $this->logger->sbcwritelog($config['params']['trno'], $config, 'HEAD', 'FOR REVISION', 'transnum_log');

                $status = $this->coreFunctions->getfieldvalue('trxstatus', 'status', 'line=?', [$statid]);
                $this->logger->sbcstatlog($config['params']['trno'], $config, 'HEAD', 'FOR REVISION ->' . $status, 'transnum_stat');
                break;
        }

        return ['status' => true, 'msg' => 'Successfully saved.', 'data' => [],  'backlisting' => true];
    }
}
