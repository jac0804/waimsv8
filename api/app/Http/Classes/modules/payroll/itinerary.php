<?php

namespace App\Http\Classes\modules\payroll;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;
use App\Http\Classes\common\linkemail;
use App\Http\Classes\common\payrollcommon;
use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;
use GuzzleHttp\Psr7\Query;
use Illuminate\Support\Facades\Storage;
use DateTime;
use DateInterval;
use DatePeriod;
use App\Http\Classes\sbcscript\sbcscript;

class itinerary
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'TRAVEL APPLICATION';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $linkemail;
    private $payrollcommon;
    private $logger;
    private $sqlquery;
    private $sbcscript;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
    public $head = 'itinerary';
    public $prefix = '';
    public $tablelogs = 'payroll_log';
    public $tablelogs_del = '';
    private $stockselect;

    private $fields = [
        'empid',
        'startdate',
        'enddate',
        'remarks',
        'mealamt',
        'mealnum',
        'texpense',
        'expensetype',
        'lodgeexp',
        'lengthstay',
        'gas',
        'misc',
        'ext',
        'islatefilling'
    ];
    private $except = ['clientid', 'client'];
    private $blnfields = ['islatefilling'];
    public $showfilteroption = true;
    public $showfilter = true;
    public $showcreatebtn = true;
    private $reporter;
    private $isexist = 0;

    public $showfilterlabel = [
        ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
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
        $this->linkemail = new linkemail;
        $this->payrollcommon = new payrollcommon;
        $this->sbcscript = new sbcscript;
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 5352,
            'new' => 5353,
            'save' => 5354,
            'delete' => 5355,
            'print' => 5356,
            'edit' => 5357
        );
        return $attrib;
    }

    public function createdoclisting($config)
    {
        $companyid = $config['params']['companyid'];
        $approver = $this->payrollcommon->checkapprover($config);
        $supervisor =  $this->payrollcommon->checksupervisor($config);


        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='TRAVEL'");
        if ($approversetup == '') {
            $approversetup = $this->approvers($config['params']);
        } else {
            $approversetup = explode(',', $approversetup);
        }
        foreach ($approversetup as $appkey => $appsetup) {
            if ($appsetup == 'Supervisor') {
                $approversetup[$appkey] = 'issupervisor';
            } else {
                $approversetup[$appkey] = 'isapprover';
            }
        }

        $getcols = ['action', 'createdate', 'startdate', 'enddate', 'clientname', 'remarks', 'listappstatus2', 'date_approved_disapprovedsup', 'approvedby_disapprovedbysup', 'rem2', 'listappstatus', 'date_approved_disapproved', 'approvedby_disapprovedby'];
        foreach ($getcols as $key => $value) {
            $$value = $key;
        }

        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[$startdate]['style'] = 'width:100px;whiteSpace: normal;min-width:120px;text-align:left;';
        $cols[$enddate]['style'] = 'width:100px;whiteSpace: normal;min-width:120px;text-align:left;';
        $cols[$listappstatus]['style'] = 'width:100px;whiteSpace: normal;min-width:120px;text-align:left;';

        $cols[$remarks]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;';
        $cols[$listappstatus]['type'] = 'label';
        $cols[$listappstatus]['label'] = 'Status';
        $cols[$clientname]['label'] = 'Name';
        $cols[$clientname]['style'] = 'width:200px;whiteSpace:normal;min-width:200px;';
        $cols[$createdate]['label'] = 'Date';

        $cols[$date_approved_disapproved]['style'] = 'text-align:center;';
        $cols[$approvedby_disapprovedby]['style'] = 'text-align:center;';

        if (count($approversetup) > 1) {
            $cols[$listappstatus2]['style'] = 'width:100px;whiteSpace: normal;min-width:120px; text-align:left;';
            $cols[$rem2]['label'] = '(Supervisor) Remarks';
        } else {
            $cols[$listappstatus2]['type'] = 'coldel';
            $cols[$date_approved_disapprovedsup]['type'] = 'coldel';
            $cols[$approvedby_disapprovedbysup]['type'] = 'coldel';
            $cols[$rem2]['type'] = 'coldel';
        }


        $this->tabClass->delcollisting($cols);
        return $cols;
    }

    public function loaddoclisting($config)
    {
        $id = $config['params']['adminid'];
        $user = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $filteroption = '';
        $option = $config['params']['itemfilter'];
        $sortby = 'it.approvedate';

        $addraft = ""; //draft
        switch ($option) {
            case 'draft':
                $filteroption = " where it.empid=" . $id . " and it.status='E' and it.submitdate is null";
                break;
            case 'forapproval':
                $filteroption = " where it.empid=" . $id . " and it.status='E' and it.submitdate is not null";
                break;
            case 'approved':
                $filteroption = " where it.empid=" . $id . " and it.status='A'";
                $sortby = 'it.dateid desc';
                break;
            case 'disapproved':
                $filteroption = " where it.empid=" . $id . " and it.status='D'";
                break;
        }
        $filtersearch = "";
        if (isset($config['params']['search'])) {
            $searchfield = ['cl.clientid', 'cl.client', 'cl.clientname', 'it.remarks', 'it.dateid'];

            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
            $limit = "";
        }

        $addcase = "

                  case 
                  when it.status2 = 'E' and it.submitdate is null then 'ENTRY'
                  when it.status2 = 'E' and it.submitdate is not null then 'FOR APPROVAL'
                  when it.status2 = 'A' then 'APPROVED'
                  when it.status2 = 'D' then 'DISAPPROVED'
                  END as status2,
                  case 
                  when it.status = 'E' and it.submitdate is null then 'ENTRY'
                  when it.status = 'E' and it.submitdate is not null then 'FOR APPROVAL'
                  when it.status = 'A' then 'APPROVED'
                  when it.status = 'D' then 'DISAPPROVED'
                  END";
        $qry = "
      select it.trno, it.trno as clientid, 
      cl.client, cl.clientname,it.remarks,
      " . $addcase . " as jstatus,
      date(it.createdate) as createdate,date(it.approvedate) as date_approved_disapproved,
      app.clientname as approvedby_disapprovedby,date(it.startdate) as startdate,date(it.enddate) as enddate,
      ifnull(appby2.clientname,disappby2.clientname) as approvedby_disapprovedbysup,ifnull(date(it.approvedate2),date(it.disapprovedate2)) as date_approved_disapprovedsup
     
      from itinerary as it
      left join employee as emp on emp.empid = it.empid
      left join client as cl on cl.clientid = emp.empid
      left join client as app on app.email = it.approvedby and app.email <> ''

      left join client as appby2 on appby2.email = it.approvedby and appby2.email <> ''
      left join client as disappby2 on disappby2.email = it.approvedby and disappby2.email <> ''
      
      
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
            'edit',
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
        $companyid = $config['params']['companyid'];
        $fields = [];

        $col1 = $this->fieldClass->create($fields);
        $tab = [];

        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        return $obj;
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

        if ($companyid == 58) { //cdo
            $fields = ['client', 'lblcleared', 'remarks', 'lblitemdesc', ['startdate', 'enddate'], 'islatefilling'];
            $col1 = $this->fieldClass->create($fields);
            data_set($col1, 'client.label', 'Code');
            data_set($col1, 'client.action', 'lookupledger');
            data_set($col1, 'client.type', 'hidden');
            data_set($col1, 'lblcleared.label', 'PURPOSE OF TRAVEL:');
            data_set($col1, 'remarks.label', '');
            data_set($col1, 'lblitemdesc.label', 'DATES OF TRAVEL:');

            data_set($col1, 'startdate.required', true);
            data_set($col1, 'enddate.required', true);
            data_set($col1, 'remarks.required', true);

            $fields = [
                'lblaccessories',
                ['lblbilling', 'lblcostuom'],
                ['mealamt', 'mealnum'],
                ['lbldepreciation', 'expensetype'],
                ['texpense', 'gas'],
                'lbllocation',
                ['lodgeexp', 'lengthstay'],
                'lblvehicleinfo',
                'misc'
            ];
            $col2 = $this->fieldClass->create($fields);
            data_set($col2, 'lblaccessories.label', 'BUDGET NEEDED:');
            data_set($col2, 'lblbilling.label', 'A. PHP PER MEAL');
            data_set($col2, 'lblcostuom.style', 'font-weight:bold');
            data_set($col2, 'lblcostuom.label', 'NO. OF MEALS');
            data_set($col2, 'lbldepreciation.label', 'B. EXPENSE TYPE:');
            data_set($col2, 'lbldepreciation.style', 'font-weight:bold');
            data_set($col2, 'expensetype.required', false);
            data_set($col2, 'expensetype.label', '');
            data_set($col2, 'lbllocation.label', 'C. LODGING EXPENSES: ');
            data_set($col2, 'lbllocation.style', 'font-weight:bold');
            data_set($col2, 'lblvehicleinfo.label', 'D. OTHER MISCELLANEOUS');
            data_set($col2, 'lblvehicleinfo.style', 'font-weight:bold');


            $fields = ['lblsource', 'lbldestination', 'amt4', 'lblpassbook', 'amt5', 'lblreconcile', 'amt6', 'lblearned', 'amt7', 'lblrem', 'ext'];
            $col3 = $this->fieldClass->create($fields);

            data_set($col3, 'lblsource.label', 'TOTAL:');
            data_set($col3, 'lbldestination.label', 'A. MEALS:');
            data_set($col3, 'amt4.label', '');
            data_set($col3, 'amt4.class', 'csamt4 sbccsreadonly');
            data_set($col3, 'lblpassbook.label', 'B. TRANSPORTATION:');
            data_set($col3, 'amt5.label', '');
            data_set($col3, 'amt5.class', 'csamt5 sbccsreadonly');
            data_set($col3, 'lblreconcile.label', 'C. LODGE:');
            data_set($col3, 'amt6.label', '');
            data_set($col3, 'amt6.class', 'csamt6 sbccsreadonly');
            data_set($col3, 'lblearned.label', 'D. MISCELLANEOUS:');
            data_set($col3, 'amt7.label', '');
            data_set($col3, 'amt7.class', 'csamt7 sbccsreadonly');
            data_set($col3, 'lblrem.label', 'TOTAL BUDGET NEEDED');
            data_set($col3, 'lblrem.style', 'font-weight:bold;font-size:15px');
            data_set($col3, 'ext.label', '');
            data_set($col3, 'ext.class', 'csamt8 sbccsreadonly');

            $fields = ['lblgrossprofit', 'lblcleared', 'lblrecondate', 'lblendingbal', 'lblsubmit', 'submit'];
            $col4 = $this->fieldClass->create($fields);

            data_set($col4, 'lblgrossprofit.style', 'font-weight:bold;font-size:20px;color:red');
            data_set($col4, 'lblgrossprofit.label', 'Reminder: After submitting you travel application, 
                            please inform your approving head/manager and the budget officer.');

            data_set($col4, 'lblcleared.label', 'NOTES:');
            data_set($col4, 'lblrecondate.label', '1. Items B and D are subject for liquidation.');
            data_set($col4, 'lblendingbal.label', '2. No LIQUIDATIONS No CASH ADVANCE release.');

            data_set($col4, 'lblsubmit.type', 'label');
            data_set($col4, 'lblsubmit.label', 'FOR APPROVAL');
            data_set($col4, 'submit.label', 'FOR APPROVAL');
            data_set($col4, 'lblsubmit.style', 'font-weight:bold;font-size:15px;font-family:Century Gothic;color: green;');
        } else {
            $fields = ['client', 'createdate', ['startdate', 'enddate'], 'status'];

            $col1 = $this->fieldClass->create($fields);
            data_set($col1, 'client.label', 'Code');
            data_set($col1, 'client.action', 'lookupledger');
            data_set($col1, 'client.type', 'hidden');

            $fields = ['remarks'];


            $col2 = $this->fieldClass->create($fields);

            $fields = ['lblsubmit', 'submit'];
            $col3 = $this->fieldClass->create($fields);

            data_set($col3, 'lblsubmit.type', 'label');
            data_set($col3, 'lblsubmit.label', 'FOR APPROVAL');
            data_set($col3, 'submit.label', 'FOR APPROVAL');
            data_set($col3, 'lblsubmit.style', 'font-weight:bold;font-size:15px;font-family:Century Gothic;color: green;');

            $fields = [];
            $col4 = $this->fieldClass->create($fields);
        }


        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function sbcscript($config)
    {
        if ($config['params']['companyid'] == 58) { //cdo
            return $this->sbcscript->itinerary($config);
        } else {
            return true;
        }
    }

    public function newclient($config)
    {
        $data = $this->resetdata($config['newclient'], $config['params']);
        $hideobj = [];

        $hideobj['submit'] = true;
        $hideobj['lblsubmit'] = true;

        return  [
            'head' => $data,
            'islocked' => false,
            'isposted' => false,
            'status' => true,
            'isnew' => true,
            'msg' => 'Ready for New Ledger',
            'hideobj' => $hideobj
        ];
    }

    private function resetdata($client = '', $config)
    {
        $data = [];
        $data[0]['clientid'] = 0;
        $data[0]['client'] = $client;
        $data[0]['dateid'] = $this->othersClass->getCurrentDate();
        $data[0]['createdate'] = $this->othersClass->getCurrentDate();
        $data[0]['remarks'] = '';
        $data[0]['status'] = 'ENTRY';
        $data[0]['startdate'] = $this->othersClass->getCurrentDate();
        $data[0]['enddate'] = $this->othersClass->getCurrentDate();
        $data[0]['submit'] = null;
        $data[0]['lblsubmit'] = '';
        $data[0]['submitdate'] = null;

        $data[0]['ext'] = '';
        $data[0]['mealamt'] = '';
        $data[0]['mealnum'] = '';
        $data[0]['expensetype'] = '';
        $data[0]['texpense'] = '';
        $data[0]['gas'] = '';
        $data[0]['lodgeexp'] = '';
        $data[0]['lengthstay'] = '';
        $data[0]['misc'] = '';
        $data[0]['islatefilling'] = '0';
        return $data;
    }

    function getheadqry($config, $trno)
    {
        $companyid = $config['params']['companyid'];
        $addcase = "case 
          when it.status = 'E' and it.submitdate is null then 'ENTRY'
          when it.status = 'E' and it.submitdate is not null then 'FOR APPROVAL'
          when it.status = 'A' then 'APPROVED'
          when it.status = 'D' then 'DISAPPROVED'
          END ";

        $amt = '';
        $transpo = '';
        $lodge = '';
        $misc = '';
        $total = '';
        if ($companyid == 58) { //cdo
            $amt = ',(it.mealamt * it.mealnum) as amt4';
            $transpo = ', (case when it.expensetype="Gasoline" then 0 else it.texpense end) as amt5';
            $lodge = ',(it.lodgeexp * it.lengthstay) as amt6';
            $misc = ',it.misc as amt7';

            $total = ", (case when it.expensetype='Gasoline' then sum((it.mealamt * it.mealnum) + (it.lodgeexp * it.lengthstay) + it.misc) else 
                         sum((it.mealamt * it.mealnum) + (it.lodgeexp * it.lengthstay) + it.misc + it.texpense) end) as amt8";
        }

        return "select it.trno, it.trno as clientid, cl.client,
                cl.clientname, cl.clientid as empid,it.islatefilling,
                date(it.dateid) as dateid,date(it.startdate) as startdate,date(it.enddate) as enddate,
                it.remarks,it.createdate,it.submitdate,
                " . $addcase . " as status, it.ext,
                it.mealamt,it.mealnum $amt,it.texpense,it.gas,
                it.expensetype $transpo,
                it.lodgeexp,it.lengthstay $lodge,it.misc $misc 

                from itinerary as it
                left join employee as emp on emp.empid = it.empid
                left join client as cl on cl.clientid = emp.empid
                where it.trno=" . $trno;
    }


    public function loadheaddata($config)
    {
        $trno = $config['params']['clientid'];
        $companyid = $config['params']['companyid'];

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



            $submit = $head[0]->submitdate != null ? true : false;
            if ($submit) {
                $hideobj['submit'] = true;
                $hideobj['lblsubmit'] = false;
            } else {
                $hideobj['submit'] = false;
                $hideobj['lblsubmit'] = true;
            }

            if ($head[0]->status == "APPROVED" || $head[0]->status == "DISAPPROVED") {
                $hideobj['lblsubmit'] = true;
            }

            foreach ($this->blnfields as $key => $value) {
                if ($head[0]->$value) {
                    $head[0]->$value = "1";
                } else
                    $head[0]->$value = "0";
            }

            return  ['head' => $head, 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => false, 'qq' => $config['params']['clientid'], 'hideobj' => $hideobj, 'action' => 'backlisting'];
        } else {
            $msg = 'Data Fetched Failed, either somebody already deleted the transaction or modified...';

            if ($this->isexist == 1) {
                $msg = "Already Exist";
            }

            $head = $this->resetdata('', $config['params']);
            return ['status' => false, 'isnew' => true, 'head' => $head, 'msg' => $msg, 'action' => 'backlisting'];
        }
    }

    public function updatehead($config, $isupdate)
    {
        $head = $config['params']['head'];
        $center = $config['params']['center'];
        $companyid = $config['params']['companyid'];
        $empid = $config['params']['adminid'];
        $data = [];

        $clientid = 0;
        $msg = '';

        if ($empid == 0) {
            return ['status' => false, 'msg' => 'Invalid Employee.'];
        }

        if ($companyid == 58) { //cdo
            $chkrestriction = $this->payrollcommon->checkportalrestrict($head, $config);
            if (!empty($chkrestriction['msg'])) {
                if ($head['islatefilling'] == 0) {
                    $msg = $chkrestriction['msg'];
                    return ['status' => false, 'msg' => $msg];
                }
            } else {
                if ($head['islatefilling'] == 1) {
                    return ['status' => false, 'msg' => 'Invalid filling, this activity is not allowed for late filing. Please uncheck the late filing box.'];
                }
            }
            // $empbrcomp = $this->coreFunctions->opentable("select branchid, divid from employee where empid='" . $empid . "'");
            // $startdate = date('Y-m-d', strtotime($head['startdate']));
            // $enddate = date('Y-m-d', strtotime($head['enddate']));
            // $batch = $this->coreFunctions->opentable("select startdate, enddate from batch 
            //             where branchid='" . $empbrcomp[0]->branchid . "' and divid='" . $empbrcomp[0]->divid . "'
            //             and '" . $enddate . "' between startdate and enddate");

            // if (empty($batch)) {
            //     return ['status' => false, 'msg' => 'Payroll cutoff date has not yet been set.', 'clientid' => $config['params']['adminid']];
            // } else {
            //     $createdate = date('Y-m-d', strtotime($head['createdate']));
            //     $date = $batch[0]->enddate;
            //     $duedate = date('Y-m-d', strtotime($date . ' +2 days'));

            //     if ($createdate > $duedate) {
            //         return ['status' => false, 'msg' => 'Final filing & approval of activities is every 2nd and 17th of the month. Ensure the form is completely filed out to avoid invalid activities.', 'clientid' => $config['params']['adminid']];
            //     }
            // }
        }

        if (isset($head['trno'])) {
            $submitdate = $this->coreFunctions->datareader("select submitdate as value from $this->head where trno=? and submitdate is not null", [$head['trno']]);
            if ($submitdate) {
                return ['status' => false, 'msg' => 'Cannot update; already For approval.', 'clientid' => $config['params']['adminid']];
            }

            $approved = $this->coreFunctions->datareader("select approvedate as value from $this->head where trno=? and approvedate is not null", [$head['trno']]);
            $disapproved = $this->coreFunctions->datareader("select disapprovedate as value from $this->head where trno=? and disapprovedate is not null", [$head['trno']]);
            if ($approved) {
                return ['status' => false, 'msg' => 'Cannot update; already Approved.', 'clientid' => $config['params']['adminid']];
            }
            if ($disapproved) {
                return ['status' => false, 'msg' => 'Cannot update; already Disapproved.', 'clientid' => $config['params']['adminid']];
            }
        }

        foreach ($this->fields as $key) {
            if (array_key_exists($key, $head)) {
                $data[$key] = $head[$key];
                if (!in_array($key, $this->except)) {
                    $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
                } //end if 
            }
        }
        if ($data['startdate'] == null) {
            return ['status' => false, 'msg' => 'Start Date must not be empty or Invalid.', 'clientid' => $config['params']['adminid']];
        }
        if ($data['enddate'] == null) {
            return ['status' => false, 'msg' => 'End Date must not be empty or Invalid.', 'clientid' => $config['params']['adminid']];
        }

        if ($data['startdate'] > $data['enddate']) {
            return ['status' => false, 'msg' => 'Start Date must not be greater than End Date.', 'clientid' => $config['params']['adminid']];
        }

        $data['dateid'] = $this->othersClass->sanitizekeyfield('dateid', $head['dateid']);
        $empname = $this->coreFunctions->datareader("select cl.clientname as value 
        from employee as e
        left join client as cl on cl.clientid = e.empid
        where e.empid = ?", [$config['params']['adminid']]);

        $start = date('Y-m-d', strtotime($data['startdate']));
        $end = date('Y-m-d', strtotime($head['enddate']));

        // $data['ext']=;

        $total = 0;

        if ($companyid == 58) { //cdo
            if ($data['expensetype'] == 'Gasoline') {
                $total = (($data['mealamt'] * $data['mealnum']) + ($data['lodgeexp'] * $data['lengthstay']) + $data['misc']);
            } else {
                $total = (($data['mealamt'] * $data['mealnum']) + ($data['lodgeexp'] * $data['lengthstay']) + $data['misc'] + $data['texpense']);
            }
            $data['ext'] = $total;
        }


        if ($isupdate) {
            if ($companyid == 58) { //cdo
                if ($data['expensetype'] == 'Gasoline') {
                    $data['texpense'] = 0;
                }
            }

            if (!empty($this->checking($config, $start, $end))) {
                $msg = "Cannot update, application already exist.";
                $clientid = 0;
            } else {
                $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
                $data['editby'] = $config['params']['user'];
                $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['clientid']]);
                $clientid = $head['clientid'];
                $this->logger->sbcmasterlog(
                    $clientid,
                    $config,
                    "UPDATE - NAME: $empname, DATE: " . $data['dateid'] . " TYPE: ON TRIP" . ", 
                        REMARKS: " . $data['remarks'] . ""
                );
            }
        } else {
            $data['empid'] =  $config['params']['adminid'];
            $data['status'] =  'E';
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();

            if (!empty($this->checking($config, $start, $end))) {
                $msg = "Cannot create, application already exist.";
                $clientid = 0;
            } else {
                $clientid = $this->coreFunctions->insertGetId($this->head, $data);
                $this->logger->sbcmasterlog(
                    $clientid,
                    $config,
                    "CREATE - NAME: $empname, DATE: " . $data['dateid'] . " TYPE: ON TRIP" . ", 
                        REMARKS: " . $data['remarks'] . ""
                );
            }
        }
        def:
        $status = true;
        if ($msg == '') {
            $msg = 'Successfully saved';
        } else {
            $status = false;
        }
        return ['status' => $status, 'msg' => $msg, 'clientid' => $clientid];
    } // end function

    public function checking($config, $start, $end)
    {
        $head = $config['params']['head'];
        $empid = $config['params']['adminid'];
        $companyid = $config['params']['companyid'];
        $line = $head['clientid'];

        $qry = "select trno from $this->head where empid = $empid and (date(startdate) between '" . $start . "' and '" . $end . "' or date(enddate) between '" . $start . "' and '" . $end . "') and status <> 'D'";

        $data =  $this->coreFunctions->opentable($qry);

        if (!empty($data)) {
            if ($data[0]->trno == $line) {
                return [];
            }
            return  $data;
        }

        return [];
    }

    public function getlastclient()
    {
        $last_id = $this->coreFunctions->datareader("select trno as value 
        from " . $this->head . " 
        order by trno DESC LIMIT 1");

        return $last_id;
    }

    public function openstock($trno, $config)
    {
        $qry = "";
        return $this->coreFunctions->opentable($qry);
    }

    public function deletetrans($config)
    {

        $clientid = $config['params']['clientid'];
        $companyid = $config['params']['companyid'];
        //and approvedate is not null
        $approved = $this->coreFunctions->opentable("select approvedate,disapprovedate,approvedbuddate from itinerary where trno=?", [$clientid]);

        if (!empty($approved)) {
            if ($approved[0]->approvedate != null) {
                return ['status' => false, 'msg' => 'Cannot delete; already approved.', 'clientid' => $clientid];
            }
            if ($approved[0]->disapprovedate != null) {
                return ['status' => false, 'msg' => 'Cannot delete; already disapproved.', 'clientid' => $clientid];
            }
            if ($approved[0]->approvedbuddate != null) {
                return ['status' => false, 'msg' => 'Travel budget has already been approved; it cannot be deleted.', 'clientid' => $clientid];
            }
        }
        $this->coreFunctions->execqry('delete from itinerary where trno=?', 'delete', [$clientid]);
        $this->coreFunctions->execqry('delete from pendingapp where trno=?', 'delete', [$clientid]);
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
        $approvers = ['issupervisor'];
        return $approvers;
    }
    public function stockstatusposted($config)
    {
        return $this->submit($config);
    }

    public function submit($config)
    {
        $trno = $config['params']['trno'];
        $companyid = $config['params']['companyid'];

        $submitdate = $this->coreFunctions->datareader("select submitdate as value from itinerary where trno=? and submitdate is not null", [$trno]);
        if (!empty($submitdate)) {
            return ['row' => [], 'status' => false, 'msg' => 'Already Submitted', 'backlisting' => false];
        } else {
            $update = $this->coreFunctions->sbcupdate($this->head, ['submitdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $trno]);
            if ($update) {
                $url = 'App\Http\Classes\modules\payroll\\' . 'itinerary';
                $empid = $this->coreFunctions->getfieldvalue($this->head, "empid", "trno=?", [$trno]);
                $data = ['empid' => $empid];

                if ($companyid == 58) { //cdohris
                    $latefilling = $this->coreFunctions->getfieldvalue($this->head, "islatefilling", "trno=?", [$trno]);
                    $level = $this->coreFunctions->datareader("select level as value from employee where empid=?", [$empid]);
                    if ($latefilling != 0) {
                        $approvers = "select apr.clientid,client.email from approvers as apr
                          left join moduleapproval as approval on approval.line = apr.trno
                          left join client on client.clientid=apr.clientid
                          where apr.isapprover = 1 and approval.modulename = 'TRAVEL'";
                        $appr = $this->coreFunctions->opentable($approvers);

                        if (!empty($appr)) {
                            foreach ($appr as $apps) {
                                if ($this->othersClass->checkApproverAccess($apps, $level)) {
                                    $appid = $apps->clientid;
                                    $approvernotes = 'LATE FILLING';
                                    $appstatus = $this->othersClass->insertUpdatePendingapp($trno, 0, 'TRAVEL', $data, $url, $config, $appid, true, false, $approvernotes);
                                }
                            }
                        }
                    } else {
                        // not late filling 
                        $appstatus = $this->othersClass->insertUpdatePendingapp($trno, 0, 'TRAVEL', $data, $url, $config, 0, true, true);
                    }
                } else {
                    // not cdo
                    $appstatus = $this->othersClass->insertUpdatePendingapp($trno, 0, 'TRAVEL', $data, $url, $config, 0, true, true);
                    if (!$appstatus['status']) {
                        $this->coreFunctions->sbcupdate($this->head, ['submitdate' => null], ['trno' => $trno]);
                        $msg = $appstatus['msg'];
                        $status = $appstatus['status'];
                        return ['status' => $status, 'msg' => $msg];
                    }
                }


                return ['status' => true, 'msg' => 'Success', 'backlisting' => true];
            }
        }
        return ['row' => [], 'status' => true, 'msg' => 'Success', 'backlisting' => true];
    }
} //end class
