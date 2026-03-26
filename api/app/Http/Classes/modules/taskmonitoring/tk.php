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

class tk
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'TASK MONITORING';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $head = 'tmdetail';
  public $prefix = '';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $stockselect;
  public $doclistdaterange = 12;
  private $sbcscript;

  private $fields = [
    'clientid',
    'systype',
    'tasktype',
    'rate',
    'dateid',
    'requestby',
    'rem'
  ];
  private $except = ['systype', 'tasktype', 'requestby'];
  public $showfilteroption = true;
  public $showfilter = false;
  public $showcreatebtn = false;
  private $reporter;
  public $showfilterlabel = [
    ['val' => 'open', 'label' => 'Open', 'color' => 'primary'],
    ['val' => 'draft', 'label' => 'Pending', 'color' => 'primary'],
    ['val' => 'posted', 'label' => 'For Checking', 'color' => 'primary'],
    ['val' => 'complete', 'label' => 'Completed', 'color' => 'primary'],
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
      'view' => 5474,
      'edit' => 5475,
      'save' => 5476
    );
    return $attrib;
  }

  public function sbcscript($config)
  {
    return $this->sbcscript->tk($config);
  }


  public function createdoclisting($config)
  {
    $viewcompletebtn = $this->othersClass->checkAccess($config['params']['user'], 5481);
    $edittask = $this->othersClass->checkAccess($config['params']['user'], 5462);

    $getcols = ['action', 'statname', 'listdate',  'listclientname', 'title', 'assignto', 'startdate', 'enddate', 'requestby'];
    $stockbuttons = ['viewtaskinfo', 'addattachments', 'addcomments', 'postomitem']; //,, 'pickerdrop' 

    foreach ($getcols as $key => $value) {
      $$value = $key;
    }

    // if ($viewcompletebtn == '1') {
    //   array_push($stockbuttons, 'pickerdropall');
    // }

    if ($edittask == '1') {
      array_push($stockbuttons, 'jumpmodule');
    }
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    if ($edittask == '1') {
      $cols[$action]['btns']['jumpmodule']['icon'] = 'folder_open';
    }
    $cols[$action]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $cols[$statname]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$listdate]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$listclientname]['label'] = 'Customer';
    $cols[$statname]['label'] = 'Status';
    $cols[$listdate]['label'] = 'Date Create';
    $cols[$startdate]['type'] = 'label';
    $cols[$title]['type'] = 'label';
    $cols[$enddate]['type'] = 'label';

    $cols[$action]['btns']['postomitem']['label'] = 'Mine';
    $cols[$action]['btns']['postomitem']['checkfield'] = "ismine";
    $cols[$action]['btns']['postomitem']['icon'] = 'check';
    $cols[$action]['btns']['postomitem']['confirm'] = true;
    $cols[$action]['btns']['postomitem']['confirmlabel'] = 'Are you sure you want to take this task?';


    // $cols[$action]['btns']['pickerdrop']['label'] = 'Start';
    // $cols[$action]['btns']['pickerdrop']['checkfield'] = "isassigned";
    // $cols[$action]['btns']['pickerdrop']['confirm'] = true;
    // $cols[$action]['btns']['pickerdrop']['confirmlabel'] = 'Are you sure you want to start this task?';

    $cols[$action]['btns']['viewtaskinfo']['action'] = "customformdialog";
    $cols[$action]['btns']['viewtaskinfo']['access'] = "view";

    $cols[$action]['btns']['addattachments']['access'] = "view";
    $cols[$action]['btns']['addattachments']['label'] = "View Attachment";
    $cols[$action]['btns']['addattachments']['icon'] = "visibility";

    // $cols[$action]['btns']['addcomments']['checkfield'] = "iscomment";



    // if ($viewcompletebtn == '1') {
    //   $cols[$action]['btns']['pickerdropall']['label'] = 'Complete';
    //   $cols[$action]['btns']['pickerdropall']['confirm'] = true;
    //   $cols[$action]['btns']['pickerdropall']['checkfield'] = "iscompleted";
    //   $cols[$action]['btns']['pickerdropall']['confirmlabel'] = 'Are you sure you want to complete this task?';
    // }
    return $cols;
  }

  public function loaddoclisting($config)
  {
    $userid = $config['params']['adminid'];
    $viewall = $this->othersClass->checkAccess($config['params']['user'], 5479);

    if ($viewall == '0') {
      if ($userid == 0) {
        return ['data' => [], 'status' => false, 'msg' => 'Sorry, you`re not allowed to view transaction. Please setup first your Employee Code.'];
      }
    }

    $limit = ' limit 500';
    $filtersearch = "";
    $searcfield = $this->fields;
    $search = '';

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

    $option = $config['params']['itemfilter'];
    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));
    $filterdate = " and date(h.dateid) between '" . $date1 . "' and '" . $date2 . "' ";
    $filter = '';
    // 0 -draft, 1-open , 2 -pending , 3 - ongoing , 4- for checking 5-complete
    $stat = ",(case h.status when 1 then (case d.status when 0 then 'Draft' when '1' then 'Open' when '2' then 'Pending' when '3' then 'On-going' when '4' then 'For Checking' else 'Completed' end) else 'Close' end) as statname";

    $user = " and d.userid=" . $userid . " ";

    if ($viewall == '1') {
      $user = " ";
    }

    switch ($option) {
      case 'open': //open
        // $filter = " and h.status = 1 and d.status = 1";
        //$filter = " and d.startdate is null and  d.userid = 0 and d.status=0 ";
        $filter = " and h.status = 1 and d.status = 1 and d.userid = 0";
        break;
      case 'posted': //for checking
        $filter = " $user  and  h.status = 1 and d.status = 4";
        //$filter = " $user  and d.startdate is not null and d.enddate is null and d.fcheckingdate is not null and d.status=4";
        //$stat=",'For Checking' as statname ";
        break;
      case 'complete': //OK N
        $filter = " $user  and  h.status = 1 and d.status = 5";
        //$filter = " $user  and d.startdate is not null and d.enddate is not null and d.status=5";
        //$stat=",'Completed' as statname ";
        break;
      case 'cancelled': //skip
        $filter = " $user  and d.startdate is not null and d.enddate is not null ";
        // $stat=",'Cancelled' as statname ";
        break;
      case 'draft': //assigned-posted
        $filter = " $user and h.status = 1 and  d.status in (1,2,3) ";
        // $filter = " $user  and h.status = 1 and ( d.status in (2,3)  or (d.status = 1 and d.userid <> 0) )";

        //$filter = " $user d.status in (2,3) and d.enddate is null  and d.acceptdate is not null and d.fcheckingdate is null";
        // $stat=", if(d.startdate is null and d.status=2,'Pending','On-going') as statname "; 
    }

    //if(d.userid = $userid and d.status = 3 and d.fcheckingdate is null ,'false','true') as isforchecking,
    $qry = "select h.trno as clientid,h.trno,date(h.dateid) as dateid, c.clientname,c.clientid as clid,
       d.title,ifnull(cla.clientname,'') as assignto,
       ifnull(e.clientname,'') as requestby,date(d.startdate) as startdate, date(d.enddate) as enddate,
       if(d.userid = 0,'false', if(d.userid = $userid and d.startdate is null and d.status not in (2,4),'false','true')) as isassigned,
       if(d.enddate is null and d.status = 4,'false','true') as iscompleted,h.requestby as reqid,d.status,h.amount,
       '../taskmonitoring/' as url,'ledgergrid' as moduletype, if(d.userid = 0 and d.startdate is null and d.status not in (2,4),'false','true') as ismine,

       'TM' as doc,d.line,d.userid as assignedid $stat
    from tmhead as h
    left join client as c on c.clientid = h.clientid
    left join client as e on e.clientid = h.requestby
    left join tmdetail as d on d.trno=h.trno
    left join trxstatus as stat on stat.line=d.status
    left join client as cla on cla.clientid = d.userid where ''='' $filter $filterdate $filtersearch order by d.encodeddate desc " . $l;
    //h.status=1

    // var_dump($qry);  if(d.status = 0 ,'true','false') as iscomment,
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
      'post',
      'unpost',
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
    $stockbuttons = ['viewhistoricalcomments'];
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
    $fields = ['lblchoice', 'tasktitle'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'lblchoice.label',  'Task');
    data_set($col1, 'tasktitle.label',  '');

    $fields = ['lblanswer', 'startdate'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'lblanswer.label',  'Start Date');
    data_set($col2, 'startdate.label',  '');

    $fields = ['lblbank', 'enddate'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'lblbank.label',  'End Date');
    data_set($col3, 'enddate.label',  '');
    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function newclient($config)
  {
    // $data = $this->resetdata($config, $config['newclient']);
    $data = [];
    return  ['head' => $data, 'islocked' => false, 'isposted' => false, 'status' => true, 'isnew' => true, 'msg' => 'Ready for New Ledger'];
  }

  private function resetdata($config, $client = '')
  {
    $companyid = $config['params']['companyid'];
    $data = [];
    $data[0]['client'] = '';
    $data[0]['clientid'] = 0;
    return $data;
  }


  public function loadheaddata($config)
  {
    $trno = isset($config['params']['row']) ? $config['params']['row']['trno'] : $config['params']['trno'];
    $line = isset($config['params']['row']) ? $config['params']['row']['line'] : $config['params']['line'];
    $qry = "select r.line as trno,r.line,concat(r.trno) as clientid,
        ifnull(user.clientname,'') as user,r.task,r.title as tasktitle,r.userid,r.startdate,r.enddate,r.percentage as percentsales
        from tmdetail as r
        left join client as user on user.clientid=r.userid
        left join client as c on c.clientid = r.userid where r.trno =? and line=?";
    $head = $this->coreFunctions->opentable($qry, [$trno, $line]);
    if (!empty($head)) {
      return  ['reloadtableentry' => true, 'head' => $head, 'isnew' => false, 'status' => true, 'msg' => '', 'islocked' => false, 'isposted' => false, 'qq' => $trno];
    } else {
      $head = $this->resetdata($config);
      return ['reloadtableentry' => true, 'status' => false, 'isnew' => true, 'head' => $head, 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified...'];
    }
  }

  public function updatehead($config, $isupdate)
  {
    $head = $config['params']['head'];

    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];
    $data = [];
    $clientid = 0;
    $msg = '';
    // $trno=0;

    if ($isupdate) {
      $trno = $head['trno']; // trno on tmhead
      $line = $head['line'];
    }


    foreach ($this->fields as $key) {
      // if (isset($head[$key]) || is_null($head[$key]))
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        } //end if
      }
    }


    // if ($isupdate) {
    $this->coreFunctions->logConsole($trno . 'trno update');
    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $trno, 'line' => $line]);


    return ['status' => $msg == '' ? true : false, 'msg' => $msg, 'trno' => $trno, 'line' => $line];
  } // end function


  public function getlastclient($pref)
  {
    return '';
  }

  public function stockstatusposted($config)
  {
    switch ($config['params']['action']) {
      case 'pickerdrop':
        return $this->starttask($config);
        break;
      case 'postomitem':
        return $this->mine($config); //mine
        break;
      case 'pickerdropall':
        return $this->completetask($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }


  public function mine($config)
  {
    $adminid = $config['params']['adminid']; //clientid
    $trno = $config['params']['row']['trno'];
    $line = $config['params']['row']['line'];
    $assigned = $config['params']['row']['assignto'];
    $requestorid = $config['params']['row']['reqid'];
    $task = $config['params']['row']['title'];
    // //update
    $userid = $this->coreFunctions->datareader("select userid as value from tmdetail where  trno=? and line=? ", [$trno, $line]);

    if ($userid != 0) {
      if ($userid != $adminid) {
        // $this->coreFunctions->execqry("delete from pendingapp where doc='TM' and trno=" . $trno . " and line=" . $line, 'delete');
        return ['status' => false, 'msg' => 'This task has already been taken by another user. Please reload the page.', 'action' => 'reloadlisting'];
      }
    }

    $mstatus = $this->coreFunctions->datareader("select status as value from tmdetail where  trno=? and line=? ", [$trno, $line]);
    if ($mstatus != 2) { //not pending
      $data = [];
      $data['userid'] = $adminid;
      $data['status'] = 2; //pending
      $updatetm = $this->coreFunctions->sbcupdate("tmdetail", $data, ['trno' => $trno, 'line' => $line]);
      if ($updatetm) {
        $url = 'App\Http\Classes\modules\taskmonitoring\\' . 'tm';
        $this->othersClass->insertUpdatePendingapp($trno, $line, 'TM', [], $url, $config, $adminid, false, true); //create sa pendingapp 
        $assigned = $this->coreFunctions->getfieldvalue("client", "clientname", "clientid=?", [$adminid]);
        $config['params']['doc'] = 'ENTRYTASK';
        $this->logger->sbcmasterlog($trno, $config, ' Line: ' . $line . ' , This task has been taken by ' . $assigned);
        //mag nonotif sa requestor pag kamine
        $username = $this->coreFunctions->getfieldvalue("client", "email", "clientid=?", [$requestorid]);
        $socketmsg = "Task accepted by " . $assigned . ":<br>" . $task;
        if ($socketmsg != '') $this->othersClass->socketmsg($config, $socketmsg, '', $username);
      }
    } else {
      return ['status' => false, 'msg' => 'This task has already been taken by another user. Please reload the page.', 'action' => 'reloadlisting'];
    }
    return ['status' => true, 'msg' => 'The task has been moved to pending.', 'action' => 'reloadlisting'];
  }


  public function starttask($config)
  {

    $adminid = $config['params']['adminid']; //clientid
    $trno = $config['params']['row']['trno'];
    $line = $config['params']['row']['line'];
    $statnow = $config['params']['row']['status'];
    $doc = $config['params']['row']['doc'];
    $assigned = $config['params']['row']['assignto'];
    $data = [];
    $data['userid'] = $adminid;
    $data['status'] = 2; //pending
    $updatetm = $this->coreFunctions->sbcupdate("tmdetail", $data, ['trno' => $trno, 'line' => $line]);
    if ($updatetm) {
      $qry = "insert into pendingapp (trno,line,doc,clientid)
                        SELECT $trno, $line, 'TM', " . $adminid . "
                        FROM pendingapp as p  where p.trno=? limit 1";
      $dtinsert = $this->coreFunctions->execqry($qry, 'insert', [$trno]);

      $config['params']['doc'] = 'ENTRYTASK';
      $this->logger->sbcmasterlog($trno, $config, ' Line: ' . $line . ' , Task has been taken from open status by: ' . $assigned);
    }
    return ['status' => true, 'msg' => 'The task has been moved to pending.', 'action' => 'reloadlisting'];
  }

  public function forchecking($config)
  {
    $adminid = $config['params']['adminid']; //clientid
    $trno = $config['params']['row']['trno'];
    $line = $config['params']['row']['line'];
    $statnow = $config['params']['row']['status'];
    $doc = $config['params']['row']['doc'];
    $assigned = $config['params']['row']['assignto'];
    $date = $this->othersClass->getCurrentTimeStamp();
    $data2 = [];

    $url = 'App\Http\Classes\modules\taskmonitoring\\' . 'tm';
    $data = [];
    $data['fcheckingdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['status'] = 4; //for checking na 
    //update
    $try = $this->coreFunctions->sbcupdate("tmdetail", $data, ['trno' => $trno, 'line' => $line]);
    if ($try) { //pag nag update
      $appname = [];
      $requestorid = $this->coreFunctions->getfieldvalue("tmhead", "requestby", "trno=?", [$trno]);
      $this->othersClass->insertUpdatePendingapp($trno, $line, 'TM', $data2, $url, $config, $requestorid, false, true); //create sa pendingapp
      $appname['approver'] = 'FOR CHECKING';
      $this->coreFunctions->sbcupdate('pendingapp', $appname, ['trno' => $trno, 'doc' => $doc, 'clientid' => $requestorid, 'line' => $line]);
      $assignedid = $this->coreFunctions->getfieldvalue("tmdetail", "userid", "trno=? and line=?", [$trno, $line]);
      $assigned = $this->coreFunctions->getfieldvalue("client", "clientname", "clientid=?", [$assignedid]);
      $config['params']['doc'] = 'ENTRYTASK';
      $this->logger->sbcmasterlog($trno, $config, ' Line: ' . $line . ' , Task submitted for checking by: ' . $assigned);
      $doc = 'TM';
    }
    return ['status' => true, 'msg' => 'The task has been successfully moved for checking.', 'action' => 'reloadlisting'];
  }



  public function completetask($config)
  {
    $trno = $config['params']['row']['trno'];
    $line = $config['params']['row']['line'];
    $doc = $config['params']['row']['doc'];
    $date = $this->othersClass->getCurrentTimeStamp();
    $requestorid = $this->coreFunctions->getfieldvalue("tmhead", "requestby", "trno=?", [$trno]);
    $requestorname = $this->coreFunctions->getfieldvalue("client", "clientname", "clientid=?", [$requestorid]);
    $stat = $this->coreFunctions->sbcupdate('tmdetail', ['enddate' => $date, 'status' => 5], ['trno' => $trno, 'line' => $line]);
    if ($stat) {
      $this->coreFunctions->execqry("delete from pendingapp where doc='TM' and trno=" . $trno . " and line=" . $line, 'delete');
    }
    $config['params']['doc'] = 'ENTRYTASK';
    $this->logger->sbcmasterlog($trno, $config, ' Line: ' . $line . ' , Task has been completed by: ' . $requestorname);
    $config['params']['doc'] = $doc;
    return ['status' => true, 'msg' => 'Successfully completed.', 'action' => 'reloadlisting'];
  }

  private function getstockselect($clientid, $config)
  {
    $sqlselect = "";
    return $sqlselect;
  }

  public function openstock($clientid, $config)
  {
    $sqlselect = $this->getstockselect($clientid, $config);
    return  $sqlselect;
  }

  public function deletetrans($config)
  {
    return ['clientid' => 0, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function


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
    // $data = $this->report_default_query($config['params']['dataid']);
    // $str = $this->reportplotting($config, $data);

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
