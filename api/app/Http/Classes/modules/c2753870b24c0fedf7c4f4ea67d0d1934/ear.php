<?php

namespace App\Http\Classes\modules\c2753870b24c0fedf7c4f4ea67d0d1934;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

class ear
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Employee Activity Report';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $sqlquery;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
    public $head = 'tmdetail';
    public $prefix = '';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    private $stockselect;
    public $doclistdaterange = 12;

    private $fields = ['clientname', 'jstatus', 'status2', 'ref', 'title', 'scheddate', 'sortdate'];
    private $except = [];
    public $showfilteroption = true;
    public $showfilter = true;
    public $showcreatebtn = false;
    private $reporter;
    public $showfilterlabel = [];

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
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 5484
        );
        return $attrib;
    }
    public function createHeadbutton($config)
    {
        $btns = array(
            'load'
        );
        $buttons = $this->btnClass->create($btns);
        return $buttons;
    } // createHeadbutton
    public function createHeadField($config)
    {
        return [];
    }
    public function createTab($access, $config)
    {
        $tab = [];
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        return $obj;
    }
    public function loaddoclisting($config)
    {
        $userid = $config['params']['adminid'];
        $supervisor = $this->coreFunctions->datareader("select emp.issupervisor as value from client as c  left join employee as emp on emp.empid=c.clientid where c.clientid=?", [$userid]);
        $viewallemp = $this->othersClass->checkAccess($config['params']['user'], 5511);
        $addsvfliter = "";
        $joins = "";
        if ($viewallemp == '1') { //viewall
            $addsvfliter = "";
        } else {
            if ($supervisor == "1") {
                $joins = " join employee as sv on sv.empid=cl.clientid ";
                $addsvfliter = " and sv.supervisorid = $userid ";
            } else {
                return ['status' => false, 'msg' => 'Please advice your administrator regarding this restriction.'];
            }
        }

        $limit = '';
        $filtersearch = "";
        $searcfield = $this->fields;
        $search = '';

        if (isset($config['params']['search'])) {
            $search = $config['params']['search'];
            foreach ($searcfield as $key => $sfield) {
                if ($filtersearch == "") {
                    $filtersearch .= " and (" . $sfield . " like '%" . $search . "%'";
                } else {
                    $filtersearch .= " or " . $sfield . " like '%" . $search . "%'";
                } //end if
            }
            $filtersearch .= ")";
        }
        $filterdoc = '';
        $tracking = " ";
        $otapp = "  ";
        $undertime = " ";
        $leave = "  ";
        $word = " ";
        $restday = " ";
        $loan = " ";
        $travel = "";
        if (isset($config['params']['doclistingparam']['searchby'])) {
            $doclist = $config['params']['doclistingparam']['searchby'];
            if (isset($doclist['value']) && $doclist['value'] != "") {
                $tracking .= " and 'tracking' = '" . $doclist['value'] . "' ";
                $otapp .= " and 'otapp' = '" . $doclist['value'] . "' ";
                $undertime .= " and 'undertime' =  '" . $doclist['value'] . "' ";
                $leave .= " and 'leave' =  '" . $doclist['value'] . "' ";
                $restday .= " and 'restday' = '" . $doclist['value'] . "' ";
                $word .= " and 'word' =  '" . $doclist['value'] . "' ";
                $loan .= " and 'loan' = '" . $doclist['value'] . "' ";
                $travel .= " and 'travel' = '" . $doclist['value'] . "'";
            } else {
                $tracking .= " and 'tracking' = 'tracking' ";
                $otapp .= " and 'otapp' = 'otapp' ";
                $undertime .= " and 'undertime' = 'undertime' ";
                $leave .= " and 'leave' = 'leave' ";
                $restday .= " and 'restday' = 'restday' ";
                $word .= " and 'word' = 'word' ";
                $loan .= " and 'loan' = 'loan' ";
                $travel .= " and 'travel' = 'travel' ";
            }
        }

        if ($search != "") {
            $l = '';
        } else {
            $l = $limit;
        }

        $option = $config['params']['itemfilter'];
        $date1 = date('Y-m-d', strtotime($config['params']['date1']));
        $date2 = date('Y-m-d', strtotime($config['params']['date2']));
        $fillter_search = "";
        if ($filtersearch != "") {
            $fillter_search .= "where ''='' " . $filtersearch;
        }

        $qry = "select * from (
        
        select date(allapp.createdate) as dateid,
        
        concat(
          IF(allapp.dateid is not null, concat('In - ', allapp.dateid), ''),
          IF(allapp.dateid is not null AND allapp.dateid2 is not null, ' ', ''),
          IF(allapp.dateid2 is not null, concat('Out - ', allapp.dateid2), '')
        ) as scheddate
        
        , if(allapp.islatefilling=1,'Late Filling','') as ref,'' as tothrs,ifnull(allapp.dateid,allapp.dateid2) as sortdate,
        case
        when allapp.status = 'E' and allapp.status2 = 'A' then 'FOR APPROVAL'
        when allapp.status = 'E' then 'ENTRY'
        when allapp.status = 'A' then 'APPROVED'
        else 'DISAPPROVED' end as jstatus,
        case
        when allapp.status2 = 'E' and allapp.submitdate is null then 'ENTRY' 
        when allapp.status2 = 'E' and allapp.submitdate is not null then 'FOR APPROVAL'
        when allapp.status2 = 'A' then 'APPROVED'
         else 'DISAPPROVED' end as status2,
        cl.clientname,allapp.rem as remarks,'Tracking Application' as title,ifnull(allapp.approvedate,allapp.disapprovedate) as approvedate,
        ifnull(allapp.approvedate2,allapp.disapprovedate2) as date_approved_disapproved from obapplication as allapp
        join client as cl on cl.clientid = allapp.empid $joins
        where (date(allapp.dateid) between '$date1' and '$date2' or date(allapp.dateid2) between '$date1' and '$date2' and batchob <> 0) $addsvfliter $tracking 

        union all 
        select date(allapp.createdate) as dateid,date(allapp.dateid) as scheddate,'' as ref,allapp.othrs as tothrs,allapp.dateid as sortdate,
        case
        when allapp.otstatus = '1' and allapp.otstatus2 = '2' then 'FOR APPROVAL'
        when allapp.otstatus = '1' then 'ENTRY'
        when allapp.otstatus = '2' then 'APPROVED'
        else 'DISAPPROVED' end as jstatus,
        case
        when allapp.otstatus2 = '1' and allapp.submitdate is null then 'ENTRY' 
        when allapp.otstatus2 = '1' and allapp.submitdate is not null then 'FOR APPROVAL' 
        when allapp.otstatus2 = '2' then 'APPROVED'
        else 'DISAPPROVED' end as status2,
        cl.clientname,allapp.rem as remarks,'OT Application' as title,ifnull(allapp.approvedate,allapp.disapprovedate) as approvedate,
        ifnull(allapp.approvedate2,allapp.disapprovedate2) as date_approved_disapproved from otapplication as allapp
        join client as cl on cl.clientid = allapp.empid $joins
        where date(allapp.dateid) between '$date1' and '$date2' $addsvfliter $otapp
        

        union all 
        select date(allapp.createdate) as dateid,allapp.dateid as scheddate,'' as ref,'' as tothrs,allapp.dateid as sortdate,
        case
        when allapp.status = 'E' and allapp.status2 = 'A' then 'FOR APPROVAL'
        when allapp.status = 'E' then 'ENTRY'
        when allapp.status = 'A' then 'APPROVED'
        else 'DISAPPROVED' end as jstatus,
        case
        when allapp.status2 = 'E' and allapp.submitdate is null then 'ENTRY' 
        when allapp.status2 = 'E' and allapp.submitdate is not null then 'FOR APPROVAL' 
        when allapp.status2 = 'A' then 'APPROVED'
        else 'DISAPPROVED' end as status2,

        cl.clientname,allapp.rem as remarks,'Undertime Application' as title,ifnull(allapp.approvedate,allapp.disapprovedate) as approvedate,
        ifnull(allapp.approvedate2,allapp.disapprovedate2) as date_approved_disapproved from undertime as allapp
        join client as cl on cl.clientid = allapp.empid $joins
        where date(allapp.dateid) between '$date1' and '$date2' $addsvfliter $undertime
        

        union all
        select date(allapp.dateid) as dateid,date(allapp.effectivity) as scheddate,st.docno as ref,
        case when allapp.adays = 1 then 'Whole Day'
        else 'Half Day' end as tothrs,allapp.effectivity as sortdate,
        case
        when allapp.status = 'E' and allapp.status2 = 'A' then 'FOR APPROVAL'
        when allapp.status = 'E' then 'ENTRY'
        when allapp.status = 'A' then 'APPROVED'
        else 'DISAPPROVED' end as jstatus,
        case
        when allapp.status2 = 'E' then 'FOR APPROVAL'
        when allapp.status2 = 'A' then 'APPROVED'
        else 'DISAPPROVED' end as status2,
        cl.clientname,allapp.remarks,'Leave Application' as title,allapp.date_approved_disapproved as approvedate,
        date_approved_disapproved2 as date_approved_disapproved from leavetrans as allapp
        join client as cl on cl.clientid = allapp.empid
        left join leavesetup as st on st.trno = allapp.trno $joins
        where date(allapp.effectivity) between '$date1' and '$date2' $addsvfliter $leave
        

        union all
        select date(allapp.createdate) as dateid,date(allapp.dateid) as scheddate,'' as ref,'' as tothrs,allapp.dateid as sortdate,
        case
        when allapp.status = '0' and allapp.status2 = '1' then 'FOR APPROVAL'
        when allapp.status = '0' then 'ENTRY'
        when allapp.status = '1' then 'APPROVED'
        else 'DISAPPROVED' end as jstatus,
        case 
        when allapp.status2 = '0' and  allapp.submitdate is null then 'ENTRY' 
        when allapp.status2 = '0' and  allapp.submitdate is not null then 'FOR APPROVAL' 
        when allapp.status2 = '1' then 'APPROVED'
        else 'DISAPPROVED' end as status2,
        
        cl.clientname,allapp.rem as remarks,'Restday Application' as title,ifnull(allapp.approveddate,disapproveddate) as approvedate,
        ifnull(allapp.approveddate2,disapproveddate2) as date_approved_disapproved  from changeshiftapp as allapp
        join client as cl on cl.clientid = allapp.empid  $joins
        where date(allapp.dateid) between '$date1' and '$date2' $addsvfliter $restday
        

        union all
        select date(allapp.createdate) as dateid,date(allapp.dateid) as scheddate,'' as ref,'' as tothrs,allapp.dateid as sortdate,
        case
        when allapp.status = '0' and allapp.status2 = '1' then 'FOR APPROVAL'
        when allapp.status = '0' then 'ENTRY'
        when allapp.status = '1' then 'APPROVED'
        else 'DISAPPROVED' end as jstatus,
        case
        when allapp.status2 = '0' and  allapp.submitdate is null then 'ENTRY' 
        when allapp.status2 = '0' and  allapp.submitdate is not null then 'FOR APPROVAL' 
        when allapp.status2 = '1' then 'APPROVED'
        else 'DISAPPROVED' end as status2,
        cl.clientname,allapp.rem as remarks,' Working On Rest Day Application' as title,ifnull(allapp.approveddate,disapproveddate) as approvedate,
        ifnull(allapp.approveddate2,disapproveddate2) as date_approved_disapproved from changeshiftapp as allapp
        join client as cl on cl.clientid = allapp.empid $joins
        where date(allapp.dateid) between '$date1' and '$date2'  $addsvfliter $word
        

        union all
        select date(allapp.dateid) as dateid,date(allapp.effdate) as scheddate,allapp.docno as ref,'' as tothrs,allapp.effdate as sortdate,
        case
        when allapp.status = 'E' and allapp.status2 = 'A' then 'FOR APPROVAL'
        when allapp.status = 'E' then 'ENTRY'
        when allapp.status = 'A' then 'APPROVED'
        else 'DISAPPROVED' end as jstatus,
        case
        when allapp.status2 = 'E' and allapp.submitdate is null then 'ENTRY' 
        when allapp.status2 = 'E' and  allapp.submitdate is not null then 'FOR APPROVAL'
        when allapp.status2 = 'A' then 'APPROVED'
        else 'DISAPPROVED' end as status2,
        cl.clientname,allapp.remarks,'Loan Application' as title,allapp.date_approved_disapproved as approvedate,
        date_approved_disapproved2 as date_approved_disapproved from loanapplication as allapp
        join client as cl on cl.clientid = allapp.empid $joins
        where date(allapp.effdate) between '$date1' and '$date2' $addsvfliter $loan
        

        union all
        select allapp.dateid,
        concat(
          IF(date(allapp.startdate) is not null, concat('Start Date - ', date(allapp.startdate)), ''),
          IF(date(allapp.startdate) is not null and date(allapp.enddate) is not null, ' ', ''),
          IF(date(allapp.enddate) is not null, concat('End Date - ', date(allapp.enddate)), '')
        ) as scheddate,'' as ref,'' as tothrs,date(allapp.startdate) as sortdate,
         case
        when allapp.status = 'E' and allapp.status2 = 'A' then 'FOR APPROVAL'
        when allapp.status = 'E' then 'ENTRY'
        when allapp.status = 'A' then 'APPROVED'
        else 'DISAPPROVED' end as jstatus,
        case
        when allapp.status2 = 'E' and allapp.submitdate is null then 'ENTRY' 
        when allapp.status2 = 'E' and  allapp.submitdate is not null then 'FOR APPROVAL'
        when allapp.status2 = 'A' then 'APPROVED'
        else 'DISAPPROVED' end as status2,cl.clientname,allapp.remarks,'Travel Application' as title,
        ifnull(allapp.approvedate,disapprovedate) as approvedate,ifnull(allapp.approvedate2,disapprovedate2) as date_approved_disapproved
		FROM itinerary as allapp
	    join client as cl on cl.clientid = allapp.empid
        where date(startdate) between '$date1' and '$date2' $addsvfliter $travel


        ) as ear $fillter_search

        order by ear.sortdate desc 
        ";
        $data = $this->coreFunctions->opentable($qry);


        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
    }
    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }
    public function createdoclisting($config)
    {
        // 'action', 
        $getcols = ['title', 'scheddate', 'tothrs', 'listappstatus2', 'date_approved_disapproved', 'listappstatus', 'listapprovedate', 'listclientname', 'remarks', 'ref'];
        $stockbuttons = [];

        foreach ($getcols as $key => $value) {
            $$value = $key;
        }

        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

        $cols[$listclientname]['label'] = 'Employee Name';
        $cols[$title]['type'] = 'label';
        $cols[$title]['label'] = 'Application Name';
        $cols[$listappstatus2]['label'] = 'First Approver Status';
        $cols[$listappstatus]['label'] = 'Last Approver Status';

        $cols[$tothrs]['type'] = 'label';
        $cols[$tothrs]['label'] = 'Hours/Day';
        $cols[$tothrs]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';

        $cols[$scheddate]['label'] = 'Date';
        $cols[$scheddate]['style'] = 'width:170px;whiteSpace: normal;min-width:170px;';
        $cols[$listappstatus2]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$listappstatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$date_approved_disapproved]['label'] = 'First Approved Date';
        $cols[$listapprovedate]['label'] = 'Last Approved Date';
        $cols[$listapprovedate]['style'] = 'width:130px;whiteSpace: normal;min-width:130px;';

        return $cols;
    }
    public function paramsdatalisting($config)
    {
        $fields = ['searchby'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'searchby.label', 'Search by');
        data_set(
            $col1,
            'searchby.options',
            [
                ['label' => '', 'value' => ''],
                ['label' => 'Tracking Application', 'value' => 'tracking'],
                ['label' => 'Travel Application', 'value' => 'travel'],
                ['label' => 'OT Application', 'value' => 'otapp'],
                ['label' => 'Undertime Application', 'value' => 'undertime'],
                ['label' => 'Leave Application', 'value' => 'leave'],
                ['label' => 'Restday Application', 'value' => 'restday'],
                ['label' => 'Working On Rest Day Application', 'value' => 'word'],
                ['label' => 'Loan Application', 'value' => 'loan']
            ]
        );
        $data = $this->coreFunctions->opentable("select '' as searchby");
        return ['status' => true, 'data' => $data[0], 'txtfield' => ['col1' => $col1]];
    }
} //end class
