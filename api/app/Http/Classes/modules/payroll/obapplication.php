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
use App\Http\Classes\sbcscript\sbcscript;
use DateTime;
use DateInterval;
use DatePeriod;

class obapplication
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'OB APPLICATION';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $linkemail;
  private $payrollcommon;
  private $logger;
  private $sbcscript;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $head = 'obapplication';
  public $prefix = '';
  public $tablelogs = 'payroll_log';
  public $tablelogs_del = '';
  private $stockselect;

  private $fields = [
    'empid',
    'dateid',
    'scheddate',
    'rem',
    'type',
    'location',
    'picture',
    'trackingtype',
    'islatefilling'
  ];
  // 'remarks','acno','days','bal',
  private $except = ['clientid', 'client'];
  private $blnfields = ['islatefilling'];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  private $reporter;
  private $isexist = 0;

  public $showfilterlabel = [
    ['val' => 'draft', 'label' => 'Entry', 'color' => 'primary'],
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
      'view' => 2540,
      'new' => 2538,
      'save' => 2536,
      'delete' => 2539,
      'print' => 2537,
      'edit' => 2541,
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $empid = $config['params']['adminid'];
    $companyid = $config['params']['companyid'];

    $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='OB'");
    $both = false;
    if ($approversetup == '') {
      $approversetup = $this->approvers($config['params']);
    } else {
      if (str_contains($approversetup, ' or ')) {
        $approversetup = explode(' or ', $approversetup);
        $both = true;
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
    }


    $companylist  = [53, 44, 58]; //stone/ cdohris
    switch ($companyid) {
      case 53: //camera
        $this->modulename = 'OB/OFFSET APPLICATION';
        break;
      case 58: //cdo
        if ($empid == 0) {
          $this->showcreatebtn = false;
        }
        $this->modulename = 'TRACKING APPLICATION';
        break;
    }

    $approver = $this->payrollcommon->checkapprover($config);
    $supervisor =  $this->payrollcommon->checksupervisor($config);

    switch ($companyid) {
      case 53: // camera
        $this->showfilterlabel = [
          ['val' => 'draft', 'label' => 'Entry', 'color' => 'primary'],
          ['val' => 'forapproval', 'label' => 'For Approval', 'color' => 'primary'],
          ['val' => 'initial', 'label' => 'Initial Approved', 'color' => 'primary'],
          ['val' => 'initiald', 'label' => 'Initial Disapproved', 'color' => 'primary'],
          ['val' => 'approved', 'label' => 'Approved', 'color' => 'primary'],
          ['val' => 'disapproved', 'label' => 'Disapproved', 'color' => 'primary']
        ];
        break;
      default:
        $this->showfilterlabel = [
          ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
          ['val' => 'forapproval', 'label' => 'For Approval', 'color' => 'primary'],
          ['val' => 'approved', 'label' => 'Approved', 'color' => 'primary'],
          ['val' => 'disapproved', 'label' => 'Disapproved', 'color' => 'primary']
        ];
        break;
    }
    if ($approver || $supervisor) {
      array_push($this->showfilterlabel, ['val' => 'approvedemp', 'label' => 'Approved Employees', 'color' => 'primary']);
    }


    $getcols = ['action', 'createdate', 'clientname', 'scheddate', 'dateid', 'dateid2', 'type', 'trackingtype', 'rem', 'listinitialstatus2', 'initial_date_approved2', 'approvedby_initial2', 'initialremarks2', 'listinitialstatus', 'initial_date_approved', 'approvedby_initial', 'initialremarks', 'listappstatus2', 'date_approved_disapprovedsup', 'approvedby_disapprovedbysup', 'rem2', 'listappstatus', 'date_approved_disapproved', 'approvedby_disapprovedby', 'remarks'];

    // switch ($companyid) {
    //   case 51: //ulitc
    //     $getcols = ['action', 'createdate', 'clientname', 'scheddate', 'type', 'rem', 'listappstatus2', 'date_approved_disapprovedsup', 'approvedby_disapprovedbysup', 'rem1', 'listappstatus', 'date_approved_disapproved', 'approvedby_disapprovedby', 'remarks'];
    //     break;
    //   case 53: //camera
    //     $getcols = ['action', 'createdate', 'clientname', 'scheddate', 'type', 'loc2', 'rem', 'listinitialstatus', 'initial_date_approved', 'approvedby_initial', 'initialremarks', 'listappstatus2', 'date_approved_disapprovedsup', 'approvedby_disapprovedbysup', 'rem2', 'listappstatus', 'date_approved_disapproved', 'approvedby_disapprovedby', 'remarks'];
    //     break;
    // }

    if ($companyid == 53) { //camera
      array_push($getcols, 'void_date', 'void_approver', 'void_remarks');
    }


    foreach ($getcols as $key => $value) {
      $$value = $key;
    }

    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';

    $cols[$type]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;text-align:left;';
    $cols[$listappstatus2]['style'] = 'width:100px;whiteSpace: normal;min-width:120px; text-align:left;';
    $cols[$listappstatus]['style'] = 'width:100px;whiteSpace: normal;min-width:120px;text-align:left;';

    $cols[$rem]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;';

    $cols[$type]['label'] = 'Type';
    $cols[$listappstatus]['type'] = 'label';
    $cols[$listappstatus]['label'] = 'Status (HR)';


    if (in_array($companyid, $companylist)) {
      if ($companyid == 53) { //camera
        $cols[$listappstatus2]['label'] = 'Initial Status';
      }
    } else {
      $cols[$listappstatus2]['label'] = 'First Approver Status';
    }

    if (in_array('loc2', $getcols)) {
      // $cols[$loc2]['label'] = "Location";
      $cols[$rem]['label'] = "Purpose/s";
    }
    $cols[$createdate]['label'] = 'Date Applied';
    $cols[$createdate]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$clientname]['label'] = 'Name';
    $cols[$clientname]['style'] = 'width:200px;whiteSpace:normal;min-width:200px;';
    $cols[$rem]['label'] = 'Remarks';

    $cols[$rem2]['label'] = 'Remarks';
    $cols[$rem2]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';

    $cols[$remarks]['label'] = 'Remarks';
    $cols[$remarks]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $cols[$dateid]['style'] = 'width:170px;whiteSpace: normal;min-width:170px;';

    $cols[$dateid]['type'] = 'coldel';

    switch ($companyid) {
      case 53: //camera
        $cols[$date_approved_disapprovedsup]['label'] = 'Date Approved/Disapproved (Head Dept. Approver)';
        $cols[$approvedby_disapprovedbysup]['label'] = 'Approved/Disapproved By (Head Dept. Approver)';
        $cols[$listappstatus]['label'] = 'Hr/Payroll Approver Status';
        $cols[$date_approved_disapproved]['label'] = 'Date Approved/Disapproved (Hr/Payroll Approver)';
        $cols[$approvedby_disapprovedby]['label'] = 'Approved/Disapproved By Hr/Payroll Approver';

        $cols[$scheddate]['label'] = 'Schedule Date';
        $cols[$scheddate]['style'] = 'width:120px;whiteSpace:normal;min-width:130px;';
        $cols[$rem2]['label'] = 'Head Dept. Approver Reason';
        $cols[$remarks]['label'] = 'Hr/Payroll Approver Reason';
        $cols[$rem2]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
        $cols[$remarks]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';

        $cols[$remarks]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
        $cols[$listappstatus2]['label'] = 'Head Dept. Approver Status';


        $cols[$listinitialstatus2]['label'] = 'Initial Status (Hr/Payroll Approver)';
        $cols[$initial_date_approved2]['label'] = 'Initial Date Approved/Disapproved (Hr/Payroll Approver)';
        $cols[$approvedby_initial2]['label'] = 'Initial Approved/Disapproved (Hr/Payroll Approver)';
        $cols[$initialremarks2]['label'] = 'Initial (Hr/Payroll Approver) Reason';

        $cols[$initial_date_approved]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
        $cols[$approvedby_initial]['label'] = 'Initial Approved/Disapproved By (Hr/Payroll Approver)';
        $cols[$listinitialstatus]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;text-align:right;';
        $cols[$initialremarks]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';


        $cols[$listinitialstatus2]['label'] = 'Initial Status (Head Dept. Approver)';
        $cols[$initial_date_approved2]['label'] = 'Initial Date Approved/Disapproved (Head Dept. Approver)';
        $cols[$approvedby_initial2]['label'] = 'Initial Approved/Disapproved (Head Dept. Approver)';
        $cols[$initialremarks2]['label'] = 'Initial (Head Dept. Approver) Reason';

        $cols[$initial_date_approved2]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
        $cols[$approvedby_initial2]['label'] = 'Initial Approved/Disapproved By (Hr/Payroll Approver)';
        $cols[$listinitialstatus2]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;text-align:right;';
        $cols[$initialremarks2]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';


        $cols[$void_remarks]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
        $cols[$void_remarks]['label'] = 'Reason';
        $cols[$void_date]['type'] = 'label';
        $cols[$void_approver]['type'] = 'label';

        break;
      case 51: //ulitc
        $cols[$date_approved_disapprovedsup]['label'] = 'Date Approved/Disapproved';
        $cols[$approvedby_disapprovedbysup]['label'] = 'Approved/Disapproved';
        $cols[$listappstatus]['label'] = ' Last Approver Status';
        $cols[$date_approved_disapproved]['label'] = 'Date Approved/Disapproved';
        $cols[$approvedby_disapprovedby]['label'] = 'Approved/Disapproved';

        $cols[$listappstatus2]['label'] = ' First Approver Status';
        $cols[$listappstatus2]['style'] = 'width:100px;white Space:normal;min-width:100px;';
        $cols[$listappstatus2]['align'] = 'text-align:left;';


        $cols[$scheddate]['label'] = 'Schedule Date';
        $cols[$scheddate]['style'] = 'width:120px;white Space:normal;min-width:130px;';
        $cols[$listinitialstatus]['type'] = 'coldel';
        $cols[$initial_date_approved]['type'] = 'coldel';
        $cols[$approvedby_initial]['type'] = 'coldel';
        $cols[$initialremarks]['type'] = 'coldel';
        $cols[$rem2]['type'] = 'coldel';

        $cols[$listinitialstatus2]['type'] = 'coldel';
        $cols[$initial_date_approved2]['type'] = 'coldel';
        $cols[$approvedby_initial2]['type'] = 'coldel';
        $cols[$initialremarks2]['type'] = 'coldel';
        break;
      default:
        $cols[$dateid]['type'] = 'label';

        $cols[$scheddate]['type'] = 'coldel';
        $cols[$listinitialstatus]['type'] = 'coldel';
        $cols[$initial_date_approved]['type'] = 'coldel';
        $cols[$approvedby_initial]['type'] = 'coldel';
        $cols[$initialremarks]['type'] = 'coldel';


        $cols[$listinitialstatus2]['type'] = 'coldel';
        $cols[$initial_date_approved2]['type'] = 'coldel';
        $cols[$approvedby_initial2]['type'] = 'coldel';
        $cols[$initialremarks2]['type'] = 'coldel';

        break;
    }


    if ($companyid != 58) {
      $cols[$trackingtype]['type'] = 'coldel';
      $cols[$dateid2]['type'] = 'coldel';
    } else {
      $cols[$type]['type'] = 'coldel';

      $cols[$dateid]['label'] = 'Time-In';
      $cols[$dateid2]['label'] = 'Time-Out';
    }

    if (count($approversetup) == 1) {
      $cols[$listappstatus2]['type'] = 'coldel';
      $cols[$date_approved_disapprovedsup]['type'] = 'coldel';
      $cols[$approvedby_disapprovedbysup]['type'] = 'coldel';
      $cols[$rem2]['type'] = 'coldel';
    }

    $cols = $this->tabClass->delcollisting($cols);
    return $cols;
  }

  public function loaddoclisting($config)
  {
    $id = $config['params']['adminid'];
    $user = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $filteroption = '';
    $option = $config['params']['itemfilter'];

    $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='OB'");
    $both = false;
    if ($approversetup == '') {
      $approversetup = $this->approvers($config['params']);
    } else {
      if (str_contains($approversetup, ' or ')) {
        $approversetup = explode(' or ', $approversetup);
        $both = true;
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
    }
    $filter = "'ENTRY'";
    if (count($approversetup) == 1) {
      $filter = "''";
    }

    $sortby = 'obapp.approvedate';
    $leftjoin = "";
    $addraft = ""; //draft
    $addcase = " case when obapp.status2 = 'E' then 'ENTRY'
                 when obapp.status2 = 'A' then 'APPROVED'
                 when obapp.status2 = 'D' then 'DISAPPROVED' END as status2,
                  case 
                  when obapp.status = 'E' then 'ENTRY'
                  when obapp.status = 'A' then 'APPROVED'
                  when obapp.status = 'D' then 'DISAPPROVED'
                  END";

    switch ($companyid) {
      case 53: // camera 
        $addraft = " and obapp.initialapp is null";
        $leftjoin = "
        left join client as obap on obap.email = obapp.initialapprovedby and obap.email <> ''
        left join client as obap2 on obap2.email = obapp.initialapprovedby2 and obap2.email <> ''
        left join client as void on void.email = obapp.void_by and void.email <> ''";

        $addcase = " 
                   case when obapp.status2 = 'E' then 'ENTRY'
                   when obapp.status2 = 'A' then 'APPROVED'
                   when obapp.status2 = 'D' then 'DISAPPROVED' 
                   END as status2,
                    case
                    when  obapp.initialstatus2 = 'A' and obapp.initialstatus = 'A' then 'INITIAL APPROVED'
                    when  obapp.initialstatus = 'D' then 'INITIAL DISAPPROVED'
                    when  obapp.initialstatus = '' and obapp.initialstatus2 = 'A' then 'FOR APPROVAL'
                    when  obapp.initialstatus = '' and obapp.initialstatus2 = '' then 'ENTRY'

                    END as inistatus,
                    case
                    when  obapp.initialstatus2 = 'A' then 'INITIAL APPROVED'
                    when  obapp.initialstatus2 = 'D' and obapp.initialstatus = '' then 'INITIAL DISAPPROVED'           
                    when  obapp.initialstatus2 = '' and obapp.initialstatus = '' and obapp.initialapp is not null then 'FOR APPROVAL'  
                    when  obapp.initialstatus2 = '' and obapp.initialstatus = '' then 'ENTRY'
                    END as inistatus2,

                    date_format(obapp.initialappdate, '%m-%d-%y') as initial_date_approved,date_format(obapp.initialappdate2, '%m-%d-%y') as initial_date_approved2,obap.clientname as approvedby_initial,obap2.clientname as approvedby_initial2,
                    date_format(obapp.approvedate2, '%m-%d-%y') as date_approved_disapprovedsup,
                    date_format(ifnull(obapp.approvedate,obapp.disapprovedate), '%m-%d-%y') as date_approved_disapproved, ifnull(obapp.approvedby,obapp.disapprovedby) as approvedby_disapprovedby,obapp.initial_remarks as initialremarks,obapp.initial_remarks2 as initialremarks2,
                    ifnull(app2dis.clientname,app2.clientname) as approvedby_disapprovedbysup,void.clientname as void_approver,obapp.void_date,obapp.void_remarks,
                    case 
                    when obapp.status = 'E' then 'ENTRY'
                    when obapp.status = 'A' then 'APPROVED'
                    when obapp.status = 'D' then 'DISAPPROVED'
                    END";
        break;

        break;
      default:
        $addraft = " and obapp.submitdate is null";
        if (count($approversetup) == 1 || $both) {
          $addcase = "
          case when obapp.status2 = 'E' then 'ENTRY'
               when obapp.status2 = 'A' then 'APPROVED'
               when obapp.status2 = 'D' then 'DISAPPROVED' end as status2,
          case when obapp.status = 'E' and obapp.submitdate is null then 'ENTRY'
               when obapp.status = 'E' and obapp.submitdate is not null then 'FOR APPROVAL'
               when obapp.status = 'A' then 'APPROVED'
               when obapp.status = 'D' then 'DISAPPROVED' end";
        } else {
          $addcase = " 
                    case
                    when obapp.status2 = 'E' and obapp.submitdate is null then $filter
                    when obapp.status2 = 'E' and obapp.submitdate is not null then 'FOR APPROVAL'
                    when obapp.status2 = 'A' then 'APPROVED'
                    when obapp.status2 = 'D' then 'DISAPPROVED' end as status2,
                    
                    date_format(ifnull(obapp.approvedate2,obapp.disapprovedate2), '%m-%d-%y') as date_approved_disapprovedsup,ifnull(app2.clientname,app2dis.clientname) as approvedby_disapprovedbysup,
                    date_format(ifnull(obapp.approvedate,obapp.disapprovedate), '%m-%d-%y') as date_approved_disapproved, obapp.approvedby as approvedby_disapprovedby,
                    case 
                    when obapp.status = 'E' then 'ENTRY'
                    when obapp.status = 'A' then 'APPROVED'
                    when obapp.status = 'D' then 'DISAPPROVED'
                    END";
        }
        break;
    }
    $sortby = 'obapp.scheddate desc';
    switch ($option) {
      case 'draft':
        if ($companyid == 44) { //camera
          $addraft = " and obapp.status2 = 'E' and obapp.submitdate is null";
        }
        $filteroption = " where obapp.empid=" . $id . " and obapp.status='E' " . $addraft . " ";
        break;
      case 'forapproval':
        if ($companyid == 53) {
          $filteroption = " where obapp.empid=" . $id . " and obapp.status='E' and ((initialstatus2 = '' or initialstatus2 = 'A') and initialstatus  = '') and obapp.initialapp is not null";
        } else {
          $filteroption = " where obapp.empid=" . $id . " and obapp.status='E' and obapp.submitdate is not null";
        }
        break;
      case 'initial':
        // $addcase = "if(submitdate is null,'INITIAL APPROVED','SUBMITTED')";
        $filteroption = " where obapp.empid=" . $id . " and obapp.status='E' and (obapp.initialstatus2 = 'A' and obapp.initialstatus = 'A')  and obapp.initialapp is not null";
        break;
      case 'initiald':

        $filteroption = " where obapp.empid=" . $id . " and obapp.status='E' and (obapp.initialstatus = 'D' or obapp.initialstatus2 = 'D') and obapp.initialapp is not null";
        // $addcase = "if(submitdate is not null and initialstatus = 'D','INITIAL DISAPPROVED','SUBMITTED')";
        break;
      case 'approved':

        $filteroption = " where obapp.empid=" . $id . " and obapp.status='A'";
        break;
      case 'disapproved':
        $filteroption = " where obapp.empid=" . $id . " and obapp.status='D'";
        break;
      default:
        $isapprover = $this->coreFunctions->getfieldvalue("employee", "isapprover", "empid=?", [$id]);
        if ($isapprover == 1) {
          $filteroption = " where obapp.status='A' and approvedby='" . $user . "' ";
        } else {
          return ['data' => [], 'status' => false, 'msg' => 'This feature is for approvers only.'];
        }
        break;
    }
    $filteroption .= " and isitinerary = 0 ";
    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['cl.clientid', 'cl.client', 'cl.clientname', 'obapp.type', 'obapp.rem', 'obapp.dateid'];

      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
      $limit = "";
    }

    $qry = "
      select obapp.line as trno, obapp.line as clientid, 
      cl.client, cl.clientname, cl.clientid as empid,
      obapp.type,
        concat(
          IF(obapp.dateid is not null, concat('In - ', obapp.dateid), ''),
          IF(obapp.dateid is not null AND obapp.dateid2 is not null, ' ', ''),
          IF(obapp.dateid2 is not null, concat('Out - ', obapp.dateid2), '')
        ) as dateid,
      obapp.dateid2, obapp.rem, obapp.location as loc2, 
      " . $addcase . " as jstatus,date_format(obapp.createdate, '%m-%d-%y') as createdate,date_format(obapp.scheddate, '%m-%d-%y') as scheddate, 
      obapp.disapproved_remarks2 as rem2,obapp.approverem as remarks,ifnull(appdis.clientname,app.clientname) as approvedby_disapprovedby, obapp.trackingtype

     
      from obapplication as obapp
      left join employee as emp on emp.empid = obapp.empid
      left join client as cl on cl.clientid = emp.empid
      left join client as app on app.email = obapp.approvedby and app.email <> ''
      left join client as appdis on appdis.email = obapp.disapprovedby and appdis.email <> ''
      left join client as app2 on app2.email = obapp.approvedby2 and app2.email <> ''
      left join client as app2dis on app2dis.email = obapp.disapprovedby2 and app2dis.email <> ''
      $leftjoin
      
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
    $companyid = $config['params']['companyid'];
    $fields = [];
    if ($companyid == 53) { // camera
      array_push($fields, 'picture');
    }
    $col1 = $this->fieldClass->create($fields);
    $tab = [];
    if ($companyid == 53) { // camera
      data_set($col1, 'picture.table', 'obapplication');
      data_set($col1, 'picture.fieldid', 'line');
      data_set($col1, 'picture.folder', 'obapplication');
      data_set($col1, 'picture.style', 'height: 500px; max-width: 500px;');
      $tab = ['multiinput1' => ['inputcolumn' => ['col1' => $col1], 'label' => 'IMAGE']];
    }
    if ($companyid == 51) {
      $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrytripob', 'label' => 'TRIP']];
    }


    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
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

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];

    switch ($companyid) {
      case 29: //sbc portal
        $fields = ['client', 'dateid', 'itime', 'type'];
        break;
      case 58: //cdo
        $fields = ['client', 'trackingtype', 'dateid', 'itime', 'itime1']; //'scheddate',
        break;
      case 51: //ulitc
      case 53: //camera
        $fields = ['client', 'scheddate', 'dateid', 'type', 'itime', 'itime1',]; //'itime1',
        break;
      default:
        $fields = ['client', 'scheddate', 'dateid', 'type', 'itime'];
        break;
    }

    if ($companyid == 58) { //cdo
      array_push($fields, ['status2', 'status']);
    } else {
      array_push($fields, 'status');
    }

    $col1 = $this->fieldClass->create($fields);

    if ($companyid == 58) { //cdo
      data_set($col1, 'status.label', 'Status (HR)');
      data_set($col1, 'trackingtype.required', true);
    } else {
      data_set($col1, 'type.action', 'lookupobtype');
      data_set($col1, 'type.lookupclass', 'lookupobtype');
      data_set($col1, 'type.error', false);
    }
    data_set($col1, 'client.required', false);
    data_set($col1, 'refresh.type', 'actionbtn');
    data_set($col1, 'refresh.lookupclass', 'stockstatusposted');
    data_set($col1, 'refresh.action', 'tagsomething');
    data_set($col1, 'refresh.access', 'save');
    data_set($col1, 'client.label', 'Code');
    data_set($col1, 'client.action', 'lookupledger');
    data_set($col1, 'client.lookupclass', 'lookupemployeeobapplication');
    data_set($col1, 'client.type', 'hidden');
    // data_set($col1, 'dateid.label', 'Start Date');
    data_set($col1, 'itime.label', 'Time');
    data_set($col1, 'end.label', 'Schedule End Date');



    $fields = ['createdate', 'rem'];

    if ($companyid == 53) { // camera
      array_push($fields, 'location');
    }
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'rem.label', 'Remarks');
    if ($companyid == 53) { // camera
      data_set($col2, 'rem.label', 'Purpose/s');
      data_set($col2, 'location.label', 'Location/Destination');
    }
    data_set($col2, 'rem.required', true);
    $fields = [];
    switch ($companyid) {
      case 53: // camera
        array_push($fields, 'forapproval', 'lblsubmit', 'submit');
        break;
      case 58: //cdohris
        array_push($fields, 'islatefilling', 'lblsubmit', 'submit');
        break;
      default:
        array_push($fields, 'lblsubmit', 'submit');
        break;
    }


    $col3 = $this->fieldClass->create($fields);

    if ($companyid == 53) {
      data_set($col3, 'lblsubmit.label', 'SUBMITTED');
      // data_set($col3, 'forapproval.style', 'font-weight:bold;font-size:15px;font-family:Century Gothic;color: green;');
    } else {
      data_set($col3, 'lblsubmit.label', 'FOR APPROVAL');
      data_set($col3, 'submit.label', 'FOR APPROVAL');
    }
    data_set($col3, 'lblsubmit.style', 'font-weight:bold;font-size:15px;font-family:Century Gothic;color: green;');;


    $fields = [];

    if ($companyid == 58) { //cdo
      $fields = ['lblgrossprofit'];
    }

    $col4 = $this->fieldClass->create($fields);

    if ($companyid == 58) { //cdo
      data_set($col4, 'lblgrossprofit.style', 'font-weight:bold;font-size:20px;color:red');
      data_set($col4, 'lblgrossprofit.label', 'Reminder: Final filing & approval of activities is every 
      2nd and 17th of the month. Ensure the form is completely filed out to avoid invalid activities.');
    }
    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function newclient($config)
  {
    $data = $this->resetdata($config['newclient'], $config['params']);
    $companyid = $config['params']['companyid'];
    $hideobj = [];
    switch ($companyid) {
      case 53: //camera
        $hideobj['submit'] = true;
        $hideobj['lblsubmit'] = true;
        $hideobj['forapproval'] = true;
        break;
      default:
        $hideobj['submit'] = true;
        $hideobj['lblsubmit'] = true;
        break;
    }

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
    $data[0]['scheddate'] = $this->othersClass->getCurrentDate();
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['createdate'] = $this->othersClass->getCurrentDate();
    $data[0]['itime'] = '00:00';
    $data[0]['itime1'] = '00:00';
    $data[0]['rem'] = '';
    $data[0]['type'] = '';
    $data[0]['status'] = 'ENTRY';
    $data[0]['location'] = '';
    $data[0]['picture'] = '';
    $data[0]['divid'] = 0;
    $data[0]['ontrip'] = '';
    $data[0]['submitdate'] = null;
    if ($config['companyid'] == 53) { //camera
      $data[0]['divid'] = $this->coreFunctions->datareader("select divid as value from employee where empid=?", [$config['adminid']]);
    }
    if ($config['companyid'] == 58) { //cdo
      $data[0]['status2'] = 'ENTRY';
    }
    $data[0]['end'] = '';
    $data[0]['trackingtype'] = '';
    $data[0]['islatefilling'] = '0';
    return $data;
  }

  function getheadqry($config, $trno)
  {
    $companyid = $config['params']['companyid'];
    $adminid = $config['params']['adminid'];
    $addcase = "case 
          when obapp.status = 'E' then 'ENTRY'
          when obapp.status = 'A' then 'APPROVED'
          END ";
    switch ($companyid) {
      case 53: //camera
        $addcase = " case 
                      when obapp.status = 'E' and obapp.initialapp is null then 'ENTRY'
                      when obapp.status = 'E' and obapp.initialstatus = '' and obapp.initialapp is not null then 'FOR APPROVAL'
                      when obapp.status = 'E' and obapp.initialstatus = 'A' then 'INITIAL APPROVED'
                      when obapp.status = 'E' and obapp.initialstatus = 'D' then 'INITIAL DISAPPROVED'
                      when obapp.status = 'A' then 'APPROVED'
                      when obapp.status = 'D' then 'DISAPPROVED'
                      END ";
        break;

      default:
        $addcase = " case 
                      when obapp.status = 'E' and obapp.submitdate is null then 'ENTRY'
                      when obapp.status = 'E' and (obapp.status2 = 'E' or obapp.status2 = 'A') and obapp.submitdate is not null then 'FOR APPROVAL'
                      when obapp.status = 'A' then 'APPROVED'
                      when obapp.status = 'D' then 'DISAPPROVED'
                      END ";
        break;
    }

    return "
        select obapp.line as trno, obapp.line as clientid, cl.client, obapp.ontrip,
        cl.clientname, cl.clientid as empid,
        obapp.type, ifnull(date(obapp.dateid),date(obapp.dateid2)) as dateid,date_format(ifnull(obapp.dateid,obapp.dateid2), '%Y-%m-%d %H:%i %s') as datetime,date_format(ifnull(obapp.dateid2,obapp.dateid), '%Y-%m-%d %H:%i %s') as datetime2, time(ifnull(dateid,dateid2)) as itime, time(ifnull(dateid2,dateid)) as itime1,
        obapp.rem, date(obapp.scheddate) as scheddate,dayname(obapp.scheddate) as dayname, obapp.createdate,obapp.submitdate,obapp.location,emp.divid,obapp.initialapp,obapp.initialstatus,obapp.initial_remarks,obapp.initial_remarks2,
        " . $addcase . "
         as status, obapp.picture, 
         case when obapp.status2 = 'E' then 'ENTRY'
              when obapp.status2 = 'A' then 'APPROVED' END as status2,date(obapp.scheddate2) as end,obapp.approverem,obapp.disapproved_remarks2,

        obapp.approvedate,obapp.disapprovedate,obapp.approvedate2,obapp.disapprovedate2,obapp.trackingtype,obapp.islatefilling
        from obapplication as obapp
        left join employee as emp on emp.empid = obapp.empid
        left join client as cl on cl.clientid = emp.empid
        where obapp.line= '$trno' and obapp.empid = '$adminid'";
  }


  public function loadheaddata($config)
  {
    $trno = $config['params']['clientid'];
    $companyid = $config['params']['companyid'];

    if ($trno == 0) {
      $trno = $this->coreFunctions->opentable("select line as trno from obapplication where empid=" . $config['params']['adminid'] . " order by trno desc limit 1");
      if (empty($trno)) {
        $trno = 0;
      } else {
        $trno = $trno[0]->trno;
      }
    }

    $head = $this->coreFunctions->opentable($this->getheadqry($config, $trno));
    if (!empty($head)) {
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }
      $hideobj = [];

      switch ($companyid) {
        case 53:
          $initialapp = $head[0]->initialapp != null ? true : false;

          if ($initialapp) {
            $hideobj['forapproval'] = true;
            $hideobj['submit'] = true;
            $hideobj['lblsubmit'] = true;
            if ($head[0]->initialstatus == 'A') {
              $submit = $head[0]->submitdate != null ? true : false;
              if ($submit) {
                $hideobj['submit'] = true;
                $hideobj['lblsubmit'] = false;
              } else {
                $hideobj['submit'] = false;
                $hideobj['lblsubmit'] = true;
              }
            }
          } else {
            $hideobj['forapproval'] = false;
            $hideobj['submit'] = true;
            $hideobj['lblsubmit'] = true;
          }
          break;
        default:
          $hideobj['submit'] = true;
          $hideobj['lblsubmit'] = true;
          $submitdate = $head[0]->submitdate != null ? true : false;
          $approvedate = $head[0]->approvedate != null ? true : false;
          $disapprovedate = $head[0]->disapprovedate != null ? true : false;
          $approvedate2 = $head[0]->approvedate2 != null ? true : false;
          $disapprovedate2 = $head[0]->disapprovedate2 != null ? true : false;


          if ($submitdate || $approvedate || $disapprovedate || $approvedate2 || $disapprovedate2) {
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


          if ($companyid == 58) { //cdohris
            foreach ($this->blnfields as $key => $value) {
              if ($head[0]->$value) {
                $head[0]->$value = "1";
              } else
                $head[0]->$value = "0";
            }
          }



          break;
      }

      // $tripob = $this->coreFunctions->opentable("select detail.trno,detail.line,detail.purpose,detail.destination,
      //   date_format(detail.leadfrom,'%H:%i') as leadfrom,date_format(detail.leadto,'%H:%i') as leadto,contact,
      //   '' as bgcolor  from obdetail as detail 
      //   where detail.trno=".$head[0]->trno." and detail.trno <> 0 order by line desc");

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
    $companyidlist = [58]; // sbc portal,cdohris

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
    }


    if (isset($head['trno'])) {
      $initialapp = $this->coreFunctions->datareader("select initialapp as value from obapplication where line=? ", [$head['trno']], '', true);
      $submitdate = $this->coreFunctions->datareader("select submitdate as value from $this->head where line=? and submitdate is not null", [$head['trno']], '', true);

      if ($companyid == 53) { //camera
        if ($submitdate || $initialapp) {
          $url = 'App\Http\Classes\modules\payroll\\' . 'obapplication';
          $result = $this->payrollcommon->checkapplicationstatus($config, $head['trno'], $url, $submitdate);

          if (!$result['status']) {
            return ['status' => false, 'msg' => $result['msg'], 'clientid' => $config['params']['adminid']];
          }
        }
      } else {

        $approved = $this->coreFunctions->datareader("select approvedate as value from obapplication where line=? and approvedate is not null", [$head['trno']]);
        $approved2 = $this->coreFunctions->datareader("select approvedate2 as value from obapplication where line=? and approvedate2 is not null", [$head['trno']]);

        if ($approved || $approved2) {
          return ['status' => false, 'msg' => 'Cannot update; already Approved' . '.', 'clientid' => $config['params']['adminid']];
        }
        $disapproved = $this->coreFunctions->datareader("select disapprovedate as value from obapplication where line=? and disapprovedate is not null", [$head['trno']]);
        $disapproved = $this->coreFunctions->datareader("select disapprovedate2 as value from obapplication where line=? and disapprovedate2 is not null", [$head['trno']]);

        if ($disapproved || $disapproved) {
          return ['status' => false, 'msg' => 'Cannot update; already Disapproved' . '.', 'clientid' => $config['params']['adminid']];
        }

        if ($submitdate) {
          return ['status' => false, 'msg' => 'Cannot update; already For approval.', 'clientid' => $config['params']['adminid']];
        }
      }
    }


    $clientid = 0;
    $msg = '';

    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        } //end if 
      }
    }
    if ($head['dateid'] == 'Invalid date' || $head['dateid'] == null) {
      return ['status' => false, 'msg' => 'Date must not be empty or invalid', 'clientid' => $config['params']['adminid']];
    }
    if (!in_array($companyid, $companyidlist)) {
      if ($head['scheddate'] == 'Invalid date' || $head['scheddate'] == null) {
        return ['status' => false, 'msg' => 'Schedule date must not be empty or invalid', 'clientid' => $config['params']['adminid']];
      }
    }
    if ($companyid == 51) { //ulitc
      if ($head['submitdate'] != null) {
        return ['status' => false, 'msg' => 'Cannot update already submitted.'];
      }
    }
    if ($companyid == 53) { // camera
      $checkcutoffdate = $this->payrollcommon->checkbatchsched($data['scheddate'], $head['divid']);
      if (!empty($checkcutoffdate['msg'])) {
        $msg = $checkcutoffdate['msg'];
        return ['status' => false, 'msg' => $msg];
      }
    }

    if ($head['ontrip'] != "") {
      $data['ontrip'] = $head['ontrip'];
    }

    //make sure to setup value for scheddate field

    switch ($companyid) {
      case 58: //cdo
        switch ($head['trackingtype']) {
          case "DIRECT FIELD IN ONLY":
          case "KEY CUSTODIANS LATE":
          case "LATE TIME IN":
            $data['dateid'] = $this->othersClass->sanitizekeyfield('dateid', $head['dateid'] . " " . $head['itime']);
            $data['dateid2'] = null;
            $data['scheddate'] =  date('Y-m-d', strtotime($data['dateid']));
            break;
          case "DIRECT FIELD OUT ONLY":
          case "EARLY TIME OUT":
            $data['dateid'] = null;
            $data['dateid2'] = $this->othersClass->sanitizekeyfield('dateid', $head['dateid'] . " " . $head['itime1']);
            $data['scheddate'] =  date('Y-m-d', strtotime($data['dateid2']));
            break;
          case "BLACK OUT (1 ATTLOG)":
          case "BLACK OUT WHOLEDAY":
          case "RELIEVER FOR CASHIER (WHOLE DAY)":
          case "DAMAGE BIOMETRIC":
          case "PRORATE":
            $data['dateid'] = $this->othersClass->sanitizekeyfield('dateid', $head['dateid'] . " " . $head['itime']);
            $data['dateid2'] = $this->othersClass->sanitizekeyfield('dateid', $head['dateid'] . " " . $head['itime1']);
            $data['scheddate'] =  date('Y-m-d', strtotime($data['dateid']));
            break;
        }
        break;
      case 51:
        $data['dateid'] = $this->othersClass->sanitizekeyfield('dateid', $head['dateid'] . " " . $head['itime']);
        $data['dateid2'] = $this->othersClass->sanitizekeyfield('dateid', $head['dateid'] . " " . $head['itime1']);
        $data['scheddate'] =  date('Y-m-d', strtotime($data['dateid']));
        break;
      case 53:
        switch ($head['type']) {
          case "Off-setting":
            $data['dateid'] = $this->othersClass->sanitizekeyfield('dateid', $head['dateid'] . " " . $head['itime']);
            $data['dateid2'] = $this->othersClass->sanitizekeyfield('dateid', $head['dateid'] . " " . $head['itime1']);
            $data['scheddate'] =  date('Y-m-d', strtotime($data['dateid']));
            break;
          case "Time-In":
          case "Time-In at the Place Visited":
            $data['dateid'] = $this->othersClass->sanitizekeyfield('dateid', $head['dateid'] . " " . $head['itime']);
            $data['dateid2'] = null;
            $data['scheddate'] =  date('Y-m-d', strtotime($data['dateid']));
            break;
          case "Time-Out":
          case "Time-Out at the Place Visited":
            $data['dateid'] = null;
            $data['dateid2'] = $this->othersClass->sanitizekeyfield('dateid', $head['dateid'] . " " . $head['itime1']);
            $data['scheddate'] =  date('Y-m-d', strtotime($data['dateid2']));
            break;
          default:
            $data['dateid'] = $this->othersClass->sanitizekeyfield('dateid', $head['dateid'] . " " . $head['itime']);
            $data['scheddate'] =  date('Y-m-d', strtotime($data['dateid']));
            break;
        }
        break;
      default:
        $data['dateid'] = $this->othersClass->sanitizekeyfield('dateid', $head['dateid'] . " " . $head['itime']);
        $data['scheddate'] =  date('Y-m-d', strtotime($data['dateid']));
        break;
    }
    $empname = $this->coreFunctions->datareader("select cl.clientname as value 
      from employee as e
      left join client as cl on cl.clientid = e.empid
      where e.empid = ?", [$config['params']['adminid']]);

    $date = date('Y-m-d', strtotime($data['dateid']));
    if ($data['dateid'] == null) {
      $date = date('Y-m-d', strtotime($data['dateid2']));
    }
    if ($head['end'] == '') {
      $end = date('Y-m-d', strtotime($head['dateid']));
    } else {
      $end = date('Y-m-d', strtotime($head['end']));
    }
    if ($isupdate) {
      $type = $head['type'];
      if (!empty($this->checking($config, $date))) { //checking of scheddate
        $msg = "Cannot update, application already exist.";
        $clientid = 0;
      } else {
        // $this->othersClass->logConsole(json_encode($data));
        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];
        $this->coreFunctions->sbcupdate($this->head, $data, ['line' => $head['clientid']]);
        $clientid = $head['clientid'];
        $this->logger->sbcmasterlog(
          $clientid,
          $config,
          "UPDATE - NAME: $empname, DATE: " . $data['dateid'] . " TYPE: " . $data['type'] . ", 
          REMARKS: " . $data['rem'] . ""
        );
      }
    } else {
      $data['empid'] =  $config['params']['adminid'];
      $data['status'] =  'E';
      $data['status2'] =  'E';
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $type = $head['type'];
      if (!empty($this->checking($config, $date))) {
        $msg = "Cannot create, application already exist.";
        $clientid = 0;
      } else {
        $clientid = $this->coreFunctions->insertGetId($this->head, $data);
        $this->logger->sbcmasterlog($clientid, $config, "CREATE - NAME: $empname, DATE: " . $data['dateid'] . " TYPE: " . $data['type'] . ", REMARKS: " . $data['rem'] . "");
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

  public function checking($config, $date)
  {
    $head = $config['params']['head'];
    $empid = $config['params']['adminid'];
    $companyid = $config['params']['companyid'];
    $line = $head['clientid'];

    $initial = '';
    $filter = "and status2 <> 'D'";
    if ($companyid == 53) { // camera
      // $initial = ' ,initialstatus';
      // $filter .= " and (initialstatus <> 'D' or initialstatus2 <> 'D')";
      goto status;
    }
    if ($companyid == 58) { //cdohris
      $filter .= " and trackingtype = '" . $head['trackingtype'] . "'";
    }

    $qry = "select status,status2,line $initial from $this->head where empid = $empid and type = '" . $head['type'] . "' and date(scheddate) = '" . $date . "' and status <> 'D' $filter ";
    $data =  $this->coreFunctions->opentable($qry);

    if (!empty($data)) {
      // if ($companyid == 53) { // camera
      //   if ($data[0]->initialstatus != 'D') {
      //     goto status;
      //   }
      // }
      // status:
      if ($data[0]->line == $line) {
        return [];
      }
      return  $data;
    }
    status:
    return [];
  }

  public function getlastclient()
  {
    $last_id = $this->coreFunctions->datareader("select line as value 
        from " . $this->head . " 
        order by line DESC LIMIT 1");

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
    $approved = $this->coreFunctions->opentable("select status,status2,initialstatus2,initialstatus,submitdate, approvedate,disapprovedate,approvedate2,disapprovedate2,initialappdate,initialapp,initialappdate2 from obapplication where line=?", [$clientid]);

    $msg = "Cannot delete an already approved application.";
    $msginitial = "'Cannot delete; already approved/disapproved initial application.'";

    if ($approved[0]->status == 'A') {
      return ['status' => false, 'msg' => $msg, 'clientid' => $clientid];
    }
    if ($approved[0]->status2 == 'A') {
      return ['status' => false, 'msg' => $msg, 'clientid' => $clientid];
    }

    if (!empty($approved)) {
      if ($approved[0]->approvedate != null) {
        return ['status' => false, 'msg' => 'Cannot delete; already approved.', 'clientid' => $clientid];
      }
      if ($companyid == 53) { //camera
        if ($approved[0]->initialappdate != null || $approved[0]->initialappdate2 != null) { //initialappdate2
          return ['status' => false, 'msg' => $msginitial, 'clientid' => $clientid];
        }
        if ($approved[0]->initialstatus2 == 'A' || $approved[0]->initialstatus == 'A') {
          return ['status' => false, 'msg' => $msginitial, 'clientid' => $clientid];
        }
      } else {
        if ($approved[0]->approvedate2 != null) {
          return ['status' => false, 'msg' => $msg, 'clientid' => $clientid];
        }
        if ($approved[0]->disapprovedate2 != null) {
          return ['status' => false, 'msg' => 'Cannot delete an already disapproved.', 'clientid' => $clientid];
        }
        if ($approved[0]->submitdate != null) {
          return ['status' => false, 'msg' => 'Cannot delete an already disapproved.', 'clientid' => $clientid];
        }
      }
    }


    $qry = "select line as value from obapplication where line = '$clientid' and status != 'E'";
    $count = $this->coreFunctions->datareader($qry);

    if ($count != "") {
      return ['clientid' => '0', 'status' => false, 'msg' => "Transaction cannot be deleted."];
    }
    $date = $this->othersClass->getCurrentDate();
    $this->coreFunctions->execqry('delete from obapplication where line=?', 'delete', [$clientid]);
    $this->logger->sbcmasterlog($clientid, $config, "DELETED - DATE: '$date' ");
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
    $companyid = $config['params']['companyid'];
    $line = $config['params']['dataid'];
    $adminid = $config['params']['adminid'];



    switch ($companyid) {
      case 51: //ulitc
        $status = $this->coreFunctions->datareader("select status as value from obapplication where line = '" . $line . "' and '" . $adminid . "'");
        if ($status != 'A') {
          $this->logger->sbcviewreportlog($config, "Application not yet approved");
          $str = app($this->companysetup->getreportpath($config['params']))->notallowtoprint($config, "Application not yet approved");
        } else {
          goto def;
        }

        break;
      default:
        def:
        $this->logger->sbcviewreportlog($config);
        $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
        break;
    }

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

  public function stockstatusposted($config)
  {

    $action = $config['params']['action'];

    switch ($action) {
      case 'forapproval':
        return  $this->forapprovalmail($config);
        break;

      case 'submit':
        return $this->submitmail($config);
        break;
    }
  }

  public function submitmail($config)
  {
    $line = $config['params']['trno'];
    $empid = $config['params']['adminid'];
    $companyid = $config['params']['companyid'];
    $submitdate = $this->coreFunctions->datareader("select submitdate as value from obapplication where line=? and submitdate is not null", [$line]);
    if (!empty($submitdate)) {
      return ['row' => [], 'status' => false, 'msg' => 'Already Submitted', 'backlisting' => false];
    }
    if ($companyid == 53) { // camera
      $picture = $this->coreFunctions->opentable("select picture,type from obapplication where line=? ", [$line]);
      if (!empty($picture)) {
        if ($picture[0]->type != "Off-setting") { //offsetting required malibasn sa offsetting
          if ($picture[0]->picture == "") {
            return ['row' => [], 'status' => false, 'msg' => 'Please Upload Image', 'backlisting' => false];
          }
        }
      }
    }

    $update = $this->coreFunctions->sbcupdate($this->head, ['submitdate' => $this->othersClass->getCurrentTimeStamp()], ['line' => $line]);
    $msg = "Application Submited.";
    $blnSuccess = true;
    $obdata = json_decode(json_encode($this->coreFunctions->opentable($this->getheadqry($config, $line))), true);
    if ($update) {
      switch ($companyid) {
        case 53: // camera
        case 51: // ulitc
          // $obdata = $this->coreFunctions->opentable($this->getheadqry($config, $trno));
          $filter = "";
          if ($companyid == 53) { // camera
            if ($obdata[0]['picture'] != '') {
              $mainfolder = '/images/';

              $attachments = [];
              $filename = str_replace($mainfolder, '', $obdata[0]['picture']);
              if (Storage::disk('public')->exists($filename)) {
                $file = Storage::disk('public')->get($filename);
                array_push($attachments, ['file' => $file, 'filename' => $obdata[0]['picture'], 'filetype' => Storage::disk('public')->mimeType($filename)]);
              }

              if (!empty($attachments)) {
                $params['attachments'] = $attachments;
                $params['hasattachment'] = true;
              }
            }
          }
          $url = 'App\Http\Classes\modules\payroll\\' . 'obapplication';
          $data2 = [];

          $data2 = ['empid' => $obdata[0]['empid']];

          switch ($companyid) {
            case 53: //camera
            case 51: //ulitc
              $stats = true;
              break;

            default:
              $stats = false;
              break;
          }
          $result = $this->othersClass->insertUpdatePendingapp(0, $line, 'OB', $data2, $url, $config, 0, $stats, true);
          // $this->othersClass->insertPendingapp(0, $line, 'OB', $data2, $url, $config, 0,  $stats);
          $params = [];
          $params['title'] = $this->modulename;
          $params['clientname'] = $obdata[0]['clientname'];
          $params['line'] = $obdata[0]['trno'];
          $params['scheddate'] = $obdata[0]['scheddate'] . " (" . $obdata[0]['dayname'] . ")";
          $params['dateid'] = $obdata[0]['dateid'];
          $params['rem'] = $obdata[0]['rem'];
          $params['reason1'] = $obdata[0]['disapproved_remarks2'];

          $params['reason2'] = $obdata[0]['approverem'];
          $params['datetime'] = $obdata[0]['datetime'];
          $params['datetime2'] = $obdata[0]['datetime2'];
          $params['type'] = $obdata[0]['type'];
          $params['location'] = $obdata[0]['location'];
          $params['companyid'] = $companyid;
          $params['muduletype'] = 'OB';


          // for new setup
          $query = "    select app.line, app.doc,app.clientid,emp.email,cl.email as username,app.approver from pendingapp as app
                        left join employee as emp on emp.empid = app.clientid
                        left join client as cl on cl.clientid = emp.empid
                        where doc = 'OB' and app.line = $line ";
          $data_ob = $this->coreFunctions->opentable($query);

          if (empty($data_ob)) {
            $this->coreFunctions->sbcupdate($this->head, ['submitdate' => null], ['line' => $line]);
            return ['row' => [], 'status' => false, 'msg' => 'Please advice admin to set up approver.', 'backlisting' => true];
          }
          foreach ($data_ob as $key => $value) {
            if (!empty($value->email)) {
              $params['approver'] = $value->username;
              $params['email'] = $value->email;
              $params['isapp'] = $value->approver;
              // $result =  $this->linkemail->createOBEmail($params);
              $result = $this->linkemail->weblink($params, $config);
              if (!$result['status']) {
                $msg = $result['msg'];
                $blnSuccess = false;
              }
            }
          }
          if (!$blnSuccess) {
            $this->coreFunctions->execqry("delete from pendingapp where doc='OB' and line=" . $line, 'delete');
            $this->coreFunctions->sbcupdate($this->head, ['submitdate' => null], ['line' => $line]);
          }
          break;
        default:

          $msg = 'Success';
          $url = 'App\Http\Classes\modules\payroll\\' . 'obapplication';
          $empid = $this->coreFunctions->getfieldvalue($this->head, "empid", "line=?", [$line]);
          $data = ['empid' => $empid];
          if ($companyid == 58) { //cdohris
            $latefilling = $this->coreFunctions->getfieldvalue($this->head, "islatefilling", "line=?", [$line]);
            $emplid =  $obdata[0]['empid'];
            $level = $this->coreFunctions->datareader("select level as value from employee where empid=?", [$emplid]);
            if ($latefilling != 0) {
              $approvers = "select apr.clientid,client.email from approvers as apr
                          left join moduleapproval as approval on approval.line = apr.trno
                          left join client on client.clientid=apr.clientid
                          where apr.isapprover = 1 and approval.modulename = 'OB'";
              $appr = $this->coreFunctions->opentable($approvers);

              if (!empty($appr)) {
                foreach ($appr as $apps) {
                  if ($this->othersClass->checkApproverAccess($apps, $level)) {
                    $appid = $apps->clientid;
                    $approvernotes = 'LATE FILLING';
                    $appstatus = $this->othersClass->insertUpdatePendingapp(0, $line, 'OB', $data, $url, $config, $appid, true, false, $approvernotes);
                  }
                }
              }
            } else {
              // not late filling 
              $appstatus = $this->othersClass->insertUpdatePendingapp(0, $line, 'OB', $data, $url, $config, 0, true, true);
            }
          } else { //not cdohris
            $appstatus = $this->othersClass->insertUpdatePendingapp(0, $line, 'OB', $data, $url, $config, 0, true, true);
          }
          if (!$appstatus['status']) {
            $this->coreFunctions->sbcupdate($this->head, ['submitdate' => null], ['line' => $line]);
            $msg = $appstatus['msg'];
            $blnSuccess = false;
          }
          break;
      }
      $submitdate = $this->othersClass->getCurrentTimeStamp();
      $this->logger->sbcmasterlog($obdata[0]['trno'], $config, "SUBMIT DATE : " . $submitdate);
      return ['row' => [], 'status' => $blnSuccess, 'msg' => $msg, 'backlisting' => true];
    }
  }
  public function forapprovalmail($config)
  {
    $line = $config['params']['trno'];
    $companyid = $config['params']['companyid'];
    $url = 'App\Http\Classes\modules\payroll\\' . 'obapplication';
    $initialapp = $this->coreFunctions->datareader("select initialapp as value from obapplication where line=? and initialapp is not null", [$line]);
    if (!empty($initialapp)) {
      return ['row' => [], 'status' => false, 'msg' => 'Already Forapproval', 'backlisting' => false];
    }
    $update = $this->coreFunctions->sbcupdate($this->head, ['initialapp' => $this->othersClass->getCurrentTimeStamp()], ['line' => $line]);
    $status = true;
    $msg = "Application For Approval";

    if ($update) {
      $obdata = json_decode(json_encode($this->coreFunctions->opentable($this->getheadqry($config, $line))), true);
      $params = [];

      $params['title'] = $this->modulename . ' INITIAL';
      $params['clientname'] = $obdata[0]['clientname'];
      $params['line'] = $obdata[0]['trno'];
      $params['scheddate'] = $obdata[0]['scheddate'] . " (" . $obdata[0]['dayname'] . ")";
      $params['dateid'] = $obdata[0]['dateid'];
      $params['rem'] = $obdata[0]['rem'];

      $params['reason1'] = $obdata[0]['initial_remarks'];
      $params['reason2'] = $obdata[0]['initial_remarks2'];

      $params['datetime'] = $obdata[0]['datetime'];
      $params['datetime2'] = $obdata[0]['datetime2'];
      $params['type'] = $obdata[0]['type'];
      $params['location'] = $obdata[0]['location'];
      $params['companyid'] = $companyid;
      $params['muduletype'] = 'OB_INITIAL';
      $data2 = ['empid' => $obdata[0]['empid']];

      $result = $this->othersClass->insertUpdatePendingapp(0, $line, 'INITIALOB', $data2, $url, $config, 0, true, true);
      if (!$result['status']) {
        $this->coreFunctions->sbcupdate($this->head, ['initialapp' => null], ['line' => $line]);
        return ['row' => [], 'status' => $result['status'], 'msg' => $result['msg'], 'backlisting' => true];
      }
      $query = "select cl.email as username, emp.email,app.approver from pendingapp as app 
                left join employee as emp on emp.empid = app.clientid
                left join client as cl on cl.clientid = emp.empid 
                where app.line = $line and app.doc = 'INITIALOB'";
      $data = $this->coreFunctions->opentable($query);
      if (empty($data)) {
        $this->coreFunctions->sbcupdate($this->head, ['initialapp' => null], ['line' => $line]);
        return ['row' => [], 'status' => false, 'msg' => 'Please advice admin to set up approver.', 'backlisting' => true];
      }
      foreach ($data as $key => $value) {
        if (!empty($value->email)) {
          $params['approver'] = $value->username;
          $params['isapp'] = $value->approver;
          $params['email'] = $value->email;
          // $res =  $this->linkemail->createOBInitialEmail($params);
          $res =  $this->linkemail->weblink($params, $config);
          if (!$res['status']) {
            $msg = $res['msg'];
            $status = false;
          }
        }
      } // end foreach


      if (!$status) {
        $this->coreFunctions->sbcupdate($this->head, ['initialapp' => null], ['line' => $line]);
        return ['row' => [], 'status' => false, 'msg' => $msg, 'backlisting' => true];
      }
    }
    $submitdate = $this->othersClass->getCurrentTimeStamp();
    $this->logger->sbcmasterlog($obdata[0]['trno'], $config, "FOR APPROVAL DATE : " . $submitdate);
    return ['row' => [], 'status' => $status, 'msg' => $msg, 'backlisting' => true];
  }
  public function generate_ob($config, $start, $end, $time, $data, $empname)
  {
    $start = new DateTime(date('Y-m-d', strtotime($start)));
    $end = new DateTime($end);
    $status = true;

    $interval = new DateInterval('P1D');
    $period = new DatePeriod($start, $interval, $end->modify('+1 day'));

    $dates = [];
    foreach ($period as $date) {
      array_push($dates, $date->format('Y-m-d'));
    }

    $line = 0;
    $i = 0;
    $msg = '';
    $linelist = '';


    foreach ($dates as $date) {
      if (!empty($this->checking($config, $date))) {
        $msg .= " Already Exist. $date and the other date can't be created.";
        $status = false;
      }
      foreach ($data as $key2 => $value) {

        if (strpos($key2, "dateid") !== false) {
          $data[$key2] = $date;
          $data[$key2] = $this->othersClass->sanitizekeyfield('dateid', $data[$key2] . " " . $time);
        }
        if (strpos($key2, 'scheddate2') !== false) {
          $data[$key2] =  $value;
        }
      }
      if ($status) {
        $lineob = $this->coreFunctions->insertGetId($this->head, $data);
        //WAG IDELETE: NI COMMENT LANG MUNA PARA MAUPDATE KAY CDO
        // if ($config['params']['companyid'] == 58) {
        //   $url = 'App\Http\Classes\modules\payroll\\' . 'obapplication';
        //   $appstatus = $this->othersClass->insertPendingapp(0, $lineob, 'OB', $data, $url, $config, 0, true);
        //   if (!$appstatus['status']) {
        //     $this->coreFunctions->execqry("delete from obapplication where  line=".$lineob, 'delete');
        //     $msg = $appstatus['msg'];
        //     $status = $appstatus['status'];
        //   } else {
        //     goto cont;
        //   }
        // } else {
        //   cont:
        // }
        if ($lineob != 0) {
          if ($i != 0) {
            $linelist .= ',' . $lineob;
          } else {
            $line = $lineob;
            $linelist .= $lineob;
          }
          $this->coreFunctions->sbcupdate($this->head, ['batchob' =>  $line], ['line' => $lineob]);
        }
        $this->logger->sbcmasterlog($lineob, $config, "CREATE - NAME: $empname, DATE: " . $data['dateid'] . " TYPE: " . $data['type'] . "," . "  Schedule Date : " . $start->format('Y-m-d') . " Schedule End: " . $end->format('Y-m-d') . " STATUS : ENTRY");
      } else {
        $line = 0;
        $this->coreFunctions->execqry("delete from " . $this->head . " where line in (" . $linelist . ") ", "delete");
        $this->coreFunctions->execqry("delete from pendingapp where doc='OB' and line in (" . $linelist . ")", 'delete');
        break;
      }
      $i++;
    }
    return ['status' => $status, 'clientid' => $line, 'msg' => $msg];
  }


  public function sbcscript($config)
  {
    $companyid = $config['params']['companyid'];
    switch ($companyid) {
      case 51: //ulitc
        return $this->sbcscript->obapplication($config);
        break;
      case 53: //camera
        return $this->sbcscript->obapplicationcamera($config);
        break;
      case 58: //cdohris
        return $this->sbcscript->obapplication_cdohris($config);
        break;
      default:
        return true;
        break;
    }
  }
} //end class
