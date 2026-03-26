<?php

namespace App\Http\Classes\modules\ati;

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

class rr
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'RECEIVING ITEMS';
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
  public $statlogs = 'cntnum_stat';
  public $tablelogs_del = 'del_table_log';
  private $stockselect;
  public $dqty = 'rrqty';
  public $hqty = 'qty';
  public $damt = 'rrcost';
  public $hamt = 'cost';
  public $defaultContra = 'AP1';

  private $fields = ['trno', 'docno', 'dateid', 'due', 'client', 'clientname', 'yourref', 'ourref', 'rem', 'terms', 'forex', 'cur', 'wh', 'address', 'contra', 'tax', 'vattype', 'projectid', 'subproject', 'branch', 'deptid', 'billid', 'shipid', 'billcontactid', 'shipcontactid', 'invoiceno', 'invoicedate', 'ewt', 'ewtrate'];
  private $except = ['trno', 'dateid', 'due'];
  private $otherfields = ['trno', 'isacknowledged', 'dropoffwh', 'trnxtype'];
  private $acctg = [];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  private $reporter;
  private $helpClass;

  public $showfilterlabel = [
    // ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
    // ['val' => 'initialreceiving', 'label' => 'For Initial Receiving', 'color' => 'primary'],
    // ['val' => 'finalreceiving', 'label' => 'For Final Receiving', 'color' => 'primary'],
    // ['val' => 'forchecking', 'label' => 'For Checking', 'color' => 'primary'],
    // // ['val' => 'acknowledged', 'label' => 'Acknowledged', 'color' => 'primary'],
    // ['val' => 'forposting', 'label' => 'For Posting', 'color' => 'primary'],
    // ['val' => 'forrevision', 'label' => 'For Revision', 'color' => 'primary'],
    // ['val' => 'posted', 'label' => 'Posted', 'color' => 'primary'],
    // ['val' => 'all', 'label' => 'All', 'color' => 'primary']
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
      'view' => 79,
      'edit' => 80,
      'new' => 81,
      'save' => 82,
      // 'change' => 83, remove change doc
      'delete' => 84,
      'print' => 85,
      'lock' => 86,
      'unlock' => 87,
      'acctg' => 90,
      'changeamt' => 91,
      'post' => 88,
      'unpost' => 89,
      'additem' => 811,
      'edititem' => 812,
      'deleteitem' => 813,
      'forchecking' => 4140,
      'generatecode' => 4608
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
    $whname = 5;
    $warehouse = 6;
    $yourref = 7;
    $ourref = 8;
    $rem = 9;
    $getcols = ['action', 'stat', 'listdocument', 'listdate', 'listclientname', 'whname', 'warehouse', 'yourref', 'ourref', 'rem', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];

    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$listclientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $cols[$liststatus]['label'] = 'Status';
    $cols[$warehouse]['label'] = 'Drop-off Warehouse';
    $cols[$rem]['type'] = 'coldel';

    $cols[7]['align'] = 'text-left';
    $cols[8]['align'] = 'text-left';
    $cols[9]['label'] = 'Post Date';

    $cols = $this->tabClass->delcollisting($cols);
    return $cols;
  }

  public function paramsdatalisting($config)
  {
    $isshortcutpo = $this->companysetup->getisshortcutpo($config['params']);

    $fields = ['stat'];
    if ($isshortcutpo) {
      $allownew = $this->othersClass->checkAccess($config['params']['user'], 81);
      if ($allownew == '1') {
        array_push($fields, 'pickpo');
      }
    }
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'stat.label', 'Status');
    data_set($col1, 'stat.type', 'lookup');
    data_set($col1, 'stat.action', 'lookuprrtransstatus');
    data_set($col1, 'stat.lookupclass', 'lookuprrtransstatus');

    $fields = ['sortby'];
    $col2 = $this->fieldClass->create($fields);

    data_set($col2, 'sortby.type', 'lookup');
    data_set($col2, 'sortby.readonly', true);
    data_set($col2, 'sortby.action', 'lookupsortby');
    data_set($col2, 'sortby.lookupclass', 'lookupsortby');

    $data = $this->coreFunctions->opentable("SELECT 'DRAFT' as stat, 'draft' as typecode, '' as sortby, '' as sortcode");

    return ['status' => true, 'data' => $data[0], 'txtfield' => ['col1' => $col1, 'col2' => $col2]];
  }

  public function loaddoclisting($config)
  {
    $isproject = $this->companysetup->getisproject($config['params']);
    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));

    $itemfilter = isset($config['params']['doclistingparam']['typecode']) ? $config['params']['doclistingparam']['typecode'] : 'draft';

    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $adminid = $config['params']['adminid'];

    $condition = '';
    $projectfilter = '';
    $searchfilter = $config['params']['search'];

    $limit = "limit 150";

    $filtersearch = "";
    if (isset($config['params']['search'])) {
      if ($config['params']['search'] != "") {
        $searchfield = ['head.docno', 'head.clientname', 'head.yourref', 'head.ourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];
        $search = $config['params']['search'];
        if ($search != "") {
          $filtersearch = $this->othersClass->multisearch($searchfield, $search);
        }
      }
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
    $bgcolor = "''";
    $leftjoin_item = '';



    switch ($itemfilter) {
      case 'draft':
        $condition = ' and num.postdate is null and num.statid=0';
        $bgcolor = "if(wh.clientname<>ifnull(dowh.clientname,''),'bg-deep-purple-3','')";
        break;

      case 'locked':
        $condition = ' and num.postdate is null and head.lockdate is not null ';
        $status = "'LOCKED'";
        break;



      case 'initialreceiving':
        $condition = ' and num.postdate is null and (num.statid=70 or sinfo.status1<>52)';
        $status = "ifnull(stat.status,'DRAFT')";
        $bgcolor = "if(wh.clientname<>ifnull(dowh.clientname,''),'bg-deep-purple-3','')";
        break;

      case 'finalreceiving':
        $condition = ' and num.postdate is null and num.statid in (71,72)';
        $status = "ifnull(stat.status,'DRAFT')";
        $bgcolor = "if(wh.clientname<>ifnull(dowh.clientname,''),'bg-deep-purple-3','')";
        break;

      case 'forchecking':
        $condition = ' and num.postdate is null and num.statid=45';
        $status = "ifnull(stat.status,'DRAFT')";
        break;

      case 'forrevision':
        $condition = " and num.postdate is null and info.instructions='For Revision'";

        $status = "concat(stat.status,' (',info.instructions,')')";
        break;


      case 'acknowledged':
        $condition = ' and num.postdate is null and num.statid=73';
        $status = "ifnull(stat.status,'DRAFT')";
        break;

      case 'forposting':
        $condition = ' and num.postdate is null and num.statid=39';
        $status = "ifnull(stat.status,'DRAFT')";
        break;

      case 'forgenerateassettag':
        $condition = ' and i.isgeneric=1 and num.postdate is null and num.statid=73';
        $status = "ifnull(stat.status,'DRAFT')";
        $leftjoin_item = 'left join item as i on i.itemid=stock.itemid';
        break;

      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;

      default:
        $status = "ifnull(stat.status,'DRAFT')";
        break;
    }


    $sortby = 'dateid desc, docno desc ';
    if (isset($config['params']['doclistingparam']['sortcode'])) {
      if ($config['params']['doclistingparam']['sortcode'] != '') {
        $sortby = $config['params']['doclistingparam']['sortcode'] . " ";
      }
    }

    $filterassetrequest = '';
    $leftjoin_assetrequest = '';


    $chkallwh = $this->othersClass->checkAccess($config['params']['user'], 4031);
    if ($chkallwh == 0) {
      $defaultwh = $this->coreFunctions->getfieldvalue("client", "wh", "clientid=?", [$adminid]);
      $dropoffwh = $this->coreFunctions->getfieldvalue("client", "dropoffwh", "clientid=?", [$adminid]);
      if ($defaultwh != "") {
        $isassetwh = $this->coreFunctions->datareader("select isassetwh as value from client where client in ('" . $defaultwh . "','" . $dropoffwh . "')", [], '', true);
        if ($isassetwh) {
          $filterassetrequest = ' and req.isgeneratefa=1';
          $leftjoin_assetrequest = ' left join hheadinfotrans as hinfo on hinfo.trno=stock.reqtrno left join reqcategory as req on req.line=hinfo.reqtypeid ';
        } else {
          $condition .= " and (wh.client='" . $defaultwh . "' or info.dropoffwh='" . $dropoffwh . "')";
        }
      }
    }

    $trnxx = '';
    if ($adminid != 0) {
      $trnx = $this->coreFunctions->getfieldvalue("client", "deptcode", "clientid=?", [$adminid]);
      $trnxx = " and info.trnxtype='" . $trnx . "' ";
    }

    $qry = "select head.trno,head.docno,head.clientname, 'DRAFT' as status, " . $status . " as stat,
    head.createby,head.editby,head.viewby,num.postedby, date(num.postdate) as postdate,wh.clientname as whname,dowh.clientname as warehouse,
     head.yourref, head.ourref, " . $bgcolor . " as bgcolor,left(head.dateid,10) as dateid, head.rem, head.dateid as transdate
     from " . $this->head . " as head left join " . $this->tablenum . " as num on num.trno=head.trno 
     left join trxstatus as stat on stat.line=num.statid
     left join client as wh on wh.client=head.wh
     left join cntnuminfo as info on info.trno=head.trno
     left join client as dowh on dowh.clientid=info.dropoffwh
     left join lastock as stock on stock.trno=head.trno
     left join stockinfo as sinfo on sinfo.trno=stock.trno and sinfo.line=stock.line " . $leftjoin_assetrequest . "
     $leftjoin_item
     where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=?  $trnxx" . $projectfilter . $condition . " " . $filtersearch . $filterassetrequest . " 
     group by head.trno,head.docno,head.clientname,stat.status,head.createby,head.editby,head.viewby,num.postedby,num.postdate,wh.clientname,dowh.clientname,head.yourref, head.ourref, head.dateid, head.rem,info.status,info.instructions
     union all
     select head.trno,head.docno,head.clientname,'POSTED' as status, 'POSTED' as stat,
     head.createby,head.editby,head.viewby, num.postedby, date(num.postdate) as postdate,wh.clientname as whname,dowh.clientname as warehouse,
      head.yourref, head.ourref, " . $bgcolor . " as bgcolor,left(head.dateid,10) as dateid, head.rem, head.dateid as transdate
     from " . $this->hhead . " as head left join " . $this->tablenum . " as num on num.trno=head.trno
     left join trxstatus as stat on stat.line=num.statid 
     left join client as wh on wh.clientid=head.whid
     left join hcntnuminfo as info on info.trno=head.trno
     left join client as dowh on dowh.clientid=info.dropoffwh
     left join glstock as stock on stock.trno=head.trno
     left join stockinfo as sinfo on sinfo.trno=stock.trno and sinfo.line=stock.line
     $leftjoin_item
     where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=?  $trnxx " . $projectfilter . $condition . " " . $filtersearch . "
     group by head.trno,head.docno,head.clientname,stat.status,head.createby,head.editby,head.viewby,num.postedby,num.postdate,wh.clientname,dowh.clientname,head.yourref, head.ourref, head.dateid, head.rem,info.status,info.instructions
     order by " .  $sortby . $limit;
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

    return $buttons;
  } // createHeadbutton


  public function createtab2($access, $config)
  {
    $billshipdefault = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewbillingshipping']];
    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrycntnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);
    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];

    return $return;
  }

  public function createTab($access, $config)
  {
    $isexpiry = $this->companysetup->getisexpiry($config['params']);
    $isproject = $this->companysetup->getisproject($config['params']);
    $ispallet = $this->companysetup->getispallet($config['params']);
    $isfa = $this->companysetup->getisfixasset($config['params']);
    $viewcost = $this->othersClass->checkAccess($config['params']['user'], 368);
    $rr_btnreceived_access = $this->othersClass->checkAccess($config['params']['user'], 2728);
    $rr_btnunreceived_access = $this->othersClass->checkAccess($config['params']['user'], 2729);
    $isacctgentry = $this->companysetup->getisacctgentry($config['params']);
    $allowassettag = $this->othersClass->checkAccess($config['params']['user'], 3619);

    $columns = [
      'action',
      'notectr',
      'ctrlno',
      'rrqty',
      'uom',
      'rrcost',
      'disc',
      'ext',
      'wh',
      'requestorname',
      'purpose',
      'barcode',
      'itemdesc',
      'specs',
      'unit',
      'rem',
      'ref',
      'amt1',
      'amt2',
      'amt3',
      'amt4',
      'amt5',
      'itemname',
      'clientname',
      'category',
      'intransit',
      'status1name',
      'qty1',
      'status2name',
      'qty2',
      'checkstatname',
      'ispicked',
      'tqty',
      'ismanual',
      'isasset',
      'waivedspecs'
    ];

    foreach ($columns as $key => $value) {
      $$value = $key;
    }

    $headgridbtns = ['viewref', 'viewdiagram'];

    if ($isacctgentry) {
      array_push($headgridbtns, 'viewdistribution', 'viewsobreakdown');
    }

    if ($isfa) {
    }

    $tab = [
      $this->gridname => [
        'gridcolumns' => $columns,
        'computefield' => ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'disc' => 'disc', 'total' => 'ext'],
        'headgridbtns' => $headgridbtns
      ]
    ];

    if ($allowassettag) {
      $tab['tableentry'] = ['action' => 'tableentry', 'lookupclass' => 'viewrrfams', 'label' => 'Asset Tag'];
    }

    if ($this->companysetup->getserial($config['params'])) {
      $stockbuttons = ['save', 'delete', 'serialin'];
    } else {
      $stockbuttons = ['save', 'delete', 'showbalance', 'viewhistoricalcomments', 'viewprinfo', 'showsobreakdown'];
    }

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    if ($viewcost == '0') {
      $obj[0]['inventory']['columns'][$rrcost]['type'] = 'coldel';
    }

    $obj[0]['inventory']['columns'][$ref]['lookupclass'] = 'refrr';

    if (!$access['changeamt']) {
      $obj[0]['inventory']['columns'][$rrcost]['readonly'] = true;
      $obj[0]['inventory']['columns'][$disc]['readonly'] = true;
    }

    $obj[0]['inventory']['columns'][$purpose]['type'] = 'label';
    $obj[0]['inventory']['columns'][$unit]['type'] = 'label';
    $obj[0]['inventory']['columns'][$rem]['type'] = 'textarea';

    $obj[0]['inventory']['columns'][$action]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $obj[0]['inventory']['columns'][$notectr]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $obj[0]['inventory']['columns'][$ctrlno]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $obj[0]['inventory']['columns'][$rrqty]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $obj[0]['inventory']['columns'][$uom]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $obj[0]['inventory']['columns'][$rrcost]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
    $obj[0]['inventory']['columns'][$disc]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $obj[0]['inventory']['columns'][$ext]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
    $obj[0]['inventory']['columns'][$wh]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $obj[0]['inventory']['columns'][$requestorname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $obj[0]['inventory']['columns'][$purpose]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $obj[0]['inventory']['columns'][$barcode]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $obj[0]['inventory']['columns'][$itemdesc]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;';
    $obj[0]['inventory']['columns'][$specs]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;';
    $obj[0]['inventory']['columns'][$unit]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
    $obj[0]['inventory']['columns'][$rem]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;';
    $obj[0]['inventory']['columns'][$ref]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $obj[0]['inventory']['columns'][$amt1]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';
    $obj[0]['inventory']['columns'][$amt2]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';
    $obj[0]['inventory']['columns'][$amt3]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';
    $obj[0]['inventory']['columns'][$amt4]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';
    $obj[0]['inventory']['columns'][$amt5]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';
    $obj[0]['inventory']['columns'][$itemname]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $obj[0]['inventory']['columns'][$clientname]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
    $obj[0]['inventory']['columns'][$category]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
    $obj[0]['inventory']['columns'][$intransit]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $obj[0]['inventory']['columns'][$status1name]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $obj[0]['inventory']['columns'][$qty1]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $obj[0]['inventory']['columns'][$status2name]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $obj[0]['inventory']['columns'][$qty2]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $obj[0]['inventory']['columns'][$checkstatname]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $obj[0]['inventory']['columns'][$ispicked]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $obj[0]['inventory']['columns'][$tqty]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $obj[0]['inventory']['columns'][$ismanual]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $obj[0]['inventory']['columns'][$isasset]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $obj[0]['inventory']['columns'][$waivedspecs]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';

    $obj[0]['inventory']['columns'][$barcode]['type'] = 'lookup';
    $obj[0]['inventory']['columns'][$barcode]['action'] = 'lookupbarcode';
    $obj[0]['inventory']['columns'][$barcode]['lookupclass'] = 'gridbarcode';

    $obj[0]['inventory']['columns'][$clientname]['label'] = 'Project';
    $obj[0]['inventory']['columns'][$clientname]['type'] = 'label';
    $obj[0]['inventory']['columns'][$category]['type'] = 'label';

    $obj[0]['inventory']['columns'][$unit]['label'] = 'Temp UOM';
    $obj[0]['inventory']['columns'][$ispicked]['label'] = 'For Transmittal';

    $obj[0]['inventory']['columns'][$ismanual]['checkfield'] = 'ismanual2';

    $obj[0]['inventory']['columns'][$amt1]['label'] = 'Delivery Fee';
    $obj[0]['inventory']['columns'][$amt2]['label'] = 'Diagnostic Fee';
    $obj[0]['inventory']['columns'][$amt3]['label'] = 'Installation Fee';
    $obj[0]['inventory']['columns'][$amt4]['label'] = ' Consultation Fee';
    $obj[0]['inventory']['columns'][$amt5]['label'] = ' Misc Fee';
    $obj[0]['inventory']['columns'][$isasset]['type'] = 'input';
    $obj[0]['inventory']['columns'][$isasset]['readonly'] = true;
    $obj[0]['inventory']['columns'][$waivedspecs]['type'] = 'coldel';

    $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $isproject = $this->companysetup->getisproject($config['params']);
    $isexpiry = $this->companysetup->getisexpiry($config['params']);

    $tbuttons = ['pendingpr', 'pendingpo', 'additem', 'quickadd', 'saveitem', 'deleteallitem']; //'pendingcd',

    if ($isproject) {
      $viewall = $this->othersClass->checkAccess($config['params']['user'], 2232);
      if ($viewall == '0') {
        $tbuttons = ['pendingpo', 'saveitem', 'deleteallitem'];
      }
    }

    $obj = $this->tabClass->createtabbutton($tbuttons);

    $obj[0]['label'] = "PR";

    $obj[1]['action'] = "pendingpodetail";
    $obj[1]['lookupclass'] = "pendingpodetail";

    return $obj;
  }

  public function createHeadField($config)
  {
    $allowassettag = $this->othersClass->checkAccess($config['params']['user'], 3619);

    $fields = ['docno', 'client', 'clientname', 'address'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.label', 'Vendor');
    data_set($col1, 'docno.label', 'Transaction#');

    $fields = [['dateid', 'terms'], ['due', 'dvattype'], 'dacnoname', 'dwhname', 'dropoffwarehouse'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'dacnoname.label', 'AP Account');
    data_set($col2, 'dwhname.condition', ['checkstock']);
    data_set($col2, 'dropoffwarehouse.labeldata', 'dowh~dowhname');
    data_set($col2, 'dateid.required', true);

    $fields = [['yourref', 'ourref'], ['cur', 'forex']];
    $col3 = $this->fieldClass->create($fields);

    $fields = ['rem', 'updatepostedinfo', 'forrevision', 'forreceiving', 'forchecking', 'intransit', 'acknowledged', 'create', 'generatecode', 'forposting'];
    $col4 = $this->fieldClass->create($fields); //actionbtn
    data_set($col4, 'updatepostedinfo.label', 'UPDATE INFO');
    data_set($col4, 'updatepostedinfo.access', 'view');

    data_set($col4, 'create.type', 'actionbtn');
    data_set($col4, 'create.label', 'GENERATE ASSET TAG FOR GENERIC ITEMS');
    data_set($col4, 'create.confirm', true);
    data_set($col4, 'create.confirmlabel', 'Generate asset tag?');
    data_set($col4, 'create.access', 'save');
    data_set($col4, 'create.lookupclass', 'stockstatusposted');
    data_set($col4, 'create.action', 'generatetag');
    data_set($col4, 'create.style', 'width:100%');
    data_set($col4, 'generatecode.style', 'width:100%');
    data_set($col4, 'generatecode.access', 'generatecode');
    data_set($col4, 'forchecking.access', 'forchecking');

    data_set($col4, 'forposting.name', 'backlisting');

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }



  public function createnewtransaction($docno, $params)
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['due'] = $this->othersClass->getCurrentDate();
    $data[0]['client'] = '';
    $data[0]['clientname'] = '';
    $data[0]['yourref'] = '';
    $data[0]['shipto'] = '';
    $data[0]['ourref'] = '';
    $data[0]['rem'] = '';
    $data[0]['terms'] = '';
    $data[0]['forex'] = 1;
    $data[0]['cur'] = $this->companysetup->getdefaultcurrency($params);
    $data[0]['tax'] = 0;
    $data[0]['vattype'] = 'NON-VATABLE';
    $data[0]['dvattype'] = '';
    $data[0]['contra'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', [$this->defaultContra]);
    $data[0]['acnoname'] = $this->coreFunctions->getfieldvalue('coa', 'acnoname', 'acno=?', [$data[0]['contra']]);
    // $data[0]['wh'] = $this->companysetup->getwh($params);
    // $name = $this->coreFunctions->getfieldvalue('client', 'clientname', 'client=?', [$data[0]['wh']]);
    // $data[0]['whname'] = $name;
    $data[0]['wh'] = '';
    $data[0]['whname'] = '';

    $data[0]['isacknowledged'] = '0';

    $isproject = $this->companysetup->getisproject($params);

    if ($isproject) {
      $viewall = $this->othersClass->checkAccess($params['user'], 2232);
      $data[0]['projectid'] = '0';
      $data[0]['projectname'] = '';
      $data[0]['projectcode'] = '';

      if ($viewall == '0') {
        $pid = $this->coreFunctions->getfieldvalue("useraccess", "project", "username=?", [$params['user']]);
        $data[0]['projectid'] = $this->coreFunctions->getfieldvalue("projectmasterfile", "line", "code=?", [$pid]);
        $data[0]['projectcode'] =  $pid;
        $data[0]['projectname'] = $this->coreFunctions->getfieldvalue("projectmasterfile", "name", "code=?", [$pid]);
      }
    } else {
      $data[0]['projectid'] = '0';
      $data[0]['projectname'] = '';
      $data[0]['projectcode'] = '';
    }

    $data[0]['dprojectname'] = '';
    $data[0]['subproject'] = '0';
    $data[0]['subprojectname'] = '';
    $data[0]['address'] = '';
    $data[0]['branchcode'] = '';
    $data[0]['branchname'] = '';
    $data[0]['dbranchname'] = '';
    $data[0]['branch'] = '0';
    $data[0]['ddeptname'] = '';
    $data[0]['deptid'] = '0';
    $data[0]['dept'] = '';
    $data[0]['billid'] = 0;
    $data[0]['shipid'] = 0;
    $data[0]['billcontactid'] = 0;
    $data[0]['shipcontactid'] = 0;
    $data[0]['invoiceno'] = '';
    $data[0]['invoicedate'] = $this->othersClass->getCurrentDate();
    $data[0]['ewt'] = '';
    $data[0]['dewt'] = '';
    $data[0]['ewtrate'] = 0;
    $data[0]['dowh'] = $this->companysetup->getwh($params);
    $name = $this->coreFunctions->getfieldvalue('client', 'clientname', 'client=?', [$data[0]['dowh']]);
    $dowhid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$data[0]['dowh']]);
    $data[0]['dowhname'] = $name;
    $data[0]['dropoffwh'] = $dowhid;
    $data[0]['trnxtype'] = '';
    if ($params['adminid'] != 0) {
      $data[0]['trnxtype'] = $this->coreFunctions->getfieldvalue("client", "deptcode", "clientid=?", [$params['adminid']]);
    }
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
    $statid = $this->othersClass->getstatid($config);
    $table = $this->head;
    $htable = $this->hhead;
    $tablenum = $this->tablenum;

    if ($isproject) {
      $viewall = $this->othersClass->checkAccess($config['params']['user'], 2232);
      $project = $this->coreFunctions->getfieldvalue("useraccess", "project", "username=?", [$config['params']['user']]);
      $projectid = $this->coreFunctions->getfieldvalue("projectmasterfile", "line", "code=?", [$project]);
      if ($viewall == '0') {
        $projectfilter = " and head.projectid = " . $projectid . " ";
      }
    }

    $adminid = $config['params']['adminid'];
    $trnxx = '';
    if ($adminid != 0) {
      $trnx = $this->coreFunctions->getfieldvalue("client", "deptcode", "clientid=?", [$adminid]);
      $trnxx = " and info.trnxtype='" . $trnx . "' ";
    }


    $qryselect = "select
         num.center,
         head.trno,
         head.docno,
         client.client,
         head.terms,
         head.cur,
         head.forex,
         head.yourref,
         head.ourref,
         head.contra,
         coa.acnoname,
         '' as dacnoname,
         left(head.dateid,10) as dateid,
         head.clientname,
         head.address,
         head.shipto,
         date_format(head.createdate,'%Y-%m-%d') as createdate,
         head.rem,
         head.tax,
         head.vattype,
         head.billid,
         head.shipid,
         head.billcontactid,
         head.shipcontactid,
         '' as dvattype,
         ifnull(warehouse.client,'') as wh,
         ifnull(warehouse.clientname,'') as whname,
         '' as dwhname,
         head.projectid,
         '' as dprojectname,
         left(head.due,10) as due,
         client.groupid,ifnull(p.code,'') as projectcode,ifnull(p.name,'') as projectname,ifnull(s.line,0) as subproject,ifnull(s.subproject,'') as subprojectname,
         head.branch,ifnull(b.clientname,'') as branchname,ifnull(b.client,'') as branchcode,'' as dbranchname,ifnull(d.client,'') as dept,ifnull(d.clientname,'') as deptname,
         head.deptid,'' as ddeptname,head.invoiceno,left(head.invoicedate,10) as invoicedate,head.ewt,head.ewtrate,
         cast(ifnull(info.isacknowledged,0) as char) as isacknowledged,0 as dropoffwh,dowh.client as dowh,dowh.clientname as dowhname, ifnull(info.trnxtype,'') as trnxtype";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as b on b.clientid = head.branch
        left join cntnuminfo as info on info.trno=head.trno
        left join client as dowh on dowh.clientid=info.dropoffwh
        left join coa on coa.acno=head.contra
        left join projectmasterfile as p on p.line=head.projectid
        left join client as d on d.clientid = head.deptid
        left join subproject as s on s.line = head.subproject
        where head.trno = ? and num.doc=? and num.center = ?  $trnxx " . $projectfilter . "
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.clientid = client.clientid
        left join client as warehouse on warehouse.clientid = head.whid
        left join client as b on b.clientid = head.branch
        left join hcntnuminfo as info on info.trno=head.trno
        left join client as dowh on dowh.clientid=info.dropoffwh
        left join coa on coa.acno=head.contra
        left join projectmasterfile as p on p.line=head.projectid
        left join client as d on d.clientid = head.deptid
        left join subproject as s on s.line = head.subproject
        where head.trno = ? and num.doc=? and num.center=?   $trnxx" . $projectfilter;

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

      $hideobj['forrevision'] = false;
      $hideobj['forchecking'] = true;
      $hideobj['create'] = true;
      $hideobj['intransit'] = true;
      $hideobj['acknowledged'] = true;
      $hideobj['generatecode'] = true;
      $hideobj['forposting'] = true;

      if ($isposted) {
        $hideobj['forreceiving'] = true;
        $hideobj['forrevision'] = true;
        $hideobj['updatepostedinfo'] = true;
      } else {
        $hideobj['forrevision'] = false;
        if ($statid == 44) {
          $hideobj['forreceiving'] = true;
          $hideobj['forchecking'] = false;
        } else {
          switch ($statid) {
            case 39:
              $hideobj['forreceiving'] = true;
              break;
            case 45:
              $hideobj['forreceiving'] = true;
              $hideobj['forchecking'] = true;
              $hideobj['forrevision'] = false;

              $hideobj['create'] = true;
              $hideobj['acknowledged'] = false;

              // 2023.11.20 temporary remove generate asset tag
              // $fa = $this->coreFunctions->opentable("select s.trno from lastock as s left join item on item.itemid=s.itemid where s.trno=? and item.isgeneric=1", [$trno]);
              // if (empty($fa)) {
              //   $hideobj['create'] = true;
              //   $hideobj['acknowledged'] = false;
              // } else {
              //   $generic = $this->getpendinggenericeitem($config);
              //   if (empty($generic)) {
              //     $hideobj['create'] = true;
              //     $hideobj['acknowledged'] = false;
              //   } else {
              //     $hideobj['create'] = false;
              //   }
              // }
              break;
            case 16:
            case 71:
            case 72:
              $hideobj['forreceiving'] = true;
              $hideobj['forrevision'] = false;
              $hideobj['create'] = true;
              $hideobj['forchecking'] = false;
              break;
            case 70;
              $hideobj['forreceiving'] = true;
              $hideobj['intransit'] = true;
              $hideobj['forrevision'] = false;
              $hideobj['forchecking'] = false;
              break;
            case 73:
              $hideobj['forreceiving'] = true;
              $hideobj['forposting'] = false;

              $fa = $this->coreFunctions->opentable("select s.trno 
                                                    from lastock as s 
                                                    left join item on item.itemid=s.itemid 
                                                    left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline
                                                    where s.trno=? and (item.isgeneric=1 or info.isasset = 'YES')", [$trno]);

              if (empty($fa)) {
                $hideobj['create'] = true;
              } else {
                $generic = $this->getpendinggenericeitem($config);
                if (empty($generic)) {
                  $hideobj['create'] = true;
                } else {
                  $hideobj['create'] = false;
                  $hideobj['forposting'] = true;
                }
              }

              $nobarcode = $this->coreFunctions->opentable("select trno from lastock where trno=? and itemid=0", [$trno]);
              if (empty($nobarcode)) {
                $hideobj['generatecode'] = true;
              } else {
                $hideobj['generatecode'] = false;
                $hideobj['forposting'] = true;
              }
              break;

            default:
              $hideobj['forreceiving'] = false;
              $hideobj['forrevision'] = true;
              break;
          }
        }
      }

      return  [
        'head' => $head,
        'griddata' => ['inventory' => $stock],
        'islocked' => $islocked,
        'isposted' => $isposted,
        'isnew' => false,
        'status' => true,
        'msg' => $msg,
        'hideobj' => $hideobj,
        'hideheadgridbtns' => $hideheadergridbtns
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
    $dataother = [];
    $check = 0;
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

    foreach ($this->otherfields as $key) {

      if ($key == 'dropoffwh') {
        if ($head[$key] != 0) {
          $check = 1;
        }
      } else {
        $check = 1;
      }

      if ($check == 1) {
        if (array_key_exists($key, $head)) {
          $dataother[$key] = $head[$key];
          if (!in_array($key, $this->except)) {
            $dataother[$key] = $this->othersClass->sanitizekeyfield($key, $dataother[$key], '', $companyid);
          }
        }
        $check = 0;
      }
    }

    $data['due'] = $this->othersClass->computeterms($data['dateid'], $data['due'], $data['terms']);

    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    if ($isupdate) {
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
      $whid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$data['wh']]);
      $this->coreFunctions->sbcupdate($this->stock, ['whid' => $whid], ['trno' => $head['trno']]);
      $this->recomputecost($head, $config);
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head, $data);
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['client'] . ' - ' . $head['clientname']);
    }

    $infotransexist = $this->coreFunctions->getfieldvalue("cntnuminfo", "trno", "trno=?", [$head['trno']]);
    if ($infotransexist == '') {
      $this->coreFunctions->sbcinsert("cntnuminfo", $dataother);
    } else {
      $this->coreFunctions->sbcupdate("cntnuminfo", $dataother, ['trno' => $head['trno']]);
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
    $this->coreFunctions->execqry("delete from cntnuminfo where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry("delete from stockinfo where trno=?", 'delete', [$trno]);
    $this->othersClass->deleteattachments($config);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function



  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    if (!$this->othersClass->checkserialin($config)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. There are serialized items. To proceed, please encode the serial number.'];
    }

    $zeroid = $this->coreFunctions->opentable("select trno from " . $this->stock . " where trno=? and itemid=0", [$trno]);
    if (!empty($zeroid)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Failed to post, please assign all items with valid barcode.'];
    }

    $reqgeneric = $this->coreFunctions->opentable("select item.barcode from lastock as s left join item on item.itemid=s.itemid 
                                                   left join hstockinfotrans as pr on pr.trno=s.reqtrno and pr.line=s.reqline 
                                                   where s.trno=? and pr.isasset='YES' and item.isgeneric=0", [$trno]);
    if (!empty($reqgeneric)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Failed to post, assigned barcode is not generic item.'];
    }



    //start add item && rrfams
    $qry = "select s.itemid, s.trno, s.line
            from lastock as s 
            left join item on item.itemid=s.itemid 
            left join hstockinfotrans as pr on pr.trno=s.reqtrno and pr.line=s.reqline
            where s.trno=? and (item.isgeneric=1 or pr.isasset='YES')";
    $generic = $this->coreFunctions->opentable($qry, [$trno]);
    foreach ($generic as $key => $value) {

      $qry = "select s.trno, s.line, s.itemid, s.qty as rrqty, s.uom, rrf.barcode, item.itemname, 
                       item.brand, item.model, item.groupid, item.class, item.part, item.category, item.sizeid, 
                       item.body, client.clientid, h.dateid,rrf.sku,s.ref as podocno,s.cost,h.createby   
                from lastock as s 
                left join item on item.itemid=s.itemid 
                left join lahead as h on h.trno=s.trno 
                left join client on client.client=h.client
                left join hstockinfotrans as pr on pr.trno=s.reqtrno and pr.line=s.reqline 
                left join rrfams as rrf on rrf.trno=s.trno and rrf.line=s.line
                where s.trno=? and item.isgeneric=1 and s.itemid=? and s.line=? and pr.isasset='YES'
                group by s.trno, s.line, s.itemid, s.qty, s.uom, rrf.barcode, item.itemname, item.brand, item.model, 
                         item.groupid, item.class, item.part, item.category, item.sizeid, item.body, 
                         client.clientid, h.dateid,rrf.sku,s.ref,s.cost,h.createby";
      $generics = $this->coreFunctions->opentable($qry, [$trno, $value->itemid, $value->line]);

      $podate = '';
      $purchaserid = 0;
      if (!empty($generics)) {
        $podate = $this->coreFunctions->getfieldvalue("hpohead", "date(dateid)", "docno=?", [$generics[0]->podocno]);
        $purchaserid = $this->coreFunctions->getfieldvalue("client", "clientid", "email=?", [$generics[0]->createby]);
      }

      $isnsi = 0;
      foreach ($generics as $k => $v) {
        $itemseq = $this->coreFunctions->datareader("select itemseq as value from item where subcode='" . $v->barcode . "' and isfa=1 order by itemseq desc limit 1");
        if ($itemseq == '') {
          $itemseq = 1;
        } else {
          $itemseq = $itemseq + 1;
        }
        $data = [
          'barcode' => $v->barcode,
          'subcode' => $v->barcode,
          'itemname' => $v->itemname,
          'isfa' => 1,
          'uom' => $v->uom,
          'brand' => $v->brand,
          'model' => $v->model,
          'groupid' => $v->groupid,
          'class' => $v->class,
          'part' => $v->part,
          'category' => $v->category,
          'sizeid' => $v->sizeid,
          'body' => $v->body,
          'isnsi' => $isnsi,
          'supplier' => $v->clientid,
          'itemseq' => $itemseq,
          'othcode' => '',
          'amt' => $v->cost
        ];

        $fa_itemid = $this->coreFunctions->insertGetId('item', $data);

        if ($fa_itemid != 0) {
          $rrfams = [
            'trno' => $trno,
            'line' => $v->line,
            'itemid' => $fa_itemid,
            'qty' => 1,
            'isnsi' => $isnsi,
            'barcode' => $v->barcode,
            'sku' => $v->sku
          ];
          $this->coreFunctions->sbcinsert('rrfams', $rrfams, ['trno' => $trno, 'line' => $v->line]);

          $iteminfo = [
            'itemid' => $fa_itemid,
            'icondition' => 0,
            'dateacquired' => $v->dateid,
            'pono' => $v->podocno,
            'podate' => $podate,
            'purchaserid' => $purchaserid
          ];

          $this->coreFunctions->sbcinsert('iteminfo', $iteminfo);
        }
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'Generate Asset tag for item ' . $generics[0]->itemname . '. Qty:' . round($generics[0]->rrqty, 2));
      }
    }
    $this->coreFunctions->execqry('delete from rrfams where trno=? and itemid=0', 'delete', [$trno]);

    ///end



    $generic = $this->getpendinggenericeitem($config);
    if (empty($generic)) {
      // $resultgeneric = $this->generateAJ($config);
      $resultgeneric = $this->othersClass->generateAJFAMS($config);
      if (!$resultgeneric['status']) {
        return ['trno' => $trno, 'status' => false, 'msg' => 'Failed to post, ' . $resultgeneric['msg']];
      }
    }

    $isdropoff = $this->coreFunctions->opentable("SELECT h.wh, client.client FROM lahead AS h LEFT JOIN cntnuminfo AS info ON info.trno=h.trno LEFT JOIN client ON client.clientid=info.dropoffwh WHERE h.trno=? AND h.wh<>client.client", [$trno]);
    if (!empty($isdropoff)) {
      $status1 = $this->coreFunctions->opentable("select trno from stockinfo where trno=? and status1<>52", [$trno]);
      if (!empty($status1)) {
        return ['trno' => $trno, 'status' => false, 'msg' => 'Failed to post, all items status must be Full - Initial'];
      }
    }

    $status2 = $this->coreFunctions->opentable("select trno from stockinfo where trno=? and status2<>54", [$trno]);
    if (!empty($status2)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Failed to post, all items status must be Full - Final'];
    }

    $checkstat = $this->coreFunctions->opentable("select trno from stockinfo where trno=? and checkstat<>56", [$trno]);
    if (!empty($checkstat)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Failed to post, all items must be Fully Checked'];
    }

    $wh = $this->coreFunctions->getfieldvalue("lahead", "wh", "trno=?", [$trno]);
    if ($wh == '') {
      return ['status' => false, 'msg' => 'Posting failed. Input Warehouse first.'];
    }

    $statid = $this->othersClass->getstatid($config);

    // hindi eto
    // if ($statid != 73) {
    //   return ['trno' => $trno, 'status' => false, 'msg' => 'Failed to post, must be Acknowledged first.'];
    // }
    // hindi eto

    if ($statid != 39) { //not cbbsi
      return ['trno' => $trno, 'status' => false, 'msg' => 'For Posting status only is allowed to post.'];
    }


    $this->coreFunctions->execqry("update lastock as s left join hprstock as prs on prs.trno=s.reqtrno and prs.line=s.reqline set prs.itemid=s.itemid, prs.uom=s.uom,prs.editby = '" . $config['params']['user'] . "',prs.editdate = '" . $this->othersClass->getCurrentTimeStamp() . "' where s.trno=" . $trno . " ");

    $getpr = $this->coreFunctions->opentable("select reqtrno,reqline from lastock where trno=" . $trno . "");


    $this->coreFunctions->execqry("update lastock as s
                left join lahead as h on h.trno=s.trno
                left join hprstock as pr on pr.trno=s.reqtrno and pr.line=s.reqline
                set s.itemid=pr.itemid,s.uom=pr.uom,s.editby='" . $config['params']['user'] . "',s.editdate='" . $this->othersClass->getCurrentTimeStamp() . "'
                where h.doc= 'SS' and s.reqtrno= '" . $getpr[0]->reqtrno . "' and s.reqline='" . $getpr[0]->reqline . "'");

    $this->coreFunctions->execqry("update glstock as s
                left join glhead as h on h.trno=s.trno
                left join hprstock as pr on pr.trno=s.reqtrno and pr.line=s.reqline
                set s.itemid=pr.itemid,s.uom=pr.uom,s.editby='" . $config['params']['user'] . "',s.editdate='" . $this->othersClass->getCurrentTimeStamp() . "'
                where h.doc= 'SS' and s.reqtrno= '" . $getpr[0]->reqtrno . "' and s.reqline='" . $getpr[0]->reqline . "'");



    if (!$this->companysetup->getisacctgentry($config['params'])) {
      return $this->othersClass->posttranstock($config);
    } else {
      $checkacct = $this->othersClass->checkcoaacct(['AP1', 'IN1', 'PD1', 'TX1']);
      if ($checkacct != '') {
        return ['trno' => $trno, 'status' => false, 'msg' => 'Accounts not yet setup:' . $checkacct];
      }

      if (!$this->createdistribution($config)) {
        return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. Problems in creating accounting entries.'];
      } else {
        $return = $this->othersClass->posttranstock($config);

        $this->coreFunctions->execqry("update " . $this->hstock . " as stock
          left join hprstock as prs on prs.trno=stock.reqtrno and prs.line=stock.reqline
          set prs.statrem='Receiving Report - Posted',prs.statdate='" . $this->othersClass->getCurrentTimeStamp() . "' where stock.trno=" . $trno, 'update');

        if ($return['status']) {
          $this->coreFunctions->execqry("update rrfams as rr left join item on item.itemid=rr.itemid set item.isinactive=0 where rr.trno=" . $trno); //set active because asset created is inactive by default
        }


        $reqdata = $this->coreFunctions->opentable('select reqtrno,reqline from ' . $this->hstock . ' where trno=?', [$trno]);

        foreach ($reqdata as $key => $value) {
          $rrref = $this->coreFunctions->datareader(
            "select ifnull(group_concat(ref SEPARATOR '\r'),'') as value 
                        from (select concat(num.docno, ' - Draft') as ref 
                              from lastock as s 
                              left join cntnum as num on num.trno=s.trno 
                              where num.doc = 'RR' and s.reqtrno=? and s.reqline=? 
                              group by num.docno
                              union all
                              select concat(num.docno, ' - Posted') as ref 
                              from glstock as s 
                              left join cntnum as num on num.trno=s.trno 
                              where num.doc = 'RR' and s.reqtrno=? and s.reqline=? 
                              group by num.docno) as s",
            [$reqdata[$key]->reqtrno, $reqdata[$key]->reqline, $reqdata[$key]->reqtrno, $reqdata[$key]->reqline]
          );
          $this->coreFunctions->execqry("update hstockinfotrans set rrref='" . $rrref . "'  where trno=" . $reqdata[$key]->reqtrno . " and line=" . $reqdata[$key]->reqline);
        }


        return $return;
      }
    }
  } //end function

  // public function generateAJFAMS($config, $modulename = 'receiving')
  // {
  //   $status = true;
  //   $msg = '';
  //   $trno = $config['params']['trno'];
  //   $user = $config['params']['user'];
  //   $rrdocno = $this->coreFunctions->getfieldvalue($this->head, "docno", "trno=?", [$trno]);
  //   $rrwh = $this->coreFunctions->getfieldvalue($this->head, "wh", "trno=?", [$trno]);

  //   $data = [];

  //   $ajtrno = 0;

  //   try {
  //     $faitems = $this->coreFunctions->opentable("select rr.trno, rr.line, rr.itemid, rr.qty, rr.ajtrno, rr.ajline, s.uom, s.whid, s.rrcost, s.disc
  //     from rrfams as rr left join lastock as s on s.trno=rr.trno and s.line=rr.line 
  //     where rr.trno=? and rr.ajtrno=0", [$trno]);
  //     if (!empty($faitems)) {

  //       foreach ($faitems as $key => $value) {
  //         $uom = $value->uom;
  //         $itemid =  $value->itemid;
  //         $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
  //         $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);

  //         $barcode = '';
  //         $qty = $value->qty;

  //         $qty = $this->othersClass->sanitizekeyfield('qty', $qty);
  //         $amt = $this->othersClass->sanitizekeyfield('amt', $value->rrcost);
  //         $factor = 1;
  //         if (!empty($item)) {
  //           $barcode = $item[0]->barcode;
  //           if ($item[0]->factor !== 0 || $item[0]->factor !== '') {
  //             $factor = $item[0]->factor;
  //           }
  //         }
  //         $qty = round($qty, $this->companysetup->getdecimal('qty', $config['params']));
  //         $computed = $this->othersClass->computestock($amt, $value->disc, $qty, $factor);
  //         $arr = [
  //           'trno' => $value->trno,
  //           'line' => $value->line,
  //           'ajtrno' => 0,
  //           'ajline' => 0,
  //           'itemid' => $itemid,
  //           'whid' => $value->whid,
  //           'uom' => $uom,
  //           'disc' => $value->disc,
  //           'rrqty' => $qty,
  //           'qty' => $computed['qty'],
  //           'rrcost' => $amt,
  //           'cost' => $computed['amt'],
  //           'ext' => $computed['ext']
  //         ];
  //         array_push($data, $arr);
  //       }

  //       if (!empty($data)) {
  //         $ajtrno = $this->othersClass->generatecntnum($config, $this->tablenum, 'AJ', 'AJ');
  //         if ($ajtrno != -1) {
  //           $docno =  $this->coreFunctions->getfieldvalue($this->tablenum, 'docno', "trno=?", [$ajtrno]);

  //           $head = [
  //             'trno' => $ajtrno,
  //             'doc' => 'AJ',
  //             'docno' => $docno,
  //             'dateid' => date('Y-m-d'),
  //             'contra' => $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['IS1']),
  //             'wh' => $rrwh,
  //             'rem' => 'Adjustment for auto-created assets from ' . $modulename .  ' '  . $rrdocno
  //           ];

  //           if ($this->coreFunctions->sbcinsert('lahead', $head)) {
  //             $this->logger->sbcwritelog($ajtrno, $config, 'CREATE', 'AUTO-GENERATED ' . $docno, $this->tablelogs);

  //             $line = 0;
  //             foreach ($data as $d => $dataval) {
  //               $qry = "select line as value from lastock where trno=? order by line desc limit 1";
  //               $line = $this->coreFunctions->datareader($qry, [$ajtrno]);

  //               if ($line == '') {
  //                 $line = 0;
  //               }

  //               $line = $line + 1;

  //               $stock = [
  //                 'trno' => $ajtrno,
  //                 'line' => $line,
  //                 'itemid' => $dataval['itemid'],
  //                 'whid' => $dataval['whid'],
  //                 'uom' => $dataval['uom'],
  //                 'disc' => $dataval['disc'],
  //                 'rrcost' => $dataval['rrcost'],
  //                 'cost' => $dataval['cost'],
  //                 'rrqty' => $dataval['rrqty'],
  //                 'qty' => $dataval['qty'],
  //                 'ext' => $dataval['ext']
  //               ];

  //               foreach ($stock as $skey => $sval) {
  //                 $stock[$skey] = $this->othersClass->sanitizekeyfield($skey, $stock[$skey]);
  //               }

  //               if ($this->coreFunctions->sbcinsert('lastock', $stock)) {
  //                 $this->coreFunctions->sbcupdate('rrfams', ['ajtrno' => $ajtrno, 'ajline' => $line], ['trno' => $dataval['trno'], 'line' => $dataval['line'], 'itemid' => $dataval['itemid']]);
  //               } else {
  //                 $msg = 'Failed to insert AJ stock';
  //                 $status = false;
  //                 goto exithere;
  //               }
  //             } //end foreach items

  //             //postAJ
  //             $path = 'App\Http\Classes\modules\inventory\aj';
  //             $config['params']['trno'] = $ajtrno;
  //             $config['params']['doc'] = 'AJ';
  //             $return = app($path)->posttrans($config);
  //             if ($return['status']) {
  //               $status = true;
  //             } else {
  //               $msg = 'Failed to post AJ';
  //               $status = false;
  //               goto exithere;
  //             }
  //           } else {
  //             $msg = 'Failed to insert AJ head';
  //             $status = false;
  //             goto exithere;
  //           } //end insert glhead
  //         }
  //       }
  //     }
  //   } catch (Exception $e) {
  //     $msg = $e;
  //     $status = false;
  //   }

  //   exithere:
  //   if (!$status) {
  //     if ($ajtrno != 0) {
  //       $this->coreFunctions->execqry('delete from cntnum where trno=?', 'delete', [$ajtrno]);
  //       $this->coreFunctions->execqry('delete from lastock where trno=?', 'delete', [$ajtrno]);
  //       $this->coreFunctions->execqry('delete from lahead where trno=?', 'delete', [$ajtrno]);
  //       $this->coreFunctions->execqry('update rrfams set ajtrno=0, ajline=0 where ajtrno=?', 'delete', [$ajtrno]);
  //     }
  //   }

  //   return ['status' => $status, 'msg' => $msg];
  // }

  public function unposttrans($config)
  {
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $isfa = $this->companysetup->getisfixasset($config['params']);
    $trno = $config['params']['trno'];
    $data = $this->coreFunctions->opentable("select sum(a.cr-a.db) as bal,d.projectid,d.subproject,d.stageid from apledger as a left join gldetail as d on d.trno = a.trno and d.line = a.line  where a.trno =" . $trno . " group by d.projectid,d.subproject,d.stageid");

    if ($isfa) {
      $isexist = $this->coreFunctions->getfieldvalue("fasched", "rrtrno", "rrtrno = ? and jvtrno <>0", [$trno]);

      if (floatval($isexist) != 0) {
        return ['status' => false, 'msg' => 'Already have posted depreciation schedule.'];
      }
    }

    $return = $this->othersClass->unposttranstock($config);
    if ($return['status']) {
      $this->coreFunctions->execqry("update cntnum set statid=39 where trno=" . $trno);
    }

    $reqdata = $this->coreFunctions->opentable('select reqtrno,reqline from ' . $this->stock . ' where trno=?', [$trno]);

    foreach ($reqdata as $key => $value) {
      $rrref = $this->coreFunctions->datareader(
        "select ifnull(group_concat(ref SEPARATOR '\r'),'') as value 
                        from (select concat(num.docno, ' - Draft') as ref 
                              from lastock as s 
                              left join cntnum as num on num.trno=s.trno 
                              where num.doc = 'RR' and s.reqtrno=? and s.reqline=? 
                              group by num.docno
                              union all
                              select concat(num.docno, ' - Posted') as ref 
                              from glstock as s 
                              left join cntnum as num on num.trno=s.trno 
                              where num.doc = 'RR' and s.reqtrno=? and s.reqline=? 
                              group by num.docno) as s",
        [$reqdata[$key]->reqtrno, $reqdata[$key]->reqline, $reqdata[$key]->reqtrno, $reqdata[$key]->reqline]
      );

      $this->coreFunctions->execqry("update hstockinfotrans set rrref='" . $rrref . "'  where trno=" . $reqdata[$key]->reqtrno . " and line=" . $reqdata[$key]->reqline);
      $this->logger->sbcstatlog($trno, $config, 'HEAD', 'For Posting');
    }

    return $return;
  } //end function

  private function getstockselect($config)
  {
    $sqlselect = "select item.brand as brand,
    ifnull(mm.model_name,'') as model,
    ifnull(item.itemid,0) as itemid,
    stock.trno,
    stock.line,
    stock.refx,
    stock.linex,
    item.barcode,
    item.itemname,
    stock.uom,
    stock." . $this->hamt . ",
    stock." . $this->hqty . " as qty,
    FORMAT(sinfo.amt1," . $this->companysetup->getdecimal('currency', $config['params']) . ") as amt1,
    FORMAT(sinfo.amt2," . $this->companysetup->getdecimal('currency', $config['params']) . ") as amt2,
    FORMAT(sinfo.amt3," . $this->companysetup->getdecimal('currency', $config['params']) . ") as amt3,
    FORMAT(sinfo.amt4," . $this->companysetup->getdecimal('currency', $config['params']) . ") as amt4,
    FORMAT(sinfo.amt5," . $this->companysetup->getdecimal('currency', $config['params']) . ") as amt5,
    FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . ") as " . $this->damt . ",
    FORMAT(stock." . $this->dqty . "," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as " . $this->dqty . ",
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,
    left(stock.encodeddate,10) as encodeddate,
    stock.disc,
    stock.void,
    round((stock." . $this->hqty . "-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
    stock.ref,
    stock.whid,
    if(stock.ref='','true','false') as ismanual,
    'true' as ismanual2,
    warehouse.client as wh,
    warehouse.clientname as whname,
    stock.loc,
    stock.expiry,
    item.brand,
    stock.rem,
    stock.palletid,
    stock.locid,
    ifnull(pallet.name,'') as pallet,
    ifnull(location.loc,'') as location,
    ifnull(uom.factor,1) as uomfactor,stock.fcost,ifnull(stock.stageid,0) as stageid ,ifnull(st.stage,'') as stage,
    '' as bgcolor,
    stock.cdrefx,
    stock.cdlinex,
    stock.prrefx,
    stock.prlinex,
    if(sinfo.checkstat=56,'bg-green-2',if(sinfo.intransit=1,'bg-purple-2','')) as qacolor,
    prj.name as stock_projectname,
    stock.projectid as projectid,
    item.subcode, item.partno, round(item.dqty, " . $this->companysetup->getdecimal('qty', $config['params']) . ") as boxcount,stock.poref,stock.sgdrate,
    ifnull(info.itemdesc,'') as itemdesc, ifnull(info.unit,'') as unit, ifnull(info.specs,'') as specs, ifnull(info.purpose,'') as purpose,ifnull(info.requestorname,'') as requestorname,stock.reqtrno,stock.reqline,
    (case when sinfo.intransit<>0 then 'true' else 'false' end) as intransit, sinfo.status1, sinfo.status2, sinfo.checkstat, 
    FORMAT(sinfo.qty1," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qty1, 
    FORMAT(sinfo.qty2," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qty2, 
    FORMAT(sinfo.tqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as tqty,
    ifnull(stat1.status,'') as status1name, ifnull(stat2.status,'') as status2name, ifnull(stat3.status,'') as checkstatname, 
    if(stock.pickerstart is null,'false','true') as ispicked, pr.clientname, ifnull(cat.category,'') as category, info.ctrlno,
    ifnull((select count(rrtrno) from headprrem where headprrem.rrtrno=stock.trno and headprrem.rrline=stock.line),0) as notectr,
    info.isasset,
    case when sinfo.waivedspecs=0 then 'false' else 'true' end as waivedspecs,
    case info.isasset when 'YES' then 'bg-yellow-2' else '' end as  errcolor";
    return $sqlselect;
  }

  public function openstock($trno, $config)
  {
    $sqlselect = $this->getstockselect($config);

    $qry = $sqlselect . "
    FROM $this->stock as stock
    left join item on item.itemid=stock.itemid
    left join model_masterfile as mm on mm.model_id = item.model
    left join pallet on pallet.line=stock.palletid
    left join location on location.line=stock.locid
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid
    left join stagesmasterfile as st on st.line = stock.stageid
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
    left join stockinfo as sinfo on sinfo.trno=stock.trno and sinfo.line=stock.line
    left join trxstatus as stat1 on stat1.line=sinfo.status1
    left join trxstatus as stat2 on stat2.line=sinfo.status2
    left join trxstatus as stat3 on stat3.line=sinfo.checkstat
    left join hprhead as pr on pr.trno=info.trno
    left join reqcategory as cat on cat.line=pr.ourref

    where stock.trno =?
    UNION ALL
    " . $sqlselect . "
    FROM $this->hstock as stock
    left join item on item.itemid=stock.itemid
    left join model_masterfile as mm on mm.model_id = item.model
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
    left join client as warehouse on warehouse.clientid=stock.whid
    left join pallet on pallet.line=stock.palletid
    left join location on location.line=stock.locid
    left join stagesmasterfile as st on st.line = stock.stageid
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
    left join hstockinfo as sinfo on sinfo.trno=stock.trno and sinfo.line=stock.line
    left join trxstatus as stat1 on stat1.line=sinfo.status1
    left join trxstatus as stat2 on stat2.line=sinfo.status2
    left join trxstatus as stat3 on stat3.line=sinfo.checkstat
    left join hprhead as pr on pr.trno=info.trno
    left join reqcategory as cat on cat.line=pr.ourref
    where stock.trno =? order by intransit desc, line";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $stock;
  } //end function

  public function openstockline($config)
  {
    $sqlselect = $this->getstockselect($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = $sqlselect . "
   FROM $this->stock as stock
    left join item on item.itemid=stock.itemid
    left join model_masterfile as mm on mm.model_id = item.model
    left join pallet on pallet.line=stock.palletid
    left join location on location.line=stock.locid
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid
    left join stagesmasterfile as st on st.line = stock.stageid 
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
    left join stockinfo as sinfo on sinfo.trno=stock.trno and sinfo.line=stock.line
    left join client as intrans on intrans.clientid=sinfo.intransit
    left join trxstatus as stat1 on stat1.line=sinfo.status1
    left join trxstatus as stat2 on stat2.line=sinfo.status2
    left join trxstatus as stat3 on stat3.line=sinfo.checkstat
    left join hprhead as pr on pr.trno=info.trno
    left join reqcategory as cat on cat.line=pr.ourref    
    where stock.trno = ? and stock.line = ? ";
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
      case 'quickadd':
        return $this->quickadd($config);
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
      case 'getposummary':
        return $this->getposummary($config);
        break;
      case 'getpodetails':
      case 'getporeqpaydetails':
        return $this->getpodetails($config);
        break;
      case 'getcdsummary':
        return $this->getcdsummary($config);
        break;
      case 'getcddetails':
        return $this->getcddetails($config);
        break;
      case 'getprsummary':
        return $this->getprsummary($config);
        break;
      case 'getprdetails':
        return $this->getprdetails($config);
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
      case 'generatetag':
        return $this->generateassettag($config);
        break;
      case 'forreceiving':
        return $this->forreceiving($config);
        break;
      case 'intransit':
        return $this->intransit($config);
        break;
      case 'forchecking':
        return $this->forchecking($config);
        break;
      case 'acknowledged':
      case 'forposting':
        return $this->acknowledged($config);
        break;
      case 'generatecode':
        return $this->generatecode($config);
        break;
      default:
        return ['status' => false, 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function generatecode($config)
  {
    $status = true;
    $trno = $config['params']['trno'];
    $msg = '';

    $tempdata = $this->coreFunctions->opentable("select s.trno,s.line,s.reqtrno,s.reqline,info.itemdesc,info.unit,
                                                info.specs,info.isasset,s.cost
                                                 from lastock as s 
                                                 left join hstockinfotrans as info on info.trno=s.reqtrno 
                                                          and info.line=s.reqline 
                                                 where s.itemid=0 and s.trno=" . $trno);
    if (!empty($tempdata)) {
      $this->logger->sbcwritelog($trno, $config, 'STOCK', 'Generate temporary barcode(s)');

      foreach ($tempdata as $key => $value) {
        $tmpcode = $this->generatetempbarcode($config);
        $this->coreFunctions->LogConsole($tmpcode);
        if ($tmpcode == '') {
          if ($msg == '') {
            $msg .= "" . $value->itemdesc;
          } else {
            $msg .= ", " . $value->itemdesc;
          }
        } else {

          $rawdata = [
            'barcode' => $tmpcode,
            'othcode' => $tmpcode,
            'itemname' => $value->itemdesc,
            'uom' => $value->unit,
            'shortname' => $value->specs,
            'amt' => $value->cost
          ];

          if ($value->isasset == 'YES') {
            $rawdata['isgeneric'] = 1;
          }

          $itemid = $this->coreFunctions->insertGetId('item', $rawdata);
          if ($itemid != 0) {
            $this->logger->sbcwritelog($itemid, $config, 'CREATE', $itemid . ' - ' . $tmpcode . ' - ' . $value->itemdesc . ' (Auto-create from RR)', 'item_log');
            $this->coreFunctions->sbcinsert("uom", ['itemid' => $itemid, 'uom' => $value->unit, 'factor' => 1]);
            $this->coreFunctions->sbcupdate("lastock", ['itemid' => $itemid, 'uom' => $value->unit, 'editdate' => $this->othersClass->getCurrentTimeStamp(), 'editby' => $config['params']['user']], ["trno" => $value->trno, "line" => $value->line]);
          }
        }
      }
    }

    $msg2 = '';
    if ($msg != '') {
      $msg2 = "Failed to generate barcode for " . $msg;
    } else {
      $msg2 = 'Temporary barcode successfully created and assigned.';
    }

    return ['trno' => $trno, 'status' => $status, 'msg' => $msg2, 'reloadhead' => true];
  }

  public function generatetempbarcode($config)
  {
    $barcodelength = $this->companysetup->getbarcodelength($config['params']);
    $pref = '';
    $path = 'App\Http\Classes\modules\masterfile\stockcard';

    $barcode2 = '';

    $pref = "ITM";
    if (strlen($pref) == 0) {
      $pref = app($path)->prefix;
    }
    if (!$pref) {
      $prefixes = $this->othersClass->getPrefixes($pref, $config);
      $pref = isset($prefixes[0]) ? $prefixes[0] : $pref;
    }

    $barcode2 = app($path)->getlastbarcode($pref, $config['params']['companyid']);
    $this->othersClass->logConsole("barcode:" . $barcode2);

    setlastbarcodehere:
    $seq = (substr($barcode2, $this->othersClass->SearchPosition($barcode2), strlen($barcode2)));
    $seq += 1;

    if ($seq == 0 || empty($pref)) {
      if (empty($pref)) {
        $pref = strtoupper($barcode2);
      }
      $barcode2 =  app($path)->getlastbarcode($pref, $config['params']['companyid']);
      $seq = (substr($barcode2, $this->othersClass->SearchPosition($barcode2), strlen($barcode2)));
      $seq += 1;
    }
    $poseq = $pref . $seq;

    $newbarcode = $this->othersClass->PadJ($poseq, $barcodelength);

    $itemid = $this->coreFunctions->getfieldvalue("item", "itemid", "barcode=?", [$newbarcode], '', true);
    if ($itemid != 0) {
      $barcode2 = app($path)->getlastbarcode($pref, $config['params']['companyid'], 'itemid');
      goto setlastbarcodehere;
    }

    return $newbarcode;
  }

  public function acknowledged($config)
  {
    $trno = $config['params']['trno'];
    $msg = "";
    $status = true;

    $action = $config['params']['action'];
    if ($action == 'stockstatusposted') {
      $action = $config['params']['lookupclass'];
    }

    // 2023.11.21 - temporary remove
    // $zeroid = $this->coreFunctions->opentable("select trno from " . $this->stock . " where trno=? and itemid=0", [$trno]);
    // if (!empty($zeroid)) {
    //   return ['trno' => $trno, 'status' => false, 'msg' => 'please assign all items with valid barcode.'];
    // }

    // $reqgeneric = $this->coreFunctions->opentable("select item.barcode from lastock as s left join item on item.itemid=s.itemid left join hstockinfotrans as pr on pr.trno=s.reqtrno and pr.line=s.reqline where s.trno=? and pr.isasset='YES' and item.isgeneric=0", [$trno]);
    // if (!empty($reqgeneric)) {
    //   return ['trno' => $trno, 'status' => false, 'msg' => 'There are items tagged as ASSET, but the assigned barcode is not tagged as generic.'];
    // }

    // $generic = $this->getpendinggenericeitem($config);
    // if (!empty($generic)) {
    //   return ['trno' => $trno, 'status' => false, 'msg' => 'Please generate asset tag first for all generic items'];
    // }
    // 2023.11.21

    switch ($action) {
      case 'acknowledged':
        $statid = 73;
        $label = 'Acknowledged';
        $this->coreFunctions->execqry("update lastock as s left join hstockinfotrans as prs on prs.trno=s.reqtrno and prs.line=s.reqline set prs.isrr=1 where s.trno=" . $trno);
        break;
      case 'forposting':
        $chkassetbarcode = $this->coreFunctions->opentable("select s.itemid
                          from lastock as s
                          left join rrfams as rrf on rrf.trno=s.trno and rrf.line=s.line
                          left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline
                          where s.trno = " . $trno . " and info.isasset='YES' and rrf.barcode = ''");

        if (!empty($chkassetbarcode)) {
          return ['trno' => $trno, 'status' => false, 'msg' => 'Input asset tag code first before clicking For Posting button.'];
        } else {
          $zeroid = $this->coreFunctions->opentable("select trno from " . $this->stock . " where trno=? and itemid=0", [$trno]);
          if (!empty($zeroid)) {
            return ['trno' => $trno, 'status' => false, 'msg' => 'please assign all items with valid barcode.'];
          }
          $statid = 39;
          $label = 'For Posting';
        }
        break;
    }

    $this->coreFunctions->sbcupdate($this->tablenum, ['statid' =>  $statid], ['trno' => $trno]);
    // $this->logger->sbcwritelog($trno, $config, 'HEAD', $label);
    $this->logger->sbcstatlog($trno, $config, 'HEAD', $label);

    return ['trno' => $trno, 'status' => $status, 'msg' => $msg, 'backlisting' => true];
  }

  public function forreceiving($config)
  {
    $trno = $config['params']['trno'];
    $msg = "";
    $status = true;

    if ($this->othersClass->isposted2($trno, $this->tablenum)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Transaction has already been posted.'];
    }

    $whid = $this->coreFunctions->datareader("select client.clientid as value from lahead as h left join client on client.client=h.wh where h.trno=?", [$trno]);
    $dropoff = $this->coreFunctions->datareader("select dropoffwh as value from cntnuminfo where trno=?", [$trno]);

    $statid = 71;
    $label = 'FOR FINAL RECEIVING';
    if ($whid != $dropoff) {
      $statid = 70;
      $label = 'FOR INITIAL RECEIVING';
    }

    $this->coreFunctions->sbcupdate($this->tablenum, ['statid' => $statid], ['trno' => $trno]);
    // $this->logger->sbcwritelog($trno, $config, 'HEAD',   $label);
    $this->logger->sbcstatlog($trno, $config, 'HEAD',   $label);

    return ['trno' => $trno, 'status' => $status, 'msg' => $msg, 'backlisting' => true];
  }

  public function intransit($config)
  {
    $trno = $config['params']['trno'];
    $msg = "";
    $status = true;

    if ($this->othersClass->isposted2($trno, $this->tablenum)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Transaction has already been posted.'];
    }

    $intransit = $this->coreFunctions->opentable("select trno from stockinfo where trno=? and intransit=1", [$trno]);
    if (empty($intransit)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Unable to tag intransit, required to tag atleast 1 item to proceed the process'];
    }

    $statid = 72;
    $label = 'INSTRANSIT';

    $this->coreFunctions->sbcupdate($this->tablenum, ['statid' => $statid], ['trno' => $trno]);
    // $this->logger->sbcwritelog($trno, $config, 'HEAD',   $label);
    $this->logger->sbcstatlog($trno, $config, 'HEAD',   $label);

    return ['trno' => $trno, 'status' => $status, 'msg' => $msg, 'backlisting' => true];
  }

  public function forchecking($config)
  {
    $trno = $config['params']['trno'];
    $msg = "";
    $status = true;

    if ($this->othersClass->isposted2($trno, $this->tablenum)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Transaction has already been posted.'];
    }

    $isdropoff = $this->coreFunctions->opentable("SELECT h.wh, client.client FROM lahead AS h LEFT JOIN cntnuminfo AS info ON info.trno=h.trno LEFT JOIN client ON client.clientid=info.dropoffwh WHERE h.trno=? AND h.wh<>client.client", [$trno]);
    if (!empty($isdropoff)) {
      $data = $this->coreFunctions->opentable("select trno from stockinfo where trno=? and status1=0", [$trno]);
      if (!empty($data)) {
        return ['trno' => $trno, 'status' => false, 'msg' => 'Received Status 1 is required'];
      }
    }

    $data = $this->coreFunctions->opentable("select trno from stockinfo where trno=? and status2=0", [$trno]);
    if (!empty($data)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Received Status 2 is required'];
    }

    $this->coreFunctions->sbcupdate($this->tablenum, ['statid' => 45], ['trno' => $trno]);
    // $this->logger->sbcwritelog($trno, $config, 'HEAD', 'TAGGED FOR CHECKING');
    $this->logger->sbcstatlog($trno, $config, 'HEAD', 'TAGGED FOR CHECKING');

    return ['trno' => $trno, 'status' => $status, 'msg' => $msg, 'backlisting' => true];
  }

  public function generateassettag($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];

    $msg = "";
    $status = true;

    try {
      $generic = $this->getpendinggenericeitem($config);

      if (empty($generic)) {
        return ['trno' => $trno, 'status' => $status, 'msg' => 'There is nothing to generate asset tags.', 'reloadhead' => true];
      }

      foreach ($generic as $key => $value) {
        $qry = "select s.trno, s.line, s.itemid, (case when item.isnonserial = 0 then s.qty else 1 end) as rrqty, s.uom, rrf.barcode, item.itemname, 
                       item.brand, item.model, item.groupid, item.class, item.part, item.category, item.sizeid, 
                       item.body, client.clientid, h.dateid
                from lastock as s 
                left join item on item.itemid=s.itemid 
                left join lahead as h on h.trno=s.trno 
                left join client on client.client=h.client
                left join hstockinfotrans as pr on pr.trno=s.reqtrno and pr.line=s.reqline 
                left join rrfams as rrf on rrf.trno=s.trno and rrf.line=s.line
                where s.trno=? and item.isgeneric=1 and s.itemid=? and s.line=? and pr.isasset='YES'
                group by s.trno, s.line, s.itemid, s.qty, s.uom, rrf.barcode, item.itemname, item.brand, item.model, 
                         item.groupid, item.class, item.part, item.category, item.sizeid, item.body, 
                         client.clientid, h.dateid,item.isnonserial";
        $generics = $this->coreFunctions->opentable($qry, [$trno, $value->itemid, $value->line]);

        $isnsi = 0;
        foreach ($generics as $k => $v) {
          // $this->coreFunctions->LogConsole(($v->rrqty - $value->qty));
          for ($index = 1; $index <= ($v->rrqty - $value->qty); $index++) {
            // $this->coreFunctions->LogConsole(($index));
            // $itemseq = $this->coreFunctions->datareader("select itemseq as value from item where subcode='" . $v->barcode . "' and isfa=1 order by itemseq desc limit 1");
            // if ($itemseq == '') {
            //   $itemseq = 1;
            // } else {
            //   $itemseq = $itemseq + 1;
            // }
            // $barcode =  $v->barcode . '-' . $itemseq;

            // $this->othersClass->logConsole("index:" . $index . ' - count:' . ($v->rrqty - $value->qty) . ' - barcode:' . $barcode);

            // $data = [
            //   'barcode' => $barcode,
            //   'subcode' => $v->barcode,
            //   'itemname' => $v->itemname,
            //   'isfa' => 1,
            //   'uom' => $v->uom,
            //   'brand' => $v->brand,
            //   'model' => $v->model,
            //   'groupid' => $v->groupid,
            //   'class' => $v->class,
            //   'part' => $v->part,
            //   'category' => $v->category,
            //   'sizeid' => $v->sizeid,
            //   'body' => $v->body,
            //   'isnsi' => $isnsi,
            //   'supplier' => $v->clientid,
            //   'itemseq' => $itemseq,
            //   'isinactive' => 1,
            //   'othcode' => ''
            // ];
            // $fa_itemid = $this->coreFunctions->insertGetId('item', $data);
            // if ($fa_itemid != 0) {
            // $rrfams = [
            //   'trno' => $trno,
            //   'line' => $v->line,
            //   'itemid' => $fa_itemid,
            //   'qty' => 1,
            //   'isnsi' => $isnsi
            // ];
            // $this->coreFunctions->sbcinsert('rrfams', $rrfams);
            // $iteminfo = [
            //   'itemid' => $fa_itemid,
            //   'icondition' => 0,
            //   'dateacquired' => $v->dateid
            // ];
            // $this->coreFunctions->sbcinsert('iteminfo', $iteminfo);
            // }

            //

            $rrfams = [
              'trno' => $trno,
              'line' => $v->line,
              'qty' => 1,
              'isnsi' => $isnsi
            ];
            $this->coreFunctions->sbcinsert('rrfams', $rrfams);
          }
        }
      }
    } catch (Exception $e) {
      $status = false;
      $msg .= 'Failed to generate asset tag. Exception error ' . $e->getMessage();
      goto exithere;
    }

    exithere:
    if ($msg == '') {
      $msg = 'Successfully uploaded.';
    }
    return ['trno' => $trno, 'status' => $status, 'msg' => $msg, 'reloadhead' => true];
  }

  public function getpendinggenericeitem($config)
  {
    $trno = $config['params']['trno'];
    $qry = "select s.itemid, s.trno, s.line, (case when item.isnonserial =0 then s.qty else 1 end) as rrqty, ifnull(sum(rr.qty),0) as qty
            from lastock as s 
            left join item on item.itemid=s.itemid 
            left join rrfams as rr on rr.trno=s.trno and rr.line=s.line
            left join hstockinfotrans as pr on pr.trno=s.reqtrno and pr.line=s.reqline 
            where s.trno=? and (item.isgeneric=1 or pr.isasset='YES')
            group by s.itemid, s.trno, s.line, item.isnonserial,s.qty 
            having (case when item.isnonserial =0 then s.qty else 1 end)<>ifnull(sum(rr.qty),0)";

    return $this->coreFunctions->opentable($qry, [$trno]);
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
        $config['params']['trno'] = $trno;
        $config['params']['data']['uom'] = $rawdata[$key]['uom'];
        $config['params']['data']['itemid'] = $this->coreFunctions->getfieldvalue("item", "itemid", "barcode = '" . $rawdata[$key]['itemcode'] . "'");
        $config['params']['data']['qty'] = $rawdata[$key]['qty'];
        $config['params']['data']['wh'] =  $this->coreFunctions->getfieldvalue($this->head, "wh", "trno = ?", [$trno]);
        $config['params']['data']['amt'] = $rawdata[$key]['cost'];
        $config['params']['data']['loc'] = $rawdata[$key]['location'];
        $config['params']['data']['expiry'] = $rawdata[$key]['expiry'];
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
      $msg = 'Successfully uploaded.';
    }
    return ['trno' => $trno, 'status' => $status, 'msg' => $msg, 'reloadhead' => true];
  }


  public function tagreceived($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];

    $msg = "";
    $status = true;
    $qry = "select receivedby, ifnull(date(receiveddate), '') as receiveddate from cntnum where trno = ?";
    $checking = $this->coreFunctions->opentable($qry, [$trno]);

    if ($checking[0]->receiveddate != "") {
      $msg = "Already Received! " . $checking[0]->receivedby . ' ' . $checking[0]->receiveddate;
    } else {
      $tag = $this->coreFunctions->execqry("update cntnum set receivedby='" . $user . "',
      receiveddate = '" . date("Y-m-d") . "' where trno=? ", "update", [$trno]);

      if ($tag) {
        $msg = "Received Success!";
        $status = true;
        $this->logger->sbcwritelog($trno, $config, 'RECEIVED', 'CLICKED RECEIVED');
      } else {
        $msg = "Received Failed!";
        $status = false;
      }
    }

    return ['status' => $status, 'msg' => $msg, 'dd' => 'Received', 'reloadhead' => true];
  }

  public function untagreceived($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];

    $msg = "";
    $status = true;
    $qry = "select receivedby, ifnull(date(receiveddate), '') as receiveddate from cntnum where trno = ?";
    $checking = $this->coreFunctions->opentable($qry, [$trno]);

    if ($checking[0]->receiveddate == "") {
      $msg = "Already Unreceived! " . $checking[0]->receivedby . ' ' . $checking[0]->receiveddate;
    } else {
      $tag = $this->coreFunctions->execqry("update cntnum set receivedby='',
        receiveddate = null where trno=? ", "update", [$trno]);

      if ($tag) {
        $msg = "Unreceived Success!";
        $status = true;
        $this->logger->sbcwritelog($trno, $config, 'UNRECEIVED', 'CLICKED UNRECEIVED');
      } else {
        $msg = "Unreceived Failed!";
        $status = false;
      }
    }

    return ['status' => $status, 'msg' => $msg, 'dd' => 'Unreceived', 'reloadhead' => true];
  }


  public function gethead_receivedbutton($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];

    $table = $this->head;
    $htable = $this->hhead;
    $tablenum = $this->tablenum;

    $isproject = $this->companysetup->getisproject($config['params']);
    $projectfilter  = "";

    if ($isproject) {
      $viewall = $this->othersClass->checkAccess($config['params']['user'], 2232);
      $project = $this->coreFunctions->getfieldvalue("useraccess", "project", "username=?", [$config['params']['user']]);
      $projectid = $this->coreFunctions->getfieldvalue("projectmasterfile", "line", "code=?", [$project]);
      if ($viewall == '0') {
        $projectfilter = " and head.projectid = " . $projectid . " ";
      }
    }

    $qryselect = "select
      num.center,
      head.trno,
      head.docno,
      client.client,
      head.terms,
      head.cur,
      head.forex,
      head.yourref,
      head.ourref,
      head.contra,
      coa.acnoname,
      '' as dacnoname,
      left(head.dateid,10) as dateid,
      head.clientname,
      head.address,
      head.shipto,
      date_format(head.createdate,'%Y-%m-%d') as createdate,
      head.rem,
      head.tax,
      head.vattype,
      head.billid,
      head.shipid,
      head.billcontactid,
      head.shipcontactid,
      '' as dvattype,
      warehouse.client as wh,
      warehouse.clientname as whname,
      '' as dwhname,
      head.projectid,
      '' as dprojectname,
      left(head.due,10) as due,
      client.groupid,ifnull(p.code,'') as projectcode,ifnull(p.name,'') as projectname,ifnull(s.line,0) as subproject,ifnull(s.subproject,'') as subprojectname,
      head.branch,ifnull(b.clientname,'') as branchname,ifnull(b.client,'') as branchcode,'' as dbranchname,ifnull(d.client,'') as dept,ifnull(d.clientname,'') as deptname,
      head.deptid,'' as ddeptname,head.invoiceno,left(head.invoicedate,10) as invoicedate,head.ewt,head.ewtrate ";

    $qry = $qryselect . " from $table as head
      left join $tablenum as num on num.trno = head.trno
      left join client on head.client = client.client
      left join client as warehouse on warehouse.client = head.wh
      left join client as b on b.clientid = head.branch
      left join coa on coa.acno=head.contra
      left join projectmasterfile as p on p.line=head.projectid
      left join client as d on d.clientid = head.deptid
      left join subproject as s on s.line = head.subproject
      where head.trno = ? and num.doc=? and num.center = ? " . $projectfilter . "
      union all " . $qryselect . " from $htable as head
      left join $tablenum as num on num.trno = head.trno
      left join client on head.clientid = client.clientid
      left join client as warehouse on warehouse.clientid = head.whid
      left join client as b on b.clientid = head.branch
      left join coa on coa.acno=head.contra
      left join projectmasterfile as p on p.line=head.projectid
      left join client as d on d.clientid = head.deptid
      left join subproject as s on s.line = head.subproject
      where head.trno = ? and num.doc=? and num.center=? " . $projectfilter;

    $head = $this->coreFunctions->opentable($qry, [$trno, $doc, $center, $trno, $doc, $center]);

    if (empty($head)) {
      $head = [];
    }

    return $head;
  }

  public function diagram($config)
  {

    $data = [];
    $nodes = [];
    $links = [];
    $data['width'] = 1500;
    $startx = 100;

    $qry = "select po.trno,po.docno,left(po.dateid,10) as dateid,concat('Total PO Amt: ',round(sum(s.ext),2)) as rem,s.refx 
    from hpohead as po left join hpostock as s on s.trno = po.trno left join glstock as g on g.refx = po.trno and g.linex = s.line where g.trno = ? group by po.trno,po.docno,po.dateid,s.refx";
    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
    if (!empty($t)) {
      $startx = 550;
      $a = 0;
      foreach ($t as $key => $value) {
        //PO
        data_set($nodes, $t[$key]->docno, ['align' => 'right', 'x' => 200, 'y' => 50 + $a, 'w' => 250, 'h' => 80, 'type' => $t[$key]->docno, 'label' => $t[$key]->rem, 'color' => 'blue', 'details' => [$t[$key]->dateid]]);
        array_push($links, ['from' => $t[$key]->docno, 'to' => 'rr']);
        $a = $a + 100;

        if (floatval($t[$key]->refx) != 0) {
          //pr
          $qry = "select po.docno,left(po.dateid,10) as dateid,concat('Total PR Qty: ',round(sum(s.qty),2)) as rem from hprhead as po left join hprstock as s on s.trno = po.trno  where po.trno = ? group by po.docno,po.dateid";
          $x = $this->coreFunctions->opentable($qry, [$t[$key]->refx]);
          $poref = $t[$key]->docno;
          if (!empty($x)) {
            foreach ($x as $key2 => $value) {
              data_set($nodes, $x[$key2]->docno, ['align' => 'right', 'x' => 10, 'y' => 50 + $a, 'w' => 250, 'h' => 80, 'type' => $x[$key2]->docno, 'label' => $x[$key2]->rem, 'color' => 'yellow', 'details' => [$x[$key2]->dateid]]);
              array_push($links, ['from' => $x[$key2]->docno, 'to' => $poref]);
              $a = $a + 100;
            }
          }
        }
      }
    }

    $qry = "select head.docno,
    left(head.dateid,10) as dateid,
    concat('Amount: ',round(ifnull(apledger.db, 0)+ifnull(apledger.cr, 0),2),'  -  ','BALANCE: ',
    round(ifnull(apledger.bal, 0),2)) as rem
    from glhead as head
    left join apledger on head.trno = apledger.trno
    where head.trno=?";
    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
    if (!empty($t)) {
      data_set($nodes, 'rr', ['align' => 'right', 'x' => $startx, 'y' => 100, 'w' => 250, 'h' => 130, 'type' => $t[0]->docno, 'label' => $t[0]->rem, 'color' => 'green', 'details' => [$t[0]->dateid]]);
    }



    $qry = "select head.docno as docno,left(head.dateid,10) as dateid,
    CAST(concat('Applied Amount: ',round(detail.db+detail.cr,2)) as CHAR) as rem
    from lahead as head
    left join ladetail as detail on detail.trno=head.trno
    where detail.refx=?
    union all
    select head.docno as docno,left(head.dateid,10) as dateid,
    CAST(concat('Applied Amount: ',round(detail.db+detail.cr,2)) as CHAR) as rem
    from glhead as head
    left join gldetail as detail on detail.trno=head.trno
    where detail.refx=?
    union all
    select head.docno as docno,left(head.dateid,10) as dateid,
    CAST(concat('Return Item: ',item.barcode,'-',item.itemname,' Qty: ',round(stock.isqty, 2)) as CHAR) as rem
    from lahead as head
    left join lastock as stock on stock.trno=head.trno
    left join item on item.itemid=stock.itemid
    where head.doc != ? and stock.refx=?
    union all
    select head.docno as docno,left(head.dateid,10) as dateid,
    CAST(concat('Return Item: ',item.barcode,'-',item.itemname,' Qty: ',round(stock.isqty, 2)) as CHAR) as rem
    from glhead as head
    left join glstock as stock on stock.trno=head.trno
    left join item on item.itemid = stock.itemid
    where head.doc != ? and stock.refx=?
    ";

    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno'], $config['params']['trno'], $config['params']['doc'], $config['params']['trno'], $config['params']['doc'], $config['params']['trno'], $config['params']['trno'], $config['params']['trno']]);
    if (!empty($t)) {
      $y = 0;
      foreach ($t as $key => $value) {
        data_set($nodes, $t[$key]->docno, ['align' => 'left', 'x' => $startx + 400, 'y' => 50 + $y, 'w' => 250, 'h' => 80, 'type' => $t[$key]->docno, 'label' => $t[$key]->rem, 'color' => 'red', 'details' => [$t[$key]->dateid]]);
        array_push($links, ['from' => 'rr', 'to' => $t[$key]->docno]);
        $y = $y + 120;
      }
    }
    $data['nodes'] = $nodes;




    $data['links'] = $links;
    return ['status' => true, 'msg' => 'Successfully fetched.', 'data' => $data];
  }

  public function flowchart($config)
  {
    $data = [];
    $nodes = [];
    $links = [];
    $data['centerX'] = 1024;
    $data['centerY'] = 140;
    $data['scale'] = 1;
    $qry = "select apledger.docno,left(apledger.dateid,10) as dateid,CAST(concat('Amount: ',round(apledger.db+apledger.cr,2),'  -  ','BALANCE: ',round(apledger.bal,2)) as CHAR) as rem from apledger where trno=?";
    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
    array_push($nodes, ['id' => 2, 'x' => -500, 'y' => -120, 'type' => $t[0]->docno, 'label' => $t[0]->rem]);

    array_push($nodes, ['id' => 4, 'x' => -357, 'y' => 80, 'type' => 'Script', 'label' => 'test2']);
    array_push($nodes, ['id' => 6, 'x' => -557, 'y' => 80, 'type' => 'Rule', 'label' => 'test3']);
    $data['nodes'] = $nodes;
    array_push($links, ['id' => 3, 'from' => 2, 'to' => 4]);
    array_push($links, ['id' => 5, 'from' => 2, 'to' => 6]);
    $data['links'] = $links;
    return ['status' => true, 'msg' => 'Successfully fetched.', 'data' => $data];
  }

  public function updateperitem($config)
  {
    $config['params']['data'] = $config['params']['row'];
    $isupdate = $this->additem('update', $config);
    $data = $this->openstockline($config);
    $data2 = json_decode(json_encode($data), true);

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

      $minmax = $this->othersClass->getitemminmax($data2[0]['barcode'], $data2[0]['wh'], $data2[0]['qty']);
      if ($minmax <> "") {
        $msg = $minmax;
      }
      return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved -' . $msg];
    }
  }



  public function updateitem($config)
  {
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $this->additem('update', $config);
    }
    $data = $this->openstock($config['params']['trno'], $config);
    $data2 = json_decode(json_encode($data), true);
    $isupdate = true;
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
    if ($isupdate) {
      return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    } else {
      return ['inventory' => $data, 'status' => true, 'msg' => 'Please check some items have zero qty (' . $msg1 . ' / ' . $msg2 . ')'];
    }
  } //end function


  public function addallitem($config)
  {
    $row = [];
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $row = $this->additem('insert', $config);
      if (isset($config['params']['data']['refx'])) {
        if ($config['params']['data']['refx'] != 0) {
          if ($this->othersClass->setserveditemsRR($config['params']['data']['refx'], $config['params']['data']['linex'], $this->hqty) == 0) {
            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $row['row'][0]->trno, 'line' => $row['row'][0]->line]);
            $this->othersClass->setserveditemsRR($config['params']['data']['refx'], $config['params']['data']['linex'], $this->hqty);
          }
        }
      }
    }
    $data = $this->openstock($config['params']['trno'], $config);
    return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
  } //end function


  public function quickadd($config)
  {
    $barcodelength = $this->companysetup->getbarcodelength($config['params']);
    $trno = $config['params']['trno'];
    $config['params']['barcode'] = trim($config['params']['barcode']);
    if ($barcodelength == 0) {
      $barcode = $config['params']['barcode'];
    } else {
      $barcode = $this->othersClass->padj($config['params']['barcode'], $barcodelength);
    }
    $wh = $config['params']['wh'];
    $item = $this->coreFunctions->opentable("select item.itemid,0 as amt,'' as disc,'' as loc,'" . $wh . "' as wh, 1 as qty, uom,'' as expiry,'' as rem from item where barcode=?", [$barcode]);

    $item = json_decode(json_encode($item), true);
    $forex = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno = ?', [$trno]);

    if (!empty($item)) {
      $config['params']['barcode'] = $barcode;
      $lprice = $this->getlatestprice($config, $forex);
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
  public function additem($action, $config, $setlog = false)
  {
    $isproject = $this->companysetup->getisproject($config['params']);
    $uom = $config['params']['data']['uom'];
    $itemid = $config['params']['data']['itemid'];
    if ($itemid == '') {
      $itemid = 0;
    }
    $trno = $config['params']['trno'];
    $disc = isset($config['params']['data']['disc']) ? $config['params']['data']['disc'] : '';
    $wh = $config['params']['data']['wh'];
    $loc = $config['params']['data']['loc'];
    $expiry = isset($config['params']['data']['expiry']) ? $config['params']['data']['expiry'] : '';
    $rem = isset($config['params']['data']['rem']) ? $config['params']['data']['rem'] : '';
    $refx = 0;
    $linex = 0;
    $fcost = 0;
    $ref = '';
    $stageid = 0;
    $palletid = 0;
    $locid = 0;
    $stock_projectid = 0;
    $poref = '';
    $sgdrate = 0;
    $reqtrno = 0;
    $reqline = 0;
    $intransit = 0;

    $reqtrno = isset($config['params']['data']['reqtrno']) ? $config['params']['data']['reqtrno'] : 0;
    $reqline = isset($config['params']['data']['reqline']) ? $config['params']['data']['reqline'] : 0;

    $cdrefx = isset($config['params']['data']['cdrefx']) ? $config['params']['data']['cdrefx'] : 0;
    $cdlinex = isset($config['params']['data']['cdlinex']) ? $config['params']['data']['cdlinex'] : 0;

    $prrefx = isset($config['params']['data']['prrefx']) ? $config['params']['data']['prrefx'] : 0;
    $prlinex = isset($config['params']['data']['prlinex']) ? $config['params']['data']['prlinex'] : 0;

    $status1 = isset($config['params']['data']['status1']) ? $config['params']['data']['status1'] : 0;
    $status2 = isset($config['params']['data']['status2']) ? $config['params']['data']['status2'] : 0;
    $checkstat = isset($config['params']['data']['checkstat']) ? $config['params']['data']['checkstat'] : 0;

    $qty1 = isset($config['params']['data']['qty1']) ? $config['params']['data']['qty1'] : 0;
    $qty2 = isset($config['params']['data']['qty2']) ? $config['params']['data']['qty2'] : 0;
    $tqty = isset($config['params']['data']['tqty']) ? $config['params']['data']['tqty'] : 0;

    $ctrlno = isset($config['params']['data']['ctrlno']) ? $config['params']['data']['ctrlno'] : '';

    $amt1 = isset($config['params']['data']['amt1']) ? $config['params']['data']['amt1'] : 0;
    $amt2 = isset($config['params']['data']['amt2']) ? $config['params']['data']['amt2'] : 0;
    $amt3 = isset($config['params']['data']['amt3']) ? $config['params']['data']['amt3'] : 0;
    $amt4 = isset($config['params']['data']['amt4']) ? $config['params']['data']['amt4'] : 0;
    $amt5 = isset($config['params']['data']['amt5']) ? $config['params']['data']['amt5'] : 0;

    $waivedspecs = isset($config['params']['data']['waivedspecs']) ? $config['params']['data']['waivedspecs'] : 0;
    if (isset($config['params']['data']['refx'])) {
      $refx = $config['params']['data']['refx'];
    }
    if (isset($config['params']['data']['linex'])) {
      $linex = $config['params']['data']['linex'];
    }
    if (isset($config['params']['data']['ref'])) {
      $ref = $config['params']['data']['ref'];
    }

    if (isset($config['params']['data']['stageid'])) {
      $stageid = $config['params']['data']['stageid'];
    }

    if (isset($config['params']['data']['palletid'])) {
      $palletid = $config['params']['data']['palletid'];
    }

    if (isset($config['params']['data']['stageid'])) {
      $stageid = $config['params']['data']['stageid'];
    }

    if (isset($config['params']['data']['palletid'])) {
      $palletid = $config['params']['data']['palletid'];
    }

    if (isset($config['params']['data']['locid'])) {
      $locid = $config['params']['data']['locid'];
    }

    if (isset($config['params']['data']['poref'])) {
      $poref = $config['params']['data']['poref'];
    }

    if (isset($config['params']['data']['sgdrate'])) {
      $sgdrate = $config['params']['data']['sgdrate'];
    } else {
      $sgdrate = $this->othersClass->getexchangerate('PHP', 'SGD');
    }

    if (isset($config['params']['data']['intransit'])) {
      if ($config['params']['data']['intransit'] == 'true') {
        $intransit = 1;
      }
    }

    if ($waivedspecs == 'true') {
      $waivedspecs = 1;
    } else {
      $waivedspecs = 0;
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
      $amt = $config['params']['data']['amt'];
      $qty = $config['params']['data']['qty'];
    } elseif ($action == 'update') {
      $config['params']['line'] = $config['params']['data']['line'];
      $line = $config['params']['data']['line'];
      $amt = $config['params']['data'][$this->damt];
      $qty = $config['params']['data'][$this->dqty];

      $config['params']['line'] = $line;
    }

    $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
    $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);

    $barcode = '';
    $qty = $this->othersClass->sanitizekeyfield('qty', $qty);
    $amt = $this->othersClass->sanitizekeyfield('amt', $amt);
    $factor = 1;
    if (!empty($item)) {
      $barcode = $item[0]->barcode;
      $item[0]->factor = $this->othersClass->val($item[0]->factor);
      if ($item[0]->factor !== 0) $factor = $item[0]->factor;
    }
    $vat = $this->coreFunctions->getfieldvalue($this->head, 'tax', 'trno=?', [$trno]);
    $forex = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
    $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);

    if (empty($whid)) {
      $whid = 0;
    }
    $projectid = $this->coreFunctions->getfieldvalue($this->head, 'projectid', 'trno=?', [$trno]);

    if (floatval($forex) <> 1) {
      $fcost = $amt;
    }
    $qty = round($qty, $this->companysetup->getdecimal('qty', $config['params']));
    $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor, $vat);

    $ext = number_format($computedata['ext'], 2, '.', '');

    $data = [
      'trno' => $trno,
      'line' => $line,
      'itemid' => $itemid,
      $this->damt => $amt,
      $this->hamt => $computedata['amt'] * $forex,
      $this->dqty => $qty,
      $this->hqty => $computedata['qty'],
      'ext' => $ext,
      'disc' => $disc,
      'whid' => $whid,
      'refx' => $refx,
      'linex' => $linex,
      'ref' => $ref,
      'loc' => $loc,
      'expiry' => $expiry,
      'uom' => $uom,
      'rem' => $rem,
      'palletid' => $palletid,
      'locid' => $locid,
      'stageid' => $stageid,
      'reqtrno' => $reqtrno,
      'reqline' => $reqline,
      'cdrefx' => $cdrefx,
      'cdlinex' => $cdlinex,
      'prrefx' => $prrefx,
      'prlinex' => $prlinex
    ];

    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }


    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $data['editdate'] = $current_timestamp;
    $data['editby'] = $config['params']['user'];

    if ($action == 'insert') {
      $data['encodeddate'] = $current_timestamp;
      $data['encodedby'] = $config['params']['user'];
      if ($isproject) {
        $isho = $this->coreFunctions->getfieldvalue("projectmasterfile", "isho", "line = " . $projectid);
        if (!$isho) {
          if ($data['stageid'] == 0) {
            $msg = 'Stage cannot be blank -' . $item[0]->barcode;
            return ['status' => false, 'msg' => $msg];
          }
        }
      }
      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
        switch ($this->companysetup->getsystemtype($config['params'])) {
          case 'AIMS':
          case 'ATI':
            $stockinfo_data = [
              'trno' => $trno,
              'line' => $line,
              'amt1' => $amt1,
              'amt2' => $amt2,
              'amt3' => $amt3,
              'amt4' => $amt4,
              'amt5' => $amt5,
              'rem' => $rem,
              'intransit' => $intransit,
              'ctrlno' => $ctrlno,
              'waivedspecs' => $waivedspecs
            ];
            $this->coreFunctions->sbcinsert('stockinfo', $stockinfo_data);
            break;
        }

        if ($isproject) {
          $this->updateprojmngmt($config, $stageid);
        }
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' Barcode:' . $barcode . ' Amt:' . $amt . ' Disc:' . $disc . ' WH:' . $wh . ' Uom:' . $uom . ' Ext:' . $computedata['ext'], $setlog ? $this->tablelogs : '');

        if ($refx != 0) {

          $this->coreFunctions->sbcupdate("hpostock", ['rramt' => $data['rrcost']], ['trno' => $refx, 'line' => $linex]);
        }

        if ($data['cdrefx'] != 0) {
          if (app('App\Http\Classes\modules\ati\po')->setservedcanvassitems($data['cdrefx'], $data['cdlinex']) == 0) {
            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
            app('App\Http\Classes\modules\ati\po')->setservedcanvassitems($data['cdrefx'], $data['cdlinex']);
            return ['status' => false, 'msg' => 'Failed to apply qa of canvass'];
          }
        }

        if ($data['prrefx'] != 0 || $reqtrno != 0) {
          if ($this->setserveditemsPR($data['prrefx'], $data['prlinex'], $reqtrno, $reqline) == 0) {
            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
            $this->setserveditemsPR($data['prrefx'], $data['prlinex'], $reqtrno, $reqline);
            return ['status' => false, 'msg' => 'Failed to apply qa of request'];
          }
        }

        $row = $this->openstockline($config);

        return ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
      } else {
        return ['status' => false, 'msg' => 'Add item Failed'];
      }
    } elseif ($action == 'update') {

      if (isset($config['params']['data']['ispicked'])) {
        if ($config['params']['data']['ispicked'] == 'true') {
          $istransmit = $this->coreFunctions->getfieldvalue($this->stock, "pickerstart", "trno=? and line=?", [$trno, $line]);
          if ($istransmit == '' || $istransmit == null) {
            $data['pickerstart'] = $this->othersClass->getCurrentTimeStamp();
          }
        }
      } else {
        $data['pickerstart'] = null;
      }

      $return = true;
      $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);


      switch ($this->companysetup->getsystemtype($config['params'])) {
        case 'AIMS':
        case 'ATI':
          $stockinfo_data = [
            'trno' => $trno,
            'line' => $line,
            'amt1' => $amt1,
            'amt2' => $amt2,
            'amt3' => $amt3,
            'amt4' => $amt4,
            'amt5' => $amt5,
            'rem' => $rem,
            'intransit' => $intransit,
            'status1' => $status1,
            'qty1' => $qty1,
            'status2' => $status2,
            'qty2' => $qty2,
            'tqty' => $tqty,
            'checkstat' => $checkstat,
            'ctrlno' => $ctrlno,
            'waivedspecs' => $waivedspecs
          ];

          $stockinfo_data['editdate'] = $this->othersClass->getCurrentTimeStamp();
          $stockinfo_data['editby'] = $config['params']['user'];
          foreach ($stockinfo_data as $key => $valueinfo) {
            $stockinfo_data[$key] = $this->othersClass->sanitizekeyfield($key, $stockinfo_data[$key]);
          }


          $this->coreFunctions->sbcupdate('stockinfo', $stockinfo_data, ['trno' => $trno, 'line' => $line]);

          break;
      }

      $this->updateprojmngmt($config, $stageid);
      if ($this->othersClass->setserveditemsRR($refx, $linex, $this->hqty) === 0) {
        $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
        $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
        $this->othersClass->setserveditemsRR($refx, $linex, $this->hqty);
        $return = false;
      }

      if ($refx != 0) {

        $this->coreFunctions->sbcupdate("hpostock", ['rramt' => $data['rrcost']], ['trno' => $refx, 'line' => $linex]);
      }

      if ($data['cdrefx'] != 0) {
        if (app('App\Http\Classes\modules\ati\po')->setservedcanvassitems($data['cdrefx'], $data['cdlinex']) == 0) {
          $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
          $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
          app('App\Http\Classes\modules\ati\po')->setservedcanvassitems($data['cdrefx'], $data['cdlinex']);
          return false;
        }
      }

      if ($data['prrefx'] != 0 || $reqtrno != 0) {
        if ($this->setserveditemsPR($data['prrefx'], $data['prlinex'], $reqtrno, $reqline) == 0) {
          $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
          $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
          $this->setserveditemsPR($data['prrefx'], $data['prlinex'], $reqtrno, $reqline);
          return ['status' => false, 'msg' => 'Failed to apply qa of request'];
        }
      }

      return $return;
    }
  } // end function

  public function deleteallitem($config)
  {

    $trno = $config['params']['trno'];
    $data = $this->coreFunctions->opentable('select refx,linex,stageid,cdrefx,cdlinex,prrefx,prlinex,reqtrno,reqline from ' . $this->stock . ' where trno=? and (refx<>0 or cdrefx<>0 or prrefx<>0)', [$trno]);

    $checkstat = $this->coreFunctions->opentable("select trno from stockinfo where trno=? and (status1 in (51,52) or status2 in (53,54) or checkstat in (55,56))", [$trno]);
    if (!empty($checkstat)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Failed to delete all items, some items are have received/check status'];
    }
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from serialin where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from stockinfo where trno=?', 'delete', [$trno]);
    foreach ($data as $key => $value) {
      $this->updateprojmngmt($config, $data[$key]->stageid);
      $this->othersClass->setserveditemsRR($data[$key]->refx, $data[$key]->linex, $this->hqty);

      if ($data[$key]->cdrefx != 0) {
        app('App\Http\Classes\modules\ati\po')->setservedcanvassitems($data[$key]->cdrefx, $data[$key]->cdlinex);
      }

      if ($data[$key]->prrefx != 0 || $data[$key]->reqtrno) {
        $this->setserveditemsPR($data[$key]->prrefx, $data[$key]->prlinex, $data[$key]->reqtrno, $data[$key]->reqline);
      }
    }

    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL ITEMS');
    return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
  }


  public function deleteitem($config)
  {
    $config['params']['trno'] = $config['params']['row']['trno'];
    $config['params']['line'] = $config['params']['row']['line'];
    $data = $this->openstockline($config);

    $trno = $config['params']['trno'];
    $line = $config['params']['line'];

    switch ($config['params']['row']['status1']) {
      case 51:
      case 52:
        return ['status' => false, 'msg' => 'Unable to delete item with status of ' . $config['params']['row']['status1name']];
        break;
    }

    switch ($config['params']['row']['status2']) {
      case 53:
      case 54:
        return ['status' => false, 'msg' => 'Unable to delete item with status of ' . $config['params']['row']['status2name']];
        break;
    }

    switch ($config['params']['row']['checkstat']) {
      case 55:
      case 56:
        return ['status' => false, 'msg' => 'Unable to delete item with status of ' . $config['params']['row']['checkstatname']];
        break;
    }

    $qry = "delete from " . $this->stock . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
    $this->coreFunctions->execqry('delete from stockinfo where trno=? and line=?', 'delete', [$trno, $line]);
    $this->logger->sbcwritelog(
      $trno,
      $config,
      'STOCKINFO',
      'DELETE - Line:' . $line
        . ' Notes:' . $config['params']['row']['rem']
    );

    $qry = "delete from serialin where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
    $this->updateprojmngmt($config, $data[0]->stageid);
    if ($data[0]->refx !== 0) {
      $this->othersClass->setserveditemsRR($data[0]->refx, $data[0]->linex, $this->hqty);
    }
    if ($data[0]->cdrefx != 0) {
      app('App\Http\Classes\modules\ati\po')->setservedcanvassitems($data[0]->cdrefx, $data[0]->cdlinex);
    }
    if ($data[0]->prrefx != 0 || $data[0]->reqtrno != 0) {
      $this->setserveditemsPR($data[0]->prrefx, $data[0]->prlinex, $data[0]->reqtrno, $data[0]->reqline);
    }
    $data = json_decode(json_encode($data), true);
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' Barcode:' . $data[0]['barcode'] . ' Qty:' . $data[0][$this->dqty] . ' Amt:' . $data[0][$this->damt] . ' Disc:' . $data[0]['disc'] . ' WH:' . $data[0]['wh'] . ' Ext:' . $data[0]['ext']);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
  } // end function

  public function setserveditemsPR($refx, $linex, $reqtro, $reqline)
  {

    $qry1 = "select stock." . $this->hqty . " from lahead as head left join lastock as stock on stock.trno=head.trno where head.doc='RR' and stock.reqtrno<>0 and stock.prrefx<>0 and stock.prrefx=" . $refx . " and stock.prlinex=" . $linex;
    $qry1 = $qry1 . " union all select glstock." . $this->hqty . " from glhead left join glstock on glstock.trno=glhead.trno where glhead.doc='RR' and glstock.reqtrno<>0 and glstock.prrefx<>0 and glstock.prrefx=" . $refx . " and glstock.prlinex=" . $linex;
    $qry1 = $qry1 . " union all select lastock." . $this->hqty . " from lahead left join lastock on lastock.trno=lahead.trno where lahead.doc='RR' and lastock.prrefx=0 and lastock.reqtrno=" . $reqtro . " and lastock.reqline=" . $reqline;
    $qry1 = $qry1 . " union all select glstock." . $this->hqty . " from glhead left join glstock on glstock.trno=glhead.trno where glhead.doc='RR' and glstock.prrefx=0 and glstock.reqtrno=" . $reqtro . " and glstock.reqline=" . $reqline;

    $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";

    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty === '') {
      $qty = 0;
    }

    if ($refx != 0) {
      $result = $this->coreFunctions->execqry("update hprstock set rrqa=" . $qty . ", statrem='Receiving Report - Draft', statdate='" . $this->othersClass->getCurrentTimeStamp() . "' where trno=" . $refx . " and line=" . $linex, 'update'); //qa=" . $qty . ", 
    } else {
      $result = $this->coreFunctions->execqry("update hprstock set rrqa=" . $qty . ", statrem='Receiving Report - Draft', statdate='" . $this->othersClass->getCurrentTimeStamp() . "' where trno=" . $reqtro . " and line=" . $reqline, 'update');
    }

    $status = $this->coreFunctions->datareader("select ifnull(count(trno),0) as value from hprstock where trno=? and qty>(qa+voidqty)", [$refx]);
    if ($status) {
      $status = $this->coreFunctions->datareader("select ifnull(count(trno),0) as value from hprstock where trno=? and (qa+voidqty)<>0", [$refx]);
      if ($status) {
        $this->coreFunctions->execqry("update transnum set statid=6 where trno=" . $refx);
      } else {
        $this->coreFunctions->execqry("update transnum set statid=5 where trno=" . $refx);
      }
    } else {
      $this->coreFunctions->execqry("update transnum set statid=7 where trno=" . $refx);
    }
    return $result;
  } //end function

  public function getlatestprice($config, $forex = 1)
  {
    $barcode = $config['params']['barcode'];
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];

    $qry = "select docno,left(dateid,10) as dateid,round(amt,2) as amt,disc,uom from(select head.docno,head.dateid,
            case " . $forex . " when 1 then stock." . $this->damt . "*head.forex else stock." . $this->damt . " end as amt,stock.uom,stock.disc
            from lahead as head
            left join lastock as stock on stock.trno = head.trno
            left join cntnum on cntnum.trno=head.trno
            left join item on item.itemid = stock.itemid
            where head.doc = '" . $config['params']['doc'] . "' and cntnum.center = ?
            and item.barcode = ? and head.client =?
            and stock.cost <> 0 and cntnum.trno <>?
            UNION ALL
            select head.docno,head.dateid,case " . $forex . " when 1 then stock." . $this->damt . "*head.forex else stock." . $this->damt . " end as amt,
            stock.uom,stock.disc from glhead as head
            left join glstock as stock on stock.trno = head.trno
            left join item on item.itemid = stock.itemid
            left join client on client.clientid = head.clientid
            left join cntnum on cntnum.trno=head.trno
            where head.doc = '" . $config['params']['doc'] . "' and cntnum.center = ?
            and item.barcode = ? and client.client =?
            and stock." . $this->hamt . " <> 0 and cntnum.trno <>?
            order by dateid desc limit 5) as tbl order by dateid desc limit 1";
    $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $client, $trno, $center, $barcode, $client, $trno]);

    if (!empty($data)) {
      return ['status' => true, 'msg' => 'Found the latest purchase price...', 'data' => $data];
    } else {
      return ['status' => false, 'msg' => 'No Latest price found...'];
    }
  } // end function

  public function getposummaryqry($config)
  {
    return "
      select head.docno, head.client, head.clientname, head.address, ifnull(head.rem,'') as rem, head.cur, head.forex, head.shipto, head.ourref, head.yourref, head.projectid, head.terms,
      item.itemid,stock.trno, stock.line, item.barcode,stock.uom, stock.cost, 
      ((stock.qty-stock.voidqty)-stock.qa) as qty,stock.rrcost, head.tax, head.vattype,
     FORMAT(((  stock.qty-stock.voidqty )/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
      FORMAT(sm.amt1," . $this->companysetup->getdecimal('currency', $config['params']) . ") as amt1,
      FORMAT(sm.amt2," . $this->companysetup->getdecimal('currency', $config['params']) . ") as amt2,
      FORMAT(sm.amt3," . $this->companysetup->getdecimal('currency', $config['params']) . ") as amt3,
      FORMAT(sm.amt4," . $this->companysetup->getdecimal('currency', $config['params']) . ") as amt4,
      FORMAT(sm.amt5," . $this->companysetup->getdecimal('currency', $config['params']) . ") as amt5,
      stock.disc,stock.stageid,head.branch,head.billcontactid,head.shipcontactid,head.billid,head.shipid,head.tax,head.vattype,head.deptid,
      stock.sgdrate,stock.reqtrno,stock.reqline,stock.isadv,info.itemdesc,stock.cvtrno,hinfo.pdeadline,head.wh,info.ctrlno
      FROM hpohead as head left join hpostock as stock on stock.trno=head.trno 
      left join item on item.itemid=stock.itemid left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
      left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
      left join hstockinfotrans as sm on sm.trno=stock.trno and sm.line=stock.line
      left join hheadinfotrans as hinfo on hinfo.trno=head.trno 
      where stock.trno = ? and stock.qty>stock.qa and (stock.qty-stock.voidqty) <> 0";
  }

  public function getposummary($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];

    $msg = '';
    $reqpayment_msg = '';

    foreach ($config['params']['rows'] as $key => $value) {
      $qry = $this->getposummaryqry($config);
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno']]);
      if (!empty($data)) {

        $headdata = [
          'vattype' => $data[0]->vattype,
          'tax' => $data[0]->tax,
          'terms' => $data[0]->terms,
          'cur' => $data[0]->cur,
          'forex' => $data[0]->forex,
          'orderno' => $data[0]->yourref
          #'wh' => $data[0]->wh
        ];

        $this->coreFunctions->sbcupdate($this->head, $headdata, ['trno' => $trno]);
        $this->coreFunctions->sbcupdate("cntnuminfo", ['pdeadline' => $data[0]->pdeadline], ['trno' => $trno]);

        foreach ($data as $key2 => $value) {
          if ($data[$key2]->isadv == 1 && $data[$key2]->cvtrno == 0) {
            if ($reqpayment_msg == "") {
              $reqpayment_msg = $data[$key2]->itemdesc . "";
            } else {
              $reqpayment_msg .= ", " . $data[$key2]->itemdesc . "";
            }
            continue;
          }
          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['qty'] = $data[$key2]->rrqty;
          $config['params']['data']['wh'] = $wh;
          $config['params']['data']['loc'] = '';
          $config['params']['data']['expiry'] = '';
          $config['params']['data']['rem'] = '';
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->rrcost;
          $config['params']['data']['stageid'] = $data[$key2]->stageid;
          $config['params']['data']['reqtrno'] = $data[$key2]->reqtrno;
          $config['params']['data']['reqline'] = $data[$key2]->reqline;
          $config['params']['data']['ctrlno'] = $data[$key2]->ctrlno;
          $config['params']['data']['amt1'] = $data[$key2]->amt1;
          $config['params']['data']['amt2'] = $data[$key2]->amt2;
          $config['params']['data']['amt3'] = $data[$key2]->amt3;
          $config['params']['data']['amt4'] = $data[$key2]->amt4;
          $config['params']['data']['amt5'] = $data[$key2]->amt5;
          $return = $this->additem('insert', $config);
          if ($return['status']) {

            $this->coreFunctions->execqry("update hprstock set statrem='Receiving Report - Draft', statdate='" . $this->othersClass->getCurrentTimeStamp() . "' where trno=" . $data[$key2]->reqtrno . " and line=" . $data[$key2]->reqline, 'update');

            if ($this->othersClass->setserveditemsRR($data[$key2]->trno, $data[$key2]->line, $this->hqty) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->othersClass->setserveditemsRR($data[$key2]->trno, $data[$key2]->line, $this->hqty);
              $row = $this->openstockline($config);
              $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
            }

            $rrref = $this->coreFunctions->datareader(
              "select ifnull(group_concat(ref SEPARATOR '\r'),'') as value 
                      from (select concat(num.docno, ' - Draft') as ref 
                            from lastock as s 
                            left join cntnum as num on num.trno=s.trno 
                            where num.doc = 'RR' and s.reqtrno=? and s.reqline=? 
                            group by num.docno
                            union all
                            select concat(num.docno, ' - Posted') as ref 
                            from glstock as s 
                            left join cntnum as num on num.trno=s.trno 
                            where num.doc = 'RR' and s.reqtrno=? and s.reqline=? 
                            group by num.docno) as s",
              [$data[$key2]->reqtrno, $data[$key2]->reqline, $data[$key2]->reqtrno, $data[$key2]->reqline]
            );

            $this->coreFunctions->execqry("update hstockinfotrans set rrref='" . $rrref . "'  where trno=" . $data[$key2]->reqtrno . " and line=" . $data[$key2]->reqline);

            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach

    if ($reqpayment_msg == '') {
      $msg = 'Item was successfully added.';
    } else {
      $msg = $reqpayment_msg . " required payment first";
    }

    return ['row' => $rows, 'status' => true, 'msg' => $msg, 'reloadhead' => true];
  } //end function


  public function getpodetails($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];

    $msg = '';
    $reqpayment_msg = '';

    $filter = '';

    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.docno, item.itemid,stock.trno,stock.line, item.barcode,stock.uom, stock.cost,
        ((stock.qty-stock.voidqty)-stock.qa) as qty,stock.rrcost,
        FORMAT(((  stock.qty-stock.voidqty )/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
        FORMAT(sm.amt1," . $this->companysetup->getdecimal('currency', $config['params']) . ") as amt1,
        FORMAT(sm.amt2," . $this->companysetup->getdecimal('currency', $config['params']) . ") as amt2,
        FORMAT(sm.amt3," . $this->companysetup->getdecimal('currency', $config['params']) . ") as amt3,
        FORMAT(sm.amt4," . $this->companysetup->getdecimal('currency', $config['params']) . ") as amt4,
        FORMAT(sm.amt5," . $this->companysetup->getdecimal('currency', $config['params']) . ") as amt5,
        stock.disc,stock.stageid,head.yourref,stock.reqtrno,stock.reqline,stock.isadv,info.itemdesc,
        stock.cvtrno,head.tax,head.vattype,hinfo.pdeadline,head.cur,head.forex,head.terms,head.wh,head.due,
        info.ctrlno,case when sm.waivedspecs=0 then 'false' else 'true' end as waivedspecs
        FROM hpohead as head left join hpostock as stock on stock.trno=head.trno 
        left join item on item.itemid=stock.itemid 
        left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
        left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
        left join hstockinfotrans as sm on sm.trno=stock.trno and sm.line=stock.line
        left join hheadinfotrans as hinfo on hinfo.trno=head.trno 
        where stock.trno = ? and stock.line=? and (stock.qty-stock.voidqty) <> 0" . $filter;
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line']]);
      if (!empty($data)) {

        $headdata = [
          'vattype' => $data[0]->vattype,
          'tax' => $data[0]->tax,
          'terms' => $data[0]->terms,
          'due' => $data[0]->due,
          'cur' => $data[0]->cur,
          'forex' => $data[0]->forex,
          'orderno' => $data[0]->yourref
          #'wh' => $data[0]->wh
        ];

        $this->coreFunctions->sbcupdate($this->head, $headdata, ['trno' => $trno]);
        $this->coreFunctions->sbcupdate("cntnuminfo", ['pdeadline' => $data[0]->pdeadline], ['trno' => $trno]);

        foreach ($data as $key2 => $value) {
          if ($data[$key2]->isadv == 1 && $data[$key2]->cvtrno == 0) {
            if ($reqpayment_msg == "") {
              $reqpayment_msg = $data[$key2]->itemdesc . "";
            } else {
              $reqpayment_msg .= ", " . $data[$key2]->itemdesc . "";
            }
            continue;
          }
          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['qty'] = $data[$key2]->rrqty;
          $config['params']['data']['wh'] = $wh;
          $config['params']['data']['loc'] = '';
          $config['params']['data']['expiry'] = '';
          $config['params']['data']['rem'] = '';
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->rrcost;
          $config['params']['data']['stageid'] = $data[$key2]->stageid;
          $config['params']['data']['reqtrno'] = $data[$key2]->reqtrno;
          $config['params']['data']['reqline'] = $data[$key2]->reqline;
          $config['params']['data']['ctrlno'] = $data[$key2]->ctrlno;
          $config['params']['data']['amt1'] = $data[$key2]->amt1;
          $config['params']['data']['amt2'] = $data[$key2]->amt2;
          $config['params']['data']['amt3'] = $data[$key2]->amt3;
          $config['params']['data']['amt4'] = $data[$key2]->amt4;
          $config['params']['data']['amt5'] = $data[$key2]->amt5;
          $config['params']['data']['waivedspecs'] = $data[$key2]->waivedspecs;
          $return = $this->additem('insert', $config);
          if ($return['status']) {

            $this->coreFunctions->execqry("update hprstock set statrem='Receiving Report - Draft', statdate='" . $this->othersClass->getCurrentTimeStamp() . "' where trno=" . $data[$key2]->reqtrno . " and line=" . $data[$key2]->reqline, 'update');

            if ($this->othersClass->setserveditemsRR($data[$key2]->trno, $data[$key2]->line, $this->hqty) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->othersClass->setserveditemsRR($data[$key2]->trno, $data[$key2]->line, $this->hqty);
              $row = $this->openstockline($config);
              $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
            }

            $rrref = $this->coreFunctions->datareader(
              "select ifnull(group_concat(ref SEPARATOR '\r'),'') as value 
                      from (select concat(num.docno, ' - Draft') as ref 
                            from lastock as s 
                            left join cntnum as num on num.trno=s.trno 
                            where num.doc = 'RR' and s.reqtrno=? and s.reqline=? 
                            group by num.docno
                            union all
                            select concat(num.docno, ' - Posted') as ref 
                            from glstock as s 
                            left join cntnum as num on num.trno=s.trno 
                            where num.doc = 'RR' and s.reqtrno=? and s.reqline=? 
                            group by num.docno) as s",
              [$data[$key2]->reqtrno, $data[$key2]->reqline, $data[$key2]->reqtrno, $data[$key2]->reqline]
            );

            $this->coreFunctions->execqry("update hstockinfotrans set rrref='" . $rrref . "'  where trno=" . $data[$key2]->reqtrno . " and line=" . $data[$key2]->reqline);

            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach

    if ($reqpayment_msg == '') {
      $msg = 'Items were successfully added.';
    } else {
      $msg = $reqpayment_msg . " required payment first";
    }

    return ['row' => $rows, 'status' => true, 'msg' => $msg, 'reloadhead' => true];
  } //end function


  public function getcdsummaryqry($config)
  {
    $filter = '';
    if (isset($config['params']['client'])) {
      if ($config['params']['client'] != '') {
        $filter = " and head.client='" . $config['params']['client'] . "'";
      }
    }

    return "
    select head.docno, ifnull(item.itemid,0) as itemid,stock.trno,stock.line, ifnull(item.barcode,'') as barcode,stock.uom, stock.cost,(stock.qty-stock.qa) as qty,stock.rrcost,
        round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
        stock.disc,stock.reqtrno,stock.reqline, client.addr as address, '' as rem
        FROM hcdhead as head left join hcdstock as stock on stock.trno=head.trno
        left join transnum on transnum.trno=head.trno left join item on item.itemid=stock.itemid left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
        left join client on client.client=head.client
        where transnum.center= ? and stock.qty>stock.qa and stock.void=0 and stock.status=1" . $filter;
  }

  public function getcdsummary($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $center = $config['params']['center'];
    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = $this->getcdsummaryqry($config);
      $qry .= " and stock.trno = ? ";
      $data = $this->coreFunctions->opentable($qry, [$center, $config['params']['rows'][$key]['trno']]);
      if (!empty($data)) {
        foreach ($data as $key2 => $value) {
          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['qty'] = $data[$key2]->rrqty;
          $config['params']['data']['wh'] = $wh;
          $config['params']['data']['loc'] = '';
          $config['params']['data']['expiry'] = '';
          $config['params']['data']['rem'] = '';
          $config['params']['data']['refx'] = 0;
          $config['params']['data']['linex'] = 0;
          $config['params']['data']['cdrefx'] = $data[$key2]->trno;
          $config['params']['data']['cdlinex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->rrcost;
          $config['params']['data']['reqtrno'] = $data[$key2]->reqtrno;
          $config['params']['data']['reqline'] = $data[$key2]->reqline;
          $return = $this->additem('insert', $config);
          if ($return['status']) {
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.'];
  } //end function

  public function getcddetails($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $center = $config['params']['center'];
    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = $this->getcdsummaryqry($config);
      $qry =  $qry . " and stock.trno=? and stock.line=?";
      $data = $this->coreFunctions->opentable($qry, [$center, $config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line']]);
      if (!empty($data)) {
        foreach ($data as $key2 => $value) {
          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['qty'] = $data[$key2]->rrqty;
          $config['params']['data']['wh'] = $wh;
          $config['params']['data']['loc'] = '';
          $config['params']['data']['expiry'] = '';
          $config['params']['data']['rem'] = '';
          $config['params']['data']['refx'] = 0;
          $config['params']['data']['linex'] = 0;
          $config['params']['data']['cdrefx'] = $data[$key2]->trno;
          $config['params']['data']['cdlinex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->rrcost;
          $config['params']['data']['reqtrno'] = $data[$key2]->reqtrno;
          $config['params']['data']['reqline'] = $data[$key2]->reqline;
          $return = $this->additem('insert', $config);
          if ($return['status']) {
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.'];
  } //end function

  public function getprsummaryqry($config)
  {
    return "
    select head.docno, ifnull(item.itemid,0) as itemid,stock.trno,stock.line, ifnull(item.barcode,'') as barcode,stock.uom, stock.cost,(stock.qty-stock.qa-stock.voidqty) as qty,stock.rrcost,
        round((stock.qty-stock.qa-stock.voidqty)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,stock.disc,
        stockinfo.ctrlno,stockinfo.isasset
        FROM hprhead as head left join hprstock as stock on stock.trno=head.trno
        left join hstockinfotrans as stockinfo on stockinfo.trno = stock.trno and stockinfo.line = stock.line
        left join transnum on transnum.trno=head.trno left join item on item.itemid=stock.itemid left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
        where transnum.center= ? and stock.qty>(stock.qa+stock.cdqa) and stock.void=0";
  }

  public function getprsummary($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $center = $config['params']['center'];
    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = $this->getprsummaryqry($config);
      $qry .= " and stock.trno = ? ";
      $data = $this->coreFunctions->opentable($qry, [$center, $config['params']['rows'][$key]['trno']]);
      if (!empty($data)) {
        foreach ($data as $key2 => $value) {
          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['qty'] = $data[$key2]->rrqty;
          $config['params']['data']['wh'] = $wh;
          $config['params']['data']['loc'] = '';
          $config['params']['data']['expiry'] = '';
          $config['params']['data']['rem'] = '';
          $config['params']['data']['refx'] = 0;
          $config['params']['data']['linex'] = 0;
          $config['params']['data']['prrefx'] = $data[$key2]->trno;
          $config['params']['data']['prlinex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->rrcost;
          $config['params']['data']['reqtrno'] = $data[$key2]->trno;
          $config['params']['data']['reqline'] = $data[$key2]->line;
          $config['params']['data']['ctrlno'] = $data[$key2]->ctrlno;
          $config['params']['data']['isasset'] = $data[$key2]->isasset;
          $return = $this->additem('insert', $config);
          if ($return['status']) {

            $rrref = $this->coreFunctions->datareader(
              "select ifnull(group_concat(ref SEPARATOR '\r'),'') as value 
                      from (select concat(num.docno, ' - Draft') as ref 
                            from lastock as s 
                            left join cntnum as num on num.trno=s.trno 
                            where num.doc = 'RR' and s.reqtrno=? and s.reqline=? 
                            group by num.docno
                            union all
                            select concat(num.docno, ' - Posted') as ref 
                            from glstock as s 
                            left join cntnum as num on num.trno=s.trno 
                            where num.doc = 'RR' and s.reqtrno=? and s.reqline=? 
                            group by num.docno) as s",
              [$data[$key2]->trno, $data[$key2]->line, $data[$key2]->trno, $data[$key2]->line]
            );

            $this->coreFunctions->execqry("update hstockinfotrans set rrref='" . $rrref . "'  where trno=" . $data[$key2]->trno . " and line=" . $data[$key2]->line);


            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.'];
  }

  public function getprdetails($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $center = $config['params']['center'];
    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = $this->getprsummaryqry($config);
      $qry .= " and stock.trno = ? and stock.line=? ";
      $data = $this->coreFunctions->opentable($qry, [$center, $config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line']]);
      if (!empty($data)) {
        foreach ($data as $key2 => $value) {
          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['qty'] = $data[$key2]->rrqty;
          $config['params']['data']['wh'] = $wh;
          $config['params']['data']['loc'] = '';
          $config['params']['data']['expiry'] = '';
          $config['params']['data']['rem'] = '';
          $config['params']['data']['refx'] = 0;
          $config['params']['data']['linex'] = 0;
          $config['params']['data']['prrefx'] = $data[$key2]->trno;
          $config['params']['data']['prlinex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->rrcost;
          $config['params']['data']['reqtrno'] = $data[$key2]->trno;
          $config['params']['data']['reqline'] = $data[$key2]->line;
          $config['params']['data']['ctrlno'] = $data[$key2]->ctrlno;
          $config['params']['data']['isasset'] = $data[$key2]->isasset;
          $return = $this->additem('insert', $config);
          if ($return['status']) {
            $rrref = $this->coreFunctions->datareader(
              "select ifnull(group_concat(ref SEPARATOR '\r'),'') as value 
                      from (select concat(num.docno, ' - Draft') as ref 
                            from lastock as s 
                            left join cntnum as num on num.trno=s.trno 
                            where num.doc = 'RR' and s.reqtrno=? and s.reqline=? 
                            group by num.docno
                            union all
                            select concat(num.docno, ' - Posted') as ref 
                            from glstock as s 
                            left join cntnum as num on num.trno=s.trno 
                            where num.doc = 'RR' and s.reqtrno=? and s.reqline=? 
                            group by num.docno) as s",
              [$data[$key2]->trno, $data[$key2]->line, $data[$key2]->trno, $data[$key2]->line]
            );

            $this->coreFunctions->execqry("update hstockinfotrans set rrref='" . $rrref . "'  where trno=" . $data[$key2]->trno . " and line=" . $data[$key2]->line);

            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.'];
  }

  public function createdistribution($config)
  {
    $trno = $config['params']['trno'];
    $status = true;
    $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);
    $qry = 'select head.dateid,head.client,head.tax,head.contra,head.cur,head.forex,stock.ext,wh.client as wh,ifnull(item.asset,"") as asset,ifnull(item.revenue,"") as revenue,stock.rrcost,stock.cost,stock.disc,stock.rrqty,stock.qty,head.projectid,head.subproject,stock.stageid
          from ' . $this->head . ' as head left join ' . $this->stock . ' as stock on stock.trno=head.trno
          left join client as wh on wh.clientid=stock.whid
          left join item on item.itemid=stock.itemid where head.trno=?';

    $stock = $this->coreFunctions->opentable($qry, [$trno]);
    $tax = 0;
    $ewt = 0;
    if (!empty($stock)) {
      $invacct = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['IN1']);
      $vat = $stock[0]->tax;
      $tax1 = 0;
      $tax2 = 0;
      if ($vat != 0) {
        $tax1 = 1 + ($vat / 100);
        $tax2 = $vat / 100;
      }

      foreach ($stock as $key => $value) {
        $params = [];
        $disc = $stock[$key]->rrcost - ($this->othersClass->discount($stock[$key]->rrcost, $stock[$key]->disc));
        if ($vat != 0) {

          $tax = round(($stock[$key]->ext / $tax1), 4);
          $tax = round($stock[$key]->ext - $tax, 4);
        }

        $params = [
          'client' => $stock[$key]->client,
          'acno' => $stock[$key]->contra,
          'ext' => $stock[$key]->ext,
          'wh' => $stock[$key]->wh,
          'date' => $stock[$key]->dateid,
          'inventory' => $stock[$key]->asset !== '' ? $stock[$key]->asset : $invacct,
          'tax' =>  $tax,
          'discamt' => $disc * $stock[$key]->rrqty,
          'cur' => $stock[$key]->cur,
          'forex' => $stock[$key]->forex,
          'cost' => $stock[$key]->ext - $tax,
          'projectid' => $stock[$key]->projectid,
          'subproject' => $stock[$key]->subproject,
          'stageid' => $stock[$key]->stageid
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
        $status =  true;
      } else {
        $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION FAILED');
        $status = false;
      }

      //checking for less than 1 discrepancy
      $variance = $this->coreFunctions->datareader("select ifnull(sum(db-cr),0) as value from " . $this->detail . " where trno=?", [$trno], '', true);
      if (abs($variance) < 1) {

        $qry = "select client,forex,dateid,cur,branch,deptid,contra,projectid,wh from " . $this->head . " where trno = ?";
        $d = $this->coreFunctions->opentable($qry, [$trno]);
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['GLC']);

        $entry = ['acnoid' => $acnoid, 'client' => $d[0]->wh, 'db' => 0, 'cr' => $variance, 'postdate' => $d[0]->dateid, 'cur' => $d[0]->cur, 'forex' => $d[0]->forex, 'fcr' => 0, 'fdb' => 0, 'projectid' => $d[0]->projectid];

        if ($variance > 0) {
          $entry['cr'] = abs($variance);
          $entry['db'] = 0;
        } else {
          $entry['db'] = abs($variance);
          $entry['cr'] = 0;
        }

        $qry = "select line as value from " . $this->detail . " where trno=? order by line desc limit 1";
        $line = $this->coreFunctions->datareader($qry, [$trno]);
        if ($line == '') {
          $line = 0;
        }
        $entry['line'] = $line + 1;
        $entry['trno'] = $trno;
        $this->coreFunctions->sbcinsert($this->detail, $entry);
      }
    }

    return $status;
  } //end function

  public function   distribution($params, $config)
  {
    $entry = [];
    $forex = $params['forex'];
    if ($forex == 0) {
      $forex = 1;
    }
    $suppinvoice = $this->companysetup->getsupplierinvoice($config['params']);

    $cur = $params['cur'];
    $invamt = $params['cost'];
    $ewt = isset($params['ewt']) ? $params['ewt'] : 0;
    $ext = $params['ext'];

    //AP
    if (!$suppinvoice) {
      if (floatval($ext) != 0) {
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['acno']]);
        $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'db' => 0, 'cr' => ($ext * $forex), 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => 0, 'fcr' => floatval($forex) == 1 ? 0 : $ext, 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }

      //disc
      if (floatval($params['discamt']) != 0) {
        $inputid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['PD1']);
        $entry = ['acnoid' => $inputid, 'client' => $params['client'], 'cr' => ($params['discamt'] * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => 0, 'fcr' => floatval($forex) == 1 ? 0 : ($params['discamt']), 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }

      if (floatval($params['tax']) != 0) {
        // input tax
        $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['TX1']);
        $entry = ['acnoid' => $input, 'client' => $params['client'], 'cr' => 0, 'db' => ($params['tax'] * $forex), 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($params['tax']), 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }

      if (floatval($ewt) != 0) {
        // EWt
        $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['APWT1']);
        $entry = ['acnoid' => $input, 'client' => $params['client'], 'db' => 0, 'cr' => ($ewt * $forex), 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => 0, 'fcr' => floatval($forex) == 1 ? 0 : ($ewt), 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }
    }

    //INV
    if (floatval($invamt) != 0) {
      if (floatval($params['discamt']) != 0) {
        $invamt  = $invamt + ($params['discamt'] * $forex);
      }
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['inventory']]);
      $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'db' => ($invamt * $forex), 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($invamt), 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

      if ($suppinvoice) {
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['CG1']);
        $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'cr' => ($invamt * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($invamt), 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }
    }
  } //end function

  private function updateprojmngmt($config, $stage)
  {
    $trno = $config['params']['trno'];
    $data = $this->openstock($trno, $config);
    $proj = $this->coreFunctions->getfieldvalue($this->head, "projectid", "trno=?", [$trno]);
    $sub = $this->coreFunctions->getfieldvalue($this->head, "subproject", "trno=?", [$trno]);

    $qry1 = "select stock.ext from " . $this->head . " as head left join " . $this->stock . " as
    stock on stock.trno=head.trno where head.doc='RR' and head.projectid = " . $proj . " and head.subproject = " . $sub . " and stock.stageid=" . $stage;

    $qry1 = $qry1 . " union all select stock.ext from " . $this->hhead . " as head left join " . $this->hstock . " as stock on stock.trno=
      head.trno where head.doc='RR' and head.projectid = " . $proj . " and head.subproject = " . $sub . " and stock.stageid=" . $stage;

    $qry2 = "select ifnull(sum(ext),0) as value from (" . $qry1 . ") as t";

    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty === '') {
      $qty = 0;
    }

    $editdate = $this->othersClass->getCurrentTimeStamp();
    $editby = $config['params']['user'];

    return $this->coreFunctions->execqry("update stages set rr=" . $qty . ", editdate = '" . $editdate . "', editby = '" . $editby . "' where projectid = " . $proj . " and subproject=" . $sub . " and stage=" . $stage, 'update');
  }

  private function updateprojmngmtap($config, $data)
  {
    $trno = $config['params']['trno'];
    foreach ($data as $key => $value) {
      $data2 = $this->coreFunctions->datareader("select ifnull(sum(a.cr-a.db),0) as value from apledger as a left join gldetail as d on d.trno = a.trno and d.line = a.line  where d.projectid =" . $data[$key]->projectid . " and d.subproject=" . $data[$key]->subproject . " and d.stageid=" . $data[$key]->stageid);

      if (empty($data2)) {
        $data2 = 0;
      }
      $this->coreFunctions->execqry("update stages set ap=" . $data2 . " where projectid = " . $data[$key]->projectid . " and subproject=" . $data[$key]->subproject . " and stage=" . $data[$key]->stageid, 'update');
    }
    return true;
  }

  public function reportsetup($config)
  {



    $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
    $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);

    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false, 'reloadhead' => true];
  }

  public function reportdata($config)
  {
    $this->logger->sbcviewreportlog($config);
    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    // auto lock
    $date = date("Y-m-d H:i:s");
    $user = $config['params']['user'];
    $trno = $config['params']['dataid'];
    $this->coreFunctions->sbcupdate($this->head, ['lockdate' => $date, 'lockuser' => $user], ['trno' => $trno]);
    $this->coreFunctions->sbcupdate($this->stock, ['pickerstart' => null], ['trno' => $trno]);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  public function recomputecost($head, $config)
  {
    $data = $this->openstock($head['trno'], $config);
    $data2 = json_decode(json_encode($data), true);
    $exec = true;
    foreach ($data2 as $key => $value) {
      $damt = $this->othersClass->sanitizekeyfield('amt', $data2[$key][$this->damt]);
      $dqty = round($this->othersClass->sanitizekeyfield('qty', $data2[$key][$this->dqty]), $this->companysetup->getdecimal('qty', $config['params']));

      $computedata = $this->othersClass->computestock($damt * $head['forex'], $data[$key]->disc, $dqty, $data[$key]->uomfactor, $head['tax']);
      $exec = $this->coreFunctions->execqry("update lastock set cost = " . $computedata['amt'] . " where trno = " . $head['trno'] . " and line=" . $data[$key]->line, "update");
    }
    return $exec;
  }
} //end class
