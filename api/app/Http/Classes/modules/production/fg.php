<?php

namespace App\Http\Classes\modules\production;

use Illuminate\Http\Request;
use DB;
use Session;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Milon\Barcode\DNS1D;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\builder\helpClass;
use App\Http\Classes\modules\calendar\em;
use Exception;

class fg
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'FINISH GOODS ENTRY';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => false, 'show' => true, 'showdate' => true];
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
  private $stockselect;
  public $dqty = 'rrqty';
  public $hqty = 'qty';
  public $damt = 'rrcost';
  public $hamt = 'cost';
  public $defaultContra = 'AP1';

  private $fields = ['trno', 'docno', 'dateid', 'yourref', 'ourref', 'rem', 'wh', 'pdtrno', 'stageid'];
  private $except = ['trno', 'dateid'];
  private $acctg = [];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  private $reporter;
  private $helpClass;

  public $showfilterlabel = [
    ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
    ['val' => 'posted', 'label' => 'Posted', 'color' => 'primary'],
    ['val' => 'all', 'label' => 'All', 'color' => 'primary']
  ];

  private $barcode;

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
    $this->barcode = new  DNS1D;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 3692,
      'edit' => 3693,
      'new' => 3694,
      'save' => 3695,
      'delete' => 3696,
      'print' => 3697,
      'lock' => 3698,
      'unlock' => 3699,
      'changeamt' => 3704,
      'post' => 3700,
      'unpost' => 3701,
      'additem' => 3702,
      'edititem' => 3705,
      'deleteitem' => 3703
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    if ($config['params']['companyid'] == 3) { //conti
      $this->showfilterlabel = [
        ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
        ['val' => 'locked', 'label' => 'Locked', 'color' => 'primary'],
        ['val' => 'posted', 'label' => 'Posted', 'color' => 'primary']
      ];
    }

    $action = 0;
    $liststatus = 1;
    $listdocument = 2;
    $listdate = 3;
    $listclientname = 4;
    switch ($config['params']['companyid']) {
      case 10: //afti 
      case 12: //afti usd
        $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'invoiceno', 'yourref', 'ourref', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
        break;

      default:
        $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listpddocno', 'yourref', 'ourref', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
        break;
    }

    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$listclientname]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;';

    switch ($config['params']['companyid']) {
      case 10: //afti
      case 12: //afti usd
        $cols[5]['label'] = 'Supplier Invoice';
        $cols[6]['label'] = 'Customer PO';
        $cols[6]['align'] = 'text-left';
        $cols[7]['align'] = 'text-left';
        $cols[8]['label'] = 'Post Date';
        break;
      default:
        $cols[5]['align'] = 'text-left';
        $cols[6]['align'] = 'text-left';
        $cols[7]['label'] = 'Post Date';
        break;
    }
    return $cols;
  }

  public function paramsdatalisting($config)
  {
    $isshortcutpo = $this->companysetup->getisshortcutpo($config['params']);

    $fields = [];
    if ($isshortcutpo) {
      $allownew = $this->othersClass->checkAccess($config['params']['user'], 81);
      if ($allownew == '1') {
        array_push($fields, 'pickpo');
      }
    }
    $col1 = $this->fieldClass->create($fields);

    $data = [];

    return ['status' => true, 'data' => $data, 'txtfield' => ['col1' => $col1]];
  }

  public function loaddoclisting($config)
  {
    $isproject = $this->companysetup->getisproject($config['params']);
    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));
    $itemfilter = $config['params']['itemfilter'];
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];

    $condition = '';
    $projectfilter = '';
    $searchfilter = $config['params']['search'];
    $limit = "limit 150";

    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'head.clientname', 'head.createby', 'head.editby', 'head.viewby', 'num.postedby', 'head.yourref', 'head.ourref', 'pd.docno'];

      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
      $limit = "";
    }

    if ($isproject) {
      $viewall = $this->othersClass->checkAccess($config['params']['user'], 2232);
      $project = $this->coreFunctions->getfieldvalue("useraccess", "project", "username=?", [$config['params']['user']]);
      $projectid = $this->coreFunctions->getfieldvalue("projectmasterfile", "line", "code=?", [$project]);
      if ($viewall == '0') {
        $projectfilter = " and head.projectid = " . $projectid . " ";
      }
    }
    $status = "'DRAFT'";

    switch ($itemfilter) {
      case 'draft':
        $condition = ' and num.postdate is null ';
        if ($companyid == 3) { //conti
          $condition = ' and num.postdate is null and head.lockdate is null ';
        }
        break;

      case 'locked':
        $condition = ' and num.postdate is null and head.lockdate is not null ';
        $status = "'LOCKED'";
        break;

      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
    }
    $companyid = $config['params']['companyid'];
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $fields = ",date_format(head.dateid,'%m-%d-%Y') as dateid, head.invoiceno";
        break;
      default:
        $fields = ",left(head.dateid,10) as dateid";

        break;
    }
    $qry = "select head.trno, head.docno, head.clientname, 'DRAFT' as status, head.createby, head.editby, head.viewby, num.postedby,
        date(num.postdate) as postdate, head.yourref, head.ourref, pd.docno as pddocno $fields
      from " . $this->head . " as head 
        left join " . $this->tablenum . " as num on num.trno=head.trno 
        left join hpdhead as pd on pd.trno=head.pdtrno
        where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $projectfilter . $condition . " $filtersearch
      union all
      select head.trno,head.docno,head.clientname,'POSTED' as status, head.createby,head.editby,head.viewby, num.postedby,
        date(num.postdate) as postdate, head.yourref, head.ourref, pd.docno as pddocno $fields
      from " . $this->hhead . " as head 
        left join " . $this->tablenum . " as num on num.trno=head.trno 
        left join hpdhead as pd on pd.trno=head.pdtrno
        where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $projectfilter . $condition . " $filtersearch
        order by dateid desc, docno, pddocno desc " . $limit;

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
      'help'
    );

    if ($config['params']['companyid'] == 14) { //majesty
      array_push($btns, 'others');
    }

    $buttons = $this->btnClass->create($btns);
    $step1 = $this->helpClass->getFields(['btnnew', 'supplier', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
    $step2 = $this->helpClass->getFields(['btnedit', 'supplier', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
    $step3 = $this->helpClass->getFields(['btnadditem', 'btnquickadd', 'rrqty', 'uom', 'rrcost', 'disc', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
    $step4 = $this->helpClass->getFields(['rrqty', 'uom', 'rrcost', 'disc', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
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

    if ($config['params']['companyid'] == 14) { //majesty
      $buttons['others']['items'] = [
        'uploadexcel' => ['label' => 'Upload Items', 'todo' => ['type' => 'uploadexcel', 'action' => 'uploadexcel', 'lookupclass' => 'uploadexcel', 'access' => 'view']]
      ];
    }

    return $buttons;
  } // createHeadbutton


  public function createtab2($access, $config)
  {
    return [];
  }

  public function createTab($access, $config)
  {
    $action = 0;
    $itemdesc = 1;
    $rrqty = 2;
    $uom = 3;
    $rrcost = 4;
    $ext = 5;
    $void = 6;
    $barcode = 7;
    $disc = 8;
    $wh = 9;
    $loc = 10;

    $column = ['action', 'itemdescription', 'rrqty', 'uom', 'rrcost', 'ext', 'void', 'barcode', 'disc', 'wh', 'loc'];
    $sortcolumn =  ['action', 'itemdescription', 'rrqty', 'uom', 'rrcost', 'ext', 'void', 'barcode', 'disc', 'wh', 'loc'];
    $stockbuttons = ['save', 'delete'];

    $tab = [
      $this->gridname => [
        'gridcolumns' => $column, 'sortcolumns' => $sortcolumn,
        'computefield' => ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'disc' => 'disc', 'total' => 'ext'],
        'headgridbtns' => ['viewdistribution']
      ]
    ];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][$barcode]['type'] = 'hidden';
    $obj[0][$this->gridname]['columns'][$barcode]['label'] = '';
    $obj[0][$this->gridname]['columns'][$rrcost]['label'] = 'Unit Cost';
    $obj[0][$this->gridname]['columns'][$ext]['label'] = 'Amount';
    $obj[0][$this->gridname]['columns'][$disc]['type'] = 'hidden';
    $obj[0][$this->gridname]['columns'][$disc]['label'] = '';
    $obj[0][$this->gridname]['columns'][$wh]['type'] = 'hidden';
    $obj[0][$this->gridname]['columns'][$wh]['label'] = '';
    $obj[0][$this->gridname]['columns'][$loc]['type'] = 'coldel';

    if (!$access['changeamt']) {
      $obj[0][$this->gridname]['columns'][$rrcost]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$disc]['readonly'] = true;
    }

    if ($config['params']['companyid'] == 24) {
      $obj[0][$this->gridname]['columns'][$itemdesc]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$loc]['type'] = 'input';
      $obj[0][$this->gridname]['columns'][$loc]['label'] = 'Batch No';
      $obj[0][$this->gridname]['columns'][$loc]['readonly'] = false;
    }


    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    if ($config['params']['companyid'] == 24) { //goodfound
      $tbuttons = ['additem', 'saveitem', 'deleteallitem'];
    } else {
      $tbuttons = ['saveitem', 'deleteallitem'];
    }
    $obj = $this->tabClass->createtabbutton($tbuttons);
    if ($config['params']['companyid'] != 24) { //not goodfound
      $obj[0]['label'] = "SAVE ALL";
      $obj[1]['label'] = "DELETE ALL";
    }
    return $obj;
  }

  public function createHeadField($config)
  {
    switch ($config['params']['companyid']) {
      case 24: //goodfound
        $fields = ['docno', 'wh'];
        break;
      default:
        $fields = ['docno', 'pddocno', 'pdprocess'];
        break;
    }
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'docno.label', 'Transaction#');
    data_set($col1, 'pddocno.required', true);
    data_set($col1, 'pddocno.condition', ['checkstock']);
    data_set($col1, 'pdprocess.condition', ['checkstock']);

    switch ($config['params']['companyid']) {
      case 24: //goodfound
        $fields = ['dateid', ['yourref', 'ourref']];
        break;
      default:
        $fields = ['dateid', 'wh', ['yourref', 'ourref']];
        break;
    }

    $col2 = $this->fieldClass->create($fields);

    $fields = ['rem'];
    $col3 = $this->fieldClass->create($fields);

    $fields = [];
    $col4 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
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
    $data[0]['pdtrno'] = 0;
    $data[0]['pddocno'] = '';
    $data[0]['pdprocess'] = '';
    $data[0]['stageid'] = 0;
    $data[0]['itemid'] = 0;
    if ($params['companyid'] == 24) { //goodfound
      $data[0]['wh'] = 'WH0000000000002';
    } else {
      $data[0]['wh'] = $this->companysetup->getwh($params);
    }
    $name = $this->coreFunctions->getfieldvalue('client', 'clientname', 'client=?', [$data[0]['wh']]);
    $data[0]['whname'] = $name;
    return $data;
  }

  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $trno = $config['params']['trno'];
    $center = $config['params']['center'];
    $isproject = $this->companysetup->getisproject($config['params']);
    $projectfilter = "";

    if ($trno == 0) {
      $trno = $this->othersClass->readprofile('TRNO', $config);
      if ($trno == '') {
        $trno = $this->coreFunctions->datareader("select trno as value from " . $this->tablenum . " where doc=? and center=? order by trno desc limit 1", [$doc, $center]);
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
    $tablenum = $this->tablenum;

    $qryselect = "select num.center, date(head.dateid) as dateid, head.trno, head.docno, head.yourref, head.ourref, head.rem, head.pdtrno, head.stageid, pd.docno as pddocno, st.stage as pdprocess, warehouse.client as wh, warehouse.clientname as whname";
    $qry = $qryselect . " from " . $table . " as head
        left join " . $tablenum . " as num on num.trno = head.trno
        left join client as warehouse on warehouse.client = head.wh
        left join hpdhead as pd on pd.trno=head.pdtrno
        left join stagesmasterfile as st on st.line=head.stageid
        where head.trno=? and num.doc=? and num.center=?
        union all " . $qryselect . " from " . $htable . " as head
        left join " . $tablenum . " as num on num.trno = head.trno
        left join client as warehouse on warehouse.clientid = head.whid
        left join hpdhead as pd on pd.trno=head.pdtrno
        left join stagesmasterfile as st on st.line=head.stageid
        where head.trno=? and num.doc=? and num.center=?";
    $head = $this->coreFunctions->opentable($qry, [$trno, $doc, $center, $trno, $doc, $center]);
    if (!empty($head)) {
      $stock = $this->openstock($trno, $config);
      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }
      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);

      $receivedby = $this->coreFunctions->datareader("select receivedby as value from cntnum  where trno=?", [$trno]);

      $lblreceived_stat = $receivedby == "" ? true : false;
      $hideobj = ['lblreceived' => $lblreceived_stat];
      $hideheadergridbtns = ['tagreceived' => !$lblreceived_stat, 'untagreceived' => $lblreceived_stat];

      return  [
        'head' => $head, 'griddata' => ['inventory' => $stock], 'islocked' => $islocked, 'isposted' => $isposted,
        'isnew' => false, 'status' => true, 'msg' => $msg, 'hideobj' => $hideobj, 'hideheadgridbtns' => $hideheadergridbtns
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
      if ($this->coreFunctions->sbcinsert($this->head, $data) == 1) {
        $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$data['wh']]);
        if ($data['pdtrno'] != '') {
          if ($data['stageid'] != 0 && $head['itemid'] != 0) {
            $uom = $this->coreFunctions->getfieldvalue('item', 'uom', 'itemid=?', [$head['itemid']]);
            $line = $this->coreFunctions->datareader("select line as value from lastock where trno=? order by line desc limit 1", [$head['trno']]);
            if ($line == '') $line = 0;
            $line = $line + 1;
            $dd = [
              'trno' => $head['trno'],
              'line' => $line,
              'itemid' => $head['itemid'],
              'uom' => $uom,
              'disc' => '',
              'rem' => '',
              $this->damt => 0,
              $this->hamt => 0,
              $this->dqty => 0,
              $this->hqty => 0,
              'ext' => 0,
              'qa' => 0,
              'void' => 0,
              'refx' => $data['pdtrno'],
              'linex' => 0,
              'ref' => '',
              'sku' => '',
              'loc' => '',
              'stageid' => 0,
              'whid' => $whid
            ];
            $this->coreFunctions->sbcinsert($this->stock, $dd);
          } else {
            $itemid = $this->coreFunctions->getfieldvalue('hpdhead', "itemid", "trno=?", [$data['pdtrno']]);
            $uom = $this->coreFunctions->getfieldvalue('hpdhead', 'uom', 'trno=?', [$data['pdtrno']]);
            $qty = $this->coreFunctions->getfieldvalue('hpdhead', 'qty', 'trno=?', [$data['pdtrno']]);
            $qry = "select line as value from lastock where trno=? order by line desc limit 1";
            $line = $this->coreFunctions->datareader($qry, [$head['trno']]);
            if ($line == '') $line = 0;
            $line = $line + 1;
            $dd = [
              'trno' => $head['trno'],
              'line' => $line,
              'itemid' => $itemid,
              'uom' => $uom,
              'disc' => '',
              'rem' => '',
              $this->damt => 0,
              $this->hamt => 0,
              $this->dqty => $qty,
              $this->hqty => $qty,
              'ext' => 0,
              'qa' => 0,
              'void' => 0,
              'refx' => $data['pdtrno'],
              'linex' => 0,
              'ref' => '',
              'sku' => '',
              'loc' => '',
              'stageid' => 0,
              'whid' => $whid
            ];
            $current_timestamp = $this->othersClass->getCurrentTimeStamp();
            $this->coreFunctions->sbcinsert($this->stock, $dd);
          }
        }
        $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['pdtrno']);
      }
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
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function


  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $companyid = $config['params']['companyid'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    if (!$this->othersClass->checkserialout($config)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. There are serialized items. To proceed, please encode the serial number.'];
    }

    if ($this->companysetup->isinvonly($config['params'])) {
      return $this->othersClass->posttranstock($config);
    } else {

      if ($companyid != 24) { //not goodfound
        $checkacct = $this->othersClass->checkcoaacct(['RM1', 'IN1']);

        if ($checkacct != '') {
          return ['trno' => $trno, 'status' => false, 'msg' => 'Accounts not yet setup:' . $checkacct];
        }
      }

      if (!$this->createdistribution($config)) {
        return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. Problems in creating accounting entries.'];
      } else {
        $return = $this->othersClass->posttranstock($config);
        return $return;
      }
    }
  } //end function


  public function createdistribution($config)
  {
    $trno = $config['params']['trno'];
    $companyid =  $config['params']['companyid'];

    if ($companyid == 24)  return true; //goodfound

    $status = true;
    $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);

    $qry = 'select head.dateid,head.client,head.tax,head.contra,head.cur,head.forex,stock.qty * stock.cost as ext,wh.client as wh,
    ifnull(item.asset,"") as asset,ifnull(item.revenue,"") as revenue,stock.isamt,stock.disc,stock.isqty,stock.cost,stock.qty,stock.fcost,head.projectid,client.rev,stock.rebate,head.subproject,stock.stageid   
          from ' . $this->head . ' as head left join ' . $this->stock . ' as stock on stock.trno=head.trno
          left join item on item.itemid=stock.itemid left join client on client.client = head.client left join client as wh on wh.clientid = stock.whid where head.trno=?';

    $stock = $this->coreFunctions->opentable($qry, [$trno]);
    $tax = 0;
    if (!empty($stock)) {
      $invacct = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['IN1']);
      $revacct = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['WIP']);

      $cur = $this->coreFunctions->getfieldvalue($this->head, 'cur', 'trno=?', [$trno]);
      foreach ($stock as $key => $value) {
        $params = [];
        if ($stock[$key]->revenue != '') {
          $revacct = $stock[$key]->revenue;
        } else {
          if ($stock[$key]->rev != '' && $stock[$key]->rev != '\\') {
            $revacct = $stock[$key]->rev;
          }
        }

        $params = [
          'client' => $stock[$key]->client,
          'acno' => $stock[$key]->contra,
          'ext' => $stock[$key]->ext,
          'wh' => $stock[$key]->wh,
          'date' => $stock[$key]->dateid,
          'inventory' => $stock[$key]->asset !== '' ? $stock[$key]->asset : $invacct,
          'revenue' => $revacct,
          'tax' =>  0,
          'discamt' => 0,
          'cur' => $stock[$key]->cur,
          'forex' => $stock[$key]->forex,
          'cost' => $stock[$key]->cost * $stock[$key]->qty,
          'fcost' => $stock[$key]->fcost * $stock[$key]->qty,
          'stage' => $stock[$key]->stageid

        ];
        $this->distribution($params, $config);
      }
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
    //$doc,$trno,$client,$acno,$alias,$amt,$famt,$charge,$cogsamt,$wh,$date,$project='',$inventory='',$cogs='',$tax=0,$rem='',$revenue='',$disc='',$discamt=0
    $entry = [];
    $forex = $params['forex'];
    $cur = $params['cur'];
    $sales = 0;
    if (floatval($forex) == 0) {
      $forex = 1;
    }

    if (floatval($params['ext']) != 0) {
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['inventory']]);
      $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'db' => ($params['ext'] * $forex), 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => floatval($forex) == 1 ? 0 : $params['ext'], 'fcr' => 0,  'stageid' => $params['stage']];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['revenue']]);
      $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'db' => 0, 'cr' => $params['cost'], 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : $params['fcost'], 'fdb' => 0,  'stageid' => $params['stage']];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }
  } //end function

  public function unposttrans($config)
  {
    return $this->othersClass->unposttranstock($config);
  } //end function

  private function getstockselect($config)
  {
    $sqlselect = "select stock.trno, stock.line, stock.itemid, item.barcode, item.itemname, stock.uom, stock.disc, stock.rem, stock.loc,
      stock." . $this->hamt . ", stock." . $this->hqty . " as qty,
      FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . ") as " . $this->damt . ",
      FORMAT(stock." . $this->dqty . "," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as " . $this->dqty . ",
      format(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext, 
      case when stock.void=0 then 'false' else 'true' end as void, stock.refx, stock.linex, stock.ref,
      case when stock.void=0 then '' else 'bg-red-2' end as errcolor,
      stock.whid, warehouse.client as wh, warehouse.clientname as whname, '' as bgcolor, 0 as qa";
    return $sqlselect;
  }

  public function openstock($trno, $config)
  {
    $sqlselect = $this->getstockselect($config);
    $qry = $sqlselect . " FROM " . $this->stock . " as stock left join item on item.itemid=stock.itemid left join client as warehouse on warehouse.clientid=stock.whid where stock.trno =? UNION ALL " . $sqlselect . " FROM " . $this->hstock . " as stock left join item on item.itemid=stock.itemid left join stagesmasterfile as stage on stage.line=stock.stageid left join client as warehouse on warehouse.clientid=stock.whid where stock.trno =? order by line";
    return $this->coreFunctions->opentable($qry, [$trno, $trno]);
  } //end function

  public function openstockline($config)
  {
    $sqlselect = $this->getstockselect($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = $sqlselect . " from " . $this->stock . " as stock left join item on item.itemid=stock.itemid left join client as warehouse on warehouse.clientid=stock.whid where stock.trno=? and stock.line=?";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $stock;
  } // end function

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'additem':
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
      case 'saveitem': //save all item edited
        return $this->updateitem($config);
        break;
      case 'saveperitem':
        return $this->updateperitem($config);
        break;
      case 'getpddetails':
        return $this->getpddetails($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function stockstatusposted($config)
  {
    $action = $config['params']['action'];
    if ($action == 'stockstatusposted') {
      $action = $config['params']['lookupclass'];
    }

    switch ($action) {
      case 'flowchart':
        return $this->flowchart($config);
        break;
      case 'diagram':
        return $this->diagram($config);
        break;
      case 'tagreceived':
        return $this->tagreceived($config);
        break;
      case 'untagreceived':
        return $this->untagreceived($config);
        break;
      case 'uploadexcel':
        return $this->uploadexcel($config);
        break;
      case 'makepayment':
        return $this->othersClass->generateShortcutTransaction($config, 0, 'RRCV');
        break;
      default:
        return ['status' => false, 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function getlatestprice($config)
  {
    $barcode = $config['params']['barcode'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
    $qry = "select docno,left(dateid,10) as dateid,round(amt,2) as amt,'' as disc,uom from(select head.docno,head.dateid,
          stock.cost/uom.factor as amt,stock.uom,stock.disc
          from lahead as head
          left join lastock as stock on stock.trno = head.trno
          left join cntnum on cntnum.trno=head.trno
          left join item on item.itemid = stock.itemid
          left join uom on uom.itemid = item.itemid
          where head.doc in ('RR','CM','IS','AJ','TS') and cntnum.center = ?
          and item.barcode = ?
          and stock.rrcost <> 0 and cntnum.trno <>?
          UNION ALL
          select head.docno,head.dateid,stock.cost/uom.factor as amt,
          stock.uom,stock.disc from glhead as head
          left join glstock as stock on stock.trno = head.trno
          left join item on item.itemid = stock.itemid
          left join client on client.clientid = head.clientid
          left join cntnum on cntnum.trno=head.trno
          left join uom on uom.itemid = item.itemid
          where head.doc in ('RR','CM','IS','AJ','TS') and cntnum.center = ?
          and item.barcode = ?
          and stock." . $this->damt . " <> 0 and cntnum.trno <>?
          order by dateid desc limit 5) as tbl order by dateid desc limit 1";
    $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $trno, $center, $barcode, $trno]);
    if (!empty($data)) {
      return ['status' => true, 'msg' => 'Found the latest purchase price...', 'data' => $data];
    } else {
      return ['status' => false, 'msg' => 'No Latest price found...'];
    }
  } // end function

  public function updateperitem($config)
  {
    $config['params']['data'] = $config['params']['row'];
    $isupdate = $this->additem('update', $config);
    $data = $this->openstockline($config);
    $data2 = json_decode(json_encode($data), true);
    // if(!$isupdate){
    //   $data[0]->errcolor = 'bg-red-2';
    // }
    $msg1 = '';
    $msg2 = '';
    foreach ($data2 as $key => $value) {
      if ($data2[$key][$this->dqty] == 0) {
        $data[$key]->errcolor = 'bg-red-2';
        $isupdate = false;
        if ($data[$key]->refx == 0) {
          $msg1 = ' Out of stock ';
        } else {
          $msg2 = ' Qty Received is Greater than PO Qty ';
        }
      }
    }

    if (!$isupdate) {
      return ['row' => $data, 'status' => true, 'msg' => $msg1 . '/' . $msg2];
    } else {
      $msg = "";
      return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved. -' . $msg];
    }
  }


  public function updateitem($config)
  {
    $msg = '';
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $update = $this->additem('update', $config);
      if ($msg != '') {
        $msg = $msg . ' ' . $update['msg'];
      } else {
        $msg = $update['msg'];
      }
    }
    $this->othersClass->getcreditinfo($config, $this->head);
    $data = $this->openstock($config['params']['trno'], $config);
    $data2 = json_decode(json_encode($data), true);
    $isupdate = true;
    $msg1 = '';
    $msg2 = '';
    foreach ($data2 as $key => $value) {
      if ($data2[$key][$this->dqty] == 0) {
        $data[$key]->errcolor = 'bg-red-2';
      }
    }
    return ['inventory' => $data, 'status' => true, 'msg' => $msg];
  } //end function


  public function addallitem($config)
  {
    $row = [];
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $row = $this->additem('insert', $config);
      $trno = isset($config['params']['data']['trno']) ? $config['params']['data']['trno'] : 0;
      $stageid = isset($config['params']['data']['stageid']) ? $config['params']['data']['stageid'] : 0;
      $this->setserveditems($trno, $stageid);
    }
    $data = $this->openstock($config['params']['trno'], $config);
    if ($config['params']['companyid'] == 8) { //maxipro
      return ['inventory' => $data, 'status' => true, 'msg' => $row['msg']];
    } else {
      return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    }
  } //end function


  // insert and update item
  public function additem($action, $config, $setlog = false)
  {
    $companyid = $config['params']['companyid'];
    $uom = $config['params']['data']['uom'];
    $itemid = $config['params']['data']['itemid'];
    $trno = $config['params']['trno'];
    $wh = $config['params']['data']['wh'];
    $pdtrno = isset($config['params']['data']['refx']) ? $config['params']['data']['refx'] : 0;
    $expiry = '';
    $rem = '';
    $loc = '';
    $disc = '';
    if (isset($config['params']['data']['rem'])) {
      $rem = $config['params']['data']['rem'];
    }
    $refx = 0;
    $linex = 0;
    $ref = '';
    $palletid = 0;
    $locid = 0;
    $projectid = 0;
    $void = 'false';

    if (isset($config['params']['data']['void'])) {
      $void = $config['params']['data']['void'];
    }
    if (isset($config['params']['data']['refx'])) {
      $refx = $config['params']['data']['refx'];
    }
    if (isset($config['params']['data']['linex'])) {
      $linex = $config['params']['data']['linex'];
    }
    if (isset($config['params']['data']['ref'])) {
      $ref = $config['params']['data']['ref'];
    }
    if (isset($config['params']['data']['palletid'])) {
      $palletid = $config['params']['data']['palletid'];
    }
    if (isset($config['params']['data']['locid'])) {
      $locid = $config['params']['data']['locid'];
    }
    if (isset($config['params']['data']['disc'])) {
      $disc = $config['params']['data']['disc'];
    }
    if (isset($config['params']['data']['loc'])) {
      $loc = $config['params']['data']['loc'];
    }
    if (isset($config['params']['data']['expiry'])) {
      $expiry = $config['params']['data']['expiry'];
    }
    $line = 0;
    if ($action == 'insert') {
      $qry = "select line as value from " . $this->stock . " where trno=? order by line desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$trno]);
      if ($line == '') $line = 0;
      $line = $line + 1;
      $config['params']['line'] = $line;
      $amt = $config['params']['data']['amt'];
      $qty = $config['params']['data']['qty'];
      if ($companyid == 10) { //afti
        $projectid = $this->coreFunctions->getfieldvalue("item", 'projectid', 'itemid=?', [$itemid]);
      }
    } elseif ($action == 'update') {
      $config['params']['line'] = $config['params']['data']['line'];
      $line = $config['params']['data']['line'];
      $amt = $config['params']['data'][$this->damt];
      $qty = $config['params']['data'][$this->dqty];
      $config['params']['line'] = $line;
      if ($companyid == 10) { //afti
        $projectid = $config['params']['data']['projectid'];
      }
    }
    $amt = round($this->othersClass->sanitizekeyfield('amt', $amt), $this->companysetup->getdecimal('price', $config['params']));
    $qty = $this->othersClass->sanitizekeyfield('qty', $qty);
    $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
    $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
    $factor = 1;
    if (!empty($item)) {
      $item[0]->factor = $this->othersClass->val($item[0]->factor);
      if ($item[0]->factor !== 0 ) $factor = $item[0]->factor;
    }
    $vat = $this->coreFunctions->getfieldvalue($this->head, 'tax', 'trno=?', [$trno]);
    $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);
    $qty = round($qty, $this->companysetup->getdecimal('qty', $config['params']));
    $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor, $vat);
    $data = [
      'trno' => $trno,
      'line' => $line,
      'itemid' => $itemid,
      $this->damt => $amt,
      $this->hamt => $computedata['amt'],
      $this->dqty => $qty,
      $this->hqty => $computedata['qty'],
      'ext' => $computedata['ext'],
      'disc' => $disc,
      'whid' => $whid,
      'refx' => $refx,
      'linex' => $linex,
      'ref' => $ref,
      'loc' => $loc,
      'expiry' => $expiry,
      'uom' => $uom,
      'palletid' => $palletid,
      'locid' => $locid,
      'void' => $void
    ];
    if ($companyid == 11) $data['rem'] = $rem; //summit
    switch ($companyid) {
      case 11: //summit
        $data['rem'] = $rem;
        break;
      case 10: //afti
        $data['projectid'] = $projectid;
        break;
    }
    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $data['editdate'] = $current_timestamp;
    $data['editby'] = $config['params']['user'];

    if ($action == 'insert') {
      $data['encodeddate'] = $current_timestamp;
      $data['encodedby'] = $config['params']['user'];
      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
        switch ($this->companysetup->getsystemtype($config['params'])) {
          case 'AIMS':
            if ($companyid == 0 || $companyid == 10) { //main,afti
              $stockinfo_data = [
                'trno' => $trno,
                'line' => $line,
                'rem' => $rem
              ];
              $this->coreFunctions->sbcinsert('stockinfo', $stockinfo_data);
            }
            break;
        }
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' Barcode:' . $item[0]->barcode . ' Amt:' . $amt . ' Disc:' . $disc . ' WH:' . $wh . ' Ext:' . $computedata['ext']);
        $row = $this->openstockline($config);
        return ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
      } else {
        return ['status' => false, 'msg' => 'Add item Failed'];
      }
    } elseif ($action == 'update') {
      if ($this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]) == 1) {
        switch ($companyid) {
          case 24: //goodfound
            break;
          default:
            $avqty = $this->coreFunctions->getfieldvalue('hpdhead', 'qty', 'trno=?', [$pdtrno]);
            if ($data[$this->dqty] > $avqty) {
              $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'OUT_STOCK', 'editdate' => $current_timestamp], ['trno' => $trno, 'line' => $line]);
              $this->logger->sbcwritelog($trno, $config, 'STOCK', 'OUT OF STOCK - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Qty' . $qty . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0');
              $msg = "(" . $item[0]->barcode . ") Qty is Greater than PD Qty.";
              return ['status' => false, 'msg' => $msg];
            }
            break;
        }
      }
      return true;
    }
  } // end function

  public function deleteallitem($config)
  {
    $companyid = $config['params']['companyid'];
    $trno = $config['params']['trno'];
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from serialin where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from stockinfo where trno=?', 'delete', [$trno]);
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL ITEMS');
    return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
  }

  public function deleteitem($config)
  {
    $companyid = $config['params']['companyid'];
    $config['params']['trno'] = $config['params']['row']['trno'];
    $config['params']['line'] = $config['params']['row']['line'];
    $data = $this->openstockline($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = "delete from " . $this->stock . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
    $qry = "delete from serialin where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
    $this->coreFunctions->execqry('delete from stockinfo where trno=? and line=?', 'delete', [$trno, $line]);
    $this->logger->sbcwritelog(
      $trno,
      $config,
      'STOCKINFO',
      'DELETE - Line:' . $line
        . ' Notes:' . $config['params']['row']['rem']
    );
    $data = json_decode(json_encode($data), true);
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' Barcode:' . $data[0]['barcode'] . ' Qty:' . $data[0][$this->dqty] . ' Amt:' . $data[0][$this->damt] . ' Disc:' . $data[0]['disc'] . ' WH:' . $data[0]['wh'] . ' Ext:' . $data[0]['ext']);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
  } // end function

  public function getpendingqty($refx, $linex, $config)
  {
    $qry = "select format(qty-qa," . $this->companysetup->getdecimal('qty', $config['params']) . ") as value from hpdstock where trno=? and line=?";
    $qty = $this->coreFunctions->datareader($qry, [$refx, $linex]);
    if ($qty === '') $qty = 0;
    return $qty;
  }

  public function getallqty($refx, $linex)
  {
    $qry2 = "select ifnull(sum(qty),0) as value from (select lastock.qty from lahead left join lastock on lastock.trno=lahead.trno where lahead.doc='RM' and lastock.refx='" . $refx . "' and lastock.linex='" . $linex . "' union all select qty from glhead left join glstock on glstock.trno=glhead.trno where glhead.doc='RM' and glstock.refx='" . $refx . "' and glstock.linex='" . $linex . "') as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty === '') $qty = 0;
    return $qty;
  }

  public function setserveditems($refx, $linex)
  {
    if ($refx == 0) return 1;
    $qry1 = "select stock." . $this->hqty . " from lahead as head left join lastock as stock on stock.trno=head.trno where head.doc='RM' and stock.refx=" . $refx . " and stock.linex=" . $linex . " union all select glstock." . $this->hqty . " from glhead left join glstock on glstock.trno=glhead.trno where glhead.doc='RM' and glstock.refx=" . $refx . " and glstock.linex=" . $linex;
    $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty == '') $qty = 0;
    return $this->coreFunctions->execqry("update hpdstock set qa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
  }

  public function getpddetails($config)
  {
    $trno = $config['params']['trno'];
    $rows = [];
    $msg = '';
    foreach ($config['params']['rows'] as $key => $value) {
      $data = $this->coreFunctions->opentable("select s.line, i.itemid, s.uom, s.disc, s.rem, s.cost, s.rrqty, s.rrcost, s.qty, s.ext, s.qa, s.void, s.refx, s.linex, s.ref, s.sku, s.loc, s.iss, s.stageid from hpdstock as s left join item as i on i.barcode=s.barcode where s.trno=? and s.line=?", [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line']]);
      $qry = "select item.barcode, item.itemname, ifnull(uom.factor,1) as factor, item.isnoninv from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
      $item = $this->coreFunctions->opentable($qry, [$data[0]->uom, $data[0]->itemid]);
      $isnoninv = 0;
      $factor = 1;
      if (!empty($item)) {
        $isnoninv = $item[0]->isnoninv;
        $item[0]->factor = $this->othersClass->val($item[0]->factor);
        if ($item[0]->factor !== 0 ) $factor = $item[0]->factor;
      }
      $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$config['params']['rows'][$key]['wh']]);
      $computedata = $this->othersClass->computestock($data[0]->rrcost, $data[0]->disc, $data[0]->qty, $factor, 0);

      $qry = "select line as value from lastock where trno=? order by line desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$trno]);
      if ($line == '') $line = 0;
      $line = $line + 1;
      $config['params']['data']['trno'] = $trno;
      $config['params']['data']['line'] = $line;
      $config['params']['data']['itemid'] = $data[0]->itemid;
      $config['params']['data']['uom'] = $data[0]->uom;
      $config['params']['data']['disc'] = $data[0]->disc;
      $config['params']['data']['rem'] = $data[0]->rem;
      $config['params']['data'][$this->damt] = $data[0]->rrcost;
      $config['params']['data'][$this->hamt] = round($computedata['amt'], 2);
      $config['params']['data'][$this->dqty] = $data[0]->rrqty;
      $config['params']['data'][$this->hqty] = $computedata['qty'];
      $config['params']['data']['ext'] = $computedata['ext'];
      $config['params']['data']['qa'] = $data[0]->qa;
      $config['params']['data']['void'] = $data[0]->void;
      $config['params']['data']['refx'] = $config['params']['rows'][$key]['trno'];
      $config['params']['data']['linex'] = $data[0]->line;
      $config['params']['data']['ref'] = $data[0]->ref;
      $config['params']['data']['sku'] = $data[0]->sku;
      $config['params']['data']['loc'] = $data[0]->loc;
      $config['params']['data']['stageid'] = $data[0]->stageid;
      $config['params']['data']['whid'] = $whid;
      $config['params']['data']['wh'] = $config['params']['rows'][$key]['wh'];

      $return = $this->additem('insert', $config);
      if ($msg = '') {
        $msg = $return['msg'];
      } else {
        $msg = $msg . $return['msg'];
      }
      if ($return['status']) {
        if ($this->setserveditems($config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line']) == 0) {
          $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
          $line = $return['row'][0]->line;
          $config['params']['trno'] = $trno;
          $config['params']['line'] = $line;
          $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
          $this->setserveditems($config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line']);
          $row = $this->openstockline($config);
          $return = ['row' => $row, 'status' => true, 'msg' => $msg];
        }
        array_push($rows, $return['row'][0]);
      }
    }
    return ['row' => $rows, 'status' => true, 'msg' => $msg];
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
    $this->logger->sbcviewreportlog($config);

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    // auto lock
    $date = date("Y-m-d H:i:s");
    $user = $config['params']['user'];
    $trno = $config['params']['dataid'];
    $this->coreFunctions->sbcupdate($this->head, ['lockdate' => $date, 'lockuser' => $user], ['trno' => $trno]);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
