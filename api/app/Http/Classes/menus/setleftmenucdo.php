<?php

namespace App\Http\Classes\menus;

/*
use Session;*/

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Classes\companysetup;
use App\Http\Classes\leftmenu;
use App\Http\Classes\leftmenu2;
use App\Http\Classes\coreFunctions;

use Exception;
use Throwable;
use Session;



class setleftmenucdo
{
  private $coreFunctions;
  private $companysetup;
  private $leftmenu;

  public function __construct()
  {
    $this->companysetup = new companysetup;
    $this->leftmenu = new leftmenu;
    $this->coreFunctions = new coreFunctions;
  }

  public function createmodule($params, $module, $i)
  {
    $menu = $this->$module($params);
    if (!isset($menu[$module])) {
      return [];
    }
    $parentid = $menu[$module]['parent'];
    $left_menu = '';
    $left_parent = '';
    $b = 1;
    $tmp = '';
    foreach ($menu[$module]['modules'] as $key => $value) {
      if (substr($value, 0, 6) != 'parent') {
        $tmp = $this->leftmenu->$value($params, $parentid, $b);
        if (isset($this->companysetup->posmodule)) {
          if (($key = array_search($value, $this->companysetup->posmodule)) !== false) {
            $tmp = str_replace('module', 'pos', $tmp);
          }
        }
        if ($left_menu == '') {
          $left_menu = $tmp;
        } else {
          $left_menu = $left_menu . "," . $tmp;
        }
        $b++;
      } else {
        $left_parent = $this->leftmenu->$value($params, $parentid, $i);
      }
    }
    if ($left_menu != '') {

      $left_menu = "insert into left_menu (seq,parent_id,doc,url,module,class,access,levelid) values " . $left_menu;
    }
    return [$left_parent, $left_menu];
  } //end function


  public function reportlist($params)
  {
    $reportslist = ['parentreportslist'];
    return ['reportlist' => ['parent' => 100, 'modules' => $reportslist]];
  }

  public function payrollportal($params)
  {
    $payrollportal = ['myinfo', 'parentpayrollportal', 'leaveapplicationportal', 'leaveapplicationportalapproval', 'obapplication', 'itinerary', 'otapplicationadv', 'undertime', 'restday', 'word', 'leavecancellation', 'obcancellation', 'undertimecancellation'];
    return ['payrollportal' => ['parent' => 1, 'modules' => $payrollportal]];
  } // end function

  public function recruitment($params)
  {
    $recruitment = ['parentrecruitment', 'personnel_requisition', 'applicant', 'appport', 'job_offer'];
    return ['recruitment' => ['parent' => 2, 'modules' => $recruitment]];
  } //end function

  public function employment($params)
  {
    $employment = ['parentemployment', 'ep', 'rs']; //employeepayroll
    return ['employment' => ['parent' => 3, 'modules' => $employment]];
  } //end function

  public function contractmonitoring($params)
  {
    $contractmonitoring = ['parentcontractmonitoring', 'contractmonitoring'];
    return ['contractmonitoring' => ['parent' => 45, 'modules' => $contractmonitoring]];
  } //end function

  public function discipline($params)
  {
    $discipline = ['parentdiscipline', 'code_of_conduct', 'hi', 'hn', 'hd', 'empndahistory'];
    return ['discipline' => ['parent' => 4, 'modules' => $discipline]];
  } //end function

  public function timekeeping($params)
  {
    $timekeeping = ['parenttimekeeping', 'emptimecard', 'leaveapplication', 'ear'];
    return ['timekeeping' => ['parent' => 37, 'modules' => $timekeeping]];
  }

  public function payrolltransaction($params)
  {
    $payrolltransaction = ['parentpayrolltransaction', 'loanapplication', 'timecardsetup', 'payrollsetup', 'hs','nb'];
    return ['payrolltransaction' => ['parent' => 19, 'modules' => $payrolltransaction]];
  } //end function

  public function benefits($params)
  {
    $benefits = ['parentbenefits', 'philhealth', 'sss', 'pagibig'];
    return ['benefits' => ['parent' => 38, 'modules' => $benefits]];
  } //end function

  public function monitoring($params)
  {
    $monitoring = ['parentmonitoring', 'adashboard', 'hc', 'ho', 'hr', 'employeeoverview'];
    return ['monitoring' => ['parent' => 39, 'modules' => $monitoring]];
  } //end function

  public function trainingdev($params)
  {
    $trainingdev = ['parenttrainingdev', 'ha', 'ht'];
    return ['trainingdev' => ['parent' => 40, 'modules' => $trainingdev]];
  } //end function

  public function announcement($params)
  {
    $announcement = ['parentannouncement', 'notice', 'event', 'holidayannouncement'];
    return ['announcement' => ['parent' => 22, 'modules' => $announcement]];
  } //end function

  public function branch($params)
  {
    $branch = ['parentbranch', 'branch'];
    return ['branch' => ['parent' => 23, 'modules' => $branch]];
  }

  public function transactionutilities($params)
  {
    $transactionutilities = ['parenttransactionutilities', 'prefix', 'audittrail', 'executionlog', 'uploadingutility', 'moduleapproval'];
    return ['transactionutilities' => ['parent' => 87, 'modules' => $transactionutilities]];
  } //end function


  public function accountutilities($params)
  {
    $isproject = $this->companysetup->getisproject($params);
    if ($this->companysetup->getbranchaccess($params)) {
      $accountutilities = ['parentaccountutilities', 'useraccess', 'branchaccess'];
    } else {
      $accountutilities = ['parentaccountutilities', 'useraccess', 'companyinfoaccess'];
    }
    array_push($accountutilities, 'rg');

    return ['accountutilities' => ['parent' => 88, 'modules' => $accountutilities]];
  } //end function


  public function masterfilerecruitment($params)
  {
    $masterrecruitment = ['parentmasterrecruitment', 'pre_employment_test', 'employmentrequirements', 'role'];
    return ['masterfilerecruitment' => ['parent' => 41, 'modules' => $masterrecruitment]];
  } //end function

  public function masterfileemployment($params)
  {
    $masteremployment = ['parentmasteremployment', 'branchledger', 'division', 'departmentmaster', 'section', 'rank', 'job_title', 'reassignmentcategory', 'employment_status', 'status_change', 'regularizationprocess', 'generationmaster'];
    return ['masterfileemployment' => ['parent' => 42, 'modules' => $masteremployment]];
  } //end function

  public function masterfiletimekeeping($params)
  {
    $mastertimekeeping = ['parentmastertimekeeping', 'createportaltempschedule', 'temptimecard', 'ls', 'leavesetup', 'leavecategory', 'shiftsetup', 'otapproval', 'locationsetup', 'holiday', 'holidayloc'];
    return ['masterfiletimekeeping' => ['parent' => 43, 'modules' => $mastertimekeeping]];
  } //end function

  public function masterfilepayroll($params)
  {
    $masterpayroll = ['parentmasterpayroll', 'paygroup', 'annualtax', 'tax', 'payrollaccount', 'ratesetup', 'accountingaccount', 'earningdeductionsetup', 'advancesetup', 'allowancesetup', 'batchsetup'];
    return ['masterfilepayroll' => ['parent' => 44, 'modules' => $masterpayroll]];
  } //end function

  public function dashboard($params)
  {
    $announcement = ['parentdashboard'];
    return ['dashboard' => ['parent' => 27, 'modules' => $announcement]];
  } //end function

} // end class
