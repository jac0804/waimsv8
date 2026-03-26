<?php

namespace App\Http\Classes\modules\taskmonitoring;

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
use App\Http\Classes\sbcscript\sbcscript;

class dy
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'DAILY TASK';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $head = 'dailytask';
  public $prefix = '';
  public $tablelogs = 'task_log';
  public $tablelogs_del = 'del_task_log';
  private $stockselect;
  private $sbcscript;



  private $fields = ['clientid', 'tasktrno', 'taskline', 'reftrno', 'rem', 'amt', 'userid', 'dateid', 'donedate', 'statid', 'apvtrno', 'jono', 'createdate', 'empid', 'reseller', 'isprev', 'rem1', 'taskcatid', 'complexity', 'assignedid'];
  private $except = [];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  private $reporter;

  public $showfilterlabel = [
    ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
    ['val' => 'complete', 'label' => 'Done', 'color' => 'primary'],
    ['val' => 'open', 'label' => 'Undone', 'color' => 'primary'],
    ['val' => 'neglect', 'label' => 'Neglect', 'color' => 'primary'],
    ['val' => 'cancelled', 'label' => 'Cancelled', 'color' => 'primary'],
    ['val' => 'all', 'label' => 'All', 'color' => 'primary']
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
    $this->sbcscript = new sbcscript;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 5562,
      'new' => 5560,
      'save' => 5558,
      'delete' => 5561,
      'print' => 5559,
      'edit' => 5563,

    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $getcols = ['action', 'listdate', 'statname', 'listcreateby', 'checkerdate', 'checker', 'listclientname', 'rem', 'rem1', 'jono', 'amt'];
    foreach ($getcols as $key => $value) {
      $$value = $key;
    }
    $stockbuttons = ['view', 'addcomments'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$listdate]['style'] = 'width:80px;whiteSpace: normal;min-width:80px;';
    $cols[$checkerdate]['style'] = 'width:80px;whiteSpace: normal;min-width:80px;';
    $cols[$listclientname]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;';
    $cols[$listcreateby]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
    // $cols[$amt]['style'] = 'width:80px;whiteSpace: normal;min-width:80px; text-align:right;';
    $cols[$amt]['style'] = 'text-align:left; width:80px;whiteSpace: normal;min-width:80px;';
    $cols[$listclientname]['label'] = 'Customer';
    $cols[$rem]['label'] = 'Remarks';
    $cols[$listdate]['label'] = 'Create Date';
    $cols[$checkerdate]['label'] = 'Done Date';
    $cols[$rem1]['label'] = 'Solution Remarks';
    $cols[$statname]['label'] = 'Status';
    $cols[$amt]['label'] = 'Amount';
    $cols[$rem]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$jono]['style'] = 'width:80px;whiteSpace: normal;min-width:80px;';
    $cols[$rem]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;';
    return $cols;
  }

  public function loaddoclisting($config)
  {
    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));
    $viewall = $this->othersClass->checkAccess($config['params']['user'], 5584);
    $limit = ' limit 500';
    $filtersearch = "";
    $searcfield = $this->fields;
    $search = '';
    $userid = $config['params']['adminid'];
    if (isset($config['params']['filter'])) {
      $search = $config['params']['filter'];
      foreach ($searcfield as $key => $sfield) {
        if ($filtersearch == "") {
          $filtersearch .= " and (" . $sfield . " like '%" . $search . "%'";
        } else {
          $filtersearch .= " or " . $sfield . " like '%" . $search . "%'";
        } //end if
      }
      $filtersearch .= ")";
    }

    if ($search != "") {
      $l = '';
    } else {
      $l = $limit;
    }

    $user = "and dt.userid = $userid";
    if ($viewall == '1') {
      $user = " ";
    }


    $option = $config['params']['itemfilter'];

    $filter = '';
    //  $stat=",'Draft' as statname";
    switch ($option) {
      case 'draft':
        $filter = " and dt.statid = 0 ";
        break;
      case 'open': //undone
        $filter = "  and dt.statid = 2 ";
        break;

      case 'complete': //done
        $filter = " and dt.statid in (1,6)";
        break;

      case 'cancelled': //done
        $filter = " and dt.statid = 5";
        break;

      case 'neglect': //done
        $filter = " and dt.statid = 4";
        break;

      default:
        $filter = "";
        break;
    }


    $qry = "select dt.trno,dt.trno as clientid,0 as line, 'DY' as doc,
             c.client,if(dt.reseller<>'',concat(c.clientname,'/ ',dt.reseller),c.clientname) as clientname,dt.createdate as dateid, dt.rem,dt.amt,dt.donedate as checkerdate,dt.jono,userr.clientname as createby,
             (case when dt.statid=1 then 'Completed' when dt.statid=2 then 'Undone' when dt.statid=4 then 'Neglect' when dt.statid=5 then 'Cancelled' when dt.statid=6 then 'Return' else 'On-going' end) as statname, 
             ck.clientname as checker, dt.isprev, dt.ischecker, dt.userid, dt.empid as checkerid, dt.rem1, dt.statid
             from dailytask as dt
             
            left join client as c on c.clientid = dt.clientid 
            left join client as userr on userr.clientid=dt.userid
            left join client as ck on ck.clientid=dt.empid
            where  CONVERT(dt.dateid,DATE)>=? and CONVERT(dt.dateid,DATE)<=? $filter $filtersearch $user
            union all
      
            select dt.trno,dt.trno as clientid,0 as line, 'DY' as doc,
            c.client,if(dt.reseller<>'',concat(c.clientname,'/ ',dt.reseller),c.clientname) as clientname,dt.createdate as dateid, dt.rem,dt.amt,dt.donedate as checkerdate,dt.jono,userr.clientname as createby,
            (case when dt.statid=1 then 'Completed' when dt.statid=2 then 'Undone' when dt.statid=4 then 'Neglect' when dt.statid=5 then 'Cancelled' when dt.statid=6 then 'Return' else 'On-going' end) as statname, 
            ck.clientname as checker, dt.isprev, dt.ischecker, dt.userid, dt.empid as checkerid, dt.rem1, dt.statid
            from hdailytask as dt
             
            left join client as c on c.clientid = dt.clientid 
            left join client as userr on userr.clientid=dt.userid
            left join client as ck on ck.clientid=dt.empid
            where  CONVERT(dt.dateid,DATE)>=? and CONVERT(dt.dateid,DATE)<=?  $filter $filtersearch $user
            order by dateid desc, trno desc " . $l;
    // var_dump($qry, [ $date1, $date2,$date1, $date2]);
    $data = $this->coreFunctions->opentable($qry, [$date1, $date2, $date1, $date2]);

    foreach ($data as $key => $value) {
      $addonstatus = '';
      if ($data[$key]->isprev) {
        $addonstatus .= 'Continuation';
      }
      if ($data[$key]->ischecker) {
        if ($data[$key]->statid == 0) {
          $data[$key]->statname = 'Checking';
        } else {
          $addonstatus .=  ($addonstatus == '' ? '' : '/') .  'Checker';
        }
      }

      if ($addonstatus != '') {
        $data[$key]->statname = $data[$key]->statname . ' (' . $addonstatus . ')';
      }

      if ($data[$key]->userid == $data[$key]->checkerid) {
        $data[$key]->checker = '';
      }
    }

    return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
  }

  public function createHeadbutton($config)
  {
    $btns = array(
      'load',
      'new',
      'save',
      // 'delete',
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
    $tab = [];
    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    return $obj;
  }

  public function createTab2($config)
  {
    $tab = ['tableentry' => ['action' => 'announcemententry', 'lookupclass' => 'addattachments', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);
    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];
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
    $allowbypass = $this->othersClass->checkAccess($config['params']['user'], 5601);
    $fields = ['client', 'clientname', 'reseller', 'rem', ['amt', 'jono']];
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'client.lookupclass', 'taskcustomerlookup');
    // data_set($col1, 'jono.label', 'JO#');
    data_set($col1, 'amt.label', 'Amount');
    data_set($col1, 'clientname.class', 'csclientname sbccsreadonly');
    data_set($col1, 'reseller.label', 'Reseller Customer');
    data_set($col1, 'rem.required', true);

    $fields = ['empname', 'rem1', 'category', 'complexity'];
    $col2 = $this->fieldClass->create($fields);

    data_set($col2, 'empname.type', 'lookup');
    data_set($col2, 'empname.lookupclass', 'employee');
    data_set($col2, 'empname.action', 'lookupclient');
    data_set($col2, 'empname.label', 'Request by/Checker');
    if ($allowbypass == 0) {
      data_set($col2, 'empname.required', true);
    }
    data_set($col2, 'rem1.label', 'Solution Remarks');
    data_set($col2, 'rem1.type', 'textarea');
    data_set($col2, 'category.lookupclass', 'lookuptaskcategory');
    data_set($col2, 'category.action', 'lookupreqcategory');
    data_set($col2, 'category.label', 'Task Category');
    data_set($col2, 'category.required', true);

    $fields = ['updatenotes'];
    $col3 = $this->fieldClass->create($fields);

    data_set($col3, 'updatenotes.label', 'ASSIGNED TO USER');
    data_set($col3, 'updatenotes.lookupclass', 'assignuser');


    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function newclient($config)
  {
    $data = $this->resetdata($config, $config['newclient']);
    return  ['head' => $data, 'islocked' => false, 'isposted' => false, 'status' => true, 'isnew' => true, 'msg' => 'Ready for New Ledger'];
  }

  private function resetdata($config, $client = '')
  {
    $companyid = $config['params']['companyid'];
    $adminid =  $config['params']['adminid'];

    $data = [];
    $data[0]['client'] = '';
    $data[0]['clientid'] = 0;
    $data[0]['trno'] = 0;
    $data[0]['dateid'] =  $this->othersClass->getCurrentDate();
    $data[0]['tasktrno'] = 0;
    $data[0]['taskline'] = 0;
    $data[0]['reftrno'] = 0;

    $data[0]['createdate'] = $this->othersClass->getCurrentTimeStamp();
    $data[0]['donedate'] = null;
    $data[0]['statid'] = 0;
    $data[0]['apvtrno'] = 0;

    $data[0]['userid'] = 0;
    if ($adminid != 0) {
      $data[0]['userid'] = $adminid;
    }

    $data[0]['clientname'] = '';
    $data[0]['reseller'] = '';
    $data[0]['rem'] = '';
    $data[0]['amt'] = 0;
    $data[0]['jono'] = '';
    $data[0]['custid'] = 0;

    $data[0]['empid'] = 0;
    $data[0]['isprev'] = 0;
    $data[0]['empname'] = '';
    $data[0]['taskcatid'] = 0;
    $data[0]['complexity'] = '';
    //3/24/2026 -rwen
    $data[0]['assignedid'] = 0;
    $data[0]['username'] = '';

    return $data;
  }


  public function loadheaddata($config)
  {
    $trno = $config['params']['clientid'];
    $qry = " select  dt.trno as clientid, dt.trno, cl.client,cl.clientname,
           dt.tasktrno,dt.taskline,dt.reftrno, dt.rem, dt.amt,
           dt.userid,date(dt.dateid) as dateid, date(dt.donedate) as donedate, dt.statid, dt.apvtrno,dt.jono,
           dt.clientid as custid,dt.empid,ck.clientname as empname,dt.reseller,dt.isprev,dt.rem1,req.category,
           dt.complexity,dt.assignedid, ifnull(assigned.clientname,'') as username,dt.taskcatid
           from dailytask as dt
           left join client as cl on cl.clientid=dt.clientid 
           left join client as ck on ck.clientid=dt.empid 
           left join client as assigned on assigned.clientid=dt.assignedid
           left join reqcategory as req on req.line=dt.taskcatid
           where dt.trno = ?
           union all
           select  dt.trno as clientid, dt.trno, cl.client,cl.clientname,
           dt.tasktrno,dt.taskline,dt.reftrno, dt.rem, dt.amt,
           dt.userid,date(dt.dateid) as dateid, date(dt.donedate) as donedate, dt.statid, dt.apvtrno,dt.jono,
           dt.clientid as custid,dt.empid,ck.clientname as empname,dt.reseller,dt.isprev,dt.rem1,req.category,
           dt.complexity,dt.assignedid, ifnull(assigned.clientname,'') as username,dt.taskcatid
           from hdailytask as dt
           left join client as cl on cl.clientid=dt.clientid 
           left join client as ck on ck.clientid=dt.empid 
           left join client as assigned on assigned.clientid=dt.assignedid
           left join reqcategory as req on req.line=dt.taskcatid
           where dt.trno = ?
           ";
    $head = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    if (!empty($head)) {
      $hideobj = [];
      $hideobj['updatenotes'] = false; //nakahide 
      $tasktrno = $head[0]->tasktrno;
      if ($tasktrno != 0) { // galing task monitoring
        $hideobj['updatenotes'] = true; //nakahide 
      }

      return  ['head' => $head, 'isnew' => false, 'status' => true, 'msg' => '', 'islocked' => false, 'isposted' => false, 'qq' => $trno, 'hideobj' => $hideobj];
    } else {
      $head = $this->resetdata($config);
      return ['status' => false, 'isnew' => true, 'head' => $head, 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified...'];
    }
  }

  public function updatehead($config, $isupdate)
  {
    $head = $config['params']['head'];

    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];
    $userid = $config['params']['adminid'];
    $data = [];
    $clientid = 0;
    $msg = '';
    $trno = 0;

    if ($isupdate) {
      $trno = $head['clientid']; // trno on dailytask
    }

    $head['clientid'] = $head['custid']; //clientid

    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        }
      }
    }

    if ($data['statid'] != 0) {
      CantUpdateHere:
      return ['status' => false, 'msg' => "Cannot update task that is already done/undone/for checking/cancelled.", 'trno' => $trno, 'clientid' => $trno];
    } else {
      if ($data['isprev']) goto CantUpdateHere;
    }

    if ($isupdate) {
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $trno]);
    } else {
      if ($head['clientid'] != 0) {
        $data['encodeddate'] = $this->othersClass->getCurrentTimeStamp();
        $trno = $this->coreFunctions->insertGetId($this->head, $data);
      } else {
        $msg == 'Please refresh the customer lookup list before adding a customer.';
      }

      //  if($userid != 0){
      //    $data3=[];
      //    $url = 'App\Http\Classes\modules\taskmonitoring\\' . 'dy';
      //    $this->othersClass->insertUpdatePendingapp($trno, 0, 'DY', $data3, $url, $config, $userid, false, true);

      // }
      $this->logger->sbcmasterlog($trno, $config, 'CREATE' . ' - ' . 'CUSTOMER : ' . 'ID:' . $head['clientid'] . ' ' . $head['clientname'] . ' ' . 'REMARKS : ' . $head['rem'] . ' AMOUNT : ' . $head['amt'] . ' JO# :' . $head['jono'], 0, 0, 0, 1);
      //($trno, $config, $task, $isedit = 0, $ismultigrid = 0, $trno2 = 0, $istemp = 0)
    }

    return ['status' => $msg == '' ? true : false, 'msg' => $msg, 'trno' => $trno, 'clientid' => $trno];
  } // end function



  public function getlastclient($pref)
  {
    return '';
  }

  public function openstock($clientid, $config)
  {
    return '';
  }

  public function deletetrans($config)
  {
    // $trno = $config['params']['clientid'];
    // $qryhere =  "select donedate from dailytask where trno = $trno";
    // $done = $this->coreFunctions->opentable($qryhere);
    // $istarted = false;
    // if (!empty($done)) {
    //   foreach ($done as $key => $value) {
    //     //check kung may donedate na hindi null
    //     if ($value->donedate != null) {
    //       $istarted = true;
    //     }
    //   }
    // }
    // if ($istarted) { //may laman startdate
    //   return ['status' => false, 'msg' => 'Cannot delete the task: task is already done or pending.'];
    // }
    // $this->coreFunctions->execqry("delete from dailytask where trno=" . $trno, 'delete');
    // // $this->coreFunctions->execqry("delete from pendingapp where doc='DY' and line=" . $trno, 'delete');

    // return ['clientid' => 0, 'status' => true, 'msg' => 'Successfully deleted.'];

    return '';
  } //end function


  public function stockstatusposted($config)
  {
    switch ($config['params']['action']) {
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }


  // -> print function
  public function reportsetup($config)
  {
    // $txtfield = $this->createreportfilter();
    // $txtdata = $this->reportparamsdata($config);

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
    $config['params']['trno'] = $config['params']['dataid'];
    $dataparams = $config['params']['dataparams'];

    // $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config['params']['dataid']);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
