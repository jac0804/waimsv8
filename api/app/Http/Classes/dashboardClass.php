<?php

namespace App\Http\Classes;

/*
use Session;*/

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Classes\stockClass;
use App\Http\Classes\othersClass;
use App\Http\Classes\clientClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\headClass;
use App\Http\Classes\Logger;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\builder\lookupClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\mobile\modules\inventoryapp\inventory;
use App\Http\Classes\common\payrollcommon;
use Exception;
use Throwable;
use Session;

use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;


class dashboardClass
{
  private $othersClass;
  private $coreFunctions;
  private $headClass;
  private $logger;
  private $lookupClass;
  private $companysetup;
  private $config = [];
  private $sqlquery;
  private $tabClass;
  private $fieldClass;
  private $payrollcommon;

  public function __construct()
  {
    $this->othersClass = new othersClass;
    $this->coreFunctions = new coreFunctions;
    $this->headClass = new headClass;
    $this->logger = new Logger;
    $this->lookupClass = new lookupClass;
    $this->companysetup = new companysetup;
    $this->sqlquery = new sqlquery;
    $this->tabClass = new tabClass;
    $this->fieldClass = new txtfieldClass;
    $this->payrollcommon = new payrollcommon;
  }

  public function sbc($params)
  {
    $this->config['params'] = $params;
    $access = $this->othersClass->getAccess($params['user']);
    $this->config['access'] = json_decode(json_encode($access), true);
    if ($this->companysetup->getrestrictip($params)) {
      $ipaccess = $this->config['access'][0]['attributes'][3722]; //restrict ip access
      if ($ipaccess == 1) {
        $this->config['allowlogin'] = $this->othersClass->checkip($params);
        $this->coreFunctions->LogConsole("Your IP - '" . $params['ip'] . "'");
      } else {
        $this->config['allowlogin'] = true;
      }
    }
    return $this;
  }

  private function checksecurity($id)
  {
    $this->config['verifyaccess'] = isset($this->config['access'][0]['attributes'][$id - 1]) ? $this->config['access'][0]['attributes'][$id - 1] : 0;
    if ($this->config['verifyaccess'] == 0) {
      return false;
    } else {
      return true;
    }
  }

  private function checkapprover($config)
  {
    $approver = $this->coreFunctions->datareader("select isapprover as value from employee where empid=?", [$config['params']['adminid']]);
    if ($approver == "1") {
      return true;
    } else {
      return false;
    }
  }


  private function checksupervisor($config)
  {
    $supervisor = $this->coreFunctions->datareader("select issupervisor as value from employee where empid=?", [$config['params']['adminid']]);
    if ($supervisor == "1") {
      return true;
    } else {
      return false;
    }
  }
  private function check_otapprover($config)
  {
    $otapprover = $this->coreFunctions->datareader("select isotapprover as value from employee where empid=?", [$config['params']['adminid']]);
    if ($otapprover == "1") {
      return true;
    } else {
      return false;
    }
  }


  private function checkassignee($config)
  {
    $user = $config['params']['user'];
    $userid = $this->coreFunctions->datareader("select userid as value from useraccess where username = ? 
              union all select clientid as value from client where email = ?", [$user, $user]);

    $usertodo = $this->coreFunctions->datareader("select distinct value from (
                select createby as value from transnumtodo where createby = ?
                union all
                select createby as value from cntnumtodo where createby = ?) as a", [$userid, $userid]);
    if ($userid == $usertodo) {
      return true;
    } else {
      return false;
    }
  }


  public function loadform()
  {
    if ($this->config['params']['logintype'] == '62608e08adc29a8d6dbc9754e659f125') {
      switch ($this->config['params']['companyid']) {
        case 58: //cdo
        case 51: //ulitc
        case 53: //camera
        case 44: //stonepro
          $this->dashboardclientCDO();
          break;
        default:
          $this->dashboardclienttable();
          break;
      }
    } elseif ($this->config['params']['logintype'] == '4e02771b55c0041180efc9fca6e04a77') {
      $this->dashboardapplicanttable();
    } else {
      $this->dashboardwaims();
    }
    return $this;
  } //end function

  public function dashboardapplicanttable()
  {
    $companyid = $this->config['params']['companyid'];
    if ($companyid == 58) {
      $this->loadQuestionnaire();
    }


    $sorting = ['actionlist', 'dailynotif', 'sbcgraph', 'sbclist', 'calendar'];
    $this->config['return'] = [
      'status' => true,
      'msg' => 'Loaded Success',
      'obj' => $this->config,
      'sorting' => $sorting
    ];
  }

  public function dashboardclienttable()
  {
    ini_set('max_execution_time', -1);
    $systemtype = $this->companysetup->getsystemtype($this->config['params']);
    $companyid = $this->config['params']['companyid'];
    $istodo = $this->companysetup->getistodo($this->config['params']);

    if ($companyid == 29) { //sbc
      if ($this->checksecurity(5564)) $this->dailytask();
    }

    if ($istodo || $systemtype == 'ATI') {
      if ($systemtype == 'ATI') {
        $cd_access = $this->checksecurity(4010);
        $po_access = $this->checksecurity(4009);

        if ($cd_access || $po_access) {
          $this->forapprovallist();
        }
      }

      // if($companyid !=29){
      //   $this->todo();
      //   if ($this->checkassignee($this->config)) {
      //     $this->pendingtodo();
      //   }
      // }
    }
    if ($companyid == 53) { // camera
      if ($this->checksecurity(5091)) {
        $this->violation();
      }
      $this->notice();
    }

    if ($companyid == 29) { //sbc
      if ($this->checkapprover($this->config)) {
        $this->currenttimerec($this->config);
      }
    }

    if ($this->companysetup->getistaskmonitor($this->config['params'])) {
      if ($this->checksecurity(5483)) {
        $this->gapplications();
      }
    }

    if ($companyid == 29) $this->taskassign();

    if ($this->checkapprover($this->config)) {
      vehicleschedulehere:
      switch ($systemtype) {
        case 'PAYROLLPORTAL':
        case 'PAYROLL':
        case 'HRISPAYROLL':
        case 'AIMSPAYROLL':
          if ($companyid != 29) {
            if ($this->companysetup->getispendingapp($this->config['params'])) $this->gapplications();
          }
          if ($this->companysetup->getispayrollportal($this->config['params'])) {
            if ($this->checksecurity(3628) || $this->checksecurity(4841)) {
              if ($this->config['params']['logintype'] == '9bc65c2abec141778ffaa729489f3e87') {
                $levelid = $this->coreFunctions->getfieldvalue("useraccess", "accessid", "username=?", [$this->config['params']['user']]);
              } else {
                $levelid = $this->coreFunctions->getfieldvalue("client", "userid", "email=?", [$this->config['params']['user']]);
              }
              $otdashboard = $this->coreFunctions->getfieldvalue("left_menu", "doc", "levelid=? and module = 'OT APPLICATION' ", [$levelid]);
              if ($otdashboard == 'otapplication') {
                $this->otapplication();
              } else {
                $this->otapplicationadvance();
              }
            }
            if ($this->checksecurity(3627)) $this->obapplication();

            if ($this->checksecurity(3629)) $this->leaveapplication($this->config);

            if ($this->checksecurity(4801)) $this->undertimeapplication($this->config);

            if ($this->config['params']['companyid'] == 58) { //cdo
              if ($this->checksecurity(5155)) {
                $this->restday();
              }
              if ($this->checksecurity(5159)) {
                $this->word();
              }
            }

            if ($this->checksecurity(3630)) $this->loanapplication();

            if ($this->config['params']['companyid'] == 58) { //cdo
              if ($this->checksecurity(5158)) {
                $this->leavecancellation();
              }
              if ($this->checksecurity(5179)) {
                $this->undertimecancellation();
              }
              if ($this->checksecurity(5200)) {
                $this->obcancellation();
              }
              if ($this->checksecurity(5358)) {
                $this->itinerary();
              }
            }

            if ($this->config['params']['companyid'] == 53) { //camera
              if ($this->checksecurity(5032)) {
                $this->obapplicationInitial();
              }

              $ob = $this->checksecurity(5033);
              $leave = $this->checksecurity(5034);
              $ot = $this->checksecurity(5035);
              $changeshf = $this->checksecurity(5036);
              $loan = $this->checksecurity(5037);

              if (
                $ob || $leave || $ot || $changeshf || $loan
              ) {
                $this->allapprovedapplication($this->config);
              }
            }

            if ($this->checksecurity(4605)) $this->changeshiftapplication();
          } // end of ispayrollportal
          break;
        case 'HRIS':
          if ($this->checksecurity(3629)) $this->leaveapplication($this->config);

          break;
        case 'REALESTATE':
        case 'AMS':
          if ($this->checksecurity(878)) {
            $this->modulecount('CV', 'light-blue-3', true);
          }

          if ($this->checksecurity(5031)) {
            $this->modulecount('CR', 'light-blue-3', true);
          }

          if ($this->checksecurity(879)) {
            $this->ap();
          }
          if ($this->checksecurity(880)) {
            $this->ar();
          }
          break;

        case 'ATI':
          if ($this->checksecurity(3722)) {
            $this->vehiclerequest();
          }
          if ($this->checksecurity(3715)) {
            $this->vehiclerequestwithoutvehicle();
          }
          if ($this->checksecurity(3716)) {
            $this->vehicleschedule();
          }
          if ($this->checksecurity(2998)) {
            $this->vehicleschedulecalendar();
          }
          if ($this->checksecurity(4166)) {
            $this->canvassforpo();
          }
          break;
        default:
          break;
      }
    } else {
      if ($this->checksecurity(4166)) {
        $this->canvassforpo();
      }

      switch ($systemtype) {
        case 'PAYROLLPORTAL':
        case 'HRISPAYROLL': //cdohris
          switch ($companyid) {
            case 44: //STONEPRO
            case 53: //CAMERA SOUND
            case 58: //CDOHRIS
            case 51: //ULITC
              if ($this->companysetup->getispayrollportal($this->config['params'])) {
                $supervisor = $this->checksupervisor($this->config);
                if ($companyid == 53) { // camera
                  $otsupervisor = $this->check_otapprover($this->config);
                  if ($otsupervisor) {
                    if ($this->checksecurity(4841)) {
                      $this->otapplicationadvance();
                    }
                  }
                } else {
                  if ($supervisor) {
                    $this->gapplications();
                    if ($this->checksecurity(4841)) {
                      $this->otapplicationadvance();
                    }
                  }
                }

                if ($supervisor) {

                  if ($this->checksecurity(3627)) {
                    $this->obapplication();
                  }
                  if ($this->checksecurity(3629)) {
                    $this->leaveapplication($this->config);
                  }

                  if ($this->checksecurity(4801)) {
                    $this->undertimeapplication($this->config);
                  }

                  if ($this->config['params']['companyid'] == 58) { //cdo
                    if ($this->checksecurity(5155)) {
                      $this->restday();
                    }
                    if ($this->checksecurity(5159)) {
                      $this->word();
                    }
                  }

                  if ($this->checksecurity(3630)) {
                    $this->loanapplication();
                  }


                  if ($this->config['params']['companyid'] == 58) { //cdo
                    if ($this->checksecurity(5158)) {
                      $this->leavecancellation();
                    }
                    if ($this->checksecurity(5179)) {
                      $this->undertimecancellation();
                    }
                    if ($this->checksecurity(5200)) {
                      $this->obcancellation();
                    }
                  }

                  if ($companyid == 53) { //camera
                    if ($this->checksecurity(5032)) {
                      $this->obapplicationInitial();
                    }

                    $ob = $this->checksecurity(5033);
                    $leave = $this->checksecurity(5034);
                    $ot = $this->checksecurity(5035);
                    $changeshf = $this->checksecurity(5036);
                    $loan = $this->checksecurity(5037);

                    if ($ob || $leave || $ot || $changeshf || $loan) {
                      $this->allapprovedapplication($this->config);
                    }
                  }

                  if ($this->checksecurity(4605)) {
                    $this->changeshiftapplication();
                  }
                }
              } // end of ispayrollportal



              break;
          }

          break;
        case 'ATI':
          goto vehicleschedulehere;
          break;
      }
    }

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        if ($this->checksecurity(1734)) {
          $this->purchasegraph();
        }
        if ($this->checksecurity(1735)) {
          $this->salesgraph();
        }

        if ($this->checksecurity(4012)) {
          $this->MonthlySalesgraph();
        }

        if ($this->checksecurity(4011)) {
          $this->BranchperItemgraph();
        }

        if ($this->checksecurity(3807)) {
          $this->MTDgraph();
        }

        if ($this->checksecurity(3863)) {
          $this->YTDgraph();
        }

        break;
    }

    switch ($systemtype) {
      case 'EAPPLICATION':
        if ($this->checksecurity(1735)) {
          $this->salesgraph();
        }

        if ($this->checksecurity(880)) {
          $this->ar();
          $this->oversixtyage();
        }

        if ($this->checksecurity(4101)) {
          $this->eventandholidaycalendar();
        }
        break;
      case 'AIMS':
        if ($companyid == 47) { // kstar
          if ($this->checksecurity(4877)) {
            $this->modulecount('SO', 'green-4', false);
          }
          if ($this->checksecurity(4857)) {
            $this->modulecount('SJ', 'green-5', true);
          }
          if ($this->checksecurity(4858)) {
            $this->modulecount('PO', 'blue-3', false);
          }
          if ($this->checksecurity(876)) {
            $this->modulecount('RR', 'blue-4', true);
          }
          if ($this->checksecurity(4894)) {
            $this->modulecount('AJ', 'green-5', true);
          }
          if ($this->checksecurity(4895)) {
            $this->modulecount('TS', 'red-5', true);
          }

          if ($this->checksecurity(1735)) {
            $this->salesgraph();
          }

          if ($this->checksecurity(4859)) {
            $this->kstarmonthlysales();
          }
        }

        if ($this->checksecurity(5483)) {
          $this->gapplications();
        }
        break;
      default:
        $this->eventandholidaycalendar();
        break;
    }


    // $this->timerec();
    switch ($systemtype) {
      case 'PAYROLLPORTAL':
      case 'PAYROLL':
      case 'HRIS':
      case 'HRISPAYROLL':
      case 'AIMSPAYROLL';
        $this->timecardcalendar();
        $this->dailyNotif();
        if ($systemtype != 'HRIS') {
          $this->actionlist();
        }
        break;
    }

    switch ($systemtype) {
      case 'EAPPLICATION':
        if ($this->checksecurity(4100)) {
          $this->notice();
        }
        break;
      default:

        if ($companyid != 53) {
          $this->notice();
        }
        break;
    }

    if ($this->checksecurity(3894)) {
      $this->actionlist();
    }

    if ($companyid == 47) { //kstar
      if ($this->checksecurity(4842)) {
        $this->fordel();
      }
    }

    // $this->taskassign();

    // if ($companyid == 29) { //sbc
    //   if ($this->checksecurity(5564)) {
    //     $this->dailytask();
    //   }
    // }

    $sorting = ['actionlist', 'dailynotif', 'sbclist', 'sbcgraph', 'calendar'];
    $this->config['return'] = [
      'status' => true,
      'msg' => 'Loaded Success',
      'obj' => $this->config,
      'sorting' => $sorting
    ];
  } //end function

  public function dashboardwaims()
  {

    ini_set('max_execution_time', -1);
    $systemtype = $this->companysetup->getsystemtype($this->config['params']);
    $istodo = $this->companysetup->getistodo($this->config['params']);
    $companyid = $this->config['params']['companyid'];
    $ismain = $this->coreFunctions->getfieldvalue("center", "ismain", "code = '" . $this->config['params']['center'] . "'");

    if ($istodo || $systemtype == 'ATI') {
      if ($systemtype == 'ATI') {
        $cd_access = $this->checksecurity(4010);
        $po_access = $this->checksecurity(4009);

        if ($cd_access || $po_access) {
          $this->forapprovallist();
        }
      }

      $this->todo();
      if ($this->checkassignee($this->config)) {
        $this->pendingtodo();
      }
    }

    switch ($systemtype) {
      case 'PAYROLLPORTAL':
      case 'PAYROLL':
      case 'HRIS':
      case "HRISPAYROLL":
        $this->dailyNotif();
        break;
      case 'MIS':
        if ($this->checksecurity(876)) {
          $this->modulecount('RR', 'red-3', true);
        }
        if ($this->checksecurity(877)) {
          $this->modulecount('DM', 'blue-3', true);
        }
        if ($this->checksecurity(3719)) {
          $this->incomingdel();
        }
        break;
      case 'REALESTATE':
      case 'AMS':
        switch ($companyid) {
          case 35: //aquamax
            if ($this->checksecurity(878)) {
              $this->modulecount('CR', 'light-blue-3', true);
            }
            break;
          default:
            if ($this->checksecurity(878)) {
              $this->modulecount('CV', 'light-blue-3', true);
            }

            if ($this->checksecurity(5031)) {
              $this->modulecount('CR', 'light-blue-3', true);
            }

            if ($this->checksecurity(879)) {
              $this->ap();
            }
            break;
        }
        if ($this->checksecurity(880)) {
          $this->ar();
        }
        break;
      case 'EAPPLICATION':
        if ($this->checksecurity(878)) {
          $this->modulecount('CR', 'light-blue-3', true);
        }

        if ($this->checksecurity(880)) {
          $this->ar();
          $this->oversixtyage();
        }


        break;
      case 'LENDING':
        if ($this->checksecurity(878)) {
          $this->modulecount('CV', 'light-blue-3', true);
        }
        if ($this->checksecurity(879)) {
          $this->ap();
        }
        if ($this->checksecurity(880)) {
          $this->ar();
        }

        if ($this->checksecurity(5089)) {
          $this->pendingpdc(); //change to postdated checks
          $this->pendingsd(); //salary deduction

        }

        if ($this->checksecurity(5345)) {
          $this->loanappforapproval(); //loan approval list

        }

        break;
      case 'QUEUING':
        $this->hourlyserved();
        $this->servedperservice_pie();
        $this->ticketsummary();
        $this->ticketsummaryperservice();
        break;
      default:
        switch ($companyid) {
          case 57:  //cdofinancing 
            if ($this->checksecurity(5089)) {
              $this->pendingpdc(); //change to postdated checks
            }
            break;
          case  47: //kstar
            if ($this->checksecurity(4877)) {
              $this->modulecount('SO', 'green-4', false);
            }
            if ($this->checksecurity(4857)) {
              $this->modulecount('SJ', 'green-5', true);
            }
            if ($this->checksecurity(4858)) {
              $this->modulecount('PO', 'blue-3', false);
            }
            if ($this->checksecurity(876)) {
              $this->modulecount('RR', 'blue-4', true);
            }
            if ($this->checksecurity(4894)) {
              $this->modulecount('AJ', 'green-5', true);
            }
            if ($this->checksecurity(4895)) {
              $this->modulecount('TS', 'red-5', true);
            }

            if ($this->checksecurity(4859)) {
              $this->kstarmonthlysales();
            }

            if ($this->checksecurity(3719)) {
              $this->incomingdel();
            }
            break;
          case 40: //cdo
            if ($this->checksecurity(876)) {
              $this->modulecount('RR', 'red-3', true);
            }
            if ($this->checksecurity(877)) {
              $this->modulecount('DM', 'blue-3', true);
            }
            if ($this->checksecurity(878)) {
              $this->modulecount('CV', 'light-blue-3', true);
            }
            if ($this->checksecurity(879)) {
              $this->ap();
            }
            if ($this->checksecurity(880)) {
              $this->ar();
            }

            if ($this->checksecurity(3808)) {
              $this->pendingcanvassheet();
            }
            if ($this->checksecurity(4166)) {
              $this->canvassforpo();
            }

            if ($this->checksecurity(4455)) {
              $this->incomingts();
            }

            if ($this->checksecurity(4735)) {
              $this->pendingmc();
            }
            break;
          default:
            if ($this->checksecurity(876)) {
              $this->modulecount('RR', 'red-3', true);
            }
            if ($this->checksecurity(877)) {
              $this->modulecount('DM', 'blue-3', true);
            }
            if ($this->checksecurity(878)) {
              $this->modulecount('CV', 'light-blue-3', true);
            }
            if ($this->checksecurity(879)) {
              $this->ap();
            }
            if ($this->checksecurity(880)) {
              $this->ar();
            }
            if ($this->checksecurity(3808)) {
              $this->pendingcanvassheet();
            }
            if ($this->checksecurity(4166)) {
              $this->canvassforpo();
            }

            if ($this->checksecurity(3719)) {
              $this->incomingdel();
            }

            if ($companyid == 19) { //housegem  
              if ($this->checksecurity(5030)) {
                $this->truckexpiry();
              }
            }

            if ($companyid == 56) { //homeworks 
              if ($this->checksecurity(4455)) {
                $this->incomingts();
              }
            }

            if ($companyid == 29) {
              if ($this->checksecurity(5477)) {
                $this->taskassign();
              }

              if ($this->checksecurity(5564)) {
                $this->dailytask();
              }

              if ($this->checksecurity(5478)) {
                $this->gapplications();
              }
            }

            break;
        } //end switch company
        break;
    }

    if ($this->checkapprover($this->config)) {
      vehicleschedulehere:
      switch ($systemtype) {
        case 'PAYROLLPORTAL':
        case 'PAYROLL':
        case 'HRISPAYROLL':
          if ($this->checksecurity(3628) || $this->checksecurity(4841)) {
            if ($this->config['params']['logintype'] == '9bc65c2abec141778ffaa729489f3e87') {
              $levelid = $this->coreFunctions->getfieldvalue("useraccess", "accessid", "username=?", [$this->config['params']['user']]);
            } else {
              $levelid = $this->coreFunctions->getfieldvalue("client", "userid", "email=?", [$this->config['params']['user']]);
            }
            $otdashboard = $this->coreFunctions->getfieldvalue("left_menu", "doc", "levelid=? and module = 'OT APPLICATION' ", [$levelid]);
            if ($otdashboard == 'otapplication') {
              $this->otapplication();
            } else {
              $this->otapplicationadvance();
            }
          }


          if ($this->checksecurity(3627)) {
            $this->obapplication();
          }

          if ($this->checksecurity(3630)) {
            $this->loanapplication();
          }
          if ($this->checksecurity(3629)) {
            $this->leaveapplication($this->config);
          }
          if ($this->checksecurity(4605)) {
            $this->changeshiftapplication();
          }
          if ($this->checksecurity(4801)) {
            $this->undertimeapplication($this->config);
          }
          break;

        case 'HRIS':
          if ($this->checksecurity(3629)) {
            $this->leaveapplication($this->config);
          }
          break;

        case 'ATI':
          if ($this->checksecurity(3722)) {
            $this->vehiclerequest();
          }
          if ($this->checksecurity(3715)) {
            $this->vehiclerequestwithoutvehicle();
          }
          if ($this->checksecurity(3716)) {
            $this->vehicleschedule();
          }
          if ($this->checksecurity(2998)) {
            $this->vehicleschedulecalendar();
          }

          break;
      }
    } else {
      switch ($systemtype) {
        case 'PAYROLLPORTAL':
          if ($companyid == 44 || $companyid == 53) { //STONEPRO | //CAMERA SOUND
            if ($this->checksupervisor($this->config)) {
              if ($this->checksecurity(4605)) {
                $this->changeshiftapplication();
              }
              if ($this->checksecurity(4801)) {
                $this->undertimeapplication($this->config);
              }
              if ($this->checksecurity(4841)) {
                $this->otapplicationadvance();
              }
            }
          }

          break;
        case 'ATI':
          goto vehicleschedulehere;
          break;
      }
    }

    //start graphs

    switch ($systemtype) {
      case 'PAYROLLPORTAL':
      case 'PAYROLL':
      case 'HRISPAYROLL':
      case 'REALESTATE':
      case 'AMS':
      case 'HRIS':
        if ($companyid == 35) { //aquamax
          if ($this->checksecurity(1735)) {
            $this->salesgraph();
          }
        }
        break;
      case 'EAPPLICATION':
        if ($this->checksecurity(1735)) {
          $this->salesgraph();
        }
        break;
      case 'LENDING':
        if ($companyid == 55) { //afli
          if ($this->checksecurity(5090)) {
            $this->aflicollectiongraph();
          }

          if ($this->checksecurity(5346)) {
            $this->loanreleasegraph();
          }
        }
        break;
      case 'QUEUING':
        break;
      default:
        switch ($companyid) {
          case 57: //financing
            if ($this->checksecurity(1734)) {
              $this->collectiongraph(); //change to collection graph
            }
            break;
          case 47: //kstar
            if ($this->checksecurity(1734)) {
              $this->purchasegraph();
            }
            if ($this->checksecurity(1735)) {
              $this->salesgraph();
            }

            if ($this->checksecurity(4012)) {
              $this->MonthlySalesgraph();
            }

            break;
          default:
            if ($this->checksecurity(1734)) {
              $this->purchasegraph();
            }
            if ($this->checksecurity(1735)) {
              $this->salesgraph();
            }

            if ($this->checksecurity(4012)) {
              $this->MonthlySalesgraph();
            }

            if ($this->checksecurity(4011)) {
              $this->BranchperItemgraph();
            }

            if ($this->checksecurity(3807)) {
              $this->MTDgraph();
            }

            if ($this->checksecurity(3863)) {
              $this->YTDgraph();
            }

            if ($this->checksecurity(4845)) { //Total Ticket Per Type
              $this->ticketpertype();
            }
            if ($this->checksecurity(4846)) { //Caller Gender
              $this->gendercaller();
            }
            if ($this->checksecurity(4847)) { //Total Ticket Status
              $this->totalticketstatus();
            }
            break;
        }

        break;
    }


    switch ($systemtype) {
      case 'EAPPLICATION':
        if ($this->checksecurity(4101)) {
          $this->eventandholidaycalendar();
        }
        break;
      case 'QUEUING':
        break;
      default:
        $this->eventandholidaycalendar();
        break;
    }

    if ($this->checksecurity(3894)) {
      $this->actionlist();
    }

    if ($companyid == 40) { //cdo
      if ($ismain == 1) {
        if ($this->checksecurity(4456)) {
          $this->tsrequest();
        }

        if ($this->checksecurity(4457)) {
          $this->porequest();
        }
      }
    }

    if ($companyid == 47) { //kstar
      if ($this->checksecurity(4842)) {
        $this->fordel();
      }
    }

    switch ($systemtype) {
      case 'EAPPLICATION':
        if ($this->checksecurity(4100)) {
          $this->notice();
        }
        break;
      case 'QUEUING':
        break;
      default:
        $this->notice();
        break;
    }

    // $sorting = ['qcard', 'overview', 'dailynotif', 'actionlist', 'sbcgraph', 'sbclist', 'calendar'];
    $sorting = ['qcard', 'dailynotif', 'actionlist', 'sbcgraph', 'sbclist', 'calendar'];

    if ($companyid == 51) { //ulitc
      unset($this->config['return'], $this->config['return']['calendar']);
      if (($key = array_search('calendar', $sorting)) !== false) {
        unset($sorting[$key]);
      }
    }

    $this->config['return'] = [
      'status' => true,
      'msg' => 'Loaded Success',
      'obj' => $this->config,
      'sorting' => $sorting
    ];
  } //end function

  public function dashboardclientCDO()
  {
    ini_set('max_execution_time', -1);
    $systemtype = $this->companysetup->getsystemtype($this->config['params']);
    $companyid = $this->config['params']['companyid'];
    $istodo = $this->companysetup->getistodo($this->config['params']);

    if ($this->checkapprover($this->config) || $this->companysetup->getispendingapp($this->config['params'])) {
      $this->gapplications();
    } else {
      $supervisor = $this->checksupervisor($this->config);
      if ($supervisor) {
        $this->gapplications();
      }
    }

    if ($companyid != 51) { //ulitc
      $this->eventandholidaycalendar();
    }
    $this->timecardcalendar();
    $this->dailyNotif();
    $this->actionlist();
    if ($companyid != 58) {
      $this->notice();
    }
    if ($companyid == 53) {
      if ($this->checksecurity(5091)) {
        $this->violation();
      }
      $supervisor = $this->checksupervisor($this->config);
      $approver = $this->checkapprover($this->config);
      if ($supervisor || $approver) {
        // $this->allapprovedapplication($this->config);
        $this->allapprovedapplication_test($this->config);
      }
    }

    if ($this->checksecurity(3894)) {
      $this->actionlist();
    }

    $sorting = ['actionlist', 'sbclist', 'dailynotif', 'sbcgraph', 'calendar'];
    $this->config['return'] = [
      'status' => true,
      'msg' => 'Loaded Success',
      'obj' => $this->config,
      'sorting' => $sorting
    ];
  } //end function


  public function actionlist()
  {
    $buttons1 = [];
    switch ($this->config['params']['companyid']) {
      case 10: //afti
        $buttons1 = [
          'obj1' => ['label' => 'Detailed Sales Report', 'todo' => ['action' => 'detailed_sales_db', 'lookupclass' => 'mancom_reports', 'access' => 'view', 'type' => 'dashboardprinting']],
          'obj2' => ['label' => 'Sales Report Per Sales Group', 'todo' => ['action' => 'sales_per_sales_group_per_person_db', 'lookupclass' => 'mancom_reports', 'access' => 'view', 'type' => 'dashboardprinting']],
          'obj3' => ['label' => 'MTD Sales Report', 'todo' => ['action' => 'mtd_sales_report_db', 'lookupclass' => 'mancom_reports', 'access' => 'view', 'type' => 'dashboardprinting']],
          'obj4' => ['label' => 'YTD Sales Report', 'todo' => ['action' => 'ytd_sales_report_db', 'lookupclass' => 'mancom_reports', 'access' => 'view', 'type' => 'dashboardprinting']]
        ];
        break;
      case 55:
        $buttons1 = [
          'obj1' => ['label' => 'Detailed Sales Report', 'todo' => ['action' => 'detailed_sales_db', 'lookupclass' => 'mancom_reports', 'access' => 'view', 'type' => 'dashboardprinting']],
          'obj2' => ['label' => 'Sales Report Per Sales Group', 'todo' => ['action' => 'sales_per_sales_group_per_person_db', 'lookupclass' => 'mancom_reports', 'access' => 'view', 'type' => 'dashboardprinting']]
        ];
        break;
      case 44: //stonepro
      case 51: //ulitc
      case 53: //camera
      case 58: //cdo
        $payslip = $this->checksecurity(3429);
        if ($payslip) {
          $buttons1['obj1'] =  ['label' => 'Pay Slip', 'todo' => ['action' => 'pay_slip', 'lookupclass' => 'payroll_reports', 'access' => 'view', 'type' => 'dashboardprinting']];
        }
        break;
      case 29: //sbc
        $payslip = $this->checksecurity(3429);
        if ($payslip) {
          $buttons1['obj1'] =  ['label' => 'Pay Slip', 'todo' => ['action' => 'pay_slip', 'lookupclass' => 'payroll_reports', 'access' => 'view', 'type' => 'dashboardprinting']];
        }
        $dailytask = $this->checksecurity(5586);
        if ($dailytask) {
          $buttons1['obj2'] =  ['label' => 'Daily Task Report', 'todo' => ['action' => 'dailytask_report', 'lookupclass' => 'other_reports', 'access' => 'view', 'type' => 'dashboardprinting']];
        }
        $dailytask = $this->checksecurity(5585);
        if ($dailytask) {
          $buttons1['obj3'] =  ['label' => 'Task Monitoring Report', 'todo' => ['action' => 'task_monitoring_report', 'lookupclass' => 'other_reports', 'access' => 'view', 'type' => 'dashboardprinting']];
        }
        break;
    }

    $this->config['actionlist']['View Report'] = $buttons1;
  }

  public function pendingcanvassheet()
  {
    $center = $this->config['params']['center'];
    $getcols = ['docno', 'dateid'];
    $cols = $this->tabClass->createdoclisting($getcols, []);
    $qry = "select head.docno, stock.trno, date(head.dateid) as dateid, head.clientname from cdhead as head left join cdstock as stock on stock.trno=head.trno where stock.status=0 group by head.docno, stock.trno, dateid, head.clientname union all select head.docno, stock.trno, date(head.dateid) as dateid, head.clientname from hcdhead as head left join hcdstock as stock on stock.trno=head.trno where stock.status=0 group by head.docno, stock.trno, dateid, head.clientname";
    $data = $this->coreFunctions->opentable($qry);
    $this->config['sbclist']['pendingcanvassheet'] = ['cols' => $cols, 'data' => $data, 'title' => 'Pending Canvass Sheet'];
  }

  public function incomingdel()
  {
    $center = $this->config['params']['center'];
    $getcols = ['docno', 'loc2', 'loc', 'dateid'];
    $cols = $this->tabClass->createdoclisting($getcols, []);
    $cols[1]['label'] = 'Destination WH';
    $cols[2]['label'] = 'Source WH';

    $fields = ['release', 'ourref'];
    $fields = [];
    $col1 = $this->fieldClass->create($fields);
    $paramsdata = $this->coreFunctions->opentable("SELECT 'XXX' as ourref");


    $qry = "select head.trno, head.docno, date(head.dateid) as dateid, head.clientname as loc2, wh.clientname as loc from lahead as head left join cntnum on cntnum.trno=head.trno left join client as wh on wh.client=head.wh where head.doc='TS' and head.lockdate is not null and cntnum.center='" . $center . "'";
    $data = $this->coreFunctions->opentable($qry);
    $this->config['sbclist']['incomingdel'] = ['cols' => $cols, 'data' => $data, 'title' => 'Incoming (Unposted) Deliveries', 'txtfield' => ['col1' => $col1], 'paramsdata' => $paramsdata[0], 'bgcolor' => 'bg-red', 'textcolor' => 'white'];
  }

  public function fordel()
  {
    $center = $this->config['params']['center'];
    $getcols = ['dateid', 'docno', 'listdeldate', 'clientname'];
    $cols = $this->tabClass->createdoclisting($getcols, []);
    $cols[3]['label'] = 'Customer Name';

    $fields = [];
    $col1 = $this->fieldClass->create($fields);
    $paramsdata = $this->coreFunctions->opentable("SELECT 'XXX' as ourref");

    $qry = "select head.trno, head.docno, date(head.dateid) as dateid,head.deldate, head.clientname 
    from lahead as head left join cntnum on cntnum.trno=head.trno 
    where head.doc='SJ' and cntnum.center='" . $center . "' and head.deldate between now() and date_add(now(),interval 14 day)
    union all
    select head.trno, head.docno, date(head.dateid) as dateid,head.deldate, head.clientname 
    from glhead as head left join cntnum on cntnum.trno=head.trno 
    where head.doc='SJ' and cntnum.center='" . $center . "' and head.deldate between now() and date_add(now(),interval 14 day)";
    $data = $this->coreFunctions->opentable($qry);
    $this->config['sbclist']['fordel'] = ['cols' => $cols, 'data' => $data, 'title' => 'Outgoing Deliveries', 'txtfield' => ['col1' => $col1], 'paramsdata' => $paramsdata[0]];
  }
  public function pendingmc()
  {
    $center = $this->config['params']['center'];
    $getcols = ['dateid', 'docno', 'clientname', 'amount']; // , 'loc2', 'loc',
    $cols = $this->tabClass->createdoclisting($getcols, []);
    // $cols[1]['label'] = 'Destination WH';
    $cols[2]['label'] = 'Client Name';

    $fields = ['release', 'ourref'];
    $fields = [];
    $col1 = $this->fieldClass->create($fields);
    $paramsdata = $this->coreFunctions->opentable("SELECT 'XXX' as ourref");


    $qry = "
      select 
      head.isok,
      date(head.dateid) as dateid,
      head.docno,
      c.clientname,
      head.trnxtype,
      format(ifnull(detail.amount,0),2) as amount
      from hmchead as head
      left join hmcdetail as detail on detail.trno=head.trno
      left join client as c on c.clientid=head.clientid
      left join transnum as num on num.trno=head.trno
      where head.dateid <=now() and head.crtrno=0 and num.center='" . $center . "'
      order by head.dateid desc limit 20";
    $data = $this->coreFunctions->opentable($qry);
    $this->config['sbclist']['pendingmc'] = ['cols' => $cols, 'data' => $data, 'title' => 'Pending MC', 'txtfield' => ['col1' => $col1], 'paramsdata' => $paramsdata[0]];
  }



  public function loanappforapproval()
  {
    $center = $this->config['params']['center'];
    $companyid = $this->config['params']['companyid'];
    $getcols = ['action', 'dateid', 'docno', 'clientname', 'reqtype', 'amount']; // , 'loc2', 'loc',

    $stockbuttons = ['view'];


    foreach ($getcols as $key => $value) {
      $$value = $key;
    }

    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    // $cols[1]['label'] = 'Destination WH';
    $cols[$dateid]['label'] = 'Application Date';
    $cols[$docno]['label'] = 'Application #';
    $cols[$clientname]['label'] = 'Borrower';
    $cols[$reqtype]['label'] = 'Loan Type';
    $cols[$action]['btns']['view']['lookupclass'] = 'jumptableentry';

    $fields = ['release', 'ourref'];
    $fields = [];
    $col1 = $this->fieldClass->create($fields);
    $paramsdata = $this->coreFunctions->opentable("SELECT 'XXX' as ourref");

    $qry = "
      select 
      date(head.dateid) as dateid, 
      head.docno,head.trno,
      head.clientname,
      req.reqtype, 
      info.amount,'module/lending/le' as url
      from eahead as head
      left join eainfo as info on info.trno=head.trno
      left join reqcategory as req on req.line=head.planid
      left join transnum as num on num.trno = head.trno
      where num.postdate is null and head.lockdate is not null and num.center='" . $center . "'
      order by head.dateid desc limit 20";
    $data = $this->coreFunctions->opentable($qry);
    $this->config['sbclist']['loanappforapproval'] = ['cols' => $cols, 'data' => $data, 'title' => 'For Approvals', 'txtfield' => ['col1' => $col1], 'bgcolor' => 'bg-blue-4', 'textcolor' => 'white', 'paramsdata' => $paramsdata[0]];
  }

  public function pendingpdc()
  {
    $center = $this->config['params']['center'];
    $companyid = $this->config['params']['companyid'];
    $getcols = ['dateid', 'checkdate', 'checkno', 'docno', 'clientname', 'amount']; // , 'loc2', 'loc',
    $stockbuttons = [];
    if ($companyid == 55) {
      $getcols = ['action', 'dateid', 'checkdate', 'checkno', 'docno', 'clientname', 'amount']; // , 'loc2', 'loc',
      $stockbuttons = ['view'];
    }

    foreach ($getcols as $key => $value) {
      $$value = $key;
    }

    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    // $cols[1]['label'] = 'Destination WH';
    $cols[$docno]['label'] = 'Doc#';
    $cols[$dateid]['label'] = 'Collection Date';
    if ($companyid == 55) {
      $cols[$action]['btns']['view']['lookupclass'] = 'jumptableentry';
    }
    $fields = ['release', 'ourref'];
    $fields = [];
    $col1 = $this->fieldClass->create($fields);
    $paramsdata = $this->coreFunctions->opentable("SELECT 'XXX' as ourref");

    $qry = "
      select
      date(head.dateid) as dateid,
      head.docno,
      head.clientname,
      detail.checkno,
      format(ifnull(detail.amount,0),2) as amount,detail.checkdate,'headtable/lending/postingpdc' as url
      from hrchead as head left join hrcdetail as detail on detail.trno = head.trno
      left join client as c on c.client=head.client
      left join transnum as num on num.trno=head.trno
      where detail.ortrno =0 and detail.checkdate <=now() and num.center='" . $center . "'
      order by head.dateid desc limit 20";
    $data = $this->coreFunctions->opentable($qry);
    $this->config['sbclist']['pendingpdc'] = ['cols' => $cols, 'data' => $data, 'title' => 'Postdated Checks', 'txtfield' => ['col1' => $col1], 'bgcolor' => 'bg-blue-4', 'textcolor' => 'white', 'paramsdata' => $paramsdata[0]];
  }

  public function pendingsd()
  {
    $center = $this->config['params']['center'];
    $companyid = $this->config['params']['companyid'];
    $getcols = ['action', 'dateid', 'sbu', 'docno', 'clientname', 'amount']; // , 'loc2', 'loc',
    $stockbuttons = ['view'];

    foreach ($getcols as $key => $value) {
      $$value = $key;
    }

    $sbu = '';
    if (isset($this->config['params']['addedparams'])) $sbu = $this->config['params']['addedparams'][0];

    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$docno]['label'] = 'Doc#';
    $cols[$action]['btns']['view']['lookupclass'] = 'jumptableentry';
    $cols[$clientname]['label'] = 'Borrower';
    $fields = [['sbu', 'refresh']];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'refresh.action2', 'pendingsd');
    data_set($col1, 'refresh.addedparams', ['sbu']);
    data_set($col1, 'checkno.readonly', false);
    $paramsdata = $this->coreFunctions->opentable("SELECT '" . $sbu . "' as sbu");

    $sbufilter = '';
    if ($sbu != '') {
      $sbufilter = " and r.category = '" . $sbu . "'";
    }

    $qry = "
      select
      date(head.dateid) as dateid,
      head.docno,head.trno,
      c.clientname,
      format(ifnull(sum(head.db),0),2) as amount,'headtable/lending/postingsd' as url,ifnull(r.category,'')  as sbu
      from arledger as head left join cntnum as num on num.trno = head.trno
      left join heahead as app on app.trno = num.dptrno
      left join heainfo as info on info.trno = app.trno
      left join client as c on c.clientid=head.clientid
      left join reqcategory as r on r.line = info.sbuid
      where  num.dptrno<> 0 and head.dateid <=now() and head.bal <>0 and num.center='" . $center . "' and info.isselfemployed =1 $sbufilter
      group by head.dateid,head.docno,head.trno,c.clientname,r.category
      order by head.dateid limit 20
";
    $data = $this->coreFunctions->opentable($qry);
    $this->config['sbclist']['pendingsd'] = ['cols' => $cols, 'data' => $data, 'title' => 'Salary Deductions', 'txtfield' => ['col1' => $col1], 'bgcolor' => 'bg-orange', 'textcolor' => 'white', 'paramsdata' => $paramsdata[0]];
  }

  public function incomingts()
  {
    $center = $this->config['params']['center'];
    $getcols = ['action', 'docno', 'loc2', 'loc', 'dateid'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[2]['label'] = 'Destination WH';
    $cols[3]['label'] = 'Source WH';
    $cols[0]['btns']['view']['lookupclass'] = 'jumptableentry';
    $wh = $this->companysetup->getwh($this->config['params']);

    $fields = ['release', 'ourref'];
    $fields = [];
    $col1 = $this->fieldClass->create($fields);
    $paramsdata = $this->coreFunctions->opentable("SELECT 'XXX' as ourref");


    $qry = "select head.trno, head.docno, date(head.dateid) as dateid, d.clientname as loc2, wh.clientname as loc,'tableentries/tableentry/postingst' as url
    from lahead as head left join cntnum on cntnum.trno=head.trno 
    left join client as wh on wh.client=head.wh left join client as d on d.client = head.client where head.doc='ST' 
    and head.client ='" . $wh . "' and head.lockdate is not null limit 20";
    $data = $this->coreFunctions->opentable($qry);
    $this->config['sbclist']['incomingts'] = ['cols' => $cols, 'data' => $data, 'title' => 'Incoming Transfers', 'txtfield' => ['col1' => $col1], 'paramsdata' => $paramsdata[0]];
  }

  public function tsrequest()
  {
    $center = $this->config['params']['center'];
    $getcols = ['docno', 'loc2', 'loc', 'dateid'];
    $cols = $this->tabClass->createdoclisting($getcols, []);
    $cols[1]['label'] = 'Branch';
    $cols[2]['label'] = 'Request TS From ';

    $fields = ['release', 'ourref'];
    $fields = [];
    $col1 = $this->fieldClass->create($fields);
    $paramsdata = $this->coreFunctions->opentable("SELECT 'XXX' as ourref");


    $qry = "select head.trno, head.docno, date(head.dateid) as dateid, head.clientname as loc2, wh.clientname as loc 
    from htrhead as head left join transnum on transnum.trno=head.trno
    left join htrstock as stock on stock.trno = head.trno 
    left join client as wh on wh.client=head.wh
    left join center on center.code = head.client where head.doc='TR'  
    and stock.qty>stock.qa group by head.trno,head.docno,head.dateid,head.clientname,wh.clientname order by head.dateid desc limit 20
    ";
    $data = $this->coreFunctions->opentable($qry);
    $this->config['sbclist']['tsrequest'] = ['cols' => $cols, 'data' => $data, 'title' => 'Request for Transfer', 'txtfield' => ['col1' => $col1], 'paramsdata' => $paramsdata[0]];
  }

  public function porequest()
  {
    $center = $this->config['params']['center'];
    $getcols = ['docno', 'branch', 'dateid'];
    $cols = $this->tabClass->createdoclisting($getcols, []);
    $cols[1]['label'] = 'Branch';

    $fields = ['release', 'ourref'];
    $fields = [];
    $col1 = $this->fieldClass->create($fields);
    $paramsdata = $this->coreFunctions->opentable("SELECT 'XXX' as ourref");


    $qry = "select head.trno, head.docno, date(head.dateid) as dateid, head.clientname as branch 
    from hprhead as head left join transnum on transnum.trno=head.trno 
    left join hprstock as stock on stock.trno = head.trno
    left join center  on center.code=head.client where head.doc='PR' and stock.qty>stock.qa group by head.trno,head.docno,head.dateid,head.clientname order by head.dateid desc limit 20";
    $data = $this->coreFunctions->opentable($qry);
    $this->config['sbclist']['porequest'] = ['cols' => $cols, 'data' => $data, 'title' => 'Request for PO', 'txtfield' => ['col1' => $col1], 'paramsdata' => $paramsdata[0]];
  }

  public function forapprovallist()
  {
    $user = $this->config['params']['user'];
    $cols = ['action', 'docno', 'dateid', 'clientname'];
    $stockbuttons = ['jumpmodule'];
    $cols = $this->tabClass->createdoclisting($cols, $stockbuttons);
    $cols[0]['btns']['jumpmodule']['lookupclass'] = 'jumpmodule';
    $cols[0]['style'] = 'width:50px;min-width:50px;';
    $cols[1]['style'] = 'width:75px;whiteSpace:normal;min-width:75px;';
    $cols[2]['style'] = 'width:100px;whiteSpace:normal;min-width:100px;';
    $cols[3]['style'] = 'width:200px;whiteSpace:normal;min-width:200px;';


    $cd_access = $this->checksecurity(4010);
    $po_access = $this->checksecurity(4009);

    $qry  = "";

    if ($cd_access) {
      $qry  = "select h.trno, h.docno, h.clientname, date(h.dateid) as dateid, '/module/ati/' as url, h.doc, 'module' as moduletype from cdhead as h left join transnum as num on num.trno=h.trno where num.statid=45";
    }
    if ($po_access) {
      if ($qry != '') {
        $qry  .= " union all ";
      }
      $qry  .= " select h.trno, h.docno, h.clientname, date(h.dateid) as dateid, '/module/ati/' as url, h.doc, 'module' as moduletype from pohead as h left join transnum as num on num.trno=h.trno where num.statid=10 and num.appuser='" . $user . "'";
    }

    $data = $this->coreFunctions->opentable($qry);
    $this->config['sbclist']['approvallist'] = ['cols' => $cols, 'data' => $data, 'title' => 'Approval List'];
  }

  public function canvassforpo()
  {
    $center = $this->config['params']['center'];
    $action = 0;
    $docno = 1;
    $dateid = 2;
    $clientname = 3;

    $getcols = ['action', 'docno', 'dateid', 'clientname'];
    $stockbuttons = ['jumpmodule'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:20px;whiteSpace: normal;min-width:20px;';
    $cols[$docno]['style'] = 'width:70px;whiteSpace: normal;min-width:70px;';
    $cols[$dateid]['style'] = 'width:90px;whiteSpace: normal;min-width:90px;';
    $cols[$clientname]['style'] = 'width:220px;whiteSpace: normal;min-width:220px;';
    $cols[$action]['btns']['jumpmodule']['lookupclass'] = 'jumpmodule';

    $qry = "select h.trno, h.docno, h.clientname,  date(h.dateid) as dateid, '/module/ati/' as url, h.doc, 'module' as moduletype
    from hcdstock as s left join hcdhead as h on h.trno=s.trno left join transnum on transnum.trno=h.trno where approveddate2 is not null and qty>qa and transnum.center='" . $center . "' and s.void=0
    group by h.trno, h.docno, h.clientname, h.dateid, h.doc order by h.dateid desc;";
    $data = $this->coreFunctions->opentable($qry);
    $this->config['sbclist']['canvassforpo'] = ['cols' => $cols, 'data' => $data, 'title' => 'Canvass for PO'];
  }

  public function vehicleschedulecalendar()
  {
    $data = $this->getvehicleschedulecalendar($this->config['params']['dateid']);
    $link = ['type' => 'customform', 'action' => 'vehicleschedule', 'classid' => ''];
    $this->config['calendar']['vehicleschedule'] = ['title' => 'Vehicle Status', 'view' => 'month', 'data' => $data, 'link' => $link];
  }

  public function getvehicleschedulecalendar($dateid)
  {
    $month = date('m', strtotime($dateid));
    $year = date('Y', strtotime($dateid));

    $qry = "select 't' as type, md5(dateid) as line, date(dateid) as datestart, date(dateid) as dateend, concat('AM: ',amcount-amused) as title, '' as rem, '' as icon, 'orange' as color 
    from vehiclesched where  month(dateid)=" . $month . " and year(dateid)=" . $year . "
    union all
    select 't' as type, md5(dateid) as line, date(dateid) as datestart, date(dateid) as dateend, concat('PM: ',pmcount-pmused) as title, '' as rem, '' as icon, 'blue' as color 
    from vehiclesched where  month(dateid)=" . $month . " and year(dateid)=" . $year . "";
    return $this->coreFunctions->opentable($qry);
  }

  public function timecardcalendar()
  {
    $data = $this->gettimecard_query();
    $link = ['type' => 'customform', 'action' => 'timecard', 'classid' => ''];
    $this->config['calendar']['timecard'] = ['title' => 'Timecard', 'view' => 'month', 'data' => $data, 'link' => $link];
  } //end function

  public function gettimecard_query()
  {
    $companyid = $this->config['params']['companyid'];
    switch ($companyid) {
      case 53: // camera
      case 51: // ulitc
      case 44: //stonepro
        return $this->getdatatimecard_camera($this->config['params']['dateid']);
        break;
      default:
        return $this->getdatatimecard($this->config['params']['dateid']);
        break;
    }
  }

  private function getdatatimecard_camera($dateid)
  {


    $leavelabel = $this->companysetup->getleavelabel($this->config['params']);
    if ($leavelabel == 'Days') {
      $leavelabel = 'Day';
    }
    $filteremp = " and emp.empid = '" . $this->config['params']['adminid'] . "'";

    $month = date('m', strtotime($dateid));
    $year = date('Y', strtotime($dateid));
    // schedule
    $sql = "
    select 'sched' as type, tc.daytype,md5(tc.line) as line, tc.empid, emp.idbarcode, tc.dateid as datestart, tc.dateid as dateend,
    concat(time_format(time(schedin),'%h%p'),' - ',time_format(time(schedout),'%h%p')) as title, '' as rem, '' as icon, '' as color, tc.reghrs, tc.schedin
    from timecard as tc left join employee as emp on emp.empid=tc.empid
    where month(dateid)=" . $month . " and year(dateid)=" . $year . " and tc.daytype <> 'RESTDAY' " . $filteremp;
    $data_sched = $this->coreFunctions->opentable($sql);
    // actual in
    $sql = "
    select 'in' as type,'' as daytype, md5(tc.line) as line, tc.empid, emp.idbarcode, tc.dateid as datestart, tc.dateid as dateend,
    time(actualin) as title, '' as rem, '' as icon, '' as color, tc.reghrs, tc.schedin
    from timecard as tc left join employee as emp on emp.empid=tc.empid
    where month(dateid)=" . $month . " and year(dateid)= " . $year . " " . $filteremp; //and actualin is not null

    $data_in = $this->coreFunctions->opentable($sql);
    // actual out
    $sql = "
    select 'out' as type,'' as daytype, md5(tc.line) as line, tc.empid, emp.idbarcode, tc.dateid as datestart, tc.dateid as dateend,
    time(actualout) as title, '' as rem, '' as icon, '' as color, tc.reghrs, tc.schedin
    from timecard as tc left join employee as emp on emp.empid=tc.empid
    where month(dateid)=" . $month . " and year(dateid)=  " . $year . " and actualout is not null " . $filteremp;


    $data_out = $this->coreFunctions->opentable($sql);
    $sql = "
    select 'o' as type,'' as daytype, md5(concat(ob.line,ob.empid)) as line, ob.empid, emp.idbarcode, date(ob.dateid) as datestart, date(ob.dateid) as dateend, 
    concat('OB (',ob.status,') - ',time(dateid)) as title, '' as rem, '' as icon, '' as color, '0' as reghrs, dateid as schedin 
    from obapplication as ob left join employee as emp on emp.empid=ob.empid
    where month(dateid)=" . $month . " and year(dateid)=" . $year .  $filteremp . " and (ob.status <> 'D' and ob.status2 <> 'D') order by dateid
    ";

    $dataob = $this->coreFunctions->opentable($sql);

    $sql = " select 'l' as type,'' as daytype, md5(concat(lv.trno,lv.line,lv.empid)) as line, lv.empid, emp.idbarcode, date(lv.effectivity) as datestart, date(lv.effectivity) as dateend, 
    concat('L-(',lv.status,') - ',cast(lv.adays as unsigned),'$leavelabel') as title, '' as rem, '' as icon, '' as color, '0' as reghrs, effectivity as schedin 
    from leavetrans as lv left join employee as emp on emp.empid=lv.empid
    where month(effectivity)=" . $month . " and year(effectivity)=" . $year . $filteremp . " and (lv.status <> 'D' and lv.status2 <> 'D') order by effectivity
    ";

    $dataleave = $this->coreFunctions->opentable($sql);

    $shift = [];
    if (!empty($data_in)) {
      $shift = $this->payrollcommon->getShiftDetails($data_in[0]->empid);
    }

    $returndata = [];

    // schedule
    if (!empty($data_sched)) {
      foreach ($data_sched as $key => $value_sched) {
        // $value_sched->color = $value_sched->color;
        array_push($returndata, $value_sched);
      }
    }

    foreach ($data_in as $key => $val) {
      if ($val->reghrs != 0) {
        if ($val->title == null) {
          if ($val->datestart > $this->othersClass->getCurrentDate()) {
            $val->color = 'transparent';
            $val->title = '';
          } else {
            $val->color = 'red';
            $val->title = 'ABSENT';
          }
        } else {
          $val->color = 'green';

          $actualin = Carbon::parse($val->datestart . ' ' . $val->title);
          $schedin = Carbon::parse($val->schedin);

          $late = $schedin->diffInMinutes($actualin, false);

          if ($shift[0]->gtin != 0) {
            if ($late > 0) {
              $late -= $shift[0]->gtin;
            }
          }


          if ($late > 0) {
            $late = $schedin->diffInMinutes($actualin, false);
          }

          if ($late > 0) {
            $val->color = 'orange';
          }
        }
      } else {
        $val->color = 'transparent';
      }
      $val->title = 'I-' . $val->title;

      array_push($returndata, $val);
    }

    // out
    if (!empty($data_out)) {
      foreach ($data_out as $key => $value_out) {
        $value_out->title = 'O-' . $value_out->title;
        $value_out->color = 'green';
        array_push($returndata, $value_out);
      }
    }
    // obvio
    if (!empty($dataob)) {
      foreach ($dataob as $key => $valob) {
        $valob->color = 'purple';
        array_push($returndata, $valob);
      }
    }
    // leave
    if (!empty($dataleave)) {
      foreach ($dataleave as $key => $vallev) {
        $vallev->color = 'blue';
        array_push($returndata, $vallev);
      }
    }

    return $returndata;
  }
  private function getdatatimecard($dateid)
  {
    $filteremp = " and emp.idbarcode='" . $this->config['params']['user'] . "'";
    $filteruserid = " userid='" . $this->config['params']['user'] . "'";
    $leavelabel = $this->companysetup->getleavelabel($this->config['params']);
    switch ($this->config['params']['companyid']) {
      case 44: //stonepro
        $idbarcode = $this->coreFunctions->datareader("select idbarcode as value from client as c join employee as emp on emp.empid=c.clientid where c.email=?", [$this->config['params']['user']]);
        $filteremp = " and emp.idbarcode='" . $idbarcode  . "'";
        $filteruserid = " userid='" . $idbarcode . "'";
        break;
    }

    $month = date('m', strtotime($dateid));
    $year = date('Y', strtotime($dateid));

    $sql = "
    select 't' as type, md5(tc.line) as line, tc.empid, emp.idbarcode, tc.dateid as datestart, tc.dateid as dateend, 
    (select concat(time_format(time(timeinout),'%H:%i')) as timeinout from timerec 
    where $filteruserid and date(timeinout)=tc.dateid and month(timeinout)=" . $month . " and year(timeinout)=" . $year . "
    group by timeinout order by timeinout limit 1
    ) as title, '' as rem, '' as icon, '' as color, tc.reghrs, tc.schedin
  from timecard as tc join employee as emp on emp.empid=tc.empid
    where month(dateid)=" . $month . " and year(dateid)=" . $year . $filteremp;

    $data = $this->coreFunctions->opentable($sql);

    $sql = "
    select 'o' as type, md5(concat(ob.line,ob.empid)) as line, ob.empid, emp.idbarcode, date(ob.dateid) as datestart, date(ob.dateid) as dateend, 
    concat('OB (',ob.status,') - ',time(dateid)) as title, '' as rem, '' as icon, '' as color, '0' as reghrs, dateid as schedin 
    from obapplication as ob join employee as emp on emp.empid=ob.empid
    where month(dateid)=" . $month . " and year(dateid)=" . $year .  $filteremp . " order by dateid
    ";

    $dataob = $this->coreFunctions->opentable($sql);

    $sql = " select 'l' as type, md5(concat(ob.trno,ob.line,ob.empid)) as line, ob.empid, emp.idbarcode, date(ob.effectivity) as datestart, date(ob.effectivity) as dateend, 
    concat('Leave (',ob.status,') - ',cast(ob.adays as unsigned),'$leavelabel') as title, '' as rem, '' as icon, '' as color, '0' as reghrs, effectivity as schedin 
    from leavetrans as ob join employee as emp on emp.empid=ob.empid
    where month(effectivity)=" . $month . " and year(effectivity)=" . $year . $filteremp . " order by effectivity
    ";

    $dataleave = $this->coreFunctions->opentable($sql);

    $shift = [];
    if (!empty($data)) {
      $shift = $this->payrollcommon->getShiftDetails($data[0]->empid);
    }

    $returndata = [];

    foreach ($data as $key => $val) {
      if ($val->reghrs != 0) {
        if ($val->title == null) {
          if ($val->datestart > $this->othersClass->getCurrentDate()) {
            $val->color = 'transparent';
            $val->title = '';
          } else {
            $val->color = 'red';
            $val->title = 'ABSENT';
          }
        } else {
          $val->color = 'green';

          $actualin = Carbon::parse($val->datestart . ' ' . $val->title);
          $schedin = Carbon::parse($val->schedin);

          $late = $schedin->diffInMinutes($actualin, false);

          if ($shift[0]->gtin != 0) {
            if ($late > 0) {
              $late -= $shift[0]->gtin;
            }
          }

          if ($late > 0) {
            $late = $schedin->diffInMinutes($actualin, false);
          }

          if ($late > 0) {
            $val->color = 'orange';
          }
        }
      } else {
        $val->color = 'transparent';
      }

      array_push($returndata, $val);
    }

    if (!empty($dataob)) {
      foreach ($dataob as $key => $valob) {
        array_push($returndata, $valob);
      }
    }

    if (!empty($dataleave)) {
      foreach ($dataleave as $key => $vallev) {
        array_push($returndata, $vallev);
      }
    }

    return $returndata;
  }

  public function eventandholidaycalendar()
  {
    $data = $this->getdataeventandholiday($this->config['params']['dateid']);
    $link = ['type' => 'customform', 'action' => 'event', 'classid' => ''];
    $this->config['calendar']['event'] = ['title' => 'Events and Holiday', 'view' => 'month', 'data' => $data, 'link' => $link];
  } //end function

  private function getdataeventandholiday($dateid)
  {
    $month = date('m', strtotime($dateid));
    $year = date('Y', strtotime($dateid));

    $systemtype = $this->companysetup->getsystemtype($this->config['params']); //($this->config['params'])
    $unionall = "";

    if (stripos($systemtype, 'PAYROLL') !== false) {
      $unionall = "
     union all
     select 'hll' as type,md5(line) as line, date(dateid) as datestart,date(dateid) as dateend,`description` as title,'' as rem,'' as icon,'orange' as color from holidayloc where (month(dateid)='" . $month . "' and year(dateid)='" . $year . "')
     union all
     select 'hl' as type,md5(line) as line, date(dateid) as datestart,date(dateid) as dateend,`description` as title,'' as rem,'' as icon,'orange' as color from holiday where (month(dateid)='" . $month . "' and year(dateid)='" . $year . "')";
    }


    $sql = "select 'e' as type,md5(line) as line,datestart,dateend,title,rem,icon,color from waims_event where (month(datestart)='" . $month . "' and year(datestart)='" . $year . "') or (month(dateend)='" . $month . "' and year(dateend)='" . $year . "')
             union all 
             select 'h' as type,md5(line) as line,datestart,dateend,title,rem,icon,color from waims_holiday where (month(datestart)='" . $month . "' and year(datestart)='" . $year . "') or (month(dateend)='" . $month . "' and year(dateend)='" . $year . "')
    $unionall ";  // $unionall
    return $this->coreFunctions->opentable($sql);
  }

  public function getdata()
  {
    switch ($this->config['params']['action2']) {
      case 'event':
        $data = $this->getdataeventandholiday($this->config['params']['dateid']);
        $this->config['return'] = ['status' => true, 'msg' => 'Data was successfully received.', 'data' => $data, 'date' => $this->config['params']['dateid']];
        break;
      case 'timecard':
        $data = $this->gettimecard_query();
        $this->config['return'] = ['status' => true, 'msg' => 'Data was successfully received.', 'data' => $data, 'date' => $this->config['params']['dateid']];
        break;
      case 'vehicleschedule':
        $data = $this->getvehicleschedulecalendar($this->config['params']['dateid']);
        $this->config['return'] = ['status' => true, 'msg' => 'Data was successfully received.', 'data' => $data, 'date' => $this->config['params']['dateid']];
        break;

      case 'obapplication':
        $data = $this->getobapplication();
        $this->config['return'] = ['status' => true, 'msg' => 'Data was successfully received.', 'data' => $data, 'table' => $this->config['params']['action2']];
        break;
      case 'obapplicationinitial':
        $data = $this->getobapplicationInitial();
        $this->config['return'] = ['status' => true, 'msg' => 'Data was successfully received.', 'data' => $data, 'table' => $this->config['params']['action2']];
        break;
      case 'changeshiftapplication':
        $data = $this->getchangeshiftapplication();
        $this->config['return'] = ['status' => true, 'msg' => 'Data was successfully received.', 'data' => $data, 'table' => $this->config['params']['action2']];
        break;

      case 'otapplication':
        $data = $this->getotapplication();
        $this->config['return'] = ['status' => true, 'msg' => 'Data was successfully received.', 'data' => $data, 'table' => $this->config['params']['action2']];
        break;

      case 'leaveapplication':
        $data = $this->getleaveapplication();
        $this->config['return'] = ['status' => true, 'msg' => 'Data was successfully received.', 'data' => $data, 'table' => $this->config['params']['action2']];
        break;

      case 'loanapplication':
        $data = $this->coreFunctions->opentable($this->loanapplicationqry());
        $this->config['return'] = ['status' => true, 'msg' => 'Data was successfully received.', 'data' => $data, 'table' => $this->config['params']['action2']];
        break;
      case 'undertime':
        $data = $this->getundertimeapplication();
        $this->config['return'] = ['status' => true, 'msg' => 'Data was successfully received.', 'data' => $data, 'table' => $this->config['params']['action2']];
        break;
      case 'otapplicationadv':
        $data = $this->getotapplicationadvance();
        $this->config['return'] = ['status' => true, 'msg' => 'Data was successfully received.', 'data' => $data, 'table' => $this->config['params']['action2']];
        break;
      case 'allapprovedapplication':
        $data = $this->allapprovedata($this->config);
        $this->config['return'] = ['status' => true, 'msg' => 'Data was successfully received.', 'data' => $data, 'table' => $this->config['params']['action2']];
        break;
      case 'getpendingallapplication':
        $data = $this->allapproveddata_test($this->config);
        $this->config['return'] = ['status' => true, 'msg' => 'Data was successfully received.', 'data' => $data, 'table' => $this->config['params']['action2']];
        break;
      case 'kstarmonthlysales':
        $data = $this->kstarmonthlysales();
        $this->config['return'] = ['status' => true, 'msg' => 'Data was successfully received.', 'data' => $this->config['sbclist']['kstarsales']['data'], 'table' => 'kstarsales'];
        break;
      case 'pendingsd':
        $data = $this->pendingsd();
        $this->config['return'] = ['status' => true, 'msg' => 'Data was successfully received.', 'data' => $this->config['sbclist']['pendingsd']['data'], 'table' => 'pendingsd'];
        break;
      case 'restday':
        $data = $this->getrestday();
        $this->config['return'] = ['status' => true, 'msg' => 'Data was successfully received.', 'data' => $data, 'table' => $this->config['params']['action2']];
        break;
      case 'word':
        $data = $this->getword();
        $this->config['return'] = ['status' => true, 'msg' => 'Data was successfully received.', 'data' => $data, 'table' => $this->config['params']['action2']];
        break;
      case 'leavecancellation':
        $data = $this->getcancellation();
        $this->config['return'] = ['status' => true, 'msg' => 'Data was successfully received.', 'data' => $data, 'table' => $this->config['params']['action2']];
        break;
      case 'undertimecancellation':
        $data = $this->get_undertime_cancellation();
        $this->config['return'] = ['status' => true, 'msg' => 'Data was successfully received.', 'data' => $data, 'table' => $this->config['params']['action2']];
        break;
      case 'obcancellation':
        $data = $this->get_undertime_cancellation();
        $this->config['return'] = ['status' => true, 'msg' => 'Data was successfully received.', 'data' => $data, 'table' => $this->config['params']['action2']];
        break;
      case 'itinerary':
        $data = $this->getitinerary();
        $this->config['return'] = ['status' => true, 'msg' => 'Data was successfully received.', 'data' => $data, 'table' => $this->config['params']['action2']];
        break;
      case 'gapplications':
        $data = $this->getgapplications();
        $this->config['return'] = ['status' => true, 'msg' => 'Data was successfully received.', 'data' => $data, 'table' => $this->config['params']['action2']];
        break;
      case 'dailytask':
        $data = $this->getdailytask();
        $this->config['return'] = ['status' => true, 'msg' => 'Data was successfully received.', 'data' => $data, 'table' => $this->config['params']['action2']];
        break;
      default:
        $this->config['return'] = ['status' => false, 'msg' => 'Data is not yet setup in the dashboardClass.'];
        break;
    }

    return $this;
  } //end function

  public function currenttimerec($config)
  {
    $getcols = ['action', 'clientname', 'dateid'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:20px;whiteSpace: normal;min-width:20px;';
    $cols[0]['btns']['view']['action'] = 'viewpiclogs';
    $cols[0]['btns']['view']['lookupclass'] = 'customform';
    $cols[0]['btns']['view']['classid'] = 'posted';
    $cols[1]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
    $cols[2]['style'] = 'width:800px;whiteSpace: normal;min-width:800px;';

    $cols[1]['label'] = 'Name';
    $cols[2]['label'] = 'Time';

    $qry = "select concat(empfirst,' ',LEFT(emplast,1),'.') as clientname, emp.empid, emp.idbarcode, '' as dateid
        from employee as emp where emp.isactive=1 order by clientname";
    $data = $this->coreFunctions->opentable($qry);

    foreach ($data as $key => $value) {
      $log = $this->coreFunctions->datareader("select TIME_FORMAT(timeinout, '%h:%i') as value from timerec where userid='" . $value->idbarcode . "' and date(timeinout)='" . $this->othersClass->getCurrentDate() . "'");
      $data[$key]->dateid = $log;
    }

    $this->config['sbclist']['currenttimerec'] = ['cols' => $cols, 'data' => $data, 'title' => 'EMPLOYEE LOGS', 'txtfield' => ['col1' => []], 'bgcolor' => 'bg-red-10', 'textcolor' => 'white', 'issearchshow' => false];
  } //end function

  public function notice()
  {
    $getcols = ['action', 'title', 'rem'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:20px;whiteSpace: normal;min-width:20px;';
    $cols[0]['btns']['view']['action'] = 'notice';
    $cols[0]['btns']['view']['lookupclass'] = 'customform';
    $cols[0]['btns']['view']['classid'] = 'posted';
    $cols[1]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
    $cols[2]['style'] = 'width:800px;whiteSpace: normal;min-width:800px;';
    $label = 'NOTICE';
    if ($this->config['params']['companyid'] == 53) { // camera
      $label = 'ADVISORY/MEMORANDUM';
    }

    $accessid = $this->coreFunctions->getfieldvalue("client", "userid", "clientid=?", [$this->config['params']['adminid']]);
    if ($accessid == 0) {
      $accessid = $this->coreFunctions->getfieldvalue("useraccess", "accessid", "username=?", [$this->config['params']['user']]);
    }

    $qry = "select line,dateid,title,concat(left(rem,20),'....') as rem,users.username as clientname from waims_notice as notice
    left join users on users.idno =  '" . floatval($accessid) . "'
    where notice.status=0 and notice.roleid = " . floatval($accessid) . " and notice.empid = 0
    union all 
    select line, dateid, title,concat(left(rem,20),'....') as rem,concat(emp.emplast,', ',emp.empfirst,' ',emp.empmiddle) as clientname from waims_notice as notice
    left join employee as emp on emp.empid = notice.empid 
    where notice.status=0 and notice.roleid = 0 and notice.empid = " . $this->config['params']['adminid'] . "
    union all 
    select line, dateid, title,concat(left(rem,20),'....') as rem,concat(emp.emplast,', ',emp.empfirst,' ',emp.empmiddle) as clientname from waims_notice as notice
    left join employee as emp on emp.empid = notice.empid 
    where notice.status=0 and notice.roleid = 0 and notice.empid = 0
    order by line";
    $data = $this->coreFunctions->opentable($qry);
    $this->config['sbclist']['notice'] = ['cols' => $cols, 'data' => $data, 'title' => $label, 'txtfield' => ['col1' => []], 'bgcolor' => 'bg-red-10', 'textcolor' => 'white', 'issearchshow' => true];
  } //end function
  public function violation()
  {
    $getcols = ['action', 'dateid', 'remarks'];
    foreach ($getcols as $key => $value) {
      $$value = $key;
    }
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:20px;whiteSpace: normal;min-width:20px;';
    $cols[$action]['btns']['view']['action'] = 'violation';
    $cols[$action]['btns']['view']['lookupclass'] = 'customform';
    $cols[$remarks]['label'] = 'Reason';

    $accessid = $this->coreFunctions->getfieldvalue("client", "clientid", "clientid=?", [$this->config['params']['adminid']]);

    $qry = "select vi.trno as line ,date(vi.dateid) as dateid,cl.clientname,vi.remarks from 
    violation as vi left join client as cl on cl.clientid = vi.empid 
    where vi.empid = " . $accessid . "  and closedate is null  ";
    $data = $this->coreFunctions->opentable($qry);
    $this->config['sbclist']['violation'] = ['cols' => $cols, 'data' => $data, 'title' => 'VIOLATION', 'txtfield' => ['col1' => []], 'bgcolor' => 'bg-red-10', 'textcolor' => 'white'];
  } //end function


  public function todo()
  {
    $companyid = $this->config['params']['companyid'];
    $getcols = ['action', 'module', 'total'];
    $stockbuttons = ['referencemodule'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:20px;whiteSpace: normal;min-width:20px;';
    $cols[0]['btns']['referencemodule']['action'] = 'todo';
    $cols[0]['btns']['referencemodule']['lookupclass'] = 'customform';
    $cols[1]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';

    $clientid = $this->config['params']['adminid'];
    if ($clientid == 0) {
      $clientid = $this->coreFunctions->getfieldvalue("useraccess", "userid", "username=?", [$this->config['params']['user']]);
    }

    $condition = '';
    $condition_h = '';
    switch ($companyid) {
      case 16: //ati
        $condition = " and t.appuser='" . $this->config['params']['user'] . "'";
        break;
      case 58: //cdo hrispayroll
        $condition_h = " and t.statid =19";
        break;
    }

    $docqry = "select module,doc,seen,total,userid,clientid
              from ( select doc as module,doc,sum(seen) as seen,sum(total) as total,userid,clientid
                     from (select t.doc,case when todo.seendate is not null then count(seendate) else 0 end as seen,
                                  case when todo.donedate is not null then count(donedate) else count(todo.trno) end as total,
                                  todo.userid,todo.clientid
                          from transnumtodo as todo
                          left join transnum as t on t.trno=todo.trno
                          where ((todo.userid = $clientid and todo.clientid=0) or (todo.userid=0 and todo.clientid = $clientid))
                          and t.postdate is null and donedate is null " . $condition . "
                          group by t.doc,todo.seendate,todo.donedate,todo.userid,todo.clientid
                          union all
                          select t.doc, case when todo.seendate is not null then count(seendate) else 0 end as seen,
                                 case when todo.donedate is not null then count(donedate) else count(todo.trno) end as total,
                                 todo.userid,todo.clientid
                          from cntnumtodo as todo
                          left join cntnum as t on t.trno=todo.trno
                          where ((todo.userid = $clientid and todo.clientid=0) or (todo.userid=0 and todo.clientid = $clientid)) and t.postdate is null and donedate is null " . $condition . "
                          group by t.doc,todo.seendate,todo.donedate,todo.userid,todo.clientid
                          union all
                          select t.doc, case when todo.seendate is not null then count(seendate) else 0 end as seen,
                                 case when todo.donedate is not null then count(donedate) else count(todo.trno) end as total,
                                 todo.userid,todo.clientid
                          from hrisnumtodo as todo
                          left join hrisnum as t on t.trno=todo.trno
                          where ((todo.userid = $clientid and todo.clientid=0) or (todo.userid=0 and todo.clientid = $clientid)) and t.postdate is null and donedate is null " . $condition_h . "
                          group by t.doc,todo.seendate,todo.donedate,todo.userid,todo.clientid ) as a 
                  group by module,doc,userid,clientid ) as t ";
    $docresult =   $this->coreFunctions->opentable($docqry);
    $docresult = json_decode(json_encode($docresult), true);


    for ($i = 0; $i < count($docresult); $i++) {
      switch ($docresult[$i]['module']) {
        case 'CD':
        case 'PO':
        case 'RR':
        case 'DM':
        case 'PR':
          $folderloc = 'purchase';
          if ($companyid == 16) $folderloc = 'ati'; //ati
          $path = 'App\Http\Classes\modules\\' . $folderloc . '\\' . strtolower($docresult[$i]['module']);
          break;
        case 'OQ':
          $path = 'App\Http\Classes\modules\ati\\' . strtolower($docresult[$i]['module']);
          break;
        case 'SO':
        case 'SJ':
        case 'CM':
          $path = 'App\Http\Classes\modules\sales\\' . strtolower($docresult[$i]['module']);
          break;
        case 'PC':
        case 'AJ':
        case 'TS':
        case 'IS':
          $path = 'App\Http\Classes\modules\inventory\\' . strtolower($docresult[$i]['module']);
          break;
        case 'PQ':
        case 'SV':
        case 'AP':
        case 'PV':
        case 'CV':
          $folderloc = 'payable';
          if ($companyid == 16 && $docresult[$i]['module'] == 'CV') $folderloc = 'ati'; //ati
          $path = 'App\Http\Classes\modules\\' . $folderloc . '\\' . strtolower($docresult[$i]['module']);
          break;
        case 'AR':
        case 'CR':
        case 'KR':
          $path = 'App\Http\Classes\modules\receivable\\' . strtolower($docresult[$i]['module']);
          break;
        case 'GJ':
        case 'GD':
        case 'GC':
        case 'DS':
          $path = 'App\Http\Classes\modules\accounting\\' . strtolower($docresult[$i]['module']);
          break;
        case 'HQ':
          $path = 'App\Http\Classes\modules\hris\\' . strtolower($docresult[$i]['module']);
          break;
      }
      $docresult[$i]['module'] = app($path)->modulename;
    }
    $this->config['sbclist']['todo'] = ['cols' => $cols, 'data' => $docresult, 'title' => 'ASSIGNED TO DO'];
  } //end function

  public function pendingtodo()
  {
    $getcols = ['action', 'module', 'seen', 'total'];
    $stockbuttons = ['referencemodule'];
    $companyid = $this->config['params']['companyid'];

    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:20px;whiteSpace: normal;min-width:20px;';
    $cols[0]['btns']['referencemodule']['action'] = 'pendingtodo';
    $cols[0]['btns']['referencemodule']['lookupclass'] = 'customform';
    $cols[1]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';

    $clientid = $this->config['params']['adminid'];
    if ($clientid == 0) {
      $clientid = $this->coreFunctions->getfieldvalue("useraccess", "userid", "username=?", [$this->config['params']['user']]);
    }

    $condition = '';
    switch ($companyid) {
      case 16: //ati
        $condition = " and t.appuser='" . $this->config['params']['user'] . "'";
        break;
    }

    $docqry = $this->coreFunctions->opentable("
              select module,doc,seen,total 
              from ( select doc as module,doc,sum(seen) as seen,sum(total) as total
                     from (select t.doc,case when todo.seendate is not null then count(seendate) else 0 end as seen,
                                  case when todo.donedate is not null then count(donedate) else count(todo.trno) end as total,
                                  todo.userid,todo.clientid
                          from transnumtodo as todo
                          left join transnum as t on t.trno=todo.trno
                          where todo.createby = $clientid and donedate is null " . $condition . "
                          and t.postdate is null
                          group by t.doc,todo.seendate,todo.donedate,todo.userid,todo.clientid
                          union all
                          select t.doc, case when todo.seendate is not null then count(seendate) else 0 end as seen,
                                 case when todo.donedate is not null then count(donedate) else count(todo.trno) end as total,
                                 todo.userid,todo.clientid
                          from cntnumtodo as todo
                          left join cntnum as t on t.trno=todo.trno
                          where todo.createby = $clientid and donedate is null and t.postdate is null " . $condition . "
                          group by t.doc,todo.seendate,todo.donedate,todo.userid,todo.clientid ) as a 
                  group by module,doc) as t");
    $docresult = json_decode(json_encode($docqry), true);


    for ($i = 0; $i < count($docresult); $i++) {
      switch ($docresult[$i]['module']) {
        case 'PR':
        case 'CD':
        case 'PO':
        case 'RR':
        case 'DM':
          $folderloc = 'purchase';
          if ($companyid == 16) $folderloc = 'ati'; //ati
          $path = 'App\Http\Classes\modules\\' . $folderloc . '\\' . strtolower($docresult[$i]['module']);
          break;
        case 'OQ':
          $path = 'App\Http\Classes\modules\ati\\' . strtolower($docresult[$i]['module']);
          break;
        case 'SO':
        case 'SJ':
        case 'CM':
          $path = 'App\Http\Classes\modules\sales\\' . strtolower($docresult[$i]['module']);
          break;
        case 'PC':
        case 'AJ':
        case 'TS':
        case 'IS':
          $path = 'App\Http\Classes\modules\inventory\\' . strtolower($docresult[$i]['module']);
          break;
        case 'PQ':
        case 'SV':
        case 'AP':
        case 'PV':
        case 'CV':
          $folderloc = 'payable';
          if ($companyid == 16 && $docresult[$i]['module'] == 'CV') $folderloc = 'ati';
          $path = 'App\Http\Classes\modules\\' . $folderloc . '\\' . strtolower($docresult[$i]['module']);
          break;
        case 'AR':
        case 'CR':
        case 'KR':
          $path = 'App\Http\Classes\modules\receivable\\' . strtolower($docresult[$i]['module']);
          break;
        case 'GJ':
        case 'GD':
        case 'GC':
        case 'DS':
          $path = 'App\Http\Classes\modules\accounting\\' . strtolower($docresult[$i]['module']);
          break;
      }
      $docresult[$i]['module'] = app($path)->modulename;
    }
    $this->config['sbclist']['pendingtodo'] = ['cols' => $cols, 'data' => $docresult, 'title' => 'PENDING TO DO'];
  } //end function


  public function changeshiftapplication()
  {
    $companyid = $this->config['params']['companyid'];
    $getcols = ['action', 'clientname', 'dateid', 'schedin', 'schedout', 'rem', 'lblforapp'];

    foreach ($getcols as $key => $value) {
      $$value = $key;
    }

    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:20px;whiteSpace: normal;min-width:20px;max-width:20px;';
    $cols[$action]['btns']['view']['action'] = 'changeshiftapplication';
    $cols[$action]['btns']['view']['lookupclass'] = 'customform';

    $cols[$clientname]['label'] = 'Name';

    $cols[$clientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;max-width:200px;';
    $cols[$dateid]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;max-width:100px;';
    $cols[$schedin]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;max-width:100px;';
    $cols[$schedout]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;max-width:100px;';

    if ($companyid == 53) { // camera
      $cols[$rem]['label'] = 'Reason';
    }

    $data = $this->getchangeshiftapplication();
    $this->config['sbclist']['changeshiftapplication'] = ['cols' => $cols, 'data' => $data, 'title' => 'PENDING CHANGE SHIFT APPLICATION', 'txtfield' => ['col1' => []], 'bgcolor' => 'bg-green-9', 'textcolor' => 'white'];
  }

  public function getchangeshiftapplication()
  {
    $companyid = $this->config['params']['companyid'];
    $url = 'App\Http\Classes\modules\payroll\\' . 'changeshiftapplication';
    $data = app($url)->approvers($this->config['params']);
    $emplvl = $this->othersClass->checksecuritylevel($this->config, true);

    $approver = $this->checkapprover($this->config);
    $supervisor =  $this->checksupervisor($this->config);
    $condition = '';
    $filter = "";
    $isapp_issup = "";
    $label_status = ",'For Approver' as lblforapp";
    foreach ($data as $key => $value) {
      if (count($data) > 1) {

        if ($key == 0) {
          if ($value == 'issupervisor' && $supervisor || $value == 'isapprover' && $approver) {
            if ($value == 'issupervisor' && $supervisor) {
              $label_status = ",'For Supervisor' as lblforapp";
            }
            $condition = " and cs.status2 = 0 and approveddate2 is null and approveddate is null and disapproveddate is null ";
            break;
          }
        } else {
          if ($value == 'issupervisor' && $supervisor || $value == 'isapprover' && $approver) {
            if ($value == 'issupervisor' && $supervisor) {
              $label_status = ",'For Supervisor' as lblforapp";
            }
            $condition = " and cs.status2 = 1 and approveddate is null and approveddate2 is not null";
            break;
          }
        } //end else index
      } else {
        if (count($data) == 1) {
          if ($supervisor) {
            $isapp_issup = " and 1=0 ";
          }
          if ($approver) {
            $isapp_issup = " and 1=1 ";
          }
          $condition = " and cs.approveddate is null and cs.disapproveddate is null ";
          break;
        }
      }
    }
    if ($companyid == 53) { // camera
      $condition .= " and cs.submitdate is not null $isapp_issup and emp.level in $emplvl ";
    }
    $filter = " cs.status= 0 " . $condition;
    $qry = "select cs.line, client.clientname, date(cs.dateid) as dateid, date(cs.schedin) as schedin,date(cs.schedout) as schedout,
     cs.rem,cs.status,cs.approvedby,cs.approveddate,cs.status2,cs.approvedby2,cs.approveddate2 $label_status
    from changeshiftapp as cs 
    left join client on client.clientid=cs.empid
    left join employee as emp on emp.empid = cs.empid
    where $filter
    order by dateid, client.clientname";
    return $this->coreFunctions->opentable($qry);
  }

  // public function gtasks()
  // {

  //   $companyid = $this->config['params']['companyid'];
  //   $title = 'PENDING TASK 2';
  //   $getcols = ['action', 'description', 'modulename', 'appcount'];

  //   foreach ($getcols as $key => $value) {
  //     $$value = $key;
  //   }
  //   $stockbuttons = ['view'];
  //   $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
  //   $cols[$action]['btns']['view']['action'] = 'pendingtask';
  //   $cols[$action]['btns']['view']['lookupclass'] = 'tableentry';
  //   $cols[$description]['type'] = 'label';
  //   $cols[$description]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;max-width:200px;';

  //   $cols[$action]['style'] = 'width:20px;whiteSpace: normal;min-width:20px;max-width:20px;';
  //   $cols[$modulename]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;max-width:300px;';
  //   $cols[$appcount]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;max-width:200px;';
  //   $cols[$modulename]['field'] = 'modulename';
  //   $cols[$modulename]['type'] = 'label';

  //   $data = $this->getgapplications();
  //   $this->config['sbclist']['gapplications'] = ['cols' => $cols, 'data' => $data, 'title' => $title, 'txtfield' => ['col1' => []], 'bgcolor' => 'bg-purple-6', 'textcolor' => 'white'];
  // }

  public function gapplications()
  {

    $companyid = $this->config['params']['companyid'];
    $getcols = ['action', 'modulename', 'appcount', 'lblforapp'];
    $title = 'PENDING APPLICATIONS';
    if ($companyid == 29) { //sbc main 
      $title = 'PENDING TASK';
      $getcols = ['action', 'description', 'modulename', 'appcount'];
    }

    foreach ($getcols as $key => $value) {
      $$value = $key;
    }
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    if ($companyid == 29) { //sbc main
      $cols[$description]['type'] = 'label';
      $cols[$description]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;max-width:200px;';
    }
    $cols[$action]['btns']['view']['action'] = 'pendingapplications';
    $cols[$action]['btns']['view']['lookupclass'] = 'tableentry';

    $cols[$action]['style'] = 'width:20px;whiteSpace: normal;min-width:20px;max-width:20px;';
    $cols[$modulename]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;max-width:300px;';
    $cols[$appcount]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;max-width:200px;';
    $cols[$modulename]['field'] = 'modulename';
    $cols[$modulename]['type'] = 'label';

    if ($companyid != 29) { // not sbc main
      $cols[$lblforapp]['type'] = 'label';
      $cols[$lblforapp]['label'] = 'Approver Sequence';
    }

    $data = $this->getgapplications();
    $this->config['sbclist']['gapplications'] = ['cols' => $cols, 'data' => $data, 'title' => $title, 'txtfield' => ['col1' => []], 'bgcolor' => 'bg-purple-6', 'textcolor' => 'white'];
  }

  public function getgapplications()
  {
    $approver = $this->checkapprover($this->config);
    $supervisor = $this->checksupervisor($this->config);
    $companyid = $this->config['params']['companyid'];
    $adminid = $this->config['params']['adminid'];

    $addselect = '';
    $condition = '';
    $labelapp = 'FOR APPROVER';
    $labelsupp = 'FOR SUPERVISOR';
    if ($companyid == 53) { //camera
      $labelapp = 'FOR HR/PAYROLL APPROVER';
      $labelsupp = 'FOR HEAD DEPT. APPROVER';
    }
    $desc = '';

    if ($companyid == 29) { //sbc main
      $desc = ", if(p.approver='','PENDING TASKS',p.approver) as description";
      $condition = " and m.sbcpendingapp not in  ('pendingtask','pdailytask2')";
      $addselect = " union all 

      select count(p.line) as appcount, m.labelname as modulename, 'TM' as doc, 'pendingtask' as sbcpendingapp,
						p.approver, ''  as lblforapp  $desc
						from pendingapp as p
            left join moduleapproval as m on m.modulename=p.doc
            where m.modulename <> '' and p.clientid= " . $adminid . " and p.doc='TM'
            group by m.labelname,p.approver


                  union all

      select count(p.line) as appcount, m.labelname as modulename, 'DY' as doc, 'pdailytask2' as sbcpendingapp,	
           p.approver ,'' as lblforapp, (case when p.approver in ('RETURN','FOR CHECKING','COMMENT') then p.approver else 'PENDING DAILY TASK' end) as description
						from pendingapp as p 
            left join moduleapproval as m on m.modulename=p.doc  
						where m.modulename <> '' and p.clientid= " . $adminid . " and p.doc='DY'
            group by  m.labelname,p.approver";
    }

    if ($companyid == 58) { //cdo
      $addselect = " union all select count(p.line) as appcount, 'Budget for Travel Applications' as modulename, m.modulename as doc, m.sbcpendingapp,
						p.approver,'' as lblforapp
						from pendingapp as p left join moduleapproval as m on m.modulename=p.doc 
						where p.clientid= " . $adminid . " and p.approver = 'isbudgetapprover'
            group by m.labelname, m.modulename, m.sbcpendingapp,m.line,p.approver
            union all
            select count(p.line) as appcount, reg.description as modulename, 'CONTRACTMONITORING' as doc, 'pendingregprocess' as sbcpendingapp, '' as approver, 'FOR EVALUATOR' as lblforapp
            from pendingapp as p
            left join regprocess as regp on regp.line=p.line
            left join regularization as reg on reg.line=regp.regid
            where p.doc='CONTRACTMONITORING' and p.clientid=" . $adminid . " group by reg.description ";

      $condition = "and p.approver <> 'isbudgetapprover'";
    }

    $query = "select count(p.line) as appcount, m.labelname as modulename, m.modulename as doc, m.sbcpendingapp,
						p.approver,if(p.approver = 'isapprover', '$labelapp', '$labelsupp') as lblforapp  $desc
						from pendingapp as p 
            left join moduleapproval as m on m.modulename=p.doc  
						where m.modulename <> '' and p.clientid= " . $adminid . " $condition
            group by m.labelname, m.modulename, m.sbcpendingapp,m.line,p.approver,p.doc 
            $addselect ";
    $result = $this->coreFunctions->opentable($query);

    foreach ($result as $key => $value) {

      if ($result[$key]->approver == 'isapprover') {
        $result[$key]->lblforapp = 'For Approver';
      } elseif ($result[$key]->approver == 'issupervisor') {
        $result[$key]->lblforapp = 'For Supervisor';
      } else {
        $result[$key]->lblforapp = '';
      }

      if ($result[$key]->doc == 'HQ' && $result[$key]->approver != '') {
        $result[$key]->lblforapp = '';
        if ($result[$key]->approver == 'DISAPPROVED') {
          $result[$key]->modulename = $result[$key]->modulename . ' (Disapproved)';
        } else {
          $result[$key]->modulename = $result[$key]->modulename . ' (Approved)';
        }
      }

      if ($result[$key]->doc == 'HD') {
        $result[$key]->lblforapp = ''; //APPROVER SEQ
        if ($result[$key]->approver == 'NEWEXPLANATION') {
          $result[$key]->modulename = $result[$key]->modulename . '( New Explanation)';
        }
      }

      if (($result[$key]->doc == 'OB' || $result[$key]->doc == 'TRAVEL') && $result[$key]->approver == 'LATE FILLING') {
        $result[$key]->lblforapp = ''; //APPROVER SEQ
        $result[$key]->modulename = $result[$key]->modulename . '(LATE FILLING)';
      }

      if ($companyid == 29) { //sbc main
        if ($result[$key]->doc == 'PV') {
          $result[$key]->description = 'For Posting';
        }
      }
    }

    return $result;
    // return $this->coreFunctions->opentable("select count(p.line) as appcount, m.labelname as modulename, m.modulename as doc, m.sbcpendingapp from pendingapp as p left join moduleapproval as m on m.modulename=p.doc where p.clientid=".$adminid." group by m.labelname, m.modulename, m.sbcpendingapp");
  }
  public function obapplication()
  {
    $companyid = $this->config['params']['companyid'];
    $getcols = ['action', 'clientname', 'dateid', 'type', 'rem', 'lblforapp'];
    foreach ($getcols as $key => $value) {
      $$value = $key;
    }
    $stockbuttons = ['view'];

    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:20px;whiteSpace: normal;min-width:20px;max-width:20px;';
    $cols[$clientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;max-width:200px;';
    $cols[$dateid]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;max-width:200px;';
    $cols[$type]['style'] = 'width:50px;whiteSpace: normal;min-width:50px;max-width:50px;';
    $cols[$rem]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;max-width:200px;';
    $cols[$action]['btns']['view']['action'] = 'obapplication';
    $cols[$action]['btns']['view']['lookupclass'] = 'customform';
    $cols[$clientname]['label'] = 'Name';
    $cols[$type]['label'] = 'Type';
    if ($companyid) {
      $cols[$rem]['label'] = 'Reason';
    }
    $data = $this->getobapplication();
    $this->config['sbclist']['obapplication'] = ['cols' => $cols, 'data' => $data, 'title' => 'PENDING OB APPLICATION', 'txtfield' => ['col1' => []], 'bgcolor' => 'bg-purple-6', 'textcolor' => 'white'];
  }

  public function getobapplication()
  {
    $approver = $this->checkapprover($this->config);
    $supervisor =  $this->checksupervisor($this->config);
    $companyid = $this->config['params']['companyid'];
    $adminid = $this->config['params']['adminid'];
    $emplvl = $this->othersClass->checksecuritylevel($this->config, true);

    $url = 'App\Http\Classes\modules\payroll\\' . 'obapplication';
    $data = app($url)->approvers($this->config['params']);
    $condition = "";

    $filter = "";
    $underadmin = "";
    $isapp_issup = "";
    $label_status = ",'For Approver' as lblforapp";
    foreach ($data as $key => $value) {
      if (count($data) > 1) {

        if ($key == 0) {
          if ($value == 'issupervisor' && $supervisor || $value == 'isapprover' && $approver) {

            if ($value == 'issupervisor' && $supervisor) {
              $label_status = ",'For Supervisor' as lblforapp";
              $underadmin = " and emp.supervisorid  = $adminid ";
            }
            $condition = " and ob.status2 = 'E' and approvedate2 is null and disapprovedate2 is null $underadmin ";
            break;
          }
        } else {
          if ($value == 'issupervisor' && $supervisor || $value == 'isapprover' && $approver) {
            if ($value == 'issupervisor' && $supervisor) {
              $label_status = ",'For Supervisor' as lblforapp";
            }
            $condition = " and ob.status2 = 'A' and approvedate2 is not null and disapprovedate is null ";
            break;
          }
        } //end else index
      } else {
        if (count($data) == 1) {

          if ($supervisor) {
            $isapp_issup = " and 1=0 ";
          }
          if ($approver) {
            $isapp_issup = " and 1=1 ";
          }
          $condition = " and approvedate is null and disapprovedate is null ";
          break;
        }
      }
    }

    switch ($companyid) {
      case 53: //camera
        $condition .= " and initialstatus = 'A' and submitdate is not null $isapp_issup and emp.level in $emplvl ";
        break;
      // for either or, hr/supervisor 1 setup approver
      case 51: //ulit
        $isapp_issup = '';
        $condition .= " and submitdate is not null ";
        break;
      default:
        // default only the setup can view
        $condition .= $isapp_issup;
        break;
    }

    $filter = " ob.status = 'E' and ob.forapproval is null " . $condition . $isapp_issup;

    $qry = "select ob.line, client.clientname, ob.dateid, ob.type, ob.rem, ob.approverem as remarks,
    ob.approvedby2,emp.supervisorid,ob.ontrip $label_status
    from obapplication as ob left join client on client.clientid=ob.empid
    left join employee as emp on emp.empid = ob.empid where $filter   
    order by dateid, client.clientname";


    return $this->coreFunctions->opentable($qry);
  }

  public function obapplicationInitial()
  {
    $companyid = $this->config['params']['companyid'];
    $getcols = ['action', 'clientname', 'dateid', 'type', 'rem'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:20px;whiteSpace: normal;min-width:20px;max-width:20px;';
    $cols[0]['btns']['view']['action'] = 'obapplicationinitial';
    $cols[0]['btns']['view']['lookupclass'] = 'customform';
    $cols[1]['label'] = 'Name';

    if ($companyid == 53) { // camera
      $cols[4]['label'] = 'Reason';
    }

    $cols[3]['label'] = 'Type';
    $data = $this->getobapplicationInitial();
    $this->config['sbclist']['obapplicationinitial'] = ['cols' => $cols, 'data' => $data, 'title' => 'PENDING OB APPLICATION INITIAL', 'txtfield' => ['col1' => []], 'bgcolor' => 'bg-purple-4', 'textcolor' => 'white'];
  }
  public function getobapplicationInitial()
  {
    $approver = $this->checkapprover($this->config);
    $supervisor =  $this->checksupervisor($this->config);
    $companyid = $this->config['params']['companyid'];
    $adminid = $this->config['params']['adminid'];

    $url = 'App\Http\Classes\modules\payroll\\' . 'obapplication';
    $data = app($url)->approvers($this->config['params']);
    $condition = "";
    $filter = "";
    foreach ($data as $key => $value) {
      if (count($data) > 1) {

        if ($key == 0) {
          if ($value == 'issupervisor' && $supervisor || $value == 'isapprover' && $approver) {
            $condition = " and ob.status2 = 'E' and approvedate2 is null and disapprovedate2 is null ";
            break;
          }
        } else {
          if ($value == 'issupervisor' && $supervisor || $value == 'isapprover' && $approver) {
            $condition = " and ob.status2 = 'A' and approvedate2 is not null and disapprovedate is null ";
            break;
          }
        } //end else index
      } else {
        if (count($data) == 1) {
          $condition = " and approvedate is null and disapprovedate is null ";
          break;
        }
      }
    }
    if ($companyid == 53) { // camera
      $condition .= " and ob.initialstatus = '' and ob.initialapp is not null and (emp.obapp1 = $adminid or emp.obapp2 = $adminid )";
    }
    $filter = " ob.status = 'E' " . $condition;

    $qry = "select ob.line, client.clientname, ob.dateid, ob.type, ob.rem, ob.approverem as remarks,ob.approvedby2
    from obapplication as ob left join client on client.clientid=ob.empid
    left join employee as emp on emp.empid = ob.empid where $filter   
    order by dateid, client.clientname";

    return $this->coreFunctions->opentable($qry);
  }


  public function undertimeapplication($config)
  {
    $companyid = $config['params']['companyid'];
    $getcols = ['action', 'clientname', 'dateid', 'rem'];

    foreach ($getcols as $key => $value) {
      $$value = $key;
    }

    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:10px;whiteSpace: normal;min-width:10px;max-width:10px;';
    $cols[$clientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;max-width:200px;';
    $cols[$dateid]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;max-width:200px;';
    $cols[$rem]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;max-width:200px;';


    $cols[$action]['btns']['view']['action'] = 'undertime';
    $cols[$action]['btns']['view']['lookupclass'] = 'customform';

    $cols[$clientname]['label'] = 'Name';

    $data = $this->getundertimeapplication();
    $this->config['sbclist']['undertime'] = ['cols' => $cols, 'data' => $data, 'title' => 'PENDING UNDERTIME APPLICATION', 'txtfield' => ['col1' => []], 'bgcolor' => 'bg-pink-5', 'textcolor' => 'white'];
  }
  public function getundertimeapplication()
  {
    $url = 'App\Http\Classes\modules\payroll\\' . 'undertime';
    $data = app($url)->approvers($this->config['params']);
    $approver = $this->checkapprover($this->config);
    $supervisor =  $this->checksupervisor($this->config);
    $companyid = $this->config['params']['companyid'];
    $adminid = $this->config['params']['adminid'];
    $condition = "";
    $underadmin = "";
    $filter = "";
    $label_for = " , 'For Approver' as lblforapp";
    foreach ($data as $key => $value) {
      if (count($data) > 1) {

        if ($key == 0) {
          if ($value == 'issupervisor' && $supervisor || $value == 'isapprover' && $approver) {
            if ($value == 'issupervisor' && $supervisor) {
              $label_for = " , 'For Supervisor' as lblforapp";
              $underadmin = " and emp.supervisorid  = $adminid ";
            }
            $condition = " and under.status2 = 'E' and approvedate2 is null and disapprovedate2 is null $underadmin";
            break;
          }
        } else {
          if ($value == 'issupervisor' && $supervisor || $value == 'isapprover' && $approver) {
            $condition = " and under.status2 = 'A' and approvedate2 is not null and approvedate is null and disapprovedate is null";
            break;
          }
        } //end else index
      } else {
        if (count($data) == 1) {
          $condition = " and approvedate is null";
          break;
        }
      }
    }
    $filter = " under.status = 'E' and under.forapproval is null" . $condition;

    $qry = "select under.line, client.clientname, under.dateid, under.rem, under.rem as remarks, 
            under.approvedby2,under.catid,emp.supervisorid,under.dateid2 $label_for
    from undertime as under 
    left join client on client.clientid=under.empid
    left join employee AS emp ON emp.empid = under.empid 
    left join leavecategory as lv on lv.line = under.catid where $filter
    order by dateid, client.clientname";
    return $this->coreFunctions->opentable($qry);
  }
  public function otapplication()
  {
    $getcols = ['action', 'clientname', 'dateid', 'othrs', 'ndiffot', 'rem'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:20px;whiteSpace: normal;min-width:20px;max-width:20px;';

    $cols[0]['btns']['view']['action'] = 'otapplication';
    $cols[0]['btns']['view']['lookupclass'] = 'customform';
    $cols[3]['label'] = 'Applied OT';
    $cols[4]['label'] = 'Applied N-Diff';

    $data = $this->getotapplication();
    $this->config['sbclist']['otapplication'] = ['cols' => $cols, 'data' => $data, 'title' => 'PENDING OT APPLICATION'];
  }

  public function getotapplication()
  {

    $url = 'App\Http\Classes\modules\payroll\\' . 'otapplication';
    $data = app($url)->approvers($this->config['params']);
    $approver = $this->checkapprover($this->config);
    $supervisor = $this->checksupervisor($this->config);

    $condition = '';
    $filter = "";
    foreach ($data as $key => $value) {
      if (count($data) > 1) {

        if ($key == 0) {
          if ($value == 'issupervisor' && $supervisor || $value == 'isapprover' && $approver) {
            $condition = " and otstatus2 = 1 and ot.date_approved_disapproved2 is null";
            break;
          }
        } else {
          if ($value == 'issupervisor' && $supervisor || $value == 'isapprover' && $approver) {
            $condition = " and otstatus2= 2 and ot.date_approved_disapproved2 is not null";
            break;
          }
        } //end else index
      } else {
        if (count($data) == 1) {
          $condition = "";
          break;
        }
      }
    }
    $filter = " and otstatus = 1 " . $condition;
    $qry = "select ot.line, client.clientname, ot.dateid, ot.entryot as othrs, ot.entryndiffot as ndiffot, ot.entryremarks as remarks 
    from timecard as ot 
    left join client on client.clientid=ot.empid where ((otapproved = 0 and ot.othrs <> 0) or (ndiffsapprvd = 0 and ndiffot <> 0)) $filter
    order by dateid, client.clientname ";

    return $this->coreFunctions->opentable($qry);
  }
  public function otapplicationadvance()
  {
    $getcols = ['action', 'clientname', 'dateid', 'othrs', 'othrsextra', 'ndiffot', 'daytype', 'createdate', 'lblforapp'];
    $companyid = $this->config['params']['companyid'];
    foreach ($getcols as $key => $value) {
      $$value = $key;
    }
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:10px;whiteSpace: normal;min-width:10px;max-width:10px;';
    $cols[$clientname]['style'] = 'width:160px;whiteSpace: normal;min-width:160px;max-width:160px;';
    $cols[$dateid]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;max-width:100px;';
    $cols[$othrs]['style'] = 'width:60px;whiteSpace: normal;min-width:60px;max-width:60px;';
    $cols[$othrsextra]['style'] = 'width:60px;whiteSpace: normal;min-width:60px;max-width:60px;';
    $cols[$ndiffot]['style'] = 'width:60px;whiteSpace: normal;min-width:60px;max-width:60px;';
    $cols[$daytype]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;max-width:100px;';
    $cols[$createdate]['style'] = 'width:80px;whiteSpace: normal;min-width:80px;max-width:80px;';

    $cols[$action]['btns']['view']['action'] = 'otapplicationadv';
    $cols[$action]['btns']['view']['lookupclass'] = 'customform';

    $cols[$clientname]['label'] = 'Name';
    $cols[$othrs]['label'] = 'Applied OT';
    $cols[$ndiffot]['label'] = 'Applied N-DIFF OT';

    $cols[$dateid]['align'] = 'text-center';


    $data = $this->getotapplicationadvance();
    $this->config['sbclist']['otapplicationadv'] = ['cols' => $cols, 'data' => $data, 'title' => 'PENDING OT APPLICATION', 'txtfield' => ['col1' => []], 'bgcolor' => 'bg-teal-6', 'textcolor' => 'white'];
  }
  public function getotapplicationadvance()
  {

    $url = 'App\Http\Classes\modules\payroll\\' . 'otapplicationadv';
    $data = app($url)->approvers($this->config['params']);
    $emplvl = $this->othersClass->checksecuritylevel($this->config, true);
    $companyid = $this->config['params']['companyid'];
    $adminid = $this->config['params']['adminid'];
    $approver = $this->checkapprover($this->config);
    $supervisor = $this->checksupervisor($this->config);
    $otapprover = $this->check_otapprover($this->config);
    $condition = '';
    $filter = "";
    $underadmin = "";
    $isapp_issup = "";
    $app_fields = ", 'For OT Approver' as lblforapp";
    $lastapp = false;
    foreach ($data as $key => $value) {
      if (count($data) > 1) {

        if ($key == 0) {
          if ($value == 'issupervisor' && $supervisor || $value == 'isapprover' && $approver || $value == 'isotapprover' && $otapprover) {

            if ($value == 'issupervisor' && $supervisor) {
              $app_fields = ", 'For OT Supervisor' as lblforapp";
              $underadmin = " and emp.supervisorid  = $adminid ";
            }
            if ($value == 'isotapprover' && $otapprover) {
              $app_fields = ", 'For OT Supervisor' as lblforapp";
              $underadmin = " and emp.otsupervisorid = $adminid ";
            }
            $condition = " and otstatus2 = 1 and ot.approvedate2 is null and disapprovedate2 is null $underadmin";
            break;
          }
        } else {
          if ($value == 'issupervisor' && $supervisor || $value == 'isapprover' && $approver) {
            $lastapp = true;
            $condition = " and otstatus2 = 2 and ot.approvedate2 is not null and disapprovedate2 is null";
            break;
          }
        } //end else index
      } else {
        if (count($data) == 1) {
          if ($supervisor) {
            $isapp_issup = " and 1=0 ";
          }
          if ($approver) {
            $isapp_issup = " and 1=1 ";
          }
          $condition = " and approvedate is null ";
          break;
        }
      }
    }
    $filterapp = "";
    switch ($companyid) {
      case 53: //camera
        if ($lastapp) {
          $filterapp = " and (emp.approver1 = $adminid or emp.approver2 = $adminid ) ";
        }
        $condition .= " and ot.submitdate is not null and emp.level in  $emplvl $filterapp ";
        break;
      // for either or, hr/supervisor 1 setup approver
      case 51: //ulit
        $isapp_issup = "";
        $condition .= " and ot.submitdate is not null ";
        break;
      default:
        // default only the setup can view
        $condition .= $isapp_issup;
        break;
    }


    $filter = " and otstatus = 1" . $condition;
    $qry = "select ot.line, client.clientname, date(ot.dateid) as dateid, ot.othrs,ot.othrsextra,emp.empid,emp.divid,
                  ot.batchid,ot.ndiffothrs as ndiffot,date(ot.createdate) as createdate,ot.approvedby2,ot.daytype $app_fields
    from otapplication as ot 
    left join client on client.clientid=ot.empid
    left join employee as emp on emp.empid=ot.empid
    left join batch on batch.line = ot.batchid where ((apothrs = 0 and ot.othrs <> 0) or (ot.apndiffhrs = 0 and ot.apndiffhrs <> 0)) $filter 
    order by dateid, client.clientname";
    return $this->coreFunctions->opentable($qry);
  }
  public function  leaveapplication($config)
  {
    $companyid = $config['params']['companyid'];
    $leavelabel = $this->companysetup->getleavelabel($this->config['params']);

    $getcols = ['action', 'clientname', 'codename', 'dateid', 'effectivity', 'hours', 'remarks', 'lblforapp'];

    foreach ($getcols as $key => $value) {
      $$value = $key;
    }
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:10px;whiteSpace: normal;min-width:10px;max-width:10px;';
    $cols[$clientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;max-width:200px;';
    $cols[$codename]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;max-width:200px;';
    $cols[$dateid]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;max-width:100px;';
    $cols[$effectivity]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;max-width:100px;';
    $cols[$hours]['style'] = 'width:30px;whiteSpace: normal;min-width:30px;max-width:30px;';
    $cols[$remarks]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;max-width:200px;';
    $cols[$action]['btns']['view']['action'] = 'leaveapplication';
    $cols[$action]['btns']['view']['lookupclass'] = 'customform';
    $cols[$hours]['label'] = $leavelabel;
    $cols[$clientname]['label'] = 'Name';
    if ($companyid == 53) { // camera
      $cols[$remarks]['label'] = 'Reason';
    }


    $data = $this->getleaveapplication();
    $this->config['sbclist']['leaveapplication'] = [
      'cols' => $cols,
      'data' => $data,
      'title' => 'PENDING LEAVE APPLICATION',
      'txtfield' => ['col1' => []],
      'bgcolor' => 'bg-light-green-7',
      'textcolor' => 'white'
    ];
  }
  //LEAVESSSS
  public function getleaveapplication()
  {
    $url = 'App\Http\Classes\modules\payrollentry\\' . 'leaveapplicationportalapproval';
    $data = app($url)->approvers($this->config['params']);
    $approver = $this->checkapprover($this->config);
    $supervisor = $this->checksupervisor($this->config);
    $companyid = $this->config['params']['companyid'];
    $adminid = $this->config['params']['adminid'];
    $emplvl = $this->othersClass->checksecuritylevel($this->config, true);
    $filter = "";
    $condition = "";
    $underadmin = "";
    $label_for = ", 'For Approver' as lblforapp";
    $lastapp = false;
    foreach ($data as $key => $value) {

      if (count($data) > 1) {
        if ($key == 0) {
          if ($value == 'issupervisor' && $supervisor || $value == 'isapprover' && $approver) {

            if ($value == 'issupervisor' && $supervisor) {
              $label_for = " , 'For Supervisor' as lblforapp";
              $underadmin = " and emp.supervisorid  = $adminid ";
            } else {
              $lastapp = true;
            }
            $condition = " and l.status2='E' and l.date_approved_disapproved2 is null $underadmin ";
            break;
          }
        } else {

          if ($value == 'issupervisor' && $supervisor || $value == 'isapprover' && $approver) {
            if ($value == 'issupervisor' && $supervisor) {
              $label_for = " , 'For Supervisor' as lblforapp";
            }
            $condition = " and (l.status2='A' or l.status2='P') and l.date_approved_disapproved2 is not null";
            break;
          }
        }
      } else {
        if (count($data) == 1) {
          $condition = " and l.date_approved_disapproved is null";
          break;
        }
      }
    }
    $filteremplvl = "";
    if ($companyid == 53) {
      if ($lastapp) {
        $filteremplvl .= " and (emp.approver1 = $adminid or emp.approver2  = $adminid )";
      }
      $filteremplvl .=  " and emp.level in $emplvl ";
    }
    $filter = "l.status='E' and l.forapproval is null and l.iswindows = 0 " . $condition . $filteremplvl;

    $qry = "select l.status2,ls.trno, l.line, client.clientname, date(l.dateid) as dateid, date(l.effectivity) as effectivity, 
        l.adays as hours, l.remarks,ls.days  as entitled,l.empid,l.approvedby_disapprovedby2 as approvedby2,lv.line as catid,
        acc.codename,emp.supervisorid $label_for
        from leavetrans as l 
        left join leavesetup as ls on ls.trno = l.trno
        left join client on client.clientid=l.empid 
        left join paccount as acc on acc.line=ls.acnoid
        left join leavecategory as lv on lv.line = l.catid
        left join employee as emp on emp.empid = l.empid
        where $filter
        order by client.clientname, l.effectivity";
    return $this->coreFunctions->opentable($qry);
  }

  public function loanapplication()
  {
    $companyid = $this->config['params']['companyid'];
    $getcols = ['action', 'clientname', 'codename', 'dateid', 'amt', 'remarks', 'lblforapp'];
    foreach ($getcols as $key => $value) {
      $$value = $key;
    }
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:20px;whiteSpace: normal;min-width:20px;max-width:20px;';
    $cols[$clientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;max-width:200px;';
    $cols[$dateid]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;max-width:200px;';
    $cols[$codename]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;max-width:200px;';
    $cols[$amt]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;max-width:100px;';
    $cols[$action]['btns']['view']['action'] = 'loanapplication';
    $cols[$action]['btns']['view']['lookupclass'] = 'customform';
    $cols[$clientname]['label'] = 'Name';
    $cols[$codename]['label'] = 'Loan Type';

    if ($companyid == 53) { // camera
      $cols[$remarks]['label'] = 'Reason';
    }

    $data = $this->coreFunctions->opentable($this->loanapplicationqry());
    $this->config['sbclist']['loanapplication'] = ['cols' => $cols, 'data' => $data, 'title' => 'PENDING LOAN APPLICATION', 'txtfield' => ['col1' => []], 'bgcolor' => 'bg-amber-8', 'textcolor' => 'white'];
  }

  private function loanapplicationqry()
  {
    $approver = $this->checkapprover($this->config);
    $supervisor = $this->checksupervisor($this->config);
    $url = 'App\Http\Classes\modules\payroll\\' . 'loanapplicationportal';
    $data = app($url)->approvers($this->config['params']);
    $emplvl = $this->othersClass->checksecuritylevel($this->config, true);
    $filter = "";
    $companyid = $this->config['params']['companyid'];
    $adminid = $this->config['params']['adminid'];
    $condition = "";
    $underadmin = "";
    $label_status = ", 'For Approver' as lblforapp";
    foreach ($data as $key => $value) {

      if (count($data) > 1) {
        if ($key == 0) {
          if ($value == 'issupervisor' && $supervisor || $value == 'isapprover' && $approver) {
            if ($value == 'issupervisor' && $supervisor) {
              $label_status = ",'For Supervisor' as lblforapp";
              $underadmin = " and emp.supervisorid  = $adminid ";
            }
            $condition = " and l.status2='E' and l.date_approved_disapproved is null $underadmin ";
            break;
          }
        } else {

          if ($value == 'issupervisor' && $supervisor || $value == 'isapprover' && $approver) {
            if ($value == 'issupervisor' && $supervisor) {
              $label_status = ",'For Supervisor' as lblforapp";
            }
            $condition = " and l.status2='A' and l.date_approved_disapproved2 is not null";
            break;
          }
        }
      } else {
        if (count($data) == 1) {
          if ($supervisor) {
            $isapp_issup = " and 1=0 ";
          }
          if ($approver) {
            $isapp_issup = " and 1=1 ";
          }
          $condition = " and l.date_approved_disapproved is null";
          break;
        }
      }
    }
    if ($companyid == 53) { // camera
      $condition .= " and l.submitdate is not null $isapp_issup and emp.level in $emplvl and (emp.approver1 = $adminid or emp.approver2  = $adminid )";
    }
    $filter = " l.status='E' " . $condition;
    $qry = "select l.trno, client.clientname, FORMAT(l.amt,2) as amt, p.codename, date(l.effdate) as dateid,
    l.status ,l.approvedby_disapprovedby2 as approvedby2,emp.supervisorid,l.remarks $label_status
    from loanapplication as l left join client on client.clientid=l.empid
    left join paccount as p on p.line=l.acnoid
    left join employee as emp on emp.empid = l.empid where $filter 
    order by client.clientname";
    $this->othersClass->logConsole($qry);
    return $qry;
  }

  public function allapprovedapplication($config)
  {

    $getcols = ['action', 'clientname', 'dateid', 'type'];

    foreach ($getcols as $key => $value) {
      $$value = $key;
    }
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:20px;whiteSpace: normal;min-width:20px;max-width:20px;';
    $cols[$clientname]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;max-width:300px;';
    $cols[$dateid]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;max-width:150px;';
    $cols[$type]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;max-width:100px;';
    $cols[$action]['btns']['view']['action'] = 'allapprovedapplication';
    $cols[$action]['btns']['view']['lookupclass'] = 'customform';
    $cols[$clientname]['label'] = 'Name';
    $cols[$type]['label'] = 'Type';
    $data = $this->allapprovedata($config);
    $this->config['sbclist']['allapprovedapplication'] = ['cols' => $cols, 'data' => $data, 'title' => 'ALL APPLICATION APPROVED', 'txtfield' => ['col1' => []], 'bgcolor' => 'bg-indigo-8', 'textcolor' => 'white'];
  }

  public function allapprovedata($config)
  {

    $all = [];
    $query = "";
    if ($this->checksecurity(5033)) {
      $obqry = $this->obapproved($config);
      array_push($all, $obqry);
    }
    if ($this->checksecurity(5034)) {
      $leaveqry = $this->leaveapproved($config);
      array_push($all, $leaveqry);
    }
    if ($this->checksecurity(5035)) {
      $otqry = $this->otapproved($config);
      array_push($all, $otqry);
    }
    if ($this->checksecurity(5036)) {
      $changeshiftqry = $this->changeshiftapproved($config);
      array_push($all, $changeshiftqry);
    }
    if ($this->checksecurity(5037)) {
      $loanqry = $this->loanapproved($config);
      array_push($all, $loanqry);
    }
    if ($all != '') {
      $i = 0;
      foreach ($all as $eachqry) {
        $i++;
        if (count($all) != $i) {
          $query .=  $eachqry . ' 
        union all 
        ';
        } else {
          $query .=  $eachqry;
        }
      }
    }
    if (!empty($query)) {
      return $this->coreFunctions->opentable($query);
    } else {
      return [];
    }
  }

  public function allapprovedapplication_test($config)
  {

    $getcols = ['action', 'moduletype', 'appcount'];
    foreach ($getcols as $key => $value) {
      $$value = $key;
    }
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['btns']['view']['action'] = 'pendingallapplications';
    $cols[$action]['btns']['view']['lookupclass'] = 'tableentry';

    $cols[$action]['style'] = 'width:20px;whiteSpace: normal;min-width:20px;max-width:20px;';
    $cols[$moduletype]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;max-width:200px;';
    $cols[$appcount]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;max-width:200px;';
    // $cols[$moduletype]['field'] = 'moduletype';
    $cols[$moduletype]['type'] = 'label';
    $data = $this->allapproveddata_test($config);
    $this->config['sbclist']['getpendingallapplication'] = ['cols' => $cols, 'data' => $data, 'title' => 'ALL APPLICATION APPROVED', 'txtfield' => ['col1' => []], 'bgcolor' => 'bg-indigo-8', 'textcolor' => 'white'];
  }

  public function allapproveddata_test($config)
  {

    $adminid = $config['params']['adminid'];
    $checking_leave = $this->othersClass->checkapproversetup($config, $adminid, 'LEAVE', 'emp', true);
    $checking_ob = $this->othersClass->checkapproversetup($config, $adminid, 'OB', 'emp', true);
    $checking_ot = $this->othersClass->checkapproversetup($config, $adminid, 'OT', 'emp', true);
    $checking_loan = $this->othersClass->checkapproversetup($config, $adminid, 'LOAN', 'emp', true);
    $checking_sched = $this->othersClass->checkapproversetup($config, $adminid, 'CHANGESHIFT', 'emp', true);

    $filter_ob = "";
    $leftjoin_ob = "";

    if ($checking_ob['filter'] != "") {
      $filter_ob .= $checking_ob['filter'];
    }
    if ($checking_ob['leftjoin'] != "") {
      $leftjoin_ob .= $checking_ob['leftjoin'];
    }

    $filter_leave = "";
    $leftjoin_leave = "";

    if ($checking_leave['filter'] != "") {
      $filter_leave .= $checking_leave['filter'];
    }
    if ($checking_leave['leftjoin'] != "") {
      $leftjoin_leave .= $checking_leave['leftjoin'];
    }

    $filter_ot = "";
    $leftjoin_ot = "";

    if ($checking_ot['filter'] != "") {
      $filter_ot .= $checking_ot['filter'];
    }
    if ($checking_ot['leftjoin'] != "") {
      $leftjoin_ot .= $checking_ot['leftjoin'];
    }

    $filter_loan = "";
    $leftjoin_loan = "";

    if ($checking_loan['filter'] != "") {
      $filter_loan .= $checking_loan['filter'];
    }
    if ($checking_loan['leftjoin'] != "") {
      $leftjoin_loan .= $checking_loan['leftjoin'];
    }

    $filter_sched = "";
    $leftjoin_sched = "";

    if ($checking_sched['filter'] != "") {
      $filter_sched .= $checking_sched['filter'];
    }
    if ($checking_sched['leftjoin'] != "") {
      $leftjoin_sched .= $checking_sched['leftjoin'];
    }
    $query = "";

    $query_ob = "
    select count(ob.line) as appcount,if(count(ob.line) <> 0,'OB','') as moduletype,'obapplication' as paths,'OB Application' as modulename
    from obapplication as ob 
    left join employee as emp on emp.empid = ob.empid
    $leftjoin_ob 
    where ob.status = 'A' and  datediff(date(now()),ob.approvedate) <= 7  $filter_ob
	  having count(ob.line) > 0 ";


    $query_leave = "
        select count(concat(lt.trno,'',lt.line)) as appcount, if(count(lt.line) <> 0,'LEAVE','') as moduletype,'leaveapplicationportalapproval' as paths,'Leave Application' as modulename
		from leavetrans  as lt
    left join employee as emp on emp.empid = lt.empid
    $leftjoin_leave
		where lt.status in ('A','P') and lt.status2 in ('A','P') and datediff(date(now()),lt.date_approved_disapproved) <= 7 $filter_leave
			 having count(lt.line) > 0 ";



    $query_ot = "
    select count(ot.line) as appcount, if(count(ot.line) <> 0,'OT','') as moduletype, 'otapplicationadv' as paths,'OT Application' as modulename
    from otapplication as ot 
    left join client on client.clientid=ot.empid
    left join batch on batch.line = ot.batchid
    left join employee as emp on emp.empid = ot.empid
    $leftjoin_ot
	  where ot.otstatus = 2 and  datediff(date(now()),ot.approvedate) <= 7 $filter_ot
	    having count(ot.line) > 0 ";


    $query_sched = "	  select count(csapp.line) as line,if(count(csapp.line) <> 0,'CHANGESHIFT','') as moduletype,'changeshiftapplication' as paths,'Changeshift Application' as modulename
    from changeshiftapp as csapp 
    left join employee as emp on emp.empid = csapp.empid
    $leftjoin_sched 
    where csapp.status= 1 and  datediff(date(now()),csapp.approveddate) <= 7 $filter_sched
      having count(csapp.line) > 0 ";

    $query_loan = "
      select count(loan.trno) as line,if(count(loan.trno) <> 0,'LOAN','') as moduletype,'loanapplicationportal' as paths,'Loan Application' as modulename
    from loanapplication as loan 
    left join client on client.clientid=loan.empid
    left join employee as emp on emp.empid = loan.empid
    $leftjoin_loan
    where loan.status='A' and datediff(date(now()),loan.date_approved_disapproved) <= 7 $filter_loan
    having count(loan.trno) > 0 ";


    $all = [];
    $query = "";
    if ($this->checksecurity(5033)) {

      if ($checking_ob['exist']) {
        if ($checking_ob['ishowall']) {
          $leftjoin_ob = "";
          $filter_ob = "";
        }
        array_push($all, $query_ob);
      }
    }
    if ($this->checksecurity(5034)) {
      if ($checking_leave['exist']) {
        if ($checking_leave['ishowall']) {
          $leftjoin_leave = "";
          $filter_leave = "";
        }
        array_push($all, $query_leave);
      }
    }
    if ($this->checksecurity(5035)) {
      if ($checking_ot['exist']) {
        if ($checking_ot['ishowall']) {
          $leftjoin_ot = "";
          $filter_ot = "";
        }
        array_push($all, $query_ot);
      }
    }
    if ($this->checksecurity(5036)) {
      if ($checking_sched['exist']) {
        if ($checking_sched['ishowall']) {
          $leftjoin_sched = "";
          $filter_sched = "";
        }
        array_push($all, $query_sched);
      }
    }
    if ($this->checksecurity(5037)) {
      if ($checking_loan['exist']) {
        if ($checking_loan['ishowall']) {
          $leftjoin_loan = "";
          $filter_loan = "";
        }
        array_push($all, $query_loan);
      }
    }
    if ($all != '') {
      $i = 0;
      foreach ($all as $eachqry) {
        $i++;
        if (count($all) != $i) {
          $query .=  $eachqry . ' 
        union all 
        ';
        } else {
          $query .=  $eachqry;
        }
      }
    }
    if (!empty($query)) {
      return $this->coreFunctions->opentable($query);
    } else {
      return [];
    }

    $this->coreFunctions->opentable($query);
  }
  public function changeshiftapproved($config)
  {
    $adminid = $config['params']['adminid'];
    $checking = $this->othersClass->checkapproversetup($config, $adminid, 'CHANGESHIFT', 'emp');

    $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='CHANGESHIFT'");
    $approversetup = explode(',', $approversetup);
    $countsetup = count($approversetup);

    $filter = "";
    $leftjoin = "";
    if ($checking['filter'] != "") {
      $filter .= $checking['filter'];
    }
    if ($checking['leftjoin'] != "") {
      $leftjoin .= $checking['leftjoin'];
    }


    $qry = "select '$countsetup' as seqcount, cs.line, client.clientname, date(cs.dateid) as dateid,cs.empid,'SCHED' as type
    from changeshiftapp as cs 
    left join client on client.clientid=cs.empid 
    left join employee as emp on emp.empid = cs.empid
    $leftjoin
    where cs.status= 1 and  datediff(date(now()),cs.approveddate) <= 7 $filter ";
    return $qry;
  }
  public function leaveapproved($config)
  {
    $adminid = $config['params']['adminid'];
    $checking = $this->othersClass->checkapproversetup($config, $adminid, 'LEAVE', 'emp');
    $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='LEAVE'");
    $approversetup = explode(',', $approversetup);
    $countsetup = count($approversetup);

    $filter = "";
    $leftjoin = "";
    if ($checking['filter'] != "") {
      $filter .= $checking['filter'];
    }
    if ($checking['leftjoin'] != "") {
      $leftjoin .= $checking['leftjoin'];
    }

    $qry = "select '$countsetup' as seqcount,concat(l.trno,'~',l.line) as line ,client.clientname, date(l.effectivity) as dateid,l.empid,'LEAVE' as type
		  from leavetrans  as l 
      left join client on client.clientid=l.empid
      left join employee as emp on emp.empid = l.empid
      $leftjoin 
		  where l.status in ('A','P') and l.status2 in ('A','P') and datediff(date(now()),l.date_approved_disapproved) <= 7 $filter  
      ";
    return $qry;
  }
  public function obapproved($config)
  {
    $adminid = $config['params']['adminid'];
    $checking = $this->othersClass->checkapproversetup($config, $adminid, 'OB', 'emp');

    $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='OB'");
    $approversetup = explode(',', $approversetup);
    $countsetup = count($approversetup);

    $filter = "";
    $leftjoin = "";
    if ($checking['filter'] != "") {
      $filter .= $checking['filter'];
    }
    if ($checking['leftjoin'] != "") {
      $leftjoin .= $checking['leftjoin'];
    }

    $qry = "select '$countsetup' as seqcount,ob.line, client.clientname, date(ob.dateid) as dateid,ob.empid, 'OB' as type
    from obapplication as ob 
    left join client on client.clientid=ob.empid
    left join employee as emp on emp.empid = ob.empid
    $leftjoin
    where ob.status = 'A' and  datediff(date(now()),ob.approvedate) <= 7 $filter  
    ";
    return $qry;
  }
  public function otapproved($config)
  {
    $adminid = $config['params']['adminid'];
    $checking = $this->othersClass->checkapproversetup($config, $adminid, 'OT', 'emp');
    $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='OT'");
    $approversetup = explode(',', $approversetup);
    $countsetup = count($approversetup);

    $filter = "";
    $leftjoin = "";
    if ($checking['filter'] != "") {
      $filter .= $checking['filter'];
    }
    if ($checking['leftjoin'] != "") {
      $leftjoin .= $checking['leftjoin'];
    }
    $qry = "select '$countsetup' as seqcount,ot.line, client.clientname, date(ot.dateid) as dateid,ot.empid,'OT' AS type
    from otapplication as ot 
    left join client on client.clientid=ot.empid
    left join batch on batch.line = ot.batchid
    left join employee as emp on emp.empid = ot.empid
    $leftjoin 
	  where ot.otstatus = 2 and  datediff(date(now()),ot.approvedate) <= 7 $filter   
      ";
    return $qry;
  }
  public function loanapproved($config)
  {
    $adminid = $config['params']['adminid'];
    $checking = $this->othersClass->checkapproversetup($config, $adminid, 'LOAN', 'emp');
    $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='LOAN'");
    $approversetup = explode(',', $approversetup);
    $countsetup = count($approversetup);

    $filter = "";
    $leftjoin = "";
    if ($checking['filter'] != "") {
      $filter .= $checking['filter'];
    }
    if ($checking['leftjoin'] != "") {
      $leftjoin .= $checking['leftjoin'];
    }
    $qry = "select '$countsetup' as seqcount,l.trno as line, client.clientname,date(l.effdate) as dateid,l.empid,'LOAN' AS type
      from loanapplication as l 
      left join client on client.clientid=l.empid
      left join employee as emp on emp.empid = l.empid
      $leftjoin 
      where l.status='A' and datediff(date(now()),l.date_approved_disapproved) <= 7 $filter   ";
    return $qry;
  }
  public function getrestday()
  {
    $url = 'App\Http\Classes\modules\payroll\\' . 'changeshiftapplication';
    $data = app($url)->approvers($this->config['params']);

    $approver = $this->checkapprover($this->config);
    $supervisor =  $this->checksupervisor($this->config);
    $adminid =  $this->config['params']['adminid'];
    $condition = '';
    $underadmin = "";
    $label_for = " , 'For Approver' as lblforapp";
    foreach ($data as $key => $value) {
      if (count($data) > 1) {

        if ($key == 0) {
          if ($value == 'issupervisor' && $supervisor || $value == 'isapprover' && $approver) {
            if ($value == 'issupervisor' && $supervisor) {
              $label_for = " , 'For Supervisor' as lblforapp";
              $underadmin = " and emp.supervisorid  = $adminid ";
            }
            $condition = " and cs.status2=0 and cs.approveddate2 is null $underadmin ";
            break;
          }
        } else {
          if ($value == 'issupervisor' && $supervisor || $value == 'isapprover' && $approver) {
            $condition = " and cs.status2=1 and cs.approveddate2 is not null";
            break;
          }
        }
      } else {
        if (count($data) == 1) {
          $condition = " and cs.approveddate is null";
        }
      }
    }
    $qry = " select cs.line, client.clientname, date(cs.dateid) as dateid,cs.empid,date(cs.createdate) as createdate,cs.orgdaytype as daytype,
    emp.supervisorid,cs.approvedby2 $label_for
    from changeshiftapp as cs
    left join client on client.clientid=cs.empid
    left join employee as emp on emp.empid = cs.empid
    where cs.status = 0 and isrestday = 1 $condition ";
    return  $this->coreFunctions->opentable($qry);
  }

  public function restday()
  {
    $getcols = ['action', 'clientname', 'daytype', 'createdate', 'dateid', 'lblforapp'];
    foreach ($getcols as $key => $value) {
      $$value = $key;
    }
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:20px;whiteSpace: normal;min-width:20px;max-width:20px;';
    $cols[$clientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;max-width:200px;';
    $cols[$createdate]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;max-width:200px;';
    $cols[$dateid]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;max-width:200px;';
    $cols[$daytype]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;max-width:100px;';
    $cols[$action]['btns']['view']['action'] = 'restday';
    $cols[$action]['btns']['view']['lookupclass'] = 'customform';
    $cols[$clientname]['label'] = 'Name';
    $cols[$dateid]['label'] = 'Effectivity Date';

    $data = $this->getrestday();
    $this->config['sbclist']['restday'] = ['cols' => $cols, 'data' => $data, 'title' => 'REST DAY', 'txtfield' => ['col1' => []], 'bgcolor' => 'bg-lime-8', 'textcolor' => 'white'];
  }
  public function getword()
  {
    $url = 'App\Http\Classes\modules\payroll\\' . 'changeshiftapplication';
    $data = app($url)->approvers($this->config['params']);

    $approver = $this->checkapprover($this->config);
    $supervisor =  $this->checksupervisor($this->config);
    $adminid =  $this->config['params']['adminid'];
    $condition = '';
    $underadmin = "";
    $label_for = " , 'For Approver' as lblforapp";
    foreach ($data as $key => $value) {
      if (count($data) > 1) {

        if ($key == 0) {
          if ($value == 'issupervisor' && $supervisor || $value == 'isapprover' && $approver) {
            if ($value == 'issupervisor' && $supervisor) {
              $label_for = " , 'For Supervisor' as lblforapp";
              $underadmin = " and emp.supervisorid  = $adminid ";
            }
            $condition = " and cs.status2=0 and cs.approveddate2 is null $underadmin ";
            break;
          }
        } else {
          if ($value == 'issupervisor' && $supervisor || $value == 'isapprover' && $approver) {
            $condition = " and cs.status2=1 and cs.approveddate2 is not null";
            break;
          }
        }
      } else {
        if (count($data) == 1) {
          $condition = " and cs.approveddate is null";
          break;
        }
      }
    }
    $qry = " select cs.line, client.clientname, date(cs.dateid) as dateid,cs.empid,date(cs.createdate) as createdate,cs.orgdaytype as daytype,cs.approvedby2 $label_for
    from changeshiftapp as cs
    left join client on client.clientid=cs.empid
    left join employee as emp on emp.empid = cs.empid
    where cs.status = 0 and isword = 1 $condition ";
    return  $this->coreFunctions->opentable($qry);
  }
  public function word()
  {
    $getcols = ['action', 'clientname', 'daytype', 'createdate', 'dateid'];
    foreach ($getcols as $key => $value) {
      $$value = $key;
    }
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:20px;whiteSpace: normal;min-width:20px;max-width:20px;';
    $cols[$clientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;max-width:200px;';
    $cols[$createdate]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;max-width:200px;';
    $cols[$dateid]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;max-width:200px;';
    $cols[$daytype]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;max-width:100px;';
    $cols[$action]['btns']['view']['action'] = 'word';
    $cols[$action]['btns']['view']['lookupclass'] = 'customform';
    $cols[$clientname]['label'] = 'Name';
    $cols[$dateid]['label'] = 'Effectivity Date';
    $data = $this->getword();
    $this->config['sbclist']['word'] = ['cols' => $cols, 'data' => $data, 'title' => 'WORK ON RESTDAY', 'txtfield' => ['col1' => []], 'bgcolor' => 'bg-lime-9', 'textcolor' => 'white'];
  }
  public function getcancellation() // leave
  {

    $url = 'App\Http\Classes\modules\payrollentry\\' . 'leaveapplicationportalapproval';
    $data = app($url)->approvers($this->config['params']);
    $approver = $this->checkapprover($this->config);
    $supervisor = $this->checksupervisor($this->config);
    $adminid = $this->config['params']['adminid'];
    $condition = "";
    foreach ($data as $key => $value) {
      if (count($data) > 1) {
        if ($key == 0) { // supervisor
          if ($value == 'issupervisor' && $supervisor) {
            $condition = " and emp.supervisorid  = $adminid ";
            break;
          }
        }
      }
    }
    $query = "select date(lt.dateid) as dateid,cl.clientname,lt.trno,lt.line,lt.empid,
                     dept.clientname AS deptname,p.codename,date(lt.effectivity) as dateeffect 
              from leavetrans as lt
              left join leavesetup as ls on ls.trno = lt.trno
              left join paccount as p on p.line=ls.acnoid
              left join employee as emp on emp.empid = lt.empid
              left join client as cl on cl.clientid = lt.empid
              left join client as dept on dept.clientid = emp.deptid 
              where lt.status = 'E' and lt.forapproval is not null and canceldate is null $condition ";
    return $this->coreFunctions->opentable($query);
  }

  public function leavecancellation()
  {
    $getcols = ['action', 'clientname', 'codename', 'dateid', 'dateeffect'];
    foreach ($getcols as $key => $value) {
      $$value = $key;
    }
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:20px;whiteSpace: normal;min-width:20px;max-width:20px;';
    $cols[$clientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;max-width:200px;';
    $cols[$codename]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;max-width:200px;';
    $cols[$dateid]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;max-width:100px;';
    $cols[$dateeffect]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;max-width:100px;';
    $cols[$action]['btns']['view']['action'] = 'leavecancellation';
    $cols[$action]['btns']['view']['lookupclass'] = 'customform';
    $cols[$clientname]['label'] = 'Name';
    $cols[$dateid]['label'] = 'Date Applied';

    $data = $this->getcancellation();
    $this->config['sbclist']['leavecancellation'] = ['cols' => $cols, 'data' => $data, 'title' => 'LEAVE CANCELLATION', 'txtfield' => ['col1' => []], 'bgcolor' => 'bg-light-green-6', 'textcolor' => 'white'];
  }
  public function get_undertime_cancellation()
  {
    $url = 'App\Http\Classes\modules\payroll\\' . 'undertime';
    $data = app($url)->approvers($this->config['params']);
    $approver = $this->checkapprover($this->config);
    $supervisor =  $this->checksupervisor($this->config);
    $adminid = $this->config['params']['adminid'];
    $condition = "";
    foreach ($data as $key => $value) {
      if (count($data) > 1) {
        if ($key == 0) { // supervisor
          if ($value == 'issupervisor' && $supervisor) {
            $condition = " and emp.supervisorid  = $adminid ";
            break;
          }
        }
      }
    }
    $query = "
        select ut.line as trno,ut.line,concat(emp.emplast,', ',emp.empfirst,' ',emp.empmiddle) as clientname,
        dept.clientname as deptname,ut.forapproval,date(ut.dateid) as dateid,jt.jobtitle,ut.rem
        from undertime as ut
        left join employee as emp on emp.empid = ut.empid
        left join jobthead as jt on jt.line = emp.jobid
        left join client as dept on dept.clientid = emp.deptid
        where ut.status = 'E' and ut.forapproval is not null and canceldate is null $condition ";
    return $this->coreFunctions->opentable($query);
  }
  public function undertimecancellation()
  {

    $getcols = ['action', 'clientname', 'dateid'];
    foreach ($getcols as $key => $value) {
      $$value = $key;
    }
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:20px;whiteSpace: normal;min-width:20px;max-width:20px;';
    $cols[$clientname]['style'] = 'width:500px;whiteSpace: normal;min-width:500px;max-width:500px;';
    $cols[$dateid]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;max-width:200px;';
    $cols[$action]['btns']['view']['action'] = 'undertimecancellation';
    $cols[$action]['btns']['view']['lookupclass'] = 'customform';
    $cols[$clientname]['label'] = 'Name';
    $cols[$dateid]['label'] = 'Date Applied';

    $data = $this->get_undertime_cancellation();
    $this->config['sbclist']['undertimecancellation'] = ['cols' => $cols, 'data' => $data, 'title' => 'UNDERTIME CANCELLATION', 'txtfield' => ['col1' => []], 'bgcolor' => 'bg-pink-4', 'textcolor' => 'white'];
  }
  public function get_obcancellation()
  {
    $approver = $this->checkapprover($this->config);
    $supervisor =  $this->checksupervisor($this->config);
    $companyid = $this->config['params']['companyid'];
    $adminid = $this->config['params']['adminid'];
    $url = 'App\Http\Classes\modules\payroll\\' . 'obapplication';
    $data = app($url)->approvers($this->config['params']);
    $condition = "";

    foreach ($data as $key => $value) {
      if (count($data) > 1) {

        if ($key == 0) {
          if ($value == 'issupervisor' && $supervisor) {
            $condition = " and emp.supervisorid  = $adminid ";
            break;
          }
        }
      }
    }

    $query = "
        select ob.line as trno,ob.line,cl.clientname,
        dept.clientname as deptname,ob.forapproval,date(ob.dateid) as dateid,ob.reason, ob.type,ob.ontrip
        from obapplication as ob
        left join employee as emp on emp.empid = ob.empid
        left join client as cl on cl.clientid = ob.empid
        left join client as dept on dept.clientid = emp.deptid
        where ob.status = 'E' and ob.forapproval is not null and canceldate is null $condition";
    return $this->coreFunctions->opentable($query);
  }

  public function obcancellation()
  {
    $getcols = ['action', 'clientname', 'dateid', 'type', 'ontrip',];
    foreach ($getcols as $key => $value) {
      $$value = $key;
    }
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:20px;whiteSpace: normal;min-width:20px;max-width:20px;';
    $cols[$clientname]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;max-width:300px;';
    $cols[$dateid]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;max-width:100px;';
    $cols[$type]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;max-width:100px;';
    $cols[$ontrip]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;max-width:100px;';
    $cols[$action]['btns']['view']['action'] = 'obcancellation';
    $cols[$action]['btns']['view']['lookupclass'] = 'customform';
    $cols[$clientname]['label'] = 'Name';
    $cols[$type]['label'] = 'Type';
    $cols[$dateid]['label'] = 'Date';


    $data = $this->get_obcancellation();
    $this->config['sbclist']['obcancellation'] = ['cols' => $cols, 'data' => $data, 'title' => 'OB CANCELLATION', 'txtfield' => ['col1' => []], 'bgcolor' => 'bg-purple-4', 'textcolor' => 'white'];
  }
  public function timerec()
  {
    $getcols = ['dateid', 'semtime'];
    $stockbuttons = [];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $qry = "select date(timeinout) as dateid, time(timeinout) as semtime  from timerec where userid='" . $this->config['params']['user'] . "' order by timeinout desc limit 60";
    $data = $this->coreFunctions->opentable($qry);
    $this->config['sbclist']['timerec'] = ['cols' => $cols, 'data' => $data, 'title' => 'TIME-IN RECORDS'];
  }

  public function vehicleschedule()
  {
    $docno = 0;
    $clientname = 1;
    $plateno = 2;
    $driver = 3;
    $schedin = 4;
    $schedout = 5;

    $getcols = ['docno', 'clientname', 'plateno', 'driver', 'schedin', 'schedout'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$clientname]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;max-width:100px;';
    $cols[$driver]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;max-width:100px;';
    $cols[$schedin]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;max-width:100px;';
    $cols[$schedout]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;max-width:100px;';

    $cols[$clientname]['label'] = 'Vehicle';
    $cols[$schedin]['label'] = 'Start Time';
    $cols[$schedout]['label'] = 'End Time';

    if ($this->checksecurity(3717)) {
      $filterdept = '';
    } else {
      $deptid = $this->coreFunctions->getfieldvalue("client", "deptid", "clientid=?", [$this->config['params']['adminid']]);
      if ($deptid == '') {
        $deptid = 0;
      }
      $filterdept = ' and h.deptid=' . $deptid;
    }

    $qry = "select h.docno, client.clientname, client.plateno, h.schedin, h.schedout, d.clientname as driver
        from vrhead as h left join client on client.clientid=h.vehicleid left join client as d on d.clientid=h.driverid
        where h.vehicleid<>0 " . $filterdept . " order by h.schedin";

    $data = $this->coreFunctions->opentable($qry);

    $this->config['sbclist']['vehicleschedule'] = ['cols' => $cols, 'data' => $data, 'title' => 'Vehicle Schedules'];
  }

  public function vehiclerequest()
  {
    $stat = 0;
    $listdocument = 1;
    $listdate = 2;
    $clientname = 3;
    $schedin = 4;
    $schedout = 5;
    $getcols = ['lblstatus', 'listdocument', 'listdate', 'clientname', 'schedin', 'schedout'];
    $stockbuttons = [];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$schedin]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;max-width:100px;';
    $cols[$schedout]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;max-width:100px;';

    $cols[$clientname]['label'] = 'Employee';
    $cols[$schedin]['label'] = 'Start Time';
    $cols[$schedout]['label'] = 'End Time';

    $center = $this->config['params']['center'];
    $userid = $this->config['params']['adminid'];

    $deptid =   $this->coreFunctions->getfieldvalue("client", "deptid", "clientid=?", [$userid]);
    if ($deptid == "") {
      $deptid = 0;
    }

    $filterdept = " and emp.deptid=" . $deptid;
    if ($this->checksecurity(3590)) {
      $filterdept = '';
    }

    $qry = "select 
    head.docno, 
    left(head.dateid,10) as dateid, 
    head.schedin,
    head.schedout, 
    emp.clientname,
    ifnull(stat.status,'Draft') as stat
    from vrhead as head
    left join transnum as num on num.trno = head.trno 
    left join client as emp on emp.clientid = head.clientid
    left join trxstatus as stat on stat.line=num.statid
    where num.doc='VR' and num.center = '" . $center . "' and head.approveddate is null " . $filterdept;

    $data = $this->coreFunctions->opentable($qry);

    $this->config['sbclist']['vehiclerequest'] = ['cols' => $cols, 'data' => $data, 'title' => 'Schedule Requests'];
  }

  public function vehiclerequestwithoutvehicle()
  {
    $listdocument = 0;
    $clientname = 1;
    $schedin = 2;
    $schedout = 3;

    $getcols = ['listdocument', 'clientname', 'schedin', 'schedout'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$clientname]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;max-width:100px;';
    $cols[$schedin]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;max-width:100px;';
    $cols[$schedout]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;max-width:100px;';

    $cols[$clientname]['label'] = 'Employee';
    $cols[$schedin]['label'] = 'Start Time';
    $cols[$schedout]['label'] = 'End Time';

    if ($this->checksecurity(3717)) {
      $filterdept = '';
    } else {
      $deptid = $this->coreFunctions->getfieldvalue("client", "deptid", "clientid=?", [$this->config['params']['adminid']]);
      if ($deptid == '') {
        $deptid = 0;
      }
      $filterdept = ' and h.deptid=' . $deptid;
    }

    $qry = "select h.docno, client.clientname, h.schedin, h.schedout
        from vrhead as h left join client on client.clientid=h.clientid
        left join transnum as num on num.trno=h.trno
        where h.vehicleid=0 and num.statid=11 " . $filterdept . " order by h.schedin";

    $data = $this->coreFunctions->opentable($qry);
    $this->config['sbclist']['vehiclerequestwithoutvehicle'] = ['cols' => $cols, 'data' => $data, 'title' => 'Approved Request without Vehicle'];
  }


  //type
  //1.display - no happenings, just display
  //2.customform - may pop up form 

  private function ap()
  {
    $center = $this->config['params']['center'];
    $qry = 'select ifnull(format(sum(case when apledger.cr<>0 then 1 else -1 end * apledger.bal),2),0) as value from  apledger left join cntnum on cntnum.trno=apledger.trno where cntnum.center=? and apledger.bal>0';
    $pap = $this->coreFunctions->datareader($qry, [$center]);
    $qry = 'select ifnull(format(sum(stock.ext),2),0) as value from lahead as head left join lastock as stock on stock.trno=head.trno left join cntnum on cntnum.trno=head.trno where cntnum.center=? and head.doc=? and stock.ext>0';
    $uap = $this->coreFunctions->datareader($qry, [$center, 'RR']);
    $pap2 = str_replace(',', '', $pap);
    $uap2 = str_replace(',', '', $uap);
    $total = number_format(floatval($pap2) + floatval($uap2), 2);
    $this->config['qcard']['ap'] =
      [
        'class' => 'bg-primary text-white',
        'headalign' => 'right',
        'title' => $total,
        'subtitle' => 'Accounts Payable',
        'object' => 'btn',
        'isvertical' => true,
        'align' => 'right',
        'detail' => [
          'btn1' => [
            'label' => 'Posted ' . $pap,
            'type' => 'customform',
            'action' => 'ap',
            'classid' => 'posted'
          ],
          'btn2' => [
            'label' => 'Unposted ' . $uap,
            'type' => 'customform',
            'action' => 'ap',
            'classid' => 'unposted'
          ]
        ]
      ];
  } // end function

  private function ar()
  {
    $center = $this->config['params']['center'];
    $qry = 'select ifnull(format(sum(case when arledger.db then 1 else -1 end * arledger.bal),2),0) as value from  arledger join cntnum on cntnum.trno=arledger.trno where cntnum.center=? and arledger.bal>0';
    $pap = $this->coreFunctions->datareader($qry, [$center]);
    $qry = 'select ifnull(format(sum(stock.ext),2),0) as value from lahead as head join lastock as stock on stock.trno=head.trno join cntnum on cntnum.trno=head.trno where cntnum.center=? and head.doc=? and stock.ext>0';
    $uap = $this->coreFunctions->datareader($qry, [$center, 'SJ']);
    $pap2 = str_replace(',', '', $pap);
    $uap2 = str_replace(',', '', $uap);
    $total = number_format(floatval($pap2) + floatval($uap2), 2);
    $color = 'bg-purple text-white';
    if ($this->config['params']['companyid'] == 55) {
      $color = 'bg-blue text-white';
    }

    $this->config['qcard']['ar'] =
      [
        'class' => $color,
        'headalign' => 'right',
        'title' => $total,
        'subtitle' => 'Accounts Receivable',
        'object' => 'btn',
        'isvertical' => true,
        'align' => 'right',
        'detail' => [
          'btn1' => [
            'label' => 'Posted ' . $pap,
            'type' => 'customform',
            'action' => 'ar',
            'classid' => 'posted'
          ],
          'btn2' => [
            'label' => 'Unposted ' . $uap,
            'type' => 'customform',
            'action' => 'ar',
            'classid' => 'unposted'
          ]
        ]
      ];
  } // end function

  private function modulecount($doc, $color, $inventory)
  {
    // inventory set true or false
    $center = $this->config['params']['center'];
    $dateid = date('Y-m-d');
    if ($inventory) {
      $qry = "select ifnull(count(glhead.trno), 0) as counting from glhead join cntnum on cntnum.trno=glhead.trno where glhead.doc = '" . $doc . "' and cntnum.center='" . $center . "' and date(glhead.dateid) = '" . $dateid . "'";
      $pap = $this->coreFunctions->opentable($qry);
      $qry1 = "select ifnull(count(lahead.trno), 0) as counting from lahead join cntnum on cntnum.trno=lahead.trno where lahead.doc = '" . $doc . "' and cntnum.center='" . $center . "' and date(lahead.dateid) = '" . $dateid . "'";
      $uap = $this->coreFunctions->opentable($qry1);
    } else {
      $ldoc = strtolower($doc);
      $qry = "select ifnull(count(head.trno), 0) as counting from h" . $ldoc . "head as head join transnum on transnum.trno=head.trno where head.doc = '" . $doc . "' and transnum.center='" . $center . "' and date(head.dateid) = '" . $dateid . "'";
      $pap = $this->coreFunctions->opentable($qry);
      $qry1 = "select ifnull(count(head.trno), 0) as counting from " . $ldoc . "head as head join transnum on transnum.trno=head.trno where head.doc = '" . $doc . "' and transnum.center='" . $center . "' and date(head.dateid) = '" . $dateid . "'";
      $uap = $this->coreFunctions->opentable($qry1);
    }
    $total = $pap[0]->counting + $uap[0]->counting;

    $this->config['qcard'][$doc] =
      [
        'class' => 'bg-' . $color . ' text-white',
        'headalign' => 'right',
        'title' => $doc . ' Transaction',
        'subtitle' => $dateid . ' - ' . $total,
        'object' => 'btn',
        'isvertical' => true,
        'align' => 'right',
        'detail' => [
          'btn1' => [
            'label' => 'Posted ' . $pap[0]->counting,
            'type' => 'customform',
            'action' => $doc,
            'classid' => 'posted'
          ],
          'btn2' => [
            'label' => 'Unposted ' . $uap[0]->counting,
            'type' => 'customform',
            'action' => $doc,
            'classid' => 'unposted'
          ]
        ]
      ];
  } // end function

  private function salesgraph()
  {
    $systemtype = $this->companysetup->getsystemtype($this->config['params']);

    if ($systemtype == 'ATI') return;

    $dateid = date('Y-m-d');
    $year = date('Y');
    $center = $this->config['params']['center'];
    $allowall = $this->othersClass->checkAccess($this->config['params']['user'], 4077);
    $adminid =  $this->config['params']['adminid'];
    $leftjoin = "";
    $pleftjoin = "";
    $condition = "";

    if ($systemtype == 'EAPPLICATION') {
      $agentfilter = "";
      if ($allowall == '0') {
        if ($adminid != 0) {
          $isleader = $this->coreFunctions->getfieldvalue("client", "isleader", "clientid=?", [$adminid]);
          if (floatval($isleader) == 1) {
            $agentfilter = " and (lead.clientid = " . $adminid . " or  ag.clientid =  " . $adminid . ") ";
          } else {
            $agentfilter = " and ag.clientid = " . $adminid . " ";
          }
        }
      }

      $qry = "select m,sum(amt) as amt from (select month(head.dateid) as m,sum(stock.cr) as amt from
      glhead as head join gldetail as stock
      on stock.trno=head.trno left join coa on coa.acnoid = stock.acnoid 
      left join client as ag on ag.clientid = head.agentid left join client as lead on lead.clientid = ag.parent 
      join cntnum on cntnum.trno=head.trno where head.doc='CP' and
      year(dateid)='" . $year . "' and left(coa.alias,2)='SA' " . $agentfilter . " group by month(head.dateid)
      UNION ALL
      select month(head.dateid),sum(stock.cr) from lahead as head
      join ladetail as stock
      on stock.trno=head.trno left join coa on coa.acnoid = stock.acnoid 
      left join client as ag on ag.client = head.agent left join client as lead on lead.clientid = ag.parent 
      join cntnum on cntnum.trno=head.trno where head.doc='CP' and
      year(dateid)='" . $year . "' and left(coa.alias,2)='SA' " . $agentfilter . " group by month(head.dateid)) as T group by m";
    } else {

      $addunion = '';
      if ($this->config['params']['companyid'] == 3) { //conti
        $addunion = " union all
            select month(head.dateid) as m,sum(stock.cr) as amt from
            glhead as head join gldetail as stock
            on stock.trno=head.trno left join coa on coa.acnoid = stock.acnoid 
            left join client as ag on ag.clientid = head.agentid left join client as lead on lead.clientid = ag.parent 
            join cntnum on cntnum.trno=head.trno where head.doc<>'SJ' and
            year(dateid)='" . $year . "' and left(coa.alias,2)='SA'  group by month(head.dateid)
            UNION ALL
            select month(head.dateid),sum(stock.cr) from lahead as head
            join ladetail as stock
            on stock.trno=head.trno left join coa on coa.acnoid = stock.acnoid 
            left join client as ag on ag.client = head.agent left join client as lead on lead.clientid = ag.parent 
            join cntnum on cntnum.trno=head.trno where head.doc<>'SJ' and
            year(dateid)='" . $year . "' and left(coa.alias,2)='SA' group by month(head.dateid)";
      }

      if ($this->config['params']['companyid'] == 36) {  //rozlab
        $addunion = " union all
            select month(head.dateid) as m,sum(stock.ext)*-1 as amt from 
            glhead as head join glstock as stock on stock.trno=head.trno join cntnum on cntnum.trno=head.trno where head.doc='CM' and 
            year(dateid)='" . $year . "' group by month(head.dateid)
            UNION ALL
            select month(head.dateid),sum(stock.ext)*-1 from lahead as head 
            join lastock as stock on stock.trno=head.trno join cntnum on cntnum.trno=head.trno where head.doc='CM' and 
            year(dateid)='" . $year . "' group by month(head.dateid)";
      }

      if ($this->config['params']['companyid'] == 47) { //kstar
        if ($allowall == '0') {
          if ($adminid != 0) {
            $leftjoin = " left join client as ag on ag.client = head.agent  ";
            $pleftjoin = " left join client as ag on ag.clientid = head.agentid  ";
            $condition  = " and ag.clientid = " . $adminid . " ";
          }
        }
      }


      $qry = "select m,sum(amt) as amt from (select month(head.dateid) as m,sum(stock.ext) as amt from 
        glhead as head join glstock as stock 
        on stock.trno=head.trno join cntnum on cntnum.trno=head.trno " . $pleftjoin . " where head.doc='SJ' and 
        year(dateid)='" . $year . "' " . $condition . " group by month(head.dateid)
        UNION ALL
        select month(head.dateid),sum(stock.ext) from lahead as head 
        join lastock as stock
        on stock.trno=head.trno join cntnum on cntnum.trno=head.trno " . $leftjoin . " where head.doc='SJ' and 
        year(dateid)='" . $year . "' " . $condition . " group by month(head.dateid)
        $addunion
        ) as T group by m";
    }
    $data = $this->coreFunctions->opentable($qry);
    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    $graphdata = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    foreach ($data as $key => $value) {
      $graphdata[$data[$key]->m - 1] = $data[$key]->amt;
    }

    //type of graph
    //1.bar
    //2.line
    //3.radar
    $series = [['name' => 'Total', 'data' => $graphdata]];
    $option = [
      'chart' => ['type' => 'bar', 'height' => 300],
      'plotOptions' => ['bar' => ['horizontal' => false, 'columnWidth' => '55%', 'endingShape' => 'rounded']],
      'title' => ['text' => 'Sales ' . $year, 'align' => 'left', 'style' => ['color' => 'black']],
      'dataLabels' => ['enabled' => false],
      'stroke' => ['show' => true, 'width' => 2, 'color' => ['transparent']],
      'xaxis' => ['categories' => $months],
      'yaxis' => ['title' => ['text' => 'P (thousands)']],
      'fill' => ['opacity' => 1]
    ];
    $this->config['sbcgraph']['sales'] = ['series' => $series, 'option' => $option];
  }

  private function purchasegraph()
  {
    $companyid = $this->config['params']['companyid'];
    $dateid = date('Y-m-d');
    $year = date('Y');
    $center = $this->config['params']['center'];
    $addunion = "";
    switch ($companyid) {
      case 36: //rozlab
      case 27: //nte

        if ($companyid == 36) { //rozlab
          $addunion = " union all
              select month(head.dateid) as m,sum(stock.ext)*-1 as amt,0 as amt2
              from glhead as head left join glstock as stock on stock.trno=head.trno
              left join cntnum on cntnum.trno=head.trno where head.doc='DM' and year(dateid)='$year'
              group by month(head.dateid)
              UNION ALL
              select month(head.dateid),sum(stock.ext)*-1 as amt,0 as amt2
              from lahead as head left join lastock as stock on stock.trno=head.trno
              left join cntnum on cntnum.trno=head.trno where head.doc='DM' and year(dateid)='$year'
              group by month(head.dateid)";
        }

        $qry = "
        select m,sum(amt) as amt,sum(amt2) as amt2 from (
          select month(head.dateid) as m,sum(stock.ext) as amt,0 as amt2
          from glhead as head
          left join glstock as stock on stock.trno=head.trno
          left join cntnum on cntnum.trno=head.trno where head.doc='RR' and year(dateid)='$year'
          group by month(head.dateid)
          UNION ALL
          select month(head.dateid),sum(stock.ext) as amt,0 as amt2
          from lahead as head
          left join lastock as stock on stock.trno=head.trno
          left join cntnum on cntnum.trno=head.trno
          where head.doc='RR' and year(dateid)='$year'
          group by month(head.dateid)
          union all
          select month(head.dateid) as m,0 as amt,sum(detail.db-detail.cr) as amt2
          from glhead as head
          left join gldetail as detail on detail.trno=head.trno
          left join cntnum on cntnum.trno=head.trno
          where detail.acnoid in (select acnoid from coa where parent='\\\\52')
          and year(head.dateid)='$year'
          group by month(head.dateid)
          UNION ALL
          select month(head.dateid),0 as amt,sum(detail.db-detail.cr) as amt2
          from lahead as head
          left join ladetail as detail on detail.trno=head.trno
          left join cntnum on cntnum.trno=head.trno
          where detail.acnoid in (select acnoid from coa where parent='\\\\52')
          and year(head.dateid)='$year'
          group by month(head.dateid)
          $addunion
        ) as T group by m";
        $data = $this->coreFunctions->opentable($qry);
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $graphdata = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
        $graphdata2 = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
        foreach ($data as $key => $value) {
          $graphdata[$data[$key]->m - 1] = $data[$key]->amt;
        }
        foreach ($data as $key => $value) {
          $graphdata2[$data[$key]->m - 1] = $data[$key]->amt2;
        }
        //type of graph
        //1.bar
        //2.line
        //3.radar
        $series = [['name' => 'TotalOE', 'data' => $graphdata2], ['name' => 'Total Purchases', 'data' => $graphdata]];
        $option = [
          'chart' => ['type' => 'bar', 'height' => 300],
          'plotOptions' => ['bar' => ['horizontal' => false, 'columnWidth' => '55%', 'endingShape' => 'rounded']],
          'title' => ['text' => 'Purchases ' . $year, 'align' => 'left', 'style' => ['color' => 'black']],
          'dataLabels' => ['enabled' => false],
          'xaxis' => ['categories' => $months],
          'yaxis' => ['title' => ['text' => 'P (thousands)']],
          'fill' => ['opacity' => 1]
        ];
        break;
      case 47: //kstar
        $qry = "select m,sum(amt) as amt from (select month(head.dateid) as m,sum(stock.cost*stock.qty) as amt from 
        glhead as head left join glstock as stock 
        on stock.trno=head.trno left join cntnum on cntnum.trno=head.trno where head.doc='RR' and 
        year(dateid)='" . $year . "' group by month(head.dateid)
        UNION ALL
        select month(head.dateid),sum(stock.cost*stock.qty) from lahead as head 
        left join lastock as stock
        on stock.trno=head.trno left join cntnum on cntnum.trno=head.trno where head.doc='RR' and 
        year(dateid)='" . $year . "' group by month(head.dateid)) as T group by m";

        $data = $this->coreFunctions->opentable($qry);
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $graphdata = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
        foreach ($data as $key => $value) {
          $graphdata[$data[$key]->m - 1] = $data[$key]->amt;
        }
        //type of graph
        //1.bar
        //2.line
        //3.radar
        $series = [['name' => 'Total', 'data' => $graphdata]];
        $option = [
          'chart' => ['type' => 'bar', 'height' => 300],
          'plotOptions' => ['bar' => ['horizontal' => false, 'columnWidth' => '55%', 'endingShape' => 'rounded']],
          'title' => ['text' => 'Purchases ' . $year, 'align' => 'left', 'style' => ['color' => 'black']],
          'dataLabels' => ['enabled' => false],
          'xaxis' => ['categories' => $months],
          'yaxis' => ['title' => ['text' => 'P (thousands)']],
          'fill' => ['opacity' => 1]
        ];
        break;

      default:
        $qry = "select m,sum(amt) as amt from (select month(head.dateid) as m,sum(stock.ext)*case head.forex when 0 then 1 else head.forex end as amt from 
        glhead as head left join glstock as stock 
        on stock.trno=head.trno left join cntnum on cntnum.trno=head.trno where head.doc='RR' and 
        year(dateid)='" . $year . "' group by month(head.dateid),head.forex
        UNION ALL
        select month(head.dateid),sum(stock.ext)*case head.forex when 0 then 1 else head.forex end from lahead as head 
        left join lastock as stock
        on stock.trno=head.trno left join cntnum on cntnum.trno=head.trno where head.doc='RR' and 
        year(dateid)='" . $year . "' group by month(head.dateid),head.forex) as T group by m";

        $data = $this->coreFunctions->opentable($qry);
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $graphdata = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
        foreach ($data as $key => $value) {
          $graphdata[$data[$key]->m - 1] = $data[$key]->amt;
        }
        //type of graph
        //1.bar
        //2.line
        //3.radar
        $series = [['name' => 'Total', 'data' => $graphdata]];
        $option = [
          'chart' => ['type' => 'bar', 'height' => 300],
          'plotOptions' => ['bar' => ['horizontal' => false, 'columnWidth' => '55%', 'endingShape' => 'rounded']],
          'title' => ['text' => 'Purchases ' . $year, 'align' => 'left', 'style' => ['color' => 'black']],
          'dataLabels' => ['enabled' => false],
          'xaxis' => ['categories' => $months],
          'yaxis' => ['title' => ['text' => 'P (thousands)']],
          'fill' => ['opacity' => 1]
        ];
        break;
    }

    $this->config['sbcgraph']['purchase'] = ['series' => $series, 'option' => $option];
  }

  private function collectiongraph()
  {
    $companyid = $this->config['params']['companyid'];
    $dateid = date('Y-m-d');
    $year = date('Y');
    $center = $this->config['params']['center'];
    $addunion = "";
    $qry = "select m,sum(amt) as amt from (select month(head.dateid) as m,sum(detail.db) amt from 
        glhead as head left join gldetail as detail on detail.trno=head.trno  left join coa on coa.acnoid = detail.acnoid
        left join cntnum on cntnum.trno=head.trno where head.doc='CR' and 
        year(dateid)='" . $year . "' and left(coa.alias,2) in ('CA','CR','CB') group by month(head.dateid)
        UNION ALL
        select month(head.dateid),sum(detail.db) as amt from lahead as head 
        left join ladetail as detail on detail.trno=head.trno left join coa on coa.acnoid = detail.acnoid
         left join cntnum on cntnum.trno=head.trno where head.doc='CR' and 
        year(dateid)='" . $year . "'  and left(coa.alias,2) in ('CA','CR','CB')  group by month(head.dateid)) as T group by m";

    $data = $this->coreFunctions->opentable($qry);
    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    $graphdata = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    foreach ($data as $key => $value) {
      $graphdata[$data[$key]->m - 1] = $data[$key]->amt;
    }
    //type of graph
    //1.bar
    //2.line
    //3.radar
    $series = [['name' => 'Total', 'data' => $graphdata]];
    $option = [
      'chart' => ['type' => 'bar', 'height' => 300],
      'plotOptions' => ['bar' => ['horizontal' => false, 'columnWidth' => '55%', 'endingShape' => 'rounded']],
      'title' => ['text' => 'Collection ' . $year, 'align' => 'left', 'style' => ['color' => 'black']],
      'dataLabels' => ['enabled' => false],
      'xaxis' => ['categories' => $months],
      'yaxis' => ['title' => ['text' => 'P (thousands)']],
      'fill' => ['opacity' => 1]
    ];

    $this->config['sbcgraph']['collection'] = ['series' => $series, 'option' => $option];
  }

  private function loanreleasegraph()
  {
    $series = [];
    $year = date('Y');
    $cmonth = date('M');

    $loantype =  $this->coreFunctions->opentable("select line,category,reqtype from reqcategory where isloantype =1 order by reqtype");
    $colors = [];

    foreach ($loantype as $grp => $g) {
      $qry2 = "select m,sum(amt) as amt,planid from (select month(dateid) as m,sum(amount) amt,loantype as planid from 
       loansum where year(dateid) = " . $year . " and loantype = " . $g->line . " group by month(dateid),loantype) as T group by m,planid";

      $data2 = json_decode(json_encode($this->coreFunctions->opentable($qry2)), true);

      $proj = [0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00];
      $strprj = [];
      if (!empty($data2)) {
        foreach ($data2 as $key2 => $value2) {
          $proj[$data2[$key2]['m'] - 1] = $data2[$key2]['amt'];
          $strprj['data'] = $proj;
        }
      } else {
        $proj = [0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00];;
        $strprj['data'] = $proj;
      }

      //$colors[$grp] = isset($g->color) ? $g->color : '';
      $strprj['name'] = $g->reqtype;
      array_push($series, $strprj);
    }

    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    $chartoption = [
      'chart' => ['type' => 'bar', 'height' => 300],
      'plotOptions' => ['bar' => ['horizontal' => false, 'columnWidth' => '85%', 'endingShape' => 'rounded']],
      'title' => ['text' => 'Loan Release per Type ' . $year, 'align' => 'left', 'style' => ['color' => 'black']],
      'dataLabels' => ['enabled' => false],
      'stroke' => ['show' => true, 'width' => 2, 'color' => ['transparent']],
      'xaxis' => ['title' => ['text' => 'Loan Type'], 'categories' => $months],
      'yaxis' => ['title' => ['text' => 'Amount']],
      //'colors' => $colors,
      'fill' => ['opacity' => 1]
    ];
    $this->config['sbcgraph']['loanreleasegraph'] = ['series' => $series, 'option' => $chartoption];
  }

  private function aflicollectiongraph()
  {
    $series = [];
    $year = date('Y');
    $cmonth = date('M');

    $loantype =  $this->coreFunctions->opentable("select line,category,reqtype from reqcategory where isloantype =1 order by reqtype");
    $colors = [];

    foreach ($loantype as $grp => $g) {
      $qry2 = "select m,sum(amt) as amt,planid from (select month(head.dateid) as m,sum(detail.db) amt,ea.planid from 
        glhead as head left join gldetail as detail on detail.trno=head.trno  left join coa on coa.acnoid = detail.acnoid
        left join cntnum on cntnum.trno=head.trno left join heahead as ea on ea.trno = head.aftrno 
        where head.doc='CR' and year(head.dateid)='" . $year . "' and left(coa.alias,2) in ('CA','CR','CB') and ea.planid = " . $g->line . " group by month(head.dateid) ,ea.planid
        UNION ALL
        select month(head.dateid),sum(detail.db) as amt,ea.planid from lahead as head 
        left join ladetail as detail on detail.trno=head.trno left join coa on coa.acnoid = detail.acnoid
         left join cntnum on cntnum.trno=head.trno left join heahead as ea on ea.trno = head.aftrno  where head.doc='CR' and 
        year(head.dateid)='" . $year . "'  and left(coa.alias,2) in ('CA','CR','CB') and ea.planid = " . $g->line . " group by month(head.dateid),ea.planid) as T group by m,planid";

      $data2 = json_decode(json_encode($this->coreFunctions->opentable($qry2)), true);

      $proj = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
      $strprj = [];
      if (!empty($data2)) {
        foreach ($data2 as $key2 => $value2) {
          $proj[$data2[$key2]['m'] - 1] = $data2[$key2]['amt'];
          $strprj['data'] = $proj;
        }
      } else {
        $proj = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
        $strprj['data'] = $proj;
      }

      //$colors[$grp] = isset($g->color) ? $g->color : '';
      $strprj['name'] = $g->reqtype;
      array_push($series, $strprj);
    }

    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    $chartoption = [
      'chart' => ['type' => 'bar', 'height' => 300],
      'plotOptions' => ['bar' => ['horizontal' => false, 'columnWidth' => '85%', 'endingShape' => 'rounded']],
      'title' => ['text' => 'Collections ' . $year, 'align' => 'left', 'style' => ['color' => 'black']],
      'dataLabels' => ['enabled' => false],
      'stroke' => ['show' => true, 'width' => 2, 'color' => ['transparent']],
      'xaxis' => ['title' => ['text' => 'Loan Type'], 'categories' => $months],
      'yaxis' => ['title' => ['text' => 'Amount']],
      //'colors' => $colors,
      'fill' => ['opacity' => 1]
    ];
    $this->config['sbcgraph']['aflicollectiongraph'] = ['series' => $series, 'option' => $chartoption];
  }

  private function MTDgraph()
  {

    $year = date('Y');

    $qry = "select ifnull(a.igroup,'NO GROUP') as itemgroup, ifnull(sum(ext),0) as ext, sum(ext) as totaldollar,color,case a.igroup when 'others' then 100 else 1 end as sort
    from (
    select sum(hqsstock.ext*hqsstock.sgdrate) as ext,prj.groupid as igroup,prj.color
    from hsqhead as sohead
    left join hqshead as hqshead on hqshead.sotrno = sohead.trno
    left join hqsstock as hqsstock on hqsstock.trno = hqshead.trno
    left join item as item on item.itemid = hqsstock.itemid
    left join client as ag on ag.client = hqshead.agent
    left join client on client.client=hqshead.client
    left join projectmasterfile as prj on prj.line = hqsstock.projectid
    where  year(sohead.dateid) = year(now())
    group by prj.groupid,prj.color
    union all
    select sum(hqtstock.ext*hqtstock.sgdrate) as ext,prj.groupid as igroup,prj.color
    from hqshead as sohead
    left join hqtstock as hqtstock on sohead.trno = hqtstock.trno
    left join item as item on item.itemid = hqtstock.itemid
    left join client as ag on ag.client = sohead.agent
    left join client on client.client=sohead.client
    left join projectmasterfile as prj on prj.line = hqtstock.projectid
    where year(sohead.due) = year(now())
    group by prj.groupid,prj.color
    ) as a where ext<>0
    group by a.igroup,a.color order by sort,igroup";

    $data = $this->coreFunctions->opentable($qry);


    $proj = [];
    $strprj = [];
    $colors = [];

    foreach ($data as $key => $value) {
      $qrys =  "select ifnull(sum(ig.amt),0)*month(now()) as value
      from itemgroupqouta as ig left join projectmasterfile as p on p.line = ig.projectid where p.groupid = '" . $value->itemgroup . "' and  ig.yr = year(now()) ";
      $totalsales = $this->coreFunctions->datareader($qrys);
      if ($totalsales != 0) {
        $proj[$key] = number_format(($value->ext / $totalsales) * 100, 2) . '%';
      } else {
        $proj[$key] = '0%';
      }

      $colors[$key] = isset($value->color) ? $value->color : '';
      $strprj[$key] = $value->itemgroup;
    }
    $series = [['name' => 'itemgroup', 'data' => $proj]];


    $chartoption = [
      'chart' => ['type' => 'bar', 'height' => 500],
      'plotOptions' => ['bar' => ['horizontal' => false, 'columnWidth' => '45%', 'endingShape' => 'rounded', 'distributed' => true]],
      'title' => ['text' => 'Month To Date Sales ' . $year, 'align' => 'left', 'style' => ['color' => 'black']],
      'dataLabels' => ['enabled' => false],

      'xaxis' => ['title' => ['text' => 'Item Group'], 'categories' => $strprj],
      'yaxis' => ['title' => ['text' => 'Sales %'], 'max' => 100],
      'colors' => $colors,
      'fill' => ['opacity' => 1]
    ];
    $this->config['sbcgraph']['mtd'] = ['series' => $series, 'option' => $chartoption];
  }

  private function YTDgraph()
  {

    $year = date('Y');

    $qry = "select ifnull(a.igroup,'NO GROUP') as itemgroup, ifnull(sum(ext),0) as ext, sum(ext) as totaldollar,color,case a.igroup when 'others' then 100 else 1 end as sort
    from (
    select sum(hqsstock.ext*hqsstock.sgdrate) as ext,prj.groupid as igroup,ifnull(prj.color,'') as color
    from hsqhead as sohead
    left join hqshead as hqshead on hqshead.sotrno = sohead.trno
    left join hqsstock as hqsstock on hqsstock.trno = hqshead.trno
    left join item as item on item.itemid = hqsstock.itemid
    left join client as ag on ag.client = hqshead.agent
    left join client on client.client=hqshead.client
    left join projectmasterfile as prj on prj.line = hqsstock.projectid
    where  year(sohead.dateid) = year(now())
    group by prj.groupid,prj.color
    union all
    select sum(hqtstock.ext*hqtstock.sgdrate) as ext,prj.groupid as igroup,ifnull(prj.color,'') as color
    from hqshead as sohead
    left join hqtstock as hqtstock on sohead.trno = hqtstock.trno
    left join item as item on item.itemid = hqtstock.itemid
    left join client as ag on ag.client = sohead.agent
    left join client on client.client=sohead.client
    left join projectmasterfile as prj on prj.line = hqtstock.projectid
    where  year(sohead.due) = year(now())
    group by prj.groupid,prj.color
    ) as a where ext<>0
    group by a.igroup,a.color order by sort,igroup";

    $data = $this->coreFunctions->opentable($qry);
    $proj = [];
    $strprj = [];
    $colors = [];

    foreach ($data as $key => $value) {
      $qrys =  "select ifnull(sum(ig.amt),0)*12 as value
      from itemgroupqouta as ig left join projectmasterfile as p on p.line = ig.projectid where p.groupid = '" . $value->itemgroup . "' and  ig.yr = year(now()) ";
      $totalsales = $this->coreFunctions->datareader($qrys);

      if ($totalsales != 0) {
        $proj[$key] = number_format(($value->ext / $totalsales) * 100, 2) . '%';
      } else {
        $proj[$key] = '0%';
      }

      $colors[$key] = isset($value->color) ? $value->color : '';
      $strprj[$key] = $value->itemgroup;
    }
    $series = [['name' => 'itemgroup', 'data' => $proj]];


    $chartoption = [
      'chart' => ['type' => 'bar', 'height' => 500],
      'plotOptions' => ['bar' => ['horizontal' => false, 'columnWidth' => '45%', 'endingShape' => 'rounded', 'distributed' => true]],
      'title' => ['text' => 'Year To Date Sales ' . $year, 'align' => 'left', 'style' => ['color' => 'black']],
      'dataLabels' => ['enabled' => false],

      'xaxis' => ['title' => ['text' => 'Item Group'], 'categories' => $strprj],
      'yaxis' => ['title' => ['text' => 'Sales %'], 'max' => 100],
      'colors' => $colors,
      'fill' => ['opacity' => 1]
    ];
    $this->config['sbcgraph']['ytd'] = ['series' => $series, 'option' => $chartoption];
  }

  private function YTDgraph_monthlycompare()
  {
    $series = [];
    $year = date('Y');

    $qry = "select ifnull(a.igroup,'NO GROUP') as itemgroup, ifnull(sum(ext),0) as ext, color
    from (
    select sum(hqsstock.ext*hqsstock.sgdrate) as ext,prj.groupid as igroup, prj.color
    from hsqhead as sohead
    left join hqshead as hqshead on hqshead.sotrno = sohead.trno
    left join hqsstock as hqsstock on hqsstock.trno = hqshead.trno
    left join projectmasterfile as prj on prj.line = hqsstock.projectid
    where year(sohead.dateid) = year(now())
    group by prj.groupid, prj.color
    union all
    select sum(hqtstock.ext*hqtstock.sgdrate) as ext,prj.groupid as igroup, prj.color
    from hqshead as sohead
    left join hqtstock as hqtstock on sohead.trno = hqtstock.trno
    left join projectmasterfile as prj on prj.line = hqtstock.projectid
    where year(sohead.due) = year(now())
    group by prj.groupid, prj.color
    ) as a
    group by a.igroup, a.color";

    $data = $this->coreFunctions->opentable($qry);

    $qrys =  "select ifnull(sum(amt),0)*12 as value
    from itemgroupqouta where yr = year(now()) ";
    $totalsales = $this->coreFunctions->datareader($qrys);

    $colors = [];
    foreach ($data as $key => $value) {
      $qry2 = "select ifnull(sum(ext),0) as ext,mnth
      from (
      select sum(hqsstock.ext*hqsstock.sgdrate) as ext,prj.groupid as igroup,month(sohead.dateid) as mnth
      from hsqhead as sohead
      left join hqshead as hqshead on hqshead.sotrno = sohead.trno
      left join hqsstock as hqsstock on hqsstock.trno = hqshead.trno
      left join item as item on item.itemid = hqsstock.itemid
      left join client as ag on ag.client = hqshead.agent
      left join client on client.client=hqshead.client
      left join projectmasterfile as prj on prj.line = hqsstock.projectid
      where year(sohead.dateid) = year(now()) and prj.groupid = '" . $value->itemgroup . "' 
      group by prj.groupid,month(sohead.dateid)
      union all
      select sum(hqtstock.ext*hqtstock.sgdrate) as ext,prj.groupid as igroup,month(sohead.due) as mnth
      from hqshead as sohead
      left join hqtstock as hqtstock on sohead.trno = hqtstock.trno
      left join item as item on item.itemid = hqtstock.itemid
      left join client as ag on ag.client = sohead.agent
      left join client on client.client=sohead.client
      left join projectmasterfile as prj on prj.line = hqtstock.projectid
      where year(sohead.due) = year(now()) and prj.groupid = '" . $value->itemgroup . "' 
      group by prj.groupid,month(sohead.due)
      ) as a group by mnth order by mnth";

      $data2 = json_decode(json_encode($this->coreFunctions->opentable($qry2)), true);
      $proj = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
      $strprj = [];
      if (!empty($data2)) {
        foreach ($data2 as $key2 => $value2) {
          if ($totalsales != 0) {
            $proj[$data2[$key2]['mnth'] - 1] = number_format(($data2[$key2]['ext'] / $totalsales) * 100, 2) . '%';
          } else {
            $proj[$data2[$key2]['mnth'] - 1] = '0%';
          }

          $colors[$key - 1] = isset($value->color) ? $value->color : '';
          $strprj['data'] = $proj;
        }
        $strprj['name'] = $value->itemgroup;
        array_push($series, $strprj);
      }
    }
    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    $chartoption = [
      'chart' => ['type' => 'bar', 'height' => 500],
      'plotOptions' => ['bar' => ['horizontal' => false, 'columnWidth' => '45%', 'endingShape' => 'rounded']],
      'title' => ['text' => 'Year To Date Sales ' . $year, 'align' => 'left', 'style' => ['color' => 'black']],
      'dataLabels' => ['enabled' => false],
      'stroke' => ['show' => true, 'width' => 2, 'color' => ['transparent']],
      'xaxis' => ['title' => ['text' => 'Month'], 'categories' => $months],
      'yaxis' => ['title' => ['text' => 'Sales %'], 'max' => 100],
      'colors' => $colors,
      'fill' => ['opacity' => 1]
    ];
    $this->config['sbcgraph']['ytd'] = ['series' => $series, 'option' => $chartoption];
  }

  private function BranchSalesgraph()
  {

    $year = date('Y');

    $qry = "select ifnull(branch,'No Branch') as branch, ifnull(sum(ext),0) as ext, sum(ext) as totaldollar
    from (
    select sum(hqsstock.ext*hqsstock.sgdrate) as ext,b.clientname as branch
    from hsqhead as sohead
    left join hqshead as hqshead on hqshead.sotrno = sohead.trno
    left join hqsstock as hqsstock on hqsstock.trno = hqshead.trno
    left join item as item on item.itemid = hqsstock.itemid
    left join client as ag on ag.client = hqshead.agent
    left join client on client.client=hqshead.client
    left join client as b on b.clientid = hqshead.branch
    where  year(sohead.dateid) = year(now())
    group by b.clientname
    union all
    select sum(hqtstock.ext*hqtstock.sgdrate) as ext,b.clientname as branch
    from hqshead as sohead
    left join hqtstock as hqtstock on sohead.trno = hqtstock.trno
    left join item as item on item.itemid = hqtstock.itemid
    left join client as ag on ag.client = sohead.agent
    left join client on client.client=sohead.client
    left join client as b on b.clientid = sohead.branch
    where  year(sohead.due) = year(now())
    group by b.clientname
    ) as a
    group by a.branch";

    $data = $this->coreFunctions->opentable($qry);

    $qry2 = "select ifnull(sum(ext),0) as ext, sum(ext) as totaldollar
    from (
      select sum(hqsstock.ext*hqsstock.sgdrate) as ext,b.clientname as branch
      from hsqhead as sohead
      left join hqshead as hqshead on hqshead.sotrno = sohead.trno
      left join hqsstock as hqsstock on hqsstock.trno = hqshead.trno
      left join item as item on item.itemid = hqsstock.itemid
      left join client as ag on ag.client = hqshead.agent
      left join client on client.client=hqshead.client
      left join client as b on b.clientid = sohead.branch
      where  year(sohead.dateid) = year(now())
      group by b.clientname
      union all
      select sum(hqtstock.ext*hqtstock.sgdrate) as ext,b.clientname as branch
      from hqshead as sohead
      left join hqtstock as hqtstock on sohead.trno = hqtstock.trno
      left join item as item on item.itemid = hqtstock.itemid
      left join client as ag on ag.client = sohead.agent
      left join client on client.client=sohead.client
      left join client as b on b.clientid = sohead.branch
      where  year(sohead.due) = year(now())
      group by b.clientname
    ) as a";

    $data2 = json_decode(json_encode($this->coreFunctions->opentable($qry2)), true);

    $proj = [];
    $strprj = [];

    foreach ($data as $key => $value) {

      if ($value->ext != 0) {
        $proj[$key] = number_format(($value->ext / $data2[0]['ext']) * 100, 2) . '%';
      } else {
        $proj[$key] = '0%';
      }

      $strprj[$key] = $value->branch;
    }
    $series = [['name' => 'branch', 'data' => $proj]];

    $chartoption = [
      'chart' => ['type' => 'bar', 'height' => 500],
      'plotOptions' => ['bar' => ['horizontal' => false, 'columnWidth' => '45%', 'endingShape' => 'rounded']],
      'title' => ['text' => 'Sales per Branch ' . $year, 'align' => 'left', 'style' => ['color' => 'black']],
      'dataLabels' => ['enabled' => true],
      'stroke' => ['show' => true, 'width' => 2, 'color' => ['transparent']],
      'xaxis' => ['title' => ['text' => 'Branch'], 'categories' => $strprj],
      'yaxis' => ['title' => ['text' => 'Sales %']],
      'fill' => ['opacity' => 1]
    ];
    $this->config['sbcgraph']['branchsales'] = ['series' => $series, 'option' => $chartoption];
  }

  private function BranchperItemgraph()
  {
    $series = [];
    $year = date('Y');
    $cmonth = date('M');

    $group =  $this->coreFunctions->opentable("select distinct p.groupid as itemgroup,case p.groupid when 'Others' then 100 else 1 end as sort,p.color
    from itemgroupqouta as ig left join projectmasterfile as p on p.line = ig.projectid where groupid <> '' order by sort,itemgroup");
    $colors = [];

    foreach ($group as $grp => $g) {
      $qrys =  "select ifnull(sum(ig.amt),0) as value
      from itemgroupqouta as ig left join projectmasterfile as p on p.line = ig.projectid where p.groupid = '" . $g->itemgroup . "' and  ig.yr = year(now()) ";
      $totalsales = $this->coreFunctions->datareader($qrys);

      $qry2 = "select ifnull(sum(ext),0) as ext,mnth,case a.igroup when 'others' then 100 else 1 end as sort
      from (
      select sum(hqsstock.ext*hqsstock.sgdrate) as ext,prj.groupid as igroup,case b.clientname when 'Makati' then 1 else 2 end as mnth
      from hsqhead as sohead
      left join hqshead as hqshead on hqshead.sotrno = sohead.trno
      left join hqsstock as hqsstock on hqsstock.trno = hqshead.trno
      left join item as item on item.itemid = hqsstock.itemid
      left join client as ag on ag.client = hqshead.agent
      left join client on client.client=hqshead.client
      left join projectmasterfile as prj on prj.line = hqsstock.projectid
      left join client as b on b.clientid = hqshead.branch
      where year(sohead.dateid) = year(now())  and month(sohead.dateid) = month(now()) and prj.groupid = '" . $g->itemgroup . "' 
      group by prj.groupid,b.clientname
      union all
      select sum(hqtstock.ext*hqtstock.sgdrate) as ext,prj.groupid as igroup,case b.clientname when 'Makati' then 1 else 2 end as mnth
      from hqshead as sohead
      left join hqtstock as hqtstock on sohead.trno = hqtstock.trno
      left join item as item on item.itemid = hqtstock.itemid
      left join client as ag on ag.client = sohead.agent
      left join client on client.client=sohead.client
      left join projectmasterfile as prj on prj.line = hqtstock.projectid
      left join client as b on b.clientid = sohead.branch
      where year(sohead.due) = year(now()) and month(sohead.due) = month(now()) and prj.groupid = '" . $g->itemgroup . "' 
      group by prj.groupid,b.clientname
      ) as a group by mnth,igroup order by mnth,sort,igroup";

      $data2 = json_decode(json_encode($this->coreFunctions->opentable($qry2)), true);

      $proj = [0, 0];
      $strprj = [];
      if (!empty($data2)) {
        foreach ($data2 as $key2 => $value2) {
          if ($totalsales != 0) {
            $proj[$data2[$key2]['mnth'] - 1] = number_format(($data2[$key2]['ext'] / $totalsales) * 100, 2) . '%';
          } else {
            $proj[$data2[$key2]['mnth'] - 1] = '0%';
          }
          $strprj['data'] = $proj;
        }
      } else {
        $proj[0] = '0%';
        $proj[1] = '0%';
        $strprj['data'] = $proj;
      }
      $colors[$grp] = isset($g->color) ? $g->color : '';
      $strprj['name'] = $g->itemgroup;
      array_push($series, $strprj);
    }

    $months = ['Makati', 'Cebu'];
    $chartoption = [
      'chart' => ['type' => 'bar', 'height' => 500],
      'plotOptions' => ['bar' => ['horizontal' => false, 'columnWidth' => '45%', 'endingShape' => 'rounded']],
      'title' => ['text' => 'Per Branch Sales ' . $cmonth . ' ' . $year, 'align' => 'left', 'style' => ['color' => 'black']],
      'dataLabels' => ['enabled' => false],
      'stroke' => ['show' => true, 'width' => 2, 'color' => ['transparent']],
      'xaxis' => ['title' => ['text' => 'Item Group'], 'categories' => $months],
      'yaxis' => ['title' => ['text' => 'Sales %'], 'max' => 100],
      'colors' => $colors,
      'fill' => ['opacity' => 1]
    ];
    $this->config['sbcgraph']['branchitemsales'] = ['series' => $series, 'option' => $chartoption];
  }

  private function MonthlySalesgraph()
  {

    $year = date('Y');
    $cmonth = date('M');

    $group =  $this->coreFunctions->opentable("select distinct p.groupid as itemgroup,case p.groupid when 'Others' then 100 else 1 end as sort,p.color
      from itemgroupqouta as ig left join projectmasterfile as p on p.line = ig.projectid where groupid <> '' order by sort,itemgroup");

    $qry = "select ifnull(a.igroup,'NO GROUP') as itemgroup, ifnull(sum(ext),0) as ext, sum(ext) as totaldollar,color,case a.igroup when 'others' then 100 else 1 end as sort
    from (
    select sum(hqsstock.ext*hqsstock.sgdrate) as ext,prj.groupid as igroup,prj.color
    from hsqhead as sohead
    left join hqshead as hqshead on hqshead.sotrno = sohead.trno
    left join hqsstock as hqsstock on hqsstock.trno = hqshead.trno
    left join item as item on item.itemid = hqsstock.itemid
    left join client as ag on ag.client = hqshead.agent
    left join client on client.client=hqshead.client
    left join projectmasterfile as prj on prj.line = hqsstock.projectid
    where  year(sohead.dateid) = year(now())  and month(sohead.dateid) = month(now()) and prj.groupid =?
    group by prj.groupid,prj.color
    union all
    select sum(hqtstock.ext*hqtstock.sgdrate) as ext,prj.groupid as igroup,prj.color
    from hqshead as sohead
    left join hqtstock as hqtstock on sohead.trno = hqtstock.trno
    left join item as item on item.itemid = hqtstock.itemid
    left join client as ag on ag.client = sohead.agent
    left join client on client.client=sohead.client
    left join projectmasterfile as prj on prj.line = hqtstock.projectid
    where year(sohead.due) = year(now()) and month(sohead.dateid) = month(now()) and prj.groupid =?
    group by prj.groupid,prj.color
    ) as a where ext<>0
    group by a.igroup,a.color order by sort,igroup";

    $proj = [];
    $strprj = [];
    $colors = [];

    foreach ($group as $grp => $g) {
      $qrys =  "select ifnull(sum(ig.amt),0) as value
      from itemgroupqouta as ig left join projectmasterfile as p on p.line = ig.projectid where p.groupid = '" . $g->itemgroup . "' and  ig.yr = year(now()) ";
      $totalsales = $this->coreFunctions->datareader($qrys);

      $data = $this->coreFunctions->opentable($qry, [$g->itemgroup, $g->itemgroup]);

      if (!empty($data)) {
        if ($totalsales != 0) {
          $proj[$grp] = number_format(($data[0]->ext / $totalsales) * 100, 2) . '%';
        } else {
          $proj[$grp] = '0%';
        }
      } else {
        $proj[$grp] = '0%';
      }

      $colors[$grp] = isset($g->color) ? $g->color : '';
      $strprj[$grp] = $g->itemgroup;
    }

    $series = [['name' => 'itemgroup', 'data' => $proj]];

    $chartoption = [
      'chart' => ['type' => 'bar', 'height' => 500],
      'plotOptions' => ['bar' => ['horizontal' => false, 'columnWidth' => '45%', 'endingShape' => 'rounded', 'distributed' => true]],
      'title' => ['text' =>  $cmonth . ' ' . $year . ' Sales', 'align' => 'left', 'style' => ['color' => 'black']],
      'dataLabels' => ['enabled' => false],
      'xaxis' => ['title' => ['text' => 'Item Group'], 'categories' => $strprj],
      'yaxis' => ['title' => ['text' => 'Sales %'], 'max' => 100],
      'colors' => $colors,
      'fill' => ['opacity' => 1]
    ];
    $this->config['sbcgraph']['monthlysales'] = ['series' => $series, 'option' => $chartoption];
  }

  public function changeTheme()
  {
    $userid = $this->coreFunctions->getfieldvalue("useraccess", "userid", "md5(userid)=?", [$this->config['params']['userid']]);
    if ($userid == '') {
      $userid = $this->coreFunctions->getfieldvalue("client", "clientid", "md5(clientid)=?", [$this->config['params']['userid']]);
    }
    $checkuser = $this->coreFunctions->opentable("select userid from user_themer where userid = ?", [$userid]);
    if (count($checkuser) > 0) {
      $i = $this->coreFunctions->execqry("update user_themer set themecode = ? where userid = ?", 'update', [$this->config['params']['theme'], $userid]);
    } else {
      $i = $this->coreFunctions->execqry("insert into user_themer(userid, themecode) values(?,?)", 'insert', [$userid, $this->config['params']['theme']]);
    }
    if ($i > 0) {
      $this->config['return'] = ['status' => true, 'msg' => 'Successfully updated.'];
    } else {
      $this->config['return'] = ['status' => false, 'msg' => 'Updating failed.'];
    }
    return $this;
  }

  public function loadDefaultTheme()
  {
    $getDefault = $this->coreFunctions->opentable("select pvalue from profile where puser = '{$this->config['params']['user']}' and doc = 'theme' and psection = 'default'");
    if (count($getDefault) > 0) {
      $this->config['return'] = ['status' => true, 'data' => $getDefault];
    } else {
      $this->coreFunctions->execqry("insert into profile(doc, psection, pvalue, puser) values(?, ?, ?, ?)", 'insert', ['theme', 'default', '#1d6920,#00a300,#064a22', $this->config['params']['user']]);
      $getDefault = $this->coreFunctions->opentable("select pvalue from profile where puser='{$this->config['params']['user']}' and doc='theme' and psection='default'");
      $this->config['return'] = ['status' => false, 'data' => $getDefault];
    }
    return $this;
  }

  public function saveDefaultTheme()
  {
    $checkuser = $this->coreFunctions->opentable("select puser from profile where puser = '{$this->config['params']['user']}' and doc = 'theme' and psection = 'default'");
    $msg = "";
    $status = false;
    if (count($checkuser) > 0) {
      if ($this->coreFunctions->execqry("update profile set pvalue = ? where doc = ? and psection = ? and puser = ?", 'update', [$this->config['params']['colors'], 'theme', 'default', $this->config['params']['user']]) > 0) {
        $msg = "Default theme updated";
        $status = true;
      } else {
        $msg = "Error updating default theme, Please try again.";
      }
    } else {
      if ($this->coreFunctions->execqry("insert into profile(doc, psection, pvalue, puser) values(?,?,?,?)", 'insert', ['theme', 'default', $this->config['params']['colors'], $this->config['params']['user']]) > 0) {
        $msg = "Default theme saved";
        $status = true;
      } else {
        $msg = "Error saving default theme, Please try again.";
      }
    }
    $this->config['return'] = ['status' => $status, 'msg' => $msg];
    return $this;
  }

  public function oversixtyage()
  {
    $center = $this->config['params']['center'];
    $getcols = ['docno', 'dateid', 'due', 'planholder', 'amount', 'age'];
    $cols = $this->tabClass->createdoclisting($getcols, []);
    $cols[1]['label'] = 'LA Date';
    $cols[2]['label'] = 'Due Date';

    $qry = "select c.bref,c.seq,head.docno,left(head.dateid,10) as dateid,left(ar.dateid,10) as due,i.clientname as planholder,format(ar.bal,2) as amount,datediff(now(),ar.dateid) as age from glhead as head
    left join cntnum as c on c.trno = head.trno
    left join gldetail as d on d.trno = head.trno
    left join arledger as ar on ar.trno = c.trno and ar.line = d.line
    left join heahead as ea on ea.trno = head.aftrno left join heainfo as i on i.trno = ea.trno
    where head.doc ='CP' and ar.bal<>0 and ar.dateid <= now() and datediff(now(),ar.dateid)>60 limit 100";
    $data = $this->coreFunctions->opentable($qry);
    foreach ($data  as $key => $v) {
      $data[$key]->docno = $this->othersClass->Padj($data[$key]->bref . $data[$key]->seq, 10);
    }
    $this->config['sbclist']['oversixtyage'] = ['cols' => $cols, 'data' => $data, 'title' => 'Over 60 Days Due'];
  }


  public function totalticketstatus()
  {
    $getcols = ['stat', 'count'];
    $cols = $this->tabClass->createdoclisting($getcols);
    $cols[0]['label'] = 'TYPE';
    $cols[1]['label'] = 'COUNT';

    $qry = " select
    ifnull(case
        when num.statid = 92 then 'Open Ticket'
        when num.statid = 93 then 'In Progress Ticket'
        when num.statid = 94 then 'Resolved Ticket'
        when num.statid = 12 then 'Closed Ticket' end, '') as stat,
        SUM(case when num.statid IN (92, 93, 94, 12) THEN 1 else 0 end) as count

    from csstickethead AS head
    LEFT JOIN transnum AS num ON num.trno = head.trno
    LEFT JOIN trxstatus AS stat ON stat.line = num.statid
    where
    stat.status is not null
    group by
    num.statid

    union all

    select 
    ifnull(case when num.statid = 12 then 'Closed Ticket'  end, '') AS stat,
    SUM(case when num.statid IN (92, 93, 94, 12) THEN 1 else 0 end) as count

    FROM
    hcsstickethead AS head
    LEFT JOIN transnum AS num ON num.trno = head.trno
    LEFT JOIN trxstatus AS stat ON stat.line = num.statid
    WHERE
    stat.status is not null
    group by
    num.statid";

    $data = $this->coreFunctions->opentable($qry);
    $this->config['sbclist']['totalticketstatus'] = ['cols' => $cols, 'data' => $data, 'title' => 'TOTAL TICKET STATUS'];

    return $this->coreFunctions->opentable($qry);
  }


  private function ticketpertype()
  {
    $qry = "
    select m,sum(c) as c from
    (select distinct ifnull(req1.category,'')  as m, count(head.docno) as c
    from csstickethead as head
    left join reqcategory as req1 on req1.line=head.orderid where req1.category is not null
     group by req1.category
     union all
     select distinct ifnull(req1.category,'')  as m, count(head.docno) as c
      from hcsstickethead as head
      left join reqcategory as req1 on req1.line=head.orderid where req1.category is not null
       group by req1.category
     ) as t
     group by t.m";
    $data = $this->coreFunctions->opentable($qry);

    $datahere = [];
    $cat = [];
    foreach ($data as $key => $value) {
      $datahere[$key] = $value->c;
      $cat[$key] = $value->m;
    }

    $series = [['name' => 'ticket', 'data' => $datahere]];
    $chartoption = [
      'chart' => ['type' => 'bar', 'height' => 500],
      'plotOptions' => ['bar' => ['horizontal' => false, 'columnWidth' => '45%', 'endingShape' => 'rounded', 'distributed' => true]],
      'title' => ['text' => 'Total Ticket Per Type ', 'align' => 'left', 'style' => ['color' => 'black']],
      'dataLabels' => ['enabled' => false],

      'xaxis' => ['title' => ['text' => 'Ticket Type'], 'categories' => $cat],
      'yaxis' => ['title' => ['text' => ''], 'max' => 50],
      'fill' => ['opacity' => 1]
    ];
    $this->config['sbcgraph']['tickettype'] = ['series' => $series, 'option' => $chartoption];
  }


  private function gendercaller()
  {
    $qry = "
    select m,sum(c) as c from
    (select distinct ifnull(gendercaller,'')  as m, count(gendercaller) as c
    from headinfotrans
    where gendercaller != ''
     group by gendercaller
     union all
     select distinct ifnull(gendercaller,'')  as m, count(gendercaller) as c
      from hheadinfotrans
       where gendercaller != ''
       group by gendercaller
     ) as t
     group by t.m;
    ";
    $data = $this->coreFunctions->opentable($qry);

    $datahere = [];
    $cat = [];

    $total = 0;
    foreach ($data as $key => $value) {
      $total += $value->c;
    }
    foreach ($data as $key => $value) {
      $datahere[$key] = round(($value->c /  $total) * 100);
      $cat[$key] = $value->m . ' (' . $value->c . ')';
    }

    // $series = [['name' => 'Gender Caller', 'data' => $datahere]];
    // $chartoption = [
    //   'chart' => ['type' => 'bar', 'height' => 500],
    //   'plotOptions' => ['bar' => ['horizontal' => false, 'columnWidth' => '45%', 'endingShape' => 'rounded', 'distributed' => true]],
    //   'title' => ['text' => 'Gender Caller ', 'align' => 'left', 'style' => ['color' => 'black']],
    //   'dataLabels' => ['enabled' => false],

    //   'xaxis' => ['title' => ['text' => 'Gender Caller'], 'categories' => $cat],
    //   'yaxis' => ['title' => ['text' => ''], 'max' => 50],
    //   'fill' => ['opacity' => 1]
    // ];


    $series = $datahere;
    $chartoption = [
      'chart' => ['type' => 'pie', 'height' => 500],
      'labels' => $cat
    ];

    $this->config['sbcgraph']['gendercaller'] = ['series' => $series, 'option' => $chartoption];
  }

  public function execute()
  {
    if (isset($this->config['allowlogin'])) {
      if (!$this->config['allowlogin']) {
        return response()->json(['status' => 'ipdenied', 'msg' => 'Sorry, Please contact your Network Administrator', 'xx' => $this->config], 200);
      }
    }

    return response()->json($this->config['return'], 200);
  } // end function

  private function kstarmonthlysales()
  {
    $year = date('Y');
    //$year = '2024';
    if (isset($this->config['params']['addedparams'])) $year = $this->config['params']['addedparams'][0];
    $graphdata = [];
    $center = $this->config['params']['center'];
    $getcols = ['branch', 'amt', 'amt1', 'amt2', 'amt3', 'amt4', 'amt5', 'amt6', 'amt7', 'amt8', 'amt9', 'amt10', 'amt11', 'amt12'];
    $cols = $this->tabClass->createdoclisting($getcols, []);
    $cols[0]['style'] = 'text-align: left; width: 90px;';
    $cols[1]['style'] = 'text-align: left;';
    $cols[1]['label'] = 'Total';
    $fields = ['year'];
    $col1 = $this->fieldClass->create($fields);
    $fields = ['refresh'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'refresh.action2', 'kstarmonthlysales');
    data_set($col2, 'refresh.addedparams', ['year']);
    $paramsdata = $this->coreFunctions->opentable("SELECT '" . $year . "' as year");

    $qry = "select m,format(sum(amt),2) as amt,grp,sum(amt) as total from (select month(head.dateid) as m,sum(stock.ext) as amt,'SALES' as grp from 
    glhead as head left join glstock as stock 
    on stock.trno=head.trno left join cntnum on cntnum.trno=head.trno where head.doc='SJ' and 
    year(dateid)='" . $year . "' group by month(head.dateid)
    UNION ALL
    select month(head.dateid),sum(stock.ext),'SALES' as grp from lahead as head 
    left join lastock as stock
    on stock.trno=head.trno left join cntnum on cntnum.trno=head.trno where head.doc='SJ' and 
    year(dateid)='" . $year  . "' group by month(head.dateid)
    union all
    select month(head.dateid) as m,sum(stock.cost*stock.iss) as amt,'COGS' as grp from 
    glhead as head left join glstock as stock 
    on stock.trno=head.trno left join cntnum on cntnum.trno=head.trno where head.doc='SJ' and 
    year(dateid)='" . $year  . "' group by month(head.dateid)
    UNION ALL
    select month(head.dateid),sum(stock.cost*stock.iss) as amt,'COGS' as grp from  lahead as head 
    left join lastock as stock
    on stock.trno=head.trno left join cntnum on cntnum.trno=head.trno where head.doc='SJ' and 
    year(dateid)='" . $year . "' group by month(head.dateid)
    union all
    select month(head.dateid) as m,sum(stock.ext)-sum(stock.cost*stock.iss) as amt,'PROFIT' as grp  from 
    glhead as head left join glstock as stock 
    on stock.trno=head.trno left join cntnum on cntnum.trno=head.trno where head.doc='SJ' and 
    year(dateid)='" . $year . "' group by month(head.dateid)
    UNION ALL
    select month(head.dateid),sum(stock.ext)-sum(stock.cost*stock.iss) as amt,'PROFIT' as grp  from  lahead as head 
    left join lastock as stock
    on stock.trno=head.trno left join cntnum on cntnum.trno=head.trno where head.doc='SJ' and 
    year(dateid)='" . $year . "' group by month(head.dateid)
    union all
    select month(head.dateid) as m,sum(stock.cost*stock.qty) as amt,'PURCHASES' as grp  from 
    glhead as head left join glstock as stock 
    on stock.trno=head.trno left join cntnum on cntnum.trno=head.trno where head.doc='RR' and 
    year(dateid)='" . $year . "' group by month(head.dateid)) as T group by m,grp order by FIELD(grp,'sales','cogs','profit','purchases'),m";
    $this->coreFunctions->LogConsole($qry);

    $data = $this->coreFunctions->opentable($qry);
    $d = [];
    $grp = '';
    $total = 0;
    foreach ($data as $key => $value) {
      if ($grp == $data[$key]->grp) {
        if ($data[$key]->m == 1) {
          $graphdata['amt1'] =  $data[$key]->amt;
        }

        if ($data[$key]->m == 2) {
          $graphdata['amt2'] =  $data[$key]->amt;
        }

        if ($data[$key]->m == 3) {
          $graphdata['amt3'] =  $data[$key]->amt;
        }

        if ($data[$key]->m == 4) {
          $graphdata['amt4'] =  $data[$key]->amt;
        }

        if ($data[$key]->m == 5) {
          $graphdata['amt5'] =  $data[$key]->amt;
        }

        if ($data[$key]->m == 6) {
          $graphdata['amt6'] =  $data[$key]->amt;
        }

        if ($data[$key]->m == 7) {
          $graphdata['amt7'] =  $data[$key]->amt;
        }

        if ($data[$key]->m == 8) {
          $graphdata['amt8'] =  $data[$key]->amt;
        }

        if ($data[$key]->m == 9) {
          $graphdata['amt9'] =  $data[$key]->amt;
        }

        if ($data[$key]->m == 10) {
          $graphdata['amt10'] =  $data[$key]->amt;
        }

        if ($data[$key]->m == 11) {
          $graphdata['amt11'] =  $data[$key]->amt;
        }

        if ($data[$key]->m == 12) {
          $graphdata['amt12'] =  $data[$key]->amt;
        }
        $total = $total + $data[$key]->total;
      } else {
        if (!empty($graphdata)) {
          array_push($d, $graphdata);
          $graphdata = [];
          $total = 0;
        }

        $graphdata['branch'] = $data[$key]->grp;
        if ($data[$key]->m == 1) {
          $graphdata['amt1'] =  $data[$key]->amt;
        }

        if ($data[$key]->m == 2) {
          $graphdata['amt2'] =  $data[$key]->amt;
        }

        if ($data[$key]->m == 3) {
          $graphdata['amt3'] =  $data[$key]->amt;
        }

        if ($data[$key]->m == 4) {
          $graphdata['amt4'] =  $data[$key]->amt;
        }

        if ($data[$key]->m == 5) {
          $graphdata['amt5'] =  $data[$key]->amt;
        }

        if ($data[$key]->m == 6) {
          $graphdata['amt6'] =  $data[$key]->amt;
        }

        if ($data[$key]->m == 7) {
          $graphdata['amt7'] =  $data[$key]->amt;
        }

        if ($data[$key]->m == 8) {
          $graphdata['amt8'] =  $data[$key]->amt;
        }

        if ($data[$key]->m == 9) {
          $graphdata['amt9'] =  $data[$key]->amt;
        }

        if ($data[$key]->m == 10) {
          $graphdata['amt10'] =  $data[$key]->amt;
        }

        if ($data[$key]->m == 11) {
          $graphdata['amt11'] =  $data[$key]->amt;
        }

        if ($data[$key]->m == 12) {
          $graphdata['amt12'] =  $data[$key]->amt;
        }
        $total = $total + $data[$key]->total;
      }
      $graphdata['amt'] = number_format($total, 2);
      $grp = $data[$key]->grp;
    }
    if (!empty($graphdata)) {
      array_push($d, $graphdata);
    }

    $this->config['sbclist']['kstarsales'] = ['cols' => $cols, 'data' => $d, 'title' => 'Monthly Sales', 'txtfield' => ['col1' => $col1, 'col2' => $col2], 'paramsdata' => $paramsdata[0]];
  }


  public function truckexpiry()
  {
    $center = $this->config['params']['center'];
    $getcols = ['docno', 'expiry'];
    $cols = $this->tabClass->createdoclisting($getcols, []);
    $cols[0]['label'] = 'Reference';
    $cols[0]['style'] = 'text-align: left; width: 250px;';

    $fields = [];
    $col1 = $this->fieldClass->create($fields);

    $qry = "select whd.line,whd.docno, date(whd.expiry) as expiry,
                  date(whd.expiry2) as expiry2,whd.days
            from whdoc as whd
            left join client as tr on tr.clientid=whd.whid
            where tr.istrucking=1 and curdate() between date(whd.expiry2) and date(whd.expiry) 
                  and date(whd.expiry) is not null 
            order by whd.expiry limit 20";
    $data = $this->coreFunctions->opentable($qry);
    $this->config['sbclist']['truckexpiry'] = ['cols' => $cols, 'data' => $data, 'title' =>  'Truck Expiry', 'txtfield' => ['col1' => $col1], 'bgcolor' => 'bg-red', 'textcolor' => 'white'];
  }



  public function dailyNotif()
  {
    $blnprivacy = false;
    if ($this->config['params']['companyid'] == 53 || $this->config['params']['companyid'] == 51) { //camera / ulitc
      $blnprivacy = true;
    }

    $curdate = $this->othersClass->getCurrentDate();

    $bday = $this->coreFunctions->opentable("select emp.empfirst, emp.emplast, emp.bday, DATEDIFF(concat(YEAR('" . $curdate . "'),'-',date_format(emp.bday,'%m-%d')), '" . $curdate . "') AS agecount, 
          DATE_FORMAT(emp.bday, '%M %d, %Y') AS bdayname, DATE_FORMAT(emp.bday, '%M %d') AS bdayname2,
              CONCAT(TIMESTAMPDIFF(YEAR, emp.bday, '" . $curdate . "'),
                  CASE 
                      WHEN TIMESTAMPDIFF(YEAR, emp.bday, '" . $curdate . "') % 100 BETWEEN 11 AND 13 THEN 'th'
                      WHEN TIMESTAMPDIFF(YEAR, emp.bday, '" . $curdate . "') % 10 = 1 THEN 'st'
                      WHEN TIMESTAMPDIFF(YEAR, emp.bday, '" . $curdate . "') % 10 = 2 THEN 'nd'
                      WHEN TIMESTAMPDIFF(YEAR, emp.bday, '" . $curdate . "') % 10 = 3 THEN 'rd'
                      ELSE 'th'
                  END
              ) AS age, client.picture, ifnull(job.jobtitle,'') AS job
          FROM employee AS emp LEFT JOIN client ON client.clientid=emp.empid LEFT JOIN jobthead AS job ON job.line=emp.jobid
          WHERE emp.isactive=1 AND emp.bday IS NOT NULL AND DATEDIFF(concat(YEAR('" . $curdate . "'),'-',date_format(emp.bday,'%m-%d')), '" . $curdate . "') BETWEEN 0 AND 30 
          ORDER BY DATEDIFF(concat(YEAR('" . $curdate . "'),'-',date_format(emp.bday,'%m-%d')), '" . $curdate . "')");

    $data = [];

    $row1 = [];
    $row2 = [];
    $row3 = [];

    foreach ($bday as $key => $value) {

      if ($value->picture == '') {
        $value->picture = '/images/employee/default_emp_portal.png';
      } else {
        $value->picture = ltrim($value->picture, '/');
      }

      $line = [
        'subtitle' => $value->emplast . ", " .  $value->empfirst,
        'subtitle2' => $value->job,
        'subtitle3' => ['text' => $value->age . ' Birthday', 'icon' => 'cake', 'color' => 'red'],
        'dateid' => $value->bdayname,
        'image' => Storage::disk('public')->url($value->picture)
      ];

      if ($blnprivacy) {
        $line['subtitle3']['text'] = $value->bdayname2;
        $line['dateid'] = '';
      }

      if ($value->agecount == 0) {
        array_push($row1, $line);
      } elseif ($value->agecount == 1) {
        array_push($row2, $line);
      } else {
        array_push($row3, $line);
      }
    }

    array_push($data, ['title' => [
      'text' => "Today, Birthday Celebration",
      'icon' => 'star',
      'bgcolor' => 'red-10',
      'textcolor' => 'white'
    ], 'data' => $row1]);

    if (!empty($row2)) {
      array_push($data, ['title' => [
        'text' => "Tomorrow, Birthday Celebration",
        'icon' => 'star',
        'bgcolor' => 'red',
        'textcolor' => 'white'
      ], 'data' => $row2]);
    }

    if (!empty($row3)) {
      array_push($data, ['title' => [
        'text' => "Incoming, Birthday Celebration",
        'icon' => 'star',
        'bgcolor' => 'red',
        'textcolor' => 'white'
      ], 'data' => $row3]);
    }

    $anniv = $this->coreFunctions->opentable("select client.clientname, date(emp.hired) AS hired, TIMESTAMPDIFF(YEAR, emp.hired, '" . $curdate . "') AS yrs, 
          DATEDIFF(concat(YEAR('" . $curdate . "'),'-',date_format(emp.hired,'%m-%d')), '" . $curdate . "') AS annivcount,
          client.picture, ifnull(job.jobtitle,'') AS job, DATE_FORMAT(emp.hired, '%M %d, %Y') as hiredname
          FROM employee AS emp LEFT JOIN client ON client.clientid=emp.empid LEFT JOIN jobthead AS job ON job.line=emp.jobid
          WHERE emp.isactive=1 AND emp.hired IS NOT NULL AND DATEDIFF(concat(YEAR('" . $curdate . "'),'-',date_format(emp.hired,'%m-%d')), '" . $curdate . "') BETWEEN 0 AND 30
          ORDER BY DATEDIFF(concat(YEAR('" . $curdate . "'),'-',date_format(emp.hired,'%m-%d')), '" . $curdate . "'), emp.hired");

    $data2 = [];
    $row1 = [];
    $row2 = [];
    $row3 = [];
    $line = [];

    foreach ($anniv as $key => $value) {
      if ($value->picture == '') {
        $value->picture = '/images/employee/default_emp_portal.png';
      }

      $line = [
        'subtitle' => $value->clientname,
        'subtitle2' => $value->job,
        'subtitle3' => ['text' => $value->yrs . ' Years of Service', 'icon' => 'check', 'color' => 'blue'],
        'dateid' => $value->hiredname,
        'image' => Storage::disk('public')->url($value->picture)
      ];
      // array_push($row1, $line);

      if ($value->annivcount == 0) {
        array_push($row1, $line);
      } elseif ($value->annivcount == 1) {
        array_push($row2, $line);
      } else {
        array_push($row3, $line);
      }
    }

    array_push($data2, ['title' => [
      'text' => "Anniversary Celebration",
      'icon' => 'star',
      'bgcolor' => 'blue-10',
      'textcolor' => 'white'
    ], 'data' => $row1]);

    if (!empty($row2)) {
      array_push($data2, ['title' => [
        'text' => "Tomorrow, Anniversary Celebration",
        'icon' => 'star',
        'bgcolor' => 'blue',
        'textcolor' => 'white'
      ], 'data' => $row2]);
    }

    if (!empty($row3)) {
      array_push($data2, ['title' => [
        'text' => "Incoming, Anniversary Celebration",
        'icon' => 'star',
        'bgcolor' => 'blue',
        'textcolor' => 'white'
      ], 'data' => $row3]);
    }

    $this->config['dailynotif']['birthdays'] = ['data' => $data, 'title' => ['text' => 'BIRTHDAY ANNOUNCEMENT', 'icon' => 'notifications', 'bgcolor' => 'red', 'textcolor' => 'white']];
    if ($this->config['params']['companyid'] != 53) { //camera
      $this->config['dailynotif']['anniversaries'] = ['data' => $data2, 'title' => ['text' => 'MILESTONE', 'icon' => 'notifications', 'bgcolor' => 'blue', 'textcolor' => 'white']];
    }
  }

  public function loadQuestionnaire()
  {
    $user = $this->config['params']['user'];
    $getcols = ['action', 'listqtype', 'listrem', 'startdate', 'runtime'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:20px;whiteSpace:normal;min-width:20px;max-width:20px;';
    $cols[0]['btns']['view']['lookupclass'] = 'jumpmodule';
    $cols[3]['label'] = 'Start Date';
    $cols[3]['type'] = 'label';
    $cols[4]['type'] = 'label';
    $cols[4]['label'] = 'Runtime (mins)';
    $appid = $this->coreFunctions->datareader("select empid as value from app where username='" . $user . "'");
    $empid = $this->coreFunctions->datareader("select empid as value from employee where email='" . $user . "'");
    $filter = " and ex.clientid=" . $empid;
    if ($this->config['params']['logintype'] == '4e02771b55c0041180efc9fca6e04a77') $filter = " and ex.appid=" . $appid;
    $data = [];
    $qry = "select ex.qid as trno, q.qtype, q.rem, q.startdate, q.runtime, 'qnn' as doc, 'headtable/hris/' as url, 'headtable' as moduletype
      from examinees as ex left join qnhead as q on q.qid=ex.qid where 1=1 " . $filter;
    $data = $this->coreFunctions->opentable($qry);
    $this->config['sbclist']['viewquestionnaires'] = ['cols' => $cols, 'data' => $data, 'title' => 'Pending Exams'];
  }
  public function itinerary()
  {
    $companyid = $this->config['params']['companyid'];
    $getcols = ['action', 'dateid', 'clientname', 'startdate', 'enddate', 'remarks'];
    foreach ($getcols as $key => $value) {
      $$value = $key;
    }
    $stockbuttons = ['view'];

    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:20px;whiteSpace: normal;min-width:20px;max-width:20px;';
    $cols[$clientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;max-width:200px;';
    $cols[$dateid]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;max-width:100px;';
    $cols[$remarks]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;max-width:200px;';
    $cols[$action]['btns']['view']['action'] = 'itinerary';
    $cols[$action]['btns']['view']['lookupclass'] = 'customform';
    $cols[$clientname]['label'] = 'Name';
    $cols[$startdate]['label'] = 'Start Date';
    $data = $this->getitinerary();
    $this->config['sbclist']['itinerary'] = ['cols' => $cols, 'data' => $data, 'title' => 'PENDING TRAVEL APPLICATION', 'txtfield' => ['col1' => []], 'bgcolor' => 'bg-cyan-5', 'textcolor' => 'white'];
  }

  public function getitinerary()
  {

    $supervisor =  $this->checksupervisor($this->config);
    $adminid = $this->config['params']['adminid'];
    $url = 'App\Http\Classes\modules\payroll\\' . 'itinerary';
    $data = app($url)->approvers($this->config['params']);

    $filter = " and it.status = 'E' and approvedate is null and disapprovedate is null";

    $qry = "select it.trno as line, client.clientname,date(it.dateid) as dateid,
    date(it.startdate) as startdate,date(it.enddate) as enddate,it.remarks
    from itinerary as it left join client on client.clientid=it.empid
    left join employee as emp on emp.empid = it.empid 
    where submitdate is not null and emp.supervisorid  = $adminid $filter
    order by dateid, client.clientname";

    return $this->coreFunctions->opentable($qry);
  }

  public function taskassign()
  {
    $center = $this->config['params']['center'];
    $adminid = $this->config['params']['adminid'];
    $getcols = ['action', 'statname', 'clientname', 'description', 'startdate'];
    foreach ($getcols as $key => $value) {
      $$value = $key;
    }
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[0]['btns']['view']['lookupclass'] = 'jumptableentry';
    $cols[$startdate]['label'] = 'Start Date';
    $cols[$clientname]['label'] = 'Customer';
    $cols[$statname]['label'] = 'Status';
    $fields = [];
    $col1 = $this->fieldClass->create($fields);
    $paramsdata = $this->coreFunctions->opentable("SELECT 'XXX' as ourref");


    $qry = "select h.trno as clientid,h.trno, c.clientname,h.dateid,
       d.title as description,date(d.startdate) as startdate,
       (case h.status when 1 then (case d.status when 0 then 'Open' when '1' then 'Open' when '2' then 'Pending' when '3' then 'On-going' when '4' then 'For Checking' else 'Completed' end) else 'Close' end) as statname,    
       'TM' as doc,d.line,'ledgergrid/taskmonitoring/tk' as url
    from tmhead as h
    left join client as c on c.clientid = h.clientid
    left join tmdetail as d on d.trno=h.trno
    left join client as cla on cla.clientid = d.userid where d.userid =" . $adminid . " and h.status <> 2 and d.status in (2,3) 
    union all
    select h.trno as clientid,h.trno, c.clientname,h.dateid,
    d.title as description,date(d.startdate) as startdate, 
    'For Checking' as statname,   
    'TM' as doc,d.line,'ledgergrid/taskmonitoring/tk' as url
    from tmhead as h
    left join client as c on c.clientid = h.clientid
    left join tmdetail as d on d.trno=h.trno
    left join client as cla on cla.clientid = d.userid where h.requestby =" . $adminid . " and d.status = 4 order by dateid desc";
    $data = $this->coreFunctions->opentable($qry);
    $this->config['sbclist']['taskassign'] = ['cols' => $cols, 'data' => $data, 'title' => 'Listing of Assigned Task', 'txtfield' => ['col1' => $col1], 'paramsdata' => $paramsdata[0]];
  }


  public function dailytask()
  {
    $center = $this->config['params']['center'];
    $adminid = $this->config['params']['adminid'];
    $getcols = ['action', 'statname', 'rem', 'clientname', 'dateid', 'amt', 'modulename'];
    foreach ($getcols as $key => $value) {
      $$value = $key;
    }
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['btns']['view']['action'] = 'pdailytask';
    $cols[$action]['btns']['view']['lookupclass'] = 'tableentry';
    $cols[$dateid]['label'] = 'Create Date';
    $cols[$clientname]['label'] = 'Customer';
    $cols[$action]['style'] = 'width:50px;whiteSpace: normal;min-width:50px;max-width:50px;';
    $cols[$clientname]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;max-width:300px;';
    $cols[$rem]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;max-width:300px;';
    $cols[$rem]['label'] = 'Remarks';
    $cols[$amt]['label'] = 'Amount';
    $cols[$statname]['label'] = 'Status';
    $cols[$statname]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;max-width:100px;';
    $cols[$amt]['style'] = 'width:50px;whiteSpace: normal;min-width:50px;max-width:50px;';
    $data = $this->getdailytask();
    $this->config['sbclist']['dailytask'] = ['cols' => $cols, 'data' => $data, 'title' => 'Daily Task', 'txtfield' => ['col1' => []]];
  }


  public function getdailytask()
  {
    $adminid = $this->config['params']['adminid'];
    $currentdate = date('Y-m-d', strtotime($this->othersClass->getCurrentTimeStamp()));
    $qry = "select dt.trno as clientid,dt.trno, if(dt.reseller<>'',concat(c.clientname,'/ ',dt.reseller),c.clientname) as clientname,date(dt.dateid) as dateid, dt.amt,dt.rem,dt.statid,
              (case dt.statid when '0' then 'Pending' when '1' then 'Done' when '2' then 'Undone' when 4 then 'Neglect' when '5' then 'Cancelled'  when '6' then 'Returned' end) as statname,
              if(dt.tasktrno=0,'Daily Task','Task Monitoring') as modulename, dt.ischecker, dt.startchecker
              from dailytask as dt
              left join client as c on c.clientid = dt.clientid
              where dt.userid =" . $adminid . " and dt.reftrno=0  and dt.statid not in (2,4) and date(dt.dateid) ='" . $currentdate . "'
              union all
              
              select dt.trno as clientid,dt.trno, if(dt.reseller<>'',concat(c.clientname,'/ ',dt.reseller),c.clientname) as clientname,date(dt.dateid) as dateid, dt.amt,dt.rem,dt.statid,
              (case dt.statid when '0' then 'Pending' when '1' then 'Done' when '2' then 'Undone' when 4 then 'Neglect' when '5' then 'Cancelled' when '6' then 'Returned' end) as statname,
              if(dt.tasktrno=0,'Daily Task','Task Monitoring') as modulename, dt.ischecker, dt.startchecker
              from hdailytask as dt
              left join client as c on c.clientid = dt.clientid
              where dt.userid =" . $adminid . " and dt.reftrno=0  and dt.statid not in (2,4)   and date(dt.dateid) ='" . $currentdate . "'
              order by dateid desc, statid, trno desc";

    $result = $this->coreFunctions->opentable($qry);
    foreach ($result as $key => $value) {
      if ($value->ischecker == 1 && $value->statid == 0) {
        $result[$key]->statname = 'Checking Task';
      }
    }
    return $result;
  }

  private function hourlyserved()
  {
    $companyid = $this->config['params']['companyid'];
    $dateid = date('Y-m-d');
    $year = date('Y');
    $center = $this->config['params']['center'];
    $addunion = "";
    $qry = "select concat(m,':00') as m,sum(amt) as amt from (select DATE_FORMAT(head.dateid,'%H') as m,sum(head.isdone) as amt from 
    currentservice as head where left(head.dateid,10) <= '" . $dateid . "' and year(dateid)=" . $year . " group by DATE_FORMAT(head.dateid,'%H')
    union all
    select DATE_FORMAT(head.dateid,'%H') as m,sum(head.isdone) as amt from 
    hcurrentservice as head where left(head.dateid,10) <= '" . $dateid . "' and year(dateid)=" . $year . " group by DATE_FORMAT(head.dateid,'%H')) as T group by m order by m";

    $data = $this->coreFunctions->opentable($qry);
    $hr = []; //$this->coreFunctions->opentable("select distinct m from (".$qry.") as a");
    //$graphdata = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    $graphdata = [];
    foreach ($data as $key => $value) {
      $hr[$key] = $data[$key]->m;
      $graphdata[$key] = $data[$key]->amt;
    }
    //type of graph
    //1.bar
    //2.line
    //3.radar
    $series = [['name' => 'Total', 'data' => $graphdata]];
    $option = [
      'chart' => ['type' => 'bar', 'height' => 300],
      'plotOptions' => ['bar' => ['horizontal' => false, 'columnWidth' => '55%', 'endingShape' => 'rounded']],
      'title' => ['text' => 'Hourly Served ', 'align' => 'left', 'style' => ['color' => '#40403e']],
      'dataLabels' => ['enabled' => false],
      'xaxis' => ['categories' => $hr],
      'yaxis' => ['title' => ['text' => 'No. (customers)']],
      'colors' => ['#99a832'],
      'fill' => ['opacity' => 1]
    ];

    $this->config['sbcgraph']['hourlyserved'] = ['series' => $series, 'option' => $option];
  }

  private function servedperservice_pie()
  {
    $companyid = $this->config['params']['companyid'];
    $dateid = date('Y-m-d');
    $year = date('Y');
    $center = $this->config['params']['center'];
    $addunion = "";
    $qry = "select m,sum(amt) as amt,color from (select s.code as m,sum(head.isdone) as amt,s.color from 
    currentservice as head left join reqcategory as s on s.line = head.serviceline and s.isservice =1 where left(head.dateid,10) <= '" . $dateid . "' 
    and year(dateid)=" . $year . " group by s.code,s.color
    union all
    select s.code as m,sum(head.isdone) as amt,s.color from 
    hcurrentservice as head left join reqcategory as s on s.line = head.serviceline and s.isservice =1 
    where left(head.dateid,10) <= '" . $dateid . "' and year(dateid)=" . $year . " group by s.code,s.color) as T group by m,color";

    $data = $this->coreFunctions->opentable($qry);
    $hr = []; //$this->coreFunctions->opentable("select distinct m from (".$qry.") as a");
    //$graphdata = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    $graphdata = [];
    $colors = [];
    $total = 0;
    foreach ($data as $key => $value) {
      $total += $value->amt;
    }

    foreach ($data as $key => $value) {
      $hr[$key] = $data[$key]->m;
      $graphdata[$key] = round(($value->amt /  $total) * 100); // $data[$key]->amt;
      $colors[$key] = isset($value->color) ? $value->color : '';
    }
    //type of graph
    //1.bar
    //2.line
    //3.radar
    $series = $graphdata;
    $chartoption = [
      'chart' => ['type' => 'donut', 'height' => 350, 'width' => 300],
      'title' => ['text' => 'Served per Service', 'align' => 'left', 'style' => ['color' => '#40403e']],
      'labels' => $hr,
      'colors' => $colors,
      'plotOptions' => [
        'pie' => [
          'donut' => [
            'size' => '60%'
          ]
        ]
      ]
    ];
    $this->config['sbcgraph']['servedservice'] = ['series' => $series, 'option' => $chartoption];
  }

  private function servedperservice()
  {
    $companyid = $this->config['params']['companyid'];
    $dateid = date('Y-m-d');
    $year = date('Y');
    $center = $this->config['params']['center'];
    $addunion = "";
    $qry = "select m,sum(amt) as amt,color from (select s.code as m,sum(head.isdone) as amt,s.color from 
    currentservice as head left join reqcategory as s on s.line = head.serviceline and s.isservice =1 where left(head.dateid,10) <= '" . $dateid . "' 
    and year(dateid)=" . $year . " group by s.code,s.color
    union all
    select s.code as m,sum(head.isdone) as amt,s.color from 
    hcurrentservice as head left join reqcategory as s on s.line = head.serviceline and s.isservice =1 
    where left(head.dateid,10) <= '" . $dateid . "' and year(dateid)=" . $year . " group by s.code,s.color) as T group by m,color";

    $data = $this->coreFunctions->opentable($qry);
    $hr = []; //$this->coreFunctions->opentable("select distinct m from (".$qry.") as a");
    //$graphdata = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    $graphdata = [];
    $colors = [];
    $total = 0;

    foreach ($data as $key => $value) {
      $hr[$key] = $data[$key]->m;
      $graphdata[$key] =  $data[$key]->amt;
      $colors[$key] = isset($value->color) ? $value->color : '';
    }
    //type of graph
    //1.bar
    //2.line
    //3.radar
    $series = [['name' => 'Total', 'data' => $graphdata]];
    $option = [
      'chart' => ['type' => 'bar', 'height' => 300],
      'plotOptions' => ['bar' => ['horizontal' => false, 'columnWidth' => '55%', 'endingShape' => 'rounded', 'distributed' => true]],
      'title' => ['text' => 'Served per Service ', 'align' => 'left', 'style' => ['color' => 'black']],
      'dataLabels' => ['enabled' => false],
      'xaxis' => ['categories' => $hr],
      'yaxis' => ['title' => ['text' => 'No. (customers)']],
      'colors' => $colors,
      'fill' => ['opacity' => 1]
    ];

    $this->config['sbcgraph']['servedservice'] = ['series' => $series, 'option' => $option];
  }

  public function ticketsummary()
  {
    $center = $this->config['params']['center'];
    $getcols = ['type', 'served', 'cancelrem'];
    foreach ($getcols as $key => $value) {
      $$value = $key;
    }
    $dateid = date('Y-m-d');
    $year = date('Y');
    $cols = $this->tabClass->createdoclisting($getcols, []);
    $cols[$cancelrem]['label'] = 'Cancelled';
    $cols[$type]['label'] = 'Customer Type';
    $qry = "select type,sum(served) as served,sum(cancelrem) as cancelrem from (select 'Regular' as type, sum(isdone) as served,sum(iscancel) as cancelrem from currentservice where ispwd =0 and year(dateid) = " . $year . " 
    union all
    select 'Regular' as type, sum(isdone) as served,sum(iscancel) as cancelrem from hcurrentservice where ispwd =0 and year(dateid) = " . $year . " 
    union all
    select 'Priority' as type, sum(isdone) as served,sum(iscancel) as cancelrem from currentservice where ispwd =1 and year(dateid) = " . $year . " 
    union all
    select 'Priority' as type, sum(isdone) as served,sum(iscancel) as cancelrem from hcurrentservice where ispwd =1 and year(dateid) = " . $year . ") as a group by type";
    $data = $this->coreFunctions->opentable($qry);
    $this->config['sbclist']['ticketsummary'] = ['cols' => $cols, 'data' => $data, 'title' => 'Ticket Summary Per Customer Type', 'bgcolor' => 'bg-green-8', 'textcolor' => 'white'];
  }

  public function ticketsummaryperservice()
  {
    $center = $this->config['params']['center'];
    $getcols = ['type', 'served', 'cancelrem'];
    foreach ($getcols as $key => $value) {
      $$value = $key;
    }
    $dateid = date('Y-m-d');
    $year = date('Y');
    $cols = $this->tabClass->createdoclisting($getcols, []);
    $cols[$cancelrem]['label'] = 'Cancelled';
    $cols[$type]['label'] = 'Service';
    $qry = "select type,sum(served) as served,sum(cancelrem) as cancelrem from (select req.code as type, sum(cs.isdone) as served,sum(cs.iscancel) as cancelrem from currentservice as cs left join reqcategory as req on req.line = cs.serviceline and req.isservice =1 
    where year(dateid) = " . $year . " and req.code is not null group by req.code
    union all
    select req.code as type, sum(cs.isdone) as served,sum(cs.iscancel) as cancelrem from hcurrentservice as cs left join reqcategory as req on req.line = cs.serviceline and req.isservice =1 
    where year(dateid) = " . $year . " and req.code is not null group by req.code) as a group by type order by served desc";
    $data = $this->coreFunctions->opentable($qry);
    $this->config['sbclist']['ticketsummaryperservice'] = ['cols' => $cols, 'data' => $data, 'title' => 'Ticket Summary Per Service', 'bgcolor' => 'bg-teal-9', 'textcolor' => 'white'];
  }
} // end class
