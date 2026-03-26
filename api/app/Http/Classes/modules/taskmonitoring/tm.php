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

class tm
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'TASK SETUP';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $head = 'tmhead';
  public $prefix = '';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $stockselect;
  private $sbcscript;



  private $fields = [
    'clientid',
    'systype',
    'tasktype',
    'rate',
    'dateid',
    'requestby',
    'rem',
    'amount',
    'checkerid'
  ];
  private $except = ['systype', 'tasktype', 'requestby'];
  public $showfilteroption = true;
  public $showfilter = false;
  public $showcreatebtn = true;
  private $reporter;

  public $showfilterlabel = [
    ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
    ['val' => 'open', 'label' => 'Open', 'color' => 'primary'],
    ['val' => 'complete', 'label' => 'Completed', 'color' => 'primary'],
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
      'view' => 5460,
      'new' => 5461,
      'edit' => 5462,
      'save' => 5463,
      'delete' => 5465,
      'print' => 5464

    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $getcols = ['action', 'statname', 'listdate',  'listclientname', 'requestby', 'rem'];
    foreach ($getcols as $key => $value) {
      $$value = $key;
    }
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$statname]['label'] = 'Status';
    $cols[$listdate]['style'] = 'width:80px;whiteSpace: normal;min-width:80px;';
    $cols[$listclientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';

    return $cols;
  }

  public function loaddoclisting($config)
  {
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
    //  $stat=",'Draft' as statname";
    switch ($option) {
      case 'draft':
        $filter = " and h.status = 0 ";
        break;
      case 'open': //open
        $filter = "  and h.status = 1 ";
        break;

      case 'complete':
        $filter = " and h.status = 2";
        // $stat=",'Completed' as statname ";
        break;
    }

    $qry = "select h.trno,h.trno as clientid,c.clientid as custid,
            c.client,c.clientname,left(h.dateid,10) as dateid,
            u.clientname as requestby,case h.status when 0 then 'Draft' when 1 then 'Open' else 'Completed' end as statname, h.rem
            from tmhead as h 
             
          left join client as c on c.clientid = h.clientid 
          left join client as u on u.clientid = h.requestby  
          where 1=1  $filter $filterdate $filtersearch order by h.dateid desc " . $l;
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

  public function createTab($access, $config)
  {
    $tab = ['tableentry' => ['action' => 'taskentry', 'lookupclass' => 'entrytask', 'label' => 'DETAILS']];
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
    $viewrate = $this->othersClass->checkAccess($config['params']['user'], 5480);

    $fields = ['client', 'clientname', 'systype', 'tasktype'];
    if ($viewrate != '0') {
      $fields = ['client', 'clientname', 'systype', 'tasktype', 'rate'];
    }

    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'client.lookupclass', 'taskcustomerlookup');
    data_set($col1, 'systype.lookupclass', 'lookupitemtm');
    data_set($col1, 'systype.action', 'lookupitem');
    data_set($col1, 'systype.class', 'sbccsreadonly');
    data_set($col1, 'systype.required', true);
    data_set($col1, 'tasktype.lookupclass', 'lookuptasktype');
    data_set($col1, 'tasktype.action', 'lookupreqcategory');
    data_set($col1, 'tasktype.readonly', true);
    data_set($col1, 'tasktype.required', true);

    $fields = ['dateid', 'empname', 'rem'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'dateid.readonly', true);
    // data_set($col2, 'requestby.action', 'lookupusers');
    // data_set($col2, 'requestby.lookupclass', 'lookupreqby');

    data_set($col2, 'empname.type', 'lookup');
    data_set($col2, 'empname.lookupclass', 'projectheadlookup');
    data_set($col2, 'empname.action', 'lookupclient');
    data_set($col2, 'empname.label', 'Project Head');
    data_set($col2, 'empname.required', true);


    $fields = ['amount', 'checker', 'forwtinput', 'forreceiving', 'lblpaid', 'lblsubmit'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'forwtinput.label', 'TAG AS OPEN');
    data_set($col3, 'lblpaid.label', 'OPEN');
    data_set($col3, 'forreceiving.label', 'CLOSE');
    data_set($col3, 'lblsubmit.label', 'CLOSED');
    data_set($col3, 'lblsubmit.style', 'font-weight:bold; font-size:30px;');

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function newclient($config)
  {
    $data = $this->resetdata($config, $config['newclient']);
    $hideobj = [];
    $hideobj['forwtinput'] = true; //naka hide
    $hideobj['forreceiving'] = true; //nakahide 
    $hideobj['lblpaid'] = true; //naka hide
    $hideobj['lblsubmit'] = true; //nakahide 

    return  ['head' => $data, 'islocked' => false, 'isposted' => false, 'status' => true, 'isnew' => true, 'msg' => 'Ready for New Ledger', 'hideobj' => $hideobj];
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
    $data[0]['systype'] = '';
    $data[0]['tasktype'] = '';
    $data[0]['sysid'] = 0;
    $data[0]['taskid'] = 0;
    $data[0]['custid'] = 0;

    $projecthead = $this->coreFunctions->getfieldvalue("client", "clientid", "client.clientid in (3863,3866,3867,3865,3868,3870) and clientid=?", [$adminid]);
    $projecthead = empty($projecthead) ? 0 : $projecthead;

    if ($adminid != 0) {
      if ($adminid != $projecthead) { //normal user
        $data[0]['empname'] = '';
        $data[0]['empid'] = 0;
      } else { //project head
        $data[0]['empid'] = $adminid;
        $data[0]['empname'] = $config['params']['user'];
      }
    }

    $data[0]['rate'] = 0;
    $data[0]['status'] = 0;
    $data[0]['clientname'] = '';
    $data[0]['rem'] = '';
    $data[0]['amount'] = 0;

    $data[0]['checker'] = '';
    $data[0]['checkerid'] = 0;

    return $data;
  }


  public function loadheaddata($config)
  {
    //$trno = isset($config['params']['row']) ? $config['params']['row']['trno'] : $config['params']['trno'];
    $trno = $config['params']['clientid'];
    $qry = "select h.trno as clientid,h.trno,c.client,c.clientname,h.dateid,ifnull(e.clientname,'') as empname,
    h.rem,i.itemid as sysid,i.itemname as systype,r.line as taskid,
    r.category as tasktype,e.clientid as empid,h.rem,h.clientid as custid,h.rate,h.status as status,h.amount,h.checkerid,ifnull(f.clientname,'') as checker
    from tmhead as h 
    left join client as c on c.clientid = h.clientid 
    left join client as e on e.clientid = h.requestby
    left join client as f on f.clientid = h.checkerid
    left join reqcategory as r on r.line = h.tasktype
    left join item as i on i.itemid = h.systype  where h.trno = ?";
    // var_dump($qry, [$trno]);
    $head = $this->coreFunctions->opentable($qry, [$trno]);


    if (!empty($head)) {
      $hideobj = [];
      $hideobj['forwtinput'] = false; //naka show
      $hideobj['forreceiving'] = true; //nakahide 
      $hideobj['lblpaid'] = true; //naka hide
      $hideobj['lblsubmit'] = true; //nakahide 

      $headstat = $head[0]->status;
      if ($headstat == 1) { //open
        $hideobj['forwtinput'] = true; //nakahide na 
        $hideobj['lblpaid'] = false; //label ng open nakashow
        $hideobj['forreceiving'] = false; // ididisplay kapag 1 na ang status
        $hideobj['lblsubmit'] = true; //nakahide label na CLOSED
      } else {
        if ($headstat == 2) { //close
          $hideobj['forwtinput'] = true; //nakahide na 
          $hideobj['lblpaid'] = true; //label ng open naka hide
          $hideobj['forreceiving'] = true; //close button , nakahide
          $hideobj['lblsubmit'] = false; //naka show
        }
      }

      // else{ //una kapag 0 pa lang ang status
      // $hideobj['forwtinput'] = false; //naka show
      // $hideobj['forreceiving'] = true;//nakahide 
      // $hideobj['lblpaid']=true; //naka hide
      // $hideobj['lblsubmit'] = true; //nakahide label na CLOSED
      // }

      //   $hideobj['forwtinput'] = false;
      //   $hideobj['forreceiving'] = true;//nakahide 
      //   $hideobj['lblpaid']=true;
      //  if($status1 == 1){ 
      //   $hideobj['forwtinput'] = true; //nakahide na 
      //   $hideobj['lblpaid']=false;
      //   $hideobj['forreceiving'] = false; // ididisplay kapag 1 na ang status
      //  }
      return  ['reloadtableentry' => true, 'head' => $head, 'isnew' => false, 'status' => true, 'msg' => '', 'islocked' => false, 'isposted' => false, 'qq' => $trno, 'hideobj' => $hideobj];
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
    $trno = 0;

    if ($isupdate) {
      $trno = $head['clientid']; // trno on tmhead
    }

    $head['clientid'] = $head['custid']; //clientid

    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          if ($key == 'rate') {
            $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key], '', 0, [], true);
          } else {
            $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
          }
        }
      }
    }
    $data['tasktype'] = $head['taskid'];
    $data['systype'] = $head['sysid'];
    $data['requestby'] = $head['empid'];


    if ($isupdate) {
      if($data['requestby'] !=$data['checkerid']){
        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];
        $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $trno]);
        return ['status' => true, 'msg' => $msg, 'trno' => $trno, 'clientid' => $trno, 'reloadtableentry' => true];
      }else{
        return ['status' => false, 'msg' => 'The project head cannot be the same as the checker.', 'trno' => $trno, 'clientid' => $trno, 'reloadtableentry' => true];
      }
    
    } else {
      if ($head['clientid'] != 0) {
        if ($head['empid'] != $head['checkerid']) {
          $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
          $data['createby'] = $config['params']['user'];
          $trno = $this->coreFunctions->insertGetId($this->head, $data);
          $this->logger->sbcmasterlog($trno, $config, 'CREATE' . ' - ' . $head['client'] . ' Task type: ' . $head['tasktype']);
          return ['status' => true, 'msg' => $msg, 'trno' => $trno, 'clientid' => $trno, 'reloadtableentry' => true];
        } else {
          return ['status' => false, 'msg' => 'The project head cannot be the same as the checker.', 'trno' => $trno, 'clientid' => $trno, 'reloadtableentry' => true];
        }
      } else {
        return ['status' => false, 'msg' => 'Please refresh the customer lookup list before adding a customer.', 'trno' => $trno, 'clientid' => $trno, 'reloadtableentry' => true];
      }
    }
    // return ['status' => $msg == '' ? true : false, 'msg' => $msg, 'trno' => $trno, 'clientid' => $trno, 'reloadtableentry' => true];
  } // end function

  // public function getlastclient()
  // {
  //   $last_id = $this->coreFunctions->datareader("select trno as value 
  //       from " . $this->head . " 
  //       order by trno DESC LIMIT 1");

  // //   return $last_id;
  // }

  public function getlastclient($pref)
  {
    return '';
    // return $last_id;
  }

  public function openstock($clientid, $config)
  {
    $qry = "select r.trno,r.line,ifnull(u.username,c.clientname) as user,r.task,r.title,r.userid,r.startdate,r.enddate,r.percentage,'' as bgcolor  from tmdetail as r left join useraccess as u on u.userid = r.userid
      left  join client as c on c.clientid = r.userid where r.trno =?   order by r.line";

    return $this->coreFunctions->opentable($qry, [$clientid]);
  }

  public function deletetrans($config)
  {
    $trno = $config['params']['clientid'];
    $qryhere =  "select startdate from tmdetail where trno = $trno";
    $accept = $this->coreFunctions->opentable($qryhere);
    $istarted = false;
    if (!empty($accept)) {
      foreach ($accept as $key => $value) {
        //check kung may startdate na hindi null
        if ($value->startdate != null) {
          $istarted = true;
        }
      }
    }
    if ($istarted) { //may laman startdate
      return ['status' => false, 'msg' => 'Cannot delete the task: some tasks have already been assigned or started.'];
    }
    $this->coreFunctions->execqry("delete from tmhead where trno=" . $trno, 'delete');
    $this->coreFunctions->execqry("delete from tmdetail where trno=" . $trno, 'delete');
    $this->coreFunctions->execqry("delete from pendingapp where doc='TM' and trno=" . $trno, 'delete');
    $this->coreFunctions->execqry("delete from headprrem where tmtrno=" . $trno, 'delete');
    $this->coreFunctions->execqry("delete from waims_attachments where trno=" . $trno, 'delete');
    return ['clientid' => 0, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function


  public function stockstatusposted($config)
  {
    switch ($config['params']['action']) {

      case 'forwtinput':
        return $this->forwtinput($config);
        break;

      case 'forreceiving':
        return $this->closetask($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function forwtinput($config)
  {
    $data2 = [];
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];
    $url = 'App\Http\Classes\modules\taskmonitoring\\' . 'tm';

    $qry = "select line,userid from tmdetail as d  where d.trno =$trno and userid<>0 and acceptdate is null";
    $assigned = $this->coreFunctions->opentable($qry);

    if (!empty($assigned)) {
      foreach ($assigned as $key => $value) {
        $this->othersClass->insertUpdatePendingapp($trno, $value->line, 'TM', $data2, $url, $config, $value->userid, false, true); //insert sa pendingapp
      }
    }

    if ($this->coreFunctions->sbcupdate('tmhead', ['status' => 1], ['trno' => $config['params']['trno']])) {
      $this->coreFunctions->sbcupdate('tmdetail', ['status' => 1], ['trno' => $config['params']['trno']]);
      $this->logger->sbcmasterlog($config['params']['trno'], $config, 'HEAD: Tagged as Open');


      return ['status' => true, 'msg' => 'Successfully tagged as open.', 'backlisting' => true];
    } else {
      return ['status' => false, 'msg' => 'Failed to post task'];
    }
  }

  public function closetask($config)
  {
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];
    $tasktype = $this->coreFunctions->getfieldvalue("tmhead", "tasktype", "trno=?", [$trno]);
    $isdailytask = $this->coreFunctions->getfieldvalue("reqcategory", "isdailytask", "line=?", [$tasktype]);
    //check kung 100 yung rate sa head
    $rate = $this->coreFunctions->datareader("select rate as value from tmhead where trno = ?", [$trno]);
    $amt = $this->coreFunctions->datareader("select amount as value from tmhead where trno = ?", [$trno], '', true);

    if ($rate == 100 || $isdailytask == 1) {
      $qryhere =  "select percentage,enddate from tmdetail where trno = $trno";
      $percent = $this->coreFunctions->opentable($qryhere);

      $totalpercent = 0;
      $hasnulldate = false;
      if (!empty($percent)) {
        foreach ($percent as $key => $value) {
          $totalpercent += $value->percentage;

          //check kung may enddate na null
          if (empty($value->enddate)) {
            $hasnulldate = true;
          }
        }
      }

      if ($hasnulldate) { //may walang enddate
        return ['status' => false, 'msg' => 'Cannot close task: some tasks have no end date'];
      }

      if ($isdailytask == 0) {
        if ($amt != 0) {
          if ($totalpercent != 100) { //hindi 100 yung percentage sa detail
            return ['status' => false, 'msg' => 'Cannot close task: detail total rate not 100'];
          }
        }
      }


      //update ng task dito then return pag 100 din yung percentage sa detail
      if ($this->coreFunctions->sbcupdate('tmhead', ['status' => 2], ['trno' => $config['params']['trno']])) { //close
        $this->logger->sbcmasterlog($config['params']['trno'], $config, 'HEAD: Tagged as Closed');

        return ['status' => true, 'msg' => 'Successfully tagged as closed.', 'backlisting' => true];
      }
    } else { //hindi 100 ang total rate sa head
      if ($amt != 0) {
        return ['status' => false, 'msg' => 'Cannot close task: head total rate not 100'];
      } else {
        if ($this->coreFunctions->sbcupdate('tmhead', ['status' => 2], ['trno' => $config['params']['trno']])) { //close
          $this->logger->sbcmasterlog($config['params']['trno'], $config, 'HEAD: Tagged as Closed');

          return ['status' => true, 'msg' => 'Successfully tagged as closed.', 'backlisting' => true];
        }
      }
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


  public function sbcscript($config)
  {
    $companyid = $config['params']['companyid'];
    switch ($companyid) {
      case 29: //sbc main
        return $this->sbcscript->taskmonitoring($config);
        break;
      default:
        return true;
        break;
    }
  }
} //end class
