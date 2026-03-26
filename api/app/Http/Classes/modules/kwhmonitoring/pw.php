<?php

namespace App\Http\Classes\modules\kwhmonitoring;

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

class pw
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'POWER CONSUMPTION ENTRY';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => true, 'show' => true, 'showdate' => false];
  public $tablenum = 'transnum';
  public $head = 'pwhead';
  public $hhead = 'hpwhead';
  public $stock = 'pwstock';
  public $hstock = 'hpwstock';
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';
  private $stockselect;
  public $dqty = 'isqty';
  public $hqty = 'iss';
  public $damt = 'isamt';
  public $hamt = 'amt';
  public $fields = ['trno', 'docno', 'dateid', 'rem', 'pwrcat'];
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
      'view' => 4079,
      'edit' => 4080,
      'new' => 4081,
      'save' => 4082,
      'delete' => 4083,
      'print' => 4084,
      'lock' => 4085,
      'unlock' => 4086,
      'changeamt' => 4087,
      'post' => 4088,
      'unpost' => 4089,
      'edititem' => 4090,
    );
    return $attrib;
  }


  public function createdoclisting($config)
  {
    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'rem', 'lockdate', 'listpostedby', 'postdate', 'listcreateby', 'createdate', 'listeditby', 'listviewby'];

    //  --> array_splice for repositioning
    // test lng.
    foreach ($getcols as $key => $value) {
      $$value = $key;
    }

    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';

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

    $orderby = "order by dateid desc, docno desc";

    $searchfilter = $config['params']['search'];

    switch ($itemfilter) {
      case 'draft':
        $condition = ' and num.postdate is null and head.lockdate is null';
        break;

      case 'locked':
        $condition = ' and head.lockdate is not null and num.postdate is null ';
        break;

      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
    }

    $limit = "limit 150";

    if (isset($searchfilter)) {
      if ($searchfilter != '') {
        $searchfield = ['head.docno', 'head.yourref', 'head.ourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby', 'head.rem'];
        if ($searchfilter != "") {
          $condition .= $this->othersClass->multisearch($searchfield, $searchfilter);
        }
        // $condition .= $this->othersClass->multisearch($searchfield, $searchfilter);
      }
    }

    $qry = "select head.trno,head.docno,left(head.dateid,10) as dateid, case ifnull(head.lockdate,'') when '' then  'DRAFT' else 'LOCKED' end as status,
    head.createby,head.editby,head.viewby,num.postedby,head.createdate,num.postdate,head.yourref, head.ourref,head.rem
     from " . $this->head . " as head 
     left join " . $this->tablenum . " as num on num.trno=head.trno 
     where head.doc=? and num.center=? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " 
     union all
     select head.trno,head.docno,left(head.dateid,10) as dateid,'POSTED' as status,
     head.createby,head.editby,head.viewby, num.postedby,head.createdate,num.postdate,head.yourref, head.ourref,head.rem
     from " . $this->hhead . " as head 
     left join " . $this->tablenum . " as num on num.trno=head.trno 
     where head.doc=? and num.center=? and convert(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " 
     $orderby " . $limit;

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


    switch ($config['params']['companyid']) {
      case 19: //housegem
        $buttons['others']['items']['uploadexcel'] = ['label' => 'Upload Retail Request Order', 'todo' => ['type' => 'uploadexcel', 'action' => 'uploadexcel', 'lookupclass' => 'uploadexcel', 'access' => 'view']];
        break;
    }

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
    $action = 0;
    $cat_name = 1;
    $subcat_name = 2;
    $isqty2 = 3;
    $isqty3 = 4;
    $isqty = 5;
    $isamt = 6;

    $column = ['action', 'cat_name', 'subcat_name', 'isqty2', 'isqty3', 'isqty', 'isamt'];

    $headgridbtns = [];

    $computefield = ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'total' => 'ext'];

    $tab = [
      $this->gridname => [
        'gridcolumns' => $column,
        'computefield' => $computefield, 'headgridbtns' => $headgridbtns
      ]
    ];

    $stockbuttons = ['save', 'stockinfo'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0]['inventory']['label'] = 'DETAILS';
    $obj[0]['inventory']['descriptionrow'] = [];

    $obj[0]['inventory']['columns'][$action]['btns']['stockinfo']['icon'] = 'refresh';
    $obj[0]['inventory']['columns'][$action]['btns']['stockinfo']['label'] = 'Reload previous reading';

    if (!$access['changeamt']) {
      $obj[0]['inventory']['columns'][$isamt]['readonly'] = true;
    }

    $obj[0]['inventory']['columns'][$cat_name]['label'] = 'Sub Category (Level 1)';
    $obj[0]['inventory']['columns'][$subcat_name]['label'] = 'Sub Category (Level 2)';

    $obj[0]['inventory']['columns'][$cat_name]['type'] = 'label';
    $obj[0]['inventory']['columns'][$subcat_name]['type'] = 'label';

    $obj[0]['inventory']['columns'][$isqty2]['type'] = 'input';
    $obj[0]['inventory']['columns'][$isqty2]['readonly'] = true;

    $obj[0]['inventory']['columns'][$isqty]['type'] = 'input';
    $obj[0]['inventory']['columns'][$isqty]['readonly'] = true;

    $obj[0]['inventory']['columns'][$isamt]['type'] = 'input';
    $obj[0]['inventory']['columns'][$isamt]['readonly'] = true;

    $obj[0]['inventory']['columns'][$isqty2]['label'] = 'Previous Reading (m)';
    $obj[0]['inventory']['columns'][$isqty3]['label'] = 'Current Reading (m)';
    $obj[0]['inventory']['columns'][$isqty]['label'] = 'Consumed (m)';
    $obj[0]['inventory']['columns'][$isamt]['label'] = 'Rate';

    $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['saveitem'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['label'] = 'SAVE DETAILS';
    return $obj;
  }

  public function createHeadField($config)
  {
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    // col 1
    $fields = ['docno', 'dateid', 'categoryname'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'docno.label', 'Transaction#');
    data_set($col1, 'categoryname.lookupclass', 'lookupcategory_kwh');

    // col 2
    $fields = ['rem'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'rem.required', false);

    // col 3
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
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['yourref'] = '';
    $data[0]['ourref'] = '';
    $data[0]['rem'] = '';
    $data[0]['pwrcat'] = '0';
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
         head.yourref,
         head.ourref,
         left(head.dateid,10) as dateid, 
         date_format(head.createdate,'%Y-%m-%d') as createdate,
         head.rem,
         head.pwrcat,
         cat.name as categoryname";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join powercat as cat on cat.line=head.pwrcat
        where head.trno = ? and num.center = ? 
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join powercat as cat on cat.line=head.pwrcat
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
    $companyid = $config['params']['companyid'];
    $head = $config['params']['head'];
    $data = [];
    if ($isupdate) {
      unset($this->fields[1]);
      unset($head['docno']);
    }

    if ($companyid == 0) { //main
      array_push($this->fields, 'sotype');
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
      // $current_timestamp = $this->othersClass->getCurrentTimeStamp();

      $pwexist = $this->coreFunctions->opentable(
        "select trno from pwhead where date(dateid)=? and pwrcat=? union all select trno from hpwhead where date(dateid)=? and pwrcat=?",
        [$data['dateid'], $data['pwrcat'], $data['dateid'], $data['pwrcat']]
      );
      // $current_timestamp = $this->othersClass->getCurrentTimeStamp();
      // [$this->config['params']['head']['dateid'], $this->config['params']['head']['pwrcat'], $this->config['params']['head']['dateid'], $this->config['params']['head']['pwrcat']]);
      if (empty($pwexist)) {
        $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
      }
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

    //$this->coreFunctions->execqry('delete from '.$this->stock." where trno=?",'delete',[$trno]);
    $this->deleteallitem($config);
    $this->coreFunctions->execqry('delete from headinfotrans where trno=?', 'delete', [$trno]);
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
    $companyid = $config['params']['companyid'];
    $qry = "select trno from " . $this->stock . " where trno=? and iss=0 limit 1";
    $isitemzeroqty = $this->coreFunctions->opentable($qry, [$trno]);

    $client = $this->coreFunctions->getfieldvalue($this->head, "client", "trno=?", [$trno]);
    $crlimit = $this->coreFunctions->getfieldvalue("client", "isnocrlimit", "client=?", [$client]);
    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    if ($this->othersClass->isposted($config)) {
      return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
    }

    $qry = "insert into " . $this->hhead . "(trno, doc, docno, dateid, rem, voiddate, branch, yourref, ourref, approvedby, approveddate, printtime, lockuser, lockdate, openby, users, createdate, createby, editby, editdate, viewby, viewdate, pwrcat)
      SELECT trno, doc, docno, dateid, rem, voiddate, branch, yourref, ourref, approvedby, approveddate, printtime, lockuser, lockdate, openby, users, createdate, createby, editby, editdate, viewby, viewdate, pwrcat
      FROM " . $this->head . " as head where head.trno=? limit 1";
    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($posthead) {

      $qry = "insert into " . $this->hstock . "(trno, line, uom, disc, rem, amt, isqty, isamt, iss, ext, qa, void, encodeddate, encodedby, editdate, editby, sortline, catid, subcat, subcat2, isqty2, isqty3)
        SELECT trno, line, uom, disc, rem, amt, isqty, isamt, iss, ext, qa, void, encodeddate, encodedby, editdate, editby, sortline, catid, subcat, subcat2, isqty2, isqty3 FROM " . $this->stock . " where trno =?";
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        //update transnum
        $date = $this->othersClass->getCurrentTimeStamp();
        $data = ['postdate' => $date, 'postedby' => $config['params']['user'], 'statid' => 5];
        $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
        $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
        $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
        $this->othersClass->sbctransferlog($trno, $config, $this->htablelogs);
        return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
      } else {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting stock'];
      }
      //if($posthead){      
    } else {
      return ['status' => false, 'msg' => 'Error on Posting Head'];
    }
  } //end function

  public function unposttrans($config)
  {
    $companyid = $config['params']['companyid'];
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    $qry = "insert into " . $this->head . "(trno, doc, docno, dateid, rem, voiddate, branch, yourref, ourref, approvedby, approveddate, printtime, lockuser, lockdate, openby, users, createdate, createby, editby, editdate, viewby, viewdate, pwrcat)
    select trno, doc, docno, dateid, rem, voiddate, branch, yourref, ourref, approvedby, approveddate, printtime, lockuser, lockdate, openby, users, createdate, createby, editby, editdate, viewby, viewdate, pwrcat
    from " . $this->hhead . " as head where head.trno=?";
    //head
    if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {

      $qry = "insert into " . $this->stock . "(trno, line, uom, disc, rem, amt, isqty, isamt, iss, ext, qa, void, encodeddate, encodedby, editdate, editby, sortline, catid, subcat, subcat2, isqty2, isqty3)
      select trno, line, uom, disc, rem, amt, isqty, isamt, iss, ext, qa, void, encodeddate, encodedby, editdate, editby, sortline, catid, subcat, subcat2, isqty2, isqty3 from " . $this->hstock . " where trno=?";
      //stock
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null where trno=?", 'update', [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
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
    $sqlselect = "select 
    head.trno, 
    ifnull(stock.line,0) as line,
    stock.sortline,
    stock.uom, 
    ifnull(stock.iss,0) as iss,
    ifnull(stock.amt,0) as amt, 
    if(ifnull(stock.isqty3,0)=0,'',FORMAT(ifnull(stock.isqty3,0)," . $this->companysetup->getdecimal('qty', $config['params']) . "))  as isqty3,   
    FORMAT(ifnull(stock.isqty,0)," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as isqty,
    FORMAT(ifnull(stock.isamt,0),4) as isamt,
    FORMAT(ifnull(stock.isqty * stock.isamt,0)," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext, 
    left(stock.encodeddate,10) as encodeddate,
    stock.rem,
    subcat.name as cat_name,
    subcat2.name as subcat_name,
    ifnull(subcat.line,0) as subcat,
    ifnull(subcat2.line,0) as subcat2,
    cat.line as catid,
    0 as itemid,
    '' as barcode,
    '' as bgcolor,
    case when ifnull(stock.void,0)=0 then '' else 'bg-red-2' end as errcolor ";
    return $sqlselect;
  }

  public function openstock($trno, $config)
  {
    $catid = $this->coreFunctions->datareader("select pwrcat as value from " . $this->head . " as h where h.trno=? union all select pwrcat as value from " . $this->hhead . " as h where h.trno=?", [$trno, $trno], '', true);
    $dateid = $this->othersClass->getCurrentDate();
    if (!$this->othersClass->isposted2($trno, "transnum")) {
      $dateid = $this->coreFunctions->getfieldvalue($this->head, "date(dateid)", "trno=?", [$trno]);
    }

    $sqlselect = $this->getstockselect($config);

    $qry = $sqlselect . ",
    FORMAT(ifnull(stock.isqty2,(select s.isqty3 from hpwstock as s left join hpwhead as h on h.trno=s.trno where h.dateid<'" . $dateid . "' and s.subcat2=subcat2.line order by h.dateid desc limit 1))," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as isqty2
    FROM $this->head as head
    left join powercat as cat on cat.line=head.pwrcat
    left join subpowercat as subcat on subcat.catid=cat.line
    left join subpowercat2 as subcat2 on subcat2.subcatid=subcat.line
    left join $this->stock as stock on stock.trno=head.trno and stock.subcat2=subcat2.line
    where head.trno =? and cat.line=? and ifnull(subcat2.line,0)<>0
    UNION ALL  
    " . $sqlselect . ",
    FORMAT(ifnull(stock.isqty2,0)," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as isqty2    
    FROM $this->hhead as head
    left join powercat as cat on cat.line=head.pwrcat
    left join subpowercat as subcat on subcat.catid=cat.line
    left join subpowercat2 as subcat2 on subcat2.subcatid=subcat.line
    left join $this->hstock as stock on stock.trno=head.trno and stock.subcat2=subcat2.line
    where head.trno =? and cat.line=? and ifnull(subcat2.line,0)<>0
    order by subcat, subcat2";
    // $this->othersClass->logConsole($qry);
    $stock = $this->coreFunctions->opentable($qry, [$trno, $catid, $trno, $catid]);
    return $stock;
  } //end function

  public function openstockline($config)
  {
    $sqlselect = $this->getstockselect($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];

    if (isset($config['params']['row'][0]['subcat2'])) {
      $subcat2 = $config['params']['row'][0]['subcat2'];
    } else {
      $subcat2 = $config['params']['row']['subcat2'];
    }

    $dateid = $this->coreFunctions->getfieldvalue($this->head, "date(dateid)", "trno=?", [$trno]);

    $qry = $sqlselect . ",
    FORMAT(ifnull(stock.isqty2,(select s.isqty3 from hpwstock as s left join hpwhead as h on h.trno=s.trno where h.dateid<'" . $dateid . "' and s.subcat2=subcat2.line order by h.dateid desc limit 1))," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as isqty2  
    FROM $this->head as head
    left join powercat as cat on cat.line=head.pwrcat
    left join subpowercat as subcat on subcat.catid=cat.line
    left join subpowercat2 as subcat2 on subcat2.subcatid=subcat.line
    left join $this->stock as stock on stock.trno=head.trno and stock.subcat2=subcat2.line
    where head.trno =? and subcat2.line=?  ";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $subcat2]);
    return $stock;
  } // end function

  public function stockstatus($config)
  {
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
        return $this->updateitem($config);
        break;
      case 'saveperitem':
        return $this->updateperitem($config);
        break;
      case 'deleteallitem':
        return $this->deleteallitem($config);
        break;
      case 'getqtdetails':
        return $this->getqtdetails($config);
        break;
      case 'getqtsummary':
        return $this->getqtsummary($config);
        break;
      case 'geteggitems':
        return $this->geteggitems($config);
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
      case 'uploadexcel':
        return $this->uploadexcel($config);
        break;
      case 'forapproval':
        return $this->forapproval($config);
        break;
      case 'doneapproved':
        return $this->doneapproved($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }




  public function doneapproved($config)
  {
    $posted = $this->othersClass->isposted($config);
    if ($posted) {
      return ['status' => false, 'msg' => 'Already posted'];
    }

    if ($this->coreFunctions->sbcupdate($this->tablenum, ['statid' => 36], ['trno' => $config['params']['trno']])) {
      $this->coreFunctions->sbcupdate($this->head, ['lockuser' => $config['params']['user'], 'lockdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $config['params']['trno']]);
      $this->logger->sbcwritelog($config['params']['trno'], $config, 'HEAD', 'APPROVED!');
      return ['status' => true, 'msg' => 'Successfully updated', 'backlisting' => true];
    } else {
      return ['status' => false, 'msg' => 'Failed to tag approved'];
    }
  }

  public function forapproval($config)
  {
    $posted = $this->othersClass->isposted($config);
    if ($posted) {
      return ['status' => false, 'msg' => 'Already posted'];
    }

    if ($this->coreFunctions->sbcupdate($this->tablenum, ['statid' => 10], ['trno' => $config['params']['trno']])) {
      $this->logger->sbcwritelog($config['params']['trno'], $config, 'HEAD', 'Tag FOR APPROVAL');
      return ['status' => true, 'msg' => 'Successfully updated', 'backlisting' => true];
    } else {
      return ['status' => false, 'msg' => 'Failed to tag for approval'];
    }
  }

  public function uploadexcel($config)
  {
    $rawdata = $config['params']['data'];
    $trno = $config['params']['dataparams']['trno'];
    $msg = '';
    $status = true;

    if ($trno == 0) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Kindly create the document number first.'];
    }

    foreach ($rawdata as $key => $value) {
      try {
        if (isset($rawdata[$key]['itemcode'])) {
          $itemid = $this->coreFunctions->getfieldvalue("item", "itemid", "barcode = '" . $rawdata[$key]['itemcode'] . "'");
          if ($itemid == '') {
            $status = false;
            $msg .= 'Failed to upload. ' . $rawdata[$key]['itemcode'] . ' does not exist. ';
            continue;
          }
        } else {
          $msg .= 'Failed to upload. Item code is required. ';
          continue;
        }

        if (isset($rawdata[$key]['driver'])) {
          $driverid = $this->coreFunctions->getfieldvalue("client", "clientid", "client = '" . $rawdata[$key]['driver'] . "'");
          if ($driverid == '') {
            $status = false;
            $msg .= 'Failed to upload. Driver ' . $rawdata[$key]['driver'] . ' does not exist. ';
            continue;
          }
        } else {
          $msg .= 'Failed to upload. Driver is required. ';
          continue;
        }

        if (isset($rawdata[$key]['helper'])) {
          $helperid = $this->coreFunctions->getfieldvalue("client", "clientid", "client = '" . $rawdata[$key]['helper'] . "'");
          if ($helperid == '') {
            $status = false;
            $msg .= 'Failed to upload. Helper ' . $rawdata[$key]['helper'] . ' does not exist. ';
            continue;
          }
        } else {
          $msg .= 'Failed to upload. Helper is required. ';
          continue;
        }

        if (isset($rawdata[$key]['truck'])) {
          $truckid = $this->coreFunctions->getfieldvalue("client", "clientid", "client = '" . $rawdata[$key]['truck'] . "'");
          if ($truckid == '') {
            $status = false;
            $msg .= 'Failed to upload. Helper ' . $rawdata[$key]['truck'] . ' does not exist. ';
            continue;
          }
        } else {
          $msg .= 'Failed to upload. Truck is required. ';
          continue;
        }

        $others = [
          'driverid' => $driverid,
          'helperid' => $helperid,
          'truckid' => $truckid,
          'plateno' => $rawdata[$key]['plateno'],
          'isro' => 1
        ];
        $this->coreFunctions->sbcupdate('headinfotrans', $others, ['trno' => $trno]);

        $config['params']['trno'] = $trno;
        $config['params']['data']['ref'] = $rawdata[$key]['ref'];
        $config['params']['data']['uom'] = $rawdata[$key]['uom'];
        $config['params']['data']['itemid'] = $itemid;
        $config['params']['data']['qty'] = $rawdata[$key]['qty'];
        $config['params']['data']['wh'] =  $this->coreFunctions->getfieldvalue($this->head, "wh", "trno = ?", [$trno]);
        $config['params']['data']['amt'] = $rawdata[$key]['cost'];
        $config['params']['data']['loc'] = isset($rawdata[$key]['location']) ? $rawdata[$key]['location'] : "";
        $config['params']['data']['disc'] = isset($rawdata[$key]['disc']) ? $rawdata[$key]['disc'] : "";

        if (isset($rawdata[$key]['kgs'])) {
          $config['params']['data']['kgs'] = $rawdata[$key]['kgs'];
        }
        $config['params']['data']['weight'] = $rawdata[$key]['weight'];

        $return = $this->additem('insert', $config);
        if (!$return['status']) {
          $status = false;
          $msg .= 'Failed to upload. ' . $return['msg'];
          goto exithere;
        }
      } catch (Exception $e) {
        $status = false;
        $msg .= 'Failed to upload. Exception error ' . $e->getMessage();
        goto exithere;
      }
    }

    exithere:
    if ($msg == '') {
      $this->logger->sbcwritelog($trno, $config, 'IMPORT', 'UPLOAD EXCEL FILE');
      $msg = 'Successfully uploaded.';
    }

    if (!$status) {
      $this->coreFunctions->execqry("delete from lastock where trno=" . $trno);
    }

    return ['trno' => $trno, 'status' => $status, 'msg' => $msg, 'reloadhead' => true];
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
    $config['params']['data'] = $config['params']['row'];
    $this->additem('update', $config);
    $data = $this->openstockline($config);
    return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
  }


  public function updateitem($config)
  {
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $this->additem('update', $config);
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
    $barcodelength = $this->companysetup->getbarcodelength($config['params']);
    $config['params']['barcode'] = trim($config['params']['barcode']);
    if ($barcodelength == 0) {
      $barcode = $config['params']['barcode'];
    } else {
      $barcode = $this->othersClass->padj($config['params']['barcode'], $barcodelength);
    }
    $wh = $config['params']['wh'];

    $item = $this->coreFunctions->opentable("select item.itemid,item.amt,item.disc,'' as loc,'" . $wh . "' as wh, 1 as qty, uom from item where barcode=?", [$barcode]);
    $item = json_decode(json_encode($item), true);

    if (!empty($item)) {
      $config['params']['barcode'] = $barcode;
      $lprice = $this->getlatestprice($config);
      $lprice = json_decode(json_encode($lprice), true);
      if (!empty($lprice['data'])) {
        $item[0]['amt'] = $lprice['data'][0]['amt'];
        $item[0]['disc'] = $lprice['data'][0]['disc'];
      }

      $config['params']['data'] = $item[0];
      return $this->additem('insert', $config);
    } else {
      return ['status' => false, 'msg' => 'Barcode not found.', ''];
    }
  }

  // insert and update item
  public function additem($action, $config)
  {
    $companyid = $config['params']['companyid'];
    $uom = $config['params']['data']['uom'];
    $trno = $config['params']['trno'];
    $sline = $config['params']['data']['line'];
    $catid = $config['params']['data']['catid'];
    $subcat = $config['params']['data']['subcat'];
    $subcat2 = $config['params']['data']['subcat2'];
    $isqty2 = $config['params']['data']['isqty2'];
    $isqty3 = $config['params']['data']['isqty3'];
    $void = 'false';

    if ($subcat2 == 0) {
      return ['status' => false, 'msg' => 'Sub-Category (Level 2) is required'];
    }

    $config['params']['data']['qty'] = 1;

    if (isset($config['params']['data']['void'])) {
      $void = $config['params']['data']['void'];
    }

    if ($sline == 0) {
      $action = 'insert';
    }

    $line = 0;
    if ($action == 'insert') {
      $qry = "select line as value from " . $this->stock . " where trno=? order by line desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$trno]);
      if ($line == '') {
        $line = 0;
      }
      $line = $line + 1;
      $config['params']['line'] = $line;
      $amt = $config['params']['data'][$this->damt];
    } elseif ($action == 'update') {
      $config['params']['line'] = $config['params']['data']['line'];
      $line = $config['params']['data']['line'];
      $amt = $config['params']['data'][$this->damt];
      $config['params']['line'] = $line;
    }

    $amt = $this->othersClass->sanitizekeyfield('amt', $amt);
    $isqty3 = $this->othersClass->sanitizekeyfield('qty', $isqty3);
    $isqty2 = $this->othersClass->sanitizekeyfield('qty', $isqty2);

    $qty = $isqty3 - $isqty2;
    // if ($isqty2 == 0)  $qty = 0;
    $qty = $this->othersClass->sanitizekeyfield('qty', $qty);

    $factor = 1;
    $computedata = $this->othersClass->computestock($amt, '', $qty, $factor);
    $forex = 1;

    $data = [
      'trno' => $trno,
      'line' => $line,
      'isamt' => $amt,
      'amt' => number_format($computedata['amt'] * $forex, $this->companysetup->getdecimal('price', $config['params']), '.', ''),
      'isqty' => $qty,
      'iss' => $computedata['qty'],
      'ext' => number_format($computedata['ext'], $this->companysetup->getdecimal('currency', $config['params']), '.', ''),
      'void' => $void,
      'uom' => $uom,
      'catid' => $catid,
      'subcat' => $subcat,
      'subcat2' => $subcat2,
      'isqty2' => $isqty2,
      'isqty3' => $isqty3
    ];
    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $data['editdate'] = $current_timestamp;
    $data['editby'] = $config['params']['user'];
    if ($action == 'insert') {
      $msg = 'Item was successfully added.';
      $data['encodeddate'] = $current_timestamp;
      $data['encodedby'] = $config['params']['user'];
      $data['sortline'] =  $data['line'];

      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {

        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . 'SubCat (Lvl2):' . $config['params']['data']['subcat_name'] . ' Amt:' . $amt);
        $row = $this->openstockline($config);
        $this->loadheaddata($config);

        return ['row' => $row, 'status' => true, 'msg' => $msg, 'reloaddata' => true];
      } else {
        return ['status' => false, 'msg' => 'Add item Failed'];
      }
    } elseif ($action == 'update') {
      $return = true;
      $msg = '';
      $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
      // $this->coreFunctions->execqry("update " . $this->stock . " set isamt='" . $data['isamt'] . "', amt='" . $data['amt'] . "', isqty='" . $data['isqty'] . "', iss='" . $data['iss'] . "', ext='" . $data['ext'] . "' where trno='" . $data['trno'] . "' and line='" . $data['line'] . "'", 'update');
      return ['status' => $return, 'msg' => $msg];
    }
  } // end function



  public function deleteallitem($config)
  {
  }


  public function deleteitem($config)
  {
  } // end function

  public function getlatestprice($config)
  {
    $barcode = $config['params']['barcode'];
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];

    $pricetype = $this->companysetup->getpricetype($config['params']);
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
        if ($config['params']['companyid'] == 22) { //eipi
          $qry = "select docno,left(dateid,10) as dateid,round(amt,2) as amt,round(amt,2) as defamt,disc,uom 
              from(
                select head.docno,head.dateid,
                stock.rrcost as amt,stock.uom,stock.disc
                from lahead as head
                left join lastock as stock on stock.trno = head.trno
                left join cntnum on cntnum.trno=head.trno
                left join item on item.itemid = stock.itemid
                where head.doc in ('RR','IS','AJ','TS') and cntnum.center = ?
                and item.barcode = ? 
                and stock.cost <> 0 and cntnum.trno <>?
              union all
                select head.docno,head.dateid,stock.rrcost as amt,
                stock.uom,stock.disc from glhead as head
                left join glstock as stock on stock.trno = head.trno
                left join item on item.itemid = stock.itemid
                left join client on client.clientid = head.clientid
                left join cntnum on cntnum.trno=head.trno
                where head.doc in ('RR','IS','AJ','TS') and cntnum.center = ?
                and item.barcode = ? 
                and stock.cost <> 0 and cntnum.trno <>?
              order by dateid desc limit 5 ) as tbl order by dateid desc limit 1";
          $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $trno, $center, $barcode, $trno]);
        } else {
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
        }
        break;
    }

    if (!empty($data)) {
      return ['status' => true, 'msg' => 'Found the latest purchase price...', 'data' => $data];
    } else {
      if ($config['params']['companyid'] == 15) { //NATHINA
        $trno = $config['params']['trno'];
        $qry = "select 'PRICE LIST' as docno, (select b.r from sohead as head left join client on client.client=head.client left join pricebracket as b on b.groupid=client.category where head.trno=? and b.itemid=item.itemid) as amt,
        (select b.r from sohead as head left join client on client.client=head.client left join pricebracket as b on b.groupid=client.category where head.trno=? and b.itemid=item.itemid) as defamt,
            disc,uom from item where barcode=?";
        $data = $this->coreFunctions->opentable($qry, [$trno, $barcode]);
      } else {
        itempricehere:
        $qry = "select 'Retail Price' as docno, amt,amt as defamt,disc,uom from item where barcode=?";
        $data = $this->coreFunctions->opentable($qry, [$barcode]);
      }

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
          $data[0]->docno = 'UOM';
          $data[0]->amt = $this->coreFunctions->datareader("select ifnull(uom.amt,0) as value from item left join uom on uom.itemid=item.itemid and uom.uom=item.uom where item.barcode=?", [$barcode]);
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

  public function getqtsummary($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    $msg = '';
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.docno, item.itemid,stock.trno, 
        stock.line, item.barcode,stock.uom, stock.amt,
        (stock.iss-stock.qa) as iss,stock.isamt,
        round((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty, 
        stock.disc,stock.loc,stock.expiry
        FROM hqthead as head left join hqtstock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and 
        uom.uom=stock.uom where stock.trno = ? and stock.iss>stock.qa and stock.void=0
    ";
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno']]);
      if (!empty($data)) {
        foreach ($data as $key2 => $value) {
          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['qty'] = $data[$key2]->isqty;
          $config['params']['data']['wh'] = $wh;
          $config['params']['data']['loc'] = $data[$key2]->loc;
          $config['params']['data']['expiry'] = $data[$key2]->expiry;
          $config['params']['data']['rem'] = '';
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->isamt;
          $return = $this->additem('insert', $config);

          if ($msg = '') {
            $msg = $return['msg'];
          } else {
            $msg = $msg . $return['msg'];
          }

          if ($return['status']) {
            if ($this->setserveditems($data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setserveditems($data[$key2]->trno, $data[$key2]->line);
              $row = $this->openstockline($config);
              $return = ['row' => $row, 'status' => true, 'msg' => $msg];
            }
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => $msg];
  } //end function

  public function getqtdetails($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    $msg = '';
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.docno, item.itemid,stock.trno, 
        stock.line, item.barcode,stock.uom, stock.amt,
        (stock.iss-stock.qa) as iss,stock.isamt,
        round((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty, 
        stock.disc,stock.loc,stock.expiry
        FROM hqthead as head left join hqtstock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and 
        uom.uom=stock.uom where stock.trno = ? and stock.line=? and stock.iss>stock.qa and stock.void=0
    ";
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line']]);
      if (!empty($data)) {
        foreach ($data as $key2 => $value) {
          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['qty'] = $data[$key2]->isqty;
          $config['params']['data']['wh'] = $wh;
          $config['params']['data']['loc'] = $data[$key2]->loc;
          $config['params']['data']['expiry'] = $data[$key2]->expiry;
          $config['params']['data']['rem'] = '';
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->isamt;
          $return = $this->additem('insert', $config);
          if ($msg = '') {
            $msg = $return['msg'];
          } else {
            $msg = $msg . $return['msg'];
          }
          if ($return['status']) {
            if ($this->setserveditems($data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setserveditems($data[$key2]->trno, $data[$key2]->line);
              $row = $this->openstockline($config);
              $return = ['row' => $row, 'status' => true, 'msg' => $msg];
            }
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => $msg];
  } //end function


  public function geteggitemsqry($config, $itemid)
  {
    return "select i.itemid,i.barcode,i.itemname,i.uom,i.disc
            from item as i
            left join itemcategory as cat on i.category= cat.line
            where cat.name = 'Egg' and i.itemid = " . $itemid . " ";
  }

  public function geteggitems($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $companyid = $config['params']['companyid'];
    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $itemid = $config['params']['rows'][$key]['itemid'];
      $qry = $this->geteggitemsqry($config, $itemid);
      $data = $this->coreFunctions->opentable($qry);
      if (!empty($data)) {
        foreach ($data as $key2 => $value) {
          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['qty'] = '';
          $config['params']['data']['wh'] = $wh;
          $config['params']['data']['loc'] = '';
          $config['params']['data']['expiry'] = '';
          $config['params']['data']['rem'] = '';
          $config['params']['data']['ref'] = '';
          $config['params']['data']['amt'] = '';
          $config['params']['data']['stageid'] = '';

          $return = $this->additem('insert', $config);
          if ($return['status']) {
            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
            $line = $return['row'][0]->line;
            $config['params']['trno'] = $trno;
            $config['params']['line'] = $line;
            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
            $row = $this->openstockline($config);
            $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.'];
  } //end function

  public function setserveditems($refx, $linex)
  {
    if ($refx == 0) {
      return 1;
    }
    $qry1 = "select stock." . $this->hqty . " from " . $this->head . " as head left join " . $this->stock . " as 
    stock on stock.trno=head.trno where head.doc='SO' and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry1 = $qry1 . " union all select stock." . $this->hqty . " from " . $this->hhead . " as head left join " . $this->hstock . " as stock on stock.trno=
    head.trno where head.doc='SO' and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty == '') {
      $qty = 0;
    }
    return $this->coreFunctions->execqry("update hqtstock set qa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
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
    $companyid = $config['params']['companyid'];
    if ($companyid == 10 || $companyid != 12) { //afti & not afti usd
    } else {
      $this->logger->sbcviewreportlog($config);
    }

    $data = app($this->companysetup->getreportpath($config['params']))->generateResult($config, $config['params']['dataid']); // need to pass params
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
