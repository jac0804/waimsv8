<?php

namespace App\Http\Classes;

use Request;
use App\Http\Requests;
use App\Http\Classes\othersClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\Logger;
use App\Http\Classes\posClass;
use App\Http\Classes\common\payrollcommon;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;


use Exception;
use Throwable;

use Illuminate\Support\Str;

class payrollappClass
{

    private $othersClass;
    private $coreFunctions;
    private $posClass;
    private $logger;
    private $payrollcommon;

    public function __construct()
    {
        $this->othersClass = new othersClass;
        $this->coreFunctions = new coreFunctions;
        $this->posClass = new posClass;
        $this->logger = new Logger;
        $this->payrollcommon = new payrollcommon;
    }

    public function sbcpayrollapp($params)
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');

        $companyid = isset($params['companyid']) ? $params['companyid'] : 0;

        try {

            switch ($params['action']) {
                case md5('extractfile'):
                    $msg = '';

                    $files = Storage::disk('ftp')->files('upload');

                    // used for sorting
                    $keywords = ['tmshifts', 'shiftdetail', 'batch', 'department', 'jobthead', 'employee'];

                    usort($files, function ($a, $b) use ($keywords) {
                        $priorityA = PHP_INT_MAX; // Default priority if no keyword is found
                        $priorityB = PHP_INT_MAX;

                        // Find the highest priority keyword in filename A
                        foreach ($keywords as $index => $keyword) {
                            if (stripos($a, $keyword) !== false) {
                                $priorityA = $index;
                                break;
                            }
                        }

                        // Find the highest priority keyword in filename B
                        foreach ($keywords as $index => $keyword) {
                            if (stripos($b, $keyword) !== false) {
                                $priorityB = $index;
                                break;
                            }
                        }

                        // Compare priorities (without spaceship operator)
                        if ($priorityA < $priorityB) {
                            return -1;
                        } elseif ($priorityA > $priorityB) {
                            return 1;
                        } else {
                            return 0;
                        }
                    });

                    //end of - used for sorting

                    foreach ($files as $filename) {

                        if (Str::substr($filename, -3) === 'sbc') {
                            $arrline = $this->posClass->ftpfilecheckendfile($filename);
                            if (is_array($arrline)) {

                                $this->coreFunctions->LogConsole('Extracting ' . $filename);

                                $a = explode('/', $filename);
                                $b =  explode('~', $a[1]);
                                $tablename =  $b[0];
                                $csv = '';

                                switch ($tablename) {
                                    case 'holiday':
                                    case 'holidayloc':
                                    case 'division':
                                    case 'section':
                                    case 'department':
                                    case 'tmshifts':
                                    case 'rateexempt':
                                    case 'shiftdetail':
                                    case 'employee':
                                    case 'contacts':
                                    case 'timerec':
                                    case 'timecard':
                                    case 'paccount':
                                    case 'leavesetup':
                                    case 'leavetrans':
                                    case 'cancel_leavetrans':
                                    case 'batch':
                                    case 'standardsetup':
                                    case 'standardtrans':
                                    case 'paytrancurrent':
                                    case 'paytranhistory':
                                    case 'aaccount':
                                    case 'paydeletelogs':
                                    case 'emppics':
                                    case 'ratesetup';
                                    case 'jobthead':
                                        try {
                                            $csv_result = $this->convertCSVToArray($filename);
                                            if ($csv_result['status']) {
                                                $csv = $csv_result['csv'];

                                                if ($tablename == 'paytranhistory' || $tablename == 'paytrancurrent') {
                                                    $originalArray = $csv;
                                                    $groups = [];
                                                    foreach ($originalArray as $item) {
                                                        $key = 'batch' . $item['batchid'] . '_emp' . $item['empcode'];
                                                        if (!isset($groups[$key])) {
                                                            $groups[$key] = [];
                                                        }
                                                        $groups[$key][] = $item;
                                                    }

                                                    // Now loop through all found groups
                                                    foreach ($groups as $groupName => $records) {
                                                        $result = $this->insertdata($records, $tablename, $companyid);
                                                        if (!$result['status']) {
                                                            return json_encode(['status' => false, 'filename' => $filename, 'msg' =>  $result['msg'], 'result' => $result]);
                                                        }
                                                    }

                                                    Storage::disk('ftp')->delete($filename);
                                                    $this->coreFunctions->LogConsole('Finished extracting ' . $filename);
                                                } else {
                                                    $result = $this->insertdata($csv, $tablename, $companyid);
                                                    if (!$result['status']) {
                                                        return json_encode(['status' => false, 'filename' => $filename, 'msg' =>  $result['msg'], 'result' => $result]);
                                                    } else {
                                                        Storage::disk('ftp')->delete($filename);
                                                        $this->coreFunctions->LogConsole('Finished extracting ' . $filename);
                                                    }
                                                }
                                            } else {
                                                $this->coreFunctions->sbclogger('extractcsv - ' . $filename . ' - ' . $csv_result['msg']);
                                                return json_encode(['status' => false, 'filename' => $filename, 'msg' => $csv_result['msg']]);
                                            }
                                        } catch (Exception $ex) {
                                            $this->coreFunctions->sbclogger('extractcsv - ' . $filename . ' - ' . $ex);
                                            return json_encode(['status' => false, 'filename' => $filename, 'msg' => $ex]);
                                        }
                                        break;
                                }
                            }
                        }
                    }
                    if ($msg == '') {
                        $msg = 'File extraction completed';
                    }
                    return json_encode(['status' => true, 'msg' => $msg]);
                    break;

                case md5('downloadfiles'):
                    $msg = '';

                    //timerec
                    $sql = "select machno, userid, checktype, date_format(timeinout,'%Y-%m-%d %H:%i:%s') as timeinout, sensorid, status, mode, machname, date_format(curdate,'%Y-%m-%d %H:%i') as curdate from timerec where isok=0";
                    $data = $this->coreFunctions->opentable($sql);
                    $csv = '';
                    try {
                        $csv = $this->posClass->createcsv($data, 1);
                        $this->posClass->ftpcreatefile2("timerec", $csv, 1);

                        foreach ($data as $k => $v) {
                            $this->coreFunctions->LogConsole('updating timerec: ' . $v->userid . ', time: ' . $v->timeinout);
                            $this->coreFunctions->sbcupdate("timerec", ['isok' => 1], ['machno' => $v->machno, 'userid' => $v->userid, 'timeinout' => $v->timeinout]);
                        }

                        $msg = 'Success';
                    } catch (Exception $ex) {
                        return json_encode(['status' => false, 'msg' => 'Failed to generate timerec file ' . $ex]);
                    }

                    //2025.03.07 - FMM - removed due to no more editing in timecard in portal
                    // //timecard
                    // $sql = "select line, c.client as empcode, dateid, schedin, schedout, schedbrkin, schedbrkout, actualin, actualout, actualbrkin, actualbrkout, reghrs, absdays, latehrs, underhrs, othrs, ndiffhrs, ndiffot, 
                    //     otapproved, Ndiffapproved, isprevwork, RDapprvd, RDOTapprvd, LEGapprvd, LEGOTapprvd, SPapprvd, SPOTapprvd, ndiffs, ndiffsapprvd, 1 as isportal
                    //     from timecard as tc left join client as c on c.clientid=tc.empid where tc.isok=0 order by line";
                    // $data = $this->coreFunctions->opentable($sql);
                    // $csv = '';
                    // try {
                    //     $csv = $this->posClass->createcsv($data, 1);
                    //     $this->posClass->ftpcreatefile2("timecard", $csv, 1);

                    //     foreach ($data as $k => $v) {
                    //         $this->coreFunctions->LogConsole('updating timecard: ' . $v->empcode . ', date: ' . $v->dateid);
                    //         $this->coreFunctions->sbcupdate("timecard", ['isok' => 1], ['line' => $v->line]);
                    //     }

                    //     $msg = 'Success';
                    // } catch (Exception $ex) {
                    //     return json_encode(['status' => false, 'msg' => 'Failed to generate timecard file ' . $ex]);
                    // }

                    //timecard
                    $sql = "select tc.line, c.client as empcode, dateid, schedin, schedout, schedbrkin, schedbrkout, actualin, actualout, actualbrkin, actualbrkout, reghrs, daytype, tm.shftcode as shiftcode
                        from temptimecard as tc left join client as c on c.clientid=tc.empid left join tmshifts as tm on tm.line=tc.shiftid where tc.isok=0 order by tc.line";
                    $data = $this->coreFunctions->opentable($sql);
                    $csv = '';
                    try {
                        $csv = $this->posClass->createcsv($data, 1);
                        $this->posClass->ftpcreatefile2("temptimecard", $csv, 1);

                        foreach ($data as $k => $v) {
                            $this->coreFunctions->LogConsole('updating temptimecard: ' . $v->empcode . ', date: ' . $v->dateid);
                            $this->coreFunctions->sbcupdate("temptimecard", ['isok' => 1], ['line' => $v->line]);
                        }

                        $msg = 'Success';
                    } catch (Exception $ex) {
                        return json_encode(['status' => false, 'msg' => 'Failed to generate temptimecard file ' . $ex]);
                    }


                    //leavetrans
                    $sql = "select s.trno as refno, s.line, p.code as daytype, s.adays, s.dateid, s.effectivity, s.remarks, s.status, s.approvedby_disapprovedby, s.approvedby_disapprovedby2, s.disapproved_remarks, s.date_approved_disapproved, s.disapproved_remarks2, s.islatefilling, ifnull(b.batch,'') as batch
                            from leavetrans as s left join leavesetup as ls on ls.trno=s.trno left join paccount as p on p.line=ls.acnoid left join batch as b on b.line=s.batchid
                            where s.status in ('A','P') and s.isok=0 and s.iswindows=0";
                    $data = $this->coreFunctions->opentable($sql);
                    $csv = '';
                    try {
                        $csv = $this->posClass->createcsv($data, 1);
                        $this->posClass->ftpcreatefile2("leavetrans", $csv, 1);

                        foreach ($data as $k => $v) {
                            $this->coreFunctions->LogConsole('updating leavetrans: ' . $v->refno . ', date: ' . $v->dateid);
                            $this->coreFunctions->sbcupdate("leavetrans", ['isok' => 1], ['trno' => $v->refno, 'line' => $v->line]);
                        }

                        $msg = 'Success';
                    } catch (Exception $ex) {
                        return json_encode(['status' => false, 'msg' => 'Failed to generate leavetrans file ' . $ex]);
                    }

                    //obapplication
                    $sql = "select app.line, app.empid, client.client as empcode, app.dateid,app.dateid2, app.type, app.rem, app.status, app.approvedby, app.approvedate, app.scheddate,
                    app.disapproved_remarks2
                    from obapplication as app left join client on client.clientid=app.empid where app.status='A' and app.isok=0";
                    $data = $this->coreFunctions->opentable($sql);
                    $csv = '';
                    try {
                        $csv = $this->posClass->createcsv($data, 1);
                        $this->posClass->ftpcreatefile2("obapplication", $csv, 1);

                        foreach ($data as $k => $v) {
                            $this->coreFunctions->LogConsole('updating obapplication: ' . $v->line . ', date: ' . $v->dateid);
                            $this->coreFunctions->sbcupdate("obapplication", ['isok' => 1], ['line' => $v->line]);
                        }

                        $msg = 'Success';
                    } catch (Exception $ex) {
                        return json_encode(['status' => false, 'msg' => 'Failed to generate obapplication file ' . $ex]);
                    }


                    //loanapplication
                    $sql = "select app.trno, app.docno, app.dateid, app.empid, cl.client as empcode, p.code, app.amt, app.paymode, app.w1, app.w2, app.w3, app.w4, app.w5, app.halt, app.priority, app.amortization, app.effdate, app.balance, app.w13, app.remarks, p.code as acno,
                            app.approvedby_disapprovedby, app.date_approved_disapproved, app.approvedby_disapprovedby2, app.date_approved_disapproved2, app.disapproved_remarks2, app.enddate
                            from loanapplication as app left join client as cl on cl.clientid=app.empid left join paccount as p on p.line=app.acnoid where app.status='A' and app.isok=0 order by app.docno";
                    $data = $this->coreFunctions->opentable($sql);
                    $csv = '';
                    try {
                        $csv = $this->posClass->createcsv($data, 1);
                        $this->posClass->ftpcreatefile2("loanapplication", $csv, 1);

                        foreach ($data as $k => $v) {
                            $this->coreFunctions->LogConsole('updating loanapplication: ' . $v->docno . ', date: ' . $v->dateid);
                            $this->coreFunctions->sbcupdate("loanapplication", ['isok' => 1], ['docno' => $v->docno]);
                        }

                        $msg = 'Success';
                    } catch (Exception $ex) {
                        return json_encode(['status' => false, 'msg' => 'Failed to generate loanapplication file ' . $ex]);
                    }


                    //otapplication
                    $sql = "select app.line, app.empid, client.client as empcode, app.dateid, app.othrs, app.apothrs, app.batchid, app.approvedate, app.approvedby, app.approvedate2, app.approvedby2, app.batchid, app.rem, app.remarks,
                            app.ottimein, app.ottimeout, app.ndiffhrs, app.apndiffhrs, app.ndiffothrs, app.apndiffothrs, app.othrsextra, app.apothrsextra, app.daytype, app.dateid2, app.scheddate, app.disapproved_remarks2, app.issat
                            from otapplication as app left join client on client.clientid=app.empid where app.otstatus=2 and isok=0 and apothrs<>0";
                    $data = $this->coreFunctions->opentable($sql);
                    $csv = '';
                    try {
                        $csv = $this->posClass->createcsv($data, 1);
                        $this->posClass->ftpcreatefile2("otapplication", $csv, 1);

                        foreach ($data as $k => $v) {
                            $this->coreFunctions->LogConsole('updating otapplication: ' . $v->line . ', date: ' . $v->dateid);
                            $this->coreFunctions->sbcupdate("otapplication", ['isok' => 1], ['line' => $v->line]);
                        }

                        $msg = 'Success';
                    } catch (Exception $ex) {
                        return json_encode(['status' => false, 'msg' => 'Failed to generate otapplication file ' . $ex]);
                    }

                    //undertime_application
                    $sql = "select u.line, u.empid, client.client as empcode, u.dateid, u.rem, u.approvedby, u.approvedate,  u.approvedby2, u.approvedate2, u.disapproved_remarks2
                            from undertime as u left join client on client.clientid=u.empid where u.status='A' and u.isok=0";
                    $data = $this->coreFunctions->opentable($sql);
                    $csv = '';
                    try {
                        $csv = $this->posClass->createcsv($data, 1);
                        $this->posClass->ftpcreatefile2("undertime", $csv, 1);

                        foreach ($data as $k => $v) {
                            $this->coreFunctions->LogConsole('updating undertime: ' . $v->line . ', date: ' . $v->dateid);
                            $this->coreFunctions->sbcupdate("undertime", ['isok' => 1], ['line' => $v->line]);
                        }

                        $msg = 'Success';
                    } catch (Exception $ex) {
                        return json_encode(['status' => false, 'msg' => 'Failed to generate undertime file ' . $ex]);
                    }

                    //changeshiftapp
                    $sql = "select app.line, app.dateid, client.client as empcode, app.status, app.rem, app.schedin, app.schedout,app. originalin, app.originalout, app.createdate, app.createby, app.editdate, app.editby, app.approveddate, app.approvedby, 
                            app.disapproveddate, app.disapprovedby, app.approveddate2, app.approvedby2, app.status2, app.disapproved_remarks, app.disapproved_remarks2, app.disapproveddate2, app.disapprovedby2, app.submitdate, app.daytype, app.orgdaytype, app.shftcode, app.reghrs
                            from changeshiftapp as app left join client on client.clientid=app.empid where app.status=1 and app.isok=0";
                    $data = $this->coreFunctions->opentable($sql);
                    $csv = '';
                    try {
                        $csv = $this->posClass->createcsv($data, 1);
                        $this->posClass->ftpcreatefile2("changeshiftapp", $csv, 1);

                        foreach ($data as $k => $v) {
                            $this->coreFunctions->LogConsole('updating changeshiftapp: ' . $v->line . ', date: ' . $v->dateid);
                            $this->coreFunctions->sbcupdate("changeshiftapp", ['isok' => 1], ['line' => $v->line]);
                        }

                        $msg = 'Success';
                    } catch (Exception $ex) {
                        return json_encode(['status' => false, 'msg' => 'Failed to generate changeshiftapp file ' . $ex]);
                    }

                    return json_encode(['status' => true, 'msg' => $msg]);
                    break;


                case md5('deletedata'):
                    $msg = '';
                    $status = true;
                    $delstatus = false;

                    try {
                        $sql = 'select line, docno, module from paydeletelogs where isdeleted=0';
                        $data = $this->coreFunctions->opentable($sql);

                        foreach ($data as $key => $value) {
                            switch ($value->module) {
                                case 'BATCH':
                                case 'HOLIDAY':
                                case 'HOLIDAYLOC':
                                case 'TMSHIFTS';
                                    if ($this->coreFunctions->execqry("delete from " . strtolower($value->module) . " where line=" . $value->docno)) {
                                        $this->coreFunctions->execqry("update paydeletelogs set isdeleted=1 where module='" . $value->module . "' and docno='" . $value->docno . "'");
                                        $delstatus = true;
                                    } else {
                                        $status = false;
                                        $msg .= $value->line . '. Failed to delete ' . $value->module . ' - ' . $value->docno . '. ';
                                    }
                                    break;
                                case 'LEAVESETUP';
                                    $sql = "select trno, docno from leavesetup where trno=" . $value->docno;
                                    $delLeaveSetup = $this->coreFunctions->opentable($sql);
                                    if (!empty($delLeaveSetup)) {
                                        if ($this->coreFunctions->execqry("delete from leavesetup where trno=" . $value->docno)) {
                                            $params = [];
                                            $params['params']['doc'] = 'LEAVESETUP';
                                            $params['params']['user'] = 'SYNCING';
                                            $this->logger->sbcmasterlog2($delLeaveSetup[0]->trno, $params, "DELETED LEAVESETUP FROM LOCAL " . $delLeaveSetup[0]->docno, "payroll_log");

                                            $this->coreFunctions->execqry("update paydeletelogs set isdeleted=1 where module='" . $value->module . "' and docno='" . $value->docno . "'");
                                        }
                                    }
                                    break;
                                case 'LEAVETRANS':
                                    $sql = "select trno, date(dateid) as dateid, date(effectivity) as effectivity, adays from leavetrans where refno='" . $value->docno . "'";
                                    $delLeave = $this->coreFunctions->opentable($sql);
                                    if (!empty($delLeave)) {
                                        if ($this->coreFunctions->execqry("delete from leavetrans where refno='" . $value->docno . "'")) {

                                            $params = [];
                                            $params['params']['doc'] = 'LEAVEAPPLICATIONPORTAL';
                                            $params['params']['user'] = 'SYNCING';
                                            $this->logger->sbcmasterlog2($delLeave[0]->trno, $params, "DELETED LEAVE FROM LOCAL, DATE: " . date('Y-m-d', strtotime($delLeave[0]->dateid)) . " EFFECTIVITY: " . date('Y-m-d', strtotime($delLeave[0]->effectivity)) . "  LEAVE: " . $delLeave[0]->adays, "payroll_log");

                                            $this->coreFunctions->execqry("update paydeletelogs set isdeleted=1 where module='" . $value->module . "' and docno='" . $value->docno . "'");

                                            if (!empty($delLeave) != 0) {
                                                $applied = $this->coreFunctions->datareader("select ifnull(sum(adays),0) as value from leavetrans where trno=" . $delLeave[0]->trno . " and status in ('A','P')", [], '', true);
                                                $this->coreFunctions->execqry("update leavesetup set bal=days-" . $applied . ", editby='SYNCING', editdate='" . $this->othersClass->getCurrentTimeStamp() . "' where trno=" . $delLeave[0]->trno);
                                            }
                                            $delstatus = true;
                                        } else {
                                            $status = false;
                                            $msg .= $value->line . '. Failed to delete ' . $value->module . ' - ' . $value->docno . '. ';
                                        }
                                    }
                                    break;
                                case 'EMPLOYEE':
                                    $del_empid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$value->docno], '', true);
                                    if ($del_empid != 0) {
                                        if ($this->coreFunctions->execqry("delete from client where clientid=" . $del_empid)) {
                                            if ($this->coreFunctions->execqry("delete from employee where empid=" . $del_empid)) {
                                                $this->coreFunctions->execqry("update paydeletelogs set isdeleted=1 where module='" . $value->module . "' and docno='" . $value->docno . "'");
                                                $delstatus = true;
                                            } else {
                                                $status = false;
                                                $msg .= $value->line . '. Failed to delete ' . $value->module . ' - ' . $value->docno . '. ';
                                            }
                                        } else {
                                            $status = false;
                                            $msg .= $value->line . '. Failed to delete ' . $value->module . ' - ' . $value->docno . '. ';
                                        }
                                    } else {
                                        $status = false;
                                        $msg .= $value->line . '. Failed to delete employee ' . $value->docno . ' doesnt exists';
                                        $this->coreFunctions->execqry("update paydeletelogs set isdeleted=1,isnotexist=1 where module='" . $value->module . "' and docno='" . $value->docno . "'");
                                    }

                                    break;
                            }
                        }
                    } catch (Exception $ex) {
                        return json_encode(['status' => false, 'msg' => 'Failed to delete: SQL: ' . $sql . ' ---- ' . $ex]);
                    }

                    if ($msg == '') {
                        if ($delstatus) {
                            $msg = 'Deleting data completed';
                        } else {
                            $msg = 'Nothing to delete';
                        }
                    }

                    return json_encode(['status' => $status, 'msg' => $msg]);
                    break;

                case md5('disapproved'):
                    $msg = '';
                    $status = true;

                    //obapplication
                    $sql = "select line, empid, `status`, disapprovedby, disapprovedate FROM obapplication WHERE disapprovedate IS NOT NULL AND isok=1 AND isok2=0";
                    $data = $this->coreFunctions->opentable($sql);
                    $csv = '';
                    try {
                        $csv = $this->posClass->createcsv($data, 1);
                        $this->posClass->ftpcreatefile2("disapp_obapplication", $csv, 1);

                        foreach ($data as $k => $v) {
                            $this->coreFunctions->sbcupdate("obapplication", ['isok2' => 1], ['line' => $v->line]);
                        }

                        $msg = 'Success';
                    } catch (Exception $ex) {
                        return json_encode(['status' => false, 'msg' => 'Failed to generate disapproved obapplication file ' . $ex]);
                    }

                    //otapplication
                    $sql = "select line, empid, otstatus, disapprovedby, disapprovedate FROM otapplication WHERE disapprovedate IS NOT NULL AND isok=1 AND isok2=0";
                    $data = $this->coreFunctions->opentable($sql);
                    $csv = '';
                    try {
                        $csv = $this->posClass->createcsv($data, 1);
                        $this->posClass->ftpcreatefile2("disapp_otapplication", $csv, 1);

                        foreach ($data as $k => $v) {
                            $this->coreFunctions->sbcupdate("otapplication", ['isok2' => 1], ['line' => $v->line]);
                        }

                        $msg = 'Success';
                    } catch (Exception $ex) {
                        return json_encode(['status' => false, 'msg' => 'Failed to generate otapplication file ' . $ex]);
                    }

                    //leavetrans
                    $sql = "select refno, line, empid, `status`, disapprovedby, date_disapproved FROM leavetrans WHERE date_disapproved IS NOT NULL AND isok=1 AND isok2=0 ";
                    $data = $this->coreFunctions->opentable($sql);
                    $csv = '';
                    try {
                        $csv = $this->posClass->createcsv($data, 1);
                        $this->posClass->ftpcreatefile2("disapp_leavetrans", $csv, 1);

                        foreach ($data as $k => $v) {
                            $this->coreFunctions->sbcupdate("leavetrans", ['isok2' => 1], ['trno' => $v->refno, 'line' => $v->line]);
                        }

                        $msg = 'Success';
                    } catch (Exception $ex) {
                        return json_encode(['status' => false, 'msg' => 'Failed to generate leavetrans file ' . $ex]);
                    }

                    //loanapplication
                    $sql = "select trno, docno, empid, `status`, disapprovedby, date_disapproved FROM loanapplication WHERE date_disapproved IS NOT NULL AND isok=1 AND isok2=0";
                    $data = $this->coreFunctions->opentable($sql);
                    $csv = '';
                    try {
                        $csv = $this->posClass->createcsv($data, 1);
                        $this->posClass->ftpcreatefile2("disapp_loanapplication", $csv, 1);

                        foreach ($data as $k => $v) {
                            $this->coreFunctions->sbcupdate("loanapplication", ['isok2' => 1], ['docno' => $v->docno]);
                        }

                        $msg = 'Success';
                    } catch (Exception $ex) {
                        return json_encode(['status' => false, 'msg' => 'Failed to generate loanapplication file ' . $ex]);
                    }

                    //changeshiftapp
                    $sql = "select line, dateid, empid, `status`, disapprovedby, disapproveddate FROM changeshiftapp WHERE disapproveddate IS NOT NULL AND isok=1 AND isok2=0";
                    $data = $this->coreFunctions->opentable($sql);
                    $csv = '';
                    try {
                        $csv = $this->posClass->createcsv($data, 1);
                        $this->posClass->ftpcreatefile2("disapp_changeshiftapp", $csv, 1);

                        foreach ($data as $k => $v) {
                            $this->coreFunctions->sbcupdate("changeshiftapp", ['isok2' => 1], ['line' => $v->line]);
                        }

                        $msg = 'Success';
                    } catch (Exception $ex) {
                        return json_encode(['status' => false, 'msg' => 'Failed to generate changeshiftapp file ' . $ex]);
                    }

                    return json_encode(['status' => $status, 'msg' => $msg]);
                    break;

                default:
                    return json_encode(['status' => false, 'msg' => 'Invalid action']);
                    break;
            }
        } catch (Exception $e) {
            $this->coreFunctions->LogConsole('sbcpayrollapp - ' . $e);
            return json_encode(['status' => false, 'msg' => 'sbcpayrollapp - ' . $e]);
        }
    }

    public function convertCSVToArray($path)
    {
        $status = true;
        $msg = '';
        $linedata = '';

        try {
            $csvData = Storage::disk('ftp')->get($path);
            $lines = explode(PHP_EOL, $csvData);
            $array = array();
            $header = null;

            foreach ($lines as $line) {
                $linedata .= $line . '/n';
                if (trim($line) == 'ENDFILE') {
                    break;
                }
                if (!$header) {
                    $header = str_getcsv($line, "~");
                } else {
                    $array[] = array_combine($header, str_getcsv($line, "~"));
                }
            }
        } catch (Exception $e) {
            $status = false;
            $msg =   'line data=' . $linedata . ' >>>>>>>>> ' . $e;
        }

        return ['status' => $status, 'msg' => $msg, 'csv' => $array];
    }

    public function insertdata($data, $type, $companyid)
    {
        $row = [];
        $row_employeefields = [];
        array_push($row_employeefields,  'emplast', 'empfirst',  'empmiddle', 'city', 'country', 'telno', 'mobileno', 'email', 'religion', 'status', 'gender', 'alias', 'bday', 'idbarcode', 'tin', 'sss', 'hdmf', 'phic',  'chktin', 'chksss', 'chkphealth');
        array_push($row_employeefields,  'citizenship', 'chkpibig', 'paymode', 'classrate',   'shiftid', 'paygroup', 'level', 'isactive',  'deptid', 'divid', 'sectid', 'hired', 'bankacct', 'atm');
        array_push($row_employeefields, 'issupervisor', 'isotapprover', 'emploc', 'isapprover', 'obapp1', 'obapp2', 'supervisorid', 'otsupervisorid', 'maidname', 'emprate', 'teu', 'approver1', 'jobtitle', 'jobid', 'is13th');
        $row_employee = [];

        $row_contactsfields = ['empid', 'contact1', 'contact2', 'relation1', 'relation2', 'addr1', 'addr2', 'homeno1', 'homeno2', 'mobileno1', 'mobileno2', 'officeno1', 'officeno2', 'ext1', 'ext2', 'notes1', 'notes2'];

        $row_jobtitlefields = ['line', 'trno', 'docno', 'jobtitle'];

        $SQLError = '';

        $code = '';
        $id = '';
        $table = '';

        $shiftid = 0;

        try {
            $index = 0;
            foreach ($data as $key => $value) {
                $this->coreFunctions->LogConsole("index: " . $index . ' ' . json_encode($value));

                if ($index == 0) {
                    if ($type == 'paytranhistory') {
                        $empid = $this->getfieldid('empid', "client",  $value['empcode']);
                        $this->coreFunctions->execqry("delete from paytranhistory where empid=" . $empid . " and batchid=" . $value['batchid']);
                    }

                    if ($type == 'paytrancurrent') {
                        $empid = $this->getfieldid('empid', "client",  $value['empcode']);
                        $this->coreFunctions->execqry("delete from paytrancurrent where empid=" . $empid . " and batchid=" . $value['batchid']);
                    }

                    if ($type == 'shiftdetail') {
                        $shiftid = $this->coreFunctions->getfieldvalue("tmshifts", "line", "shftcode=?", [$value['shftcode']], '', true);
                        $this->coreFunctions->execqry("delete from shiftdetail where shiftsid=" . $shiftid);
                    }
                }

                switch ($type) {
                    case 'department':
                        $code = 'deptid';
                        $id = 'clientid';
                        $table = 'client';
                        $row = ['deptid' => $value['deptid'], 'client' => $value['client'], 'clientname' => $value['clientname'], 'isdepartment' => $value['isdepartment']];
                        break;

                    case 'division':
                        $code = 'divid';
                        $id = 'divid';
                        $table = 'division';
                        $row = ['divid' => $value['divid'], 'divcode' => $value['divcode'], 'divname' => $value['divname'], 'address' => $value['address']];
                        break;

                    case 'section';
                        $code = 'sectid';
                        $id = 'sectid';
                        $table = 'section';
                        $row = ['sectid' => $value['sectid'], 'sectcode' => $value['sectcode'], 'sectname' => $value['sectname'], 'area' => $value['area']];
                        break;

                    case 'holiday';
                        $code = 'line';
                        $id = 'line';
                        $table = 'holiday';
                        $row = ['line' => $value['line'], 'dateid' => $value['dateid'], 'description' => $value['description'], 'daytype' => $value['daytype'], 'divcode' => $value['divcode']];
                        break;

                    case 'holidayloc';
                        $code = 'line';
                        $id = 'line';
                        $table = 'holidayloc';
                        $row = ['line' => $value['line'], 'dateid' => $value['dateid'], 'description' => $value['description'], 'daytype' => $value['daytype'], 'divcode' => $value['divcode'], 'location' => $value['location']];
                        break;

                    case 'rateexempt';
                        $code = 'line';
                        $id = 'line';
                        $table = 'rateexempt';
                        $row = ['line' => $value['line'], 'rate' => $value['rate'], 'area' => $value['area']];
                        break;

                    case 'ratesetup';
                        $code = 'trno';
                        $id = 'trno';
                        $table = 'ratesetup';
                        $row = ['trno' => $value['trno'], 'dateid' => $value['dateid'], 'dateeffect' => $value['dateeffect'], 'dateend' => $value['dateend'], 'remarks' => $value['remarks'], 'basicrate' => $value['basicrate'], 'type' => $value['type']];
                        $row['empid'] = $this->getfieldid('empid', "client",  $value['empcode']);
                        break;

                    case 'paydeletelogs':
                        $code = 'line';
                        $id = 'line';
                        $table = 'paydeletelogs';
                        $row = ['line' => $value['line'], 'docno' => $value['docno'], 'module' => $value['module'], 'isdelete' => $value['isdelete']];
                        break;

                    case 'tmshifts':
                        $code = 'shftcode';
                        $id = 'line';
                        $table = 'tmshifts';
                        $row = [
                            'line' => $value['line'],
                            'shftcode' => $value['shftcode'],
                            'tschedin' => $value['tschedin'],
                            'tschedout' => $value['tschedout'],
                            'flexit' => $value['flexit'],
                            'gtin' => $value['gtin'],
                            'gbrkin' => $value['gbrkin'],
                            'ndifffrom' => $value['ndifffrom'],
                            'ndiffto' => $value['ndiffto'],
                            'elapse' => $value['elapse']
                        ];
                        break;

                    case 'shiftdetail':
                        if ($shiftid != 0) {
                            $code = 'shiftsid';
                            $id = 'line';
                            $table = 'shiftdetail';
                            $row = [
                                'line' => $value['line'],
                                'shiftsid' => $shiftid,
                                'dayn' => $value['dayn'],
                                'schedin' => $value['schedin'],
                                'schedout' => $value['schedout'],
                                'breakin' => $value['brkin'],
                                'breakout' => $value['brkout'],
                                'brk1stin' => $value['brk1stin'],
                                'brk1stout' => $value['brk1stout'],
                                'brk2ndin' => $value['brk2ndin'],
                                'brk2ndout' => $value['brk2ndout'],
                                'tothrs' => $value['tothrs']
                            ];
                        } else {
                            return ['status' => false, 'msg' => 'Invalid shift code for ' . json_encode($value)];
                        }

                        break;

                    case 'batch':
                        $code = 'line';
                        $id = 'line';
                        $table = 'batch';
                        $row = ['line' => $value['line'], 'batch' => $value['batch'], 'startdate' => $value['startdate'], 'enddate' => $value['enddate'], 'paymode' => $value['paymode'], 'pgroup' => $value['pgroup'], 'postdate' => $value['postdate']];
                        $row['divid'] = $this->coreFunctions->getfieldvalue("division", "divid", "divcode=?", [$value['divcode']], '', true);
                        if (isset($value['deptid'])) $row['deptid'] = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$value['deptid']], '', true);
                        if (isset($value['isportal'])) $row['isportal'] = $value['isportal'];
                        break;

                    case 'paytranhistory':
                        $table = 'paytranhistory';
                        $row = ['batchid' => $value['batchid'], 'dateid' => $value['dateid'], 'acnoid' => $value['acnoid'], 'qty' => $value['qty'], 'db' => $value['db'], 'cr' => $value['cr']];
                        $row['empid'] = $this->getfieldid('empid', "client",  $value['empcode']);
                        break;

                    case 'paytrancurrent':
                        $table = 'paytrancurrent';
                        $row = ['batchid' => $value['batchid'], 'dateid' => $value['dateid'], 'acnoid' => $value['acnoid'], 'qty' => $value['qty'], 'db' => $value['db'], 'cr' => $value['cr']];
                        $row['empid'] = $this->getfieldid('empid', "client",  $value['empcode']);
                        break;

                    case 'standardsetup':
                        $code = 'docno';
                        $id = 'trno';
                        $table = 'standardsetup';
                        $row = [];

                        foreach ($value as $valindex => $val) {
                            switch ($valindex) {
                                case 'empcode':
                                    $row['empid'] = $this->getfieldid('empid', "client",  $value['empcode']);
                                    break;
                                case 'acno':
                                    $row['acnoid'] = $this->coreFunctions->getfieldvalue("paccount", "line",  "code=?", [$value['acno']], '', true);
                                    break;
                                default:
                                    $row[$valindex] = $value[$valindex];
                                    break;
                            }
                        }
                        break;

                    case 'standardtrans':
                        $code = 'batchid';
                        $id = 'trno';
                        $table = 'standardtrans';
                        $row = [];

                        foreach ($value as $valindex => $val) {
                            switch ($valindex) {
                                case 'empcode':
                                    $row['empid'] = $this->getfieldid('empid', "client",  $value['empcode']);
                                    break;
                                case 'acno':
                                case 'line':
                                    break;
                                case 'batch':
                                    if ($value['ismanual'] == 1) {
                                        $row['manualref'] = $value['batch'];
                                    } else {
                                        $row['manualref'] = '';
                                    }
                                    break;
                                default:
                                    $row[$valindex] = $value[$valindex];
                                    break;
                            }
                        }
                        break;

                    case 'employee':
                        $code = 'client';
                        $id = 'clientid';
                        $table = 'client';
                        $row = ['client' => $value['client'], 'clientname' => $value['emplast'] . ', ' . $value['empfirst'] . ' ' . $value['empmiddle'], 'isemployee' => 1, 'email' => $value['client'], 'password' => $value['client'], 'addr' => $value['address']];

                        $row_employee['roleid'] = $this->autocreaterole($value, $companyid);

                        foreach ($row_employeefields as $re) {
                            if (isset($value[$re])) {
                                switch ($re) {
                                    case 'deptid':
                                    case 'obapp1':
                                    case 'obapp2':
                                    case 'supervisorid':
                                    case 'otsupervisorid':
                                    case 'approver1':
                                        $row_employee[$re] = $this->getfieldid($re, "client",  $value[$re]);
                                        break;
                                    case 'shiftid':
                                        $row_employee[$re] = $this->getfieldid($re, "tmshifts",  $value[$re]);
                                        break;
                                    case 'divid':
                                        $row_employee[$re] = $this->getfieldid($re, "division",  $value[$re]);
                                        break;
                                    case 'sectid':
                                        $row_employee[$re] = $this->getfieldid($re, "section",  $value[$re]);
                                        break;
                                    case 'emprate':
                                        $row_employee[$re] = $this->getfieldid($re, "rateexempt",  $value[$re]);
                                        break;
                                    default:
                                        if (isset($value[$re])) {
                                            $row_employee[$re] = $value[$re];
                                        }
                                        break;
                                }
                            }
                        }
                        break;

                    case 'contacts':
                        $table = 'contacts';
                        $id = 'empid';
                        $code = 'empid';

                        $row['empid'] = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$value["empcode"]]);
                        foreach ($row_contactsfields as $re) {
                            if (isset($value[$re])) {
                                $row[$re] = $value[$re];
                            }
                        }
                        break;

                    case 'jobthead':
                        $table = 'jobthead';
                        $id = 'line';
                        $code = 'line';

                        foreach ($row_jobtitlefields as $re) {
                            if (isset($value[$re])) {
                                $row[$re] = $value[$re];
                            }
                        }
                        break;

                    case 'timerec':
                        $table = 'timerec';
                        $id = 'userid';
                        $row = ['userid' => $value['userid'], 'machno' => $value['machno'], 'timeinout' => $value['timeinout'], 'mode' => $value['mode'], 'curdate' => $value['curdate'], 'machname' => $value['machname']];
                        break;

                    case 'timecard':
                        $table = 'timecard';
                        $id = 'empid';
                        $row = [
                            'dateid' => $value['dateid'],
                            'shiftid' => $value['shiftid'],
                            'schedin' => $value['schedin'],
                            'schedout' => $value['schedout'],
                            'schedbrkin' => $value['schedbrkin'],
                            'schedbrkout' => $value['schedbrkout'],
                            'actualin' => $value['actualin'],
                            'actualout' => $value['actualout'],
                            'actualbrkin' => $value['actualbrkin'],
                            'actualbrkout' => $value['actualbrkout'],
                            'daytype' => $value['daytype'],
                            'reghrs' => $value['reghrs'],
                            'absdays' => $value['absdays'],
                            'latehrs' => $value['latehrs'],
                            'underhrs' => $value['underhrs'],
                            'othrs' => $value['othrs'],
                            'ndiffhrs' => $value['ndiffhrs'],
                            'ndiffot' => $value['ndiffot'],
                            'ismactualin' => isset($value['ismactualin']) ? $value['ismactualin'] : 0,
                            'ismactualout' => isset($value['ismactualout']) ? $value['ismactualout'] : 0,
                            'isobactualin' => isset($value['isobactualin']) ? $value['isobactualin'] : 0,
                            'isobactualout' => isset($value['isobactualout']) ? $value['isobactualout'] : 0,
                            'ischangesched' => isset($value['ischangesched']) ? $value['ischangesched'] : 0,
                            'logactualin' => isset($value['logactualin']) ? $value['logactualin'] : 0,
                            'logactualout' => isset($value['logactualout']) ? $value['logactualout'] : 0,
                            'ismbrkin' => isset($value['ismbrkin']) ? $value['ismbrkin'] : 0,
                            'ismbrkout' => isset($value['ismbrkout']) ? $value['ismbrkout'] : 0,
                            'ismlunchin' => isset($value['ismlunchin']) ? $value['ismlunchin'] : 0,
                            'ismlunchout' => isset($value['ismlunchout']) ? $value['ismlunchout'] : 0,
                            'loglunchin' => isset($value['loglunchin']) ? $value['loglunchin'] : 0,
                            'loglunchout' => isset($value['loglunchout']) ? $value['loglunchout'] : 0
                        ];
                        $row['empid'] = $this->getfieldid('empid', "client",  $value['empcode']);
                        break;

                    case 'paccount':
                        $table = 'paccount';
                        $code = 'code';
                        $id = 'line';
                        $row = ['line' => $value['line'], 'code' => $value['code'], 'codename' => $value['codename'], 'alias' => $value['alias'], 'type' => $value['type'], 'uom' => $value['uom'], 'seq' => $value['seq'], 'qty' => $value['qty'], 'istax' => $value['istax']];
                        if (isset($value['isportalloan'])) $row['isportalloan'] = $value['isportalloan'];
                        break;

                    case 'aaccount':
                        $table = 'aaccount';
                        $code = 'code';
                        $id = 'line';
                        $row = ['line' => $value['line'], 'code' => $value['code'], 'codename' => $value['codename'], 'type' => $value['type'], 'seq' => $value['seq']];
                        break;

                    case 'leavesetup':
                        $table = 'leavesetup';
                        $code = 'docno';
                        $id = 'trno';
                        $row = ['trno' => $value['trno'], 'docno' => $value['docno'], 'dateid' => $value['dateid'], 'remarks' => $value['remarks'], 'acnoid' => $value['acnoid'], 'days' => $value['days'], 'bal' => $value['bal'], 'prdstart' => $value['prdstart'], 'prdend' => $value['prdend']];
                        $row['empid'] = $this->getfieldid('empid', "client",  $value['empcode']);
                        break;

                    case 'leavetrans':
                        $table = 'leavetrans';
                        $code = 'trno';
                        $id = 'refno';
                        $row = ['refno' => $value['trno'], 'dateid' => $value['dateid'], 'trno' => $value['refno'], 'status' => $value['status'], 'adays' => $value['adays'], 'effectivity' => $value['effectivity'], 'remarks' => $value['remarks'], 'batchid' => $value['batchid'], 'iswindows' => 1];
                        $row['empid'] = $this->getfieldid('empid', "client",  $value['empid']);
                        break;

                    case 'cancel_leavetrans':
                        $table = 'leavetrans';
                        $code = 'trno';
                        $id = 'trno';
                        $row = ['trno' => $value['refno'],  'line' => $value['line'],  'status' => 'C', 'cancelrem' => $value['cancelrem'], 'canceldate' => $value['canceldate']];
                        break;

                    case 'emppics':
                        $code = 'client';
                        $id = 'clientid';
                        $table = 'client';

                        $row = ['client' => $value['empcode']];
                        $empid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$value['empcode']], '', true);

                        $filename = '';

                        if ($empid != 0) {
                            $base64String = $value['picture'];

                            // Add prefix if not already present (PHP 7.x compatible version)
                            if (strpos($base64String, 'data:image') !== 0) {
                                $base64String = "data:image/png;base64," . $base64String;
                            }

                            if (!empty($base64String)) {
                                $extension = 'png';

                                // Extract extension and data
                                if (strpos($base64String, ';base64') !== false) {
                                    list($type, $base64String) = explode(';', $base64String);
                                    list(, $base64String) = explode(',', $base64String);
                                    $extension = explode('/', $type)[1];
                                }

                                // // Validate size
                                // if (strlen($base64String) > 2 * 1024 * 1024) {
                                //     return ['status' => false, 'msg' => 'Image too large (max 2MB) for ' . $value['empcode']];
                                // }

                                // Decode image
                                $imageData = base64_decode($base64String);
                                if ($imageData === false) {
                                    return ['status' => false, 'msg' => 'Invalid image data for ' . $value['empcode']];
                                }

                                // Generate filename and ensure directory exists
                                $filename = 'employee/' . $empid . "-1." . $extension;

                                try {
                                    // Create directory if it doesn't exist
                                    if (!Storage::disk('public')->exists(dirname($filename))) {
                                        Storage::disk('public')->makeDirectory(dirname($filename));
                                    }

                                    // Save file
                                    $putResult = Storage::disk('public')->put($filename, $imageData);

                                    if (!$putResult) {
                                        return ['status' => false, 'msg' => 'Failed to save image for ' . $value['empcode']];
                                    }

                                    // return [
                                    //     'status' => false,
                                    //     'msg' => 'test result',
                                    //     'public_path' => public_path(),
                                    //     'storage_path' => storage_path(),
                                    //     'filesystem_config' => config('filesystems.disks.public'),
                                    //     'base64_length' => strlen($base64String),
                                    //     'image_data_length' => strlen($imageData),
                                    //     'attempted_path' => public_path('images/' . $filename),
                                    //     'directory_writable' => is_writable(public_path('images')),
                                    // ];

                                    $row['picture'] = '/images/' . $filename;
                                } catch (\Exception $e) {
                                    return [
                                        'status' => false,
                                        'msg' => 'Image upload error: ' . $e->getMessage(),
                                        // 'storage_path' => storage_path('app/public/' . $filename)
                                    ];
                                }
                            }
                        } else {
                            return ['status' => false, 'msg' => 'Missing employee code for ' . $value['empcode']];
                        }

                        break;
                }
                if (!empty($row)) {
                    foreach ($row as $r => $v) {
                        $row[$r] = $this->othersClass->sanitizekeyfield($r, $row[$r], '', 0, ['clientname']);
                    }
                    foreach ($row_employee as $re => $ve) {
                        $row_employee[$re] = $this->othersClass->sanitizekeyfield($re, $row_employee[$re]);

                        if (isset($row['isactive'])) {
                            ($row['isactive'] == 0) ? $row['isinactive'] = 1 : $row['isinactive'] = 0;
                        }
                    }

                    switch ($type) {
                        case 'timerec':
                            $exist = $this->coreFunctions->getfieldvalue($table, $id, "machno=? and userid=? and timeinout=?", [$row['machno'], $row['userid'], $row['timeinout']]);
                            break;
                        case 'standardtrans':
                            // $exist = $this->coreFunctions->getfieldvalue($table, $id, "trno=? and batchid=? and dateid=? and acnoid=? and empid=? and manualref=?", [$row['trno'], $row['batchid'], $row['dateid'], $row['acnoid'], $row['empid'], $row['manualref']]);
                            $this->coreFunctions->execqry("delete from " . $table . " where trno=? and batchid=? and acnoid=? and empid=? and manualref=?", '', [$row['trno'], $row['batchid'], $row['acnoid'], $row['empid'], $row['manualref']]);
                            $exist = 0;
                            break;
                        case 'paytrancurrent':
                        case 'paytranhistory':
                        case 'shiftdetail':
                            $exist = 0;
                            break;
                        case 'leavetrans':
                            $exist = $this->coreFunctions->getfieldvalue($table, $id, "refno=? and trno=?", [$row['refno'], $row['trno']]);
                            break;
                        case 'cancel_leavetrans':
                            $exist = $this->coreFunctions->getfieldvalue($table, $id, "line=? and trno=?", [$row['line'], $row['trno']]);
                            break;
                        case 'timecard':
                            $row['dateid'] = $this->othersClass->sanitizekeyfield('dateonly', $row['dateid']);
                            if ($row['empid'] == 0) {
                                return ['status' => false, 'msg' => 'Failed to timecard missing employee ' . json_encode($row)];
                            }
                            $exist = $this->coreFunctions->getfieldvalue($table, $id, "empid=? and dateid=?", [$row['empid'], $row['dateid']]);
                            break;
                        default:
                            $exist = $this->coreFunctions->getfieldvalue($table, $id, $code . "=?", [$row[$code]]);
                            break;
                    }
                    $result = 0;
                    if ($exist) {
                        switch ($type) {
                            case 'department':
                            case 'employee':
                                $row['editdate'] = $this->othersClass->getCurrentTimeStamp();
                                $row['editby'] = 'SYNCING';
                                if ($type == 'employee') {
                                    unset($row['email']);
                                    unset($row['password']);
                                }
                                break;
                        }

                        switch ($type) {
                            case 'timerec':
                                $result = true;
                                break;
                            case 'timecard':
                                $result = $this->coreFunctions->sbcupdate($table, $row, ['empid' => $row['empid'], 'dateid' => $row['dateid']]);
                                if ($result) {
                                }
                                break;
                            case 'leavetrans':
                                $row['editdate'] = $this->othersClass->getCurrentTimeStamp();
                                $row['editby'] = 'SYNCING';
                                $result = $this->coreFunctions->sbcupdate($table, $row, ['trno' => $row['trno'], 'refno' => $row['refno']]);
                                break;
                            case 'cancel_leavetrans':
                                $row['editdate'] = $this->othersClass->getCurrentTimeStamp();
                                $row['editby'] = 'SYNCING';
                                $result = $this->coreFunctions->sbcupdate($table, $row, ['trno' => $row['trno'], 'line' => $row['line']]);
                                break;
                            default:
                                $result = $this->coreFunctions->sbcupdate($table, $row, [$code => $row[$code]]);
                                if ($result) {
                                    if ($type == 'employee') {
                                        $row_employee['editdate'] = $this->othersClass->getCurrentTimeStamp();
                                        $row_employee['editby'] = 'SYNCING';
                                        $row_employee['empid'] = $exist;
                                        $result = $this->crud_employee($row_employee);
                                    }
                                }
                                break;
                        }
                    } else {
                        switch ($type) {
                            case 'paytrancurrent':
                            case 'paytranhistory':
                            case 'contacts':
                                $result = $this->coreFunctions->sbcinsert($table, $row);
                                break;
                            case 'emppics':
                                // case 'cancel_leavetrans':
                                $result = 1;
                                break;
                            case 'leavetrans':
                                $leaveline = $this->coreFunctions->datareader("select line as value from leavetrans where trno=" . $row['trno'] . " order by line desc limit 1");
                                if ($leaveline == '') $leaveline = 0;
                                $leaveline = $leaveline + 1;
                                $row['line'] = $leaveline;
                                $result = $this->coreFunctions->sbcinsert($table, $row);
                                if ($result) {
                                    $params = [];
                                    $params['params']['doc'] = 'LEAVEAPPLICATIONPORTAL';
                                    $params['params']['user'] = 'SYNCING';
                                    $this->logger->sbcmasterlog2($row['trno'], $params, "CREATE LEAVE FROM LOCAL, DATE: " . date('Y-m-d', strtotime($row['dateid'])) . " EFFECTIVITY: " . date('Y-m-d', strtotime($row['effectivity'])) . "  LEAVE: " . $row['adays'], "payroll_log");
                                }
                                break;
                            default:
                                switch ($type) {
                                    case 'department':
                                    case 'employee':
                                        $row['createdate'] = $this->othersClass->getCurrentTimeStamp();
                                        $row['createby'] = 'SYNCING';
                                        break;
                                }
                                $this->coreFunctions->LogConsole(json_encode($row));
                                $result = $this->coreFunctions->insertGetId($table, $row);
                                break;
                        }
                        $SQLError = $this->coreFunctions->getSQLError();
                        if ($result) {
                            if ($type == 'employee') {
                                $row_employee['editdate'] = $this->othersClass->getCurrentTimeStamp();
                                $row_employee['editby'] = 'SYNCING';
                                $row_employee['empid'] =  $result;
                                $result = $this->crud_employee($row_employee);
                            }
                        }
                    }
                    if (!$result) {
                        $this->coreFunctions->LogConsole('Failed to insert ' . json_encode($row));
                        $this->coreFunctions->sbclogger('Failed to insert2 ' . json_encode($row));
                        return ['status' => false, 'msg' => 'Failed to insert2 ' . json_encode($row) . '. SQL Error: ' . $SQLError];
                    } else {
                        if ($type == 'leavetrans') {
                            // $this->coreFunctions->sbclogger("select ifnull(sum(adays),0) as value from leavetrans where trno=" . $row['trno'] . " and status in ('A','P')");
                            $applied = $this->coreFunctions->datareader("select ifnull(sum(adays),0) as value from leavetrans where trno=" . $row['trno'] . " and status in ('A','P')", [], '', true);
                            // $this->coreFunctions->sbclogger("update leavesetup set bal=days-" . $applied . ", editby='SYNCING', editdate='" . $this->othersClass->getCurrentTimeStamp() . "' where trno=" . $row['trno']);
                            $this->coreFunctions->execqry("update leavesetup set bal=days-" . $applied . ", editby='SYNCING', editdate='" . $this->othersClass->getCurrentTimeStamp() . "' where trno=" . $row['trno']);
                        }
                    }

                    if ($type == 'timerec') {
                        $this->coreFunctions->LogConsole('ComputeTimecard');
                        $this->computetimecard($row);
                    }
                }
                $index += 1;
            }
        } catch (Exception $e) {
            $this->coreFunctions->LogConsole('insertdata - ' . $e);
            $this->coreFunctions->sbclogger('insertdata - ' . $e);
            $this->coreFunctions->sbclogger('row - ' . json_encode($row));
            return ['status' => false, 'msg' => 'insertdata - ' . json_encode($e)];
        }

        return ['status' => true, 'msg' => 'Success'];
    }

    private function getfieldid($field, $table, $code)
    {
        if ($code == '') return 0;

        $id = '';
        switch ($field) {
            case 'deptid':
            case 'empid':
            case 'obapp1':
            case 'obapp2':
            case 'supervisorid':
            case 'approver1':
                $id = $this->coreFunctions->getfieldvalue($table,  "clientid", "client=?", [$code]);
                break;
            case 'shiftid':
                $id = $this->coreFunctions->getfieldvalue($table,  "line", "shftcode=?", [$code]);
                break;
            case 'divid':
                $id = $this->coreFunctions->getfieldvalue($table,  "divid", "divcode=?", [$code]);
                break;
            case 'sectid':
                $id = $this->coreFunctions->getfieldvalue($table,  "sectid", "sectcode=?", [$code]);
                break;
            case 'emprate':
                $id = $this->coreFunctions->getfieldvalue($table,  "line", "area=?", [$code]);
                break;
        }

        if ($id == '') {
            return 0;
        }
        return $id;
    }

    private function crud_employee($row_employee)
    {
        if (!empty($row_employee)) {
            if (isset($row_employee['empid'])) {
                $empid = $this->coreFunctions->getfieldvalue('employee', "empid", "empid=?", [$row_employee['empid']]);
                if ($empid) {
                    return $this->coreFunctions->sbcupdate('employee', $row_employee, ['empid' => $row_employee['empid']]);
                } else {
                    return $this->coreFunctions->sbcinsert('employee', $row_employee);
                }
            }
        }

        return true;
    }

    private function autocreaterole($value, $companyid)
    {
        $dept = $value['deptid'];
        $div = $value['divid'];
        $sect = $value['sectid'];
        $emprate = $value['emprate'];

        $role = '';
        $data = [];

        if ($div != '') {
            $divid = $this->getfieldid('divid', 'division', $value['divid']);
            if ($divid != '') {
                $role = $div;
                $data['divid'] = $divid;
            }
        }

        if ($dept != '') {
            $deptid = $this->getfieldid('deptid', 'client', $value['deptid']);
            if ($deptid != '') {
                if ($role != '') {
                    $role .= '-' . $dept;
                } else {
                    $role .= $dept;
                }

                $data['deptid'] = $deptid;
            }
        }

        if ($companyid == 58) {
            if ($emprate != '') {
                $emprateid = $this->getfieldid('emprate', 'rateexempt', $value['emprate']);
                if ($emprateid != '') {
                    if ($role != '') {
                        $role .= '-' . $emprate;
                    } else {
                        $role .= $emprate;
                    }
                    $data['arearateid'] = $emprateid;
                }
            }
        } else {
            if ($sect != '') {
                $sectid = $this->getfieldid('sectid', 'section', $value['sectid']);
                if ($sectid != '') {
                    if ($role != '') {
                        $role .= '-' . $sect;
                    } else {
                        $role .= $sect;
                    }
                    $data['sectionid'] = $sectid;
                }
            }
        }

        if ($role != '') {
            checkidhere:
            $exist = $this->coreFunctions->getfieldvalue("rolesetup", "line",  "name=?", [$role]);
            if ($exist == '') {
                $data['name'] = $role;
                $result =  $this->coreFunctions->sbcinsert("rolesetup", $data);
                if ($result) {
                    goto checkidhere;
                } else {
                    return 0;
                }
            } else {
                return $exist;
            }
        }

        return 0;
    }

    public function computetimecard($row)
    {
        $status = true;
        $msg = '';

        try {

            if (isset($row['userid'])) {
                $employee = $this->coreFunctions->opentable("select empid from employee where idbarcode=?", [$row['userid']]);
                if ($employee) {

                    $start = date_format(date_create($row['timeinout']), 'Y-m-d');
                    $end = date_format(date_create($row['timeinout']), 'Y-m-d');

                    $this->coreFunctions->LogConsole('Post IN/OUT - ' . $employee[0]->empid . ': ' . $start . ' - ' . $end);

                    $result = $this->payrollcommon->postactualinout(null, $employee[0]->empid, $start, $end, false,  "", "", true);
                    if (!$result['status']) {
                        $msg .=  $row['userid'] . " failed. " . $result['msg'] . "...";
                        $status = false;
                    } else {
                        $config['params']['companyid'] = 0;
                        $config['params']['dataparams']['empid'] = $employee[0]->empid;
                        $config['params']['dataparams']['startdate'] = $start;
                        $config['params']['dataparams']['enddate'] = $end;
                        $config['params']['dataparams']['checkall'] = 0;

                        $this->coreFunctions->LogConsole('Compute timecard: ' . $row['timeinout']);

                        $result = app('App\Http\Classes\modules\payrollcustomform\payrollprocess')->computetimecard($config, true);
                        if ($result) {
                            $this->coreFunctions->sbcupdate("timerec", ['iscomputed' => 1], ['idbarcode' => $row['userid'], 'timeinout' => $row['timeinout']]);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            echo $e;
            $status = false;
            $msg = $e;
        }

        return ['status' =>  $status, 'msg' => $msg];
    }
}
