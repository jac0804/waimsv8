<?php

namespace App\Http\Classes\modules\ati;

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

class po
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'PURCHASE ORDER';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false];
  public $tablenum = 'transnum';
  public $head = 'pohead';
  public $hhead = 'hpohead';
  public $stock = 'postock';
  public $hstock = 'hpostock';
  public $tablelogs = 'transnum_log';
  public $statlogs = 'transnum_stat';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';
  private $stockselect;
  public $dqty = 'rrqty';
  public $hqty = 'qty';
  public $damt = 'rrcost';
  public $hamt = 'cost';
  private $fields = [
    'trno', 'docno', 'dateid', 'due', 'client', 'clientname', 'yourref', 'ourref', 'rem', 'terms',
    'forex', 'cur', 'wh', 'address', 'projectid', 'subproject', 'branch', 'deptid', 'tax', 'vattype', 'empid', 'sotrno', 'billid', 'shipid', 'billcontactid', 'shipcontactid',
    'revision', 'rqtrno', 'deldate', 'deladdress'
  ];
  private $except = ['trno', 'dateid', 'due'];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  private $reporter;
  private $helpClass;

  public $showfilterlabel = [
    // ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
    // ['val' => 'pending', 'label' => 'Pending', 'color' => 'primary'],
    // ['val' => 'locked', 'label' => 'Locked', 'color' => 'primary'],
    // ['val' => 'forapproval', 'label' => 'for Approval', 'color' => 'primary'],
    // ['val' => 'forordering', 'label' => 'for Ordering', 'color' => 'primary'],
    // ['val' => 'posted', 'label' => 'Posted', 'color' => 'primary'],
    // ['val' => 'all', 'label' => 'All', 'color' => 'primary']
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
      'view' => 63,
      'edit' => 64,
      'new' => 65,
      'save' => 66,
      // 'change' => 67, remove change doc
      'delete' => 68,
      'print' => 69,
      'lock' => 70,
      'unlock' => 71,
      'changeamt' => 72,
      'post' => 73,
      'unpost' => 74,
      'additem' => 808,
      'edititem' => 809,
      'deleteitem' => 810,
      'viewamt' => 843,
      'prbutton' => 2548,
      'approved' => 4009,
      'voiditem' => 3592,
      'ordered' => 4164,
      'generatecode' => 4368
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
    $category = 7;
    $postdate = 8;
    $listpostedby = 9;
    $listcreateby = 10;
    $listeditby = 11;
    $listviewby = 12;

    $getcols = ['action', 'lblstatus', 'listdocument', 'listdate', 'listclientname', 'yourref', 'ourref', 'category', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
    $stockbuttons = ['view']; //, 'diagram'
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$listclientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $cols[$category]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
    $cols[$yourref]['align'] = 'text-left';
    $cols[$ourref]['align'] = 'text-left';
    $cols[$category]['align'] = 'text-left';

    $cols[$postdate]['label'] = 'Post Date';
    $cols[$ourref]['label'] = 'PO Type';
    $cols[$yourref]['label'] = 'PO No.';

    return $cols;
  }

  public function paramsdatalisting($config)
  {

    $isshortcutcd = $this->companysetup->getisshortcutcd($config['params']);

    $fields = ['stat'];
    if ($isshortcutcd) {
      $allownew = $this->othersClass->checkAccess($config['params']['user'], 65);
      if ($allownew == '1') {
      }
    }

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'pickpo.label', 'PICK CANVASS');
    data_set($col1, 'pickpo.lookupclass', 'pendingcdsummaryshortcut');
    data_set($col1, 'pickpo.action', 'pendingcdsummary');
    data_set($col1, 'pickpo.confirmlabel', 'Proceed to pick Approved Canvass?');

    data_set($col1, 'stat.label', 'Status');
    data_set($col1, 'stat.type', 'lookup');
    data_set($col1, 'stat.action', 'lookuppotransstatus');
    data_set($col1, 'stat.lookupclass', 'lookuppotransstatus');

    $status = 'draft';
    $statusname = 'DRAFT';
    $adminid = $config['params']['adminid'];
    if ($adminid != 0) {
      $isapprover = $this->coreFunctions->getfieldvalue("employee", "isapprover", "empid=?", [$adminid], '', true);
      if ($isapprover == 1) {
        $status = 'forapproval';
        $statusname = 'For Approval';
      }
    }

    $fields = ['ctrlno'];
    $col2 = $this->fieldClass->create($fields);

    data_set($col2, 'ctrlno.label', 'Search by');
    data_set($col2, 'ctrlno.type', 'lookup');
    data_set($col2, 'ctrlno.readonly', true);
    data_set($col2, 'ctrlno.action', 'lookupctrlno');
    data_set($col2, 'ctrlno.lookupclass', 'lookupctrlno');

    $data = $this->coreFunctions->opentable("SELECT '" . $statusname . "' as stat, '" . $status . "' as typecode, '' as ctrlno");

    return ['status' => true, 'data' => $data[0], 'txtfield' => ['col1' => $col1, 'col2' => $col2]];
  }

  public function loaddoclisting($config)
  {
    ini_set('max_execution_time', 0);
    ini_set('memory_limit', '-1');

    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));


    $itemfilter = isset($config['params']['doclistingparam']['typecode']) ? $config['params']['doclistingparam']['typecode'] : 'draft';
    $filterid = "";
    $adminid = $config['params']['adminid'];
    if ($adminid != 0) {
      $isapprover = $this->coreFunctions->getfieldvalue("employee", "isapprover", "empid=?", [$adminid], '', true);
      if ($isapprover == 1) {
        $itemfilter = isset($config['params']['doclistingparam']['typecode']) ? $config['params']['doclistingparam']['typecode'] : 'forapproval';
      }
      $trnxtype = $this->coreFunctions->getfieldvalue("client", "deptcode", "clientid=?", [$config['params']['adminid']]);
      $filterid = " and info.trnxtype = '" . $trnxtype . "' ";
    }

    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $condition = '';
    $limit = "limit 150";

    $searchfield = [];
    $filtersearch = "";
    $search = $config['params']['search'];
    $allselect = "";
    $addselect = "";
    $haddselect = "";

    if (isset($config['params']['search'])) {
      $searchfield = ['docno', 'clientname', 'yourref', 'ourref', 'postedby', 'createby', 'editby', 'viewby'];
      $search = $config['params']['search'];
      $limit = "";
    }

    if (isset($config['params']['doclistingparam']['ctrlno'])) {
      array_push($searchfield, 'ctrlno');
      $allselect = ", ctrlno ";
      $addselect = ", ifnull((select group_concat(distinct infos.ctrlno)
              from hstockinfotrans as infos left join postock as stock on infos.trno=stock.reqtrno and infos.line=stock.reqline
              where stock.trno=head.trno ),'') as ctrlno";
      $haddselect = ", ifnull((select group_concat(distinct infos.ctrlno)
              from hstockinfotrans as infos left join hpostock as stock on infos.trno=stock.reqtrno and infos.line=stock.reqline
              where stock.trno=head.trno ),'') as ctrlno";
    }

    if ($search != "") {
      $filtersearch = $this->othersClass->multisearch($searchfield, $search);
    }


    $dateid = "left(head.dateid,10) as dateid";

    $status = "'DRAFT'";
    $leftjoin = "";
    $leftjoin_posted = "";

    $bgcolor = "''";
    switch ($itemfilter) {
      case 'draft':
        $status = "if(info.instructions<>'',info.instructions,ifnull(stat.status,'DRAFT'))";
        $condition .= ' and num.postdate is null and head.lockdate is null and num.statid=0';
        break;

      case 'pending':
        $leftjoin = ' left join postock as stock on stock.trno=head.trno';
        $leftjoin_posted = ' left join hpostock as stock on stock.trno=head.trno';
        $condition .= ' and stock.qty>stock.qa';
        $status = "'Pending'";
        break;

      case 'locked':
        $condition .= ' and head.lockdate is not null and num.postdate is null ';
        $status = "'Locked'";
        break;

      case 'forapproval':
        $condition .= " and num.postdate is null and head.lockdate is null and num.statid=10 and num.appuser='" . $config['params']['user'] . "'";
        $status = "if(info.instructions<>'',info.instructions,stat.status)";
        break;

      case 'approved':
        $condition .= " and num.postdate is null and head.lockdate is null and num.statid=36";
        $status = "'Approved'";
        $leftjoin = ' left join transnumtodo as todo on todo.trno=num.trno';
        $leftjoin_posted = ' left join transnumtodo as todo on todo.trno=num.trno';
        $condition .= ' and todo.donedate is not null and todo.clientid=' .  $adminid;
        break;

      case 'forordering':
        $condition .= ' and num.postdate is null and head.lockdate is null and num.statid=36';
        $status = "stat.status";
        break;

      case 'forposting':
        $condition .= ' and num.postdate is null and head.lockdate is null and num.statid=39';
        $status = "stat.status";
        break;

      case 'posted':
        $condition .= ' and num.postdate is not null ';
        break;

      default:
        $status = "if(info.instructions<>'',info.instructions,ifnull(stat.status,'DRAFT'))";
        $bgcolor = "if(info.instructions<>'','bg-yellow-2','')";
        break;
    }

    $allownew = $this->othersClass->checkAccess($config['params']['user'], 4480);
    if ($allownew == 0) {
      $defaultwh = $this->coreFunctions->getfieldvalue("client", "wh", "clientid=?", [$adminid]);
      if ($defaultwh != "") {
        $condition .= " and head.wh='" . $defaultwh . "'";
      }
    }


    $qry = " select trno, docno,clientname,dateid,stat,createby,editby,viewby,postedby,postdate,
                    yourref,category,ourref,bgcolor $allselect 
             from (select head.trno,head.docno,head.clientname,$dateid," . $status . " as stat,
                          head.createby,head.editby,head.viewby,num.postedby, date(num.postdate) as postdate,
                          head.yourref, req.category, head.ourref," . $bgcolor . " as bgcolor  $addselect
                   from " . $this->head . " as head 
                   left join " . $this->tablenum . " as num on num.trno=head.trno
                   left join headinfotrans as info on info.trno=head.trno
                   left join trxstatus as stat on stat.line=num.statid
                   left join reqcategory as req on req.line=info.categoryid
                   " . $leftjoin . "
                   where head.doc=? and num.center=? $filterid and CONVERT(head.dateid,DATE)>=? 
                         and CONVERT(head.dateid,DATE)<=? " . $condition . " 
                   group by head.trno, head.docno, head.clientname, head.dateid, stat.status, 
                            head.createby, head.editby, head.viewby, num.postedby, num.postdate, 
                            head.yourref, head.ourref, req.category, info.instructions
                   union all
                   select head.trno,head.docno,head.clientname,$dateid,stat.status as stat,head.createby,
                          head.editby,head.viewby, num.postedby, date(num.postdate) as postdate,head.yourref, 
                          req.category, head.ourref, " . $bgcolor . " as bgcolor  $haddselect
                   from " . $this->hhead . " as head 
                   left join " . $this->tablenum . " as num on num.trno=head.trno
                   left join hheadinfotrans as info on info.trno=head.trno
                   left join trxstatus as stat on stat.line=num.statid
                   left join reqcategory as req on req.line=info.categoryid
                   " . $leftjoin_posted . "
                   where head.doc=? and num.center=? $filterid and convert(head.dateid,DATE)>=? 
                         and CONVERT(head.dateid,DATE)<=? " . $condition . " 
             group by head.trno, head.docno, head.clientname, head.dateid, stat.status, head.createby, 
                      head.editby, head.viewby, num.postedby, num.postdate, head.yourref, 
                      head.ourref, req.category, info.instructions ) as a where 1=1 $filtersearch
             order by dateid desc,docno desc " . $limit;
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
    $instructiontab = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewinstructiontab']];

    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrytransnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);

    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];

    $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrytransnumtodo', 'label' => 'To Do', 'access' => 'view']];
    $objtodo = $this->tabClass->createtab($tab, []);
    $return['To Do'] = ['icon' => 'fa fa-list', 'tab' => $objtodo];

    return $return;
  }


  public function createTab($access, $config)
  {
    $viewrrcost = $this->othersClass->checkAccess($config['params']['user'], 843);
    $isproject = $this->companysetup->getisproject($config['params']);
    $po_btnvoid_access = $this->othersClass->checkAccess($config['params']['user'], 3592);

    $action = 0;
    $ctrlno = 1;
    $rrqty = 2;
    $uom = 3;
    $inputuom = 4;
    $sgdrate = 5;
    $rrcost = 6;
    $disc = 7;
    $ext = 8;
    $wh = 9;
    $qa = 10;
    $requestorname = 11;
    $department = 12;
    $purpose = 13;
    $barcode = 14;
    $itemdesc = 15;
    $addrem = 16;
    $specs = 17;
    $unit = 18;
    $isreturn = 19;
    $isadv = 20;
    $amt1 = 21;
    $amt2 = 22;
    $amt3 = 23;
    $amt4 = 24;
    $amt5 = 25;
    $ref = 26;
    $void = 27;
    $price = 28;
    $itemname = 29;
    $clientname = 30;
    $sanodesc = 31;
    $waivedd = 32;
    $waivedspecs = 33;


    $column = [
      'action', 'ctrlno', 'rrqty',
      'uom', 'inputuom', 'sgdrate', 'rrcost', 'disc', 'ext', 'wh', 'qa', 'requestorname', 'department', 'purpose', 'barcode', 'itemdesc', 'rem', 'specs', 'unit', 'isreturn', 'isadv', 'amt1', 'amt2', 'amt3',
      'amt4', 'amt5',  'ref', 'void', 'price', 'itemname', 'clientname', 'sanodesc', 'waivedqty', 'waivedspecs'
    ];

    $headgridbtns = ['itemqtyvoiding', 'viewref', 'viewitemstockinfo', 'viewdiagram', 'viewsobreakdown']; // itemqtyvoiding-----itemvoiding

    if ($po_btnvoid_access == 0) {
      unset($headgridbtns[0]);
    }

    $tab = [
      $this->gridname => [
        'gridcolumns' => $column,
        'computefield' => ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'disc' => 'disc', 'total' => 'ext'],
        'headgridbtns' => $headgridbtns
      ]

    ];

    if ($this->othersClass->checkAccess($config['params']['user'], 4310)) {
      $tab['stockinfotab'] = ['action' => 'tableentry', 'lookupclass' => 'tabupdatestockdetails', 'label' => 'UPDATE DETAILS', 'checkchanges' => 'tableentry'];
    }

    $tab['stathistorytab'] = ['action' => 'tableentry', 'lookupclass' => 'tabstathistory', 'label' => 'REVISION REMARKS', 'checkchanges' => 'tableentry'];

    $stockbuttons = ['save', 'delete', 'showbalance', 'showsobreakdown', 'showuomdetail'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    // $obj[0]['inventory']['headgridbtns']['itemvoiding']['access'] = 'voiditem';

    $obj[0]['inventory']['columns'][$rrqty]['checkfield'] = 'void';

    if ($viewrrcost == 0) {
      $obj[0]['inventory']['columns'][$rrcost]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$disc]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$ext]['type'] = 'coldel';
    }

    $obj[0]['inventory']['columns'][$ref]['lookupclass'] = 'refpo';
    if (!$access['changeamt']) {
      $obj[0]['inventory']['columns'][$rrcost]['readonly'] = true;
      $obj[0]['inventory']['columns'][$disc]['readonly'] = true;
    }

    $obj[0]['inventory']['columns'][$purpose]['type'] = 'label';
    $obj[0]['inventory']['columns'][$unit]['type'] = 'label';
    $obj[0]['inventory']['columns'][$department]['type'] = 'label';

    $obj[0]['inventory']['columns'][$clientname]['type'] = 'label';
    $obj[0]['inventory']['columns'][$sanodesc]['type'] = 'label';

    $obj[0]['inventory']['columns'][$barcode]['type'] = 'lookup';
    $obj[0]['inventory']['columns'][$barcode]['action'] = 'lookupbarcode';
    $obj[0]['inventory']['columns'][$barcode]['lookupclass'] = 'gridbarcode';

    $obj[0]['inventory']['columns'][$addrem]['label'] = 'Additional Remarks';

    $obj[0]['inventory']['columns'][$unit]['label'] = 'Temp UOM';
    $obj[0]['inventory']['columns'][$amt1]['label'] = 'Delivery Fee';
    $obj[0]['inventory']['columns'][$amt2]['label'] = 'Diagnostic Fee';
    $obj[0]['inventory']['columns'][$amt3]['label'] = 'Installation Fee';
    $obj[0]['inventory']['columns'][$amt4]['label'] = ' Consultation Fee';
    $obj[0]['inventory']['columns'][$amt5]['label'] = ' Misc Fee';

    $obj[0]['inventory']['columns'][$sgdrate]['label'] = 'Canvass Price';
    $obj[0]['inventory']['columns'][$sgdrate]['readonly'] = true;

    $obj[0]['inventory']['columns'][$price]['label'] = 'RR Price';
    $obj[0]['inventory']['columns'][$price]['readonly'] = true;

    $obj[0]['inventory']['columns'][$clientname]['label'] = 'Clientname';

    $obj[0]['inventory']['columns'][$inputuom]['name'] = 'inputuom';
    $obj[0]['inventory']['columns'][$inputuom]['label'] = 'Base UOM';
    $obj[0]['inventory']['columns'][$inputuom]['type'] = 'lookup';
    $obj[0]['inventory']['columns'][$inputuom]['action'] = 'lookupuom';
    $obj[0]['inventory']['columns'][$inputuom]['lookupclass'] = 'uomstockinputuom';

    $obj[0]['inventory']['columns'][$purpose]['style'] = 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px';
    $obj[0]['inventory']['columns'][$specs]['style'] = 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px';
    $obj[0]['inventory']['columns'][$clientname]['style'] = 'width: 300px;whiteSpace: normal;min-width:300px;max-width:300px';
    $obj[0]['inventory']['columns'][$sanodesc]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px';
    $obj[0]['inventory']['columns'][$addrem]['style'] = 'width: 300px;whiteSpace: normal;min-width:300px;max-width:300px';
    $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $isversion = $this->companysetup->getiscreateversion($config['params']);
    $pr_access = $this->othersClass->checkAccess($config['params']['user'], 2548);

    $tbuttons = ['pendingcd', 'pendingpr', 'additem', 'quickadd', 'saveitem', 'deleteallitem'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $adminid = $config['params']['adminid'];

    $fields = ['docno', 'client', 'clientname', 'address', 'dvattype'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.label', 'Vendor');
    data_set($col1, 'docno.label', 'Transaction#');

    if ($this->companysetup->getisproject($config['params'])) {
      $fields = [['dateid', 'terms'], 'due', 'dwhname', 'dprojectname'];
      $col2 = $this->fieldClass->create($fields);
      data_set($col2, 'dprojectname.required', true);
      data_set($col2, 'dprojectname.lookupclass', 'projectcode');
      data_set($col2, 'dprojectname.condition', ['checkstock']);
      data_set($col2, 'dprojectname.addedparams', []);
    } else {

      $fields = [['dateid', 'due'], 'terms', 'whname', 'paymentname', 'categoryname', 'ext'];
      $col2 = $this->fieldClass->create($fields);
      data_set($col2, 'whname.required', true);
      data_set($col2, 'whname.type', 'lookup');
      data_set($col2, 'whname.action', 'lookupclient');
      data_set($col2, 'whname.lookupclass', 'wh');
      data_set($col2, 'dateid.type', 'input');
      data_set($col2, 'dateid.class', 'sbccsreadonly');
      data_set($col2, 'categoryname.type', 'input');
      data_set($col2, 'categoryname.readonly', true);
      data_set($col2, 'ext.label', 'Void Amount');
    }

    if ($this->companysetup->getisproject($config['params'])) {
      $fields = [['yourref', 'ourref'], ['cur', 'forex'], 'subprojectname'];
      $col3 = $this->fieldClass->create($fields);
      data_set($col3, 'subprojectname.required', true);
    } else {
      $fields = [['yourref', 'potype'], ['cur', 'forex'], 'rem', 'generatecode'];
      $col3 = $this->fieldClass->create($fields);
      data_set($col3, 'yourref.label', 'PO No.');
      data_set($col3, 'potype.name', 'ourref');
      // data_set($col3, 'ourref.label', 'PO Type');
      // data_set($col3, 'ourref.class', 'sbccsreadonly');

      if ($adminid != 0) {
        $potype = $this->coreFunctions->getfieldvalue("client", "deptcode", "clientid=?", [$adminid]);
        if ($potype != '') {
          data_set($col3, 'potype.type', 'input');
          data_set($col3, 'potype.class', 'sbccsreadonly');
        }
      }

      data_set($col3, 'generatecode.style', 'width:100%');
      data_set($col3, 'generatecode.access', 'generatecode');
    }

    $fields = [['deadline', 'pdeadline'], ['sentdate', 'pickupdate'], 'rem2', 'forrevision', 'forapproval', 'doneapproved', 'ordered', 'updatepostedinfo'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'doneapproved.confirm', true);
    data_set($col4, 'doneapproved.confirmlabel', "Are you sure you want to approve?");
    data_set($col4, 'doneapproved.access', 'approved');
    data_set($col4, 'ordered.access', 'ordered');

    data_set($col4, 'rem2.label', 'Internal Notes');
    data_set($col4, 'deadline.label', 'PO Deadline');
    data_set($col4, 'updatepostedinfo.label', 'UPDATE INFO');

    return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4];
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
    $data[0]['address'] = '';
    $data[0]['ourref'] = '';
    $data[0]['rem'] = '';
    $data[0]['rem2'] = '';
    $data[0]['terms'] = '';
    $data[0]['forex'] = 1;
    $data[0]['cur'] = $this->companysetup->getdefaultcurrency($params);
    $data[0]['projectcode'] = '';
    $data[0]['projectname'] = '';
    $data[0]['projectid'] = 0;
    $data[0]['subprojectname'] = '';
    $data[0]['subproject'] = 0;
    $data[0]['dwhname'] = '';
    $data[0]['dbranchname'] = '';
    $data[0]['branch'] = 0;
    $data[0]['branchcode'] = '';
    $data[0]['ddeptname'] = '';
    $data[0]['deptid'] = '0';
    $data[0]['dept'] = '';
    $data[0]['tax'] = 0;
    $data[0]['vattype'] = 'NON-VATABLE';
    $data[0]['wh'] = $this->companysetup->getwh($params);
    $name = $this->coreFunctions->datareader("select clientname as value from client where client='" . $data[0]['wh'] . "'");
    $data[0]['whname'] = $name;

    $data[0]['empid'] = '0';
    $data[0]['empname'] = '';
    $data[0]['empcode'] = '';
    $data[0]['tel2'] = '';
    $data[0]['dvattype'] = '';
    $data[0]['sotrno'] = '0';
    $data[0]['sodocno'] = '';
    $data[0]['billid'] = '0';
    $data[0]['shipid'] = '0';
    $data[0]['shipcontactid'] = '0';
    $data[0]['billcontactid'] = '0';
    $data[0]['revision'] = '';
    $data[0]['rqtrno'] = '0';
    $data[0]['deldate'] = $this->othersClass->getCurrentDate();
    $data[0]['deladdress'] = '';
    $data[0]['paymentid'] = 0;
    $data[0]['paymentname'] = '';
    $data[0]['deadline'] = null;
    $data[0]['sentdate'] = null;
    $data[0]['pickupdate'] = null;
    $data[0]['pdeadline'] = null;
    $data[0]['prefix'] = '';
    $data[0]['trnxtype'] = '';
    if ($params['adminid'] != 0) {
      $data[0]['trnxtype'] = $this->coreFunctions->getfieldvalue("client", "deptcode", "clientid=?", [$params['adminid']]);
      $data[0]['ourref'] = $data[0]['trnxtype'];
    }

    return $data;
  }

  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
    $adminid = $config['params']['adminid'];
    if ($trno == 0) {
      $trno = $this->othersClass->readprofile('TRNO', $config);
      if ($trno == '') {
        $trno = $this->coreFunctions->datareader("select trno as value from " . $this->tablenum . " where doc=? and center=? order by trno desc limit 1", [$doc, $center]);
      }
      $config['params']['trno'] = $trno;
    } else {
      $this->othersClass->checkprofile('TRNO', $trno, $config);
    }
    $filterid = "";
    if ($adminid != 0) {
      $trnxtype = $this->coreFunctions->getfieldvalue("client", "deptcode", "clientid=?", [$config['params']['adminid']]);
      $filterid = " and headinfo.trnxtype = '" . $trnxtype . "' ";
    }
    $head = [];
    $islocked = $this->othersClass->islocked($config);
    $isposted = $this->othersClass->isposted($config);


    $table = $this->head;
    $htable = $this->hhead;
    $tablenum = $this->tablenum;

    $stocktable = $this->stock;
    $hstocktable = $this->hstock;

    $addedfield = ", head.yourref, head.rqtrno, left(head.deldate, 10) as deldate, head.deladdress";
    $addedjoin = "";

    $qryselect = "select
         num.center,
         head.trno,
         head.docno,
         client.client,
         head.terms,
         head.cur,
         head.forex,
         head.ourref,
         left(head.dateid,10) as dateid,
         client.clientname,
         head.address,
         head.shipto,
         date_format(head.createdate,'%Y-%m-%d') as createdate,
         head.rem,
         head.tax,
         head.billid,
         head.shipid,
         head.billcontactid,
         head.shipcontactid,
         head.vattype,
         '' as dvattype,
         head.agent,
         agent.clientname as agentname,
         head.wh as wh,
         warehouse.clientname as whname,
         '' as dwhname,
         left(head.due,10) as due,
         client.groupid,head.projectid,ifnull(p.code,'') as projectcode,ifnull(p.name,'') as projectname,
         s.line as subproject,s.subproject as subprojectname,head.branch,ifnull(b.clientname,'') as branchname,
         ifnull(b.client,'') as branchcode,'' as dbranchname,ifnull(d.client,'') as dept,
         ifnull(d.clientname,'') as deptname,head.deptid,'' as ddeptname, head.empid, e.clientname as empname,
         e.client as empcode, e.tel2,head.sotrno,ifnull(so.docno,'') as sodocno,
         head.revision, headinfo.paymentid, om.paymenttype as paymentname, headinfo.deadline, headinfo.pdeadline, headinfo.sentdate, headinfo.pickupdate,
         client.prefix,num.statid,ifnull(cat.category,'') as categoryname, headinfo.rem2,headinfo.trnxtype

         
         " . $addedfield . "  ";

    $qry = $qryselect . ", FORMAT((select sum((qty-qa)*cost) as ext from postock 
            where postock.trno=head.trno and void = 1),2) as ext
        from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as agent on agent.client = head.agent
        left join client as b on b.clientid = head.branch
        left join client as d on d.clientid = head.deptid
        left join client as e on e.clientid = head.empid
        left join projectmasterfile as p on p.line = head.projectid
        left join subproject as s on s.line = head.subproject
        left join hsqhead as so on so.trno = head.sotrno
        left join headinfotrans as headinfo on headinfo.trno = head.trno
        left join othermaster as om on om.line = headinfo.paymentid
        left join reqcategory as cat on cat.line=headinfo.categoryid
        " . $addedjoin . "
        where head.trno = ? and num.center = ? $filterid
        union all " . $qryselect . ", 
        FORMAT(ifnull((case when stock.voidqty <> 0 then (select sum(s.voidqty*s.cost)
         from hpostock as s where s.trno=head.trno) else (select sum((qty-qa)*cost)
         from hpostock where hpostock.trno=head.trno and void = 1) end),0),2) as ext
        from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as agent on agent.client = head.agent
        left join client as b on b.clientid = head.branch
        left join client as d on d.clientid = head.deptid
        left join client as e on e.clientid = head.empid
        left join projectmasterfile as p on p.line = head.projectid
        left join subproject as s on s.line = head.subproject
        left join hsqhead as so on so.trno = head.sotrno
        left join hheadinfotrans as headinfo on headinfo.trno = head.trno
        left join othermaster as om on om.line = headinfo.paymentid
        left join reqcategory as cat on cat.line=headinfo.categoryid
        left join (select trno,sum(voidqty) as voidqty from $hstocktable where void = 1 group by trno) as stock on stock.trno=head.trno
        " . $addedjoin . "
        where head.trno = ? and num.center=? $filterid ";

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

      $hidetabbtn = ['btndeleteallitem' => false];
      $clickobj = [];
      $hideobj = [];
      $hideobj['forrevision'] = false;
      $hideobj['doneapproved'] = true;
      $hideobj['forapproval'] = false;
      $hideobj['ordered'] = true;
      $hideobj['updatepostedinfo'] = true;
      $hideobj['generatecode'] = false;


      if ($isposted) {
        $hideobj['forapproval'] = true;
        $hideobj['forrevision'] = true;
        $hideobj['updatepostedinfo'] = false;
        $hideobj['generatecode'] = true;
      } else {

        switch ($head[0]->statid) {
          case 10:
            $hideobj['forapproval'] = true;
            $hideobj['doneapproved'] = false;
            break;
          case 36:
            $hideobj['forapproval'] = true;
            $hideobj['doneapproved'] = true;
            $hideobj['ordered'] = false;
            break;
          case 39:
            $hideobj['forapproval'] = true;
            break;
          default:
            $hideobj['forrevision'] = true;
            break;
        }
      }
      return  [
        'head' => $head, 'griddata' => ['inventory' => $stock], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg,
        'clickobj' => $clickobj, 'hidetabbtn' => $hidetabbtn, 'hideobj' => $hideobj
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
    $dataothers = [];
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


    $data['due'] = $this->othersClass->computeterms($data['dateid'], $data['due'], $data['terms']);
    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];

    if ($isupdate) {
      $clienthead = $this->coreFunctions->datareader("select client as value from pohead where trno = ?", [$head['trno']]);

      if ($clienthead != $head['client']) {
        $stock = $this->coreFunctions->opentable("select line as value from postock where trno = ?", [$head['trno']]);
        if (!empty($stock)) {
          return ['status' => false, 'msg' => "Can`t update, already have stock/s."];
        }
      }
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
      $whid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$data['wh']]);
      $this->coreFunctions->sbcupdate($this->stock, ['whid' => $whid], ['trno' => $head['trno']]);
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $insert = $this->coreFunctions->sbcinsert($this->head, $data);

      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['client'] . ' - ' . $head['clientname']);
    }

    $dataothers['trno'] = $head['trno'];
    $dataothers['paymentid'] = $head['paymentid'];
    $dataothers['deadline'] = $head['deadline'];
    $dataothers['pdeadline'] = $head['pdeadline'];
    $dataothers['sentdate'] = $head['sentdate'];
    $dataothers['pickupdate'] = $head['pickupdate'];
    $dataothers['rem2'] = $head['rem2'];
    $dataothers['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $dataothers['editby'] = $config['params']['user'];
    $dataothers['trnxtype'] = $head['trnxtype'];
    $arrcols = array_keys($dataothers);
    foreach ($arrcols as $key) {
      $dataothers[$key] = $this->othersClass->sanitizekeyfield($key, $dataothers[$key]);
    }
    $infotransexist = $this->coreFunctions->getfieldvalue("headinfotrans", "trno", "trno=?", [$head['trno']]);
    if ($infotransexist == '') {
      $this->coreFunctions->sbcinsert("headinfotrans", $dataothers);
    } else {
      $this->coreFunctions->sbcupdate("headinfotrans", $dataothers, ['trno' => $head['trno']]);
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
    $this->coreFunctions->execqry('delete from stockinfotrans where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from headinfotrans where trno=?', 'delete', [$trno]);
    $this->othersClass->deleteattachments($config);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function


  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $qry = "select trno from " . $this->stock . " where trno=? and qty=0 limit 1";
    $isitemzeroqty = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($isitemzeroqty)) {
      return ['status' => false, 'msg' => 'Posting failed. Check carefully, some item/s have zero quantity.'];
    }

    $qry = "select trno from " . $this->stock . " where trno=? and isadv=1 limit 1";
    $isitemzeroqty = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($isitemzeroqty)) {
      $pdeadline = $this->coreFunctions->datareader('select pdeadline as value from headinfotrans where trno=?', [$trno]);
      if ($pdeadline == null) {
        return ['status' => false, 'msg' => 'Posting failed. Please input payment deadline.'];
      }
    }

    $noitemid = $this->checkitemid($config);
    if (!$noitemid['status']) {
      return $noitemid;
    }

    $yourref = $this->coreFunctions->datareader('select yourref as value from ' . $this->head . ' where trno=?', [$trno]);
    if ($yourref == '') {
      return ['status' => false, 'msg' => 'Posting failed. Please input valid PO No.'];
    }

    $client = $this->coreFunctions->datareader('select client as value from ' . $this->head . ' where trno=?', [$trno]);

    $pickupdaterequired = $this->coreFunctions->getfieldvalue("client", "ispickupdate", "client=?", [$client]);
    if ($pickupdaterequired == '') {
      $pickupdaterequired == 0;
    }

    if ($pickupdaterequired) {
      $pickupdate = $this->coreFunctions->datareader('select pickupdate as value from headinfotrans where trno=?', [$trno]);
      if ($pickupdate == null) {
        return ['status' => false, 'msg' => 'Posting failed. Pickup date is required.'];
      }
    }


    $wh = $this->coreFunctions->getfieldvalue("pohead", "wh", "trno=?", [$trno]);
    if ($wh == '') {
      return ['status' => false, 'msg' => 'Posting failed. Please input Warehouse.'];
    }

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    if ($this->othersClass->isposted($config)) {
      return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
    }

    $statid = $this->othersClass->getstatid($config);


    if ($statid != 39) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Unable to Post. Transaction is not yet tag as ORDERED'];
    }

    //for glhead
    $qry = "insert into " . $this->hhead . "(trno,doc,docno,client,clientname,address,shipto,dateid,
      terms,rem,forex,yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,agent,wh,due,cur,projectid,subproject,branch,deptid,sotrno,billid,shipid,vattype,tax,empid,billcontactid,shipcontactid,
      revision, rqtrno, deldate, deladdress)
      SELECT head.trno,head.doc, head.docno,head.client, head.clientname, head.address,head.shipto,
      head.dateid as dateid, head.terms, head.rem, head.forex,head.yourref, head.ourref,
      head.createdate,head.createby,head.editby,head.editdate, head.lockdate,head.lockuser,head.agent,head.wh,
      head.due,head.cur,head.projectid,head.subproject,head.branch,head.deptid,head.sotrno,head.billid,head.shipid,
      head.vattype,head.tax,head.empid,head.billcontactid,head.shipcontactid,
      head.revision, head.rqtrno, head.deldate, head.deladdress
      FROM " . $this->head . " as head left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno
      where head.trno=? limit 1";
    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($posthead) {
      // for glstock

      if (!$this->othersClass->postingheadinfotrans($config)) {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'An error occurred while posting head data.'];
      }

      if (!$this->othersClass->postingstockinfotrans($config)) {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from hheadinfotrans where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'An error occurred while posting stock/s.'];
      }

      $qry = "insert into " . $this->hstock . "(trno,line,itemid,uom,whid,loc,ref,disc,cost,qty,void,rrcost,rrqty,ext,
        encodeddate,qa,encodedby,editdate,editby,sku,refx,linex,cdrefx,cdlinex,rem,stageid, projectid,sorefx,solinex,osrefx,oslinex,sgdrate,poref,reqtrno,reqline,isadv,isreturn)
        SELECT trno, line, itemid, uom,whid,loc,ref,disc,cost, qty,void,rrcost, rrqty, ext,
        encodeddate,qa, encodedby,editdate,editby,sku,refx,linex,cdrefx,cdlinex,rem,stageid, projectid ,sorefx,solinex,osrefx,oslinex,sgdrate,poref,reqtrno,reqline,isadv,isreturn
        FROM " . $this->stock . " where trno =?";
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        //update transnum
        $date = $this->othersClass->getCurrentTimeStamp();
        $data = ['postdate' => $date, 'postedby' => $config['params']['user'], 'statid' => 5];
        $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
        $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from stockinfotrans where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from headinfotrans where trno=?", "delete", [$trno]);

        $this->coreFunctions->execqry("update " . $this->hstock . " as stock
          left join hprstock as prs on prs.trno=stock.reqtrno and prs.line=stock.reqline
          set prs.statrem='Purchase Order - Posted',prs.statdate='" . $this->othersClass->getCurrentTimeStamp() . "' where stock.trno=" . $trno, 'update');

        // $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
        $this->logger->sbcstatlog($trno, $config, 'HEAD', 'POSTED');
        $this->othersClass->sbctransferlog($trno, $config, $this->htablelogs);
        return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
      } else {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from hstockinfotrans where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from hheadinfotrans where trno=?", "delete", [$trno]);
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

    $qry1 = "select trno from " . $this->hstock . " where trno=? and cvtrno <> 0";

    $data2 = $this->coreFunctions->opentable($qry1, [$trno]);
    if (!empty($data2)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, already applied in Check Voucher...'];
    }

    $qry = "select trno from " . $this->hstock . " where trno=? and (qa>0 or (void<>0 and isreturn=0))";
    $data = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($data)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, either already served or have item voided...'];
    }


    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    $qry = "insert into " . $this->head . "(trno,doc,docno,client,clientname,address,shipto,dateid,terms,rem,forex,
    yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,wh,due,cur,projectid,subproject,branch,deptid,sotrno,billid,shipid,vattype,tax,empid,billcontactid,shipcontactid,revision, rqtrno, deldate, deladdress)
    select head.trno, head.doc, head.docno, client.client, head.clientname, head.address, head.shipto,head.dateid as dateid, head.terms, head.rem, head.forex, head.yourref, head.ourref, head.createdate,
    head.createby, head.editby, head.editdate, head.lockdate, head.lockuser,head.wh,head.due,head.cur,head.projectid,head.subproject,head.branch,head.deptid,head.sotrno,head.billid,head.shipid,head.vattype,head.tax,head.empid,head.billcontactid,head.shipcontactid,
    head.revision, head.rqtrno, head.deldate, head.deladdress
    from (" . $this->hhead . " as head left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno)left join client on client.client=head.client
    where head.trno=? limit 1";
    //head
    if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {

      if (!$this->othersClass->unpostingheadinfotrans($config)) {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'An error occurred while unposting head data.'];
      }

      if (!$this->othersClass->unpostingstockinfotrans($config)) {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry("delete from headinfotrans where trno=?", 'delete', [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Unposting failed. There are issues with inventory.'];
      }

      $qry = "insert into " . $this->stock . "(trno,line,itemid,uom,whid,loc,ref,disc,cost,qty,void,rrcost,rrqty,ext,rem,encodeddate,qa,encodedby,editdate,editby,sku,refx,linex,cdrefx,cdlinex,stageid, projectid,sorefx,solinex,osrefx,oslinex,sgdrate,poref,reqtrno,reqline,isadv,isreturn)
      select trno, line, itemid, uom,whid,loc,ref,disc,cost, qty,void, rrcost, rrqty,ext,rem, encodeddate, qa, encodedby, editdate, editby,sku,refx,linex,cdrefx,cdlinex,stageid, projectid,sorefx,solinex,osrefx,oslinex,sgdrate,poref,reqtrno,reqline,isadv,isreturn
      from " . $this->hstock . " where trno=?";
      //stock
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null,statid=39 where trno=?", 'update', [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from hstockinfotrans where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from hheadinfotrans where trno=?", 'delete', [$trno]);
        // $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
        $this->logger->sbcstatlog($trno, $config, 'HEAD', 'FOR POSTING');
        return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
      } else {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry("delete from headinfotrans where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry("delete from stockinfotrans where trno=?", 'delete', [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, stock problems...'];
      }
    }
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
    stock.cdrefx,
    stock.cdlinex,
    stock.sorefx,
    stock.solinex,
    item.barcode,
    item.itemname,
    stock.uom,
    stock.cost,
    stock.qty as qty,

    FORMAT(sm.amt1," . $this->companysetup->getdecimal('currency', $config['params']) . ") as amt1,
    FORMAT(sm.amt2," . $this->companysetup->getdecimal('currency', $config['params']) . ") as amt2,
    FORMAT(sm.amt3," . $this->companysetup->getdecimal('currency', $config['params']) . ") as amt3,
    FORMAT(sm.amt4," . $this->companysetup->getdecimal('currency', $config['params']) . ") as amt4,
    FORMAT(sm.amt5," . $this->companysetup->getdecimal('currency', $config['params']) . ") as amt5,
    FORMAT(stock.rrcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrcost,
    FORMAT(stock.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as rrqty,
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,
    FORMAT(stock.sgdrate," . $this->companysetup->getdecimal('price', $config['params']) . ") as sgdrate,
    FORMAT(stock.rramt," . $this->companysetup->getdecimal('price', $config['params']) . ") as price,
    left(stock.encodeddate,10) as encodeddate,
    stock.disc,
    case when stock.void=0 then 'false' else 'true' end as void,
    round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
    stock.ref,
    stock.whid,
    warehouse.client as wh,
    warehouse.clientname as whname,
    stock.loc,
    item.brand,
    stock.rem, stock.stageid,st.stage,
    ifnull(uom.factor,1) as uomfactor,
    sm.unit as inputuom,
    sm.uom2,
    '' as bgcolor,
    case when stock.void=0 then if(stock.rrcost<>stock.sgdrate,'bg-yellow-2','') else 'bg-red-2' end as errcolor,
    prj.name as stock_projectname,
    stock.projectid as projectid,
    item.subcode, item.partno, round(item.dqty, " . $this->companysetup->getdecimal('qty', $config['params']) . ") as boxcount,stock.osrefx,stock.oslinex,stock.poref,
    ifnull(info.itemdesc,'') as itemdesc, ifnull(info.unit,'') as unit, ifnull(info.specs,'') as specs, ifnull(info.purpose,'') as purpose,ifnull(info.requestorname,'') as requestorname,stock.reqtrno,stock.reqline,
    case when stock.isadv=0 then 'false' else 'true' end as isadv,
    case when stock.isreturn=0 then 'false' else 'true' end as isreturn,
    if(stock.ref='','bg-blue-1','') as qacolor,pr.clientname,ifnull(sa.sano,'') as sanodesc,info.ctrlno,
    case when sm.waivedqty=0 then 'false' else 'true' end as waivedqty,
    case when sm.waivedspecs=0 then 'false' else 'true' end as waivedspecs,
    cast(SUBSTRING_INDEX(info.ctrlno, '-', 1) as unsigned ) as ctrlnodoc,
    cast(SUBSTRING_INDEX(info.ctrlno, '-', -1) as unsigned ) as ctrlnoline,
    prdept.clientname as department";
    return $sqlselect;
  }

  public function openstock($trno, $config)
  {
    $sqlselect = $this->getstockselect($config);

    $qry = $sqlselect . "
    FROM $this->stock as stock
    left join item on item.itemid=stock.itemid
    left join stockinfotrans as sm on sm.trno=stock.trno and sm.line=stock.line
    left join model_masterfile as mm on mm.model_id = item.model
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid
    left join stagesmasterfile as st on st.line = stock.stageid
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
    left join hprhead as pr on pr.trno=stock.reqtrno
    left join client as prdept on prdept.clientid=pr.deptid
    left join clientsano as sa on sa.line=pr.sano
    
    where stock.trno =?
    UNION ALL
    " . $sqlselect . "
    FROM $this->hstock as stock
    left join item on item.itemid=stock.itemid
    left join hstockinfotrans as sm on sm.trno=stock.trno and sm.line=stock.line
    left join model_masterfile as mm on mm.model_id = item.model
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
    left join client as warehouse on warehouse.clientid=stock.whid
    left join stagesmasterfile as st on st.line = stock.stageid
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
    left join hprhead as pr on pr.trno=stock.reqtrno
    left join client as prdept on prdept.clientid=pr.deptid
    left join clientsano as sa on sa.line=pr.sano
    where stock.trno =? order by ctrlnodoc,ctrlnoline";
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
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid
    left join stagesmasterfile as st on st.line = stock.stageid
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join stockinfotrans as sm on sm.trno=stock.trno and sm.line=stock.line
    left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
    left join hprhead as pr on pr.trno=stock.reqtrno
    left join client as prdept on prdept.clientid=pr.deptid
    left join clientsano as sa on sa.line=pr.sano
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
      case 'getsosummary':
        return $this->getsosummary($config);
        break;
      case 'getsodetails':
        return $this->getsodetails($config);
        break;
      case 'getsqsummary':
        return $this->getsqposummary($config);
        break;
      case 'getsqdetails':
        return $this->getsqdetails($config);
        break;
      case 'getcriticalstocks':
        return $this->getcriticalstocks($config);
        break;
      case 'getossummary':
        return $this->getossummary($config);
        break;
      case 'getosdetails':
        return $this->getosdetails($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }


  public function stockstatusposted($config)
  {
    switch ($config['params']['action']) {
      case 'updateitemvoid':
        return $this->updateitemvoid($config);
        break;
      case 'diagram':
        return $this->diagram($config);
        break;
      case 'exportcsv':
        return $this->exportcsv($config, 'F');

        break;
      case 'exportcsvd':
        return $this->exportcsv($config, 'D');

        break;
      case 'print1':
        return $this->reportsetup($config);

        break;
      case 'forapproval':
        return $this->forapproval($config);
        break;
      case 'doneapproved':
        return $this->doneapproved($config);
        break;
      case 'ordered':
        return $this->ordered($config);
        break;
      case 'generatecode':
        return $this->generatecode($config);
        break;
      default:
        return ['status' => false, 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function exportcsv($config, $type)
  {
    $trno = $config['params']['trno'];
    $str = "";
    $separator = "@@@";
    $nextline = "###";
    $filename = '';

    switch ($type) {
      case 'F':
        $qry = "select head.trno as exportid, head.yourref as ponum,right(head.docno,5) as erp,ifnull(date_format(qtn.dateid,'%m%/%d%/%Y'),'') as qtndate,
        ifnull(qtn.clientname,'') as customername, ifnull(concat(conbill.fname,' ', conbill.mname, ' ',conbill.lname),'') as contactperson,
        ifnull(qtn.terms, '') as terms, 'Exworks' as fob,
        0 as zero1, 0 as zero2,ifnull(date_format(so.dateid,'%m%/%d%/%Y'),'') as custpodate, ifnull(date_format(qtn.deldate,'%m%/%d%/%Y'),'') as deliverydate,
        date_format(head.dateid,'%m%/%d%/%Y') as podate,
        'URGENT' as priority,ifnull(concat(conbill.fname, ' ', conbill.mname, ' ',conbill.lname),'') as billname,
        ifnull(bill.addrline1,'') as billaddr1,ifnull(bill.addrline2,'') as billaddr2,ifnull(bill.city,'') as billcity,
        ifnull(bill.country,'') as billcountry,ifnull(bill.contactno,'') as billcontactno,ifnull(bill.fax,'') as billfax,
        ifnull(conbill.email,'') as billemail,
        ifnull(concat(conship.fname, ' ', conship.mname, ' ',conship.lname),'') as shipname,
        ifnull(ship.addrline1,'') as shipaddr1,ifnull(ship.addrline2,'') as shipaddr2,ifnull(ship.city,'') as shipcity,
        ifnull(ship.country,'') as shipcountry,ifnull(ship.contactno,'') as shipcontactno,ifnull(ship.fax,'') as shipfax,
        ifnull(conship.email,'') as shipemail,head.rem,date_format(curdate(),'%m%/%d%/%Y') as dategenerated
        from pohead as head
        left join hsqhead as so on so.trno=head.sotrno
        left join hqshead as qtn on qtn.sotrno=so.trno
        left join client on client.client=qtn.client
        left join contactperson as conbill on conbill.line=qtn.billcontactid
        left join contactperson as conship on conship.line=qtn.shipcontactid
        left join billingaddr as bill on bill.line = qtn.billid and bill.clientid = client.clientid
        left join billingaddr as ship on ship.line = qtn.shipid and ship.clientid = client.clientid
        where head.doc='po' and head.trno=" . $trno . "
        union all
        select head.trno as exportid, head.yourref as ponum,right(head.docno,5) as erp,ifnull(date_format(qtn.dateid,'%m%/%d%/%Y'),'') as qtndate,
        ifnull(qtn.clientname,'') as customername, ifnull(concat(conbill.fname, ' ', conbill.mname, ' ',conbill.lname),'') as contactperson,
        ifnull(qtn.terms, '') as terms, '???' as fob,
        0 as zero1, 0 as zero2,ifnull(date_format(so.dateid,'%m%/%d%/%Y'),'') as custpodate, ifnull(date_format(qtn.deldate,'%m%/%d%/%Y'),'') as deliverydate,
        date_format(head.dateid,'%m%/%d%/%Y') as podate,
        'URGENT' as priority,ifnull(concat(conbill.fname, ' ', conbill.mname, ' ',conbill.lname),'') as billname,
        ifnull(bill.addrline1,'') as billaddr1,ifnull(bill.addrline2,'') as billaddr2,ifnull(bill.city,'') as billcity,
        ifnull(bill.country,'') as billcountry,ifnull(bill.contactno,'') as billcontactno,ifnull(bill.fax,'') as billfax,
        ifnull(conbill.email,'') as billemail,
        ifnull(concat(conship.fname, ' ', conship.mname, ' ',conship.lname),'') as shipname,
        ifnull(ship.addrline1,'') as shipaddr1,ifnull(ship.addrline2,'') as shipaddr2,ifnull(ship.city,'') as shipcity,
        ifnull(ship.country,'') as shipcountry,ifnull(ship.contactno,'') as shipcontactno,ifnull(ship.fax,'') as shipfax,
        ifnull(conship.email,'') as shipemail,head.rem,date_format(curdate(),'%m%/%d%/%Y') as dategenerated
        from hpohead as head
        left join hsqhead as so on so.trno=head.sotrno
        left join hqshead as qtn on qtn.sotrno=so.trno
        left join client on client.client=qtn.client
        left join contactperson as conbill on conbill.line=qtn.billcontactid
        left join contactperson as conship on conship.line=qtn.shipcontactid
        left join billingaddr as bill on bill.line = qtn.billid and bill.clientid = client.clientid
        left join billingaddr as ship on ship.line = qtn.shipid and ship.clientid = client.clientid
        where head.doc='po' and head.trno=" . $trno;

        $data = $this->coreFunctions->opentable($qry);
        if (!empty($data)) {


          if ($data[0]->rem == "") {
            $rem = ' ';
          } else {
            $rem = $data[0]->rem;
          }

          $str = $data[0]->erp . $separator . $data[0]->ponum . $separator . $data[0]->erp . $separator . $data[0]->podate . $separator . $data[0]->customername . $separator . $data[0]->contactperson;
          $str = $str . $separator . $data[0]->terms . $separator . $data[0]->fob . $separator . $data[0]->fob . $separator . '0' . $separator . $data[0]->podate . $separator . $data[0]->podate . $separator . $data[0]->deliverydate . $separator . $data[0]->dategenerated . $separator . $data[0]->priority;
          $str = $str . $separator . $data[0]->customername . $separator . $data[0]->billaddr1 . ' ' . $data[0]->billaddr2 . ' City: ' . $data[0]->billcity . ' Country: ' . $data[0]->billcountry . ' Phone: ' . $data[0]->billcontactno . "\t" . 'Fax: ' . $data[0]->billfax . ' Email: ' . $data[0]->billemail;
          $str = $str . $separator . $data[0]->shipaddr1 . ' ' . $data[0]->shipaddr2 . ' City: ' . $data[0]->shipcity . ' Country: ' . $data[0]->shipcountry . ' Phone: ' . $data[0]->shipcontactno . "\t" . 'Fax: ' . $data[0]->shipfax . ' Email: ' . $data[0]->shipemail;
          $str = $str . $separator . $data[0]->billname . $separator . $data[0]->shipaddr1 . ' ' . $data[0]->shipaddr2 . ' City: ' . $data[0]->shipcity . ' Country: ' . $data[0]->shipcountry . ' Phone: ' . $data[0]->shipcontactno . "\t" . 'Fax: ' . $data[0]->shipfax . ' Email: ' . $data[0]->shipemail . $separator . $data[0]->shipname . $separator . $rem;
          $str = $str . $separator . "0" . $separator . $data[0]->erp . "####";
          $filename = 'POCPHP-F-' . $data[0]->erp . date("dmyHi");
        }
        break;

      case 'D':
        $qry = "select right(head.docno,5) as erp, stock.line, head.yourref as ponum, stock.rrqty as pocustqty, stock.uom,
        head.cur as currency, round(stock.rrcost,4) as poprice, stock.rrqty as pobalance,
        date_format(head.dateid,'%m%/%d%/%Y') as podate,date_format(qtn.deldate,'%m%/%d%/%Y') as deliverydate,date_format(curdate(),'%m%/%d%/%Y') as dategenerated,brand.brand_desc,
        item.itemname, model.model_name,iteminfo.itemdescription,iteminfo.accessories,stockinfo.rem as itemremarks,
        brand.brand_desc as crossmfr, model.model_name as crossmfritemno,
        0 as weight, 0 as ssm1, 0 as price1, 0 as ssm2, 0 as price2, 0 as ssm3, 0 as price3, 0 as ssm4, 0 as price4,
        0 as ssm5, 0 as price5, 0 as ssm6, 0 as price6,p.name as project
        from pohead as head
        left join postock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join item on item.itemid = stock.itemid
        left join stockgrp_masterfile as grp on grp.stockgrp_id = item.groupid
        left join part_masterfile as part on part.part_id = item.part
        left join frontend_ebrands as brand on brand.brandid = item.brand
        left join model_masterfile as model on model.model_id = item.model
        left join projectmasterfile as p on p.line = item.projectid
        left join iteminfo on iteminfo.itemid = item.itemid
        left join stockinfotrans as stockinfo on stockinfo.trno = head.trno and stockinfo.line = stock.line
        left join hsqhead as so on so.trno=head.sotrno
        left join hqshead as qtn on qtn.sotrno=so.trno
        where head.doc='po' and head.trno=" . $trno . "
        union all
        select right(head.docno,5) as erp, stock.line, head.yourref as ponum, stock.rrqty as pocustqty, stock.uom,
        head.cur as currency, round(stock.rrcost,4) as poprice, stock.rrqty as pobalance,
        date_format(head.dateid,'%m%/%d%/%Y') as podate,date_format(qtn.deldate,'%m%/%d%/%Y') as deliverydate,date_format(curdate(),'%m%/%d%/%Y') as dategenerated,brand.brand_desc,
        item.itemname, model.model_name,iteminfo.itemdescription,iteminfo.accessories,stockinfo.rem as itemremarks,
        brand.brand_desc as crossmfr, model.model_name as crossmfritemno,
        0 as weight, 0 as ssm1, 0 as price1, 0 as ssm2, 0 as price2, 0 as ssm3, 0 as price3, 0 as ssm4, 0 as price4,
        0 as ssm5, 0 as price5, 0 as ssm6, 0 as price6,p.name as project
        from hpohead as head
        left join hpostock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join item on item.itemid = stock.itemid
        left join stockgrp_masterfile as grp on grp.stockgrp_id = item.groupid
        left join part_masterfile as part on part.part_id = item.part
        left join frontend_ebrands as brand on brand.brandid = item.brand
        left join model_masterfile as model on model.model_id = item.model
        left join projectmasterfile as p on p.line = item.projectid
        left join iteminfo on iteminfo.itemid = item.itemid
        left join stockinfotrans as stockinfo on stockinfo.trno = head.trno and stockinfo.line = stock.line
        left join hsqhead as so on so.trno=head.sotrno
        left join hqshead as qtn on qtn.sotrno=so.trno
        where head.doc='po' and head.trno=" . $trno;

        $data = $this->coreFunctions->opentable($qry);
        if (!empty($data)) {
          foreach ($data as $key => $val) {

            if ($data[0]->accessories == "") {
              $accessories = ' ';
            } else {
              $accessories = $data[0]->accessories;
            }

            if ($data[0]->itemdescription == "") {
              $itemdescription = ' ';
            } else {
              $itemdescription = $data[0]->itemdescription;
            }

            $str = $str . $data[$key]->erp . $separator . $data[$key]->line . $separator . $data[$key]->ponum . $separator . round($data[$key]->pocustqty, 0) . $separator . $data[$key]->uom . $separator . $data[$key]->currency;
            $str = $str . $separator . $data[$key]->poprice . $separator . round($data[$key]->pobalance, 0) . $separator . $data[$key]->podate . $separator . $data[$key]->deliverydate . $separator . $data[$key]->dategenerated . $separator . $data[$key]->project . $separator . $data[$key]->itemname . $separator . $data[$key]->model_name;
            $str = $str . $separator . $itemdescription . $separator . $accessories . $separator;
            $str = $str . $separator . $separator . $separator . '0' . $separator . $data[$key]->currency . $separator . round($data[$key]->pocustqty, 0) . $separator . $data[$key]->poprice . $separator . '0' . $separator . '0' . $separator . '0' . $separator . '0' . $separator . '0' . $separator . '0' . $separator . '0' . $separator . '0' . $separator . '0' . $separator . '0' . $separator;
            $str = $str . "####";
          }

          $filename = 'POCPHP-D-' . $data[0]->erp . date("dmyHi");
        }

        break;
    }


    return ['status' => true, 'msg' => 'Successfully exported.', 'filename' => $filename, 'ext' => 'txt', 'csv' => $str];
  }

  public function diagram($config)
  {
    $data = [];
    $nodes = [];
    $links = [];
    $data['width'] = 1500;
    $startx = 100;

    $qry = "select po.trno,po.docno,left(po.dateid,10) as dateid,
       CAST(concat('Total PO Amt: ',round(sum(s.ext),2)) as CHAR) as rem,s.refx
       from hpohead as po
       left join hpostock as s on s.trno = po.trno
       where po.trno = ?
       group by po.trno,po.docno,po.dateid,s.refx
       union all
       select po.trno,po.docno,left(po.dateid,10) as dateid,
       CAST(concat('Total PO Amt: ',round(sum(s.ext),2)) as CHAR) as rem,s.refx
       from pohead as po
       left join postock as s on s.trno = po.trno
       where po.trno = ?
       group by po.trno,po.docno,po.dateid,s.refx";
    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno'], $config['params']['trno']]);
    if (!empty($t)) {
      $startx = 550;
      $a = 0;
      foreach ($t as $key => $value) {
        //PO
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
        array_push($links, ['from' => $t[$key]->docno, 'to' => 'rr']);
        $a = $a + 100;

        if (floatval($t[$key]->refx) != 0) {
          //pr
          $qry = "select po.docno,left(po.dateid,10) as dateid,
            CAST(concat('Total PR Amt: ',round(sum(s.ext),2)) as CHAR) as rem
            from hprhead as po left join hprstock as s on s.trno = po.trno
            where po.trno = ?
            group by po.docno,po.dateid";
          $x = $this->coreFunctions->opentable($qry, [$t[$key]->refx]);
          $poref = $t[$key]->docno;
          if (!empty($x)) {
            foreach ($x as $key2 => $value) {
              data_set(
                $nodes,
                $x[$key2]->docno,
                [
                  'align' => 'left',
                  'x' => 10,
                  'y' => 50 + $a,
                  'w' => 250,
                  'h' => 80,
                  'type' => $x[$key2]->docno,
                  'label' => $x[$key2]->rem,
                  'color' => 'yellow',
                  'details' => [$x[$key2]->dateid]
                ]
              );
              array_push($links, ['from' => $x[$key2]->docno, 'to' => $poref]);
              $a = $a + 100;
            }
          }
        }
      }
    }

    //RR
    $qry = "
      select head.docno,
      date(head.dateid) as dateid,
      CAST(concat('Total RR Amt: ', round(sum(stock.ext),2), ' - ', 'Balance: ', round(ap.bal, 2)) as CHAR) as rem,
      head.trno
      from glhead as head
      left join glstock as stock on head.trno = stock.trno
      left join apledger as ap on ap.trno = head.trno
      where stock.refx=? and head.doc = 'RR'
      group by head.docno, head.dateid, head.trno, ap.bal
      union all
      select head.docno,
      date(head.dateid) as dateid,
      CAST(concat('Total RR Amt: ', round(sum(stock.ext),2), ' - ', 'Balance: ', round(sum(stock.ext),2)) as CHAR) as rem,
      head.trno
      from lahead as head
      left join lastock as stock on head.trno = stock.trno
      where stock.refx=? and head.doc = 'RR'
      group by head.docno, head.dateid, head.trno";
    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno'], $config['params']['trno']]);
    if (!empty($t)) {
      data_set(
        $nodes,
        'rr',
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
        //APV
        $rrtrno = $t[$key]->trno;
        $apvqry = "
        select  head.docno, date(head.dateid) as dateid, head.trno,
        CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
        from glhead as head
        left join gldetail as detail on head.trno = detail.trno
        where detail.refx = ? and head.doc = 'AP'
        union all
        select  head.docno, date(head.dateid) as dateid, head.trno,
        CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
        from lahead as head
        left join ladetail as detail on head.trno = detail.trno
        where detail.refx = ? and head.doc = 'AP'";
        $apvdata = $this->coreFunctions->opentable($apvqry, [$rrtrno, $rrtrno]);
        if (!empty($apvdata)) {
          foreach ($apvdata as $key2 => $value2) {
            data_set(
              $nodes,
              'apv',
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
            array_push($links, ['from' => 'rr', 'to' => 'apv']);
            $a = $a + 100;
          }
        }

        //CV
        if (!empty($apvdata)) {
          $apv_rr_links = "apv";
          $apvtrno = $apvdata[0]->trno;
        } else {
          $apvtrno = $rrtrno;
          $apv_rr_links = "rr";
        }
        $cvqry = "
        select head.docno, date(head.dateid) as dateid, head.trno,
        CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
        from glhead as head
        left join gldetail as detail on head.trno = detail.trno
        where detail.refx = ? and head.doc = 'CV'
        union all
        select head.docno, date(head.dateid) as dateid, head.trno,
        CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
        from lahead as head
        left join ladetail as detail on head.trno = detail.trno
        where detail.refx = ? and head.doc = 'CV'";
        $cvdata = $this->coreFunctions->opentable($cvqry, [$apvtrno, $apvtrno]);
        if (!empty($cvdata)) {
          foreach ($cvdata as $key2 => $value2) {
            data_set(
              $nodes,
              $cvdata[$key2]->docno,
              [
                'align' => 'left',
                'x' => $startx + 800,
                'y' => 100,
                'w' => 250,
                'h' => 80,
                'type' => $cvdata[$key2]->docno,
                'label' => $cvdata[$key2]->rem,
                'color' => 'red',
                'details' => [$cvdata[$key2]->dateid]
              ]
            );
            array_push($links, ['from' => $apv_rr_links, 'to' => $cvdata[$key2]->docno]);
            $a = $a + 100;
          }
        }

        //DM
        $dmqry = "
        select head.docno as docno,left(head.dateid,10) as dateid,
        CAST(concat('Total DM Amt: ', round(sum(stock.ext), 2)) as CHAR) as rem
        from glhead as head
        left join glstock as stock on stock.trno=head.trno
        left join item on item.itemid = stock.itemid
        where stock.refx=? and head.doc = 'DM'
        group by head.docno, head.dateid
        union all
        select head.docno as docno,left(head.dateid,10) as dateid,
        CAST(concat('Total DM Amt: ', round(sum(stock.ext), 2)) as CHAR) as rem
        from lahead as head
        left join lastock as stock on stock.trno=head.trno
        left join item on item.itemid=stock.itemid
        where stock.refx=? and head.doc = 'DM'
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
            array_push($links, ['from' => 'rr', 'to' => $dmdata[$key2]->docno]);
            $a = $a + 100;
          }
        }
      }
    }

    $data['nodes'] = $nodes;
    $data['links'] = $links;

    return ['status' => true, 'msg' => 'Successfully fetched.', 'data' => $data];
  }


  private function updateitemvoid($config)
  {
    $trno = $config['params']['trno'];
    $rows = $config['params']['rows'];
    foreach ($rows as $key) {
      $this->coreFunctions->execqry('update ' . $this->hstock . ' set void=1 where trno=? and line=?', 'update', [$key['trno'], $key['line']]);
    }
  } //end function

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
          $msg2 = ' Qty Received is Greater than PR Qty ';
        }
      }
    }

    if (!$isupdate) {
      $qry = "select line as value from postock where trno=? and void=0 limit 1";
      $vitem = $this->coreFunctions->datareader($qry, [$config['params']['trno']]);
      if (empty($vitem)) {

        if ($this->coreFunctions->sbcupdate($this->tablenum, ['statid' => 39], ['trno' => $config['params']['trno']])) {
          // $this->logger->sbcwritelog($config['params']['trno'], $config, 'STATUS', 'Tag FOR POSTING');
          $this->logger->sbcstatlog($config['params']['trno'], $config, 'STATUS', 'Tag FOR POSTING');
          return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully updated.', 'reloadhead' => true];
        }
      }

      return ['row' => $data, 'status' => true, 'msg' => $msg1 . '/' . $msg2];
    } else {
      return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
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
      $qry = "select line as value from postock where trno=? and void=0 limit 1";
      $vitem = $this->coreFunctions->datareader($qry, [$config['params']['trno']]);
      if (empty($vitem)) {

        if ($this->coreFunctions->sbcupdate($this->tablenum, ['statid' => 39], ['trno' => $config['params']['trno']])) {
          // $this->logger->sbcwritelog($config['params']['trno'], $config, 'STATUS', 'Tag FOR POSTING');
          $this->logger->sbcstatlog($config['params']['trno'], $config, 'STATUS', 'Tag FOR POSTING');
          return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully updated.', 'reloadhead' => true];
        }
      }

      return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    } else {
      return ['inventory' => $data, 'status' => true, 'msg' => 'Please check, some items have zero qty (' . $msg1 . ' / ' . $msg2 . ')'];
    }
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
    $item = $this->coreFunctions->opentable("select item.itemid,0 as amt,item.disc,'' as loc,'" . $wh . "' as wh, 1 as qty, uom,famt from item where barcode=?", [$barcode]);

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
      return ['status' => false, 'msg' => 'Barcode not found.' . $barcodelength, ''];
    }
  }

  // insert and update item
  public function additem($action, $config)
  {
    $isproject = $this->companysetup->getisproject($config['params']);
    $uom = $config['params']['data']['uom'];
    $barcode = '';
    $itemid = $config['params']['data']['itemid'];
    $trno = $config['params']['trno'];
    $disc = $config['params']['data']['disc'];
    $wh = $config['params']['data']['wh'];
    $loc = $config['params']['data']['loc'];
    $ref = '';
    $void = 'false';
    if (isset($config['params']['data']['void'])) {
      $void = $config['params']['data']['void'];
    }
    if (isset($config['params']['data']['ref'])) {
      $ref = $config['params']['data']['ref'];
    }
    $itemid = ($itemid == '') ? 0 : $itemid;

    $amt1 = isset($config['params']['data']['amt1']) ? $config['params']['data']['amt1'] : 0;
    $amt2 = isset($config['params']['data']['amt2']) ? $config['params']['data']['amt2'] : 0;
    $amt3 = isset($config['params']['data']['amt3']) ? $config['params']['data']['amt3'] : 0;
    $amt4 = isset($config['params']['data']['amt4']) ? $config['params']['data']['amt4'] : 0;
    $amt5 = isset($config['params']['data']['amt5']) ? $config['params']['data']['amt5'] : 0;

    $sgdrate = isset($config['params']['data']['sgdrate']) ? $config['params']['data']['sgdrate'] : 0;

    $refx = 0;
    $linex = 0;
    $cdrefx = 0;
    $cdlinex = 0;
    $sorefx = 0;
    $solinex = 0;
    $osrefx = 0;
    $oslinex = 0;
    $rem = '';
    $stageid = 0;
    $projectid = 0;
    $poref = '';
    $sgdrate = 0;
    $reqtrno = 0;
    $reqline = 0;



    $reqtrno =  isset($config['params']['data']['reqtrno']) ? $config['params']['data']['reqtrno'] : 0;
    $reqline =  isset($config['params']['data']['reqline']) ? $config['params']['data']['reqline'] : 0;
    $isadv = isset($config['params']['data']['isadv']) ? $config['params']['data']['isadv'] : 0;
    $isreturn = isset($config['params']['data']['isreturn']) ? $config['params']['data']['isreturn'] : 0;
    $waivedqty = isset($config['params']['data']['waivedqty']) ? $config['params']['data']['waivedqty'] : 0;
    $waivedspecs = isset($config['params']['data']['waivedspecs']) ? $config['params']['data']['waivedspecs'] : 0;
    $inputuom = isset($config['params']['data']['inputuom']) ? $config['params']['data']['inputuom'] : '';
    $uom2 = isset($config['params']['data']['uom2']) ? $config['params']['data']['uom2'] : '';
    $ctrlno = isset($config['params']['data']['ctrlno']) ? $config['params']['data']['ctrlno'] : '';
    if (isset($config['params']['data']['rem'])) {
      $rem = $config['params']['data']['rem'];
    }
    if (isset($config['params']['data']['refx'])) {
      $refx = $config['params']['data']['refx'];
    }
    if (isset($config['params']['data']['linex'])) {
      $linex = $config['params']['data']['linex'];
    }
    if (isset($config['params']['data']['cdrefx'])) {
      $cdrefx = $config['params']['data']['cdrefx'];
    }
    if (isset($config['params']['data']['cdlinex'])) {
      $cdlinex = $config['params']['data']['cdlinex'];
    }

    if (isset($config['params']['data']['stageid'])) {
      $stageid = $config['params']['data']['stageid'];
    }

    if (isset($config['params']['data']['solinex'])) {
      $solinex = $config['params']['data']['solinex'];
    }

    if (isset($config['params']['data']['sorefx'])) {
      $sorefx = $config['params']['data']['sorefx'];
    }

    if (isset($config['params']['data']['oslinex'])) {
      $oslinex = $config['params']['data']['oslinex'];
    }

    if (isset($config['params']['data']['osrefx'])) {
      $osrefx = $config['params']['data']['osrefx'];
    }
    if (isset($config['params']['data']['poref'])) {
      $poref = $config['params']['data']['poref'];
    }

    if (isset($config['params']['data']['sgdrate'])) {
      $sgdrate = $config['params']['data']['sgdrate'];
    } else {
      $sgdrate = $this->othersClass->getexchangerate('PHP', 'SGD');
    }





    $line = 0;
    if ($action == 'insert') {

      if ($cdrefx != 0) {
        $itemexist = $this->coreFunctions->getfieldvalue($this->stock, "line", "trno=? and cdrefx=? and cdlinex=?", [$trno, $cdrefx, $cdlinex], '', true);
        if ($itemexist != 0) {
          return ['status' => false, 'msg' => "Item already added  (" . $ref . ")"];
        }
      }
      if ($refx != 0) {
        $itemexist = $this->coreFunctions->getfieldvalue($this->stock, "line", "trno=? and refx=? and linex=?", [$trno, $refx, $linex], '', true);
        if ($itemexist != 0) {
          return ['status' => false, 'msg' => "Item already added  (" . $ref . ")"];
        }
      }

      if ($itemid  != 0) {
        $itemexist = $this->coreFunctions->getfieldvalue($this->stock, "line", "trno=? and itemid=? and refx=0 and cdrefx=0", [$trno, $itemid], '', true);
        if ($itemexist != 0) {
          return ['status' => false, 'msg' => 'Item already added.'];
        }
      }


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

      if ($waivedspecs == 'true') {
        $waivedspecs = 1;
      } else {
        $waivedspecs = 0;
      }
      $config['params']['line'] = $config['params']['data']['line'];
      $line = $config['params']['data']['line'];
      $amt = $config['params']['data'][$this->damt];
      $qty = $config['params']['data'][$this->dqty];
      $config['params']['line'] = $line;
    }
    $amt = $this->othersClass->sanitizekeyfield('amt', $amt);
    $qty = $this->othersClass->sanitizekeyfield('qty', $qty);

    $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
    $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
    $factor = 1;
    if (!empty($item)) {
      $barcode = $item[0]->barcode;
      $item[0]->factor = $this->othersClass->val($item[0]->factor);
      if ($item[0]->factor !== 0) $factor = $item[0]->factor;
    } else {
      if ($uom2 != '') {
        $factor = $this->coreFunctions->getfieldvalue("uomlist", "factor", "uom=? and isconvert=1", [$uom2], '', true);
        if ($factor == 0) {
          $factor = 1;
        }
      }
    }

    $forex = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
    $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);
    $qty = round($qty, $this->companysetup->getdecimal('qty', $config['params']));
    $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor);

    $ext = number_format($computedata['ext'], 2, '.', '');

    $data = [
      'trno' => $trno,
      'line' => $line,
      'itemid' => $itemid,
      'rrcost' => $amt,
      'cost' => $computedata['amt'] * $forex,
      'rrqty' => $qty,
      'qty' => $computedata['qty'],
      'ext' => $ext,
      'disc' => $disc,
      'whid' => $whid,
      'loc' => $loc,
      'uom' => $uom,
      'void' => $void,
      'refx' => $refx,
      'linex' => $linex,
      'cdrefx' => $cdrefx,
      'cdlinex' => $cdlinex,
      'sorefx' => $sorefx,
      'solinex' => $solinex,
      'osrefx' => $osrefx,
      'oslinex' => $oslinex,
      'rem' => $rem,
      'ref' => $ref,
      'stageid' => $stageid,
      'reqtrno' => $reqtrno,
      'reqline' => $reqline,
      'isadv' => $isadv,
      'isreturn' => $isreturn,
      'sgdrate' => $sgdrate
    ];

    $data2 = [
      'trno' => $trno,
      'line' => $line,
      'amt1' => $amt1,
      'amt2' => $amt2,
      'amt3' => $amt3,
      'amt4' => $amt4,
      'amt5' => $amt5,
      'waivedqty' => $waivedqty,
      'unit' => $inputuom,
      'uom2' => $uom2,
      'ctrlno' => $ctrlno,
      'waivedspecs' => $waivedspecs
    ];

    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }

    foreach ($data2 as $key2 => $value2) {
      $data2[$key2] = $this->othersClass->sanitizekeyfield($key2, $data2[$key2]);
    }

    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $data['editdate'] = $current_timestamp;
    $data['editby'] = $config['params']['user'];

    if ($action == 'insert') {
      $data['encodeddate'] = $current_timestamp;
      $data['encodedby'] = $config['params']['user'];

      if ($isproject) {
        if ($data['stageid'] == 0) {
          $msg = 'Stage cannot be blank -' . $item[0]->barcode;
          return ['status' => false, 'msg' => $msg];
        }
      }

      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' Barcode:' .  $barcode . ' Amt:' . $amt . ' Disc:' . $disc . ' WH:' . $wh . ' Ext:' . $computedata['ext'] . ' Uom:' . $uom);
        if ($isproject) {
          $this->updateprojmngmt($config, $stageid);
        }

        if ($this->coreFunctions->sbcinsert("stockinfotrans", $data2) == 1) {
          $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line);
        }

        $this->loadheaddata($config);
        $row = $this->openstockline($config);
        return ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.', 'line' => $line, 'reloaddata' => true];
      } else {
        return ['status' => false, 'msg' => 'Add item Failed'];
      }
    } elseif ($action == 'update') {
      $return = true;
      // unset($data['isadv']);
      if ($data['cdrefx'] != 0 && $data['cdlinex'] != 0) {
        unset($data['isadv']);
      }

      unset($data2['waivedqty']);

      if ($data['void'] == 0) {
        $cvoid = $this->coreFunctions->datareader("select void as value from postock where trno='" . $trno . "' and line ='" . $line . "'  ");
        if ($cvoid == 1) {
          unset($data['void']);
        }
      }

      $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
      $this->coreFunctions->sbcupdate("stockinfotrans", $data2, ['trno' => $trno, 'line' => $line]);


      if ($isproject) {
        $this->updateprojmngmt($config, $stageid);
      }
      if ($refx != 0) {
        if ($this->setserveditems($refx, $linex, $data['reqtrno'], $data['reqline']) === 0) {
          $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
          $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
          $this->setserveditems($refx, $linex, $data['reqtrno'], $data['reqline']);
          $return = false;
        }
      }
      if ($cdrefx != 0) {
        if ($this->setservedcanvassitems($cdrefx, $cdlinex, $trno, $reqtrno, $reqline) === 0) {
          $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
          $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
          $this->setservedcanvassitems($cdrefx, $cdlinex, $trno, $reqtrno, $reqline);
          $return = false;
        }
      }

      if ($sorefx != 0) {
        if ($this->setservedsoitems($sorefx, $solinex) === 0) {
          $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
          $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
          $this->setservedsoitems($sorefx, $solinex);
          $return = false;
        }
      }
      if ($sorefx != 0) {
        if ($this->setservedsqitems($sorefx, $solinex) === 0) {
          $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
          $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
          $this->setservedsqitems($sorefx, $solinex);
          $return = false;
        }
      }

      if ($osrefx != 0) {
        if ($this->setservedositems($osrefx, $oslinex) === 0) {
          $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
          $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
          $this->setservedositems($osrefx, $oslinex);
          $return = false;
        }
      }




      return $return;
    }
  } // end function



  public function deleteallitem($config)
  {
    $isallow = true;
    $trno = $config['params']['trno'];
    $data = $this->coreFunctions->opentable('select refx,linex,cdrefx,cdlinex,stageid,sorefx,solinex,osrefx,oslinex,reqtrno,reqline from ' . $this->stock . ' where trno=? and (refx<>0 or cdrefx<>0 or sorefx<>0 or osrefx<>0)', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from stockinfotrans where trno=?', 'delete', [$trno]);

    foreach ($data as $key => $value) {
      if ($data[$key]->refx != 0) {
        $this->setserveditems($data[$key]->refx, $data[$key]->linex, $data[$key]->reqtrno, $data[$key]->reqline);
      } elseif ($data[$key]->cdrefx != 0) {
        $this->setservedcanvassitems($data[$key]->cdrefx, $data[$key]->cdlinex, $trno, $data[$key]->reqtrno, $data[$key]->reqline);
      }

      if (floatval($data[$key]->sorefx) != 0) {
        $this->setservedsoitems($data[$key]->sorefx, $data[$key]->solinex);
        $this->setservedsqitems($data[$key]->sorefx, $data[$key]->solinex);
      }

      if (floatval($data[$key]->osrefx) != 0) {
        $this->setservedositems($data[$key]->osrefx, $data[$key]->oslinex);
      }
      $this->updateprojmngmt($config, $data[$key]->stageid);
    }
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL ITEMS');
    return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
  }


  public function deleteitem($config)
  {
    $config['params']['trno'] = $config['params']['row']['trno'];
    $config['params']['line'] = $config['params']['row']['line'];
    $config['params']['stageid'] = $config['params']['row']['stageid'];
    $data = $this->openstockline($config);

    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = "delete from " . $this->stock . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
    $this->coreFunctions->execqry('delete from stockinfotrans where trno=? and line=?', 'delete', [$trno, $line]);
    if ($data[0]->refx !== 0) {
      $this->setserveditems($data[0]->refx, $data[0]->linex, $data[0]->reqtrno, $data[0]->reqline);
    }
    if ($data[0]->cdrefx !== 0) {
      $this->setservedcanvassitems($data[0]->cdrefx, $data[0]->cdlinex, $trno, $data[0]->reqtrno, $data[0]->reqline);
    }
    if ($data[0]->sorefx !== 0) {
      $this->setservedsoitems($data[0]->sorefx, $data[0]->solinex);
      $this->setservedsqitems($data[0]->sorefx, $data[0]->solinex);
    }
    if ($data[0]->osrefx !== 0) {
      $this->setservedositems($data[0]->osrefx, $data[0]->oslinex);
    }
    $this->updateprojmngmt($config, $config['params']['stageid']);
    $data = json_decode(json_encode($data), true);
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' Barcode:' . $data[0]['barcode'] . ' Qty:' . $data[0]['rrqty'] . ' Amt:' . $data[0]['rrcost'] . ' Disc:' . $data[0]['disc'] . ' WH:' . $data[0]['wh'] . ' Ext:' . $data[0]['ext']);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
  } // end function

  public function getposummaryqry($config)
  {
    $filter = '';
    if (isset($config['params']['client'])) {
      if ($config['params']['client'] != '') {
        $filter = " and head.client='" . $config['params']['client'] . "'";
      }
    }
    return "select head.docno, head.terms, ifnull(item.itemid,0) as itemid,stock.trno,stock.line, ifnull(item.barcode,'') as barcode,stock.uom, stock.cost,(stock.qty-stock.qa) as qty,stock.rrcost,
    round((stock.qty-stock.qa)/ case when ifnull(u.factor,0)<>0 then u.factor when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
    stock.disc,stock.reqtrno,stock.reqline, client.addr as address, '' as rem, info.isadv, info.paymentid, 
    head.yourref,stock.catid,cat.category,pr.clientname,ifnull(sa.sano,'') as sanodesc,stock.whid,stock.waivedqty,sinfo.uom2,stockinfo.ctrlno
    from hcdhead as head 
    left join hcdstock as stock on stock.trno=head.trno
    left join transnum on transnum.trno=head.trno 
    left join item on item.itemid=stock.itemid 
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client on client.client=head.client 
    left join hheadinfotrans as info on info.trno=head.trno
    left join reqcategory as cat on cat.line=stock.catid
    left join hprhead as pr on pr.trno=stock.reqtrno
    left join clientsano as sa on sa.line=pr.sano
    left join hstockinfotrans as sinfo on sinfo.trno=stock.trno and sinfo.line=stock.line
    left join hstockinfotrans as stockinfo on stockinfo.trno=stock.reqtrno and stockinfo.line=stock.reqline
    left join uomlist as u on u.uom=sinfo.uom2 and u.isconvert=1
    where transnum.center= ? and stock.qty>(stock.qa+stock.voidqty) and stock.void=0 and stock.status=1 and stock.approveddate2 is not null " . $filter;
  }

  public function getcdsummary($config)
  {
    $trno = $config['params']['trno'];

    $pr = $this->coreFunctions->opentable("select trno from " . $this->stock . " where refx<>0 and trno=?", [$trno]);
    if (!empty($pr)) {
      return ['status' => false, 'msg' => 'Can`t add Canvass items, already used in PR.'];
    }

    // $wh = $config['params']['wh'];

    $wh = $this->coreFunctions->getfieldvalue('hcdhead', "wh", "trno=?", [$config['params']['rows'][0]['trno']]);


    $center = $config['params']['center'];
    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {

      $qry = $this->getposummaryqry($config);
      $qry .= " and stock.trno = ? ";
      $data = $this->coreFunctions->opentable($qry, [$center, $config['params']['rows'][$key]['trno']]);
      if (!empty($data)) {

        $potype = $this->coreFunctions->getfieldvalue($this->head, "ourref", "trno=?", [$trno]);
        if ($potype != "") {
          if ($potype != $data[0]->yourref) {
            return ['row' => $rows, 'status' => false, 'msg' => "Unable to pick CD with different PO type, this order already created for " . $potype, 'reloadhead' => true, 'trno' => $trno];
          }
        }

        $this->coreFunctions->sbcupdate(
          "headinfotrans",
          [
            'paymentid' => $data[0]->paymentid,
            'categoryid' => $data[0]->catid
          ],
          ['trno' => $trno]
        );

        $this->coreFunctions->sbcupdate($this->head, [
          'terms' => $data[0]->terms,
          'ourref' => $data[0]->yourref,
          'editby' =>  $config['params']['user'],
          'editdate' =>  $this->othersClass->getCurrentTimeStamp(),
          'wh' => $wh
        ], ['trno' => $trno]);


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
          $config['params']['data']['sgdrate'] = $data[$key2]->rrcost;
          $config['params']['data']['reqtrno'] = $data[$key2]->reqtrno;
          $config['params']['data']['reqline'] = $data[$key2]->reqline;
          $config['params']['data']['isadv'] = $data[$key2]->isadv;
          $config['params']['data']['waivedqty'] = $data[$key2]->waivedqty;
          $config['params']['data']['uom2'] = $data[$key2]->uom2;
          $config['params']['data']['ctrlno'] = $data[$key2]->ctrlno;
          $config['params']['data']['waivedspecs'] = 0;
          $return = $this->additem('insert', $config);
          if ($return['status']) {
            if ($this->setservedcanvassitems($data[$key2]->trno, $data[$key2]->line, $trno, $data[$key2]->reqtrno, $data[$key2]->reqline) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setservedcanvassitems($data[$key2]->trno, $data[$key2]->line, $trno, $data[$key2]->reqtrno, $data[$key2]->reqline);
              $row = $this->openstockline($config);
              $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
            }
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.', 'reloadhead' => true];
  } //end function



  public function getprsummary($config)
  {
    $trno = $config['params']['trno'];

    $canvass = $this->coreFunctions->opentable("select trno from " . $this->stock . " where cdrefx<>0 and trno=?", [$trno]);
    if (!empty($canvass)) {
      return ['status' => false, 'msg' => 'Cannot add PR items, already used in canvass'];
    }

    $wh = $config['params']['wh'];
    $center = $config['params']['center'];
    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "select head.docno, head.ourref, item.itemid,stock.trno,
      stock.line, item.barcode,stock.uom, stock.cost,
      (stock.qty-(stock.qa+stock.poqa+stock.voidqty)) as qty,stock.rrcost,
      round((stock.qty-(stock.qa+stock.poqa+stock.voidqty))/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
      stock.disc,st.line as stageid,head.yourref, info.isadv,head.potype,stockinfo.ctrlno
      from hprhead as head 
      left join hprstock as stock on stock.trno=head.trno 
      left join transnum on transnum.trno=head.trno 
      left join item on item.itemid=stock.itemid 
      left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
      left join stagesmasterfile as st on st.line = stock.stageid 
      left join hheadinfotrans as info on info.trno=head.trno
      left join hstockinfotrans as stockinfo on stockinfo.trno = stock.trno and stockinfo.line = stock.line
      where stock.trno = ? and transnum.center=? and stock.qty>(stock.qa+stock.poqa) and stock.void=0";

      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $center]);
      if (!empty($data)) {
        $potype = $this->coreFunctions->getfieldvalue($this->head, "ourref", "trno=?", [$trno]);
        if ($potype != "") {
          if ($potype != $data[0]->potype) {
            return ['row' => $rows, 'status' => false, 'msg' => "Unable to pick PR with different PO type, this order already created for " . $potype, 'reloadhead' => true, 'trno' => $trno];
          }
        }

        $this->coreFunctions->sbcupdate($this->head, [
          'ourref' => $data[0]->potype,
          'editby' =>  $config['params']['user'],
          'editdate' =>  $this->othersClass->getCurrentTimeStamp()
        ], ['trno' => $trno]);

        $this->coreFunctions->sbcupdate("headinfotrans", ['categoryid' => $data[0]->ourref, 'editby' =>  $config['params']['user'], 'editdate' =>  $this->othersClass->getCurrentTimeStamp()], ['trno' => $trno]);

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
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['reqtrno'] = $data[$key2]->trno;
          $config['params']['data']['reqline'] = $data[$key2]->line;
          $config['params']['data']['cdrefx'] = 0;
          $config['params']['data']['cdlinex'] = 0;
          $config['params']['data']['stageid'] =  $data[$key2]->stageid;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->rrcost;
          $config['params']['data']['ctrlno'] = $data[$key2]->ctrlno;
          $config['params']['data']['isadv'] = 1;
          $return = $this->additem('insert', $config);
          if ($return['status']) {

            if ($this->setserveditems($data[$key2]->trno, $data[$key2]->line, $data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setserveditems($data[$key2]->trno, $data[$key2]->line, $data[$key2]->trno, $data[$key2]->line);
              $row = $this->openstockline($config);
              $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
            }
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

    $pr = $this->coreFunctions->opentable("select trno from " . $this->stock . " where refx<>0 and trno=?", [$trno]);
    if (!empty($pr)) {
      return ['status' => false, 'msg' => 'Cannot add Canvass items, already used in PR'];
    }

    // $wh = $config['params']['wh'];
    $wh = $this->coreFunctions->getfieldvalue('hcdhead', "wh", "trno=?", [$config['params']['rows'][0]['trno']]);
    $center = $config['params']['center'];
    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "select head.docno, head.terms, ifnull(item.itemid,0) as itemid,stock.trno,stock.line, ifnull(item.barcode,'') as barcode,stock.uom, stock.cost,(stock.qty-stock.qa) as qty,stock.rrcost,
      round((stock.qty-stock.qa)/ case when ifnull(u.factor,0)<>0 then u.factor when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
      stock.disc,stock.reqtrno,stock.reqline,info.isadv,info.paymentid,head.yourref,stock.catid,cat.category,pr.clientname,ifnull(sa.sano,'') as sanodesc,stock.waivedqty,stock.reqtrno,stock.reqline,sinfo.uom2,stockinfo.ctrlno
      from hcdhead as head 
      left join hcdstock as stock on stock.trno=head.trno 
      left join transnum on transnum.trno=head.trno
      left join item on item.itemid=stock.itemid 
      left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
      left join hheadinfotrans as info on info.trno=stock.trno
      left join reqcategory as cat on cat.line=stock.catid
      left join hprhead as pr on pr.trno=stock.reqtrno
      left join clientsano as sa on sa.line=pr.sano
      left join hstockinfotrans as sinfo on sinfo.trno=stock.trno and sinfo.line=stock.line
      left join hstockinfotrans as stockinfo on stockinfo.trno=stock.reqtrno and stockinfo.line=stock.reqline
      left join uomlist as u on u.uom=sinfo.uom2 and u.isconvert=1
      where stock.trno = ? and stock.line=? and transnum.center=? 
            and stock.qty>(stock.qa+stock.voidqty) and stock.void=0 and stock.status=1";

      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line'], $center]);
      if (!empty($data)) {

        $potype = $this->coreFunctions->getfieldvalue($this->head, "ourref", "trno=?", [$trno]);
        if ($potype != "") {
          if ($potype != $data[0]->yourref) {
            return ['row' => $rows, 'status' => false, 'msg' => "Unable to pick CD with different PO type, this order already created for " . $potype, 'reloadhead' => true, 'trno' => $trno];
          }
        }

        $this->coreFunctions->sbcupdate(
          "headinfotrans",
          [
            'paymentid' => $data[0]->paymentid,
            'categoryid' => $data[0]->catid
          ],
          ['trno' => $trno]
        );


        $this->coreFunctions->sbcupdate($this->head, [
          'terms' => $data[0]->terms,
          'ourref' => $data[0]->yourref,
          'editby' =>  $config['params']['user'],
          'editdate' =>  $this->othersClass->getCurrentTimeStamp(),
          'wh' => $wh
        ], ['trno' => $trno]);

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
          $config['params']['data']['sgdrate'] = $data[$key2]->rrcost;
          $config['params']['data']['reqtrno'] = $data[$key2]->reqtrno;
          $config['params']['data']['reqline'] = $data[$key2]->reqline;
          $config['params']['data']['isadv'] = $data[$key2]->isadv;
          $config['params']['data']['waivedqty'] = $data[$key2]->waivedqty;
          $config['params']['data']['uom2'] = $data[$key2]->uom2;
          $config['params']['data']['ctrlno'] = $data[$key2]->ctrlno;
          $config['params']['data']['waivedspecs'] = 0;
          $return = $this->additem('insert', $config);
          if ($return['status']) {
            if ($this->setservedcanvassitems($data[$key2]->trno, $data[$key2]->line, $trno, $data[$key2]->reqtrno, $data[$key2]->reqline) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setservedcanvassitems($data[$key2]->trno, $data[$key2]->line, $trno, $data[$key2]->reqtrno, $data[$key2]->reqline);
              $row = $this->openstockline($config);
              $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
            }

            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.', 'reloadhead' => true];
  } //end function



  public function getprdetails($config)
  {
    $trno = $config['params']['trno'];

    $canvass = $this->coreFunctions->opentable("select trno from " . $this->stock . " where cdrefx<>0 and trno=?", [$trno]);
    if (!empty($canvass)) {
      return ['status' => false, 'msg' => 'Cannot add PR items, already used in canvass'];
    }

    $wh = $config['params']['wh'];
    $center = $config['params']['center'];
    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "select head.docno, head.ourref, item.itemid,stock.trno,
      stock.line, item.barcode,stock.uom, stock.cost,
      (stock.qty-(stock.qa+stock.poqa+stock.voidqty)) as qty,stock.rrcost,
      round((stock.qty-(stock.qa+stock.poqa+stock.voidqty))/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
      stock.disc,st.line as stageid,info.paymentid,head.yourref,info.isadv, head.potype, stockinfo.ctrlno
      from hprhead as head 
      left join hprstock as stock on stock.trno=head.trno
      left join transnum on transnum.trno=head.trno 
      left join item on item.itemid=stock.itemid 
      left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
      left join hheadinfotrans as info on info.trno=head.trno
      left join hstockinfotrans as stockinfo on stockinfo.trno = stock.trno and stockinfo.line = stock.line
      left join stagesmasterfile as st on st.line = stock.stageid
      where stock.trno = ? and stock.line=? and transnum.center=? and stock.qty>(stock.qa+stock.poqa) and stock.void=0";

      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line'], $center]);
      if (!empty($data)) {

        $potype = $this->coreFunctions->getfieldvalue($this->head, "ourref", "trno=?", [$trno]);
        if ($potype != "") {
          if ($potype != $data[0]->potype) {
            return ['row' => $rows, 'status' => false, 'msg' => "Unable to pick PR with different PO type, this order already created for " . $potype, 'reloadhead' => true, 'trno' => $trno];
          }
        }

        $this->coreFunctions->sbcupdate($this->head, [
          'ourref' => $data[0]->potype,
          'editby' =>  $config['params']['user'],
          'editdate' =>  $this->othersClass->getCurrentTimeStamp()
        ], ['trno' => $trno]);

        $this->coreFunctions->sbcupdate("headinfotrans", ['categoryid' => $data[0]->ourref, 'editby' =>  $config['params']['user'], 'editdate' =>  $this->othersClass->getCurrentTimeStamp()], ['trno' => $trno]);

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
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['reqtrno'] = $data[$key2]->trno;
          $config['params']['data']['reqline'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->rrcost;
          $config['params']['data']['stageid'] =  $data[$key2]->stageid;
          $config['params']['data']['isadv'] =  1;
          $config['params']['data']['catid'] = $data[$key2]->ourref;
          $config['params']['data']['ctrlno'] = $data[$key2]->ctrlno;
          $return = $this->additem('insert', $config);
          if ($return['status']) {

            if ($this->setserveditems($data[$key2]->trno, $data[$key2]->line, $data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setserveditems($data[$key2]->trno, $data[$key2]->line, $data[$key2]->trno, $data[$key2]->line);
              $row = $this->openstockline($config);
              $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
            }
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.'];
  } //end function

  public function setserveditems($refx, $linex, $reqtrno, $reqline)
  {
    $qry1 = "select stock." . $this->hqty . " from pohead as head left join postock as
    stock on stock.trno=head.trno where head.doc='PO' and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry1 = $qry1 . " union all select hpostock." . $this->hqty . " from hpohead left join hpostock on hpostock.trno=
    hpohead.trno where hpohead.doc='PO' and hpostock.refx=" . $refx . " and hpostock.linex=" . $linex;

    $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty === '') {
      $qty = 0;
    }

    if ($reqtrno != 0 && $qty != 0) {
      $this->othersClass->logConsole("update hprstock set statrem='Purchase Order',statdate='" . $this->othersClass->getCurrentTimeStamp() . "'  where trno=" . $reqtrno . " and line=" . $reqline);
      $this->coreFunctions->execqry("update hprstock set statrem='Purchase Order - Draft',statdate='" . $this->othersClass->getCurrentTimeStamp() . "',poqa=" . $qty . "  where trno=" . $reqtrno . " and line=" . $reqline, 'update');
    }

    return $this->coreFunctions->execqry("update hprstock set poqa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
  }

  public function setservedcanvassitems($cdtrno, $cdline, $potrno, $reqtrno, $reqline)
  {
    $this->othersClass->logConsole('cdtrno:' . $cdtrno . '- cdline:' . $cdline);

    $qty = 0;
    $prqty = 0;
    $qry1 = "select stock." . $this->hqty . " from pohead as head left join postock as stock on stock.trno=head.trno where head.doc='PO' and stock.cdrefx=" . $cdtrno . " and stock.cdlinex=" . $cdline;
    $qry1 = $qry1 . " union all select hpostock." . $this->hqty . " from hpohead left join hpostock on hpostock.trno=hpohead.trno where hpohead.doc='PO' and hpostock.cdrefx=" . $cdtrno . " and hpostock.cdlinex=" . $cdline;

    $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty === '') {
      $qty = 0;
    }

    $this->othersClass->logConsole('cd qty:' . $qty);

    $qryrr = "select stock.qty from lahead as head left join lastock as stock on stock.trno=head.trno where head.doc='RR' and stock.cdrefx=" . $cdtrno . " and stock.cdlinex=" . $cdline;
    $qryrr = $qryrr . " union all select glstock.qty from glhead left join glstock on glstock.trno=glhead.trno where glhead.doc='RR' and glstock.cdrefx=" . $cdtrno . " and glstock.cdlinex=" . $cdline;

    $qry = "select ifnull(sum(qty),0) as value from (" . $qryrr . ") as t";
    $qtyrr = $this->coreFunctions->datareader($qry);
    if ($qtyrr == '') {
      $qtyrr = 0;
    }

    $prtrno = 0;
    $prline = 0;
    $prtrno = $this->coreFunctions->getfieldvalue('hcdstock', 'refx', 'trno=? and line=?', [$cdtrno, $cdline]);
    if ($prtrno === '') {
      $prtrno = 0;
    }

    $qtypr = 0;
    $qrypr = "select stock.qty from lahead as head left join lastock as stock on stock.trno=head.trno where head.doc='RR' and stock.prrefx=" . $cdtrno . " and stock.prlinex=" . $cdline;
    $qrypr = $qrypr . " union all select glstock.qty from glhead left join glstock on glstock.trno=glhead.trno where glhead.doc='RR' and glstock.prrefx=" . $cdtrno . " and glstock.prlinex=" . $cdline;
    $qry = "select ifnull(sum(qty),0) as value from (" . $qrypr . ") as t";
    $qtypr = $this->coreFunctions->datareader($qry);
    if ($qtypr == '') {
      $qtypr = 0;
    } else {
      $prtrno = $cdtrno;
      $prline = $cdline;
    }

    if ($prtrno != 0) {
      if ($prline == 0) {
        $prline = $this->coreFunctions->getfieldvalue('hcdstock', 'linex', 'trno=? and line=?', [$cdtrno, $cdline]);
      }



      $this->othersClass->logConsole('pr trno:' . $reqtrno . ' - pr line:' . $reqline);

      $qry1 = "select stock." . $this->hqty . " from pohead as head left join postock as stock on stock.trno=head.trno where head.doc='PO' and stock.void=0 and stock.reqtrno=" . $reqtrno . " and stock.reqline=" . $reqline;
      $qry1 = $qry1 . " union all select hpostock." . $this->hqty . " from hpohead left join hpostock on hpostock.trno=hpohead.trno where hpohead.doc='PO' and hpostock.void=0 and hpostock.reqtrno=" . $reqtrno . " and hpostock.reqline=" . $reqline;

      $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
      $prqty = $this->coreFunctions->datareader($qry2);
      if ($prqty === '') {
        $prqty = 0;
      }

      $this->othersClass->logConsole('pr qty:' . $qtypr);

      //2024.04.19 remove updating cdqa=" . $qty . " move to approved canvass
      if ($this->coreFunctions->execqry("update hprstock set poqa=" . $prqty . ", statrem='Purchase Order - Draft', statdate='" . $this->othersClass->getCurrentTimeStamp() . "' where trno=" . $reqtrno . " and line=" . $reqline, 'update') == 1) {

        if (!empty($reqref)) {
          $reqref = $this->coreFunctions->opentable("select isadv from postock where cdrefx=? and cdlinex=? and trno=?", [$cdtrno, $cdline, $potrno]);
          $podeadline = $this->coreFunctions->getfieldvalue("headinfotrans", "deadline", "trno=?", [$potrno]);
          if ($podeadline != "") {
            $this->coreFunctions->execqry("update hstockinfotrans set podeadline='" . $podeadline . "' where trno=" . $reqref[0]->reqtrno . " and line=" . $reqref[0]->reqline, 'update');
          }

          $this->coreFunctions->execqry("update hprstock set isadv='" . $reqref[0]->isadv . "' where trno=" . $reqref[0]->reqtrno . " and line=" . $reqref[0]->reqline, 'update');
        }
        $result = $this->coreFunctions->execqry("update hcdstock set qa=" . ($qty + $qtyrr) . " where trno=" . $cdtrno . " and line=" . $cdline, 'update');

        // updating status
        $status = $this->coreFunctions->datareader("select ifnull(count(trno),0) as value from hcdstock where trno=? and qty>qa", [$cdtrno]);
        if ($status) {
          $status = $this->coreFunctions->datareader("select ifnull(count(trno),0) as value from hcdstock where trno=? and qa<>0", [$cdtrno]);
          if ($status) {
            $this->coreFunctions->execqry("update transnum set statid=6 where trno=" . $cdtrno);
          } else {
            $this->coreFunctions->execqry("update transnum set statid=5 where trno=" . $cdtrno);
          }
        } else {
          $this->coreFunctions->execqry("update transnum set statid=7 where trno=" . $cdtrno);
        }
        return $result;
      } else {
        return 0;
      }
    } else {
      return $this->coreFunctions->execqry("update hcdstock set qa=" . ($qty + $qtyrr) . " where trno=" . $cdtrno . " and line=" . $cdline, 'update');
    }
  } //end func

  public function setservedsoitems($refx, $linex)
  {
    $qry1 = "select stock." . $this->hqty . " from pohead as head left join postock as
    stock on stock.trno=head.trno where head.doc='PO' and stock.sorefx=" . $refx . " and stock.solinex=" . $linex;

    $qry1 = $qry1 . " union all select hpostock." . $this->hqty . " from hpohead left join hpostock on hpostock.trno=
    hpohead.trno where hpohead.doc='PO' and hpostock.sorefx=" . $refx . " and hpostock.solinex=" . $linex;

    $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty === '') {
      $qty = 0;
    }
    return $this->coreFunctions->execqry("update hsostock set poqa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
  }

  public function setservedsqitems($refx, $linex)
  {
    if ($refx == 0) {
      return 1;
    }
    $qryso = "select stock.iss from lahead as head left join lastock as
  stock on stock.trno=head.trno where head.doc='SJ' and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qryso = $qryso . " union all select glstock.iss from glhead left join glstock on glstock.trno=
  glhead.trno where glhead.doc='SJ' and glstock.refx=" . $refx . " and glstock.linex=" . $linex;

    $qry = "select ifnull(sum(iss),0) as value from (" . $qryso . ") as t";
    $qtysj = $this->coreFunctions->datareader($qry);
    if ($qtysj == '') {
      $qtysj = 0;
    }

    $qrypo = "select stock." . $this->hqty . " from pohead as head left join postock as
  stock on stock.trno=head.trno where head.doc='PO' and stock.sorefx=" . $refx . " and stock.solinex=" . $linex;

    $qrypo = $qrypo . " union all select stock." . $this->hqty . " from hpohead as head left join hpostock as
  stock on stock.trno=head.trno where head.doc='PO' and stock.sorefx=" . $refx . " and stock.solinex=" . $linex;

    $qry = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qrypo . ") as t";
    $qtypo = $this->coreFunctions->datareader($qry);

    if ($qtypo == '') {
      $qtypo = 0;
    }

    return $this->coreFunctions->execqry("update hqsstock set poqa=" . ($qtypo + $qtysj) . " where trno=" . $refx . " and line=" . $linex, 'update');
  }

  public function setservedositems($refx, $linex)
  {
    $qry1 = "select stock." . $this->hqty . " from pohead as head left join postock as
    stock on stock.trno=head.trno where head.doc='PO' and stock.osrefx=" . $refx . " and stock.oslinex=" . $linex;

    $qry1 = $qry1 . " union all select hpostock." . $this->hqty . " from hpohead left join hpostock on hpostock.trno=
    hpohead.trno where hpohead.doc='PO' and hpostock.osrefx=" . $refx . " and hpostock.oslinex=" . $linex;

    $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if (floatval($qty) == 0) {
      $qty = 0;
    }
    return $this->coreFunctions->execqry("update hosstock set qa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
  }


  public function getlatestprice($config)
  {
    $barcode = $config['params']['barcode'];
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
    $forex = $this->coreFunctions->getfieldvalue($this->head, "forex", "trno=?", [$trno]);

    $qry = "select docno,left(dateid,10) as dateid,round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,disc,uom from(select head.docno,head.dateid,
    stock.rrcost as amt,stock.uom,stock.disc
    from lahead as head
    left join lastock as stock on stock.trno = head.trno
    left join cntnum on cntnum.trno=head.trno
    left join item on item.itemid=stock.itemid
    where head.doc = 'RR' and cntnum.center = ?
    and item.barcode = ? and head.client = ?
    and stock.rrcost <> 0
    UNION ALL
    select head.docno,head.dateid,stock.rrcost as computeramt,
    stock.uom,stock.disc from glhead as head
    left join glstock as stock on stock.trno = head.trno
    left join item on item.itemid = stock.itemid
    left join client on client.clientid = head.clientid
    left join cntnum on cntnum.trno=head.trno
    where head.doc = 'RR' and cntnum.center = ?
    and item.barcode = ? and client.client = ?
    and stock.rrcost <> 0
    order by dateid desc limit 5) as tbl order by dateid desc limit 1";

    $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $client, $center, $barcode, $client]);
    if (!empty($data)) {
      return ['status' => true, 'msg' => 'Found the latest purchase price...', 'data' => $data];
    } else {
      return ['status' => false, 'msg' => 'No Latest price found...'];
    }
  } // end function


  private function updateprojmngmt($config, $stage)
  {
    $trno = $config['params']['trno'];
    $data = $this->openstock($trno, $config);
    $proj = $this->coreFunctions->getfieldvalue($this->head, "projectid", "trno=?", [$trno]);
    $sub = $this->coreFunctions->getfieldvalue($this->head, "subproject", "trno=?", [$trno]);

    $qry1 = "select stock.ext from " . $this->head . " as head left join " . $this->stock . " as
    stock on stock.trno=head.trno where head.doc='PO' and head.projectid = " . $proj . " and head.subproject = " . $sub . " and stock.stageid=" . $stage;

    $qry1 = $qry1 . " union all select stock.ext from " . $this->hhead . " as head left join " . $this->hstock . " as stock on stock.trno=
      head.trno where head.doc='PO' and head.projectid = " . $proj . " and head.subproject = " . $sub . " and stock.stageid=" . $stage;

    $qry2 = "select ifnull(sum(ext),0) as value from (" . $qry1 . ") as t";

    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty === '') {
      $qty = 0;
    }

    $editdate = $this->othersClass->getCurrentTimeStamp();
    $editby = $config['params']['user'];

    return $this->coreFunctions->execqry("update stages set po=" . $qty . ", editdate = '" . $editdate . "', editby = '" . $editby . "' where projectid = " . $proj . " and subproject=" . $sub . " and stage=" . $stage, 'update');
  }

  public function getsosummary($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    $msg = '';
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.amt,
        (stock.iss-stock.poqa) as iss,stock.isamt,item.famt as tpdollar,
        round((stock.iss-stock.poqa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
        stock.disc,stock.loc,stock.expiry,head.yourref
        FROM hsohead as head left join hsostock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom where stock.trno = ? and stock.iss>stock.poqa and stock.void=0
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
          $config['params']['data']['amt'] = 0;
          $config['params']['data']['sorefx'] = $data[$key2]->trno;
          $config['params']['data']['solinex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $return = $this->additem('insert', $config);

          if ($msg = '') {
            $msg = $return['msg'];
          } else {
            $msg = $msg . $return['msg'];
          }

          if ($return['status']) {
            if ($this->setservedsoitems($data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setservedsoitems($data[$key2]->trno, $data[$key2]->line);
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

  public function getsodetails($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    $msg = '';
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.amt,
        (stock.iss-stock.poqa) as iss,stock.isamt,
        round((stock.iss-stock.poqa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
        stock.disc,stock.loc,stock.expiry,item.famt as tpdollar,head.yourref
        FROM hsohead as head left join hsostock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom where stock.trno = ? and stock.line=? and stock.iss>stock.poqa and stock.void=0
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
          $config['params']['data']['amt'] = 0;
          $config['params']['data']['sorefx'] = $data[$key2]->trno;
          $config['params']['data']['solinex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $return = $this->additem('insert', $config);
          if ($msg = '') {
            $msg = $return['msg'];
          } else {
            $msg = $msg . $return['msg'];
          }
          if ($return['status']) {
            if ($this->setservedsoitems($data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setservedsoitems($data[$key2]->trno, $data[$key2]->line);
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

  public function getsqposummary($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    $msg = '';
    $sotrno = 0;
    $forex  = $this->coreFunctions->getfieldvalue($this->head, "forex", "trno=?", [$trno]);
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
      select concat(stock.trno,stock.line) as keyid, stock.trno, stock.line, stock.itemid, item.barcode, item.itemname, stock.uom, so.docno, date(head.dateid) as dateid,
      (stock.iss-(stock.qa+stock.sjqa+stock.poqa)) as iss,stock.isamt, stock.disc,stock.amt,stock.ext,so.trno as sotrno,
      FORMAT(((stock.iss-(stock.qa+stock.sjqa+stock.poqa))/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
      item.famt as tpdollar,item.amt as tpphp
      from hsqhead as so left join hqshead as head on head.sotrno=so.trno left join hqsstock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid
      left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
      left join transnum on transnum.trno = head.trno
      where so.doc='SQ' and stock.iss > (stock.qa+stock.sjqa+stock.poqa) and stock.void = 0 and stock.iscanvass=0 and stock.trno=?
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
          $config['params']['data']['loc'] = '';
          $config['params']['data']['expiry'] = '';
          $config['params']['data']['rem'] = '';
          $config['params']['data']['amt'] = 0;
          $config['params']['data']['sorefx'] = $data[$key2]->trno;
          $config['params']['data']['solinex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
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
          $sotrno = $data[$key2]->sotrno;
        } // end foreach
        $this->coreFunctions->sbcupdate($this->head, ['sotrno' => $sotrno], ['trno' => $trno]);
      } //end if
    } //end foreach
    $this->loadheaddata($config);
    return ['row' => $rows, 'status' => true, 'msg' => $msg, 'reloaddata' => true];
  } //end function

  public function getsqdetails($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    $msg = '';
    $forex  = $this->coreFunctions->getfieldvalue($this->head, "forex", "trno=?", [$trno]);
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
      select concat(stock.trno,stock.line) as keyid, stock.trno, stock.line, stock.itemid, item.barcode, item.itemname, stock.uom, so.docno, date(head.dateid) as dateid,
      (stock.iss-(stock.qa+stock.sjqa+stock.poqa)) as iss,stock.isamt, stock.disc,stock.amt,stock.ext,
      FORMAT(((stock.iss-(stock.qa+stock.sjqa+stock.poqa))/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,item.famt as tpdollar,head.yourref
      from hsqhead as so left join hqshead as head on head.sotrno=so.trno left join hqsstock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid
      left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
      left join transnum on transnum.trno = head.trno
      where so.doc='SQ' and stock.iss > (stock.qa+stock.sjqa+stock.poqa) and stock.void = 0 and stock.iscanvass=0 and stock.trno=? and stock.line=?
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
          $config['params']['data']['amt'] = 0;
          $config['params']['data']['sorefx'] = $data[$key2]->trno;
          $config['params']['data']['solinex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
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

  public function getcriticalstocks($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    $msg = '';

    $data = $config['params']['rows'];

    foreach ($data as $key => $value) {

      $latestcost = $this->othersClass->getlatestcostTS($config, $value['barcode'], '', $config['params']['center'], $trno);
      if ($latestcost['status']) {
        $amt = $latestcost['data'][0]->amt;
      } else {
        $amt = 0;
      }

      $config['params']['data']['uom'] = $value['uom'];
      $config['params']['data']['itemid'] = $value['itemid'];
      $config['params']['trno'] = $trno;
      $config['params']['data']['disc'] = '';
      $config['params']['data']['amt'] = $amt;
      $config['params']['data']['qty'] = $value['reorder'] + $value['sobal'] - $value['pobal'];
      $config['params']['data']['wh'] = $wh;
      $config['params']['data']['rem'] = '';
      $config['params']['data']['ref'] = '';
      $config['params']['data']['loc'] = '';
      $return = $this->additem('insert', $config);
      if ($return['status']) {
        $line = $return['row'][0]->line;
        $config['params']['trno'] = $trno;
        $config['params']['line'] = $line;
        $row = $this->openstockline($config);
        $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
        array_push($rows, $return['row'][0]);
      }
    }

    return ['row' => $rows, 'status' => true, 'msg' => $msg];
  }

  private function autocreatestock($config, $data)
  {
    $trno = $config['params']['trno'];
    $sotrno = $data['sotrno'];
    $wh = $data['wh'];
    $rows = [];
    $msg = '';
    $forex  = $this->coreFunctions->getfieldvalue($this->head, "forex", "trno=?", [$trno]);
    $qry = "select concat(stock.trno,stock.line) as keyid, stock.trno, stock.line, stock.itemid, item.barcode, item.itemname, stock.uom, so.docno, date(head.dateid) as dateid,
      (stock.iss-(stock.qa+stock.sjqa+stock.poqa)) as iss,stock.isamt, stock.disc,stock.amt,stock.ext,
      FORMAT(((stock.iss-(stock.qa+stock.sjqa+stock.poqa))/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,item.famt as tpdollar,head.yourref
      from hsqhead as so left join hqshead as head on head.sotrno=so.trno left join hqsstock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid
      left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
      left join transnum on transnum.trno = head.trno
      where so.doc='SQ' and stock.iss > (stock.qa+stock.sjqa+stock.poqa) and stock.void = 0 and stock.iscanvass=0 and so.trno=?
    ";
    $data2 = $this->coreFunctions->opentable($qry, [$sotrno]);
    if (!empty($data2)) {
      foreach ($data2 as $key2 => $value) {
        $config['params']['data']['uom'] = $data2[$key2]->uom;
        $config['params']['data']['itemid'] = $data2[$key2]->itemid;
        $config['params']['trno'] = $trno;
        $config['params']['data']['disc'] = $data2[$key2]->disc;
        $config['params']['data']['qty'] = $data2[$key2]->isqty;
        $config['params']['data']['wh'] = $wh;
        $config['params']['data']['loc'] = '';
        $config['params']['data']['expiry'] = '';
        $config['params']['data']['rem'] = '';
        $config['params']['data']['amt'] = 0;
        $config['params']['data']['sorefx'] = $data2[$key2]->trno;
        $config['params']['data']['solinex'] = $data2[$key2]->line;
        $config['params']['data']['ref'] = $data2[$key2]->docno;
        $return = $this->additem('insert', $config);

        if ($msg = '') {
          $msg = $return['msg'];
        } else {
          $msg = $msg . $return['msg'];
        }

        if ($return['status']) {
          if ($this->setservedsqitems($data2[$key2]->trno, $data2[$key2]->line) == 0) {
            $datax = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
            $line = $return['row'][0]->line;
            $config['params']['trno'] = $trno;
            $config['params']['line'] = $line;
            $this->coreFunctions->sbcupdate($this->stock, $datax, ['trno' => $trno, 'line' => $line]);
            $this->setservedsqitems($data2[$key2]->trno, $data2[$key2]->line);
            $row = $this->openstockline($config);
            $return = ['row' => $row, 'status' => true, 'msg' => $msg];
          }
          array_push($rows, $return['row'][0]);
        }
      } // end foreach
      return ['row' => $rows, 'status' => true, 'msg' => 'Item was successfully added.', 'reloaddata' => true];
    } //end if

  }

  public function getossummary($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    $msg = '';
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.cost,
        (stock.qty-stock.qa) as qty,stock.rrcost,item.famt as tpdollar,
        round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
        stock.disc,stock.loc
        FROM hoshead as head left join hosstock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom where stock.trno = ? and stock.qty>stock.qa and stock.void=0
    ";
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno']]);
      if (!empty($data)) {
        foreach ($data as $key2 => $value) {
          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['qty'] = $data[$key2]->rrqty;
          $config['params']['data']['wh'] = $wh;
          $config['params']['data']['loc'] = $data[$key2]->loc;
          $config['params']['data']['rem'] = '';
          $config['params']['data']['amt'] = $data[$key2]->rrcost;
          $config['params']['data']['osrefx'] = $data[$key2]->trno;
          $config['params']['data']['oslinex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;

          $return = $this->additem('insert', $config);

          if ($msg = '') {
            $msg = $return['msg'];
          } else {
            $msg = $msg . $return['msg'];
          }

          if ($return['status']) {
            if ($this->setservedositems($data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setservedositems($data[$key2]->trno, $data[$key2]->line);
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

  public function getosdetails($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    $msg = '';
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.cost,
        (stock.qty-stock.qa) as qty,stock.rrcost,
        round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
        stock.disc,stock.loc,item.famt as tpdollar
        FROM hoshead as head left join hosstock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom where stock.trno = ? and stock.line=? and stock.qty>stock.qa and stock.void=0
    ";
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line']]);
      if (!empty($data)) {
        foreach ($data as $key2 => $value) {
          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['qty'] = $data[$key2]->rrqty;
          $config['params']['data']['wh'] = $wh;
          $config['params']['data']['loc'] = $data[$key2]->loc;
          $config['params']['data']['rem'] = '';
          $config['params']['data']['amt'] = $data[$key2]->rrcost;
          $config['params']['data']['osrefx'] = $data[$key2]->trno;
          $config['params']['data']['oslinex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $return = $this->additem('insert', $config);
          if ($msg = '') {
            $msg = $return['msg'];
          } else {
            $msg = $msg . $return['msg'];
          }
          if ($return['status']) {
            if ($this->setservedositems($data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setservedositems($data[$key2]->trno, $data[$key2]->line);
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


  public function checkitemid($config)
  {
    $noitemid = $this->coreFunctions->opentable("select trno from " . $this->stock . " where trno=? and itemid=0", [$config['params']['trno']], '', true);
    if (!empty($noitemid)) {
      return ['status' => false, 'msg' => 'Please generate temporary barcode first.'];
    }

    return ['status' => true];
  }

  public function forapproval($config)
  {
    $posted = $this->othersClass->isposted($config);
    if ($posted) {
      return ['status' => false, 'msg' => 'Already posted'];
    }

    $catid = $this->coreFunctions->getfieldvalue("headinfotrans", "categoryid", "trno=?", [$config['params']['trno']], '', true);
    if ($catid == 0) {
      return ['status' => false, 'msg' => 'Invalid category. Please ask your system provider.'];
    }

    $stock = $this->coreFunctions->datareader("select count(trno) as value from " . $this->stock . " where trno=?", [$config['params']['trno']], '', true);
    if ($stock == 0) {
      return ['status' => false, 'msg' => 'Nothing to approve, please add items first.'];
    }

    $blnfirst = true;

    $this->coreFunctions->execqry("delete from transnumtodo where trno=? and donedate is null", 'delete', [$config['params']['trno']]);

    $approver = $this->coreFunctions->opentable("select client.clientid, d.approver, d.appline, d.line, d.ordernum, d.iscat from approversetup as s left join approverdetails as d on d.appline=s.line left join client on client.email=d.approver where s.isapprover=1 and s.doc='PO' order by d.ordernum, d.iscat desc");
    if (!empty($approver)) {
      $category = $this->coreFunctions->getfieldvalue($this->head, "ourref", "trno=?", [$config['params']['trno']], '', true);
      foreach ($approver as $key => $value) {
        if ($value->iscat == 1) {
          $catexist = $this->coreFunctions->getfieldvalue("approverrcat", "catid", "appid=?", [$value->line], '', true);
          if ($catexist == $category) {
            $insert = [
              'clientid' => $value->clientid,
              'trno' => $config['params']['trno'],
              'createby' => $config['params']['user'],
              'createdate' => $this->othersClass->getCurrentTimeStamp()
            ];
            $this->coreFunctions->sbcinsert("transnumtodo", $insert);
            if ($blnfirst) {
              $this->coreFunctions->sbcupdate("transnum", ['appuser' => $value->approver], ['trno' => $config['params']['trno']]);
              $blnfirst = false;
            }
          }
        } else {
          $checkifcatprio = $this->coreFunctions->opentable("select d.line from approverrcat as cat left join approverdetails as d on d.line=cat.appid where cat.catid=? and d.ordernum=?", [$category, $value->ordernum]);
          if (!empty($checkifcatprio)) {
            continue;
          }
          $insert = [
            'clientid' => $value->clientid,
            'trno' => $config['params']['trno'],
            'createby' => $config['params']['user'],
            'createdate' => $this->othersClass->getCurrentTimeStamp()
          ];
          $this->coreFunctions->sbcinsert("transnumtodo", $insert);
          if ($blnfirst) {
            $this->coreFunctions->sbcupdate("transnum", ['appuser' => $value->approver], ['trno' => $config['params']['trno']]);
            $blnfirst = false;
          }
        }
      }
    }

    if ($this->coreFunctions->sbcupdate($this->tablenum, ['statid' => 10], ['trno' => $config['params']['trno']])) {
      $this->coreFunctions->sbcupdate('headinfotrans', ['instructions' => ''], ['trno' => $config['params']['trno']]);
      // $this->logger->sbcwritelog($config['params']['trno'], $config, 'HEAD', 'Tag FOR APPROVAL');
      $this->logger->sbcstatlog($config['params']['trno'], $config, 'HEAD', 'Tag FOR APPROVAL');
      return ['status' => true, 'msg' => 'Successfully updated.', 'backlisting' => true];
    } else {
      return ['status' => false, 'msg' => 'Failed to tag for approval.'];
    }
  }

  public function ordered($config)
  {
    $posted = $this->othersClass->isposted($config);
    if ($posted) {
      return ['status' => false, 'msg' => 'Already posted.'];
    }

    $access = $this->othersClass->checkAccess($config['params']['user'], 4164);
    if (!$access) {
      return ['status' => false, 'msg' => 'Please advice your administrator regarding this restriction.'];
    }

    $noitemid = $this->checkitemid($config);
    if (!$noitemid['status']) {
      return $noitemid;
    }

    $statid = $this->othersClass->getstatid($config);
    if ($statid != 36) {
      return ['trno' =>  $config['params']['trno'], 'status' => false, 'msg' => 'Transaction is not yet approved'];
    }


    $printed = $this->coreFunctions->getfieldvalue("headinfotrans", "printdate", "trno=?", [$config['params']['trno']]);
    if ($printed == '') {
      return ['trno' =>  $config['params']['trno'], 'status' => false, 'msg' => 'Please print PO first'];
    }

    if ($this->coreFunctions->sbcupdate($this->tablenum, ['statid' => 39], ['trno' => $config['params']['trno']])) {
      $this->coreFunctions->sbcupdate('headinfotrans', ['instructions' => ''], ['trno' => $config['params']['trno']]);
      // $this->logger->sbcwritelog($config['params']['trno'], $config, 'HEAD', 'ORDERED.');
      $this->logger->sbcstatlog($config['params']['trno'], $config, 'HEAD', 'ORDERED.');
      return ['status' => true, 'msg' => 'Successfully updated.', 'backlisting' => true];
    } else {
      return ['status' => false, 'msg' => 'Failed to tag ordered.'];
    }
  }


  public function doneapproved($config)
  {
    $posted = $this->othersClass->isposted($config);
    if ($posted) {
      return ['status' => false, 'msg' => 'Already posted'];
    }

    $access = $this->othersClass->checkAccess($config['params']['user'], 4009);
    if (!$access) {
      return ['status' => false, 'msg' => 'Please advice your administrator regarding this restriction'];
    }

    $noitemid = $this->checkitemid($config);
    if (!$noitemid['status']) {
      return $noitemid;
    }

    $blnApproved = false;
    checkapproverhere:
    $approver = $this->coreFunctions->opentable("select c.line, client.clientname, client.clientid, client.email from transnumtodo as c left join client on client.clientid=c.clientid where c.trno=? and c.donedate is null order by c.line limit 1", [$config['params']['trno']]);
    if (!empty($approver)) {
      if ($config['params']['adminid'] != $approver[0]->clientid) {
        if ($blnApproved) {
          $this->coreFunctions->sbcupdate("transnum", ['appuser' => $approver[0]->email], ['trno' => $config['params']['trno']]);
          return ['status' => true, 'msg' => 'Successfully approved, but need to approve by next approver ' . $approver[0]->clientname, 'backlisting' => true];
        } else {
          return ['status' => false, 'msg' => 'Failed to approved, must approved by user ' . $approver[0]->clientname];
        }
      } else {
        if ($this->coreFunctions->sbcupdate("transnumtodo", ['donedate' => $this->othersClass->getCurrentTimeStamp()], ['line' => $approver[0]->line])) {
          // $this->logger->sbcwritelog($config['params']['trno'], $config, 'HEAD', 'APPROVED.');
          $this->logger->sbcstatlog($config['params']['trno'], $config, 'HEAD', 'APPROVED.');
          $this->coreFunctions->sbcupdate("transnum", ['appuser' => ''], ['trno' => $config['params']['trno']]);
          $this->coreFunctions->sbcupdate('headinfotrans', ['instructions' => ''], ['trno' => $config['params']['trno']]);
          $blnApproved = true;
        }
      }
      goto checkapproverhere;
    }
    updateforpostinghere:

    $this->othersClass->logConsole('update status');
    if ($this->coreFunctions->sbcupdate($this->tablenum, ['statid' => 36], ['trno' => $config['params']['trno']])) {
      return ['status' => true, 'msg' => 'Successfully updated.', 'backlisting' => true];
    } else {
      return ['status' => false, 'msg' => 'Failed to tag approved'];
    }
  }

  public function generatecode($config)
  {
    $status = true;
    $trno = $config['params']['trno'];
    $msg = '';

    $pending = $this->coreFunctions->opentable("select info.itemdesc,info.ctrlno
          from postock as s left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline
          left join stockinfotrans as sm on sm.trno=s.trno and sm.line=s.line
          where sm.unit='' and s.itemid=0 and s.trno=" . $trno);
    if (!empty($pending)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Please input base UOM for item ' . $pending[0]->itemdesc . ' (' . $pending[0]->ctrlno . ')'];
    }

    $tempdata = $this->coreFunctions->opentable("select s.trno,s.line,s.reqtrno,s.reqline,info.itemdesc,info.specs,sm.unit, 
                            s.uom, sm.uom2, ifnull(u.factor,0) as factor,s.rrcost, s.disc, s.rrqty, 
                            s.cdrefx, s.cdlinex, s.reqtrno, s.reqline,info.isasset
                                                from postock as s left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline
                                                left join stockinfotrans as sm on sm.trno=s.trno and sm.line=s.line
                                                left join uomlist as u on u.uom=sm.uom2 and u.isconvert=1
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

          $otheruom = [];
          $baseuom = $value->unit;
          if ($value->uom2 != '') {
            if ($value->factor != 1) {
              if ($baseuom != $value->uom) {
                $otheruom = ['uom' => $value->uom, 'factor' => $value->factor];
              } else {
                if ($msg == '') {
                  $msg = $value->itemdesc . " based UOM must " . $value->uom . " not have factor greater than 1 (Ref. conversion: " . $value->uom2 . ")";
                } else {
                  $msg .= $value->itemdesc . " based UOM must " . $value->uom . " not have factor greater than 1 (Ref. conversion: " . $value->uom2 . ")";
                }
                continue;
              }
            }
          }

          $rawdata = [
            'barcode' => $tmpcode,
            'othcode' => $tmpcode,
            'itemname' => $value->itemdesc,
            'uom' => $baseuom,
            'shortname' => $value->specs
          ];

          if ($value->isasset == 'YES') {
            $rawdata['isgeneric'] = 1;
          }

          $itemid = $this->coreFunctions->insertGetId('item', $rawdata);
          if ($itemid != 0) {
            $this->logger->sbcwritelog($itemid, $config, 'CREATE', $itemid . ' - ' . $tmpcode . ' - ' . $value->itemdesc . ' (Auto-create from RR)', 'item_log');
            $this->coreFunctions->sbcinsert("uom", ['itemid' => $itemid, 'uom' => $baseuom, 'factor' => 1]);
            $otheruom['itemid'] = $itemid;
            $this->coreFunctions->sbcinsert("uom", $otheruom);

            $factor = $this->coreFunctions->getfieldvalue("uom", "factor", "itemid=? and uom=?", [$itemid, $value->uom], '', true);
            if ($factor == 0) {
              $factor = 1;
            }

            $computedata = $this->othersClass->computestock($value->rrcost, $value->disc,  $value->rrqty, $factor);

            $stockupdate = [
              'itemid' => $itemid,
              'uom' => $value->uom,
              'qty' => $computedata['qty'],
              'cost' => $computedata['amt'],
              'ext' => $computedata['ext'],
              'editdate' => $this->othersClass->getCurrentTimeStamp(),
              'editby' => $config['params']['user']
            ];


            if ($this->coreFunctions->sbcupdate($this->stock, $stockupdate, ["trno" => $value->trno, "line" => $value->line])) {
              if ($value->cdrefx != 0) {
                if ($this->setservedcanvassitems($value->cdrefx, $value->cdlinex, $trno, $value->reqtrno, $value->reqline) === 0) {
                  $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                  $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $value->trno, 'line' => $value->line]);
                  $this->setservedcanvassitems($value->cdrefx, $value->cdlinex, $trno, $value->reqtrno, $value->reqline);
                }
              }
            }
          }
        }
      }
    }

    $msg2 = '';
    if ($msg != '') {
      $msg2 = "Failed to generate barcode for " . $msg;
      $status = false;
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

  // start
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
    ini_set('memory_limit', '-1');

    $config['params']['trno'] = $config['params']['dataid'];
    $exportonly = $this->othersClass->checkAccess($config['params']['user'], 4848);

    if ($exportonly) {
      $this->logger->sbcviewreportlog($config);

      $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config['params']['dataid']);
      $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
    } else {
      if ($config['params']['dataparams']['radioaticompany'] == 'c4') {
        $this->logger->sbcviewreportlog($config, 'View Printing. (Public format)');

        $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config['params']['dataid']);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
      } else {
        $statid = $this->othersClass->getstatid($config);
        $isposted = $this->othersClass->isposted($config);

        if ($isposted) goto postedhere;
        if ($statid == "36") {

          $printed = $this->coreFunctions->getfieldvalue($isposted ? "hheadinfotrans" :  "headinfotrans", "printdate", "trno=?", [$config['params']['dataid']]);
          postedhere:
          $multiprint = $this->othersClass->checkAccess($config['params']['user'], 4122);
          if ($multiprint) $printed = "";

          if ($printed == "") {
            $this->logger->sbcviewreportlog($config);

            $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config['params']['dataid']);
            $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

            $this->coreFunctions->execqry("update headinfotrans set printdate='" . $this->othersClass->getCurrentTimeStamp() . "' where trno=" . $config['params']['dataid'] . " and printdate is null");
          } else {
            $this->logger->sbcviewreportlog($config, "You can only print PO at once.");
            $str = app($this->companysetup->getreportpath($config['params']))->notallowtoprint($config, "You can only print PO at once.");
          }
        } else {
          if ($statid == 39) {
            goto postedhere;
          }
          ini_set('memory_limit', '-1');
          $this->logger->sbcviewreportlog($config, "Only approved PO can be printed.");
          $str = app($this->companysetup->getreportpath($config['params']))->notallowtoprint($config, "Only approved PO can be printed.");
        }
      }
    }



    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
  // end



} //end class
