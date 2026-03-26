<?php

namespace App\Http\Classes\modules\waterbilling;

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
use App\Http\Classes\headClass;
use App\Http\Classes\builder\helpClass;
use Exception;

class wm
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'WATER CONSUMPTION';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => true, 'show' => true, 'showdate' => false];
  public $tablenum = 'cntnum';
  public $head = 'lahead';
  public $hhead = 'glhead';
  public $stock = 'lastock';
  public $hstock = 'glstock';
  public $detail = 'ladetail';
  public $hdetail = 'gldetail';
  public $tablelogs = 'table_log';
  public $htablelogs = 'htable_log';
  public $tablelogs_del = 'del_table_log';
  public $dqty = 'isqty';
  public $hqty = 'iss';
  public $damt = 'isamt';
  public $hamt = 'amt';
  public $defaultContra = 'AR1';
  private $stockselect;
  private $fields = ['trno', 'docno', 'dateid', 'due', 'rem', 'tax', 'vattype', 'projectid', 'sdate1', 'sdate2', 'contra', 'wh'];
  private $except = ['trno', 'dateid', 'due', 'sdate1', 'sdate2'];
  private $acctg = [];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  private $reporter;
  private $helpClass;
  private $headClass;

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
    $this->sqlquery = new sqlquery;
    $this->logger = new Logger;
    $this->reporter = new SBCPDF;
    $this->helpClass = new helpClass;
    $this->headClass = new headClass;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 4125,
      'edit' => 4126,
      'new' => 4127,
      'save' => 4128,
      'delete' => 4129,
      'print' => 4130,
      'lock' => 4131,
      'unlock' => 4132,
      'acctg' => 4136,
      'changeamt' => 4135,
      'post' => 4133,
      'unpost' => 4134,
      'additem' => 4137,
      'edititem' => 4138,
      'deleteitem' => 4139,
      'release' => 2994,
      'whinfo' => 3959
    );

    return  $attrib;
  }

  public function createdoclisting($config)
  {
    $companyid =  $config['params']['companyid'];
    $userid =  $config['params']['adminid'];

    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listprojectname', 'startdate', 'enddate', 'rem'];

    foreach ($getcols as $key => $value) {
      $$value = $key;
    }

    $stockbuttons = ['view'];
    $cols =  $this->tabClass->createdoclisting($getcols,  $stockbuttons);

    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$liststatus]['name'] = 'statuscolor';
    $cols[$startdate]['label'] = 'Start Date';

    $cols =  $this->tabClass->delcollisting($cols);
    return  $cols;
  }

  public function loaddoclisting($config)
  {
    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));

    $doc =  $config['params']['doc'];
    $companyid =  $config['params']['companyid'];
    $center =  $config['params']['center'];
    $condition = '';
    $searchfilter =  $config['params']['search'];
    $itemfilter = $config['params']['itemfilter'];

    $limit = '';
    $lstat =  " 'DRAFT' ";
    $gstat =  " 'POSTED' ";
    $lstatcolor =  " 'blue' ";
    $gstatcolor =  " 'grey' ";


    switch ($itemfilter) {
      case 'draft':
        $condition = ' and head.lockdate is null and num.postdate is null ';
        break;
      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
      case 'locked':
        $condition = ' and head.lockdate is not null and num.postdate is null ';
        break;
    }

    $dateid =  " left (head . dateid, 10) as dateid ";
    $orderby =  " order by dateid desc, docno desc ";

    if ($searchfilter ==  " ")  $limit = 'limit 150';
    $lstat =  " case ifnull (head.lockdate, '') when '' then 'DRAFT' else 'LOCKED' end ";
    $lstatcolor =  " case ifnull (head.lockdate, '') when '' then 'red' else 'green' end ";

    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'head.dateid', 'project.name', 'head.sdate1', 'head.sdate2', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby', 'project.name'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
      $limit = "";
    }

    $qry =  " select date(head.dateid) as date2, head.trno, head.docno, $dateid, $lstat as status, $lstatcolor as statuscolor,
    head.createby, head.editby, head.viewby, num.postedby, date(head.sdate1) as startdate, date(head.sdate2) as enddate, ifnull(project.name,'') as projectname
    from " .  $this->head .  " as head left join " .  $this->tablenum .  " as num on num.trno = head.trno
    left join trxstatus as stat on stat.line = num.statid
    left join projectmasterfile as project on project.line = head.projectid
    where head.doc = ? and num.center = ? and CONVERT (head.dateid, DATE) >= ? and CONVERT (head.dateid, DATE) <= ? " .  $condition .   " $filtersearch
    union all
    select date(head.dateid) as date2, head.trno, head.docno, $dateid, $gstat as status, $gstatcolor as statuscolor,
    head.createby, head.editby, head.viewby, num.postedby, date(head.sdate1) as startdate, date(head.sdate2) as enddate, ifnull(project.name,'') as projectname
    from " .  $this->hhead .  " as head left join " .  $this->tablenum .  " as num
    on num.trno = head.trno
    left join trxstatus as stat on stat.line = num.statid
    left join projectmasterfile as project on project.line = head.projectid
    where head.doc = ? and num.center = ? and CONVERT (head.dateid, DATE) >= ? and CONVERT (head.dateid, DATE) <= ? " .  $condition .   " $filtersearch
    $orderby $limit ";
    $data =  $this->coreFunctions->opentable($qry, [$doc,  $center,  $date1,  $date2,  $doc,  $center,  $date1,  $date2]);
    return ['data' =>  $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
  }

  public function paramsdatalisting($config)
  {
    $companyid =  $config['params']['companyid'];

    $fields = [];
    $col1 =  $this->fieldClass->create($fields);

    $fields = [];
    $col2 =  $this->fieldClass->create($fields);

    $prefix =  $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'doc=? and psection=?', ['SED', 'WM']);
    if ($prefix != '') {
      $prefixes = explode(", ",  $prefix);
      $list = array();
      foreach ($prefixes as  $key) {
        array_push($list, ['label' =>  $key, 'value' =>  $key]);
      }
      data_set($col2, 'selectprefix.options',  $list);
    }
    $data =  $this->coreFunctions->opentable(" select '' as docno, '' as selectprefix ");

    return ['status' => true, 'data' =>  $data[0], 'txtfield' => ['col1' =>  $col1, 'col2' =>  $col2]];
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

    $buttons =  $this->btnClass->create($btns);
    $step1 =  $this->helpClass->getFields(['btnnew', 'customer', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
    $step2 =  $this->helpClass->getFields(['btnedit', 'customer', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
    $step3 =  $this->helpClass->getFields(['btnadditem', 'btnquickadd', 'isqty', 'uom', 'isamt', 'disc', 'wh', 'btnstocksave', 'btnsaveitem']);
    $step4 =  $this->helpClass->getFields(['isqty', 'uom', 'isamt', 'disc', 'wh', 'btnstocksave', 'btnsaveitem']);
    $step5 =  $this->helpClass->getFields(['btnstockdelete', 'btndeleteallitem']);
    $step6 =  $this->helpClass->getFields(['btndelete']);


    $buttons['help']['items'] = [
      'create' => ['label' => 'How to create New Document', 'action' =>  $step1],
      'edit' => ['label' => 'How to edit details from the header', 'action' =>  $step2],
      'additem' => ['label' => 'How to add item/s', 'action' =>  $step3],
      'edititem' => ['label' => 'How to edit item details', 'action' =>  $step4],
      'deleteitem' => ['label' => 'How to delete item/s', 'action' =>  $step5],
      'deletehead' => ['label' => 'How to delete whole transaction', 'action' =>  $step6]
    ];

    $buttons['others']['items'] = [
      'first' => ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']],
      'prev' => ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']],
      'next' => ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']],
      'last' => ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']],
    ];

    switch ($config['params']['companyid']) {
      case 19: //housegem
        $buttons['others']['items']['downloadexcel'] = ['label' => 'Download DR (Excel)', 'todo' => ['type' => 'downloadexcel', 'action' => 'downloadexcel', 'lookupclass' => 'downloadexcel', 'access' => 'view']];
        break;
    }

    if ($this->companysetup->getisshowmanual($config['params'])) {
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => 'sj', 'title' => 'SJ_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
    }

    return  $buttons;
  } // createHeadbutton

  public function createtab2($access,  $config)
  {
    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrycntnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj =  $this->tabClass->createtab($tab, []);
    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' =>  $obj];
    if ($this->companysetup->getistodo($config['params'])) {
      $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrycntnumtodo', 'label' => 'To Do', 'access' => 'view']];
      $objtodo =  $this->tabClass->createtab($tab, []);
      $return['To Do'] = ['icon' => 'fa fa-list', 'tab' =>  $objtodo];
    }
    return  $return;
  }


  public function createTab($access,  $config)
  {
    $column = ['action', 'barcode', 'address', 'customer', 'isqty3', 'isqty2', 'isqty', 'prevqty', 'serial', 'specs', 'isemail'];
    foreach ($column as $key => $value) {
      $$value = $key;
    }

    $acctgcolumn = []; // accounting column

    $headgridbtns = ['viewdistribution'];
    $computefield = [];

    $tab = [


      $this->gridname => [ // meter reading
        'gridcolumns' => $column,
        'headgridbtns' => $headgridbtns
      ]
    ];
    $stockbuttons = ['save', 'delete'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    // 
    // set for accounting tab here 
    // 




    // METER READING
    $obj[0][$this->gridname]['label'] = 'METER READING';
    $obj[0][$this->gridname]['descriptionrow'] = [];

    if (!$access['changeamt']) {
      $obj[0][$this->gridname]['columns'][$isamt]['readonly'] = true;
    }

    $obj[0][$this->gridname]['columns'][$barcode]['label'] = 'Meter No';
    $obj[0][$this->gridname]['columns'][$barcode]['style'] = 'width:90px;whiteSpace: normal;min-width:90px;';
    $obj[0][$this->gridname]['columns'][$address]['label'] = 'Meter Address';
    $obj[0][$this->gridname]['columns'][$customer]['label'] = 'Name';

    $obj[0][$this->gridname]['columns'][$barcode]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$address]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$customer]['type'] = 'label';

    $obj[0][$this->gridname]['columns'][$isqty3]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][$isqty2]['readonly'] = true;

    $obj[0][$this->gridname]['columns'][$isqty]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][$isqty]['readonly'] = true;

    $obj[0][$this->gridname]['columns'][$prevqty]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][$prevqty]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$prevqty]['style'] = 'width:90px;whiteSpace: normal;min-width:90px;';

    $obj[0][$this->gridname]['columns'][$serial]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][$serial]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$serial]['style'] = 'width:90px;whiteSpace: normal;min-width:90px;';
    $obj[0][$this->gridname]['columns'][$serial]['align'] = 'text-right';

    $obj[0][$this->gridname]['columns'][$specs]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][$specs]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$specs]['style'] = 'width:90px;whiteSpace: normal;min-width:90px;';
    $obj[0][$this->gridname]['columns'][$specs]['align'] = 'text-right';

    $obj[0][$this->gridname]['columns'][$isqty2]['label'] = 'Previous Reading';
    $obj[0][$this->gridname]['columns'][$isqty3]['label'] = 'Present Reading';
    $obj[0][$this->gridname]['columns'][$isqty]['label'] = 'Consumption';
    $obj[0][$this->gridname]['columns'][$prevqty]['label'] = 'Prev';
    $obj[0][$this->gridname]['columns'][$serial]['label'] = 'Diff';
    $obj[0][$this->gridname]['columns'][$specs]['label'] = '%';

    $obj[0][$this->gridname]['columns'][$isemail]['readonly'] = true;

    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);

    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['saveitem'];
    $obj =  $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['label'] = 'SAVE';
    return  $obj;
  }

  public function createHeadField($config)
  {
    $companyid =  $config['params']['companyid'];
    $fields = ['docno', 'dprojectname', 'sdate1', 'dvattype'];
    $col1 =  $this->fieldClass->create($fields);
    data_set($col1, 'docno.label', 'Transaction#');

    $fields = ['dateid', 'due', 'sdate2'];
    $col2 =  $this->fieldClass->create($fields);

    $fields = ['rem'];
    $col3 =  $this->fieldClass->create($fields);

    $fields = ['loadmeter'];
    $col4 =  $this->fieldClass->create($fields);
    return array('col1' =>  $col1, 'col2' =>  $col2, 'col3' =>  $col3, 'col4' =>  $col4);
  }



  public function createnewtransaction($docno,  $params)
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] =  $docno;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['due'] = null;
    $data[0]['sdate1'] = $this->othersClass->getCurrentDate();
    $data[0]['sdate2'] = $this->othersClass->getCurrentDate();
    $data[0]['dvattype'] = '';
    $data[0]['tax'] = 0;
    $data[0]['rem'] = '';
    $data[0]['projectid'] = '0';
    $data[0]['vattype'] = 'NON-VATABLE';
    $data[0]['projectcode'] = '';
    $data[0]['projectname'] = '';
    $data[0]['wh'] = $this->companysetup->getwh($params);
    $data[0]['contra'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', [$this->defaultContra]);
    return  $data;
  }

  public function loadheaddata($config)
  {
    $doc =  $config['params']['doc'];
    $trno =  $config['params']['trno'];
    $center =  $config['params']['center'];
    $companyid =  $config['params']['companyid'];
    $tablenum =  $this->tablenum;
    if ($trno == 0) {
      $trno =  $this->othersClass->readprofile('TRNO',  $config);
      if ($trno == '') {
        $trno =  $this->coreFunctions->datareader(" select trno as value
    from " .  $this->tablenum .  "
    where doc = ? and center = ? and bref <> 'SJS'
    order by trno desc limit 1 ", [$doc,  $center]);
      }
      $config['params']['trno'] =  $trno;
    } else {
      $this->othersClass->checkprofile('TRNO',  $trno,  $config);
    }
    $center =  $config['params']['center'];

    if ($this->companysetup->getistodo($config['params'])) {
      $this->othersClass->checkseendate($config,  $tablenum);
    }

    $head = [];
    $islocked =  $this->othersClass->islocked($config);
    $isposted =  $this->othersClass->isposted($config);
    $table =  $this->head;
    $htable =  $this->hhead;

    $qryselect = "select
         num.center,
         head.trno,
         head.docno,
         left(head.dateid,10) as dateid,
         head.address,
         date_format(head.createdate,'%Y-%m-%d') as createdate,
         head.rem,
         head.tax,
         head.vattype,
         '' as dvattype,
         left(head.due,10) as due,
          head.projectid,
         ifnull(project.name,'') as projectname,
         ifnull(project.code,'') as projectcode,
         '' as dprojectname,
         head.sdate1, head.sdate2,
         head.contra,
         warehouse.client as wh
         ";

    $qry =  $qryselect .  " from $table as head
    left join $tablenum as num on num.trno = head.trno
    left join client on head.client = client.client
    left join projectmasterfile as project on project.line = head.projectid
    left join client as warehouse on warehouse.client = head.wh
    where head.trno = ? and num.doc = ? and num.center = ?
    union all " .  $qryselect .  " from $htable as head
    left join $tablenum as num on num.trno = head.trno
    left join client on head.clientid = client.clientid
    left join projectmasterfile as project on project.line = head.projectid
    left join client as warehouse on warehouse.clientid = head.whid
    where head.trno = ? and num.doc = ? and num.center = ? ";

    $head =  $this->coreFunctions->opentable($qry, [$trno,  $doc,  $center,  $trno,  $doc,  $center]);

    if (!empty($head)) {
      $stock =  $this->openstock($trno,  $config);
      $viewdate =  $this->othersClass->getCurrentTimeStamp();
      $viewby =  $config['params']['user'];
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg =  $config['msg'];
      }
      $this->coreFunctions->sbcupdate($this->head, ['viewdate' =>  $viewdate, 'viewby' =>  $viewby], ['trno' =>  $trno]);


      $hideobj = [];
      if ($this->companysetup->getistodo($config['params'])) {
        $btndonetodo =  $this->othersClass->checkdonetodo($config,  $tablenum);
        $hideobj = ['donetodo' => !$btndonetodo];
      }
      if ($isposted) {
        $hideobj['loadmeter'] = true;
      } else {
        $hideobj['loadmeter'] = false;
      }

      return  [
        'head' =>  $head,
        'griddata' => ['inventory' =>  $stock],
        'islocked' =>  $islocked,
        'isposted' =>  $isposted,
        'isnew' => false,
        'status' => true,
        'msg' =>  $msg,
        'hideobj' =>  $hideobj
      ];
    } else {
      $head[0]['trno'] = 0;
      $head[0]['docno'] = '';
      return ['status' => false, 'isnew' => true, 'head' =>  $head, 'griddata' => ['inventory' => []], 'msg' => 'Data Head Fetched Failed'];
    }
  }


  public function updatehead($config,  $isupdate)
  {
    $head =  $config['params']['head'];
    $companyid =  $config['params']['companyid'];
    $data = [];
    $info = [];
    if ($isupdate) {
      unset($this->fields[1]);
      unset($head['docno']);
    }

    foreach ($this->fields as  $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] =  $head[$key];
        if (!in_array($key,  $this->except)) {
          $data[$key] =  $this->othersClass->sanitizekeyfield($key,  $data[$key], '',  $companyid);
        } //end if
      }
    }

    $data['editdate'] =  $this->othersClass->getCurrentTimeStamp();
    $data['editby'] =  $config['params']['user'];

    if ($isupdate) {
      $this->coreFunctions->sbcupdate($this->head,  $data, ['trno' =>  $head['trno']]);
    } else {
      $data['doc'] =  $config['params']['doc'];
      $data['createdate'] =  $this->othersClass->getCurrentTimeStamp();
      $data['createby'] =  $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head,  $data);
      $this->logger->sbcwritelog($head['trno'],  $config, 'CREATE',  $head['docno']);
    }
  } // end function

  public function deletetrans($config)
  {
    $trno =  $config['params']['trno'];
    $doc =  $config['params']['doc'];
    $table =  $config['docmodule']->tablenum;
    $docno =  $this->coreFunctions->getfieldvalue($table, 'docno', 'trno=?', [$trno]);
    $trno2 =  $this->coreFunctions->getfieldvalue($table, 'trno', 'doc=? and trno<?', [$doc,  $trno]);
    $this->deleteallitem($config);
    $this->coreFunctions->execqry('delete from ' .  $this->head .  " where trno = ? ", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' .  $table .  " where trno = ? ", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from stockinfo where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from delstatus where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from cntnuminfo where trno=?', 'delete', [$trno]);
    $this->othersClass->deleteattachments($config);
    $this->logger->sbcdel_log($trno,  $config,  $docno);
    return ['trno' =>  $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  public function posttrans($config)
  {
    $trno =  $config['params']['trno'];

    $checkacct = $this->othersClass->checkcoaacct(['AR1', 'SA1', 'ARSC', 'SAINT']);
    if ($checkacct != '') {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Accounts not yet setup:' . $checkacct];
    }

    $projectid = $this->coreFunctions->getfieldvalue($this->head, "projectid", "trno=?", [$trno], '', true);
    $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=" . $trno . " and projectid<>" . $projectid);

    if (!$this->createdistribution($config)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. Problems in creating accounting entries.'];
    } else {

      return  $this->othersClass->posttranstock($config);
    }
  } //end function

  public function unposttrans($config)
  {
    return  $this->othersClass->unposttranstock($config);
  } //end function

  private function getstockselect($config)
  {
    // diff ang serial
    // % specs
    $sqlselect = "select 
    head.trno, 
    ifnull(stock.line,0) as line,
    stock.uom,
    stock.sortline,
    FORMAT(ifnull(stock.isamt,0)," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as isamt,
    FORMAT(ifnull(stock.isqty3,0)," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as isqty3,
    FORMAT(ifnull(stock.isqty2,0)," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as isqty2,
    FORMAT(ifnull(stock.isqty,0)," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as isqty,
    FORMAT(ifnull(stock.prevqty,0)," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as prevqty,
    FORMAT(ifnull(stock.isqty-stock.prevqty,0)," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as serial,
    FORMAT(ifnull(((stock.isqty-stock.prevqty)/stock.isqty) * 100,0)," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as specs,
    left(stock.encodeddate,10) as encodeddate,
    stock.rem,
    ifnull(p.line,0) as projectid,
    item.itemid,
    item.barcode,
    item.shortname as address,
    item.clientid,
    client.clientname as customer,
    stock.whid,
    warehouse.client as wh,
    warehouse.clientname as whname,
    stock.suppid,
    '' as bgcolor,
    case when stock.isemail=0 then 'false' else 'true' end as isemail,
    case when ifnull(stock.void,0)=0 then '' else 'bg-red-2' end as errcolor ";
    return $sqlselect;
  }

  public function openstock($trno, $config)
  {

    $projectid = $this->coreFunctions->datareader("select projectid as value from " . $this->head . " as h where h.trno=? union all select projectid as value from " . $this->hhead . " as h where h.trno=?", [$trno, $trno], '', true);

    $dateid = $this->othersClass->getCurrentDate();

    if (!$this->othersClass->isposted($config)) {
      $dateid = $this->coreFunctions->getfieldvalue($this->head, "sdate1", "trno=?", [$trno]);
    }

    $sqlselect = $this->getstockselect($config);



    $qry = $sqlselect . "
    FROM $this->head as head
    left join projectmasterfile as p on p.line = head.projectid
    left join $this->stock as stock on stock.trno=head.trno  and stock.projectid=p.line
    left join item on stock.itemid = item.itemid
    left join client on client.clientid = stock.suppid
    left join client as warehouse on warehouse.clientid=stock.whid
    where stock.trno =? and p.line=?
    UNION ALL  
    " . $sqlselect . "
    FROM $this->hhead as head
    left join projectmasterfile as p on p.line = head.projectid
    left join $this->hstock as stock on stock.trno=head.trno and stock.projectid=p.line
    left join item on stock.itemid = item.itemid
    left join client on client.clientid = stock.suppid
    left join client as warehouse on warehouse.clientid=stock.whid
    where stock.trno =? and p.line=?
    order by address, customer, barcode";

    $stock = $this->coreFunctions->opentable($qry, [$trno, $projectid, $trno, $projectid]);
    return $stock;
  } //end function

  public function openstockline($config)
  {
    $companyid = $config['params']['companyid'];
    $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);

    $sqlselect = $this->getstockselect($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = $sqlselect . ", '' as bgcolor
    from $this->head as head
    left join $this->stock as stock on stock.trno = head.trno
    left join projectmasterfile as p on p.line = head.projectid
    left join item on item.itemid=stock.itemid
    left join client on client.clientid = stock.suppid
    left join client as warehouse on warehouse.clientid=stock.whid
    where stock.trno = ? and stock.line = ? ";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $stock;
  } // end function

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'additem':
        $return =   $this->additem('insert',  $config);
        if ($return['status'] == true) {
          $this->othersClass->getcreditinfo($config,  $this->head);
        }
        return  $return;
        break;

      case 'addallitem':
        return  $this->addallitem($config);
        break;
      case 'quickadd':
        return  $this->quickadd($config);
        break;
      case 'deleteallitem':
        return  $this->deleteallitem($config);
        break;
      case 'deleteitem':
        return  $this->deleteitem($config);
        break;
      case 'saveitem': //save all item edited
        return  $this->updateitem($config);
        break;
      case 'saveperitem':
        return  $this->updateperitem($config);
        break;
      case 'getsosummary':
        if ($this->companysetup->getserial($config['params'])) {
          return  $this->getsosummaryserial($config);
        } else {
          return  $this->getsosummary($config);
        }
        break;
      case 'getsodetails':
        if ($this->companysetup->getserial($config['params'])) {
          return  $this->getsodetailsserial($config);
        } else {
          return  $this->getsodetails($config);
        }
        break;
      case 'getsqsummary':
        return  $this->getsqsummary($config);
        break;
      case 'getsqdetails':
        return  $this->getsqdetails($config);
        break;
      case 'refreshso':
        $data =  $this->sqlquery->getpendingsodetailsperpallet($config);
        return ['status' => true, 'msg' => 'Refresh Data', 'data' =>  $data];
        break;
      case 'getserialout':
        return  $this->getserialout($config);
        break;
      default:
        return ['status' => false, 'msg' => 'Please check stockstatus (' .  $config['params']['action'] . ')'];
        break;
    }
  }

  public function diagram($config)
  {
    $data = [];
    $nodes = [];
    $links = [];
    $data['width'] = 1650;
    $startx = 100;
    $a = 0;

    $qry =  " select so . trno, so . docno, left (so . dateid, 10) as dateid,
    CAST (concat ('Total SO Amt: ', round (sum (s . ext), 2)) as CHAR) as rem
    from hsohead as so
    left join hsostock as s on s . trno = so . trno
    left join glstock as sstock on sstock . refx = s . trno and sstock . linex = s . line
    where sstock . trno = ?
    group by so . trno, so . docno, so . dateid ";
    $t =  $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
    if (!empty($t)) {
      $startx = 550;
      foreach ($t as  $key =>  $value) {
        //SO
        data_set(
          $nodes,
          $t[$key]->docno,
          [
            'align' => 'right',
            'x' => 200,
            'y' => 50 +  $a,
            'w' => 250,
            'h' => 80,
            'type' =>  $t[$key]->docno,
            'label' =>  $t[$key]->rem,
            'color' => 'blue',
            'details' => [$t[$key]->dateid]
          ]
        );
        array_push($links, ['from' =>  $t[$key]->docno, 'to' => 'sj']);
        $a =  $a + 100;
      }
    }

    //SJ
    $qry =  "
    select head . docno,
    date (head . dateid) as dateid,
    CAST (concat ('Total SJ Amt: ', round (sum (stock . ext), 2), if (head . ms_freight <> 0, concat('\rOther Charges: ', round(head . ms_freight, 2)), ''), '\r\r', 'Balance: ', round (ar . bal, 2)) as CHAR) as rem,
    head . trno
    from glhead as head
    left join glstock as stock on head . trno = stock . trno
    left join arledger as ar on ar . trno = head . trno
    where head . trno = ?
    group by head . docno, head . dateid, head . trno, ar . bal, head . ms_freight ";
    $t =  $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
    if (!empty($t)) {
      data_set(
        $nodes,
        'sj',
        [
          'align' => 'left',
          'x' =>  $startx,
          'y' => 100,
          'w' => 400,
          'h' => 80,
          'type' =>  $t[0]->docno,
          'label' =>  $t[0]->rem,
          'color' => 'green',
          'details' => [$t[0]->dateid]
        ]
      );

      foreach ($t as  $key =>  $value) {
        //CR
        $sjtrno =  $t[$key]->trno;
        $crqry =  "
    select  head . docno, date (head . dateid) as dateid, head . trno,
    CAST (concat ('Applied Amount: ', round (detail . db + detail . cr, 2)) as CHAR) as rem
    from glhead as head
    left join gldetail as detail on head . trno = detail . trno
    where detail . refx = ? and head . doc = 'CR'
    union all
    select  head . docno, date (head . dateid) as dateid, head . trno,
    CAST (concat ('Applied Amount: ', round (detail . db + detail . cr, 2)) as CHAR) as rem
    from lahead as head
    left join ladetail as detail on head . trno = detail . trno
    where detail . refx = ? and head . doc = 'CR' ";
        $crdata =  $this->coreFunctions->opentable($crqry, [$sjtrno,  $sjtrno]);
        if (!empty($crdata)) {
          foreach ($crdata as  $key2 =>  $value2) {
            data_set(
              $nodes,
              'cr',
              [
                'align' => 'left',
                'x' =>  $startx + 400,
                'y' => 100,
                'w' => 250,
                'h' => 80,
                'type' =>  $crdata[$key2]->docno,
                'label' =>  $crdata[$key2]->rem,
                'color' => 'red',
                'details' => [$crdata[$key2]->dateid]
              ]
            );
            array_push($links, ['from' => 'sj', 'to' => 'cr']);
            $a =  $a + 100;
          }
        }

        //CM
        $cmqry =  "
    select head . docno as docno, left (head . dateid, 10) as dateid,
    CAST (concat ('Total CM Amt: ', round (sum (stock . ext), 2)) as CHAR) as rem
    from glhead as head
    left join glstock as stock on stock . trno = head . trno
    left join item on item . itemid = stock . itemid
    where stock . refx = ? and head . doc = 'CM'
    group by head . docno, head . dateid
    union all
    select head . docno as docno, left (head . dateid, 10) as dateid,
    CAST (concat ('Total CM Amt: ', round (sum (stock . ext), 2)) as CHAR) as rem
    from lahead as head
    left join lastock as stock on stock . trno = head . trno
    left join item on item . itemid = stock . itemid
    where stock . refx = ? and head . doc = 'CM'
    group by head . docno, head . dateid ";
        $cmdata =  $this->coreFunctions->opentable($cmqry, [$sjtrno,  $sjtrno]);
        if (!empty($cmdata)) {
          foreach ($cmdata as  $key2 =>  $value2) {
            data_set(
              $nodes,
              $cmdata[$key2]->docno,
              [
                'align' => 'left',
                'x' =>  $startx + 400,
                'y' => 200,
                'w' => 250,
                'h' => 80,
                'type' =>  $cmdata[$key2]->docno,
                'label' =>  $cmdata[$key2]->rem,
                'color' => 'red',
                'details' => [$cmdata[$key2]->dateid]
              ]
            );
            array_push($links, ['from' => 'sj', 'to' =>  $cmdata[$key2]->docno]);
            $a =  $a + 100;
          }
        }
      }
    }
    $data['nodes'] =  $nodes;
    $data['links'] =  $links;

    return ['status' => true, 'msg' => 'Successfully fetched.', 'data' =>  $data];
  }

  public function diagram_aftech($config)
  {

    $data = [];
    $nodes = [];
    $links = [];
    $data['width'] = 1500;
    $startx = 100;

    $qry =  " select head . trno, head . docno, left (head . dateid, 10) as dateid,
    CAST (concat ('Total OP Amt: ', round (sum (s . ext), 2)) as CHAR) as rem, s . refx
    from hophead as head
    left join hopstock as s on s . trno = head . trno
    left join hqsstock as qtstock on qtstock . refx = s . trno and s . line = qtstock . linex
    left join hqshead as qthead on qthead . trno = qtstock . trno
    left join hsqhead as sohead on sohead . trno = qthead . sotrno
    left join glstock as glstock on glstock . refx = qthead . trno
    where glstock . trno = ?
    group by head . trno, head . docno, head . dateid, s . refx ";
    $t =  $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
    $a = 0;
    if (!empty($t)) {
      $startx = 550;

      foreach ($t as  $key =>  $value) {
        //qs quotation 
        data_set(
          $nodes,
          $t[$key]->docno,
          [
            'align' => 'right',
            'x' => 100,
            'y' => 50 +  $a,
            'w' => 250,
            'h' => 80,
            'type' =>  $t[$key]->docno,
            'label' =>  $t[$key]->rem,
            'color' => '#88DDFF',
            'details' => [$t[$key]->dateid]
          ]
        );
        array_push($links, ['from' =>  $t[$key]->docno, 'to' => 'qt']);
        $a =  $a + 100;


        // quotation
        $qry =  "
    select head . docno, left (head . dateid, 10) as dateid,
    CAST (concat ('Total QS Amt: ', round (sum (s . ext), 2)) as CHAR) as rem
    from hqshead as head
    left join hqsstock as s on s . trno = head . trno
    left join glstock as glstock on glstock . refx = head . trno
    where glstock . trno = ?
    group by head . docno, head . dateid ";
        $x =  $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
        $poref =  $t[$key]->docno;
        if (!empty($x)) {
          foreach ($x as  $key2 =>  $value) {
            data_set(
              $nodes,
              'qt',
              [
                'align' => 'left',
                'x' => 500,
                'y' => 50 +  $a,
                'w' => 250,
                'h' => 80,
                'type' =>  $x[$key2]->docno,
                'label' =>  $x[$key2]->rem,
                'color' => '#ff88dd',
                'details' => [$x[$key2]->dateid]
              ]
            );
            array_push($links, ['from' => 'qt', 'to' => 'so']);
            $a =  $a + 100;
          }
        }


        // SO
        $qry =  "
    select head . docno, left (head . dateid, 10) as dateid,
    CAST (concat ('Total SO Amt: ', round (sum (s . ext), 2)) as CHAR) as rem
    from sqhead as head
    left join hqshead as qthead on qthead . sotrno = head . trno
    left join hqsstock as s on s . trno = qthead . trno
    left join glstock as glstock on glstock . refx = qthead . trno
    where glstock . trno = ?
    group by head . docno, head . dateid
    union all
    select head . docno, left (head . dateid, 10) as dateid,
    CAST (concat ('Total SO Amt: ', round (sum (s . ext), 2)) as CHAR) as rem
    from hsqhead as head
    left join hqshead as qthead on qthead . sotrno = head . trno
    left join hqsstock as s on s . trno = qthead . trno
    left join glstock as glstock on glstock . refx = qthead . trno
    where glstock . trno = ?
    group by head . docno, head . dateid ";
        $sodata =  $this->coreFunctions->opentable($qry, [$config['params']['trno'],  $config['params']['trno']]);
        if (!empty($sodata)) {
          foreach ($sodata as  $sodatakey =>  $value) {
            data_set(
              $nodes,
              'so',
              [
                'align' => 'left',
                'x' => 600,
                'y' => 100 +  $a,
                'w' => 250,
                'h' => 80,
                'type' =>  $sodata[$sodatakey]->docno,
                'label' =>  $sodata[$sodatakey]->rem,
                'color' => 'blue',
                'details' => [$sodata[$sodatakey]->dateid]
              ]
            );
            array_push($links, ['from' => 'so', 'to' => 'sj']);
            $a =  $a + 100;
          }
        }
      }
    }

    //SJ
    $qry =  "
    select sjhead . docno,
    date (sjhead . dateid) as dateid,
    CAST (concat ('Total SJ Amt: ', round (sum (sjstock . ext), 2), ' - ', 'Balance: ', round (ar . bal, 2)) as CHAR) as rem,
    sjhead . trno
    from hqshead as head
    left join hqsstock as stock on stock . trno = head . trno
    left join hsqhead as sohead on sohead . trno = head . sotrno
    left join glstock as sjstock on sjstock . refx = stock . trno and sjstock . linex = stock . line
    left join glhead as sjhead on sjhead . trno = sjstock . trno
    left join arledger as ar on ar . trno = sjhead . trno
    where sjhead . trno = ? and sjhead . docno is not null
    group by sjhead . docno, sjhead . dateid, ar . bal, sjhead . trno
    union all
    select sjhead . docno,
    date (sjhead . dateid) as dateid,
    CAST (concat ('Total SJ Amt: ', round (sum (sjstock . ext), 2), ' - ', 'Balance: ', round (sum (sjstock . ext), 2)) as CHAR) as rem,
    sjhead . trno
    from hqshead as head
    left join hqsstock as stock on stock . trno = head . trno
    left join hsqhead as sohead on sohead . trno = head . sotrno
    left join lastock as sjstock on sjstock . refx = stock . trno and sjstock . linex = stock . line
    left join lahead as sjhead on sjhead . trno = sjstock . trno
    where sjhead . trno = ? and sjhead . docno is not null
    group by sjhead . docno, sjhead . dateid, sjhead . trno ";
    $t =  $this->coreFunctions->opentable($qry, [$config['params']['trno'],  $config['params']['trno']]);
    if (!empty($t)) {
      data_set(
        $nodes,
        'sj',
        [
          'align' => 'left',
          'x' => 450 +  $startx,
          'y' => 300,
          'w' => 250,
          'h' => 80,
          'type' =>  $t[0]->docno,
          'label' =>  $t[0]->rem,
          'color' => 'green',
          'details' => [$t[0]->dateid]
        ]
      );

      foreach ($t as  $key =>  $value) {
        //CR
        $rrtrno =  $t[$key]->trno;
        $apvqry =  "
    select  head . docno, date (head . dateid) as dateid, head . trno,
    CAST (concat ('Applied Amount: ', round (detail . db + detail . cr, 2)) as CHAR) as rem
    from glhead as head
    left join gldetail as detail on head . trno = detail . trno
    where detail . refx = ?
    union all
    select  head . docno, date (head . dateid) as dateid, head . trno,
    CAST (concat ('Applied Amount: ', round (detail . db + detail . cr, 2)) as CHAR) as rem
    from lahead as head
    left join ladetail as detail on head . trno = detail . trno
    where detail . refx = ? ";
        $apvdata =  $this->coreFunctions->opentable($apvqry, [$rrtrno,  $rrtrno]);
        if (!empty($apvdata)) {
          foreach ($apvdata as  $key2 =>  $value2) {
            data_set(
              $nodes,
              'cr',
              [
                'align' => 'left',
                'x' =>  $startx + 800,
                'y' => 100,
                'w' => 250,
                'h' => 80,
                'type' =>  $apvdata[$key2]->docno,
                'label' =>  $apvdata[$key2]->rem,
                'color' => '#6D50E8',
                'details' => [$apvdata[$key2]->dateid]
              ]
            );
            array_push($links, ['from' => 'sj', 'to' => 'cr']);
            $a =  $a + 100;
          }
        }

        //CM
        $dmqry =  "
    select head . docno as docno, left (head . dateid, 10) as dateid,
    CAST (concat ('Total CM Amt: ', round (sum (stock . ext), 2)) as CHAR) as rem
    from glhead as head
    left join glstock as stock on stock . trno = head . trno
    left join item on item . itemid = stock . itemid
    where stock . refx = ?
    group by head . docno, head . dateid
    union all
    select head . docno as docno, left (head . dateid, 10) as dateid,
    CAST (concat ('Total CM Amt: ', round (sum (stock . ext), 2)) as CHAR) as rem
    from lahead as head
    left join lastock as stock on stock . trno = head . trno
    left join item on item . itemid = stock . itemid
    where stock . refx = ?
    group by head . docno, head . dateid ";
        $dmdata =  $this->coreFunctions->opentable($dmqry, [$rrtrno,  $rrtrno]);
        if (!empty($dmdata)) {
          foreach ($dmdata as  $key2 =>  $value2) {
            data_set(
              $nodes,
              $dmdata[$key2]->docno,
              [
                'align' => 'left',
                'x' =>  $startx + 800,
                'y' => 300,
                'w' => 250,
                'h' => 80,
                'type' =>  $dmdata[$key2]->docno,
                'label' =>  $dmdata[$key2]->rem,
                'color' => 'red',
                'details' => [$dmdata[$key2]->dateid]
              ]
            );
            array_push($links, ['from' => 'sj', 'to' =>  $dmdata[$key2]->docno]);
            $a =  $a + 100;
          }
        }
      }
    }

    $data['nodes'] =  $nodes;
    $data['links'] =  $links;

    return ['status' => true, 'msg' => 'Successfully fetched.', 'data' =>  $data];
  }

  public function stockstatusposted($config)
  {
    $action =  $config['params']['action'];
    if ($action == 'stockstatusposted') {
      $action =  $config['params']['lookupclass'];
    }

    switch ($action) {
      case 'diagram':
        switch ($config['params']['companyid']) {
          case 10: //afti
            return  $this->diagram_aftech($config);
            break;
          default:
            return  $this->diagram($config);
            break;
        }
        break;
      case 'batchpostsj':
        return  $this->batchpostsj($config);
        break;
      case 'navigation':
        return  $this->othersClass->navigatedocno($config);
        break;
      case 'makepayment':
        return  $this->othersClass->generateShortcutTransaction($config, 0, 'SJCR');
        break;
      case 'donetodo':
        $tablenum =  $this->tablenum;
        return  $this->othersClass->donetodo($config,  $tablenum);
        break;
      case 'downloadexcel':
        return  $this->downloadexcel($config);
        break;
      case 'loadmeter':
        return $this->loadmeter($config);
        break;
      case 'surcharge':
        return $this->distributesurcharge($config);
        break;
      default:
        return ['status' => false, 'msg' => 'Please check stockstatusposted (' .  $config['params']['action'] . ')'];
        break;
    }
  }

  private function distributesurcharge($config)
  {

    $posted = $this->othersClass->isposted($config);
    if (!$posted) {
      return ['status' => true, 'msg' => 'For posted transaction only'];
    }

    $line = $this->coreFunctions->datareader("select line as value from gldetail where trno=" . $config['params']['trno'] . " order by line desc");

    $qry = "select client.client, client.clientname, client.clientid, h.dateid, pm.surcharge, h.cur, h.forex, h.projectid, month(h.dateid) as mon, DATE_FORMAT(h.dateid ,'%Y-%m-01') as enddate from glhead as h left join glstock as s on s.trno=h.trno 
            left join client on client.clientid=s.suppid left join projectmasterfile as pm on pm.line = h.projectid
            where h.trno=? group by client.client, client.clientname, client.clientid, h.dateid, pm.surcharge, h.cur, h.forex, h.projectid, month(h.dateid)";
    $scharge = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
    $this->coreFunctions->LogConsole("count:" . count($scharge));
    foreach ($scharge as $key => $value) {
      $surcharge = 0;

      $arbalSCqry = "select ifnull(sum(bal),0) as value from (
          select detail.db-detail.cr AS bal 
          from gldetail detail left join glhead head on head.trno = detail.trno left join coa on coa.acnoid = detail.acnoid 
          left join client dclient on dclient.clientid = detail.clientid left join client on client.clientid = head.clientid 
          where date(head.dateid)<'" . $scharge[$key]->enddate . "' and dclient.clientid=" . $scharge[$key]->clientid . " and left(coa.alias,2)='AR') as bal";

      $arbal = $this->coreFunctions->datareader($arbalSCqry, [], '', true);

      if ($scharge[$key]->surcharge != 0) {

        $this->othersClass->logConsole($scharge[$key]->client . '-' . $scharge[$key]->clientname . ": " . $arbal);

        if ($arbal > 0) {

          $surcharge = $arbal * ($scharge[$key]->surcharge / 100);

          if ($scharge[$key]->forex == 0) $scharge[$key]->forex = 1;

          $current_timestamp = $this->othersClass->getCurrentTimeStamp();

          $line =  $line + 1;
          $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['ARSC']);
          $entry = ['trno' => $config['params']['trno'], 'line' => $line, 'acnoid' => $acnoid, 'clientid' => $scharge[$key]->clientid, 'db' => round(($surcharge * $scharge[$key]->forex), 2), 'cr' => 0, 'postdate' => $scharge[$key]->dateid, 'cur' => $scharge[$key]->cur, 'forex' => $scharge[$key]->forex, 'fdb' => floatval($scharge[$key]->forex) == 1 ? 0 : $scharge[$key]->dateid, 'fcr' => 0, 'projectid' => $scharge[$key]->projectid];
          $entry['editdate'] = $current_timestamp;
          $entry['editby'] = $config['params']['user'];
          $entry['encodeddate'] = $current_timestamp;
          $entry['encodedby'] = $config['params']['user'];
          $exists = $this->coreFunctions->getfieldvalue("gldetail", "trno", "trno=? and clientid=? and acnoid=?", [$config['params']['trno'], $scharge[$key]->clientid, $acnoid], '', true);
          if ($exists != 0) {
            $this->coreFunctions->execqry("delete from gldetail where trno=? and clientid=? and acnoid=?", 'delete', [$config['params']['trno'], $scharge[$key]->clientid, $acnoid]);
            $this->coreFunctions->execqry("delete from arledger where trno=? and clientid=? and acnoid=? and bal=db", 'delete', [$config['params']['trno'], $scharge[$key]->clientid, $acnoid]);
          }
          if ($this->coreFunctions->sbcinsert($this->hdetail, $entry) == 1) {

            $qry = "
              insert into arledger(dateid,trno,line,acnoid,clientid,db,cr,bal,docno,ref,agentid,fdb,fcr,forex)
              select d.postdate,d.trno,line,coa.acnoid,ifNull(client.clientid,0),round(d.db,2),round(d.cr,2),round(d.db+d.cr,2) as bal,
              head.docno,d.ref,d.agentid,d.fdb,d.fcr,d.forex
              from glhead as head
              left join gldetail as d on head.trno=d.trno
              left join coa on coa.acnoid=d.acnoid
              left join client on client.clientid=d.clientid
              left join client as agent on agent.clientid=d.agentid
              where left(coa.alias,2)='AR' and d.trno=" . $config['params']['trno'] . " and d.refx=0 and d.line=" . $line;

            $this->coreFunctions->execqry($qry);

            $this->logger->sbcwritelog($config['params']['trno'], $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION SUCCESS');
          } else {
            $this->logger->sbcwritelog($config['params']['trno'], $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION FAILED');
          }

          $line =  $line + 1;
          $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['SAINT']);
          $entry = ['trno' => $config['params']['trno'], 'line' => $line, 'acnoid' => $acnoid, 'clientid' => $scharge[$key]->clientid, 'db' => 0, 'cr' => round(($surcharge * $scharge[$key]->forex), 2), 'postdate' => $scharge[$key]->dateid, 'cur' => $scharge[$key]->cur, 'forex' => $scharge[$key]->forex, 'fdb' => floatval($scharge[$key]->forex) == 1 ? 0 : $scharge[$key]->dateid, 'fcr' => 0, 'projectid' => $scharge[$key]->projectid];
          $entry['editdate'] = $current_timestamp;
          $entry['editby'] = $config['params']['user'];
          $entry['encodeddate'] = $current_timestamp;
          $entry['encodedby'] = $config['params']['user'];
          $exists = $this->coreFunctions->getfieldvalue("gldetail", "trno", "trno=? and clientid=? and acnoid=?", [$config['params']['trno'], $scharge[$key]->clientid, $acnoid], '', true);
          if ($exists != 0) $this->coreFunctions->execqry("delete from gldetail where trno=? and clientid=? and acnoid=?", 'delete', [$config['params']['trno'], $scharge[$key]->clientid, $acnoid]);
          if ($this->coreFunctions->sbcinsert($this->hdetail, $entry) == 1) {
            $this->logger->sbcwritelog($config['params']['trno'], $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION SUCCESS');
          } else {
            $this->logger->sbcwritelog($config['params']['trno'], $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION FAILED');
          }
        }
      }
    }

    return ['status' => true, 'msg' => 'Finished inserting surcharge'];
  }

  private function loadmeter($config)
  {
    $posted =  $this->othersClass->isposted($config);
    if ($posted) {
      return ['status' => false, 'reloaddata' => true, 'msg' => 'Already posted', 'griddata' => ['inventory' => []]];
    }
    $trno = $config['params']['trno'];

    $startdate = $this->coreFunctions->getfieldvalue($this->head, "dateid", "trno=?", [$trno]);
    $projectid = $this->coreFunctions->getfieldvalue($this->head, "projectid", "trno=?", [$trno]);

    if ($projectid == 0) {
      return ['status' => false, 'reloaddata' => true, 'msg' => 'Project is Required', 'griddata' => ['inventory' => []]];
    }



    $qry = "select itemid, uom, proj.rate, item.clientid from item left join projectmasterfile as proj on proj.line=item.projectid where projectid = ? and clientid <> 0 and isinactive=0";
    $items =  $this->coreFunctions->opentable($qry, [$projectid]);


    foreach ($items as $key => $data) {

      $qryx = "select ifnull(isqty3,0) as isqty3, ifnull(isqty,0) as isqty from (
          select s.itemid, h.dateid, s.isqty3, s.isqty from lastock as s left join lahead as h on h.trno=s.trno where h.doc='WM' and s.isqty3<>0 and s.itemid = '$data->itemid' and s.suppid=" . $data->clientid . "
          union all
          select s.itemid, h.dateid, s.isqty3, s.isqty from glstock as s left join glhead as h on h.trno=s.trno where h.doc='WM' and s.isqty3<>0 and s.itemid = '$data->itemid' and s.suppid=" . $data->clientid . ") as x
          where date(x.dateid) < '$startdate'
          order by x.dateid desc limit 1 ";
      $prevreading =  $this->coreFunctions->opentable($qryx);

      $prevqty = 0;

      if (empty($prevreading)) {
        $prevqty = $this->coreFunctions->datareader("select ifnull(h.begqty,0) as value from hwnhead as h left join client on client.client=h.client 
          where h.itemid=" . $data->itemid . " and client.clientid=" . $data->clientid . " and h.disconndate is null order by h.dateid desc limit 1", [], '', true);
      } else {
        $prevqty = (isset($prevreading[0]->isqty3) ? $prevreading[0]->isqty3 : 0);
      }

      $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$this->companysetup->getwh($config['params'])]);
      $laman = [
        'itemid' => $data->itemid,
        'isamt' => $data->rate,
        'uom' => $data->uom,
        'trno' => $trno,
        'projectid' => $projectid,
        'isqty2' => $prevqty,
        'prevqty' => (isset($prevreading[0]->isqty) ? $prevreading[0]->isqty : 0),
        'whid' => $whid,
        'suppid' => $data->clientid
      ];

      $exist = $this->coreFunctions->opentable("select isqty from " . $this->stock . " where trno=? and itemid=?", [$trno, $data->itemid]);
      if (empty($exist)) {
        $this->coreFunctions->sbcinsert($this->stock, $laman);
      } else {
        $this->othersClass->logConsole('itemid:' . $data->itemid . ' - ' . 'isqty:' . $exist[0]->isqty);
        if ($exist[0]->isqty == 0) {
          $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=? and itemid=?", "delete", [$trno, $data->itemid]);
          $this->coreFunctions->sbcinsert($this->stock, $laman);
        }
      }
    }
    $stock =  $this->openstock($trno,  $config);
    return ['status' => true, 'reloaddata' => true, 'msg' => 'Load Meter Reading', 'griddata' => ['inventory' => $stock]];
  }

  public function forwtinput($config)
  {
    $posted =  $this->othersClass->isposted($config);
    if ($posted) {
      return ['status' => false, 'msg' => 'Already posted'];
    }

    if ($this->coreFunctions->sbcupdate($this->tablenum, ['statid' => 74], ['trno' =>  $config['params']['trno']])) {
      $this->coreFunctions->sbcupdate($this->head, ['lockdate' =>  $this->othersClass->getCurrentTimeStamp(), 'lockuser' =>  $config['params']['user']], ['trno' =>  $config['params']['trno']]);
      $this->logger->sbcwritelog($config['params']['trno'],  $config, 'HEAD', 'FOR WEIGHT INPUT');
      return ['status' => true, 'msg' => 'Successfully updated', 'backlisting' => true];
    } else {
      return ['status' => false, 'msg' => 'Failed to tag approved'];
    }
  }

  public function warehousedone($config)
  {
    $posted =  $this->othersClass->isposted($config);
    if ($posted) {
      return ['status' => false, 'msg' => 'Already posted'];
    }

    $qry =  " select trno from stockinfo where trno = ? and weight2 = 0 limit 1 ";
    $isitemzeroqty =  $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
    if (!empty($isitemzeroqty)) {
      return ['status' => false, 'msg' => 'Failed to tag done, please input actual weight of all items'];
    }

    if ($this->coreFunctions->sbcupdate($this->tablenum, ['statid' => 39], ['trno' =>  $config['params']['trno']])) {
      $this->logger->sbcwritelog($config['params']['trno'],  $config, 'HEAD', 'WAREHOUSE DONE');
      return ['status' => true, 'msg' => 'Successfully updated', 'backlisting' => true];
    } else {
      return ['status' => false, 'msg' => 'Failed to tag approved'];
    }
  }

  private function downloadexcel($config)
  {
    $trno =  $config['params']['trno'];
    $cntnum =  $this->coreFunctions->opentable(" select docno, ifnull (postdate, '') as postdate from cntnum where trno = ? ", [$trno]);
    if (empty($cntnum)) {
      return ['status' => false, 'msg' => 'Failed to download, invalid transaction', 'name' => 'dr', 'data' => []];
    }

    $data =  $this->coreFunctions->opentable(" select item . barcode as `itemcode `, s . uom, s . isqty as `qty `, s . disc, s . isamt as `cost `, s . kgs, s . sortline, s . line from lahead as h left join lastock as s on s . trno = h . trno left join item on item . itemid = s . itemid where h . trno = ?
    union all
    select item . barcode as `itemcode `, s . uom, s . isqty as `qty `, s . disc, s . isamt as `cost `, s . kgs, s . sortline, s . line from glhead as h left join glstock as s on s . trno = h . trno left join item on item . itemid = s . itemid where h . trno = ?
    order by sortline, line ", [$trno,  $trno]);

    $this->logger->sbcwritelog($trno,  $config, 'EXPORT', 'DOWNLOAD EXCEL FILE');
    return ['status' => true, 'msg' =>  $cntnum[0]->docno . ' is ready to Download', 'name' => 'dr', 'data' =>  $data];
  }

  private function batchpostsj($config)
  {
    $msg = '';
    try {
      $curdate =  $this->othersClass->getCurrentDate();
      $sql =  " select trno, docno from lahead where doc = 'SJ' and lockdate is not null and date (dateid) <= ? ";
      $data =  $this->coreFunctions->opentable($sql, [$curdate]);
      foreach ($data as  $key =>  $value) {
        $config['params']['trno'] =  $value->trno;
        $result =  $this->posttrans($config);
        if (!$result['status']) {
          $msg =  $result['msg'];
          goto exithere;
        } else {
          $this->logger->sbcwritelog($value->trno,  $config, 'BATCH POST',  $value->docno);
        }
      }
    } catch (Exception  $ex) {
      $msg =  $ex;
    }
    exithere:
    if ($msg = '') {
      $msg = 'Batch posting was finished';
    }
    return ['status' => 'true', 'msg' =>  $msg];
  }

  public function updateperitem($config)
  {
    $config['params']['data'] =  $config['params']['row'];
    $isupdate =  $this->additem('update',  $config);
    $data =  $this->openstockline($config);
    $msg = '';
    if ($isupdate['msg'] != '') {
      $msg =  $isupdate['msg'];
    }
    if (!$isupdate['status']) {
      $data[0]->errcolor = 'bg-red-2';
      return ['row' =>  $data, 'status' => true, 'msg' =>  $msg];
    } else {
      return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    }
  }


  public function updateitem($config)
  {
    $msg = '';
    foreach ($config['params']['row'] as  $key =>  $value) {
      $config['params']['data'] =  $value;
      $update =  $this->additem('update',  $config);
      if ($msg != '') {
        $msg =  $msg . ' ' .  $update['msg'];
      } else {
        $msg =  $update['msg'];
      }
    }
    $data =  $this->openstock($config['params']['trno'],  $config);
    $data2 = json_decode(json_encode($data), true);
    $isupdate = true;
    $msg1 = '';
    $msg2 = '';

    return ['inventory' =>  $data, 'status' => true, 'msg' =>  $msg];
    //}
  } //end function

  public function addallitem($config)
  {
    $msg = '';
    foreach ($config['params']['row'] as  $key =>  $value) {
      $config['params']['data'] =  $value;
      $row =  $this->additem('insert',  $config);
      if ($msg != '') {
        $msg =  $msg . ' ' .  $row['msg'];
      } else {
        $msg =  $row['msg'];
      }
    }

    $data =  $this->openstock($config['params']['trno'],  $config);
    $status = true;
    return ['inventory' =>  $data, 'status' => true, 'msg' =>  $msg];
  } //end function


  public function quickadd($config)
  {
    $barcodelength =  $this->companysetup->getbarcodelength($config['params']);
    $config['params']['barcode'] = trim($config['params']['barcode']);
    if ($barcodelength == 0) {
      $barcode =  $config['params']['barcode'];
    } else {
      $barcode =  $this->othersClass->padj($config['params']['barcode'],  $barcodelength);
    }

    $wh =  $config['params']['wh'];
    $item =  $this->coreFunctions->opentable(" select item . itemid, item . amt, item . disc, '' as loc, '" . $wh . "' as wh, 1 as qty, uom, '' as expiry from item where barcode = ? ", [$barcode]);
    if (!empty($item)) {
      $config['params']['barcode'] =  $barcode;
      $data =  $this->getlatestprice($config);

      if (!empty($data)) {
        $item[0]->amt =  $data['data'][0]->amt;
        $item[0]->disc =  $data['data'][0]->disc;
        $item[0]->uom =  $data['data'][0]->uom;
      }
      $config['params']['data'] = json_decode(json_encode($item[0]), true);
      return  $this->additem('insert',  $config);
    } else {
      return ['status' => false, 'msg' => 'Barcode not found.', ''];
    }
  }

  // insert and update item
  public function additemxxxx($action,  $config,  $setlog = false)
  {
    $companyid =  $config['params']['companyid'];

    $itemid =  $config['params']['data']['itemid'];
    $trno =  $config['params']['trno'];
    $projectid = $config['params']['data']['projectid'];
    $isqty = $config['params']['data']['isqty'];
    $isqty2 = $config['params']['data']['isqty2'];
    $isqty3 = $config['params']['data']['isqty3'];
    $prevqty = $config['params']['data']['prevqty'];

    $line = 0;

    if ($action == 'insert') {
      $qry =  " select line as value from " .  $this->stock .  " where trno = ? order by line desc limit 1 ";
      $line =  $this->coreFunctions->datareader($qry, [$trno]);
      if ($line == '') {
        $line = 0;
      }
      $line =  $line + 1;
      $config['params']['line'] =  $line;
      $amt = $config['params']['data']['amt'];
      $isqty = $config['params']['data']['qty'];
    } elseif ($action == 'update') {
      $config['params']['line'] =  $config['params']['data']['line'];
      $line =  $config['params']['data']['line'];
      $config['params']['line'] =  $line;
      $amt = $config['params']['data'][$this->damt];
      $isqty = $config['params']['data'][$this->dqty];
    }
    $isqty =  $this->othersClass->sanitizekeyfield('qty',  $isqty); // consumption
    $isqty2 =  $this->othersClass->sanitizekeyfield('qty',  $isqty2); // previous reading
    $isqty3 =  $this->othersClass->sanitizekeyfield('qty',  $isqty3); // present reading
    $prevqty =  $this->othersClass->sanitizekeyfield('qty',  $prevqty); // previous consumption reading

    $qty = $isqty3 - $isqty2;
    if ($isqty2 == 0)  $qty = 0;
    $qty = $this->othersClass->sanitizekeyfield('qty', $qty);

    $computedata = $this->othersClass->computestock($amt, '', $isqty, 1);

    $data = [
      'trno' =>  $trno,
      'line' =>  $line,
      'itemid' =>  $itemid,
      'isqty' =>  $qty,
      'isqty2' =>  $isqty2,
      'isqty3' =>  $isqty3,
      'prevqty' =>  $prevqty,
      'projectid' =>  $projectid,
      'amt' => $computedata['amt'],
      'iss' => $computedata['qty'],
      'ext' => number_format($computedata['ext'], $this->companysetup->getdecimal('currency', $config['params']), '.', '')
    ];

    foreach ($data as  $key =>  $value) {
      $data[$key] =  $this->othersClass->sanitizekeyfield($key,  $data[$key]);
    }
    $item = $this->othersClass->getitemname($itemid);

    $current_timestamp =  $this->othersClass->getCurrentTimeStamp();
    $data['editdate'] =  $current_timestamp;
    $data['editby'] =  $config['params']['user'];

    //insert item
    if ($action == 'insert') {

      $data['encodeddate'] =  $current_timestamp;
      $data['encodedby'] =  $config['params']['user'];

      if (isset($config['params']['data']['sortline'])) {
        $data['sortline'] =   $config['params']['data']['sortline'];
      } else {
        $data['sortline'] =   $data['line'];
      }

      if ($trno == 0 ||  $trno == '') {
        $this->logger->sbcwritelog($trno,  $config, 'STOCK', 'ZERO TRNO (METER READING)');
        return ['status' => false, 'msg' => 'Add item Failed. Zero trno generated'];
      }

      if ($this->coreFunctions->sbcinsert($this->stock,  $data) == 1) {
        $havestock = true;
        $msg = 'Add Meter Success';
        $this->logger->sbcwritelog($trno,  $config, 'STOCK', 'ADD - Line:' .  $line . ' barcode:' .  $item[0]->barcode,  $setlog ?  $this->tablelogs : '');
        $row =  $this->openstockline($config);

        return ['row' =>  $row, 'status' => true, 'msg' =>  $msg];
      } else {
        return ['status' => false, 'msg' => 'Add item Failed'];
      }
    } elseif ($action == 'update') {
      $return = true;
      $msg = '';
      $this->coreFunctions->sbcupdate($this->stock,  $data, ['trno' =>  $trno, 'line' =>  $line]);
      return ['status' =>  $return, 'msg' =>  $msg];
    }
  } // end function

  public function additem($action, $config, $setlog = false)
  {
    $companyid = $config['params']['companyid'];
    $ispallet = $this->companysetup->getispallet($config['params']);
    $uom = isset($config['params']['data']['uom']) ? $config['params']['data']['uom'] : '';

    $itemid = $config['params']['data']['itemid'];
    $trno = $config['params']['trno'];
    $disc = '';
    $wh = $config['params']['data']['wh'];
    $suppid = isset($config['params']['data']['suppid']) ? $config['params']['data']['suppid'] : '';

    $qty = $config['params']['data']['isqty'];
    $isqty2 = $config['params']['data']['isqty2'];
    $isqty3 = $config['params']['data']['isqty3'];
    $prevqty = $config['params']['data']['prevqty'];

    $projectid = 0;

    $line = 0;

    if ($action == 'insert') {
      $qry = "select line as value from " . $this->stock . " where trno=? order by line desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$trno]);
      if ($line == '') {
        $line = 0;
      }
      $line = $line + 1;
      $config['params']['line'] = $line;

      $amt = $config['params']['data']['amt'];
      $qty = $config['params']['data']['qty'];
    } elseif ($action == 'update') {
      $config['params']['line'] = $config['params']['data']['line'];
      $line = $config['params']['data']['line'];
      $amt = $config['params']['data'][$this->damt];
      $qty = $config['params']['data'][$this->dqty];
      $config['params']['line'] = $line;
    }
    $amt = $this->othersClass->sanitizekeyfield('amt', $amt);
    $qty = $this->othersClass->sanitizekeyfield('qty', $qty);
    $isqty2 =  $this->othersClass->sanitizekeyfield('qty',  $isqty2); // previous reading
    $isqty3 =  $this->othersClass->sanitizekeyfield('qty',  $isqty3); // present reading
    $prevqty =  $this->othersClass->sanitizekeyfield('qty',  $prevqty); // previous consumption reading

    $qty = $isqty3 - $isqty2;
    if ($isqty2 == 0) $qty = $isqty3;
    if ($qty < 0)  $qty = 0;
    $qty = $this->othersClass->sanitizekeyfield('qty', $qty);

    $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor,item.isnoninv from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
    $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
    $factor = 1;
    $isnoninv = 0;
    if (!empty($item)) {
      $isnoninv = $item[0]->isnoninv;
      $item[0]->factor = $this->othersClass->val($item[0]->factor);
      if ($item[0]->factor !== 0) $factor = $item[0]->factor;
    }
    $vat = $this->coreFunctions->getfieldvalue($this->head, 'tax', 'trno=?', [$trno]);
    $cur = $this->coreFunctions->getfieldvalue($this->head, 'cur', 'trno=?', [$trno]);
    $curtopeso = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
    $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);
    $qty = round($qty, $this->companysetup->getdecimal('qty', $config['params']));
    $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor);

    if (floatval($curtopeso) == 0) {
      $curtopeso = 1;
    }

    $hamt = $computedata['amt'] * $curtopeso;
    $hamt = $this->othersClass->sanitizekeyfield('amt', $hamt);

    $data = [
      'trno' => $trno,
      'line' => $line,
      'itemid' => $itemid,
      $this->damt => $amt,
      $this->hamt => $hamt,
      $this->dqty => $qty,
      $this->hqty => $computedata['qty'],
      'ext' => number_format($computedata['ext'], $this->companysetup->getdecimal('currency', $config['params']), '.', ''),
      'disc' => $disc,
      'whid' => $whid,
      'uom' => $uom,
      'isqty2' =>  $isqty2,
      'isqty3' =>  $isqty3,
      'prevqty' =>  $prevqty,
      'suppid' => $suppid
    ];

    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $data['editdate'] = $current_timestamp;
    $data['editby'] = $config['params']['user'];

    //insert item
    if ($action == 'insert') {

      $data['encodeddate'] = $current_timestamp;
      $data['encodedby'] = $config['params']['user'];
      if (isset($config['params']['data']['sortline'])) {
        $data['sortline'] =  $config['params']['data']['sortline'];
      } else {
        $data['sortline'] =  $data['line'];
      }

      $trno = $this->othersClass->val($trno);
      if ($trno == 0) {
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ZERO TRNO (SJ)');
        return ['status' => false, 'msg' => 'Add item Failed. Zero trno generated'];
      }

      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
        $havestock = true;
        $msg = 'Item was successfully added.';

        $this->othersClass->getcreditinfo($config, $this->head);
        $row = $this->openstockline($config);
        if (!$havestock) {
          $row[0]->errcolor = 'bg-red-2';
          $msg = '(' . $item[0]->barcode . ') Out of Stock.';
        }
        return ['row' => $row, 'status' => true, 'msg' => $msg];
      } else {
        return ['status' => false, 'msg' => 'Add item Failed'];
      }
    } elseif ($action == 'update') {
      $return = true;
      $msg = '';
      $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
      return ['status' => $return, 'msg' => $msg];
    }
  } // end function

  public function deleteallitem($config)
  {
    $trno =  $config['params']['trno'];
    if ($this->companysetup->getserial($config['params'])) {
      $data2 =  $this->coreFunctions->opentable('select trno,line from ' .  $this->stock . ' where trno=?', [$trno]);
      foreach ($data2 as  $key =>  $value) {
        $this->othersClass->deleteserialout($data2[$key]->trno,  $data2[$key]->line);
      }
    }

    $data =  $this->coreFunctions->opentable('select refx,linex from ' .  $this->stock . ' where trno=? and refx<>0', [$trno]);
    $this->coreFunctions->execqry('delete from ' .  $this->stock . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from costing where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from stockinfo where trno=?', 'delete', [$trno]);
    $this->logger->sbcwritelog($trno,  $config, 'STOCK', 'DELETED ALL ITEMS');
    return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
  }


  public function setserveditems($refx,  $linex)
  {
    if ($refx == 0) {
      return 1;
    }
    $qry1 =  " select stock . " .  $this->hqty .  " from lahead as head left join lastock as
    stock on stock . trno = head . trno where head . doc in ('SJ', 'BO') and stock . refx = " .  $refx .  " and stock . linex = " .  $linex;

    $qry1 =  $qry1 .  " union all select glstock . " .  $this->hqty .  " from glhead left join glstock on glstock . trno =
    glhead . trno where glhead . doc in ('SJ', 'BO') and glstock . refx = " .  $refx .  " and glstock . linex = " .  $linex;

    $qry2 =  " select ifnull (sum (" .  $this->hqty .  "), 0) as value from (" .  $qry1 .  ") as t ";
    $qty =  $this->coreFunctions->datareader($qry2);
    if ($qty == '') {
      $qty = 0;
    }
    $result =  $this->coreFunctions->execqry(" update hsostock set qa = " .  $qty .  " where trno = " .  $refx .  " and line = " .  $linex, 'update');

    $status =  $this->coreFunctions->datareader(" select ifnull (count (trno), 0) as value from hsostock where trno = ? and iss > qa ", [$refx]);
    if ($status) {
      $status =  $this->coreFunctions->datareader(" select ifnull (count (trno), 0) as value from hsostock where trno = ? and qa <> 0 ", [$refx]);
      if ($status) {
        $this->coreFunctions->execqry(" update transnum set statid = 6 where trno = " .  $refx);
      } else {
        $this->coreFunctions->execqry(" update transnum set statid = 5 where trno = " .  $refx);
      }
    } else {
      $this->coreFunctions->execqry(" update transnum set statid = 7 where trno = " .  $refx);
    }

    return  $result;
  }

  public function setservedsqitems($refx,  $linex)
  {
    if ($refx == 0) {
      return 1;
    }
    $qry1 =  " select stock . " .  $this->hqty .  " from lahead as head left join lastock as
    stock on stock . trno = head . trno where head . doc = 'SJ' and stock . refx = " .  $refx .  " and stock . linex = " .  $linex;

    $qry1 =  $qry1 .  " union all select glstock . " .  $this->hqty .  " from glhead left join glstock on glstock . trno =
    glhead . trno where glhead . doc = 'SJ' and glstock . refx = " .  $refx .  " and glstock . linex = " .  $linex;

    $qry2 =  " select ifnull (sum (" .  $this->hqty .  "), 0) as value from (" .  $qry1 .  ") as t ";
    $qty =  $this->coreFunctions->datareader($qry2);
    if (floatval($qty) == 0) {
      $qty = 0;
    }

    $return =   $this->coreFunctions->execqry(" update hqsstock set sjqa = " .  $qty .  " where trno = " .  $refx .  " and line = " .  $linex, 'update');
    $sotrno =  $this->coreFunctions->datareader(" select sotrno as value from hqshead where trno = ? ", [$refx]);
    $status =  $this->coreFunctions->datareader(" select ifnull (count (trno), 0) as value from hqsstock where trno = ? and iss > (sjqa + voidqty) ", [$refx]);
    if ($status) {
      $status =  $this->coreFunctions->datareader(" select ifnull (count (trno), 0) as value from hqsstock where trno = ? and sjqa <> 0 ", [$refx]);
      if ($status) {
        $this->coreFunctions->execqry(" update transnum set statid = 6 where trno = " .  $sotrno);
      } else {
        $this->coreFunctions->execqry(" update transnum set statid = 5 where trno = " .  $sotrno);
      }
    } else {
      $this->coreFunctions->execqry(" update transnum set statid = 7 where trno = " .  $sotrno);
    }
    return  $return;
  }


  public function deleteitem($config)
  {
    $config['params']['trno'] =  $config['params']['row']['trno'];
    $config['params']['line'] =  $config['params']['row']['line'];

    $data =  $this->openstockline($config);
    $trno =  $config['params']['trno'];
    $line =  $config['params']['line'];

    $qry =  " delete from " .  $this->stock .  " where trno = ? and line = ? ";
    $this->coreFunctions->execqry($qry, 'delete', [$trno,  $line]);
    $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno,  $line]);
    $this->coreFunctions->execqry('delete from stockinfo where trno=? and line=?', 'delete', [$trno,  $line]);
    $this->logger->sbcwritelog($trno,  $config, 'STOCK', 'REMOVED - Line:' .  $line . ' barcode:' .  $data[0]->barcode . ' Qty:' .  $data[0]->isqty . ' Amt:' .  $data[0]->isamt);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
  } // end function

  public function getlatestprice($config)
  {
    $barcode =  $config['params']['barcode'];
    $client =  $config['params']['client'];
    $center =  $config['params']['center'];
    $trno =  $config['params']['trno'];
    $companyid =  $config['params']['companyid'];

    $pricetype =  $this->companysetup->getpricetype($config['params']);
    $data = [];

    switch ($pricetype) {
      case 'Stockcard':
        goto itempricehere;
        break;

      case 'CustomerGroup':
      case 'CustomerGroupLatest':
        $pricegrp =  $this->coreFunctions->getfieldvalue(" client ", " class ", " client = ? ", [$client]);
        if ($pricegrp != '') {
          $pricefield =  $this->othersClass->getamtfieldbygrp($pricegrp);

          $qry =  " select '" . $pricefield[' label '] . "' as docno, left (now (), 10) as dateid, " .  $pricefield['amt'] .  " as amt, " .  $pricefield['amt'] .  " as defamt, " .  $pricefield['disc'] .  " as disc, uom from item where barcode = ?
      union all
      select docno, left (dateid, 10) as dateid, round (amt, " .  $this->companysetup->getdecimal('price',  $config['params']) .  ") as amt, round (amt, " .  $this->companysetup->getdecimal('price',  $config['params']) .  ") as defamt, disc, uom from (select head . docno, head . dateid,
      stock . isamt as amt, stock . uom, stock . disc
      from lahead as head
      left join lastock as stock on stock . trno = head . trno
      left join cntnum on cntnum . trno = head . trno
      left join item on item . itemid = stock . itemid
      where head . doc = 'SJ' and cntnum . center = ?
      and item . barcode = ? and head . client = ?
      and stock . isamt <> 0 and cntnum . trno <> ?
      UNION ALL
      select head . docno, head . dateid, stock . isamt as computeramt,
      stock . uom, stock . disc from glhead as head
      left join glstock as stock on stock . trno = head . trno
      left join item on item . itemid = stock . itemid
      left join client on client . clientid = head . clientid
      left join cntnum on cntnum . trno = head . trno
      where head . doc = 'SJ' and cntnum . center = ?
      and item . barcode = ? and client . client = ?
      and stock . isamt <> 0 and cntnum . trno <> ?
      order by dateid desc limit 5) as tbl order by dateid desc ";

          $data =  $this->coreFunctions->opentable($qry, [$barcode,  $center,  $barcode,  $client,  $trno,  $center,  $barcode,  $client,  $trno]);

          if (!empty($data)) {
            goto setpricehere;
          }
        } else {
          if ($pricetype == 'CustomerGroupLatest') {
            goto getCustomerLatestPriceHere;
          } else {
            goto setpricehere;
          }
        }
        break;

      default:
        getCustomerLatestPriceHere:
        if ($companyid == 22) { //eipi
          $qry =  " select docno, left (dateid, 10) as dateid, round (amt, 2) as amt, round (amt, 2) as defamt, disc, uom
                    from (select head . docno, head . dateid,
                    stock . rrcost as amt, stock . uom, stock . disc
                    from lahead as head
                    left join lastock as stock on stock . trno = head . trno
                    left join cntnum on cntnum . trno = head . trno
                    left join item on item . itemid = stock . itemid
                    where head . doc in ('RR', 'IS', 'AJ', 'TS') and cntnum . center = ?
                    and item . barcode = ?
                    and stock . cost <> 0 and cntnum . trno <> ?
                    union all
                    select head . docno, head . dateid, stock . rrcost as amt,
                    stock . uom, stock . disc from glhead as head
                    left join glstock as stock on stock . trno = head . trno
                    left join item on item . itemid = stock . itemid
                    left join client on client . clientid = head . clientid
                    left join cntnum on cntnum . trno = head . trno
                    where head . doc in ('RR', 'IS', 'AJ', 'TS') and cntnum . center = ?
                    and item . barcode = ?
                    and stock . cost <> 0 and cntnum . trno <> ?
                    order by dateid desc limit 5) as tbl order by dateid desc limit 1 ";
          $data =  $this->coreFunctions->opentable($qry, [$center,  $barcode,  $trno,  $center,  $barcode,  $trno]);
        } else {
          $qry =  " select docno, left (dateid, 10) as dateid, round (amt, " .  $this->companysetup->getdecimal('price',  $config['params']) .  ") as amt, round (amt, " .  $this->companysetup->getdecimal('price',  $config['params']) .  ") as defamt, disc, uom from (select head . docno, head . dateid,
                    stock . isamt as amt, stock . uom, stock . disc
                    from lahead as head
                    left join lastock as stock on stock . trno = head . trno
                    left join cntnum on cntnum . trno = head . trno
                    left join item on item . itemid = stock . itemid
                    where head . doc = 'SJ' and cntnum . center = ?
                    and item . barcode = ? and head . client = ?
                    and stock . isamt <> 0 and cntnum . trno <> ?
                    UNION ALL
                    select head . docno, head . dateid, stock . isamt as computeramt,
                    stock . uom, stock . disc from glhead as head
                    left join glstock as stock on stock . trno = head . trno
                    left join item on item . itemid = stock . itemid
                    left join client on client . clientid = head . clientid
                    left join cntnum on cntnum . trno = head . trno
                    where head . doc = 'SJ' and cntnum . center = ?
                    and item . barcode = ? and client . client = ?
                    and stock . isamt <> 0 and cntnum . trno <> ?
                    order by dateid desc limit 5) as tbl order by dateid desc ";

          $data =  $this->coreFunctions->opentable($qry, [$center,  $barcode,  $client,  $trno,  $center,  $barcode,  $client,  $trno]);
        }
        break;
    }



    if (!empty($data)) {
      return ['status' => true, 'msg' => 'Found the latest price...', 'data' => $data];
    } else {
      itempricehere:
      $qry = "select 'STOCKCARD'  as docno,left(now(),10) as dateid,amt,amt as defamt,disc,uom from item where barcode=? union all
            select docno,left(dateid,10) as dateid,round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as defamt,disc,uom from(select head.docno,head.dateid,
            stock.isamt as amt,stock.uom,stock.disc
            from lahead as head
            left join lastock as stock on stock.trno = head.trno
            left join cntnum on cntnum.trno=head.trno
            left join item on item.itemid = stock.itemid
            where head.doc = 'SJ' and cntnum.center = ?
            and item.barcode = ? and head.client = ?
            and stock.isamt <> 0 and cntnum.trno <> ?
            UNION ALL
            select head.docno,head.dateid,stock.isamt as computeramt,
            stock.uom,stock.disc from glhead as head
            left join glstock as stock on stock.trno = head.trno
            left join item on item.itemid = stock.itemid
            left join client on client.clientid = head.clientid
            left join cntnum on cntnum.trno=head.trno
            where head.doc = 'SJ' and cntnum.center = ?
            and item.barcode = ? and client.client = ?
            and stock.isamt <> 0 and cntnum.trno <> ?
            order by dateid desc limit 5) as tbl";

      $data = $this->coreFunctions->opentable($qry, [$barcode, $center, $barcode, $client, $trno, $center, $barcode, $client, $trno]);

      setpricehere:
      $usdprice = 0;
      $forex = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
      $cur = $this->coreFunctions->getfieldvalue($this->head, 'cur', 'trno=?', [$trno]);
      $dollarrate = $this->coreFunctions->getfieldvalue('forex_masterfile', 'dollartocur', 'cur=?', [$cur]);
      $defuom = '';

      if ($this->companysetup->getisdefaultuominout($config['params'])) {
        if (empty($data)) {
          $data[0]->docno = 'UOM';
        }
        $defuom = $this->coreFunctions->datareader("select ifnull(uom.uom,'') as value from item left join uom on uom.itemid=item.itemid and uom.isdefault2 = 1 where item.barcode=?", [$barcode]);

        if ($defuom != "") {
          $data[0]->uom = $defuom;
          if ($this->companysetup->getisrecalcamtchangeuom($config['params'])) {
            if (floatval($data[0]->amt) != 0) {
              $data[0]->amt = $data[0]->amt * ($this->coreFunctions->datareader("select uom.factor as value from item left join uom on uom.itemid=item.itemid and uom.uom = '" . $defuom . "' where item.barcode=?", [$barcode]));
            } else {
              if ($companyid != 32) { //not 3m
                $data[0]->amt = $this->coreFunctions->datareader("select (item.amt*ifnull(uom.factor,1)) as value from item left join uom on uom.itemid=item.itemid and uom.uom = '" . $defuom . "' where item.barcode=?", [$barcode]);
              }
            }
          }
        } else {
          if ($this->companysetup->getisrecalcamtchangeuom($config['params'])) {
            if (floatval($data[0]->amt) != 0) {
              $data[0]->amt = $data[0]->amt * ($this->coreFunctions->datareader("select uom.factor as value from item left join uom on uom.itemid=item.itemid and uom.uom = item.uom where item.barcode=?", [$barcode]));
            } else {
              if ($companyid != 32) { //not 3m
                $data[0]->amt = $this->coreFunctions->datareader("select (item.amt*ifnull(uom.factor,1)) as value from item left join uom on uom.itemid=item.itemid and uom.uom = item.uom where item.barcode=?", [$barcode]);
              }
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
          $data[0]->amt = round($usdprice * $dollarrate, $this->companysetup->getdecimal('price', $config['params']));
        }
      }

      if (floatval($data[0]->amt) == 0) {
        return ['status' => false, 'msg' => 'No Latest price found...', 'data' => $data];
      } else {
        return ['status' => true, 'msg' => 'Found the latest price...', 'data' => $data];
      }
    }
  } // end function

  public function getposummaryqry($config)
  {
    $addfield = ",head.ourref";

    switch ($config['params']['companyid']) {
      case 28: //xcomp
        $addfield = ",head.docno as ourref";
        break;
      case 22: //EIPI
        $addfield .= ",stock.fstatus as itemstatus";
        break;
    }
    return "
        select head.docno,head.client, head.clientname, head.address, ifnull(head.rem,'') as rem, 
        head.cur, head.forex, head.shipto " . $addfield . " , head.yourref, head.terms, 
        ifnull(head.branch,0) as branch,item.itemid,stock.trno,stock.line, item.barcode,
        stock.uom,stock.amt,(stock.iss-stock.qa) as iss,stock.isamt,stock.kgs,stock.weight,
        round((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
        stock.disc,stock.loc,stock.expiry,stock.projectid,head.shipto,head.mlcp_freight,
        head.ms_freight,head.agent,head.projectid as hprojectid,wh.client as swh,
        info.driverid,info.helperid,info.checkerid,info.plateno,info.truckid,sinfo.itemdesc,head.sano,head.pono,head.wh,head.salestype
        FROM hsohead as head left join hsostock as stock on stock.trno=head.trno 
        left join item on item.itemid=stock.itemid 
        left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
        left join client as wh on wh.clientid=stock.whid 
        left join hheadinfotrans as info on info.trno=head.trno
        left join hstockinfotrans as sinfo on sinfo.trno=stock.trno and sinfo.line=stock.line
        left join clientsano as sa on sa.line=head.sano
        left join clientsano as po on po.line=head.pono
        where stock.trno = ? and stock.iss>stock.qa and stock.void=0";
  }

  public function getsosummary($config)
  {
    $fifoexpiration = $this->companysetup->getfifoexpiration($config['params']);
    $companyid = $config['params']['companyid'];
    $this->coreFunctions->LogConsole('FIFO-' . $fifoexpiration);
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    $msg = '';
    $updatehead = 0;

    foreach ($config['params']['rows'] as $key => $value) {
      $qry = $this->getposummaryqry($config);
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno']]);
      if (!empty($data)) {

        if ($updatehead == 0) {
          $headupdate = [
            'ourref' => $data[0]->ourref,
            'yourref' => $data[0]->yourref,
            'agent' => $data[0]->agent,
            'rem' => $data[0]->rem,
            'wh' => $data[0]->wh,
            'shipto' => $data[0]->shipto,
            'projectid' => $data[0]->hprojectid,
            'sano' => $data[0]->sano,
            'pono' => $data[0]->pono
          ];
          if ($companyid == 24) { //goodfound
            if (substr($data[0]->docno, 0, 2) == 'SO') {
              $headupdate['tax'] = 12;
              $headupdate['vattype'] = 'VATABLE';

              if ($data[0]->salestype == 'Pickup') {
                $headupdate['statid'] = 40;
              }
              if ($data[0]->salestype == 'Deliver') {
                $headupdate['statid'] = 24;
              }
            }
          }

          $updatehead = $this->coreFunctions->sbcupdate($this->head, $headupdate, ["trno" => $trno]);
          if ($updatehead) {
            if ($companyid == 19) { //housegem
              $headinfo = [
                'driverid' => $data[0]->driverid,
                'helperid' => $data[0]->helperid,
                'checkerid' => $data[0]->checkerid,
                'plateno' => $data[0]->plateno,
                'truckid' => $data[0]->truckid
              ];
              $this->coreFunctions->sbcupdate('cntnuminfo', $headinfo, ["trno" => $trno]);
            }
          }
        }

        foreach ($data as $key2 => $value) {

          if ($fifoexpiration) {
            $wh = $data[$key2]->swh;
            $return_result = $this->insertfifoexpiration($config, $value, $wh);
            if (!empty($return_result)) {
              foreach ($return_result as $key => $return_val) {
                array_push($rows, $return_val);
              }
            } else {
              goto defaultsjentryhere;
            }
          } else {
            defaultsjentryhere:
            $config['params']['data']['uom'] = $data[$key2]->uom;
            $config['params']['data']['itemid'] = $data[$key2]->itemid;
            $config['params']['trno'] = $trno;
            $config['params']['data']['disc'] = $data[$key2]->disc;
            $config['params']['data']['qty'] = $data[$key2]->isqty;
            $config['params']['data']['wh'] = $data[$key2]->swh;
            $config['params']['data']['loc'] = $data[$key2]->loc;
            $config['params']['data']['expiry'] = $data[$key2]->expiry;
            $config['params']['data']['rem'] = '';
            $config['params']['data']['refx'] = $data[$key2]->trno;
            $config['params']['data']['linex'] = $data[$key2]->line;
            $config['params']['data']['ref'] = $data[$key2]->docno;
            $config['params']['data']['amt'] = $data[$key2]->isamt;
            $config['params']['data']['projectid'] = $data[$key2]->projectid;
            $config['params']['data']['kgs'] = $data[$key2]->kgs;
            $config['params']['data']['weight'] = $data[$key2]->weight;
            $config['params']['data']['itemdesc'] = $data[$key2]->itemdesc;
            if ($companyid == 22) { //EIPI
              $config['params']['data']['itemstatus'] = $data[$key2]->itemstatus;
            }
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
          }
        } // end foreach
      } //end if
    } //end foreach
    switch ($companyid) {
      case 19: //HOUESGEM
      case 24: //goodfound
        return ['row' => $rows, 'status' => true, 'msg' => $msg, 'reloadhead' => true];
        break;

      default:
        return ['row' => $rows, 'status' => true, 'msg' => $msg];
        break;
    }
  } //end function

  public function insertfifoexpiration($config, $value, $wh, $setlog = false)
  {
    $companyid = $config['params']['companyid'];
    $trno = $config['params']['trno'];
    $return_row = [];

    $sql = "select rrstatus.expiry,rrstatus.loc,rrstatus.whid,ifnull(sum(rrstatus.bal),0) as bal from rrstatus
        left join item on item.itemid = rrstatus.itemid left join client on client.clientid=rrstatus.whid
        where rrstatus.itemid = " . $value->itemid . " and client.client = '" . $wh . "' and rrstatus.bal <> 0 
        group by rrstatus.expiry,rrstatus.loc,rrstatus.whid order by rrstatus.expiry,rrstatus.loc,rrstatus.whid asc";

    $invdata = $this->coreFunctions->opentable($sql);

    $running_qty = $value->isqty;
    $qty = 0;

    foreach ($invdata as $key => $val) {

      $expiry  = $val->expiry;
      $loc = $val->loc;

      if ($running_qty > 0) {
        if ($running_qty > $val->bal) {
          $qty = $val->bal;
        } else {
          $qty = $running_qty;
        }

        inserthere:
        $running_qty = $running_qty - $qty;

        $config['params']['data']['uom'] = $value->uom;
        $config['params']['data']['itemid'] = $value->itemid;
        $config['params']['trno'] = $trno;
        $config['params']['data']['disc'] = $value->disc;
        $config['params']['data']['qty'] = $qty;
        $config['params']['data']['wh'] = $value->swh;
        $config['params']['data']['loc'] = $loc;
        $config['params']['data']['expiry'] = $expiry;
        $config['params']['data']['rem'] = '';
        $config['params']['data']['refx'] = $value->trno;
        $config['params']['data']['linex'] = $value->line;
        $config['params']['data']['ref'] = $value->docno;
        $config['params']['data']['amt'] = $value->isamt;
        $config['params']['data']['projectid'] = $value->projectid;
        $config['params']['data']['itemdesc'] = $value->itemdesc;
        $return = $this->additem('insert', $config, $setlog);

        if ($msg = '') {
          $msg = $return['msg'];
        } else {
          $msg = $msg . $return['msg'];
        }

        if ($return['status']) {
          if ($this->setserveditems($value->trno, $value->line) == 0) {
            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
            $line = $return['row'][0]->line;
            $config['params']['trno'] = $trno;
            $config['params']['line'] = $line;
            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
            $this->setserveditems($$value->trno, $value->line);
            $row = $this->openstockline($config);
            $return = ['row' => $row, 'status' => true, 'msg' => $msg];
          }
          array_push($return_row, $return['row'][0]);
        }
      }

      $this->coreFunctions->LogConsole('key: ' . $key . ' - count: ' . count($invdata) . ' - bal:' . $running_qty);

      if ($key >= (count($invdata) - 1)) {
        if ($running_qty > 0) {
          $qty = $running_qty;
          $expiry  = '';
          $loc = '';
          goto inserthere;
        }
        break;
      }
    } //end foreach

    return $return_row;
  }


  public function getsosummaryserial($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.amt,
        (stock.iss-stock.qa) as iss,stock.isamt,
        round((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
        stock.disc,stock.loc,stock.expiry,stock.whid
        FROM hsohead as head left join hsostock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom where stock.trno = ? and stock.iss>stock.qa and stock.void=0
    ";
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno']]);
      if (!empty($data)) {
        foreach ($data as $key2 => $value) {
          $qry = "select serialin.sline as value from rrstatus left join serialin on serialin.trno=rrstatus.trno and serialin.line=rrstatus.line
                where rrstatus.itemid=? and rrstatus.whid=? and serialin.serial=? and serialin.outline=0 ";
          $sline = $this->coreFunctions->datareader($qry, [$data[$key2]->itemid, $data[$key2]->whid, $data[$key2]->loc]);

          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['qty'] = $data[$key2]->isqty;
          $config['params']['data']['wh'] = $wh;
          $config['params']['data']['loc'] = '';

          $config['params']['data']['rem'] = '';
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->isamt;
          $return = $this->additem('insert', $config);
          if ($return['status']) {
            if ($this->setserveditems($data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setserveditems($data[$key2]->trno, $data[$key2]->line);
              $row = $this->openstockline($config);
              $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
            } else {
              if ($sline != '') {
                $line = $return['row'][0]->line;
                $this->othersClass->insertserialout($sline, $trno, $line, $data[$key2]->loc);
              }
            }
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => 'Added Items Successful...'];
  } //end function


  public function getsodetailsserial($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.amt,
        (stock.iss-stock.qa) as iss,stock.isamt,
        round((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
        stock.disc,stock.loc,stock.expiry,stock.whid
        FROM hsohead as head left join hsostock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom where stock.trno = ? and stock.line=? and stock.iss>stock.qa and stock.void=0
    ";
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line']]);
      if (!empty($data)) {
        foreach ($data as $key2 => $value) {
          $qry = "select serialin.sline as value from rrstatus left join serialin on serialin.trno=rrstatus.trno and serialin.line=rrstatus.line
                where rrstatus.itemid=? and rrstatus.whid=? and serialin.serial=? and serialin.outline=0 ";
          $sline = $this->coreFunctions->datareader($qry, [$data[$key2]->itemid, $data[$key2]->whid, $data[$key2]->loc]);

          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['qty'] = $data[$key2]->isqty;
          $config['params']['data']['wh'] = $wh;
          $config['params']['data']['loc'] = '';

          $config['params']['data']['rem'] = '';
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->isamt;
          $return = $this->additem('insert', $config);
          if ($return['status']) {
            if ($this->setserveditems($data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setserveditems($data[$key2]->trno, $data[$key2]->line);
              $row = $this->openstockline($config);
              $return = ['row' => $row, 'status' => true, 'msg' => $return['msg']];
            } else {
              if ($sline != '') {
                $line = $return['row'][0]->line;
                $this->othersClass->insertserialout($sline, $trno, $line, $data[$key2]->loc);
              }
            }
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => $return['msg']];
  } //end function



  public function getsodetails($config)
  {
    $fifoexpiration = $this->companysetup->getfifoexpiration($config['params']);

    $companyid = $config['params']['companyid'];
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    $msg = '';

    $addfield = '';
    if ($companyid == 22) { //EIPI
      $addfield = ', stock.fstatus as itemstatus';
    }
    foreach ($config['params']['rows'] as $key => $value) {

      $qry = "
        select head.docno, head.ourref, head.yourref, head.agent, head.shipto, head.projectid as hprojectid,head.rem,item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.amt,
        (stock.iss-stock.qa) as iss,stock.isamt,stock.kgs,
        round((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
        stock.disc,stock.loc,stock.expiry,stock.projectid,wh.client as swh,info.driverid,info.helperid,info.checkerid,info.plateno,stock.weight,sinfo.itemdesc,head.sano,head.pono,head.wh  $addfield
        FROM hsohead as head left join hsostock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
        left join client as wh on wh.clientid=stock.whid left join hheadinfotrans as info on info.trno=head.trno
        left join hstockinfotrans as sinfo on sinfo.trno=stock.trno and sinfo.line=stock.line
        where stock.trno = ? and stock.line=? and stock.iss>stock.qa and stock.void=0
    ";
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line']]);
      if (!empty($data)) {
        $updatehead = 0;
        foreach ($data as $key2 => $value) {

          if ($updatehead == 0) {
            $headupdate = [
              'ourref' => $data[0]->ourref,
              'yourref' => $data[0]->yourref,
              'agent' => $data[0]->agent,
              'rem' => $data[0]->rem,
              'wh' => $data[0]->wh,
              'shipto' => $data[0]->shipto,
              'projectid' => $data[0]->hprojectid,
              'sano' => $data[0]->sano,
              'pono' => $data[0]->pono
            ];

            if ($companyid == 24) { //goodfound
              if (substr($data[0]->docno, 0, 2) == 'SO') {
                $headupdate['tax'] = 12;
                $headupdate['vattype'] = 'VATABLE';
              }
            }

            $updatehead = $this->coreFunctions->sbcupdate($this->head, $headupdate, ["trno" => $trno]);
            if ($updatehead) {
              if ($companyid == 19) { //housegem
                $headinfo = [
                  'driverid' => $data[0]->driverid,
                  'helperid' => $data[0]->helperid,
                  'checkerid' => $data[0]->checkerid,
                  'plateno' => $data[0]->plateno
                ];
                $this->coreFunctions->sbcupdate('cntnuminfo', $headinfo, ["trno" => $trno]);
              }
            }
          }

          if ($fifoexpiration) {
            $wh = $data[$key2]->swh;
            $return_result = $this->insertfifoexpiration($config, $value, $wh);
            if (!empty($return_result)) {
              foreach ($return_result as $key => $return_val) {
                array_push($rows, $return_val);
              }
            } else {
              goto defaultsjentryhere;
            }
          } else {
            defaultsjentryhere:
            $config['params']['data']['uom'] = $data[$key2]->uom;
            $config['params']['data']['itemid'] = $data[$key2]->itemid;
            $config['params']['trno'] = $trno;
            $config['params']['data']['disc'] = $data[$key2]->disc;
            $config['params']['data']['qty'] = $data[$key2]->isqty;
            if ($companyid == 15) { //nathina
              $config['params']['data']['wh'] = $data[$key2]->swh;
            } else {
              $config['params']['data']['wh'] = $wh;
            }
            $config['params']['data']['loc'] = $data[$key2]->loc;
            $config['params']['data']['expiry'] = $data[$key2]->expiry;
            $config['params']['data']['rem'] = '';
            $config['params']['data']['refx'] = $data[$key2]->trno;
            $config['params']['data']['linex'] = $data[$key2]->line;
            $config['params']['data']['ref'] = $data[$key2]->docno;
            $config['params']['data']['amt'] = $data[$key2]->isamt;
            $config['params']['data']['projectid'] = $data[$key2]->projectid;
            $config['params']['data']['kgs'] = $data[$key2]->kgs;
            $config['params']['data']['weight'] = $data[$key2]->weight;
            $config['params']['data']['itemdesc'] = $data[$key2]->itemdesc;
            if ($companyid == 22) { //EIPI
              $config['params']['data']['itemstatus'] = $data[$key2]->itemstatus;
            }

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
          }
        } // end foreach
      } //end if
    } //end foreach
    switch ($companyid) {
      case 19: //HOUSEGEM
      case 24: //goodfound
        return ['row' => $rows, 'status' => true, 'msg' => $msg, 'reloadhead' => true];
        break;

      default:
        return ['row' => $rows, 'status' => true, 'msg' => $msg];
        break;
    }
  } //end function

  public function getsqsummary($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    $msg = '';
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
      select concat(stock.trno,stock.line) as keyid, stock.trno, stock.line, stock.itemid, item.barcode, item.itemname, stock.uom, so.docno, 
      date(head.dateid) as dateid, left(head.due, 10) as podate, head.yourref,
      (stock.iss-(stock.qa+stock.sjqa)) as iss,stock.isamt, stock.disc,stock.amt,stock.ext,
      FORMAT(((stock.iss-(stock.qa+stock.sjqa+stock.voidqty))/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
      stock.projectid,stock.sgdrate,stock.sortline
      from hsqhead as so 
      left join hqshead as head on head.sotrno=so.trno 
      left join hqsstock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid
      left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
      left join transnum on transnum.trno = head.trno
      where so.doc='SQ' and stock.iss > (stock.qa+stock.sjqa+stock.voidqty) and stock.void = 0 and stock.trno=? order by stock.line
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
          $config['params']['data']['rem'] = '';
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->isamt;
          $config['params']['data']['projectid'] = $data[$key2]->projectid;
          $config['params']['data']['sgdrate'] = $data[$key2]->sgdrate;
          $config['params']['data']['poref'] = $data[$key2]->yourref;
          $config['params']['data']['podate'] = $data[$key2]->podate;
          $config['params']['data']['sortline'] = $data[$key2]->sortline;
          $return = $this->additem('insert', $config);

          if ($msg = '') {
            $msg = $return['msg'];
          } else {
            $msg = $msg . $return['msg'];
          }

          if ($return['status']) {
            if ($this->setservedsqitems($data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setservedsqitems($data[$key2]->trno, $data[$key2]->line);
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

  public function getsqdetails($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    $msg = '';
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
      select concat(stock.trno,stock.line) as keyid, stock.trno, stock.line, stock.itemid, item.barcode, item.itemname, stock.uom, so.docno, 
      date(head.dateid) as dateid, left(head.due, 10) as podate, head.yourref,
      (stock.iss-(stock.qa+stock.sjqa)) as iss,stock.isamt, stock.disc,stock.amt,stock.ext,
      FORMAT(((stock.iss-(stock.qa+stock.sjqa+stock.voidqty))/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
      stock.projectid,stock.sgdrate,stock.sortline
      from hsqhead as so left join hqshead as head on head.sotrno=so.trno left join hqsstock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid
      left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
      left join transnum on transnum.trno = head.trno
      where so.doc='SQ' and stock.iss > (stock.qa+stock.sjqa+stock.voidqty) and stock.void = 0 and stock.trno=? and stock.line=?
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
          $config['params']['data']['loc'] = '';
          $config['params']['data']['expiry'] = '';
          $config['params']['data']['rem'] = '';
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->isamt;
          $config['params']['data']['projectid'] = $data[$key2]->projectid;
          $config['params']['data']['sgdrate'] = $data[$key2]->sgdrate;
          $config['params']['data']['poref'] = $data[$key2]->yourref;
          $config['params']['data']['podate'] = $data[$key2]->podate;
          $config['params']['data']['sortline'] = $data[$key2]->sortline;
          $return = $this->additem('insert', $config);
          if ($msg = '') {
            $msg = $return['msg'];
          } else {
            $msg = $msg . $return['msg'];
          }
          if ($return['status']) {
            if ($this->setservedsqitems($data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setservedsqitems($data[$key2]->trno, $data[$key2]->line);
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

  public function createdistribution($config)
  {
    $companyid = $config['params']['companyid'];
    $trno = $config['params']['trno'];
    $status = true;
    $totalar = 0;
    $isvatexsales = $this->companysetup->getvatexsales($config['params']);
    $delcharge = $this->coreFunctions->getfieldvalue($this->head, "ms_freight", "trno=?", [$trno]);
    if ($delcharge == '') {
      $delcharge = 0;
    }
    $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);

    $qry = 'select head.dateid,client.client,client.clientid,head.tax,head.contra,head.cur,head.forex,stock.ext,wh.client as wh,ifnull(item.asset,"") as asset,ifnull(item.revenue,"") as revenue,
      item.expense,stock.isamt,stock.disc,stock.isqty,stock.cost,stock.iss,stock.fcost,head.projectid,client.rev,stock.rebate,head.taxdef,head.deldate
          from ' . $this->head . ' as head left join ' . $this->stock . ' as stock on stock.trno=head.trno
          left join item on item.itemid=stock.itemid left join client on client.clientid = item.clientid left join client as wh on wh.clientid = stock.whid 
          where head.trno=?';

    $stock = $this->coreFunctions->opentable($qry, [$trno]);
    $tax = 0;
    if (!empty($stock)) {
      $invacct = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['IN1']);
      $revacct = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['SA1']);
      $vat = floatval($stock[0]->tax);
      $tax1 = 0;
      $tax2 = 0;
      if ($vat !== 0) {
        $tax1 = 1 + ($vat / 100);
        $tax2 = $vat / 100;
      }
      $cur = $this->coreFunctions->getfieldvalue($this->head, 'cur', 'trno=?', [$trno]);
      foreach ($stock as $key => $value) {
        $params = [];
        $disc = $stock[$key]->isamt - ($this->othersClass->discount($stock[$key]->isamt, $stock[$key]->disc));

        if ($vat != 0) {
          if ($isvatexsales) {
            $tax = number_format(($stock[$key]->ext * $tax2), 2, '.', '');
            $totalar = $totalar + $stock[$key]->ext;
          } else {
            $tax = number_format(($stock[$key]->ext / $tax1), 2, '.', '');
            $tax = number_format($stock[$key]->ext - $tax, 2, '.', '');
            $totalar = $totalar + number_format($stock[$key]->ext, 2, '.', '');
          }
        }

        if ($stock[$key]->revenue != '') {
          $revacct = $stock[$key]->revenue;
        } else {
          if ($stock[$key]->rev != '' && $stock[$key]->rev != '\\') {
            $revacct = $stock[$key]->rev;
          }
        }

        $expense = isset($stock[$key]->expense) ? $stock[$key]->expense : '';

        $params = [
          'client' => $stock[$key]->client,
          'acno' => $stock[$key]->contra,
          'ext' => number_format($stock[$key]->ext, 2, '.', ''),
          'ar' => $stock[$key]->taxdef == 0 ? number_format($stock[$key]->ext, 2, '.', '') : 0,
          'wh' => $stock[$key]->wh,
          'date' => $stock[$key]->dateid,
          'inventory' => $stock[$key]->asset !== '' ? $stock[$key]->asset : $invacct,
          'revenue' => $revacct,
          'expense' => $expense,
          'tax' =>  $stock[$key]->taxdef == 0 ? $tax : 0,
          'discamt' => number_format($disc * $stock[$key]->isqty, 2, '.', ''),
          'cur' => $stock[$key]->cur,
          'forex' => $stock[$key]->forex,
          'cost' => number_format($stock[$key]->cost * $stock[$key]->iss, 2, '.', ''),
          'fcost' => number_format($stock[$key]->fcost * $stock[$key]->iss, 2, '.', ''),
          'projectid' => $stock[$key]->projectid,
          'rebate' => $stock[$key]->rebate,
          'deldate' => $stock[$key]->deldate
        ];
        if ($isvatexsales) {
          $this->distributionvatex($params, $config);
        } else {
          $this->distribution($params, $config);
        }
      }

      $qry = "select client.client, client.clientid, h.dateid, pm.surcharge, h.cur, h.forex, h.projectid, month(h.dateid) as mon, year(h.dateid) as yr, DATE_FORMAT(h.dateid ,'%Y-%m-01') as enddate from lahead as h left join lastock as s on s.trno=h.trno 
            left join client on client.clientid=s.suppid left join projectmasterfile as pm on pm.line = h.projectid
            where h.trno=? group by client.client, client.clientname, client.clientid, h.dateid, pm.surcharge, h.cur, h.forex, h.projectid, month(h.dateid)";
      $scharge = $this->coreFunctions->opentable($qry, [$trno]);

      foreach ($scharge as $key => $value) {
        $surcharge = 0;
        // $arbal = $this->coreFunctions->datareader("select ifnull(sum(if(ar.cr<>0,ar.bal*-1,ar.bal)),0) as value from arledger as ar left join glhead as h on h.trno=ar.trno where month(h.dateid)<" . $scharge[$key]->mon . " and date(h.dateid)<'" . $scharge[$key]->dateid . "' and ar.clientid = " . $scharge[$key]->clientid . " ", [], '', true);

        $arbalSCqry = "select ifnull(sum(bal),0) as value from ( 
          select detail.db-detail.cr AS bal 
          from gldetail detail left join glhead head on head.trno = detail.trno left join coa on coa.acnoid = detail.acnoid 
          left join client dclient on dclient.clientid = detail.clientid left join client on client.clientid = head.clientid 
          where date(head.dateid)<'" . $scharge[$key]->enddate . "' and dclient.clientid=" . $scharge[$key]->clientid . " and left(coa.alias,2)='AR') as bal";

        $arbal = $this->coreFunctions->datareader($arbalSCqry, [], '', true);

        if ($scharge[$key]->surcharge != 0) {
          if ($arbal > 0) {
            $surcharge = $arbal * ($scharge[$key]->surcharge / 100);

            if ($scharge[$key]->forex == 0) $scharge[$key]->forex = 1;

            $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['ARSC']);
            $entry = ['acnoid' => $acnoid, 'client' => $scharge[$key]->client, 'db' => ($surcharge * $scharge[$key]->forex), 'cr' => 0, 'postdate' => $scharge[$key]->dateid, 'cur' => $scharge[$key]->cur, 'forex' => $scharge[$key]->forex, 'fdb' => floatval($scharge[$key]->forex) == 1 ? 0 : $scharge[$key]->dateid, 'fcr' => 0, 'projectid' => $scharge[$key]->projectid];
            $this->othersClass->logConsole(json_encode($entry));
            $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

            $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['SAINT']);
            $entry = ['acnoid' => $acnoid, 'client' => $scharge[$key]->client, 'db' => 0, 'cr' => ($surcharge * $scharge[$key]->forex), 'postdate' => $scharge[$key]->dateid, 'cur' => $scharge[$key]->cur, 'forex' => $scharge[$key]->forex, 'fdb' => floatval($scharge[$key]->forex) == 1 ? 0 : $scharge[$key]->dateid, 'fcr' => 0, 'projectid' => $scharge[$key]->projectid];
            $this->othersClass->logConsole(json_encode($entry));
            $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
          }
        }
      }
    }

    //entry ar and vat if with default tax    
    $taxdef = $this->coreFunctions->getfieldvalue($this->head, "taxdef", "trno=?", [$trno]);
    if ($taxdef != 0) {
      $qry = "select client,forex,dateid,cur,branch,deptid,contra from " . $this->head . " where trno = ?";
      $d = $this->coreFunctions->opentable($qry, [$trno]);
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$d[0]->contra]);
      $entry = ['acnoid' => $acnoid, 'client' => $d[0]->client, 'db' => (($totalar + $taxdef) * $d[0]->forex), 'cr' => 0, 'postdate' => $d[0]->dateid, 'cur' => $d[0]->cur, 'forex' => $d[0]->forex, 'fdb' => floatval($d[0]->forex) == 1 ? 0 : $totalar + $taxdef, 'fcr' => 0];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ["TX2"]);
      $entry = ['acnoid' => $acnoid, 'client' => $d[0]->client, 'cr' => ($taxdef * $d[0]->forex), 'db' => 0, 'postdate' => $d[0]->dateid, 'cur' => $d[0]->cur, 'forex' => $d[0]->forex, 'fdb' => floatval($d[0]->forex) == 1 ? 0 : $taxdef, 'fcr' => 0];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }

    if ($delcharge != 0) {
      $qry = "select client,forex,dateid,cur,branch,deptid,contra from " . $this->head . " where trno = ?";
      $d = $this->coreFunctions->opentable($qry, [$trno]);
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['DC1']);
      $entry = ['acnoid' => $acnoid, 'client' => $d[0]->client, 'db' => 0, 'cr' => $delcharge * $d[0]->forex, 'postdate' => $d[0]->dateid, 'cur' => $d[0]->cur, 'forex' => $d[0]->forex, 'fcr' => floatval($d[0]->forex) == 1 ? 0 : $delcharge, 'fdb' => 0];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['acno']]);
      $entry = ['acnoid' => $acnoid, 'client' => $d[0]->client, 'db' => ($delcharge * $d[0]->forex), 'cr' => 0, 'postdate' => $d[0]->dateid, 'cur' => $d[0]->cur, 'forex' => $d[0]->forex, 'fdb' => floatval($d[0]->forex) == 1 ? 0 : $d[0]->dateid, 'fcr' => 0];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }

    if (!empty($this->acctg)) {
      $current_timestamp = $this->othersClass->getCurrentTimeStamp();
      foreach ($this->acctg as $key => $value) {
        foreach ($value as $key2 => $value2) {
          $this->acctg[$key][$key2] = $this->othersClass->sanitizekeyfield($key2, $value2);
        }
        $this->acctg[$key]['editdate'] = $current_timestamp;
        $this->acctg[$key]['editby'] = $config['params']['user'];
        $this->acctg[$key]['encodeddate'] = $current_timestamp;
        $this->acctg[$key]['encodedby'] = $config['params']['user'];
        $this->acctg[$key]['trno'] = $config['params']['trno'];
        $this->acctg[$key]['db'] = round($this->acctg[$key]['db'], 2);
        $this->acctg[$key]['cr'] = round($this->acctg[$key]['cr'], 2);
        $this->acctg[$key]['fdb'] = round($this->acctg[$key]['fdb'], 2);
        $this->acctg[$key]['fcr'] = round($this->acctg[$key]['fcr'], 2);
        $this->acctg[$key]['poref'] = $this->acctg[$key]['poref'];
        $this->acctg[$key]['podate'] = $this->acctg[$key]['podate'];
      }
      if ($this->coreFunctions->sbcinsert($this->detail, $this->acctg) == 1) {
        $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION SUCCESS');
        $status = true;
      } else {
        $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION FAILED');
        $status = false;
      }
    }

    return $status;
  } //end function

  public function distribution($params, $config)
  {

    $companyid = $config['params']['companyid'];
    $periodic = $this->companysetup->getisperiodic($config['params']);
    $entry = [];
    $forex = $params['forex'];
    $cur = $params['cur'];
    $sales = 0;
    if (floatval($forex) == 0) {
      $forex = 1;
    }
    //AR
    if (floatval($params['ar']) != 0) {
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['acno']]);
      $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'db' => ($params['ar'] * $forex), 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => floatval($forex) == 1 ? 0 : $params['ar'], 'fcr' => 0, 'projectid' => $params['projectid']];
      if ($companyid == 10) { //afti
        $entry['branch'] = $params['branch'];
        $entry['deptid'] = $params['deptid'];
        $entry['projectid'] = 0;
      }
      if ($companyid == 19) { //housegem
        if (date_format(date_create($params['deldate']), "Y-m-d") >= '2023-04-01') $entry['postdate'] = $params['deldate'];
      }
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }


    //disc
    if (floatval($params['discamt']) != 0) {
      $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['SD1']);
      $entry = ['acnoid' => $input, 'client' => $params['client'], 'db' => ($params['discamt'] * $forex), 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($params['discamt']), 'projectid' => $params['projectid']];
      if ($companyid == 10) { //afti
        $entry['branch'] = $params['branch'];
        $entry['deptid'] = $params['deptid'];
      }
      if ($companyid == 19) { //housegem
        if (date_format(date_create($params['deldate']), "Y-m-d") >= '2023-04-01') $entry['postdate'] = $params['deldate'];
      }
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }

    //INV
    if (!$periodic) {
      if (floatval($params['cost']) != 0) {
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['inventory']]);
        $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'db' => 0, 'cr' => $params['cost'], 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : $params['fcost'], 'fdb' => 0, 'projectid' => $params['projectid']];
        if ($companyid == 10) { //afti
          $entry['branch'] = $params['branch'];
          $entry['deptid'] = $params['deptid'];
        }
        if ($companyid == 19) { //housegem
          if (date_format(date_create($params['deldate']), "Y-m-d") >= '2023-04-01') $entry['postdate'] = $params['deldate'];
        }
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

        //cogs
        if ($params['expense'] == '') {
          $cogs = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['CG1']);
        } else {
          $cogs =  $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['expense']]);
        }
        $entry = ['acnoid' => $cogs, 'client' => $params['wh'], 'db' => $params['cost'], 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : $params['fcost'], 'projectid' => $params['projectid']];
        if ($companyid == 10) { //afti
          $entry['branch'] = $params['branch'];
          $entry['deptid'] = $params['deptid'];
        }
        if ($companyid == 19) { //housegem
          if (date_format(date_create($params['deldate']), "Y-m-d") >= '2023-04-01') $entry['postdate'] = $params['deldate'];
        }
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }
    }


    //rebate vitaline
    if (floatval($params['rebate']) != 0) {
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['AR3']);
      $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'db' => 0, 'cr' => $params['rebate'] * $forex, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : $params['rebate'], 'fdb' => 0, 'projectid' => $params['projectid']];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }

    if (floatval($params['tax']) != 0) {
      //sales
      $sales = ($params['ext'] - $params['rebate'] - $params['tax']);
      $sales  = $sales + $params['discamt'];
      if (floatval($sales) != 0) {
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['revenue']]);
        $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'cr' => ($sales * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : $sales, 'fdb' => 0, 'projectid' => $params['projectid']];
        if ($companyid == 10) { //afti
          $entry['branch'] = $params['branch'];
          $entry['deptid'] = $params['deptid'];
        }
        if ($companyid == 19) { //housegem
          if (date_format(date_create($params['deldate']), "Y-m-d") >= '2023-04-01') $entry['postdate'] = $params['deldate'];
        }
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }


      // output tax
      $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['TX2']);
      $entry = ['acnoid' => $input, 'client' => $params['client'], 'cr' => ($params['tax'] * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : ($params['tax']), 'fdb' => 0, 'projectid' => $params['projectid']];
      if ($companyid == 10) { //afti
        $entry['branch'] = $params['branch'];
        $entry['deptid'] = $params['deptid'];
        $entry['projectid'] = 0;
      }
      if ($companyid == 19) { //housegem
        if (date_format(date_create($params['deldate']), "Y-m-d") >= '2023-04-01') $entry['postdate'] = $params['deldate'];
      }
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    } else {
      //sales
      $sales = ($params['ext'] - $params['rebate']);
      $sales = round(($sales + $params['discamt']), 2);
      if (floatval($sales) != 0) {
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['revenue']]);
        $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'cr' => ($sales * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : $sales, 'fdb' => 0, 'projectid' => $params['projectid']];
        if ($companyid == 10) { //afti
          $entry['branch'] = $params['branch'];
          $entry['deptid'] = $params['deptid'];
        }
        if ($companyid == 19) { //housegem
          if (date_format(date_create($params['deldate']), "Y-m-d") >= '2023-04-01') $entry['postdate'] = $params['deldate'];
        }
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }
    }
  } //end function

  public function distributionvatex($params, $config)
  {
    $companyid = $config['params']['companyid'];
    $periodic = $this->companysetup->getisperiodic($config['params']);
    $entry = [];
    $forex = $params['forex'];
    $cur = $params['cur'];
    $sales = 0;
    if (floatval($forex) == 0) {
      $forex = 1;
    }

    //AR
    if (floatval($params['ar']) != 0) {
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['acno']]);
      $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'db' => (($params['ar'] + $params['tax']) * $forex), 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => floatval($forex) == 1 ? 0 : $params['ar'] + $params['tax'], 'fcr' => 0, 'projectid' => $params['projectid']];
      if ($companyid == 10) { //afti
        $entry['branch'] = $params['branch'];
        $entry['deptid'] = $params['deptid'];
        if ($params['sotrno'] != 0) {
          $entry['poref'] = $params['poref'];
          $entry['podate'] = $this->coreFunctions->getfieldvalue("hqshead", "due", "trno=?", [$params['sotrno']]);
        }
        $entry['projectid'] = 0;
      }
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }


    //disc
    if ($this->companysetup->getissalesdisc($config['params'])) {
      if (floatval($params['discamt']) != 0) {
        $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['SD1']);
        $entry = ['acnoid' => $input, 'client' => $params['client'], 'db' => ($params['discamt'] * $forex), 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($params['discamt']), 'projectid' => $params['projectid']];
        if ($companyid == 10) { //afti
          $entry['branch'] = $params['branch'];
          $entry['deptid'] = $params['deptid'];
        }
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }
    }


    //INV
    if (!$periodic) {
      if (floatval($params['cost']) != 0) {
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['inventory']]);
        $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'db' => 0, 'cr' => $params['cost'], 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : $params['fcost'], 'fdb' => 0, 'projectid' => $params['projectid']];
        if ($companyid == 10) { //afti
          $entry['branch'] = $params['branch'];
          $entry['deptid'] = $params['deptid'];
        }
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

        //cogs
        $cogs =  $params['expense'] == 0 ? $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['CG1']) : $params['expense'];
        $entry = ['acnoid' => $cogs, 'client' => $params['wh'], 'db' => $params['cost'], 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : $params['fcost'], 'projectid' => $params['projectid']];
        if ($companyid == 10) { //afti
          $entry['branch'] = $params['branch'];
          $entry['deptid'] = $params['deptid'];
        }
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }
    }

    //sales
    $sales = $params['ext'];
    if ($this->companysetup->getissalesdisc($config['params'])) {
      $sales = round(($sales + $params['discamt']), 2);
    }

    if (floatval($sales) != 0) {
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['revenue']]);
      $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'cr' => ($sales * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : $sales, 'fdb' => 0, 'projectid' => $params['projectid']];
      if ($companyid == 10) { //afti
        $entry['branch'] = $params['branch'];
        $entry['deptid'] = $params['deptid'];
      }
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }

    // output tax
    if ($params['tax'] != 0) {
      $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['TX2']);
      $entry = ['acnoid' => $input, 'client' => $params['client'], 'cr' => ($params['tax'] * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : ($params['tax']), 'fdb' => 0, 'projectid' => $params['projectid']];
      if ($companyid == 10) { //afti
        $entry['branch'] = $params['branch'];
        $entry['deptid'] = $params['deptid'];
        $entry['projectid'] = 0;
      }
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }
  } //end function

  public function getpaysummaryqry($config)
  {
    return "
    select arledger.docno,arledger.trno,arledger.line,ctbl.clientname,ctbl.client,forex.cur,forex.curtopeso as forex,arledger.acnoid,coa.acno,coa.acnoname,cntnum.center,
    arledger.clientid,arledger.db,arledger.cr, arledger.bal ,left(arledger.dateid,10) as dateid,
    abs(arledger.fdb-arledger.fcr) as fdb,glhead.yourref,gldetail.rem as drem,glhead.rem as hrem,gldetail.projectid,gldetail.subproject,
    gldetail.stageid,gldetail.branch,gldetail.deptid,gldetail.poref,gldetail.podate,coa.alias,gldetail.postdate,glhead.tax,glhead.vattype,glhead.ewt,glhead.ewtrate,a.client as agent from (arledger
    left join coa on coa.acnoid=arledger.acnoid)
    left join glhead on glhead.trno = arledger.trno
    left join gldetail on gldetail.trno=arledger.trno and gldetail.line=arledger.line
    left join cntnum on cntnum.trno = glhead.trno
    left join client as ctbl on ctbl.clientid = arledger.clientid
    left join client as a on a.clientid = glhead.agentid
    left join forex_masterfile as forex on forex.line = ctbl.forexid
    where cntnum.trno = ? and arledger.bal<>0";
  }


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
    if ($companyid == 10 || $companyid != 12) { //afti, not afti usd
    } else {
      $this->logger->sbcviewreportlog($config);
    }

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config['params']['dataid']);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  private function autocreatestock($config, $data2, $trno)
  {
    $wh = $data2['wh'];
    $rows = [];
    $msg = '';
    $qry = "
      select concat(stock.trno,stock.line) as keyid, stock.trno, stock.line, stock.itemid, item.barcode, item.itemname, stock.uom, so.docno, date(head.dateid) as dateid,
      (stock.iss-(stock.qa+stock.sjqa)) as iss,stock.isamt, stock.disc,stock.amt,stock.ext,
      ((stock.iss-(stock.qa+stock.sjqa))/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end) as isqty,stock.projectid,stock.sgdrate
      from hsqhead as so left join hqshead as head on head.sotrno=so.trno left join hqsstock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid
      left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
      left join transnum on transnum.trno = head.trno
      where so.doc='SQ' and stock.iss > (stock.qa+stock.sjqa) and stock.void = 0 and stock.trno=? order by stock.line
    ";
    $data = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($data)) {
      foreach ($data as $key2 => $value) {
        $config['params']['data']['uom'] = $data[$key2]->uom;
        $config['params']['data']['itemid'] = $data[$key2]->itemid;
        $config['params']['trno'] = $config['params']['trno'];
        $config['params']['data']['disc'] = $data[$key2]->disc;
        $config['params']['data']['qty'] = $data[$key2]->isqty;
        $config['params']['data']['ext'] = $data[$key2]->ext;
        $config['params']['data']['wh'] = $wh;
        $config['params']['data']['rem'] = '';
        $config['params']['data']['refx'] = $data[$key2]->trno;
        $config['params']['data']['linex'] = $data[$key2]->line;
        $config['params']['data']['ref'] = $data[$key2]->docno;
        $config['params']['data']['amt'] = $data[$key2]->isamt;
        $config['params']['data']['projectid'] = $data[$key2]->projectid;
        $config['params']['data']['sgdrate'] = $data[$key2]->sgdrate;
        $return = $this->additem('insert', $config);

        if ($msg = '') {
          $msg = $return['msg'];
        } else {
          $msg = $msg . $return['msg'];
        }

        if ($return['status']) {
          if ($this->setservedsqitems($data[$key2]->trno, $data[$key2]->line) == 0) {
            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
            $line = $return['row'][0]->line;
            $config['params']['trno'] = $return['row'][0]->trno;
            $config['params']['line'] = $line;
            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $return['row'][0]->trno, 'line' => $line]);
            $this->setservedsqitems($data[$key2]->trno, $data[$key2]->line);
            $row = $this->openstockline($config);
            $return = ['row' => $row, 'status' => true, 'msg' => $msg];
          }
          array_push($rows, $return['row'][0]);
        }
      }
      return ['row' => $rows, 'status' => true, 'msg' => 'Item was successfully added.', 'reloaddata' => true];
    }
  }


  public function recomputestock($head, $config)
  {
    $data = $this->openstock($head['trno'], $config);
    $data2 = json_decode(json_encode($data), true);
    $exec = true;
    $companyid = $config['params']['companyid'];
    $deci = $this->companysetup->getdecimal('price', $config['params']);
    foreach ($data2 as $key => $value) {
      $damt = $this->othersClass->sanitizekeyfield('amt', $data2[$key][$this->damt]);
      $dqty = $this->othersClass->sanitizekeyfield('qty', round($data2[$key][$this->dqty], $this->companysetup->getdecimal('qty', $config['params'])));
      if ($companyid == 10) { //afti
        if ($data[$key]->disc != "") {
          $computedata = $this->othersClass->computestock(
            $damt * $head['forex'],
            $data[$key]->disc,
            $dqty,
            $data[$key]->uomfactor,
            0,
            '',
            0,
            1
          );
          $computedata['amt']  = number_format($computedata['amt'], 2, '.', '');
        } else {
          $computedata = $this->othersClass->computestock(
            $damt * $head['forex'],
            $data[$key]->disc,
            $damt,
            $data[$key]->uomfactor,
            0
          );
          $computedata['amt']  = number_format($computedata['amt'], $deci, '.', '');
        }

        $computedata['amt'] = $this->othersClass->sanitizekeyfield('amt', $computedata['amt']);

        $exec = $this->coreFunctions->execqry("update lastock set amt = " . $computedata['amt'] . " where trno = " . $head['trno'] . " and line=" . $data[$key]->line, "update");
      } else {
        $computedata = $this->othersClass->computestock(
          $damt * $head['forex'],
          $data[$key]->disc,
          $dqty,
          $data[$key]->uomfactor,
          0
        );

        $computedata['amt']  = number_format($computedata['amt'], $deci, '.', '');
        $computedata['amt'] = $this->othersClass->sanitizekeyfield('amt', $computedata['amt']);

        $exec = $this->coreFunctions->execqry("update lastock set amt = " . $computedata['amt'] . " where trno = " . $head['trno'] . " and line=" . $data[$key]->line, "update");
      }
    }
    return $exec;
  }

  public function getserialout($config)
  {
    $dinsert = [];
    $trno = $config['params']['trno'];

    foreach ($config['params']['rows'] as $key => $value) {
      $dinsert['trno'] = $trno;
      $dinsert['line'] = $config['params']['rows'][$key]['stockline'];
      $dinsert['serial'] = $config['params']['rows'][$key]['serial'];
      $sline = $config['params']['rows'][$key]['sline'];
      $outline = $this->coreFunctions->insertGetId('serialout', $dinsert);
      if ($outline != 0) {
        $qry = "update serialin set outline=? where sline=? and outline=0";
        $this->coreFunctions->execqry($qry, 'update', [$outline, $sline]);
      }
      $stockline = $config['params']['rows'][$key]['stockline'];
    }

    $data = $this->openstock($trno, $config);
    return ['status' => true, 'reloadgriddata' => true, 'msg' => 'Serial has been added.', 'griddata' => ['inventory' => $data]];
  } //end function  
} //end class
