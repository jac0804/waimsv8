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

class pendingrestdayapplications
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'PENDING REST DAY APPLICATIONS';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $linkemail;
    public $tablelogs = 'payroll_log';
    public $style = 'width:90%;max-width:90%;';
    public $issearchshow = true;
    public $showclosebtn = true;
    public $fields = ['status', 'disapproved_remarks', 'approvedby', 'approveddate', 'disapproveddate', 'disapprovedby', 'status2', 'disapproved_remarks2', 'approvedby2', 'approveddate2', 'disapprovedby2', 'disapproveddate2'];


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
            'load' => 5155
        );
        return $attrib;
    }

    public function createTab($config)
    {
        $doc = $config['params']['row']['doc'];
        $approver = $this->coreFunctions->getfieldvalue("employee", "isapprover", "empid=?", [$config['params']['adminid']]);
        $supervisor = $this->coreFunctions->getfieldvalue("employee", "issupervisor", "empid=?", [$config['params']['adminid']]);
        $url = 'App\Http\Classes\modules\payroll\\' . 'restday';
        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='RESTDAY'");
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
        $cols = ['action', 'clientname', 'daytype', 'createdate', 'dateid', 'rem1', 'rem2'];
        foreach ($cols as $key => $value) {
            $$value = $key;
        }
        $tab = [$this->gridname => ['gridcolumns' => $cols]];
        $stockbuttons = ['approve', 'disapprove'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Name';
        $obj[0][$this->gridname]['columns'][$clientname]['style'] = 'width:200px;min-width:200px;';
        $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$daytype]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$createdate]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$dateid]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$dateid]['label'] = 'Applied Date';
        $obj[0][$this->gridname]['columns'][$rem1]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$rem1]['label'] = 'First Approver';
        $obj[0][$this->gridname]['columns'][$rem2]['label'] = 'Approver Remarks';
        // if (($supervisor && $approversetup[0] == 'issupervisor') || ($approver && $approversetup[0] == 'isapprover')) {
        //     $obj[0][$this->gridname]['columns'][5]['readonly'] = false;
        //     $obj[0][$this->gridname]['columns'][6]['readonly'] = true;
        // } else if (($supervisor && $approversetup[1] == 'issupervisor') || ($approver && $approversetup[1] == 'isapprover')) {
        //     $obj[0][$this->gridname]['columns'][5]['readonly'] = true;
        //     $obj[0][$this->gridname]['columns'][6]['readonly'] = false;
        // } else {
        //     $obj[0][$this->gridname]['columns'][5]['readonly'] = true;
        //     $obj[0][$this->gridname]['columns'][6]['readonly'] = true;
        // }
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
        $qry = "select cs.line, client.clientname, date(cs.dateid) as dateid,cs.empid,date(cs.createdate) as createdate,cs.orgdaytype as daytype,
            emp.supervisorid,cs.approvedby2, '' as rem2,cs.disapproved_remarks2 as rem1, m.sbcpendingapp, m.modulename as doc,
            case when p.approver = 'isapprover' then 'FOR APPROVER' else 'FOR SUPERVISOR' end as lblforapp, p.approver
            from changeshiftapp as cs
            left join client on client.clientid=cs.empid
            left join employee as emp on emp.empid = cs.empid
            left join pendingapp as p on p.line=cs.line and p.doc='RESTDAY'
            left join moduleapproval as m on m.modulename=p.doc
            where cs.status = 0 and isrestday = 1 and p.clientid=" . $config['params']['adminid'] . " order by cs.dateid, client.clientname";
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
        if ($isapp == '' || $isapp == null) $isapp = $this->coreFunctions->datareader("select approver as value from pendingapp where doc='RESTDAY' and line=" . $row['line']);
        $approver = $this->coreFunctions->getfieldvalue("employee", $isapp, "empid=?", [$admin]);
        // $approver = $this->coreFunctions->getfieldvalue("employee", "isapprover", "empid=?", [$admin]);
        // $supervisor = $this->coreFunctions->getfieldvalue("employee", "issupervisor", "empid=?", [$admin]);
        $url = 'App\Http\Classes\modules\payroll\\' . 'obapplication';
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
        $line = $row['line'];
        if ($status == 'A') {
            $label = 'Approved';
            $cstatus = 1;
            $status = $this->coreFunctions->datareader("select approveddate as value from changeshiftapp where line=? and approveddate is not null", [$line]);
        } else {
            $label = 'Disapproved';
            $cstatus = 2;
            $status = $this->coreFunctions->datareader("select disapproveddate as value from changeshiftapp where line=? and disapproveddate is not null", [$line]);
        }
        if (!$status) {
            $bothapprover = $lastapp = false;
            foreach ($approversetup as $key => $value) {
                if (count($approversetup) > 1) {
                    if ($key == 0) {
                        if ($value == $isapp && $approver) {
                            $data = ['status2' => $cstatus, 'disapproved_remarks2' => $row['rem2']];
                            if ($cstatus == 2) {
                                $lastapp = true;
                                if ($row['rem2'] == '') return ['status' => false, 'msg' => 'First Approver Remarks is empty.', 'data' => []];
                                $data['disapprovedby2'] = $config['params']['user'];
                                $data['disapproveddate2'] = $this->othersClass->getCurrentTimeStamp();
                            } else {
                                $data['approvedby2'] = $config['params']['user'];
                                $data['approveddate2'] = $this->othersClass->getCurrentTimeStamp();
                            }
                            break;
                        }
                    } else {
                        if ($value == $isapp && $approver) {
                            if ((count($approversetup) - 1) == $key) goto approved;
                        }
                    }
                    // if ($supervisor && $approver) {
                    //     $bothapprover = true;
                    //     goto approved;
                    // } else {
                    // }
                } else {
                    if (count($approversetup) == 1) {
                        approved:
                        $lastapp = true;
                        // $rem12 = count($approversetup) == 1 ? $row['rem1'] : $row['rem2'];
                        $data = ['status' => $cstatus, 'disapproved_remarks' => $row['rem2']];
                        if ($cstatus == 1) {
                            $data['approvedby'] = $config['params']['user'];
                            $data['approveddate'] = $this->othersClass->getCurrentTimeStamp();
                        } else {
                            if ($row['rem2'] == '') return ['status' => false, 'msg' => 'Approver Remarks is empty.', 'data' => []];
                            $data['disapprovedby'] = $config['params']['user'];
                            $data['disapproveddate'] = $this->othersClass->getCurrentTimeStamp();
                        }
                        if ($bothapprover) $data['status2'] = $cstatus;
                        break;
                    }
                }
            }
        }
        $tempdata = [];
        foreach ($this->fields as $key2) {
            if (isset($data[$key2])) {
                $tempdata[$key2] = $data[$key2];
                $tempdata[$key2] = $this->othersClass->sanitizekeyfield($key2, $tempdata[$key2]);
            }
        }
        $tempdata['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $tempdata['editby'] = $config['params']['user'];

        $pendingdata = $this->coreFunctions->opentable("select * from pendingapp where line=" . $line . " and doc='RESTDAY'");
        $this->coreFunctions->execqry("delete from pendingapp where doc='RESTDAY' and line=" . $line, 'delete');
        $appstatus = ['status' => true];
        $tempdata['empid'] = $row['empid'];
        if (!$lastapp && $cstatus == 1) $appstatus = $this->othersClass->insertUpdatePendingapp(0, $line, 'RESTDAY', $tempdata, $url, $config, 0, true);
        if (!$appstatus['status']) {
            goto reinsertpendingapp;
            return ['status' => false, 'msg' => $appstatus['msg'], 'data' => []];
        } else {
            $update = $this->coreFunctions->sbcupdate("changeshiftapp", $data, ['line' => $line]);
            if ($update) {
                $config['params']['doc'] = 'RESTDAY';
                $this->logger->sbcmasterlog($line, $config, $label . ' (' . $row['clientname'] . ') - ' . $row['dateid']);
                return ['status' => true, 'msg' => 'Successfully ' . $label, 'data' => [], 'reloadsbclist' => true, 'action' => 'gapplications', 'deleterow' => true];
            } else {
                reinsertpendingapp:
                $this->coreFunctions->execqry("delete from pendingapp where doc='RESTDAY' and line=" . $line, 'delete');
                if (!empty($pendingdata)) {
                    foreach ($pendingdata as $pd) {
                        $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid) values(?, ?, ?, ?, ?)", 'insert', [$pd->trno, $pd->line, $pd->doc, $pd->clientid, $pd->approver]);
                    }
                }
                return ['status' => false, 'msg' => 'Error updating record, Please try again.', 'data' => []];
            }
        }
    }
} //end class
