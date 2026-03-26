<?php

namespace App\Http\Classes\modules\cbbsi;

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
use App\Http\Classes\SBCPDF;
use App\Http\Classes\builder\helpClass;
use Exception;

class dp
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'DISPATCH SCHEDULE'; // pending, dr/st tagging grouping on pick up, shipdate save per line, save all
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $tablenum = 'transnum';
  public $head = 'dphead';
  public $hhead = 'hdphead';
  public $stock = 'ckstock'; //edit out later
  public $hstock = 'hckstock'; //edit out later
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';
  private $stockselect;
  public $dqty = 'isqty';
  public $hqty = 'iss';
  public $damt = 'isamt';
  public $hamt = 'amt';
  public $fields = ['trno', 'docno', 'trnxtype', 'deldate', 'truckno', 'driver', 'dateid', 'rem'];
  public $except = ['trno', 'dateid'];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  private $reporter;
  private $helpClass;

  public $showfilterlabel = [
    ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
    ['val' => 'locked', 'label' => 'Locked', 'color' => 'primary'],
    ['val' => 'posted', 'label' => 'Posted', 'color' => 'primary'],
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
    $this->reporter = new SBCPDF;
    $this->helpClass = new helpClass;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 4371,
      'edit' => 4372,
      'new' => 4373,
      'save' => 4374,
      'delete' => 4375,
      'print' => 4376,
      'lock' => 4377,
      'unlock' => 4378,
      'changeamt' => 4379,
      'crlimit' => 4380,
      'post' => 4381,
      'unpost' => 4382,
      'additem' => 4383,
      'edititem' => 4384,
      'deleteitem' => 4385
    );
    return $attrib;
  }


  public function createdoclisting($config)
  {
    $getcols = ['action', 'liststatus', 'lblstatus', 'listdocument', 'listdate', 'listclientname', 'yourref', 'ourref', 'rem', 'ext', 'lockdate', 'listpostedby', 'postdate', 'listcreateby', 'createdate', 'listeditby', 'listviewby'];

    foreach ($getcols as $key => $value) {
      $$value = $key;
    }


    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$listclientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $cols[$yourref]['align'] = 'text-left';
    $cols[$ourref]['align'] = 'text-left';
    $cols[$liststatus]['name'] = 'statuscolor';

    $cols[$ext]['type'] = 'coldel';
    $cols[$rem]['type'] = 'coldel';

    $cols[$lblstatus]['type'] = 'coldel';
    $cols[$postdate]['type'] = 'coldel';
    $cols[$createdate]['type'] = 'coldel';
    $cols[$lockdate]['type'] = 'coldel';

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
    $laext = '';
    $glext = '';

    $join = '';
    $hjoin = '';
    $addparams = '';

    $lfield = '';
    $gfield = '';
    $ljoin = '';
    $gjoin = '';
    $group = '';

    $ustatus = "'Pending'";

    $orderby = "order by dateid desc, docno desc";

    $searchfilter = $config['params']['search'];

    $leftjoin = "";
    $leftjoin_posted = "";
    switch ($itemfilter) {
      case 'draft':
        $condition = ' and num.postdate is null';
        break;
      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
    }

    $limit = "limit 150";

    // replace multisearch
    if (isset($searchfilter)) {
      if ($searchfilter != '') {
        $searchfield = ['head.docno', 'num.postedby', 'head.rem'];

        if ($searchfilter != "") {
          $condition .= $this->othersClass->multisearch($searchfield, $searchfilter);
        }
      }
    }

    $qry = "select head.trno,head.docno,left(head.dateid,10) as dateid, head.rem,
    num.postedby,
    num.postdate,
    case ifnull(head.lockdate,'') when '' then  'DRAFT' else 'LOCKED' end as status,
    case ifnull(head.lockdate,'') when '' then 'red' else 'green' end as statuscolor
    from " . $this->head . " as head 
    left join " . $this->tablenum . " as num on num.trno=head.trno 
    $ljoin
    where head.doc=? and num.center=? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " 
    $group
    union all
    select  head.trno,head.docno,left(head.dateid,10) as dateid, head.rem,
    num.postedby,
    num.postdate,
    case ifnull(head.lockdate,'') when '' then  'DRAFT' else 'LOCKED' end as status,
    case ifnull(head.lockdate,'') when '' then 'red' else 'green' end as statuscolor
     
     from " . $this->hhead . " as head 
     left join " . $this->tablenum . " as num on num.trno=head.trno 
     $gjoin
     where head.doc=? and num.center=? and convert(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " 
     $group
    $orderby " . $limit;


    $data = $this->coreFunctions->opentable($qry, [$doc, $center, $date1, $date2, $doc, $center, $date1, $date2]);
    return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
  }

  public function paramsdatalisting($config)
  {
    $isshortcutdr = $this->companysetup->getisshortcutdr($config['params']);
    $fields = [];
    $col1 = $this->fieldClass->create($fields);

    $fields = [];
    $col2 = $this->fieldClass->create($fields);

    return ['status' => true, 'data' => [], 'txtfield' => ['col1' => $col1, 'col2' => $col2]];
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
    $step1 = $this->helpClass->getFields(['btnnew', 'customer', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
    $step2 = $this->helpClass->getFields(['btnedit', 'customer', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
    $step3 = $this->helpClass->getFields(['btnadditem', 'btnquickadd', 'isqty', 'uom', 'isamt', 'disc', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
    $step4 = $this->helpClass->getFields(['isqty', 'uom', 'isamt', 'disc', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
    $step5 = $this->helpClass->getFields(['btnstockdelete', 'btndeleteallitem']);
    $step6 = $this->helpClass->getFields(['btndelete']);


    $buttons['help']['items'] = [
      'create' => ['label' => 'How to create New Document', 'action' => $step1],
      'edit' => ['label' => 'How to edit details from the header', 'action' => $step2],
      'additem' => ['label' => 'How to add item/s', 'action' => $step3],
      'edititem' => ['label' => 'How to edit item details', 'action' => $step4],
      'deleteitem' => ['label' => 'How to delete item/s', 'action' => $step5],
      'deletehead' => ['label' => 'How to delete whole transaction', 'action' => $step6]
    ];

    $buttons['others']['items'] = [
      'first' => ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']],
      'prev' => ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']],
      'next' => ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']],
      'last' => ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']],
    ];

    if ($this->companysetup->getisshowmanual($config['params'])) {
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => 'so', 'title' => 'SO_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
    }

    return $buttons;
  } // createHeadbutton


  public function createtab2($access, $config)
  {
    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrytransnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);

    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];

    if ($this->companysetup->getistodo($config['params'])) {
      $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrytransnumtodo', 'label' => 'To Do', 'access' => 'view']];
      $objtodo = $this->tabClass->createtab($tab, []);
      $return['To Do'] = ['icon' => 'fa fa-list', 'tab' => $objtodo];
    }

    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];

    return $return;
  }

  public function createTab($access, $config)
  {
    $fields = ['creditinfo'];
    $col1 = $this->fieldClass->create($fields);
    $iscreateversion = $this->companysetup->getiscreateversion($config['params']);
    $so_btnvoid_access = $this->othersClass->checkAccess($config['params']['user'], 3593);
    $whinfo = $this->othersClass->checkAccess($config['params']['user'], 3889);
    $iskgs = $this->companysetup->getiskgs($config['params']);
    $changedisc = $this->othersClass->checkAccess($config['params']['user'], 4037);

    $action = 0;
    $invoiceno = 1;
    $docno = 2;
    $dateid = 3;
    $client = 4;
    $rem = 5;
    $ext = 6;
    $shipdate = 7;

    $column = ['action', 'invoiceno', 'docno', 'dateid', 'client', 'rem', 'ext', 'shipdate'];
    $sortcolumn = ['action', 'invoiceno', 'docno', 'dateid', 'client', 'rem', 'ext', 'shipdate'];

    $headgridbtns = ['itemvoiding', 'viewref', 'viewdiagram'];

    if ($so_btnvoid_access == 0) {
      unset($headgridbtns[0]);
    }
    $tab = [
      $this->gridname => [
        'gridcolumns' => $column, 'sortcolumns' => $sortcolumn,
        'headgridbtns' => $headgridbtns
      ]

    ];

    $stockbuttons = ['save',  'delete'];

    if ($this->companysetup->getiseditsortline($config['params'])) {
      array_push($stockbuttons, 'sortline');
    }


    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0]['inventory']['descriptionrow'] = [];
    $obj[0]['inventory']['columns'][$rem]['type'] = 'label';
    $obj[0]['inventory']['columns'][$rem]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $obj[0]['inventory']['columns'][$docno]['type'] = 'label';
    $obj[0]['inventory']['columns'][$dateid]['type'] = 'label';
    $obj[0]['inventory']['columns'][$invoiceno]['label'] = 'Invoice No.';
    $obj[0]['inventory']['columns'][$client]['label'] = 'Customer';
    $obj[0]['inventory']['columns'][$client]['type'] = 'label';

    $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['pendingdr', 'saveitem', 'deleteallitem'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['label'] = 'DR/ST';
    return $obj;
  }

  public function createHeadField($config)
  {
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    // col 1
    $fields = ['docno', 'trnxtype', 'deldate', 'truckno', 'driver'];

    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'docno.label', 'Transaction#');
    data_set($col1, 'driver.label', 'Driver');

    $fields = ['dateid', 'rem'];
    $col2 = $this->fieldClass->create($fields);

    $fields = [];
    $col3 = $this->fieldClass->create($fields);

    // col 4
    $fields = [];
    $col4 = $this->fieldClass->create($fields);

    return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4];
  }

  public function createnewtransaction($docno, $params)
  {

    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['deldate'] = $this->othersClass->getCurrentDate();
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['trnxtype'] = '';
    $data[0]['truckno'] = '';
    $data[0]['driver'] = '';
    $data[0]['rem'] = '';
    return $data;
  }

  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
    $tablenum = $this->tablenum;
    if ($trno == 0) {
      $trno = $this->othersClass->readprofile('TRNO', $config);
      if ($trno == '') {
        $trno = $this->coreFunctions->datareader("select trno as value from " . $this->tablenum . " where doc=? and center=? order by trno desc limit 1", [$doc, $center]);
      }
      $config['params']['trno'] = $trno;
    } else {
      $this->othersClass->checkprofile('TRNO', $trno, $config);
    }

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
         
         left(head.dateid,10) as dateid, 
         date_format(head.deldate,'%Y-%m-%d') as deldate,
         head.rem,
         head.trnxtype,head.truckno,head.driver
         ";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        
        where head.trno = ? and num.center = ? 
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        
        where head.trno = ? and num.center=? ";

    $head = $this->coreFunctions->opentable($qry, [$trno, $center, $trno, $center]);
    if (!empty($head)) {
      $stock = $this->openstock($trno, $config);
      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }

      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
      $hideobj = [];
      if ($this->companysetup->getistodo($config['params'])) {
        $btndonetodo = $this->othersClass->checkdonetodo($config, $tablenum);
        $hideobj = ['donetodo' => !$btndonetodo];
      }


      return  [
        'head' => $head, 'griddata' => ['inventory' => $stock], 'islocked' => $islocked,
        'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg, 'hideobj' => $hideobj
      ];
    } else {
      $head[0]['trno'] = 0;
      $head[0]['docno'] = '';
      return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => []], 'msg' => 'Data Head Fetched Failed'];
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
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
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
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno']);
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

    $this->coreFunctions->execqry('update cntnum set dptrno=0 where dptrno=?', 'update', [$trno]);
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
    $addfield = "";
    $addfieldfilter = "";
    $addsfield = "";

    $qry = "insert into " . $this->hhead . "(trno,doc,docno,dateid,trnxtype,deldate,truckno,driver,rem,createby,createdate,editdate,editby,viewby,viewdate,lockdate)
      SELECT head.trno,head.doc,head.docno,head.dateid,head.trnxtype,head.deldate,head.truckno,head.driver,head.rem,head.createby,head.createdate,
      head.editdate,head.editby,head.viewby,head.viewdate,head.lockdate
      FROM " . $this->head . " as head 
      where head.trno=? limit 1";
    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($posthead) {
      if ($posthead) {
        //update transnum
        $date = $this->othersClass->getCurrentTimeStamp();
        $data = ['postdate' => $date, 'postedby' => $config['params']['user'], 'statid' => 5];
        $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
        $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
        $this->othersClass->sbctransferlog($trno, $config, $this->htablelogs);
        return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
      } else {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting stock'];
      }
    } else {
      return ['status' => false, 'msg' => 'Error on Posting Head'];
    }
  } //end function

  public function unposttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    $qry = "insert into " . $this->head . "(trno,doc,docno,dateid,trnxtype,deldate,truckno,driver,rem,createby,createdate,editdate,editby,viewby,viewdate,lockdate)
    select head.trno,head.doc,head.docno,head.dateid,head.trnxtype,head.deldate,head.truckno,head.driver,head.rem,head.createby,head.createdate,
    head.editdate,head.editby,head.viewby,head.viewdate,head.lockdate
    from " . $this->hhead . " as head 
    where head.trno=? limit 1";
    //head
    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($posthead) {
      //stock
      if ($posthead) {
        $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null where trno=?", 'update', [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
        return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
      } else {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, stock problems...'];
      }
    }
  } //end function

  private function getstockselect($config)
  {
    $itemname = 'item.itemname,';

    $sqlselect = "select 
    head.docno,head.dateid,c.client,c.clientname,head.rem,
    FORMAT(sum(stock.ext)," . $this->companysetup->getdecimal('price', $config['params']) . ") as ext,left(info.shipdate,10) as shipdate,ifnull(num2.docno,'') as invoiceno,
    '' as bgcolor,head.trno as detailtrno,num.dptrno as trno,0 as line ";
    return $sqlselect;
  }

  public function openstock($trno, $config)
  {
    $leftjoin = '';
    $hleftjoin = '';

    $sqlselect = $this->getstockselect($config);

    $qry = $sqlselect . " 
    FROM lahead as head 
    left join lastock as stock on stock.trno=head.trno
    left join client as c on c.client=head.client
    left join cntnum as num on num.trno=head.trno
    left join cntnuminfo as info on info.trno=head.trno
    left join cntnum as num2 on num2.trno=num.svnum
    $leftjoin
    where num.dptrno = $trno
    group by head.docno,head.dateid,c.client,c.clientname,head.rem,
    info.shipdate,num2.docno,head.trno,num.dptrno
    UNION ALL  
    " . $sqlselect . "  
    FROM glhead as head
    left join glstock as stock on stock.trno=head.trno
    left join client as c on c.clientid=head.clientid
    left join cntnum as num on num.trno=head.trno
    left join hcntnuminfo as info on info.trno=head.trno

    left join cntnum as num2 on num2.trno=num.svnum
    $hleftjoin
    where num.dptrno = $trno
    group by head.docno,head.dateid,c.client,c.clientname,head.rem,
    info.shipdate,num2.docno,head.trno,num.dptrno";

    $stock = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $stock;
  } //end function

  public function openstockline($config)
  {
    $sqlselect = $this->getstockselect($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];

    $qry = $sqlselect . " 
    FROM lahead as head 
    left join lastock as stock on stock.trno=head.trno
    left join client as c on c.client=head.client
    left join cntnum as num on num.trno=head.trno
    left join cntnuminfo as info on info.trno=head.trno
    left join cntnum as num2 on num2.trno=num.svnum
    where num.dptrno = $trno and num.trno = $line
    group by head.docno,head.dateid,c.client,c.clientname,head.rem,
    info.shipdate,num2.docno,head.trno,num.dptrno
    UNION ALL  
    " . $sqlselect . "  
    FROM glhead as head
    left join glstock as stock on stock.trno=head.trno
    left join client as c on c.clientid=head.clientid
    left join cntnum as num on num.trno=head.trno
    left join hcntnuminfo as info on info.trno=head.trno
    left join cntnum as num2 on num2.trno=num.svnum
    where num.dptrno = $trno  and num.trno = $line
    group by head.docno,head.dateid,c.client,c.clientname,head.rem,
    info.shipdate,num2.docno,head.trno,num.dptrno";


    $stock = $this->coreFunctions->opentable($qry);
    return $stock;
  } // end function

  public function stockstatus($config)
  {
    $this->coreFunctions->LogConsole('stockstatus');
    switch ($config['params']['action']) {
      case 'createversion':
        $return = $this->posttrans($config);
        if ($return['status']) {
          return $this->othersClass->createversion($config);
        } else {
          return $return;
        }
        break;
      case 'additem':
        $return =  $this->additem('insert', $config);
        return $return;
        break;
      case 'addallitem': // save all item selected from lookup
        return $this->addallitem($config);
        break;
      case 'quickadd':
        return $this->quickadd($config);
        break;
      case 'deleteitem':
        return $this->deleteitem($config);
        break;
      case 'saveitem': //save all item edited
        $this->coreFunctions->LogConsole($this->othersClass->getCurrentTimeStamp() . ' stockstatus saveitem');
        return $this->updateitem($config);
        break;
      case 'saveperitem':
        $this->coreFunctions->LogConsole($this->othersClass->getCurrentTimeStamp() . ' stockstatus saveperitem');
        return $this->updateperitem($config);
        break;
      case 'deleteallitem':
        return $this->deleteallitem($config);
        break;
      case 'getdrdetails':
        return $this->getdrdetails($config);
        break;
      case 'getdrsummary':
        return $this->getdrsummary($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
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

  public function diagram($config)
  {

    $data = [];
    $nodes = [];
    $links = [];
    $data['width'] = 1500;
    $startx = 100;

    $qry = "select so.trno,so.docno,left(so.dateid,10) as dateid,
     CAST(concat('Total SO Amt: ',round(sum(s.ext),2)) as CHAR) as rem
     from hsohead as so 
     left join hsostock as s on s.trno = so.trno
     where so.trno = ? 
     group by so.trno,so.docno,so.dateid";
    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
    if (!empty($t)) {
      $startx = 550;
      $a = 0;
      foreach ($t as $key => $value) {
        //SO            
        data_set(
          $nodes,
          $t[$key]->docno,
          [
            'align' => 'right',
            'x' => 200,
            'y' => 50 + $a,
            'w' => 250,
            'h' => 80,
            'type' => $t[$key]->docno,
            'label' => $t[$key]->rem,
            'color' => 'blue',
            'details' => [$t[$key]->dateid]
          ]
        );
        array_push($links, ['from' => $t[$key]->docno, 'to' => 'sj']);
        $a = $a + 100;
      }
    }

    //SJ
    $qry = "
    select head.docno,
    date(head.dateid) as dateid,
    CAST(concat('Total SJ Amt: ', round(sum(stock.ext),2), ' - ', 'Balance: ', round(ar.bal, 2)) as CHAR) as rem, 
    head.trno
    from glhead as head
    left join glstock as stock on head.trno = stock.trno
    left join arledger as ar on ar.trno = head.trno
    where stock.refx=? and head.doc = 'SJ'
    group by head.docno, head.dateid, head.trno, ar.bal
    union all 
    select head.docno,
    date(head.dateid) as dateid,
    CAST(concat('Total SJ Amt: ', round(sum(stock.ext),2), ' - ', 'Balance: ', round(sum(stock.ext),2)) as CHAR) as rem, 
    head.trno
    from lahead as head
    left join lastock as stock on head.trno = stock.trno
    where stock.refx=? and head.doc = 'SJ'
    group by head.docno, head.dateid, head.trno";
    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno'], $config['params']['trno']]);
    if (!empty($t)) {
      data_set(
        $nodes,
        'sj',
        [
          'align' => 'left',
          'x' => $startx,
          'y' => 100,
          'w' => 250,
          'h' => 80,
          'type' => $t[0]->docno,
          'label' => $t[0]->rem,
          'color' => 'green',
          'details' => [$t[0]->dateid]
        ]
      );

      foreach ($t as $key => $value) {
        //CR
        $rrtrno = $t[$key]->trno;
        $apvqry = "
        select  head.docno, date(head.dateid) as dateid, head.trno,
        CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
        from glhead as head
        left join gldetail as detail on head.trno = detail.trno
        where detail.refx = ? and head.doc = 'CR'
        union all
        select  head.docno, date(head.dateid) as dateid, head.trno,
        CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
        from lahead as head
        left join ladetail as detail on head.trno = detail.trno
        where detail.refx = ? and head.doc = 'CR'";
        $apvdata = $this->coreFunctions->opentable($apvqry, [$rrtrno, $rrtrno]);
        if (!empty($apvdata)) {
          foreach ($apvdata as $key2 => $value2) {
            data_set(
              $nodes,
              'cr',
              [
                'align' => 'left',
                'x' => $startx + 400,
                'y' => 100,
                'w' => 250,
                'h' => 80,
                'type' => $apvdata[$key2]->docno,
                'label' => $apvdata[$key2]->rem,
                'color' => 'red',
                'details' => [$apvdata[$key2]->dateid]
              ]
            );
            array_push($links, ['from' => 'sj', 'to' => 'cr']);
            $a = $a + 100;
          }
        }

        //CM
        $dmqry = "
        select head.docno as docno,left(head.dateid,10) as dateid,
        CAST(concat('Total CM Amt: ', round(sum(stock.ext), 2)) as CHAR) as rem 
        from glhead as head
        left join glstock as stock on stock.trno=head.trno 
        left join item on item.itemid = stock.itemid
        where stock.refx=? and head.doc = 'CM'
        group by head.docno, head.dateid
        union all
        select head.docno as docno,left(head.dateid,10) as dateid,
        CAST(concat('Total CM Amt: ', round(sum(stock.ext), 2)) as CHAR) as rem 
        from lahead as head
        left join lastock as stock on stock.trno=head.trno 
        left join item on item.itemid=stock.itemid
        where stock.refx=? and head.doc = 'CM'
        group by head.docno, head.dateid";
        $dmdata = $this->coreFunctions->opentable($dmqry, [$rrtrno, $rrtrno]);
        if (!empty($dmdata)) {
          foreach ($dmdata as $key2 => $value2) {
            data_set(
              $nodes,
              $dmdata[$key2]->docno,
              [
                'align' => 'left',
                'x' => $startx + 400,
                'y' => 200,
                'w' => 250,
                'h' => 80,
                'type' => $dmdata[$key2]->docno,
                'label' => $dmdata[$key2]->rem,
                'color' => 'red',
                'details' => [$dmdata[$key2]->dateid]
              ]
            );
            array_push($links, ['from' => 'sj', 'to' => $dmdata[$key2]->docno]);
            $a = $a + 100;
          }
        }
      }
    }

    $data['nodes'] = $nodes;
    $data['links'] = $links;

    return ['status' => true, 'msg' => 'Successfully fetched.', 'data' => $data];
  }

  public function updateperitem($config)
  {
    $detailtrno = $config['params']['row']['detailtrno'];
    $dptrno = $config['params']['trno'];
    $shipdate = $config['params']['row']['shipdate'];
    $info = '';
    $isposted = $this->othersClass->isposted2($detailtrno, "cntnum");
    if ($isposted) {
      $exist = $this->coreFunctions->getfieldvalue("hcntnuminfo", "trno", "trno=?", [$detailtrno]);
      $info = 'hcntnuminfo';
    } else {
      $exist = $this->coreFunctions->getfieldvalue("cntnuminfo", "trno", "trno=?", [$detailtrno]);
      $info = 'cntnuminfo';
    }
    if (floatval($exist) != 0) {
      $this->coreFunctions->execqry("update " . $info . " set shipdate='" . $shipdate . "' where trno='" . $detailtrno . "'", 'update');
      $config['params']['line'] = $detailtrno;
    } else {
      $data['trno'] = $detailtrno;
      $data['shipdate'] = $shipdate;
      $this->coreFunctions->sbcinsert($info, $data);
      $this->logger->sbcwritelog($dptrno, $config, 'STOCK', 'ADD SHIPDATE:' . $config['params']['row']['docno'] . "-" . $shipdate);
      $config['params']['line'] = $detailtrno;
    }

    $data = $this->openstockline($config);
    return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
  }


  public function updateitem($config)
  {
    //save all
    $dptrno = $config['params']['trno'];
    $info = '';
    foreach ($config['params']['row'] as $key => $value) {
      $isposted = $this->othersClass->isposted2($value['detailtrno'], "cntnum");
      if ($isposted) {
        $exist = $this->coreFunctions->getfieldvalue("hcntnuminfo", "trno", "trno=?", [$value['detailtrno']]);
        $info = 'hcntnuminfo';
      } else {
        $exist = $this->coreFunctions->getfieldvalue("cntnuminfo", "trno", "trno=?", [$value['detailtrno']]);
        $info = 'cntnuminfo';
      }

      if (floatval($exist) != 0) {
        $this->coreFunctions->execqry("update " . $info . " set shipdate='" . $value['shipdate'] . "' where trno='" . $value['detailtrno'] . "'", 'update');
      } else {
        $data['trno'] = $value['detailtrno'];
        $data['shipdate'] = $value['shipdate'];
        $this->coreFunctions->sbcinsert($info, $data);
        $this->logger->sbcwritelog($dptrno, $config, 'STOCK', 'ADD SHIPDATE:' . $value['docno'] . "-" . $value['shipdate']);
      }
    }
    $data = $this->openstock($config['params']['trno'], $config);
    return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
  } //end function

  public function addallitem($config)
  {
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $this->additem('insert', $config);
    }


    $data = $this->openstock($config['params']['trno'], $config);
    return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
  } //end function

  public function quickadd($config)
  {
  }

  // insert and update item
  public function additem($action, $config, $setlog = false)
  {
  } // end function



  public function deleteallitem($config)
  {
    $isallow = true;
    $dptrno = $config['params']['trno'];
    $data = $this->openstock($dptrno, $config);
    foreach ($data as $key => $value) {
      $trno = $data[$key]->detailtrno;
      $this->coreFunctions->sbcupdate('cntnuminfo', ['shipdate' => null], ['trno' => $trno]);
      $this->coreFunctions->sbcupdate('hcntnuminfo', ['shipdate' => null], ['trno' => $trno]);
    }
    $this->coreFunctions->sbcupdate('cntnum', ['dptrno' => 0], ['dptrno' => $dptrno]);
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL DR/ST');
    return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
  }


  public function deleteitem($config)
  {
    $dptrno = $config['params']['row']['trno'];
    $trno = $config['params']['row']['detailtrno'];
    $config['params']['line'] = $trno;
    $data = $this->openstockline($config);

    $this->coreFunctions->sbcupdate('cntnuminfo', ['shipdate' => null], ['trno' => $trno]);
    $this->coreFunctions->sbcupdate('hcntnuminfo', ['shipdate' => null], ['trno' => $trno]);
    $this->coreFunctions->sbcupdate('cntnum', ['dptrno' => 0], ['trno' => $trno]);

    $this->logger->sbcwritelog($dptrno, $config, 'STOCK', 'REMOVED - DR/ST:' . $data[0]->docno);
    return ['status' => true, 'msg' => 'DR/ST successfully deleted.'];
  } // end function

  public function getlatestprice($config)
  {
    $barcode = $config['params']['barcode'];
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];

    $pricetype = $this->companysetup->getpricetype($config['params']);
    $pricegrp = '';
    $data = [];

    switch ($pricetype) {
      case 'Stockcard':
        goto itempricehere;
        break;

      case 'CustomerGroup':
      case 'CustomerGroupLatest':
        $pricegrp = $this->coreFunctions->getfieldvalue("client", "class", "client=?", [$client]);
        if ($pricegrp != '') {
          $pricefield = $this->othersClass->getamtfieldbygrp($pricegrp);
          $this->coreFunctions->LogConsole($pricefield);
          $data = $this->coreFunctions->opentable("select '" . $pricefield['label'] . "' as docno, " . $pricefield['amt'] . " as amt," . $pricefield['amt'] . " as defamt, " . $pricefield['disc'] . " as disc, uom from item where barcode=?", [$barcode]);
          if (!empty($data)) {
            goto setpricehere;
          }
        } else {
          if ($pricetype == 'CustomerGroupLatest') {
            goto getCustomerLatestPriceHere;
          } else {
            goto itempricehere;
          }
        }
        break;

      default:
        getCustomerLatestPriceHere:

        $qry = "select docno,left(dateid,10) as dateid,round(amt,2) as amt,round(amt,2) as defamt,disc,uom from(select head.docno,head.dateid,
            stock.isamt as amt,stock.uom,stock.disc
            from lahead as head
            left join lastock as stock on stock.trno = head.trno
            left join cntnum on cntnum.trno=head.trno
            left join item on item.itemid = stock.itemid
            where head.doc = 'SJ' and cntnum.center = ?
            and item.barcode = ? and head.client = ?
            and stock.isamt <> 0
            UNION ALL
            select head.docno,head.dateid,stock.isamt as amt,
            stock.uom,stock.disc from glhead as head
            left join glstock as stock on stock.trno = head.trno
            left join item on item.itemid = stock.itemid
            left join client on client.clientid = head.clientid
            left join cntnum on cntnum.trno=head.trno 
            where head.doc = 'SJ' and cntnum.center = ?
            and item.barcode = ? and client.client = ?
            and stock.isamt <> 0
            order by dateid desc limit 5) as tbl order by dateid desc limit 1";
        $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $client, $center, $barcode, $client]);
        break;
    }



    if (!empty($data)) {
      return ['status' => true, 'msg' => 'Found the latest purchase price...', 'data' => $data];
    } else {
      itempricehere:
      $qry = "select 'Retail Price' as docno, amt,amt as defamt,disc,uom from item where barcode=?";
      $data = $this->coreFunctions->opentable($qry, [$barcode]);

      setpricehere:
      $usdprice = 0;
      $forex = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
      $cur = $this->coreFunctions->getfieldvalue($this->head, 'cur', 'trno=?', [$trno]);
      $dollarrate = $this->coreFunctions->getfieldvalue('forex_masterfile', 'dollartocur', 'cur=?', [$cur]);
      $defuom = '';

      if ($this->companysetup->getisdefaultuominout($config['params'])) {
        $data[0]->docno = 'UOM';
        $defuom = $this->coreFunctions->datareader("select ifnull(uom.uom,'') as value from item left join uom on uom.itemid=item.itemid and uom.isdefault2 = 1 where item.barcode=?", [$barcode]);
        if ($defuom != "") {
          $data[0]->uom = $defuom;
          if ($this->companysetup->getisrecalcamtchangeuom($config['params'])) {
            if ($data[0]->amt != 0) {
              $data[0]->amt = $data[0]->amt * ($this->coreFunctions->datareader("select uom.factor as value from item left join uom on uom.itemid=item.itemid and uom.uom = '" . $defuom . "' where item.barcode=?", [$barcode]));
            } else {
              $data[0]->amt = $this->coreFunctions->datareader("select (item.amt*ifnull(uom.factor,1)) as value from item left join uom on uom.itemid=item.itemid and uom.uom = '" . $defuom . "' where item.barcode=?", [$barcode]);
            }
          }
        }
      } else {
        if ($this->companysetup->getisuomamt($config['params'])) {
          $pricefield = $this->othersClass->getamtfieldbygrp($pricegrp);
          $data[0]->docno = 'UOM';
          $data[0]->amt = $this->coreFunctions->datareader("select ifnull(uom." . $pricefield['amt'] . ",0) as value from item left join uom on uom.itemid=item.itemid and uom.uom=item.uom where item.barcode=?", [$barcode]);
        }
      }

      if (floatval($forex) <> 1) {
        $usdprice = $this->coreFunctions->getfieldvalue('item', 'foramt', 'barcode=?', [$barcode]);
        if ($cur == '$') {
          $data[0]->amt = $usdprice;
        } else {
          $data[0]->amt = round($usdprice * $dollarrate, 2);
        }
      }

      if (floatval($data[0]->amt) == 0) {
        return ['status' => false, 'msg' => 'No Latest price found...'];
      } else {
        return ['status' => true, 'msg' => 'Found the latest price...', 'data' => $data];
      }
    }
  } // end function

  public function getdrsummaryqry($config)
  {
    $qry = "
        select head.docno,left(head.dateid,10) as dateid,
        client.client,client.clientname,head.address,head.shipto,ifnull(p.name,'') as projectname,head.yourref,head.ourref,head.terms,head.trnxtype,head.cur,
        head.forex,head.rem,head.vattype,head.agentid,head.due,head.tax,agent.client as agent,head.projectid,wh.client as wh,head.contra, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.cost, (stock.iss-stock.qa) as iss,stock.isamt,
        round((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
        stock.disc FROM glhead as head left join glstock as stock on stock.trno=head.trno 
        left join cntnum on cntnum.trno = head.trno
        left join client on client.clientid=head.clientid
        left join client as agent on agent.clientid=head.agentid
        left join client as wh on wh.clientid=head.whid
        left join projectmasterfile as p on p.line = head.projectid
        left join item on item.itemid=stock.itemid 
        left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
        where stock.trno = ? and stock.iss>stock.qa and stock.void=0
    ";
    return $qry;
  }


  public function getdrsummary($config)
  {
    $dptrno = $config['params']['trno'];
    $forex = 1;
    $rows = [];

    foreach ($config['params']['rows'] as $key => $value) {
      $this->coreFunctions->sbcupdate('cntnum', ['dptrno' => $dptrno], ['trno' => $config['params']['rows'][$key]['trno']]);
      $this->logger->sbcwritelog($dptrno, $config, 'STOCK', 'ADD DR/ST:' . $config['params']['rows'][$key]['docno']);
      $line = $config['params']['rows'][$key]['trno'];
      $config['params']['line'] = $line;
      $row = $this->openstockline($config);
      $return = ['row' => $row, 'status' => true, 'msg' => ''];

      array_push($rows, $return['row'][0]);
    } //end foreach

    return ['row' => $rows, 'status' => true, 'msg' => 'Tagged DR/ST Successfully...'];
  } //end function


  public function getdrdetails($config)
  {
    $dptrno = $config['params']['trno'];
    $forex = 1;
    foreach ($config['params']['rows'] as $key => $value) {
      $this->coreFunctions->sbcupdate('cntnum', ['dptrno' => $dptrno], ['trno' => $config['params']['rows'][$key]['trno']]);
    } //end foreach
    $row = $this->openstock($dptrno, $config);
    return ['row' => $row, 'status' => true, 'msg' => 'Tagged DR Successfully...'];
  } //end function

  public function setserveditems($refx, $linex)
  {
    if ($refx == 0) {
      return 1;
    }
    $qry1 = "select stock." . $this->hqty . " from " . $this->head . " as head left join " . $this->stock . " as 
    stock on stock.trno=head.trno where head.doc='CK' and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry1 = $qry1 . " union all select stock." . $this->hqty . " from " . $this->hhead . " as head left join " . $this->hstock . " as stock on stock.trno=
    head.trno where head.doc='CK' and stock.refx=" . $refx . " and stock.linex=" . $linex;


    $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";

    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty == '') {
      $qty = 0;
    }
    return $this->coreFunctions->execqry("update glstock set ckqa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
  }

  // reports 

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

    $dataparams = $config['params']['dataparams'];
    if (isset($dataparams['prepared'])) $this->othersClass->writeSignatories($config, 'prepared', $dataparams['prepared']);
    if (isset($dataparams['approved'])) $this->othersClass->writeSignatories($config, 'approved', $dataparams['approved']);
    if (isset($dataparams['received'])) $this->othersClass->writeSignatories($config, 'received', $dataparams['received']);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
