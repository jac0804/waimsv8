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
use App\Http\Classes\modules\tableentry\entryhistoricalcomments;

class viewhistoricalcomments
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'View Historical Comments';
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
    $this->grid = new entryhistoricalcomments;
  }

  public function createTab($config)
  {

    $companyid = $config['params']['companyid'];

    $viewcost = $this->othersClass->checkAccess($config['params']['user'], 368);

    $rem = 0;


    $columns = [
      'rem'
    ];

    $tab = [
      $this->gridname => ['action' => 'tableentry', 'lookupclass' => 'entryhistoricalcomments', 'label' => 'LIST']
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
    if ($config['params']['moduletype'] == 'dashboard') {
      $doc = 'TM';
    } else {
      $doc = $config['params']['doc'];
    }
    $trno = $config['params']['row']['trno'];
    $line = $config['params']['row']['line'];
    switch ($doc) {
      case 'CV':
        $qry = "select '$trno' as cvtrno,'$line' as cvline,'' as createby,'' as createdate,'' as rem";
        break;
      case 'TM':
      case 'TK':
        $qry = "select '$trno' as tmtrno,'$line' as tmline,'' as createby,'' as createdate,'' as rem";
        break;
      case 'DY':
        $userid = $config['params']['row']['userid'];
        $touser = '';
        if ($userid != 0) $touser = $this->coreFunctions->getfieldvalue("client", "email", "clientid=?", [$userid]);
        $qry = "select '$trno' as dytrno,'' as createby,'' as createdate,'' as rem,'" . $touser . "' as touser," . $userid . " as userid";
        break;
      default:
        $qry = "select '$trno' as rrtrno,'$line' as rrline,'' as createby,'' as createdate,'' as rem";
        break;
    }
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  public function data($config)
  {
    return [];
  }

  public function loaddata($config)
  {
    $adminid = $config['params']['adminid'];
    $dstatus = 0;
    if ($config['params']['moduletype'] == 'dashboard') {
      $doc = 'TM';
      $dstatus = 1;
    } else {
      $doc = $config['params']['doc'];
    }
    $line = 0;
    $userid = 0;
    $touser = '';
    switch ($doc) {
      case 'RR':
        $trno = isset($config['params']['dataparams']['rrtrno']) ? $config['params']['dataparams']['rrtrno'] : 0;
        $line = isset($config['params']['dataparams']['rrline']) ? $config['params']['dataparams']['rrline'] : 0;
        break;
      case 'TM':
      case 'TK':
        $trno = isset($config['params']['dataparams']['tmtrno']) ? $config['params']['dataparams']['tmtrno'] : 0;
        $line = isset($config['params']['dataparams']['tmline']) ? $config['params']['dataparams']['tmline'] : 0;
        break;
      case 'DY':
        $trno = isset($config['params']['dataparams']['dytrno']) ? $config['params']['dataparams']['dytrno'] : 0;
        $touserid = isset($config['params']['dataparams']['userid']) ? $config['params']['dataparams']['userid'] : 0;
        $touser = isset($config['params']['dataparams']['touser']) ? $config['params']['dataparams']['touser'] : 0;
        break;
      default:
        $trno = isset($config['params']['dataparams']['cvtrno']) ? $config['params']['dataparams']['cvtrno'] : 0;
        $line = isset($config['params']['dataparams']['cvline']) ? $config['params']['dataparams']['cvline'] : 0;
        break;
    }
    // control how buttons behave
    // the data in return will showcase the values in the grid, made in createtab function
    // the txtdata in return will display the values in the head, made in createheadfield function
    switch ($config['params']['action2']) {
      case 'history':
        $data = $this->loadhistory($config, $trno, $line);
        switch ($doc) {
          case 'RR':
            $txtdata = $this->coreFunctions->opentable("select '' as rem, " . $trno . " as rrtrno, " . $line . " as rrline");
            break;
          case 'TM':
          case 'TK':
            $txtdata = $this->coreFunctions->opentable("select '' as rem, " . $trno . " as tmtrno, " . $line . " as tmline");
            break;
          case 'DY':
            $txtdata = $this->coreFunctions->opentable("select '' as rem, " . $trno . " as dytrno, '" . $touser . "' as touser");
            break;
          default:
            $txtdata = $this->coreFunctions->opentable("select '' as rem, " . $trno . " as cvtrno, " . $line . " as cvline");
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
        $useridd = $config['params']['dataparams']['userid'];

        if ($updatedrem != '') { //check comment if not empty
          $createby = $config['params']['user'];
          $date = $this->othersClass->getCurrentTimeStamp();

          switch ($doc) {
            case 'RR':
              $columns = ['rem' => $updatedrem, 'rrtrno' => $trno, 'rrline' => $line, 'createby' => $createby, 'createdate' => $date];
              $txtdata = $this->coreFunctions->opentable("select '' as rem, " . $trno . " as rrtrno, " . $line . " as rrline");
              break;
            case 'TM':
            case 'TK':
              $columns = ['rem' => $updatedrem, 'tmtrno' => $trno, 'tmline' => $line, 'createby' => $createby, 'createdate' => $date];
              $txtdata = $this->coreFunctions->opentable("select '' as rem, " . $trno . " as tmtrno, " . $line . " as tmline");
              break;
            case 'DY':
              $columns = ['rem' => $updatedrem, 'dytrno' => $trno, 'createby' => $createby, 'createdate' => $date, 'touser' => $touser];
              $txtdata = $this->coreFunctions->opentable("select '' as rem, " . $trno . " as dytrno");
              break;
            default:
              $columns = ['rem' => $updatedrem, 'cvtrno' => $trno, 'cvline' => $line, 'createby' => $createby, 'createdate' => $date];
              $txtdata = $this->coreFunctions->opentable("select '' as rem, " . $trno . " as cvtrno, " . $line . " as cvline");
              break;
          }

          $this->coreFunctions->sbcinsert("headprrem", $columns);


          if ($dstatus == 1) { //kapag nareturn dito papasok
            //pag katapos niya magcomment- nainsert na, kukunin ang id ng naka assign para bumalik sa kanya  
            $assignedid = $this->coreFunctions->getfieldvalue('tmdetail', "userid", "trno=? and line=?", [$trno, $line]);
            $stat = $this->coreFunctions->sbcupdate('tmdetail', ['fcheckingdate' => null, 'status' => 3], ['trno' => $trno, 'line' => $line]); //status is on going para i aaccept uli
            $data2 = [];
            $url = 'App\Http\Classes\modules\taskmonitoring\\' . 'tm';
            if ($assignedid != 0) {
              if ($stat) { //  ipapasok sa pending app- pag ini accept ito maseen yung comment 

                $task = $this->coreFunctions->getfieldvalue('tmdetail', "title", "trno=? and line=?", [$trno, $line]); //assigned
                $qry1 = "select line,rem from headprrem where tmtrno=$trno and tmline=$line  order by line desc limit 1";
                $lines2 = $this->coreFunctions->opentable($qry1);
                if (!empty($lines2)) {
                  $this->coreFunctions->sbcupdate('pendingapp', ['approver' => 'COMMENT', 'line' => $lines2[0]->line, 'clientid' => $assignedid], ['trno' => $trno, 'line' => $line]);
                  $config['params']['doc'] = 'ENTRYTASK';
                  $this->logger->sbcmasterlog($trno, $config, 'CREATE - Comment: ' . $lines2[0]->rem . ' for Task: ' . $task . ' on Line: ' . $line);
                }
              }
            }
            $config['params']['doc'] = 'ENTRYTASK';
            $task = $this->coreFunctions->getfieldvalue('tmdetail', "title", "trno=? and line=?", [$trno, $line]);
            $assignedid = $this->coreFunctions->getfieldvalue("tmdetail", "userid", "trno=? and line=?", [$trno, $line]);
            $assigned = $this->coreFunctions->getfieldvalue("client", "clientname", "clientid=?", [$assignedid]);

            $this->logger->sbcmasterlog($trno, $config, 'Return Task - Line: ' . $line . ' Task: ' . $task . ' to : ' . $assigned);
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

          if ($doc == 'DY') {
            if ($touser != '') {
              $data2 = [];
              $url = 'App\Http\Classes\modules\taskmonitoring\\' . 'dy';
              $qry1 = "select rem,line,createby from headprrem where dytrno=$trno order by line desc limit 1";
              $lines2 = $this->coreFunctions->opentable($qry1);
              if ($useridd != $adminid) {
                $this->othersClass->insertUpdatePendingapp($trno, $lines2[0]->line, 'DY', $data2, $url, $config, $touserid, false, true, 'COMMENT'); //insert sa pendingapp
              }
              $config['params']['doc'] = 'DY';
              $task = $this->coreFunctions->getfieldvalue('dailytask', "rem", "trno=?", [$trno]); //assigned
              $this->logger->sbcmasterlog($trno, $config, 'CREATE - Comment: ' . $lines2[0]->rem . ' for Task: ' . $task);

              $logusername = $this->coreFunctions->getfieldvalue("client", "clientname", "clientid=?", [$adminid], '', 0);
              $groupusers = $this->coreFunctions->opentable("select pr.createby, client.clientid from headprrem as pr left join client on client.email=pr.createby 
                                where dytrno=" . $trno . " and pr.createby not in ('" . $touser . "','" . $lines2[0]->createby . "') group by pr.createby, client.clientid");
              if (!empty($groupusers)) {
                foreach ($groupusers as $key_grp => $val_grp) {
                  $this->othersClass->insertUpdatePendingapp($trno, $lines2[0]->line, 'DY', $data2, $url, $config, $val_grp->clientid, false, true, 'COMMENT', $logusername); //insert sa pendingapp
                }
              }
            }
          }

          $config['params']['doc'] = $doc;
          $data = $this->grid->loaddata($config);
          switch ($doc) {
            case 'TM':
            case 'TK':
            case 'DY':
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
} //end class
