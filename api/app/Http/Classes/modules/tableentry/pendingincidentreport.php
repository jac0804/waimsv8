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

class pendingincidentreport
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'PENDING INCIDENT REPORT';
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
    public $head = 'incidenthead';
    public $hhead = 'hincidenthead';
    public $detail = 'incidentdtail';
    public $hdetail = 'hincidentdtail';
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
            'load' => 0
        );
        return $attrib;
    }

    public function createTab($config)
    {
        $row = $config['params']['row'];

        $cols = ['action', 'docno', 'dateid', 'clientname', 'idescription', 'iplace', 'idate'];
        foreach ($cols as $key => $value) {
            $$value = $key;
        }
        $tab = [$this->gridname => ['gridcolumns' => $cols]];

        $stockbuttons = ['jumpmodule'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Employee';
        $obj[0][$this->gridname]['columns'][$idescription]['label'] = 'Incident Description';

        $obj[0][$this->gridname]['columns'][$docno]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$dateid]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$idescription]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$iplace]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$idate]['type'] = 'label';


        $obj[0][$this->gridname]['columns'][$docno]['style'] = 'width:100px;min-width:100px;';
        $obj[0][$this->gridname]['columns'][$dateid]['style'] = 'width:120px;min-width:120px;';
        $obj[0][$this->gridname]['columns'][$clientname]['style'] = 'width:250px;min-width:250px;';
        $obj[0][$this->gridname]['columns'][$idescription]['style'] = 'width:150px;min-width:150px;';
        $obj[0][$this->gridname]['columns'][$iplace]['style'] = 'width:150px;min-width:150px;';
        $obj[0][$this->gridname]['columns'][$idate]['style'] = 'width:100px;min-width:100px;';

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
        // $approver = $row['approver'];
        $url = "/module/hris/";
        $adminid = $config['params']['adminid'];

        $qry = "select head.docno,m.modulename as doc,head.trno,date(head.dateid) as dateid,
                        head.idescription,date(head.idate) as idate,head.iplace,head.tempid,cl.clientname,
                        head.notedid,m.sbcpendingapp,'$url' as url,
                       'DBTODO' as tabtype,'module' as moduletype
                from incidenthead as head
                left join pendingapp as p on p.trno=head.trno and p.doc='HI'
                left join moduleapproval as m on m.modulename=p.doc
                left join hrisnum as num on num.trno=head.trno
                left join client as cl on cl.clientid=head.tempid
                where p.clientid=" . $adminid . " ";

        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }


    public function updateapp($config, $status)
    {
        $row = $config['params']['row'];
        $adminid = $config['params']['adminid'];
        $data = [];
        $trno = $row['trno'];
        $doc = $row['doc'];

        $label = '';
        return ['status' => true, 'msg' => 'Successfully ' . $label . ' ', 'data' => [], 'reloadsbclist' => true, 'action' => 'gapplications', 'deleterow' => true];
    }



    public function posttrans($config)
    {
        $row = $config['params']['row'];
        $trno = $row['trno'];
        $adminid = $config['params']['adminid'];

        $user = $this->coreFunctions->datareader("select client.clientname as value from employee as d  left join client on client.clientid=d.empid
                                                     where d.empid=?", [$adminid]);

        $docno = $this->coreFunctions->datareader('select docno as value from ' . $config['docmodule']->tablenum . ' where trno=?', [$trno]);
        $msg = '';

        $qry = "insert into " . $this->hhead . " (trno, docno, dateid, idescription, idate, iplace, idetails, icomments, 
                        createby, createdate, editby, editdate, lockdate, lockuser, viewdate, viewby, tempid, fempid, 
                        doc, tempjobid, fempjobid,fempname,fjobtitle,artid,sectid,notedid)
                select trno, docno, dateid, idescription, idate, iplace, idetails, icomments, createby, createdate, 
                        editby, editdate, lockdate, lockuser, viewdate, viewby, tempid, fempid, doc, tempjobid, 
                        fempjobid,fempname,fjobtitle,artid,sectid,notedid from " . $this->head . " where trno=?";
        $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);

        if ($result === 1) {
        } else {
            $msg = "Posting failed. Kindly check the head data.";
        }

        if ($msg === '') {
            $date = $this->othersClass->getCurrentTimeStamp();
            $data = ['postdate' => $date, 'postedby' => $user];
            $this->coreFunctions->sbcupdate($config['docmodule']->tablenum, $data, ['trno' => $trno]);
            $this->coreFunctions->execqry("delete from " . $config['docmodule']->head . " where trno=?", "delete", [$trno]);
            $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
            return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
        } else {
            $this->coreFunctions->execqry("delete from " . $config['docmodule']->hhead . " where trno=?", "delete", [$trno]);
            return ['trno' => $trno, 'status' => false, 'msg' => $msg];
        }
    } //end function 
} //end class
