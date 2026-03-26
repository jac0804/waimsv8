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

use Datetime;
use DateInterval;

class cd
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'CANVASS SHEET';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false];
  public $tablenum = 'transnum';
  public $head = 'cdhead';
  public $hhead = 'hcdhead';
  public $stock = 'cdstock';
  public $hstock = 'hcdstock';
  public $tablelogs = 'transnum_log';
  public $statlogs = 'transnum_stat';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';
  private $stockselect;
  public $dqty = 'rrqty';
  public $hqty = 'qty';
  public $damt = 'rrcost';
  public $hamt = 'cost';
  private $fields = ['trno', 'docno', 'dateid', 'due', 'client', 'clientname', 'yourref', 'ourref', 'rem', 'terms', 'forex', 'cur', 'wh', 'shipto', 'branch', 'deptid', 'iscanvassonly', 'procid'];
  private $except = ['trno', 'dateid', 'due'];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  private $reporter;

  public $showfilterlabel = [
    // ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
    // ['val' => 'locked', 'label' => 'Locked', 'color' => 'primary'],
    // ['val' => 'forchecking', 'label' => 'For Checking', 'color' => 'primary'],
    // ['val' => 'forposting', 'label' => 'For Posting', 'color' => 'primary'],
    // ['val' => 'iscanvassonly', 'label' => 'Canvass Only', 'color' => 'primary'],
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
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 1428,
      'edit' => 1429,
      'new' => 1430,
      'save' => 1431,
      'change' => 1432,
      'delete' => 1433,
      'print' => 1434,
      'lock' => 1435,
      'unlock' => 1436,
      'changeamt' => 1437,
      'post' => 1438,
      'unpost' => 1439,
      'additem' => 1440,
      'edititem' => 1441,
      'deleteitem' => 1442,
      'donechecking' => 4010
    );
    return $attrib;
  }


  public function createdoclisting()
  {

    $action = 0;
    $liststatus = 1;
    $listdocument = 2;
    $listdate = 3;
    $listclientname = 4;
    $yourref = 5;
    $ourref = 6;

    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'yourref', 'ourref', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
    $stockbuttons = ['view', 'duplicatedoc'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$listclientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $cols[$yourref]['align'] = 'text-left';
    $cols[$ourref]['align'] = 'text-left';

    $cols[$yourref]['label'] = 'PO Type';
    return $cols;
  }

  public function paramsdatalisting($config)
  {
    $fields = ['stat'];
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'stat.label', 'Status');
    data_set($col1, 'stat.type', 'lookup');
    data_set($col1, 'stat.action', 'lookupcdtransstatus');
    data_set($col1, 'stat.lookupclass', 'lookupcdtransstatus');

    $status = 'draft';
    $statusname = 'DRAFT';
    $access = $this->othersClass->checkAccess($config['params']['user'], 4010);
    if ($access == 1) {
      $status = 'forchecking';
      $statusname = 'For Checking';
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
    $viewall = $this->othersClass->checkAccess($config['params']['user'], 3767);

    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));
    $adminid = $config['params']['adminid'];
    $itemfilter = isset($config['params']['doclistingparam']['typecode']) ? $config['params']['doclistingparam']['typecode'] : 'draft';

    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $condition = '';
    $searchfilter = $config['params']['search'];
    $limit = "limit 150";
    $filtersearch = "";
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
              from hstockinfotrans as infos left join cdstock as stock on infos.trno=stock.reqtrno and infos.line=stock.reqline
              where stock.trno=head.trno ),'') as ctrlno";
      $haddselect = ", ifnull((select group_concat(distinct infos.ctrlno)
              from hstockinfotrans as infos left join hcdstock as stock on infos.trno=stock.reqtrno and infos.line=stock.reqline
              where stock.trno=head.trno ),'') as ctrlno";
    }

    if ($search != "") {
      $filtersearch = $this->othersClass->multisearch($searchfield, $search);
    }

    $access = $this->othersClass->checkAccess($config['params']['user'], 4010);
    if ($access == 1) {
      $itemfilter = isset($config['params']['doclistingparam']['typecode']) ? $config['params']['doclistingparam']['typecode'] : 'forchecking';
      if (isset($config['params']['doclistingparam']['typecode'])) {
        $itemfilter = $config['params']['doclistingparam']['typecode'];
      } else {
        $itemfilter = "forchecking";
      }
    }
    $filterid = "";
    if ($adminid != 0) {
      $trnxtype = $this->coreFunctions->getfieldvalue("client", "deptcode", "clientid=?", [$config['params']['adminid']]);
      $filterid = " and info.trnxtype = '" . $trnxtype . "' ";
    }
    $user = ($config['params']['user']);
    $filter3 = '';
    if (!$viewall) {
      $filter3 = "and head.createby = '"  . $user . "'";
    }

    if ($itemfilter == 'all') {
      $draft = "ifnull(stat.status,'DRAFT')";
      $locked = "'LOCKED'";
      $qry = "select trno, docno,clientname,dateid,status,createby,editby,viewby,postedby,yourref,ourref $allselect from ( 
              select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid, $draft as status,
                     head.createby,head.editby,head.viewby,num.postedby,head.yourref, head.ourref $addselect
              from " . $this->head . " as head 
              left join " . $this->tablenum . " as num on num.trno=head.trno 
              left join trxstatus as stat on stat.line=num.statid 
              left join headinfotrans as info on info.trno=head.trno
              where head.doc=? and num.center=? and 
                    CONVERT(head.dateid,DATE)>=?   $filter3 $filterid and CONVERT(head.dateid,DATE)<=? 
                    and num.postdate is null and head.lockdate is null 
              union all
              select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid, $locked as status,
                     head.createby,head.editby,head.viewby,num.postedby,head.yourref, head.ourref $addselect   
              from " . $this->head . " as head 
              left join " . $this->tablenum . " as num on num.trno=head.trno 
              left join trxstatus as stat on stat.line=num.statid
              left join headinfotrans as info on info.trno=head.trno
              where head.doc=? and num.center=? and 
                    CONVERT(head.dateid,DATE)>=?   $filter3 $filterid and CONVERT(head.dateid,DATE)<=? 
                    and head.lockdate is not null and num.postdate is null
              union all
              select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid,'POSTED' as status,
                     head.createby,head.editby,head.viewby, num.postedby,head.yourref, head.ourref $haddselect   
              from " . $this->hhead . " as head 
              left join " . $this->tablenum . " as num on num.trno=head.trno   
              left join hheadinfotrans as info on info.trno=head.trno 
              where head.doc=? and num.center=?   $filter3 $filterid  and convert(head.dateid,DATE)>=? 
                    and CONVERT(head.dateid,DATE)<=? ) as a where 1=1 $filtersearch
              order by dateid desc,docno desc " . $limit;

      $data = $this->coreFunctions->opentable($qry, [$doc, $center, $date1, $date2, $doc, $center, $date1, $date2, $doc, $center, $date1, $date2]);
    } else {

      $status = "'DRAFT'";
      switch ($itemfilter) {
        case 'draft':
          $condition .= ' and num.postdate is null and head.lockdate is null  and num.statid in (0,16)';
          $status = "if(num.statid=16,stat.status,'DRAFT')";
          break;
        case 'locked':
          $condition .= ' and head.lockdate is not null and num.postdate is null and iscanvassonly = 0';
          $status = "'LOCKED'";
          break;
        case 'posted':
          $condition .= ' and num.postdate is not null ';
          $status = "if(num.statid=5,'For PO',stat.status)";
          break;
        case 'forchecking':
          $status = "stat.status";
          $condition .= ' and num.postdate is null and num.statid=45';
          break;
        case 'forposting':
          $status = "stat.status";
          $condition .= ' and num.postdate is null and num.statid=39';
          break;
        case 'iscanvassonly':
          $condition .= ' and head.iscanvassonly=1';
          $status = "'Canvass Only'";
          break;
      }
      $qry = "select trno, docno,clientname,dateid,status,createby,editby,viewby,postedby,yourref,ourref $allselect from ( 
              select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid, $status as status,
                     head.createby,head.editby,head.viewby,num.postedby,head.yourref, head.ourref $addselect    
              from " . $this->head . " as head 
              left join " . $this->tablenum . " as num on num.trno=head.trno 
              left join trxstatus as stat on stat.line=num.statid 
              left join headinfotrans as info on info.trno=head.trno  
              where head.doc=? and num.center=?   $filter3 $filterid and CONVERT(head.dateid,DATE)>=? 
                    and CONVERT(head.dateid,DATE)<=?  " . $condition . " 
              union all
              select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid, $status as status,
                     head.createby,head.editby,head.viewby, num.postedby,head.yourref, head.ourref $haddselect    
              from " . $this->hhead . " as head 
              left join " . $this->tablenum . " as num on num.trno=head.trno 
              left join trxstatus as stat on stat.line=num.statid 
              left join hheadinfotrans as info on info.trno=head.trno  
              where head.doc=? and num.center=?   $filter3 $filterid and convert(head.dateid,DATE)>=? 
                    and CONVERT(head.dateid,DATE)<=? " . $condition . " ) as a where 1=1 $filtersearch
              order by dateid desc,docno desc  "   . $limit;
      $data = $this->coreFunctions->opentable($qry, [$doc, $center, $date1, $date2, $doc, $center, $date1, $date2]);
    }

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
      'toggledown'
    );

    $buttons = $this->btnClass->create($btns);
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
    $cd_btnvoid_access = $this->othersClass->checkAccess($config['params']['user'], 3600);
    $waived_prqty_access = $this->othersClass->checkAccess($config['params']['user'], 4218);

    $action = 0;
    $ctrlno = 1;
    $rrqty2 = 2;
    $unit = 3;
    $rrqty = 4;
    $uom = 5;
    $uom2 = 6;
    $rrcost = 7;
    $disc = 8;
    $cost = 9;
    $ext = 10;
    $rqcd = 11;
    $basepending = 12;
    $uom3 = 13;
    $qa = 14;
    $waivedqty = 15;
    $isprefer = 16;
    $canvasstatus = 17;
    $wh = 18;
    $requestorname = 19;
    $purpose = 20;
    $dateneeded = 21;
    $barcode = 22;
    $partno = 23;
    $itemdesc = 24;
    $itemdesc2 = 25;
    $specs = 26;
    $specs2 = 27;
    $rem1 = 28;
    $clientname = 29;
    $duration = 30;
    $sanodesc = 31;
    $sono = 32;
    $department = 33;
    $category = 34;
    $rem = 35;
    $carem = 36;
    $amt1 = 37;
    $amt2 = 38;
    $ref = 39;
    $ismanual = 40;
    $void = 41;
    $itemname = 42;


    $headgridbtns = ['itemvoiding', 'viewref', 'viewdiagram']; //itemqtyvoiding


    if ($cd_btnvoid_access == 0) {
      unset($headgridbtns[0]);
    }

    $tab = [
      $this->gridname => [
        'gridcolumns' => [
          'action', 'ctrlno', 'rrqty2', 'unit', 'rrqty', 'uom', 'uom2', 'rrcost', 'disc', 'cost', 'ext', 'rqcd', 'basepending', 'uom3',
          'qa', 'waivedqty', 'isprefer', 'canvasstatus', 'wh', 'requestorname', 'purpose', 'dateneeded', 'barcode', 'partno', 'itemdesc', 'itemdesc2',
          'specs', 'specs2', 'rem1', 'clientname', 'duration', 'sanodesc', 'sono', 'department', 'category',
          'rem', 'carem', 'amt1', 'amt2', 'ref', 'ismanual', 'void', 'itemname'
        ],
        'computefield' => ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'disc' => 'disc', 'total' => 'ext'],
        'headgridbtns' => $headgridbtns
      ]
    ];
    if ($this->othersClass->checkAccess($config['params']['user'], 4481)) {
      $tab['stockinfotab'] = ['action' => 'tableentry', 'lookupclass' => 'tabstockinfo', 'label' => 'UPDATE DETAILS', 'checkchanges' => 'tableentry'];
    }
    $tab['stathistorytab'] = ['action' => 'tableentry', 'lookupclass' => 'tabstathistory', 'label' => 'REVISION REMARKS', 'checkchanges' => 'tableentry'];
    $stockbuttons = ['save', 'delete', 'showbalance', 'sortline'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0]['inventory']['descriptionrow'] = ['itemname', 'partno', 'Itemname'];

    if (!$access['changeamt']) {
      $obj[0]['inventory']['columns'][$qa]['readonly'] = true;
      $obj[0]['inventory']['columns'][$rem]['readonly'] = true;
    }

    $obj[0]['inventory']['columns'][$action]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px';
    $obj[0]['inventory']['columns'][$rrqty]['style'] = 'width: 120px;whiteSpace: normal;min-width:120px;max-width:120px';
    $obj[0]['inventory']['columns'][$qa]['style'] = 'width: 90px;whiteSpace: normal;min-width:90px;max-width:90px';
    $obj[0]['inventory']['columns'][$ext]['style'] = 'text-align:right; width: 120px;whiteSpace: normal;min-width:120px;max-width:120px';

    $obj[0]['inventory']['columns'][$itemdesc]['style'] = 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px';
    $obj[0]['inventory']['columns'][$itemname]['style'] = 'width: 0px;whiteSpace: normal;min-width:0px;max-width:0px';
    $obj[0]['inventory']['columns'][$purpose]['style'] = 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px';
    $obj[0]['inventory']['columns'][$dateneeded]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px';
    $obj[0]['inventory']['columns'][$clientname]['style'] = 'width: 300px;whiteSpace: normal;min-width:300px;max-width:300px';
    $obj[0]['inventory']['columns'][$department]['style'] = 'width: 1500px;whiteSpace: normal;min-width:150px;max-width:150px';
    $obj[0]['inventory']['columns'][$sanodesc]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px';
    $obj[0]['inventory']['columns'][$sono]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px';

    $obj[0]['inventory']['columns'][$rem1]['style'] = 'width: 300px;whiteSpace: normal;min-width:300px;max-width:300px';
    $obj[0]['inventory']['columns'][$rem]['style'] = 'width: 300px;whiteSpace: normal;min-width:300px;max-width:300px';

    $obj[0]['inventory']['columns'][$uom]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px';
    $obj[0]['inventory']['columns'][$barcode]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px';

    $obj[0]['inventory']['columns'][$rrqty2]['type'] = 'label';
    $obj[0]['inventory']['columns'][$purpose]['type'] = 'label';
    $obj[0]['inventory']['columns'][$unit]['type'] = 'label';
    $obj[0]['inventory']['columns'][$rem]['type'] = 'label';
    $obj[0]['inventory']['columns'][$partno]['type'] = 'label';
    $obj[0]['inventory']['columns'][$dateneeded]['type'] = 'label';
    $obj[0]['inventory']['columns'][$clientname]['type'] = 'label';
    $obj[0]['inventory']['columns'][$sono]['type'] = 'coldel';
    $obj[0]['inventory']['columns'][$sanodesc]['type'] = 'label';
    $obj[0]['inventory']['columns'][$department]['type'] = 'label';
    $obj[0]['inventory']['columns'][$duration]['type'] = 'label';

    $obj[0]['inventory']['columns'][$rem1]['type'] = 'textarea';
    $obj[0]['inventory']['columns'][$rem]['type'] = 'textarea';

    $obj[0]['inventory']['columns'][$rem1]['readonly'] = true;
    $obj[0]['inventory']['columns'][$carem]['readonly'] = true;
    $obj[0]['inventory']['columns'][$carem]['type'] = 'label';
    $obj[0]['inventory']['columns'][$category]['type'] = 'label';
    $obj[0]['inventory']['columns'][$cost]['type'] = 'input';
    $obj[0]['inventory']['columns'][$cost]['readonly'] = true;

    $obj[0]['inventory']['columns'][$ismanual]['checkfield'] = 'ismanual2';

    $obj[0]['inventory']['columns'][$barcode]['type'] = 'lookup';
    $obj[0]['inventory']['columns'][$barcode]['action'] = 'lookupbarcode';
    $obj[0]['inventory']['columns'][$barcode]['lookupclass'] = 'gridbarcode';

    $obj[0]['inventory']['columns'][$wh]['type'] = 'coldel';

    $obj[0]['inventory']['columns'][$rrqty2]['label'] = 'Request Qty';
    $obj[0]['inventory']['columns'][$rrqty]['label'] = 'Canvass Qty';
    $obj[0]['inventory']['columns'][$uom]['label'] = 'Canvass UOM';
    $obj[0]['inventory']['columns'][$unit]['label'] = 'Request UOM';
    $obj[0]['inventory']['columns'][$uom2]['label'] = 'Ref. Conversion UOM';
    $obj[0]['inventory']['columns'][$uom3]['label'] = 'Reference Conversion';
    $obj[0]['inventory']['columns'][$cost]['label'] = 'Base Price';
    $obj[0]['inventory']['columns'][$clientname]['label'] = 'Customer';
    $obj[0]['inventory']['columns'][$rem1]['label'] = 'Requestor Notes';
    $obj[0]['inventory']['columns'][$ismanual]['label'] = 'Manually added';

    $obj[0]['inventory']['columns'][$amt1]['label'] = 'Freight Fees';
    $obj[0]['inventory']['columns'][$amt2]['label'] = 'Installation Fees';

    $obj[0]['inventory']['columns'][$amt1]['type'] = 'coldel';
    $obj[0]['inventory']['columns'][$amt2]['type'] = 'coldel';
    $obj[0]['inventory']['columns'][$ref]['lookupclass'] = 'refprati';

    $obj[0]['inventory']['columns'][$uom2]['lookupclass'] = 'uomstock2';
    $obj[0]['inventory']['columns'][$uom2]['type'] = 'lookup';
    $obj[0]['inventory']['columns'][$uom2]['action'] = 'lookupuom';

    $obj[0]['inventory']['columns'][$uom3]['lookupclass'] = 'uomstock3';
    $obj[0]['inventory']['columns'][$uom3]['type'] = 'lookup';
    $obj[0]['inventory']['columns'][$uom3]['action'] = 'lookupuom';

    if (!$waived_prqty_access) {
      $obj[0]['inventory']['columns'][$waivedqty]['type'] = 'coldel';
    }

    $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {

    $tbuttons = ['pendingpr'];
    array_push($tbuttons, 'additem', 'quickadd', 'saveitem', 'deleteallitem');

    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['label'] = 'PR';
    $obj[0]['action'] = 'pendingprdetail';
    $obj[0]['lookupclass'] = 'pendingprdetail';
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['docno', 'client', 'clientname', 'shipto'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.label', 'Vendor');
    data_set($col1, 'docno.label', 'Transaction#');

    $fields = [['dateid', 'terms'], 'due', 'dwhname', 'statname'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'statname.lookupclass', 'lookup_procid');
    data_set($col2, 'statname.label', 'Procurement');
    data_set($col2, 'statname.required', false);


    $fields = [['yourref', 'ourref'], ['cur', 'forex'], 'paymentname'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'paymentname.required', false);
    data_set($col3, 'yourref.label', 'PO Type');
    data_set($col3, 'yourref.class', 'sbccsreadonly');

    $fields = ['rem',  'isinvoice', 'isadv', 'iscanvassonly', 'forrevision', 'forchecking', 'forposting'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'forchecking.confirmlabel', 'Do you want to tag this canvass sheet to For Checking?');
    data_set($col4, 'forchecking.confirm', true);
    data_set($col4, 'forposting.label', 'DONE CHECKING');
    data_set($col4, 'forposting.access', 'donechecking');

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
    $data[0]['shipto'] = '';
    $data[0]['ourref'] = '';
    $data[0]['rem'] = '';
    $data[0]['terms'] = '';
    $data[0]['ddeptname'] = '';
    $data[0]['deptid'] = '0';
    $data[0]['dept'] = '';
    $data[0]['forex'] = 1;
    $data[0]['dbranchname'] = '';
    $data[0]['branch'] = 0;
    $data[0]['branchcode'] = '';
    $data[0]['cur'] = $this->companysetup->getdefaultcurrency($params);
    $data[0]['wh'] = $this->companysetup->getwh($params);
    $name = $this->coreFunctions->datareader("select clientname as value from client where client='" . $data[0]['wh'] . "'");
    $data[0]['whname'] = $name;
    $data[0]['isadv'] = '0';
    $data[0]['iscanvassonly'] = '0';
    $data[0]['statname'] = '';
    $data[0]['procid'] = '0';
    $data[0]['paymentname'] = '';
    $data[0]['paymentid'] = 0;
    $data[0]['trnxtype'] = '';
    $data[0]['isinvoice'] = '0';
    if ($params['adminid'] != 0) {
      $data[0]['trnxtype'] = $this->coreFunctions->getfieldvalue("client", "deptcode", "clientid=?", [$params['adminid']]);
      $data[0]['yourref'] = $data[0]['trnxtype'];
    }
    return $data;
  }

  public function openhead($config)
  {
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];

    $table = $this->head;
    $htable = $this->hhead;
    $tablenum = $this->tablenum;
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
         left(head.dateid,10) as dateid, 
         head.clientname,
         head.address, 
         head.shipto, 
         date_format(head.createdate,'%Y-%m-%d') as createdate,
         head.rem,
         head.agent,
         agent.clientname as agentname,
         head.wh as wh,
         head.procid,
         trx.status as statname,
         warehouse.clientname as whname,
         '' as dwhname, 
         left(head.due,10) as due, cast(ifnull(info.isadv,0) as char) as isadv, cast(ifnull(head.iscanvassonly,0) as char) as iscanvassonly,
         client.groupid,ifnull(d.client,'') as dept,ifnull(d.clientname,'') as deptname,head.deptid,
         '' as ddeptname,head.branch,ifnull(b.clientname,'') as branchname,ifnull(b.client,'') as branchcode,'' as dbranchname
         , headinfo.paymentid, om.paymenttype as paymentname,cast(ifnull(info.isinvoice,0) as char) as isinvoice
           ";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as agent on agent.client = head.agent
        left join client as d on d.clientid = head.deptid
        left join client as b on b.clientid = head.branch
        left join headinfotrans as info on info.trno=head.trno
        left join trxstatus as trx on trx.line=head.procid
        left join headinfotrans as headinfo on headinfo.trno = head.trno
        left join othermaster as om on om.line = headinfo.paymentid
        where head.trno = ? and num.center = ? 
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as agent on agent.client = head.agent
        left join client as d on d.clientid = head.deptid
        left join client as b on b.clientid = head.branch
        left join hheadinfotrans as info on info.trno=head.trno
        left join trxstatus as trx on trx.line=head.procid
        left join hheadinfotrans as headinfo on headinfo.trno = head.trno
        left join othermaster as om on om.line = headinfo.paymentid 
        where head.trno = ? and num.center=? ";

    return $this->coreFunctions->opentable($qry, [$trno, $center, $trno, $center]);
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
      $filterid = " and info.trnxtype = '" . $trnxtype . "' ";
    }
    $head = [];
    $islocked = $this->othersClass->islocked($config);
    $isposted = $this->othersClass->isposted($config);
    $statid = $this->othersClass->getstatid($config);

    $table = $this->head;
    $htable = $this->hhead;
    $tablenum = $this->tablenum;
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
         left(head.dateid,10) as dateid, 
         client.clientname,
         head.address, 
         head.shipto, 
         date_format(head.createdate,'%Y-%m-%d') as createdate,
         head.rem,
         head.agent,
         agent.clientname as agentname,
         head.wh as wh,
         head.procid,
         trx.status as statname,
         warehouse.clientname as whname,
         '' as dwhname, 
         left(head.due,10) as due, cast(ifnull(info.isadv,0) as char) as isadv, cast(ifnull(head.iscanvassonly,0) as char) as iscanvassonly,
         client.groupid,ifnull(d.client,'') as dept,ifnull(d.clientname,'') as deptname,head.deptid,
         '' as ddeptname,head.branch,ifnull(b.clientname,'') as branchname,ifnull(b.client,'') as branchcode,'' as dbranchname
         , headinfo.paymentid, om.paymenttype as paymentname,info.trnxtype,cast(ifnull(info.isinvoice,0) as char) as isinvoice
           ";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as agent on agent.client = head.agent
        left join client as d on d.clientid = head.deptid
        left join client as b on b.clientid = head.branch
        left join headinfotrans as info on info.trno=head.trno
        left join trxstatus as trx on trx.line=head.procid
        left join headinfotrans as headinfo on headinfo.trno = head.trno
        left join othermaster as om on om.line = headinfo.paymentid
        where head.trno = ? and num.center = ? 
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as agent on agent.client = head.agent
        left join client as d on d.clientid = head.deptid
        left join client as b on b.clientid = head.branch
        left join hheadinfotrans as info on info.trno=head.trno
        left join trxstatus as trx on trx.line=head.procid
        left join hheadinfotrans as headinfo on headinfo.trno = head.trno
        left join othermaster as om on om.line = headinfo.paymentid 
        where head.trno = ? and num.center=? $filterid ";

    $head = $this->coreFunctions->opentable($qry, [$trno, $center, $trno, $center]);
    if (!empty($head)) {
      $stock = $this->openstock($trno, $config);
      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      $msg = 'Data was successfully fetched.';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }

      $hideobj = [];
      $hideobj['forchecking'] = false;
      $hideobj['forposting'] = true;
      $hideobj['forrevision'] = true;
      switch ($statid) {
        case 45:
          $hideobj['forchecking'] = true;
          $hideobj['forposting'] = false;
          $hideobj['forrevision'] = false;
          break;
        case 39:
          $hideobj['forchecking'] = true;
          $hideobj['forposting'] = true;
          break;
        default:
          if ($isposted) {
            $hideobj['forchecking'] = true;
            $hideobj['forrevision'] = true;
          }
          break;
      }
      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
      return  ['head' => $head, 'griddata' => ['inventory' => $stock], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg, 'hideobj' => $hideobj];
    } else {
      $head[0]['trno'] = 0;
      $head[0]['docno'] = '';
      return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => []], 'msg' => 'Data head fetch failed.'];
    }
  }

  public function updatehead($config, $isupdate)
  {
    $head = $config['params']['head'];
    $data = [];
    $dataothers = [];
    if ($isupdate) {
      unset($this->fields['docno']);
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
    $data['due'] = $this->othersClass->computeterms($data['dateid'], $data['due'], $data['terms']);
    if ($isupdate) {
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head, $data);
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['client'] . ' - ' . $head['clientname']);
    }

    $dataothers['trno'] = $head['trno'];
    $dataothers['isadv'] = $head['isadv'];
    $dataothers['isinvoice'] = $head['isinvoice'];
    $dataothers['paymentid'] = $head['paymentid'];
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
      return ['status' => false, 'msg' => 'Post failed, Please check, some item have zero quantity.'];
    }
    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    if ($this->othersClass->isposted($config)) {
      return ['status' => false, 'msg' => 'Post failed; already posted.'];
    }

    $paymentid = $this->coreFunctions->getfieldvalue("headinfotrans", "paymentid", "trno=?", [$trno], '', true);
    if ($paymentid == 0) {
      return ['status' => false, 'msg' => 'Post failed; Payment type is required.'];
    }

    $procid = $this->coreFunctions->getfieldvalue($this->head, "procid", "trno=?", [$trno], '', true);
    if ($procid == 0) {
      return ['status' => false, 'msg' => 'Post failed, Procurement is required.'];
    }

    $statid = $this->othersClass->getstatid($config);
    if ($statid != 39) {
      return ['status' => false, 'msg' => 'Post failed, Not done checking.'];
    }

    $nocat = $this->coreFunctions->opentable("select trno from " . $this->stock . " where trno=? and catid=0", [$trno]);
    if (!empty($nocat)) {
      return ['status' => false, 'msg' => 'Post failed; Category missing. Please ask your system provider.'];
    }

    //checking of approved qty
    $check = $this->checkapprovedqty($config);
    if (!$check['status']) {
      return $check;
    }

    //checking
    $checkreq = $this->coreFunctions->opentable("select ref from cdstock where trno =? and ismanual =1 and reqtrno=0 order by ref limit 1 ", [$trno]);

    if (!empty($checkreq)) {
      return ['status' => false, 'msg' => 'Post failed. No reference for manually added items.'];
    }

    //for glhead
    $qry = "insert into " . $this->hhead . "(trno,doc,docno,client,clientname,address,shipto,dateid,
      terms,rem,forex,yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,agent,wh,procid,due,cur,deptid,branch,prtrno,iscanvassonly)
      SELECT head.trno,head.doc, head.docno,head.client, head.clientname, head.address,head.shipto,
      head.dateid as dateid, head.terms, head.rem, head.forex,head.yourref, head.ourref,
      head.createdate,head.createby,head.editby,head.editdate, head.lockdate,head.lockuser,head.agent,head.wh,head.procid,
      head.due,head.cur,head.deptid,head.branch,head.prtrno,head.iscanvassonly FROM " . $this->head . " as head left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno
      where head.trno=? limit 1";
    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($posthead) {

      if (!$this->othersClass->postingheadinfotrans($config)) {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Error in posting headinfo.'];
      }
      if (!$this->othersClass->postingstockinfotrans($config)) {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from hheadinfotrans where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Error in posting stockinfo.'];
      }
      $qry = "insert into " . $this->hstock . "(trno,line,itemid,uom, whid,loc,ref,disc,cost,qty,void,rrcost,rrqty,ext, encodeddate,qa,rem,encodedby,editdate,editby,refx,linex, projectid,reqtrno,reqline,suppid,sano,deptid,rrqty2,sortline,ismanual,isprefer,rrqty3,catid,waivedqty,rqcd)
        SELECT trno, line, itemid, uom,whid,loc,ref,disc,cost, qty,void,rrcost, rrqty, ext, encodeddate,qa,rem,encodedby,editdate,editby,refx,linex, projectid ,reqtrno,reqline,suppid,sano,deptid,rrqty2,sortline,ismanual,isprefer,rrqty3,catid,waivedqty,rqcd
        FROM " . $this->stock . " where trno =?";
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        //update transnum
        $date = $this->othersClass->getCurrentTimeStamp();
        $data = ['postdate' => $date, 'postedby' => $config['params']['user'], 'statid' => 12];
        $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
        $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from headinfotrans where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from stockinfotrans where trno=?", "delete", [$trno]);

        $this->coreFunctions->execqry("update " . $this->hstock . " as stock
          left join hprstock as prs on prs.trno=stock.reqtrno and prs.line=stock.reqline
          set prs.statrem='Canvass Sheet - Posted',prs.statdate='" . $this->othersClass->getCurrentTimeStamp() . "' where stock.trno=" . $trno, 'update');

        // $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
        $this->logger->sbcstatlog($trno, $config, 'HEAD', 'POSTED');
        $this->othersClass->sbctransferlog($trno, $config, $this->htablelogs);
        return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
      } else {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from hheadinfotrans where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from hstockinfotrans where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Error in posting stock.'];
      }
    } else {
      return ['status' => false, 'msg' => 'Error in posting head.'];
    }
  } //end function

  public function unposttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $qry = "select trno from " . $this->hstock . " where trno=? and (qa>0 or void<>0)";
    $data = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($data)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Unposting failed; either the item was already served or it was voided.'];
    }

    $qry = "select trno from " . $this->hstock . " where trno=? and status<>0";
    $data = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($data)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Unposting failed; either already approved or rejected.'];
    }

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    $qry = "insert into " . $this->head . "(trno,doc,docno,client,clientname,address,shipto,dateid,terms,rem,forex,
  yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,wh,procid,due,cur,deptid,branch,prtrno,iscanvassonly)
  select head.trno, head.doc, head.docno, head.client, head.clientname, head.address, head.shipto,
  head.dateid as dateid, head.terms, head.rem, head.forex, head.yourref, head.ourref, head.createdate,
  head.createby, head.editby, head.editdate, head.lockdate, head.lockuser,head.wh,head.procid,head.due,head.cur,head.deptid,head.branch,
  head.prtrno,head.iscanvassonly
  from (" . $this->hhead . " as head left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno)
  where head.trno=? limit 1";
    //head
    if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {

      if (!$this->othersClass->unpostingheadinfotrans($config)) {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Error on unposting headinfo.'];
      }

      if (!$this->othersClass->unpostingstockinfotrans($config)) {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry("delete from headinfotrans where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Unposting failed; stockinfo problems.'];
      }

      $qry = "insert into " . $this->stock . "(
      trno,line,itemid,uom,whid,loc,ref,disc,cost,qty,void,rrcost,rrqty,ext,rem,encodeddate,qa,encodedby,editdate,editby,refx,linex, projectid,reqtrno,reqline,suppid,sano,deptid,rrqty2,sortline,ismanual,isprefer,rrqty3,catid,waivedqty,rqcd)
      select trno, line, itemid, uom,whid,loc,ref,disc,cost, qty,void, rrcost, rrqty, ext,rem, encodeddate, qa, encodedby, editdate, editby,refx,linex, projectid,reqtrno,reqline,suppid,sano,deptid,rrqty2,sortline,ismanual,isprefer,rrqty3,catid,waivedqty,rqcd
      from " . $this->hstock . " where trno=?";
      //stock
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null, statid=39 where trno=?", 'update', [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from hheadinfotrans where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from hstockinfotrans where trno=?", "delete", [$trno]);
        // $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
        $this->logger->sbcstatlog($trno, $config, 'HEAD', 'DONE CHECKING');
        return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
      } else {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry("delete from headinfotrans where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from stockinfotrans where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Unposting failed; stock problems.'];
      }
    }
  } //end function

  private function getstockselect($config)
  {
    $sqlselect = "select item.brand as brand,
    ifnull(mm.model_name,'') as model,
    ifnull(item.itemid,0) as itemid,
    item.partno,
    stock.trno,
    stock.line,
    stock.sortline,
    stock.refx,
    stock.linex,
    item.barcode,
    item.itemname,
    stock.uom,
    si.uom2,
    si.uom3,
    stock.cost,
    stock.qty as qty,
    FORMAT(stock.rrcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrcost,
    FORMAT(stock.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as rrqty,
    FORMAT(stock.ext,2) as ext,
    FORMAT(stock.rrqty2," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as rrqty2,
    left(stock.encodeddate,10) as encodeddate,
    stock.disc,
    case when stock.void=0 then 'false' else 'true' end as void,
    case when stock.ismanual=0 then 'false' else 'true' end as ismanual,
    case when stock.waivedqty=0 then 'false' else 'true' end as waivedqty,
    'true' as ismanual2,
    case when stock.isprefer=0 then 'false' else 'true' end as isprefer,
    round((stock.qty-stock.qa)/ case when ifnull(uom2.factor,0)<>0 then uom2.factor when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
    CONCAT(CAST(FORMAT(stock.rqcd," . $this->companysetup->getdecimal('qty', $config['params']) . ") as CHAR),' ',stock.uom) as rqcd,
    FORMAT((stock.rrqty2 * case when ifnull(uom3.factor,0)=0 then 1 else uom3.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as basepending,
    stock.ref,
    stock.whid,
    warehouse.client as wh,
    warehouse.clientname as whname,
    ifnull(cust.clientname,'') as clientname,
    stock.loc,
    item.brand,
    case 
      when stock.status = 0 then 'Pending'
      when stock.status = 1 then 'Approved'
      when stock.status = 2 then 'Rejected'
    end as canvasstatus,
    stock.rem, stock.suppid, stock.deptid, stock.sano, 
    FORMAT(si.amt1," . $this->companysetup->getdecimal('currency', $config['params']) . ") as amt1,
    FORMAT(si.amt2," . $this->companysetup->getdecimal('currency', $config['params']) . ") as amt2,
    ifnull(uom.factor,1) as uomfactor,
    '' as bgcolor,
    if(stock.status=1,if(stock.void=1,'bg-red-2','bg-yellow-2'),'') as qacolor,
    case when stock.void=0 then '' else 'bg-red-2' end as errcolor,
    item.subcode, item.partno, round(item.dqty, " . $this->companysetup->getdecimal('qty', $config['params']) . ") as boxcount, 
    ifnull(info.itemdesc,'') as itemdesc, ifnull(info.itemdesc2,'') as itemdesc2, ifnull(info.unit,'') as unit, ifnull(info.specs,'') as specs, ifnull(info.specs2,'') as specs2, ifnull(info.purpose,'') as purpose, date(info.dateneeded) as dateneeded,
    ifnull(info.requestorname,'') as requestorname, ifnull(info.rem,'') as rem1,stock.reqtrno,stock.reqline, ifnull(sa.sano,'') as sanodesc, si.sono, ifnull(dept.clientname,'') as department,
    ifnull(d.duration,'') as duration, stock.catid, ifnull(cat.category,'') as category,info.ctrlno,
    cast(SUBSTRING_INDEX(info.ctrlno, '-', 1) as unsigned ) as ctrlnodoc,cast(SUBSTRING_INDEX(info.ctrlno, '-', -1) as unsigned ) as ctrlnoline";
    return $sqlselect;
  }

  public function openstock($trno, $config)
  {

    $sqlselect = $this->getstockselect($config);

    $qry = $sqlselect . ",'' as carem
  FROM $this->stock as stock
  left join item on item.itemid=stock.itemid 
  left join stockinfotrans as si on si.trno=stock.trno and si.line=stock.line
  left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
  left join model_masterfile as mm on mm.model_id = item.model 
  left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
  left join client as warehouse on warehouse.clientid=stock.whid 
  left join client as cust on cust.clientid=stock.suppid 
  left join clientsano as sa on sa.line=stock.sano 
  left join client as dept on dept.clientid=stock.deptid 
  left join reqcategory as cat on cat.line=stock.catid 
  left join duration as d on d.line=info.durationid
  left join uomlist as uom3 on uom3.uom=si.uom3 and uom3.isconvert=1
  left join uomlist as uom2 on uom2.uom=si.uom2 and uom2.isconvert=1
  where stock.trno =?
  UNION ALL
  " . $sqlselect . ",si.carem
  FROM $this->hstock as stock
  left join item on item.itemid=stock.itemid left join hstockinfotrans as si on si.trno=stock.trno and si.line=stock.line
  left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
  left join model_masterfile as mm on mm.model_id = item.model left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
  left join client as warehouse on warehouse.clientid=stock.whid left join client as cust on cust.clientid=stock.suppid 
  left join clientsano as sa on sa.line=stock.sano 
  left join client as dept on dept.clientid=stock.deptid
  left join reqcategory as cat on cat.line=stock.catid
  left join duration as d on d.line=info.durationid
  left join uomlist as uom3 on uom3.uom=si.uom3 and uom3.isconvert=1
  left join uomlist as uom2 on uom2.uom=si.uom2 and uom2.isconvert=1
  where stock.trno =? order by ctrlnodoc,ctrlnoline"; // and stock.ismanual=0;
    $stock = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $stock;
  } //end function

  public function openstockline($config)
  {
    $sqlselect = $this->getstockselect($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = $sqlselect . ",'' as carem
    FROM $this->stock as stock
    left join item on item.itemid=stock.itemid left join stockinfotrans as si on si.trno=stock.trno and si.line=stock.line
    left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
    left join model_masterfile as mm on mm.model_id = item.model left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid left join client as cust on cust.clientid=stock.suppid 
    left join clientsano as sa on sa.line=stock.sano left join client as dept on dept.clientid=stock.deptid 
    left join duration as d on d.line=info.durationid
    left join reqcategory as cat on cat.line=stock.catid
    left join stockinfotrans as cinfo on cinfo.trno=stock.trno and cinfo.line=stock.line
    left join uomlist as uom3 on uom3.uom=si.uom3 and uom3.isconvert=1
    left join uomlist as uom2 on uom2.uom=si.uom2 and uom2.isconvert=1
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
      case 'getprsummary':
        return $this->getprsummary($config);
        break;
      case 'getprdetails':
        return $this->getprdetails($config);
        break;
      case 'getsqsummary':
        return $this->getsqsummary($config);
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
      case 'forchecking':
        return $this->forchecking($config);
        break;
      case 'forposting':
        return $this->forposting($config);
        break;
      case 'duplicatedoc':
        return $this->othersClass->duplicateTransaction($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }


  public function forchecking($config)
  {
    $trno = $config['params']['trno'];
    $msg = "";
    $status = true;

    if ($this->othersClass->isposted2($trno, $this->tablenum)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Already posted.'];
    }

    $stock = $this->coreFunctions->opentable("select trno from " . $this->stock . " where trno=?", [$trno]);
    if (empty($stock)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Can`t proceed, must have valid items.'];
    }



    // 2024.03.15 - remove
    //checking if request vs canvass qty
    // $check = $this->checkprcanvassqty($config);

    //checking reference conversion
    $check = $this->checkuomconversion($config);
    if (!$check['status']) {
      return $check;
    }

    $this->coreFunctions->sbcupdate($this->tablenum, ['statid' => 45], ['trno' => $trno]);
    // $this->logger->sbcwritelog($trno, $config, 'HEAD', 'TAGGED FOR CHECKING');
    $this->logger->sbcstatlog($trno, $config, 'HEAD', 'TAGGED FOR CHECKING');

    return ['trno' => $trno, 'status' => $status, 'msg' => $msg, 'backlisting' => true];
  }

  public function forposting($config)
  {
    $trno = $config['params']['trno'];
    $msg = "";
    $status = true;

    if ($this->othersClass->isposted2($trno, $this->tablenum)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Already posted.'];
    }

    $stock = $this->coreFunctions->opentable("select trno from " . $this->stock . " where trno=?", [$trno]);
    if (empty($stock)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Can`t proceed, must have valid items.'];
    }

    // $qty = $this->coreFunctions->opentable("select rrqty2,rrqty from " . $this->stock . " where rrqty > rrqty2 and trno=? and waivedqty=0", [$trno]);


    //checking reference conversion
    $check = $this->checkuomconversion($config);
    if (!$check['status']) {
      return $check;
    }

    //checking if request vs canvass qty
    $check = $this->checkprcanvassqty($config);
    if (!$check['status']) {
      return $check;
    }

    $access = $this->othersClass->checkAccess($config['params']['user'], 4010);
    if (!$access) {
      return ['status' => false, 'msg' => 'Please advice your administrator regarding this restriction.'];
    }
    //checking of approved qty
    $check = $this->checkapprovedqty($config);
    if (!$check['status']) {
      return $check;
    }

    $this->coreFunctions->sbcupdate($this->tablenum, ['statid' => 39], ['trno' => $trno]);
    // $this->logger->sbcwritelog($trno, $config, 'HEAD', 'DONE CHECKING');
    $this->logger->sbcstatlog($trno, $config, 'HEAD', 'DONE CHECKING');

    return ['trno' => $trno, 'status' => $status, 'msg' => $msg, 'backlisting' => true];
  }

  public function checkprcanvassqty($config)
  {
    $trno = $config['params']['trno'];
    $stock = $this->coreFunctions->opentable("select s.qty, s.rrqty, s.rrqty2 as prqty, s.reqtrno, s.reqline, info.itemdesc, s.rrqty2 * ifnull(uom3.factor,1) as basetotal, uom3.factor, s.uom
              from cdstock as s left join uom on uom.itemid=s.itemid and uom.uom=s.uom left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline
              left join stockinfotrans as si on si.trno=s.trno and si.line=s.line 
              left join uomlist as uom3 on uom3.uom=si.uom3 and uom3.isconvert=1
              where s.waivedqty=0 and s.trno=? and s.qty > s.rrqty2 * ifnull(uom3.factor,1)", [$trno]);
    if (empty($stock)) {
      return ['status' => true];
    } else {
      return ['status' => false, 'msg' => 'Canvass quantity must not be greater than request quantity (' . $stock[0]->itemdesc . '). Request base quantity:' . number_format($stock[0]->basetotal, 2) . ",  Canvass base quantity " . number_format($stock[0]->qty, 2) . " " . $stock[0]->uom];
    }
  }


  public function checkuomconversion($config)
  {
    $trno = $config['params']['trno'];
    $stock = $this->coreFunctions->opentable("select s.reqtrno, s.reqline, info.itemdesc, si.uom3
              from cdstock as s left join uom on uom.itemid=s.itemid and uom.uom=s.uom left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline
              left join stockinfotrans as si on si.trno=s.trno and si.line=s.line 
              left join uomlist as uom3 on uom3.uom=si.uom3 and uom3.isconvert=1
              where s.waivedqty=0 and s.void=0 and s.trno=?", [$trno]);

    foreach ($stock as $key => $value) {

      $isposteduom = $this->coreFunctions->opentable(
        "select h.docno, si.uom3 from hcdstock as s left join hcdhead as h on h.trno=s.trno 
                left join hstockinfotrans as si on si.trno=s.trno and si.line=s.line left join uomlist as uom3 on uom3.uom=si.uom3 and uom3.isconvert=1 
                where s.void=0 and s.status<>2 and s.reqtrno=? and s.reqline=? and s.trno<>?",
        [$value->reqtrno, $value->reqline, $trno]
      );

      if (!empty($isposteduom)) {
        if ($isposteduom[0]->uom3 != $value->uom3) {
          if ($isposteduom[0]->uom3 == "") {
            return ['status' => true];
          }
          return ['status' => false, 'msg' => 'Unable to set reference conversion of ' . $value->uom3 . ' for item ' . $value->itemdesc . ', there is an existing posted Canvass ' . $isposteduom[0]->docno . ' used the reference conversion of ' . $isposteduom[0]->uom3];
        }
      }
    }

    return ['status' => true];
  }


  public function checkapprovedqty($config)
  {
    $trno = $config['params']['trno'];
    //checking of approved qty
    // $stock = $this->coreFunctions->opentable("select s.qty, s.rrqty, s.rrqty2 * ifnull(uom.factor,1) as prqty, s.reqtrno, s.reqline, info.itemdesc
    // from cdstock as s left join uom on uom.itemid=s.itemid and uom.uom=s.uom left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline where s.waivedqty=0 and s.trno=?", [$trno]);

    // $stock = $this->coreFunctions->opentable("select s.qty, s.rrqty, s.rrqty2 as prqty, s.reqtrno, s.reqline, info.itemdesc
    //                                           from cdstock as s left join uom on uom.itemid=s.itemid and uom.uom=s.uom left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline where s.waivedqty=0 and s.trno=?", [$trno]);


    $stock = $this->coreFunctions->opentable("select s.qty, s.rrqty, s.rrqty2 as prqty, s.reqtrno, s.reqline, info.itemdesc, s.rrqty2 * ifnull(uom3.factor,1) as basetotal, s.uom
              from cdstock as s left join uom on uom.itemid=s.itemid and uom.uom=s.uom left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline
              left join stockinfotrans as si on si.trno=s.trno and si.line=s.line 
              left join uomlist as uom3 on uom3.uom=si.uom3 and uom3.isconvert=1
              where s.waivedqty=0 and s.void=0 and s.status<>2 and s.trno=?", [$trno]);

    foreach ($stock as $key => $value) {
      $approvedqty = $this->getApprovedQty($value->reqtrno, $value->reqline, $trno);

      if ($approvedqty != 0) {

        //   return ['status' => false, 'msg' => 'Request quantity of ' . number_format($value->prqty, 2) . ' for item has already been approved. You are not allow to post another canvass sheet.'];
        // }
        if (($approvedqty + $value->qty) > $value->basetotal) {
          $pendingqty = $value->basetotal - $approvedqty;
          return ['status' => false, 'msg' => "Request quantity of " . number_format($value->prqty, 2) . " for item " . $value->itemdesc . " has already been approved. You are not allow to post another canvass sheet. Pending base quantity is " . $pendingqty . " " . $value->uom];
        }
      }
    }
    return ['status' => true];
  }

  private function getApprovedQty($reqtrno, $reqline, $trno)
  {
    return $this->coreFunctions->datareader("select ifnull(sum(s.qty),0) as value from hcdstock as s 
    where s.approveddate is not null and s.status=1 and s.void=0 and s.waivedqty=0 and s.reqtrno=? and s.reqline=? and trno<>?", [$reqtrno, $reqline, $trno], '', true);
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
          $msg1 = ' Out of stock.';
        } else {
          $msg2 = ' Quantity Received is greater than PR Quantity.';
        }
      }
    }

    if (!$isupdate) {
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
          $msg1 = ' Out of stock.';
        } else {
          $msg2 = ' Quantity Received is greater than PO Quantity.';
        }
      }
    }
    if ($isupdate) {
      return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    } else {
      return ['inventory' => $data, 'status' => true, 'msg' => 'Please check that some items have a zero quantity. (' . $msg1 . ' / ' . $msg2 . ')'];
    }
  } //end function

  public function addallitem($config)
  {
    $msg = '';
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $result = $this->additem('insert', $config);
      // $this->coreFunctions->LogConsole($result['status']);
      if ($result['status']) {
      } else {
        $msg .= $result['msg'];
      }
    }

    $data = $this->openstock($config['params']['trno'], $config);
    if ($msg == '') {
      $msg = 'Successfully saved.';
    }
    return ['inventory' => $data, 'status' => true, 'msg' => $msg];
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
    $item = $this->coreFunctions->opentable("select item.itemid,0 as amt,item.disc,'' as loc,'" . $wh . "' as wh, 1 as qty, uom from item where barcode=?", [$barcode]);
    $item = json_decode(json_encode($item), true);

    if (!empty($item)) {
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
    $uom = $config['params']['data']['uom'];
    $itemid = $config['params']['data']['itemid'];
    $trno = $config['params']['trno'];
    $disc = $config['params']['data']['disc'];
    $wh = $config['params']['data']['wh'];
    $loc = $config['params']['data']['loc'];
    $void = 'false';
    if (isset($config['params']['data']['void'])) {
      $void = $config['params']['data']['void'];
    }
    $sorefx = isset($config['params']['data']['sorefx']) ? $config['params']['data']['sorefx'] : 0;
    $solinex = isset($config['params']['data']['solinex']) ? $config['params']['data']['solinex'] : 0;
    $reqtrno = isset($config['params']['data']['reqtrno']) ? $config['params']['data']['reqtrno'] : 0;
    $reqline = isset($config['params']['data']['reqline']) ? $config['params']['data']['reqline'] : 0;
    $suppid = isset($config['params']['data']['suppid']) ? $config['params']['data']['suppid'] : 0;
    $deptid = isset($config['params']['data']['deptid']) ? $config['params']['data']['deptid'] : 0;
    $sano = isset($config['params']['data']['sano']) ? $config['params']['data']['sano'] : 0;
    $sono = isset($config['params']['data']['sono']) ? $config['params']['data']['sono'] : 0;
    $amt1 = isset($config['params']['data']['amt1']) ? $config['params']['data']['amt1'] : 0;
    $amt2 = isset($config['params']['data']['amt2']) ? $config['params']['data']['amt2'] : 0;
    $rrqty2 = isset($config['params']['data']['rrqty2']) ? $config['params']['data']['rrqty2'] : 0;
    $isprefer = isset($config['params']['data']['isprefer']) ? $config['params']['data']['isprefer'] : 0;
    $catid = isset($config['params']['data']['catid']) ? $config['params']['data']['catid'] : 0;
    $waivedqty = isset($config['params']['data']['waivedqty']) ? $config['params']['data']['waivedqty'] : 0;

    $refx = 0;
    $linex = 0;
    $rem = '';
    $ref = '';
    $projectid = 0;
    $itemdesc = '';
    $ext = 0;

    $uom2 = isset($config['params']['data']['uom2']) ? $config['params']['data']['uom2'] : '';
    $uom3 = isset($config['params']['data']['uom3']) ? $config['params']['data']['uom3'] : '';
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
    if (isset($config['params']['data']['ref'])) {
      $ref = $config['params']['data']['ref'];
    }

    if (isset($config['params']['data']['itemdesc'])) {
      $itemdesc = $config['params']['data']['itemdesc'];
    }

    if ($action == 'insert') {
      if ($itemid  == 0) {
        $itemexist = $this->coreFunctions->getfieldvalue($this->stock, "line", "trno=? and reqtrno=? and reqline=?", [$trno, $refx, $linex], '', true);
        if ($itemexist != 0) {
          return ['status' => false, 'msg' => 'Item was already added ' . $itemdesc . " (" . $ref . ")"];
        }
      } else {
        $itemexist = $this->coreFunctions->getfieldvalue($this->stock, "line", "trno=? and itemid=?", [$trno, $itemid], '', true);
        if ($itemexist != 0) {
          return ['status' => false, 'msg' => 'Item was already added.'];
        }
      }
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
    $amt = $this->othersClass->sanitizekeyfield('amt', $amt);
    $qty = $this->othersClass->sanitizekeyfield('qty', $qty);

    $qry = "select ifnull(item.barcode,'') as barcode, ifnull(item.itemname,0) as itemname,ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
    $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
    $factor = 1;
    if (!empty($item)) {
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
    $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor);
    $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);
    $ext = round($computedata['ext'], $this->companysetup->getdecimal('qty', $config['params']));


    $rqcd = 0;
    $factor3 = $this->coreFunctions->getfieldvalue("uomlist", "factor", "uom=? and isconvert=1", [$uom3], '', true);
    if ($factor3 == 0) {
      $factor3 = 1;
    }
    $factor2 = $this->coreFunctions->getfieldvalue("uomlist", "factor", "uom=? and isconvert=1", [$uom2], '', true);
    if ($factor2 == 0) {
      $factor2 = 1;
    }
    $basetotal = $rrqty2 * $factor3;
    $approvedrq = $this->getApprovedQty($reqtrno, $reqline, $trno);
    $rqcd = ($basetotal - $approvedrq) / $factor2;

    // $this->othersClass->logConsole('basetotal:' . $basetotal . ' - rqcd:' . $rqcd . ' - approved:' . $approvedrq . ' - factor2:' . $factor2);

    $data = [
      'trno' => $trno,
      'line' => $line,
      'itemid' => $itemid,
      'rrcost' => $amt,
      'cost' => $computedata['amt'] * $forex,
      'rrqty' => $qty,
      'rrqty3' => $qty,
      'rrqty2' => $rrqty2,
      'qty' => $computedata['qty'],
      'ext' => $ext,
      'disc' => $disc,
      'whid' => $whid,
      'loc' => $loc,
      'uom' => $uom,
      'void' => $void,
      'refx' => $refx,
      'linex' => $linex,
      'sorefx' => $sorefx,
      'solinex' => $solinex,
      'ref' => $ref,
      'rem' => $rem,
      'reqtrno' => $reqtrno,
      'reqline' => $reqline,
      'suppid' => $suppid,
      'deptid' => $deptid,
      'sano' => $sano,
      'isprefer' => $isprefer,
      'catid' => $catid,
      'waivedqty' => $waivedqty,
      'rqcd' => round($rqcd, $this->companysetup->getdecimal('qty', $config['params']))
    ];

    if ($refx == 0) {
      $data['ismanual'] = 1;
    }
    $data2 = [
      'trno' => $trno,
      'line' => $line,
      'sono' => $sono,
      'amt1' => $amt1,
      'amt2' => $amt2,
      'uom2' => $uom2,
      'uom3' => $uom3,
      'ctrlno' => $ctrlno,
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

    $data2['editdate'] = $current_timestamp;
    $data2['editby'] = $config['params']['user'];
    if ($action == 'insert') {
      $data['encodeddate'] = $current_timestamp;
      $data['encodedby'] = $config['params']['user'];
      $data['sortline'] =  $data['line'];

      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {

        if ($this->coreFunctions->sbcinsert("stockinfotrans", $data2) == 1) {
          $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' Uom:' . $uom);
        }
        if ($data['sorefx'] != 0) {
          $this->coreFunctions->sbcupdate("hqsstock", ['iscanvass' => 1], ['trno' => $sorefx, 'line' => $solinex]);
        }


        $row = $this->openstockline($config);
        return ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
      } else {
        return ['status' => false, 'msg' => 'Add item failed.'];
      }
    } elseif ($action == 'update') {
      $return = true;
      $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);


      $checkstockinfo = $this->coreFunctions->getfieldvalue("stockinfotrans", "trno", "trno=? and line =?", [$trno, $line]);
      if ($checkstockinfo == '') {
        $this->coreFunctions->sbcinsert("stockinfotrans", $data2);
      } else {
        $this->coreFunctions->sbcupdate("stockinfotrans", $data2, ['trno' => $trno, 'line' => $line]);
      }

      if ($data['sorefx'] != 0) {
        $this->coreFunctions->sbcupdate("hqsstock", ['iscanvass' => 1], ['trno' => $sorefx, 'line' => $solinex]);
      }

      if ($refx != 0) {
        if ($this->setserveditemsPR($refx, $linex, $this->hqty) === 0) {
          $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
          $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
          $this->setserveditemsPR($refx, $linex, $this->hqty);
          $return = false;
        }
      }
      if (isset($data['ismanual'])) {
        if ($data['ismanual']) {

          $this->logger->sbcwritelog($trno, $config, 'STOCK', 'Add Suggested item. Line:' . $line);
        }
      }
      return $return;
    }
  } // end function



  public function deleteallitem($config)
  {
    $isallow = true;
    $trno = $config['params']['trno'];
    $data = $this->coreFunctions->opentable('select refx,linex from ' . $this->stock . ' where trno=? and refx<>0', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from stockinfotrans where trno=?', 'delete', [$trno]);
    foreach ($data as $key => $value) {
      $this->setserveditemsPR($data[$key]->refx, $data[$key]->linex, $this->hqty);
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
    $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=? and line=?", 'delete', [$trno, $line]);
    $this->coreFunctions->execqry("delete from stockinfotrans where trno=? and line=?", 'delete', [$trno, $line]);
    if ($data[0]->refx != 0) {
      $this->setserveditemsPR($data[0]->refx, $data[0]->linex, $this->hqty);
    }
    $data = json_decode(json_encode($data), true);
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' barcode:' . $data[0]['barcode'] . ' Qty:' . $data[0]['rrqty'] . ' Amt:' . $data[0]['rrcost'] . ' Disc:' . $data[0]['disc'] . ' wh:' . $data[0]['wh'] . ' ext:' . $data[0]['ext']);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
  } // end function

  public function getprsummary($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "select head.docno, ifnull(item.itemid,0) as itemid,stock.trno, 
      stock.line, ifnull(item.barcode,'') as barcode,stock.uom, stock.cost,
      (stock.qty-(stock.qa+stock.voidqty)) as qty,stock.rrcost,
      round((stock.qty-(stock.qa+stock.voidqty))/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty, stock.disc, stockinfo.itemdesc, stockinfo.unit, stockinfo.purpose, 
      stockinfo.requestorname, stockinfo.specs, stockinfo.dateneeded, head.deptid, head.sano, headinfo.proformainvoice as sono, client.clientid, head.ourref, head.potype, stockinfo.ctrlno
      FROM hprhead as head 
      left join hprstock as stock on stock.trno=head.trno 
      left join item on item.itemid = stock.itemid left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
      left join hstockinfotrans as stockinfo on stockinfo.trno = stock.trno and stockinfo.line = stock.line
      left join hheadinfotrans as headinfo on headinfo.trno=stockinfo.trno
      left join client on client.client=head.client
      left join uomlist as uom2 on uom2.uom=stockinfo.uom2 and uom2.isconvert=1
      where stock.trno = ? and stock.qty>(stock.qa) and stock.void=0";
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno']]);
      if (!empty($data)) {

        $this->coreFunctions->sbcupdate($this->head, [
          'editdate' => $this->othersClass->getCurrentTimeStamp(),
          'editby' => $config['params']['user'],
          'yourref' => $data[0]->potype
        ], ['trno' => $trno]);

        foreach ($data as $key2 => $value) {
          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['qty'] = $data[$key2]->rrqty;
          $config['params']['data']['rrqty2'] = $data[$key2]->rrqty;
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
          $config['params']['data']['itemdesc'] = $data[$key2]->itemdesc;
          $config['params']['data']['unit'] = $data[$key2]->unit;
          $config['params']['data']['purpose'] = $data[$key2]->purpose;
          $config['params']['data']['requestorname'] = $data[$key2]->requestorname;
          $config['params']['data']['specs'] = $data[$key2]->specs;
          $config['params']['data']['dateneeded'] = $data[$key2]->dateneeded;
          $config['params']['data']['deptid'] = $data[$key2]->deptid;
          $config['params']['data']['sano'] = $data[$key2]->sano;
          $config['params']['data']['sono'] = $data[$key2]->sono;
          $config['params']['data']['suppid'] = $data[$key2]->clientid;
          $config['params']['data']['catid'] = $data[$key2]->ourref;
          $config['params']['data']['ctrlno'] = $data[$key2]->ctrlno;
          $return = $this->additem('insert', $config);
          if ($return['status']) {
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.'];
  } //end function


  public function getprdetails($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];

    $msg = '';

    $filteruser = '';
    $admin = $this->othersClass->checkAccess($config['params']['user'], 3767);
    if (!$admin) {
      $filteruser = " and (stock.suppid=0 or (stock.status=0 and stock.suppid=" . $config['params']['adminid'] . "))";
    }

    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "select head.docno, ifnull(item.itemid,0) as itemid,stock.trno, 
      stock.line, ifnull(item.barcode,'') as barcode,stock.uom, stock.cost,
      (stock.qty-(stock.qa+stock.voidqty)) as qty,stock.rrcost,
      round((stock.qty-(stock.qa+stock.voidqty))/ case when stock.itemid=0 then ifnull(uom3.factor,1) when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) .
        ") as rrqty, stock.disc, stockinfo.itemdesc, stockinfo.unit, stockinfo.purpose,
      stockinfo.requestorname, stockinfo.specs, stockinfo.dateneeded, stockinfo.ctrlno, head.deptid, head.sano, headinfo.proformainvoice as sono, client.clientid, head.ourref, head.potype,head.wh
      FROM hprhead as head 
      left join hprstock as stock on stock.trno=head.trno 
      left join item on item.itemid = stock.itemid left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
      left join hstockinfotrans as stockinfo on stockinfo.trno = stock.trno and stockinfo.line = stock.line
      left join hheadinfotrans as headinfo on headinfo.trno=stockinfo.trno
      left join client on client.client=head.client
      left join uomlist as uom3 on uom3.uom=stockinfo.uom3 and uom3.isconvert=1
      where stock.trno = ? and stock.line = ? and stock.qty>(stock.qa) and stock.void=0 " . $filteruser;
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line']]);
      if (!empty($data)) {

        $cdpotype = $this->coreFunctions->getfieldvalue($this->head, "yourref", "trno=?", [$trno]);
        if ($cdpotype != "") {
          if ($cdpotype != $data[0]->potype) {
            return ['row' => $rows, 'status' => false, 'msg' => "Unable to select a PR with different PO type, this canvass was already created for " . $cdpotype . ".", 'reloadhead' => true, 'trno' => $trno];
          }
        }

        $this->coreFunctions->sbcupdate($this->head, [
          'editdate' => $this->othersClass->getCurrentTimeStamp(),
          'editby' => $config['params']['user'],
          'yourref' => $data[0]->potype,
          'wh' => $data[0]->wh
        ], ['trno' => $trno]);

        foreach ($data as $key2 => $value) {
          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['qty'] = $data[$key2]->rrqty;
          $config['params']['data']['rrqty2'] = $data[$key2]->rrqty;
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
          $config['params']['data']['itemdesc'] = $data[$key2]->itemdesc;
          $config['params']['data']['unit'] = $data[$key2]->unit;
          $config['params']['data']['purpose'] = $data[$key2]->purpose;
          $config['params']['data']['requestorname'] = $data[$key2]->requestorname;
          $config['params']['data']['specs'] = $data[$key2]->specs;
          $config['params']['data']['dateneeded'] = $data[$key2]->dateneeded;
          $config['params']['data']['deptid'] = $data[$key2]->deptid;
          $config['params']['data']['sano'] = $data[$key2]->sano;
          $config['params']['data']['sono'] = $data[$key2]->sono;
          $config['params']['data']['suppid'] = $data[$key2]->clientid;
          $config['params']['data']['catid'] = $data[$key2]->ourref;
          $config['params']['data']['ctrlno'] = $data[$key2]->ctrlno;
          $return = $this->additem('insert', $config);
          if ($return['status']) {
            if ($this->setserveditemsPR($data[$key2]->trno, $data[$key2]->line, $this->hqty) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setserveditemsPR($data[$key2]->trno, $data[$key2]->line, $this->hqty);
              $row = $this->openstockline($config);
              $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
            }
            array_push($rows, $return['row'][0]);
          } else {
            $msg .= "  " . $return['msg'];
          }
        } // end foreach
      } //end if
    } //end foreach

    if ($msg == '') {
      $msg = 'Items were successfully added.';
    }

    return ['row' => $rows, 'status' => true, 'msg' => $msg, 'reloadhead' => true, 'trno' => $trno];
  } //end function


  public function getsqsummary($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
      select concat(stock.trno,stock.line) as keyid, stock.trno, stock.line, stock.itemid, item.barcode, item.itemname, stock.uom, so.docno, date(head.dateid) as dateid,
      (stock.iss-(stock.qa+stock.sjqa+stock.poqa)) as iss,stock.isamt, stock.disc,stock.amt,stock.ext,
      FORMAT(((stock.iss-(stock.qa+stock.sjqa+stock.poqa))/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty
      from hsqhead as so left join hqshead as head on head.sotrno=so.trno left join hqsstock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid
      left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
      left join transnum on transnum.trno = head.trno
      where so.doc='SQ' and stock.iss > (stock.qa+stock.sjqa+stock.poqa) and stock.void = 0 and stock.trno=?
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
          $config['params']['data']['sorefx'] = $data[$key2]->trno;
          $config['params']['data']['solinex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->isamt;
          $return = $this->additem('insert', $config);
          if ($return['status']) {
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.'];
  } //end function


  public function getlatestprice($config)
  {
    $barcode = $config['params']['barcode'];
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $qry = "select docno,left(dateid,10) as dateid,round(amt,2) as amt,disc,uom from(select head.docno,head.dateid,
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
      return ['status' => true, 'msg' => 'Found the latest purchase price.', 'data' => $data];
    } else {
      return ['status' => false, 'msg' => 'No latest price found.'];
    }
  } // end function


  private function setserveditemsPR($refx, $linex, $qtyfield)
  {
    $qry1 = "select stock." . $qtyfield . " from " . $this->head . " as head left join " . $this->stock . " as
    stock on stock.trno=head.trno where head.doc='CD' and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry1 = $qry1 . " union all select stock." . $qtyfield . " from " . $this->hhead . " as head left join " . $this->hstock . " as stock on stock.trno=
    head.trno where head.doc='CD' and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry2 = "select ifnull(sum(" . $qtyfield . "),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty === '') {
      $qty = 0;
    }
    $iscanvass = 1;
    if ($qty == 0) {
      $iscanvass = 0;
    }
    $result = $this->coreFunctions->execqry("update hprstock set iscanvass=" . $iscanvass . ", statrem=" . ($iscanvass == 1 ? "'Canvass Sheet - Draft', statdate = '" . $this->othersClass->getCurrentTimeStamp() . "'"  : "''") . " where trno=" . $refx . " and line=" . $linex, 'update');
    return $result;
  } //end function


  // report startto

  public function reportsetup($config)
  {


    $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
    $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);

    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Successfully loaded.', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  }

  public function reportdata($config)
  {
    $this->logger->sbcviewreportlog($config);


    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config['params']['dataid']);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }





  private function report_default_query($trno)
  {

    $query = "select date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address, 
        head.terms,head.rem, item.barcode,
        item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as netamt, stock.disc, stock.ext,m.model_name as model,item.sizeid
        from cdhead as head left join cdstock as stock on stock.trno=head.trno 
        left join client on client.client=head.client
        left join item on item.itemid = stock.itemid
        left join model_masterfile as m on m.model_id = item.model
        where head.doc='cd' and head.trno='$trno'
        union all
        select date(head.dateid) as dateid, head.docno, client.client, client.clientname, 
        head.address, head.terms,head.rem, item.barcode,
        item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as netamt, stock.disc, stock.ext,m.model_name as model,item.sizeid
        from hcdhead as head left join cdstock as stock on stock.trno=head.trno 
        left join client on client.client=head.client
        left join item on item.itemid = stock.itemid
        left join model_masterfile as m on m.model_id = item.model
        where head.doc='cd' and head.trno='$trno'";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn




  public function default_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = "";
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br><br>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col($this->modulename, '580', null, false, $border, '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT # :', '120', null, false, $border, '', 'L', $font, '13', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, $border, 'B', 'L', $font, '13', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SUPPLIER : ', '80', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '520', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('DATE : ', '50', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '150', null, false, $border, 'B', 'R', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ADDRESS : ', '80', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['address']) ? $data[0]['address'] : ''), '520', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('TERMS : ', '60', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['terms']) ? $data[0]['terms'] : ''), '140', null, false, $border, 'B', 'R', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CODE', '50', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('QTY', '50', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('UNIT', '50', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('D E S C R I P T I O N', '475', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('UNIT PRICE', '100', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('(+/-) %', '75', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('TOTAL', '100', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    return $str;
  }

  public function reportplotting($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];


    $str = '';
    $count = 35;
    $page = 35;
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport();
    $str .= $this->default_header($params, $data);


    $totalext = 0;
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data[$i]['barcode'], '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col(number_format($data[$i]['qty'], $this->companysetup->getdecimal('qty', $params['params'] )), '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['uom'], '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['itemname'], '475', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col(number_format($data[$i]['netamt'], $this->companysetup->getdecimal('price',  $params['params'])), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['disc'], '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['ext'], $decimal), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $totalext = $totalext + $data[$i]['ext'];



      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        $str .= $this->default_header($params, $data);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ITEM(S)', '50', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($i, '50', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '440', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('GRAND TOTAL :', '110', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalext, $decimal), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('NOTE : ', '60', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col(isset($data[0]['rem']) ? $data[0]['rem'] : "", '600', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '140', null, false, $border, '', 'L', $font, '12', 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br><br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'R', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($params['params']['dataparams']['prepared'], '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['approved'], '266', null, false, $border, '', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['received'], '266', null, false, $border, '', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
} //end class
