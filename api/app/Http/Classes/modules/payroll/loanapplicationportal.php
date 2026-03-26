<?php

namespace App\Http\Classes\modules\payroll;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;
use App\Http\Classes\common\payrollcommon;
use App\Http\Classes\common\linkemail;
use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\sbcscript\sbcscript;

class loanapplicationportal
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'LOAN APPLICATION PORTAL';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $linkemail;
  private $sqlquery;
  private $payrollcommon;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $head = 'loanapplication';
  public $prefix = 'ED';
  public $tablelogs = 'payroll_log';
  public $tablelogs_del = '';
  private $stockselect;
  private $isexist = 0;

  private $fields = [
    'docno',
    'dateid',
    'empid',
    'remarks',
    'acno',
    'amt',
    'w1',
    'w2',
    'w3',
    'w4',
    'w5',
    'halt',
    'priority',
    'amortization',
    'effdate',
    'balance',
    'pament',
    'w13',
    'acnoid',
    'enddate',
    'licenseno',
    'licensetype',
    'purpose',
    'purpose1'
  ];
  // 'remarks','acno','days','bal',
  private $except = ['clientid', 'client'];
  private $blnfields = ['w1', 'w2', 'w3', 'w4', 'w5', 'halt', 'w13'];
  public $showfilteroption = true;
  public $showfilter = false;
  public $showcreatebtn = true;
  private $reporter;
  private $sbcscript;

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
    $this->linkemail = new linkemail;
    $this->payrollcommon = new payrollcommon;
    $this->sbcscript = new sbcscript;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 4779,
      'new' => 4781,
      'save' => 4779,
      'delete' => 4782,
      'print' => 4780,
      'edit' => 4784,
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $companyid = $config['params']['companyid'];
    switch ($config['params']['companyid']) {
      case 44: //stonepro
      case 51: //ulitc
      case 53: //camera
        $this->showcreatebtn = true;
        break;
    }
    $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='LOAN'");
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

    $getcols = ['action', 'createdate', 'listdocument', 'listdate', 'scheddate', 'acnoname', 'bal', 'rem', 'listappstatus2', 'approvedby_disapprovedbysup', 'date_approved_disapprovedsup', 'rem2', 'listappstatus', 'approvedby_disapprovedby', 'date_approved_disapproved', 'remarks'];

    // if ($companyid == 53) { //camera
    //   $getcols = ['action', 'createdate', 'scheddate', 'acnoname', 'bal', 'rem', 'listappstatus2', 'approvedby_disapprovedbysup', 'date_approved_disapprovedsup', 'rem1', 'listappstatus', 'approvedby_disapprovedby', 'date_approved_disapproved', 'remarks'];
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

    $cols[$acnoname]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;';
    $cols[$bal]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;text-align:right';
    $cols[$listappstatus]['style'] = 'width:120px;whiteSpace: normal;min-width:120px; text-align:left;';
    $cols[$listappstatus2]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; text-align:left;';
    $cols[$acnoname]['type'] = 'label';
    $cols[$acnoname]['label'] = 'Account Name';
    $cols[$bal]['label'] = 'Amount';
    $cols[$rem]['label'] = 'Remarks';


    switch ($companyid) {
      case '53':
        $cols[$createdate]['label'] = 'Date Applied';
        $cols[$acnoname]['label'] = 'Account Name';
        $cols[$date_approved_disapproved]['label'] = 'Date Approved/Disapproved (Hr/Payroll Approver)';
        $cols[$approvedby_disapprovedby]['label'] = 'Approved/Disapproved By (Hr/Payroll Approver)';
        $cols[$rem]['label'] = 'Reason';
        $cols[$scheddate]['style'] = 'width:120px;whiteSpace:normal;min-width:130px;';
        $cols[$acnoname]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;';
        $cols[$remarks]['label'] = 'Hr/Payroll Approver Reason';
        $cols[$rem2]['label'] = 'Head Dept. Approver Reason';
        $cols[$date_approved_disapprovedsup]['label'] = 'Date Approved/Disapproved (Head Dept. Approver)';

        $cols[$listappstatus2]['label'] = 'Head Dept. Approver Status';

        $cols[$approvedby_disapprovedbysup]['label'] = 'Approved/Disapproved by (Head Dept. Approver)';
        $cols[$listappstatus]['label'] = 'Hr/Payroll Approver status';
        $cols[$listdocument]['type'] = 'coldel';
        $cols[$listdate]['type'] = 'coldel';

        $cols[$void_remarks]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
        $cols[$void_remarks]['label'] = 'Reason';
        $cols[$void_date]['type'] = 'label';
        $cols[$void_approver]['type'] = 'label';

        break;
      case '51': //ulitc

        $cols[$rem2]['type'] = 'coldel';
        $cols[$remarks]['type'] = 'coldel';

        $cols[$listdocument]['style'] = 'width:130px;whiteSpace: normal;min-width:130px;';
        $cols[$listdate]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;text-align:center';
        $cols[$scheddate]['type'] = 'coldel';

        break;
      default:
        $cols[$listdocument]['style'] = 'width:130px;whiteSpace: normal;min-width:130px;';
        $cols[$listdate]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;text-align:center';
        $cols[$scheddate]['type'] = 'coldel';
        break;
    }

    if ($config['params']['companyid'] == 58) { //cdo
      $cols[$listappstatus]['label'] = 'Status (HR)';
      $cols[$listappstatus]['style'] = 'width:600px;whiteSpace: normal;min-width:600px; text-align:left;';
      $cols[$listappstatus2]['style'] = 'width:250px;whiteSpace: normal;min-width:250px; text-align:left;';
    } else {
      if (count($approversetup) == 1) {
        $cols[$listappstatus2]['type'] = 'coldel';
        $cols[$date_approved_disapprovedsup]['type'] = 'coldel';
        $cols[$approvedby_disapprovedbysup]['type'] = 'coldel';
        $cols[$rem2]['type'] = 'coldel';
      }
    }



    $cols = $this->tabClass->delcollisting($cols);
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
        ['label' => 'Entry', 'value' => 'entry'],
        ['label' => 'Approved', 'value' => 'approved'],
        ['label' => 'Disapproved', 'value' => 'disapproved']
      ]
    );

    $fields = ['refresh'];
    $col2 = $this->fieldClass->create($fields);

    $data = $this->coreFunctions->opentable("select '' as searchby");
    return ['status' => true, 'data' => $data[0], 'txtfield' => ['col1' => $col1, 'col2' => $col2]];
  }

  public function loaddoclisting($config)
  {
    $filtersearch = "";
    $searchby = isset($config['params']['doclistingparam']['searchby']['value']) ? $config['params']['doclistingparam']['searchby']['value'] : '';
    if (isset($config['params']['search'])) {
      $searchfield = ['e.empid', 'client.client', 'client.clientname', 'acct.codename', 's.docno'];

      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }
    $companyid = $config['params']['companyid'];
    $id = $config['params']['adminid'];
    $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='LOAN'");
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

    $leftjoin = "";
    $addfields = "";

    $casestat = "";

    switch ($companyid) {
      case '44':
        $casestat = ",case
      when s.status = 'E' && s.status2 = 'E' then 'ENTRY'
      when s.status = 'E' && s.status2 = 'A' then 'APPROVED (Supervisor)'
      when s.status = 'A' && s.status2 = 'A' then 'APPROVED'
      when s.status = 'E' && s.status2 = 'D' then 'DISAPPROVED' 
      when s.status = 'D' && s.status2 = 'A' then 'DISAPPROVED'
      END as jstatus";
        break;
      case '53':
        $casestat = ",
      case 
      when s.status = 'E' then 'ENTRY'
      when s.status = 'A' then 'APPROVED'
      when s.status = 'D' then 'DISAPPROVED'
      END as jstatus,
      case 
      when s.status2 = 'E' and s.status = 'E' and s.submitdate is null then 'ENTRY'
      when s.status2 = 'E' and s.status = 'E' and s.submitdate is not null then 'FOR APPROVAL'
      when s.status2 = 'A' then 'APPROVED'
      when s.status2 = 'D' then 'DISAPPROVED'
      END as status2  ";
        $addfields = ",voidapp.clientname as void_approver,s.void_remarks,s.void_date ";
        $leftjoin = "left join client as voidapp on voidapp.email = s.void_by and voidapp.email <> ''";
        break;
      default:

        if (count($approversetup) > 1 || $both) {
          $casestat = ",
      case 
      when s.status = 'E' then 'ENTRY'
      when s.status = 'A' then 'APPROVED'
      when s.status = 'D' then 'DISAPPROVED'
      END as jstatus ";
        } else {
          $casestat = ",
      case 
      when s.status = 'E' then 'ENTRY'
      when s.status = 'A' then 'APPROVED'
      when s.status = 'D' then 'DISAPPROVED'
      END as jstatus,
      case 
      when s.status2 = 'E' and s.submitdate is null then 'ENTRY'
      when s.status2 = 'E' and s.submitdate is not null then 'FOR APPROVAL'
      when s.status2 = 'A' then 'APPROVED'
      when s.status2 = 'D' then 'DISAPPROVED'
      END as status2 ";
        }

        break;
    }

    $qry = "select s.trno as clientid, s.docno, date_format(s.dateid, '%m-%d-%y') as dateid, e.empid, client.client as empcode, 
        client.clientname as empname, acct.codename as acnoname, format(s.balance,2) as bal,s.disapproved_remarks as remarks,
        s.disapproved_remarks2 as rem2,client.clientname,s.remarks as rem,
        date(s.date_approved_disapproved) as date_approved_disapproved,app.clientname as approvedby_disapprovedby, date_format(s.dateid, '%m-%d-%y') as createdate,date(s.effdate) as scheddate,
        app2.clientname as approvedby_disapprovedbysup,s.date_approved_disapproved2 as date_approved_disapprovedsup,s.licensetype,s.licenseno,s.purpose,s.purpose1
        $casestat $addfields
        from loanapplication as s 
        left join employee as e ON e.empid = s.empid
        left join client on client.clientid=e.empid
        left join paccount as acct on acct.line = s.acnoid
        left join client as app on app.email = s.approvedby_disapprovedby and app.email <> ''
        left join client as app2 on app2.email = s.approvedby_disapprovedby2 and app2.email <> ''
        $leftjoin
        where client.clientid = '$id' $filtersearch";

    $searcfield = ['s.status'];

    $svstdisapprove = "";
    $svdisapprove2 = "";
    $svapprove1 = "";
    $stsvapprove2 = "";
    $entry = "";
    if ($config['params']['companyid'] == 44) { //stonepro
      $svstdisapprove = "and s.status2='A'"; //stdisapproved
      $svdisapprove2 = " or s.status='E' and s.status2 = 'D'"; //sv disapprove
      $svapprove1 = " or s.status='E' and s.status2='A' "; //sv approved
      $stsvapprove2 = " and s.status2='A'"; //stapproved
      $entry = "and s.status2 = 'E'"; //entry
    }

    switch ($searchby) {
      case 'entry':
        $qry .= " and s.status = 'E' $entry ";
        break;

      case 'approved':
        $qry .= " and s.status = 'A' $stsvapprove2 $svapprove1";
        break;

      case 'disapproved':
        $qry .= " and s.status = 'D' $svstdisapprove $svdisapprove2";
        break;
    }
    array_push($searcfield, 's.status');
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
  public function createtab2($access, $config)
  {
    $companyid = $config['params']['companyid'];
    if ($companyid == 51) { // ulitc
      $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrypayrollpicture', 'label' => 'Attachment', 'access' => 'view']];
      $obj = $this->tabClass->createtab($tab, []);
      $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];
      return $return;
    }
    return [];
  }
  public function createTab($access, $config)
  {
    // $tab = [
    //     'jobdesctab' => [
    //         'action' => 'payrollentry',
    //         'lookupclass' => 'entryearningdeduction',
    //         'label' => 'EARNING AND DEDUCTION'
    // ]];
    $tab = [];
    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    return $obj;
  }

  public function createtabbutton($config)
  {
    return [];
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];
    $fields = ['client', 'dateid', 'remarks'];

    if ($companyid == 51) { //ulitc
      $fields = ['client', 'dateid', 'acno', 'acnoname', 'effdate', 'remarks'];
    }
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.class', 'csclientearningdeduction sbccsenablealways');
    data_set($col1, 'client.label', 'Docno #');
    // data_set($col1, 'client.action', 'lookupledger');
    data_set($col1, 'client.type', 'input');
    data_set($col1, 'dateid.lookupclass', 'lookupearningdeduction');

    data_set($col1, 'client.readonly', true);
    data_set($col1, 'client.class', 'csclient sbccsreadonly');

    data_set($col1, 'dateid.readonly', true);
    data_set($col1, 'dateid.class', 'csdateid sbccsreadonly');

    data_set($col1, 'remarks.type', 'ctextarea');

    if ($companyid == 51) { //ulitc
      data_set($col1, 'acno.lookupclass', 'lookuploanapp_account');
      data_set($col1, 'acnoname.readonly', true);
      data_set($col1, 'acnoname.class', 'csacnoname sbccsreadonly');
    }

    $fields = ['acno', 'acnoname', 'effdate'];
    if ($companyid == 51) { //ulitc
      $fields = ['licenseno', 'licensetype', 'purpose', 'purpose1'];
    }
    if ($companyid == 53) {
      array_push($fields, 'enddate');
      data_set($col1, 'remarks.label', 'Reason');
    }

    if ($companyid == 58) { //cdo
      array_push($fields, 'status2', 'status');
    } else {
      array_push($fields, 'status');
    }

    $col2 = $this->fieldClass->create($fields);
    if ($companyid == 51) { //ulitc
      data_set($col2, 'licenseno.label', "Driver's License no.");
      data_set($col2, 'licenseno.readonly', true);
      data_set($col2, 'licensetype.readonly', true);
      data_set($col2, 'licenseno.class', 'cslicenseno sbccsreadonly');
      data_set($col2, 'licensetype.class', 'cslicensetype sbccsreadonly');
      data_set($col2, 'purpose.label', 'Purpose of Loan');
      data_set($col2, 'purpose.type', "lookup");
      data_set($col2, 'purpose.action', "lookuppurposeloan");
      data_set($col2, 'purpose.lookupclass', "lookuppurposeloan");
      data_set($col2, 'purpose.class', 'cspurpose sbccsreadonly');
      data_set($col2, 'purpose1.class', 'cspurpose1 sbccsreadonly');
    } else {
      data_set($col2, 'acno.lookupclass', 'lookuploanapp_account');
      data_set($col2, 'acnoname.readonly', true);
      data_set($col2, 'acnoname.class', 'csacnoname sbccsreadonly');
    }
    if ($companyid == 58) { //cdo
      data_set($col2, 'status.label', 'Status (HR)');
    }

    $fields = ['amt', 'amortization', 'bal'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'amt.label', 'Total Loan Amount');
    data_set($col3, 'amt.type', 'cinput');

    data_set($col3, 'bal.name', 'balance');
    data_set($col3, 'bal.type', 'cinput');
    data_set($col3, 'bal.class', 'csbal sbccsreadonly');

    data_set($col3, 'amortization.type', 'cinput');

    $fields = [['w1', 'w4'], ['w2', 'w5'], ['w3']];

    if ($companyid == 51) { //ulitc
      $fields = [];
    }
    if ($companyid == 53 || $companyid == 51) { // camera|ulitc
      array_push($fields, 'lblsubmit', 'submit');
    }
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'lblsubmit.type', 'label');
    data_set($col4, 'lblsubmit.label', 'SUBMITTED');
    data_set($col4, 'lblsubmit.style', 'font-weight:bold;font-size:15px;font-family:Century Gothic;color:green;');

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function newclient($config)
  {
    $data = $this->resetdata($config['newclient'], $config['params']);
    $hideobj = [];
    $companyid = $config['params']['companyid'];
    if ($companyid == 53 || $companyid == 51) { //camera|ulitc
      $hideobj['submit'] = true;
      $hideobj['lblsubmit'] = true;
    }
    return  ['head' => $data, 'islocked' => false, 'isposted' => false, 'status' => true, 'isnew' => true, 'msg' => 'Ready for New Ledger', 'hideobj' => $hideobj];
  }

  private function resetdata($client = '', $config)
  {
    $data = [];
    $data[0]['clientid'] = 0;
    $data[0]['client'] = $client;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['remarks'] = '';
    $data[0]['empid'] = '';
    $data[0]['acno'] = '';
    $data[0]['acnoid'] = 0;
    $data[0]['acnoname'] = '';
    $data[0]['priority'] = 0;
    $data[0]['amt'] = 0;
    $data[0]['amortization'] = 0;
    $data[0]['balance'] = 0;
    $data[0]['effdate'] = $this->othersClass->getCurrentDate();
    $data[0]['w1'] = '0';
    $data[0]['w2'] = '0';
    $data[0]['w3'] = '0';
    $data[0]['w4'] = '0';
    $data[0]['w5'] = '0';
    $data[0]['status'] = 'ENTRY';
    $data[0]['status2'] = 'ENTRY';
    $data[0]['submitdate'] = null;
    $data[0]['enddate'] = $this->othersClass->getCurrentDate();
    $data[0]['divid'] = 0;
    $data[0]['licenseno'] = '';
    $data[0]['licensetype'] = '';
    $data[0]['purpose'] = '';
    $data[0]['purpose1'] = '';
    if ($config['companyid'] == 53) { //camera
      $data[0]['divid'] = $this->coreFunctions->datareader("select divid as value from employee where empid=?", [$config['adminid']]);
    }
    return $data;
  }

  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $companyid = $config['params']['companyid'];
    $adminid = $config['params']['adminid'];
    $clientid = $this->othersClass->val($config['params']['clientid']);
    $center = $config['params']['center'];
    if ($clientid == 0) $clientid = $this->getlastclient();
    $submdate = '';
    $status2 = '';
    $entrystat = "s.status = 'E'";

    switch ($companyid) {
      case 44: //stonepro
      case 51: //stonepro
        $entrystat = "s.status = 'E' and s.status2 = 'E'";
        break;
      case 53: //camera
        $submdate = ',s.enddate';
        break;
      case 58: //cdo
        $status2 = ",case 
          when s.status2 = 'E' then 'ENTRY'
          when s.status2 = 'A' then 'APPROVED'
          when s.status2 = 'E' and s.status2='A' then 'APPROVED (Supervisor)'
          when s.status2 = 'D' then 'DISAPPROVED'
          else s.status2
        END as status2 ";
        break;
    }

    $qryselect = "select s.trno as clientid,s.trno as trno, s.docno as client, 
        s.docno, s.dateid, s.empid, s.remarks, pac.code as acno, s.amt, s.paymode, 
        w1,w2,w3,w4,w5,w13,halt,s.priority, s.earnded, s.amortization, 
        s.effdate,s.payment,e.divid,
        concat(e.emplast,', ',e.empfirst,' ',e.empmiddle) as empname, 
        pac.codename as acnoname, client.client as empcode,
        balance, s.acnoid, s.submitdate,s.date_approved_disapproved2,s.date_approved_disapproved,s.licenseno,s.licensetype,s.purpose,s.purpose1,
        case 
          when " . $entrystat . " and s.submitdate is null then 'ENTRY'
          when s.status = 'E' and (s.status2 = 'E' or s.status2 = 'A') and s.submitdate is not null then 'FOR APPROVAL'  
          when s.status = 'A' then 'APPROVED'
          when s.status = 'E' and s.status2='A' then 'APPROVED (Supervisor)'
          when s.status = 'D' then 'DISAPPROVED'
          else s.status
        END as status $submdate $status2";

    $qry = $qryselect . " 
        from loanapplication as s
        left join employee as e on s.empid = e.empid
        left join client on client.clientid = e.empid
        left join paccount as pac on pac.line = s.acnoid
        left join standardtrans as st on s.trno = st.line
        where s.trno = ? and s.empid = '$adminid' ";

    $head = $this->coreFunctions->opentable($qry, [$clientid]);

    if (!empty($head)) {

      foreach ($this->blnfields as $key => $value) {
        if ($head[0]->$value) {
          $head[0]->$value = "1";
        } else {
          $head[0]->$value = "0";
        }
      }
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }
      $hideobj = [];

      $submitdate = $head[0]->submitdate != null ? true : false;
      $date_approved_disapproved2 = $head[0]->date_approved_disapproved2 != null ? true : false;
      $date_approved_disapproved = $head[0]->date_approved_disapproved != null ? true : false;

      if ($submitdate || $date_approved_disapproved2 || $date_approved_disapproved) {
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

      if ($submitdate) {
        $hideobj['lblsubmit'] = false;
      }
      return  ['head' => $head, 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => false, 'qq' => $config['params']['clientid'], 'hideobj' => $hideobj];
    } else {
      $head = $this->resetdata('', $config['params']);
      return ['status' => false, 'isnew' => true, 'head' => $head, 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified...'];
    }
  }

  public function updatehead($config, $isupdate)
  {
    $head = $config['params']['head'];
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];
    $empid = $config['params']['adminid'];
    $data = [];

    if (isset($head['clientid'])) {

      $submitdate = $this->coreFunctions->datareader("select submitdate as value from loanapplication where trno=? ", [$head['clientid']], '', true);
      if ($companyid == 53) { //camera 
        if ($submitdate) {
          $url = 'App\Http\Classes\modules\payroll\\' . 'loanapplicationportal';
          $result = $this->payrollcommon->checkapplicationstatus($config, $head['clientid'], $url, $submitdate);
          if (!$result['status']) {
            return ['status' => false, 'msg' => $result['msg'], 'clientid' => $head['clientid']];
          }
        }
      } else {
        $approved = $this->coreFunctions->datareader("select date_approved_disapproved2 as value from loanapplication where trno=? and date_approved_disapproved2 is not null", [$head['clientid']]);
        if ($approved) {
          return ['status' => false, 'msg' => 'Cannot update; already approved/disapproved by the supervisor.', 'clientid' => $head['clientid']];
        }


        $approved = $this->coreFunctions->datareader("select date_approved_disapproved as value from loanapplication where trno=? and date_approved_disapproved is not null", [$head['clientid']]);
        if ($approved) {
          return ['status' => false, 'msg' => 'Cannot update; already approved/disapproved.', 'clientid' => $head['clientid']];
        }
      }
    }
    $clientid = 0;
    $msg = '';

    if ($isupdate) {
      unset($this->fields['docno']);
    } else {
      $data['docno'] = $head['client'];
      $head['docno'] = $head['client'];
    }

    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        } //end if 
      }
    }
    if ($companyid != 53) { // camera
      if ($head['submitdate'] != null) { //foremail
        return ['status' => false, 'msg' => 'Cannot update already submitted.'];
      }
    }
    if ($companyid == 53) {
      $checkcutoffdate = $this->payrollcommon->checkbatchsched($data['effdate'], $head['divid']);
      if (!empty($checkcutoffdate['msg'])) {
        $msg = $checkcutoffdate['msg'];
        return ['status' => false, 'msg' => $msg];
      }
    }
    $date = date('Y-m-d', strtotime($head['effdate']));

    if ($companyid == 51) { // ulitc
      $data['w2'] = 1;
      $data['w4'] = 1;
      $data['apamt'] = $data['amt'];
      $data['apamortization'] = $data['amortization'];
    }

    if ($isupdate) {
      if ($companyid != 53) { // not camera
        if (substr($head['status'], 0, 1) != 'E') {
          return ['status' => false, 'msg' => "Can't Modified", 'clientid' => '0'];
        }
      }
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];

      if (!empty($this->checking($config, $date))) {
        $msg = "Already Exist";
        $head['clientid'] = 0;
      } else {
        $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['clientid']]);
        $clientid = $head['clientid'];
      }
    } else {

      $data['empid'] = $config['params']['adminid'];
      $data['status'] = "E";
      $data['balance'] = $data['amt'];
      $data['status2'] = "E";

      if (!empty($this->checking($config, $date))) {
        $msg = "Already Exist";
        $head['clientid'] = 0;
      } else {
        $clientid = $this->coreFunctions->insertGetId($this->head, $data);
        $this->logger->sbcmasterlog(
          $clientid,
          $config,
          'CREATE' . ' - ' . $head['client'] . ' - ' . $head['acnoname']
            . ' - ' . 'AMT: ' . $head['amt'] . ' - ' . 'AMORTIZATION: ' . $head['amortization'] .
            ' - ' . 'BAL: ' . $head['balance']
        );
      }
    }
    ext:
    $status = true;
    if ($msg == '') {
      $msg = 'Successfully saved';
    } else {
      $status = false;
    }
    return ['status' => $status, 'msg' => $msg, 'clientid' => $clientid];
  } // end function

  public function getlastclient()
  {
    $last_id = $this->coreFunctions->datareader("select head.trno as value 
      from " . $this->head . " as head
      order by trno DESC LIMIT 1");

    return $last_id;
  }

  public function openstock($trno, $config)
  {
    $qry = 'select line, trno, description from jobtdesc where trno=?';
    return $this->coreFunctions->opentable($qry, [$trno]);
  }

  public function deletetrans($config)
  {
    $clientid = $config['params']['clientid'];

    $approved = $this->coreFunctions->opentable("select status,status2 from loanapplication where trno=? ", [$clientid]);
    if (!empty($approved)) {
      if ($approved[0]->status == "A") {
        return ['status' => false, 'msg' => 'Cannot delete an already approved application.', 'clientid' => $clientid];
      }
      if ($approved[0]->status2 == "A") {
        return ['status' => false, 'msg' => 'Cannot delete an already approved application.', 'clientid' => $clientid];
      }
    }

    $qry = "select line as value from loanapplication where trno=? and status != 'E'";
    $count = $this->coreFunctions->datareader($qry, [$clientid]);

    if ($count != "") {
      return ['clientid' => $clientid, 'status' => false, 'msg' => "Transaction cannot be deleted."];
    }

    $this->coreFunctions->execqry('delete from ' . $this->head . ' where trno=?', 'delete', [$clientid]);
    $this->coreFunctions->execqry('delete from pendingapp where trno=?', 'delete', [$clientid]);
    return ['clientid' => 0, 'status' => true, 'msg' => 'Successfully deleted.'];
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
    $this->logger->sbcviewreportlog($config);

    if ($companyid == 51) { // ulitc
      $dataparams = $config['params']['dataparams'];
      if (isset($dataparams['prepared'])) $this->othersClass->writeSignatories($config, 'prepared', $dataparams['prepared']);
      if (isset($dataparams['noted'])) $this->othersClass->writeSignatories($config, 'noted', $dataparams['noted']);
      if (isset($dataparams['endorseby'])) $this->othersClass->writeSignatories($config, 'endorseby', $dataparams['endorseby']);
    }

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
  public function stockstatusposted($config)
  {
    $trno = $config['params']['trno'];
    $empid = $config['params']['adminid'];
    $companyid = $config['params']['companyid'];
    $submitdate = $this->coreFunctions->datareader("select submitdate as value from loanapplication where trno=? and submitdate is not null", [$trno]);
    $attachment = $this->coreFunctions->datareader("select trno as value from loan_picture where trno=?", [$trno]);
    if ($companyid == 51) { //ulitc
      if (empty($attachment)) {
        return ['row' => [], 'status' => false, 'msg' => 'Please attach the necessary requirements for the loan application.', 'backlisting' => false];
      }
    }
    if (!empty($submitdate)) {
      return ['row' => [], 'status' => false, 'msg' => 'Already Submitted', 'backlisting' => false];
    }
    $update = $this->coreFunctions->sbcupdate($this->head, ['submitdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $trno]);
    $msg = 'Success';
    $status = true;
    if ($update) {
      $url = 'App\Http\Classes\modules\payroll\\' . 'loanapplicationportal';
      $data2 = ['empid' => $empid];
      $result = $this->othersClass->insertUpdatePendingapp($trno, 0, 'LOAN', $data2, $url, $config, 0, true, true);
      if (!$result['status']) {
        $msg = $result['msg'];
        $this->coreFunctions->sbcupdate($this->head, ['submitdate' => null], ['trno' => $trno]);
        return ['row' => [], 'status' => $result['status'], 'msg' => $msg, 'backlisting' => true];
      }
      if ($companyid == 53 || $companyid == 51) {
        $query = "
        select s.trno,s.dateid, s.empid, s.remarks, pac.code as acno, format(s.amt,2) as amt, s.paymode,
        w1,w2,w3,w4,w5,w13, format(s.amortization,2) as amortization,date(s.effdate) as effdate,
        concat(e.emplast,', ',e.empfirst,' ',e.empmiddle) as empname,s.balance,s.disapproved_remarks as remlast,s.disapproved_remarks2 as rem2,
        pac.codename as acnoname,
        case 
        when w1 = 1 then 'Week 1'
        when w2 = 1 then 'Week 2' 
        when w3 = 1 then 'Week 3'
        when w4 = 1 then 'Week 4'
        when w5 = 1 then 'Week 5'
        else ''
        end as week
        from loanapplication as s
        left join employee as e on s.empid = e.empid
        left join paccount as pac on pac.line = s.acnoid
        where s.trno = $trno ";

        $loandata = json_decode(json_encode($this->coreFunctions->opentable($query)), true);

        $params = [];
        $params['title'] = 'LOAN APPLICATION';
        $params['clientname'] = $loandata[0]['empname'];
        $params['line'] = $loandata[0]['trno'];
        $params['effdate'] = $loandata[0]['effdate'];
        $params['acnoname'] = $loandata[0]['acnoname'];
        $params['amount'] = $loandata[0]['amt'];
        $params['amortization'] = $loandata[0]['amortization'];
        $params['remarks'] = $loandata[0]['remarks'];
        $params['reason1'] = $loandata[0]['rem2'];
        $params['remlast'] = $loandata[0]['remlast'];
        $params['week'] = $loandata[0]['week'];
        $params['balance'] = $loandata[0]['balance'];
        $params['companyid'] = $companyid;
        $params['muduletype'] = 'LOAN';


        $query = "select app.line, app.doc,app.clientid,emp.email,cl.email as username,app.approver as isapp from pendingapp as app
                            left join employee as emp on emp.empid = app.clientid
                            left join client as cl on cl.clientid = emp.empid
                            where doc = 'LOAN' and app.trno = $trno ";
        $data = $this->coreFunctions->opentable($query);

        if (empty($data)) {
          $this->coreFunctions->sbcupdate($this->head, ['submitdate' => null], ['trno' => $trno]);
          $this->coreFunctions->execqry("delete from pendingapp where doc='LOAN' and trno=" . $trno, 'delete');
          return ['row' => [], 'status' => false, 'msg' => 'Please advice admin to set up approver.', 'backlisting' => true];
        }


        foreach ($data as $key => $value) {
          if (!empty($data[$key]->email)) {
            $params['approver'] = $data[$key]->username;
            $params['email'] = $data[$key]->email;
            $params['isapp'] = $data[$key]->isapp;
            // $res = $this->linkemail->createLoanEmail($params);
            $res = $this->linkemail->weblink($params, $config);
            if (!$res['status']) {
              $msg = $res['msg'];
              $status = false;
            }
          }
        }
        if (!$status) {
          $this->coreFunctions->sbcupdate($this->head, ['submitdate' => null], ['trno' => $trno]);
          $this->coreFunctions->execqry("delete from pendingapp where doc='LOAN' and trno=" . $trno, 'delete');
          return ['row' => [], 'status' => false, 'msg' => $msg, 'backlisting' => true];
        }
      }
    } else {
      return ['row' => [], 'status' => false, 'msg' => 'Error updating record', 'backlisting' => true];
    }
    $submitdate = $this->othersClass->getCurrentTimeStamp();
    $this->logger->sbcmasterlog($trno, $config, "SUBMIT DATE : " . $submitdate);
    return ['row' => [], 'status' => $status, 'msg' => $msg, 'backlisting' => true];
  }
  public function checking($config, $date)
  {
    $head = $config['params']['head'];
    $empid = $config['params']['adminid'];
    $line = $head['clientid'];

    $filter = "";

    $data =  $this->coreFunctions->opentable("select status,status2,trno from $this->head 
              where empid = $empid and acnoid = '" . $head['acnoid'] . "' and balance <> 0
              and (status <> 'D' and status2 <> 'D')");

    if (!empty($data)) {

      if ($data[0]->trno == $line) {
        return [];
      }
      return $data;
    }
    return [];
  }
  public function sbcscript($config)
  {
    if ($config['params']['companyid'] == 51) { //ulitc
      return $this->sbcscript->loanapplicationportal($config);
    } else {
      return true;
    }
  }
} //end class
