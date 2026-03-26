<?php

namespace App\Http\Classes\modules\ati;

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

class cv
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'CHECK VOUCHER';
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
  public $statlogs = 'cntnum_stat';
  public $tablelogs_del = 'del_table_log';
  private $stockselect;
  public $defaultContra = 'PC1';

  private $fields = ['trno', 'docno', 'dateid', 'client', 'clientname', 'yourref', 'ourref', 'rem', 'forex', 'cur', 'address', 'tax', 'vattype', 'projectid', 'ewt', 'ewtrate', 'brtrno', 'contra', 'isencashment', 'isonlineencashment', 'paymode', 'hacno', 'hacnoname', 'modeofpayment', 'salestype'];
  private $except = ['trno', 'dateid'];
  private $blnfields = ['isencashment', 'isonlineencashment'];
  private $otherfields = ['trno', 'ischqreleased', 'ispaid', 'ispartial', 'trnxtype'];
  private $acctg = [];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  private $reporter;
  private $helpClass;


  public $showfilterlabel = [
    ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
    ['val' => 'forapproval', 'label' => 'For Supervisor Approval', 'color' => 'primary'],
    ['val' => 'approved', 'label' => 'Approved', 'color' => 'primary'],
    ['val' => 'itemscollected', 'label' => 'Forwarded to accounting', 'color' => 'primary'],
    ['val' => 'forwardop', 'label' => 'Forwarded to OP', 'color' => 'primary'],
    ['val' => 'posted', 'label' => 'Posted', 'color' => 'primary']
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
      'view' => 117,
      'edit' => 118,
      'new' => 119,
      'save' => 120,
      // 'change'=>121, remove change doc
      'delete' => 122,
      'print' => 123,
      'lock' => 124,
      'unlock' => 125,
      'post' => 126,
      'unpost' => 127,
      'additem' => 128,
      'edititem' => 129,
      'deleteitem' => 130,
      'doneapproved' => 3985,
      'doneinitialchecking' => 3986,
      'donefinalchecking' => 3987,
      'payreleased' => 3988,
      'forwardencoder' => 3989,
      'forwardwh' => 3990,
      'itemscollected' => 3991,
      'forwardop' => 3992,
      'forwardasset' => 3993,
      'forliquidation' => 3994,
      'forwardacctg' => 3995,
      'forwardchecking' => 3996,
      'checkissued' => 3997,
      'paidcv' => 3998,
      'checkedcv' => 3999,
      'advancesclr' => 4000,
      'soareceived' => 4001,
      'forposting' => 4002,
      'allowvoid' => 4196,
      'voidpayment' => 4406
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
    $modeofpayment = 5;
    $salestype = 6;
    $yourref = 7;
    $cr = 8;
    $rem = 9;
    $postdate = 10;

    $getcols = ['action', 'lblstatus', 'listdocument', 'listdate', 'listclientname',  'modeofpayment', 'salestype', 'yourref', 'cr', 'rem', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];

    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$listclientname]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;';
    $cols[$modeofpayment]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';

    $cols[$salestype]['label'] = 'Payment Terms';
    $cols[$postdate]['label'] = 'Post Date';
    $cols[$modeofpayment]['label'] = 'Payment Type';

    $cols[$cr]['type'] = 'coldel';
    $cols[$rem]['type'] = 'coldel';

    $userid = $config['params']['adminid'];
    $isapprover = $this->coreFunctions->getfieldvalue("employee", "isapprover", "empid=?", [$userid]);
    if ($isapprover == '')  $isapprover = 0;

    $this->showfilterlabel = [];
    array_push($this->showfilterlabel, ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary']);
    array_push($this->showfilterlabel, ['val' => 'partial', 'label' => 'Partial Payment', 'color' => 'primary']);
    array_push($this->showfilterlabel, ['val' => 'all', 'label' => 'ALL', 'color' => 'primary']);
    // change to lookup

    array_push($this->showfilterlabel, ['val' => 'posted', 'label' => 'Posted', 'color' => 'primary']);

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
    $limit = '';
    $laleftjoin = '';
    $glleftjoin = '';
    $grpby = '';
    $lacr = '';
    $glcr = '';
    $searchfield = [];
    $filtersearch = "";
    $filterid = "";
    $search = $config['params']['search'];

    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'head.clientname', 'head.yourref', 'head.ourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    } else {
      $limit = 'limit 150';
    }

    $type = isset($config['params']['doclistingparam']['typecode']) ? $config['params']['doclistingparam']['typecode'] : '';
    $paymentterms = isset($config['params']['doclistingparam']['salestype']) ? $config['params']['doclistingparam']['salestype'] : '';

    $adminid = $config['params']['adminid'];
    if ($adminid != 0) {
      $trnxtype = $this->coreFunctions->getfieldvalue("client", "deptcode", "clientid=?", [$config['params']['adminid']]);
      $filterid = " and info.trnxtype = '" . $trnxtype . "' ";
      $isapprover = $this->coreFunctions->getfieldvalue("employee", "isapprover", "empid=?", [$adminid], '', true);
      if ($isapprover == 1) {

        if ($itemfilter == "all") $itemfilter = 'all';
        $type = isset($config['params']['doclistingparam']['typecode']) ? $config['params']['doclistingparam']['typecode'] : 'forapproval';
      }
    }

    $status = "'DRAFT'";
    switch ($itemfilter) {
      case 'draft':
        $condition = ' and num.postdate is null and num.statid in (0,16)';
        $status = "ifnull(stat.status,'DRAFT')";
        break;

      case 'all':
        $condition = ' and num.postdate is null and num.statid not in (0,16)';
        $status = "ifnull(stat.status,'DRAFT')";

        switch ($type) {
          case 'forapproval':
            $condition = ' and num.postdate is null and num.statid=10';
            $status = "stat.status";
            break;
          case 'approved':
            $condition = ' and num.postdate is null and num.statid=36';
            $status = "stat.status";
            break;
          case 'initialchecking':
            $condition = ' and num.postdate is null and num.statid=57';
            $status = "if(ifnull(info.status,'')<>'',info.status,stat.status)";
            break;
          case 'finalchecking':
            $condition = ' and num.postdate is null and num.statid in (58,67,42,69)';
            $status = "if(num.statid=42,'Cash Released',stat.status)";
            break;
          case 'paymentreleased':
            $condition = " and num.postdate is null and (num.statid=50 or (num.statid in (48,60,61,59) and head.salestype<>'COD Cheque'))";
            $status = "if(ifnull(info.status,'')<>'' and num.statid=50,info.status,stat.status)";
            break;
          case 'paid':
            $condition = " and num.postdate is null and (num.statid=66 or (num.statid in (48,60,61,59) and head.salestype='COD Cheque'))";
            $status = "if(ifnull(info.status,'')<>'' and num.statid=66,info.status,stat.status)";
            break;
          case 'forliquidation':
            $condition = ' and num.postdate is null and num.statid=62';
            $status = "stat.status";
            break;
          case 'forwardacctg':
            $condition = ' and num.postdate is null and num.statid=63';
            $status = "if(ifnull(info.status,'')<>'',info.status,stat.status)";
            break;
          case 'forchecking':
            $condition = ' and num.postdate is null and num.statid in (64,65)';
            $status = "if(ifnull(info.status,'')<>'' and num.statid=65,info.status,stat.status)";
            break;
          case 'forposting':
            $condition = ' and num.postdate is null and num.statid=39';
            $status = "stat.status";
            break;

          case 'forwardop':
            $condition = ' and num.postdate is null and num.statid in (49,67,68)';
            $status = "if(ifnull(info.status,'')<>'' and num.statid=49,info.status,stat.status)";
            break;
          case 'voidpayment':
            $condition = ' and num.statid =26 ';
            $status = "'Void'";
            break;
        }

        if ($paymentterms != '') {
          $condition .= " and head.salestype='" . $paymentterms . "'";
        }
        break;

      case 'partial':
        $condition = ' and num.postdate is null and info.ispartial=1';
        $status = "ifnull(stat.status,'DRAFT')";
        break;

      case 'posted':
        $condition = ' and num.postdate is not null ';
        $status = "'POSTED'";
        break;
    }
    $companyid = $config['params']['companyid'];
    switch ($companyid) {
      default:
        $dateid = "left(head.dateid,10) as dateid";
        $orderby =  "order by  dateid desc, docno desc";
        break;
    }

    $filteruser = '';
    $adminid = $config['params']['adminid'];
    if ($adminid != 0) {
      $paytype = $this->coreFunctions->getfieldvalue("client", "type", "clientid=?", [$adminid]);
      switch ($paytype) {
        case 'CASH':
          $filteruser = " and head.modeofpayment='CASH'";
          break;
        case 'CHEQUE/Terms':
          $filteruser = " and head.modeofpayment<>'CASH' and head.modeofpayment<>''";
          break;
        default:
          $filteruser = " and head.modeofpayment<>''";
          break;
      }
    }

    $qry = "select head.trno,head.docno,head.clientname,$dateid $lacr , concat(" . $status . ",if(info.instructions='For Revision',' (For Revision)','')) as stat,
                   head.createby,head.editby,head.viewby,num.postedby, date(num.postdate) as postdate,
                   head.yourref, head.ourref,head.rem, head.modeofpayment, head.salestype           
            from " . $this->head . " as head 
            left join " . $this->tablenum . " as num on num.trno=head.trno " . $laleftjoin . "
             left join trxstatus as stat on stat.line=num.statid
             left join cntnuminfo as info on info.trno=head.trno 
            where head.doc=? and num.center = ? $filterid and CONVERT(head.dateid,DATE)>=? 
                  and CONVERT(head.dateid,DATE)<=? " . $condition . $filteruser . $grpby . " " . $filtersearch . " 
            union all
            select head.trno,head.docno,head.clientname,$dateid $glcr, " . $status . " as stat,
                   head.createby,head.editby,head.viewby, num.postedby, date(num.postdate) as postdate,
                   head.yourref, head.ourref,head.rem, head.modeofpayment, head.salestype           
            from " . $this->hhead . " as head 
            left join " . $this->tablenum . " as num on num.trno=head.trno " . $glleftjoin . "
             left join trxstatus as stat on stat.line=num.statid
             left join hcntnuminfo as info on info.trno=head.trno 
            where head.doc=? and num.center = ? $filterid and CONVERT(head.dateid,DATE)>=? 
                  and CONVERT(head.dateid,DATE)<=? " . $condition . $filteruser . $grpby . " " . $filtersearch . " 
           $orderby $limit";

    $data = $this->coreFunctions->opentable($qry, [$doc, $center, $date1, $date2, $doc, $center, $date1, $date2]);
    return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
  }

  public function paramsdatalisting($config)
  {
    $fields = ['salestype'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'salestype.label', 'Payment Terms');
    data_set($col1, 'salestype.action', 'lookuppaymentterms');
    data_set($col1, 'salestype.lookupclass', 'lookuppaymentterms');

    $fields = ['type'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'type.label', 'Status');
    data_set($col2, 'type.action', 'lookupcvtransstatus');
    data_set($col2, 'type.lookupclass', 'lookupcvtransstatus');

    data_set($col2, 'type.addedparams', ['salestype']);


    $status = '';
    $statusname = '';
    $adminid = $config['params']['adminid'];
    if ($adminid != 0) {
      $isapprover = $this->coreFunctions->getfieldvalue("employee", "isapprover", "empid=?", [$adminid], '', true);
      if ($isapprover == 1) {
        $status = 'forapproval';
        $statusname = 'For Approval';
      }
    }

    $data = $this->coreFunctions->opentable("SELECT '' AS salestype, '" . $statusname . "' as type, '" . $status . "' as typecode");

    return ['status' => true, 'data' => $data[0], 'txtfield' => ['col1' => $col1, 'col2' => $col2]];
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

    return $buttons;
  } // createHeadbutton


  public function createtab2($access, $config)
  {
    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrycntnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);

    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];

    $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrycntnumtodo', 'label' => 'To Do', 'access' => 'view']];
    $objtodo = $this->tabClass->createtab($tab, []);
    $return['To Do'] = ['icon' => 'fa fa-list', 'tab' => $objtodo];


    return $return;
  }

  public function createTab($access, $config)
  {
    $editamt = $this->othersClass->checkAccess($config['params']['user'], 4143);
    $editapproved = $this->othersClass->checkAccess($config['params']['user'], 4144);
    $allowvoid = $this->othersClass->checkAccess($config['params']['user'], 4196);
    $admin = $this->othersClass->checkAccess($config['params']['user'], 4387);

    // $action = 0;
    // $isewt = 1;
    // $isvat = 2;
    // $isvewt = 3;
    // $ewtcode = 4;
    // $db = 5;
    // $cr = 6;
    // $postdate = 7;
    // $checkno = 8;
    // $rem = 9;
    // $project = 10;
    // $subprojectname = 11;
    // $client = 12;
    // $ref = 13;
    // $invoiceno = 14;
    // $invoicedate = 15;
    // $void = 16;
    // $acnoname = 17;
    // $si2 = 18;
    // $si1 = 19;
    // $prref = 20;
    // $appamt = 21;

    $columns = ['action', 'notectr', 'isewt', 'isvat', 'isvewt', 'ewtcode', 'db', 'cr', 'surcharge', 'postdate', 'checkno', 'rem', 'project', 'subprojectname', 'client', 'ref',  'invoiceno', 'invoicedate', 'void', 'acnoname', 'si2', 'si1', 'prref', 'appamt'];

    foreach ($columns as $key => $value) {
      $$value = $key;
    }

    $tab = [
      $this->gridname => [
        'gridcolumns' =>  $columns,
        'headgridbtns' => ['viewacctginfo', 'viewref', 'viewdiagram', 'viewsobreakdown']
      ],

      'stathistorytab' => ['action' => 'tableentry', 'lookupclass' => 'tabstathistory', 'label' => 'REVISION REMARKS', 'checkchanges' => 'tableentry'],
      'notehistorytab' => ['action' => 'tableentry', 'lookupclass' => 'tabnotehistory', 'label' => 'NOTES HISTORY', 'checkchanges' => 'tableentry']
    ];



    $stockbuttons = ['save', 'delete'];
    if ($this->companysetup->getiseditsortline($config['params'])) {
      array_push($stockbuttons, 'sortline');
    }
    array_push($stockbuttons, 'detailinfo', 'rrstockinfo', 'viewcvitems', 'viewhistoricalcomments', 'viewnotes');

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0]['accounting']['columns'][$ref]['lookupclass'] = 'refcv';

    $obj[0]['accounting']['columns'][$client]['lookupclass'] = 'vendordetail';
    $obj[0]['accounting']['columns'][$action]['checkfield'] = 'void';

    $obj[0]['accounting']['columns'][$postdate]['style'] = '150px;whiteSpace: normal; min-width:150px;max-width:150px;';
    $obj[0]['accounting']['columns'][$checkno]['style'] = '150px;whiteSpace: normal; min-width:150px;max-width:150px;text-align:left;';
    $obj[0]['accounting']['columns'][$checkno]['align'] = 'text-left';
    $obj[0]['accounting']['columns'][$client]['style'] = '200px;whiteSpace: normal; min-width:200px;max-width:200px;';
    $obj[0]['accounting']['columns'][$ref]['style'] = '150px;whiteSpace: normal; min-width:150px;max-width:150px;';

    $obj[0]['accounting']['columns'][$rem]['style'] = '200px;whiteSpace: normal; min-width:200px;max-width:200px;';

    $obj[0]['accounting']['columns'][$action]['style'] = '150px;whiteSpace: normal; min-width:150px;max-width:150px;';

    $obj[0]['accounting']['columns'][$si1]['label'] = 'Your Ref (RR)';
    $obj[0]['accounting']['columns'][$prref]['label'] = 'Payment Ref.';
    $obj[0]['accounting']['columns'][$surcharge]['label'] = 'Surcharge Amt.';

    $obj[0]['accounting']['columns'][$surcharge]['readonly'] = true;

    $obj[0]['accounting']['columns'][$prref]['type'] = 'coldel';

    if ($this->companysetup->getsystemtype($config['params']) != 'CAIMS') {
      $obj[0]['accounting']['columns'][$rem]['type'] = 'coldel';
      $obj[0]['accounting']['columns'][$subprojectname]['type'] = 'coldel';
    }



    $obj[0]['accounting']['columns'][$invoiceno]['type'] = 'coldel';
    $obj[0]['accounting']['columns'][$invoicedate]['type'] = 'coldel';
    $obj[0]['accounting']['columns'][$isewt]['type'] = 'coldel';
    $obj[0]['accounting']['columns'][$isvat]['type'] = 'coldel';
    $obj[0]['accounting']['columns'][$isvewt]['type'] = 'coldel';
    $obj[0]['accounting']['columns'][$ewtcode]['type'] = 'coldel';
    $obj[0]['accounting']['columns'][$rem]['type'] = 'coldel';
    if (!$editamt) {
      $obj[0]['accounting']['columns'][$db]['readonly'] = true;
      $obj[0]['accounting']['columns'][$cr]['readonly'] = true;
    }

    if (!$allowvoid) {
      $obj[0]['accounting']['columns'][$void]['type'] = "coldel";
    }

    if (!$admin) {
      $obj[0]['accounting']['columns'][$appamt]['type'] = "coldel";
    }

    $obj[0]['accounting']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $poreqpayment = 0;
    $unpaid = 1;
    $unpaiddm = 2;
    $additem = 3;
    $saveitem = 4;
    $deleteallitem = 5;
    $generateewt = 6;
    $payreleased = 7;

    $tbuttons = ['poreqpayment', 'unpaid', 'unpaiddm', 'additem', 'saveitem', 'deleteallitem', 'generateewt'];

    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[$poreqpayment]['label'] = "ADV";
    $obj[$additem]['label'] = "ADD ACCOUNT";
    $obj[$additem]['action'] = "adddetail";
    $obj[$saveitem]['label'] = "SAVE ACCOUNT";
    $obj[$deleteallitem]['label'] = "DELETE ACCOUNT";
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['docno', 'client', 'clientname', 'address'];

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.label', 'Payee');
    data_set($col1, 'client.lookupclass', 'allclienthead');
    data_set($col1, 'docno.label', 'Transaction#');

    $fields = ['dateid', 'dvattype', 'dewt'];
    $col2 = $this->fieldClass->create($fields);

    $fields = ['yourref', 'ourref', ['cur', 'forex'], ['modeofpayment', 'salestype'], ['pdeadline', 'ispartial'], 'voidpayment'];
    $col3 = $this->fieldClass->create($fields);

    data_set($col3, 'modeofpayment.label', 'Payment Type');
    data_set($col3, 'modeofpayment.action', 'lookuppaymenttype');
    data_set($col3, 'modeofpayment.lookupclass', 'lookuppaymenttype');
    data_set($col3, 'modeofpayment.required', true);

    data_set($col3, 'salestype.label', 'Payment Terms');
    data_set($col3, 'salestype.action', 'lookuppaymentterms');
    data_set($col3, 'salestype.lookupclass', 'lookuppaymentterms');
    data_set($col3, 'salestype.required', true);

    data_set($col3, 'pdeadline.class', 'sbccsreadonly');

    data_set($col3, 'voidpayment.access', 'voidpayment');

    $fields = [
      'rem',
      'updatepostedinfo',
      'forrevision',
      'forapproval',
      'doneapproved',
      'doneinitialchecking',
      'donefinalchecking',
      'tagreleased',
      'forwardencoder',
      'forwardwh',
      'itemscollected',
      'forwardop',
      'forwardasset',
      'forliquidation',
      'forwardacctg',
      'forchecking',
      'forposting',
      'checkissued',
      'paid',
      'checked',
      'advancesclr',
      'soareceived',
      'post'
    ];
    if ($this->companysetup->getistodo($config['params'])) {
      array_push($fields, 'donetodo');
    }
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'updatepostedinfo.label', 'UPDATE INFO');
    data_set($col4, 'updatepostedinfo.access', 'view');

    data_set($col4, 'tagreleased.confirmlabel', 'Are you sure you want to tag as Payment Released?');
    data_set($col4, 'tagreleased.label', 'PAYMENT RELEASED');
    data_set($col4, 'forposting.label', 'APPROVED');

    data_set($col4, 'checked.type', 'actionbtn');
    data_set($col4, 'checked.style', 'width:100%');
    data_set($col4, 'checked.label', 'CHECKED');
    data_set($col4, 'checked.action', 'checked');
    data_set($col4, 'checked.lookupclass', 'stockstatusposted');
    data_set($col4, 'checked.icon', 'check');
    data_set($col4, 'checked.access', 'save');
    data_set($col4, 'checked.name', 'backlisting');

    data_set($col4, 'post.type', 'actionbtn');
    data_set($col4, 'post.style', 'width:100%');
    data_set($col4, 'post.label', 'For Posting');
    data_set($col4, 'post.action', 'posting');
    data_set($col4, 'post.lookupclass', 'stockstatusposted');
    data_set($col4, 'post.icon', 'check');
    data_set($col4, 'post.access', 'save');
    data_set($col4, 'post.name', 'backlisting');

    data_set($col4, 'forwardop.access', 'forwardop');
    data_set($col4, 'forwardacctg.access', 'forwardacctg');
    data_set($col4, 'forwardasset.access', 'forwardasset');
    data_set($col4, 'forliquidation.access', 'forliquidation');

    data_set($col4, 'doneapproved.access', 'doneapproved');
    data_set($col4, 'doneinitialchecking.access', 'doneinitialchecking');
    data_set($col4, 'donefinalchecking.access', 'donefinalchecking');

    data_set($col4, 'tagreleased.access', 'payreleased');
    data_set($col4, 'forchecking.access', 'forwardchecking');
    data_set($col4, 'checkissued.access', 'checkissued');
    data_set($col4, 'paid.access', 'paidcv');
    data_set($col4, 'checked.access', 'checkedcv');
    data_set($col4, 'advancesclr.access', 'advancesclr');
    data_set($col4, 'soareceived.access', 'soareceived');
    data_set($col4, 'post.access', 'forposting');
    data_set($col4, 'forposting.access', 'forposting');
    data_set($col4, 'itemscollected.access', 'itemscollected');
    data_set($col4, 'forwardwh.access', 'forwardwh');
    data_set($col4, 'forwardencoder.access', 'forwardencoder');
    // data_set($col4, 'voidpayment.access', 'voidpayment');

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
    $data[0]['address'] = '';
    $data[0]['yourref'] = '';
    $data[0]['shipto'] = '';
    $data[0]['ourref'] = '';
    $data[0]['rem'] = '';
    $data[0]['terms'] = '';
    $data[0]['forex'] = 1;
    $data[0]['cur'] = $this->companysetup->getdefaultcurrency($params);
    $data[0]['projectcode'] = '';
    $data[0]['projectid'] = '0';
    $data[0]['projectname'] = '';
    $data[0]['tax'] = 0;
    $data[0]['ewt'] = '';
    $data[0]['ewtrate'] = 0;
    $data[0]['brtrno'] = 0;
    $data[0]['tax'] = 0;
    $data[0]['vattype'] = 'Non-vat';
    $data[0]['contra'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', [$this->defaultContra]);
    $data[0]['acnoname'] = $this->coreFunctions->getfieldvalue('coa', 'acnoname', 'acno=?', [$data[0]['contra']]);
    $data[0]['isencashment'] = '0';
    $data[0]['isonlineencashment'] = '0';
    $data[0]['paymode'] = '';
    $data[0]['hacno'] = '';
    $data[0]['hacnoname'] = '';
    $data[0]['modeofpayment'] = '';
    $data[0]['salestype'] = '';
    $data[0]['ischqreleased'] = '0';
    $data[0]['ispaid'] = '0';
    $data[0]['ispartial'] = '0';
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
    $adminid = $config['params']['adminid'];
    $payreleased = $this->othersClass->checkAccess($config['params']['user'], 4406);
    $tablenum = $this->tablenum;
    $filterid = "";
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

    if ($adminid != 0) {
      $trnxtype = $this->coreFunctions->getfieldvalue("client", "deptcode", "clientid=?", [$config['params']['adminid']]);
      $filterid = " and info.trnxtype = '" . $trnxtype . "' ";
    }
    $head = [];
    $islocked = $this->othersClass->islocked($config);
    $isposted = $this->othersClass->isposted($config);
    $table = $this->head;
    $htable = $this->hhead;
    $addedfield = '';

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
         client.clientname,
         head.address, 
         head.shipto, 
         date_format(head.createdate,'%Y-%m-%d') as createdate,
         head.rem,
         head.tax,
         head.ewt,head.ewtrate,'' as dewt, 
         head.vattype,
         '' as dvattype,
         left(head.due,10) as due, 
         head.projectid,
         ifnull(project.name,'') as projectname,
         '' as dprojectname,
         client.groupid,ifnull(project.code,'') as projectcode,ifnull(br.docno,'') as brdocno,
         head.brtrno,head.isencashment,
         head.isonlineencashment, 
         case when paymode = 'D' then 'Debit Payment'
         when paymode = 'O' then 'Online Payment'
         when paymode = 'C' then 'Check Payment' end as paymode,head.hacno,head.hacnoname,head.modeofpayment,head.salestype,
         cast(ifnull(info.ischqreleased,0) as char) as ischqreleased,
         cast(ifnull(info.ispaid,0) as char) as ispaid,cast(ifnull(info.ispartial,0) as char) as ispartial,num.statid, info.pdeadline,info.trnxtype ";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join coa on coa.acno=head.contra
        left join projectmasterfile as project on project.line=head.projectid 
        left join hbrhead as br on br.trno = head.brtrno
        left join cntnuminfo as info on info.trno=head.trno
        where head.trno = ? and num.doc=? and num.center = ?  $filterid 
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.clientid = client.clientid
        left join coa on coa.acno=head.contra 
        left join projectmasterfile as project on project.line=head.projectid         
        left join hbrhead as br on br.trno = head.brtrno
        left join hcntnuminfo as info on info.trno=head.trno
        where head.trno = ? and num.doc=? and num.center=? $filterid  ";
    $head = $this->coreFunctions->opentable($qry, [$trno, $doc, $center, $trno, $doc, $center]);
    if (!empty($head)) {

      foreach ($this->blnfields as $key => $value) {
        if ($head[0]->$value) {
          $head[0]->$value = "1";
        } else
          $head[0]->$value = "0";
      }
      $detail = $this->opendetail($trno, $config);
      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
      $msg = 'Data was successfully fetched.';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }
      $pcv = $this->coreFunctions->opentable("select docno from hsvhead where cvtrno =?", [$trno]);

      $hideobj = [];

      $hideobj['forapproval'] = false;
      $hideobj['updatepostedinfo'] = $isposted ? true : false;
      $hideobj['itemscollected'] = true;
      $hideobj['forwardop'] = true;
      $hideobj['doneapproved'] = true;
      $hideobj['tagreleased'] = true;
      $hideobj['doneinitialchecking'] = true;
      $hideobj['donefinalchecking'] = true;
      $hideobj['forwardencoder'] = true;
      $hideobj['forwardwh'] = true;
      $hideobj['forwardasset'] = true;
      $hideobj['forliquidation'] = true;
      $hideobj['forwardacctg'] = true;
      $hideobj['forchecking'] = true;
      $hideobj['forposting'] = true;
      $hideobj['forrevision'] = $isposted;
      $hideobj['checkissued'] = true;
      $hideobj['paid'] = true;
      $hideobj['checked'] = true;
      $hideobj['advancesclr'] = true;
      $hideobj['soareceived'] = true;
      $hideobj['post'] = true;
      $hideobj['voidpayment'] = true;

      switch ($head[0]->statid) {
        case 10:
          $hideobj['forapproval'] = true;
          $hideobj['doneapproved'] = false;
          $hideobj['updatepostedinfo'] = true;
          if ($payreleased) {
            $hideobj['voidpayment'] = false;
          }
          break;

        case 36:
          $hideobj['doneapproved'] = true;
          $hideobj['forapproval'] = true;
          $hideobj['forwardacctg'] = false;
          if ($payreleased) {
            $hideobj['voidpayment'] = false;
          }
          break;

        case 39:
          $hideobj['doneapproved'] = true;
          $hideobj['forapproval'] = true;
          if ($payreleased) {
            $hideobj['voidpayment'] = false;
          }
          break;

        case 42:
          $hideobj['doneapproved'] = true;
          $hideobj['forapproval'] = true;
          $hideobj['tagreleased'] = false;
          if ($payreleased) {
            $hideobj['voidpayment'] = false;
          }
          break;

        case 48:
          $hideobj['forapproval'] = true;
          $hideobj['doneapproved'] = true;
          $hideobj['forwardencoder'] = false;
          $hideobj['forwardwh'] = false;
          if ($payreleased) {
            $hideobj['voidpayment'] = false;
          }
          break;

        case 49:

          $hideobj['forapproval'] = true;
          switch ($head[0]->salestype) {
            case 'Terms':
              $hideobj['soareceived'] = false;
              break;
            default:
              $hideobj['checkissued'] = false;
              break;
          }
          if ($payreleased) {
            $hideobj['voidpayment'] = false;
          }
          break;

        case 50:
          $hideobj['forapproval'] = true;
          $hideobj['doneapproved'] = true;

          switch ($head[0]->salestype) {
            case 'COD Cash':
              $hideobj['itemscollected'] = false;
              break;

            case 'COD Cheque':
            case 'Terms':
              $hideobj['paid'] = false;
              break;
          }
          if ($payreleased) {
            $hideobj['voidpayment'] = false;
          }
          break;

        case 57:
          $hideobj['doneapproved'] = true;
          $hideobj['forapproval'] = true;

          switch ($head[0]->salestype) {
            case 'COD Cash':
            case 'COD Cheque':
              $hideobj['doneinitialchecking'] = false;
              break;
          }
          if ($payreleased) {
            $hideobj['voidpayment'] = false;
          }
          break;

        case 58:
          $hideobj['doneapproved'] = true;
          $hideobj['forapproval'] = true;
          $hideobj['doneinitialchecking'] = true;

          switch ($head[0]->salestype) {
            case 'COD Cash':
              $hideobj['donefinalchecking'] = false;
              break;
            case 'COD Cheque':
              $hideobj['checked'] = false;
              break;
          }
          if ($payreleased) {
            $hideobj['voidpayment'] = false;
          }
          break;

        case 59:
          $hideobj['doneapproved'] = true;
          $hideobj['forapproval'] = true;
          $hideobj['forwardwh'] = false;
          $isfulfill = $this->coreFunctions->getfieldvalue("cntnuminfo", "termsyear", "trno=?", [$config['params']['trno']]);
          if ($isfulfill == "") $isfulfill = 0;
          if ($isfulfill > 1) {
            $hideobj['forwardwh'] = true;
            if ($this->checkisgeneric($config['params']['trno'])) {
              $hideobj['forwardasset'] = false;
            } else {
              $hideobj['forwardasset'] = true;
              $hideobj['forliquidation'] = false;
            }
          }
          if ($payreleased) {
            $hideobj['voidpayment'] = false;
          }
          break;

        case 60:
          $hideobj['doneapproved'] = true;
          $hideobj['forapproval'] = true;
          $hideobj['forwardencoder'] = false;
          $isfulfill = $this->coreFunctions->getfieldvalue("cntnuminfo", "termsyear", "trno=?", [$config['params']['trno']]);
          if ($isfulfill == "") $isfulfill = 0;
          if ($isfulfill > 1) {
            $hideobj['forwardencoder'] = true;
            if ($this->checkisgeneric($config['params']['trno'])) {
              $hideobj['forwardasset'] = false;
            } else {
              $hideobj['forwardasset'] = true;
              $hideobj['forliquidation'] = false;
            }
          }
          if ($payreleased) {
            $hideobj['voidpayment'] = false;
          }
          break;

        case 61:
          $hideobj['doneapproved'] = true;
          $hideobj['forapproval'] = true;
          $hideobj['forliquidation'] = false;
          if ($payreleased) {
            $hideobj['voidpayment'] = false;
          }
          break;

        case 62:
          $hideobj['doneapproved'] = true;
          $hideobj['forapproval'] = true;
          $hideobj['forwardacctg'] = false;
          if ($payreleased) {
            $hideobj['voidpayment'] = false;
          }
          break;

        case 63:
          $hideobj['doneapproved'] = true;
          $hideobj['forapproval'] = true;

          switch ($head[0]->salestype) {
            case 'COD Cash':
              $hideobj['forchecking'] = false;
              break;
            case 'COD Cheque':
            case 'Terms':
              $hideobj['forwardop'] = false;
              break;
          }
          if ($payreleased) {
            $hideobj['voidpayment'] = false;
          }
          break;

        case 64:
          $hideobj['doneapproved'] = true;
          $hideobj['forapproval'] = true;
          switch ($head[0]->salestype) {
            case 'Terms':
              $hideobj['doneapproved'] = false;
              break;
            default:
              $hideobj['forposting'] = false;
              break;
          }
          if ($payreleased) {
            $hideobj['voidpayment'] = false;
          }

          break;

        case 65:
          $hideobj['doneapproved'] = false;
          $hideobj['forapproval'] = true;
          if ($payreleased) {
            $hideobj['voidpayment'] = false;
          }
          break;

        case 66:
          $hideobj['doneapproved'] = true;
          $hideobj['forapproval'] = true;
          switch ($head[0]->salestype) {
            case 'Terms':
              $hideobj['post'] = false;
              break;
            default:
              $hideobj['itemscollected'] = false;
              break;
          }
          if ($payreleased) {
            $hideobj['voidpayment'] = false;
          }
          break;

        case 67:
          $hideobj['doneapproved'] = true;
          $hideobj['forapproval'] = true;
          if ($payreleased) {
            $hideobj['voidpayment'] = false;
          }
          break;

        case 68:
          $hideobj['doneapproved'] = true;
          $hideobj['forapproval'] = true;
          $hideobj['checkissued'] = false;
          if ($payreleased) {
            $hideobj['voidpayment'] = false;
          }
          break;

        case 69:
          $hideobj['doneapproved'] = true;
          $hideobj['forapproval'] = true;
          $hideobj['advancesclr'] = false;
          if ($payreleased) {
            $hideobj['voidpayment'] = false;
          }
          break;
        case 26:
          $hideobj['updatepostedinfo'] = true;
          $hideobj['forrevision'] = true;
          $hideobj['forapproval'] = true;
          if ($payreleased) {
            $hideobj['voidpayment'] = true;
          }
          break;

        default:
          $hideobj['forrevision'] = true;
          $hideobj['updatepostedinfo'] = true;
          break;
      }

      if ($this->companysetup->getistodo($config['params'])) {
        $btndonetodo = $this->othersClass->checkdonetodo($config, $tablenum);
        $hideobj = ['donetodo' => !$btndonetodo];
      }
      return  ['head' => $head, 'griddata' => ['accounting' => $detail, 'reference' => $pcv], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg, 'hideobj' => $hideobj];
    } else {
      $head[0]['trno'] = 0;
      $head[0]['docno'] = '';
      return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['accounting' => []], 'msg' => 'Data head fetched failed; either the transaction was deleted or modified.'];
    }
  }

  public function checkisgeneric($trno)
  {
    $qry = "select ifnull(item.isgeneric,0) from ladetail as d left join glstock as s on s.trno=d.refx left join item on item.itemid=s.itemid where d.trno=? and d.refx<>0 and ifnull(item.isgeneric,0)=1";
    $data = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($data)) {
      return true;
    } else {
      return false;
    }
  }

  public function updatehead($config, $isupdate)
  {
    $companyid = $config['params']['companyid'];
    $head = $config['params']['head'];
    $data = [];
    $dataother = [];
    if ($isupdate) {
      unset($this->fields['docno']);
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
      $dataother[$key] = $head[$key];
      if (!in_array($key, $this->except)) {
        $dataother[$key] = $this->othersClass->sanitizekeyfield($key, $dataother[$key], '', $companyid);
      } //end if
    }

    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    if ($isupdate) {
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
      if (floatval($head['brtrno']) != 0) {
        $this->coreFunctions->sbcupdate("hbrhead", ['cvtrno' => $head['trno']], ['trno' => $head['brtrno']]);
      }
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head, $data);
      if (floatval($head['brtrno']) != 0) {
        $this->coreFunctions->sbcupdate("hbrhead", ['cvtrno' => $head['trno']], ['trno' => $head['brtrno']]);
      }
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

    $isnoedit = $this->coreFunctions->opentable("select trno from " . $this->detail . " where isnoedit=1 and trno=?", [$trno]);
    if (!empty($isnoedit)) {
      return ['status' => false, 'msg' => 'Not allowed to delete, already approved.'];
    }

    $this->deleteallitem($config);
    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry("delete from cntnuminfo where trno=?", 'delete', [$trno]);
    $brdocno = $this->coreFunctions->datareader("select brtrno as value from " . $this->head . ' where trno=?', [$trno]);
    if (floatval($brdocno) != 0) {
      $this->coreFunctions->sbcupdate("hbrhead", ['cvtrno' => 0], ['trno' => $brdocno]);
    }
    $this->othersClass->deleteattachments($config);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  public function osiAndSoQryChecker($field, $trno)
  {

    return $this->coreFunctions->datareader("select 
    
    $field as value

    from ladetail as d
    left join glstock as g on g.trno=d.refx and g.line=d.linex

    left join (
    select h.docno,s.trno,s.line,s.reqtrno,s.reqline
    from omhead as h
    left join omstock as s on s.trno=h.trno
    union all
    select h.docno,s.trno,s.line,s.reqtrno,s.reqline
    from homhead as h
    left join homstock as s on s.trno=h.trno
    ) as osi on osi.reqtrno=g.reqtrno and osi.reqline=g.reqline

    left join (
    select s.trno,s.line,s.sono
    from omso as s
    union all
    select s.trno,s.line,s.sono
    from homso as s
    ) as so on so.trno=osi.trno and so.line=osi.line

    where d.trno=$trno and (osi.docno!='' or so.sono!='')
    group by osi.docno");
  }



  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $docno = $this->coreFunctions->getfieldvalue("lahead", "docno", "trno=?", [$trno]);


    // $existOSI = false;
    $existOSI = $this->coreFunctions->opentable("select d.trno,d.line,pr.ourref from ladetail as d
                left join cvitems as cv on cv.trno=d.trno
                left join hprhead as pr on pr.trno=cv.reqtrno
                where (d.trno=" . $trno . " and d.refx<>0) or (d.trno=" . $trno . " and d.acnoid=4891) and pr.ourref <> 1");
    if (!$existOSI) {
      $existOSI = true;
    } else {
      $osi = $this->coreFunctions->opentable("select trno,line from ladetail where (trno=" . $trno . " and refx<>0) or (trno=" . $trno . " and acnoid=4891)");
      foreach ($osi as $key => $value) {
        $osiref = $this->coreFunctions->opentable("select omh.docno  as omdocno, cv.trno, cv.line
          from cvitems as cv
          left join homstock as om on om.reqtrno=cv.reqtrno and om.reqline=cv.reqline
          left join homhead as omh on omh.trno=om.trno
          left join homso as so on so.trno=om.trno and so.line=om.line
          left join transnum as num on num.trno=om.trno
          where ifnull(om.trno,0) is not null  and num.postdate is not null and cv.trno=" . $value->trno . " and cv.line=" . $value->line . "
          union all
          select omh.docno as omdocno, cv.trno, cv.line
          from ladetail as cv left join lahead as h on h.trno=cv.trno
          left join glstock as s on s.trno=cv.refx
          left join homstock as om on om.reqtrno=s.reqtrno and om.reqline=s.reqline
          left join homhead as omh on omh.trno=om.trno
          left join homso as so on so.trno=om.trno and so.line=om.line
          left join transnum as num on num.trno=om.trno
          where h.doc='CV' and om.trno is not null and num.postdate is not null and cv.trno=" . $value->trno . " and cv.line=" . $value->line);

        if (!empty($osiref)) {
          $existOSI = true;
        }
      }
    }





    if (!$existOSI) {
      return ['status' => false, 'msg' => 'Posting failed; CV must have posted OSI.'];
    }

    $qry = "select oqh.docno as value from lastock as cv left join glstock as rr on rr.trno=cv.refx left join oqstock as oq on oq.reqtrno=rr.reqtrno and oq.reqline=rr.reqline left join oqhead as oqh on oqh.trno=oq.trno where cv.trno=? and oq.trno is not null and ifnull(rr.reqtrno,0)<>0";
    $rr = $this->coreFunctions->datareader($qry, [$trno]);
    if ($rr != "") {
      return ['status' => false, 'msg' => 'Posting failed; Please check, pending Oracle Code Request ' . $rr];
    }

    $qry = "select oqh.docno value from ladetail as cv left join cvitems as ci on ci.trno=cv.trno left join hpostock as po on po.trno=ci.refx and po.line=ci.linex left join oqstock as oq on oq.reqtrno=po.reqtrno and oq.reqline=po.reqline left join oqhead as oqh on oqh.trno=oq.trno where cv.trno=? and oq.trno is not null";
    $po = $this->coreFunctions->datareader($qry, [$trno]);
    if ($po != "") {
      return ['status' => false, 'msg' => 'Posting failed; Please check, pending Oracle Code Request ' . $po];
    }

    $qry = "SELECT po.trno FROM cvitems as cv left join hpostock as po on po.trno=cv.refx AND po.line=cv.linex WHERE cv.trno=? AND po.ext>po.paid and po.void=0";

    $pending = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($pending)) {
      return ['status' => false, 'msg' => 'Posting failed; Please pay all items in PO.'];
    }

    $statid = $this->othersClass->getstatid($config);
    if ($statid != 39) {
      $qry = "select d.line
                from ladetail as d
                left join (select line from voiddetail where trno = $trno) as vd on vd.line=d.line
                where trno = $trno";

      $this->coreFunctions->LogConsole($qry);
      $chkline = $this->coreFunctions->opentable($qry);

      if (empty($chkline)) {
        $this->coreFunctions->execqry("update cntnum set statid = 39 where trno = ?", "update", [$trno]);
      } else {
        return ['status' => false, 'msg' => 'Posting failed; status must be For Posting.'];
      }
    }

    $return = $this->othersClass->posttransacctg($config);

    if ($return['status']) {
      if ($this->coreFunctions->execqry("insert into hcvitems (trno, line, refx, linex, surcharge, acnoid, isapproved, reqtrno, reqline, amt, scamt) select trno, line, refx, linex, surcharge, acnoid, isapproved, reqtrno, reqline, amt, scamt from cvitems where trno=" . $trno)) {
        $this->coreFunctions->execqry("delete from cvitems where trno=" . $trno);
      }
    }

    $this->coreFunctions->execqry("update hcvitems as cv
          left join hpostock as po on po.trno=cv.refx and po.line=cv.linex
	        left join hstockinfotrans as prs on prs.trno=po.reqtrno and prs.line=po.reqline
          set prs.cvref='" . $docno . " - Posted',prs.otherleadtime='" . $this->othersClass->getCurrentTimeStamp() . "' 
          where cv.trno=" . $trno, 'update');

    $this->coreFunctions->execqry("update gldetail as cv
          left join glstock as rr on rr.trno=cv.refx
          left join hstockinfotrans as prs on prs.trno=rr.reqtrno and prs.line=rr.reqline
          set prs.cvref='" . $docno . " - Posted',prs.otherleadtime='" . $this->othersClass->getCurrentTimeStamp() . "' 
          where cv.refx<>0 and cv.trno=" . $trno, 'update');

    return $return;
  } //end function

  public function unposttrans($config)
  {
    $return = $this->othersClass->unposttransacctg($config);

    if ($return['status']) {
      $trno = $config['params']['trno'];
      if ($this->coreFunctions->execqry("insert into cvitems (trno, line, refx, linex, surcharge, acnoid, isapproved, reqtrno, reqline, amt, scamt) select trno, line, refx, linex, surcharge, acnoid, isapproved, reqtrno, reqline, amt, scamt from hcvitems where trno=" . $trno)) {
        $this->coreFunctions->execqry("delete from hcvitems where trno=" . $trno);
      }
    }

    return $return;
  } //end function


  private function getdetailselect($config)
  {
    $qry = " head.trno,left(head.dateid,10) as dateid,d.ref,d.line,d.sortline,coa.acno,coa.acnoname,
            client.client,client.clientname,d.rem,
            FORMAT(d.db,2) as db,FORMAT(d.cr,2) as cr,d.fdb,d.fcr,d.refx,d.linex,FORMAT(info.payment,2) as surcharge,
            left(d.postdate,10) as postdate,d.checkno,coa.alias,d.pdcline,
            d.projectid,ifnull(proj.name,'') as projectname,d.cur,d.forex,
            d.subproject,d.stageid,d.pcvtrno,proj.code as project,
            case d.isewt when 0 then 'false' else 'true' end as isewt,
            case d.isvat when 0 then 'false' else 'true' end as isvat,
            case d.isvewt when 0 then 'false' else 'true' end as isvewt,
            d.ewtcode,d.ewtrate,d.damt, case d.void when 0 then 'false' else 'true' end as void,
            '' as bgcolor,case d.void when 1 then 'bg-red-2' else '' end as  errcolor,
            (select group_concat(distinct h.invoiceno) 
            from glhead as h where h.trno=d.refx) as invoiceno,
            (select group_concat(distinct left(h.invoicedate,10))
                from glhead as h where h.trno=d.refx) as invoicedate,
             subproj.subproject as subprojectname,info.si1,info.si2,info.ref as prref,FORMAT(d.appamt,2) as appamt,
             ifnull((select count(rrtrno) from headprrem where headprrem.cvtrno=head.trno and headprrem.cvline=d.line),0) as notectr";
    return $qry;
  }


  public function opendetail($trno, $config)
  {
    $sqlselect = $this->getdetailselect($config);

    $qry = "select " . $sqlselect . ",d.isnoedit   
    from " . $this->detail . " as d
    left join " . $this->head . " as head on head.trno=d.trno
    left join client on client.client=d.client
    left join projectmasterfile as proj on proj.line = d.projectid
    left join subproject as subproj on subproj.line = d.subproject
    left join coa on d.acnoid=coa.acnoid
    left join detailinfo as info on info.trno=d.trno and info.line=d.line
    where d.trno=?
    union all
    select " . $sqlselect . ",d.isnoedit
    from " . $this->hdetail . " as d   
    left join " . $this->hhead . " as head on head.trno=d.trno
    left join client on client.clientid=d.clientid
    left join projectmasterfile as proj on proj.line = d.projectid
    left join subproject as subproj on subproj.line = d.subproject
    left join coa on coa.acnoid=d.acnoid
    left join hdetailinfo as info on info.trno=d.trno and info.line=d.line
    where d.trno=?
    union all
    select " . $sqlselect . ", 1 as isnoedit   
    from voiddetail as d
    left join " . $this->head . " as head on head.trno=d.trno
    left join client on client.client=d.client
    left join projectmasterfile as proj on proj.line = d.projectid
    left join subproject as subproj on subproj.line = d.subproject
    left join coa on d.acnoid=coa.acnoid
    left join detailinfo as info on info.trno=d.trno and info.line=d.line
    where d.trno=?
    union all
    select " . $sqlselect . ", 1 as isnoedit    
    from hvoiddetail as d
    left join " . $this->hhead . " as head on head.trno=d.trno
    left join client on client.clientid=d.clientid
    left join projectmasterfile as proj on proj.line = d.projectid
    left join subproject as subproj on subproj.line = d.subproject
    left join coa on coa.acnoid=d.acnoid
    left join hdetailinfo as info on info.trno=d.trno and info.line=d.line
    where d.trno=? order by  sortline,line";
    $detail = $this->coreFunctions->opentable($qry, [$trno, $trno, $trno, $trno]);
    return $detail;
  }


  public function opendetailline($config)
  {
    $sqlselect = $this->getdetailselect($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = "select " . $sqlselect . " ,d.isnoedit 
    from " . $this->detail . " as d
    left join " . $this->head . " as head on head.trno=d.trno
    left join client on client.client=d.client
    left join projectmasterfile as proj on proj.line = d.projectid
    left join subproject as subproj on subproj.line = d.subproject
    left join coa on d.acnoid=coa.acnoid
    left join detailinfo as info on info.trno=d.trno and info.line=d.line
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
      case 'getunpaidselected':
        return $this->getunpaidselected($config);
        break;
      case 'getpcvselected':
        return $this->getpcvselected($config);
        break;
      case 'generateewt':
        return $this->generateewt($config);
        break;
      case 'getporeqpaydetails':
        return $this->getporeqpaydetails($config);
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
        return $this->donetodo($config);
        break;
      case 'forapproval':
        return $this->forapproval($config);
        break;
      case 'doneapproved':
      case 'doneinitialchecking':
      case 'donefinalchecking':
        return $this->doneapproved($config);
        break;
      case 'itemscollected':
        return $this->itemscollected($config);
        break;
      case 'voidpayment':
        return $this->voidpayment($config);
        break;
      case 'forwardop':
      case 'forwardencoder':
      case 'forwardwh':
      case 'forwardasset':
      case 'forliquidation':
      case 'forwardacctg':
      case 'forchecking':
      case 'forposting':
      case 'checkissued':
      case 'paid':
      case 'checked':
      case 'advancesclr':
      case 'soareceived':
      case 'posting':
      case 'tagreleased':
        return $this->forwardop($config);
        break;

      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function forapproval($config)
  {
    $posted = $this->othersClass->isposted($config);
    if ($posted) {
      return ['status' => false, 'msg' => 'Already posted.'];
    }

    if ($this->coreFunctions->sbcupdate($this->tablenum, ['statid' => 10], ['trno' => $config['params']['trno']])) {
      // $this->logger->sbcwritelog($config['params']['trno'], $config, 'HEAD', 'Tag FOR APPROVAL');
      $this->logger->sbcstatlog($config['params']['trno'], $config, 'HEAD', 'Tag FOR APPROVAL');
      return ['status' => true, 'msg' => 'Successfully updated.', 'backlisting' => true];
    } else {
      return ['status' => false, 'msg' => 'Failed to tag for approval.'];
    }
  }

  public function doneapproved($config)
  {
    $posted = $this->othersClass->isposted($config);
    if ($posted) {
      return ['status' => false, 'msg' => 'Already posted.'];
    }

    $action = $config['params']['action'];
    $label = 'APPROVED';
    $statid = 0;
    $paymentterms = $this->coreFunctions->getfieldvalue($this->head, "salestype", "trno=?", [$config['params']['trno']]);

    switch ($action) {
      case 'doneinitialchecking':
        $label = 'APPROVED (Initial Checking)';
        switch ($paymentterms) {
          case 'COD Cash':
          case 'COD Cheque':
            $access = $this->othersClass->checkAccess($config['params']['user'], 3986);
            if (!$access) {
              return ['status' => false, 'msg' => 'Please advice your administrator regarding this restriction.'];
            }
            $statid = 58;
            break;
        }
        break;

      case 'donefinalchecking':
        switch ($paymentterms) {
          case 'COD Cash':
            $access = $this->othersClass->checkAccess($config['params']['user'], 3987);
            if (!$access) {
              return ['status' => false, 'msg' => 'Please advice your administrator regarding this restriction.'];
            }
            $label = 'APPROVED (Final Checking)';
            $statid = 42;
            break;
        }
        break;

      case 'doneapproved':
        switch ($paymentterms) {
          case 'COD Cash':
            // 2024.02.26 - remove initial checking
            // $access = $this->othersClass->checkAccess($config['params']['user'], 3985);

            // $statid = 57;

            $access = $this->othersClass->checkAccess($config['params']['user'], 3987);
            if (!$access) {
              return ['status' => false, 'msg' => 'Please advice your administrator regarding this restriction.'];
            }
            $label = 'APPROVED';
            $statid = 58;
            break;

          case 'COD Cheque':
            $prevstat = $this->othersClass->getstatid($config);
            if ($prevstat == 65) { //for checking
              $statid = 50;
            } else {
              $statid = 36;
            }
            break;

          case 'Terms':
            $prevstat = $this->othersClass->getstatid($config);
            if ($prevstat == 64) {
              $statid = 50;
            } else {

              //$this->logger->sbcwritelog($config['params']['trno'], $config, 'HEAD', 'APPROVED');
              // $label = 'Forwarded to Accounting';
              // $statid = 63;
              $label = 'Approved';
              $statid = 36;
            }
            break;

          default:
            $statid = 36;
            break;
        }
        break;
    }

    //2024.03.20 -- pinabalik sa dati ni mam JO
    // $clientid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$config['params']['row'][0]['client']]);
    // switch ($clientid) {
    //   case 4394:
    //   case 4479:
    //     $label = 'For Liquidation';
    //     $statid = 62;
    //     break;
    // }


    if ($this->coreFunctions->sbcupdate($this->tablenum, ['statid' => $statid], ['trno' => $config['params']['trno']])) {
      $cntnuminfo =  ['instructions' => ''];
      if ($action == 'doneapproved') {
        $cntnuminfo['checkerdone'] = $this->othersClass->getCurrentTimeStamp();
      }

      if ($statid == 50) {
        $cntnuminfo['releasedate'] = $this->othersClass->getCurrentTimeStamp();
      }

      $this->coreFunctions->sbcupdate('cntnuminfo', $cntnuminfo, ['trno' => $config['params']['trno']]);
      if ($action == 'doneapproved') {
        $this->coreFunctions->sbcupdate('ladetail', ['isnoedit' => 1], ['trno' => $config['params']['trno']]);
      }

      // $this->logger->sbcwritelog($config['params']['trno'], $config, 'HEAD', $label);
      $this->logger->sbcstatlog($config['params']['trno'], $config, 'HEAD', $label);

      return ['status' => true, 'msg' => 'Successfully updated.', 'backlisting' => true];
    } else {
      return ['status' => false, 'msg' => 'Failed to tag.'];
    }
  }

  public function tagreleased($config)
  {
    $posted = $this->othersClass->isposted($config);
    if ($posted) {
      return ['status' => false, 'msg' => 'Already posted.'];
    }

    $insert = false;
    checkerhere:
    $checker = $this->checker($config);
    if ($checker) {
      if ($this->coreFunctions->sbcupdate($this->tablenum, ['statid' => 50], ['trno' => $config['params']['trno']])) {
        $this->coreFunctions->sbcupdate($this->head, ['lockuser' => $config['params']['user'], 'lockdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $config['params']['trno']]);
        $this->coreFunctions->sbcupdate("cntnuminfo", ['releasedate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $config['params']['trno']]);
        // $this->logger->sbcwritelog($config['params']['trno'], $config, 'HEAD', 'PAYMENT RELEASED.');
        $this->logger->sbcstatlog($config['params']['trno'], $config, 'HEAD', 'PAYMENT RELEASED.');
        return ['status' => true, 'msg' => 'Successfully updated.', 'backlisting' => true];
      } else {
        return ['status' => false, 'msg' => 'Failed to tag payment released.'];
      }
    } else {
      if ($insert) {
        return ['status' => true, 'msg' => "Must check by other users."];
      }
      $exist = $this->coreFunctions->datareader("select count(trno) as value from approverinfo where trno=? and approver=?", [$config['params']['trno'], $config['params']['user']]);
      if ($exist == "") {
        $this->coreFunctions->sbcinsert("approverinfo", ['trno' => $config['params']['trno'], 'approver' => $config['params']['user'], 'dateid' => $this->othersClass->getCurrentTimeStamp()]);
        $insert = true;
        // $this->logger->sbcwritelog($config['params']['trno'], $config, 'HEAD', 'CHECKED by user');
        $this->logger->sbcstatlog($config['params']['trno'], $config, 'HEAD', 'CHECKED by user');
        goto checkerhere;
      } else {
        return ['status' => false, 'msg' => "Document was already checked."];
      }
      return ['status' => false, 'msg' => "Must check by required users."];
    }
  }

  public function checker($config)
  {
    $checker = $this->coreFunctions->opentable("select checker, checkercount from approversetup where doc='CV' and ischecker=1");
    $checked = $this->coreFunctions->datareader("select count(trno) as value from approverinfo where trno=?", [$config['params']['trno']]);
    if ($checked == "") {
      $checked = 0;
    }
    $this->othersClass->logConsole($checked . ' - MAX: ' . $checker[0]->checkercount);

    if ($checked < $checker[0]->checker) {
      return false;
    } else {
      return true;
    }
  }


  //Status notes for every payment terms
  // COD Cash: 0-10(for approval)-57(For Initial Checking)-58(For Final Checking)-42(Released)-50(Payment Released)-48(Items Collected)-59/60(Forwarded to Encoder/Warehouse)-61(Forwarded to Asset Management/FAMS)-62(For Liquidation)-63(Forwarded to Accounting)-64(For Checking)-39(For Posting)
  // COD Cheque: 0-10(for approval)-36(Approved)-63(Forwarded to Accounting)-49(Forwarded to OP)-65(Check Issued)-50(Payment Released)-66(Paid)-48(Items Collected)-60/59(Forwarded to Encoder/Warehouse)-61(Forwarded to Asset Management/FAMS)-62(For Liquidation)-63(Forwarded to Accounting)-57(For Initial Checking)-58(For Final Checking)-69(Checked)-39(For Posting)
  // Terms: 0-10(for approval)-36(Approved)-63(Forwarded to Accounting)-49(Forwarded to OP)-68(SOA Received)-64(For Checking)-50(Payment Released)-66(Paid)-39(for posting)

  public function forwardop($config)
  {
    $posted = $this->othersClass->isposted($config);
    if ($posted) {
      return ['status' => false, 'msg' => 'Already posted.'];
    }

    $paymentterms = $this->coreFunctions->getfieldvalue($this->head, "salestype", "trno=?", [$config['params']['trno']]);

    $label = '';
    $statid = 0;
    $action = $config['params']['action'];

    $this->coreFunctions->LogConsole($action);
    switch ($action) {
      case 'forwardop':
        $access = $this->othersClass->checkAccess($config['params']['user'], 3992);
        if (!$access) {
          return ['status' => false, 'msg' => 'Please advice your administrator regarding this restriction.'];
        }
        $label = 'Forwarded to OP';
        switch ($paymentterms) {
          case 'COD Cash':
          case 'Terms':
            $statid = 49;
            break;
          case 'COD Cheque':
            $prevstat = $this->othersClass->getstatid($config);
            if ($prevstat == 63) {
              $checkissued = $this->coreFunctions->getfieldvalue("cntnuminfo", "ischqreleased", "trno=?", [$config['params']['trno']]);
              if ($checkissued  == "1") {
                $label = 'For initial checking OP';
                $statid = 57;
              } else {
                $statid = 49;
              }
            } else {
              $statid = 67;
            }
            break;
        }
        break;
      case 'forwardencoder':
        $access = $this->othersClass->checkAccess($config['params']['user'], 3989);
        if (!$access) {
          return ['status' => false, 'msg' => 'Please advice your administrator regarding this restriction.'];
        }

        $statid = 59;
        $label = 'Forwarded to Encoder';
        $prevstat = $this->othersClass->getstatid($config);
        $this->coreFunctions->execqry("update cntnuminfo set termsyear=termsyear+1 where trno=?", "update", [$config['params']['trno']]);
        break;
      case 'forwardwh':
        $access = $this->othersClass->checkAccess($config['params']['user'], 3990);
        if (!$access) {
          return ['status' => false, 'msg' => 'Please advice your administrator regarding this restriction.'];
        }

        $statid = 60;
        $label = 'Forwarded to Warehouse';
        $this->coreFunctions->execqry("update cntnuminfo set termsyear=termsyear+1 where trno=?", "update", [$config['params']['trno']]);
        break;
      case 'forwardasset':
        $access = $this->othersClass->checkAccess($config['params']['user'], 3993);
        if (!$access) {
          return ['status' => false, 'msg' => 'Please advice your administrator regarding this restriction.'];
        }

        $statid = 61;
        $label = 'Forwarded to Asset Mgmt';
        break;
      case 'forliquidation':
        $access = $this->othersClass->checkAccess($config['params']['user'], 3994);
        if (!$access) {
          return ['status' => false, 'msg' => 'Please advice your administrator regarding this restriction.'];
        }

        $statid = 62;
        $label = 'For Liquidation';
        break;
      case 'forwardacctg':
        $access = $this->othersClass->checkAccess($config['params']['user'], 3995);
        if (!$access) {
          return ['status' => false, 'msg' => 'Please advice your administrator regarding this restriction.'];
        }

        $paymentterms = $this->coreFunctions->getfieldvalue($this->head, "salestype", "trno=?", [$config['params']['trno']]);

        $statid = 63;
        $label = 'Forwarded to Accounting';

        if ($paymentterms <> 'Terms') {
          $checkissued = $this->coreFunctions->getfieldvalue("cntnuminfo", "ischqreleased", "trno=?", [$config['params']['trno']]);
          if ($checkissued == "1") {
            $this->coreFunctions->sbcupdate('cntnuminfo', ['status' => 'For Liquidation'], ['trno' => $config['params']['trno']]);
          } else {
            $this->coreFunctions->sbcupdate('cntnuminfo', ['status' => 'For Payment'], ['trno' => $config['params']['trno']]);
          }
        }

        break;
      case 'forchecking':
        $access = $this->othersClass->checkAccess($config['params']['user'], 3996);
        if (!$access) {
          return ['status' => false, 'msg' => 'Please advice your administrator regarding this restriction.'];
        }

        switch ($paymentterms) {
          case 'COD Cash':
            $approversetup = $this->coreFunctions->opentable("select client.clientid from approverdetails as d left join approversetup as s on s.line=d.appline left join client on client.email=d.approver 
                where s.isapprover=1 and s.doc='CV' and ifnull(client.clientid,0)<>0 order by d.ordernum");
            if (empty($approversetup)) {
              return ['status' => false, 'msg' => 'Failed to proceed, please setup linear approvers first.'];
            }

            break;
        }

        $statid = 64;
        $label = 'For Checking';
        break;
      case 'forposting':
      case 'posting':
        switch ($paymentterms) {
          case 'COD Cash':
            $blnApproved = false;
            $approveduser = '';
            checkapproverhere:
            $approver = $this->coreFunctions->opentable("select c.line, client.clientname, client.clientid from cntnumtodo as c left join client on client.clientid=c.clientid where c.trno=? and c.donedate is null order by c.line limit 1", [$config['params']['trno']]);
            if (!empty($approver)) {
              if ($config['params']['adminid'] == $approver[0]->clientid) {
                if ($this->coreFunctions->sbcupdate("cntnumtodo", ['donedate' => $this->othersClass->getCurrentTimeStamp()], ['line' => $approver[0]->line])) {
                  $approveduser = $approver[0]->clientname;
                  $blnApproved = true;

                  goto checkapproverhere;
                }
              } else {
                if ($blnApproved) {
                  // $this->logger->sbcwritelog($config['params']['trno'], $config, 'HEAD', 'Approved.');
                  $this->logger->sbcstatlog($config['params']['trno'], $config, 'HEAD', 'Approved.');
                  return ['status' => true, 'msg' => 'Successfully approved by ' . $approveduser . '. Next approver must be ' . $approver[0]->clientname, 'backlisting' => true];
                }
                return ['status' => false, 'msg' => 'Failed to approved, must approved by user ' . $approver[0]->clientname];
              }
            }
            updateforpostinghere:
            $statid = 39;
            $label = 'Approved.';

            break;
          default:
            $access = $this->othersClass->checkAccess($config['params']['user'], 4002);
            if (!$access) {
              return ['status' => false, 'msg' => 'Please advice your administrator regarding this restriction.'];
            }
            $statid = 39;
            $label = 'For Posting';
            break;
        }
        break;
      case 'advancesclr':
        $access = $this->othersClass->checkAccess($config['params']['user'], 4000);
        if (!$access) {
          return ['status' => false, 'msg' => 'Please advice your administrator regarding this restriction.'];
        }
        $statid = 39;
        $label = 'Advances Cleared';
        break;
      case 'checkissued':
        $access = $this->othersClass->checkAccess($config['params']['user'], 3997);
        if (!$access) {
          return ['status' => false, 'msg' => 'Please advice your administrator regarding this restriction.'];
        }
        $label = 'Check Issued';
        if ($paymentterms == 'Terms') {
          $statid = 64;
        } else {
          $statid = 65;
          $this->coreFunctions->sbcupdate("cntnuminfo", ['ischqreleased' => 1], ['trno' => $config['params']['trno']]);

          $this->coreFunctions->execqry("update cvitems as cv 
	        left join hstockinfotrans as prs on prs.trno=cv.reqtrno and prs.line=cv.reqline set prs.payreleased='" . $this->othersClass->getCurrentTimeStamp() . "' 
          where cv.trno=" . $config['params']['trno'], 'update');

          $this->coreFunctions->execqry("update ladetail as cv left join glstock as rr on rr.trno=cv.refx
          left join hstockinfotrans as prs on prs.trno=rr.reqtrno and prs.line=rr.reqline set prs.payreleased='" . $this->othersClass->getCurrentTimeStamp() . "' 
          where cv.refx<>0 and cv.trno=" . $config['params']['trno'], 'update');
        }
        break;
      case 'paid':
        $access = $this->othersClass->checkAccess($config['params']['user'], 3998);
        if (!$access) {
          return ['status' => false, 'msg' => 'Please advice your administrator regarding this restriction.'];
        }
        $statid = 66;
        $label = 'Paid';
        $this->coreFunctions->sbcupdate('cntnuminfo', ['status' => 'For Liquidation'], ['trno' => $config['params']['trno']]);
        break;
      case 'checked':
        $access = $this->othersClass->checkAccess($config['params']['user'], 3999);
        if (!$access) {
          return ['status' => false, 'msg' => 'Please advice your administrator regarding this restriction.'];
        }
        $statid = 69;
        $label = 'Checked';
        break;
      case 'soareceived':
        $access = $this->othersClass->checkAccess($config['params']['user'], 4001);
        if (!$access) {
          return ['status' => false, 'msg' => 'Please advice your administrator regarding this restriction.'];
        }
        $statid = 68;
        $label = 'SOA Received';
        break;
      case 'tagreleased':
        $access = $this->othersClass->checkAccess($config['params']['user'], 3988);
        if (!$access) {
          return ['status' => false, 'msg' => 'Please advice your administrator regarding this restriction.'];
        }
        $statid = 50;
        $label = 'Payment Released';
        break;
    }

    if ($this->coreFunctions->sbcupdate($this->tablenum, ['statid' => $statid], ['trno' => $config['params']['trno']])) {
      if ($action == 'forchecking') {
        if ($paymentterms == 'COD Cash') {
          $this->coreFunctions->execqry("insert into cntnumtodo (clientid, trno, createby, createdate)
              select client.clientid, " . $config['params']['trno'] . " as trno, '" . $config['params']['user'] . "' as createby, '" . $this->othersClass->getCurrentTimeStamp() . "' as createdate
              from approverdetails as d left join approversetup as s on s.line=d.appline left join client on client.email=d.approver 
              where s.isapprover=1 and s.doc='CV' and ifnull(client.clientid,0)<>0 order by d.ordernum");
        }
      }

      if ($statid == 50) {
        $this->coreFunctions->sbcupdate('cntnuminfo', ['releasedate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $config['params']['trno']]);

        $this->coreFunctions->execqry("update cvitems as cv 
	        left join hstockinfotrans as prs on prs.trno=cv.reqtrno and prs.line=cv.reqline set prs.payreleased='" . $this->othersClass->getCurrentTimeStamp() . "' 
          where cv.trno=" . $config['params']['trno'], 'update');

        $this->coreFunctions->execqry("update ladetail as cv left join glstock as rr on rr.trno=cv.refx
          left join hstockinfotrans as prs on prs.trno=rr.reqtrno and prs.line=rr.reqline set prs.payreleased='" . $this->othersClass->getCurrentTimeStamp() . "' 
          where cv.refx<>0 and cv.trno=" . $config['params']['trno'], 'update');
      }

      $this->coreFunctions->sbcupdate('cntnuminfo', ['instructions' => ''], ['trno' => $config['params']['trno']]);
      // $this->logger->sbcwritelog($config['params']['trno'], $config, 'HEAD', $label);
      $this->logger->sbcstatlog($config['params']['trno'], $config, 'HEAD', $label);

      return ['status' => true, 'msg' => 'Successfully updated.', 'backlisting' => true];
    } else {
      return ['status' => false, 'msg' => 'Update failed.'];
    }
  }

  public function itemscollected($config)
  {
    $posted = $this->othersClass->isposted($config);
    if ($posted) {
      return ['status' => false, 'msg' => 'Already posted.'];
    }

    $access = $this->othersClass->checkAccess($config['params']['user'], 3991);
    if (!$access) {
      return ['status' => false, 'msg' => 'Please advice your administrator regarding this restriction.'];
    }

    if ($this->coreFunctions->sbcupdate($this->tablenum, ['statid' => 48], ['trno' => $config['params']['trno']])) {
      // $this->logger->sbcwritelog($config['params']['trno'], $config, 'HEAD', 'Items Collected');
      $this->logger->sbcstatlog($config['params']['trno'], $config, 'HEAD', 'Items Collected');
      return ['status' => true, 'msg' => 'Successfully updated.', 'backlisting' => true];
    } else {
      return ['status' => false, 'msg' => 'Failed to tag items collected.'];
    }
  }

  public function voidpayment($config)
  {
    $trno = $config['params']['trno'];
    $qry = "select info.trno,info.releasedate,det.line,cv.docno,cv.client,cv.clientname
    from cntnuminfo as info
    left join lahead as cv on cv.trno=info.trno
    left join detailinfo as det on det.trno=info.trno
    where info.releasedate is not null and info.trno=? and info.trno in (select voiddetail.trno from voiddetail where trno=?)";
    $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    if (!empty($data)) {
      if ($this->coreFunctions->sbcupdate($this->tablenum, ['statid' => 26], ['trno' => $config['params']['trno']])) {
        $this->coreFunctions->execqry("update cntnuminfo set releasedate=NULL,status='Void' where trno=" . $config['params']['trno']);

        $this->coreFunctions->execqry("update cvitems as cv 
                                            left join hstockinfotrans as prs on prs.trno=cv.reqtrno and prs.line=cv.reqline set prs.payreleased=null where cv.trno=" . $trno, 'update');

        $this->coreFunctions->execqry("update ladetail as cv left join glstock as rr on rr.trno=cv.refx
                                            left join hstockinfotrans as prs on prs.trno=rr.reqtrno and prs.line=rr.reqline set prs.payreleased=null where cv.refx<>0 and cv.trno=" . $trno, 'update');


        // $this->logger->sbcwritelog($config['params']['trno'], $config, 'HEAD', 'Items Collected');
        $this->logger->sbcstatlog($config['params']['trno'], $config, 'HEAD', 'Items Collected');
        return ['status' => true, 'msg' => 'Successfully updated.', 'backlisting' => true];
      }
    } else {
      return ['status' => false, 'msg' => 'Items were not voided.'];
    }
  }

  public function donetodo($config)
  {
    $trno = $config['params']['trno'];

    $msg = "";
    $status = true;

    $user = $config['params']['user'];
    $userid = $this->coreFunctions->datareader("select userid as value from useraccess where username = ? 
              union all select clientid as value from client where email = ?", [$user, $user]);

    $donedate = $this->coreFunctions->opentable("select line,donedate from cntnumtodo where trno=? and (userid = ? or clientid = ?) and donedate is null ", [$trno, $userid, $userid]);

    if (empty($donedate[0]->donedate)) {
      $this->coreFunctions->execqry("update cntnumtodo set donedate='" . $this->othersClass->getCurrentTimeStamp() . "' where trno = $trno and (userid = ? or clientid = ?) and line = '" . $donedate[0]->line . "' ", "update", [$userid, $userid]);
    }

    return ['status' => $status, 'msg' => $msg, 'reloadhead' => true];
  }

  public function diagram($config)
  {

    $data = [];
    $nodes = [];
    $links = [];
    $data['width'] = 1500;
    $startx = 100;

    //CV
    $qry = "
    select head.docno, date(head.dateid) as dateid, head.trno,
    CAST(concat('CV Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem, detail.refx
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
          'cv',
          [
            'align' => 'left',
            'x' => $startx + 800,
            'y' => 100,
            'w' => 250,
            'h' => 80,
            'type' => $t[$key]->docno,
            'label' => $t[$key]->rem,
            'color' => 'red',
            'details' => [$t[$key]->dateid]
          ]
        );

        //PV
        $pvqry = "
        select head.docno, date(head.dateid) as dateid, head.trno,
        CAST(concat('PV Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem,
        detail.refx
        from glhead as head
        left join gldetail as detail on head.trno = detail.trno
        where head.trno = ? and head.doc = 'PV'";

        $pvdata = $this->coreFunctions->opentable($pvqry, [$t[$key]->refx]);
        if (!empty($pvdata)) {
          foreach ($pvdata as $key2 => $value2) {
            data_set(
              $nodes,
              'pv',
              [
                'align' => 'left',
                'x' => $startx + 400,
                'y' => 100,
                'w' => 250,
                'h' => 80,
                'type' => $pvdata[$key2]->docno,
                'label' => $pvdata[$key2]->rem,
                'color' => 'red',
                'details' => [$pvdata[$key2]->dateid]
              ]
            );
            array_push($links, ['from' => 'cv', 'to' => 'pv']);
          }
        }

        if ($t[$key]->refx != 0) {
          if (!empty($pvdata)) {
            $cvtrno = $pvdata[0]->refx;
          } else {
            $cvtrno = $t[$key]->refx;
          }

          //RR
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
          $rrdata = $this->coreFunctions->opentable($qry, [$cvtrno]);
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

              if (!empty($pvdata)) {
                array_push($links, ['from' => 'rr', 'to' => 'pv']);
              } else {
                array_push($links, ['from' => 'rr', 'to' => 'cv']);
              }

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

    if ($data[0]->isnoedit == 1) {
      return ['status' => false, 'msg' => 'Not allowed to update, already approved.'];
    }

    if (!$isupdate) {
      $data[0]->errcolor = 'bg-red-2';
      return ['row' => $data, 'status' => true, 'msg' => 'Payment amount is greater than setup amount.'];
    } else {
      return ['row' => $data, 'status' => true, 'msg' => $isupdate['msg']];
    }
  }


  public function updateitem($config)
  {

    $isnoedit = $this->coreFunctions->opentable("select trno from " . $this->detail . " where isnoedit=1 and trno=?", [$config['params']['trno']]);
    if (!empty($isnoedit)) {
      return ['status' => false, 'msg' => 'Not allowed to update, already approved.'];
    }

    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $isupdate = $this->additem('update', $config);
      if ($isupdate['status'] == false) {
        break;
      }
    }
    $data = $this->opendetail($config['params']['trno'], $config);
    $data2 = json_decode(json_encode($data), true);

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
        return ['accounting' => $data, 'status' => true, 'msg' => 'Please check some items that have zero quantity (' . $msg1 . ' / ' . $msg2 . ')'];
      } else {
        return ['accounting' => $data, 'status' => $isupdate['status'], 'msg' => $isupdate['msg']];
      }
    }
  } //end function

  public function addallitem($config)
  {
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $items = $this->additem('insert', $config);
    }

    if ($items['status'] == false) {
      return ['status' => $items['status'], 'msg' => $items['msg']];
    }

    $data = $this->opendetail($config['params']['trno'], $config);
    return ['accounting' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
  } //end function


  // insert and update detail
  public function additem($action, $config)
  {
    $acno = $config['params']['data']['acno'];
    $acnoname = $config['params']['data']['acnoname'];
    $trno = $config['params']['trno'];
    $db = $config['params']['data']['db'];
    $cr = $config['params']['data']['cr'];
    $appamt = isset($config['params']['data']['appamt']) ? $config['params']['data']['appamt'] : 0;
    $fdb = $config['params']['data']['fdb'];
    $fcr = $config['params']['data']['fcr'];
    $postdate = $config['params']['data']['postdate'];
    $rem = $config['params']['data']['rem'];
    $client = $config['params']['data']['client'];

    switch ($config['params']['action']) {
      case 'getpcvselected':
        $project = $config['params']['data']['project'];
        break;
    }

    $refx = 0;
    $linex = 0;
    $ref = '';
    $checkno = '';
    $isewt = false;
    $isvat = false;
    $isvewt = false;
    $project = 0;
    $ewtcode = '';
    $ewtrate = '';
    $damt = 0;
    $subproject = 0;
    $stageid = 0;
    $pcvtrno = 0;
    $void = 0;
    $isencashment = $this->coreFunctions->getfieldvalue($this->head, "isencashment", "trno=?", [$trno]);
    $isonlineencashment = $this->coreFunctions->getfieldvalue($this->head, 'isonlineencashment', 'trno=?', [$trno]);
    $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$acno]);
    $ischecksetup = false;
    $branch = 0;
    $deptid = 0;
    $acnocat = $this->coreFunctions->getfieldvalue('coa', 'cat', 'acnoid =?', [$acnoid]);

    $si1 = '';
    $si2 = '';


    if (isset($config['params']['data']['si1'])) {
      $si1 = $config['params']['data']['si1'];
    }
    if (isset($config['params']['data']['si2'])) {
      $si2 = $config['params']['data']['si2'];
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



    if (isset($config['params']['data']['projectid'])) {
      $project = $config['params']['data']['projectid'];
    }

    if (isset($config['params']['data']['subproject'])) {
      $subproject = $config['params']['data']['subproject'];
    }
    if (isset($config['params']['data']['stageid'])) {
      $stageid = $config['params']['data']['stageid'];
    }

    if (isset($config['params']['data']['pcvtrno'])) {
      $pcvtrno = $config['params']['data']['pcvtrno'];
    }

    if (isset($config['params']['data']['void'])) {
      $void = $config['params']['data']['void'];
    }

    if ($postdate == '') {
      $postdate = $this->coreFunctions->getfieldvalue($this->head, "dateid", "trno=?", [$trno]);
    }

    if ($ewtcode == '') {
      $ewtcode = $this->coreFunctions->getfieldvalue($this->head, "ewt", "trno=?", [$trno]);
    }

    if ($project == '') {
      $project = $this->coreFunctions->getfieldvalue($this->head, "projectid", "trno=?", [$trno]);
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

    if (isset($config['params']['data']['branch'])) {
      $branch = $config['params']['data']['branch'];
    }

    if ($branch == 0) {
      $branch = $this->coreFunctions->getfieldvalue($this->head, "branch", "trno=?", [$trno]);
    }

    if (isset($config['params']['data']['deptid'])) {
      $deptid = $config['params']['data']['deptid'];
    }

    if ($deptid == 0) {
      $deptid = $this->coreFunctions->getfieldvalue($this->head, "deptid", "trno=?", [$trno]);
    }

    if ($checkno == '') {
      $checksetup = $this->coreFunctions->getfieldvalue("checksetup", "line", "acnoid=? and (current =0 or current<>end)", [$acnoid]);
      if ($checksetup != 0) {
        $current = $this->coreFunctions->getfieldvalue("checksetup", "current", "acnoid=?", [$acnoid]);
        if ($current != 0) {
          $checkno = $current + 1;
        } else {
          $checkno = $this->coreFunctions->getfieldvalue("checksetup", "start", "acnoid=?", [$acnoid]);
        }

        $ischecksetup = true;
      }
    }

    $db = $this->othersClass->sanitizekeyfield('db', $db);
    $cr = $this->othersClass->sanitizekeyfield('cr', $cr);

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
      $project = $config['params']['data']['projectid'];
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

    if ($action == 'insert') {
      if ($config['params']['action'] == 'getpcvselected') {
        $data = [
          'trno' => $trno,
          'line' => $line,
          'sortline' => $line,
          'acnoid' => $acnoid,
          'client' => $client,
          'db' => $db,
          'cr' => $cr,
          'fdb' => $fdb,
          'fcr' => $fcr,
          'postdate' => $postdate,
          'rem' => $rem,
          'projectid' => $project,
          'refx' => $refx,
          'linex' => $linex,
          'ref' => $ref,
          'checkno' => $checkno,
          'isewt' => $isewt,
          'isvat' => $isvat,
          'isvewt' => $isvewt,
          'ewtcode' => $ewtcode,
          'ewtrate' => $ewtrate,
          'damt' => $damt,
          'subproject' => $subproject,
          'stageid' => $stageid,
          'pcvtrno' => $pcvtrno,
          'void' => $void,
          'appamt' => $appamt
        ];
      } else {
        $data = [
          'trno' => $trno,
          'line' => $line,
          'sortline' => $line,
          'acnoid' => $acnoid,
          'client' => $client,
          'db' => $db,
          'cr' => $cr,
          'fdb' => $fdb,
          'fcr' => $fcr,
          'postdate' => $postdate,
          'rem' => $rem,
          'projectid' => $project,
          'refx' => $refx,
          'linex' => $linex,
          'ref' => $ref,
          'checkno' => $checkno,
          'isewt' => $isewt,
          'isvat' => $isvat,
          'isvewt' => $isvewt,
          'ewtcode' => $ewtcode,
          'ewtrate' => $ewtrate,
          'damt' => $damt,
          'subproject' => $subproject,
          'stageid' => $stageid,
          'pcvtrno' => $pcvtrno,
          'void' => $void,
          'branch' => $branch,
          'deptid' => $deptid,
          'appamt' => $appamt
        ];
      }
    } else {
      $data = [
        'trno' => $trno,
        'line' => $line,
        'sortline' => $line,
        'acnoid' => $acnoid,
        'client' => $client,
        'db' => round($db, 2),
        'cr' => round($cr, 2),
        'fdb' => $fdb,
        'fcr' => $fcr,
        'postdate' => $postdate,
        'rem' => $rem,
        'projectid' => $project,
        'refx' => $refx,
        'linex' => $linex,
        'ref' => $ref,
        'checkno' => $checkno,
        'isewt' => $isewt,
        'isvat' => $isvat,
        'isvewt' => $isvewt,
        'ewtcode' => $ewtcode,
        'ewtrate' => $ewtrate,
        'damt' => $damt,
        'subproject' => $subproject,
        'stageid' => $stageid,
        'pcvtrno' => $pcvtrno,
        'void' => $void,
        'branch' => $branch,
        'deptid' => $deptid,
        'appamt' => $appamt
      ];
    }

    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }

    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $data['editdate'] = $current_timestamp;
    $data['editby'] = $config['params']['user'];
    $msg = '';
    $status = true;


    $cbalias = $this->coreFunctions->getfieldvalue("coa", "left(alias,2)", "acnoid=?", [$acnoid]);


    if ($cbalias == 'CB' && $checkno != '') {
      $qry = "select trno as value from (select trno from ladetail where cr<>0 and acnoid = " . $acnoid . " and trno <> " . $trno . " and checkno ='" . $checkno . "' union all
            select trno from gldetail where cr<>0 and acnoid = " . $acnoid . " and trno <> " . $trno . " and checkno ='" . $checkno . "') as a limit 1";
      $isexist = $this->coreFunctions->datareader($qry, [], '', true);

      if ($isexist != 0) {

        if ($isencashment == 0 && $isonlineencashment == 0) {
          $msg = 'Check number already exist.';
          return ['status' => false, 'msg' => $msg];
        }
      }
    }

    if ($cbalias == 'CB' && $checkno == '') {
      return ['status' => false, 'msg' => 'Please enter check# for Bank Accounts.'];
    }

    if ($action == 'insert') {
      $data['encodedby'] = $config['params']['user'];
      $data['encodeddate'] = $current_timestamp;
      $data['sortline'] =  $data['line'];
      if ($this->coreFunctions->sbcinsert($this->detail, $data) == 1) {
        switch ($this->companysetup->getsystemtype($config['params'])) {
          case 'AIMS':
          case 'ATI':
            $detailinfo_data = [
              'trno' => $trno,
              'line' => $line,
              'si1' => $si1

            ];
            $this->coreFunctions->sbcinsert('detailinfo', $detailinfo_data);
            break;
        }
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

        if ($ischecksetup) {
          $this->coreFunctions->execqry("update checksetup set current = ? where acnoid = ? and current <> end", "update", [$checkno, $acnoid]);
        }

        if ($pcvtrno != 0) {
          $this->coreFunctions->sbcupdate("hsvhead", ['cvtrno' => $trno], ['trno' => $pcvtrno]);
        }
        $row = $this->opendetailline($config);
        return ['row' => $row, 'status' => true, 'msg' => $msg];
      } else {
        return ['status' => false, 'msg' => 'Add account failed.'];
      }
    } elseif ($action == 'update') {
      $return = true;

      if ($this->coreFunctions->sbcupdate($this->detail, $data, ['trno' => $trno, 'line' => $line]) == 1) {

        switch ($this->companysetup->getsystemtype($config['params'])) {
          case 'AIMS':
          case 'ATI':
            $detailinfo_data = [
              'trno' => $trno,
              'line' => $line,
              'si1' => $si1,
              'si2' => $si2
            ];
            $this->coreFunctions->sbcupdate('detailinfo', $detailinfo_data, ['trno' => $trno, 'line' => $line]);
            break;
        }
        if ($refx != 0) {
          if (!$this->sqlquery->setupdatebal($refx, $linex, $acno, $config)) {
            $this->coreFunctions->sbcupdate($this->detail, ['db' => 0, 'cr' => 0, 'fdb' => 0, 'fcr' => 0], ['trno' => $trno, 'line' => $line]);
            $this->sqlquery->setupdatebal($refx, $linex, $acno, $config);
            $return = false;
          }
        }

        if ($pcvtrno != 0) {
          $this->coreFunctions->sbcupdate("hsvhead", ['cvtrno' => $trno], ['trno' => $pcvtrno]);
        }

        if ($data['void'] == 1) {
          $qry = "insert into voiddetail (postdate,trno,line,acnoid,client,db,cr,fdb,fcr,refx,linex,encodeddate,encodedby,editdate,
            editby,ref,checkno,rem,clearday,pdcline,projectid,isewt,isvat,ewtcode,ewtrate,forex,isvewt,subproject,stageid,void,branch,deptid)
            select d.postdate,d.trno,d.line,d.acnoid,
            ifNull(client.client,''),d.db,d.cr,d.fdb,d.fcr,d.refx,d.linex,
            d.encodeddate,d.encodedby,d.editdate,d.editby,d.ref,d.checkno,d.rem,d.clearday,d.pdcline,d.projectid,
            d.isewt,d.isvat,d.ewtcode,d.ewtrate,d.forex,d.isvewt,d.subproject,d.stageid,d.void,d.branch,d.deptid
            from " . $this->head . " as h
            left join " . $this->detail . " as d on d.trno=h.trno
            left join client on client.client=d.client
            where  d.trno=? and d.line =?
        ";
          $result = $this->coreFunctions->execqry($qry, 'insert', [$trno, $line]);
          if ($result) {
            $this->coreFunctions->execqry("delete from " . $this->detail . " where trno =? and line =?", 'delete', [$trno, $line]);

            if ($refx != 0) {
              if (!$this->sqlquery->setupdatebal($refx, $linex, $acno, $config)) {
                $this->coreFunctions->sbcupdate($this->detail, ['db' => 0, 'cr' => 0, 'fdb' => 0, 'fcr' => 0], ['trno' => $trno, 'line' => $line]);
                $this->sqlquery->setupdatebal($refx, $linex, $acno, $config);
                $return = false;
              }
            }

            $this->logger->sbcwritelog($trno, $config, 'VOID', 'AccountID: ' . $acnoid . ' Check#: ' . $checkno);
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
    $data = $this->coreFunctions->opentable('select coa.acno,t.refx,t.linex from ' . $this->detail . ' as t 
                  left join coa on coa.acnoid=t.acnoid where t.trno=? and t.refx<>0', [$trno]);
    $pcv = $this->coreFunctions->opentable('select distinct pcvtrno from ' . $this->detail . ' as t  
    where t.trno=? and t.pcvtrno<>0', [$trno]);

    $isnoedit = $this->coreFunctions->opentable("select trno from " . $this->detail . " where isnoedit=1 and trno=?", [$trno]);
    if (!empty($isnoedit)) {
      return ['status' => false, 'msg' => 'Not allowed to delete, already approved.'];
    }
    //check series setup
    $detail = $this->opendetail($trno, $config);
    foreach ($detail as $key => $value) {
      $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "acno=?", [$detail[$key]->acno]);
      $alias = $this->coreFunctions->getfieldvalue("coa", "left(alias,2)", "acnoid=?", [$acnoid]);
      if ($alias == 'CB') {
        $exist = $this->coreFunctions->getfieldvalue("checksetup", "line", "acnoid=?", [$acnoid]);
        if ($exist != 0) {
          $current = $this->coreFunctions->getfieldvalue("checksetup", "current", "acnoid=? and " . $detail[$key]->checkno . " between `start` and `end`", [$acnoid]);
          if ($current == $detail[$key]->checkno) {
            $this->coreFunctions->execqry("update checksetup set current = " . $detail[$key]->checkno . "-1 where acnoid =" . $acnoid . " and " . $detail[$key]->checkno . " between `start` and `end`");
          } else {
            return ['status' => false, 'msg' => 'Not allowed to delete' . $detail[$key]->acno . ', last check number used is ' . $current . ', you may VOID the entry to cancel it.'];
          }
        }
      }
    }


    $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);
    foreach ($data as $key => $value) {
      $this->sqlquery->setupdatebal($data[$key]->refx, $data[$key]->linex, $data[$key]->acno, $config);
    }

    if (!empty($pcv)) {
      foreach ($pcv as $key2 => $value) {
        $this->coreFunctions->execqry("update hsvhead set cvtrno = 0 where trno=" . $pcv[$key2]->pcvtrno, "update");
      }
    }

    $this->coreFunctions->execqry('delete from detailinfo where trno=?', 'delete', [$trno]);
    if ($this->coreFunctions->execqry("update cvitems as cv left join hpostock as s on s.trno=cv.refx and s.line=cv.linex set s.cvtrno=0 where cv.trno=" . $trno)) {
      $this->coreFunctions->execqry("delete from cvitems where trno=?", 'delete', [$trno]);
    }

    $this->logger->sbcwritelog($trno, $config, 'ACCTG', 'DELETED ALL ACCTG ENTRIES');
    return ['status' => true, 'msg' => 'Successfully deleted.', 'accounting' => []];
  }

  public function deleteitem($config)
  {
    $config['params']['trno'] = $config['params']['row']['trno'];
    $config['params']['line'] = $config['params']['row']['line'];
    $data = $this->opendetailline($config);

    if ($data[0]->isnoedit == 1) {
      return ['status' => false, 'msg' => 'Not allowed to delete, already approved.'];
    }

    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $pcvtrno = $config['params']['row']['pcvtrno'];

    //check series setup    
    $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "acno=?", [$data[0]->acno]);
    $alias = $this->coreFunctions->getfieldvalue("coa", "left(alias,2)", "acnoid=?", [$acnoid]);
    if ($alias == 'CB') {
      $exist = $this->coreFunctions->getfieldvalue("checksetup", "line", "acnoid=?", [$acnoid]);
      if ($exist != 0) {
        $current = $this->coreFunctions->getfieldvalue("checksetup", "current", "acnoid=? and " . $data[0]->checkno . " between `start` and `end`", [$acnoid]);
        if ($current == $data[0]->checkno) {
          $this->coreFunctions->execqry("update checksetup set current = " . $data[0]->checkno . "-1 where acnoid =" . $acnoid . " and " . $data[0]->checkno . " between `start` and `end`");
        } else {
          return ['status' => false, 'msg' => 'Not allowed to delete' . $data[0]->acno . ', last check number used is ' . $current . ', you may VOID the entry to cancel it.'];
        }
      }
    }


    $update = "update hsvhead set cvtrno = 0 where trno =?";
    $this->coreFunctions->execqry($update, 'update', [$pcvtrno]);

    $qry = "delete from " . $this->detail . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);

    $this->coreFunctions->execqry('delete from detailinfo where trno=? and line=?', 'delete', [$trno, $line]);
    if ($this->coreFunctions->execqry("update cvitems as cv left join hpostock as s on s.trno=cv.refx and s.line=cv.linex set s.cvtrno=0 where cv.trno=" . $trno . " and cv.line=" . $line)) {
      $this->coreFunctions->execqry("delete from cvitems where trno=? and line=?", 'delete', [$trno, $line]);
    }

    if ($data[0]->refx != 0) {
      $this->sqlquery->setupdatebal($data[0]->refx, $data[0]->linex, $data[0]->acno, $config);
      $this->coreFunctions->execqry("update hstockinfo set paytrno=0 where trno=" . $data[0]->refx . " and paytrno=" . $trno);
    }

    $data = json_decode(json_encode($data), true);
    $this->logger->sbcwritelog(
      $trno,
      $config,
      'DETAILINFO',
      'DELETE - Line:' . $line
        . ' Notes:' . $config['params']['row']['rem']
    );
    $this->logger->sbcwritelog($trno, $config, 'ACCTG', 'REMOVED - Line:' . $line . ' Code:' . $data[0]['acno'] . ' DB:' . $data[0]['db'] . ' CR:' . $data[0]['cr'] . ' Client:' . $data[0]['client'] . ' Date:' . $data[0]['postdate'] . ' Ref:' . $data[0]['ref']);
    return ['status' => true, 'msg' => 'Account was successfully deleted.'];
  } // end function

  public function getunpaidselected($config)
  {
    $trno = $config['params']['trno'];
    $rows = [];
    $data = $config['params']['rows'];
    if (!empty($data)) {
      $this->coreFunctions->sbcupdate($this->head, ['vattype' => $data[0]['vattype'], 'tax' => $data[0]['tax']], ['trno' => $trno]);
      $this->coreFunctions->sbcupdate("cntnuminfo", ['pdeadline' => $data[0]['pdeadline']], ['trno' => $trno]);
    }
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
      $config['params']['data']['project'] = $data[$key]['projectid'];
      $config['params']['data']['subproject'] = $data[$key]['subproject'];
      $config['params']['data']['stageid'] = $data[$key]['stageid'];
      $config['params']['data']['client'] = $data[$key]['client'];
      $config['params']['data']['refx'] = $data[$key]['trno'];
      $config['params']['data']['linex'] = $data[$key]['line'];
      $config['params']['data']['ref'] = $data[$key]['docno'];
      $config['params']['data']['si1'] = $data[$key]['yourref'];
      $config['params']['data']['si2'] = '';

      $return = $this->additem('insert', $config);
      if ($return['status']) {
        array_push($rows, $return['row'][0]);
      }
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.', 'reloadhead' => true];
  } //end function

  public function getporeqpaydetails($config)
  {
    $trno = $config['params']['trno'];
    $client = $this->coreFunctions->getfieldvalue("lahead", "client", "trno=?", [$trno]);
    $docno = $this->coreFunctions->getfieldvalue("lahead", "docno", "trno=?", [$trno]);

    $rows = [];

    $totalpo = 0;
    $insert_success = true;

    foreach ($config['params']['rows'] as $key => $value) {
      $config['params']['rows'][$key]['ext'] = $this->othersClass->sanitizekeyfield("ext", $config['params']['rows'][$key]['ext']);
      $totalpo = $totalpo + $config['params']['rows'][$key]['ext'];
    }

    if ($totalpo > 0) {
      $acno = $this->coreFunctions->getfieldvalue("coa", "acno", "alias=?", ['AP98']);
      if ($acno == '') {
        return ['row' => [], 'status' => false, 'msg' => 'Please setup advances to supplier account (AP98).'];
      }

      $headdata = [
        'vattype' => $config['params']['rows'][0]['vattype'],
        'tax' => $config['params']['rows'][0]['tax'],
        'cur' => $config['params']['rows'][0]['cur'],
        'forex' => $config['params']['rows'][0]['forex'],
        'terms' => $config['params']['rows'][0]['terms'],
        'modeofpayment' => strtoupper($config['params']['rows'][0]['paymentname']),
      ];

      $this->coreFunctions->sbcupdate($this->head, $headdata, ['trno' => $trno]);
      $this->coreFunctions->sbcupdate("cntnuminfo", ['pdeadline' => $config['params']['rows'][0]['pdeadline']], ['trno' => $trno]);

      $acnoname = $this->coreFunctions->getfieldvalue("coa", "acno", "alias=?", ['AP98']);

      $config['params']['data']['acno'] = $acno;
      $config['params']['data']['acnoname'] = $acnoname;
      $config['params']['data']['db'] = $totalpo;
      $config['params']['data']['cr'] = 0;
      $config['params']['data']['fdb'] = 0;
      $config['params']['data']['fcr'] = 0;
      $config['params']['data']['postdate'] = '';
      $config['params']['data']['rem'] = '';
      $config['params']['data']['client'] = $client;

      $return = $this->additem('insert', $config);

      if ($return['status']) {
        array_push($rows, $return['row'][0]);
      } else {
        $insert_success = false;
      }
      $msg = 'Accounts were successfully added.';
      if ($insert_success) {
        foreach ($config['params']['rows'] as $key => $value) {
          $cvitems = [
            'trno' => $trno,
            'line' => $return['row'][0]->line,
            'refx' => $config['params']['rows'][$key]['trno'],
            'linex' => $config['params']['rows'][$key]['line'],
            'reqtrno' => $config['params']['rows'][$key]['reqtrno'],
            'reqline' => $config['params']['rows'][$key]['reqline'],
            'cdrefx' => $config['params']['rows'][$key]['cdrefx'],
            'cdlinex' => $config['params']['rows'][$key]['cdlinex']
          ];

          $this->coreFunctions->sbcinsert("cvitems", $cvitems);
          $this->coreFunctions->execqry("update hpostock set cvtrno=" . $trno . " where trno=" . $config['params']['rows'][$key]['trno'] . " and line=" . $config['params']['rows'][$key]['line']);
          $this->coreFunctions->execqry("update hprstock set isforrr=1 where trno=" . $config['params']['rows'][$key]['reqtrno'] . " and line=" . $config['params']['rows'][$key]['reqline']);
          $this->coreFunctions->execqry("update hstockinfotrans set cvref='" . $docno . " - Draft', otherleadtime='" . $this->othersClass->getCurrentTimeStamp() . "',isforpay=1 where trno=" . $config['params']['rows'][$key]['reqtrno'] . " and line=" . $config['params']['rows'][$key]['reqline']);
        }
      } else {
        $msg = 'Failed to insert selected PO.';
        return ['row' => [], 'status' => false, 'msg' =>  $msg];
      }

      return ['row' => $rows, 'status' => true, 'msg' => $msg, 'reloadhead' => true];
    } else {
      return ['row' => [], 'status' => false, 'msg' =>  'Selected PO doesn`t have payable amount.'];
    }
  }

  public function getpcvselected($config)
  {
    $trno = $config['params']['trno'];
    $client = $this->coreFunctions->getfieldvalue("lahead", "client", "trno=?", [$trno]);
    $rows = [];

    $strtrno = '';

    foreach ($config['params']['rows'] as $key => $value) {
      if ($strtrno == '') {
        $strtrno .= $config['params']['rows'][$key]['trno'];
      } else {
        $strtrno .= ',' . $config['params']['rows'][$key]['trno'];
      }
    }

    $qry = "select sum(detail.db) as db,sum(detail.cr) as cr,
          detail.projectid as headprjid,coa.acno,coa.acnoname,detail.isvat,
          detail.isewt,detail.ewtrate,detail.ewtcode,detail.isvewt,detail.projectid,head.vattype,head.tax
          from hsvhead as head 
          left join hsvdetail as detail on detail.trno = head.trno
          left join coa as coa on coa.acnoid = detail.acnoid
          left join transnum on transnum.trno = head.trno 
          where head.cvtrno=0 and left(coa.alias,2)<>'PC' and head.trno in (" . $strtrno . ")
          group by detail.projectid,coa.acno,coa.acnoname,detail.isvat,detail.isewt,detail.ewtrate,detail.ewtcode,detail.isvewt,
          detail.projectid,head.vattype,head.tax order by head.vattype,detail.projectid";

    $data = $this->coreFunctions->opentable($qry);

    $insert_success = true;

    if (!empty($data)) {
      foreach ($data as $key2 => $value) {

        $config['params']['data']['acno'] = $data[$key2]->acno;
        $config['params']['data']['acnoname'] = $data[$key2]->acnoname;
        $config['params']['data']['db'] = $data[$key2]->db;
        $config['params']['data']['cr'] = $data[$key2]->cr;
        $config['params']['data']['fdb'] = 0;
        $config['params']['data']['fcr'] = 0;
        $config['params']['data']['postdate'] = '';
        $config['params']['data']['rem'] = '';
        $config['params']['data']['project'] = $data[$key2]->projectid;
        $config['params']['data']['client'] = $client;
        $config['params']['data']['pcvtrno'] = 0;
        $config['params']['data']['ref'] = '';
        $config['params']['data']['isewt'] = $data[$key2]->isewt;
        $config['params']['data']['isvat'] = $data[$key2]->isvat;
        $config['params']['data']['isvewt'] = $data[$key2]->isvewt;
        $config['params']['data']['ewtcode'] = $data[$key2]->ewtcode;
        $config['params']['data']['ewtrate'] = $data[$key2]->ewtrate;


        $return = $this->additem('insert', $config);



        if ($return['status']) {
          array_push($rows, $return['row'][0]);
        } else {
          $insert_success = false;
        }
      } //end foreach

    }
    $msg = 'Accounts were successfully added.';
    if ($insert_success) {

      $this->coreFunctions->execqry("update hsvhead set cvtrno=" . $trno . " where trno in (" . $strtrno . ")");
    } else {
      $msg = 'Failed to insert selected PCV.';
    }

    return ['row' => $rows, 'status' => true, 'msg' => $msg];
  } //end function

  public function generateewt_afti($config)
  {
    $trno = $config['params']['trno'];
    $data = $config['params']['row'];
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
    $ewtacno = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['APWT1']);
    $taxacno = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['TX1']);
    $project = $this->coreFunctions->getfieldvalue($this->head, 'projectid', 'trno=?', [$trno]);

    if (empty($ewtacno) || empty($taxacno)) {
      $status = false;
      $msg = "Please setup account for EWT and Input VAT.";
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
        if ($value['isvat'] == 'true' or $value['isewt'] == 'true' or $value['isvewt'] == 'true') {
          $damt   = $value['damt'];

          if ($value['isvewt'] == 'true') { //for vewt
            if (floatval($value['db']) != 0) {
              $dbval = $damt;
              $crval = 0;
              $ewtvalue =  (($dbval / 1.12) * ($value['ewtrate'] / 100));
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
              $crval  = 0;
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
                $ewtvalue =  ($dbval * ($value['ewtrate'] / 100));
              } else {
                $dbval = $damt;
                $ewtvalue =  ($dbval * ($value['ewtrate'] / 100));
              }
              $crval = 0;
            } else {
              if ($value['isvat'] == 'true') {
                $crval = $damt / $vatrate;
                $ewtvalue = (($crval * ($value['ewtrate'] / 100)) * -1);
              } else {
                $crval = $damt;
                $ewtvalue =  (($crval * ($value['ewtrate'] / 100)) * -1);
              }
              $dbval = 0;
            }
          }


          $ret = $this->coreFunctions->execqry("update ladetail set db = " . round($dbval, 2) . ",cr=" . round($crval, 2) . ",fdb=" . round($dbval * $value['forex'], 2) . ",fcr=" . round($crval * $value['forex'], 2) . " where trno = " . $trno . " and line = " . $value['line'], "update");
          if ($value['refx'] != 0) {
            if (!$this->sqlquery->setupdatebal($value['refx'], $value['linex'], $value['acno'], $config)) {
              $this->coreFunctions->sbcupdate($this->detail, ['db' => 0, 'cr' => 0, 'fdb' => 0, 'fcr' => 0], ['trno' => $trno, 'line' => $value['line']]);
              $this->sqlquery->setupdatebal($value['refx'], $value['linex'], $value['acno'], $config);
              $msg = "Payment amount is greater than amount setup.";
              $status = false;
              $vatvalue = 0;
              $ewtvalue = 0;
            }
          }


          if ($vatvalue != 0) {
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

            $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            $line = $line + 1;
          }


          if ($ewtvalue != 0 && $status == true) {
            $entry = ['line' => $line, 'acnoid' => $ewtacno, 'client' => $data[0]['client'], 'cr' => ($ewtvalue < 0 ? 0 : abs(round($ewtvalue, 2))), 'db' => ($ewtvalue < 0 ? abs(round($ewtvalue, 2)) : 0), 'postdate' => $data[0]['dateid'], 'fdb' => ($ewtvalue > 0 ? 0 : abs($ewtvalue)) * $forex, 'fcr' => ($ewtvalue > 0 ? abs($ewtvalue) : 0) * $forex, 'rem' => "Auto entry", 'cur' => $cur, 'forex' => $forex, 'projectid' => $project, 'ewtcode' => $value['ewtcode'], 'ewtrate' => $value['ewtrate']];

            $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            $line = $line + 1;
          }
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
          $msg = "Automatic accounting entry success.";
          $status = true;
          //return true;
        } else {
          $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING ENTRY FAILED');
          $msg = "Automatic accounting entry failed.";
          $status = false;
        }
      }
    }

    $data = $this->opendetail($trno, $config);
    return ['accounting' => $data, 'status' => $status, 'msg' => $msg];
  } //end function

  public function generateewt($config)
  {
    $trno = $config['params']['trno'];
    $data = $config['params']['row'];

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
    $ewtacno = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['APWT1']);
    $taxacno = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['TX1']);
    $project = $this->coreFunctions->getfieldvalue($this->head, 'projectid', 'trno=?', [$trno]);
    if (empty($ewtacno) || empty($taxacno)) {
      $status = false;
      $msg = "Please setup account for EWT and Input VAT.";
    } else {

      $this->coreFunctions->execqry("delete from ladetail where trno = " . $trno . " and acnoid =" . $ewtacno, "delete");

      foreach ($data as $key => $value) {
        if ($value['void'] == 'true') {
          continue;
        }

        if ($value['isvat'] == 'true' or $value['isewt'] == 'true' or $value['isvewt'] == 'true') {
          $damt   = $value['damt'];

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
              $crval  = 0;
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
              } else {
                $dbval = $damt;
                $ewtvalue = $ewtvalue + ($dbval * ($value['ewtrate'] / 100));
              }
              $crval = 0;
            } else {
              if ($value['isvat'] == 'true') {
                $crval = $damt / $vatrate;
                $ewtvalue = $ewtvalue + (($crval * ($value['ewtrate'] / 100)) * -1);
              } else {
                $crval = $damt;
                $ewtvalue = $ewtvalue + (($crval * ($value['ewtrate'] / 100)) * -1);
              }
              $dbval = 0;
            }
          }


          $ret = $this->coreFunctions->execqry("update ladetail set db = " . round($dbval, 2) . ",cr=" . round($crval, 2) . ",fdb=" . round($dbval * $value['forex'], 2) . ",fcr=" . round($crval * $value['forex'], 2) . " where trno = " . $trno . " and line = " . $value['line'], "update");

          if ($value['refx'] != 0) {
            if (!$this->sqlquery->setupdatebal($value['refx'], $value['linex'], $value['acno'], $config)) {
              $this->coreFunctions->sbcupdate($this->detail, ['db' => 0, 'cr' => 0, 'fdb' => 0, 'fcr' => 0], ['trno' => $trno, 'line' => $value['line']]);
              $this->sqlquery->setupdatebal($value['refx'], $value['linex'], $value['acno'], $config);
              $msg = "Payment amount is greater than amount setup.";
              $status = false;
              $vatvalue = 0;
              $ewtvalue = 0;
            }
          }
        }
      }

      $qry = "select line as value from " . $this->detail . " where trno=? order by line desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$trno]);
      if ($line == '') {
        $line = 0;
      }
      $line = $line + 1;


      if ($vatvalue != 0) {
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
          'projectid' => $project
        ];

        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
        $line = $line + 1;
      }



      if ($ewtvalue != 0 && $status == true) {
        $entry = ['line' => $line, 'acnoid' => $ewtacno, 'client' => $data[0]['client'], 'cr' => ($ewtvalue < 0 ? 0 : abs(round($ewtvalue, 2))), 'db' => ($ewtvalue < 0 ? abs(round($ewtvalue, 2)) : 0), 'postdate' => $data[0]['dateid'], 'fdb' => ($ewtvalue > 0 ? 0 : abs($ewtvalue)) * $forex, 'fcr' => ($ewtvalue > 0 ? abs($ewtvalue) : 0) * $forex, 'rem' => "Auto entry", 'cur' => $cur, 'forex' => $forex, 'projectid' => $project];

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
        }

        if ($this->coreFunctions->sbcinsert($this->detail, $this->acctg) == 1) {
          $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING ENTRY SUCCESS');
          $msg = "Automatic accounting entry success.";
          $status = true;
          //return true;
        } else {
          $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING ENTRY FAILED');
          $msg = "Automatic accounting entry failed.";
          $status = false;
        }
      }
    }

    $data = $this->opendetail($trno, $config);
    return ['accounting' => $data, 'status' => $status, 'msg' => $msg];
  } //end function

  public function reportsetup($config)
  {


    $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
    $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);

    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Successfully Loaded.', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  }

  public function reportdata($config)
  {


    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
