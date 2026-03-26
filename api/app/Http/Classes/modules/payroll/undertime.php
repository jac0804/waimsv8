<?php

namespace App\Http\Classes\modules\payroll;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\common\payrollcommon;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

use Carbon\Carbon;

class undertime
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'UNDERTIME APPLICATION';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $sqlquery;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
    public $head = 'undertime';
    public $prefix = '';
    public $tablelogs = 'payroll_log';
    public $tablelogs_del = '';
    private $stockselect;
    private $payrollcommon;

    private $fields = [
        'empid',
        'dateid',
        'rem',
        'underhrs'
    ];
    // 'remarks','acno','days','bal',
    private $except = ['clientid', 'client'];
    private $blnfields = [];
    public $showfilteroption = true;
    public $showfilter = true;
    public $showcreatebtn = true;
    private $reporter;
    private $isexist = 0;

    public $showfilterlabel = [
        ['val' => 'draft', 'label' => 'Entry', 'color' => 'primary'],
        ['val' => 'forapproval', 'label' => 'For Approval', 'color' => 'primary'],
        ['val' => 'approved', 'label' => 'Approved', 'color' => 'primary'],
        ['val' => 'disapproved', 'label' => 'Disapproved', 'color' => 'primary']
    ];


    public function __construct()
    {
        $this->btnClass = new buttonClass;
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
        $this->sqlquery = new sqlquery;
        $this->reporter = new SBCPDF;
        $this->payrollcommon = new payrollcommon;
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 4776,
            'new' => 4774,
            'save' => 4772,
            'delete' => 4775,
            'print' => 4773,
            'edit' => 4777

        );
        return $attrib;
    }

    public function createdoclisting($config)
    {
        $companyid = $config['params']['companyid'];
        // if ($companyid == 58) { //cdo
        //     $empid = isset($config['params']['adminid']) ? $config['params']['adminid'] : 0;
        //     if ($empid == 0) {
        //         $this->showcreatebtn = false;
        //     }
        // }
        $approver = $this->payrollcommon->checkapprover($config);
        $supervisor =  $this->payrollcommon->checksupervisor($config);
        if ($approver || $supervisor) {
            array_push($this->showfilterlabel, ['val' => 'approvedemp', 'label' => 'Approved Employees', 'color' => 'primary']);
        }
        $getcols = ['action', 'dateid', 'listappstatus2', 'listappstatus', 'clientname', 'rem', 'sync'];
        foreach ($getcols as $key => $value) {
            $$value = $key;
        }
        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[$dateid]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $cols[$listappstatus2]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;text-align:left';
        $cols[$listappstatus]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;text-align:left';
        $cols[$rem]['style'] = 'width:400px;whiteSpace: normal;min-width:400px;';

        $cols[$listappstatus]['type'] = 'label';
        $cols[$clientname]['label'] = 'Name';
        $cols[$clientname]['style'] = 'width:200px;whiteSpace:normal;min-width:200px;';

        if ($companyid != 44) {
            $cols[$sync]['type'] = 'coldel';
        }

        if ($companyid == 58) { //cdo
            $cols[$listappstatus]['label'] = 'Status (HR)';
        } else {
            $cols[$listappstatus2]['type'] = 'coldel';
        }

        $cols = $this->tabClass->delcollisting($cols);
        return $cols;
    }

    public function loaddoclisting($config)
    {
        $id = $config['params']['adminid'];
        $user = $config['params']['user'];

        $companyid = $config['params']['companyid'];

        $date1 = date('Y-m-d', strtotime($config['params']['date1']));
        $date2 = date('Y-m-d', strtotime($config['params']['date2']));

        $filteroption = '';
        $option = $config['params']['itemfilter'];
        $sortby = 'under.approvedate';

        $issupervisor = $this->coreFunctions->getfieldvalue("employee", "issupervisor", "empid=?", [$id]);
        $isapprover = $this->coreFunctions->getfieldvalue("employee", "isapprover", "empid=?", [$id]);

        $filteroptiondraft = '';
        $filteroptionapp = " under.status='A' and approvedate is not null ";
        switch ($companyid) {
            case 44: //stonepro
            case 53: //CAMERA SOUND
                $filteroptiondraft = " and under.status2='E'";
                $filteroptionapp = " ((under.status='A' and approvedate is not null) or under.status2='A') ";
                break;
        }

        switch ($option) {
            case 'draft':
                $filteroption = " and under.empid=" . $id . " and under.status='E' and under.submitdate is null";
                break;
            case 'forapproval':
                $filteroption = " and under.empid=" . $id . " and under.status='E' and under.status2='E'  and under.submitdate is not null";
                break;
            case 'approved':
                $sortby = 'under.dateid desc';
                $filteroption = " and under.empid=" . $id . " and " . $filteroptionapp;
                break;
            case 'disapproved':
                $filteroption = " and under.empid=" . $id . " and under.status='D' and disapprovedate is not null or disapprovedate2 is not null";
                break;
            default:
                if ($isapprover == 1) {
                    $filteroption = " and under.status='A' and approvedby='" . $user . "'";
                } else {
                    return ['data' => [], 'status' => false, 'msg' => 'This feature is for approvers only.'];
                }
                break;
        }

        $filtersearch = "";
        if (isset($config['params']['search'])) {
            $searchfield = ['cl.clientid', 'cl.client', 'cl.clientname', 'under.type', 'under.rem'];

            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
            $limit = "";
        }

        $entrystat = "under.status = 'E'";
        $approvedstat = "under.status = 'A'";
        $approvedstat2 = "under.status2 = 'A'";
        $disapprovedstat = "under.status = 'D'";
        $addfield = "";
        switch ($companyid) {
            case 53: //CAMERA SOUND
                $entrystat = "under.status = 'E' and under.status2 = 'E'";
                break;
            case 44: //stonepro
                $entrystat = "under.status = 'E' and under.status2 = 'E' and under.submitdate is null ";
                $addfield = "when under.status = 'E' and under.status2 = 'E' and under.submitdate is not null then 'FOR APPROVAL'";
                break;
            default:
                $entrystat = "under.status = 'E' and under.submitdate is null";
                $addfield = "when under.status = 'E' and under.submitdate is not null then 'FOR APPROVAL'";
                break;
        }

        if ($companyid == 58) {
            $jstatus = "case when under.status = 'E' and under.submitdate is null then 'ENTRY'
               when under.status = 'E' and under.submitdate is not null then 'FOR APPROVAL'
               when under.status = 'A' then 'APPROVED'
               when under.status = 'D' then 'DISAPPROVED' end as jstatus";
        } else {
            $jstatus = "case 
                        when " . $entrystat . " then 'ENTRY'
                        $addfield
                        when " . $approvedstat . " then 'APPROVED'
                        when " . $approvedstat2 . " then 'APPROVED (Supervisor)'
                        when " . $disapprovedstat . " then 'DISAPPROVED'
                    END as jstatus";
        }

        $qry = "
      select under.line as trno, under.line as clientid, 
      cl.client, cl.clientname, cl.clientid as empid,under.dateid, under.rem,  
      case
        when  approvedate2 is not null then approvedby2
        when  disapprovedate2 is not null then disapprovedby2
      end as issupervisor,
      case
        when  approvedate is not null then approvedby
        when  disapprovedate is not null then disapprovedby
      end as isapprover,
      $jstatus
      
      , if(under.isok=1,'Y','N') as sync,
      case when under.status2 = 'E' then 'ENTRY'
               when under.status2 = 'A' then 'APPROVED'
               when under.status2 = 'D' then 'DISAPPROVED' end as status2
      from undertime as under
      left join employee as emp on emp.empid = under.empid
      left join client as cl on cl.clientid = emp.empid
      where date(under.dateid) between '$date1' and '$date2'
      " . $filteroption . " $filtersearch 
      order by " . $sortby;
        $data = $this->coreFunctions->opentable($qry);

        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
    }

    public function createHeadbutton($config)
    {
        $btns = array(
            'load',
            'new',
            'save',
            'delete',
            'cancel',
            'print',
            'logs',
            'edit',
            'backlisting',
            'toggleup',
            'toggledown'
        );
        $buttons = $this->btnClass->create($btns);
        return $buttons;
    } // createHeadbutton

    public function createTab($access, $config)
    {
        return [];
    }

    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }
    public function createtab2($access, $config)
    {
        //testing for cdohris approver list tab
        $companyid = $config['params']['companyid'];

        if ($companyid == 58) { //cdohris
            $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entryapproverlist', 'label' => 'Approver List', 'access' => 'view']];
            $obj = $this->tabClass->createtab($tab, []);
            $return['APPROVER LIST'] = ['icon' => 'fa fa-users', 'tab' => $obj];
        } else {
            $return = [];
        }
        return $return;
    }

    public function createHeadField($config)
    {
        $companyid = $config['params']['companyid'];
        $fields = ['client', 'dateid', 'itime', 'status'];

        if ($companyid == 58) { //cdohris
            $fields = ['client', 'dateid', 'ttime', 'itime', 'status'];
        }
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'client.label', 'Code');
        data_set($col1, 'client.type', 'hidden');
        data_set($col1, 'client.required', false);
        data_set($col1, 'itime.label', 'Time');


        if ($companyid == 58) { // cdohris
            data_set($col1, 'itime.label', 'Time In');
            data_set($col1, 'ttime.label', 'Time Out');
        }
        $fields = ['createdate', 'rem'];
        $col2 = $this->fieldClass->create($fields);

        $fields = ['lblsubmit', 'submit'];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'lblsubmit.label', 'FOR APPROVAL');
        data_set($col3, 'submit.label', 'FOR APPROVAL');

        $fields = [];
        $col4 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function newclient($config)
    {
        $data = $this->resetdata($config['newclient']);
        $hideobj = [];
        $hideobj['lblsubmit'] = true;
        $hideobj['submit'] = true;
        return  ['head' => $data, 'islocked' => false, 'isposted' => false, 'status' => true, 'isnew' => true, 'msg' => 'Ready for New Ledger', 'hideobj' => $hideobj];
    }

    private function resetdata($client = '')
    {
        $data = [];
        $data[0]['clientid'] = 0;
        $data[0]['client'] = $client;
        $data[0]['dateid'] = $this->othersClass->getCurrentDate();
        $data[0]['itime'] = '00:00';
        $data[0]['ttime'] = '00:00';
        $data[0]['rem'] = '';
        $data[0]['type'] = '';
        $data[0]['status'] = 'ENTRY';
        $data[0]['lblsubmit'] = '';
        $data[0]['submit'] = null;
        $data[0]['submitdate'] = null;
        $data[0]['createdate'] = $this->othersClass->getCurrentDate();
        return $data;
    }

    function getheadqry($config, $trno)
    {
        $adminid = $config['params']['adminid'];
        return "
        select under.line as trno, under.line as clientid, cl.client, 
        cl.clientname, cl.clientid as empid,
        under.type, date(under.dateid) as dateid, time(dateid) as itime,
        time(dateid2) as ttime,
        under.rem,under.createdate, under.submitdate,
        case 
          when under.status = 'E' and under.status2 = 'E' and under.submitdate is null then 'ENTRY'
          when under.status = 'E' and (under.status2 = 'E' or under.status2 = 'A') and under.submitdate is not null then 'FOR APPROVAL'
          when under.status = 'A' then 'APPROVED'
          when under.status = 'D' or under.status = 'D' then 'DISAPPROVED'
        END as status
        from undertime as under
        left join employee as emp on emp.empid = under.empid
        left join client as cl on cl.clientid = emp.empid
        where under.line = '$trno' and under.empid = '$adminid'";
    }


    public function loadheaddata($config)
    {
        $trno = $config['params']['clientid'];

        if ($trno == 0) {
            if (isset($config['params']['adminid'])) {
                $trno = $config['params']['adminid'];
            }
        }

        $head = $this->coreFunctions->opentable($this->getheadqry($config, $trno));
        if (!empty($head)) {
            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }
            $hideobj = [];
            $submitdate = $head[0]->submitdate != null ? true : false;
            if ($submitdate) {
                $hideobj['submit'] = true;
                switch ($head[0]->status) {
                    case 'ENTRY':
                        $hideobj['submit'] = false;
                        $hideobj['lblsubmit'] = true;
                        break;
                    case 'FOR APPROVAL':
                        $hideobj['lblsubmit'] = false;
                        break;
                    default:
                        $hideobj['lblsubmit'] = true;
                        break;
                }
            } else {
                $hideobj['submit'] = false;
                $hideobj['lblsubmit'] = true;
            }

            if ($head[0]->status == 'APPROVED') {
                $hideobj['lblsubmit'] = true;
            }

            return  ['head' => $head, 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => false, 'qq' => $config['params']['clientid'], 'action' => 'backlisting', 'hideobj' => $hideobj];
        } else {
            $msg = 'Data Fetched Failed, either somebody already deleted the transaction or modified...';

            if ($this->isexist == 1) {
                $msg = "Already Exist";
            }

            $head = $this->resetdata();
            return ['status' => false, 'isnew' => true, 'head' => $head, 'msg' => $msg, 'action' => 'backlisting'];
        }
    }

    public function updatehead($config, $isupdate)
    {
        $head = $config['params']['head'];
        $center = $config['params']['center'];
        $empid = $config['params']['adminid'];
        $companyid = $config['params']['companyid'];
        $data = [];

        if ($companyid == 58) { //cdo
            $chkrestriction = $this->payrollcommon->checkportalrestrict($head, $config);
            if (!empty($chkrestriction['msg'])) {
                $msg = $chkrestriction['msg'];
                return ['status' => false, 'msg' => $msg];
            }
        }

        if (isset($head['trno'])) {
            $approved = $this->coreFunctions->datareader("select approvedate as value from undertime where line=? and approvedate is not null", [$head['trno']]);
            $approved2 = $this->coreFunctions->datareader("select approvedate2 as value from undertime where line=? and approvedate2 is not null", [$head['trno']]);
            if ($approved || $approved2) {
                return ['status' => false, 'msg' => 'Cannot update; already approved.', 'clientid' => $config['params']['adminid']];
            }
            $disapproved = $this->coreFunctions->datareader("select disapprovedate as value from undertime where line=? and disapprovedate is not null", [$head['trno']]);
            $disapproved2 = $this->coreFunctions->datareader("select disapprovedate2 as value from undertime where line=? and disapprovedate2 is not null", [$head['trno']]);
            if ($disapproved || $disapproved2) {
                return ['status' => false, 'msg' => 'Cannot update; already Disapproved.', 'clientid' => $config['params']['adminid']];
            }
            $submitdate = $this->coreFunctions->datareader("select submitdate as value from undertime where line=? and submitdate is not null", [$head['trno']]);
            if ($submitdate) {
                return ['status' => false, 'msg' => 'Cannot update; already For approval.', 'clientid' => $config['params']['adminid']];
            }
        }
        $clientid = 0;
        $msg = '';
        $status = true;
        foreach ($this->fields as $key) {
            if (array_key_exists($key, $head)) {
                $data[$key] = $head[$key];
                if (!in_array($key, $this->except)) {
                    $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
                } //end if 
            }
        }

        $data['dateid'] = $this->othersClass->sanitizekeyfield('dateid', $head['dateid'] . " " . $head['itime']);

        $date = date('Y-m-d', strtotime($data['dateid']));
        $empname = $this->coreFunctions->datareader("select cl.clientname as value 
      from employee as e
      left join client as cl on cl.clientid = e.empid
      where e.empid = ?", [$config['params']['adminid']]);
        $underhrs = 0;
        if ($companyid == 58) { // cdohris
            $data['dateid2'] = $this->othersClass->sanitizekeyfield('dateid', $head['dateid'] . " " . $head['ttime']);
            //dateid: in  //dateid2: out 
            $actualout = Carbon::parse($data['dateid2']);
            $actualin = Carbon::parse($data['dateid']);


            if ($actualin->lessThan($actualout)) {
                $underhrs = $actualout->diffInMinutes($actualin);
                $underhrs = $underhrs / 60;
            }
            $data['underhrs'] = $underhrs;
        }
        if ($companyid == 44) { //stonepro
            $schedout = $this->coreFunctions->datareader("select schedout as value from timecard where empid = ? and dateid = '" . $date . "'", [$empid]);
            if (!empty($schedout)) {
                $schedout = Carbon::parse($schedout);
                $actualout = Carbon::parse($data['dateid']);

                if ($actualout->lessThan($schedout)) {
                    $underhrs = $actualout->diffInMinutes($schedout);
                    $underhrs = $underhrs / 60;
                }
                $data['underhrs'] = $underhrs;
            }
        }
        if ($isupdate) {
            if (!empty($this->checking($config, $date))) {
                $msg = "Already Exist";
                $clientid = 0;
                $this->isexist = 1;
                $status = false;
            } else {
                $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
                $data['editby'] = $config['params']['user'];
                $this->coreFunctions->sbcupdate($this->head, $data, ['line' => $head['clientid']]);
                $clientid = $head['clientid'];
                $this->isexist = 0;
            }
        } else {

            $data['empid'] =  $config['params']['adminid'];
            $data['status'] =  'E';
            $data['status2'] =  'E';
            $date = date('Y-m-d', strtotime($data['dateid']));
            if (!empty($this->checking($config, $date))) {
                $msg = "Cant not Create Already Exist " . $date;
                $clientid = 0;
                $this->isexist = 1;
                $status = false;
            } else {
                $data['createdate'] =  $this->othersClass->getCurrentTimeStamp();
                $data['createby'] =  $config['params']['user'];
                $clientid = $this->coreFunctions->insertGetId($this->head, $data);
                $this->logger->sbcmasterlog(
                    $clientid,
                    $config,
                    "CREATE - NAME: $empname, DATE: " . $data['dateid'] . ", 
          REMARKS: " . $data['rem'] . ""
                );
            }
        }

        if ($status) {
            $msg = 'Successfully saved';
        }
        return ['status' => $status, 'msg' => $msg, 'clientid' => $clientid];
    } // end function

    public function checking($config, $date)
    {
        $head = $config['params']['head'];
        $empid = $config['params']['adminid'];
        $line = $head['clientid'];

        $qry = "select status,status,line from $this->head where empid = $empid and 
        date(dateid) = '" . $date . "' and (status <> 'D' and status2 <> 'D') order by line desc";
        $data =  $this->coreFunctions->opentable($qry);
        if (!empty($data)) {

            if ($data[0]->line == $line) {
                return [];
            }
            return $data;
        }
        return [];
    }

    public function stockstatusposted($config)
    {
        $line = $config['params']['trno'];
        $update = $this->coreFunctions->sbcupdate($this->head, ['submitdate' => $this->othersClass->getCurrentTimeStamp()], ['line' => $line]);
        if ($update) {
            $url = 'App\Http\Classes\modules\payroll\\' . 'undertime';
            $empid = $this->coreFunctions->getfieldvalue($this->head, "empid", "line=?", [$line]);
            $data = ['empid' => $empid];
            $appstatus = $this->othersClass->insertUpdatePendingapp(0, $line, 'UNDERTIME', $data, $url, $config, 0, true, true);
            if (!$appstatus['status']) {
                $this->coreFunctions->sbcupdate($this->head, ['submitdate' => null], ['line' => $line]);
                $msg = $appstatus['msg'];
                $status = $appstatus['status'];
                return ['status' => $status, 'msg' => $msg];
            }
        } else {
            return ['status' => false, 'msg' => 'Error updating record'];
        }
        $submitdate = $this->othersClass->getCurrentTimeStamp();
        $this->logger->sbcmasterlog($line, $config, "SUBMIT DATE : " . $submitdate);
        return ['status' => true, 'msg' => 'Success', 'backlisting' => true];
    }

    public function getlastclient()
    {
        $last_id = $this->coreFunctions->datareader("select line as value 
        from " . $this->head . " 
        order by line DESC LIMIT 1");

        return $last_id;
    }
    public function deletetrans($config)
    {

        $clientid = $config['params']['clientid'];

        $res = $this->approved_dis($config);
        if ($res['status']) {
            $msg = 'Cannot update; already approved.';
            if ($res['istatus'] == 2) {
                $msg = 'Cannot update; already disapproved.';
            }
            return ['status' => false, 'msg' => $msg, 'clientid' => $clientid];
        }


        $qry = "select line as value from undertime where line = '$clientid' and status != 'E'";
        $count = $this->coreFunctions->datareader($qry);

        if ($count != "") {
            return ['clientid' => '0', 'status' => false, 'msg' => "Transaction cannot be deleted."];
        }

        $submitdate = $this->coreFunctions->datareader("select submitdate as value from undertime where line=? and submitdate is not null", [$clientid]);
        if ($submitdate) {
            return ['status' => false, 'msg' => 'Cannot delete; already For approval.', 'clientid' => $clientid];
        }

        $this->coreFunctions->execqry('delete from undertime where line=?', 'delete', [$clientid]);
        return ['clientid' => $clientid, 'status' => true, 'msg' => 'Successfully deleted.', 'action' => 'backlisting'];
    } //end function


    // -> print function
    public function reportsetup($config)
    {
        $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
        $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);

        $modulename = $this->modulename;
        $data = [];
        $style = 'width:500px;max-width:500px;';
        return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
    }

    public function reportdata($config)
    {
        $this->logger->sbcviewreportlog($config);
        $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }
    public function approvers($params)
    {
        $companyid = $params['companyid'];

        switch ($companyid) {
            case 44: // stonepro
            case 58: // cdohris
                $approvers = ['issupervisor', 'isapprover'];
                break;
            default:
                $approvers = ['isapprover'];
                break;
        }
        return $approvers;
    }
    public function approved_dis($config)
    {
        $clientid = $config['params']['clientid'];
        $qry = "select status, status2 from undertime where line = ?";
        $status = $this->coreFunctions->opentable($qry, [$clientid]);
        $array_stat = ['A', 'D'];

        if (in_array($status[0]->status2, $array_stat)) {
            return ['status' => true, 'istatus' => $status[0]->status2];
        }

        if (in_array($status[0]->status, $array_stat)) {
            return ['status' => true, 'istatus' => $status[0]->status];
        }

        return ['status' => false];
    }
} //end class
