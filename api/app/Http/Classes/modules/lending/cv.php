<?php

namespace App\Http\Classes\modules\lending;

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
  public $tablelogs_del = 'del_table_log';
  private $stockselect;
  public $defaultContra = 'PC1';

  private $fields = ['trno', 'docno', 'dateid', 'client', 'clientname', 'yourref', 'ourref', 'rem', 'forex', 'cur', 'address', 'tax', 'vattype', 'projectid', 'ewt', 'ewtrate',  'contra', 'isencashment', 'isonlineencashment', 'paymode', 'hacno', 'hacnoname', 'costcodeid', 'empid', 'deptid', 'amount', 'checkno', 'checkdate','acctname'];
  private $except = ['trno', 'dateid'];
  private $blnfields = ['isencashment', 'isonlineencashment'];
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
      'release' => 4391
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
    $listclientname = 4;
    $yourref = 5;
    $amt = 6;
    $ourref = 7;
    $rem = 8;
    $postdate = 9;

    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'yourref','amt', 'ourref', 'rem', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];

    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$listclientname]['style'] = 'width:350px;whiteSpace: normal;min-width:350px;';
    $cols[$yourref]['style'] = 'width:180px;whiteSpace: normal;min-width:180px;';
    $cols[$yourref]['align'] = 'text-left';
    $cols[$ourref]['align'] = 'text-left';
    $cols[$postdate]['label'] = 'Post Date';
    $cols[$liststatus]['name'] = 'statuscolor';


    $cols = $this->tabClass->delcollisting($cols);

    return $cols;
  }

  public function paramsdatalisting($config)
  {
    $companyid = $config['params']['companyid'];
    $col1 = [];
    $col2 = [];

    $fields = [];
    $allownew = $this->othersClass->checkAccess($config['params']['user'], 119);
    if ($allownew == '1') $fields = ['pickpo'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'pickpo.label', 'APPROVED LOAN APPLICATION');
    data_set($col1, 'pickpo.action', 'pendingloan');
    data_set($col1, 'pickpo.lookupclass', 'pendingloanshortcut');
    data_set($col1, 'pickpo.confirmlabel', 'Proceed to Issue Loan?');

    return ['status' => true, 'data' => [], 'txtfield' => ['col1' => $col1, 'col2' => $col2]];
  }

  public function loaddoclisting($config)
  {
    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));
    $itemfilter = $config['params']['itemfilter'];
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $condition = '';
    $limit = 'limit 150';
    $laleftjoin = '';
    $glleftjoin = '';
    $grpby = '';
    $lacr = '';
    $glcr = '';
    $lstatus = 'DRAFT';
    $lstatuscolor = 'red';
    $searchfilter = $config['params']['search'];

    switch ($itemfilter) {
      case 'draft':
        $condition = ' and head.lockdate is null and  num.postdate is null ';
        break;
      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
      case 'locked':
        $lstatus = 'LOCKED';
        $lstatuscolor = 'green';
        $condition = ' and head.lockdate is not null and num.postdate is null ';
        break;
    }

    $companyid = $config['params']['companyid'];
    $dateid = "left(head.dateid,10) as dateid";
    $orderby =  "order by  dateid desc, docno desc";

    if ($searchfilter != '') {
      $limit = "";
    }

    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'head.clientname', 'head.yourref', 'head.rem', 'head.ourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];

      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }

    $qry = "select head.trno,head.docno,head.clientname,$dateid $lacr , case ifnull(head.lockdate,'') when '' then 'DRAFT' else 'LOCKED' end as status,
    head.createby,head.editby,head.viewby,num.postedby, date(num.postdate) as postdate,(select format(sum(d.cr),2) from ".$this->detail." as d left join coa on coa.acnoid = d.acnoid where left(coa.alias,2)='CB' and d.trno = head.trno) as amt,
    head.yourref, head.ourref,head.rem, case ifnull(head.lockdate,'') when '' then 'red' else 'green' end as statuscolor           
    from " . $this->head . " as head 
    left join " . $this->tablenum . " as num on num.trno=head.trno " . $laleftjoin . "
    where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? 
    and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
    " . $grpby . " 
    union all
    select head.trno,head.docno,head.clientname,$dateid $glcr,'POSTED' as status,
    head.createby,head.editby,head.viewby, num.postedby, date(num.postdate) as postdate,(select format(sum(d.cr),2) from ".$this->hdetail." as d left join coa on coa.acnoid = d.acnoid where left(coa.alias,2)='CB' and d.trno = head.trno) as amt,
    head.yourref, head.ourref,head.rem,'grey' as statuscolor           
    from " . $this->hhead . " as head 
    left join " . $this->tablenum . " as num on num.trno=head.trno " . $glleftjoin . "
    where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? 
    and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
    " . $grpby . " 
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

    if ($this->companysetup->getisshowmanual($config['params'])) {
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => 'cv', 'title' => 'CV_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
    }

    //void
    $allowVoid = $this->othersClass->checkAccess($config['params']['user'], 5085);
    if ($allowVoid) {
      $buttons['others']['items']['void'] = ['label' => 'Void CV', 'todo' => ['lookupclass' => 'voidtrans', 'action' => 'voidtrans', 'access' => 'view', 'type' => 'navigation']];
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
    $companyid = $config['params']['companyid'];
    $release = $this->othersClass->checkAccess($config['params']['user'], 4391);
    $systype = $this->companysetup->getsystemtype($config['params']);
    $action = 0;
    $isewt = 1;
    $isvat = 2;
    $isvewt = 3;
    $ewtcode = 4;
    $db = 5;
    $cr = 6;
    $postdate = 7;
    $checkno = 8;
    $rem = 9;
    $project = 10;
    $subprojectname = 11;
    $client = 12;
    $clientname = 13;
    $ref = 14;
    $dept = 15;
    $invoiceno = 16;
    $invoicedate = 17;
    $void = 18;
    $acnoname = 19;
    $b = 20;


    $column = ['action', 'isewt', 'isvat', 'isvewt', 'ewtcode', 'db', 'cr', 'postdate', 'checkno', 'rem', 'project', 'subprojectname', 'client', 'clientname', 'ref', 'dept',  'invoiceno', 'invoicedate', 'void', 'acnoname'];

    $tab = [
      $this->gridname => [
        'gridcolumns' => $column,
        'headgridbtns' => ['viewacctginfo', 'viewref', 'viewdiagram']
      ],

    ];


    $stockbuttons = ['save', 'delete'];
    if ($this->companysetup->getiseditsortline($config['params'])) {
      array_push($stockbuttons, 'sortline');
    }
    array_push($stockbuttons, 'detailinfo');

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // 11 - ref 
    $obj[0]['accounting']['columns'][$ref]['lookupclass'] = 'refcv';
    //10 - client      
    $obj[0]['accounting']['columns'][$client]['lookupclass'] = 'vendordetail';
    $obj[0]['accounting']['columns'][$action]['checkfield'] = 'void';

    $obj[0]['accounting']['columns'][$postdate]['style'] = '150px;whiteSpace: normal; min-width:150px;max-width:150px;';
    $obj[0]['accounting']['columns'][$checkno]['style'] = '150px;whiteSpace: normal; min-width:150px;max-width:150px;text-align:left;';
    $obj[0]['accounting']['columns'][$checkno]['align'] = 'text-left';
    $obj[0]['accounting']['columns'][$checkno]['type'] = 'label';
    $obj[0]['accounting']['columns'][$client]['style'] = '200px;whiteSpace: normal; min-width:200px;max-width:200px;';
    $obj[0]['accounting']['columns'][$ref]['style'] = '150px;whiteSpace: normal; min-width:150px;max-width:150px;';
    $obj[0]['accounting']['columns'][$rem]['style'] = '200px;whiteSpace: normal; min-width:200px;max-width:200px;';
    $obj[0]['accounting']['columns'][$action]['style'] = '150px;whiteSpace: normal; min-width:150px;max-width:150px;';
    $obj[0]['accounting']['columns'][$subprojectname]['type'] = 'coldel';
    $obj[0]['accounting']['columns'][$invoiceno]['type'] = 'coldel';
    $obj[0]['accounting']['columns'][$invoicedate]['type'] = 'coldel';
    $obj[0]['accounting']['columns'][$isewt]['type'] = 'coldel';
    $obj[0]['accounting']['columns'][$isvat]['type'] = 'coldel';
    $obj[0]['accounting']['columns'][$isvewt]['type'] = 'coldel';
    $obj[0]['accounting']['columns'][$ewtcode]['type'] = 'coldel';
    $obj[0]['accounting']['columns'][$clientname]['type'] = 'coldel';
    $obj[0]['accounting']['columns'][$dept]['type'] = 'coldel';


    $obj[0]['accounting']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $companyid = $config['params']['companyid'];
    $tbuttons = ['unpaid', 'additem', 'saveitem', 'deleteallitem', 'generateewt'];

    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[1]['label'] = "ADD ACCOUNT";
    $obj[1]['action'] = "adddetail";
    $obj[2]['label'] = "SAVE ACCOUNT";
    $obj[3]['label'] = "DELETE ACCOUNT";

    return $obj;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];
    $noeditdate = $this->othersClass->checkAccess($config['params']['user'], 4853);
    $systype = $this->companysetup->getsystemtype($config['params']);
    //$doclength = $this->companysetup->getdocumentlength($config['params']);
    $fields = ['docno', 'client', 'clientname','acctname'];

    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'client.label', 'Payee');
    data_set($col1, 'client.lookupclass', 'allclienthead');
    data_set($col1, 'docno.label', 'Transaction#');
    data_set($col1, 'acctname.label', 'Other Name');
    //data_set($col1, 'docno.maxlength', $doclength);

    $fields = ['dateid', 'dprojectname', 'dewt'];

    $col2 = $this->fieldClass->create($fields);

    $fields = [['yourref', 'ourref'], ['dacnoname', 'amount'], ['checkno', 'checkdate']];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'yourref.type', 'lookup');
    data_set($col3, 'yourref.lookupclass', 'cvpendingloan');
    data_set($col3, 'yourref.action', 'pendingloan');
    data_set($col3, 'yourref.addedparams', ['client']);
    data_set($col3, 'yourref.label', 'Application#');
    data_set($col3, 'yourref.class', 'sbccsreadonly');

    data_set($col3, 'dacnoname.label', 'Bank');
    data_set($col3, 'dacnoname.lookupclass', 'CB');
    data_set($col3, 'dacnoname.required', true);
    data_set($col3, 'dacnoname.error', false);
    data_set($col3, 'checkno.required', true);
    data_set($col3, 'checkno.error', false);

    $fields = ['rem'];

    if ($this->companysetup->getistodo($config['params'])) {
      array_push($fields, 'donetodo');
    }

    $col4 = $this->fieldClass->create($fields);

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
    $data[0]['client2'] = '';
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
    $data[0]['vattype'] = 'NON-VATABLE';
    $data[0]['contra'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', [$this->defaultContra]);
    $data[0]['acnoname'] = $this->coreFunctions->getfieldvalue('coa', 'acnoname', 'acno=?', [$data[0]['contra']]);
    $data[0]['isencashment'] = '0';
    $data[0]['isonlineencashment'] = '0';
    $data[0]['radioencash'] = 0;
    $data[0]['paymode'] = '';
    $data[0]['hacno'] = '';
    $data[0]['hacnoname'] = '';
    $data[0]['costcode'] = '';
    $data[0]['costcodename'] = '';
    $data[0]['empid'] = 0;
    $data[0]['empname'] = '';
    $data[0]['costcodeid'] = 0;
    $data[0]['deptid'] = 0;
    $data[0]['dept'] = '';
    $data[0]['deptname'] = '';

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
    $data[0]['checkno'] = '';
    $data[0]['checkdate'] = $this->othersClass->getCurrentDate();
    $data[0]['amount'] = 0;
    $data[0]['dptrno'] = 0;
    $data[0]['acctname'] = '';

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
    $addedfield = '';

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
    client.groupid,ifnull(project.code,'') as projectcode,
    head.brtrno,head.isencashment,
    head.isonlineencashment, 
    case when paymode = 'D' then 'Debit Payment'
    when paymode = 'O' then 'Online Payment'
    when paymode = 'C' then 'Check Payment' end as paymode,head.hacno,head.hacnoname,
    head.costcodeid, ifnull(ccm.code,'')  as costcode, ifnull(ccm.name,'')  as costcodename,
    emp.clientname as empname, head.empid,
    head.deptid,ifnull(dept.client,'')  as dept,ifnull(dept.clientname,'')  as deptname,ifnull(t.docno,'') as pldocno,
   head.checkno,head.checkdate,format(head.amount,2) as amount,num.dptrno,head.acctname
    ";

    $qry = $qryselect . " from $table as head
    left join $tablenum as num on num.trno = head.trno
    left join client on head.client = client.client
    left join client as emp on head.empid = emp.clientid
    left join coa on coa.acno=head.contra
    left join projectmasterfile as project on project.line=head.projectid 
    left join costcode_masterfile as ccm on ccm.line = head.costcodeid
    left join client as dept on dept.clientid=head.deptid
    left join transnum as t on t.cvtrno = num.trno
    where head.trno = ? and num.doc=? and num.center = ? 
    union all " . $qryselect . " from $htable as head
    left join $tablenum as num on num.trno = head.trno
    left join client on head.clientid = client.clientid
    left join client as emp on head.empid = emp.clientid
    left join coa on coa.acno=head.contra 
    left join projectmasterfile as project on project.line=head.projectid      
    left join costcode_masterfile as ccm on ccm.line = head.costcodeid
    left join client as dept on dept.clientid=head.deptid
    left join transnum as t on t.cvtrno = num.trno
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
      $pcv = $this->coreFunctions->opentable("select docno from hsvhead where cvtrno =?", [$trno]);

      $hideobj = [];
      if ($this->companysetup->getistodo($config['params'])) {
        $btndonetodo = $this->othersClass->checkdonetodo($config, $tablenum);
        $hideobj = ['donetodo' => !$btndonetodo];
      }
      return  ['head' => $head, 'griddata' => ['accounting' => $detail, 'reference' => $pcv], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg, 'hideobj' => $hideobj];
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

    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    if ($isupdate) {
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
      if($head['dptrno']!=0){
        $entry = $this->coreFunctions->datareader("select trno  as value from ".$this->detail." where trno=?",[$head['trno']]);
        $this->coreFunctions->sbcupdate('cntnum', ['dptrno' => $head['dptrno']], ['trno' => $head['trno']]);
        if(empty($entry)){
          $this->distributecvloan($config,$head['dptrno']);
        }                
      }
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head, $data);
      if($head['dptrno']!=0){
        $entry = $this->coreFunctions->datareader("select trno as value from ".$this->detail." where trno=?",[$head['trno']]);
        $this->coreFunctions->sbcupdate('cntnum', ['dptrno' => $head['dptrno']], ['trno' => $head['trno']]);
        if(empty($entry)){
          $this->distributecvloan($config,$head['dptrno']);
        }        
        
      }
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['client'] . ' - ' . $head['clientname']);
    }
    $this->autoentry($config);
  } // end function

  private function distributecvloan($config,$loantrno){
    $trno = $config['params']['trno'];
    $data2 = $this->othersClass->distributeloanentry($config, $loantrno, $trno);
    $this->logger->sbcwritelog($trno, $config, 'AUTO ENTRY', 'AUTO LOAN ENTRY', $this->tablelogs);
    foreach ($data2 as $key2 => $value) {
      $config['params']['data']['acno'] = $this->coreFunctions->getfieldvalue("coa", "acno", "acnoid = ?", [$data2[$key2]['acnoid']]);
      $config['params']['data']['acnoname'] = $this->coreFunctions->getfieldvalue("coa", "acnoname", "acnoid = ?", [$data2[$key2]['acnoid']]);
      $config['params']['data']['db'] = $data2[$key2]['db'];
      $config['params']['data']['cr'] = $data2[$key2]['cr'];
      $config['params']['data']['fdb'] = 0;
      $config['params']['data']['fcr'] = 0;
      $config['params']['data']['postdate'] = $data2[$key2]['postdate'];
      $config['params']['data']['project'] = 0;
      $config['params']['data']['client'] = $data2[$key2]['client'];
      $config['params']['data']['refx'] = 0;
      $config['params']['data']['linex'] = 0;
      $config['params']['data']['ref'] =  $data2[$key2]['ref'];
      $config['params']['data']['rem'] = $data2[$key2]['rem'];

      $return =$this->additem('insert', $config, true);
      if ($return['status']) {
        $this->coreFunctions->sbcupdate('transnum', ['cvtrno' => $trno], ['trno' => $loantrno]);
        //$this->coreFunctions->sbcupdate('cntnum', ['dptrno' => $loantrno], ['trno' => $trno]);
      }
    }
  }

  public function autoentry($config)
  {
    $trno = $config['params']['trno'];
    $rows = [];

    $contra = $this->coreFunctions->getfieldvalue($this->head, 'contra', 'trno=?', [$trno]);
    $dateid = $this->coreFunctions->getfieldvalue($this->head, 'checkdate', 'trno=?', [$trno]);
    $project = $this->coreFunctions->getfieldvalue($this->head, 'project', 'trno=?', [$trno]);
    $checkno = $this->coreFunctions->getfieldvalue($this->head, 'checkno', 'trno=?', [$trno]);
    $acnoid = $this->coreFunctions->getfieldvalue("coa", 'acnoid', 'acno=?', [$contra]);
    $client = $this->coreFunctions->getfieldvalue($this->head, 'client', 'trno=?', [$trno]);

    $qry = "delete from " . $this->detail . " where trno=? and acnoid=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $acnoid]);

    $contraname = $this->coreFunctions->getfieldvalue('coa', 'acnoname', 'acno=?', [$contra]);
    //$dbval = $this->coreFunctions->getfieldvalue($this->detail, '(case when sum(db-cr) < 0 then sum(db-cr) else (sum(db-cr) * -1) end)', 'trno=?', [$trno]);
    //$dbval = $this->coreFunctions->getfieldvalue($this->detail, 'sum(db-cr)', 'trno=?', [$trno]);
    $dbval = $this->coreFunctions->getfieldvalue($this->head, 'amount', 'trno=?', [$trno]);
    $this->coreFunctions->LogConsole($dbval."discrepancy");
    if ($dbval != 0) {
      $config['params']['data']['acno'] = $contra;
      $config['params']['data']['acnoname'] = $contraname;

      if ($dbval > 0) {
        $config['params']['data']['db'] = 0;
        $config['params']['data']['cr'] =  $dbval;
      } else {
        $config['params']['data']['db'] =  $dbval * -1;
        $config['params']['data']['cr'] = 0;
      }
      $config['params']['data']['fdb'] =  0;
      $config['params']['data']['fcr'] = 0;

      $config['params']['data']['postdate'] = $dateid;
      $config['params']['data']['project'] = $project;
      $config['params']['data']['checkno'] = $checkno;
      $config['params']['data']['rem'] = '';
      $config['params']['data']['client'] = $client;
      $config['params']['data']['ref'] = '';
      $config['params']['data']['refx'] = 0;
      $config['params']['data']['linex'] = 0;


      $return = $this->additem('insert', $config);
      if ($return['status']) {
        $rows = $return['row'][0];
      }
    }

    return ['row' => $rows, 'status' => true, 'msg' => 'Account was successfully added.'];
  }

  public function deletetrans($config)
  {
    $companyid = $config['params']['companyid'];
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];
    $table = $config['docmodule']->tablenum;
    $docno = $this->coreFunctions->datareader("select docno as value from " . $table . ' where trno=?', [$trno]);
    $qry = "select trno as value from " . $this->tablenum . " where doc=? and trno<? order by trno desc limit 1 ";
    $trno2 = $this->coreFunctions->datareader($qry, [$doc, $trno]);
    $this->deleteallitem($config);
    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry("delete from cntnuminfo where trno=?", "delete", [$trno]);
    $this->coreFunctions->execqry("delete from loansum where trno=?", "delete", [$trno]);

    $this->coreFunctions->sbcupdate("transnum", ['cvtrno' => 0], ['cvtrno' => $trno]);
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
    d.projectid,ifnull(proj.name,'') as projectname,d.cur,d.forex,
    d.subproject,d.stageid,d.pcvtrno,proj.code as project,
    case d.isewt when 0 then 'false' else 'true' end as isewt,
    case d.isvat when 0 then 'false' else 'true' end as isvat,
    case d.isvewt when 0 then 'false' else 'true' end as isvewt,
    d.ewtcode,d.ewtrate,d.damt,case d.void when 0 then 'false' else 'true' end as void,
    '' as bgcolor,case d.void when 1 then 'bg-red-2' else '' end as  errcolor,
    (select group_concat(distinct h.invoiceno) 
    from glhead as h where h.trno=d.refx) as invoiceno,
    (select group_concat(distinct left(h.invoicedate,10))
    from glhead as h where h.trno=d.refx) as invoicedate,
    subproj.subproject as subprojectname,d.branch,d.deptid,ifnull(dept.clientname,'') as dept,
         
    d.phaseid, ph.code as phasename,

    d.modelid, hm.model as housemodel, 

    d.blklotid, bl.blk, bl.lot,
    
    am.line as amenity,
    am.description as amenityname,
    subam.line as subamenity,
    subam.description as subamenityname 
    ";

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
    where d.trno=?
    union all
    select " . $sqlselect . " 
    from voiddetail as d
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
    from hvoiddetail as d
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
    where d.trno=?  order by  sortline,line";

    $detail = $this->coreFunctions->opentable($qry, [$trno, $trno, $trno, $trno]);
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
      case 'voidtrans':
        return $this->voidtrans($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }

  private function voidtrans($config)
  {
    $doc = $config['params']['doc'];
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $path = '';

    $docno = $this->coreFunctions->getfieldvalue($this->tablenum, "docno", "trno=?", [$trno]);
    //check if posted
    $isposted = $this->othersClass->isposted2($trno, $this->tablenum);

    if ($isposted) {
      return ['status' => false, 'msg' => 'Already Posted.'];
    } else {
      $detail = $this->opendetail($trno, $config);
      $thead = $this->head;
      $tdetail = $this->detail;

      if (!empty($detail)) {
        foreach ($detail as $k => $v) {
          $qry = "insert into voiddetail (postdate,trno,line,acnoid,client,db,cr,fdb,fcr,refx,linex,encodeddate,encodedby,editdate,
          editby,ref,checkno,rem,clearday,pdcline,projectid,isewt,isvat,ewtcode,ewtrate,forex,isvewt,subproject,stageid,void,branch,deptid)
          select d.postdate,d.trno,d.line,d.acnoid,
          ifNull(client.client,''),d.db,d.cr,d.fdb,d.fcr,d.refx,d.linex,
          d.encodeddate,d.encodedby,d.editdate,d.editby,d.ref,d.checkno,d.rem,d.clearday,d.pdcline,d.projectid,
          d.isewt,d.isvat,d.ewtcode,d.ewtrate,d.forex,d.isvewt,d.subproject,d.stageid,1,d.branch,d.deptid
          from " . $thead . " as h
          left join " . $tdetail . " as d on d.trno=h.trno
          left join client on client.client=d.client
          where  d.trno=? and d.line =?
          ";
          $result = $this->coreFunctions->execqry($qry, 'insert', [$detail[$k]->trno, $detail[$k]->line]);
          if ($result) {
            $this->coreFunctions->execqry("delete from " . $tdetail . " where trno =? and line =?", 'delete', [$detail[$k]->trno, $detail[$k]->line]);
            if ($detail[$k]->refx != 0) {
              if (!$this->sqlquery->setupdatebal($detail[$k]->refx, $detail[$k]->linex, $detail[$k]->acno, $config)) {
                $this->coreFunctions->sbcupdate($tdetail, ['db' => 0, 'cr' => 0, 'fdb' => 0, 'fcr' => 0], ['trno' => $trno, 'line' => $detail[$k]->line]);
                $this->sqlquery->setupdatebal($detail[$k]->refx, $detail[$k]->linex, $detail[$k]->acno, $config);
              }
            }

            $this->logger->sbcwritelog($trno, $config, 'VOID', 'Document #: ' . $docno);
          }
        }

        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $this->coreFunctions->execqry("update " . $this->head . " set voiddate = '" . $current_timestamp . "',voidby ='" . $user . "' where trno =? ", 'update', [$trno]);
      } else {
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $this->coreFunctions->execqry("update " . $this->head . " set voiddate = '" . $current_timestamp . "',voidby ='" . $user . "' where trno =? ", 'update', [$trno]);
      }

      $config['params']['trno'] =  $trno;
      $this->coreFunctions->execqry("update transnum set cvtrno = 0 where cvtrno =? ", 'update', [$trno]);
      $this->loadheaddata($config);
      return ['status' => true, 'msg' => 'Void successfully', 'reloadhead' => true, 'trno' => $trno, 'moduletype' => 'module'];
    }
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
    if (!$isupdate) {
      $data[0]->errcolor = 'bg-red-2';
      return ['row' => $data, 'status' => true, 'msg' => 'Payment amount is greater than setup amount.'];
    } else {
      return ['row' => $data, 'status' => true, 'msg' => $isupdate['msg']];
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
      $items = $this->additem('insert', $config);
    }

    if ($items['status'] == false) {
      return ['status' => $items['status'], 'msg' => $items['msg']];
    }

    $data = $this->opendetail($config['params']['trno'], $config);
    return ['accounting' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
  } //end function


  // insert and update detail
  public function additem($action, $config, $setlog = false)
  {
    $acno = $config['params']['data']['acno'];
    $acnoname = $config['params']['data']['acnoname'];
    $trno = $config['params']['trno'];
    $db = $config['params']['data']['db'];
    $cr = $config['params']['data']['cr'];
    $fdb = $config['params']['data']['fdb'];
    $fcr = $config['params']['data']['fcr'];
    $postdate = $config['params']['data']['postdate'];
    $rem = $config['params']['data']['rem'];

    $client = $config['params']['data']['client'];
    $companyid = $config['params']['companyid'];
    $systype = $this->companysetup->getsystemtype($config['params']);

    if (isset($config['params']['action'])) {
      switch ($config['params']['action']) {
        case 'getpcvselected':
          $project = $config['params']['data']['project'];
          break;
      }
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
      'void' => $void
    ];

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
      $qry = "select trno as value from (select trno from ladetail where cr<>0 and acnoid = " . $acnoid . " and trno <> " . $trno . " and checkno ='" . $checkno . "' and (upper(checkno) not like '%ONLINE%' and upper(checkno) not like '%MANAGERS%') union all
      select trno from gldetail where cr<>0 and acnoid = " . $acnoid . " and trno <> " . $trno . " and checkno ='" . $checkno . "' and (upper(checkno) not like '%ONLINE%' and upper(checkno) not like '%MANAGERS%')) as a limit 1";
      $isexist = $this->coreFunctions->datareader($qry, [], '', true);

      if ($isexist != 0) {
        if (!$this->companysetup->getisautosaveacctgstock($config['params'])) {
          if ($isencashment == 0 && $isonlineencashment == 0) {
            $msg = 'Check number already exist.';
            return ['status' => false, 'msg' => $msg];
          }
        }
      }
    }

    if ($cbalias == 'CB' && $checkno == '' && $db == 0) {
      return ['status' => false, 'msg' => 'Please enter check# for Bank Accounts.'];
    }

    if ($checkno != '') {
      $checksetup = $this->coreFunctions->getfieldvalue("checksetup", "line", "acnoid=? and (current =0 or current<>end)", [$acnoid]);
      if ($checksetup != 0) {
        $ischecksetup = true;
      }
    }

    if($db !=0 && $cr !=0){
      return ['status' => false, 'msg' => "Debit and Credit amount should not be same in one entry."];
    }

    if ($action == 'insert') {
      $data['encodedby'] = $config['params']['user'];
      $data['encodeddate'] = $current_timestamp;
      $data['sortline'] =  $data['line'];
      if ($this->coreFunctions->sbcinsert($this->detail, $data) == 1) {
        $msg = 'Account was successfully added.';
        $this->logger->sbcwritelog($trno, $config, 'ACCTG', 'ADD - Line:' . $line . ' Code:' . $acno . ' DB:' . $db . ' CR:' . $cr . ' Client:' . $client . ' Date:' . $postdate, $setlog ? $this->tablelogs : '');
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
    $companyid = $config['params']['companyid'];
    $trno = $config['params']['trno'];
    $data = $this->coreFunctions->opentable('select coa.acno,t.refx,t.linex from ' . $this->detail . ' as t 
      left join coa on coa.acnoid=t.acnoid where t.trno=? and t.refx<>0', [$trno]);
    $pcv = $this->coreFunctions->opentable('select distinct pcvtrno from ' . $this->detail . ' as t  
      where t.trno=? and t.pcvtrno<>0', [$trno]);

    //check series setup
    $detail = $this->opendetail($trno, $config);
    $search = "online";
    foreach ($detail as $key => $value) {
      if (preg_match("/{$search}/i", $detail[$key]->checkno)) {
      } else {
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

    $this->coreFunctions->execqry("update transnum set cvtrno = 0 where cvtrno=" . $trno, "update");
    $this->logger->sbcwritelog($trno, $config, 'ACCTG', 'DELETED ALL ACCTG ENTRIES');
    return ['status' => true, 'msg' => 'Successfully deleted.', 'accounting' => []];
  }

  public function getloanapplication($config)
  {
    $qryselect = "select   head.trno, 
         head.docno,left(head.dateid,10) as dateid,
         ifnull(r.reqtype,'') as categoryname,  format(head.monthly,2) as monthly, 
         head.interest, 'P' as cur,1 as forex,
         head.planid, info.pf,
         head.client,head.clientname,
         format(info.amount,2) as amount,  head.terms";

    $qryselect = $qryselect . " from heahead as head
        left join heainfo as info on head.trno = info.trno
        left join reqcategory as r on r.line=head.planid
        where head.catrno = 0  and head.trno =?";
    return $qryselect;
  }



  public function deleteitem($config)
  {
    $companyid = $config['params']['companyid'];
    $config['params']['trno'] = $config['params']['row']['trno'];
    $config['params']['line'] = $config['params']['row']['line'];
    $data = $this->opendetailline($config);

    $trno = $config['params']['trno'];
    $line = $config['params']['line'];

    $pcvtrno = $config['params']['row']['pcvtrno'];

    //check series setup    
    $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "acno=?", [$data[0]->acno]);
    $alias = $this->coreFunctions->getfieldvalue("coa", "left(alias,2)", "acnoid=?", [$acnoid]);
    $search = 'online';
    if (preg_match("/{$search}/i", $data[0]->checkno)) {
    } else {
      if ($alias == 'CB') {
        $exist = $this->coreFunctions->getfieldvalue("checksetup", "line", "acnoid=?", [$acnoid]);
        if ($exist != 0) {
          $current = $this->coreFunctions->getfieldvalue("checksetup", "current", "acnoid=? and " . $data[0]->checkno . " between `start` and `end`", [$acnoid]);
          if ($current != "") {
            if ($current == $data[0]->checkno) {
              $this->coreFunctions->execqry("update checksetup set current = " . $data[0]->checkno . "-1 where acnoid =" . $acnoid . " and " . $data[0]->checkno . " between `start` and `end`");
            } else {
              return ['status' => false, 'msg' => 'Not allowed to delete' . $data[0]->acno . ', last check number used is ' . $current . ', you may VOID the entry to cancel it.'];
            }
          }
        }
      }
    }

    $update = "update hsvhead set cvtrno = 0 where trno =?";
    $this->coreFunctions->execqry($update, 'update', [$pcvtrno]);

    $qry = "delete from " . $this->detail . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);

    if ($data[0]->refx != 0) {
      $this->sqlquery->setupdatebal($data[0]->refx, $data[0]->linex, $data[0]->acno, $config);
    }

    $detail = $this->opendetail($trno, $config);
    if (empty($detail)) {
      $this->coreFunctions->execqry("update transnum set cvtrno = 0 where cvtrno=" . $trno, "update");
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
    $companyid = $config['params']['companyid'];
    $systype = $this->companysetup->getsystemtype($config['params']);
    $rows = [];
    $alias = '';
    $int = 0;
    $othrs =0;
    $data = $config['params']['rows'];
    foreach ($data as $key => $value) {
      $alias = $this->coreFunctions->getfieldvalue("coa","alias","acnoid = ?",[$data[$key]['acnoid']]);

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

      if($alias == 'AR2'){//interest
        $int = $int + $data[$key]['bal'];
      }

      if($alias == 'AR3'){//pfnf
        $othrs = $othrs + $data[$key]['bal'];
      }

      $config['params']['data']['postdate'] = $data[$key]['dateid'];
      $config['params']['data']['project'] = $data[$key]['projectid'];
      $config['params']['data']['subproject'] = $data[$key]['subproject'];
      $config['params']['data']['stageid'] = $data[$key]['stageid'];
      $config['params']['data']['deptid'] = isset($data[$key]['deptid']) ? $data[$key]['deptid'] : 0;
      $config['params']['data']['client'] = $data[$key]['client'];
      $config['params']['data']['refx'] = $data[$key]['trno'];
      $config['params']['data']['linex'] = $data[$key]['line'];
      $config['params']['data']['ref'] = $data[$key]['docno'];
      $config['params']['data']['rem'] = $data[$key]['rem'];

      $return = $this->additem('insert', $config);
      if ($return['status']) {
        array_push($rows, $return['row'][0]);
      }
    } //end foreach

    //insert reversal for unearned
    $cvdate = $this->coreFunctions->getfieldvalue($this->head,"dateid","trno=?",[$trno]);
    $client = $this->coreFunctions->getfieldvalue($this->head,"client","trno=?",[$trno]);
    if($int!=0){
      $intacno = $this->coreFunctions->getfieldvalue("coa","acno","alias = 'UE1'");
      $intacnoname = $this->coreFunctions->getfieldvalue("coa","acno","alias = 'UE1'");

      $config['params']['data']['acno'] = $intacno;
      $config['params']['data']['acnoname'] = $intacnoname;
      $config['params']['data']['db'] = $int;
      $config['params']['data']['cr'] = 0;
      $config['params']['data']['fdb'] = 0;
      $config['params']['data']['fcr'] = 0;
      $config['params']['data']['postdate'] = $cvdate;
      $config['params']['data']['project'] = 0;
      $config['params']['data']['subproject'] = 0;
      $config['params']['data']['stageid'] = 0;
      $config['params']['data']['deptid'] = 0;
      $config['params']['data']['client'] = $client;
      $config['params']['data']['refx'] = 0;
      $config['params']['data']['linex'] = 0;
      $config['params']['data']['ref'] = '';
      $config['params']['data']['rem'] = '';

      $return = $this->additem('insert', $config);
      if ($return['status']) {
        array_push($rows, $return['row'][0]);
      }

      $intacno = $this->coreFunctions->getfieldvalue("coa","acno","alias = 'SA2'");
      $intacnoname = $this->coreFunctions->getfieldvalue("coa","acno","alias = 'SA2'");

      $config['params']['data']['acno'] = $intacno;
      $config['params']['data']['acnoname'] = $intacnoname;
      $config['params']['data']['db'] = 0;
      $config['params']['data']['cr'] = $int;
      $config['params']['data']['fdb'] = 0;
      $config['params']['data']['fcr'] = 0;
      $config['params']['data']['postdate'] = $cvdate;
      $config['params']['data']['project'] = 0;
      $config['params']['data']['subproject'] = 0;
      $config['params']['data']['stageid'] = 0;
      $config['params']['data']['deptid'] = 0;
      $config['params']['data']['client'] = $client;
      $config['params']['data']['refx'] = 0;
      $config['params']['data']['linex'] = 0;
      $config['params']['data']['ref'] = '';
      $config['params']['data']['rem'] = '';

      $return = $this->additem('insert', $config);
      if ($return['status']) {
        array_push($rows, $return['row'][0]);
      }
    }

    if($othrs!=0){
      $intacno = $this->coreFunctions->getfieldvalue("coa","acno","alias = 'UE2'");
      $intacnoname = $this->coreFunctions->getfieldvalue("coa","acno","alias = 'UE2'");

      $config['params']['data']['acno'] = $intacno;
      $config['params']['data']['acnoname'] = $intacnoname;
      $config['params']['data']['db'] = $othrs;
      $config['params']['data']['cr'] = 0;
      $config['params']['data']['fdb'] = 0;
      $config['params']['data']['fcr'] = 0;
      $config['params']['data']['postdate'] = $cvdate;
      $config['params']['data']['project'] = 0;
      $config['params']['data']['subproject'] = 0;
      $config['params']['data']['stageid'] = 0;
      $config['params']['data']['deptid'] = 0;
      $config['params']['data']['client'] = $client;
      $config['params']['data']['refx'] = 0;
      $config['params']['data']['linex'] = 0;
      $config['params']['data']['ref'] = '';
      $config['params']['data']['rem'] = '';

      $return = $this->additem('insert', $config);
      if ($return['status']) {
        array_push($rows, $return['row'][0]);
      }

      $intacno = $this->coreFunctions->getfieldvalue("coa","acno","alias = 'SA3'");
      $intacnoname = $this->coreFunctions->getfieldvalue("coa","acno","alias = 'SA3'");

      $config['params']['data']['acno'] = $intacno;
      $config['params']['data']['acnoname'] = $intacnoname;
      $config['params']['data']['db'] = 0;
      $config['params']['data']['cr'] = $othrs;
      $config['params']['data']['fdb'] = 0;
      $config['params']['data']['fcr'] = 0;
      $config['params']['data']['postdate'] = $cvdate;
      $config['params']['data']['project'] = 0;
      $config['params']['data']['subproject'] = 0;
      $config['params']['data']['stageid'] = 0;
      $config['params']['data']['deptid'] = 0;
      $config['params']['data']['client'] = $client;
      $config['params']['data']['refx'] = 0;
      $config['params']['data']['linex'] = 0;
      $config['params']['data']['ref'] = '';
      $config['params']['data']['rem'] = '';

      $return = $this->additem('insert', $config);
      if ($return['status']) {
        array_push($rows, $return['row'][0]);
      }
    }
    

    return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.', 'reloadhead' => true];
  } //end function

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
    detail.projectid as headprjid,coa.acno,coa.acnoname,detail.isvat,head.rem,
    detail.isewt,detail.ewtrate,detail.ewtcode,detail.isvewt,head.projectid,head.vattype,head.tax,head.trno,head.docno
    from hsvhead as head 
    left join hsvdetail as detail on detail.trno = head.trno
    left join coa as coa on coa.acnoid = detail.acnoid
    left join transnum on transnum.trno = head.trno 
    where head.cvtrno=0 and left(coa.alias,2)<>'PC' and head.trno in (" . $strtrno . ")
    group by detail.projectid,coa.acno,coa.acnoname,detail.isvat,head.rem,detail.isewt,detail.ewtrate,detail.ewtcode,detail.isvewt,
    head.projectid,head.vattype,head.tax,head.trno,head.docno order by head.vattype,head.projectid";

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
        $config['params']['data']['rem'] = $data[$key2]->rem;
        // $config['params']['data']['rem'] = '';
        $config['params']['data']['project'] = '';
        $config['params']['data']['projectid'] = $data[$key2]->projectid;
        $config['params']['data']['client'] = $client;
        $config['params']['data']['pcvtrno'] = $data[$key2]->trno;
        $config['params']['data']['ref'] = $data[$key2]->docno;
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
    $msg = 'Added accounts Successfully...';
    if ($insert_success) {

      $this->coreFunctions->execqry("update hsvhead set cvtrno=" . $trno . " where trno in (" . $strtrno . ")");
    } else {
      $msg = 'Failed to insert selected PCV.';
    }

    return ['row' => $rows, 'status' => true, 'msg' => $msg];
  } //end function

  public function getplsummaryqry($config)
  {
    return "select head.trno as pltrno,ap.trno,ap.line,ap.docno as ref,head.docno,left(ap.dateid,10) as dateid,
    ap.db,ap.cr,ap.bal,ap.fdb,ap.fcr,head.yourref,head.ourref,head.clientname,client.client,client.clientid,detail.projectid,'P' as cur,1 as forex,coa.acnoid,coa.acno,coa.acnoname,detail.rem
    from hpyhead as head
    left join apledger as ap on ap.py = head.trno
    left join gldetail as detail on detail.trno = ap.trno and detail.line = ap.line
    left join transnum on transnum.trno = head.trno
    left join client on client.client=head.client
    left join coa on coa.acnoid = ap.acnoid 
    where transnum.trno =? and ap.bal<>0
    group by head.trno,ap.trno,ap.line,ap.docno,head.docno,ap.dateid,ap.db,ap.cr,ap.fdb,ap.fcr,ap.bal,head.yourref,head.ourref,head.clientname,client.client,client.clientid,detail.projectid,coa.acnoid,coa.acno,coa.acnoname,detail.rem";
  }

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
      $msg = "Please setup account for EWT and Input VAT";
    } else {

      $this->coreFunctions->execqry("delete from ladetail where trno = " . $trno . " and acnoid =" . $ewtacno, "delete");
      $this->coreFunctions->execqry("delete from ladetail where trno = " . $trno . " and acnoid =" . $taxacno, "delete");

      $ewtcode = '';

      foreach ($data as $key => $value) {
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
            $ewtcode = $value['ewtcode'];
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

            $ewtcode = $value['ewtcode'];
          }


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
        $entry = ['line' => $line, 'acnoid' => $ewtacno, 'client' => $data[0]['client'], 'cr' => ($ewtvalue < 0 ? 0 : abs(round($ewtvalue, 2))), 'db' => ($ewtvalue < 0 ? abs(round($ewtvalue, 2)) : 0), 'postdate' => $data[0]['dateid'], 'fdb' => ($ewtvalue > 0 ? 0 : abs($ewtvalue)) * $forex, 'fcr' => ($ewtvalue > 0 ? abs($ewtvalue) : 0) * $forex, 'rem' => "Auto entry", 'cur' => $cur, 'forex' => $forex, 'projectid' => $project, 'ewtcode' => $ewtcode];

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
          $msg = "AUTOMATIC ACCOUNTING ENTRY SUCCESS";
          $status = true;
        } else {
          $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING ENTRY FAILED');
          $msg = "AUTOMATIC ACCOUNTING ENTRY FAILED";
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

    $companyid = $config['params']['companyid'];
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  }

  public function reportdata($config)
  {
    ini_set('memory_limit', '-1');
    $dataparams = $config['params']['dataparams'];
    $trno = $config['params']['dataid'];
    $this->logger->sbcviewreportlog($config);
    $isposted = $this->othersClass->isposted2($trno, $this->tablenum);
    if (!$isposted) {
      $received = $this->coreFunctions->getfieldvalue($this->head, 'clientname', 'trno=?', [$trno]);
    } else {
      $received = $this->coreFunctions->getfieldvalue($this->hhead, 'clientname', 'trno=?', [$trno]);
    }
    $dataparams['received'] = $received;
    if (isset($dataparams['approved'])) $this->othersClass->writeSignatories($config, 'approved', $dataparams['approved']);
    if (isset($dataparams['received'])) $this->othersClass->writeSignatories($config, 'received', $dataparams['received']);
    if (isset($dataparams['approved2'])) $this->othersClass->writeSignatories($config, 'approved2', $dataparams['approved2']);
    if (isset($dataparams['checked'])) $this->othersClass->writeSignatories($config, 'checked', $dataparams['checked']);
    if (isset($dataparams['payor'])) $this->othersClass->writeSignatories($config, 'payor', $dataparams['payor']);
    if (isset($dataparams['position'])) $this->othersClass->writeSignatories($config, 'position', $dataparams['position']);
    if (isset($dataparams['tin'])) $this->othersClass->writeSignatories($config, 'tin', $dataparams['tin']);

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
