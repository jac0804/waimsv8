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

class pendinglockedapv
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'FOR POSTING APV';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $linkemail;
    public $tablelogs = 'table_log';
    public $htablelogs = 'htable_log';
    public $style = 'width:90%;max-width:90%;';
    public $issearchshow = true;
    public $showclosebtn = true;
    public $tablenum = 'cntnum';
    public $head = 'lahead';
    public $hhead = 'glhead';
    public $detail = 'ladetail';
    public $hdetail = 'gldetail';
    public $tablelogs_del = 'del_table_log';

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

        $cols = ['action', 'docno', 'dateid', 'clientname', 'rem', 'ext']; //, 'rem2'
        foreach ($cols as $key => $value) {
            $$value = $key;
        }
        $tab = [$this->gridname => ['gridcolumns' => $cols]];

        $stockbuttons = ['approve'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Supplier';
        $obj[0][$this->gridname]['columns'][$ext]['label'] = 'Amount';

        $obj[0][$this->gridname]['columns'][$action]['btns']['approve']['label'] = 'POST';
        // $obj[0][$this->gridname]['columns'][$rem2]['label'] = 'Approver Remarks';

        $obj[0][$this->gridname]['columns'][$docno]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$dateid]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$rem]['type'] = 'label';

        $obj[0][$this->gridname]['columns'][$docno]['style'] = 'width:100px;min-width:100px;';
        $obj[0][$this->gridname]['columns'][$dateid]['style'] = 'width:120px;min-width:120px;';
        $obj[0][$this->gridname]['columns'][$clientname]['style'] = 'width:150px;min-width:150px;';
        $obj[0][$this->gridname]['columns'][$rem]['style'] = 'width:200px;min-width:200px;';

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
        $row = $config['params']['row'];
        $approver = $row['approver'];
        $url = "/module/payable/";
        $adminid = $config['params']['adminid'];

        $qry = "select head.docno,m.modulename as doc,head.trno,date(head.dateid) as dateid,cl.clientname,m.sbcpendingapp,head.rem,
        format(ifnull((select sum(cr) from ladetail where ladetail.trno=head.trno),0),2) as ext,'' as rem2,p.approver,
        '$url' as url,'DBTODO' as tabtype,'module' as moduletype
                from lahead as head
                left join pendingapp as p on p.trno=head.trno and p.doc='PV'
                left join moduleapproval as m on m.modulename=p.doc
                left join cntnum as num on num.trno=head.trno
                left join client as cl on cl.client=head.client
                where p.clientid=" . $adminid . " and p.approver = '$approver'";

        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }


    public function updateapp($config, $status)
    {
        $row = $config['params']['row'];
        $doc = $row['doc'];
        $admin = $config['params']['adminid'];
        $isapp = $row['approver'];

        if ($isapp == '' || $isapp == null) $isapp = $this->coreFunctions->datareader("select approver as value from pendingapp where doc='PV' and trno=" . $row['trno']);
        $approver = $this->coreFunctions->getfieldvalue("employee", $isapp, "empid=?", [$admin]);
        $url = 'App\Http\Classes\modules\payable\\pv';
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

        $posted = $this->othersClass->isposted2($row['trno'], 'cntnum');
        if ($posted) return ['status' => false, 'msg' => 'Transaction already posted!'];

        if ($status == 'A') {
            $utdstatus = 'A';
        } else {
            $utdstatus = 'D';
        }

        $bothapprover = $lastapp = false;
        foreach ($approversetup as $key => $value) {
            if (count($approversetup) > 1) {
                if ($key == 0) {
                    if ($value == $isapp && $approver) {
                        $data = ['status2' => $utdstatus];
                        if ($utdstatus == 'A') {
                        } else { // disapproved
                            $lastapp = true;
                            if ($row['rem2'] == '') return ['status' => false, 'msg' => 'First Approver Remarks is empty.', 'data' => []];
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
                    $data = ['status' => $utdstatus];
                    if ($utdstatus == 'A') {
                    } else { //disapproved
                        if ($row['rem2'] == '') return ['status' => false, 'msg' => 'Approver Remarks is empty.', 'data' => []];
                    }
                    if ($bothapprover) $data['status2'] = $utdstatus;
                    break;
                }
            }
        }

        $pendingdata = $this->coreFunctions->opentable("select * from pendingapp where trno=" . $row['trno'] . " and doc='" . $doc . "'");
        $del = $this->coreFunctions->execqry("delete from pendingapp where doc='" . $doc . "' and trno=" . $row['trno'], 'delete');
        $tempdata = ['empid' => 0];
        $appstatus = ['status' => true];
        if (!$lastapp && $utdstatus == 'A') $appstatus = $this->othersClass->insertUpdatePendingapp(0, $row['trno'], $doc, $tempdata, $url, $config, 0);
        $status1 = false;
        if (!$appstatus['status']) {
            $status1 = $appstatus['status'];
            $msg = $appstatus['msg'];
            goto reinsertpendingapp;
        } else {
            $path = 'App\Http\Classes\modules\payable\\pv';
            $config['params']['trno'] = $row['trno'];
            $posting = app($path)->posttrans($config);

            if ($posting['status']) {
                return ['status' => true, 'msg' => 'Successfully posted', 'data' => [], 'reloadsbclist' => true, 'action' => 'gapplications', 'deleterow' => true];
            } else {

                $msg = 'Error updating record, please try again';
                $status1 = false;
                reinsertpendingapp:
                $this->coreFunctions->execqry("delete from pendingapp where doc='" .   $doc . "' and trno=" . $row['trno'], 'delete');
                if (!empty($pendingdata)) {
                    foreach ($pendingdata as $pd) {
                        $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid) values(?, ?, ?, ?, ?)", 'insert', [$pd->trno, $pd->line, $pd->doc, $pd->clientid, $pd->approver]);
                    }
                }
                return ['status' => $status1, 'msg' =>  $msg, 'data' => []];
            }
        }
    }
} //end class
