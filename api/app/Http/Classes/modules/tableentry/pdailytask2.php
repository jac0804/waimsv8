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

class pdailytask2
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'DAILY TASK2';
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
        $approver = $config['params']['row']['approver'];

        $cols = ['action', 'statname', 'dateid', 'clientname', 'amt', 'rem', 'username', 'rem1', 'comment', 'carem'];
        foreach ($cols as $key => $value) {
            $$value = $key;
        }
        $tab = [$this->gridname => ['gridcolumns' => $cols]];

        $stockbuttons = ['approve', 'viewdailytaskattachment'];


        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Customer';
        $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$dateid]['label'] = 'Create Date';
        $obj[0][$this->gridname]['columns'][$dateid]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$amt]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$amt]['label'] = 'Amount';
        $obj[0][$this->gridname]['columns'][$username]['label'] = 'From User';

        $obj[0][$this->gridname]['columns'][$rem]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$username]['type'] = 'label';

        $obj[0][$this->gridname]['columns'][$rem]['label'] = 'Remarks';
        $obj[0][$this->gridname]['columns'][$statname]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$statname]['label'] = 'Status';

        $obj[0][$this->gridname]['columns'][$rem1]['label'] = 'Solution Remarks';
        $obj[0][$this->gridname]['columns'][$rem1]['type'] = 'label';

        $obj[0][$this->gridname]['columns'][$action]['btns']['approve']['label'] = 'Start';

        $obj[0][$this->gridname]['columns'][$carem]['label'] = 'Comment Reply';
        $obj[0][$this->gridname]['columns'][$carem]['type'] = 'textarea';

        $obj[0][$this->gridname]['columns'][$comment]['type'] = 'textarea';
        $obj[0][$this->gridname]['columns'][$comment]['readonly'] = true;

        switch ($approver) {
            case 'RETURN':
            case 'COMMENT':
                $obj[0][$this->gridname]['columns'][$rem1]['type'] = 'coldel';
                break;
        }

        if ($approver != 'COMMENT') {
            $obj[0][$this->gridname]['columns'][$carem]['type'] = 'coldel';
        } else {
            $obj[0][$this->gridname]['columns'][$action]['btns']['approve']['label'] = 'Accept';
        }

        $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
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
        //   var_dump($config['params']);
        $adminid = $config['params']['adminid'];
        // $trno = $config['params']['row']['trno'];
        $orderby = " order by trno asc";
        // $qry="select c.clientname, date(dt.dateid) as dateid,
        //         dt.clientid,dt.trno,'DY' as doc,dt.userid, dt.amt, dt.statid,dt.rem,
        //         dt.tasktrno,dt.taskline,dt.reftrno,dt.userid,dt.donedate,dt.apvtrno,
        //         (case dt.statid when 0 then 'Pending' when '1' then 'Done' when '2' then 'Undone' end) as statname,
        //         m.modulename as doc, m.sbcpendingapp,'' as lblforapp, '' as approver
        //        from dailytask as dt
        //        left join client as c on c.clientid = dt.clientid
        //        left join pendingapp as p on p.trno=dt.trno and p.doc='DY'
        //        left join moduleapproval as m on m.modulename=p.doc
        //        where  dt.userid=" . $adminid . "  and dt.statid=2 
        //        $orderby";  

        $addOnQry = '';
        $addOnLeft = '';
        $addOnField = '';
        $addOnLeftUser = ' left join client as c2 on c2.clientid = dt.userid';
        $comment = "''";

        $filterapp = " and p.approver not in ('RETURN','COMMENT') ";
        $statname = "'For Checking'";

        $approver = $config['params']['row']['approver'];
        switch ($approver) {
            case 'RETURN':
            case 'COMMENT':
                $filterapp = " and p.approver='" . $config['params']['row']['approver'] . "' ";
                $statname = "'" . $config['params']['row']['approver'] . "'";

                if ($approver == 'COMMENT') {
                    $addOnLeft = ' left join headprrem as rem on rem.dytrno=p.trno and rem.line=p.line';
                    $addOnLeftUser = ' left join client as c2 on c2.email = rem.createby';

                    $comment = "rem.rem";
                    $addOnField = ",rem.touser";

                    $addOnQry = " union all
                    select c.clientname, date(dt.dateid) as dateid,
                    dt.clientid,p.trno,p.doc,dt.userid, dt.amt, dt.statid,dt.rem,
                    dt.tasktrno,dt.taskline,dt.reftrno,dt.userid,dt.donedate,dt.apvtrno,
                    " . $statname . " as statname,
                    m.modulename as doc, m.sbcpendingapp,'' as lblforapp, '' as approver, dt.empid, dt.origtrno, p.line as cline, dt.refx, c2.email as fruser, c2.clientname as username,dt.ischecker,
                    dt.rem1, '' as carem, rem.rem as comment,dt.taskcatid,ifnull(dt.assignedid,0) as assignedid $addOnField
                   from pendingapp as p
                   left join dailytask as dt on dt.trno=p.trno 
                   left join client as c on c.clientid = dt.clientid
                   left join moduleapproval as m on m.modulename=p.doc
                   left join headprrem as rem on rem.dytrno=p.trno and rem.line=p.line
                   left join client as c2 on c2.email = rem.createby
                   where  p.clientid=" . $adminid . "  and dt.statid=0 and dt.tasktrno=0 and p.doc='DY' $filterapp";
                }

                break;

            case 'FOR CHECKING':
                $filterapp = " and p.approver='" . $config['params']['row']['approver'] . "' ";
                // $addOnLeft = ' left join waims_attachments as wa on wa.trno=p.trno and rem.line=p.line';
                break;

            default:
                $filterapp = " and p.approver=''";
                break;
        }

        //1st qry = undone/neglect
        //2nd qry = for checking
        //3rd qry = return (daily task/comment)
        //4th qry = comment for current task


        $qry = "select c.clientname, date(dt.dateid) as dateid,
                    dt.clientid,p.trno,p.doc,dt.userid, dt.amt, dt.statid,dt.rem,
                    dt.tasktrno,dt.taskline,dt.reftrno,dt.userid,dt.donedate,dt.apvtrno,
                    (case dt.statid when 0 then 'Pending' when '1' then 'Done' when '2' then 'Undone' when '4' then 'Neglect' end) as statname,
                    m.modulename as doc, m.sbcpendingapp,'' as lblforapp, '' as approver, dt.empid, dt.origtrno, 0 as cline, dt.refx, c2.email as fruser, c2.clientname as username,dt.ischecker,
                    dt.rem1, '' as carem, " . $comment . " as comment,dt.taskcatid,ifnull(dt.assignedid,0) as assignedid $addOnField
                   from pendingapp as p
                   left join dailytask as dt on dt.trno=p.trno 
                   left join client as c on c.clientid = dt.clientid
                   left join moduleapproval as m on m.modulename=p.doc
                   $addOnLeft
                   $addOnLeftUser                   
                   where  p.clientid=" . $adminid . "  and dt.statid in (2,4) $filterapp

                   union all
                   select c.clientname, date(dt.dateid) as dateid,
                    dt.clientid,p.trno,p.doc,dt.userid, dt.amt, dt.statid,dt.rem,
                    dt.tasktrno,dt.taskline,dt.reftrno,dt.userid,dt.donedate,dt.apvtrno,
                    " . $statname . " as statname,
                    m.modulename as doc, m.sbcpendingapp,'' as lblforapp, '' as approver, dt.empid, dt.origtrno, p.line as cline, dt.refx, c2.email as fruser, c2.clientname as username,dt.ischecker,
                    dt.rem1, '' as carem, " . $comment . " as comment,dt.taskcatid,ifnull(dt.assignedid,0) as assignedid $addOnField
                   from pendingapp as p
                   left join hdailytask as dt on dt.trno=p.trno 
                   left join client as c on c.clientid = dt.clientid
                   left join moduleapproval as m on m.modulename=p.doc
                   $addOnLeft
                   $addOnLeftUser
                   where  p.clientid=" . $adminid . "  and dt.statid=1 and p.doc='DY' $filterapp
                    union all
                   select c.clientname, date(dt.dateid) as dateid,
                    dt.clientid,p.trno,p.doc,dt.userid, dt.amt, dt.statid,dt.rem,
                    dt.tasktrno,dt.taskline,dt.reftrno,dt.userid,dt.donedate,dt.apvtrno,
                    " . $statname . " as statname,
                    m.modulename as doc, m.sbcpendingapp,'' as lblforapp, '' as approver, dt.empid, dt.origtrno, 0 as cline, dt.refx, c2.email as fruser, c2.clientname as username,dt.ischecker,
                    dt.rem1, '' as carem, " . $comment . " as comment,dt.taskcatid,ifnull(dt.assignedid,0) as assignedid $addOnField
                   from pendingapp as p
                   left join hdailytask as dt on dt.trno=p.trno 
                   left join client as c on c.clientid = dt.clientid
                   left join moduleapproval as m on m.modulename=p.doc
                   $addOnLeft
                   $addOnLeftUser
                   where  p.clientid=" . $adminid . "  and dt.statid=6 and p.doc='DY' $filterapp
                   $addOnQry
                   $orderby";
        // var_dump($qry);
        // Logger($qry);
        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }


    public function updateapp($config, $status)
    {

        //status - dailytask table
        // 0	
        // 1	done
        // 2	pending
        // 3	re-assign
        // 4	negligence
        // 5	cancel


        // var_dump($config['params']);
        // break;
        $row = $config['params']['row'];
        $trno = $row['trno'];
        $statid = $row['statid'];
        $createdate = $row['dateid'];
        $adminid = $config['params']['adminid'];
        $user = $config['params']['user'];
        $data = [];
        $datenow = $this->othersClass->getCurrentTimeStamp();
        $dateid = $this->othersClass->getCurrentDate();
        $label = '';
        $config['params']['doc'] = 'DY';
        $tasktrno = $row['tasktrno'];
        $taskline = $row['taskline'];
        $creator = $this->coreFunctions->datareader("select c.clientname as value from dailytask as d
                                                          left join client as c on c.clientid=d.userid
                                                          where  d.trno = ?", [$trno]);

        $logusername = $this->coreFunctions->getfieldvalue("client", "clientname", "clientid=?", [$adminid], '', 0);

        if ($status == 'A') {
            $label =  'Successfully restarted.';

            if ($row['statname'] == 'COMMENT') {
                $label = ' Successfully accepted.';
                $rcoment = $this->coreFunctions->datareader("select rem as value from headprrem where dytrno = ? and line=?", [$row['trno'], $row['cline']]);

                $replymsg = $row['carem'];

                if ($rcoment != '') {
                    if ($row['touser'] == '') {
                        $seendate = $this->coreFunctions->sbcupdate('headprrem', ['seendate' => $datenow], ['dytrno' => $row['trno'], 'line' => $row['cline']]);
                    } else {
                        if ($row['touser'] == $user) {
                            $seendate = $this->coreFunctions->sbcupdate('headprrem', ['seendate' => $datenow], ['dytrno' => $row['trno'], 'line' => $row['cline']]);
                        } else {
                            $seendate = true; //para sa users na hindi direct sa kanya yung reply pero need din nyang mainform kasi nasa group na sya
                        }
                    }
                    //logs pag ka accept ng comment
                    $config['params']['doc'] = 'DY';

                    if ($seendate) { //pag comment lang delete na agad dito
                        $username = $this->coreFunctions->datareader("select c.clientname as value from client as c where c.clientid =?", [$adminid]);
                        $this->logger->sbcmasterlog($trno, $config, 'Comment has been seen by: ' . $username);
                        $this->coreFunctions->execqry("delete from pendingapp where doc='DY' and approver='COMMENT' and clientid=" . $adminid . " and trno=" . $trno . " and line=" . $row['cline'], 'delete');

                        if ($replymsg != '') {
                            $columns = ['rem' => $replymsg, 'dytrno' => $trno, 'createby' => $user, 'createdate' => $this->othersClass->getCurrentTimeStamp(), 'touser' => $row['fruser']];
                            if ($this->coreFunctions->sbcinsert("headprrem", $columns)) {
                                $data2 = [];
                                $url = 'App\Http\Classes\modules\taskmonitoring\\' . 'dy';
                                $qry1 = "select rem,line from headprrem where dytrno=$trno order by line desc limit 1";
                                $lines2 = $this->coreFunctions->opentable($qry1);
                                $mainreceiver = $this->coreFunctions->getfieldvalue("client", "clientid", "email=?", [$row['fruser']], '', true);
                                $this->othersClass->insertUpdatePendingapp($trno, $lines2[0]->line, 'DY', $data2, $url, $config, $mainreceiver, false, true, 'COMMENT', $logusername); //insert sa pendingapp

                                $groupusers = $this->coreFunctions->opentable("select pr.createby, client.clientid from headprrem as pr left join client on client.email=pr.createby 
                                where dytrno=" . $trno . " and pr.createby not in ('" . $user . "', '" . $row['fruser'] . "') group by pr.createby, client.clientid");
                                if (!empty($groupusers)) {
                                    foreach ($groupusers as $key_grp => $val_grp) {
                                        Logger('new comment to ' . $val_grp->createby);
                                        $this->othersClass->insertUpdatePendingapp($trno, $lines2[0]->line, 'DY', $data2, $url, $config, $val_grp->clientid, false, true, 'COMMENT', $logusername); //insert sa pendingapp
                                    }
                                }
                            }
                        }
                    }

                    return ['status' => true, 'msg' =>  $label, 'data' => [], 'reloadsbclist' => true, 'action' => 'gapplications', 'deleterow' => true];
                }

                return ['status' => false, 'msg' =>  'No existing comment', 'data' => [], 'reloadsbclist' => true, 'action' => 'gapplications', 'deleterow' => true];
            }

            if ($statid == 2 || $statid == 4) { //undone galing sa manual

                $pendingtm = $this->coreFunctions->datareader("select ifnull(count(trno),0) as value from dailytask where tasktrno<>0 and userid=" . $adminid . " and statid=0", [], '', true);
                if ($pendingtm != 0) {
                    return ['status' => false, 'msg' => 'You cant start another task if you have pending or current task.', 'data' => []];
                }


                $currentdate = date('Y-m-d', strtotime($this->othersClass->getCurrentTimeStamp()));
                // Logger($createdate . '  = ' . $currentdate);
                if ($createdate != $currentdate) { //auto create new
                    $label = 'Successfully restarted.';
                    $data = [
                        'tasktrno' => $row['tasktrno'],
                        'taskline' => $row['taskline'],
                        'reftrno' => 0,
                        'rem' => $row['rem'],
                        'amt' => $row['amt'],
                        'clientid' => $row['clientid'],
                        'userid' => $row['userid'],
                        'dateid' => $dateid,
                        'donedate' => null,
                        'statid' => 0,
                        'apvtrno' => $row['apvtrno'],
                        'isprev' => 1,
                        'createdate' => $datenow,
                        'encodeddate' => $datenow,
                        'empid' => $row['empid'],
                        'taskcatid' => $row['taskcatid'],
                        'assignedid' => $row['assignedid']
                    ];

                    if ($row['origtrno'] != 0) {
                        $data['origtrno'] = $row['origtrno'];
                    } else {
                        $data['origtrno'] = $trno;
                    }

                    if ($row['ischecker'] == 1) {
                        $data['startchecker'] = $this->othersClass->getCurrentTimeStamp();
                        $data['ischecker'] = 1;

                        if ($row['refx'] != 0) {
                            $data['refx'] = $row['refx'];
                        }
                    }


                    $dttrno = $this->coreFunctions->insertGetId('dailytask', $data);

                    $this->coreFunctions->sbcupdate('dailytask', ['reftrno' => $dttrno], ['trno' => $trno]);


                    if ($dttrno != 0) { //lipat sa history ang previous na task
                        $urlHistory = 'App\Http\Classes\modules\tableentry\\' . 'pdailytask';
                        $inserthistory = $this->coreFunctions->execqry(app($urlHistory)->transferhistoryquery(), 'insert', [$trno]);
                    }

                    if ($inserthistory) {
                        $this->coreFunctions->execqry("delete from dailytask where  trno=" . $trno, 'delete');
                        $this->coreFunctions->execqry("delete from pendingapp where  trno=" . $trno, 'delete');
                    }

                    $config['params']['doc'] = 'DY';
                    $this->logger->sbcmasterlog($dttrno, $config, 'Daily task has been re-created and started by: ' . $creator);

                    $config['params']['doc'] = 'ENTRYTASK';
                    $this->logger->sbcmasterlog2($row['tasktrno'], $config, ' Line: ' . $row['taskline'] . ' Task has been restarted by ' . $creator, 'masterfile_log');
                } else { //same day ini undone at inistart stat -2  na i aaccept
                    $label = 'Pending daily task has been restarted.';
                    $update_dailytask =  $this->coreFunctions->sbcupdate('dailytask', ['statid' => 0, 'createdate' => $this->othersClass->getCurrentTimeStamp(), 'donedate' => null, 'isprev' => 1, 'rem1' => ''], ['trno' => $trno]); // makikita na yung  done or undone uli sa pending app - isprev is 1 para hindi nya madelete ang pending niya dapat cancel
                    if ($update_dailytask) {
                        $this->coreFunctions->execqry("delete from pendingapp where  trno=" . $trno, 'delete');
                    }
                    $config['params']['doc'] = 'DY';
                    $this->logger->sbcmasterlog($trno, $config, 'Pending daily task has been restarted by: ' . $creator);

                    $config['params']['doc'] = 'ENTRYTASK';
                    $this->logger->sbcmasterlog2($row['tasktrno'], $config, ' Line: ' . $row['taskline'] . ' Task has been restarted by ' . $creator, 'masterfile_log');
                }
            } else if ($statid == 1) { // start ng checker na may checkerid
                $label = 'The task is now being checked.';
                $checkername = $this->coreFunctions->datareader("select c.clientname as value from client as c
                                                          where c.clientid =?", [$adminid]);

                $data = [
                    'tasktrno' => $row['tasktrno'],
                    'taskline' => $row['taskline'],
                    'reftrno' => 0,
                    'rem' => $row['rem'],
                    'amt' => $row['amt'],
                    'clientid' => $row['clientid'],
                    'userid' => $adminid,
                    'dateid' => $dateid,
                    'donedate' => null,
                    'statid' => 0,
                    'apvtrno' => $row['apvtrno'],
                    'isprev' => 1,
                    'createdate' => $datenow,
                    'encodeddate' => $datenow,
                    'ischecker' => 1,
                    'startchecker' => $this->othersClass->getCurrentTimeStamp(),
                    'empid' => $row['empid'],
                    'taskcatid' => $row['taskcatid'],
                    'assignedid' => $row['assignedid']
                ];

                if ($row['refx'] != 0) {
                    $data['refx'] = $row['refx'];
                } else {
                    $data['refx'] = $row['trno'];
                }

                $dttrno = $this->coreFunctions->insertGetId('dailytask', $data);

                if ($dttrno != 0) {
                    $this->coreFunctions->execqry("delete from pendingapp where doc='DY' and trno=" . $trno, 'delete');

                    if ($data['ischecker']) {
                        $username = $this->coreFunctions->getfieldvalue("client", "email", "clientid=?", [$row['userid']]);
                        $tasktitle = $data['rem'];
                        $socketmsg = "Checking task: " . $tasktitle;
                        if ($socketmsg != '') $this->othersClass->socketmsg($config, $socketmsg, '', $username);
                    }

                    $config['params']['doc'] = 'DY';
                    $this->logger->sbcmasterlog($dttrno, $config, 'Daily checking task has been created and started by  ' . $checkername);

                    if ($row['tasktrno'] != 0) {
                        $config['params']['doc'] = 'ENTRYTASK';
                        $this->logger->sbcmasterlog2($row['tasktrno'], $config, ' Line: ' . $row['taskline'] . ' Task checking started by  ' . $checkername, 'masterfile_log');
                    }
                }
            } else if ($statid == 6) { // iaccept ni user yung return ni checker - manual DY

                $username = $this->coreFunctions->datareader("select c.clientname as value from client as c
                                                          where c.clientid =?", [$adminid]);
                $data = [
                    'tasktrno' => 0,
                    'taskline' => 0,
                    'reftrno' => 0,
                    'rem' => $row['rem'],
                    'amt' => $row['amt'],
                    'clientid' => $row['clientid'],
                    'userid' => $adminid,
                    'dateid' => $dateid,
                    'donedate' => null,
                    'statid' => 0,
                    'apvtrno' => $row['apvtrno'],
                    'isprev' => 1,
                    'createdate' => $datenow,
                    'encodeddate' => $datenow,
                    'empid' => $row['empid'],
                    'refx' => $row['refx'], //to sa manual DY
                    'taskcatid' => $row['taskcatid'],
                    'assignedid' => $row['assignedid']
                ];


                $dttrno = $this->coreFunctions->insertGetId('dailytask', $data);

                if ($dttrno != 0) {
                    $this->coreFunctions->execqry("delete from pendingapp where  doc='DY' and trno=" . $trno, 'delete');

                    if ($row['empid'] != 0) {
                        $checkername = $this->coreFunctions->getfieldvalue("client", "email", "clientid=?", [$row['empid']]);
                        $tasktitle = $row['rem'];
                        $socketmsg = "The user accepted the task: " . $tasktitle;
                        if ($socketmsg != '') $this->othersClass->socketmsg($config, $socketmsg, '', $checkername);
                    }

                    $config['params']['doc'] = 'DY';
                    $this->logger->sbcmasterlog($dttrno, $config, 'Daily task has been created and started by  ' . $username);
                }
            }
        }


        return ['status' => true, 'msg' =>  $label, 'data' => [], 'getsbclistdata' => ['gapplications', 'dailytask'], 'deleterow' => true]; //'reloadsbclist' => true, 'action' => 'gapplications',
    }
} //end class
