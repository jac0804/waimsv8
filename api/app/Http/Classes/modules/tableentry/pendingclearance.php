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

class pendingclearance
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'PENDING CLEARANCE';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $linkemail;
    public $tablelogs = 'hrisnum_log';
    public $style = 'width:90%;max-width:90%;';
    public $issearchshow = true;
    public $showclosebtn = true;


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
        $doc = $config['params']['row']['doc'];

        $cols = ['action', 'docno', 'clientname', 'deptname', 'jobtitle', 'dateid', 'hired', 'dateid2', 'rem2', 'rem'];

        foreach ($cols as $key => $value) {
            $$value = $key;
        }
        $tab = [$this->gridname => ['gridcolumns' => $cols]];

        if ($config['params']['row']['approver'] == 'HEAD') {
            $stockbuttons = ['jumpmodule', 'approve', 'disapprove', 'undone'];
        } else {
            $stockbuttons = ['jumpmodule', 'approve'];
        }

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);

        $obj[0][$this->gridname]['columns'][$action]['btns']['approve']['label'] = 'Cleared';
        if ($config['params']['row']['approver'] == 'HEAD') {
            $obj[0][$this->gridname]['columns'][$action]['btns']['disapprove']['label'] = 'Not Cleared';
            $obj[0][$this->gridname]['columns'][$action]['btns']['undone']['label'] = 'Pending';
        }

        $obj[0][$this->gridname]['columns'][$docno]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$dateid]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$deptname]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$hired]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$dateid2]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$jobtitle]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$rem2]['type'] = 'label';

        $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Out-going Employee';
        $obj[0][$this->gridname]['columns'][$rem]['label'] = 'Remarks';
        $obj[0][$this->gridname]['columns'][$hired]['label'] = 'Hired Date';
        $obj[0][$this->gridname]['columns'][$dateid2]['label'] = 'Last day of work';
        $obj[0][$this->gridname]['columns'][$rem2]['label'] = 'Cause of Separation';

        $obj[0][$this->gridname]['columns'][$action]['style'] = 'width:100px;whiteSpace: normal;min-width:100px; max-width:100px;';
        $obj[0][$this->gridname]['columns'][$docno]['style'] = 'width:100px;whiteSpace: normal;min-width:100px; max-width:100px;';
        $obj[0][$this->gridname]['columns'][$dateid]['style'] = 'width:100px;whiteSpace: normal;min-width:100px; max-width:100px;';
        $obj[0][$this->gridname]['columns'][$hired]['style'] = 'width:100px;whiteSpace: normal;min-width:100px; max-width:100px;';
        $obj[0][$this->gridname]['columns'][$dateid2]['style'] = 'width:100px;whiteSpace: normal;min-width:100px; max-width:100px;';

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

        $qry = " select cl.trno, cl.docno, date(cl.dateid) as dateid, client.clientname, '' as rem, dept.clientname as deptname, date(cl.hired) as hired, date(cl.lastdate) as dateid2, cl.jobtitle, 
        cl.cause as rem2, m.sbcpendingapp, 'HC' as doc, 'module/hris/' as url, '' as bgcolor,app.approver
            from pendingapp as app left join clearance as cl on cl.trno=app.trno 
            left join client on client.clientid=cl.empid
            left join client as dept on dept.clientid=cl.deptid
            left join moduleapproval as m on m.modulename=app.doc
            where app.doc='HC' and app.approver='" . $row['approver'] . "' and app.clientid=" . $adminid;
        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }

    public function updateapp($config, $status)
    {
        $adminid = $config['params']['adminid'];
        $row = $config['params']['row'];

        $data = [];
        switch ($status) {
            case 'A':
                $data['status'] = 'Cleared';
                break;
            case 'D':
                $data['status']  = 'Not Cleared';
                break;
            case 'U':
                $data['status'] = 'Pending';
                break;
        }

        $row['rem'] = $this->othersClass->sanitizekeyfield("rem", $row['rem']);

        if ($row['approver'] == 'HEAD') {
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['editby'] = $config['params']['user'];
            $data['cleardate'] = $this->othersClass->getCurrentTimeStamp();
            $data['rem'] = $row['rem'];
            $updatecl = $this->coreFunctions->sbcupdate("clearance", $data, ['trno' => $row['trno']]);
        } else {
            $data2 = ['trno' => $row['trno'], 'clientid' => $adminid, 'donedate' => $this->othersClass->getCurrentTimeStamp(), 'rem' => $row['rem']];
            $updatecl = $this->coreFunctions->sbcinsert("hrissig",  $data2);
        }

        if ($updatecl) {
            $pendingdata = $this->coreFunctions->opentable("select * from pendingapp where trno=" . $row['trno'] . " and doc='HC' and approver='" . $row['approver'] . "' and clientid=" . $adminid);

            if ($this->coreFunctions->execqry("delete from pendingapp where trno=" . $row['trno'] . " and doc='HC' and approver='" . $row['approver'] . "' and clientid=" . $adminid, 'delete')) {
                $addonfilter = '';
                if ($row['approver'] != 'HEAD') {
                    $currentseq = $this->coreFunctions->datareader("select app.seq as value from moduleapproval as m  left join approvers as app on app.trno=m.line where m.modulename='HC' and app.clientid=" . $adminid . " order by app.seq limit 1", [], '', true);
                    $addonfilter = ' and app.seq>' . $currentseq;
                }
                $qrynextsig = "select app.clientid as value from moduleapproval as m left join approvers as app on app.trno=m.line where m.modulename='HC' " . $addonfilter . " order by app.seq limit 1";
                $nextsigid = $this->coreFunctions->datareader($qrynextsig, [], '', true);
                $url = 'App\Http\Classes\modules\hris\\' . 'hc';
                if ($nextsigid != 0) {
                    $appstatus = $this->othersClass->insertUpdatePendingapp($row['trno'], 0, 'HC', [], $url, $config, $nextsigid);
                    if (!$appstatus['status']) {
                        if (!empty($pendingdata)) {
                            foreach ($pendingdata as $pd) {
                                $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid,approver) values(?, ?, ?, ?,?)", 'insert', [$pd->trno, $pd->line, $pd->doc, $pd->clientid, $pd->approver]);
                            }
                        }
                        return ['status' => false, 'msg' => $appstatus['msg'], 'data' => []];
                    }
                } else {
                    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
                    $data['editby'] = $config['params']['user'];
                    $data['approvedate'] = $this->othersClass->getCurrentTimeStamp();
                    $updatecl = $this->coreFunctions->sbcupdate("clearance", $data, ['trno' => $row['trno']]);
                }

                $headname = $this->coreFunctions->getfieldvalue("client", "clientname", "clientid=?", [$adminid]);
                $this->logger->sbcwritelog($row['trno'], $config, 'HEAD', 'Cleared by Head Department ' . $headname);


                return ['status' => true, 'msg' => 'Successfully ', 'data' => [], 'reloadsbclist' => true, 'action' => 'gapplications', 'deleterow' => true];
            } else {
                return ['status' => false, 'msg' => 'Failed to delete pending app.', 'data' => []];
            }
        } else {
            return ['status' => false, 'msg' => 'Failed to update clearance.', 'data' => []];
        }
    }
} //end class
