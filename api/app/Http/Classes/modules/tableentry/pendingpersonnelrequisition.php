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

class pendingpersonnelrequisition
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'PENDING PERSONNEL REQUISITION APPLICATIONS';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $linkemail;
    public $tablelogs = 'hrisnum_log';
    public $style = 'width:90%;max-width:90%;';
    public $issearchshow = true;
    public $showclosebtn = true;
    public $tablenum = 'hrisnum';
    public $head = 'personreq';
    public $hhead = 'hpersonreq';
    public $tablelogs_del = 'del_hrisnum_log';

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
            'load' => 3627
        );
        return $attrib;
    }

    public function createTab($config)
    {
        $row = $config['params']['row'];
        $approver = $row['approver'];

        $cols = ['action', 'docno', 'clientname', 'due', 'jobtitle', 'name', 'pydocno', 'counts', 'amount', 'age', 'disapproved_remarks'];
        foreach ($cols as $key => $value) {
            $$value = $key;
        }
        $tab = [$this->gridname => ['gridcolumns' => $cols]];

        if ($approver != '') {
            if ($approver == 'DISAPPROVED') {
                $stockbuttons = ['accept'];
            } else {
                $stockbuttons = ['jumpmodule'];
            }
        } else {
            $stockbuttons = ['jumpmodule', 'approve', 'disapprove'];
        }
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Requesting Personnel';
        $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'label';

        $obj[0][$this->gridname]['columns'][$docno]['type'] = 'label';

        $obj[0][$this->gridname]['columns'][$due]['label'] = 'Date Needed';
        $obj[0][$this->gridname]['columns'][$due]['type'] = 'label';

        $obj[0][$this->gridname]['columns'][$jobtitle]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$jobtitle]['type'] = 'label';

        $obj[0][$this->gridname]['columns'][$name]['label'] = 'Place of Assignment';
        $obj[0][$this->gridname]['columns'][$name]['type'] = 'label';


        $obj[0][$this->gridname]['columns'][$pydocno]['label'] = 'Hiring Preference';
        $obj[0][$this->gridname]['columns'][$pydocno]['type'] = 'label';

        $obj[0][$this->gridname]['columns'][$counts]['label'] = 'No. of personnel needed';
        $obj[0][$this->gridname]['columns'][$counts]['type'] = 'label';

        $obj[0][$this->gridname]['columns'][$amount]['label'] = 'Salary';
        $obj[0][$this->gridname]['columns'][$amount]['type'] = 'label';

        $obj[0][$this->gridname]['columns'][$age]['label'] = 'Age Range';
        $obj[0][$this->gridname]['columns'][$age]['type'] = 'label';

        $obj[0][$this->gridname]['columns'][$disapproved_remarks]['type'] = 'input';
        $obj[0][$this->gridname]['columns'][$disapproved_remarks]['readonly'] = false;
        $obj[0][$this->gridname]['columns'][$disapproved_remarks]['style'] = 'width:200px;min-width:200px;';

        $obj[0][$this->gridname]['columns'][$docno]['style'] = 'width:100px;min-width:100px;';
        $obj[0][$this->gridname]['columns'][$clientname]['style'] = 'width:150px;min-width:150px;';
        $obj[0][$this->gridname]['columns'][$due]['style'] = 'width:80px;min-width:80px;';
        $obj[0][$this->gridname]['columns'][$jobtitle]['style'] = 'width:100px;min-width:100px;';
        $obj[0][$this->gridname]['columns'][$name]['style'] = 'width:200px;min-width:200px;';
        $obj[0][$this->gridname]['columns'][$pydocno]['style'] = 'text-align: center; width: 100px;whiteSpace: normal;min-width:100px;max-width:100px;';
        $obj[0][$this->gridname]['columns'][$counts]['style'] = 'text-align: center; width: 100px;whiteSpace: normal;min-width:100px;max-width:100px;';
        $obj[0][$this->gridname]['columns'][$amount]['style'] = 'width:100px;min-width:100px;';
        $obj[0][$this->gridname]['columns'][$age]['style'] = 'text-align: center; width: 80px;whiteSpace: normal;min-width:80px;max-width:80px;';

        if ($approver != '' && $approver != 'DISAPPROVED') {
            $obj[0][$this->gridname]['columns'][$disapproved_remarks]['type'] = 'coldel';
        } else {
            if ($approver == 'DISAPPROVED') {
                $obj[0][$this->gridname]['columns'][$disapproved_remarks]['type'] = 'label';
            }
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
        //and ((head.startdate is not null and head.enddate is null) or (head.startdate is null and head.enddate is not null))
        $row = $config['params']['row'];
        $approver = $row['approver'];
        $url = "/module/hris/";
        $adminid = $config['params']['adminid'];
        if ($approver != '') {
            $qry = "select head.docno, date(head.dateid) as dateid,head.empid,em.clientname,head.remark as remarks, head.appdisid,
                   m.modulename as doc,head.trno,head.notedid,m.sbcpendingapp,head.status1,head.status2,'$url' as url,
                   'DBTODO' as tabtype,'module' as moduletype,
                    date(head.dateneed) as due,ifnull(jt.jobtitle,'') as jobtitle,ifnull(branch.clientname,'') as name,
                    ifnull(head.hpref,'') as pydocno, head.headcount  as counts,format(head.amount,2) as amount,ifnull(head.agerange,'') as age, head.disapproved_remarks, '' as bgcolor

                   from hpersonreq as head
                   left join client as em on em.client = head.personnel
                   left join pendingapp as p on p.trno=head.trno  and p.doc='HQ'
                   left join moduleapproval as m on m.modulename=p.doc
                   left join hrisnum as num on num.trno=head.trno 
                   left join jobthead as jt on jt.docno = head.job
                   left join client as branch on branch.clientid = head.branchid 

                    where p.clientid=" . $adminid . " and num.statid=19  
                    and ((head.startdate is not null and head.enddate is null) or (head.startdate is null and head.enddate is not null) or (head.startdate is null and head.enddate is null) ) ";
        } else {
            $qry = "select head.docno, head.empid, em.clientname,head.appdisid,
                    m.modulename as doc,head.trno,head.notedid,m.sbcpendingapp,head.status1,head.status2,'$url' as url,
                   'DBTODO' as tabtype,'module' as moduletype,
                    date(head.dateneed) as due,ifnull(jt.jobtitle,'') as jobtitle,ifnull(branch.clientname,'') as name,
                    ifnull(head.hpref,'') as pydocno, head.headcount as counts,format(head.amount,2) as amount,ifnull(head.agerange,'') as age, '' as disapproved_remarks, '' as bgcolor
                   from personreq as head
                   left join client as em on em.client = head.personnel
                   left join pendingapp as p on p.trno=head.trno  and p.doc='HQ'
                   left join moduleapproval as m on m.modulename=p.doc
                   left join hrisnum as num on num.trno=head.trno
                   left join jobthead as jt on jt.docno = head.job
                   left join client as branch on branch.clientid = head.branchid 
                   
                   where p.clientid=" . $adminid . " ";
        }
        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }


    public function updateapp($config, $status)
    {
        $row = $config['params']['row'];
        $user = $config['params']['user'];
        $adminid = $config['params']['adminid'];
        $data = [];
        $trno = $row['trno'];
        $doc = $row['doc'];
        $status1 = $this->coreFunctions->datareader("select status1 as value from personreq  as head
                                                    left join pendingapp as p on p.trno=head.trno  and p.doc='HQ'
                                                     where head.trno=? and p.clientid=?", [$trno, $adminid]);



        $recomapprover = $this->coreFunctions->datareader("select recappid as value from personreq  as head
                                                    left join pendingapp as p on p.trno=head.trno  and p.doc='HQ'
                                                     where head.trno=? and p.clientid=?", [$trno, $adminid]);

        $status3 = $this->coreFunctions->datareader("select status3 as value from personreq  as head
                                                    left join pendingapp as p on p.trno=head.trno  and p.doc='HQ'
                                                     where head.trno=? and p.clientid=?", [$trno, $adminid]);
        $label = '';

        $url = 'App\Http\Classes\modules\hris\\' . 'hq';

        $config['params']['trno'] = $row['trno'];
        $config['params']['doc'] = 'hq';

        if ($status1 != '') {
            if (!empty($recomapprover)) {

                //checking ng kung sino ang mag aaprove
                if ($adminid == $recomapprover) { //kung ang mag aapprove ay yung recommending approver
                    if ($status1 == 'A') { //iniapprove ng  manager
                        if ($status == 'A') { //i ni approve ng recommending approver
                            $stat3 = $this->coreFunctions->sbcupdate('personreq', ['status3' => $status], ['trno' => $trno]);
                            $label = 'Approved';
                            if ($stat3) {
                                $deletepending3 = $this->coreFunctions->execqry("delete from pendingapp where doc='HQ' and trno=" . $trno . " and clientid=" . $adminid, 'delete');

                                $this->logger->sbcwritelog($row['trno'], $config, 'HEAD', 'Approved by Recommended Approver');
                            }
                            if ($deletepending3) {

                                $generalmanager = $row['appdisid'];
                                $this->othersClass->insertUpdatePendingapp($row['trno'], 0, 'HQ', $data, $url, $config, $generalmanager, false, true);

                                $this->logger->sbcwritelog($row['trno'], $config, 'HEAD', 'For Approval by General Manager');
                            }
                        } else { //status ay D-nidisapprove ng recommending approver
                            if ($row['disapproved_remarks'] == '') {
                                return ['status' => false, 'msg' => 'Please input valid disapproved remarks.'];
                            }

                            $stat3 = $this->coreFunctions->sbcupdate('personreq', ['status3' => $status, 'disapproved_remarks' => $row['disapproved_remarks']], ['trno' => $trno]);
                            $label = 'Disapproved';

                            if ($stat3) {
                                $deletepending3 = $this->coreFunctions->execqry("delete from pendingapp where doc='HQ' and trno=" . $trno . " and clientid=" . $adminid, 'delete');

                                $this->logger->sbcwritelog($row['trno'], $config, 'HEAD', 'Disapproved by Recommended Approver');
                            }
                            if ($deletepending3) {
                                $post = app($url)->posttrans($config);
                                if (!$post) { //pag hindi nag post yung ni dis approve  ni recommending approver
                                    $status03 = '';
                                    $stat1 = $this->coreFunctions->sbcupdate('personreq', ['status3' => $status03], ['trno' => $row['trno']]); //i empty yung status3
                                    $this->othersClass->insertUpdatePendingapp($row['trno'], 0, 'HQ', $data, $url, $config, $adminid, false, true); //ibabalik yung id ng recommending approver
                                } else {
                                    $this->othersClass->insertUpdatePendingapp($row['trno'], 0, 'HQ', $data, $url, $config, $row['empid'], false, true, 'DISAPPROVED'); //inform yung personnel na nagrequest
                                }
                            }
                        }
                    }
                } else { //kung yung general manager ang mag aaprove
                    if ($status3 == 'A') { //kapag na na approve ng recommending approver
                        if ($status == 'A') {
                            $stat2 = $this->coreFunctions->sbcupdate('personreq', ['status2' => $status], ['trno' => $trno]);
                            $label = 'Approved';
                            if ($stat2) {
                                $deletepending2 = $this->coreFunctions->execqry("delete from pendingapp where doc='HQ' and trno=" . $trno . " and clientid=" . $adminid, 'delete');

                                $this->logger->sbcwritelog($row['trno'], $config, 'HEAD', 'Approved by General Manager');
                            }
                            //kapag ini approve ng general manager
                            if ($deletepending2) {
                                $qry = "select client.clientid,client.clientname from employee as d  left join client on client.clientid=d.empid where d.isapprover=1 and ifnull(client.clientid,0)<>0";
                                $hrapprover = $this->coreFunctions->opentable($qry);
                                $data2 = [];
                                $appname = [];
                                if (!empty($hrapprover)) {
                                    $this->logger->sbcwritelog($row['trno'], $config, 'HEAD', 'For Approval by HR Approver');

                                    foreach ($hrapprover as $key => $value) {
                                        $this->othersClass->insertUpdatePendingapp($trno, 0, 'HQ', $data2, $url, $config, $value->clientid, false, true); //create sa pendingapp
                                        $appname['approver'] = 'APPROVED';
                                        $clientid = $value->clientid; //update hr approver field sa pendingapp
                                        $updatependingup =  $this->coreFunctions->sbcupdate('pendingapp', $appname, ['trno' => $trno, 'doc' => $doc, 'clientid' => $clientid]);

                                        if ($updatependingup) {
                                            $post = app($url)->posttrans($config);
                                            if (!$post) {
                                                $del =  $this->coreFunctions->execqry("delete from pendingapp where doc='HQ' and trno=" . $trno, 'delete');
                                                if ($del) {
                                                    $status02 = '';
                                                    $stat1 = $this->coreFunctions->sbcupdate('personreq', ['status2' => $status02], ['trno' => $row['trno']]); //i empty yung status2
                                                    $this->othersClass->insertUpdatePendingapp($row['trno'], 0, 'HQ', $data, $url, $config, $adminid, false, true); //ibabalik yung id ng genmanager 
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        } else { //status ay D-nidisapprove ng general manager

                            if ($row['disapproved_remarks'] == '') {
                                return ['status' => false, 'msg' => 'Please input valid disapproved remarks.'];
                            }

                            $stat2 = $this->coreFunctions->sbcupdate('personreq', ['status2' => $status, 'disapproved_remarks' => $row['disapproved_remarks']], ['trno' => $trno]);
                            $label = 'Disapproved';

                            if ($stat2) {
                                $deletepending2 = $this->coreFunctions->execqry("delete from pendingapp where doc='HQ' and trno=" . $trno . " and clientid=" . $adminid, 'delete');

                                $this->logger->sbcwritelog($row['trno'], $config, 'HEAD', 'Disapproved by General Manager');
                            }
                            if ($deletepending2) {
                                $post = app($url)->posttrans($config);

                                if (!$post) { //pag hindi nag post yung ni disapprove ni general manager
                                    $status02 = '';
                                    $stat1 = $this->coreFunctions->sbcupdate('personreq', ['status2' => $status02], ['trno' => $row['trno']]); //i empty yung status2
                                    $this->othersClass->insertUpdatePendingapp($row['trno'], 0, 'HQ', $data, $url, $config, $adminid, false, true); //ibabalik yung id ng genmanager 
                                } else {
                                    $this->othersClass->insertUpdatePendingapp($row['trno'], 0, 'HQ', $data, $url, $config, $row['empid'], false, true, 'DISAPPROVED'); //inform yung personnel na nagrequest
                                }
                            }
                        }
                    }
                }
            } else { //kapag walang recommending approver

                if ($status1 == 'A') { //kapag na na approve ng manager 
                    if ($status == 'A') {
                        $stat2 = $this->coreFunctions->sbcupdate('personreq', ['status2' => $status], ['trno' => $trno]);
                        $label = 'Approved';
                        if ($stat2) {
                            $deletepending2 = $this->coreFunctions->execqry("delete from pendingapp where doc='HQ' and trno=" . $trno . " and clientid=" . $adminid, 'delete');

                            $this->logger->sbcwritelog($row['trno'], $config, 'HEAD', 'Apprvoved by General Manager');
                        }
                        //kapag ini approve ng general manager
                        if ($deletepending2) {
                            $qry = "select client.clientid,client.clientname from employee as d  left join client on client.clientid=d.empid where d.isapprover=1 and ifnull(client.clientid,0)<>0";
                            $hrapprover = $this->coreFunctions->opentable($qry);
                            $data2 = [];
                            $appname = [];
                            if (!empty($hrapprover)) {

                                $this->logger->sbcwritelog($row['trno'], $config, 'HEAD', 'For Approval by HR Approver');

                                foreach ($hrapprover as $key => $value) {
                                    $this->othersClass->insertUpdatePendingapp($trno, 0, 'HQ', $data2, $url, $config, $value->clientid, false, true); //create sa pendingapp
                                    $appname['approver'] = "APPROVED";
                                    $clientid = $value->clientid; //update hr approver field sa pendingapp
                                    $updatependingup =  $this->coreFunctions->sbcupdate('pendingapp', $appname, ['trno' => $trno, 'doc' => $doc, 'clientid' => $clientid]);

                                    if ($updatependingup) {
                                        $post = app($url)->posttrans($config);
                                        if (!$post) {
                                            $del =  $this->coreFunctions->execqry("delete from pendingapp where doc='HQ' and trno=" . $trno, 'delete');
                                            if ($del) {
                                                $status02 = '';
                                                $stat1 = $this->coreFunctions->sbcupdate('personreq', ['status2' => $status02], ['trno' => $row['trno']]); //i empty yung status2
                                                $this->othersClass->insertUpdatePendingapp($row['trno'], 0, 'HQ', $data, $url, $config, $adminid, false, true); //ibabalik yung id ng genmanager 
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    } else { //status ay D-nidisapprove ng general manager
                        if ($row['disapproved_remarks'] == '') {
                            return ['status' => false, 'msg' => 'Please input valid disapproved remarks.'];
                        }

                        $stat2 = $this->coreFunctions->sbcupdate('personreq', ['status2' => $status, 'disapproved_remarks' => $row['disapproved_remarks']], ['trno' => $trno]);
                        $label = 'Disapproved';

                        if ($stat2) {
                            $deletepending2 = $this->coreFunctions->execqry("delete from pendingapp where doc='HQ' and trno=" . $trno . " and clientid=" . $adminid, 'delete');

                            $this->logger->sbcwritelog($row['trno'], $config, 'HEAD', 'Disapproved by General Manager');
                        }
                        if ($deletepending2) {
                            $post = app($url)->posttrans($config);

                            if (!$post) { //pag hindi nag post yung ni disapprove ni general manager
                                $status02 = '';
                                $stat1 = $this->coreFunctions->sbcupdate('personreq', ['status2' => $status02], ['trno' => $row['trno']]); //i empty yung status2
                                $this->othersClass->insertUpdatePendingapp($row['trno'], 0, 'HQ', $data, $url, $config, $adminid, false, true); //ibabalik yung id ng genmanager 
                            } else {
                                $this->othersClass->insertUpdatePendingapp($row['trno'], 0, 'HQ', $data, $url, $config, $row['empid'], false, true, 'DISAPPROVED'); //inform yung personnel na nagrequest
                            }
                        }
                    }
                }
            } //end ng status 1 na approved 

        } else { //status1 is empty- manager approval pa lang 

            //recommending approver ito
            $recomapprover = $this->coreFunctions->datareader("select recappid as value from personreq  as head
                                                    left join pendingapp as p on p.trno=head.trno  and p.doc='HQ'
                                                     where head.trno=? and p.clientid=?", [$trno, $adminid]);
            if ($status == 'A') { //ini approve ng manager
                $stat1 = $this->coreFunctions->sbcupdate('personreq', ['status1' => $status], ['trno' => $row['trno']]);
                $label = 'Approved';
                if ($stat1) {
                    $this->logger->sbcwritelog($row['trno'], $config, 'HEAD', 'Approved by Manager/Supervisor');

                    $deletepending = $this->coreFunctions->execqry("delete from pendingapp where doc='HQ' and trno=" . $row['trno'] . " and clientid=" . $adminid, 'delete');
                    if ($deletepending) {
                        $url = 'App\Http\Classes\modules\hris\\' . 'hq';
                        $generalmanager = $row['appdisid'];
                        if (!empty($recomapprover)) {
                            $this->othersClass->insertUpdatePendingapp($row['trno'], 0, 'HQ', $data, $url, $config, $recomapprover, false, true); // pag may recommending approver

                            $this->logger->sbcwritelog($row['trno'], $config, 'HEAD', 'For Approval - Recommended Approver');
                        } else {
                            $this->othersClass->insertUpdatePendingapp($row['trno'], 0, 'HQ', $data, $url, $config, $generalmanager, false, true);

                            $this->logger->sbcwritelog($row['trno'], $config, 'HEAD', 'For Approval - General Manager');
                        }
                    }
                }
            } else { //pag ni disapprove ng manager

                if ($row['disapproved_remarks'] == '') {
                    return ['status' => false, 'msg' => 'Please input valid disapproved remarks.'];
                }

                $stat1 = $this->coreFunctions->sbcupdate('personreq', ['status1' => $status, 'disapproved_remarks' => $row['disapproved_remarks']], ['trno' => $row['trno']]);
                if ($stat1) {
                    $deletepending = $this->coreFunctions->execqry("delete from pendingapp where doc='HQ' and trno=" . $row['trno'] . " and clientid=" . $adminid, 'delete');

                    $this->logger->sbcwritelog($row['trno'], $config, 'HEAD', 'Disapproved by Manager/Supervisor');
                }

                $post1 = app($url)->posttrans($config);
                if (!$post1) { //pag hindi nag post yung ni disapprove
                    // $deletepending = $this->coreFunctions->execqry("delete from pendingapp where doc='HQ' and trno=" . $row['trno'], 'delete');
                    $status01 = '';
                    $stat1 = $this->coreFunctions->sbcupdate('personreq', ['status1' => $status01], ['trno' => $row['trno']]); //i empty yung status1
                    $this->othersClass->insertUpdatePendingapp($row['trno'], 0, 'HQ', $data, $url, $config, $adminid, false, true); //ibabalik yung id ng manager 
                } else {
                    $this->othersClass->insertUpdatePendingapp($row['trno'], 0, 'HQ', $data, $url, $config, $row['empid'], false, true, 'DISAPPROVED'); //inform yung personnel na nagrequest
                }
                $label = 'Disapproved';
            }
        }

        return ['status' => true, 'msg' => 'Successfully ' . $label . ' ', 'data' => [], 'reloadsbclist' => true, 'action' => 'gapplications', 'deleterow' => true];
    }



    // public function posttrans($config)
    // {

    //     $row = $config['params']['row'];
    //     $trno = $row['trno'];
    //     // $doc = $row['doc'];
    //     $adminid = $config['params']['adminid'];

    //     $user = $this->coreFunctions->datareader("select client.clientname as value from employee as d  left join client on client.clientid=d.empid
    //                                                  where d.empid=?", [$adminid]);

    //     $docno = $this->coreFunctions->datareader('select docno as value from ' . $config['docmodule']->tablenum . ' where trno=?', [$trno]);
    //     $msg = '';
    //     $qry = "insert into " . $this->hhead . " (trno,docno,dateid,dept,personnel,empid,dateneed,job,class,
    //                     headcount,hpref,agerange,gpref,rank,empstatusid,reason,hirereason,prdstart,prdend,empmonths,empdays,remark,qualification, 
    //                     createby,createdate,editby,editdate,lockdate,lockuser,viewdate,viewby,
    //                     amount,skill,educlevel,civilstatus,jobsumm,branchid,notedid,appdisid,status1,status2,status3,recappid)
    //             select trno,docno,dateid,dept,personnel,empid,dateneed,job,class,headcount,hpref,
    //                    agerange,gpref,rank,empstatusid,reason,hirereason,prdstart,prdend,empmonths,empdays,remark,qualification,createby,createdate,
    //                    editby,editdate,lockdate,lockuser,viewdate,viewby,amount,skill,educlevel,
    //                    civilstatus,jobsumm,branchid,notedid,appdisid,status1,status2,status3,recappid
    //             from " . $this->head . " where trno=?";
    //     // var_dump($qry);
    //     $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    //     if ($result === 1) {
    //     } else {
    //         $msg = "Posting failed. Kindly check the head data.";
    //     }

    //     if ($msg === '') {
    //         $date = $this->othersClass->getCurrentTimeStamp();
    //         $data = ['postdate' => $date, 'postedby' => $user];
    //         $this->coreFunctions->sbcupdate($config['docmodule']->tablenum, $data, ['trno' => $trno]);
    //         $this->coreFunctions->execqry("delete from " . $config['docmodule']->head . " where trno=?", "delete", [$trno]);
    //         $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
    //         return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
    //     } else {
    //         $this->coreFunctions->execqry("delete from " . $config['docmodule']->hhead . " where trno=?", "delete", [$trno]);
    //         return ['trno' => $trno, 'status' => false, 'msg' => $msg];
    //     }
    // } //end function 

    public function accept($config)
    {
        $row = $config['params']['row'];
        $this->coreFunctions->execqry("delete from pendingapp where doc='HQ' and trno=" . $row['trno'] . " and clientid=" . $row['empid'], 'delete');
        return ['status' => true, 'msg' => 'Successfully accept', 'data' => [], 'reloadsbclist' => true, 'action' => 'gapplications', 'deleterow' => true];
    }
} //end class
