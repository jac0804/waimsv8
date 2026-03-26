<?php

namespace App\Http\Classes\modules\receivable;

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
use App\Http\Classes\sbcscript\sbcscript;

class rc
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'RECEIVED CHECKS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  private $sbcscript;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $tablenum = 'transnum';
  public $head = 'rchead';
  public $hhead = 'hrchead';
  public $detail = 'rcdetail';
  public $hdetail = 'hrcdetail';
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';
  private $stockselect;
  public $defaultContra = 'CR1';

  private $fields = [
    'trno', 'docno', 'dateid', 'client', 'clientname', 'address', 'dateid', 'yourref', 'ourref', 'phaseid', 'modelid', 'blklotid',
    'agent',  'rem', 'projectid'
  ];
  private $except = ['trno', 'dateid'];
  private $acctg = [];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  private $reporter;
  private $helpClass;

  public $showfilterlabel = [
    ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
    ['val' => 'locked', 'label' => 'Locked', 'color' => 'red'],
    ['val' => 'posted', 'label' => 'Posted', 'color' => 'orange'],
    ['val' => 'all', 'label' => 'All', 'color' => 'blue']
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
    $this->helpClass = new helpClass;
    $this->sbcscript = new sbcscript;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 3907,
      'edit' => 3908,
      'new' => 3909,
      'save' => 3910,
      'delete' => 3911,
      'print' => 3912,
      'lock' => 3913,
      'unlock' => 3914,
      'post' => 3915,
      'unpost' => 3916,
      'additem' => 3917,
      'edititem' => 3918,
      'deleteitem' => 3919
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $companyid = $config['params']['companyid'];

    $action = 0;
    $liststatus = 1;
    $listdocument = 2;
    $listdate = 3;
    $yourref = 4;
    $ourref = 5;
    $postdate = 6;

    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'yourref', 'ourref', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];

    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$yourref]['align'] = 'text-left';
    $cols[$ourref]['align'] = 'text-left';
    $cols[$postdate]['label'] = 'Post Date';

    $cols = $this->tabClass->delcollisting($cols);
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
    $stat = "'DRAFT'";

    $yourref = '';
    $ladb = '';
    $gldb = '';

    switch ($itemfilter) {
      case 'draft':
        $condition = ' and num.postdate is null and head.lockdate is null';
        break;
      case 'locked':
        $condition = ' and num.postdate is null and head.lockdate is not null ';
        $stat = "'LOCKED'";
        break;
      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
      default:
        $stat = ' (case when num.statid = 0 then "DRAFT" else stat.status end) ';
        break;
    }

    $dateid = "left(head.dateid,10) as dateid";
    $yourref = 'head.yourref';
    if ($searchfilter == "") $limit = 'limit 150';
    $orderby =  "order by  dateid desc, docno desc";
    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'head.clientname', 'head.createby', 'head.editby', 'head.viewby', 'num.postedby', 'head.yourref', 'head.ourref'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    } else {
      $limit = 'limit 25';
    }

    $qry = "select head.trno,head.docno,head.clientname,$dateid $ladb,  $stat as status,
                   head.createby,head.editby,head.viewby,num.postedby, date(num.postdate) as postdate,
                    " . $yourref . " as yourref, head.ourref
            from " . $this->head . " as head 
            left join " . $this->tablenum . " as num on num.trno=head.trno 
            left join " . $this->detail . " as detail on detail.trno = head.trno
            left join trxstatus as stat on stat.line=num.statid
            where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? 
                  and CONVERT(head.dateid,DATE)<=? " . $condition . " $filtersearch
            group by head.trno,head.docno,head.clientname,dateid,status,
                    head.createby,head.editby,head.viewby,num.postedby, date(num.postdate),
                    head.ourref, head.yourref,num.statid
            union all
            select head.trno,head.docno,head.clientname,$dateid $gldb,'POSTED' as status,
                   head.createby,head.editby,head.viewby, num.postedby, date(num.postdate) as postdate,
                   " . $yourref . " as yourref, head.ourref
            from " . $this->hhead . " as head 
            left join " . $this->tablenum . " as num on num.trno=head.trno 
            left join " . $this->hdetail . " as detail on detail.trno = head.trno
            where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? 
                  and CONVERT(head.dateid,DATE)<=? " . $condition . " $filtersearch
            group by head.trno,head.docno,head.clientname,dateid,status,
                    head.createby,head.editby,head.viewby,num.postedby, date(num.postdate),
                    head.ourref, head.yourref
            $orderby  $limit";

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
      'print',
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
    $step1 = $this->helpClass->getFields(['btnnew', 'customer', 'dateid', 'yourref', 'cur', 'csrem', 'btnsave']);
    $step2 = $this->helpClass->getFields(['btnedit', 'customer', 'dateid', 'yourref', 'cur', 'csrem', 'btnsave']);
    $step3 = $this->helpClass->getFields(['btnaddaccount', 'db', 'cr', 'rem', 'btnstocksaveaccount', 'btnsaveaccount']);
    $step4 = $this->helpClass->getFields(['db', 'cr', 'rem', 'btnstocksaveaccount', 'btnsaveaccount']);
    $step5 = $this->helpClass->getFields(['btnstockdeleteaccount', 'btndeleteallaccount']);
    $step6 = $this->helpClass->getFields(['btndelete']);


    $buttons['help']['items'] = [
      'create' => ['label' => 'How to create New Document', 'action' => $step1],
      'edit' => ['label' => 'How to edit details from the header', 'action' => $step2],
      'additem' => ['label' => 'How to add account/s', 'action' => $step3],
      'edititem' => ['label' => 'How to edit account details', 'action' => $step4],
      'deleteitem' => ['label' => 'How to delete account/s', 'action' => $step5],
      'deletehead' => ['label' => 'How to delete whole transaction', 'action' => $step6]
    ];
    $buttons['others']['items'] = [
      'first' => ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']],
      'prev' => ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']],
      'next' => ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']],
      'last' => ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']],
    ];

    if ($this->companysetup->getisshowmanual($config['params'])) {
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => 'cr', 'title' => 'CR_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
    }
    return $buttons;
  } // createHeadbutton

  public function createtab2($access, $config)
  {
    $companyid = $config['params']['companyid'];
    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrycntnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);

    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];
    if ($this->companysetup->getistodo($config['params'])) {
      $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrycntnumtodo', 'label' => 'To Do', 'access' => 'view']];
      $objtodo = $this->tabClass->createtab($tab, []);
      $return['To Do'] = ['icon' => 'fa fa-list', 'tab' => $objtodo];
    }

    return $return;
  }

  public function createTab($access, $config)
  {
    $columns = ['action', 'clientname', 'bank', 'branch', 'checkno',  'amount', 'checkdate', 'ref'];
    foreach ($columns as $key => $value) {
      $$value = $key;
    }

    $companyid = $config['params']['companyid'];
    $tab = [
      $this->gridname => [
        'gridcolumns' => $columns
      ]
    ];

    $stockbuttons = ['save', 'delete'];
    if ($this->companysetup->getiseditsortline($config['params'])) {
      array_push($stockbuttons, 'sortline');
    }

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$action]['style'] = 'width:80px;whiteSpace: normal;min-width:80px;max-width:80px;';
    $obj[0][$this->gridname]['columns'][$bank]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;max-width:200px;';
    $obj[0][$this->gridname]['columns'][$branch]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;max-width:300px;';
    $obj[0][$this->gridname]['columns'][$checkno]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px;';
    $obj[0][$this->gridname]['columns'][$amount]['style'] = 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px;';
    $obj[0][$this->gridname]['columns'][$checkdate]['style'] = 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px;';
    $obj[0][$this->gridname]['columns'][$ref]['style'] = 'width: 300px;whiteSpace: normal;min-width:300px;max-width:300px;';
    $obj[0][$this->gridname]['columns'][$clientname]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;max-width:300px;';

    $obj[0][$this->gridname]['columns'][$ref]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][$branch]['readonly'] = false;
    $obj[0][$this->gridname]['columns'][$ref]['label'] = 'Deposit Reference';
    if ($companyid != 59) { //not roosevelt
      $obj[0][$this->gridname]['columns'][$ref]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$branch]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'coldel';
    } else {
      $obj[0][$this->gridname]['columns'][$checkdate]['placeholder'] = 'MM-DD-YYYY';
      $obj[0][$this->gridname]['columns'][$checkdate]['format'] = 'MM-DD-YYYY';
      $obj[0][$this->gridname]['columns'][$bank]['type'] = 'editlookup';
      $obj[0][$this->gridname]['columns'][$bank]['action'] = 'lookupbank';
      $obj[0][$this->gridname]['columns'][$bank]['lookupclass'] = 'lookupbank';
      $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Customer';
      $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'lookup';
      $obj[0][$this->gridname]['columns'][$clientname]['field'] = 'clientname';
      $obj[0][$this->gridname]['columns'][$clientname]['lookupclass'] = 'beclient';
      $obj[0][$this->gridname]['columns'][$clientname]['action'] = 'lookupclient';
    }

    $obj[0][$this->gridname]['descriptionrow'] = [];
    $obj[0][$this->gridname]['label'] = 'ACCOUNTING';
    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    $obj[0][$this->gridname]['totalfield'] = 'amount';

    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['addrow', 'saveitem', 'deleteallitem'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[1]['label'] = "SAVE CHECKS";
    $obj[2]['label'] = "DELETE CHECKS";
    return $obj;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    switch ($companyid) {
      case 59: //roosevelt
        $fields = ['docno', 'dateid', 'dagentname'];
        $col1 = $this->fieldClass->create($fields);

        $fields = ['yourref', 'ourref'];
        $col2 = $this->fieldClass->create($fields);

        $fields = ['rem'];
        $col3 = $this->fieldClass->create($fields);
        break;
      default:
        $fields = ['docno', 'client', 'clientname', 'address'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'client.lookupclass', 'customer');
        data_set($col1, 'docno.label', 'Transaction#');

        switch ($systemtype) {
          case 'REALESTATE':
            $fields = ['dateid', ['yourref', 'ourref'], 'dprojectname', ['phase', 'housemodel'], ['blklot', 'lot']];
            break;
          default:
            switch ($companyid) {
              case 57:
              case 55: //financing //afli
                $fields = ['dateid', ['yourref', 'ourref']];
                break;
              default:
                $fields = ['dateid', ['yourref', 'ourref'], 'dprojectname'];
                break;
            }

            break;
        }

        $col2 = $this->fieldClass->create($fields);

        data_set($col2, 'dprojectname.type', 'lookup');
        data_set($col2, 'dprojectname.action', 'lookupproject');
        data_set($col2, 'dprojectname.lookupclass', 'fproject');
        data_set($col2, 'dprojectname.addedparams', ['client']);
        data_set($col2, 'phase.addedparams', ['projectid']);
        data_set($col2, 'housemodel.addedparams', ['projectid', 'phaseid']);
        data_set($col2, 'blklot.addedparams', ['projectid', 'phaseid', 'modelid', 'fpricesqm']);
        data_set($col2, 'lot.class', 'cslot sbccsreadonly');

        switch ($companyid) {
          case 57:
          case 55: //financing //afli
            $fields = ['rem'];
            break;
          default:
            $fields = ['dagentname', 'rem'];
            break;
        }



        if ($this->companysetup->getistodo($config['params'])) {
          array_push($fields, 'donetodo');
        }
        $col3 = $this->fieldClass->create($fields);
        break;
    }

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }



  public function createnewtransaction($docno, $params)
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['client'] = '';
    $data[0]['clientname'] = '';
    $data[0]['yourref'] = '';
    $data[0]['address'] = '';
    $data[0]['ourref'] = '';
    $data[0]['rem'] = '';
    $data[0]['projectid'] = '0';
    $data[0]['projectcode'] = '';
    $data[0]['projectname'] = '';
    $data[0]['agent'] = '';
    $data[0]['agentname'] = '';
    $data[0]['dagentname'] = '';
    $data[0]['modelid'] = 0;
    $data[0]['phaseid'] = 0;
    $data[0]['blklotid'] = 0;
    $data[0]['phase'] = '';
    $data[0]['housemodel'] = '';
    $data[0]['blklot'] = '';
    $data[0]['lot'] = '';
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

    if ($this->companysetup->getistodo($config['params'])) {
      $this->othersClass->checkseendate($config, $tablenum);
    }

    $head = [];
    $islocked = $this->othersClass->islocked($config);
    $isposted = $this->othersClass->isposted($config);
    $table = $this->head;
    $htable = $this->hhead;
    $qryselect = "select 
         num.center,
         head.trno, 
         head.docno,
         client.client,
         head.yourref,
         head.ourref,
         left(head.dateid,10) as dateid, 
         head.clientname,
         head.address, 
         date_format(head.createdate,'%Y-%m-%d') as createdate,
         head.rem,
         head.projectid,
         ifnull(project.name,'') as projectname,
         '' as dprojectname,
         client.groupid,ifnull(agent.client,'') as agent,ifnull(agent.clientname,'') as agentname,'' as dagentname,
         ifnull(project.code,'') as projectcode,head.phaseid, ph.code as phase,
         head.modelid, hm.model as housemodel, 
         head.blklotid, bl.blk as blklot, bl.lot";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as agent on agent.client = head.agent
        left join projectmasterfile as project on project.line=head.projectid 
        left join phase as ph on ph.line = head.phaseid
        left join housemodel as hm on hm.line = head.modelid
        left join blklot as bl on bl.line = head.blklotid   
        where head.trno = ? and num.doc=? and num.center = ? 
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as agent on agent.client = head.agent
        left join projectmasterfile as project on project.line=head.projectid    
        left join phase as ph on ph.line = head.phaseid
        left join housemodel as hm on hm.line = head.modelid
        left join blklot as bl on bl.line = head.blklotid        
        where head.trno = ? and num.doc=? and num.center=? ";
    $head = $this->coreFunctions->opentable($qry, [$trno, $doc, $center, $trno, $doc, $center]);
    if (!empty($head)) {
      $detail = $this->openstock($trno, $config);
      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }

      $hideobj = [];
      if ($this->companysetup->getistodo($config['params'])) {
        $btndonetodo = $this->othersClass->checkdonetodo($config, $tablenum);
        $hideobj = ['donetodo' => !$btndonetodo];
      }
      return  ['head' => $head, 'griddata' => ['inventory' => $detail], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg, 'hideobj' => $hideobj];
    } else {
      $head[0]['trno'] = 0;
      $head[0]['docno'] = '';
      return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => []], 'msg' => 'Data Head Fetched Failed, either somebody already deleted the transaction or modified...'];
    }
  }


  public function updatehead($config, $isupdate)
  {
    $head = $config['params']['head'];
    $companyid = $config['params']['companyid'];
    $data = [];
    if ($isupdate) {
      unset($this->fields[1]);
      unset($head['docno']);
    }
    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key], '', $companyid);
        } //end if    
      }
    }
    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    if ($isupdate) {
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head, $data);

      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['client'] . ' - ' . $head['clientname']);
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

    if ($this->othersClass->isposted($config)) {
      return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
    }
    //for glhead
    $qry = "insert into " . $this->hhead . "(trno,doc,docno,client,clientname,address,dateid,rem,yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,projectid,phaseid,modelid,blklotid,openby,users,viewby,viewdate,agent)
      SELECT trno,doc,docno,client,clientname,address,dateid,rem,yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,projectid,phaseid,modelid,blklotid,openby,users,viewby,viewdate,agent FROM " . $this->head . " as head 
      where head.trno=? limit 1";
    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($posthead) {
      // for glstock
      $qry = "insert into " . $this->hdetail . "(trno,line,checkdate,client,sortline,checkno,amount,ortrno,orline,rem,bank,branch,
      encodeddate,encodedby,editdate,editby)
        SELECT trno,line,checkdate,client,sortline,checkno,amount,ortrno,orline,rem,bank,branch,encodeddate,encodedby,editdate,editby FROM " . $this->detail . " where trno =?";
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        //update transnum
        $date = $this->othersClass->getCurrentTimeStamp();
        $data = ['postdate' => $date, 'postedby' => $config['params']['user']];
        $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
        $this->coreFunctions->execqry("delete from " . $this->detail . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
        $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
        $this->othersClass->sbctransferlog($trno, $config, $this->htablelogs);
        return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
      } else {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting Detail'];
      }
      //if($posthead){      
    } else {
      return ['status' => false, 'msg' => 'Error on Posting Head'];
    }
  } //end function

  public function unposttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $qry = "select trno from " . $this->hdetail . " where trno=? and (ortrno<>0 or rdtrno <> 0 or retrno <> 0)";
    $data = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($data)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Unposting failed; Check has Deposit Reference.'];
    }

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    $qry = "insert into " . $this->head . "(trno,doc,docno,client,clientname,address,dateid,rem,yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,projectid,phaseid,modelid,blklotid,openby,users,viewby,viewdate,agent)
    select trno,doc,docno,client,clientname,address,dateid,rem,yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,projectid,phaseid,modelid,blklotid,openby,users,viewby,viewdate,agent from " . $this->hhead . " as head 
    where head.trno=? limit 1";
    //head
    if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
      $qry = "insert into " . $this->detail . "(trno,line,checkdate,client,sortline,checkno,amount,ortrno,orline,rem,bank,branch,encodeddate,encodedby,editdate,editby)
      select trno,line,checkdate,client,sortline,checkno,amount,ortrno,orline,rem,bank,branch,encodeddate,encodedby,editdate,editby
      from " . $this->hdetail . " where trno=?";
      //stock
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null where trno=?", 'update', [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hdetail . " where trno=?", "delete", [$trno]);
        $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);


        return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
      } else {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, detail problems...'];
      }
    } else {
      return ['status' => false, 'msg' => 'Error on Unposting Head'];
    }
  } //end function


  private function getdetailselect($config)
  {
    // $qry = " head.trno,d.line,left(d.checkdate,10) as checkdate,d.sortline,d.checkno,
    //           format(d.amount,2) as amount,d.ortrno,d.orline,d.rem,d.client,client.clientname,'' as bgcolor,
    //           '' as errcolor,d.bank,d.branch";
    $qry = " head.trno,d.line,date_format(d.checkdate, '%m-%d-%Y') as checkdate,d.sortline,d.checkno,
              format(d.amount,2) as amount,d.ortrno,d.orline,d.rem,d.client,client.clientname,'' as bgcolor,
              '' as errcolor,d.bank,d.branch";
    return $qry;
  }


  public function openstock($trno, $config)
  {
    $companyid = $config['params']['companyid'];
    $sqlselect = $this->getdetailselect($config);

    $select_u = '';
    $select_p = '';

    if ($companyid == 59) { //roosevelt
      $select_u = ",'' as ref";
      $select_p = ",(select docno from rdhead as rd where rd.trno=d.rdtrno
               union all select docno from hrdhead as rd where rd.trno=d.rdtrno) as ref";
    }

    $qry = "select " . $sqlselect . " $select_u
    from " . $this->detail . " as d
    left join " . $this->head . " as head on head.trno=d.trno
    left join client on client.client = d.client
    where d.trno=?
    union all
    select " . $sqlselect . " $select_p
    from " . $this->hdetail . " as d
    left join " . $this->hhead . " as head on head.trno=d.trno
    left join client on client.client = d.client
    where d.trno=? order by sortline,line ";


    $detail = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $detail;
  }


  public function openstockline($config)
  {
    $sqlselect = $this->getdetailselect($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = "select " . $sqlselect . " 
    from " . $this->detail . " as d
    left join " . $this->head . " as head on head.trno=d.trno
    left join client on client.client = d.client
    where d.trno=? and d.line=?";
    $detail = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $detail;
  } // end function

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'adddetail':
        return $this->additem('insert', $config);
        break;
      case 'addallitem':
        return $this->addallitem($config);
        break;
      case 'deleteallitem':
        return $this->deleteallitem($config);
        break;
      case 'deleteitem':
        return $this->deleteitem($config);
        break;
      case 'saveitem': //save all detail edited
        return $this->updateitem($config);
        break;
      case 'saveperitem':
        return $this->updateperitem($config);
        break;
      case 'addrow':
        return $this->addrow($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function addrow($config)
  {
    $data = [];
    $trno = $config['params']['trno'];
    $data['line'] = 0;
    $data['trno'] = $trno;
    $data['checkno'] = '';
    $data['checkdate'] = null;
    $data['amount'] = 0;
    $data['bank'] = '';
    $data['branch'] = '';
    $data['clientname'] = '';
    $data['client'] = '';
    $data['ortrno'] = 0;
    $data['orline'] = 0;
    $data['rem'] = '';
    $data['client'] = $this->coreFunctions->getfieldvalue($this->head, "client", "trno=?", [$trno]);
    $data['bgcolor'] = 'bg-blue-2';
    return ['row' => $data, 'status' => true, 'msg' => 'New row added'];
  }

  public function diagram($config)
  {

    $data = [];
    $nodes = [];
    $links = [];
    $data['width'] = 1500;
    $startx = 100;

    //CR
    $crqry = "
    select  head.docno, date(head.dateid) as dateid, head.trno,
    CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem, detail.refx
    from glhead as head
    left join gldetail as detail on head.trno = detail.trno
    where head.trno = ?";
    $crdata = $this->coreFunctions->opentable($crqry, [$config['params']['trno']]);
    if (!empty($crdata)) {
      $startx = 550;
      $a = 0;
      foreach ($crdata as $key2 => $value2) {
        data_set(
          $nodes,
          $crdata[$key2]->docno,
          [
            'align' => 'left',
            'x' => $startx + 400,
            'y' => 100,
            'w' => 250,
            'h' => 80,
            'type' => $crdata[$key2]->docno,
            'label' => $crdata[$key2]->rem,
            'color' => 'red',
            'details' => [$crdata[$key2]->dateid]
          ]
        );
        array_push($links, ['from' => $crdata[$key2]->docno, 'to' => 'ar']);

        //AR
        $qry = "
        select  arhead.docno, date(arhead.dateid) as dateid,
        CAST(concat('Applied Amount: ', round(ardetail.db+ardetail.cr,2)) as CHAR) as rem
        from glhead as arhead
        left join gldetail as ardetail on arhead.trno = ardetail.trno
        left join gldetail as crdetail on crdetail.refx = ardetail.trno and crdetail.linex = ardetail.line
        left join glhead as crhead on crhead.trno = crdetail.trno
        where crhead.trno = ?";
        $t = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
        if (!empty($t)) {
          $starty = 100;
          foreach ($t as $key3 => $value3) {
            data_set(
              $nodes,
              $t[$key3]->docno,
              [
                'align' => 'left',
                'x' => $startx,
                'y' => $starty,
                'w' => 250,
                'h' => 80,
                'type' => $t[$key3]->docno,
                'label' => $t[$key3]->rem,
                'color' => 'green',
                'details' => [$t[$key3]->dateid]
              ]
            );
            $starty += 100;
            array_push($links, ['from' => $t[$key3]->docno, 'to' => $crdata[$key2]->docno]);

            //DS
            $qry = "
            select  head.docno, date(head.dateid) as dateid,
            CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
            from glhead as head
            left join gldetail as detail on head.trno = detail.trno
            where detail.refx = ?
            union all
            select  head.docno, date(head.dateid) as dateid,
            CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
            from lahead as head
            left join ladetail as detail on head.trno = detail.trno
            where detail.refx = ?";
            $dsdata = $this->coreFunctions->opentable($qry, [$config['params']['trno'], $config['params']['trno']]);
            if (!empty($dsdata)) {
              foreach ($dsdata as $key4 => $value4) {
                data_set(
                  $nodes,
                  $dsdata[$key4]->docno,
                  [
                    'align' => 'left',
                    'x' => $startx + 800,
                    'y' => 100,
                    'w' => 250,
                    'h' => 80,
                    'type' => $dsdata[$key4]->docno,
                    'label' => $dsdata[$key4]->rem,
                    'color' => 'orange',
                    'details' => [$dsdata[$key4]->dateid]
                  ]
                );
                array_push($links, ['from' => $dsdata[$key4]->docno, 'to' => $crdata[$key2]->docno]);
              }
            }
          }
        }
      }
    }



    $data['nodes'] = $nodes;
    $data['links'] = $links;

    return ['status' => true, 'msg' => 'Successfully fetched.', 'data' => $data];
  }

  public function stockstatusposted($config)
  {
    switch ($config['params']['action']) {
      case 'diagram':
        return $this->diagram($config);
        break;
      case 'navigation':
        return $this->othersClass->navigatedocno($config);
        break;
      case 'donetodo':
        $tablenum = $this->tablenum;
        return $this->othersClass->donetodo($config, $tablenum);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }


  public function updateperitem($config)
  {
    $config['params']['data'] = $config['params']['row'];
    if ($config['params']['line'] != 0) {
      if ($config['params']['data']['ortrno'] != 0) {
        $data = $this->openstockline($config);
        return ['row' => $data, 'status' => true, 'msg' => 'Already Used. Update Failed.'];
      } else {
        $this->additem('update', $config);
        $data = $this->openstockline($config);
        return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
      }
    } else {
      $condition_p = " and h.client ='" . $config['params']['data']['client'] . "'";
      $condition_u = " and d.client ='" . $config['params']['data']['client'] . "'";
      $msg = 'Check # already exist. ';
      if ($config['params']['companyid'] == 59) { //roosevelt
        $condition_p = " and d.bank = '" . $config['params']['data']['bank'] . "'
                         and d.branch = '" . $config['params']['data']['branch'] . "'";
        $condition_u = " and d.bank = '" . $config['params']['data']['bank'] . "'
                         and d.branch = '" . $config['params']['data']['branch'] . "'";
        $msg = 'Duplicate Bank, Branch and Check #.';
      }

      $exist = $this->coreFunctions->datareader("select checkno as value from 
      (select d.checkno from rcdetail as d 
      where d.trno= '" . $config['params']['data']['trno'] . "' and d.checkno = '" . $config['params']['data']['checkno'] . "' $condition_u
      union all 
      select d.checkno from hrcdetail as d left join hrchead as h on h.trno = d.trno 
      where d.trno= '" . $config['params']['data']['trno'] . "' and d.checkno = '" . $config['params']['data']['checkno'] . "' $condition_p) as a limit 1");

      if ($exist != '') {
        $stats['status'] = false;
        $stats['msg'] = $msg;
      } else {
        $stats = $this->additem('insert', $config);
      }
      $data = $this->openstockline($config);

      if ($stats['status'] == true) {
        return ['row' => $stats['row'], 'status' => true, 'msg' => 'Successfully saved.'];
      } else {
        return ['row' => $data, 'status' => false, 'msg' => $stats['msg']];
      }
    }
  }

  public function updateitem($config)
  {
    $msg1 = '';
    $msg2 = '';
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      if ($value['line'] != 0) {
        if ($config['params']['data']['ortrno'] != 0) {
        } else {
          $this->additem('update', $config);
        }
      } else {
        $condition_p = " and h.client ='" . $config['params']['data']['client'] . "'";
        $condition_u = " and d.client ='" . $config['params']['data']['client'] . "'";
        $msg = 'Check # already exists.';
        if ($config['params']['companyid'] == 59) { //roosevelt
          $condition_p = " and d.bank = '" . $config['params']['data']['bank'] . "'
                         and d.branch = '" . $config['params']['data']['branch'] . "'";
          $condition_u = " and d.bank = '" . $config['params']['data']['bank'] . "'
                         and d.branch = '" . $config['params']['data']['branch'] . "'";
          $msg = 'Duplicate Bank, Branch and Check #.';
        }

        $exist = $this->coreFunctions->datareader("select checkno as value 
                from (select d.checkno from rcdetail as d 
                      where d.trno= '" . $config['params']['data']['trno'] . "' and d.client ='" . $config['params']['data']['client'] . "' 
                            and d.checkno = '" . $config['params']['data']['checkno'] . "' $condition_u
                      union all 
                      select d.checkno from hrcdetail as d 
                      left join hrchead as h on h.trno = d.trno 
                      where d.trno= '" . $config['params']['data']['trno'] . "' and d.checkno = '" . $config['params']['data']['checkno'] . "' $condition_p) as a limit 1");

        if ($exist != '') {
          $msg1 = $msg;
        } else {
          $msg1 = 'Successfully saved.';
          $this->additem('insert', $config);
        }
      }
    }
    $data = $this->openstock($config['params']['trno'], $config);
    $data2 = json_decode(json_encode($data), true);
    $isupdate = true;

    foreach ($data2 as $key => $value) {
      if ($data2[$key]['checkno'] == "") {
        $data[$key]->errcolor = 'bg-red-2';
        $isupdate = false;
        $msg1 = 'Check # required. ';
      }

      if ($data2[$key]['ortrno'] != 0) {
        $data[$key]->errcolor = 'bg-red-2';
        $isupdate = false;
        $msg2 = ' Already used.';
      }
    }
    if ($isupdate) {
      return ['inventory' => $data, 'status' => true, 'msg' => $msg1];
    } else {
      return ['inventory' => $data, 'status' => true, 'msg' => 'Please check the following errors : ' . $msg1 . $msg2];
    }
  } //end function

  public function addallitem($config)
  {
    $error_msg = '';
    $status = true;
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $result = $this->additem('insert', $config);
      if (!$result['status']) {
        $error_msg .= ' ' . $result['msg'];
      }
    }
    if ($error_msg != '') {
      $msg = $error_msg;
      $status = false;
    } else {
      $msg = 'Successfully saved.';
    }
    $data = $this->openstock($config['params']['trno'], $config);
    return ['inventory' => $data, 'status' => $status, 'msg' => $msg];
  } //end function


  // insert and update detail
  public function additem($action, $config)
  {
    $companyid = $config['params']['companyid'];
    $trno = $config['params']['trno'];
    $checkno = $config['params']['data']['checkno'];
    $checkdate = $config['params']['data']['checkdate'];
    $amount = $config['params']['data']['amount'];
    $ortrno = $config['params']['data']['ortrno'];
    $orline = $config['params']['data']['orline'];
    $rem = $config['params']['data']['rem'];
    $client = $config['params']['data']['client'];
    $bank = $config['params']['data']['bank'];
    $branch = $config['params']['data']['branch'];

    $line = 0;
    if ($action == 'insert') {
      $qry = "select line as value from " . $this->detail . " where trno=? order by line desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$trno]);
      if ($line == '') {
        $line = 0;
      }
      $line = $line + 1;
      $config['params']['line'] = $line;
    } elseif ($action == 'update') {
      $config['params']['line'] = $config['params']['data']['line'];
      $line = $config['params']['data']['line'];
      $config['params']['line'] = $line;
    }

    $data = [
      'trno' => $trno,
      'line' => $line,
      'rem' => $rem,
      'checkno' => $checkno,
      'checkdate' => str_replace('-', '/', $checkdate),
      'amount' => $amount,
      'ortrno' => $ortrno,
      'orline' => $orline,
      'client' => $client,
      'bank' => $bank
    ];
    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $data['editdate'] = $current_timestamp;
    $data['editby'] = $config['params']['user'];
    $msg = '';
    $status = true;

    if ($companyid == 59) { //roosevelt
      $data['branch'] = $branch;
    }

    if ($action == 'insert') {
      $data['encodedby'] = $config['params']['user'];
      $data['encodeddate'] = $current_timestamp;
      $data['sortline'] =  $data['line'];
      if ($this->coreFunctions->sbcinsert($this->detail, $data) == 1) {
        $msg = 'Account was successfully added.';
        $this->logger->sbcwritelog($trno, $config, 'ACCTG', 'ADD - Line:' . $line . ' CHECKNO:' . $checkno . ' AMOUNT:' . $amount . ' CHECKDATE:' . $checkdate);
        $row = $this->openstockline($config);
        return ['row' => $row, 'status' => true, 'msg' => $msg];
      } else {
        return ['status' => false, 'msg' => 'Add Account Failed'];
      }
    } elseif ($action == 'update') {
      $return = true;
      if ($this->coreFunctions->sbcupdate($this->detail, $data, ['trno' => $trno, 'line' => $line]) == 1) {
      } else {
        $return = false;
      }
      return ['status' => $return, 'msg' => ''];
    }
  } // end function

  public function deleteallitem($config)
  {
    $isallow = true;
    $trno = $config['params']['trno'];
    $data = $this->openstock($trno, $config);
    $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);
    $this->logger->sbcwritelog($trno, $config, 'DETAIL', 'DELETED ALL CHECKS');
    return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
  }


  public function deleteitem($config)
  {
    $config['params']['trno'] = $config['params']['row']['trno'];
    $config['params']['line'] = $config['params']['row']['line'];
    $data = $this->openstockline($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];

    $qry = "delete from " . $this->detail . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
    $this->logger->sbcwritelog($trno, $config, 'DETAIL', 'REMOVED - Line: ' . $line . ' Check#: ' . $data[0]->checkno . ' Amount: ' . $data[0]->amount);
    return ['status' => true, 'msg' => 'Successfully deleted check.'];
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
    $this->logger->sbcviewreportlog($config);
    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  public function sbcscript($config)
  {
    if ($config['params']['companyid'] == 59) {//roosevelt
      return $this->sbcscript->rc($config);
    }else{
      return true;
    }
  }
} //end class
