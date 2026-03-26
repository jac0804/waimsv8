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

class af
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'APPLICATION FORM';
  public $gridname = 'accounting';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $tablenum = 'transnum';
  public $head = 'eahead';
  public $hhead = 'heahead';
  public $info = 'eainfo';
  public $hinfo = 'heainfo';
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';

  private $fields = [
    'trno', 'docno', 'dateid', 'client', 'fname', 'mname', 'lname', 'ext', 'clientname', 'address', 'addressno', 'street',
    'subdistown', 'city', 'country', 'zipcode', 'terms', 'otherterms', 'rem', 'voiddate', 'agent', 'yourref', 'ourref',
    'contactno', 'contactno2', 'email', 'vattype', 'planid', 'tax', 'plangrpid', 'province', 'brgy'
  ];

  private $blnfields = [
    'isplanholder', 'ispassport', 'isdriverlisc', 'isprc', 'isseniorid', 'isotherid', 'isemployment', 'isbusiness', 'isinvestment', 'isothersource', 'isemployed', 'isselfemployed', 'isbene', 'issenior',
    'isofw', 'isretired', 'iswife', 'isnotemployed', 'lessten', 'tenthirty', 'thirtyfifty', 'fiftyhundred', 'hundredtwofifty', 'twofiftyfivehundred', 'fivehundredup', 'issameadd', 'isdp', 'ispf'
  ];

  private $except = ['trno', 'dateid', 'voiddate'];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  private $reporter;
  private $helpClass;


  public $showfilterlabel = [
    ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
    ['val' => 'locked', 'label' => 'For Review', 'color' => 'red'],
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
      'view' => 4040,
      'edit' => 4041,
      'new' => 4042,
      'save' => 4043,
      'delete' => 4044,
      'print' => 4045,
      'lock' => 4046,
      'unlock' => 4047,
      'post' => 4048,
      'unpost' => 4049,
      'additem' => 4050,
      'edititem' => 4051,
      'deleteitem' => 4052
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
    $yourref = 7;
    $rem = 6;
    $postdate = 9;

    $getcols = ['action', 'liststatus', 'listdocument', 'listdate',  'listplanholder', 'listclientname',  'rem', 'yourref', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$liststatus]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';
    $cols[$listclientname]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;';
    $cols[$listplanholder]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;';
    $cols[$yourref]['align'] = 'text-left';
    $cols[$yourref]['label'] = 'Payment Method';
    $cols[$listclientname]['label'] = 'Payor';
    $cols[$rem]['align'] = 'text-left';
    $cols[$postdate]['label'] = 'Post Date';
    $cols[$liststatus]['name'] = 'statuscolor';
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
    $searchfilter = $config['params']['search'];
    $limit = '';
    $condition = '';
    $agentfilter = '';
    $allowall = $this->othersClass->checkAccess($config['params']['user'], 4077);
    $nosearch = $this->othersClass->checkAccess($config['params']['user'], 4098);
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
      $searchfield = ['head.docno', 'head.clientname', 'head.lname', 'head.mname', 'head.fname', 'i.lname', 'i.mname', 'i.fname', 'head.yourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];

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

    $qry = "select head.trno,head.docno,concat(head.lname,', ',head.fname,' ',head.mname,' ',head.ext)  as clientname,concat(i.lname,', ',i.fname,' ',i.mname,' ',i.ext) as planholder,$dateid, case ifnull(head.lockdate,'') when '' then 'DRAFT' else 'FOR REVIEW' end as status,
    head.createby,head.editby,head.viewby,num.postedby, date(num.postdate) as postdate,
      head.yourref, head.ourref,case ifnull(head.lockdate,'') when '' then 'red' else 'green' end as statuscolor, head.rem
     from " . $this->head . " as head left join " . $this->tablenum . " as num 
     on num.trno=head.trno left join eainfo as i on i.trno = head.trno left join client as agent on agent.client = head.agent
     left join client as lead on lead.clientid = agent.parent where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $agentfilter . " 
     union all
     select head.trno,head.docno,concat(head.lname,', ',head.fname,' ',head.mname,' ',head.ext)  as clientname,concat(i.lname,', ',i.fname,' ',i.mname,' ',i.ext) as planholder,$dateid,'APPROVED' as status,
     head.createby,head.editby,head.viewby, num.postedby, date(num.postdate) as postdate,
       head.yourref, head.ourref,'blue' as statuscolor, head.rem
     from " . $this->hhead . " as head left join " . $this->tablenum . " as num 
     on num.trno=head.trno left join heainfo as i on i.trno = head.trno left join client as agent on agent.client = head.agent left join client as lead on lead.clientid = agent.parent where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $agentfilter . " 
    $orderby $limit";


    if ($nosearch == '0') {
      $data = [];
    } else {
      $data = $this->coreFunctions->opentable($qry, [$doc, $center, $date1, $date2, $doc, $center, $date1, $date2]);
      if (!empty($data)) {
        foreach ($data as $d) {
          $acount = $this->coreFunctions->opentable("select count(*) as acount from transnum_picture where trno=?", [$d->trno]);
          $d->rem .= ' ' . $acount[0]->acount . ' Attached Document(s)';
        }
      }
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
      'toggledown',
      'help',
      'others'
    );

    $buttons = $this->btnClass->create($btns);

    $allow = $this->othersClass->checkAccess($config['params']['user'], 4048);
    if ($allow != '1') {
      $buttons['post']['disable'] = true;
    }

    $allow = $this->othersClass->checkAccess($config['params']['user'], 4049);
    if ($allow != '1') {
      $buttons['unpost']['disable'] = true;
    }

    $allow = $this->othersClass->checkAccess($config['params']['user'], 4044);
    if ($allow != '1') {
      $buttons['delete']['disable'] = true;
    }

    $allow = $this->othersClass->checkAccess($config['params']['user'], 4046);
    if ($allow != '1') {
      $buttons['lock']['disable'] = true;
    }

    $allow = $this->othersClass->checkAccess($config['params']['user'], 4047);
    if ($allow != '1') {
      $buttons['unlock']['disable'] = true;
    }


    $buttons['post']['label'] = 'APPROVED';
    $buttons['unpost']['label'] = 'DISAPPROVED';
    $buttons['lock']['label'] = 'FOR REVIEW';
    $buttons['unlock']['label'] = 'RETURN TO AGENT';


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

    $buttons['others']['items']['createprofile'] = ['label' => 'Create Profile', 'todo' => ['lookupclass' => 'generateclient', 'action' => 'generateclient', 'access' => 'view', 'type' => 'navigation']];

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
    $companyid = $config['params']['companyid'];

    $fields = ['isplanholder', 'client', 'dateid', 'terms', 'yourref', 'otherterms'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.lookupclass', 'payors');
    data_set($col1, 'client.label', 'Payors');
    data_set($col1, 'clientname.label', 'Payor\'s Name');
    data_set($col1, 'client.class', 'sbccsreadonly');
    data_set($col1, 'client.required', false);
    data_set($col1, 'terms.type', 'lookup');
    data_set($col1, 'terms.action', 'lookupterms');
    data_set($col1, 'terms.lookupclass', 'ledgerterms');
    data_set($col1, 'terms.label', 'Payment Terms');
    data_set($col1, 'yourref.type', 'lookup');
    data_set($col1, 'yourref.action', 'lookuprandom');
    data_set($col1, 'yourref.lookupclass', 'lookupmethod');
    data_set($col1, 'yourref.label', 'Method');
    data_set($col1, 'yourref.class', 'csyourref sbccsreadonly');
    $fields = ['lname', 'fname', 'mname', 'ext'];
    $col2 = $this->fieldClass->create($fields);

    data_set($col2, 'lname.required', false);
    data_set($col2, 'fname.required', false);
    data_set($col2, 'mname.required', false);
    data_set($col2, 'ext.label', 'Ext');
    $fields = ['addressno', 'street', 'subdistown', 'province', 'city', 'brgy', 'country', 'zipcode'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'city.label', 'City');
    data_set($col3, 'city.lookupclass', 'city');
    data_set($col3, 'city.action', 'lookupprovcity');
    data_set($col3, 'city.type', 'lookup');
    data_set($col3, 'brgy.label', 'Barangay');
    data_set($col3, 'brgy.action', 'lookupprovcity');
    data_set($col3, 'province.lookupclass', 'province');
    data_set($col3, 'province.action', 'lookupprovcity');
    data_set($col3, 'province.type', 'lookup');
    data_set($col3, 'city.addedparams', ['province']);
    data_set($col3, 'brgy.addedparams', ['city']);
    data_set($col3, 'zipcode.type', 'lookup');
    data_set($col3, 'zipcode.action', 'lookupprovcity');
    data_set($col3, 'zipcode.lookupclass', 'zipcode');
    data_set($col3, 'zipcode.addedparams', ['city']);


    $fields = ['contactno', 'contactno2', 'email'];
    $col4 = $this->fieldClass->create($fields);

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
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];
    $allowall = $this->othersClass->checkAccess($config['params']['user'], 4077);
    $nosearch = $this->othersClass->checkAccess($config['params']['user'], 4098);

    $fields = [
      'docno', 'bclient', 'lname2', 'fname2', 'mname2', 'ext2', 'lblshipping', ['ispassport', 'isprc'], ['isdriverlisc', 'isotherid'], 'isseniorid', ['idno', 'expiration'], 'passbook',
      'lessten', 'tenthirty', 'thirtyfifty', 'fiftyhundred', 'hundredtwofifty', 'twofiftyfivehundred', 'fivehundredup'
    ];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'docno.label', 'Application #');
    data_set($col1, 'bclient.lookupclass', 'epayors');
    data_set($col1, 'bclient.class', 'csbclient sbccsreadonly');
    data_set($col1, 'bclient.label', 'Plan Holder');

    data_set($col1, 'bclient.required', false);

    data_set($col1, 'lblshipping.label', 'Government I.D');
    data_set($col1, 'lblshipping.style', 'font-weight:bold; font-size:13px;');
    data_set($col1, 'idno.label', 'Number/Type');
    data_set($col1, 'passbook.label', 'Monthly Income');
    data_set($col1, 'passbook.style', 'font-weight:bold; font-size:13px;');
    if ($nosearch == '0') {
      data_set($col1, 'docno.type', 'input');
      data_set($col1, 'docno.class', 'sbccsreadonly');
    }

    $fields = [
      'plantype', 'amount', 'dagentname', 'gender', 'civilstatus', ['bday', 'nationality'], 'pob', ['lblbilling'], ['isemployment', 'isinvestment'], ['isbusiness', 'isothersource'], 'othersource', 'lblacquisition',
      ['isemployed', 'isselfemployed'], 'isofw', 'isretired', 'iswife', 'isnotemployed'
    ];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'lblbilling.label', 'Source of Income');
    data_set($col2, 'lblbilling.style', 'font-weight:bold; font-size:13px;');
    data_set($col2, 'lblacquisition.label', 'Occupation');
    data_set($col2, 'lblacquisition.style', 'font-weight:bold; font-size:13px;');
    data_set($col2, 'amount.class', 'sbccsreadonly');

    data_set($col2, 'nationality.type', 'lookup');
    data_set($col2, 'nationality.action', 'lookupnationality');

    if ($allowall == '0') {
      data_set($col2, 'dagentname.class', 'sbccsreadonly');
      data_set($col2, 'dagentname.type', 'input');
    }

    $fields = ['lblcostuom', 'raddressno', 'rstreet', 'rsubdistown', 'rprovince', 'rcity', 'rbrgy', 'rcountry', 'rzipcode', 'lbldepreciation', 'employer', 'otherplan', 'rem'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'lblcostuom.label', 'Address (Residence)');
    data_set($col3, 'lblcostuom.style', 'font-weight:bold; font-size:13px;');
    data_set($col3, 'lbldepreciation.label', '________________________________________________');
    data_set($col3, 'lbldepreciation.style', 'width:100%; font-weight:bold; font-size:13px;color:#cccccc');
    data_set($col3, 'rcity.lookupclass', 'rcity');
    data_set($col3, 'rprovince.lookupclass', 'rprovince');
    data_set($col3, 'rcity.addedparams', ['rprovince']);
    data_set($col3, 'rcountry.type', 'lookup');
    data_set($col3, 'rcountry.action', 'lookupcountry');
    data_set($col3, 'rbrgy.lookupclass', 'rbrgy');
    data_set($col3, 'rbrgy.addedparams', ['rcity']);
    data_set($col3, 'rzipcode.type', 'lookup');
    data_set($col3, 'rzipcode.action', 'lookupprovcity');
    data_set($col3, 'rzipcode.lookupclass', 'rzipcode');
    data_set($col3, 'rzipcode.addedparams', ['rcity']);

    $fields = ['lblattached', 'lblgrossprofit', 'issameadd', 'paddressno', 'pstreet', 'psubdistown', 'pprovince', 'pcity', 'pbrgy', 'pcountry', 'pzipcode', 'lbllocation', 'tin', 'sssgsis', 'appref', ['isdp', 'ispf'], ['dp', 'pf'],  'isbene', 'issenior'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'lblgrossprofit.label', 'Permanent Address');
    data_set($col4, 'lblgrossprofit.style', 'font-weight:bold; font-size:13px;');
    data_set($col4, 'issameadd.style', 'font-weight:bold; font-size:5px;');
    data_set($col4, 'lblattached.style', 'font-family:Century Gothic; color:red; font-size:20px;font-weight:bold;');
    data_set($col4, 'lbllocation.label', '________________________________________________');
    data_set($col4, 'lbllocation.style', 'font-weight:bold; font-size:13px;color:#cccccc');
    data_set($col4, 'pcity.lookupclass', 'pcity');
    data_set($col4, 'pprovince.lookupclass', 'pprovince');
    data_set($col4, 'pcity.addedparams', ['pprovince']);
    data_set($col4, 'pbrgy.addedparams', ['pcity']);
    data_set($col4, 'pbrgy.lookupclass', 'pbrg`y');
    data_set($col4, 'pzipcode.type', 'lookup');
    data_set($col4, 'pzipcode.action', 'lookupprovcity');
    data_set($col4, 'pzipcode.lookupclass', 'pzipcode');
    data_set($col4, 'pzipcode.addedparams', ['pcity']);
    data_set($col4, 'tin.type', 'cinput');
    data_set($col4, 'tin.maxlength', 30);
    data_set($col4, 'sssgsis.type', 'cinput');
    data_set($col4, 'sssgsis.maxlength', 30);

    data_set($col4, 'issenior.class', 'sbccsreadonly');

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
    $data[0]['client'] = '';
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
    $data[0]['brgy'] = '';
    $data[0]['province'] = '';
    $data[0]['country'] = 'Philippines';
    $data[0]['zipcode'] = '';
    $data[0]['terms'] = '';
    $data[0]['otherterms'] = '';
    $data[0]['rem'] = 'Pending Payment';
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
    $data[0]['planid'] = 0;
    $data[0]['plangrpid'] = 0;
    $data[0]['tax'] = 0;

    $data[0]['amount'] = 0;

    $data[0]['bclient'] = '';
    $data[0]['bclientname'] = '';
    $data[0]['bclientid'] = 0;
    $data[0]['isplanholder'] = '0';
    $data[0]['isbene'] = '0';
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
    $data[0]['rbrgy'] = '';
    $data[0]['rprovince'] = '';
    $data[0]['rcountry'] = 'Philippines';
    $data[0]['rzipcode'] = '';
    $data[0]['paddressno'] = '';
    $data[0]['pstreet'] = '';
    $data[0]['psubdistown'] = '';
    $data[0]['pcity'] = '';
    $data[0]['pbrgy'] = '';
    $data[0]['pprovince'] = '';
    $data[0]['pcountry'] = 'Philippines';
    $data[0]['pzipcode'] = '';

    $data[0]['bday'] = null;
    $data[0]['nationality'] = 'Filipino';
    $data[0]['pob'] = '';
    $data[0]['ispassport'] = '0';
    $data[0]['isdriverlisc'] = '0';
    $data[0]['isprc'] = '0';
    $data[0]['isseniorid'] = '0';
    $data[0]['issenior'] = '0';
    $data[0]['isotherid'] = '0';
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
    $info = $this->info;
    $hinfo = $this->hinfo;

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
         head.trno, 
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
         head.vattype,
         head.planid,
         head.tax,
         head.plangrpid,

         ifnull(ag.client,'') as agent,
         ifnull(ag.clientname,'') as agentname,
         ifnull(pt.name,'') as plantype,
         ifnull(format((case info.issenior when 1 then pt.amount/1.12 else pt.amount end),2),'') as amount,
         '' as dagentname,
         info.isplanholder,
         info.issameadd,
         info.isbene,
         info.issenior,
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
         info.city as rcity,info.province as rprovince,
         info.brgy as rbrgy,
         info.country as rcountry,
         info.zipcode as rzipcode,
         info.paddressno,
         info.pstreet,
         info.psubdistown, info.pbrgy,
         info.pcity,info.pprovince ,
         info.pcountry,
         info.pzipcode,

         date_format(info.bday,'%m/%d/%Y') as bday,
         info.nationality,
         info.pob,
         info.ispassport,
         info.isdriverlisc,
         info.isprc,
         info.isotherid,
         info.idno,
         info.isseniorid,
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
         info.otherplan,
         info.appref,
         format(info.dp,2) as dp,
         info.isdp,
         format(info.pf,2) as pf,
         info.ispf
         ";


    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join $info as info on head.trno = info.trno
        left join client on head.client = client.client
        left join client as ag on ag.client = head.agent
        left join client as lead on lead.clientid = ag.parent
        left join plantype as pt on pt.line = head.planid and pt.plangrpid = head.plangrpid
        where head.trno = ? and num.doc=? and num.center = ? " . $agentfilter . "
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join $hinfo as info on head.trno = info.trno
        left join client on head.client = client.client
        left join client as ag on ag.client = head.agent
        left join client as lead on lead.clientid = ag.parent
        left join plantype as pt on pt.line = head.planid and pt.plangrpid = head.plangrpid
        where head.trno = ? and num.doc=? and num.center=? " . $agentfilter;
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

      $acount = $this->coreFunctions->opentable("select count(*) as acount from transnum_picture where trno=?", [$head[0]->trno]);
      $hideobj = [];
      $labelobj = [];
      if ($config['params']['companyid'] == 34) {
        $attached = $this->coreFunctions->datareader("select title as value from transnum_picture  where trno=?", [$trno]);

        $lblattached_stat = $attached == "" ? true : false;
        $hideobj = ['lblattached' => $lblattached_stat];
        $hideheadergridbtns = ['tagreceived' => !$lblattached_stat, 'untagreceived' => $lblattached_stat];
        $labelobj = ['lblattached' => $acount[0]->acount . ' Attached Document(s)'];
      }


      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }

      if ($this->companysetup->getistodo($config['params'])) {
        $btndonetodo = $this->othersClass->checkdonetodo($config, $tablenum);
        $hideobj = ['donetodo' => !$btndonetodo];
      }


      return  ['head' => $head, 'griddata' => ['accounting' => []], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg, 'hideobj' => $hideobj, 'hideheadgridbtns' => $hideheadergridbtns, 'labelobj' => $labelobj];
    } else {
      $head[0]['trno'] = 0;
      $head[0]['docno'] = '';
      return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['accounting' => []], 'msg' => 'Data Head Fetched Failed, either somebody already deleted the transaction or you are not allowed to view the information...'];
    }
  }


  public function updatehead($config, $isupdate)
  {
    $head = $config['params']['head'];
    $data = [];
    $info = [];

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


    $data['ext'] = $head['ext'];

    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];

    $info['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $info['editby'] = $config['params']['user'];


    $info['trno'] = $head['trno'];
    $info['client'] = $head['bclient'];
    $info['clientname'] = $head['lname2'] . ', ' . $head['fname2'] . ' ' . $head['mname2'] . ' ' . $head['ext2'];
    $info['isplanholder'] = $head['isplanholder'];
    $info['issameadd'] = $head['issameadd'];
    $info['isbene'] = $head['isbene'];

    $info['fname'] = $head['fname2'];
    $info['lname'] = $head['lname2'];
    $info['mname'] = $head['mname2'];
    $info['ext'] = $head['ext2'];
    $info['gender'] = $head['gender'];
    $info['civilstat'] = $head['civilstatus'];

    $info['addressno'] = $head['raddressno'];
    $info['street'] = $head['rstreet'];
    $info['subdistown'] = $head['rsubdistown'];
    $info['city'] = $head['rcity'];
    $info['brgy'] = $head['rbrgy'];
    $info['province'] = $head['rprovince'];
    $info['country'] = $head['rcountry'];
    $info['zipcode'] = $head['rzipcode'];
    $info['address'] = $head['raddressno'] . ' ' . $head['rstreet'] . ' ' . $head['rsubdistown'] . ' ' . $head['rbrgy'] . ' ' . $head['rcity'] . ' ' . $head['rprovince'] . ' ' . $head['rcountry'] . ' ' . $head['rzipcode'];
    $info['paddressno'] = $head['paddressno'];
    $info['pstreet'] = $head['pstreet'];
    $info['psubdistown'] = $head['psubdistown'];
    $info['pcity'] = $head['pcity'];
    $info['pbrgy'] = $head['pbrgy'];
    $info['pprovince'] = $head['pprovince'];
    $info['pcountry'] = $head['pcountry'];
    $info['pzipcode'] = $head['pzipcode'];
    $info['paddress'] = $head['paddressno'] . ' ' . $head['pstreet'] . ' ' . $head['psubdistown'] . ' ' . $head['pbrgy'] . ' ' . $head['pcity'] . ' ' . $head['pprovince'] . ' ' . $head['pcountry'] . ' ' . $head['pzipcode'];
    $info['bday'] = $this->othersClass->sanitizekeyfield('bday',  $head['bday']);
    $info['nationality'] = $head['nationality'];
    $info['pob'] = $head['pob'];
    $info['ispassport'] = $head['ispassport'];
    $info['isdriverlisc'] = $head['isdriverlisc'];
    $info['isprc'] = $head['isprc'];
    $info['isseniorid'] = $head['isseniorid'];
    $info['isotherid'] = $head['isotherid'];
    $info['idno'] = $head['idno'];
    $info['expiration'] = $head['expiration'];
    $info['isemployment'] = $head['isemployment'];
    $info['isbusiness'] = $head['isbusiness'];
    $info['isinvestment'] = $head['isinvestment'];
    $info['isothersource'] = $head['isothersource'];
    $info['othersource'] = $head['othersource'];
    $info['isemployed'] = $head['isemployed'];
    $info['isselfemployed'] = $head['isselfemployed'];
    $info['isofw'] = $head['isofw'];
    $info['isretired'] = $head['isretired'];
    $info['iswife'] = $head['iswife'];

    $date = date_create($info['bday']);
    $now = date_create(date('Y-m-d'));
    $interval = date_diff($date, $now);
    $age = $interval->format('%y');

    if ($age >= 60) {
      $info['issenior'] = 1;
    } else {
      $info['issenior'] = 0;
    }

    $info['isnotemployed'] = $head['isnotemployed'];
    $info['tin'] = $head['tin'];
    $info['sssgsis'] = $head['sssgsis'];
    $info['lessten'] = $head['lessten'];
    $info['tenthirty'] = $head['tenthirty'];
    $info['thirtyfifty'] = $head['thirtyfifty'];
    $info['fiftyhundred'] = $head['fiftyhundred'];
    $info['hundredtwofifty'] = $head['hundredtwofifty'];
    $info['twofiftyfivehundred'] = $head['twofiftyfivehundred'];
    $info['fivehundredup'] = $head['fivehundredup'];
    $info['employer'] = $head['employer'];
    $info['otherplan'] = $head['otherplan'];

    $info['appref'] = $head['appref'];

    $info['isdp'] = $head['isdp'];
    $info['dp'] = $this->othersClass->sanitizekeyfield('dp',  $head['dp']);


    $info['ispf'] = $head['ispf'];
    $info['pf'] = $this->othersClass->sanitizekeyfield('pf',  $head['pf']);

    $data['dateid'] = $this->othersClass->sanitizekeyfield('dateid',  $head['dateid']);

    if ($head['issameadd'] == 1) {

      $info['paddressno'] = $info['addressno'];
      $info['pstreet'] = $info['street'];
      $info['psubdistown'] = $info['subdistown'];
      $info['pcity'] = $info['city'];
      $info['pbrgy'] = $info['brgy'];
      $info['pprovince'] = $info['province'];
      $info['pcountry'] = $info['country'];
      $info['pzipcode'] = $info['zipcode'];
      $info['paddress'] = $info['addressno'] . ' ' . $info['street'] . ' ' . $info['subdistown'] . ' ' . $info['brgy'] . ' ' . $info['city'] . ' ' . $info['province'] . ' ' . $info['country'] . ' ' . $info['zipcode'];
    }

    if ($head['isplanholder'] == 1) {

      $data['client'] = $info['client'];
      $data['clientname'] = $info['clientname'];
      $data['ext'] = $info['ext'];
      $data['fname'] = $info['fname'];
      $data['lname'] = $info['lname'];
      $data['mname'] = $info['mname'];

      $data['addressno'] = $info['addressno'];
      $data['street'] = $info['street'];
      $data['subdistown'] = $info['subdistown'];
      $data['city'] = $info['city'];
      $data['brgy'] = $info['brgy'];
      $data['province'] = $info['province'];
      $data['country'] = $info['country'];
      $data['zipcode'] = $info['zipcode'];
      $data['address'] = $info['addressno'] . ' ' . $info['street'] . ' ' . $info['subdistown'] . ' ' . $info['brgy'] . ' ' . $info['city'] . ' ' . $info['province'] . ' ' . $info['country'] . ' ' . $info['zipcode'];
    }


    if ($info['isdp'] == 1 && $info['dp'] == 0) {
      return ['status' => false, 'msg' => 'Saving FAILED, Downpayment is Required'];
    }


    if ($info['ispf'] == 1 && $info['pf'] == 0) {
      return ['status' => false, 'msg' => 'Saving FAILED, Processing Fee is Required'];
    }



    if ($isupdate) {
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
      // for info table
      $exist = $this->coreFunctions->getfieldvalue($this->info, "trno", "trno=?", [$head['trno']]);
        if (floatval($exist) <> 0) {
          $this->coreFunctions->sbcupdate($this->info, $info, ['trno' => $head['trno']]);
        } else {
          //$info['trno'] = $head['trno'];
          $this->coreFunctions->sbcinsert($this->info, $info);
        }
      
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head, $data);
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['client'] . ' - ' . $head['clientname']);

      // for info table
      $this->coreFunctions->sbcinsert($this->info, $info);
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

    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->info . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from beneficiary where trno=?', 'delete', [$trno]);
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

    if (!$this->othersClass->islocked($config)) {
      return ['status' => false, 'msg' => 'Posting failed. Not yet locked.'];
    }

    $isdp = $this->coreFunctions->getfieldvalue("eainfo", "isdp", "trno=?", [$trno]);
    $ispf = $this->coreFunctions->getfieldvalue("eainfo", "ispf", "trno=?", [$trno]);

    if (floatval($isdp) == 0) {
      return ['status' => false, 'msg' => 'Please check the downpayment.'];
    }

    if (floatval($ispf) == 0) {
      return ['status' => false, 'msg' => 'Please check the processing fee.'];
    }

    $plangrpid = $this->coreFunctions->getfieldvalue($this->head, "plangrpid", "trno=?", [$trno]);
    $amount = $this->coreFunctions->datareader("select pt.amount as value from plantype as pt left join " . $this->head . " as head on head.planid = pt.line and head.plangrpid = pt.plangrpid where head.trno = ?", [$trno]);

    if (!$this->othersClass->getplanlimit($plangrpid, floatval($amount), $trno)) {
      $allowoverride = $this->othersClass->checkAccess($config['params']['user'], 1729);
      if (!$allowoverride) {
        return ['status' => false, 'msg' => 'Posting failed. Above the plan limit.'];
      }
    }


    $isdp = $this->coreFunctions->getfieldvalue($this->info, "isdp", "trno=?", [$trno]);
    $ispf = $this->coreFunctions->getfieldvalue($this->info, "ispf", "trno=?", [$trno]);

    if ($isdp == 0 || $ispf == 0) {
      return ['status' => false, 'msg' => 'Posting failed. Check Downpayment or Processing Fee.'];
    }


    $client = $this->coreFunctions->getfieldvalue($this->head, "client", "trno=?", [$trno]);

    if ($client == '') {
      $this->createclient($config);
    }

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    if ($this->othersClass->isposted($config)) {
      return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
    }

    $qry = "insert into " . $this->hhead . "(trno,doc,docno,client,fname,mname,lname,ext,clientname,address,addressno,street,subdistown,city,brgy,country,zipcode,dateid,terms,otherterms,rem,voiddate,agent,yourref,ourref,contactno,
    contactno2,email,vattype,lockuser,lockdate,openby,users,createdate,createby,editby,editdate,viewby,viewdate,planid,tax,plangrpid,province)
    select trno,doc,docno,client,fname,mname,lname,ext,clientname,address,addressno,street,subdistown,city,brgy,country,zipcode,dateid,terms,otherterms,rem,voiddate,agent,yourref,ourref,contactno,contactno2,email,vattype,lockuser,
    lockdate,openby,users,createdate,createby,editby,editdate,viewby,viewdate,planid,tax,plangrpid,province
    from " . $this->head . " where trno=? limit 1";
    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($posthead) {

      $qry = "insert into " . $this->hinfo . "(trno,client,clientname,fname,mname,lname,ext,isplanholder,gender,civilstat,address,addressno,street,subdistown,brgy,city,country,zipcode, paddress,paddressno,pstreet,psubdistown,pcity,pbrgy,pcountry,pzipcode,bday,pob,nationality,rem,ispassport,isprc,isdriverlisc,isotherid,idno,expiration,isemployment,isinvestment,isbusiness,isothersource,othersource,isemployed,isselfemployed,isofw,isretired,iswife,isnotemployed,employer,tin,sssgsis,lessten,tenthirty,thirtyfifty,fiftyhundred,hundredtwofifty,twofiftyfivehundred,fivehundredup,otherplan,amount,editby,editdate,issameadd,isbene,issenior,province,pprovince,appref,isdp,dp,ispf,pf,isseniorid)
      select trno,client,clientname,fname,mname,lname,ext,isplanholder,gender,civilstat,address,addressno,street,subdistown,brgy,city,country,zipcode, paddress,paddressno,pstreet,psubdistown,pcity,pbrgy,pcountry,pzipcode,bday,pob,nationality,rem,ispassport,isprc,isdriverlisc,isotherid,idno,expiration,isemployment,isinvestment,isbusiness,isothersource,othersource,isemployed,isselfemployed,isofw,isretired,iswife,isnotemployed,employer,tin,sssgsis,lessten,tenthirty,thirtyfifty,fiftyhundred,hundredtwofifty,twofiftyfivehundred,fivehundredup,otherplan,amount,editby,editdate,issameadd,isbene,issenior,province,pprovince,appref,isdp,dp,ispf,pf,isseniorid
      from " . $this->info . " where trno=? limit 1";
      $postinfo = $this->coreFunctions->execqry($qry, 'insert', [$trno]);

      if ($postinfo) {

        $date = $this->othersClass->getCurrentTimeStamp();
        $data = ['postdate' => $date, 'postedby' => $config['params']['user']];
        $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->info . " where trno=?", "delete", [$trno]);
        $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
        //update plangrp bal
        $served = $this->coreFunctions->datareader("select sum(case info.issenior when 1 then pt.amount/1.12 else pt.amount end) as value from " . $this->hhead . " as head 
        left join plantype as pt on pt.line = head.planid and pt.plangrpid = head.plangrpid left join heainfo as info on info.trno = head.trno where head.plangrpid = " . $plangrpid);
        $this->coreFunctions->execqry("update plangrp set bal = amt - " . floatval($served) . " where line = " . $plangrpid);
        $this->othersClass->sbctransferlog($trno, $config, $this->htablelogs);
        return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
      } else {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting Info'];
      }
    } else {
      return ['status' => false, 'msg' => 'Error on Posting Head'];
    }
  } //end function

  public function unposttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    $lplan = $this->coreFunctions->datareader('select catrno as value from ' . $this->hhead . ' where trno=?', [$trno]);

    if (floatval($lplan) != 0) {
      return ['status' => false, 'msg' => 'Unpost FAILED, Already have agreement...'];
    }

    if (!$this->othersClass->isposted($config)) {
      return ['status' => false, 'msg' => 'Unpost FAILED, Already unposted...'];
    }

    $qry = "insert into " . $this->head . "(trno,doc,docno,client,fname,mname,lname,ext,clientname,address,addressno,street,subdistown,city,brgy,country,zipcode,dateid,terms,otherterms,rem,voiddate,agent,yourref,ourref,contactno,
    contactno2,email,vattype,lockuser,lockdate,openby,users,createdate,createby,editby,editdate,viewby,viewdate,planid,tax,plangrpid,province)
    select trno,doc,docno,client,fname,mname,lname,ext,clientname,address,addressno,street,subdistown,city,brgy,country,zipcode,dateid,terms,otherterms,rem,voiddate,agent,yourref,ourref,contactno,contactno2,email,vattype,lockuser,
    lockdate,openby,users,createdate,createby,editby,editdate,viewby,viewdate,planid,tax,plangrpid,province
    from " . $this->hhead . " where trno=? limit 1";
    $unposthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($unposthead) {

      $qry = "insert into " . $this->info . "(trno,client,clientname,fname,mname,lname,ext,isplanholder,gender,civilstat,address,addressno,street,subdistown,brgy,city,country,zipcode, paddress,paddressno,pstreet,psubdistown,pcity,pbrgy,pcountry,pzipcode,bday,pob,nationality,rem,ispassport,isprc,isdriverlisc,isotherid,idno,expiration,isemployment,isinvestment,isbusiness,isothersource,othersource,isemployed,isselfemployed,isofw,isretired,iswife,isnotemployed,employer,tin,sssgsis,lessten,tenthirty,thirtyfifty,fiftyhundred,hundredtwofifty,twofiftyfivehundred,fivehundredup,otherplan,amount,editby,editdate,issameadd,issenior,province,pprovince,appref,isdp,dp,ispf,pf,isseniorid)
      select trno,client,clientname,fname,mname,lname,ext,isplanholder,gender,civilstat,address,addressno,street,subdistown,brgy,city,country,zipcode, paddress,paddressno,pstreet,psubdistown,pcity,pbrgy,pcountry,pzipcode,bday,pob,nationality,rem,ispassport,isprc,isdriverlisc,isotherid,idno,expiration,isemployment,isinvestment,isbusiness,isothersource,othersource,isemployed,isselfemployed,isofw,isretired,iswife,isnotemployed,employer,tin,sssgsis,lessten,tenthirty,thirtyfifty,fiftyhundred,hundredtwofifty,twofiftyfivehundred,fivehundredup,otherplan,amount,editby,editdate,issameadd,issenior,province,pprovince,appref,isdp,dp,ispf,pf,isseniorid
      from " . $this->hinfo . " where trno=? limit 1";
      $unpostinfo = $this->coreFunctions->execqry($qry, 'insert', [$trno]);

      if ($unpostinfo) {

        $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null where trno=?", 'update', [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hinfo . " where trno=?", "delete", [$trno]);
        $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
        //update plangrp bal
        $plangrpid = $this->coreFunctions->getfieldvalue($this->head, "plangrpid", "trno=?", [$trno]);
        $served = $this->coreFunctions->datareader("select sum(case info.issenior when 1 then pt.amount/1.12 else pt.amount end) as value from heahead as head 
         left join plantype as pt on pt.line = head.planid and pt.plangrpid = head.plangrpid left join heainfo as info on info.trno = head.trno where head.plangrpid = " . $plangrpid);
        $this->coreFunctions->execqry("update plangrp set bal = amt - " . floatval($served) . " where line = " . $plangrpid);
        return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
      } else {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Unposting Info'];
      }
    } else {
      return ['status' => false, 'msg' => 'Error on Posting Head'];
    }
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

      case 'generateclient':
        return $this->createclient($config);
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

    $client = $this->coreFunctions->getfieldvalue($this->head, "client", "trno=?", [$trno]);

    if ($client != '') {
      return ['status' => false, 'msg' => 'Already have customer profile.'];
    }

    $client = $this->coreFunctions->getfieldvalue($this->head, "concat(lname,fname,mname)", "trno=?", [$trno]);
    if ($client == '') {
      return ['status' => false, 'msg' => 'Please complete details for payor.'];
    }

    $client = $this->coreFunctions->getfieldvalue("eainfo", "concat(lname,fname,mname)", "trno=?", [$trno]);
    if ($client == '') {
      return ['status' => false, 'msg' => 'Please complete details for plan holder.'];
    }

    $clientcode = $this->getnewclient($config); // create customer



    $qry = "select head.clientname, head.address, head.contactno, head.terms, head.agent,head.email,head.fname,head.lname,head.mname,head.addressno,
    head.street,head.subdistown,head.city,head.country,head.zipcode,i.isplanholder,i.tin,head.ext,i.issenior,head.brgy
    from " . $this->head . " as head left join eainfo as i on i.trno = head.trno where head.trno = ? limit 1 ";
    $res = $this->coreFunctions->opentable($qry, [$trno]);



    $data['client'] = $clientcode;
    $data['clientname'] = $res[0]->lname . ', ' . $res[0]->fname . ' ' . $res[0]->mname . ' ' . $res[0]->ext;
    $data['addr'] = $res[0]->addressno . ' ' . $res[0]->street . ' ' . $res[0]->subdistown . ' Brgy.' . $res[0]->brgy . ' ' . $res[0]->city . ' ' . $res[0]->country . ' ' . $res[0]->zipcode;
    $data['tel'] = $res[0]->contactno;
    $data['terms'] = $res[0]->terms;
    $data['agent'] = $res[0]->agent;
    $data['status'] = 'ACTIVE';
    $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['createby'] = $config['params']['user'];
    $data['iscustomer'] = 1;
    $data['center'] = $center;
    $data['email'] = $res[0]->email;
    $data['brgy'] = $res[0]->brgy;
    if ($res[0]->isplanholder == 1) {
      $data['tin'] = $res[0]->tin;
      $data['issenior'] = $res[0]->issenior;
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

    $this->coreFunctions->execqry("update " . $this->head . " set client = ?,clientname = ? where trno = ?", 'update', [$clientcode, $res[0]->lname . ', ' . $res[0]->fname . ' ' . $res[0]->mname . ' ' . $res[0]->ext, $trno]);
    if ($res[0]->isplanholder == 1) {
      $this->coreFunctions->execqry("update eainfo set client = ?,clientname = ? where trno = ?", 'update', [$clientcode, $res[0]->lname . ', ' . $res[0]->fname . ' ' . $res[0]->mname . ' ' . $res[0]->ext, $trno]);
    }

    return true;
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

  public function reportsetup($config)
  {
    $isposted = $this->othersClass->isposted($config);
    if (!$isposted) return ['status' => false, 'msg' => 'Application not yet approved.'];
    $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
    $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);
    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';

    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  }

  public function reportdata($config)
  {
    $this->logger->sbcviewreportlog($config);
    $companyid = $config['params']['companyid'];

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
