<?php

namespace App\Http\Classes\modules\payroll;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\URL;

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
use App\Http\Classes\builder\helpClass;

class oi
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'OPERATOR INCENTIVE';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $tablenum = 'transnum';
  public $head = 'oihead';
  public $hhead = 'hoihead';
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';
  private $stockselect;
  public $defaultContra = 'AR1';

  private $fields = ['trno', 'docno', 'start', 'enddate'];
  // private $except = ['trno', 'dateid'];
  private $acctg = [];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  private $reporter;
  private $helpClass;


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
    $this->helpClass = new helpClass;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 4559,
      'edit' => 4560,
      'new' => 4561,
      'save' => 4562,
      'delete' => 4563,
      'print' => 4564,
      'lock' => 4565,
      'unlock' => 4566,
      'post' => 4567,
      'unpost' => 4568,
      'additem' => 4569,
      'edititem' => 4570,
      'deleteitem' => 4571
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $action = 0;
    $liststatus = 1;
    $listdocument = 2;
    $listdate = 3;
    $listclientname = 4;
    $yourref = 5;
    $ourref = 6;
    $postdate = 7;

    $getcols = ['action', 'liststatus', 'listdocument', 'listcreateby', 'listeditby', 'listviewby'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$listclientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $cols[$yourref]['align'] = 'text-left';
    $cols[$ourref]['align'] = 'text-left';
    $cols[$postdate]['label'] = 'Post Date';
    return $cols;
  }

  public function loaddoclisting($config)
  {
    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));
    $itemfilter = $config['params']['itemfilter'];
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $condition = '';
    $searchfilter = $config['params']['search'];
    $limit = '';

    switch ($itemfilter) {
      case 'draft':
        $condition = ' and num.postdate is null ';
        break;
      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
    }

    if ($searchfilter == "") $limit = 'limit 150';
    $orderby =  "order by docno desc";




    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'head.createby', 'head.editby', 'head.viewby'];

      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }


    $qry = "
      select head.trno,head.docno,head.start,head.enddate, 'DRAFT' as status,
      head.createby,head.editby,head.viewby   
      from " . $this->head . " as head left join " . $this->tablenum . " as num 
      on num.trno=head.trno where num.doc=? and num.center = ? and CONVERT(head.start,DATE)>=? and CONVERT(head.enddate,DATE)<=? " . $condition . " " . $filtersearch . "
      union all
      select head.trno,head.docno,head.start,head.enddate, 'POSTED' as status,
      head.createby,head.editby,head.viewby   
      from " . $this->hhead . " as head left join " . $this->tablenum . " as num 
      on num.trno=head.trno where num.doc=? and num.center = ? and CONVERT(head.start,DATE)>=? and CONVERT(head.enddate,DATE)<=? " . $condition . " " . $filtersearch . "
      $orderby $limit";


    $data = $this->coreFunctions->opentable($qry, [$doc, $center, $date1, $date2, $doc, $center, $date1, $date2]);
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
      'post',
      'unpost',
      'lock',
      'unlock',
      'logs',
      'edit',
      'backlisting',
      'toggleup',
      'toggledown',
      'help',
      'others'
    );

    $buttons = $this->btnClass->create($btns);
    $step1 = $this->helpClass->getFields(['btnnew', 'customer', 'dateid', 'yourref', 'csrem', 'btnsave']);
    $step2 = $this->helpClass->getFields(['btnedit', 'customer', 'dateid', 'yourref', 'csrem', 'btnsave']);
    $step3 = $this->helpClass->getFields(['btnunpaidkr', 'db', 'cr', 'rem']);
    $step4 = $this->helpClass->getFields(['db', 'cr', 'rem']);
    $step5 = $this->helpClass->getFields(['btnstockdeleteaccount', 'btndeleteallaccount']);
    $step6 = $this->helpClass->getFields(['btndelete']);


    $buttons['help']['items'] = [
      'create' => ['label' => 'How to create New Document', 'action' => $step1],
      'edit' => ['label' => 'How to edit details from the header', 'action' => $step2],
      'additem' => ['label' => 'How to add account/s', 'action' => $step3],

      'deleteitem' => ['label' => 'How to delete account/s', 'action' => $step5],
      'deletehead' => ['label' => 'How to delete whole transaction', 'action' => $step6]
    ];
    $buttons['others']['items'] = [
      'first' => ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']],
      'prev' => ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']],
      'next' => ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']],
      'last' => ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']],
    ];



    return $buttons;
  } // createHeadbutton

  public function createtab2($access, $config)
  {
    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrytransnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);

    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];
    return $return;
  }


  public function createTab($access, $config)
  {
    $companyid = $config['params']['companyid'];
    $columns = ['action', 'dateid', 'client', 'empname', 'jobtitle', 'amt', 'batch'];

    foreach ($columns as $key => $value) {
      $$value = $key;
    }
    $tab = [
      $this->gridname => [
        'gridcolumns' =>
        $columns,
        'sortcolumns' => $columns
      ]
    ];

    $stockbuttons = ['delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$dateid]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$client]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$empname]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$jobtitle]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$amt]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$client]['label'] = 'Code';
    $obj[0][$this->gridname]['columns'][$batch]['type'] = 'label';

    $obj[0][$this->gridname]['descriptionrow'] = [];
    $obj[0][$this->gridname]['totalfield'] = 'amt';
    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj =  $this->tabClass->createtabbutton($tbuttons);

    return  $obj;
  }


  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];
    $fields = ['docno'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'docno.label', 'Transaction#');
    $fields = ['start', 'enddate'];
    $col2 = $this->fieldClass->create($fields);

    $fields = ['loadtrip'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'loadtrip.style', 'width:50%');
    data_set($col3, 'loadtrip.action', 'refreshop');
    data_set($col3, 'loadtrip.label', 'REFRESH');
    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }



  public function createnewtransaction($docno, $params)
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['start'] = $this->othersClass->getCurrentDate();
    $data[0]['enddate'] = $this->othersClass->getCurrentDate();

    return $data;
  }

  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $trno = $config['params']['trno'];
    $center = $config['params']['center'];
    $tablenum = $this->tablenum;
    if ($trno == 0) {
      $trno = $this->othersClass->readprofile('TRNO', $config);
      if ($trno == '') {
        $trno = $this->coreFunctions->datareader("select trno as value from " . $this->tablenum . " where doc=? and center=? order by trno desc limit 1", [$doc, $center]);
      } else {
        $t = $this->coreFunctions->datareader("select trno as value from " . $this->tablenum . " where trno = ? and center=? order by trno desc limit 1", [$trno, $center]);
        if ($t == '') {
          $trno = 0;
        }
      }
      $config['params']['trno'] = $trno;
    } else {
      $this->othersClass->checkprofile('TRNO', $trno, $config);
    }
    $center = $config['params']['center'];

    $head = [];
    $islocked = $this->othersClass->islocked($config);
    $isposted = $this->othersClass->isposted($config);
    $table = $this->head;
    $htable = $this->hhead;
    $qryselect = "select 
         
         head.trno, 
         head.docno,
         head.start,
         head.enddate,
         head.createby,
         date_format(head.createdate,'%Y-%m-%d') as createdate,
         head.editby,
         date_format(head.editdate,'%Y-%m-%d') as editdate,
         head.viewby,
         date_format(head.viewdate,'%Y-%m-%d') as viewdate";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        
        where head.trno = ? and num.doc=? and num.center = ? 
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        
        where head.trno = ? and num.doc=? and num.center=? ";
    $head = $this->coreFunctions->opentable($qry, [$trno, $doc, $center, $trno, $doc, $center]);
    if (!empty($head)) {
      $data = $this->opendetail($config);

      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
      $msg = 'Data Head Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }
      $hideobj = [];
      if ($isposted) {
        $hideobj['loadtrip'] = true;
      } else {
        $hideobj['loadtrip'] = false;
      }
      // $this->coreFunctions->LogConsole('test lockunlock');
      if ($this->companysetup->getistodo($config['params'])) {
        $btndonetodo = $this->othersClass->checkdonetodo($config, $tablenum);
        $hideobj = ['donetodo' => !$btndonetodo];
      }
      return  ['head' => $head, 'griddata' => ['inventory' => $data], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg, 'hideobj' => $hideobj];
    } else {
      $head[0]['trno'] = 0;
      $head[0]['docno'] = '';

      $data = [];
      return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => $data], 'msg' => 'Data Head Fetched Failed, either somebody already deleted the transaction or modified...'];
    }
  }


  public function updatehead($config, $isupdate)
  {
    $head = $config['params']['head'];
    $data = [];
    if ($isupdate) {
      unset($this->fields[1]);
      unset($head['docno']);
    }

    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
      }
    }


    if ($isupdate) {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
    } else {

      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head, $data);
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['start'] . ' - ' . $head['enddate']);
    }
  } // end function


  public function deletetrans($config)
  {
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];
    $table = $config['docmodule']->tablenum;
    $docno = $this->coreFunctions->datareader("select docno as value from " . $table . ' where trno=?', [$trno]);
    $qry = "select trno as value from " . $this->tablenum . " where doc=? and trno<? order by trno desc limit 1 ";
    $trno2 = $this->coreFunctions->datareader($qry, [$doc, $trno]);
    $this->deleteallitem($config);
    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
    $this->othersClass->deleteattachments($config);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function


  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);
    $qry = "insert into " . $this->hhead . "(trno, docno, start, enddate, createby, createdate, editby, editdate, viewby, viewdate, lockuser, lockdate)
                    SELECT trno, docno, start, enddate, createby, createdate, editby, editdate, viewby, viewdate, lockuser, lockdate FROM " . $this->head . "
                    where trno=? limit 1";
    $this->coreFunctions->LogConsole($qry);
    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);

    if ($posthead) {
      $date = $this->othersClass->getCurrentTimeStamp();
      $data = ['postdate' => $date, 'postedby' => $user];
      $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
      $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
      $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
      $this->othersClass->sbctransferlog($trno, $config, $this->htablelogs);
      return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
    } else {
      $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
      return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting'];
    }
  } //end function

  public function unposttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);
    $qry = "insert into " . $this->head . "(trno, docno, start, enddate, createby, createdate, editby, editdate, viewby, viewdate, lockuser, lockdate)
                    SELECT trno, docno, start, enddate, createby, createdate, editby, editdate, viewby, viewdate, lockuser, lockdate FROM " . $this->hhead . "
                    where trno=? limit 1";
    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);

    if ($posthead) {
      $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null where trno=?", 'update', [$trno]);
      $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
      $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
      return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
    } else {
      $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
      return ['trno' => $trno, 'status' => false, 'msg' => 'Error on unposting'];
    }
  } //end function


  private function getstockselect($config, $refreshop = false)
  {

    $trno = $config['params']['trno'];
    $filter = 'and head.oitrno=' . $trno;
    $start = $this->coreFunctions->datareader("select date_format(head.start,'%Y-%m-%d') as value from " . $this->head . " as head
      where trno=$trno limit 1");
    $end = $this->coreFunctions->datareader("select date_format(head.enddate,'%Y-%m-%d') as value from " . $this->head . " as head
      where trno=$trno limit 1");

    if ($refreshop) {
      $filter = " and oitrno=0 and date(head.dateid) between '$start' and '$end' ";
    }

    $qry = " select head.trno as eqtrno,head.oitrno as trno,date(head.dateid) as dateid,
            c.client,c.clientname as empname,ifnull(j.jobtitle,'') as jobtitle,head.opincentive as amt, '' as bgcolor, b.batch
            from heqhead as head
            left join employee as emp on emp.empid=head.empid
            left join client as c on c.clientid=emp.empid
            left join jobthead as j on j.line=emp.jobid
            left join batch as b on b.line=head.batchid
            where 1=1 " . $filter;
    // $this->coreFunctions->LogConsole($qry);
    return $qry;
  }

  public function refreshop($config)
  {
    $trno = $config['params']['trno'];
    $qry = $this->getstockselect($config, true);
    $trip = $this->coreFunctions->opentable($qry);
    $islocked = $this->othersClass->islocked($config);
    if ($islocked) {
      return ['status' => false, 'msg' => 'Transaction is locked', 'griddata' => ['inventory' => $trip]];
    } else {
      foreach ($trip as $key => $data) {
        $this->coreFunctions->sbcupdate('heqhead', ['oitrno' => $trno], ['trno' =>  $data->eqtrno]);
      }
    }
    return ['status' => true, 'reloaddata' => true, 'msg' => 'Load Operator Incentives', 'griddata' => ['inventory' => $trip]];
  }

  public function opendetail($config)
  {
    $qry = $this->getstockselect($config, false);
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'deleteallitem':
        return $this->deleteallitem($config);
        break;
      case 'deleteitem':
        return $this->deleteitem($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }


  public function stockstatusposted($config)
  {
    switch ($config['params']['action']) {
      case 'refreshop':
        return $this->refreshop($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function deleteallitem($config)
  {
    $trno = $config['params']['trno'];
    $this->coreFunctions->execqry("update heqhead set oitrno =0  where oitrno=$trno", 'update');
    return ['status' => true, 'msg' => 'Successfully deleted.', 'griddata' => ['inventory' => []]];
  }

  public function deleteitem($config)
  {

    $trno = $config['params']['trno'];
    $this->coreFunctions->execqry("update heqhead set oitrno =0  where oitrno= $trno ", 'update');
    $this->othersClass->logConsole("update heqhead set oitrno =0  where oitrno=$trno ");
    $data = $this->opendetail($config);
    return  ['status' => true, 'griddata' => ['inventory' => $data],  'msg' => 'Incentive Deleted'];
  } // end function

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
    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
    $companyid = $config['params']['companyid'];
    $dataparams = $config['params']['dataparams'];

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
