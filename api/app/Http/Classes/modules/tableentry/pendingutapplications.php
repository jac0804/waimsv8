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

class pendingutapplications
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'PENDING UNDERTIME APPLICATIONS';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $linkemail;
    public $tablelogs = 'payroll_log';
    public $style = 'width:90%;max-width:90%;';
    public $issearchshow = true;
    public $showclosebtn = true;
    public $undertimefields = ['status', 'status2', 'approverem', 'approvedby', 'approvedby2', 'approvedate', 'approvedate2', 'disapprovedby', 'disapprovedby2', 'disapprovedate', 'disapprovedate2', 'catid', 'disapproved_remarks2'];


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
            'load' => 4801
        );
        return $attrib;
    }

    public function createTab($config)
    {
        $doc = $config['params']['row']['doc'];
        $companyid = $config['params']['companyid'];
        $approver = $this->coreFunctions->getfieldvalue("employee", "isapprover", "empid=?", [$config['params']['adminid']]);
        $supervisor = $this->coreFunctions->getfieldvalue("employee", "issupervisor", "empid=?", [$config['params']['adminid']]);
        $url = 'App\Http\Classes\modules\payroll\\' . 'undertime';
        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='OB'");
        if ($approversetup == '') {
            $approversetup = app($url)->approvers($config['params']);
        } else {
            $approversetup = explode(',', $approversetup);
            foreach ($approversetup as $appkey => $appsetup) {
                if ($appsetup == 'Supervisor') {
                    $approversetup[$appkey] = 'issupervisor';
                } else {
                    $approversetup[$appkey] = 'isapprover';
                }
            }
        }
        $cols = ['action', 'clientname', 'dateid2', 'dateid', 'underhrs', 'rem', 'rem1', 'rem2'];

        foreach ($cols as $key => $value) {
            $$value = $key;
        }
        $tab = [$this->gridname => ['gridcolumns' => $cols]];
        $stockbuttons = ['approve', 'disapprove'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Name';
        $obj[0][$this->gridname]['columns'][$clientname]['style'] = 'width:200px;min-width:200px;';
        $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$dateid]['style'] = 'width:150px;min-width:150px;';
        $obj[0][$this->gridname]['columns'][$dateid]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$dateid2]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$rem]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$rem1]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$rem1]['label'] = 'First Approver';
        $obj[0][$this->gridname]['columns'][$rem2]['label'] = 'Approver Remarks';
        $obj[0][$this->gridname]['columns'][$underhrs]['label'] = 'Under Hours';
        $obj[0][$this->gridname]['columns'][$underhrs]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$dateid2]['style'] = 'width:150px;min-width:150px;';
        // if (($supervisor && $approversetup[0] == 'issupervisor') || ($approver && $approversetup[0] == 'isapprover')) {
        //     $obj[0][$this->gridname]['columns'][$rem1]['readonly'] = false;
        //     $obj[0][$this->gridname]['columns'][$rem2]['readonly'] = true;
        // } else if (($supervisor && $approversetup[1] == 'issupervisor') || ($approver && $approversetup[1] == 'isapprover')) {
        //     $obj[0][$this->gridname]['columns'][$rem1]['readonly'] = true;
        //     $obj[0][$this->gridname]['columns'][$rem2]['readonly'] = false;
        // } else {
        //     $obj[0][$this->gridname]['columns'][$rem1]['readonly'] = true;
        //     $obj[0][$this->gridname]['columns'][$rem2]['readonly'] = true;
        // }
        if ($companyid == 44) { //stonepro
            $obj[0][$this->gridname]['columns'][$underhrs]['type'] = 'coldel';
        }
        if ($companyid == 58) { //cdo
            $obj[0][$this->gridname]['columns'][$dateid2]['label'] = 'Start Date';
            $obj[0][$this->gridname]['columns'][$dateid]['label'] = 'End Date';
        } else {
            $obj[0][$this->gridname]['columns'][$dateid2]['type'] = 'coldel';
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
        $qry = "select under.line, client.clientname, under.dateid, under.rem, under.rem as remarks, 
            under.approvedby2,under.catid,emp.supervisorid,under.dateid2, '' as rem2,
            under.disapproved_remarks2 as rem1, m.modulename as doc, m.sbcpendingapp, emp.empid,
            case when p.approver = 'isapprover' then 'FOR APPROVER' else 'FOR SUPERVISOR' end as lblforapp, p.approver,under.underhrs
            from undertime as under 
            left join client on client.clientid=under.empid
            left join employee AS emp ON emp.empid = under.empid 
            left join leavecategory as lv on lv.line = under.catid
            left join pendingapp as p on p.line=under.line and p.doc='UNDERTIME'
            left join moduleapproval as m on m.modulename=p.doc
            where p.clientid=" . $config['params']['adminid'] . "
            order by dateid, client.clientname";
        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }

    private function checkapprover($config)
    {
        $approver = $this->coreFunctions->datareader("select isapprover as value from employee where empid=?", [$config['params']['adminid']]);
        if ($approver == "1") return true;
        return false;
    }


    private function checksupervisor($config)
    {
        $supervisor = $this->coreFunctions->datareader("select issupervisor as value from employee where empid=?", [$config['params']['adminid']]);
        if ($supervisor == "1") return true;
        return false;
    }

    public function updateapp($config, $status)
    {
        $row = $config['params']['row'];
        $doc = $row['doc'];
        $admin = $config['params']['adminid'];
        $isapp = $row['approver'];
        $catid = $row['catid'];
        if ($isapp == '' || $isapp == null) $isapp = $this->coreFunctions->datareader("select approver as value from pendingapp where doc='UNDERTIME' and line=" . $row['line']);
        $approver = $this->coreFunctions->getfieldvalue("employee", $isapp, "empid=?", [$admin]);
        $url = 'App\Http\Classes\modules\payroll\\' . 'undertime';
        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='" . $doc . "'");
        if ($approversetup == '') {
            $approversetup = app($url)->approvers($config['params']);
        } else {
            $approversetup = explode(',', $approversetup);
            foreach ($approversetup as $appkey => $appsetup) {
                if ($appsetup == 'Supervisor') {
                    $approversetup[$appkey] = 'issupervisor';
                } else {
                    $approversetup[$appkey] = 'isapprover';
                }
            }
        }
        if ($status == 'A') {
            $utdstatus = 'A';
            $label = 'Approved ';
            $status = $this->coreFunctions->datareader("select approvedate as value from undertime where line=? and approvedate is not null", [$row['line']]);
        } else {
            $utdstatus = 'D';
            $label = 'Disapproved ';
            $status = $this->coreFunctions->datareader("select disapprovedate as value from undertime where line=? and disapprovedate is not null", [$row['line']]);
        }
        if (!$status) {
            $bothapprover = $lastapp = false;
            foreach ($approversetup as $key => $value) {
                if (count($approversetup) > 1) {
                    if ($key == 0) {
                        if ($value == $isapp && $approver) {
                            $data = ['status2' => $utdstatus, 'disapproved_remarks2' => $row['rem2']];
                            if ($utdstatus == 'A') {
                                $data['approvedby2'] = $config['params']['user'];
                                $data['approvedate2'] = $this->othersClass->getCurrentTimeStamp();
                            } else {
                                $lastapp = true;
                                if ($row['rem2'] == '') return ['status' => false, 'msg' => 'First Approver Remarks is empty.', 'data' => []];
                                $data['disapprovedby2'] = $config['params']['user'];
                                $data['disapprovedate2'] = $this->othersClass->getCurrentTimeStamp();
                            }
                            break;
                        }
                    } else {
                        if ($value == $isapp && $approver) {
                            if ((count($approversetup) - 1) == $key) {
                                goto approved;
                            }
                        }
                    }
                } else {
                    if (count($approversetup) == 1) {
                        approved:
                        $lastapp = true;
                        $data = ['status' => $utdstatus, 'approverem' => $row['rem2']];
                        if ($utdstatus == 'A') {
                            $data['approvedby'] = $config['params']['user'];
                            $data['approvedate'] = $this->othersClass->getCurrentTimeStamp();
                        } else {
                            if ($row['rem2'] == '') return ['status' => false, 'msg' => 'Approver Remarks is empty.', 'data' => []];
                            $data['disapprovedby'] = $config['params']['user'];
                            $data['disapprovedate'] = $this->othersClass->getCurrentTimeStamp();
                        }
                        if ($bothapprover) $data['status2'] = $utdstatus;
                        break;
                    }
                }
            }
            if ($row['catid'] <> 0) $data['catid'] = $catid;
            $tempdata = [];
            foreach ($this->undertimefields as $key2) {
                if (isset($data[$key2])) {
                    $tempdata[$key2] = $this->othersClass->sanitizekeyfield($key2, $data[$key2]);
                }
            }
            $tempdata['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $tempdata['editby'] = $config['params']['user'];
            $appstatus = ['status' => true];
            $tempdata['empid'] = $row['empid'];
            $pendingdata = $this->coreFunctions->opentable("select * from pendingapp where line=" . $row['line'] . " and doc='UNDERTIME'");
            $del = $this->coreFunctions->execqry("delete from pendingapp where doc='UNDERTIME' and line=" . $row['line'], 'delete');
            if (!$lastapp && $utdstatus == 'A') $appstatus = $this->othersClass->insertUpdatePendingapp(0, $row['line'], 'UNDERTIME', $tempdata, $url, $config, 0, true);
            $status1 = false;
            if (!$appstatus['status']) {
                $status1 = $appstatus['status'];
                $msg = $appstatus['msg'];
                goto reinsertpendingapp;
            } else {
                $update = $this->coreFunctions->sbcupdate("undertime", $tempdata, ['line' => $row['line']]);
                if ($update) {
                    $config['params']['doc'] = 'UNDERTIME';
                    $this->logger->sbcmasterlog($row['line'], $config, $label . ' (' . $row['clientname'] . ') - ' . $row['dateid']);
                    $msg = 'Successfully ' . $label;
                    $status1 = true;
                } else {
                    $msg = 'Error updating record, please try again';
                    $status1 = false;
                    reinsertpendingapp:
                    $this->coreFunctions->execqry("delete from pendingapp where doc='UNDERTIME' and line=" . $row['line'], 'delete');
                    if (!empty($pendingdata)) {
                        foreach ($pendingdata as $pd) { //$pd->approver
                            $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid) values(?, ?, ?, ?, ?)", 'insert', [$pd->trno, $pd->line, $pd->doc, $pd->clientid, $pd->approver]);
                        }
                    }
                }
            }
            if ($status1) {
                return ['status' => $status1, 'msg' => $msg, 'data' => [], 'reloadsbclist' => true, 'action' => 'gapplications', 'deleterow' => true];
            } else {
                return ['status' => $status1, 'msg' => $msg, 'data' => []];
            }
        } else {
            return ['status' => false, 'msg' => 'Already approved.', 'data' => []];
        }
    }
} //end class
