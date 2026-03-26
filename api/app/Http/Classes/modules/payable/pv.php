<?php

namespace App\Http\Classes\modules\payable;

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

class pv
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'PAYABLE VOUCHER';
  public $gridname = 'accounting';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $tablenum = 'cntnum';
  public $head = 'lahead';
  public $hhead = 'glhead';
  public $detail = 'ladetail';
  public $hdetail = 'gldetail';
  public $tablelogs = 'table_log';
  public $htablelogs = 'htable_log';
  public $tablelogs_del = 'del_table_log';
  private $stockselect;
  public $defaultContra = 'AP2';

  private $fields = ['trno', 'docno', 'dateid', 'due', 'client', 'clientname', 'yourref', 'ourref', 'rem', 'forex', 'cur', 'address', 'ewt', 'ewtrate', 'projectid', 'branch', 'deptid', 'invoiceno', 'invoicedate', 'empid', 'excess', 'excessrate', 'phaseid', 'modelid', 'blklotid', 'amenityid', 'subamenityid', 'terms', 'checkno', 'checkdate'];
  private $except = ['trno', 'dateid', 'due'];
  private $acctg = [];
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
    $this->sqlquery = new sqlquery;
    $this->reporter = new SBCPDF;
    $this->helpClass = new helpClass;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 371,
      'edit' => 372,
      'new' => 373,
      'save' => 374,
      // 'change' => 375, remove change doc
      'delete' => 376,
      'print' => 377,
      'lock' => 378,
      'unlock' => 379,
      'post' => 380,
      'unpost' => 381,
      'additem' => 382,
      'edititem' => 383,
      'deleteitem' => 384
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $companyid = $config['params']['companyid'];
    // $action = 0;
    // $liststatus = 1;
    // $listdocument = 2;
    // $listdate = 3;
    // $listclientname = 4;
    // $yourref = 5;
    // $ourref = 6;
    // $cr = 7;
    // $postdate = 8;

    switch ($companyid) {
      case 56: //homeworks
        $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'client', 'listclientname', 'yourref', 'ourref', 'cr', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
        break;
      case 29: //SBC
        $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'rem',  'yourref', 'ourref', 'ref', 'cr', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
        break;
      default:
        $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'yourref', 'ourref', 'cr', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
        break;
    }




    $stockbuttons = ['view'];
    if ($config['params']['companyid'] == 10 || $config['params']['companyid'] == 12) {
      array_push($stockbuttons, 'duplicatedoc');
    }

    foreach ($getcols as $key => $value) {
      $$value = $key;
    }

    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$listclientname]['style'] = 'width:350px;whiteSpace: normal;min-width:350px;';
    $cols[$yourref]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
    $cols[$yourref]['align'] = 'text-left';
    $cols[$ourref]['align'] = 'text-left';
    $cols[$postdate]['label'] = 'Post Date';

    switch ($config['params']['companyid']) {
      case 19:
        break;
      case 10:
        $cols[$liststatus]['name'] = 'statuscolor';
        $cols[$cr]['type'] = 'coldel';
        break;
      case 56: //homeworks
        $cols[$listdocument]['style'] = 'width:180px;whiteSpace: normal;min-width:180px;';
        $cols[$client]['style'] = 'width:80px;whiteSpace: normal;min-width:80px;';
        $cols[$client]['type'] = 'label';
        $cols[$client]['label'] = 'Code';
        break;
      case 29: //SBC
        $cols[$rem]['type'] = 'label';
        $cols[$rem]['style'] = 'width:400px;whiteSpace: normal;min-width:400px;';
        $cols[$ref]['type'] = 'label';
        $cols[$ref]['label'] = 'Ref#';
        $cols[$yourref]['type'] = 'coldel';
        $cols[$ourref]['type'] = 'coldel';
        break;
      default:
        $cols[$cr]['type'] = 'coldel';
        break;
    }

    $cols = $this->tabClass->delcollisting($cols);
    return $cols;
  }

  public function loaddoclisting($config)
  {

    $companyid = $config['params']['companyid'];

    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));
    $itemfilter = $config['params']['itemfilter'];
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $condition = '';
    $searchfilter = $config['params']['search'];
    $limit = '';
    $lacr = '';
    $glcr = '';
    $laref = '';
    $glref = '';


    switch ($itemfilter) {
      case 'draft':
        $condition = ' and num.postdate is null and head.lockdate is null';
        break;
      case 'locked':
        $condition = ' and num.postdate is null and head.lockdate is not null';
        break;
      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
    }

    // if ($searchfilter != '') {
    //   $condition .= " and (head.docno like '%" . $searchfilter . "%' or head.clientname like '%" . $searchfilter . "%' or head.yourref like '%" . $searchfilter . "%' or head.ourref like '%" . $searchfilter . "%' or num.postedby like '%" . $searchfilter . "%' or head.createby like '%" . $searchfilter . "%' or head.editby like '%" . $searchfilter . "%' or head.viewby like '%" . $searchfilter . "%')";
    // }

    $companyid = $config['params']['companyid'];
    $status = "'POSTED'";
    $lstatus = "'DRAFT'";
    $lstatcolor = "'red'";
    $gstatcolor = "'grey'";
    $field = "";
    $join = "";
    $hjoin = "";
    switch ($companyid) {
      case 10:
      case 12:
        $status = "(case (select format(sum(ar.bal),2) from apledger as ar where ar.trno=head.trno) when 0 then 'PAID'
        else 'UNPAID' end)";
        $gstatcolor = "(case (select format(sum(ar.bal),2) from apledger as ar where ar.trno=head.trno) when 0 then 'green'
        else 'orange' end)";
        $dateid = "head.dateid as dateid2, date_format(head.dateid,'%m-%d-%Y') as dateid";
        if ($searchfilter == "") $limit = 'limit 25';
        $orderby =  "order by dateid2 desc, docno desc";
        break;
      case 19:
        $dateid = "left(head.dateid,10) as dateid";
        $lacr = ", (select FORMAT(sum(d.cr)," . $this->companysetup->getdecimal('currency', $config['params']) . ") 
       from ladetail as d left join coa on coa.acnoid = d.acnoid
        where d.trno= head.trno) as cr ";
        $glcr = ", (select FORMAT(sum(d.cr)," . $this->companysetup->getdecimal('currency', $config['params']) . ") 
       from gldetail as d left join coa on coa.acnoid = d.acnoid
        where d.trno= head.trno) as cr ";
        if ($searchfilter == "") $limit = 'limit 150';
        $orderby =  "order by docno desc, dateid desc";
        break;
      case 56: //homeworks
        $join = " left join client as cl on cl.client= head.client";
        $hjoin = " left join client as cl on cl.clientid= head.clientid";
        $field = ", cl.client";
        $dateid = "left(head.dateid,10) as dateid";
        if ($searchfilter == "") $limit = 'limit 150';
        $orderby =  "order by  dateid desc, docno desc";
        break;
      case 29: //SBC
        $lstatus = "case ifnull(head.lockdate,'') when '' then 'DRAFT' else 'LOCKED' end";
        $field = ",head.rem";
        $laref = ",'' as ref";
        $glref = ",(select h.docno as  ref from lahead as h
            left join ladetail as d on d.trno = h.trno
            where h.doc = 'cv' and d.refx <> 0 and d.refx = head.trno
            union all
	          select h.docno as  ref from glhead as h
            left join gldetail as d on d.trno = h.trno
            where h.doc = 'cv' and d.refx <> 0 and d.refx = head.trno limit 1) as ref";
        $dateid = "left(head.dateid,10) as dateid";
        if ($searchfilter == "") $limit = 'limit 150';
        $orderby =  "order by  dateid desc, docno desc";
        break;
      default:
        $dateid = "left(head.dateid,10) as dateid";
        if ($searchfilter == "") $limit = 'limit 150';
        $orderby =  "order by  dateid desc, docno desc";
        break;
    }


    // " . $filtersearch . "
    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'head.clientname', 'head.yourref', 'head.ourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];
      // if($companyid == 28) array_push($searchfield,'head.rem');
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }

    $qry = "select head.trno,head.docno,head.clientname,$dateid $lacr,  $lstatus as status, $lstatcolor as statuscolor,
     head.createby,head.editby,head.viewby,num.postedby, date(num.postdate) as postdate,
     head.yourref, head.ourref $laref $field     
     from " . $this->head . " as head 
     left join " . $this->tablenum . " as num  on num.trno=head.trno 
      " . $join . "
     where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
     union all
     select head.trno,head.docno,head.clientname,$dateid $glcr,$status as status, $gstatcolor as statuscolor,
     head.createby,head.editby,head.viewby, num.postedby, date(num.postdate) as postdate,
     head.yourref, head.ourref $glref $field     
     from " . $this->hhead . " as head 
     left join " . $this->tablenum . " as num  on num.trno=head.trno 
      " . $hjoin . "
     where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
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
      'print',
      'lock',
      'unlock',
      'post',
      'unpost',
      'logs',
      'edit',
      'backlisting',
      'toggleup',
      'toggledown',
      'help',
      'others'
    );

    $buttons = $this->btnClass->create($btns);
    $step1 = $this->helpClass->getFields(['btnnew', 'supplier', 'dateid', 'yourref', 'cur', 'csrem', 'btnsave']);
    $step2 = $this->helpClass->getFields(['btnedit', 'supplier', 'dateid', 'yourref', 'cur', 'csrem', 'btnsave']);
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
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => 'pv', 'title' => 'PV_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
    }
    return $buttons;
  } // createHeadbutton

  public function createtab2($access, $config)
  {
    $companyid = $config['params']['companyid'];
    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrycntnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);

    if ($companyid == 10) {
      $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entryparticulars', 'label' => 'Particulars', 'access' => 'view']];
      $particulars = $this->tabClass->createtab($tab, []);

      $return['Particulars'] = ['icon' => 'fa fa-envelope', 'tab' => $particulars];

      $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrypvitem', 'label' => 'Items', 'access' => 'view']];
      $item = $this->tabClass->createtab($tab, []);

      $return['Item'] = ['icon' => 'fa fa-envelope', 'tab' => $item];
    }
    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];

    if ($this->companysetup->getistodo($config['params'])) {
      $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrycntnumtodo', 'label' => 'To Do', 'access' => 'view']];
      $objtodo = $this->tabClass->createtab($tab, []);
      $return['To Do'] = ['icon' => 'fa fa-list', 'tab' => $objtodo];
    }


    if ($config['params']['companyid'] == 60) { //transpower      
      $changecode = $this->othersClass->checkAccess($config['params']['user'], 5499);
      if ($changecode) {
        $changecode = ['customform' => ['action' => 'customform', 'lookupclass' => 'changebarcode']];
        $return['CHANGE CODE'] = ['icon' => 'fa fa-qrcode', 'customform' => $changecode];
      }
    }



    return $return;
  }

  public function createTab($access, $config)
  {
    $companyid = $config['params']['companyid'];
    $systype = $this->companysetup->getsystemtype($config['params']);
    $makecv = $this->othersClass->checkAccess($config['params']['user'], 3579);
    $headgridbtns = ['viewref', 'viewdiagram', 'viewacctginfo'];

    if ($companyid == 10) {
      if ($makecv != 0) {
        array_push($headgridbtns, 'makecv');
      }
    }

    $action = 0;
    $isewt = 1;
    $isvat = 2;
    $isvewt = 3;
    $ewtcode = 4;
    $db = 5;
    $cr = 6;
    $postdate = 7;
    $client = 8;
    $project = 9;
    $subprojectname = 10;
    $itemgroup = 11;
    $ref = 12;
    $dept = 13;
    $type = 14;
    $rem = 15;
    $acnoname = 16;

    $columns = [
      'action',
      'isewt',
      'isvat',
      'isvewt',
      'ewtcode',
      'db',
      'cr',
      'postdate',
      'client',
      'project',
      'subprojectname',
      'stock_projectname',
      'ref',
      'dept',
      'type',
      'rem',
      'acnoname'
    ];

    switch ($systype) {
      case 'REALESTATE':

        $phasename = 17;
        $housemodel = 18;
        $blk = 19;
        $lot = 20;
        $amenityname = 21;
        $subamenityname = 22;
        array_push($columns,  'phasename', 'housemodel', 'blk', 'lot', 'amenityname', 'subamenityname');

        break;
    }


    $tab = [
      $this->gridname => [
        'gridcolumns' => $columns,
        'headgridbtns' => $headgridbtns,
      ],
      //'adddocument'=>['event'=>['lookupclass' => 'entrycntnumpicture','action' => 'documententry','access' => 'view']] 
    ];

    $stockbuttons = ['save', 'delete'];
    array_push($stockbuttons, 'detailinfo');
    if ($this->companysetup->getiseditsortline($config['params'])) {
      array_push($stockbuttons, 'sortline');
    }

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // 11 - ref 
    $obj[0]['accounting']['columns'][$ref]['lookupclass'] = 'refpv';
    //10 - client      
    $obj[0]['accounting']['columns'][$client]['lookupclass'] = 'vendordetail';
    $obj[0]['accounting']['columns'][$itemgroup]['label'] = 'Item Group';
    $obj[0]['accounting']['columns'][$postdate]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px;';

    switch ($config['params']['companyid']) {
      case 10:
        $obj[0]['accounting']['columns'][$client]['name'] = 'clientname';
        $obj[0]['accounting']['columns'][$rem]['type'] = 'coldel';
        $obj[0]['accounting']['columns'][$type]['type'] = 'coldel';
        break;
      case 8:
        $obj[0]['accounting']['columns'][$subprojectname]['lookupclass'] = 'pvsubproj';
        $obj[0]['accounting']['columns'][$type]['type'] = 'coldel';
        break;
      case 19:
        $obj[0]['accounting']['columns'][$rem]['style'] = 'text-align: left; width: 450px;whiteSpace: normal;min-width:450px;max-width:450px;';
        $obj[0]['accounting']['columns'][$rem]['type'] = 'textarea';
        $obj[0]['accounting']['columns'][$type]['type'] = 'coldel';
        break;
      default:
        if ($companyid != 8 && $companyid != 39) {
          $obj[0]['accounting']['columns'][$project]['type'] = 'coldel';
        }

        $obj[0]['accounting']['columns'][$itemgroup]['type'] = 'coldel';
        $obj[0]['accounting']['columns'][$isewt]['type'] = 'coldel';
        $obj[0]['accounting']['columns'][$isvat]['type'] = 'coldel';
        $obj[0]['accounting']['columns'][$isvewt]['type'] = 'coldel';
        $obj[0]['accounting']['columns'][$ewtcode]['type'] = 'coldel';

        if ($companyid != 24 && $companyid != 29 && $companyid != 39) {
          $obj[0]['accounting']['columns'][$rem]['type'] = 'coldel';
        } else {
          $obj[0]['accounting']['columns'][$rem]['style'] = 'text-align: left; width: 450px;whiteSpace: normal;min-width:450px;max-width:450px;';
          $obj[0]['accounting']['columns'][$rem]['type'] = 'textarea';
        }

        if ($config['params']['companyid'] != 24) {
          $obj[0]['accounting']['columns'][$dept]['type'] = 'coldel';
          // $obj[0]['accounting']['columns'][$rem]['type'] = 'coldel';
          $obj[0]['accounting']['columns'][$type]['type'] = 'coldel';
        } else {
          $obj[0]['accounting']['columns'][$dept]['action'] = 'lookupclient';
          $obj[0]['accounting']['columns'][$dept]['lookupclass'] = 'lookupdept';
          $obj[0]['accounting']['columns'][$type]['type'] = 'lookup';
          $obj[0]['accounting']['columns'][$type]['action'] = 'lookuprandom';
          $obj[0]['accounting']['columns'][$type]['lookupclass'] = 'detailtype';
          $obj[0]['accounting']['columns'][$type]['label'] = 'Type';
        }

        break;
    }

    if ($this->companysetup->getsystemtype($config['params']) != 'CAIMS') {
      $obj[0]['accounting']['columns'][$subprojectname]['type'] = 'coldel';
    }

    switch ($systype) {
      case 'REALESTATE':
        $obj[0]['accounting']['columns'][$blk]['readonly'] = true;
        $obj[0]['accounting']['columns'][$lot]['readonly'] = true;
        break;
    }

    $obj[0]['accounting']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $companyid = $config['params']['companyid'];

    $tbuttons = ['unpaid', 'additem', 'saveitem', 'deleteallitem', 'generateewt'];

    if ($companyid == 29) { // sbc
      $tbuttons = ['unpaid', 'unpaidall', 'additem', 'saveitem', 'deleteallitem', 'generateewt'];
    }

    $obj = $this->tabClass->createtabbutton($tbuttons);

    if ($companyid == 29) {
      $obj[2]['label'] = "ADD ACCOUNT";
      $obj[2]['action'] = "adddetail";
      $obj[3]['label'] = "SAVE ACCOUNT";
      $obj[4]['label'] = "DELETE ACCOUNT";
    } else {
      $obj[1]['label'] = "ADD ACCOUNT";
      $obj[1]['action'] = "adddetail";
      $obj[2]['label'] = "SAVE ACCOUNT";
      $obj[3]['label'] = "DELETE ACCOUNT";
    }
    return $obj;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];
    $noeditdate = $this->othersClass->checkAccess($config['params']['user'], 4853);
    $systype = $this->companysetup->getsystemtype($config['params']);
    $isgenerateapv = $this->companysetup->isgenerateapv($config['params']);

    //col1
    switch ($companyid) {
      case 26:
        $fields = ['docno', 'client', 'client2'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'client2.action', 'allclienthead');
        data_set($col1, 'client2.callbackfieldhead', ['dateid']);
        data_set($col1, 'client2.callbackfieldlookup', ['terms', 'days', 'client']);
        data_set($col1, 'client2.selectaction', 'computeterms');
        data_set($col1, 'client2.plottype', 'callback');
        break;
      default:
        $fields = ['docno', 'client', 'clientname'];
        if ($companyid == 32) {
          array_push($fields, 'empname');
        }
        $col1 = $this->fieldClass->create($fields);
        break;
    }

    data_set($col1, 'client.label', 'Vendor');
    data_set($col1, 'client.lookupclass', 'allclienthead');
    data_set($col1, 'docno.label', 'Transaction#');

    if ($companyid == 10 || $companyid == 12) {
      data_set($col1, 'clientname.type', 'textarea');
      data_set($col1, 'businesstype.type', 'textarea');
    } else {
      array_push($fields, 'address');
    }

    if ($companyid == 32) {
      data_set($col1, 'empname.lookupclass', 'employee');
      data_set($col1, 'empname.action', 'lookupclient');
      data_set($col1, 'empname.type', 'lookup');
    }

    //col2
    // $fields = ['dateid', 'dewt', 'dprojectname'];

    if ($companyid == 56) { //homeworks
      $fields = [['dateid', 'due'], ['terms', 'dewt'], ['checkdate', 'checkno']];
    } else {
      $fields = ['dateid', 'dewt', 'dprojectname'];
    }
    if ($companyid == 10) {
      array_push($fields, 'dbranchname', 'invoiceno');
    }

    if ($companyid == 24) {
      $fields = ['dateid', 'dewt', 'dexcess', 'dprojectname'];
    }

    $col2 = $this->fieldClass->create($fields);
    if ($companyid == 10 || $companyid == 12) {
      data_set($col2, 'invoiceno.type', 'lookup');
      data_set($col2, 'invoiceno.maxlength', 25);
      data_set($col2, 'invoiceno.label', 'Supplier Invoice No');
      data_set($col2, 'invoiceno.action', 'lookuprrinvoiceno');
      data_set($col1, 'businesstype.type', 'textarea');
    }

    if ($companyid == 26) {
      data_set($col2, 'dprojectname.label', 'Business Unit');
      data_set($col2, 'dprojectname.type', 'fselect');
      data_set($col2, 'dprojectname.action', 'businessunit');
      data_set($col2, 'dprojectname.plottype', 'plothead');
      data_set($col2, 'dprojectname.plotting', ['project' => 'projectcode', 'projectcode' => 'projectcode', 'projectid' => 'projectid', 'projectname' => 'projectname']);
      data_set($col2, 'dprojectname.class', 'csprojectname');
      data_set($col2, 'dprojectname.selectclass', '');
      $qry = "select line as projectid, code as projectcode, name as projectname, concat(code,'~',name) as description from projectmasterfile order by line limit 20";
      $bunits = $this->coreFunctions->opentable($qry);
      data_set($col2, 'dprojectname.data', json_decode(json_encode($bunits), true));
    }

    if ($companyid == 40) { //cdo
      if ($noeditdate) {
        data_set($col2, 'dateid.class', 'sbccsreadonly');
      }
    }

    if ($companyid == 56) { //homeworks
      data_set($col2, 'checkdate.label', 'Counter Date');
      data_set($col2, 'checkno.label', 'Counter #');
    }

    //col3
    $fields = [['yourref', 'ourref'], ['cur', 'forex']];
    switch ($companyid) {
      case 10:
        array_push($fields, 'ddeptname', 'invoicedate');
        break;
    }
    if ($systype == 'REALESTATE') {
      array_push($fields, 'rem');
      // $fields = ['dprojectname', 'phase', 'housemodel', ['blklot', 'lot'],'amenityname','subamenityname'];
      // $col4 = $this->fieldClass->create($fields);
      // data_set($col4, 'dprojectname.lookupclass', 'project');
      // data_set($col4, 'phase.addedparams', ['projectid']);
      // data_set($col4, 'housemodel.addedparams', ['projectid']);
      // data_set($col4, 'blklot.addedparams', ['projectid', 'phaseid', 'modelid', 'fpricesqm']);
      // data_set($col4, 'subamenityname.addedparams', ['amenityid']); 
    }
    if ($isgenerateapv) {
      array_push($fields, 'ref');
    }
    $col3 = $this->fieldClass->create($fields);

    if ($companyid == 26) {
      data_set($col3, 'yourref.label', 'Your Ref.');
      data_set($col3, 'ourref.label', 'Our Ref.');
    }
    if ($isgenerateapv) {
      data_set($col3, 'ref.label', 'RR No.');
      data_set($col3, 'ref.readonly', true);
    }



    //col4
    $fields = ['rem'];


    if ($companyid == 10 || $companyid == 12) {
      array_push($fields, 'lblpaid');
    }


    if ($this->companysetup->getistodo($config['params'])) {
      array_push($fields, 'donetodo');
    }

    if ($systype == 'REALESTATE') {
      $fields = ['dprojectname', 'phase', 'housemodel', ['blklot', 'lot'], 'amenityname', 'subamenityname'];
      $col4 = $this->fieldClass->create($fields);
      data_set($col4, 'dprojectname.lookupclass', 'project');
      data_set($col4, 'phase.addedparams', ['projectid']);
      data_set($col4, 'housemodel.addedparams', ['projectid']);
      data_set($col4, 'blklot.addedparams', ['projectid', 'phaseid', 'modelid', 'fpricesqm']);
      data_set($col4, 'subamenityname.addedparams', ['amenityid']);
    } else {
      $col4 = $this->fieldClass->create($fields);
    }


    if ($companyid == 10) {
      data_set($col2, 'dprojectname.required', false);
      data_set($col2, 'dbranchname.required', true);
      data_set($col4, 'rem.maxlength', '1000');
      data_set($col3, 'ddeptname.required', true);
      data_set($col3, 'ddeptname.label', 'Department');
    }





    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
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
    $data[0]['forex'] = 1;
    $data[0]['cur'] = $this->companysetup->getdefaultcurrency($params);
    $data[0]['projectid'] = '0';
    $data[0]['projectcode'] = '';
    $data[0]['projectname'] = '';
    $data[0]['ewt'] = '';
    $data[0]['ewtrate'] = 0;
    $data[0]['contra'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', [$this->defaultContra]);
    $data[0]['acnoname'] = $this->coreFunctions->getfieldvalue('coa', 'acnoname', 'acno=?', [$data[0]['contra']]);
    $data[0]['branchcode'] = '';
    $data[0]['branchname'] = '';
    $data[0]['dbranchname'] = '';
    $data[0]['branch'] = '0';
    $data[0]['ddeptname'] = '';
    $data[0]['deptid'] = '0';
    $data[0]['dept'] = '';
    $data[0]['invoiceno'] = '';
    $data[0]['invoicedate'] = $this->othersClass->getCurrentDate();
    $data[0]['empname'] = '';
    $data[0]['empid'] = 0;
    $data[0]['excess'] = '';
    $data[0]['dexcess'] = '';
    $data[0]['excessrate'] = 0;

    $data[0]['phaseid'] = 0;
    $data[0]['phase'] = '';

    $data[0]['modelid'] = 0;
    $data[0]['housemodel'] = '';

    $data[0]['blklotid'] = 0;
    $data[0]['blklot'] = '';
    $data[0]['lot'] = '';


    $data[0]['amenityid'] = 0;
    $data[0]['amenityname'] = '';

    $data[0]['subamenityid'] = 0;
    $data[0]['subamenityname'] = '';
    $data[0]['due'] = date('Y-m-d');
    $data[0]['terms'] = '';


    $data[0]['checkdate'] = $this->othersClass->getCurrentDate();
    $data[0]['checkno'] = '';


    return $data;
  }

  public function loadheaddata($config)
  {
    $companyid = $config['params']['companyid'];
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

    $isgenerateapv = $this->companysetup->isgenerateapv($config['params']);

    $addedfields = ",'' as ref";
    $leftjoin = "";

    if ($isgenerateapv) {
      $addedfields = ",rrnum.docno as ref";
      $leftjoin = "  LEFT JOIN cntnum as rrnum on rrnum.trno=head.rrtrno";
    }

    $qryselect = "select 
        num.center,
        head.trno, 
        head.docno,
        client.client,
        head.clientname as client2,
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
        head.ewt,head.ewtrate,'' as dewt, 
        date_format(head.createdate,'%Y-%m-%d') as createdate,
        head.rem,
        head.tax,
        head.vattype,
        '' as dvattype,
        left(head.due,10) as due, 
        head.projectid,
        ifnull(project.name,'') as projectname,
        '' as dprojectname,
        '' as dexcess,
        client.groupid,ifnull(project.code,'') as projectcode,head.branch,ifnull(b.clientname,'') as branchname,ifnull(b.client,'') as branchcode,'' as dbranchname,ifnull(d.client,'') as dept,ifnull(d.clientname,'') as deptname,head.deptid,'' as ddeptname,
        head.invoiceno,ifnull(left(head.invoicedate,10),'') as invoicedate,
        emp.clientname as empname, head.empid,head.excess,head.excessrate,

         
        head.phaseid, 
        ph.code as phase,

        head.modelid, 
        hm.model as housemodel, 
        
        head.blklotid, 
        bl.blk as blklot, 
        bl.lot,
        
        amh.line as amenityid,
        amh.description as amenityname,
        subamh.line as subamenityid, left(head.checkdate,10) as checkdate,head.checkno,
        subamh.description as subamenityname " . $addedfields . "
         
        ";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as b on b.clientid = head.branch
        left join client as d on d.clientid = head.deptid
        left join client as emp on head.empid = emp.clientid
        left join coa on coa.acno=head.contra
        left join projectmasterfile as project on project.line=head.projectid
        
        left join phase as ph on ph.line = head.phaseid
        left join housemodel as hm on hm.line = head.modelid
        left join blklot as bl on bl.line = head.blklotid

        left join amenities as amh on amh.line= head.amenityid
        left join subamenities as subamh on subamh.line=head.subamenityid and subamh.amenityid=head.amenityid
        $leftjoin

        where head.trno = ? and num.doc=? and num.center = ? 
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.clientid = client.clientid
        left join client as b on b.clientid = head.branch
        left join client as d on d.clientid = head.deptid
        left join client as emp on head.empid = emp.clientid    
        left join coa on coa.acno=head.contra 
        left join projectmasterfile as project on project.line=head.projectid         
        
        left join phase as ph on ph.line = head.phaseid
        left join housemodel as hm on hm.line = head.modelid
        left join blklot as bl on bl.line = head.blklotid

        left join amenities as amh on amh.line= head.amenityid
        left join subamenities as subamh on subamh.line=head.subamenityid and subamh.amenityid=head.amenityid
        $leftjoin

        where head.trno = ? and num.doc=? and num.center=? ";

    $head = $this->coreFunctions->opentable($qry, [$trno, $doc, $center, $trno, $doc, $center]);
    if (!empty($head)) {
      $detail = $this->opendetail($trno, $config);
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

      switch ($companyid) {
        case '10':
        case '12':
          $lvlpaid = true;
          if ($isposted) {
            $bal = $this->coreFunctions->datareader("select sum(bal) as value from apledger  where trno=?", [$trno]);
            $lvlpaid = $bal == 0 ? false : true;
          }
          $hideobj = ['lblpaid' => $lvlpaid];
          break;
      }

      return  ['head' => $head, 'griddata' => ['accounting' => $detail], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg, 'hideobj' => $hideobj];
    } else {
      $head[0]['trno'] = 0;
      $head[0]['docno'] = '';
      return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['accounting' => []], 'msg' => 'Data Head Fetched Failed, either somebody already deleted the transaction or modified...'];
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

    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key], '', $companyid);
        } //end if    
      }
    }

    if ($companyid == 56) { //homeworks
      $data['due'] = $this->othersClass->computeterms($data['dateid'], $data['due'], $data['terms']);
      $data['invoicedate'] = '';
    }

    // var_dump($data);
    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    if ($isupdate) {
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
      if ($companyid == 10) {
        $this->coreFunctions->sbcupdate($this->detail, ['postdate' => $head['dateid']], ['trno' => $head['trno'], 'refx' => 0]);
      }
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
    $generaveapv = $this->companysetup->isgenerateapv($config['params']);

    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];
    $table = $config['docmodule']->tablenum;
    $docno = $this->coreFunctions->datareader("select docno as value from " . $table . ' where trno=?', [$trno]);
    $qry = "select trno as value from " . $this->tablenum . " where doc=? and trno<? order by trno desc limit 1 ";
    $trno2 = $this->coreFunctions->datareader($qry, [$doc, $trno]);
    $this->deleteallitem($config);

    if ($generaveapv) {
      $this->coreFunctions->execqry("update glhead set pvtrno=0 where pvtrno=" . $trno);
    }

    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry("delete from particulars where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry("delete from pvitem where trno=?", 'delete', [$trno]);



    $this->othersClass->deleteattachments($config);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  public function posttrans($config)
  {
    return $this->othersClass->posttransacctg($config);
  } //end function

  public function unposttrans($config)
  {
    return $this->othersClass->unposttransacctg($config);
  } //end function

  private function getdetailselect($config)
  {
    $qry = " head.trno,left(head.dateid,10) as dateid,d.ref,d.line,d.sortline,coa.acno,coa.acnoname,
    client.client,client.clientname,d.rem,
    FORMAT(d.db,2) as db,FORMAT(d.cr,2) as cr,d.fdb,d.fcr,d.refx,d.linex,
    left(d.postdate,10) as postdate,d.checkno,coa.alias,d.pdcline,
    d.projectid,ifnull(proj.name,'') as projectname,d.subproject,proj.code as project,
    ifnull(proj.name,'') as stock_projectname,d.cur,d.forex,d.deptid,ifnull(dept.clientname,'') as dept,d.branch,
    case d.isewt when 0 then 'false' else 'true' end as isewt,
    case d.isvat when 0 then 'false' else 'true' end as isvat,
    case d.isvewt when 0 then 'false' else 'true' end as isvewt,d.ewtcode,d.ewtrate,
    d.damt,'' as bgcolor,'' as errcolor,subproj.subproject as subprojectname,d.type,case d.isexcess when 0 then 'false' else 'true' end as isexcess,
            
    d.phaseid, ph.code as phasename,

    d.modelid, hm.model as housemodel, 

    d.blklotid, bl.blk, bl.lot,
    
    am.line as amenity,
    am.description as amenityname,
    subam.line as subamenity,
    subam.description as subamenityname ";
    return $qry;
  }

  public function opendetail($trno, $config)
  {
    $sqlselect = $this->getdetailselect($config);

    $qry = "select " . $sqlselect . " 
    from " . $this->detail . " as d
    left join " . $this->head . " as head on head.trno=d.trno
    left join client on client.client=d.client
    left join projectmasterfile as proj on proj.line = d.projectid
    
    left join phase as ph on ph.line = d.phaseid
    left join housemodel as hm on hm.line = d.modelid
    left join blklot as bl on bl.line = d.blklotid
    left join amenities as am on am.line= d.amenityid
    left join subamenities as subam on subam.line=d.subamenityid and subam.amenityid=d.amenityid

    left join subproject as subproj on subproj.line = d.subproject
    left join coa on d.acnoid=coa.acnoid
    left join client as dept on dept.clientid=d.deptid
    where d.trno=?
    union all
    select " . $sqlselect . "  
    from " . $this->hdetail . " as d
    left join " . $this->hhead . " as head on head.trno=d.trno
    left join client on client.clientid=d.clientid
    left join projectmasterfile as proj on proj.line = d.projectid
    
    left join phase as ph on ph.line = d.phaseid
    left join housemodel as hm on hm.line = d.modelid
    left join blklot as bl on bl.line = d.blklotid
    left join amenities as am on am.line= d.amenityid
    left join subamenities as subam on subam.line=d.subamenityid and subam.amenityid=d.amenityid

    left join subproject as subproj on subproj.line = d.subproject
    left join coa on coa.acnoid=d.acnoid
    left join client as dept on dept.clientid=d.deptid
    where d.trno=? order by sortline,line";
    $detail = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $detail;
  }

  public function opendetailline($config)
  {
    $sqlselect = $this->getdetailselect($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = "select " . $sqlselect . " 
    from " . $this->detail . " as d
    left join " . $this->head . " as head on head.trno=d.trno
    left join client on client.client=d.client
    left join projectmasterfile as proj on proj.line = d.projectid
    
    left join phase as ph on ph.line = d.phaseid
    left join housemodel as hm on hm.line = d.modelid
    left join blklot as bl on bl.line = d.blklotid
    left join amenities as am on am.line= d.amenityid
    left join subamenities as subam on subam.line=d.subamenityid and subam.amenityid=d.amenityid

    left join subproject as subproj on subproj.line = d.subproject
    left join coa on d.acnoid=coa.acnoid
    left join client as dept on dept.clientid=d.deptid
    where d.trno=? and d.line=?";
    $detail = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $detail;
  } // end function

  public function openhead($config)
  {
    $doc = $config['params']['doc'];
    $trno = $config['params']['trno'];
    $center = $config['params']['center'];
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
        head.contra,
        coa.acnoname,
        '' as dacnoname,
        left(head.dateid,10) as dateid, 
        head.clientname,
        head.address, 
        head.ewt,head.ewtrate,'' as dewt, 
        date_format(head.createdate,'%Y-%m-%d') as createdate,
        head.rem,
        head.tax,
        head.vattype,
        '' as dvattype,
        left(head.due,10) as due, 
        head.projectid,
        ifnull(project.name,'') as projectname,
        '' as dprojectname,
        client.groupid,ifnull(project.code,'') as projectcode,head.branch,ifnull(b.clientname,'') as branchname,ifnull(b.client,'') as branchcode,'' as dbranchname,ifnull(d.client,'') as dept,ifnull(d.clientname,'') as deptname,head.deptid,'' as ddeptname,
        left(head.invoicedate,10) as invoicedate,head.invoiceno,

        head.phaseid, 
        ph.code as phase,

        head.modelid, 
        hm.model as housemodel, 
        
        head.blklotid, 
        bl.blk as blklot, 
        bl.lot,
        
        amh.line as amenityid,
        amh.description as amenityname,
        subamh.line as subamenityid,
        subamh.description as subamenityname
         
        ";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as b on b.clientid = head.branch
        left join client as d on d.clientid = head.deptid
        left join coa on coa.acno=head.contra
        left join projectmasterfile as project on project.line=head.projectid 

        left join phase as ph on ph.line = head.phaseid
        left join housemodel as hm on hm.line = head.modelid
        left join blklot as bl on bl.line = head.blklotid

        left join amenities as amh on amh.line= head.amenityid
        left join subamenities as subamh on subamh.line=head.subamenityid and subamh.amenityid=head.amenityid

        where head.trno = ? and num.doc=? and num.center = ? 
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.clientid = client.clientid
        left join client as b on b.clientid = head.branch
        left join client as d on d.clientid = head.deptid
        left join coa on coa.acno=head.contra 
        left join projectmasterfile as project on project.line=head.projectid         
        
        left join phase as ph on ph.line = head.phaseid
        left join housemodel as hm on hm.line = head.modelid
        left join blklot as bl on bl.line = head.blklotid

        left join amenities as amh on amh.line= head.amenityid
        left join subamenities as subamh on subamh.line=head.subamenityid and subamh.amenityid=head.amenityid

        where head.trno = ? and num.doc=? and num.center=? ";
    $head = $this->coreFunctions->opentable($qry, [$trno, $doc, $center, $trno, $doc, $center]);
    return $head;
  }

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
      case 'getunpaidselected':
        return $this->getunpaidselected($config);
        break;
      case 'generateewt':
        if ($config['params']['companyid'] == 10) {
          return $this->generateewt_afti($config);
        } else {
          return $this->generateewt($config);
        }

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
      case 'diagram':
        return $this->diagram($config);
        break;
      case 'duplicatedoc':
        return $this->othersClass->duplicateTransaction($config);
        break;
      case 'makepayment':
        return $this->othersClass->generateShortcutTransaction($config, 0, 'RRCV');
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

    //PV
    $qry = "
    select head.docno, date(head.dateid) as dateid, head.trno,
    CAST(concat('PV Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem, detail.refx
    from glhead as head
    left join gldetail as detail on head.trno = detail.trno
    where head.trno = ?
    group by head.docno, head.dateid, head.trno, detail.db, detail.cr, detail.refx";
    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
    if (!empty($t)) {
      $startx = 550;
      $a = 0;
      foreach ($t as $key => $value) {
        data_set(
          $nodes,
          'pv',
          [
            'align' => 'left',
            'x' => $startx + 400,
            'y' => 100,
            'w' => 250,
            'h' => 80,
            'type' => $t[$key]->docno,
            'label' => $t[$key]->rem,
            'color' => 'red',
            'details' => [$t[$key]->dateid]
          ]
        );

        // RR
        $qry = "
      select head.docno,
      date(head.dateid) as dateid,
      CAST(concat('Total RR Amt: ', round(sum(stock.ext),2), ' - ', 'Balance: ', round(ap.bal, 2)) as CHAR) as rem, 
      stock.refx, head.trno
      from glhead as head
      left join glstock as stock on head.trno = stock.trno
      left join apledger as ap on ap.trno = head.trno
      where head.trno=?
      group by head.docno, head.dateid, head.trno, ap.bal, stock.refx";

        $rrdata = $this->coreFunctions->opentable($qry, [$t[$key]->refx]);
        if (!empty($rrdata)) {
          foreach ($rrdata as $key1 => $value1) {
            data_set(
              $nodes,
              'rr',
              [
                'align' => 'left',
                'x' => $startx,
                'y' => 100,
                'w' => 250,
                'h' => 80,
                'type' => $rrdata[$key1]->docno,
                'label' => $rrdata[$key1]->rem,
                'color' => 'green',
                'details' => [$rrdata[$key1]->dateid]
              ]
            );

            array_push($links, ['from' => 'rr', 'to' => 'pv']);

            //PO
            $qry = "select po.trno,po.docno,left(po.dateid,10) as dateid,
          CAST(concat('Total PO Amt: ',round(sum(s.ext),2)) as CHAR) as rem,s.refx 
          from hpohead as po 
          left join hpostock as s on s.trno = po.trno
          where po.trno = ? 
          group by po.trno,po.docno,po.dateid,s.refx";
            $podata = $this->coreFunctions->opentable($qry, [$rrdata[$key1]->refx]);
            if (!empty($podata)) {
              foreach ($podata as $k => $v) {
                data_set(
                  $nodes,
                  'po',
                  [
                    'align' => 'right',
                    'x' => 200,
                    'y' => 50 + $a,
                    'w' => 250,
                    'h' => 80,
                    'type' => $podata[$k]->docno,
                    'label' => $podata[$k]->rem,
                    'color' => 'blue',
                    'details' => [$podata[$k]->dateid]
                  ]
                );
                array_push($links, ['from' => 'po', 'to' => 'rr']);
                $a = $a + 100;

                $qry = "select po.docno,left(po.dateid,10) as dateid,
                CAST(concat('Total PR Amt: ',round(sum(s.ext),2)) as CHAR) as rem 
                from hprhead as po left join hprstock as s on s.trno = po.trno  
                where po.trno = ? 
                group by po.docno,po.dateid";
                $prdata = $this->coreFunctions->opentable($qry, [$podata[$k]->refx]);
                if (!empty($prdata)) {
                  foreach ($prdata as $kk => $vv) {
                    data_set(
                      $nodes,
                      'pr',
                      [
                        'align' => 'left',
                        'x' => 10,
                        'y' => 50 + $a,
                        'w' => 250,
                        'h' => 80,
                        'type' => $prdata[$kk]->docno,
                        'label' => $prdata[$kk]->rem,
                        'color' => 'yellow',
                        'details' => [$prdata[$kk]->dateid]
                      ]
                    );
                    array_push($links, ['from' => 'pr', 'to' => 'po']);
                    $a = $a + 100;
                  }
                }
              }
            }

            //CV
            $cvqry = "
            select head.docno, date(head.dateid) as dateid, head.trno,
            CAST(concat('CV Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
            from glhead as head
            left join gldetail as detail on head.trno = detail.trno
            where detail.refx = ?
            union all
            select head.docno, date(head.dateid) as dateid, head.trno,
            CAST(concat('CV Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
            from lahead as head
            left join ladetail as detail on head.trno = detail.trno
            where detail.refx = ?";
            $cvdata = $this->coreFunctions->opentable($cvqry, [$t[$key]->trno, $t[$key]->trno]);
            if (!empty($cvdata)) {
              foreach ($cvdata as $key2 => $value2) {
                data_set(
                  $nodes,
                  'cv',
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
                array_push($links, ['from' => 'pv', 'to' => 'cv']);
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
            where stock.refx=?
            group by head.docno, head.dateid
            union all
            select head.docno as docno,left(head.dateid,10) as dateid,
            CAST(concat('Total DM Amt: ', round(sum(stock.ext), 2)) as CHAR) as rem 
            from lahead as head
            left join lastock as stock on stock.trno=head.trno 
            left join item on item.itemid=stock.itemid
            where stock.refx=?
            group by head.docno, head.dateid";
            $dmdata = $this->coreFunctions->opentable($dmqry, [$rrdata[$key1]->trno, $rrdata[$key1]->trno]);
            if (!empty($dmdata)) {
              foreach ($dmdata as $key2 => $value2) {
                data_set(
                  $nodes,
                  'dm',
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
                array_push($links, ['from' => 'rr', 'to' => 'dm']);
                $a = $a + 100;
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

  public function updateperitem($config)
  {
    $config['params']['data'] = $config['params']['row'];
    $isupdate = $this->additem('update', $config);
    $data = $this->opendetailline($config);
    if (!$isupdate) {
      $data[0]->errcolor = 'bg-red-2';
      return ['row' => $data, 'status' => true, 'msg' => 'Payment amount is greater than setup amount.'];
    } else {
      return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    }
  }

  public function updateitem($config)
  {
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $isupdate = $this->additem('update', $config);
      if ($isupdate['status'] == false) {
        break;
      }
    }
    $data = $this->opendetail($config['params']['trno'], $config);
    $data2 = json_decode(json_encode($data), true);
    //$isupdate = true;
    $msg1 = '';
    $msg2 = '';
    foreach ($data2 as $key => $value) {
      if ($data2[$key]['db'] == 0 && $data2[$key]['cr'] == 0) {
        $data[$key]->errcolor = 'bg-red-2';
        $isupdate = false;
        if ($data[$key]->refx == 0) {
          $msg1 = ' Some entries have zero value both debit and credit ';
        } else {
          $msg2 = ' Reference Amount is lower than encoded amount ';
        }
      }
    }
    if ($isupdate['status']) {
      return ['accounting' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    } else {
      if ($isupdate['msg'] == '') {
        return ['accounting' => $data, 'status' => true, 'msg' => 'Please check, some items have zero qty (' . $msg1 . ' / ' . $msg2 . ')'];
      } else {
        return ['accounting' => $data, 'status' => $isupdate['status'], 'msg' => $isupdate['msg']];
      }
    }
  } //end function

  public function addallitem($config)
  {
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $this->additem('insert', $config);
    }
    $data = $this->opendetail($config['params']['trno'], $config);
    //return ['accounting' => $data, 'gridheaddata' => $gridheaddata, 'status' => true, 'msg' => 'Successfully saved.'];
    return ['accounting' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
  } //end function

  // insert and update detail
  public function additem($action, $config)
  {
    $acno = $config['params']['data']['acno'];
    $acnoname = $config['params']['data']['acnoname'];
    $trno = $config['params']['trno'];
    $companyid = $config['params']['companyid'];
    $systype = $this->companysetup->getsystemtype($config['params']);
    $db = $config['params']['data']['db'];
    $cr = $config['params']['data']['cr'];
    $fdb = $config['params']['data']['fdb'];
    $fcr = $config['params']['data']['fcr'];
    $postdate = $config['params']['data']['postdate'];
    $rem = $config['params']['data']['rem'];
    if ($companyid == 19) {
      $rem = substr($config['params']['data']['rem'],  0, 500);
    }
    $project = 0;
    $client = $config['params']['data']['client'];
    $refx = 0;
    $linex = 0;
    $ref = '';
    $checkno = '';
    $isewt = false;
    $isvat = false;
    $isvewt = false;
    $isexcess = false;
    $ewtcode = '';
    $ewtrate = '';
    $subproject = 0;
    $damt = 0;
    $branch = 0;
    $deptid = 0;
    $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$acno]);
    $acnocat = $this->coreFunctions->getfieldvalue('coa', 'cat', 'acnoid =?', [$acnoid]);
    $type = '';


    $projectid = 0;
    $phaseid = 0;
    $modelid = 0;
    $blklotid = 0;
    $amenityid = 0;
    $subamenityid = 0;

    if (isset($config['params']['data']['refx'])) {
      $refx = $config['params']['data']['refx'];
    }
    if (isset($config['params']['data']['linex'])) {
      $linex = $config['params']['data']['linex'];
    }
    if (isset($config['params']['data']['ref'])) {
      $ref = $config['params']['data']['ref'];
    }
    if (isset($config['params']['data']['checkno'])) {
      $checkno = $config['params']['data']['checkno'];
    }
    if (isset($config['params']['data']['isvat'])) {
      $isvat = $config['params']['data']['isvat'];
    }

    if (isset($config['params']['data']['isewt'])) {
      $isewt = $config['params']['data']['isewt'];
    }

    if (isset($config['params']['data']['ewtcode'])) {
      $ewtcode = $config['params']['data']['ewtcode'];
    }

    if (isset($config['params']['data']['isexcess'])) {
      $isexcess = $config['params']['data']['isexcess'];
    }

    if ($ewtcode == '') {
      $ewtcode = $this->coreFunctions->getfieldvalue($this->head, "ewt", "trno=?", [$trno]);
      if ($ewtcode == '') {
        $ewtcode = isset($config['params']['data']['ewt']);
      }
    }

    if (isset($config['params']['data']['ewtrate'])) {
      $ewtrate = $config['params']['data']['ewtrate'];
    }

    if ($ewtrate == '') {
      $ewtrate = $this->coreFunctions->getfieldvalue($this->head, "ewtrate", "trno=?", [$trno]);
    }

    if (isset($config['params']['data']['isvewt'])) {
      $isvewt = $config['params']['data']['isvewt'];
    }

    if (isset($config['params']['data']['projectid'])) {
      $project = $config['params']['data']['projectid'];
    }

    if ($systype == 'REALESTATE') {

      if (isset($config['params']['data']['projectid'])) {
        $projectid = $config['params']['data']['projectid'];
      }
      if (isset($config['params']['data']['phaseid'])) {
        $phaseid = $config['params']['data']['phaseid'];
      }
      if (isset($config['params']['data']['modelid'])) {
        $modelid = $config['params']['data']['modelid'];
      }
      if (isset($config['params']['data']['blklotid'])) {
        $blklotid = $config['params']['data']['blklotid'];
      }
      if (isset($config['params']['data']['amenityid'])) {
        $amenityid = $config['params']['data']['amenityid'];
      }
      if (isset($config['params']['data']['subamenityid'])) {
        $subamenityid = $config['params']['data']['subamenityid'];
      }

      if ($projectid == 0) {
        $projectid = $this->coreFunctions->getfieldvalue($this->head, "projectid", "trno=?", [$trno]);
      }
      if ($phaseid == 0) {
        $phaseid = $this->coreFunctions->getfieldvalue($this->head, "phaseid", "trno=?", [$trno]);
      }
      if ($modelid == 0) {
        $modelid = $this->coreFunctions->getfieldvalue($this->head, "modelid", "trno=?", [$trno]);
      }
      if ($blklotid == 0) {
        $blklotid = $this->coreFunctions->getfieldvalue($this->head, "blklotid", "trno=?", [$trno]);
      }
      if ($amenityid == 0) {
        $amenityid = $this->coreFunctions->getfieldvalue($this->head, "amenityid", "trno=?", [$trno]);
      }
      if ($subamenityid == 0) {
        $subamenityid = $this->coreFunctions->getfieldvalue($this->head, "subamenityid", "trno=?", [$trno]);
      }
    }

    if ($companyid == 8) {
      $cbalias = $this->coreFunctions->getfieldvalue("coa", "left(alias,2)", "acnoid=?", [$acnoid]);

      if ($cbalias == 'CB') {
        $project = 0;
      } else {
        if ($project == '') {
          $project = $this->coreFunctions->getfieldvalue($this->head, "projectid", "trno=?", [$trno]);
        }
      }
    } else {
      if ($companyid == 39) { //cbbsi
        if ($project == 0 && $action == 'update') {
          $project = 0;
        } else {
          if ($project == 0) {
            $project = $this->coreFunctions->getfieldvalue($this->head, "projectid", "trno=?", [$trno]);
          }
        }
      } else {
        if ($project == 0) {
          $project = $this->coreFunctions->getfieldvalue($this->head, "projectid", "trno=?", [$trno]);
        }
      }
    }

    if (isset($config['params']['data']['subproject'])) {
      $subproject = $config['params']['data']['subproject'];
    }
    if (isset($config['params']['data']['branch'])) {
      $branch = $config['params']['data']['branch'];
    }

    if ($branch == 0) {
      if ($companyid == 10) {
        if ($acnocat == 'R' || $acnocat == 'E') {
          $branch = $this->coreFunctions->getfieldvalue($this->head, "branch", "trno=?", [$trno]);
        }
      } else {
        $branch = $this->coreFunctions->getfieldvalue($this->head, "branch", "trno=?", [$trno]);
      }
    }

    if ($companyid == 10) {
      $tax = $this->coreFunctions->getfieldvalue("client", "tax", "client=?", [$client]);
      if ($tax <> 0 && $isvat == '0') {
        $isvat = true;
      }
      $tax = $this->coreFunctions->getfieldvalue($this->head, "ewtrate", "trno=?", [$trno]);
      if ($tax <> 0  && $isewt == '0') {
        $isewt = true;
      }
    }

    // if ($companyid == 24) {
    //   $excise = $this->coreFunctions->getfieldvalue($this->head, "excessrate", "trno=?", [$trno]);
    //   if ($excise <> 0 && $isexcess == '0') {
    //     $isexcess = true;
    //   }
    // }

    if (isset($config['params']['data']['deptid'])) {
      $deptid = $config['params']['data']['deptid'];
    }

    if ($deptid == 0) {
      if ($companyid == 10) {
        if ($acnocat == 'R' || $acnocat == 'E') {
          $deptid = $this->coreFunctions->getfieldvalue($this->head, "deptid", "trno=?", [$trno]);
        }
      } else {
        $deptid = $this->coreFunctions->getfieldvalue($this->head, "deptid", "trno=?", [$trno]);
      }
    }

    if (isset($config['params']['data']['type'])) {
      $type = $config['params']['data']['type'];
    }


    $line = 0;
    if ($action == 'insert') {
      $qry = "select line as value from " . $this->detail . " where trno=? order by line desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$trno]);
      if ($line == '') {
        $line = 0;
      }
      $line = $line + 1;
      $config['params']['line'] = $line;
      if ($db != 0) {
        $damt = $db;
      } else {
        $damt = $cr;
      }
    } elseif ($action == 'update') {
      $config['params']['line'] = $config['params']['data']['line'];
      $line = $config['params']['data']['line'];
      $config['params']['line'] = $line;

      if ($db != 0) {
        $ddb = $this->coreFunctions->getfieldvalue($this->detail, 'db', 'trno=? and line =?', [$trno, $line]);

        if ($db != number_format($ddb, 2)) {
          $damt = $db;
        } else {
          $damt = $config['params']['data']['damt'];
        }
      } else {
        $dcr = $this->coreFunctions->getfieldvalue($this->detail, 'cr', 'trno=? and line =?', [$trno, $line]);
        if ($cr != number_format($dcr, 2)) {
          $damt = $cr;
        } else {
          $damt = $config['params']['data']['damt'];
        }
      }
    }


    $data = [
      'trno' => $trno,
      'line' => $line,
      'acnoid' => $acnoid,
      'client' => $client,
      'db' => $db,
      'cr' => $cr,
      'fdb' => $fdb,
      'fcr' => $fcr,
      'postdate' => $postdate,
      'rem' => $rem,
      'projectid' => $project,
      'subproject' => $subproject,
      'refx' => $refx,
      'linex' => $linex,
      'ref' => $ref,
      'checkno' => $checkno,
      'isewt' => $isewt,
      'isvat' => $isvat,
      'isvewt' => $isvewt,
      'isexcess' => $isexcess,
      'ewtcode' => $ewtcode,
      'ewtrate' => $ewtrate,
      'damt' => $damt,
      'deptid' => $deptid,
      'type' => $type
    ];

    if ($companyid == 10) {
      $data['branch'] = $branch;
    }

    if ($systype == 'REALESTATE') {
      $data['projectid'] = $projectid;
      $data['phaseid'] = $phaseid;
      $data['modelid'] = $modelid;
      $data['blklotid'] = $blklotid;
      $data['amenityid'] = $amenityid;
      $data['subamenityid'] = $subamenityid;
    }

    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $data['editdate'] = $current_timestamp;
    $data['editby'] = $config['params']['user'];
    $msg = '';
    $status = true;

    if ($isvewt == "true" && ($isewt == "true" || $isvat == "true")) {
      $msg = 'Already tagged as VEWT, remove tagging for EWT/VAT';
      return ['status' => false, 'msg' => $msg];
    }

    if ($action == 'insert') {
      $data['encodedby'] = $config['params']['user'];
      $data['encodeddate'] = $current_timestamp;
      $data['sortline'] =  $data['line'];
      if ($this->coreFunctions->sbcinsert($this->detail, $data) == 1) {
        $msg = 'Account was successfully added.';
        $this->logger->sbcwritelog($trno, $config, 'ACCTG', 'ADD - Line:' . $line . ' Code:' . $acno . ' DB:' . $db . ' CR:' . $cr . ' Client:' . $client . ' Date:' . $postdate);
        if ($refx != 0) {
          if (!$this->sqlquery->setupdatebal($refx, $linex, $acno, $config)) {
            $this->coreFunctions->sbcupdate($this->detail, ['db' => 0, 'cr' => 0, 'fdb' => 0, 'fcr' => 0], ['trno' => $trno, 'line' => $line]);
            $this->sqlquery->setupdatebal($refx, $linex, $acno, $config);
            $msg = "Payment Amount is greater than Amount Setup";
            $status = false;
          }
        }
        $row = $this->opendetailline($config);
        return ['row' => $row, 'status' => $status, 'msg' => $msg];
      } else {
        return ['status' => false, 'msg' => 'Add Account Failed'];
      }
    } elseif ($action == 'update') {
      $return = true;
      if ($this->coreFunctions->sbcupdate($this->detail, $data, ['trno' => $trno, 'line' => $line]) == 1) {
        if ($refx != 0) {
          if (!$this->sqlquery->setupdatebal($refx, $linex, $acno, $config)) {
            $this->coreFunctions->sbcupdate($this->detail, ['db' => 0, 'cr' => 0, 'fdb' => 0, 'fcr' => 0], ['trno' => $trno, 'line' => $line]);
            $this->sqlquery->setupdatebal($refx, $linex, $acno, $config);
            $return = false;
          }
        }
      } else {
        $return = false;
      }
      return ['status' => $return, 'msg' => ''];
    }
  } // end function

  public function deleteallitem($config)
  {
    $trno = $config['params']['trno'];
    $data = $this->coreFunctions->opentable('select coa.acno,t.refx,t.linex from ' . $this->detail . ' as t left join coa on coa.acnoid=t.acnoid where t.trno=? and t.refx<>0', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from detailinfo where trno=?', 'delete', [$trno]);
    foreach ($data as $key => $value) {
      $this->sqlquery->setupdatebal($data[$key]->refx, $data[$key]->linex, $data[$key]->acno, $config);
    }
    $this->logger->sbcwritelog($trno, $config, 'ACCTG', 'DELETED ALL ACCTG ENTRIES');
    return ['status' => true, 'msg' => 'Successfully deleted.', 'accounting' => []];
  }

  public function deleteitem($config)
  {
    $config['params']['trno'] = $config['params']['row']['trno'];
    $config['params']['line'] = $config['params']['row']['line'];
    $data = $this->opendetailline($config);
    //if(($data[0]->qa == $data[0]->qty)){
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = "delete from " . $this->detail . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
    $this->coreFunctions->execqry('delete from detailinfo where trno=? and line =?', 'delete', [$trno, $line]);
    $this->logger->sbcwritelog(
      $trno,
      $config,
      'DETAILINFO',
      'DELETE - Line:' . $line
        . ' Notes:' . $config['params']['row']['rem']
    );
    if ($data[0]->refx != 0) {
      $this->sqlquery->setupdatebal($data[0]->refx, $data[0]->linex, $data[0]->acno, $config);
    }
    $data = json_decode(json_encode($data), true);
    $this->logger->sbcwritelog($trno, $config, 'ACCTG', 'REMOVED - Line:' . $line . ' Code:' . $data[0]['acno'] . ' DB:' . $data[0]['db'] . ' CR:' . $data[0]['cr'] . ' Client:' . $data[0]['client'] . ' Date:' . $data[0]['postdate'] . ' Ref:' . $data[0]['ref']);
    return ['status' => true, 'msg' => 'Account was successfully deleted.'];
  } // end function

  public function getunpaidselected($config)
  {
    $trno = $config['params']['trno'];
    $companyid = $config['params']['companyid'];
    $systype = $this->companysetup->getsystemtype($config['params']);
    $rows = [];
    $data = $config['params']['rows'];


    foreach ($data as $key => $value) {
      $config['params']['data']['acno'] = $data[$key]['acno'];
      $config['params']['data']['acnoname'] = $data[$key]['acnoname'];
      if ($data[$key]['db'] != 0) {
        $config['params']['data']['db'] = 0;
        $config['params']['data']['cr'] = $data[$key]['bal'];
        $config['params']['data']['fdb'] = 0;
        $config['params']['data']['fcr'] = abs($data[$key]['fdb']);
      } else {
        $config['params']['data']['db'] = $data[$key]['bal'];
        $config['params']['data']['cr'] = 0;
        $config['params']['data']['fdb'] = $data[$key]['fdb'];
        $config['params']['data']['fcr'] = 0;
      }
      $config['params']['data']['postdate'] = $data[$key]['dateid'];
      $config['params']['data']['rem'] = $data[$key]['rem'];
      $config['params']['data']['projectid'] = $data[$key]['projectid'];
      $config['params']['data']['client'] = $data[$key]['client'];
      $config['params']['data']['refx'] = $data[$key]['trno'];
      $config['params']['data']['linex'] = $data[$key]['line'];
      if ($data[$key]['doc'] == 'AP') {
        if ($data[$key]['ref'] != '') {
          $config['params']['data']['ref'] = $data[$key]['ref'];
        } else {
          $config['params']['data']['ref'] = $data[$key]['docno'];
        }
      } else {
        $config['params']['data']['ref'] = $data[$key]['docno'];
      }

      if ($companyid == 56) { //homeworks
        $config['params']['data']['isvewt'] = 0;

        if ($data[$key]['doc'] == "AR" || $data[$key]['doc'] == "AP") {
          if (substr($data[$key]['ref'], 0, 3) == 'SJS') {
            if ($data[$key]['cr'] > 0) $config['params']['data']['isvewt'] = 1;
          }
        } else {
          if (substr($data[$key]['docno'], 0, 3) == 'SJS') {
            if ($data[$key]['cr'] > 0) $config['params']['data']['isvewt'] = 1;
          }
        }
      }

      if ($companyid == 8) {
        $config['params']['data']['subproject'] = $this->coreFunctions->datareader("select subproject as value from hjchead where trno = ?", [$data[$key]['trno']]);
      }

      if ($companyid == 10) {
        $config['params']['data']['branch'] = $data[$key]['branch'];
        $config['params']['data']['deptid'] = $data[$key]['deptid'];
      }

      if ($systype == 'REALESTATE') {

        // $config['params']['data']['projectid'] = $data[$key]->projectid;
        $config['params']['data']['phaseid'] = $data[$key]['phaseid'];
        $config['params']['data']['modelid'] = $data[$key]['modelid'];
        $config['params']['data']['blklotid'] = $data[$key]['blklotid'];
        $config['params']['data']['amenityid'] = $data[$key]['amenityid'];
        $config['params']['data']['subamenityid'] = $data[$key]['subamenityid'];
      }

      $return = $this->additem('insert', $config);
      if ($return['status']) {
        array_push($rows, $return['row'][0]);
      }
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.'];
  } //end function

  public function generateewt_afti($config)
  {
    $trno = $config['params']['trno'];
    $data = $config['params']['row'];
    $companyid = $config['params']['companyid'];
    $status = true;
    $msg = '';
    $entry = [];
    $vatrate = 0;
    $vatrate2 = 0;
    $vatvalue = 0;
    $ewtvalue = 0;
    $dbval = 0;
    $crval = 0;
    $db = 0;
    $cr = 0;
    $damt = 0;
    $line = 0;
    $forex = $data[0]['forex'];
    $cur = $data[0]['cur'];
    $ap = 0;
    $exp = 0;
    $ewtacno = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['APWT1']);
    $taxacno = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['TX1']);
    $project = $this->coreFunctions->getfieldvalue($this->head, 'projectid', 'trno=?', [$trno]);
    $branch = $this->coreFunctions->getfieldvalue($this->head, 'branch', 'trno=?', [$trno]);
    $dept = $this->coreFunctions->getfieldvalue($this->head, 'deptid', 'trno=?', [$trno]);

    if (empty($ewtacno) || empty($taxacno)) {
      $status = false;
      $msg = "Please setup account for EWT and Input VAT";
    } else {

      $this->coreFunctions->execqry("delete from ladetail where trno = " . $trno . " and acnoid =" . $ewtacno, "delete");
      $this->coreFunctions->execqry("delete from ladetail where trno = " . $trno . " and acnoid =" . $taxacno, "delete");

      $qry = "select line as value from " . $this->detail . " where trno=? order by line desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$trno]);
      if ($line == '') {
        $line = 0;
      }
      $line = $line + 1;

      $data2 = $this->coreFunctions->opentable("select
      FORMAT(SUM(d.db),2) as db,FORMAT(SUM(d.cr),2) as cr,
      case d.isewt when 0 then 'false' else 'true' end as isewt,case d.isvat when 0 then 'false' else 'true' end as isvat,
      case d.isvewt when 0 then 'false' else 'true' end as isvewt,d.ewtcode,d.ewtrate,sum(d.damt) as damt,d.forex,coa.acno,d.refx,d.linex,d.trno,d.line
      from ladetail as d
      left join lahead as head on head.trno=d.trno
      left join client on client.client=d.client
      left join projectmasterfile as proj on proj.line = d.projectid
      left join coa on d.acnoid=coa.acnoid
      where d.trno=? and (d.isvat =1 or d.isewt =1 or d.isvewt=1)
      group by d.isewt,d.isvewt,d.isvat,d.ewtcode,d.ewtrate,d.forex,coa.acno,d.refx,d.linex,d.trno,d.line
      union all
      select
      FORMAT(sum(d.db),2) as db,FORMAT(sum(d.cr),2) as cr,
      case d.isewt when 0 then 'false' else 'true' end as isewt,case d.isvat when 0 then 'false' else 'true' end as isvat,
      case d.isvewt when 0 then 'false' else 'true' end as isvewt,d.ewtcode,d.ewtrate,sum(d.damt) as damt,d.forex,coa.acno,d.refx,d.linex,d.trno,d.line
      from gldetail as d
      left join glhead as head on head.trno=d.trno
      left join client on client.clientid=d.clientid
      left join projectmasterfile as proj on proj.line = d.projectid
      left join coa on coa.acnoid=d.acnoid
      where d.trno=?  and (d.isvat =1 or d.isewt =1 or d.isvewt=1)
      group by d.isewt,d.isvewt,d.isvat,d.ewtcode,d.ewtrate,d.forex,coa.acno,d.refx,d.linex,d.trno,d.line", [$trno, $trno]);
      $data2 = json_decode(json_encode($data2), true);

      foreach ($data2 as $key => $value) {
        $value['db'] = $this->othersClass->sanitizekeyfield('db', $value['db']);
        $value['cr'] = $this->othersClass->sanitizekeyfield('cr', $value['cr']);
        $value['damt'] = $this->othersClass->sanitizekeyfield('damt', $value['damt']);


        if ($value['isvat'] == 'true' or $value['isewt'] == 'true' or $value['isvewt'] == 'true') {
          $damt   = floatval($value['damt']);
          if ($value['isvewt'] == 'true') { //for vewt
            if (floatval($value['db']) != 0) {
              $dbval = $damt;
              $crval = 0;
              $ewtvalue = (($dbval / 1.12) * ($value['ewtrate'] / 100));
            } else {
              $dbval = 0;
              $crval = $damt;
              $ewtvalue =  ((($crval / 1.12) * ($value['ewtrate'] / 100)) * -1);
            }
          }

          if ($value['isvat']  == 'true') { //for vat computation
            $vatrate = 1.12;
            $vatrate2 = .12;

            if (floatval($value['db']) != 0) {
              $dbval = $damt / $vatrate;
              $crval = 0;
              $vatvalue =  ($dbval * $vatrate2);
            } else {
              $dbval = 0;
              $crval = $damt / $vatrate;
              $vatvalue =   (($crval * $vatrate2) * -1);
            }
          }

          if ($value['isewt']  == 'true') { //for ewt
            if (floatval($value['db']) != 0) {
              if ($value['isvat'] == 'true') {
                $dbval = $damt / $vatrate;
                $ewtvalue = ($dbval * ($value['ewtrate'] / 100));
              } else {
                $dbval = $damt;
                $ewtvalue =  ($dbval * ($value['ewtrate'] / 100));
              }
              $crval = 0;
            } else {
              if ($value['isvat'] == 'true') {
                $crval = $damt / $vatrate;
                $ewtvalue =  (($crval * ($value['ewtrate'] / 100)) * -1);
              } else {
                $crval = $damt;
                $ewtvalue =  (($crval * ($value['ewtrate'] / 100)) * -1);
              }
              $dbval = 0;
            }
          }

          $exp =  ($dbval - $crval);

          $ret = $this->coreFunctions->execqry("update ladetail set db = " . round($dbval, 2) . ",cr=" . round($crval, 2) . ",fdb=" . round($dbval * $value['forex'], 2) . ",fcr=" . round($crval * $value['forex'], 2) . " where trno = " . $trno . " and line = " . $value['line'], "update");
          if ($value['refx'] != 0) {
            if (!$this->sqlquery->setupdatebal($value['refx'], $value['linex'], $value['acno'], $config)) {
              $this->coreFunctions->sbcupdate($this->detail, ['db' => 0, 'cr' => 0, 'fdb' => 0, 'fcr' => 0], ['trno' => $trno, 'line' => $value['line']]);
              $this->sqlquery->setupdatebal($value['refx'], $value['linex'], $value['acno'], $config);
              $msg = "Payment Amount is greater than Amount Setup";
              $status = false;
              $vatvalue = 0;
              $ewtvalue = 0;
            }
          }
        } else {
          $exp = (floatval($value['db']) - floatval($value['cr']));
        }

        //acctg entry
        if ($vatvalue != 0) {
          $this->coreFunctions->LogConsole('vat');
          $entry = [
            'line' => $line,
            'acnoid' => $taxacno,
            'client' => $data[0]['client'],
            'cr' => ($vatvalue < 0 ? abs(round($vatvalue, 2)) : 0),
            'db' => ($vatvalue < 0 ? 0 : abs(round($vatvalue, 2))),
            'postdate' => $data[0]['dateid'],
            'fdb' => ($vatvalue < 0 ? 0 : abs($vatvalue)) * $forex,
            'fcr' => ($vatvalue < 0 ? abs($vatvalue) : 0) * $forex,
            'rem' => "Auto entry",
            'cur' => $cur,
            'forex' => $forex,
            'projectid' => $project,
            'ewtcode' => '',
            'ewtrate' => ''
          ];

          if ($companyid == 10) {
            $entry['projectid'] = 0;
            $entry['branch'] = $branch;
            $entry['deptid'] = $dept;
          }
          $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
          $line = $line + 1;
        }

        if ($ewtvalue != 0 && $status == true) {
          $this->coreFunctions->LogConsole('ewt');
          $entry = [
            'line' => $line,
            'acnoid' => $ewtacno,
            'client' => $data[0]['client'],
            'cr' => ($ewtvalue < 0 ? 0 : abs(round($ewtvalue, 2))),
            'db' => ($ewtvalue < 0 ? abs(round($ewtvalue, 2)) : 0),
            'postdate' => $data[0]['dateid'],
            'fdb' => ($ewtvalue > 0 ? 0 : abs($ewtvalue)) * $forex,
            'fcr' => ($ewtvalue > 0 ? abs($ewtvalue) : 0) * $forex,
            'rem' => "Auto entry",
            'cur' => $cur,
            'forex' => $forex,
            'projectid' => $project,
            'ewtcode' => $value['ewtcode'],
            'ewtrate' => $value['ewtrate']
          ];
          if ($companyid == 10) {
            $entry['projectid'] = 0;
            $entry['branch'] = $branch;
            $entry['deptid'] = $dept;
          }
          $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
          $line = $line + 1;
        }

        $ap = ($exp + $vatvalue) - $ewtvalue;


        if ($ap != 0 && $status == true) {
          $apacno = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['AP2']);
          if ($companyid == 10) {
            $apacno = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['AP1']);
          }

          $entry = ['line' => $line, 'acnoid' => $apacno, 'client' => $data[0]['client'], 'cr' => ($ap < 0 ? 0 : abs(round($ap, 2))), 'db' => ($ap < 0 ? abs(round($ap, 2)) : 0), 'postdate' => $data[0]['dateid'], 'fdb' => ($ap > 0 ? 0 : abs($ap)) * $forex, 'fcr' => ($ap > 0 ? abs($ap) : 0) * $forex, 'rem' => "Auto entry", 'cur' => $cur, 'forex' => $forex, 'projectid' => $project, 'ewtcode' => '', 'ewtrate' => ''];
          if ($companyid == 10) {
            $entry['projectid'] = 0;
            $entry['branch'] = $branch;
            $entry['deptid'] = $dept;
          }
          $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
          $line = $line + 1;
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
        }

        if ($this->coreFunctions->sbcinsert($this->detail, $this->acctg) == 1) {
          $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING ENTRY SUCCESS');
          $msg = "AUTOMATIC ACCOUNTING ENTRY SUCCESS";
          $status = true;
          //return true;
        } else {
          $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING ENTRY FAILED');
          $msg = "AUTOMATIC ACCOUNTING ENTRY FAILED";
          $status = false;
        }
      }
    } //if (empty($ewtacno) || empty($taxacno)){

    $data = $this->opendetail($trno, $config);
    return ['accounting' => $data, 'status' => $status, 'msg' => $msg];
  } //end function

  public function generateewt($config)
  {
    $trno = $config['params']['trno'];
    $data = $config['params']['row'];
    $companyid = $config['params']['companyid'];
    $status = true;
    $msg = '';
    $entry = [];
    // $ewt = [];
    $vatrate = 0;
    $vatrate2 = 0;
    $vatvalue = 0;
    $ewtvalue = 0;
    $excisevalue = 0;
    $dbval = 0;
    $crval = 0;
    $db = 0;
    $cr = 0;
    $damt = 0;
    $line = 0;
    $forex = $data[0]['forex'];
    $cur = $data[0]['cur'];
    $ap = 0;
    $exp = 0;

    switch ($config['params']['companyid']) {
      case 3: //conti
      case 17: //unihome
      case 27: //nte
      case 36: //rozlab
      case 39: //CBBSI
      case 40: //cdo
      case 56: //homeworks
      case 29: //sbcmain
      case 60: //transpower
        $ewtacno = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['WT1']);
        break;
      default:
        $ewtacno = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['APWT1']);
        break;
    }

    $taxacno = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['TX1']);
    $exciseacno = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['TX3']);
    $project = $this->coreFunctions->getfieldvalue($this->head, 'projectid', 'trno=?', [$trno]);
    $branch = $this->coreFunctions->getfieldvalue($this->head, 'branch', 'trno=?', [$trno]);
    $dept = $this->coreFunctions->getfieldvalue($this->head, 'deptid', 'trno=?', [$trno]);
    $exciserate = $this->coreFunctions->getfieldvalue($this->head, 'excessrate', 'trno=?', [$trno]);


    if (empty($ewtacno) || empty($taxacno)) {
      $status = false;
      $msg = "Please setup account for EWT and Input VAT";
    } else {
      if ($companyid == 24) {
        if (empty($exciseacno)) {
          $status = false;
          $msg = "Please setup account for Excise Tax";
          goto here;
        }
      }

      $this->coreFunctions->execqry("delete from ladetail where trno = " . $trno . " and acnoid =" . $ewtacno, "delete");
      $this->coreFunctions->execqry("delete from ladetail where trno = " . $trno . " and acnoid =" . $taxacno, "delete");
      $this->coreFunctions->execqry("delete from ladetail where trno = " . $trno . " and acnoid =" . $exciseacno, "delete");
      //for adjusting accounts db and cr values

      foreach ($data as $key => $value) {
        $value['db'] = $this->othersClass->sanitizekeyfield('db', $value['db']);
        $value['cr'] = $this->othersClass->sanitizekeyfield('cr', $value['cr']);
        $value['damt'] = $this->othersClass->sanitizekeyfield('damt', $value['damt']);


        if ($value['isvat'] == 'true' or $value['isewt'] == 'true' or $value['isvewt'] == 'true') {
          $damt   = floatval($value['damt']);
          if ($value['isvewt'] == 'true') { //for vewt
            if (floatval($value['db']) != 0) {
              $dbval = $damt;
              $crval = 0;
              $ewtvalue = $ewtvalue + (($dbval / 1.12) * ($value['ewtrate'] / 100));
            } else {
              $dbval = 0;
              $crval = $damt;
              $ewtvalue = $ewtvalue + ((($crval / 1.12) * ($value['ewtrate'] / 100)) * -1);
            }
          }

          if ($value['isvat']  == 'true') { //for vat computation
            $vatrate = 1.12;
            $vatrate2 = .12;

            if (floatval($value['db']) != 0) {
              $dbval = $damt / $vatrate;
              $crval = 0;
              $vatvalue = $vatvalue + ($dbval * $vatrate2);
            } else {
              $dbval = 0;
              $crval = $damt / $vatrate;
              $vatvalue =  $vatvalue + (($crval * $vatrate2) * -1);
            }
          }

          if ($value['isewt']  == 'true') { //for ewt
            if (floatval($value['db']) != 0) {
              if ($value['isvat'] == 'true') {
                $dbval = $damt / $vatrate;
                $ewtvalue = $ewtvalue + ($dbval * ($value['ewtrate'] / 100));
                // array_push($ewt,($dbval*($value['ewtrate']/100)));
              } else {
                $dbval = $damt;
                $ewtvalue = $ewtvalue + ($dbval * ($value['ewtrate'] / 100));
                // array_push($ewt,($dbval*($value['ewtrate']/100)));
              }
              $crval = 0;
            } else {
              if ($value['isvat'] == 'true') {
                $crval = $damt / $vatrate;
                $ewtvalue = $ewtvalue + (($crval * ($value['ewtrate'] / 100)) * -1);
                // array_push($ewt,(($crval*($value['ewtrate']/100))*-1));
              } else {
                $crval = $damt;
                $ewtvalue = $ewtvalue + (($crval * ($value['ewtrate'] / 100)) * -1);
                // array_push($ewt,(($crval*($value['ewtrate']/100))*-1));
              }
              $dbval = 0;
            }
          }

          if ($value['isexcess']  == 'true') { //for ewt
            if (floatval($value['db']) != 0) {
              if ($value['isvat'] == 'true') {
                $dbval = $damt / $vatrate;
                $excisevalue = $excisevalue + ($dbval * ($exciserate / 100));
                // array_push($ewt,($dbval*($value['ewtrate']/100)));
              } else {
                $dbval = $damt;
                $excisevalue = $excisevalue + ($dbval * ($exciserate / 100));
                // array_push($ewt,($dbval*($value['ewtrate']/100)));
              }
              $crval = 0;
            } else {
              if ($value['isvat'] == 'true') {
                $crval = $damt / $vatrate;
                $excisevalue = $excisevalue + (($crval * ($exciserate / 100)) * -1);
                // array_push($ewt,(($crval*($value['ewtrate']/100))*-1));
              } else {
                $crval = $damt;
                $excisevalue = $excisevalue + (($crval * ($exciserate / 100)) * -1);
                // array_push($ewt,(($crval*($value['ewtrate']/100))*-1));
              }
              $dbval = 0;
            }
          }


          $dbval =  number_format($dbval, 2, '.', '');
          $crval =  number_format($crval, 2, '.', '');
          $exp = $exp + ($dbval - $crval);

          $ret = $this->coreFunctions->execqry("update ladetail set db = " . $dbval . ",cr=" . $crval . ",fdb=" . $dbval * $value['forex'] . ",fcr=" . $crval * $value['forex'] . " where trno = " . $trno . " and line = " . $value['line'], "update");
          if ($value['refx'] != 0) {
            if (!$this->sqlquery->setupdatebal($value['refx'], $value['linex'], $value['acno'], $config)) {
              $this->coreFunctions->sbcupdate($this->detail, ['db' => 0, 'cr' => 0, 'fdb' => 0, 'fcr' => 0], ['trno' => $trno, 'line' => $value['line']]);
              $this->sqlquery->setupdatebal($value['refx'], $value['linex'], $value['acno'], $config);
              $msg = "Payment Amount is greater than Amount Setup";
              $status = false;
              $vatvalue = 0;
              $ewtvalue = 0;
              $excisevalue = 0;
            }
          }
        } else {
          $exp = $exp + (floatval($value['db']) - floatval($value['cr']));
        }
      }

      $qry = "select line as value from " . $this->detail . " where trno=? order by line desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$trno]);
      if ($line == '') {
        $line = 0;
      }
      $line = $line + 1;

      //AUTO ENTRY FOR INPUT
      if ($vatvalue != 0) {
        $vatvalue =  number_format($vatvalue, 2, '.', '');
        $entry = [
          'line' => $line,
          'acnoid' => $taxacno,
          'client' => $data[0]['client'],
          'cr' => ($vatvalue < 0 ? abs($vatvalue) : 0),
          'db' => ($vatvalue < 0 ? 0 : abs($vatvalue)),
          'postdate' => $data[0]['dateid'],
          'fdb' => ($vatvalue < 0 ? 0 : abs($vatvalue)) * $forex,
          'fcr' => ($vatvalue < 0 ? abs($vatvalue) : 0) * $forex,
          'rem' => "Auto entry",
          'cur' => $cur,
          'forex' => $forex,
          'projectid' => $project
        ];

        if ($companyid == 10) {
          $entry['projectid'] = 0;
          $entry['branch'] = $branch;
          $entry['deptid'] = $dept;
        }
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
        $line = $line + 1;
      }
      //AUTO ENTRY FOR EWT
      if ($ewtvalue != 0 && $status == true) {
        $ewtvalue =  number_format($ewtvalue, 2, '.', '');
        $entry = [
          'line' => $line,
          'acnoid' => $ewtacno,
          'client' => $data[0]['client'],
          'cr' => ($ewtvalue < 0 ? 0 : abs($ewtvalue)),
          'db' => ($ewtvalue < 0 ? abs($ewtvalue) : 0),
          'postdate' => $data[0]['dateid'],
          'fdb' => ($ewtvalue > 0 ? 0 : abs($ewtvalue)) * $forex,
          'fcr' => ($ewtvalue > 0 ? abs($ewtvalue) : 0) * $forex,
          'rem' => "Auto entry",
          'cur' => $cur,
          'forex' => $forex,
          'projectid' => $project
        ];
        if ($companyid == 10) {
          $entry['projectid'] = 0;
          $entry['branch'] = $branch;
          $entry['deptid'] = $dept;
        }
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
        $line = $line + 1;
      }

      if ($excisevalue != 0 && $status == true) {

        $entry = [
          'line' => $line,
          'acnoid' => $exciseacno,
          'client' => $data[0]['client'],
          'cr' => ($excisevalue < 0 ? 0 : abs($excisevalue)),
          'db' => ($excisevalue < 0 ? abs($excisevalue) : 0),
          'postdate' => $data[0]['dateid'],
          'fdb' => ($excisevalue > 0 ? 0 : abs($excisevalue)) * $forex,
          'fcr' => ($excisevalue > 0 ? abs($excisevalue) : 0) * $forex,
          'rem' => "Auto entry",
          'cur' => $cur,
          'forex' => $forex,
          'projectid' => $project
        ];
        if ($companyid == 10) {
          $entry['projectid'] = 0;
          $entry['branch'] = $branch;
          $entry['deptid'] = $dept;
        }
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
        $line = $line + 1;
      }

      $ap = ($exp + $vatvalue) - $ewtvalue - $excisevalue;

      //AUTO ENTRY FOR AP
      if ($ap != 0 && $status == true) {
        $apacno = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['AP2']); //standard alias for APV
        switch ($companyid) {
          case 10:
            $apacno = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['AP1']);
            break;
          case 56: //homeworks
            $apacno = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['APV']);
            break;
          case 29: //sbc
            $apacno = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['APV']); //standard alias for APV
            break;
        }

        $entry = ['line' => $line, 'acnoid' => $apacno, 'client' => $data[0]['client'], 'cr' => ($ap < 0 ? 0 : abs($ap)), 'db' => ($ap < 0 ? abs($ap) : 0), 'postdate' => $data[0]['dateid'], 'fdb' => ($ap > 0 ? 0 : abs($ap)) * $forex, 'fcr' => ($ap > 0 ? abs($ap) : 0) * $forex, 'rem' => "Auto entry", 'cur' => $cur, 'forex' => $forex, 'projectid' => $project];
        if ($companyid == 10) {
          $entry['projectid'] = 0;
          $entry['branch'] = $branch;
          $entry['deptid'] = $dept;
        }
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }

      if (!empty($this->acctg)) {
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        foreach ($this->acctg as $key => $value) {
          foreach ($value as $key2 => $value2) {
            $this->acctg[$key][$key2] = $this->othersClass->sanitizekeyfield($key2, $value2);
          }

          $this->acctg[$key]['db'] = number_format($this->acctg[$key]['db'], 2, '.', '');
          $this->acctg[$key]['cr'] = number_format($this->acctg[$key]['cr'], 2, '.', '');
          $this->acctg[$key]['fdb'] = number_format($this->acctg[$key]['fdb'], 2, '.', '');
          $this->acctg[$key]['fcr'] = number_format($this->acctg[$key]['fcr'], 2, '.', '');
          $this->acctg[$key]['editdate'] = $current_timestamp;
          $this->acctg[$key]['editby'] = $config['params']['user'];
          $this->acctg[$key]['encodeddate'] = $current_timestamp;
          $this->acctg[$key]['encodedby'] = $config['params']['user'];
          $this->acctg[$key]['trno'] = $config['params']['trno'];
        }

        if ($this->coreFunctions->sbcinsert($this->detail, $this->acctg) == 1) {
          $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING ENTRY SUCCESS');
          $msg = "AUTOMATIC ACCOUNTING ENTRY SUCCESS";
          $status = true;
          //return true;
        } else {
          $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING ENTRY FAILED');
          $msg = "AUTOMATIC ACCOUNTING ENTRY FAILED";
          $status = false;
        }
      }
    } //if (empty($ewtacno) || empty($taxacno)){
    here:
    $data = $this->opendetail($trno, $config);
    return ['accounting' => $data, 'status' => $status, 'msg' => $msg];
  } //end function

  public function getpaysummaryqry($config)
  {
    return "
    select apledger.docno,apledger.trno,apledger.line,ctbl.clientname,ctbl.client,forex.cur,forex.curtopeso as forex,apledger.acnoid,coa.acno,coa.acnoname,cntnum.center,
    apledger.clientid,apledger.db,apledger.cr, apledger.bal ,left(apledger.dateid,10) as dateid,
    abs(apledger.fdb-apledger.fcr) as fdb,glhead.yourref,gldetail.rem as drem,glhead.rem as hrem,gldetail.projectid,gldetail.subproject,
    gldetail.stageid,gldetail.branch,gldetail.deptid,gldetail.poref,gldetail.podate,coa.alias,gldetail.postdate,glhead.tax,case glhead.vattype when '' then 'NON-VATABLE' else glhead.vattype end as vattype,glhead.ewt,glhead.ewtrate from (apledger
    left join coa on coa.acnoid=apledger.acnoid)
    left join glhead on glhead.trno = apledger.trno
    left join gldetail on gldetail.trno=apledger.trno and gldetail.line=apledger.line
    left join cntnum on cntnum.trno = glhead.trno
    left join client as ctbl on ctbl.clientid = apledger.clientid
    left join forex_masterfile as forex on forex.line = ctbl.forexid
    where cntnum.trno = ? and apledger.bal<>0 and coa.alias <> 'APWT1'";
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
    $dataparams = $config['params']['dataparams'];

    if ($companyid == 36 || $companyid == 3 || $companyid == 39 || $companyid == 40) { //rozlab, conti, cbssi, cdo
      if (isset($dataparams['approved'])) $this->othersClass->writeSignatories($config, 'approved', $dataparams['approved']);
      if (isset($dataparams['received'])) $this->othersClass->writeSignatories($config, 'received', $dataparams['received']);
      if (isset($dataparams['checked'])) $this->othersClass->writeSignatories($config, 'checked', $dataparams['checked']);
      if (isset($dataparams['position'])) $this->othersClass->writeSignatories($config, 'position', $dataparams['position']);
      if (isset($dataparams['payor'])) $this->othersClass->writeSignatories($config, 'payor', $dataparams['payor']);
      if (isset($dataparams['tin'])) $this->othersClass->writeSignatories($config, 'tin', $dataparams['tin']);
    }

    switch ($companyid) {
      case 36: //rozlab
        if (isset($dataparams['audited'])) $this->othersClass->writeSignatories($config, 'audited', $dataparams['audited']);
        break;
      case 3: //conti
      case 39: //cbbsi
      case 40: //cdo
        if (isset($dataparams['prepared'])) $this->othersClass->writeSignatories($config, 'prepared', $dataparams['prepared']);
        break;
      case 8: //maxipro
        if (isset($dataparams['checked'])) $this->othersClass->writeSignatories($config, 'checked', $dataparams['checked']);
        break;
    }

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
