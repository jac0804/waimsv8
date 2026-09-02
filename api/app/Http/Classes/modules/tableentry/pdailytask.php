<?php

namespace App\Http\Classes\modules\tableentry;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;
use App\Http\Classes\common\linkemail;
use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use Illuminate\Support\Facades\Storage;

class pdailytask
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'DAILY TASK';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $linkemail;
    public $tablelogs = 'task_log';
    public $style = 'width:90%;max-width:90%;';
    public $issearchshow = true;
    public $showclosebtn = true;
    public $tablelogs_del = 'del_task_log';

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
        $this->linkemail = new linkemail;
    }

    public function getAttrib()
    {
        $attrib = array(
            'load' => 0
        );
        return $attrib;
    }

    public function createTab($config)
    {
        $allowaddcredit = $this->othersClass->checkAccess($config['params']['user'], 5698);
        $modulename = $config['params']['row']['modulename'];
        $cols = ['action', 'statname', 'dateid', 'clientname', 'amt', 'rem', 'rem1'];
        $projecthead = $this->coreFunctions->getfieldvalue("client", "clientid", "client.clientid in (3863,3866,3867,3865,3868,3870) and clientid=?", [$config['params']['row']['userid']], '', true);
        if ($config['params']['row']['ischecker'] == 1) {
            array_push($cols, 'assignto');
        }

        foreach ($cols as $key => $value) {
            $$value = $key;
        }
        $tab = [$this->gridname => ['gridcolumns' => $cols]];

        if ($config['params']['row']['ischecker'] == 1) {
            if ($allowaddcredit) {
                if ($config['params']['row']['tasktrno'] != 0) {
                    $requestor = $this->coreFunctions->getfieldvalue("tmhead", "requestby", "trno=?", [$config['params']['row']['tasktrno']], '', true);
                    if ($requestor == $config['params']['adminid']) {
                        $stockbuttons = ['completetask',  'disapprove', 'undone', 'commenthistory'];
                    } else {
                        $stockbuttons = ['approve',  'disapprove', 'undone', 'commenthistory'];
                        $allowaddcredit = false;
                    }
                } else {
                    $stockbuttons = ['completetask',  'disapprove', 'undone', 'commenthistory'];
                }
            } else {
                $stockbuttons = ['approve',  'disapprove', 'undone', 'commenthistory'];
            }
        } else {
            $stockbuttons = ['approve', 'disapprove', 'delete', 'cancel', 'commenthistory']; //, 'addattachments'
        }

        if ($modulename == 'Task Monitoring') {
            array_push($stockbuttons, 'viewtaskinfo2'); //viewing ng comments
        }

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Customer';
        $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$dateid]['label'] = 'Create Date';
        $obj[0][$this->gridname]['columns'][$dateid]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$amt]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$amt]['label'] = 'Amount';
        $obj[0][$this->gridname]['columns'][$amt]['style'] = 'width:80px;whiteSpace: normal;min-width:80px;max-width:80px;';
        $obj[0][$this->gridname]['columns'][$rem]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;max-width:200px;';
        $obj[0][$this->gridname]['columns'][$clientname]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;max-width:150px;';
        $obj[0][$this->gridname]['columns'][$rem1]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;max-width:150px;';
        $obj[0][$this->gridname]['columns'][$rem]['type'] = 'textarea';
        $obj[0][$this->gridname]['columns'][$rem]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$rem]['label'] = 'Remarks';
        $obj[0][$this->gridname]['columns'][$statname]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$statname]['label'] = 'Status';
        $obj[0][$this->gridname]['columns'][$rem1]['label'] = 'Solution Remarks';
        $obj[0][$this->gridname]['columns'][$rem1]['type'] = 'textarea';

        if ($config['params']['row']['statid'] == 1 || $config['params']['row']['statid'] == 6) { //return and done
            $obj[0][$this->gridname]['columns'][$rem1]['type'] = 'label';
        }

        if ($config['params']['row']['ischecker'] == 1) {

            $obj[0][$this->gridname]['columns'][$action]['btns']['disapprove']['label'] = 'Return';

            $obj[0][$this->gridname]['columns'][$action]['btns']['disapprove']['action'] = 'customform';
            $obj[0][$this->gridname]['columns'][$action]['btns']['disapprove']['lookupclass'] = 'viewtaskcomment';
            $obj[0][$this->gridname]['columns'][$action]['btns']['disapprove']['class'] = 'btnviewtaskcomment';
            $obj[0][$this->gridname]['columns'][$action]['btns']['disapprove']['access'] = 'edititem';

            $obj[0][$this->gridname]['columns'][$action]['btns']['disapprove']['checkfield'] = 'iscomplete';
            $obj[0][$this->gridname]['columns'][$action]['btns']['undone']['checkfield'] = 'iscomplete';


            $obj[0][$this->gridname]['columns'][$assignto]['type'] = 'label';
            $obj[0][$this->gridname]['columns'][$assignto]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;max-width:100px;';
            //3.26.2026- rwen

            if ($allowaddcredit) {
                $obj[0][$this->gridname]['columns'][$action]['btns']['completetask']['action'] = 'customform';
                $obj[0][$this->gridname]['columns'][$action]['btns']['completetask']['lookupclass'] = 'viewtaskhistory';
                $obj[0][$this->gridname]['columns'][$action]['btns']['completetask']['class'] = 'btnviewtaskhistory';
                $obj[0][$this->gridname]['columns'][$action]['btns']['completetask']['access'] = 'edititem';
                $obj[0][$this->gridname]['columns'][$action]['btns']['completetask']['color'] = 'green';
                $obj[0][$this->gridname]['columns'][$action]['btns']['completetask']['checkfield'] = 'iscomplete';
            } else {
                $obj[0][$this->gridname]['columns'][$action]['btns']['approve']['label'] = 'Complete';
                $obj[0][$this->gridname]['columns'][$action]['btns']['approve']['checkfield'] = 'iscomplete';
            }
        } else {
            $obj[0][$this->gridname]['columns'][$action]['btns']['approve']['label'] = 'Done';
            $obj[0][$this->gridname]['columns'][$action]['btns']['disapprove']['label'] = 'Undone';
            $obj[0][$this->gridname]['columns'][$action]['btns']['delete']['label'] = 'Delete Task';

            $obj[0][$this->gridname]['columns'][$action]['btns']['delete']['checkfield'] = 'isdelete';
            $obj[0][$this->gridname]['columns'][$action]['btns']['approve']['checkfield'] = 'isdone';
            $obj[0][$this->gridname]['columns'][$action]['btns']['disapprove']['checkfield'] = 'isundone';
            // $obj[0][$this->gridname]['columns'][$action]['btns']['addattachments']['checkfield'] = 'isattach';
            $obj[0][$this->gridname]['columns'][$action]['btns']['cancel']['checkfield'] = 'iscancel';
        }

        // $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }

    public function createtabbutton($config)
    {

        $obj = [];
        return $obj;
    }

    public function createHeadField($config)
    {
        return array('col1' => []);
    }

    public function paramsdata($config)
    {
        return [];
    }

    public function data($config)
    {
        return [];
    }



    public function loaddata($config)
    {
        $adminid = $config['params']['adminid'];
        $trno = $config['params']['row']['trno'];
        $currentdate = date('Y-m-d', strtotime($this->othersClass->getCurrentTimeStamp()));

        //pag true ay hide
        $orderby = " order by trno asc";
        $qry = "select if(dt.reseller<>'',concat(c.clientname,' / ',dt.reseller),c.clientname) as clientname, date(dt.dateid) as dateid,
                    dt.clientid,dt.trno,'DY' as doc,dt.userid, dt.amt, dt.statid,dt.rem,
                    dt.tasktrno,dt.taskline,dt.reftrno,dt.userid,dt.donedate,dt.apvtrno,
                    (case dt.statid when '0' then 'Pending' when '1' then 'Done' when '2' then 'Undone' when '5' then 'Cancelled' when '6' then 'Return' end) as statname,dt.isprev,
                    
                    if(dt.statid = 0 and dt.isprev = 0 and dt.tasktrno = 0,'false','true') as isdelete,
                    if(dt.statid in (1,5), 'true','false') as isdone,
                    if(dt.statid in (1,2,5),'true','false') as isundone,
                 
                    if(dt.statid in (5,0) and dt.isprev = 1 and dt.tasktrno = 0,'false','true') as iscancel,

                    if(dt.statid in (1,6),'true','false') as iscomplete,
                    dt.ischecker, date(dt.startchecker) as startchecker,assigned.clientname as assignto, dt.empid,
                    dt.origtrno, dt.refx, ifnull(tm.userid,0) as tmuserid, ifnull(dt.rem1,'') as rem1, dt.createdate,ifnull(dt.assignedid,0) as assignedid,req.category,req.line as catline

                   from dailytask as dt
                   left join client as c on c.clientid = dt.clientid
                   left join tmdetail as tm on tm.trno=dt.tasktrno and tm.line=dt.taskline
                   left join client as assigned on assigned.clientid=tm.userid
                   left join reqcategory as req on req.line=dt.taskcatid
                   where dt.trno=" . $trno . " and  dt.userid=" . $adminid . "  and date(dt.dateid) ='" . $currentdate . "' 

                   union all

                   select if(dt.reseller<>'',concat(c.clientname,' / ',dt.reseller),c.clientname) as clientname, date(dt.dateid) as dateid,
                    dt.clientid,dt.trno,'DY' as doc,dt.userid, dt.amt, dt.statid,dt.rem,
                    dt.tasktrno,dt.taskline,dt.reftrno,dt.userid,dt.donedate,dt.apvtrno,
                    (case dt.statid when '0' then 'Pending' when '1' then 'Done' when '2' then 'Undone' when '5' then 'Cancelled' when '6' then 'Return' end) as statname,dt.isprev,
                    
                    if(dt.statid = 0 and dt.isprev = 0 and dt.tasktrno = 0,'false','true') as isdelete,
                    if(dt.statid in (1,5),'true','false') as isdone,
                    if(dt.statid in (1,2,5),'true','false') as isundone,
                 
                    if(dt.statid in (5,0) and dt.isprev = 1 and dt.tasktrno = 0,'false','true') as iscancel,
                    if(dt.statid in (1,6),'true','false') as iscomplete,
                    dt.ischecker, date(dt.startchecker) as startchecker,assigned.clientname as assignto, dt.empid, 
                    dt.origtrno, dt.refx, ifnull(tm.userid,0) as tmuserid, ifnull(dt.rem1,'') as rem1, dt.createdate,ifnull(dt.assignedid,0) as assignedid,req.category,req.line as catline

                   from hdailytask as dt
                   left join client as c on c.clientid = dt.clientid
                   left join tmdetail as tm on tm.trno=dt.tasktrno and tm.line=dt.taskline
                   left join client as assigned on assigned.clientid=tm.userid
                   left join reqcategory as req on req.line=dt.taskcatid
                   where dt.trno=" . $trno . " and  dt.userid=" . $adminid . " and date(dt.dateid) ='" . $currentdate . "'  
            
                   $orderby";

        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }


    public function updateapp($config, $status)
    {
        $row = $config['params']['row'];
        $trno = $row['trno'];
        $statid = $row['statid'];
        $ischecker = $row['ischecker'];
        $dycheckerid = $row['empid'];
        $startchecker = $row['startchecker'];
        $createdate = $row['dateid'];
        $adminid = $config['params']['adminid'];
        $data = [];
        $datenow = $this->othersClass->getCurrentTimeStamp();
        $label = '';
        $config['params']['doc'] = 'DY';
        $tmstat = '';
        $solutionrem = $row['rem1'];
        $amount = $row['amt'];
        $genmsg = "";


        //user
        $creator = $this->coreFunctions->datareader("select c.clientname as value from dailytask as d
                                                          left join client as c on c.clientid=d.userid
                                                          where  d.trno = ?", [$trno]);

        if ($status == 'A') { //done
            $requestorid = 0;
            if ($row['tasktrno'] != 0) $requestorid = $this->coreFunctions->getfieldvalue("tmhead", "requestby", "trno=?", [$row['tasktrno']]);

            if ($statid == 0) {

                if ($solutionrem == '') {
                    return ['status' => false, 'msg' => 'Please input valid solution remarks or findings for this task before tagged as completed', 'data' => []];
                }

                $username = $this->coreFunctions->datareader("select email as value from client where clientid = ? ", [$adminid]);
                $qry = "select i.itemid as value from hsohead as head
                    left join hsostock as stock on stock.trno = head.trno
                    left join item as i on i.itemid = stock.itemid
                    where stock.dytrno = ? and head.createby = ? limit 1";

                $soitem = $this->coreFunctions->opentable($qry, [$trno, $username]);

                //  Walang SO
                if (empty($soitem)) {
                    goto startprocess;
                }

                $sjgeneration = $this->generatesj($config);

                // Failed ang generation
                if ($sjgeneration['status'] == false) {
                    return ['status' => false, 'msg' => $sjgeneration['msg'], 'data' => []];
                }

                // Success ang generation
                $genmsg = $sjgeneration['msg'];

                startprocess:

                if ($ischecker == 1 && $startchecker != null) { // Complete ni checker - stat5 - complete

                    if ($requestorid == $adminid) {
                        $tmstat = $this->coreFunctions->sbcupdate('tmdetail', ['enddate' => $datenow, 'status' => 5], ['trno' => $row['tasktrno'], 'line' => $row['taskline']]);
                        $label =  'Successfully completed the task.';
                        if ($tmstat) { //update
                            $update_dailytask =  $this->coreFunctions->sbcupdate('dailytask', ['statid' => 1, 'donedate' => $datenow, 'rem1' => $solutionrem], ['trno' => $trno]);

                            if ($update_dailytask) { // insert sa history
                                $dtinsert = $this->coreFunctions->execqry($this->transferhistoryquery(), 'insert', [$trno]);

                                $this->coreFunctions->execqry("delete from dailytask where  trno=" . $trno, 'delete');
                                $config['params']['doc'] = 'DY';
                                $this->logger->sbcmasterlog($trno, $config, ' Daily task has been done by ' . $creator);

                                $config['params']['doc'] = 'ENTRYTASK';
                                $this->logger->sbcmasterlog2($row['tasktrno'], $config, ' Line: ' . $row['taskline'] . ' , ' . $creator . ' ' . ' has already completed the task.', 'masterfile_log');

                                $socketmsg = "Task checked and completed: " . $row['rem'];

                                if ($dycheckerid != 0) {
                                    $usernameC = $this->coreFunctions->getfieldvalue("client", "email", "clientid=?", [$dycheckerid]);
                                    if ($socketmsg != '' && $usernameC != '') $this->othersClass->socketmsg($config, $socketmsg, '', $usernameC);
                                }

                                if ($row['tmuserid'] != 0) {
                                    $usernameU = $this->coreFunctions->getfieldvalue("client", "email", "clientid=?", [$row['tmuserid']]);
                                    if ($socketmsg != '' && $usernameU != '') $this->othersClass->socketmsg($config, $socketmsg, '', $usernameU);
                                }
                            }
                        }
                    } else {
                        if ($dycheckerid != $requestorid && $requestorid != 0) {
                            $dycheckerid = 0;
                        }
                        goto ManualDoneHere;
                    }
                } else {



                    ///MANUAL DONE
                    ManualDoneHere:
                    $update_dailytask =  $this->coreFunctions->sbcupdate('dailytask', ['statid' => 1, 'donedate' => $datenow, 'rem1' => $solutionrem], ['trno' => $trno]);
                    $label =  'Successfully done.' . ' ' . $genmsg;
                    if ($update_dailytask) {
                        $dtinsert = $this->coreFunctions->execqry($this->transferhistoryquery(), 'insert', [$trno]);

                        if ($dtinsert) {
                            $this->coreFunctions->execqry("delete from dailytask where  trno=" . $trno, 'delete');

                            if ($dycheckerid != 0) {
                                if ($requestorid == $dycheckerid) goto UpdateRequestorTM;

                                if ($dycheckerid == $adminid) {
                                    //done ng checker para sa manual DY
                                    $config['params']['doc'] = 'DY';
                                    $this->logger->sbcmasterlog($trno, $config, 'Task checked and completed');

                                    $dyuser = $this->coreFunctions->getfieldvalue("hdailytask", "userid", "trno=?", [$row['refx']]);
                                    if ($dyuser != 0) {
                                        $username = $this->coreFunctions->getfieldvalue("client", "email", "clientid=?", [$dyuser]);
                                        $socketmsg = "Task checked and completed: " . $row['rem'];
                                        if ($socketmsg != '' && $username != '') $this->othersClass->socketmsg($config, $socketmsg, '', $username);
                                    }

                                    // REIMBURSEMENT
                                    if ($config['params']['doc'] == 'DY') {
                                        if ($amount != 0) {
                                            $url = 'App\Http\Classes\modules\taskmonitoring\\' . 'dy';
                                            $r =  $this->othersClass->insertUpdatePendingapp($trno, 0, 'DY', [], $url, $config, 3863, false, true, 'REIMBURSEMENT');
                                            $this->coreFunctions->LogConsole('Amount: ' . $amount . ' msg: ' . $r['msg']);
                                        }
                                    }
                                } else {
                                    //done ng user tapos for checking kay checker/requestor
                                    $url = 'App\Http\Classes\modules\taskmonitoring\\' . 'dy';
                                    $this->othersClass->insertUpdatePendingapp($trno, 0, 'DY', [], $url, $config, $dycheckerid, false, true, 'FOR CHECKING'); //create sa pendingapp 
                                    $config['params']['doc'] = 'DY';
                                    $this->logger->sbcmasterlog($trno, $config, ' Task submitted for checking by ' . $creator);

                                    $config['params']['doc'] = 'ENTRYTASK';
                                    $this->logger->sbcmasterlog2($row['tasktrno'], $config, ' Line: ' . $row['taskline'] . ' Task submitted for checking by ' . $creator, 'task_log');
                                }
                            } else {
                                UpdateRequestorTM:
                                if ($row['tasktrno'] != 0) { //KAPAG GALING TASK MONITORING
                                    $tmdetail = ['status' => 4, 'fcheckingdate' => $datenow, 'editdate' => $datenow, 'editby' => $config['params']['user']];

                                    //auto complete if user is the requestor of the task and no checker assigned
                                    if ($requestorid == $adminid) {
                                        $tmdetail['status'] = 5;
                                        $tmdetail['enddate'] = $datenow;
                                    }

                                    if ($this->coreFunctions->sbcupdate('tmdetail', $tmdetail, ['trno' => $row['tasktrno'], 'line' => $row['taskline']])) {

                                        if ($tmdetail['status'] != 5) {
                                            //UPDATE SA PENDINGAPP NG CHECKER
                                            $url = 'App\Http\Classes\modules\taskmonitoring\\' . 'tm';

                                            $this->othersClass->insertUpdatePendingapp($row['tasktrno'], $row['taskline'], 'TM', [], $url, $config, $requestorid, false, true, 'FOR CHECKING'); //create sa pendingapp

                                            $config['params']['doc'] = 'ENTRYTASK';
                                            $this->logger->sbcmasterlog2($row['tasktrno'], $config, ' Line: ' . $row['taskline'] . ' Task submitted for checking by ' . $creator, 'masterfile_log');
                                        }
                                    }
                                }
                            }
                        }
                        $config['params']['doc'] = 'DY';
                        $this->logger->sbcmasterlog($trno, $config, ' Daily task has been done by ' . $creator);
                    }
                }
            } else {
                return ['status' => false, 'msg' =>  'Task has been done or return. Please refresh the page.', 'data' => [], 'getsbclistdata' => ['gapplications', 'dailytask'], 'deleterow' => true];
            }
        } else { //undone 

            if ($solutionrem == '') {
                return ['status' => false, 'msg' => 'Please input valid reason for not completing this task', 'data' => []];
            }

            $label = 'Daily task is pending.';
            $update_dailytask =  $this->coreFunctions->sbcupdate('dailytask', ['statid' => 2, 'donedate' => $datenow], ['trno' => $trno]);
            $data2 = [];
            $url2 = 'App\Http\Classes\modules\taskmonitoring\\' . 'dy';
            $insertundone = $this->othersClass->insertUpdatePendingapp($trno, 0, 'DY', $data2, $url2, $config, $adminid, false, true); //insert sa pendingapp
            if ($insertundone) {

                if ($solutionrem != '') {
                    $columns = [
                        'dytrno' => $trno,
                        'rem' => $solutionrem,
                        'createby' => $config['params']['user'],
                        'createdate' => $this->othersClass->getCurrentTimeStamp(),
                        'deadline2' => $row['createdate'] //task created
                    ];
                    if ($row['tasktrno'] != 0) {
                        $columns['dytrno'] = 0;
                        $columns['tmtrno'] = $row['tasktrno'];
                        $columns['tmline'] = $row['taskline'];
                    }
                    // Logger(json_encode($columns));
                    $this->coreFunctions->sbcinsert("headprrem", $columns);
                }

                $config['params']['doc'] = 'DY';
                $this->logger->sbcmasterlog($trno, $config, 'Daily task has been marked as pending by: ' . $creator); //pending logs sa dailytask

                $config['params']['doc'] = 'ENTRYTASK';
                $this->logger->sbcmasterlog2($row['tasktrno'], $config, ' Line: ' . $row['taskline'] . ' This task has been marked as pending by ' . $creator, 'masterfile_log');
            } else {
                return ['status' => false, 'msg' => 'Failed to undone task', 'data' => []];
            }
        }

        return ['status' => true, 'msg' =>  $label, 'data' => [], 'getsbclistdata' => ['gapplications', 'dailytask'], 'deleterow' => true]; //'reloadsbclist' => true, 'action' => 'dailytask',
    }


    public function delete($config)
    {
        $config['params']['doc'] = 'DY';
        $row = $config['params']['row'];
        $trno = $row['trno'];
        $creator = $this->coreFunctions->datareader("select c.clientname as value from dailytask as d
                                                          left join client as c on c.clientid=d.userid
                                                          where  d.trno = ?", [$trno]);

        if ($row['tasktrno'] == 0) {
            if ($row['statid'] == 0 && $row['isprev'] == 0) {
                $qry = "delete from dailytask where trno=?";
                $this->coreFunctions->execqry($qry, 'delete', [$trno]);
                $this->logger->sbcmasterlog($trno, $config, ' Daily task has been deleted by ' . $creator);
                return ['status' => true, 'msg' => 'Successfully deleted.', 'data' => [], 'reloadsbclist' => true, 'action' => 'dailytask', 'deleterow' => true];
            } else {
                return ['status' => false, 'msg' => 'Unable to delete. The task has already been completed or is currently pending.'];
            }
        } else {
            return ['status' => false, 'msg' => 'Unable to delete. This task is from task monitoring module.'];
        }
    }


    public function cancel($config)
    {
        $row = $config['params']['row'];
        $trno = $row['trno'];
        $config['params']['doc'] = 'DY';
        $datenow = $this->othersClass->getCurrentTimeStamp();
        $creator = $this->coreFunctions->datareader("select c.clientname as value from dailytask as d
                                                          left join client as c on c.clientid=d.userid
                                                          where  d.trno = ?", [$trno]);
        if ($row['tasktrno'] == 0) {
            if ($row['isprev'] == 1) {
                $update_dailytask =  $this->coreFunctions->sbcupdate('dailytask', ['statid' => 5, 'donedate' => $datenow], ['trno' => $trno]);

                if ($update_dailytask) {
                    $insertcancelled = $this->coreFunctions->execqry($this->transferhistoryquery(), 'insert', [$trno]);

                    if ($insertcancelled) {
                        $this->coreFunctions->execqry("delete from pendingapp where doc='DY' and trno=" . $trno, 'delete');
                        $this->coreFunctions->execqry("delete from dailytask where  trno=" . $trno, 'delete');
                    }


                    $this->logger->sbcmasterlog($trno, $config, ' Daily task has been cancelled by: ' . $creator);
                    return ['status' => true, 'msg' => 'Successfully cancelled.', 'data' => [], 'reloadsbclist' => true, 'action' => 'dailytask', 'deleterow' => true];
                } else {
                    if ($row['statid'] == 1) {
                        return ['status' => false, 'msg' => 'Unable to cancel. The task has already been done.'];
                    } else {
                        return ['status' => false, 'msg' => 'Unable to cancel. The task has is a new create. Continue in deleting the task.'];
                    }
                }
            } else {

                if ($row['statid'] == 1) {
                    return ['status' => false, 'msg' => 'Unable to cancel. The task has already been done.'];
                } else {
                    return ['status' => false, 'msg' => 'Unable to cancel. The task has is a new create. Continue in deleting the task.'];
                }
            }
        } else { //from task monitoring
            return ['status' => false, 'msg' => 'Unable to cancel.This task is from task monitoring module.'];
        }
    }


    public function transferhistoryquery()
    {
        return "insert into hdailytask (trno,tasktrno,taskline,reftrno,rem,amt,clientid,userid,dateid,donedate,statid,apvtrno,jono,createdate,ischecker,startchecker,empid,origtrno,reseller,refx,rem1,taskcatid,encodeddate,assignedid,reimbursement)
        SELECT dt.trno,dt.tasktrno,dt.taskline,dt.reftrno,dt.rem,dt.amt,dt.clientid,dt.userid,dt.dateid,dt.donedate,dt.statid,dt.apvtrno,dt.jono,dt.createdate,dt.ischecker,dt.startchecker, dt.empid, 
        dt.origtrno, dt.reseller, dt.refx,dt.rem1,dt.taskcatid,dt.encodeddate,dt.assignedid,dt.reimbursement
        FROM dailytask as dt  where dt.trno=?";
    }

    public function generatesj($config)
    {
        $msg = "Failed to generate SJ.";
        $stat = true;
        $adminid = $config['params']['adminid'];
        $sourceTrno = $config['params']['row']['trno'];

        $username = $this->coreFunctions->datareader("select email as value from client where clientid = ? ", [$adminid]);
        $createdate = $this->othersClass->getCurrentTimeStamp();
        $dateid = $this->othersClass->getCurrentDate();

        $doc = 'SJ';
        $pvref = 'SJ';
        $sjref = 'SJ';
        $table = 'cntnum';
        $center = $config['params']['center'];
        $docnolength = $this->companysetup->getdocumentlength($config['params']);
        $path = 'App\Http\Classes\modules\sales\\' . strtolower($doc);
        $defaultContra = 'AR1';

        // $qry = "select head.client, head.clientname, i.itemname, i.itemid, head.cur, head.forex, head.address, 
        //                head.terms, date(head.due) as due, head.wh, head.agent, head.yourref, head.ourref, 
        //                head.projectid, head.vattype, head.tax, head.rem, head.trno, stock.line,
        //                stock.uom,stock.disc,stock.rem as srem,stock.amt,stock.isqty,stock.isamt,stock.iss,stock.ext,stock.qa,
        //                stock.void,stock.loc,stock.expiry,stock.kgs,stock.whid,stock.ref,stock.projectid as sprojid,
        //                head.docno,wh.client as swh,sinfo.itemdesc
        //                from hsohead as head 
        //                left join hsostock as stock on stock.trno = head.trno 
        //                left join client as wh on wh.clientid=stock.whid
        //                left join hstockinfotrans as sinfo on sinfo.trno=stock.trno and sinfo.line=stock.line
        //                left join item as i on i.itemid = stock.itemid where stock.dytrno = ? and head.createby = ? 
        //                and (
        //                    exists (select 1 from lahead sj
        //                      join lastock lst on lst.trno = sj.trno  where sj.doc = 'SJ'     and lst.refx = head.trno and lst.linex = stock.line)
        //                    or exists (select 1 from glhead sj
        //                      join glstock lst on lst.trno = sj.trno  where sj.doc = 'SJ'  and lst.refx = head.trno   and lst.linex = stock.line))";
        //                $soitem = $this->coreFunctions->opentable($qry, [$sourceTrno, $username]);

        // Check muna kung may existing SJ kahit isa
        $checkQry = "
            select i.itemname, x.sjdocno
            from hsohead as head
            left join hsostock as stock on stock.trno = head.trno
            left join item as i on i.itemid = stock.itemid
            join (
            select sj.docno as sjdocno, lst.refx, lst.linex
            from lahead sj
            join lastock lst on lst.trno = sj.trno  where sj.doc = 'SJ'
            union all
            select sj.docno as sjdocno, lst.refx, lst.linex
            from glhead sj
            join glstock lst on lst.trno = sj.trno where sj.doc = 'SJ'   ) as x on x.refx = head.trno and x.linex = stock.line where stock.dytrno = ? and head.createby = ? limit 1 ";

        $existing = $this->coreFunctions->opentable($checkQry, [$sourceTrno, $username]);

        if (!empty($existing)) {
            return ['status' => false, 'msg' => 'Failed to generate SJ. SJ document already exists for item ' . $existing[0]->itemname . '. SJ docno: '   . $existing[0]->sjdocno];
        }

        // Kapag walang existing SJ, kunin lahat ng SO items
        $qry = "
            select head.client, head.clientname, i.itemname, i.itemid, head.cur, head.forex, head.address, 
            head.terms, date(head.due) as due, head.wh, head.agent, head.yourref, head.ourref, 
            head.projectid, head.vattype, head.tax, head.rem, head.trno, stock.line,
            stock.uom, stock.disc, stock.rem as srem, stock.amt, stock.isqty, stock.isamt,
            stock.iss, stock.ext, stock.qa, stock.void, stock.loc, stock.expiry, stock.kgs,
            stock.whid, stock.ref, stock.projectid as sprojid, head.docno, wh.client as swh, sinfo.itemdesc
            from hsohead as head 
            left join hsostock as stock on stock.trno = head.trno 
            left join client as wh on wh.clientid = stock.whid
            left join hstockinfotrans as sinfo on sinfo.trno = stock.trno and sinfo.line = stock.line
            left join item as i on i.itemid = stock.itemid
            where stock.dytrno = ? and head.createby = ? ";

        $soitem = $this->coreFunctions->opentable($qry, [$sourceTrno, $username]);

        $getdoc = $this->coreFunctions->getfieldvalue($table, 'doc', 'bref=?', [$sjref]);
        $seq = $this->othersClass->getlastseq($sjref, $config, $table);

        if ($getdoc == '') {
            $seq = $this->othersClass->getlastseq($pvref, $config, $table);
        }

        $mrseq = $sjref . $seq;
        $newdocno = $this->othersClass->PadJ($mrseq, $docnolength);

        $col = ['doc' => $doc, 'docno' => $newdocno, 'seq' => $seq, 'bref' => $sjref, 'center' => $center, 'postdate' => null];
        $cntnumm = $this->coreFunctions->insertGetId($table, $col);

        $cntdata = $this->coreFunctions->opentable("select trno, docno from cntnum where doc = ? and docno = ? and center = ? limit 1", [$doc, $newdocno, $center]);

        if (empty($cntdata)) {
            $stat = false;
            return ['status' => false, 'msg' => 'Failed to generate SJ document number.'];
        }


        $sjTrno = $cntdata[0]->trno;
        $docno = $cntdata[0]->docno;

        $head = [
            'trno' => $sjTrno,
            'doc' => $doc,
            'docno' => $docno,
            'client' => $soitem[0]->client,
            'clientname' => $soitem[0]->clientname,
            'address' => $soitem[0]->address,
            'projectid' => $soitem[0]->projectid,
            'dateid' => $dateid,
            'terms' => $soitem[0]->terms,
            'due' => $soitem[0]->due,
            'yourref' => $soitem[0]->yourref,
            'ourref' => $soitem[0]->ourref,
            'cur' => $soitem[0]->cur,
            'forex' => $soitem[0]->forex,
            'vattype' => $soitem[0]->vattype,
            'tax' => $soitem[0]->tax,
            'rem' => $soitem[0]->rem,
            'wh' => $soitem[0]->wh,
            'agent' => $soitem[0]->agent,
            'createdate' => $createdate,
            'createby' => $config['params']['user'],
            'contra' => $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', [$defaultContra])
        ];

        $lahead = $this->coreFunctions->sbcinsert('lahead', $head);

        if ($lahead == 0) {
            return ['status' => false, 'msg' => 'Failed to insert SJ header.'];
        }

        foreach ($soitem as $key2 => $value) {
            $exists = $this->coreFunctions->datareader("select head.trno as value from lahead as head left join lastock as stock on stock.trno = head.trno where head.doc = 'SJ' and stock.refx = ? and stock.linex = ? limit 1", [$value->trno, $value->line]);

            if ($exists != '') {
                $stat = false;
                $msg =  $soitem[$key2]->itemname . ' already exists in SJ.';
                continue;
            }
            $config['params']['trno'] = $sjTrno;
            $config['params']['data']['itemid'] = $soitem[$key2]->itemid;
            $config['params']['data']['uom'] = $soitem[$key2]->uom;
            $config['params']['data']['disc'] = $soitem[$key2]->disc;
            $config['params']['data']['qty'] = $soitem[$key2]->isqty;

            $config['params']['data']['wh'] = $soitem[$key2]->swh;
            $config['params']['data']['loc'] = '';
            $config['params']['data']['expiry'] = '';
            $config['params']['data']['rem'] = '';

            $config['params']['data']['refx'] = $soitem[$key2]->trno;
            $config['params']['data']['linex'] = $soitem[$key2]->line;
            $config['params']['data']['ref'] = $soitem[$key2]->docno;

            $config['params']['data']['amt'] = $soitem[$key2]->isamt;
            $config['params']['data']['projectid'] = $soitem[$key2]->sprojid;
            if (isset($soitem[$key2]->itemdesc)) $config['params']['data']['itemdesc'] = $soitem[$key2]->itemdesc;
            $return = app($path)->additem('insert', $config, true);
            if (!$return['status']) {

                $msg = $return['msg'];
                // $msg = "Error generating SJ. Please review transaction.";

                $stat = false;
                //delete
                $cntnum = $this->coreFunctions->execqry("delete from cntnum where  trno=" . $sjTrno, 'delete');
                $sjhead = $this->coreFunctions->execqry("delete from lahead where  trno=" . $sjTrno, 'delete');
                $sjstock = $this->coreFunctions->execqry("delete from lastock where  trno=" . $sjTrno, 'delete');
                $costing = $this->coreFunctions->execqry("delete from costing where  trno=" . $sjTrno, 'delete');
                $uphsostock =  $this->coreFunctions->sbcupdate('hsostock', ['dytrno' => 0, 'qa' => 0], ['trno' => $soitem[$key2]->trno, 'line' => $soitem[$key2]->line, 'dytrno' => $sourceTrno]);

                break;
            }
        }

        if ($stat) {
            $config['docmodule']->stock = 'lastock';
            $config['docmodule']->tablelogs = 'table_log';
            $config['docmodule']->tablenum = 'cntnum';
            $config['docmodule']->hhead = 'glhead';
            $config['docmodule']->hstock = 'glstock';
            $config['docmodule']->head = 'lahead';
            $config['docmodule']->hdetail = 'gldetail';
            $config['docmodule']->detail = 'ladetail';
            $config['docmodule']->htablelogs = 'htable_log';
            $config['params']['tableid'] = $sjTrno;
            $autopost = app($path)->posttrans($config);
            $config['params']['tableid'] = 0;
            $this->tablelogs = 'task_log';
            if (!$autopost['status']) {
                $msg = $autopost['msg'];
                goto deleteall;
            }

            $msg = "Generated. Doc No: ' . $docno";
        } else {
            $msg = $return['msg'];
            // $msg = "Error generating SJ. Please review transaction.";
            deleteall:
            $stat = false;
            // delete
            $cntnum = $this->coreFunctions->execqry("delete from cntnum where  trno=" . $sjTrno, 'delete');
            $sjhead = $this->coreFunctions->execqry("delete from lahead where  trno=" . $sjTrno, 'delete');
            $sjstock = $this->coreFunctions->execqry("delete from lastock where  trno=" . $sjTrno, 'delete');
            $costing = $this->coreFunctions->execqry("delete from costing where  trno=" . $sjTrno, 'delete');
            $uphsostock =  $this->coreFunctions->sbcupdate('hsostock', ['dytrno' => 0, 'qa' => 0], ['trno' => $soitem[$key2]->trno, 'line' => $soitem[$key2]->line, 'dytrno' => $sourceTrno]);
        }



        return ['status' => $stat, 'msg' => $msg];
    }
}
