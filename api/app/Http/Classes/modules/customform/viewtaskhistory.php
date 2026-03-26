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
use App\Http\Classes\modules\taskentry\taskhistory;

class viewtaskhistory
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Task History';
  public $gridname = 'multigrid2';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $grid;
  private $logger;
  public $style = 'width:1500px;max-width:1500px;';
  public $issearchshow = true;
  public $showclosebtn = true;
  public $tablelogs = 'task_log';
  public $tablelogs_del = 'del_task_log';


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->grid = new taskhistory;
  }

  public function createTab($config)
  {

    $tab = [
      $this->gridname => ['action' => 'taskentry', 'lookupclass' => 'taskhistory', 'label' => 'Task history']
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

    // $filter = '';
    // $params = [];
    // if ($config['params']['row']['tasktrno'] == 0 && $config['params']['row']['taskline'] == 0) {
    //   $filter = "where trno = ?";
    //   $params = [$config['params']['row']['refx']];
    // } else {
    //   $filter = "where tasktrno = ? and taskline = ?";
    //   $params = [
    //     $config['params']['row']['tasktrno'],
    //     $config['params']['row']['taskline']
    //   ];
    // }

    // $exist = $this->coreFunctions->datareader("select trno as value from (
    //     select trno, tasktrno, taskline from dailytask
    //     union all
    //     select trno, tasktrno, taskline from hdailytask) as a $filter order by trno asc limit 1", $params);

    // if ($exist) {
    //   $cdexist = $this->coreFunctions->datareader("select dytrno as value from creditdetail where dytrno=? ", [$exist]);
    // }


    $fields = [['selectprefix', 'category'], ['hours', 'totalreturn']];
    $col1 = $this->fieldClass->create($fields);

    // if ($cdexist) {
    //   data_set($col1, 'selectprefix.readonly', true);
    //   data_set($col1, 'category.readonly', true);
    //   data_set($col1, 'hours.readonly', true);
    //   data_set($col1, 'totalreturn.readonly', true);
    // } else {
    data_set($col1, 'selectprefix.label', 'Task Complexity');
    data_set($col1, 'selectprefix.options', array(
      ['label' => 'Very low', 'value' => 0.1],
      ['label' => 'Low', 'value' => 0.5],
      ['label' => 'Medium', 'value' => 1],
      ['label' => 'High', 'value' => 1.5],
      ['label' => 'Critical', 'value' => 2]
    ));
    data_set($col1, 'hours.label', 'Total Hours');
    data_set($col1, 'category.type', 'qselect');
    data_set($col1, 'category.class', 'cscategory');
    data_set($col1, 'category.readonly', false);

    $categorylist = $this->coreFunctions->datareader("select group_concat(category) as value from reqcategory where istaskcat=1", [], '', '', true);
    if ($categorylist != '') {
      $catlist = explode(",", $categorylist);
      $list = array();
      foreach ($catlist as $key) {
        array_push($list, ['label' => $key, 'value' => $key]);
      }
      data_set($col1, 'category.options', $list);
    }
    // }


    $fields = ['update'];
    // if ($cdexist) {
    //   $fields = [];
    // }

    $col2 = $this->fieldClass->create($fields);
    // if (!$cdexist) {
    if (!empty($fields)) {
      data_set($col2, 'update.label', 'SUBMIT');
    }
    // }
    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // var_dump($config['params']);
    return $this->getheaddata($config);
  }

  public function getheaddata($config)
  {
  
    $trno = isset($config['params']['row']['tasktrno']) ? $config['params']['row']['tasktrno'] : 0;
    $line = isset($config['params']['row']['taskline']) ? $config['params']['row']['taskline'] :  0;

    // $tmuserid = $config['params']['row']['tmuserid'];
    $tmuserid = isset($config['params']['row']['tmuserid']) ? $config['params']['row']['tmuserid'] : (isset($config['params']['dataparams']['tmuserid']) ? $config['params']['dataparams']['tmuserid'] : 0);

    // $refx = $config['params']['row']['refx'];
    $refx = isset($config['params']['row']['refx']) ? $config['params']['row']['refx'] : (isset($config['params']['dataparams']['refx']) ? $config['params']['dataparams']['refx'] : 0);
    $solution = $config['params']['row']['rem1'];
    $statid = $config['params']['row']['statid'];
    $filter = " where dt.tasktrno = '$trno'  and dt.taskline = '$line' and dt.ischecker = 0 ";

    if ($trno == 0 && $line == 0) {
      $dyuserid = $this->coreFunctions->datareader("select userid as value from hdailytask where trno=? ", [$refx]);
      $dyuserid = ($dyuserid != 0) ? $dyuserid : 0;
      $filter = " where dt.trno = '$refx'  and dt.ischecker = 0 ";
    }

    if ($tmuserid == 0) {
      $tmuserid = $dyuserid;
    }

    //checker dailytask trno
    $ctrno = isset($config['params']['row']['trno']) ? $config['params']['row']['trno'] : 0;
    //checkerid
    $checkerid = isset($config['params']['row']['empid']) ? $config['params']['row']['empid'] : 0;


    // $qry = "select '$trno' as tmtrno,'$line' as tmline,'' as createby,'' as createdate, '" . $otherTrnoField . "' as othertrnofield, " . $otherTrnoVal . " as othertrnoval, 'Low' as selectprefix";
    $qry = "select '$trno' as tmtrno, '$line' as tmline,    '' as createby, '' as createdate, 'Low' as selectprefix,
           (select sum(tothrs)
           FROM ( select round(timestampdiff(second,dt.createdate,if(dt.donedate is null, current_timestamp, dt.donedate)) / 3600,2) as tothrs
            from hdailytask as dt  $filter ) as hrs) as hours,
             " . $tmuserid . " as tmuserid,
            (select date(dt.donedate) as donedate
              from hdailytask as dt
              $filter order by donedate desc limit 1) as donedate,
            (select count(dt.trno) as returncount
              from hdailytask as dt
              where dt.tasktrno='$trno' and dt.taskline='$line' and dt.statid=6) as totalreturn,
              
            (select dt.trno from hdailytask as dt
              $filter order by trno asc limit 1) as dytrno," . $refx . " as refx, '' as category, '" . $solution . "' as rem1, '$ctrno' as checkertrno,
            (select rem from hdailytask as dt $filter ) as rem, '$checkerid' as checkerid , '$statid' as statid ";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  public function data($config)
  {
    return [];
  }

  public function loaddata($config)
  {
    $action = $config['params']['action2'];
    $dytrno = $config['params']['dataparams']['dytrno'];
    if ($action == 'update') {
      $trno = $config['params']['dataparams']['tmtrno'];
      $line = $config['params']['dataparams']['tmline'];
      $refx = $config['params']['dataparams']['refx'];
      $tmuserid = $config['params']['dataparams']['tmuserid'];
      $createdate = $this->othersClass->getCurrentTimeStamp();
      $createby = $config['params']['user'];
      $hours = $config['params']['dataparams']['hours'];
      $donedate = $config['params']['dataparams']['donedate'];
      $totalreturn = $config['params']['dataparams']['totalreturn'];
      $dateToSave = date('Y-m-01', strtotime($donedate));
      $totalpts = isset($config['params']['dataparams']['selectprefix']['value']) ? $config['params']['dataparams']['selectprefix']['value'] : 0.5;
      // $msg = 'Data has been inserted or updated.';
      $solutionremarks = $config['params']['dataparams']['rem1'];
      $checkertrno = $config['params']['dataparams']['checkertrno']; //trno ng current na ginawang task
      $checkerid = $config['params']['dataparams']['checkerid'];
      $rem = $config['params']['dataparams']['rem'];
      $statid = $config['params']['dataparams']['statid'];

      $category = isset($config['params']['dataparams']['category']['value']) ? $config['params']['dataparams']['category']['value'] : '';
      $catname = trim($category);
      $catid = $this->coreFunctions->getfieldvalue("reqcategory", "line", "category=?", [$catname]);

      $credituserid = $this->coreFunctions->getfieldvalue("credithead", "userid", "userid=?", [$tmuserid]);
      $cdatehere = $this->coreFunctions->getfieldvalue("credithead", "dateid", "userid=? and date_format(dateid, '%Y-%m-%d')=?", [$tmuserid, $dateToSave]);

      // if ($credituserid == 0 || $credituserid == '' && $cdatehere == null || $cdatehere == '') {
      if (($credituserid == 0 || $credituserid == '') && ($cdatehere == null || $cdatehere == '') || ($credituserid != 0 && $credituserid != '') && ($cdatehere == null || $cdatehere == '')) {
        // Insert new record sa credithead 
        $cdata = [
          'userid' => $tmuserid,
          'dateid' => $dateToSave,
          'totalhrs' => $hours,
          'createby' => $createby,
          'createdate' => $createdate,
          'totalpts' => $totalpts,
          'totalrt' => $totalreturn
        ];
        $inshead = $this->coreFunctions->insertGetId('credithead', $cdata);
        if ($inshead != 0) {  //line 1 ng trno na magegenerate
          $lines = 1;
          $detailData = [
            'trno' => $inshead,
            'line' => $lines,
            'dytrno' => $dytrno,
            'dateid' => $donedate,
            'totalhrs' =>  $hours,
            'totalpts' => $totalpts,
            'totalrt' => $totalreturn,
            'createby' => $createby,
            'createdate' => $createdate
          ];
          $this->coreFunctions->insertGetId('creditdetail', $detailData);
        }
        //update category
        if ($refx == 0) {
          $updatedailytask = $this->coreFunctions->sbcupdate('tmdetail', ['taskcatid' => $catid], ['userid' => $tmuserid, 'trno' => $trno, 'line' => $line]);
        } else {
          $updatedailytask = $this->coreFunctions->sbcupdate('hdailytask', ['taskcatid' => $catid], ['userid' => $tmuserid, 'trno' => $refx]);
        }
      } else { //may userid na sa head
        // if($cdatehere != null || $cdatehere != ''){ //pag may existing sa head na same month at user
        $cheadtrno = $this->coreFunctions->getfieldvalue("credithead", "trno", "userid=? and date_format(dateid, '%Y-%m-%d')=?", [$tmuserid, $dateToSave]);
        // $inshead = $this->coreFunctions->getfieldvalue("credithead", "trno", "userid=?", [$tmuserid]);
        $getline = $this->coreFunctions->getfieldvalue("creditdetail", "line", "trno=? order by line desc", [$cheadtrno]);
        $lines = $getline + 1;
        $detailData = [
          'trno' => $cheadtrno,
          'line' => $lines,
          'dytrno' => $dytrno,
          'dateid' => $donedate,
          'totalhrs' =>  $hours,
          'totalpts' => $totalpts,
          'totalrt' => $totalreturn,
          'createby' => $createby,
          'createdate' => $createdate
        ];
        $this->coreFunctions->insertGetId('creditdetail', $detailData);

        //update category
        if ($refx == 0) {
          $updatedailytask = $this->coreFunctions->sbcupdate('tmdetail', ['taskcatid' => $catid], ['userid' => $tmuserid, 'trno' => $trno, 'line' => $line]);
        } else {
          $updatedailytask = $this->coreFunctions->sbcupdate('hdailytask', ['taskcatid' => $catid], ['userid' => $tmuserid, 'trno' => $refx]);
        }

        $qryhead = "select totalhrs,totalpts,totalrt from credithead where userid=? and date_format(dateid, '%Y-%m-%d')=?";
        $data3 = $this->coreFunctions->opentable($qryhead, [$tmuserid, $dateToSave]);

        $headtlhrs = $data3[0]->totalhrs;
        $headtlpts = $data3[0]->totalpts;
        $headtlrt = $data3[0]->totalrt;

        $gtotalhrs = $headtlhrs + $hours;
        $gtotalpts = $headtlpts + $totalpts;
        $gtotalrt = $headtlrt + $totalreturn;
        $updatehead = $this->coreFunctions->sbcupdate('credithead', ['totalhrs' => $gtotalhrs, 'totalpts' => $gtotalpts, 'totalrt' => $gtotalrt], ['userid' => $tmuserid, 'trno' => $cheadtrno]);
      }

      $datenow = $this->othersClass->getCurrentTimeStamp();
      $adminid = $config['params']['adminid'];
      // $solutionrem = $solutionremarks;
      $requestorid = 0;
      // if ($trno != 0) $requestorid = $this->coreFunctions->getfieldvalue("tmhead", "requestby", "trno=?", [$trno]);

      if ($trno != 0) {
        $requestorid = $this->coreFunctions->getfieldvalue("tmhead", "requestby", "trno=?", [$trno]);
      } else {
        $requestorid = $this->coreFunctions->getfieldvalue("hdailytask", "empid", "trno=?", [$refx]);
      }


      $creator = $this->coreFunctions->datareader("select c.clientname as value from dailytask as d
                                                          left join client as c on c.clientid=d.userid
                                                          where  d.trno = ?", [$checkertrno]);
      if ($requestorid == $adminid) { // project head

        if ($trno != 0) { //task monitoring 
          $tmstat = $this->coreFunctions->sbcupdate('tmdetail', ['enddate' => $datenow, 'status' => 5], ['trno' => $trno, 'line' => $line]);
          $label =  'Successfully completed the task.';
          if ($tmstat) { //update
            $update_dailytask =  $this->coreFunctions->sbcupdate('dailytask', ['statid' => 1, 'donedate' => $datenow, 'rem1' => $solutionremarks], ['trno' => $checkertrno]);

            if ($update_dailytask) { // insert sa history
              $urlHistory = 'App\Http\Classes\modules\tableentry\\' . 'pdailytask';
              $inserthistory = $this->coreFunctions->execqry(app($urlHistory)->transferhistoryquery(), 'insert', [$checkertrno]);
              //delete sa dailytask ng checker
              $this->coreFunctions->execqry("delete from dailytask where  trno=" . $checkertrno, 'delete');
              $config['params']['doc'] = 'DY';
              $this->logger->sbcmasterlog($checkertrno, $config, ' Daily task has been done by ' . $creator);
              $config['params']['doc'] = 'ENTRYTASK';
              $this->logger->sbcmasterlog2($trno, $config, ' Line: ' . $line . ' , ' . $creator . ' ' . ' has already completed the task.', 'masterfile_log');
              $socketmsg = "Task checked and completed: " . $rem;
              if ($checkerid != 0) {
                $usernameC = $this->coreFunctions->getfieldvalue("client", "email", "clientid=?", [$checkerid]);
                if ($socketmsg != '') $this->othersClass->socketmsg($config, $socketmsg, '', $usernameC);
              }
              if ($tmuserid != 0) {
                $usernameU = $this->coreFunctions->getfieldvalue("client", "email", "clientid=?", [$tmuserid]);
                if ($socketmsg != '') $this->othersClass->socketmsg($config, $socketmsg, '', $usernameU);
              }
            }
          }
        } else {  //done ng checker para sa manual DY
          $update_dailytask =  $this->coreFunctions->sbcupdate('dailytask', ['statid' => 1, 'donedate' => $datenow, 'rem1' => $solutionremarks], ['trno' => $checkertrno]);
          $label =  'Successfully done.';
          if ($update_dailytask) {
            $urlHistory = 'App\Http\Classes\modules\tableentry\\' . 'pdailytask';
            $dtinsert = $this->coreFunctions->execqry(app($urlHistory)->transferhistoryquery(), 'insert', [$checkertrno]);
            if ($dtinsert) {
              $this->coreFunctions->execqry("delete from dailytask where  trno=" . $checkertrno, 'delete'); //delete sa dailytask ng checker
              if ($checkerid != 0) {
                $config['params']['doc'] = 'DY';
                $this->logger->sbcmasterlog($checkertrno, $config, 'Task checked and completed');
                $dyuser = $this->coreFunctions->getfieldvalue("hdailytask", "userid", "trno=?", [$refx]);
                if ($dyuser != 0) {
                  $username = $this->coreFunctions->getfieldvalue("client", "email", "clientid=?", [$dyuser]);
                  $socketmsg = "Task checked and completed: " . $rem;
                  if ($socketmsg != '') $this->othersClass->socketmsg($config, $socketmsg, '', $username);
                }
              }
            }
            $config['params']['doc'] = 'DY';
            $this->logger->sbcmasterlog($trno, $config, ' Daily task has been done by ' . $creator);
          }
        }
      } else { //checker
        if ($checkerid != $requestorid && $requestorid != 0) { //galing sa task monitoring na iba yung checker 
          // $checkerid = 0; 
          //update dailytask ng checker
          $update_dailytask =  $this->coreFunctions->sbcupdate('dailytask', ['statid' => 1, 'donedate' => $datenow, 'rem1' => $solutionremarks], ['trno' => $checkertrno]);
          $label =  'Successfully done.';
          if ($update_dailytask) {
            $urlHistory = 'App\Http\Classes\modules\tableentry\\' . 'pdailytask';
            $dtinsert = $this->coreFunctions->execqry(app($urlHistory)->transferhistoryquery(), 'insert', [$checkertrno]);
            if ($dtinsert) {
              $this->coreFunctions->execqry("delete from dailytask where  trno=" . $checkertrno, 'delete'); //delete sa dailytask ng checker
              if ($checkerid != 0) {
                if ($checkerid == $adminid) {
                  if ($trno != 0) { //KAPAG GALING TASK MONITORING
                    $tmdetail = ['status' => 4, 'fcheckingdate' => $datenow, 'editdate' => $datenow, 'editby' => $config['params']['user']];
                    if ($this->coreFunctions->sbcupdate('tmdetail', $tmdetail, ['trno' => $trno, 'line' => $line])) {
                      //UPDATE SA PENDINGAPP NG FINAL CHECKER
                      $url = 'App\Http\Classes\modules\taskmonitoring\\' . 'tm';
                      $this->othersClass->insertUpdatePendingapp($trno, $line, 'TM', [], $url, $config, $requestorid, false, true, 'FOR CHECKING'); //create sa pendingapp

                      $config['params']['doc'] = 'ENTRYTASK';
                      $this->logger->sbcmasterlog2($trno, $config, ' Line: ' . $line . ' Task submitted for checking by ' . $creator, 'masterfile_log');
                    }
                  }
                }
              }
            }
            $config['params']['doc'] = 'DY';
            $this->logger->sbcmasterlog($trno, $config, ' Daily task has been done by ' . $creator);
          }
        }
      }

      $config['params']['trno'] = $dytrno;
      $txtdata = $this->loadcredit($config);
    }
    return ['status' => true, 'msg' => $label, 'data' => [], 'txtdata' => $txtdata];
  } //end function



  public function loadcredit($config)
  {
    $trno = isset($config['params']['dataparams']['tasktrno']) ? $config['params']['dataparams']['tasktrno'] : (isset($config['params']['row']['trno']) ? $config['params']['row']['trno'] : 0);
    $line = isset($config['params']['dataparams']['taskline']) ? $config['params']['dataparams']['taskline'] : (isset($config['params']['row']['line']) ? $config['params']['row']['line'] : 0);

    $tmuserid = isset($config['params']['dataparams']['tmuserid']) ? $config['params']['dataparams']['tmuserid'] : 0;
    $refx = isset($config['params']['dataparams']['refx']) ? $config['params']['dataparams']['refx'] : 0;

    if ($trno == 0 && $line == 0) {
      $dyuserid = $this->coreFunctions->datareader("select userid as value from hdailytask where trno=? ", [$refx]);
      $dyuserid = ($dyuserid != 0) ? $dyuserid : 0;
    }

    if ($tmuserid == 0) {
      $tmuserid = $dyuserid;
    }

    $qry = "select cd.totalpts as selectprefix,'' as category, cd.totalrt as totalreturn,cd.totalhrs as hours from creditdetail as cd 
              left join credithead as ch on ch.trno=cd.trno
              where ch.userid=" . $tmuserid . " and cd.dytrno=" . $refx . "";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }
} //end class
