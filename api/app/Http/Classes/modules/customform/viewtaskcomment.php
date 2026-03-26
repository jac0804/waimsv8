<?php

namespace App\Http\Classes\modules\customform;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;

use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\modules\tableentry\entrytaskcomment;

class viewtaskcomment
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'View Task Comments';
  public $gridname = 'multigrid2';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $grid;
  private $logger;
  public $style = 'width:1500px;max-width:1500px;';
  public $issearchshow = true;
  public $showclosebtn = true;
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->grid = new entrytaskcomment;
  }

  public function createTab($config)
  {

    $tab = [
      $this->gridname => ['action' => 'tableentry', 'lookupclass' => 'entrytaskcomment', 'label' => 'COMMENTS']
    ];

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

  public function createHeadField($config)
  {
    $viewall = $this->othersClass->checkAccess($config['params']['user'], 5479);
    $moduletype = $config['params']['moduletype'];
    $companyid = $config['params']['companyid'];
    $userid = $config['params']['adminid'];
    $doc = $config['params']['doc'];
    $fields = ['rem'];
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'rem.readonly', false);
    $fields = ['update'];

    if ($doc == 'TK') {
      if ($viewall == '1') {
        $fields = ['update'];
      } else {
        if ($userid != $config['params']['row']['assignedid']) {
          $fields = [];
        }
      }
    }

    if ($doc == 'TM') {
      $trno = $config['params']['row']['trno'];
      $reqbyid = $this->coreFunctions->getfieldvalue("tmhead", "requestby", "trno=?", [$trno]);
      if ($userid != $reqbyid) {
        $fields = [];
      }
    }

    $col2 = $this->fieldClass->create($fields);
    if (!empty($fields)) {
      data_set($col2, 'update.label', 'Add Comment');
    }
    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    return $this->getheaddata($config);
  }

  public function getheaddata($config)
  {
    // if($config['params']['moduletype']=='dashboard'){
    //   $doc='TM';
    // }else{
    //   $doc = $config['params']['doc'];
    // }
    // var_dump($config['params']);
    // break;
    // if($config['params']['doc']=='DY'){
    // $trno = $config['params']['row']['tasktrno'];
    // $line = $config['params']['row']['taskline'];
    // }else{
    // // $trno = $config['params']['row']['trno'];
    $trno = isset($config['params']['row']['tasktrno']) ? $config['params']['row']['tasktrno'] : (isset($config['params']['row']['trno']) ? $config['params']['row']['trno'] : 0);
    $line = isset($config['params']['row']['taskline']) ? $config['params']['row']['taskline'] : (isset($config['params']['row']['line']) ? $config['params']['row']['line'] : 0);


    $otherTrnoField = '';
    $otherTrnoVal = 0;

    if ($config['params']['row']['doc'] == 'DY' && $trno == 0) { //add return notes from checker in manual daily task
      $otherTrnoField = 'dytrno';
      $otherTrnoVal = $config['params']['row']['trno'];
    }


    //  $line = isset($config['params']['row']['line']) ? $config['params']['row']['taskline'] : 0;
    // }


    // switch ($doc) {
    // //   case 'CV':
    // //     $qry = "select '$trno' as cvtrno,'$line' as cvline,'' as createby,'' as createdate,'' as rem";
    // //     break;
    //   case 'TM':
    //   case 'TK':
    $qry = "select '$trno' as tmtrno,'$line' as tmline,'' as createby,'' as createdate,'' as rem, '" . $otherTrnoField . "' as othertrnofield, " . $otherTrnoVal . " as othertrnoval";
    //   break;

    // default:
    //   $qry = "select '$trno' as tasktrno,'$line' as taskline,'' as createby,'' as createdate,'' as rem";
    //   break;
    // }
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  public function data($config)
  {
    return [];
  }

  public function loaddata($config)
  {
    // var_dump($config['params']);
    // break;
    $adminid = $config['params']['adminid'];
    $dstatus = 0;
    if ($config['params']['moduletype'] == 'dashboard') {
      $doc = 'TM';
      $dstatus = 1;
    } else {
      $doc = $config['params']['doc'];
    }
    // var_dump($config['params']['dataparams']);
    // break;
    switch ($doc) {
      case 'TM':
      case 'TK':
        $trno = isset($config['params']['dataparams']['tmtrno']) ? $config['params']['dataparams']['tmtrno'] : 0;
        $line = isset($config['params']['dataparams']['tmline']) ? $config['params']['dataparams']['tmline'] : 0;
        break;
    }
    // control how buttons behave
    // the data in return will showcase the values in the grid, made in createtab function
    // the txtdata in return will display the values in the head, made in createheadfield function

    $otherTrnoField = isset($config['params']['dataparams']['othertrnofield']) ? $config['params']['dataparams']['othertrnofield'] : '';
    $otherTrnoVal = isset($config['params']['dataparams']['othertrnoval']) ? $config['params']['dataparams']['othertrnoval'] : 0;

    $origTrno = 0;

    switch ($config['params']['action2']) {
      case 'history':
        if ($otherTrnoField == 'dytrno') {
          $data = $this->loadhistory2($config, $otherTrnoVal, "dytrno");
        } else {
          $data = $this->loadhistory($config, $trno, $line);
        }

        switch ($doc) {
          case 'TM':
          case 'TK':
            $txtdata = $this->coreFunctions->opentable("select '' as rem, " . $trno . " as tmtrno, " . $line . " as tmline,'" . $otherTrnoField . "'  as othertrnofield, " . $otherTrnoVal . " as othertrnoval");
            break;
        }

        if (empty($data) && empty($txtdata)) {
          return ['status' => false, 'msg' => 'No comment history', 'data' => [], 'txtdata' => []];
        } else {
          return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => $data, 'txtdata' => $txtdata];
        }
        break;

      case 'update':
        $updatedrem = $config['params']['dataparams']['rem'];
        if ($updatedrem != '') { //check comment if not empty
          $createby = $config['params']['user'];
          $date = $this->othersClass->getCurrentTimeStamp();

          switch ($doc) {
            case 'TM':
            case 'TK':
              $columns = ['rem' => $updatedrem, 'tmtrno' => $trno, 'tmline' => $line, 'createby' => $createby, 'createdate' => $date];
              $txtdata = $this->coreFunctions->opentable("select '' as rem, " . $trno . " as tmtrno, " . $line . " as tmline");

              if ($otherTrnoField == 'dytrno') {
                $origTrno = $this->coreFunctions->getfieldvalue('dailytask', "refx", "trno=?", [$otherTrnoVal]);
                $columns['dytrno'] = $origTrno;
              }
              break;
          }

          if ($dstatus == 1) { //kapag nareturn dito papasok
            $assignedid = 0;
            $assigned = '';

            //pag katapos niya magcomment- nainsert na, kukunin ang id ng naka assign para bumalik sa kanya  
            if ($trno != 0) {
              $assignedid = $this->coreFunctions->getfieldvalue('tmdetail', "userid", "trno=? and line=?", [$trno, $line]);
              $assigned = $this->coreFunctions->getfieldvalue("client", "clientname", "clientid=?", [$assignedid]);

              $checkerid = $this->coreFunctions->getfieldvalue('tmhead', "checkerid", "trno=?", [$trno]);
              if ($checkerid != 0 && $checkerid != $adminid) {
                $assignedid = $checkerid;
                $assigned = $this->coreFunctions->getfieldvalue("client", "clientname", "clientid=?", [$assignedid]);
              }
            } else {
              if ($otherTrnoField == 'dytrno') {
                $assignedid = $this->coreFunctions->datareader("select h.userid as value from dailytask as dc join hdailytask as h on h.trno=dc.refx and h.refx=0 where dc.trno=" . $otherTrnoVal, [], '', true);
                if ($assignedid != 0) {
                  $assigned = $this->coreFunctions->getfieldvalue("client", "clientname", "clientid=?", [$assignedid]);
                }
                Logger('assignedid: ' . $assignedid);
              } else {
                return ['status' => false, 'msg' => 'No task found to add comment.', 'data' => [], 'txtdata' => []];
              }
            }

            $data2 = [];
            $url = 'App\Http\Classes\modules\taskmonitoring\\' . 'tm';

            if ($assignedid != 0) {
              if ($trno != 0) { //connected sa task monitoring

                $dytrno = isset($config['params']['dataparams']['othertrnoval']) ? $config['params']['dataparams']['othertrnoval'] : 0;
                $condDytrno = "";
                if ($dytrno != 0) {
                  $condDytrno = " and trno=" . $dytrno;
                }

                $taskstat = $this->coreFunctions->datareader("select statid as value  from dailytask  where tasktrno=" . $trno . $condDytrno .   " and taskline=" . $line, [],  '',  true);
                if ($taskstat == 0) {
                  $this->coreFunctions->sbcinsert("headprrem", $columns);
                  $task = $this->coreFunctions->getfieldvalue('tmdetail', "title", "trno=? and line=?", [$trno, $line]); //assigned
                  $qry1 = "select line,rem from headprrem where tmtrno=$trno and tmline=$line  order by line desc limit 1";
                  $lines2 = $this->coreFunctions->opentable($qry1);
                  if (!empty($lines2)) {
                    $stat = $this->coreFunctions->sbcupdate('tmdetail', ['fcheckingdate' => null, 'status' => 3], ['trno' => $trno, 'line' => $line]); //status is on going para i aaccept uli

                    if ($stat) {
                      //COMMENT
                      $insertreturn = $this->othersClass->insertUpdatePendingapp($trno, $lines2[0]->line, 'TM', $data2, $url, $config, $assignedid, false, true, 'COMMENT'); //insert sa pendingapp

                      if ($insertreturn) {
                        $dailytasktrno = $this->coreFunctions->getfieldvalue('dailytask', "trno", "tasktrno=? and taskline=?", [$trno, $line]);
                        $datenow = $this->othersClass->getCurrentTimeStamp();
                        $update_dailytask =  $this->coreFunctions->sbcupdate('dailytask', ['statid' => 6, 'donedate' => $datenow], ['trno' => $dailytasktrno]); //6 return status

                        if ($update_dailytask) { //insert sa history

                          $urlHistory = 'App\Http\Classes\modules\tableentry\\' . 'pdailytask';
                          $this->coreFunctions->execqry(app($urlHistory)->transferhistoryquery(), 'insert', [$dailytasktrno]);
                          $this->coreFunctions->execqry("delete from dailytask where  trno=" . $dailytasktrno, 'delete'); //delete sa dailytask

                          $insertp = $this->othersClass->insertUpdatePendingapp($trno, $line, 'TM', $data2, $url, $config, $assignedid, false, true, 'RETURN'); //insert sa pendingapp ng assigned

                          $config['params']['doc'] = 'DY';
                          $this->logger->sbcmasterlog2($dailytasktrno, $config, ' Daily task has been done and returned to ' . $assigned . '  by ' . $config['params']['user'] . '.', 'task_log');

                          $config['params']['doc'] = 'ENTRYTASK';
                          $this->logger->sbcmasterlog($trno, $config, 'Task - ' . $task . ' on Line: ' . $line . ' has been returned to ' . $assigned . '  by ' . $config['params']['user'] . '.');
                        }
                      }
                    }
                  }
                } else {
                  return ['status' => false, 'msg' => 'This task has been done or returned. Please refresh the page.', 'data' => [], 'txtdata' => []];
                }
              } else {

                Logger('otherTrnoVal: ' . $otherTrnoVal);

                $origTrno = $this->coreFunctions->datareader("select refx as value from dailytask where trno=" . $otherTrnoVal, [], '', true);

                Logger('refx: ' . $origTrno);

                if ($origTrno != 0) { //checker na ibabalik kay user
                  $taskstat = $this->coreFunctions->datareader("select statid as value from dailytask where trno =" . $otherTrnoVal, [], '', true);
                  if ($taskstat == 0) {
                    $this->coreFunctions->sbcinsert("headprrem", $columns);
                    $qry1 = "select line,rem from headprrem where dytrno=$origTrno order by line desc limit 1";
                    $lines2 = $this->coreFunctions->opentable($qry1);
                    if (!empty($lines2)) {
                      $url = 'App\Http\Classes\modules\taskmonitoring\\' . 'dy';
                      $insertreturn = $this->othersClass->insertUpdatePendingapp($origTrno, $lines2[0]->line, 'DY', $data2, $url, $config, $assignedid, false, true, 'COMMENT'); //insert sa pendingapp

                      if ($insertreturn) {
                        $datenow = $this->othersClass->getCurrentTimeStamp();
                        $update_dailytask =  $this->coreFunctions->sbcupdate('dailytask', ['statid' => 6, 'donedate' => $datenow], ['trno' => $otherTrnoVal]); //6 return status
                        if ($update_dailytask) { //insert sa history
                          $urlHistory = 'App\Http\Classes\modules\tableentry\\' . 'pdailytask';
                          $this->coreFunctions->execqry(app($urlHistory)->transferhistoryquery(), 'insert', [$otherTrnoVal]);
                          $this->coreFunctions->execqry("delete from dailytask where  trno=" . $otherTrnoVal, 'delete'); //delete sa dailytask

                          $insertp = $this->othersClass->insertUpdatePendingapp($otherTrnoVal, 0, 'DY', $data2, $url, $config, $assignedid, false, true, 'RETURN'); //insert sa pendingapp ng assigned

                          $config['params']['doc'] = 'DY';
                          $this->logger->sbcmasterlog2($otherTrnoVal, $config, ' Daily task has been checked and returned to ' . $assigned . '  by ' . $config['params']['user'] . '.', 'task_log');
                        }
                      }
                    }
                  } else {
                    return ['status' => false, 'msg' => 'This task has been done or returned. Please refresh the page.', 'data' => [], 'txtdata' => []];
                  }
                }
              }
            }
          }

          $config['params']['doc'] = $doc;

          if ($doc == 'TK' || $doc == 'TM' && $dstatus != 1) {
            $data2 = [];
            $url = 'App\Http\Classes\modules\taskmonitoring\\' . 'tm';

            $idhere = $this->coreFunctions->getfieldvalue('tmhead', "requestby", "trno=?", [$trno]); //requestor
            if ($adminid == $idhere) { //
              $idhere = $this->coreFunctions->getfieldvalue('tmdetail', "userid", "trno=? and line=?", [$trno, $line]); //assigned
            }

            $linesz = [0];
            $qry = "select line from pendingapp where trno=$trno ";
            $lines = $this->coreFunctions->opentable($qry);
            //  var_dump($lines);
            //  break;
            if (!empty($lines)) {
              foreach ($lines as $key => $value) {
                $linesz[] = $value->line;
              }
            }

            $string = implode(',', $linesz);
            // var_dump($string);

            $qry1 = "select rem,line from headprrem where tmtrno=$trno and tmline=$line and line not in ($string) order by line desc limit 1";

            $lines2 = $this->coreFunctions->opentable($qry1);
            if (!empty($lines2)) {
              $this->othersClass->insertUpdatePendingapp($trno, $lines2[0]->line, 'TM', $data2, $url, $config, $idhere, false, true); //insert sa pendingapp
              $stat = $this->coreFunctions->sbcupdate('pendingapp', ['approver' => 'COMMENT'], ['trno' => $trno, 'line' => $lines2[0]->line]);
              $config['params']['doc'] = 'ENTRYTASK';
              $task = $this->coreFunctions->getfieldvalue('tmdetail', "title", "trno=? and line=?", [$trno, $line]); //assigned
              $this->logger->sbcmasterlog($trno, $config, 'CREATE - Comment: ' . $lines2[0]->rem . ' for Task: ' . $task . ' on Line: ' . $line);
            }
          }

          $config['params']['doc'] = $doc;
          $data = $this->grid->loaddata($config);
          switch ($doc) {
            case 'TM':
            case 'TK':
              return ['status' => true, 'msg' => 'Successfully loaded.', 'multitableentrydata' => $data, 'multitableentryname' => 'Test', 'txtdata' => $txtdata];
              break;
            default:
              return ['status' => true, 'msg' => 'Successfully loaded.', 'tableentrydata' => $data, 'txtdata' => $txtdata];
              break;
          }
        } else {

          return ['status' => false, 'msg' => 'Please add comment.', 'data' => [], 'txtdata' => []];
        }

        break;
    }
  } //end function


  public function loadhistory($config, $trno, $line)
  {
    $qry = "select ifnull(pr.rem,'') as rem from headprrem as pr where pr.rrtrno=$trno and pr.rrline=$line
    order by pr.line desc";

    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  public function loadhistory2($config, $trno, $trnofield)
  {
    $qry = "select ifnull(pr.rem,'') as rem from headprrem as pr where pr." . $trnofield . "=$trno order by pr.line desc";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }
} //end class
