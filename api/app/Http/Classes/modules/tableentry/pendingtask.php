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

class pendingtask
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'PENDING TASK';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $linkemail;
    public $tablelogs = 'masterfile_log';
    public $style = 'width:90%;max-width:90%;';
    public $issearchshow = true;
    public $showclosebtn = true;
    // public $tablenum = 'hrisnum';
    // public $head = 'personreq';
    // public $hhead = 'hpersonreq';
    public $tablelogs_del = 'del_masterfile_log';

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
        $row = $config['params']['row'];
        $approver = $row['approver'];

        $cols = ['action', 'status', 'dateid2', 'title', 'clientname', 'dateid', 'requestorname', 'assignto'];
        foreach ($cols as $key => $value) {
            $$value = $key;
        }
        $tab = [$this->gridname => ['gridcolumns' => $cols]];

        // if ($approver != '' && $approver != 'COMMENT') {
        //     $stockbuttons = ['approve', 'disapprove'];
        // } else {
        $stockbuttons = ['approve'];
        // }

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Customer';
        $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$requestorname]['label'] = 'Request By';
        $obj[0][$this->gridname]['columns'][$dateid]['label'] = 'Date Request';
        $obj[0][$this->gridname]['columns'][$dateid2]['label'] = 'Submit Date';
        $obj[0][$this->gridname]['columns'][$dateid]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$dateid2]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$title]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$title]['label'] = 'Task';
        $obj[0][$this->gridname]['columns'][$assignto]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$assignto]['label'] = 'Assigned to';
        $obj[0][$this->gridname]['columns'][0]['btns']['approve']['label'] = 'Start';
        $obj[0][$this->gridname]['columns'][$status]['type'] = 'label';

        // if ($approver != '' && $approver != 'COMMENT') {
        //     $obj[0][$this->gridname]['columns'][0]['btns']['disapprove']['label'] = 'Return';
        //     $obj[0][$this->gridname]['columns'][0]['btns']['disapprove']['action'] = 'customform';
        //     $obj[0][$this->gridname]['columns'][0]['btns']['disapprove']['lookupclass'] = 'viewhistoricalcomments';
        //     $obj[0][$this->gridname]['columns'][0]['btns']['disapprove']['class'] = 'btnviewhistoricalcomments';
        //     $obj[0][$this->gridname]['columns'][0]['btns']['disapprove']['access'] = 'edititem';

        //     $obj[0][$this->gridname]['columns'][0]['btns']['approve']['label'] = 'Completed';
        // } else
        if ($approver == 'COMMENT') {
            $obj[0][$this->gridname]['columns'][$status]['label'] = 'Comment';
            $obj[0][$this->gridname]['columns'][0]['btns']['approve']['label'] = 'Accept';
        }
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
        $row = $config['params']['row'];
        $approver = $row['approver'];
        $stat = ", 'Task'";
        $addf = "";
        $orderby = "";
        $pjoin = " left join tmdetail as d on d.trno=p.trno  and d.line=p.line ";
        $filter = " and d.fcheckingdate is null and p.approver='' ";

        switch ($approver) {
            case 'FOR CHECKING':
                $stat = ", 'For Checking' ";
                $filter = ' and d.fcheckingdate is not null ';
                $orderby = " order by d.fcheckingdate";
                break;
            case 'RETURN':
                $stat = ", 'Return' ";
                $filter = " and d.fcheckingdate is null and p.approver='RETURN' ";
                break;
            case 'COMMENT':
                $stat = ", hp.rem ";
                $addf = " , hp.line as commentline";
                $pjoin = " left join headprrem as hp on hp.line = p.line
                           left join tmdetail as d  on d.trno = p.trno  and d.line = hp.tmline and d.line=hp.tmline";
                $orderby = " order by commentline asc";
                $filter = " and p.approver='COMMENT' and hp.line is not null ";
                break;
        }

        $qry = "select c.clientid as clid,c.clientname,e.clientid as request,e.clientname as requestorname,d.fcheckingdate as dateid2,
                d.userid,n.clientname as assignto,p.approver,h.trno, d.line $stat  as status, date(h.dateid) as dateid,
                d.title,h.amount,m.modulename as doc, m.sbcpendingapp,'' as lblforapp, h.checkerid, h.requestby, e.email as requestbycode, d.taskcatid $addf
            from pendingapp as p
            $pjoin
            left join tmhead as h on h.trno=d.trno
            left join client as c on c.clientid = h.clientid
            left join client as e on e.clientid = h.requestby
            left join client as n on n.clientid = d.userid
            left join moduleapproval as m on m.modulename=p.doc
             where p.doc='TM' and p.clientid=" . $adminid . "  " . $filter . " $orderby";
        // Logger($qry);
        // var_dump($qry);
        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }


    public function updateapp($config, $status)
    {
        // var_dump($config['params']);
        $row = $config['params']['row'];
        $approver = $row['approver'];
        $adminid = $config['params']['adminid'];
        $data = [];
        $trno = $row['trno'];
        $doc = $row['doc'];
        $line = $row['line'];
        $commentstatus = $row['status'];
        $username = $row['assignto'];
        $userid = $row['userid'];
        $checkerid = $row['checkerid'];
        $data = [];
        $data2 = [];

        $label = '';
        $datenow = $this->othersClass->getCurrentTimeStamp();
        $dateid = $this->othersClass->getCurrentDate();
        $status1 = $this->coreFunctions->datareader("select status as value from tmdetail  as d  where d.trno=? and d.line=?", [$trno, $line]);


        if ($approver == 'COMMENT') { //lahat ng comment dito dadaan pagka accept
            $cline = $row['commentline'];
            $label = ' Successfully accepted.';
            $rcoment = $this->coreFunctions->datareader("select rem as value from headprrem where line =$cline and tmtrno = ? and tmline = ?", [$trno, $line]);

            if ($rcoment != '') {
                $seendate = $this->coreFunctions->sbcupdate('headprrem', ['seendate' => $datenow], ['tmtrno' => $trno, 'tmline' => $line, 'line' => $cline]);
                //logs pag ka accept ng comment
                $config['params']['doc'] = 'ENTRYTASK';

                // if ($adminid != $userid) { //not equal to assined
                //     $username = $row['requestorname'];
                // }
                $username = $this->coreFunctions->datareader("select c.clientname as value from client as c
                                                          where c.clientid =?", [$adminid]);

                $this->logger->sbcmasterlog($trno, $config, ' Line: ' . $line . ' , Comment has been seen by: ' . $username);
                $config['params']['doc'] = $doc;
                if ($seendate) { //pag comment lang delete na agad dito
                    $this->coreFunctions->execqry("delete from pendingapp where doc='TM' and approver='COMMENT' and trno=" . $trno . " and line=" . $cline, 'delete');
                }
            }
        } else {

            if ($status1 == 1 || $status1 == 2) { //1=open with user / 2=pending

                $pendingtm = $this->coreFunctions->datareader("select ifnull(count(trno),0) as value from dailytask where tasktrno<>0 and userid=" . $adminid . " and statid=0", [], '', true);
                if ($pendingtm != 0) {
                    return ['status' => false, 'msg' => 'You cant start another task if you have pending or current task.', 'data' => []];
                }

                if ($status == 'A') { //start 3- ongoing stat
                    $stat = $this->coreFunctions->sbcupdate('tmdetail', ['acceptdate' => $datenow, 'startdate' => $datenow, 'status' => 3], ['trno' => $trno, 'line' => $line]);
                    $assigned = $this->coreFunctions->datareader("select c.clientname as value from tmdetail as d
                                                          left join client as c on c.clientid=d.userid
                                                          where d.line =? and d.trno = ?", [$line, $trno]);


                    if ($stat) {
                        $this->coreFunctions->execqry("delete from pendingapp where doc='TM' and trno=" . $trno . " and line=" . $line, 'delete');

                        $data = [
                            'tasktrno' => $trno,
                            'taskline' => $line,
                            'reftrno' => 0,
                            'rem' => $config['params']['row']['title'],
                            'amt' => 0,
                            'clientid' =>  $config['params']['row']['clid'],
                            'userid' => $adminid,
                            'dateid' => $dateid,
                            'donedate' => null,
                            'statid' => 0,
                            'apvtrno' => 0,
                            'isprev' => 0,
                            'createdate' => $datenow,
                            'encodeddate' => $datenow,
                            'empid' => $checkerid,
                            'taskcatid' => $config['params']['row']['taskcatid'],
                            'assignedid' => $config['params']['row']['userid']
                        ];

                        $dttrno = $this->coreFunctions->insertGetId('dailytask', $data);
                        if ($dttrno != 0) {
                            $config['params']['doc'] = 'DY';
                            $this->logger->sbcmasterlog2($dttrno, $config, ' Daily task has been created and started by: ' . $assigned, 'task_log');  //($trno, $config, $task, $ptable, $istemp = 0)
                            $config['params']['doc'] = $doc;

                            $label = ' Successfully started.';

                            $config['params']['doc'] = 'ENTRYTASK';
                            $this->logger->sbcmasterlog($trno, $config, ' Line: ' . $line . ' , Task started by: ' . $assigned);

                            if ($checkerid != 0) {
                                $username = $this->coreFunctions->getfieldvalue("client", "email", "clientid=?", [$checkerid]);
                                $socketmsg = "Task accepted: " . $data['rem'];
                                if ($socketmsg != '') $this->othersClass->socketmsg($config, $socketmsg, '', $username);
                            } else {
                                $username = $this->coreFunctions->getfieldvalue("client", "email", "clientid=?", [$userid]);
                                $socketmsg = "Task accepted: " . $data['rem'];
                                if ($socketmsg != '') $this->othersClass->socketmsg($config, $socketmsg, '', $row['requestbycode']);
                            }
                        }
                    }
                }
            } elseif ($status1 == 4) { //for checking ng requestor
                if ($status == 'A') { //start checking
                    // $stat = $this->coreFunctions->sbcupdate('tmdetail', ['enddate' => $datenow, 'status' => 5], ['trno' => $trno, 'line' => $line]);
                    $label = 'The task is now being checked.';
                    $checkername = $this->coreFunctions->datareader("select c.clientname as value from client as c
                                                          where c.clientid =?", [$checkerid]);
                    $chname = '';

                    $data = [
                        'tasktrno' => $trno,
                        'taskline' => $line,
                        'reftrno' => 0,
                        'rem' => $config['params']['row']['title'],
                        'amt' => 0,
                        'clientid' =>  $config['params']['row']['clid'],
                        'userid' => $adminid,
                        'dateid' => $datenow,
                        'donedate' => null,
                        'statid' => 0,
                        'apvtrno' => 0,
                        'isprev' => 0,
                        'createdate' => $datenow,
                        'encodeddate' => $datenow,
                        'ischecker' => 1,
                        'startchecker' => $datenow,
                        'empid' => $checkerid,
                        'taskcatid' => $config['params']['row']['taskcatid'],
                        'assignedid' => $config['params']['row']['userid']
                    ];

                    $dttrno = $this->coreFunctions->insertGetId('dailytask', $data);
                    if ($dttrno != 0) {
                        $this->coreFunctions->execqry("delete from pendingapp where doc='TM' and trno=" . $trno . " and line=" . $line, 'delete');

                        $config['params']['doc'] = 'ENTRYTASK';
                        $requestorname = $row['requestorname'];

                        if ($adminid != $checkerid) {
                            $chname = $requestorname;
                        } else {
                            $chname = $checkername;
                        }

                        if ($checkerid != 0) {
                            $username = $this->coreFunctions->getfieldvalue("client", "email", "clientid=?", [$checkerid]);
                            $socketmsg = "Checking task: " . $data['rem'];
                            if ($socketmsg != '') $this->othersClass->socketmsg($config, $socketmsg, '', $username);
                        } else {
                            $username = $this->coreFunctions->getfieldvalue("client", "email", "clientid=?", [$userid]);
                            $socketmsg = "Checking task: " . $data['rem'];
                            if ($socketmsg != '') $this->othersClass->socketmsg($config, $socketmsg, '', $username);
                        }

                        $this->logger->sbcmasterlog($trno, $config, ' Line: ' . $line . ' ' . $chname . ' ' . ' has started checking the task.');

                        $config['params']['doc'] = 'DY';
                        $this->logger->sbcmasterlog2($dttrno, $config, ' Daily checking task has been created and started by ' . $chname, 'task_log');
                    }
                }
            } else { // nireturn galing sa checker  nasa pendingapp lang ito - connected sa TM

                $assigned = $this->coreFunctions->datareader("select c.clientname as value from tmdetail as d
                                                          left join client as c on c.clientid=d.userid
                                                          where d.line =? and d.trno = ?", [$line, $trno]);

                $label = 'Successfully started.';
                $data = [
                    'tasktrno' => $trno,
                    'taskline' => $line,
                    'reftrno' => 0,
                    'rem' => $config['params']['row']['title'],
                    'amt' => 0,
                    'clientid' =>  $config['params']['row']['clid'],
                    'userid' => $adminid,
                    'dateid' => $dateid,
                    'donedate' => null,
                    'statid' => 0,
                    'apvtrno' => 0,
                    'isprev' => 0,
                    'createdate' => $datenow,
                    'encodeddate' => $datenow,
                    'ischecker' => 0,
                    'empid' => $checkerid,
                    'taskcatid' => $config['params']['row']['taskcatid'],
                    'assignedid' => $config['params']['row']['userid']
                ];

                if ($checkerid != 0) {
                    if ($checkerid == $adminid) {
                        $data['ischecker'] = 1;
                        $data['startchecker'] = $this->othersClass->getCurrentTimeStamp();
                        $checkerid = 0;
                    }
                }

                $dttrno = $this->coreFunctions->insertGetId('dailytask', $data);
                if ($dttrno) {
                    $this->coreFunctions->execqry("delete from pendingapp where doc='TM' and approver='RETURN' and trno=" . $trno . " and line=" . $line, 'delete');

                    if ($checkerid != 0) {
                        $checkername = $this->coreFunctions->getfieldvalue("client", "email", "clientid=?", [$checkerid]);
                        $tasktitle = $data['rem'];
                        $socketmsg = "Task accepted: " . $tasktitle;
                        if ($socketmsg != '') $this->othersClass->socketmsg($config, $socketmsg, '', $checkername);
                    } else {
                        $tasktitle = $data['rem'];
                        $socketmsg = "Task accepted: " . $tasktitle;
                        if ($socketmsg != '') $this->othersClass->socketmsg($config, $socketmsg, '',  $row['requestbycode']);
                    }

                    $config['params']['doc'] = 'DY';
                    $this->logger->sbcmasterlog2($dttrno, $config, ' Daily task has been created and started by ' . $assigned, 'task_log');
                    $config['params']['doc'] = $doc;

                    $config['params']['doc'] = 'ENTRYTASK';
                    $this->logger->sbcmasterlog($trno, $config, ' Line: ' . $line . ' Task started by: ' . $assigned);
                }
            }
        }

        return ['status' => true, 'msg' => $label, 'data' => [], 'getsbclistdata' => ['gapplications', 'dailytask'], 'deleterow' => true]; //'reloadsbclist' => true, 'action' => 'gapplications', 
    }
} //end class
