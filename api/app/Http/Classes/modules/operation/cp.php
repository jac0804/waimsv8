<?php

namespace App\Http\Classes\modules\operation;

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

class cp
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'LIFE PLAN AGREEMENT';
  public $gridname = 'accounting';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $tablenum = 'cntnum';
  public $head = 'lahead';
  public $detail = 'ladetail';
  public $hdetail = 'gldetail';
  public $acctg = [];
  public $hhead = 'glhead';
  public $tablelogs = 'table_log';
  public $htablelogs = 'htable_log';
  public $tablelogs_del = 'del_table_log';
  private $fields = ['trno', 'docno', 'client', 'clientname', 'dateid', 'aftrno', 'due', 'agent', 'vattype', 'tax'];
  private $blnfields = [
    'isplanholder', 'ispassport', 'isdriverlisc', 'isprc', 'isseniorid', 'isotherid', 'isemployment', 'isbusiness', 'isinvestment', 'isothersource', 'isemployed', 'isselfemployed',
    'isofw', 'isretired', 'iswife', 'isnotemployed', 'lessten', 'tenthirty', 'thirtyfifty', 'fiftyhundred', 'hundredtwofifty', 'twofiftyfivehundred', 'fivehundredup', 'isbene', 'issameadd', 'issenior', 'isdp', 'ispf'
  ];
  private $except = ['trno', 'dateid', 'duedate'];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = false;
  private $reporter;
  private $helpClass;

  public $showfilterlabel = [
    ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
    ['val' => 'posted', 'label' => 'Approved', 'color' => 'orange'],
    ['val' => 'all', 'label' => 'All', 'color' => 'blue']
  ];

  public $labelposted = 'Approved';
  public $labellocked = 'For Review';


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
      'view' => 4060,
      'edit' => 4061,
      'new' => 4062,
      'save' => 4063,
      'delete' => 4064,
      'print' => 4065,
      'lock' => 4066,
      'unlock' => 4067,
      'post' => 4068,
      'unpost' => 4069,
      'additem' => 4070,
      'edititem' => 4071,
      'deleteitem' => 4072
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $action = 0;
    $liststatus = 1;
    $listdocument = 2;
    $listdate = 3;
    $listclientname = 5;
    $listplanholder = 4;
    $yourref = 6;
    $ourref = 7;
    $postdate = 8;

    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listplanholder',  'listclientname', 'yourref', 'ourref', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$listclientname]['style'] = 'width:350px;whiteSpace: normal;min-width:350px;';
    $cols[$listplanholder]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;';
    $cols[$yourref]['align'] = 'text-left';
    $cols[$yourref]['label'] = 'Payment Method';
    $cols[$listclientname]['label'] = 'Payor';
    $cols[$ourref]['align'] = 'text-left';
    $cols[$postdate]['label'] = 'Post Date';
    $cols[$liststatus]['name'] = 'statuscolor';
    return $cols;
  }

  public function paramsdatalisting($config)
  {
    $fields = [];
    $allownew = $this->othersClass->checkAccess($config['params']['user'], 4062);
    if ($allownew == '1') {
      array_push($fields, 'pickpo');
    }

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'pickpo.label', 'APPROVED APPLICATION FORM');
    data_set($col1, 'pickpo.action', 'pendinglifeplan');
    data_set($col1, 'pickpo.lookupclass', 'pendinglifeplanshortcut');
    data_set($col1, 'pickpo.confirmlabel', 'Proceed to Create Agreement?');

    return ['status' => true, 'data' => [], 'txtfield' => ['col1' => $col1]];
  }

  public function loaddoclisting($config)
  {
    $companyid = $config['params']['companyid'];

    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));
    $itemfilter = $config['params']['itemfilter'];
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $searchfilter = $config['params']['search'];
    $limit = '';
    $condition = '';
    $agentfilter = '';
    $allowall = $this->othersClass->checkAccess($config['params']['user'], 4077);
    $adminid =  $config['params']['adminid'];

    switch ($itemfilter) {
      case 'draft':
        $condition = ' and num.postdate is null and head.lockdate is null';
        break;
      case 'locked':
        $condition .= ' and num.postdate is null and head.lockdate is not null ';
        break;
      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
    }

    $dateid = "left(head.dateid,10) as dateid";
    if ($searchfilter == "") $limit = 'limit 150';
    $orderby =  "order by  dateid desc, docno desc";

    if (isset($searchfilter)) {
      $searchfield = ['head.docno', 'head.clientname', 'head.yourref', 'num.postedby', 'i.clientname', 'app.yourref', 'head.createby', 'head.editby', 'head.viewby'];
      if ($searchfilter != "") {
        $condition .= $this->othersClass->multisearch($searchfield, $searchfilter);
      }
      $limit = "";
    }

    if ($allowall == '0') {
      if ($adminid != 0) {
        $isleader = $this->coreFunctions->getfieldvalue("client", "isleader", "clientid=?", [$adminid]);
        if (floatval($isleader) == 1) {
          $agentfilter = " and (lead.clientid = " . $adminid . " or  agent.clientid =  " . $adminid . ") ";
        } else {
          $agentfilter = " and agent.clientid = " . $adminid . " ";
        }
      }
    }

    $qry = "select head.trno,head.docno,head.clientname,$dateid,case ifnull(head.lockdate,'') when '' then 'DRAFT' else 'FOR REVIEW' end as status,
    head.createby,head.editby,head.viewby,num.postedby, date(num.postdate) as postdate,
      app.yourref, head.ourref,i.clientname as planholder,case ifnull(head.lockdate,'') when '' then 'red' else 'green' end as statuscolor
     from " . $this->head . " as head left join " . $this->tablenum . " as num 
     on num.trno=head.trno left join heahead as app on app.trno = head.aftrno left join heainfo as i on i.trno = app.trno
      left join client as agent on agent.client = head.agent left join client as lead on lead.clientid = agent.parent 
      where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $agentfilter . " 
     union all
     select head.trno,head.docno,head.clientname,$dateid,'APPROVED' as status,
     head.createby,head.editby,head.viewby, num.postedby, date(num.postdate) as postdate,
     app.yourref, head.ourref,i.clientname as planholder,'blue' as statuscolor
     from " . $this->hhead . " as head left join " . $this->tablenum . " as num 
     on num.trno=head.trno left join heahead as app on app.trno = head.aftrno left join heainfo as i on i.trno = app.trno 
     left join client as agent on agent.clientid = head.agentid left join client as lead on lead.clientid = agent.parent 
     where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $agentfilter . " 
    $orderby $limit";

    $data = $this->coreFunctions->opentable($qry, [$doc, $center, $date1, $date2, $doc, $center, $date1, $date2]);
    return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
  }

  public function createHeadbutton($config)
  {
    $btns = array(
      'load',
      'save',
      'delete',
      'cancel',
      'print',
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

    $buttons['save']['label'] = 'APPROVED';
    $buttons['delete']['label'] = 'DISAPPROVED';
    $buttons['print']['label'] = 'GENERATE LA';

    $step1 = $this->helpClass->getFields(['btnnew', 'customer', 'dateid', 'yourref', 'cur', 'csrem', 'btnsave']);
    $step2 = $this->helpClass->getFields(['btnedit', 'customer', 'dateid', 'yourref', 'cur', 'csrem', 'btnsave']);
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
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => $config['params']['doc'], 'title' => strtoupper($this->modulename) . '_MANUAL', 'action' => 'viewpdf', 'access' => 'view', 'type' => 'viewmanual']];
    }

    $allowCertificateForm = $this->othersClass->checkAccess($config['params']['user'], 4172);
    if ($allowCertificateForm) {
      $buttons['others']['items']['certificate'] = ['label' => 'Create Certificate of Full Payment', 'todo' => ['lookupclass' => 'generatecert', 'action' => 'generatecert', 'access' => 'view', 'type' => 'print']];
    }

    return $buttons;
  } // createHeadbutton


  public function createtab2($access, $config)
  {
    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrycntnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);

    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];

    return $return;
  }


  public function createTab($access, $config)
  {
    $companyid = $config['params']['companyid'];


    $fields = ['isplanholder', 'client', 'terms', 'yourref', 'otherterms'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.lookupclass', 'payors');
    data_set($col1, 'client.label', 'Payors');
    data_set($col1, 'clientname.label', 'Payor\'s Name');
    data_set($col1, 'client.class', '');
    data_set($col1, 'client.required', false);
    data_set($col1, 'client.class', 'csyourref sbccsreadonly');
    data_set($col1, 'terms.type', 'lookup');
    data_set($col1, 'terms.action', 'lookupterms');
    data_set($col1, 'terms.lookupclass', 'ledgerterms');
    data_set($col1, 'terms.label', 'Payment Terms');
    data_set($col1, 'donetodo.style', 'font-size:100%;');

    data_set($col1, 'donetodo.action', 'customformdialog');
    data_set($col1, 'donetodo.lookupclass', 'viewdistribution');
    data_set($col1, 'donetodo.label', 'view viewdistribution');
    data_set($col1, 'donetodo.icon', 'check');


    data_set($col1, 'yourref.label', 'Method');
    data_set($col1, 'yourref.class', 'csyourref sbccsreadonly');

    data_set($col1, 'client.type', 'input');
    data_set($col1, 'terms.type', 'input');
    data_set($col1, 'otherterms.class', 'csotherterms sbccsreadonly');

    $fields = ['lname', 'fname', 'mname', 'ext'];
    $col2 = $this->fieldClass->create($fields);

    data_set($col2, 'lname.required', false);
    data_set($col2, 'fname.required', false);
    data_set($col2, 'mname.required', false);
    data_set($col2, 'ext.label', 'Ext');
    data_set($col2, 'lname.class', 'cslname sbccsreadonly');
    data_set($col2, 'fname.class', 'csfname sbccsreadonly');
    data_set($col2, 'mname.class', 'csmname sbccsreadonly');
    data_set($col2, 'ext.class', 'csext sbccsreadonly');

    $fields = ['addressno', 'street', 'subdistown', 'province', 'city', 'brgy', 'country', 'zipcode'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'city.label', 'City');
    data_set($col3, 'addressno.class', 'csaddressno sbccsreadonly');
    data_set($col3, 'street.class', 'csstreet sbccsreadonly');
    data_set($col3, 'subdistown.class', 'cssubdistown sbccsreadonly');
    data_set($col3, 'city.class', 'cscity sbccsreadonly');
    data_set($col3, 'country.class', 'cscountry sbccsreadonly');
    data_set($col3, 'zipcode.class', 'cszipcode sbccsreadonly');
    data_set($col3, 'province.class', 'csprovince sbccsreadonly');
    data_set($col3, 'province.type', 'input');
    data_set($col3, 'brgy.addedparams', ['city']);
    data_set($col3, 'brgy.label', 'Barangay');
    data_set($col3, 'brgy.action', 'lookupprovcity');


    $fields = ['contactno', 'contactno2', 'email'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'contactno.class', 'cscontactno sbccsreadonly');
    data_set($col4, 'contactno2.class', 'cscontactno2 sbccsreadonly');
    data_set($col4, 'email.class', 'csemail sbccsreadonly');



    $tab = [
      'multiinput1' => ['inputcolumn' => ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4], 'label' => 'PAYOR INFORMATION'],

      'passengertab' => ['action' => 'operation', 'lookupclass' => 'beneficiaries', 'label' => 'BENEFICIARIES', 'checkchanges' => 'tableentry'],
    ];

    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    return $obj;
  }

  public function createtabbutton($config)
  {

    return [];
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];

    $fields = [
      'lblbranch', 'docno', 'afdocno', 'lname2', 'fname2', 'mname2', 'ext2', 'lblshipping', ['ispassport', 'isprc'], ['isdriverlisc', 'isotherid'], 'isseniorid', ['idno', 'expiration'], 'passbook',
      'lessten', 'tenthirty', 'thirtyfifty', 'fiftyhundred', 'hundredtwofifty', 'twofiftyfivehundred', 'fivehundredup'
    ];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'docno.label', 'Contract #');
    data_set($col1, 'lname2.class', 'cslname2 sbccsreadonly');
    data_set($col1, 'fname2.class', 'csfname2 sbccsreadonly');
    data_set($col1, 'mname2.class', 'csmname2 sbccsreadonly');
    data_set($col1, 'ext2.class', 'csext2 sbccsreadonly');

    data_set($col1, 'ispassport.class', 'csispassport sbccsreadonly');
    data_set($col1, 'isprc.class', 'csisprc sbccsreadonly');
    data_set($col1, 'isdriverlisc.class', 'csisdriverlisc sbccsreadonly');
    data_set($col1, 'isotherid.class', 'csisotherid sbccsreadonly');
    data_set($col1, 'idno.class', 'csidno sbccsreadonly');
    data_set($col1, 'expiration.class', 'csexpiration sbccsreadonly');
    data_set($col1, 'lessten.class', 'cslessten sbccsreadonly');
    data_set($col1, 'tenthirty.class', 'cstenthirty sbccsreadonly');
    data_set($col1, 'thirtyfifty.class', 'csthirtyfifty sbccsreadonly');
    data_set($col1, 'fiftyhundred.class', 'csfiftyhundred sbccsreadonly');
    data_set($col1, 'hundredtwofifty.class', 'cshundredtwofifty sbccsreadonly');
    data_set($col1, 'twofiftyfivehundred.class', 'cstwofiftyfivehundred sbccsreadonly');
    data_set($col1, 'fivehundredup.class', 'csfivehundredup sbccsreadonly');


    data_set($col1, 'nationality.type', 'input');
    data_set($col1, 'nationality.class', 'csnationality sbccsreadonly');
    data_set($col1, 'bclient.required', false);
    data_set($col1, 'afdocno.required', true);

    data_set($col1, 'lblshipping.label', 'Government I.D');
    data_set($col1, 'lblshipping.style', 'font-weight:bold; font-size:13px;');
    data_set($col1, 'lblbranch.label', 'Plan Holder Information');
    data_set($col1, 'lblbranch.style', 'font-weight:bold; font-size:13px;');
    data_set($col1, 'idno.label', 'Number/Type');
    data_set($col1, 'passbook.label', 'Monthly Income');
    data_set($col1, 'passbook.style', 'font-weight:bold; font-size:13px;');


    $fields = [
      'dateid', 'due', 'dvattype', 'plantype', 'amount', 'dagentname', 'gender', 'civilstatus', ['bday', 'nationality'], 'pob', ['lblbilling'], ['isemployment', 'isinvestment'], ['isbusiness', 'isothersource'], 'othersource', 'lblacquisition',
      ['isemployed', 'isselfemployed'], 'isofw', 'isretired', 'iswife', 'isnotemployed'
    ];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'lblbilling.label', 'Source of Income');
    data_set($col2, 'lblbilling.style', 'font-weight:bold; font-size:13px;');
    data_set($col2, 'lblacquisition.label', 'Occupation');
    data_set($col2, 'lblacquisition.style', 'font-weight:bold; font-size:13px;');
    data_set($col2, 'amount.class', 'sbccsreadonly');

    data_set($col2, 'dateid.class', 'csdateid');
    data_set($col2, 'plantype.class', 'csplantype sbccsreadonly');
    data_set($col2, 'plantype.type', 'input');
    data_set($col2, 'dagentname.type', 'input');
    data_set($col2, 'gender.type', 'input');
    data_set($col2, 'civilstatus.type', 'input');
    data_set($col2, 'civilstatus.class', 'cscivilstatus sbccsreadonly');
    data_set($col2, 'nationality.type', 'input');
    data_set($col2, 'nationality.class', 'csnationality sbccsreadonly');
    data_set($col2, 'bday.class', 'csbday sbccsreadonly');
    data_set($col2, 'pob.class', 'cspob sbccsreadonly');

    data_set($col2, 'isemployment.class', 'csisemployment sbccsreadonly');
    data_set($col2, 'isinvestment.class', 'csisinvestment sbccsreadonly');
    data_set($col2, 'isbusiness.class', 'csisbusiness sbccsreadonly');
    data_set($col2, 'isothersource.class', 'csisothersource sbccsreadonly');
    data_set($col2, 'othersource.class', 'csothersource sbccsreadonly');
    data_set($col2, 'othersource.class', 'csothersource sbccsreadonly');

    data_set($col2, 'isemployed.class', 'csisemployed sbccsreadonly');
    data_set($col2, 'isselfemployed.class', 'csisselfemployed sbccsreadonly');
    data_set($col2, 'isofw.class', 'csisofw sbccsreadonly');
    data_set($col2, 'isretired.class', 'csisretired sbccsreadonly');
    data_set($col2, 'iswife.class', 'csiswife sbccsreadonly');
    data_set($col2, 'isnotemployed.class', 'csisnotemployed sbccsreadonly');

    data_set($col2, 'dvattype.type', 'input');

    $fields = ['lblcostuom', 'raddressno', 'rstreet', 'rsubdistown', 'rprovince', 'rcity', 'rbrgy', 'rcountry', 'rzipcode', 'lbldepreciation', 'employer', 'otherplan', 'rem'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'lblcostuom.label', 'Address (Residence)');
    data_set($col3, 'lblcostuom.style', 'font-weight:bold; font-size:13px;');
    data_set($col3, 'lbldepreciation.label', '________________________________________________');
    data_set($col3, 'lbldepreciation.style', 'width:100%; font-weight:bold; font-size:13px;color:#cccccc');

    data_set($col3, 'raddressno.class', 'csraddressno sbccsreadonly');
    data_set($col3, 'rstreet.class', 'csrstreet sbccsreadonly');
    data_set($col3, 'rsubdistown.class', 'csrsubdistown sbccsreadonly');
    data_set($col3, 'rcity.class', 'csrcity sbccsreadonly');
    data_set($col3, 'rprovince.class', 'csrcity sbccsreadonly');
    data_set($col3, 'rprovince.type', 'input');
    data_set($col3, 'rcity.type', 'input');
    data_set($col3, 'rcountry.class', 'csrcountry sbccsreadonly');
    data_set($col3, 'rzipcode.class', 'csrzipcode sbccsreadonly');
    data_set($col3, 'employer.class', 'csemployer sbccsreadonly');
    data_set($col3, 'otherplan.class', 'csotherplan sbccsreadonly');
    data_set($col3, 'rem.class', 'csotherplan sbccsreadonly');
    data_set($col3, 'rbrgy.lookupclass', 'rbrgy');
    data_set($col3, 'rbrgy.addedparams', ['rcity']);

    $fields = ['lblattached', 'lblgrossprofit', 'issameadd', 'paddressno', 'pstreet', 'psubdistown', 'pprovince', 'pcity', 'pbrgy', 'pcountry', 'pzipcode', 'lbllocation', 'tin', 'sssgsis', 'appref', ['isdp', 'ispf'], ['dp', 'pf'],  'isbene', 'issenior'];

    $col4 = $this->fieldClass->create($fields);

    data_set($col4, 'lblgrossprofit.label', 'Permanent Address');
    data_set($col4, 'lblgrossprofit.style', 'font-weight:bold; font-size:13px;');
    data_set($col4, 'lblattached.style', 'font-family:Century Gothic; color:red; font-size:20px;font-weight:bold;');
    data_set($col4, 'lbllocation.label', '________________________________________________');
    data_set($col4, 'lbllocation.style', 'font-weight:bold; font-size:13px;color:#cccccc');
    data_set($col4, 'paddressno.class', 'cspaddressno sbccsreadonly');
    data_set($col4, 'pstreet.class', 'cspstreet sbccsreadonly');
    data_set($col4, 'psubdistown.class', 'cspsubdistown sbccsreadonly');
    data_set($col4, 'pcity.class', 'cspcity sbccsreadonly');
    data_set($col4, 'pprovince.class', 'cspprovince sbccsreadonly');
    data_set($col4, 'pprovince.type', 'input');
    data_set($col4, 'pcity.type', 'input');
    data_set($col4, 'pcountry.class', 'cspcountry sbccsreadonly');
    data_set($col4, 'pzipcode.class', 'cspzipcode sbccsreadonly');
    data_set($col4, 'tin.class', 'cstin sbccsreadonly');
    data_set($col4, 'sssgsis.class', 'cssssgsis sbccsreadonly');
    data_set($col4, 'issameadd.class', 'csissameadd sbccsreadonly');
    data_set($col4, 'isbene.class', 'csisbene sbccsreadonly');
    data_set($col4, 'appref.class', 'cssssgsis sbccsreadonly');
    data_set($col4, 'isdp.class', 'cssssgsis sbccsreadonly');
    data_set($col4, 'ispf.class', 'cssssgsis sbccsreadonly');
    data_set($col4, 'dp.class', 'cssssgsis sbccsreadonly');
    data_set($col4, 'pf.class', 'cssssgsis sbccsreadonly');
    data_set($col4, 'isplanholder.class', 'csisplanholder sbccsreadonly');
    data_set($col4, 'pbrgy.addedparams', ['pcity']);
    data_set($col4, 'pbrgy.lookupclass', 'pbrgy');

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }



  public function createnewtransaction($docno, $params)
  {

    $userid = $params['adminid'];

    $agent = "";
    $agentname = "";

    if ($userid != 0) {
      $agent = $this->coreFunctions->getfieldvalue("client", "client", "clientid=?", [$userid]);
      $agentname = $this->coreFunctions->getfieldvalue("client", "clientname", "clientid=?", [$userid]);
    }

    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['due'] = $this->othersClass->getCurrentDate();
    $data[0]['client'] = '';
    $data[0]['afdocno'] = '';
    $data[0]['fname'] = '';
    $data[0]['mname'] = '';
    $data[0]['lname'] = '';
    $data[0]['ext'] = '';
    $data[0]['clientname'] = '';
    $data[0]['address'] = '';
    $data[0]['addressno'] = '';
    $data[0]['street'] = '';
    $data[0]['subdistown'] = '';
    $data[0]['city'] = '';
    $data[0]['province'] = '';
    $data[0]['country'] = '';
    $data[0]['zipcode'] = '';
    $data[0]['terms'] = '';
    $data[0]['otherterms'] = '';
    $data[0]['rem'] = '';
    $data[0]['voiddate'] = null;
    $data[0]['agent'] = $agent;
    $data[0]['agentname'] = $agentname;
    $data[0]['dagentname'] = $agent . '~' . $agentname;
    $data[0]['yourref'] = '';
    $data[0]['ourref'] = '';
    $data[0]['contactno'] = '';
    $data[0]['contactno2'] = '';
    $data[0]['email'] = '';
    $data[0]['vattype'] = '';
    $data[0]['dvattype'] = '';
    $data[0]['planid'] = 0;
    $data[0]['tax'] = 0;

    $data[0]['amount'] = 0;

    $data[0]['bclient'] = '';
    $data[0]['bclientname'] = '';
    $data[0]['bclientid'] = 0;
    $data[0]['isplanholder'] = '0';
    $data[0]['isbene'] = '0';
    $data[0]['issenior'] = '0';
    $data[0]['issameadd'] = '0';

    $data[0]['lname2'] = '';
    $data[0]['fname2'] = '';
    $data[0]['mname2'] = '';
    $data[0]['ext2'] = '';
    $data[0]['gender'] = '';
    $data[0]['civilstatus'] = '';

    $data[0]['raddressno'] = '';
    $data[0]['rstreet'] = '';
    $data[0]['rsubdistown'] = '';
    $data[0]['rcity'] = '';
    $data[0]['rprovince'] = '';
    $data[0]['rcountry'] = '';
    $data[0]['rzipcode'] = '';
    $data[0]['paddressno'] = '';
    $data[0]['pstreet'] = '';
    $data[0]['psubdistown'] = '';
    $data[0]['pcity'] = '';
    $data[0]['pprovince'] = '';
    $data[0]['pcountry'] = '';
    $data[0]['pzipcode'] = '';

    $data[0]['bday'] = null;
    $data[0]['nationality'] = '';
    $data[0]['pob'] = '';
    $data[0]['ispassport'] = '0';
    $data[0]['isdriverlisc'] = '0';
    $data[0]['isprc'] = '0';
    $data[0]['isotherid'] = '0';
    $data[0]['isseniorid'] = '0';
    $data[0]['idno'] = '';
    $data[0]['expiration'] = '';

    $data[0]['isemployment'] = '0';
    $data[0]['isbusiness'] = '0';
    $data[0]['isinvestment'] = '0';
    $data[0]['isothersource'] = '0';
    $data[0]['othersource'] = '';

    $data[0]['isemployed'] = '0';
    $data[0]['isselfemployed'] = '0';
    $data[0]['isofw'] = '0';
    $data[0]['isretired'] = '0';
    $data[0]['iswife'] = '0';
    $data[0]['isnotemployed'] = '0';
    $data[0]['tin'] = '';
    $data[0]['sssgsis'] = '';

    $data[0]['lessten'] = '0';
    $data[0]['tenthirty'] = '0';
    $data[0]['thirtyfifty'] = '0';
    $data[0]['fiftyhundred'] = '0';
    $data[0]['hundredtwofifty'] = '0';
    $data[0]['twofiftyfivehundred'] = '0';
    $data[0]['fivehundredup'] = '0';
    $data[0]['employer'] = '';
    $data[0]['otherplan'] = '';
    $data[0]['appref'] = '';

    $data[0]['isdp'] = '0';
    $data[0]['dp'] = '0.00';

    $data[0]['ispf'] = '0';
    $data[0]['pf'] = '0.00';

    return $data;
  }

  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $trno = $config['params']['trno'];
    $center = $config['params']['center'];
    $tablenum = $this->tablenum;
    $agentfilter = '';
    $allowall = $this->othersClass->checkAccess($config['params']['user'], 4077);
    $adminid =  $config['params']['adminid'];

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

    $eahead = 'heahead';
    $info = 'heainfo';

    if ($allowall == '0') {
      if ($adminid != 0) {
        $isleader = $this->coreFunctions->getfieldvalue("client", "isleader", "clientid=?", [$adminid]);
        if (floatval($isleader) == 1) {
          $agentfilter = " and (lead.clientid = " . $adminid . " or  ag.clientid =  " . $adminid . ") ";
        } else {
          $agentfilter = " and ag.clientid = " . $adminid . " ";
        }
      }
    }

    $qryselect = "select 
         num.center,
         cp.trno, 
         cp.docno,
         head.docno as afdocno,
         cp.aftrno,
         date_format(cp.dateid,'%m/%d/%Y') as dateid,
         date_format(cp.due,'%m/%d/%Y') as due,
         client.client,
         head.fname,
         head.mname,
         head.lname,
         head.clientname,
         head.ext,
         head.address,
         head.addressno,
         head.street,
         head.subdistown,
         head.city,
         head.brgy,
         head.province,
         head.country,
         head.zipcode,
         head.terms,
         head.otherterms,
         head.rem,
         head.voiddate,
         head.yourref,
         head.ourref,
         head.contactno,
         head.contactno2,
         head.email,
         cp.vattype,
         head.planid,
         cp.tax,
         '' as dvattype,         
         ifnull(ag.client,'') as agent,
         ifnull(ag.clientname,'') as agentname,
         ifnull(pt.name,'') as plantype,
         ifnull(format((case info.issenior when 1 then pt.amount/1.12 else pt.amount end),2),0) as amount,
         '' as dagentname,
         info.isplanholder,
         info.isbene,
         info.issameadd,
         info.client as bclient,
         info.clientname as bclientname,
         info.lname as lname2,
         info.fname as fname2,
         info.mname as mname2,
         info.ext as ext2,
         info.gender,
         info.civilstat as civilstatus,

         info.addressno as raddressno,
         info.street as rstreet,
         info.subdistown as rsubdistown,
         info.city as rcity, info.brgy as rbrgy,info.province as rprovince,
         info.country as rcountry,
         info.zipcode as rzipcode,
         info.paddressno,
         info.pstreet,
         info.psubdistown,
         info.pcity,
         info.pbrgy,
         info.pprovince,
         info.pcountry,
         info.pzipcode,

         date_format(info.bday,'%m/%d/%Y') as bday,
         info.nationality,
         info.pob,
         info.ispassport,
         info.isdriverlisc,
         info.isprc,
         info.issenior,
         info.isseniorid,
         info.isotherid,
         info.idno,
         info.expiration,
         info.isemployment,
         info.isbusiness,
         info.isinvestment,
         info.isothersource,
         info.othersource,
         info.isemployed,
         info.isselfemployed,
         info.isofw,
         info.isretired,
         info.iswife,
         info.isnotemployed,
         info.tin,
         info.sssgsis,
         info.lessten,
         info.tenthirty,
         info.thirtyfifty,
         info.fiftyhundred,
         info.hundredtwofifty,
         info.twofiftyfivehundred,
         info.fivehundredup,
         info.employer,
         info.otherplan,info.appref,format(info.dp,2) as dp,format(info.pf,2) as pf,info.isdp,info.ispf
         ";


    $qry = $qryselect . " from $table as cp
        left join $tablenum as num on num.trno = cp.trno
        left join $eahead as head on cp.aftrno = head.trno
        left join $info as info on head.trno = info.trno
        left join client on cp.client = client.client
        left join client as ag on ag.client = cp.agent
        left join client as lead on lead.clientid = ag.parent
        left join plantype as pt on pt.line = head.planid
        where cp.trno = ? and num.doc=? and num.center = ? " . $agentfilter . "
        union all " . $qryselect . " from $htable as cp
        left join $tablenum as num on num.trno = cp.trno
        left join $eahead as head on cp.aftrno = head.trno
        left join $info as info on head.trno = info.trno
        left join client on cp.clientid = client.clientid
        left join client as ag on ag.clientid = cp.agentid
        left join client as lead on lead.clientid = ag.parent
        left join plantype as pt on pt.line = head.planid
        where cp.trno = ? and num.doc=? and num.center=? " . $agentfilter;

    $head = $this->coreFunctions->opentable($qry, [$trno, $doc, $center, $trno, $doc, $center]);
    if (!empty($head)) {
      foreach ($this->blnfields as $key => $value) {
        if ($head[0]->$value) {
          $head[0]->$value = "1";
        } else
          $head[0]->$value = "0";
      }
      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
      $hideobj = [];
      if ($config['params']['companyid'] == 34) {
        $attached = $this->coreFunctions->datareader("select title as value from cntnum_picture  where trno=?", [$trno]);

        $lblattached_stat = $attached == "" ? true : false;
        $hideobj = ['lblattached' => $lblattached_stat];
        $hideheadergridbtns = ['tagreceived' => !$lblattached_stat, 'untagreceived' => $lblattached_stat];
      }



      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }

      if ($this->companysetup->getistodo($config['params'])) {
        $btndonetodo = $this->othersClass->checkdonetodo($config, $tablenum);
        $hideobj = ['donetodo' => !$btndonetodo];
      }
      return  ['head' => $head, 'griddata' => ['accounting' => []], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg, 'hideobj' => $hideobj, 'hideheadgridbtns' => $hideheadergridbtns];
    } else {
      $head[0]['trno'] = 0;
      $head[0]['docno'] = '';
      return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['accounting' => []], 'msg' => 'Data Head Fetched Failed, either somebody already deleted the transaction or modified...'];
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

    $date = date_create($data['dateid']);
    if (strtoupper($head['terms']) != 'COD' && strtoupper($head['terms']) != 'SPOT CASH' && strtoupper($head['terms']) != 'UPFRONT 3 %') {
      $data['due'] = date_add($date, date_interval_create_from_date_string("2 year"));
    } else {
      $data['due'] = $data['dateid'];
    }


    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];

    if ($isupdate) {
      $aftrno = $this->coreFunctions->datareader('select aftrno as value from ' . $this->head . " where trno=?", [$head['trno']]);
      $this->coreFunctions->sbcupdate('heahead', ['catrno' => 0], ['trno' => $aftrno]);

      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);


      $this->coreFunctions->sbcupdate('heahead', ['catrno' => $head['trno']], ['trno' => $head['aftrno']]);
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head, $data);
      $this->coreFunctions->sbcupdate('heahead', ['catrno' => $head['trno']], ['trno' => $head['aftrno']]);
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['client'] . ' - ' . $head['clientname']);
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

    $aftrno = $this->coreFunctions->datareader('select aftrno as value from ' . $this->head . " where trno=?", [$trno]);

    $this->coreFunctions->sbcupdate('heahead', ['catrno' => 0], ['trno' => $aftrno]);

    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
    $this->othersClass->deleteattachments($config);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  //  to follow posting
  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    if ($this->othersClass->isposted($config)) {
      return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
    }

    if (!$this->createdistribution($config)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. Problems in creating accounting entries.'];
    } else {
      return $this->othersClass->posttransacctg($config);
    }
  } //end function

  public function unposttrans($config)
  {
    return $this->othersClass->unposttransacctg($config);
  } //end function

  public function createdistribution($config)
  {
    $companyid = $config['params']['companyid'];
    $trno = $config['params']['trno'];
    $status = true;
    $terms = 1;
    $entry = [];
    $pf = 0;

    $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);

    $qry = 'select head.dateid,head.client,head.tax,head.contra,head.cur,head.forex,p.amount,p.cash,p.annual,p.semi,p.quarterly,p.monthly,p.processfee,head.due,app.terms,terms.days
    from ' . $this->head . ' as head
    left join client on client.client = head.client
    left join heahead as app on app.trno=head.aftrno
    left join heainfo as info on info.trno = app.trno
    left join terms on terms.terms = app.terms
    left join plantype as p on p.line = app.planid
    where head.trno=?';


    $stock = $this->coreFunctions->opentable($qry, [$trno]);
    $tax = 0;
    if (!empty($stock)) {
      $aracct = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['AR1']);
      $revacct = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['SA1']);
      $pfacct = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['AR2']);
      $revpfacct = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['SA2']);
      $vatacct = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['TX2']);
      $vat = floatval($stock[0]->tax);
      $tax1 = 0;
      $tax2 = 0;
      if ($vat !== 0) {
        $tax1 = 1 + ($vat / 100);
        $tax2 = $vat / 100;
      }

      $cr = 0;
      $day = date("d", strtotime($stock[0]->dateid));
      $mnth = date("m", strtotime($stock[0]->dateid));
      $yr = date("Y", strtotime($stock[0]->dateid));

      switch (strtoupper($stock[0]->terms)) {
        case 'ANNUAL':
          $terms = 2;
          $rdate = strtotime($stock[0]->dateid);
          $dateid = $stock[0]->dateid;
          $incmonth = $stock[0]->days;
          $amount = $stock[0]->annual;
          break;
        case 'SEMI-ANNUAL':
          $terms = 4;
          $rdate = strtotime($stock[0]->dateid);
          $dateid = $stock[0]->dateid;
          $incmonth = $stock[0]->days;
          $amount = $stock[0]->semi;
          break;
        case 'MONTHLY':
          $terms =  24;
          $rdate = strtotime($stock[0]->dateid);
          $dateid = $stock[0]->dateid;
          $incmonth = $stock[0]->days;
          $amount = $stock[0]->monthly;
          break;
        case 'QUARTERLY':
          $terms = 8;
          $rdate = strtotime($stock[0]->dateid);
          $dateid = $stock[0]->dateid;
          $incmonth = $stock[0]->days;
          $amount = $stock[0]->quarterly;
          break;
        case 'FULL PAYMENT':
          $terms = 1;
          $rdate = strtotime($stock[0]->dateid);
          $dateid = $stock[0]->dateid;
          $incmonth = 0;
          $amount = $stock[0]->amount;
          break;
        default:
          $terms = 1;
          $rdate = strtotime($stock[0]->dateid);
          $dateid = $stock[0]->dateid;
          $incmonth = 0;
          $amount = $stock[0]->cash;
          break;
      }

      if ($vat == 0) {
        $amount = $amount / 1.12;
      }

      $pf = $stock[0]->processfee;

      //pf entry
      $entry = [
        'client' => $stock[0]->client,
        'acnoid' => $pfacct,
        'db' => $stock[0]->processfee,
        'cr' => 0,
        'rem' => 'Processing Fee',
        'postdate' => $stock[0]->dateid
      ];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

      //pf sales entry
      if ($vat != 0) {
        $pfvat =  ($stock[0]->processfee / $tax1) * $tax2;
        $pf = $stock[0]->processfee - $pfvat;
      }
      $entry = [
        'client' => $stock[0]->client,
        'acnoid' => $revpfacct,
        'db' => 0,
        'cr' => $pf,
        'rem' => 'Processing Fee',
        'postdate' => $stock[0]->dateid
      ];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

      //ar entry
      for ($y = 1; $y <= $terms; $y++) {
        $locale = 'en_US';
        $nf = new \NumberFormatter($locale, \NumberFormatter::ORDINAL);

        $entry = [
          'client' => $stock[0]->client,
          'acnoid' => $aracct,
          'db' => number_format($amount, 2, '.', ''),
          'cr' => 0,
          'rem' => $nf->format($y) . ' Amortization',
          'postdate' => $dateid
        ];
        $cr = $cr + number_format($amount, 2, '.', '');
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

        $newday = $day;
        if ($incmonth != 0) {
          $mnth = $mnth + $incmonth;
          if ($mnth > 12) {
            $mnth = $mnth - 12;
            $yr = $yr + 1;
          }
          //$newdate = date("Y-m-d",strtotime($mnth."/".$day."/".$yr));

          if (checkdate($mnth, $day, $yr)) {
            //$this->coreFunctions->LogConsole("Yes". $mnth."/".$day."/". $yr);
            $newdate = date("Y-m-d", strtotime($yr . "-" . $mnth . "-" . $day));
          } else {
            //$this->coreFunctions->LogConsole("No". $mnth."/".$day."/". $yr);
            x:
            $newday = $newday - 1;
            if (!checkdate($mnth, $newday, $yr)) {
              goto x;
            } else {
              //$this->coreFunctions->LogConsole("Yes". $mnth."/".$newday."/". $yr);
              $newdate = date("Y-m-d", strtotime($yr . "-" . $mnth . "-" . $newday));
            }
          }

          // $dateid = date("Y-m-d", strtotime("+" . $incmonth . " month", $rdate));
          // $rdate = strtotime($dateid);          
          $dateid = $newdate;
          $rdate = strtotime($newdate);
        }
      }

      //vat entry
      if ($vat != 0) {
        $tax = ($cr / $tax1) * $tax2;
        $cr = $cr - $tax;
        $pfvat =  ($stock[0]->processfee / $tax1) * $tax2;
        $pf = $stock[0]->processfee - $pfvat;

        $entry = [
          'client' => $stock[0]->client,
          'acnoid' => $vatacct,
          'db' => 0,
          'cr' => $tax + $pfvat,
          'rem' => '',
          'postdate' => $stock[0]->dateid
        ];
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }


      $entry = [
        'client' => $stock[0]->client,
        'acnoid' => $revacct,
        'db' => 0,
        'cr' => $cr,
        'rem' => '',
        'postdate' => $stock[0]->dateid
      ];
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
      }
      if ($this->coreFunctions->sbcinsert($this->detail, $this->acctg) == 1) {
        $this->coreFunctions->execqry("update  " . $this->head . " set rem ='Plan amount and Processing fee' where trno=?", 'update', [$trno]);
        $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION SUCCESS');
        $status = true;
      } else {
        $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION FAILED');
        $status = false;
      }
    }

    return $status;
  } //end function




  public function stockstatus($config)
  {
    return [];
  }

  public function updateperitem($config)
  {
    return [];
  }


  public function updateitem($config)
  {
    return [];
  } //end function

  public function addallitem($config)
  {
    return [];
  } //end function


  // insert and update detail
  public function additem($action, $config)
  {
    return [];
  } // end function

  public function deleteallitem($config)
  {
    return [];
  }

  public function deleteitem($config)
  {
    return [];
  } //end function


  public function stockstatusposted($config)
  {
    switch ($config['params']['action']) {
      case 'navigation':
        return $this->othersClass->navigatedocno($config);
        break;

      case 'generatecert':
        return $this->setupreport($config);

        break;

      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }


  private function createclient($config)
  {
    $trno = $config['params']['trno'];
    $center = $config['params']['center'];
    $data = [];
    $cinfo = [];

    $clientcode = $this->getnewclient($config); // create customer


    $qry = "select head.clientname, head.address, head.contactno, head.terms, head.agent,head.email,head.fname,head.lname,head.mname,head.addressno,
    head.street,head.subdistown,head.city,head.country,head.zipcode,i.isplanholder,i.tin,head.ext
    from " . $this->head . " as head left join eainfo as i on i.trno = head.trno where head.trno = ? limit 1 ";
    $res = $this->coreFunctions->opentable($qry, [$trno]);


    $data['client'] = $clientcode;
    $data['clientname'] = $res[0]->lname . ', ' . $res[0]->fname . ' ' . $res[0]->mname . ' ' . $res[0]->ext;
    $data['addr'] = $res[0]->addressno . ' ' . $res[0]->street . ' ' . $res[0]->subdistown . ' ' . $res[0]->city . ' ' . $res[0]->country . ' ' . $res[0]->zipcode;
    $data['tel'] = $res[0]->contactno;
    $data['terms'] = $res[0]->terms;
    $data['agent'] = $res[0]->agent;
    $data['status'] = 'ACTIVE';
    $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['createby'] = $config['params']['user'];
    $data['iscustomer'] = 1;
    $data['center'] = $center;
    $data['email'] = $res[0]->email;
    if ($res[0]->isplanholder == 1) {
      $data['tin'] = $res[0]->tin;
    }

    //create client
    $clientid = $this->coreFunctions->insertGetId('client', $data);
    $this->logger->sbcwritelog($clientid, $config, 'CREATE', 'Application - ' . $clientid . ' - ' . $clientcode . ' - ' . $res[0]->fname . ' ' . $res[0]->mname . ' ' . $res[0]->lname);

    $cinfo['clientid'] = $clientid;
    $cinfo['lname'] = $res[0]->lname;
    $cinfo['fname'] = $res[0]->fname;
    $cinfo['mname'] = $res[0]->mname;
    $cinfo['ext'] = $res[0]->ext;
    $cinfo['addressno'] = $res[0]->addressno;
    $cinfo['street'] = $res[0]->street;
    $cinfo['subdistown'] = $res[0]->subdistown;
    $cinfo['city'] = $res[0]->city;
    $cinfo['country'] = $res[0]->country;
    $cinfo['zipcode'] = $res[0]->zipcode;
    if ($clientid != 0) {
      $this->coreFunctions->sbcinsert('clientinfo', $cinfo);
    }

    $this->coreFunctions->execqry("update " . $this->head . " set client = ?,clientname = ? where trno = ?", 'update', [$clientcode, $res[0]->lname . ', ' . $res[0]->fname . ' ' . $res[0]->mname, $trno]);
    if ($res[0]->isplanholder == 1) {
      $this->coreFunctions->execqry("update eainfo set client = ?,clientname = ? where trno = ?", 'update', [$clientcode, $res[0]->lname . ', ' . $res[0]->fname . ' ' . $res[0]->mname, $trno]);
    }

    return ['status' => true, 'msg' => 'Successfully fetched.', 'reloadhead' => true];
  }

  private function getnewclient($config)
  {
    $pref = 'CL';
    $docnolength =  $this->companysetup->getclientlength($config['params']);
    $last = $this->othersClass->getlastclient($pref, 'customer');
    $start = $this->othersClass->SearchPosition($last);
    $seq = substr($last, $start) + 1;
    $poseq = $pref . $seq;
    $newclient = $this->othersClass->PadJ($poseq, $docnolength);
    return $newclient;
  }

  public function getapplicationform($config)
  {
    $qryselect = "select 
    head.trno as aftrno, 
    head.docno,
    date_format(head.dateid,'%m/%d/%Y') as dateid,
    head.client,
    head.fname,
    head.mname,
    head.lname,
    head.clientname,
    head.ext,
    head.address,
    head.addressno,
    head.street,
    head.subdistown,
    head.city,
    head.country,
    head.zipcode,
    head.terms,
    head.otherterms,
    head.rem,
    head.voiddate,
    head.yourref,
    head.ourref,
    head.contactno,
    head.contactno2,
    head.email,
    head.planid,
    ifnull(ag.client,'') as agent,
    ifnull(ag.clientname,'') as agentname,
    ifnull(pt.name,'') as plantype,
    ifnull(pt.amount,'') as amount,
    '' as dagentname,
    info.isplanholder,
    info.client as bclient,
    concat(info.fname,' ',info.mname,' ',info.lname,' ',info.ext) as bclientname,
    info.lname as lname2,
    info.fname as fname2,
    info.mname as mname2,
    info.ext as ext2,
    info.gender,
    info.civilstat as civilstatus,
    concat(info.addressno,' ',info.street,' ',info.subdistown,' ',info.city,' ',info.country,' ',info.zipcode) as raddress,
    info.addressno as raddressno,
    info.street as rstreet,
    info.subdistown as rsubdistown,
    info.city as rcity,
    info.country as rcountry,
    info.zipcode as rzipcode,
    info.paddressno,
    info.pstreet,
    info.psubdistown,
    info.pcity,
    info.pcountry,
    info.pzipcode,

    date_format(info.bday,'%m/%d/%Y') as bday,
    info.nationality,
    info.pob,
    case when info.ispassport=0 then '0' else '1' end as ispassport,
    case when info.isprc=0 then '0' else '1' end as isprc,
    case when info.isdriverlisc=0 then '0' else '1' end as isdriverlisc,         
    case when info.isotherid=0 then '0' else '1' end as isotherid,  
    info.idno,
    info.expiration,
    case when info.isemployment=0 then '0' else '1' end as isemployment,  
    case when info.isbusiness=0 then '0' else '1' end as isbusiness,  
    case when info.isinvestment=0 then '0' else '1' end as isinvestment,  
    case when info.isothersource=0 then '0' else '1' end as isothersource,  
    case when info.isemployed=0 then '0' else '1' end as isemployed,  
    case when info.isselfemployed=0 then '0' else '1' end as isselfemployed,  
    case when info.isofw=0 then '0' else '1' end as isofw,  
    case when info.isretired=0 then '0' else '1' end as isretired,  
    case when info.iswife=0 then '0' else '1' end as iswife,  
    case when info.isnotemployed=0 then '0' else '1' end as isnotemployed,  
    info.othersource,
    info.tin,
    info.sssgsis,
    case when info.lessten=0 then '0' else '1' end as lessten,  
    case when info.tenthirty=0 then '0' else '1' end as tenthirty,  
    case when info.thirtyfifty=0 then '0' else '1' end as thirtyfifty,  
    case when info.fiftyhundred=0 then '0' else '1' end as fiftyhundred,  
    case when info.hundredtwofifty=0 then '0' else '1' end as hundredtwofifty,  
    case when info.twofiftyfivehundred=0 then '0' else '1' end as twofiftyfivehundred,  
    case when info.fivehundredup=0 then '0' else '1' end as fivehundredup,  
    info.employer,
    info.otherplan,case t.days when 0 then now() else date_add(now(),interval 2 year) end as due,case when info.issenior=0 then '0' else '1' end as issenior,case info.issenior when 1 then 0 else 12 end as tax,case info.issenior when 1 then 'NON-VATABLE' else 'VATABLE' end as vattype
    ";


    $qryselect = $qryselect . " from heahead as head
   left join heainfo as info on head.trno = info.trno
   left join client on head.client = client.client
   left join client as ag on ag.client = head.agent
   left join plantype as pt on pt.line = head.planid
   left join terms as t on t.terms = head.terms
   where head.catrno = 0  and head.trno =?";


    return $qryselect;
  }

  public function reportsetup($config)
  {

    $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
    $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config, 1);
    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';


    $this->posttrans($config);

    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false, 'reloadhead' => true];
  }

  public function setupreport($config)
  {
    $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
    $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config, 2);
    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';

    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false, 'reloadhead' => true];
  }

  public function reportdata($config)
  {
    $this->logger->sbcviewreportlog($config);
    $companyid = $config['params']['companyid'];

    $dataparams = $config['params']['dataparams'];
    if (isset($dataparams['prepared'])) $this->othersClass->writeSignatories($config, 'prepared', $dataparams['prepared']);
    if (isset($dataparams['approved'])) $this->othersClass->writeSignatories($config, 'approved', $dataparams['approved']);
    if (isset($dataparams['received'])) $this->othersClass->writeSignatories($config, 'received', $dataparams['received']);

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
