<?php

namespace App\Http\Classes\modules\payroll;

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

class otapplication
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'OT APPLICATION';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $head = 'timecard';
  public $prefix = '';
  public $tablelogs = 'payroll_log';
  public $tablelogs_del = '';
  private $stockselect;

  private $fields = [
    'empid',
    'dateid',
    'rem',
    'type'
  ];
  // 'remarks','acno','days','bal',
  private $except = ['clientid', 'client'];
  private $blnfields = [];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = false;
  private $reporter;
  private $isexist = 0;

  public $showfilterlabel = [
    ['val' => 'draft', 'label' => 'Pending', 'color' => 'primary'],
    ['val' => 'entry', 'label' => 'Entry', 'color' => 'primary'],
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
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 3581,
      'edit' => 3581,
      'new' => 3581,
      'save' => 3581,
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $getcols = ['action', 'dateid', 'schedin', 'schedout', 'actualin', 'actualout', 'othrs', 'ndiffot'];
    $stockbuttons = ['otentryapplication'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    return $cols;
  }

  public function loaddoclisting($config)
  {
    $id = $config['params']['adminid'];
    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));

    $filteroption = '';
    $option = $config['params']['itemfilter'];

    $entry = "";
    $lastapprover = "";
    $svapprove = "and (t.otstatus=2 or t.otapproved =1)"; //default
    $svdisapp = "";
    if ($config['params']['companyid'] == 44) { //stonepro
      $entry = "and t.otstatus2 = 1 or (t.otstatus = 1 and t.otstatus2 = 2 )";
      $svapprove = " and (t.otstatus2 = 2 and t.otapproved = 0) "; //svapproved
      $lastapprover = " or ( t.otstatus2= 2 and t.otapproved = 1 )"; // last approver
      $svdisapp = " and t.otstatus2 = 3 "; //svdisapprove
    }

    switch ($option) {
      case 'draft':
        $filteroption = " and t.otstatus=0";
        break;
      case 'entry':
        $filteroption = " and t.otstatus=1 and t.otapproved =0 $entry";
        break;
      case 'approved':
        $filteroption = "$svapprove $lastapprover";
        break;
      default:
        $filteroption = " and t.otstatus=3 $svdisapp";
        break;
    }
    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['t.schedin', 't.schedout', 't.actualin', 't.othrs', 't.ndiffot', 't.entryot', 't.otstatus'];

      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
      $limit = "";
    }

    // $casestat = ", case 
    //   when t.otstatus = 0 then 'PENDING'
    //   when t.otstatus = 1 then 'ENTRY'
    //   when t.otstatus = 2 then 'APPROVED'
    //   when t.otstatus = 3 then 'DISAPPROVED'
    //   END as status";
    // if ($config['params']['companyid'] == 44 || $config['params']['companyid'] == 53) { //stonepro 
    //   $casestat = ", case
    //       when t.otstatus = 0 then 'PENDING'
    //       when t.otstatus = 1 && t.otstatus2 = 1 then 'ENTRY'
    //       when t.otstatus = 1 && t.otstatus2 = 2  then 'APPROVED (Supervisor)'
    //       when t.otstatus = 2 && t.otstatus2 = 2  then 'APPROVED'
    //       when t.otstatus = 3 && t.otstatus2 = 3 then 'DISAPPROVED'
    //       END as status";
    // }

    $qry = "select t.line,t.dateid,t.schedin,t.schedout,t.actualin,t.actualout,t.othrs,t.ndiffhrs as ndiffot,t.entryot,t.otstatus,
     case 
      when t.otstatus = 0 then 'PENDING'
      when t.otstatus = 1 then 'ENTRY'
      when t.otstatus = 2 then 'APPROVED'
      when t.otstatus = 3 then 'DISAPPROVED'
      END as status
    from timecard as t
    left join client on client.clientid=t.empid
    where (t.othrs <> 0 or t.ndiffot <> 0) and date(t.dateid) between '" . $date1 . "' and '" . $date2 . "' and t.empid=$id " . $filteroption . " $filtersearch
    order by dateid";


    $data = $this->coreFunctions->opentable($qry);

    return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
  }

  public function createHeadbutton($config)
  {
    $btns = array(
      'load'
    );
    $buttons = $this->btnClass->create($btns);
    return $buttons;
  } // createHeadbutton

  public function createTab($access, $config)
  {
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
  }


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
    $approvers = ['isapprover'];
    return $approvers;
  }
} //end class
