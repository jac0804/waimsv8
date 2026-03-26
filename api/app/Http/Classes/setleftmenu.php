<?php

namespace App\Http\Classes;

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



class setleftmenu
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

  public function masterfile($params)
  {
    $systemtype = $this->companysetup->getsystemtype($params);
    switch ($systemtype) {
      case 'AMS':
        $masterfile = ['parentmasterfile', 'customer', 'supplier', 'employeemaster', 'departmentmaster'];
        break;
      case 'BMS':
        $masterfile = ['parentmasterfile', 'bg', 'bu', 'infra', 'tl'];
        break;
      default:
        $masterfile = ['parentmasterfile', 'customer', 'supplier', 'employeemaster', 'departmentmaster', 'stockcard', 'agent', 'warehouse', 'itemquery'];
        break;
    }

    switch ($params['companyid']) {
      case 57: //cdo financing
        $masterfile = ['parentmasterfile',  'customer',   'financingpartner', 'purposeofpayment', 'transactiontype', 'modeofpayment'];
        break;
      case 10: //afti
      case 12: //afti usd
        array_push($masterfile, 'branchledger');
        array_push($masterfile, 'outsourcestockcard');
        if (($key = array_search('itemquery', $masterfile)) !== false) {
          unset($masterfile[$key]);
        }
        break;

      case 19: //housegem
        array_push($masterfile, 'forwarder');
        break;

      case 40: //cdocycles
        array_push($masterfile, 'productinquiry', 'modeoftransction', 'area', 'financingpartner', 'purposeofpayment', 'transactiontype', 'modeofpayment');
        break;

      case 35: //Aquamax
        $masterfile = ['parentmasterfile', 'customer', 'aquastockcard', 'project'];
        break;

      case 43: //mighty
        if (($key = array_search('employeemaster', $masterfile)) !== false) {
          unset($masterfile[$key]);
        }
        break;
      case 48: //seastar
        array_push($masterfile, 'warehouse');
        break;

      case 56: //homeworks
        array_push($masterfile, 'facard');
        break;
    }

    if ($this->companysetup->getsystemtype($params) == 'WAIMS' || $this->companysetup->getsystemtype($params) == 'VSCHED') {
      array_push($masterfile, 'forwarder');
      if ($params['companyid'] == 6) { //mitsukoshi
        if (($key = array_search('departmentmaster', $masterfile)) !== false) {
          unset($masterfile[$key]);
        }
      }
    }

    if ($this->companysetup->getsystemtype($params) == 'CAIMS') {
      if (($key = array_search('departmentmaster', $masterfile)) !== false) {
        unset($masterfile[$key]);
      }

      if ($params['companyid'] == 8) { //maxipro
        if (($key = array_search('agent', $masterfile)) !== false) {
          unset($masterfile[$key]);
        }
      }
    }

    if ($this->companysetup->getisfixasset($params)) {
      array_push($masterfile, 'facard');
    }

    switch ($this->companysetup->getsystemtype($params)) {
      case 'ALL':
      case 'AIMSHRIS':
        array_push($masterfile, 'role');
        break;
      case 'MMS':
        $masterfile = ['parentmasterfile', 'customer', 'supplier', 'employeemaster', 'departmentmaster', 'tenant', 'location_ledger'];
        break;
      case 'FAMS':
        array_push($masterfile, 'facard'); //, 'generalitem', 'genericitem'
        break;
      case 'VSCHED':
        $masterfile = ['parentmasterfile', 'customer', 'employeemaster', 'departmentmaster', 'forwarder'];
        break;
      case 'ATI':
        $masterfile = ['parentmasterfile', 'customer', 'supplier', 'employeemaster', 'warehouse', 'departmentmaster', 'forwarder', 'stockcard', 'facard', 'coa']; //'genericitem', 
        break;
      case 'REALESTATE':
        $masterfile = ['parentmasterfile', 'customer', 'supplier', 'employeemaster', 'departmentmaster'];
        if ($this->companysetup->isconstruction($params)) {
          $masterfile = ['parentmasterfile', 'customer', 'supplier', 'employeemaster', 'departmentmaster', 'stockcard', 'warehouse', 'agent', 'itemquery'];
        }
        break;
      case 'EAPPLICATION':
        $masterfile = ['parentmasterfile',  'agent', 'useraccess', 'plangroup', 'terms', 'prefix', 'companyinfoaccess'];
        break;
      case 'LENDING':
        $masterfile = ['parentmasterfile', 'customer', 'supplier', 'agent', 'terms', 'loantype', 'sbu'];
        break;
    }

    if ($this->companysetup->getsystemtype($params) == 'AIMSPAYROLL' || $this->companysetup->getsystemtype($params) == 'AIMSHRIS') {
      if (($key = array_search('departmentmaster', $masterfile)) !== false) {
        unset($masterfile[$key]);
      }
      if (($key = array_search('employeemaster', $masterfile)) !== false) {
        unset($masterfile[$key]);
      }
    }


    return ['masterfile' => ['parent' => 1, 'modules' => $masterfile]];
  } // end function

  public function itemmaster($params)
  {
    switch ($params['companyid']) {
      case 6: //mitsukoshi
        $itemmaster = ['parentitemmaster', 'model', 'stockgroup', 'brand', 'clientcategories', 'project', 'compatible', 'partrequesttype', 'checkerlocation', 'deliverytype', 'itemcategory', 'whrem'];
        break;
      case 10: //afti
      case 12: //afti usd
        $itemmaster = ['parentitemmaster', 'model', 'part', 'brand', 'itemclass', 'clientcategories', 'project', 'itemcategory', 'itemsubcategory', 'industry'];
        break;
      case 22: //eipi
        $itemmaster = ['parentitemmaster', 'model', 'part', 'stockgroup', 'brand', 'itemclass', 'clientcategories', 'itemcategory', 'itemsubcategory'];
        break;
      case 43: // mighty
        $itemmaster = ['parentitemmaster', 'model', 'part', 'stockgroup', 'brand', 'itemclass', 'clientcategories', 'project', 'itemcategory', 'itemsubcategory', 'activitymaster'];
        break;
      case 48: // seastar
        $itemmaster = ['parentitemmaster', 'clientcategories', 'project'];
        break;
      default:
        $itemmaster = ['parentitemmaster', 'model', 'part', 'stockgroup', 'brand', 'itemclass', 'clientcategories', 'project', 'itemcategory', 'itemsubcategory', 'ordertype', 'channel'];
        if ($params['companyid'] == 19) { //housegem
          array_push($itemmaster, 'deliverytype');
        }
        if ($params['companyid'] == 8 || $params['companyid'] == 19) { //maxipro - housegem
          if (($key = array_search('project', $itemmaster)) !== false) {
            unset($itemmaster[$key]);
          }
        }
        if ($params['companyid'] == 56) { //homeworks
          array_push($itemmaster, 'reasoncodesetup', 'expiration');
        }
        if ($params['companyid'] == 59) { //roosevelt
          array_push($itemmaster, 'certrate');
        }

        if ($params['companyid'] == 63) { //ericco
          array_push($itemmaster, 'repacker');
        }
        break;
    }

    $systemtype = $this->companysetup->getsystemtype($params);
    switch ($systemtype) {
      case 'MMS':
        $itemmaster = ['parentitemmaster', 'clientcategories', 'phase', 'mms_section', 'billableitemssetup', 'electric_rate_category', 'water_rate_category', 'electricityrate', 'storage_electricityrate', 'waterrate'];
        break;
      case 'VSCHED':
        $itemmaster = ['parentitemmaster', 'clientcategories', 'purpose'];
        break;
      case 'ATI':
        array_push($itemmaster, 'purpose', 'duration', 'requestcategory', 'requesttype', 'payments', 'uomlist', 'conversionuom');
        break;
      case 'REALESTATE':
        $itemmaster = ['parentitemmaster', 'clientcategories', 'project', 'amenities'];
        break;
    }

    return ['itemmaster' => ['parent' => 2, 'modules' => $itemmaster]];
  } //end function

  public function purchase($params)
  {
    $systemtype = $this->companysetup->getsystemtype($params);

    switch ($params['companyid']) {
      case 2: //mis
        $purchase = ['parentpurchase', 'po', 'rr', 'dm', 'pr', 'cd', 'cd2'];
        break;
      case 3: //conti
        $purchase = ['parentpurchase', 'pr', 'cd', 'cd2', 'po', 'rr', 'sn', 'dm'];
        break;
      case 10: //afti
        $purchase = ['parentpurchase', 'pr', 'po', 'rr', 'sr', 'jb', 'ac', 'dm', 'os'];
        break;
      case 12: //afti usd
        $purchase = ['parentpurchase',  'po', 'sr', 'jb'];
        break;
      case 6: //mitsukoshi
      case 8: //maxipro
      case 19: //housegem
      case 21: //kinggeorge
      case 31: //JLI
        $purchase = ['parentpurchase', 'po', 'rr', 'dm'];
        break;
      case 15: //nathina
        $purchase = ['parentpurchase', 'po', 'rr', 'sn', 'dm'];
        break;
      case 16: //ati
        $purchase = ['parentpurchase', 'prlisting', 'barcodeassigning', 'mm', 'pr', 'cd', 'cdsummary', 'cdapprovalsummary', 'cd2', 'cd3', 'oq', 'om', 'lq', 'po', 'rr', 'dm', 'cv'];
        break;
      case 24: // goodfound
        $purchase = ['parentpurchase', 'pr', 'po', 'rr', 'dm', 'pu', 'ru'];
        break;
      case 26: //bee healthy
        $purchase = ['parentpurchase', 'rr', 'dm'];
        break;
      case 32: //3m
        $purchase = ['parentpurchase',  'po', 'rr', 'dm'];
        break;
      case 39: //cbbsi
        $purchase = ['parentpurchase', 'pr', 'po', 'rt', 'rr',  'di', 'dm', 'ph', 'sm'];
        break;
      case 43: //mighty
        $purchase = ['parentpurchase',  'pr', 'po', 'rr', 'dm'];
        break;
      case 42: //pdpi
      case 56: //homeworks
        $purchase = ['parentpurchase',  'po', 'rr', 'dm'];
        break;
      default:
        // $purchase = ['parentpurchase', 'po', 'rr', 'dm', 'samplepo', 'samplepo2'];
        $purchase = ['parentpurchase', 'po', 'rr', 'dm'];
        if ($this->companysetup->getispr($params)) {
          $purchase = ['parentpurchase', 'pr', 'po', 'rr', 'dm'];
        }
        if ($systemtype == 'FAMS' && $this->companysetup->getispurchases($params)) {
          $purchase = ['parentpurchase', 'pr', 'cd', 'cd2', 'po', 'rr', 'dm'];
        }
        break;
    }
    return ['purchase' => ['parent' => 3, 'modules' => $purchase]];
  } // end function

  public function sales($params)
  {
    switch ($params['companyid']) {
      case 3: //conti
      case 15: //nathina
      case 17: //unihome
      case 20: //proline
      case 27: //nte
      case 49: //hotmix
        $sales = ['parentsales', 'qt', 'so', 'sj', 'cm', 'mr', 'mi'];
        break;
      case 6: //mitsukoshi
        $sales = ['parentsales', 'sa', 'sd', 'sb', 'se', 'sc', 'sf', 'cm', 'sg', 'sh', 'si', 'incentivesgenerator'];
        break;
      case 10: //afti
        $sales = ['parentsales', 'op', 'qs', 'sq', 'sj', 'cm', 'ao', 'ai', 'te', 'vt', 'vs', 'su', 'rf', 'comm', 'itemgroupqoutasetup', 'salesgroupqouta'];
        break;
      case 12: //afti usd
        $sales = ['parentsales', 'qs', 'sq', 'ao', 'vt', 'vs', 'rf', 'comm', 'itemgroupqoutasetup', 'salesgroupqouta'];
        break;
      case 28: //xcomp
      case 36: //rozlab
        $sales = ['parentsales', 'so', 'sj', 'cm', 'mr', 'mi'];
        break;
      case 39: //cbbsi
        $sales = ['parentsales', 'qt', 'so', 'dr', 'dn', 'sk', 'ck', 'cm', 'dp',  'mr', 'mi'];
        break;
      case 40: //cdocycles
        $sales = ['parentsales', 'so', 'sj', 'cm', 'ci', 'mc', 'closingmccollection', 'dx'];
        break;
      case 24: //goodfound
        $sales = ['parentsales', 'so', 'sj', 'bo', 'cm', 'packhouseloading', 'released'];
        break;
      case 26: //bee healthy
        $sales = ['parentsales', 'sj', 'cm'];
        break;
      case 19: //housegem
        $sales = ['parentsales', 'so', 'ro', 'solisting', 'sj', 'cm'];
        break;
      case 43: //mighty
        $sales = ['parentsales', 'so', 'sj', 'cm', 'mr', 'mi', 'jo', 'eq'];
        break;
      case 48: //seastar
        $sales = ['parentsales', 'sj', 'll'];
        break;
      case 63: //ericco
        $sales = ['parentsales', 'so', 'sj', 'cm', 'ch', 'on'];
        break;
      case 59: //roosevelt
        $sales = ['parentsales', 'so', 'sj', 'cm', 'pl'];
        break;
      default:
        if ($this->companysetup->getiscreateversion($params)) {
          $sales = ['parentsales', 'qt', 'so', 'sj', 'cm'];
        } else {
          $sales = ['parentsales', 'so', 'sj', 'cm'];
        }

        break;
    }
    return ['sales' => ['parent' => 4, 'modules' => $sales]];
  } // end function

  public function inventory($params)
  {
    $inventory = ['parentinventory', 'pc', 'aj', 'ts', 'is'];

    switch ($params['companyid']) {
      case 3: // conti
        array_push($inventory, 'va');
        break;
      case 19; //housegem
        $inventory = ['parentinventory', 'pc', 'aj', 'ts', 'is', 'mi'];
        break;
      case 43: //mighty
      case 49: //hotmix
        $inventory = ['parentinventory', 'is', 'pc', 'aj', 'tr', 'trapproval', 'ts'];
        break;
      case 27: //nte
      case 36: //rozlab
        $inventory = ['parentinventory', 'is', 'pc', 'aj', 'ts'];
        break;
      case 40: //cdo
        $inventory = ['parentinventory', 'pc', 'aj', 'ts', 'is', 'tr', 'trapproval', 'st'];
        break;
      case 17: //unihome
        $inventory = ['parentinventory', 'at', 'pc', 'aj', 'ts', 'is'];
        break;
      case 56: //homeworks
        $inventory = ['parentinventory', 'pc', 'aj', 'ts', 'is'];
        break;
      default:

        break;
    }
    return ['inventory' => ['parent' => 5, 'modules' => $inventory]];
  } // end function

  public function payable($params)
  {
    $companyid = $params['companyid'];
    switch ($companyid) {
      case 8: //maxipro
      case 10: //afti
      case 17: //unihome
      case 19: //housegem
      case 22: //eipi
      case 28: //xcomp
      case 31: //JLI
        $payable = ['parentpayable',  'ap', 'pv', 'cv'];
        break;
      case 39: //cbbsi
        // $payable = ['parentpayable', 'ap', 'pv', 'pq', 'sv', 'py','ps', 'cv'];
        $payable = ['parentpayable', 'ap', 'pv', 'pq', 'sv', 'py', 'ps', 'cv'];
        break;
      case 21: //kinggeorge
        $payable = ['parentpayable',  'ap', 'cv'];
        break;
      case 16: //ati
        $payable = [];
        break;
      case 56: //homeworks
        $payable = ['parentpayable', 'ap', 'pv', 'cv', 'checkrelease', 'kp'];
        break;
      case 60: //transpower
        $payable = ['parentpayable', 'ap', 'pv', 'cv'];
        break;
      default:
        $payable = ['parentpayable', 'pq', 'sv', 'ap', 'pv', 'cv', 'checkrelease'];
        break;
    }

    return ['payable' => ['parent' => 6, 'modules' => $payable]];
  } // end function


  public function receivable($params)
  {
    $systemtype = $this->companysetup->getsystemtype($params);

    switch ($params['companyid']) {
      case 8: //maxipro
      case 35: //aquamax
        $receivable = ['parentreceivable', 'ar', 'cr'];
        break;
      case 34: //evergreen
        $receivable = ['parentreceivable', 'cr'];
        break;
      case 39: // cbbsi
        $receivable = ['parentreceivable', 'ar', 'ka', 'cr', 'kr', 'dc'];
        break;
      case 40: //cdo
        $receivable = ['parentreceivable', 'ar', 'cr'];
        break;
      case 55: //afli
        $receivable = ['parentreceivable', 'ar', 'cr', 'rc', 'kr'];
        break;
      case 59: //roosevelt
        $receivable = ['parentreceivable', 'ar', 'cr',  'kr', 'rc', 'rh', 'rd', 'be', 're'];
        break;
      case 60: //transpower
        $receivable = ['parentreceivable', 'ar', 'cr', 'kr'];
        break;
      default:
        $receivable = ['parentreceivable', 'ar', 'cr', 'rc', 'kr'];
        break;
    }

    if ($systemtype == 'REALESTATE') {
      array_push($receivable, 'fs'); // financing setup
      array_push($receivable, 'rc'); // receive checks
    }

    if ($systemtype == 'MMS') {
      array_push($receivable, 'rc'); // receive checks
    }

    return ['receivable' => ['parent' => 7, 'modules' => $receivable]];
  } //end function

  public function accounting($params)
  {
    $systemtype = $this->companysetup->getsystemtype($params);

    $accounting = ['parentaccounting', 'coa', 'gj', 'gd', 'gc', 'ds', 'bankrecon'];
    switch ($params['companyid']) {
      case 6: //mitsukoshi
      case 11: //summit
      case 35: //aquamax
        break;
      case 8: //maxipro
      case 19: //housegem
      case 21: //kinggeorge
      case 22: //eipi
        $accounting = ['parentaccounting', 'coa', 'gj', 'gd', 'gc', 'ds'];
        break;
      case 26: //bee healthy
        $accounting = ['parentaccounting', 'coa', 'gj', 'gd', 'gc', 'ds', 'checksetup', 'project', 'costcodes'];
        break;
      case 12: //afti usd
        $accounting = ['parentaccounting', 'coa', 'exchangerate'];;
        break;
      case 16: //ati
        $accounting = [];
        break;
      case 34: //evergreen
        $accounting = ['parentaccounting', 'coa', 'gj'];
        break;
      case 48: //seastar
        $accounting = ['parentaccounting', 'coa', 'fa', 'postdep', 'gj', 'gd', 'gc', 'ds', 'bankrecon'];
        break;
      case 40: //cdo
        $accounting = ['parentaccounting', 'coa', 'gj', 'gd', 'gc', 'bankrecon'];
        break;
      case 56: //homeworks
        $accounting = ['parentaccounting', 'coa', 'gj', 'gd', 'gc', 'ds', 'exchangerate'];
        break;
      case 60: //transpower
        $accounting = ['parentaccounting', 'coa', 'gj', 'gd', 'gc', 'ds'];
        break;
      default:
        array_push($accounting, 'budget', 'checksetup', 'exchangerate');
        break;
    }
    if ($this->companysetup->getisfixasset($params)) {
      array_push($accounting, 'postdep');
    }
    if ($params['companyid'] != 16) array_push($accounting, 'coaalias'); //not ati

    return ['accounting' => ['parent' => 8, 'modules' => $accounting]];
  } // end function

  public function issuance($params)
  {
    $companyid = $params['companyid'];
    switch ($companyid) {
      case 14: //majesty
        $issuance = ['parentissuance', 'tr', 'trapproval', 'ss'];
        break;
      case 16: //ati
        $issuance = ['parentissuance', 'ss', 'sp'];
        break;
      case 3: //conti
      case 17: //unihome
      case 39: //CBBSI
        $issuance = ['parentissuance', 'tr', 'trapproval', 'st'];
        break;
      default;
        $issuance = ['parentissuance', 'tr', 'trapproval', 'st', 'ss'];
        break;
    }
    return ['issuance' => ['parent' => 15, 'modules' => $issuance]];
  } //end function

  public function schoolsetup($params)
  {
    $schoolsetup = [
      'parentschoolsetup',
      'levels',
      'semester',
      'roomlist',
      'subject',
      'student',
      'new_student_requirement',
      'transfer_requirement',
      'instructor',
      'schoolyear',
      'scheme',
      'period',
      'course',
      'fees',
      'credentials',
      'mode_of_payment',
      'quarter_setup',
      'honorroll_criteria',
      'grade_component',
      'grade_equivalent',
      'grade_equivalentletters',
      'grade_setup',
      'attendance_type',
      'conduct_grade',
      'cardremarks',
      'attendancesetup',
      'reportcard'
    ];
    return ['schoolsetup' => ['parent' => 10, 'modules' => $schoolsetup]];
  } //end function


  public function schoolsystem($params)
  {
    $schoolsystem = [
      'parentschoolsystem',
      'ec',
      'assessmentsetup',
      'schedule',
      'grade_school_assessment',
      'college_assessment',
      'registration',
      'addordrop',
      'gradeentry',
      'studentgradeentry',
      'attendanceentry',
      'studentreportcard'
    ];
    return ['schoolsystem' => ['parent' => 11, 'modules' => $schoolsystem]];
  } //end function

  public function roommanagement($params)
  {
    $roommanagement = ['parentroommanagement', 'hmsratecode', 'hmsroomtype', 'hmsothercharges', 'hmspackagesetup'];
    return ['roommanagement' => ['parent' => 12, 'modules' => $roommanagement]];
  } //end function

  public function frontdesk($params)
  {
    $frontdesk = ['parentfrontdesk', 'hmsreservation', 'hmstempreservation', 'hmswalkin', 'hmsroomplan'];
    return ['frontdesk' => ['parent' => 13, 'modules' => $frontdesk]];
  } //end function

  public function customersupport($params)
  {
    $customersupport = ['parentcustomersupport', 'create_ticket', 'ticket_history']; //update_ticket
    return ['customersupport' => ['parent' => 14, 'modules' => $customersupport]];
  } //end function


  public function hrissetup($params)
  {
    $hrissetup = [
      'parenthrissetup',
      'code_of_conduct',
      'skill_requirement',
      'job_title',
      'pre_employment_test',
      'employmentrequirements',
      'employment_status',
      'status_change',
      'requestcategory',
      'generationmaster',
      'regularizationprocess'
    ];


    switch ($params['companyid']) {
      case 3: //conti
        array_push($hrissetup, 'division', 'departmentmaster', 'section');
        break;
    }

    return ['hrissetup' => ['parent' => 17, 'modules' => $hrissetup]];
  } //end function

  public function hris($params)
  {
    switch ($params['companyid']) {
      case 3: // conti
        $hris = ['parenthris', 'applicant', 'personnel_requisition', 'job_offer', 'ha', 'ht', 'ho', 'hr', 'hi', 'hn', 'hd', 'hc', 'hs', 'empndahistory', 'empchangehistory', 'employeepayroll'];
        break;
      default:
        $hris = [
          'parenthris',
          'personnel_requisition',
          'applicant',
          'appport',
          'job_offer',
          'contractmonitoring',
          'hs',
          'empchangehistory',
          'ha',
          'ht',
          'ho',
          'hr',
          'hi',
          'hn',
          'hd',
          'empndahistory',
          'hc'
        ];
        break;
    }
    return ['hris' => ['parent' => 16, 'modules' => $hris]];
  } //end function

  public function timekeeping($params)
  {
    $timekeeping = [
      'parenttimekeeping',
      'rank',
      'role',
      'division',
      'departmentmaster',
      'section',
      'paygroup',
      'payrollaccount',
      'holiday',
      'holidayloc',
      'shiftsetup',
      'leavesetup',
      'employeepayroll',
      'leaveapplication',
      'emptimecard',
      'timecardsetup',
      'otapproval'
    ];

    if ($params['companyid'] == 61) { //Bytesized
      if (($key = array_search('departmentmaster', $timekeeping)) !== false) {
        unset($timekeeping[$key]);
      }
    }

    return ['timekeeping' => ['parent' => 37, 'modules' => $timekeeping]];
  }

  public function payrollsetup($params)
  {
    $systype = $this->companysetup->getsystemtype($params);

    switch ($params['companyid']) {
      case 45: //pdpi payroll
        $payrollsetup = [
          'parentpayrollsetup',
          'paygroup',
          'holiday',
          'holidayloc',
          'payrollaccount',
          'ratesetup',
          'shiftsetup',
          'division',
          'departmentmaster',
          'section',
          'role',
          'skill_requirement',
          'job_title'
        ];
        break;
      default:
        $payrollsetup = [
          'parentpayrollsetup',
          'branchledger',
          'rank',
          'division',
          'departmentmaster',
          'section',
          'role',
          'paygroup',
          'annualtax',
          'philhealth',
          'sss',
          'pagibig',
          'tax',
          'payrollaccount',
          'locationsetup',
          'holiday',
          'holidayloc',
          'leavesetup',
          'leavebatchcreation',
          'ratesetup',
          'shiftsetup',
          'biometric',
          'skill_requirement',
          'job_title',
          'accountingaccount'
        ];
        break;
    }

    if ($systype == 'HRISPAYROLL' || $systype == 'AIMSHRISPAYROLL') {
      if (($key = array_search('skill_requirement', $payrollsetup)) !== false) {
        unset($payrollsetup[$key]);
      }
      if (($key = array_search('job_title', $payrollsetup)) !== false) {
        unset($payrollsetup[$key]);
      }

      if ($params['companyid'] == 58) { //cdohris
        array_push($payrollsetup, 'leavecategory');
      }

      if ($params['companyid'] == 62) { //onesky
        array_push($payrollsetup, 'entryreasonforhiring', 'empstatustypeentry');
      }
    }

    if ($params['companyid'] == 43) { //mighty
      array_push($payrollsetup, 'status_change', 'employment_status');
    }

    if ($params['companyid'] == 61) { //Bytesized
      if (($key = array_search('departmentmaster', $payrollsetup)) !== false) {
        unset($payrollsetup[$key]);
      }
    }


    return ['payrollsetup' => ['parent' => 18, 'modules' => $payrollsetup]];
  } //end function


  public function payrolltransaction($params)
  {
    $payrolltransaction = ['parentpayrolltransaction', 'employeepayroll', 'earningdeductionsetup', 'advancesetup', 'allowancesetup', 'leaveapplication', 'loanapplication', 'pieceentry', 'batchsetup', 'emptimecard', 'emptimecardperday', 'timecardsetup', 'otapproval', 'payrollsetup'];

    switch ($params['companyid']) {
      case 28: //XCOMP
        array_push($payrolltransaction, 'hs');
        break;
      case 43: //mighty
        array_push($payrolltransaction, 'hs', 'ti', 'oi');
        break;
      case 44: //stonepro
        $payrolltransaction = ['parentpayrolltransaction', 'employeepayroll', 'emptimecard', 'emptimecardperday'];
        break;
      case 51: //ulitc
        $payrolltransaction = ['parentpayrolltransaction', 'employeepayroll', 'emptimecard'];
        break;
      case 53: //camera
        $payrolltransaction = ['parentpayrolltransaction', 'employeepayroll', 'emptimecard', 'createportaltempschedule', 'temptimecard', 'timesetup'];
        break;
      case 45: //pdpi payroll
        $payrolltransaction = ['parentpayrolltransaction', 'employeepayroll', 'emptimecard', 'emptimecardperday', 'batchsetup', 'timecardsetup', 'otapproval', 'payrollsetup', 'empprojectlog', 'empprojectlogb'];
        break;
      case 58:
        $payrolltransaction = ['parentpayrolltransaction', 'employeepayroll', 'earningdeductionsetup', 'advancesetup', 'allowancesetup', 'leaveapplication', 'loanapplication', 'pieceentry', 'batchsetup', 'emptimecard', 'emptimecardperday', 'timecardsetup', 'otapproval', 'payrollsetup'];
        break;
      case 62: //mighty
        array_push($payrolltransaction, 'changeschedule');
        break;
    }
    $payroll = $this->companysetup->getsystemtype($params);
    if (strpos($payroll, 'PAYROLL') !== false) {
      if (!in_array($params['companyid'], [51, 53])) { // ulitc | camera
        array_push($payrolltransaction, 'timeadjustment');
      }
    }

    if ($params['username'] == 'sbc') {
      array_push($payrolltransaction, 'timerec');
    }

    return ['payrolltransaction' => ['parent' => 19, 'modules' => $payrolltransaction]];
  } //end function


  public function projectsetup($params)
  {
    $projectsetup = ['parentprojectsetup', 'project', 'pm', 'stages'];
    return ['projectsetup' => ['parent' => 25, 'modules' => $projectsetup]];
  } //end function


  public function construction($params)
  {
    $construction = ['parentconstruction', 'br', 'al', 'bl', 'bq', 'constructionpr', 'jr', 'jo', 'jc', 'mi', 'mt', 'ba', 'pb'];
    if ($this->companysetup->isconstruction($params)) {
      $construction = ['parentconstruction', 'ct',  'cc', 'mr', 'mi', 'pn', 'mt']; //add other modules
    }
    return ['construction' => ['parent' => 20, 'modules' => $construction]];
  } //end function

  public function warehousing($params)
  {
    $warehousing = [
      'parentwarehousing',
      'pi',
      'pl',
      'rp',
      'forklift',
      'warehouseman',
      'warehousecontroller',
      'warehousepicker',
      'warehousechecker',
      'dispatching',
      'logistics',
      'wa',
      'wb',
      'replenishitem'
    ]; //'replenishpallet' 8.5.2021 temporary hide
    return ['warehousing' => ['parent' => 24, 'modules' => $warehousing]];
  } //end function


  public function payrollportal($params)
  {
    $payrollportal = ['myinfo', 'parentpayrollportal', 'loanapplicationportal', 'leaveapplicationportal', 'leaveapplicationportalapproval', 'obapplication', 'otapplication']; //, 'portalreports'
    switch ($params['companyid']) {
      case 25: //SBC PORTAL
        array_push($payrollportal, 'changeshiftapplication', 'timein');
        break;
      case 44: //STONEPRO
        array_push($payrollportal, 'changeshiftapplication', 'undertime', 'otapplicationadv');
        if (($key = array_search('otapplication', $payrollportal)) !== false) {
          unset($payrollportal[$key]);
        }
        if (($key = array_search('leaveapplicationportalapproval', $payrollportal)) !== false) {
          unset($payrollportal[$key]);
        }
        break;
      case 51: //ulitc - otapplication for test version only
        $payrollportal = ['myinfo', 'parentpayrollportal', 'leaveapplicationportal', 'obapplication', 'otapplicationadv', 'loanapplicationportal'];
        break;
      case 53: //camera
        $payrollportal = ['myinfo', 'parentpayrollportal', 'leaveapplicationportal', 'obapplication', 'otapplicationadv', 'loanapplicationportal', 'changeshiftapplication'];
        break;
      case 9: //PAYROLL PORTAL
        array_push($payrollportal, 'undertime', 'restday', 'word', 'leavecancellation', 'undertimecancellation');
        break;
      case 58: //CDO
        $payrollportal = ['myinfo', 'parentpayrollportal', 'loanapplicationportal', 'leaveapplicationportal', 'leaveapplicationportalapproval', 'obapplication', 'otapplicationadv', 'undertime', 'restday', 'word', 'leavecancellation', 'obcancellation', 'undertimecancellation']; //, 'portalreports'
        // array_push($payrollportal, 'undertime', 'restday', 'word', 'leavecancellation', 'obcancellation', 'undertimecancellation');
        break;
    }
    return ['payrollportal' => ['parent' => 26, 'modules' => $payrollportal]];
  } // end functionf

  public function consignment($params)
  {
    $consignment = ['parentconsignment', 'cn', 'co', 'cs'];
    return ['consignment' => ['parent' => 28, 'modules' => $consignment]];
  } //end function

  public function crm($params)
  {
    $crm = ['parentcrm',  'salesgroup', 'seminar', 'exhibit', 'source'];
    return ['crm' => ['parent' => 29, 'modules' => $crm]];
  } //end function

  public function operation($params)
  {
    $companyid = $params['companyid'];
    switch ($this->companysetup->getsystemtype($params)) {
      case 'MMS':
        $operation = ['parentoperation', 'lp',  'othercharges', 'waterreading', 'electricityreading', 'gb', 'mb', 'tenancy']; // remove salesgroup
        break;
      case 'EAPPLICATION':
        $operation = ['parentoperation', 'customer', 'af', 'cp'];
        break;
    }

    return ['operation' => ['parent' => 30, 'modules' => $operation]];
  } //end function

  public function announcement($params)
  {
    $announcement = ['parentannouncement', 'notice', 'event', 'holidayannouncement'];
    switch ($params['companyid']) {
      case 51: //ulitc
        $announcement = ['parentannouncement', 'notice'];
        break;
    }

    return ['announcement' => ['parent' => 22, 'modules' => $announcement]];
  } //end function

  public function branch($params)
  {
    $branch = ['parentbranch', 'branch'];
    return ['branch' => ['parent' => 23, 'modules' => $branch]];
  }


  public function transactionutilities($params)
  {
    $companyid = $params['companyid'];
    switch ($this->companysetup->getsystemtype($params)) {
      case 'SSMS':
        $transactionutilities = ['parenttransactionutilities', 'terms', 'prefix', 'ewtsetup', 'audittrail', 'unposted_transaction', 'forex', 'executionlog', 'changeitem', 'othersettings', 'gradeutility'];
        break;

      case 'AMS':
        switch ($companyid) {
          case 26: //bee healthy
            $transactionutilities = ['parenttransactionutilities', 'terms', 'prefix', 'ewtsetup', 'audittrail', 'unposted_transaction', 'forex', 'executionlog', 'changeitem', 'othersettings', 'uploadingutility', 'apiutility'];
            break;
          case 48: //seastar
            $transactionutilities = ['parenttransactionutilities', 'terms', 'prefix', 'ewtsetup', 'audittrail', 'unposted_transaction', 'forex', 'executionlog', 'othersettings', 'uploadingutility'];
            break;
          default:
            $transactionutilities = ['parenttransactionutilities', 'terms', 'prefix', 'ewtsetup', 'audittrail', 'unposted_transaction', 'forex', 'executionlog', 'changeitem', 'othersettings', 'uploadingutility'];
            break;
        }
        break;

      case 'PAYROLL':
      case 'PAYROLLPORTAL':
      case 'FAMS':
      case 'HRISPAYROLL':
        if ($this->companysetup->getiswindowspayroll($params)) {
          $transactionutilities = ['parenttransactionutilities', 'prefix', 'audittrail', 'executionlog'];
        } else {
          $transactionutilities = ['parenttransactionutilities', 'prefix', 'audittrail', 'executionlog', 'uploadingutility'];
        }
        array_push($transactionutilities, 'moduleapproval');
        break;

      case 'VSCHED':
        $transactionutilities = ['parenttransactionutilities', 'prefix', 'audittrail', 'executionlog', 'uploadingutility'];
        break;

      case 'ATI':
        $transactionutilities = ['parenttransactionutilities', 'terms', 'prefix', 'ewtsetup', 'audittrail', 'forex', 'executionlog', 'othersettings', 'approversetup', 'uploadingutility'];
        break;

      case 'MMS':
        $transactionutilities = ['parenttransactionutilities', 'terms', 'prefix', 'ewtsetup', 'audittrail', 'unposted_transaction', 'forex', 'executionlog', 'othersettings', 'uploadingutility'];
        break;
      case 'MIS':
        $transactionutilities = ['parenttransactionutilities', 'terms', 'prefix', 'audittrail', 'unposted_transaction', 'forex', 'executionlog', 'uploadingutility'];
        break;
      case 'EAPPLICATION':
        $transactionutilities = ['parenttransactionutilities', 'terms', 'prefix',  'audittrail', 'unposted_transaction', 'executionlog'];
        break;
      case 'QUEUING':
        $transactionutilities = ['parenttransactionutilities', 'prefix', 'executionlog'];
        break;

      default:
        switch ($companyid) {
          case 57:
            $transactionutilities = ['parenttransactionutilities', 'prefix', 'audittrail', 'unposted_transaction', 'executionlog', 'othersettings'];
            break;
          case 12: //afti usd
          case 10: //afti
            $transactionutilities = ['parenttransactionutilities', 'terms', 'prefix', 'ewtsetup', 'audittrail', 'unposted_transaction', 'forex', 'executionlog', 'changeitem', 'othersettings', 'uploadingutility', 'poterms', 'updatestd', 'cutoffaccounting'];
            break;
          case 22: //eipi
            $transactionutilities = ['parenttransactionutilities', 'terms', 'prefix', 'ewtsetup', 'audittrail', 'unposted_transaction', 'forex', 'executionlog', 'othersettings', 'uploadingutility'];
            break;
          case 23: //labsol cebu
          case 41: //labsol manila
          case 52: //technolab
            $transactionutilities = ['parenttransactionutilities', 'terms', 'prefix', 'ewtsetup', 'audittrail', 'unposted_transaction', 'forex', 'executionlog', 'changeitem', 'othersettings', 'uploadingutility', 'poterms'];
            break;
          case 35: //aquamax
            $transactionutilities = ['parenttransactionutilities', 'terms', 'prefix', 'audittrail', 'unposted_transaction', 'executionlog', 'othersettings', 'uploadingutility'];
            break;
          // payrollportal
          case 44: //stonepro
          case 51: //ulitc
          case 53: //camera
            $transactionutilities = ['parenttransactionutilities', 'prefix'];
            break;
          case 55: //afli
            $transactionutilities = ['parenttransactionutilities', 'prefix', 'ewtsetup', 'audittrail', 'unposted_transaction',  'executionlog', 'othersettings', 'uploadingutility', 'poterms', 'linearapprover', 'cutoffinventory', 'cutoffaccounting', 'dellogs', 'updatelogs'];
            break;
          default:
            $transactionutilities = ['parenttransactionutilities', 'terms', 'prefix', 'ewtsetup', 'audittrail', 'unposted_transaction', 'forex', 'executionlog', 'othersettings', 'uploadingutility', 'poterms', 'linearapprover', 'cutoffinventory', 'cutoffaccounting', 'dellogs', 'updatelogs'];
            break;
        }
        break;
    }

    switch ($params['companyid']) {
      case 15: //nathina
        array_push($transactionutilities, 'qtybracket', 'pricelist');
        break;
      case 19: //housegem
      case 24: //goodfound
      case 40: //cdo
      case 8: //maxipro
        array_push($transactionutilities, 'coagrouping');
        break;
      case 29: //
        array_push($transactionutilities, 'moduleapproval', 'adashboard');
        break;
    }

    if ($this->companysetup->getrestrictip($params)) {
      array_push($transactionutilities, 'ipsetup');
    }

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
    if ($isproject) {
      array_push($accountutilities, 'projectaccess');
    }
    array_push($accountutilities, 'rg');

    return ['accountutilities' => ['parent' => 88, 'modules' => $accountutilities]];
  } //end function

  public function dashboard($params)
  {
    $announcement = ['parentdashboard'];
    return ['dashboard' => ['parent' => 27, 'modules' => $announcement]];
  } //end function


  public function documentmanagement($params)
  {
    $documentmanagement = [
      'parentdocumentmanagement',
      'documententry',
      'issueslist',
      'industrylist',
      'documenttype',
      'detailslist',
      'divisionlist',
      'statuslist',
      'statusaccesslist'
    ];
    return ['documentmanagement' => ['parent' => 31, 'modules' => $documentmanagement]];
  } //end function

  public function pos($params)
  {
    switch ($params['companyid']) {
      case 56:
        $pos = ['parentpos', 'branchledger', 'pospaymentsetup', 'extraction', 'otherextraction', 'sjpos', 'cmpos', 'crpos', 'cardtypes', 'paymenttype'];
        break;
      default:
        $pos = ['parentpos', 'branchledger', 'pospaymentsetup', 'extraction', 'sjpos', 'cmpos', 'cardtypes', 'paymenttype'];
        break;
    }

    if ($this->companysetup->getispricescheme($params)) {
      array_push($pos, 'pa', 'pp');
      //pos_log
    }

    array_push($pos, 'pos_log');

    return ['pos' => ['parent' => 32, 'modules' => $pos]];
  } //end function

  public function vehiclescheduling($params)
  {
    $vehiclescheduling = ['parentvehiclescheduling', 'vehiclesched', 'vr', 'vrapproval', 'vl'];
    return ['vehiclescheduling' => ['parent' => 33, 'modules' => $vehiclescheduling]];
  } //end function

  public function fams($params)
  {
    $fams = ['parentfams', 'issueitems', 'returnitems', 'gp', 'gatepassreturn', 'fc', 'gpal'];

    if ($this->companysetup->getsystemtype($params) == 'FAMS') {
      array_push($fams, 'postdep');
    }
    return ['fams' => ['parent' => 34, 'modules' => $fams]];
  } //end function

  public function production($params)
  {
    switch ($params['companyid']) {
      case 24: //goodfound
        $prod = ['parentproduction', 'rm', 'rn', 'finishgoodsentry'];
        break;
      case 27: //nte
      case 36: //rozlab
        $prod = ['parentproduction', 'bom', 'jp', 'pg'];
        break;
      case 50: //unitech
        $prod = ['parentproduction', 'pt', 'pe', 'mi', 'pn', 'pk'];
        //$prod = ['parentproduction', 'prodinstruction', 'prodorder', 'rm', 'finishgoodsentry'];
        break;
      default:
        $prod = ['parentproduction', 'prodinstruction', 'prodorder', 'tr', 'trapproval', 'rm', 'stages', 'finishgoodsentry', 'pr', 'ts'];
        break;
    }
    return ['production' => ['parent' => 35, 'modules' => $prod]];
  }

  public function kwhmonitoring($params)
  {
    $kwhmonitoring = ['parentkwhmonitoring', 'kwhratesetup', 'powerconsumption', 'pw'];
    return ['kwhmonitoring' => ['parent' => 36, 'modules' => $kwhmonitoring]];
  }

  public function waterbilling($params)
  {
    $waterbilling = ['parentwaterbilling', 'wn', 'wm'];
    return ['waterbilling' => ['parent' => 36, 'modules' => $waterbilling]];
  }

  public function serviceticketing($params)
  {
    $serviceticketing = ['parentserviceticketing', 'ta', 'work_order'];
    return ['serviceticketing' => ['parent' => 37, 'modules' => $serviceticketing]];
  } //end function


  public function lending($params)
  {
    $lending = ['parentlending', 'le'];
    return ['lending' => ['parent' => 38, 'modules' => $lending]];
  } //end function

  public function cashier($params)
  {
    $cashier = ['parentcashier', 'ce', 'rc', 'dx', 'tc', 'dlcoll', 'endofday'];
    return ['cashier' => ['parent' => 39, 'modules' => $cashier]];
  } //end function
  public function othertransaction($params)
  {
    $othertransaction = ['parentothertransaction'];

    switch ($params['companyid']) {
      case 53: // camera
        array_push($othertransaction, 'violation');
        break;
    }

    return ['othertransaction' => ['parent' => 40, 'modules' => $othertransaction]];
  } //end function
  public function barangayoperation($params)
  {
    $barangay = ['parentbarangay', 'bd', 'bc', 'bt', 'cr'];
    return ['barangayoperation' => ['parent' => 41, 'modules' => $barangay]];
  } //end function

  public function pcf($params)
  {
    $pcf = ['parentpcf', 'pces', 'reasoncodesetup', 'pcfcur', 'px'];
    return ['pcf' => ['parent' => 42, 'modules' => $pcf]];
  } //end function

  public function taskmonitoring($params)
  {
    $tm = ['parenttm', 'tm', 'tasktype', 'tk', 'dy', 'taskcategory'];
    return ['taskmonitoring' => ['parent' => 43, 'modules' => $tm]];
  } //end function

  public function queuing($params)
  {
    $queuing = ['parentqs', 'counter', 'service', 'display', 'ticketing', 'ctr', 'closequeue'];
    return ['queuing' => ['parent' => 44, 'modules' => $queuing]];
  } //end function

  public function barangaysetup($params)
  {
    $barangaysetup = ['parentbmssetup', 'brgyofficial', 'streetsetup', 'clearancetype', 'businesstype', 'bonafide', 'trutype'];
    return ['barangaysetup' => ['parent' => 45, 'modules' => $barangaysetup]];
  } //end function


} // end class
