<?php

namespace App\Http\Classes\modules\masterfile;

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

class financingpartner
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Financing Partner';
  public $gridname = 'accounting';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $head = 'client';
  public $prefix = 'FP';
  public $tablelogs = 'client_log';
  public $tablelogs_del = 'del_client_log';
  private $stockselect;
  public $tagging = "iscustomer";

  private $fields = [
    'client', 'clientname', 'addr', 'ship', 'start', 'status', 'tin',
    'terms', 'agent', 'groupid', 'rev', 'crlimit', 'class', 'bstyle',
    'category', 'area', 'province', 'region', 'grpcode', 'zipcode', 'forexid',
    'contact', 'attention', 'acct', 'mobile', 'owner', 'prefix', 'tel', 'fax', 'tel2', 'email', 'iscustomer', 'issupplier', 'isagent', 'iswarehouse', 'isemployee', 'isinactive', 'isdepartment',
    'picture', 'isnocrlimit', 'truckid', 'disc', 'quota', 'comm', 'territory', 'type', 'crtype',
    'rem', 'crdays', 'tax', 'vattype', 'activity', 'industry', 'purchaser',
    'registername', 'issynced', 'isvatzerorated', 'isnotarizedcert', 'alias', 'officialemail', 'officialwebsite', 'rem2', 'position', 'brgy', 'ass', 'issenior', 'industryid', 'bday', 'accountid', 'sex', 'isfp'
  ];
  private $except = ['clientid'];
  private $blnfields = ['iscustomer', 'issupplier', 'isagent', 'iswarehouse', 'isemployee', 'isinactive', 'isdepartment', 'isnocrlimit', 'issynced', 'isvatzerorated', 'isnotarizedcert', 'issenior', 'isfp'];

  private $clinfo = ['bplace', 'citizenship', 'civilstatus', 'father', 'mother', 'height', 'weight'];
  private $acctg = [];
  public $showfilteroption = false;
  public $showfilter = false;
  public $showcreatebtn = true;
  private $reporter;


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
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 4653,
      'edit' => 4654,
      'new' => 4655,
      'save' => 4656,
      'delete' => 4657,
      'print' => 4658,
      'load' => 4652,
      'artab' => 4659,
      'aptab' => 4660,
      'pdstab' => 4661,
      'checkreturntab' => 4662,
      'invtab' => 4663,
    );

    return $attrib;
  }

  public function createdoclisting($config)
  {
    $companyid = $config['params']['companyid'];

    $action = 0;
    $listclient = 1;
    $listclientname = 2;
    $listgroup = 3;
    $listaddr = 4;
    $tin = 5;
    $listcategory = 6;
    $notes = 7;
    $tel = 8;
    $fax = 9;
    $contact = 10;
    $listbrgy = 11;
    $listareacode = 12;
    $listarea = 13;
    $listprovince = 14;
    $listregion = 15;

    $getcols = ['action', 'listclient', 'listclientname', 'listgroup', 'listaddr', 'tin', 'listcategory', 'notes', 'tel', 'fax', 'contact', 'listbrgy', 'listareacode', 'listarea', 'listprovince', 'listregion'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';

    $cols[$listgroup]['type'] = 'coldel';
    $cols[$tin]['type'] = 'coldel';
    $cols[$tel]['type'] = 'coldel';
    $cols[$fax]['type'] = 'coldel';
    $cols[$contact]['type'] = 'coldel';
    $cols[$listarea]['type'] = 'coldel';
    $cols[$listprovince]['type'] = 'coldel';
    $cols[$listregion]['type'] = 'coldel';
    $cols[$listareacode]['type'] = 'coldel';
    $cols[$listbrgy]['type'] = 'coldel';

    $cols = $this->tabClass->delcollisting($cols);
    return $cols;
  }

  public function paramsdatalisting($config)
  {
    return [];
  }

  public function loaddoclisting($config)
  {
    $date1 = $config['params']['date1'];
    $date2 = $config['params']['date2'];
    $itemfilter = $config['params']['itemfilter'];
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $search = $config['params']['search'];
    $compatiblefield = ", '' as compatible";
    $company = $config['params']['companyid'];
    $limit = "limit " . $this->companysetup->getmasterlimit($config['params']);
    $condition = "";
    $grp = "";
    $leftjoin = "";
    $searchby = isset($config['params']['doclistingparam']['selectprefix']) ? $config['params']['doclistingparam']['selectprefix'] : '';

    $searchfield = ['client.client', 'client.clientname', 'client.addr', 'client.rem', 'category.cat_name', 'client.tin'];

    switch ($company) {
      default:
        $address = "client.addr";
        $leftjoin = "";
        $searchfield = ['client.client', 'client.clientname', 'client.addr', 'client.rem', 'category.cat_name', 'client.tin'];
        if ($search != "") {
          $limit = "";
        }
        break;
    }

    if (isset($config['params']['doclistingparam'])) {
      $test = $config['params']['doclistingparam'];
      if ($test['selectprefix'] != "") {
        switch ($test['selectprefix']) {
          case 'Company Name':
            $searchfield = ['client.clientname'];
            break;
          case 'Contact Person(First Name)':
            $leftjoin .= " left join contactperson as cp on cp.clientid = client.clientid  ";
            $compatiblefield = ", group_concat(distinct cp.fname) as compatible";
            $searchfield = ['cp.fname'];
            break;
          case 'Contact Person(Last Name)':
            $leftjoin .= " left join contactperson as cp on cp.clientid = client.clientid  ";
            $compatiblefield = ", group_concat(distinct cp.lname) as compatible";
            $searchfield = ['cp.lname'];
            break;
          case 'Branch':
            $searchfield = ['client.groupid'];
            break;
          case 'Organizational Structure':
            $searchfield = ['client.type'];
            break;
          case 'TIN':
            $searchfield = ['client.tin'];
            break;
          case 'Terms':
            $searchfield = ['client.terms'];
            break;
          case 'VAT Type':
            $searchfield = ['client.vattype'];
            break;
          case 'Agent':
            $searchfield = ['agent.clientname'];
            break;
          case 'Credit Limit':
            $searchfield = ['client.crlimit'];
            break;
          case 'Start Date':
            $searchfield = ['client.start'];
            break;
          case 'City/Town':
            $leftjoin .= " left join billingaddr as ba on ba.clientid = client.clientid  ";
            $compatiblefield = ", group_concat(ba.city) as compatible";
            $searchfield = ['ba.city'];
            break;
          case 'Province':
            $leftjoin .= " left join billingaddr as ba on ba.clientid = client.clientid  ";
            $compatiblefield = ", group_concat(ba.province) as compatible";
            $searchfield = ['ba.province'];
            break;
        }
      }
    }

    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }

    if ($searchby != '') {
      $grp = " group by client.clientid,client.client,client.clientname," . $address . ",
    category.cat_name , client.rem ,client.groupid,client.tin";
    }

    $allowall = $this->othersClass->checkAccess($config['params']['user'], 4077);
    $adminid = $config['params']['adminid'];


    $qry = "select client.clientid,client.client,client.clientname," . $address . ",
    category.cat_name as category, client.rem as notes,client.groupid,client.tin " . $compatiblefield . "
    from client 
    left join category_masterfile as category on category.cat_id=client.category " . $leftjoin . "
    where client.isfp=1 " . $condition .  $filtersearch .  $grp . "  
    order by client " . $limit;
    $data = $this->coreFunctions->opentable($qry);
    return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
  }

  public function createHeadbutton($config)
  {
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    $btns = array(
      'load',
      'new',
      'save',
      'delete',
      'cancel',
      'print',
      'logs',
      'edit',
      'backlisting',
      'toggleup',
      'toggledown'
    );

    if ($systemtype == 'AIMSPOS' || $systemtype == 'MISPOS') {
      if (($key = array_search('delete', $btns)) !== false) {
        unset($btns[$key]);
      }
    }

    if ($this->companysetup->getclientlength($config['params']) != 0) {
      array_push($btns, 'others');
    }


    $buttons = $this->btnClass->create($btns);

    $buttons['others']['items'] = [
      'first' => ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']],
      'prev' => ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']],
      'next' => ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']],
      'last' => ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']],
    ];

    if ($this->companysetup->getisshowmanual($config['params'])) {
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => 'customer', 'title' => 'CUSTOMER_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
    }

    return $buttons;
  } // createHeadbutton


  public function createtab2($access, $config)
  {
    $companyid = $config['params']['companyid'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    $skutab_access = $this->othersClass->checkAccess($config['params']['user'], 2734);
    $ar_access = $this->othersClass->checkAccess($config['params']['user'], 2735);
    $ap_access = $this->othersClass->checkAccess($config['params']['user'], 2736);
    $pdc_access = $this->othersClass->checkAccess($config['params']['user'], 2737);
    $checkreturntab_access = $this->othersClass->checkAccess($config['params']['user'], 2738);
    $invtab_access = $this->othersClass->checkAccess($config['params']['user'], 2739);
    $defaultaddresstab_access = $this->othersClass->checkAccess($config['params']['user'], 2740);
    $setaddresstab_access = $this->othersClass->checkAccess($config['params']['user'], 2741);
    $unpaidarap_access = $this->othersClass->checkAccess($config['params']['user'], 2992);
    $contactperson_access = $this->othersClass->checkAccess($config['params']['user'], 3744);

    $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrysku', 'label' => 'SKU']];
    $sku = $this->tabClass->createtab($tab, []);

    $ar = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewar']];
    $ap = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewap']];
    $pdc = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewpdc']];
    $rc = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewrc']];
    $inv = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewcustomerinv']];
    $user = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewuseraccount']];
    $billshipdefault = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewbillingdefault']];
    $unpaidarap = ['customform' => ['action' => 'customform', 'lookupclass' => 'unpaidar']];

    //3/2/2023 FPY Testing
    $Financing = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewfinancing']];


    $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrybillingaddr', 'label' => 'BILLING/SHIPPING']];
    $billship = $this->tabClass->createtab($tab, []);

    $return = [];


    if ($skutab_access != 0) {
      $return['SKU ENTRY'] = ['icon' => 'fa fa-list-ul', 'tab' => $sku];
    }

    if ($ar_access != 0) {
      $return['ACCOUNT RECEIVABLE HISTORY'] = ['icon' => 'fa fa-coins', 'customform' => $ar];
    }

    $return['FINANCING'] = ['icon' => 'fa fa-coins', 'customform' => $Financing];

    if ($ap_access != 0) {
      $return['ACCOUNT PAYABLE HISTORY'] = ['icon' => 'fa fa-coins', 'customform' => $ap];
    }

    if ($pdc_access != 0) {
      $return['POSTDATED CHECKS HISTORY'] = ['icon' => 'fa fa-money-check', 'customform' => $pdc];
    }

    if ($checkreturntab_access) {
      $return['RETURNED CHECKS HISTORY'] = ['icon' => 'fa fa-money-check', 'customform' => $rc];
    }

    if ($invtab_access != 0) {
      $return['INVENTORY HISTORY'] = ['icon' => 'fa fa-list-ul', 'customform' => $inv];
    }

    if ($unpaidarap_access != 0) {
      $return['UNPAID AR/AP'] = ['icon' => 'fa fa-coins', 'customform' => $unpaidarap];
    }


    if ($defaultaddresstab_access != 0) {
      $return['DEFAULT SHIPPING/BILLING ADDRESS'] = ['icon' => 'fa fa-map-marker-alt', 'customform' => $billshipdefault];
    }

    if ($setaddresstab_access != 0) {
      $return['SHIPPING/BILLING ADDRESS SETUP'] = ['icon' => 'fa fa-address-book', 'tab' => $billship];
    }

    return $return;
  }

  public function createTab($access, $config)
  {
    $tab = [];

    return $tab;
  }

  public function createtabbutton($config)
  {
    return [];
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $limitview = $this->othersClass->checkAccess($config['params']['user'], 3745);
    $editlimit = $this->othersClass->checkAccess($config['params']['user'], 3768);
    $editnotes = $this->othersClass->checkAccess($config['params']['user'], 3769);


    $fields = ['client', 'clientname', 'addr', 'bday', 'bplace', 'citizenship', 'civilstatus', 'father', 'mother', 'pricegroup'];

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.label', 'Financing Partner Code');
    data_set($col1, 'client.class', 'csclient sbccsenablealways');
    data_set($col1, 'client.lookupclass', 'customer');
    data_set($col1, 'client.action', 'lookupledgerclient');
    data_set($col1, 'clientname.type', 'cinput');
    data_set($col1, 'clientname.required', true);
    data_set($col1, 'addr.type', 'cinput');
    data_set($col1, 'email.type', 'cinput');
    data_set($col1, 'tel.type', 'cinput');
    data_set($col1, 'fax.type', 'cinput');
    data_set($col1, 'tel2.type', 'cinput');

    $fields = ['sex', 'height', 'position', 'weight', 'tel2', 'accountid', 'tin', 'clientstatus', 'terms', 'dagentname'];

    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'accountid.label', 'LTO Client ID');
    data_set($col2, 'position.label', 'Occupation');
    data_set($col2, 'terms.lookupclass', 'ledgerterms');
    data_set($col2, 'tin.type', 'cinput');
    data_set($col2, 'bstyle.type', 'cinput');
    data_set($col2, 'purchaser.type', 'cinput');
    data_set($col2, 'registername.type', 'cinput');
    data_set($col2, 'groupid.lookupclass', 'lookupclientgroupledger');
    data_set($col2, 'groupid.action', 'lookupclientgroupledger');
    data_set($col2, 'groupid.class', 'csgroup');
    data_set($col2, 'groupid.readonly', false);
    data_set($col2, 'contact.type', 'cinput');
    data_set($col2, 'bal.type', 'cinput');
    data_set($col2, 'bal.label', 'Credit Limit');
    data_set($col2, 'bal.name', 'crlimit');
    data_set($col2, 'dsalesacct.label', 'Sales Account');
    data_set($col2, 'crtype.action', 'lookuprandom');
    data_set($col2, 'crtype.lookupclass', 'lookup_crtype');
    data_set($col2, 'crtype.readonly', true);

    $fields = ['dcategory', 'area', 'province', 'region', 'dparentcode', 'zipcode'];

    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'zipcode.type', 'cinput');
    data_set($col3, 'disc.label', 'Mark Up');
    data_set($col3, 'dcategory.name', 'categoryname');

    $fields = ['picture', ['iscustomer', 'issupplier'], ['isfp', 'isagent'], ['isemployee', 'isinactive'], ['isdepartment', 'iswarehouse']];

    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'iscustomer.class', 'csiscustomer sbccsreadonly');
    data_set($col4, 'isfp.class', 'csisfp sbccsreadonly');
    data_set($col4, 'picture.lookupclass', 'client');
    data_set($col4, 'picture.folder', 'customer');
    data_set($col4, 'picture.table', 'client');
    data_set($col4, 'picture.fieldid', 'clientid');

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function newclient($config)
  {
    $data = [];
    $data[0]['clientid'] = 0;
    $data[0]['client'] = $config['newclient'];
    $data[0]['clientname'] = '';
    $data[0]['addr'] = '';
    $data[0]['ship'] = '';
    $data[0]['start'] = $this->othersClass->getCurrentDate();
    $data[0]['bday'] = $this->othersClass->getCurrentDate();
    $data[0]['status'] = 'ACTIVE';
    $data[0]['terms'] = '';
    $data[0]['contact'] = '';
    $data[0]['attention'] = '';
    $data[0]['acct'] = '';
    $data[0]['mobile'] = '';
    $data[0]['owner'] = '';
    $data[0]['prefix'] = '';
    $data[0]['agent'] = '';
    $data[0]['agentname'] = '';
    $data[0]['tin'] = '';
    $data[0]['groupid'] = '';
    $data[0]['rev'] = '';
    $data[0]['acnoname'] = '';
    $data[0]['ass'] = '';
    $data[0]['assetname'] = '';
    $data[0]['crlimit'] = '0';
    $data[0]['class'] = 'R';
    $data[0]['bstyle'] = '';
    $data[0]['dcategory'] = '';
    $data[0]['category'] = '';
    $data[0]['categoryname'] = '';
    $data[0]['area'] = '';
    $data[0]['province'] = '';
    $data[0]['region'] = '';
    $data[0]['grpcode'] = '';
    $data[0]['parentname'] = '';
    $data[0]['zipcode'] = '';
    $data[0]['tel'] = '';
    $data[0]['fax'] = '';
    $data[0]['tel2'] = '';
    $data[0]['email'] = '';
    $data[0]['forexid'] = '0';
    $data[0]['cur'] = '';
    $data[0]['picture'] = '';
    $data[0]['truckid'] = 0;
    $data[0]['forwarder'] = '';
    $data[0]['disc'] = '';
    $data[0]['quota'] = '0.00';
    $data[0]['comm'] = '0.00';
    $data[0]['territory'] = '';
    $data[0]['type'] = '';
    $data[0]['crtype'] = '';
    $data[0]['crdays'] = '';
    $data[0]['rem'] = '';
    $data[0]['rem2'] = '';
    $data[0]['tax'] = '0';
    $data[0]['activity'] = '';
    $data[0]['industry'] = '';
    $data[0]['vattype'] = 'NON-VATABLE';
    $data[0]['purchaser'] = '';
    $data[0]['registername'] = '';
    $data[0]['isvatzerorated'] = '0';
    $data[0]['isnotarizedcert'] = '0';
    $data[0]['alias'] = '';
    $data[0]['officialemail'] = '';
    $data[0]['officialwebsite'] = '';
    $data[0]['position'] = '';
    $data[0]['areacode'] = '';
    $data[0]['brgy'] = '';
    $data[0]['industryid'] = 0;
    $data[0]['bplace'] = '';
    $data[0]['citizenship'] = '';
    $data[0]['civilstatus'] = '';
    $data[0]['father'] = '';
    $data[0]['mother'] = '';
    $data[0]['height'] = '';
    $data[0]['weight'] = '';
    $data[0]['sex'] = '';
    $data[0]['accountid'] = '';

    $data[0]['isnocrlimit'] = '0';
    $data[0]['iscustomer'] = '1';
    $data[0]['issupplier'] = '0';
    $data[0]['isagent'] = '0';
    $data[0]['iswarehouse'] = '0';
    $data[0]['isemployee'] = '0';
    $data[0]['isinactive'] = '0';
    $data[0]['isdepartment'] = '0';
    $data[0]['issynced'] = '0';
    $data[0]['issenior'] = '0';
    $data[0]['isfp'] = '1';

    return  ['head' => $data, 'islocked' => false, 'isposted' => false, 'status' => true, 'isnew' => true, 'msg' => 'Ready for New Ledger'];
  }

  public function stockstatusposted($config)
  {
    $action = $config['params']['action'];
    switch ($action) {
      case 'navigation':
        return $this->othersClass->navigatedocno($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }


  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $clientid = $config['params']['clientid'];
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];
    if ($clientid == 0) {
      $clientid = $this->othersClass->readprofile($doc, $config);
      if ($clientid == 0) {
        $clientid = $this->coreFunctions->datareader("select clientid as value from client where isfinancing=1 and center=? order by clientid desc limit 1", [$center]);
      }
      $config['params']['clientid'] = $clientid;
    } else {
      $this->othersClass->checkprofile($doc, $clientid, $config);
    }
    $center = $config['params']['center'];
    $head = [];
    $fields = 'client.clientid, client.client as docno';

    foreach ($this->fields as $key => $value) {
      $fields = $fields . ',client.' . $value;
    }

    foreach ($this->clinfo as $key2 => $value2) {
      $fields = $fields . ',info.' . $value2;
    }

    $allowall = $this->othersClass->checkAccess($config['params']['user'], 4077);
    $adminid = $config['params']['adminid'];
    $leftjoin = "";
    $condition = "";

    $qryselect = "select " . $fields . ", ifnull(a.clientname, '') as agentname, ifnull(coa.acnoname, '') as acnoname, ifnull(ar.acnoname, '') as assetname,
        ifnull(parentcode.clientname, '') as parentname,
        ifnull(category.cat_name, '') as categoryname,
        ifnull(forex.cur, '') as cur,
        ifnull(forwarder.clientid, 0) as truckid,
        ifnull(forwarder.clientname, '') as forwarder";

    $qry = $qryselect . " from client
        left join client as a on a.client=client.agent
        left join coa as coa on coa.acno = client.rev
        left join coa as ar on ar.acno = client.ass
        left join client as parentcode on client.grpcode = parentcode.client
        left join category_masterfile as category on client.category = category.cat_id
        left join forex_masterfile as forex on forex.line = client.forexid
        left join client as forwarder on forwarder.clientid = client.truckid 
        left join reqcategory as rc on client.industryid = rc.line " . $leftjoin . "
        left join clientinfo as info on info.clientid = client.clientid
        where client.clientid = ? and client.isfp = 1 " . $condition;

    $head = $this->coreFunctions->opentable($qry, [$clientid]);
    if (!empty($head)) {
      foreach ($this->blnfields as $key => $value) {
        if ($head[0]->$value) {
          $head[0]->$value = "1";
        } else
          $head[0]->$value = "0";
      }
      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['clientid' => $clientid]);
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }
      return  ['head' => $head, 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => false, 'qq' => $config['params']['clientid']];
    } else {
      $head[0]['clientid'] = 0;
      $head[0]['client'] = '';
      $head[0]['clientname'] = '';
      return ['status' => false, 'isnew' => true, 'head' => $head, 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified...'];
    }
  }




  public function updatehead($config, $isupdate)
  {
    $head = $config['params']['head'];
    $center = $config['params']['center'];
    $data = [];
    $clientinfo = [];
    $companyid = $config['params']['companyid'];
    if ($isupdate) {
      unset($this->fields[0]);
    }
    $clientid = 0;
    $msg = '';


    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key], 'CUSTOMER', $companyid);
        } //end if
      }
    }

    foreach ($this->clinfo as $key) {
      if (!in_array($key, $this->except)) {
        if (array_key_exists($key, $head)) {
          $clientinfo[$key] = $head[$key];
          $clientinfo[$key] = $this->othersClass->sanitizekeyfield($key, $clientinfo[$key]);
        }
      } //end if    
    }

    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    $data['dlock'] = $this->othersClass->getCurrentTimeStamp();

    if ($isupdate) {
      $this->coreFunctions->sbcupdate('client', $data, ['clientid' => $head['clientid']]);
      $clientid = $head['clientid'];
      array_push($this->fields, 'client');
      //info
      $exist = $this->coreFunctions->getfieldvalue("clientinfo", "clientid", "clientid=?", [$clientid], '', true);
      if ($exist == 0) {
        $clientinfo['clientid'] = $clientid;
        $this->coreFunctions->sbcinsert("clientinfo", $clientinfo);
      } else {
        $clientinfo['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $clientinfo['editby'] = $config['params']['user'];
        $this->coreFunctions->sbcupdate('clientinfo', $clientinfo, ['clientid' => $head['clientid']]);
      }
    } else {
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $data['iscustomer'] = 1;
      $data['center'] = $center;
      $exist = $this->coreFunctions->getfieldvalue("client", "clientname", "clientname = ? and iscustomer =1", [$head['clientname']]);

      if (strlen(($exist)) != 0) {
        return ['status' => false, 'msg' => 'This customer already exist.', 'clientid' => $clientid];
      } else {
        $clientid = $this->coreFunctions->insertGetId('client', $data);
        if (!empty($clientinfo)) {
          $clientinfo['clientid'] = $clientid;
          $this->coreFunctions->sbcinsert("clientinfo", $clientinfo);
        }

        $this->logger->sbcwritelog($clientid, $config, 'CREATE', $clientid . ' - ' . $head['client'] . ' - ' . $head['clientname']);
      }
    }

    return ['status' => $msg == '' ? true : false, 'msg' => $msg, 'clientid' => $clientid];
  } // end function

  public function getlastclient($pref)
  {
    $length = strlen($pref);
    $return = '';
    if ($length == 0) {
      $return = $this->coreFunctions->datareader('select client as value from client where  isfp=1 order by client desc limit 1');
    } else {
      $return = $this->coreFunctions->datareader('select client as value from client where  isfp=1 and left(client,?)=? order by client desc limit 1', [$length, $pref]);
    }
    return $return;
  }

  public function deletetrans($config)
  {
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $clientid = $config['params']['clientid'];
    $doc = $config['params']['doc'];
    $client = $this->coreFunctions->getfieldvalue('client', 'client', 'clientid=?', [$clientid]);
    $qry = "select lahead.trno as value from lahead where client=?
            union all
            select glhead.trno as value from glhead where clientid=?
            union all
            select sohead.trno as value from sohead where client=?
            union all
            select hsohead.trno as value from hsohead where client=? 
            union all
            select trno as value from vrstock where clientid=?
            union all
            select trno as value from hvrstock where clientid=?
            union all
            select trno as value from eahead where client=?
            union all
            select trno as value from heahead where client=?
            union all
            select trno as value from eainfo where client=?
            union all
            select trno as value from heainfo where client=?
            union all
            select trno as value from lphead where client=?
            union all
            select trno as value from hlphead where client=? limit 1
            
            ";
    $count = $this->coreFunctions->datareader($qry, [$client, $clientid, $client, $client, $clientid, $clientid, $client, $client, $client, $client, $client, $client]);

    if (($count != '')) {
      return ['clientid' => $clientid, 'status' => false, 'msg' => 'Already have transaction...'];
    }

    $qry = "select clientid as value from client where clientid<? and isfp=1 order by clientid desc limit 1 ";
    $clientid2 = $this->coreFunctions->datareader($qry, [$clientid]);
    $this->coreFunctions->execqry('delete from client where clientid=?', 'delete', [$clientid]);
    $this->coreFunctions->execqry('delete from clientinfo where clientid=?', 'delete', [$clientid]);
    $this->logger->sbcdel_log($clientid, $config, $client);
    $this->othersClass->deleteattachments($config); // attachment delete
    return ['clientid' => $clientid2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

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
    $this->logger->sbcviewreportlog($config);
    $data = app($this->companysetup->getreportpath($config['params']))->generateResult($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
