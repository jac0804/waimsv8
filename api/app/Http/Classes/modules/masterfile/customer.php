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
use App\Http\Classes\sbcscript\sbcscript;

class customer
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'CUSTOMER LEDGER';
  public $gridname = 'accounting';
  private $companysetup;
  private $sbcscript;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $head = 'client';
  public $prefix = 'CL';
  public $tablelogs = 'client_log';
  public $tablelogs_del = 'del_client_log';
  private $stockselect;
  public $tagging = "iscustomer";

  private $fields = [
    'client',
    'clientname',
    'addr',
    'ship',
    'start',
    'status',
    'tin',
    'terms',
    'agent',
    'groupid',
    'rev',
    'crlimit',
    'class',
    'bstyle',
    'category',
    'area',
    'province',
    'region',
    'grpcode',
    'zipcode',
    'forexid',
    'contact',
    'attention',
    'acct',
    'mobile',
    'owner',
    'prefix',
    'tel',
    'fax',
    'tel2',
    'email',
    'iscustomer',
    'issupplier',
    'isagent',
    'iswarehouse',
    'isemployee',
    'isinactive',
    'isdepartment',
    'picture',
    'isnocrlimit',
    'truckid',
    'disc',
    'quota',
    'comm',
    'territory',
    'type',
    'crtype',
    'rem',
    'crdays',
    'tax',
    'vattype',
    'activity',
    'industry',
    'purchaser',
    'registername',
    'issynced',
    'isvatzerorated',
    'isnotarizedcert',
    'alias',
    'officialemail',
    'officialwebsite',
    'rem2',
    'position',
    'brgy',
    'ass',
    'issenior',
    'industryid',
    'bday',
    'accountid',
    'sex',
    'charge1',
    'center'
  ];
  private $except = ['clientid'];
  private $blnfields = ['iscustomer', 'issupplier', 'isagent', 'iswarehouse', 'isemployee', 'isinactive', 'isdepartment', 'isnocrlimit', 'issynced', 'isvatzerorated', 'isnotarizedcert', 'issenior'];

  private $clinfo = ['bplace', 'citizenship', 'civilstatus', 'father', 'mother', 'height', 'weight', 'fname', 'mname', 'lname'];
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
    $this->sbcscript = new sbcscript;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 22,
      'edit' => 23,
      'new' => 24,
      'save' => 25,
      'change' => 26,
      'delete' => 27,
      'print' => 28,
      'load' => 21,
      'skutab' => 2734,
      'artab' => 2735,
      'aptab' => 2736,
      'pdstab' => 2737,
      'checkreturntab' => 2738,
      'invtab' => 2739,
      'defaultaddresstab' => 2740,
      'setupaddresstab' => 2741
    );

    return $attrib;
  }

  public function sbcscript($config)
  {
    return $this->sbcscript->customer($config);
  }

  public function createdoclisting($config)
  {
    $companyid = $config['params']['companyid'];
    if ($companyid == 10 || $companyid == 12) { //afti & afti usd
      $this->prefix = "C";
    }
    $getcols = ['action', 'listclient', 'listclientname', 'listgroup', 'listaddr', 'shipto', 'tin', 'listcategory', 'notes', 'tel', 'fax', 'contact', 'listbrgy', 'agentname', 'listareacode', 'listarea', 'listprovince', 'listregion'];
    foreach ($getcols as $key => $value) {
      $$value = $key;
    }

    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';

    if ($companyid == 26) { //bee healthy
      $this->showcreatebtn = false;
    }

    switch ($config['params']['companyid']) {
      case 10: //afti
      case 12: //afti usd
        $cols[$listcategory]['label'] = 'Business Style';
        $cols[$listgroup]['label'] = 'Branch';
        $cols[$tel]['type'] = 'coldel';
        $cols[$fax]['type'] = 'coldel';
        $cols[$contact]['type'] = 'coldel';
        $cols[$listarea]['type'] = 'coldel';
        $cols[$listprovince]['type'] = 'coldel';
        $cols[$listregion]['type'] = 'coldel';
        $cols[$listareacode]['type'] = 'coldel';
        $cols[$listbrgy]['type'] = 'coldel';
        $cols[$listaddr]['type'] = 'coldel';
        $cols[$agentname]['type'] = 'coldel';
        break;
      case 24: //goodfound
        $cols[$listcategory]['type'] = 'coldel';
        $cols[$notes]['type'] = 'coldel';
        $cols[$contact]['type'] = 'coldel';
        $cols[$tel]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$listbrgy]['type'] = 'coldel';
        $cols[$agentname]['type'] = 'coldel';
        break;
      case 32: //3m
        $cols[$listcategory]['type'] = 'coldel';
        $cols[$tin]['type'] = 'coldel';
        $cols[$listaddr]['type'] = 'coldel';
        $cols[$listgroup]['type'] = 'coldel';
        $cols[$tin]['type'] = 'coldel';
        $cols[$tel]['type'] = 'coldel';
        $cols[$fax]['type'] = 'coldel';
        $cols[$contact]['type'] = 'coldel';
        $cols[$listareacode]['type'] = 'coldel';
        $cols[$notes]['type'] = 'coldel';
        $cols[$agentname]['type'] = 'coldel';

        $cols[$listclient]['style'] = 'width: 130px;whiteSpace: normal;min-width:130px;max-width:130px;text-align:left;';
        $cols[$listclientname]['style'] = 'width: 90px;whiteSpace: normal;min-width:90px;max-width:100px;text-align:left;';
        $cols[$listbrgy]['style'] = 'width: 90px;whiteSpace: normal;min-width:90px;max-width:100px;text-align:left;';
        $cols[$listarea]['style'] = 'width: 90px;whiteSpace: normal;min-width:90px;max-width:100px;text-align:left;';
        $cols[$listprovince]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px;text-align:left;';
        $cols[$listregion]['style'] = 'width: 90px;whiteSpace: normal;min-width:90px;max-width:100px;text-align:left;';
        break;
      case 39: //cbbsi
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
        $cols[$listcategory]['type'] = 'coldel';
        $cols[$agentname]['type'] = 'coldel';
        break;
      case 63: //ericco
        $cols[$tin]['type'] = 'coldel';
        $cols[$tel]['type'] = 'coldel';
        $cols[$fax]['type'] = 'coldel';
        $cols[$contact]['type'] = 'coldel';
        $cols[$listareacode]['type'] = 'coldel';
        $cols[$listbrgy]['type'] = 'coldel';
        $cols[$agentname]['style'] =  'width: 200px;whiteSpace: normal;max-width:200px;text-align:left;';
        break;
      default:
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
        $cols[$agentname]['type'] = 'coldel';
        break;
    }

    if ($companyid != 22) { //not eipi
      $cols[$shipto]['type'] = 'coldel';
    }

    $cols = $this->tabClass->delcollisting($cols);
    return $cols;
  }

  public function paramsdatalisting($config)
  {
    $companyid = $config['params']['companyid'];
    if ($companyid == 10 || $companyid == 12) { //afti & afti usd
      $fields = ['selectprefix'];
      $col1 = $this->fieldClass->create($fields);
      data_set($col1, 'selectprefix.label', 'Search by');
      data_set($col1, 'selectprefix.type', 'lookup');
      data_set($col1, 'selectprefix.lookupclass', 'lookupsearchby');
      data_set($col1, 'selectprefix.action', 'lookupsearchby');

      $fields = ['refresh'];
      $col2 = $this->fieldClass->create($fields);

      $data = $this->coreFunctions->opentable("select '' as docno,'' as selectprefix");

      return ['status' => true, 'data' => $data[0], 'txtfield' => ['col1' => $col1, 'col2' => $col2]];
    } else {
      return [];
    }
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
      case 10: //afti
      case 12: // afti usd
        $searchfield = ['client.client', 'client.clientname', 'client.addr', 'client.rem', 'category.cat_name', 'client.tin', 'client.groupid'];
        $address = "client.addr";
        $leftjoin = " left join client as agent on agent.client = client.agent ";
        if ($search != "") {
          $limit = "";
        }
        break;
      case 16: //ati
        $address = "ifnull(addr.addr,'') as addr";
        $leftjoin = " left join billingaddr as addr on addr.line=client.shipid ";
        $searchfield = ['client.client', 'client.clientname', 'client.addr', 'client.rem', 'category.cat_name'];
        if ($search != "") {
          $limit = "";
        }
        break;
      case 24: //goodfound
        $address = "client.addr,client.tel,client.fax,client.contact,client.area,client.region,client.areacode,client.province";
        $leftjoin = "";
        $searchfield = ['client.client', 'client.clientname', 'client.addr', 'client.rem', 'category.cat_name', 'client.tin'];
        if ($search != "") {
          $limit = "";
        }
        break;
      case 32: //3m
        $address = "client.addr,client.brgy,client.area,client.province,client.region";
        $leftjoin = "";
        $searchfield = ['client.client', 'client.clientname', 'client.addr', 'client.rem', 'category.cat_name', 'client.tin'];
        if ($search != "") {
          $limit = "";
        }
        break;
      case 22: //eipi
        $address = "client.addr,client.addr2 as shipto";
        $leftjoin = "";
        $searchfield = ['client.client', 'client.clientname', 'client.addr', 'client.rem', 'category.cat_name', 'client.tin'];
        if ($search != "") {
          $limit = "";
        }
        break;
      case 63: //ericco
        $address = "client.addr,client.area,client.province,client.region,ag.clientname as agentname,client.groupid";
        $leftjoin = "left join client as ag on ag.client = client.agent";
        $searchfield = ['client.client', 'client.clientname', 'client.addr', 'client.rem', 'category.cat_name', 'client.tin'];
        if ($search != "") {
          $limit = "";
        }
        break;
      default:
        $address = "client.addr";
        $leftjoin = "";
        $searchfield = ['client.client', 'client.clientname', 'client.addr', 'client.rem', 'category.cat_name', 'client.tin'];
        if ($search != "") {
          $limit = "";
        }

        if ($company ==  29) { //sbc
          $condition = " and client.center = '" . $center . "'";
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

    if ($this->companysetup->customerperagent($config['params'])) {
      if ($company == 34) { //evergreen
        if ($allowall == '0') {
          if ($adminid != 0) {
            $isleader = $this->coreFunctions->getfieldvalue("client", "isleader", "clientid=?", [$adminid]);
            if (floatval($isleader) == 1) {
              $leftjoin .= " left join client as ag on ag.client = client.agent left join client as lead on lead.clientid = ag.parent ";
              $condition  = " and (lead.clientid = " . $adminid . " or  ag.clientid =  " . $adminid . ") ";
            } else {
              $leftjoin .= " left join client as ag on ag.client = client.agent  ";
              $condition  = " and ag.clientid = " . $adminid . " ";
            }
          }
        }
      } else {
        if ($allowall == '0') {
          if ($adminid != 0) {
            $leftjoin .= " left join client as ag on ag.client = client.agent  ";
            $condition  = " and ag.clientid = " . $adminid . " ";
          }
        }
      }
    }


    $qry = "select client.clientid,client.client,client.clientname," . $address . ",
    category.cat_name as category, client.rem as notes,client.groupid,client.tin " . $compatiblefield . "
    from client 
    left join category_masterfile as category on category.cat_id=client.category " . $leftjoin . "
    where client.iscustomer =1 " . $condition .  $filtersearch .  $grp . "  
    order by client " . $limit;
    // var_dump($qry);
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

    if ($config['params']['companyid'] == 37) { //mega crystal
      $companyname = $this->coreFunctions->getfieldvalue("center", "shortname", "code=?", ['001']);
      if ($companyname == 'MULTICRYSTAL') {
        $btns = array(
          'load',
          'print',
          'logs',
          'backlisting',
          'toggleup',
          'toggledown'
        );
      }
    }

    if ($this->companysetup->getclientlength($config['params']) != 0) {
      array_push($btns, 'others');
    }

    if ($config['params']['companyid'] == 26) { //bee healthy
      if (($key = array_search('new', $btns)) !== false) {
        unset($btns[$key]);
      }
    }

    if ($config['params']['companyid'] == 57) { //finance cdo
      if (($key = array_search('new', $btns)) !== false) {
        unset($btns[$key]);
      }

      if (($key = array_search('edit', $btns)) !== false) {
        unset($btns[$key]);
      }

      if (($key = array_search('delete', $btns)) !== false) {
        unset($btns[$key]);
      }
    }

    $buttons = $this->btnClass->create($btns);
    if ($config['params']['companyid'] == 55) { // AFLI Lending
      $this->modulename = 'BORROWER LEDGER';
    }
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
    $loan_access = $this->othersClass->checkAccess($config['params']['user'], 4997);
    $loan_sched = $this->othersClass->checkAccess($config['params']['user'], 5019);

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
    $loan = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewloan']];
    $loansched = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewloansched']];

    //3/2/2023 FPY Testing
    if ($companyid != 56) { //homeworks
      $Financing = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewfinancing']];
    }

    $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrybillingaddr', 'label' => 'BILLING/SHIPPING']];
    $billship = $this->tabClass->createtab($tab, []);

    $return = [];

    // standard attachment tab
    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entryclientpicture', 'label' => 'Attachment', 'access' => 'view']];
    $attach = $this->tabClass->createtab($tab, []);
    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $attach];
    if ($companyid != 55) { // AFLI Lending
      $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrycustomercontactperson', 'label' => 'CONTACT PERSON']];
      $contactperson = $this->tabClass->createtab($tab, []);

      if ($contactperson_access != 0) {
        $return['CONTACT PERSON'] = ['icon' => 'fa fa-list-ul', 'tab' => $contactperson];
      }
    }


    switch ($systemtype) {
      case 'AMS':
      case 'EAPPLICATION':
      case 'LENDING':
        if ($ar_access != 0) {
          $return['ACCOUNT RECEIVABLE HISTORY'] = ['icon' => 'fa fa-coins', 'customform' => $ar];
        }

        if ($ap_access != 0) {
          $return['ACCOUNT PAYABLE HISTORY'] = ['icon' => 'fa fa-coins', 'customform' => $ap];
        }

        if ($pdc_access != 0) {
          $return['POSTDATED CHECKS HISTORY'] = ['icon' => 'fa fa-money-check', 'customform' => $pdc];
        }

        if ($checkreturntab_access) {
          $return['RETURNED CHECKS HISTORY'] = ['icon' => 'fa fa-money-check', 'customform' => $rc];
        }

        if ($unpaidarap_access != 0) {
          $return['UNPAID AR/AP'] = ['icon' => 'fa fa-coins', 'customform' => $unpaidarap];
        }
        if ($companyid == 55) { // afli lending
          if ($loan_access) {
            $return['LOAN HISTORY'] = ['icon' => 'fa fa-chalkboard-teacher', 'customform' => $loan];
          }
          if ($loan_sched) {
            $return['LOAN SCHEDULE'] = ['icon' => 'fa fa-calendar-check', 'customform' => $loansched];
          }
        }
        break;
      case 'MISPOS':
      case 'MIS':
        if ($skutab_access != 0) {
          $return['SKU ENTRY'] = ['icon' => 'fa fa-list-ul', 'tab' => $sku];
        }

        if ($invtab_access != 0) {
          $return['INVENTORY HISTORY'] = ['icon' => 'fa fa-list-ul', 'customform' => $inv];
        }

        break;
      default:
        if ($skutab_access != 0) {
          if ($companyid != 63) { //ericco
            $return['SKU ENTRY'] = ['icon' => 'fa fa-list-ul', 'tab' => $sku];
          }
        }

        if ($ar_access != 0) {
          $return['ACCOUNT RECEIVABLE HISTORY'] = ['icon' => 'fa fa-coins', 'customform' => $ar];
        }

        if ($companyid != 56) { //homeworks
          $return['FINANCING'] = ['icon' => 'fa fa-coins', 'customform' => $Financing];
        }

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
        break;
    }

    switch ($config['params']['companyid']) {
      case 6: //mitsukoshi
        $return['USER ACCOUNT'] = ['icon' => 'fa fa-user', 'customform' => $user];
        break;

      default:
        switch ($companyid) {
          case 34:
          case 55:
          case 56:
            break;
          case 19: //housegem
            if ($setaddresstab_access != 0) {
              $return['SHIPPING ADDRESS SETUP'] = ['icon' => 'fa fa-address-book', 'tab' => $billship];
            }
            break;
          default:
            if ($defaultaddresstab_access != 0) {
              $return['DEFAULT SHIPPING/BILLING ADDRESS'] = ['icon' => 'fa fa-map-marker-alt', 'customform' => $billshipdefault];
            }

            if ($setaddresstab_access != 0) {
              $return['SHIPPING/BILLING ADDRESS SETUP'] = ['icon' => 'fa fa-address-book', 'tab' => $billship];
            }
            break;
        }
        break;
    }

    if (strtoupper($systemtype) == 'VSCHED' || strtoupper($systemtype) == 'ATI') {
      $return = [];
      $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrycustomercontactperson', 'label' => 'CONTACT PERSON']];
      $contactperson = $this->tabClass->createtab($tab, []);

      $billshipdefault = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewbillingdefault']];

      $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrybillingaddr', 'label' => 'BILLING/SHIPPING']];
      $billship = $this->tabClass->createtab($tab, []);

      if ($contactperson_access != 0) {
        $return['CONTACT PERSON'] = ['icon' => 'fa fa-address-card', 'tab' => $contactperson];
      }

      if ($defaultaddresstab_access != 0) {
        $return['DEFAULT SHIPPING ADDRESS'] = ['icon' => 'fa fa-map-marker-alt', 'customform' => $billshipdefault];
      }

      if ($setaddresstab_access != 0) {
        $return['SHIPPING ADDRESS SETUP'] = ['icon' => 'fa fa-address-book', 'tab' => $billship];
      }

      if (strtoupper($systemtype) == 'ATI') {
        $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrycustomersa', 'label' => 'SA #']];
        $tabsa = $this->tabClass->createtab($tab, []);
        $return['SA #'] = ['icon' => 'fa fa-check', 'tab' => $tabsa];

        $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrycustomersvs', 'label' => 'SVS #']];
        $tabsvs = $this->tabClass->createtab($tab, []);
        $return['SVS #'] = ['icon' => 'fa fa-check', 'tab' => $tabsvs];

        $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrycustomerpo', 'label' => 'PO #']];
        $tabpo = $this->tabClass->createtab($tab, []);
        $return['PO #'] = ['icon' => 'fa fa-check', 'tab' => $tabpo];
      }
    }
    if ($companyid == 29) { //sbc main
      $tab = ['customform' => ['action' => 'customform', 'lookupclass' => 'generatemr']];
      $return['GENERATE MR'] = ['icon' => 'fas fa-th-list', 'customform' => $tab];
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
    $showotherinfo = $this->othersClass->checkAccess($config['params']['user'], 5349);

    //for ATI
    if ($limitview) {
      $fields = ['client', 'clientname'];
      $col1 = $this->fieldClass->create($fields);
      data_set($col1, 'client.label', 'Customer Code');
      data_set($col1, 'client.required', true);
      data_set($col1, 'client.class', 'csclient sbccsenablealways');
      data_set($col1, 'client.lookupclass', 'customer');
      data_set($col1, 'client.action', 'lookupledgerclient');
      data_set($col1, 'clientname.type', 'cinput');

      $fields = [];
      $col2 = $this->fieldClass->create($fields);

      $fields = [];
      $col3 = $this->fieldClass->create($fields);

      $fields = [];
      $col4 = $this->fieldClass->create($fields);

      return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    if ($companyid == 60) {
      if (!$showotherinfo) {
        $fields = ['client', 'clientname', 'registername'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'client.label', 'Customer Code');
        data_set($col1, 'client.class', 'csclient sbccsenablealways');
        data_set($col1, 'client.lookupclass', 'customer');
        data_set($col1, 'client.action', 'lookupledgerclient');
        data_set($col1, 'clientname.type', 'cinput');
        $fields = ['terms', 'dagentname'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'terms.lookupclass', 'ledgerterms');
        $fields = ['rem'];
        $col3 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
      }
    }

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $fields = ['client', 'clientname', 'groupid',  'territory',  'type', 'dcategory', 'industry', 'tin', 'officialemail', 'officialwebsite', 'addr2'];
        break;
      case 34: //evergreen
        $fields = ['client', 'clientname', 'addr', 'start', 'clientstatus', 'email', 'tel', 'tel2'];
        break;
      case 40: //cdo
        $fields = ['client', 'clientname', 'addr', 'bday', 'bplace', 'citizenship', 'civilstatus'];
        break;
      case 55: //AFLI Lending
        $fields = ['client', 'fname', 'mname', 'lname', 'addr', 'tel'];
        break;
      default:
        $fields = ['client', 'clientname', 'addr', 'ship', 'start', 'clientstatus', 'email', 'tel', 'fax', 'tel2'];
        break;
    }

    switch ($companyid) {
      case 24: //goodfound
      case 29: //sbc main
        array_push($fields, 'contact');
        break;
      case 6: //mitsukoshi
        array_push($fields, 'forwarder');
        break;
      case 16: //ati
        if (($key = array_search('addr', $fields)) !== false) {
          unset($fields[$key]);
        }
        break;
    }

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.label', 'Customer Code');
    data_set($col1, 'client.class', 'csclient sbccsenablealways');
    data_set($col1, 'client.lookupclass', 'customer');
    data_set($col1, 'client.action', 'lookupledgerclient');

    data_set($col1, 'clientname.type', 'cinput');
    if ($companyid != 55) { //not afli
      data_set($col1, 'clientname.required', true);
    }

    data_set($col1, 'addr.type', 'cinput');
    data_set($col1, 'email.type', 'cinput');
    data_set($col1, 'tel.type', 'cinput');
    data_set($col1, 'fax.type', 'cinput');
    data_set($col1, 'tel2.type', 'cinput');

    if ($companyid == 60) data_set($col1, 'tel.required', true);

    if ($companyid == 22) { //eipi
      data_set($col1, 'addr.type', 'ctextarea');
    }

    switch (strtoupper($systemtype)) {
      case 'WAIMS':
        $fields = ['terms', 'contact', 'attention', 'acct', 'dagentname', 'groupid', 'dsalesacct', ['bal', 'isnocrlimit'], 'bstyle', 'tin', 'pricegroup'];
        break;
      default:
        switch ($companyid) {
          case 10: //afti
          case 12: //afti usd
            data_set($col1, 'groupid.label', 'Branch');
            data_set($col1, 'dcategory.label', 'Business Style');
            data_set($col1, 'groupid.type', 'input');
            data_set($col1, 'groupid.class', 'csgroup');
            data_set($col1, 'groupid.readonly', false);
            data_set($col1, 'type.label', 'Organizational Structure');
            data_set($col1, 'type.type', 'lookup');
            data_set($col1, 'type.lookupclass', 'lookuporgstructure');
            data_set($col1, 'type.action', 'lookuprandom');
            data_set($col1, 'tin.required', true);
            data_set($col1, 'industry.required', true);
            data_set($col1, 'industry.type', 'lookup');
            data_set($col1, 'industry.action', 'lookuprandom');
            data_set($col1, 'industry.lookupclass', 'lookupindustry');
            data_set($col1, 'industry.class', 'sbccsreadonly');
            data_set($col1, 'addr2.type', 'lookup');
            data_set($col1, 'addr2.label', 'Source');
            data_set($col1, 'addr2.readonly', true);
            data_set($col1, 'addr2.action', 'lookuprandom');
            data_set($col1, 'addr2.lookupclass', 'lookupsource');
            data_set($col1, 'addr2.class', 'sbccsreadonly');
            $fields = ['terms', 'dvattype', 'dcur', 'dagentname', ['bal', 'isnocrlimit'], 'crtype', 'crdays', 'dparentcode', 'alias', 'region', 'dewt'];
            break;
          case 19: //housegem
            $fields = ['terms', 'dagentname', 'addr2', 'groupid', 'dsalesacct', ['bal', 'isnocrlimit'], 'pricegroup', 'tin', 'contact', 'bstyle'];
            break;
          case 23: //labsol cebu
          case 41: //labsol manila   
          case 52: //technolab      
            $fields = ['terms', 'dagentname', 'groupid', 'daracct', ['bal', 'isnocrlimit'], 'pricegroup', 'tin', 'dvattype'];
            break;
          case 22: //eipi
            $fields = ['terms', 'dagentname', 'dsalesacct', ['bal', 'isnocrlimit'], 'pricegroup', 'contact', 'tin', 'groupid', 'dvattype', 'dewt'];
            break;
          default:
            $fields = ['terms', 'dagentname', 'groupid', 'dsalesacct', ['bal', 'isnocrlimit'], 'pricegroup'];
            switch ($companyid) {
              case 40: //cdo
                $fields = ['father', 'mother', 'sex',  'position',  'tel2', 'accountid', 'terms'];
                break;
              case 39: //cbbsi
                array_push($fields, 'contact', 'bstyle', 'tin');
                break;
              case 34: //evergreen
                $fields = ['terms', 'dagentname', 'tin'];
                break;
              case 3: //conti
              case 0: //main
                array_push($fields, 'tin', 'bstyle', 'purchaser', 'registername');
                break;
              case 15: //nathina
                array_push($fields, 'contact', 'acct');
                break;
              case 16: //ati
                array_push($fields, 'tin', 'dvattype');
                break;
              case 20: //proline
              case 21: //kinggeorge
                array_push($fields, 'tin', 'contact', 'position');
                break;
              case 24: //goodfound
                array_push($fields, 'owner', 'mobile', 'bstyle', 'tin');
                break;
              case 27: //NTE
              case 36: //ROZLAB
                array_push($fields, 'tin', 'bstyle');
                break;
              case 28: //xcomp
                array_push($fields, 'tin', 'bstyle');
                break;
              case 29: //sbc main
                array_push($fields, 'tin', 'charge1');
                break;
              case 43: //MIGHTY
              case 47: //kstar
                data_set($col1, 'ship.label', 'Trucking');
                array_push($fields, 'tin', 'contact', 'bstyle');
                break;
              case 55: //AFLI Lending
                $fields = ['terms', 'dagentname', 'tin'];
                break;
              case 59: //roosevelt
                unset($fields[5]);
                array_push($fields, 'dpricegroup', 'tin', 'rem');
                break;
              default:
                array_push($fields, 'tin');
                if ($companyid == 37) { //mega crystal
                  array_push($fields, 'contact');
                }
                break;
            }
            break;
        }
        break;
    }

    switch ($companyid) {
      case 16: //ati
      case 23: //labsol cebu
      case 41: //labsol manila
      case 52: //technolab
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'dvattype.label', 'VAT Type');
        break;
      case 19: //housegem
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'contact.label', 'Contact Person');
        break;
      case 37: //mega crystal
      case 39: //cbbsi
      case 43: //mighty
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'contact.label', 'Contact Person');
        break;
      case 40: //cdo
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'accountid.label', 'LTO Client ID');
        data_set($col2, 'position.label', 'Occupation');
        break;
      case 60: //transpower
        array_push($fields, 'registername', 'dvattype');
        $col2 = $this->fieldClass->create($fields);
        break;
      default:
        $col2 = $this->fieldClass->create($fields);
        break;
    }

    data_set($col2, 'terms.lookupclass', 'ledgerterms');
    data_set($col2, 'tin.type', 'cinput');
    data_set($col2, 'bstyle.type', 'cinput');
    data_set($col2, 'purchaser.type', 'cinput');
    data_set($col2, 'registername.type', 'cinput');

    data_set($col2, 'groupid.lookupclass', 'lookupclientgroupledger');
    data_set($col2, 'groupid.action', 'lookupclientgroupledger');
    data_set($col2, 'groupid.class', 'csgroup');
    data_set($col2, 'groupid.readonly', false);

    switch (strtoupper($systemtype)) {
      case 'WAIMS':
        data_set($col2, 'contact.label', 'Contact Person');
        data_set($col2, 'attention.label', 'Contact Person (Sales)');
        data_set($col2, 'acct.label', 'Contact Person (Collection)');
        break;
      default:
        switch ($companyid) {
          case 15: //nathina
            data_set($col2, 'acct.label', 'FB Name');
            break;
          case 20: //proline
            data_set($col2, 'contact.label', 'Contact Person');
            break;
          case 22: //eipi
            data_set($col2, 'contact.label', 'Contact Person');
            data_set($col2, 'bstyle.label', 'Business Type');
            break;
          case 10: //afti
            if (floatval($editlimit) == 0) {
              data_set($col2, 'bal.readonly', true);
              data_set($col2, 'bal.class', 'sbccsreadonly');
            }
            break;
        }

        break;
    }

    data_set($col2, 'contact.type', 'cinput');
    data_set($col2, 'bal.type', 'cinput');
    data_set($col2, 'bal.label', 'Credit Limit');
    data_set($col2, 'bal.name', 'crlimit');
    data_set($col2, 'dsalesacct.label', 'Sales Account');

    // afti - crtype
    data_set($col2, 'crtype.action', 'lookuprandom');
    data_set($col2, 'crtype.lookupclass', 'lookup_crtype');
    data_set($col2, 'crtype.readonly', true);

    switch (strtoupper($systemtype)) {
      case 'WAIMS':
        $fields = ['dcategory', 'dcur', 'area', 'province', 'region', 'dparentcode', 'zipcode', 'owner', 'mobile', 'prefix'];
        break;
      default:
        switch ($companyid) {
          case 20: //proline
            $fields = ['dcategory', 'dcur', 'area', 'province', 'region', 'dparentcode', 'zipcode', 'rem'];
            break;
          case 10: //afti
          case 12: //afti usd
            data_set($col2, 'dcur.label', 'Billing Currency');
            data_set($col2, 'alias.label', 'USD Code');
            data_set($col2, 'region.class', 'sbccsreadonly');
            $fields = ['activity', 'start', 'clientstatus'];
            break;
          case 22: //eipi
            $fields = ['dcategory', 'owner', 'mobile', 'area', 'province', 'region', 'dparentcode', 'zipcode', 'addr2'];
            break;
          case 24: //goodfound
            $fields = ['dcategory', 'dcur', 'areacode', 'area', 'province', 'region', 'dparentcode', 'zipcode'];
            break;
          case 32: //3m
            $fields = ['dcategory', 'dcur', 'brgy', 'area', 'province', 'region', 'dparentcode', 'zipcode'];
            break;
          case 34: //evergreen
            $fields = ['picture', ['iscustomer', 'isagent'], ['isinactive', 'issenior']];
            break;
          case 40: //cdo
            $fields = ['province', 'region',  'zipcode', 'clientstatus', 'dagentname', 'tin'];
            break;
          case 39: //cbbsi
            $fields = ['dcategory', 'dcur', 'area', 'province', 'region', 'dparentcode', 'zipcode', 'rem'];
            break;
          case 55: //AFLI Lending
            $fields = [];
            break;
          case 59: //roosevelt
            $fields = ['dcategory', 'dcur', 'area', 'province', 'region', 'dparentcode', 'zipcode', 'lasttrans'];
            $allowedit = $this->othersClass->checkAccess($config['params']['user'], 23);
            if (!$allowedit) array_push($fields, 'updatenotes');
            break;
          default:
            $fields = ['dcategory', 'dcur', 'area', 'province', 'region', 'dparentcode', 'zipcode'];
            break;
        }

        break;
    }

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        array_push($fields, 'rem', 'rem2');
        break;
      case 3: //conti
        array_push($fields, 'rem');
        break;
      case 11: //summit
        array_push($fields, 'type');
        array_push($fields, 'rem');
        break;
      case 19: //housegem
        array_push($fields, 'owner', 'mobile');
        break;
      case 28: //xcomp
        array_push($fields, 'rem');
        break;
      case 6: //mitsukoshi
        array_push($fields, 'disc', ['quota', 'comm']);
        break;
      case 43: //MIGHTY
        array_push($fields, 'rem');
        break;
      case 50: //unitech
        array_push($fields, 'disc');
        break;
      case 60: //TRANSPOWER
        array_push($fields, 'rem');
        break;
    }

    $col3 = $this->fieldClass->create($fields);

    switch ($companyid) {
      case 11: //summit
        data_set($col3, 'type.required', false);
        break;
      case 20: //proline
        data_set($col3, 'rem.label', 'Remarks');
        break;
      case 22: //eipi
        data_set($col3, 'mobile.label', 'Owner Contact#');
        data_set($col3, 'addr2.label', 'Delivered To');
        data_set($col3, 'dcategory.label', 'Business Style');
        break;
      case 32: //3m
        data_set($col3, 'area.addedparams', []);
        data_set($col3, 'region.addedparams', []);
        data_set($col3, 'province.addedparams', []);
        break;
      case 50: //unitech
        data_set($col3, 'disc.label', 'Discount');
        break;
      case 23: //labsol cebu
        data_set($col3, 'dcategory.label', 'Business Style');
        break;
      default:
        data_set($col3, 'disc.label', 'Mark Up');
        break;
    }

    data_set($col3, 'zipcode.type', 'cinput');

    data_set($col3, 'dcategory.name', 'categoryname');
    if (strtoupper($systemtype) == "WAIMS") {
      data_set($col3, 'dcategory.required', true);
    }

    $fields = ['picture', ['iscustomer', 'issupplier'], ['isagent', 'iswarehouse'], ['isemployee', 'isinactive'], 'isdepartment'];
    if ($systemtype == 'AIMSPOS' || $systemtype == 'MISPOS') {
      array_push($fields, 'issynced');
    }

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        array_push($fields, 'isvatzerorated', 'isnotarizedcert');
        break;
      case 19: //housegem
        array_push($fields, 'iscis');
        break;
      case 34: //evergreen
        $fields = [];
        break;
      case 55: //AFLI Lending
        $fields = ['picture', ['iscustomer', 'issupplier'], ['isagent', 'isinactive'], ['isemployee'], 'isdepartment'];
        break;
      case 59: //roosevelt
        array_push($fields, 'ishold');
        break;
    }

    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'iscustomer.class', 'csiscustomer sbccsreadonly');
    data_set($col4, 'picture.lookupclass', 'client');
    data_set($col4, 'picture.folder', 'customer');
    data_set($col4, 'picture.table', 'client');
    data_set($col4, 'picture.fieldid', 'clientid');

    switch ($companyid) {
      case 3: //conti
        data_set($col3, 'rem.label', 'Remarks');
        break;
      case 10: //afti
      case 12: //afti usd
        data_set($col3, 'rem.label', 'Notes');
        data_set($col3, 'rem.type', 'textarea');
        data_set($col3, 'rem2.label', 'Notes (for Accounting only)');
        data_set($col3, 'rem2.type', 'textarea');
        if (floatval($editnotes) == 0) {
          data_set($col3, 'rem2.readonly', true);
          data_set($col3, 'rem2.class', 'sbccsreadonly');
        }
        break;
      case 55: //AFLI Lending
        data_set($col4, 'iscustomer.label', 'Borrower');
        break;
    }

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }


  public function newclient($config)
  {
    $data = [];
    $companyid = $config['params']['companyid'];
    $data[0]['clientid'] = 0;
    $data[0]['client'] = $config['newclient'];
    $data[0]['clientname'] = '';
    $data[0]['addr'] = '';
    $data[0]['ship'] = '';
    $data[0]['start'] = $this->othersClass->getCurrentDate();
    $data[0]['bday'] = $this->othersClass->getCurrentDate();
    $data[0]['status'] = 'ACTIVE';
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
    $data[0]['class'] = 'R';
    $data[0]['vattype'] = 'NON-VATABLE';

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
    $data[0]['iscustomer'] = '1';
    $data[0]['issupplier'] = '0';
    $data[0]['isagent'] = '0';
    $data[0]['iswarehouse'] = '0';
    $data[0]['isemployee'] = '0';
    $data[0]['isinactive'] = '0';
    $data[0]['isdepartment'] = '0';
    $data[0]['issynced'] = '0';
    $data[0]['issenior'] = '0';
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
    $data[0]['charge1'] = '0.00';
    $data[0]['fname'] = '';
    $data[0]['mname'] = '';
    $data[0]['lname'] = '';
    $data[0]['isnocrlimit'] = '0';
    $data[0]['terms'] = '';
    $data[0]['crlimit'] = '0';
    $data[0]['center'] = '';

    switch ($config['params']['companyid']) {
      case 60: //transpower
        $data[0]['class'] = 'C';
        $data[0]['vattype'] = 'VATABLE';
        $data[0]['tax'] = '12';
        break;
      case 22: //eipi
        $data[0]['addr2'] = '';
        $data[0]['isnocrlimit'] = '0';
        $data[0]['ewt'] = '';
        $data[0]['ewtrate'] = 0;
        $data[0]['terms'] = '';
        $data[0]['crlimit'] = '0';
        $data[0]['center'] = '';
        break;
      case 6: //mitsukoshi
      case 11: //summit
      case 37: //mega crystal
        $data[0]['isnocrlimit'] = '1';
        $data[0]['terms'] = '';
        $data[0]['crlimit'] = '0';
        $data[0]['center'] = '';
        break;
      case 10: //afti
      case 12: //afti usd
        $data[0]['tax'] = '12';
        $data[0]['vattype'] = 'VATABLE';
        $data[0]['isnocrlimit'] = '0';
        $data[0]['ewt'] = '';
        $data[0]['ewtrate'] = 0;
        $data[0]['addr2'] = '';
        $data[0]['crlimit'] = '100000';
        $data[0]['terms'] = '50% DOWN;50%BEFORE DELIVERY';
        $data[0]['center'] = '';
        break;
      case 19: //housegem
        $data[0]['isnocrlimit'] = '0';
        $data[0]['iscis'] = '0';
        $data[0]['addr2'] = '';
        $data[0]['terms'] = '';
        $data[0]['crlimit'] = '0';
        $data[0]['center'] = '';
        break;
      case 40: //cdo
        $data[0]['isnocrlimit'] = '1';
        $data[0]['terms'] = '';
        $data[0]['crlimit'] = '0';
        $data[0]['center'] = '';
        break;
      case 55: //AFLI Lending
        $data[0]['isnocrlimit'] = '0';
        $data[0]['terms'] = '';
        $data[0]['crlimit'] = '0';
        $data[0]['center'] = '';
        break;
      case 59: //roosevelt
        $data[0]['isnocrlimit'] = '0';
        $data[0]['terms'] = '';
        $data[0]['crlimit'] = '0';
        $data[0]['rem'] = '';
        $data[0]['ishold'] = '0';
        $data[0]['dpricegroup'] = '';
        $data[0]['center'] = '';
        break;
      case 29: //sbc
        $data[0]['center'] = $config['params']['center'];
        break;
      default:
        $data[0]['isnocrlimit'] = '0';
        $data[0]['terms'] = '';
        $data[0]['crlimit'] = '0';
        $data[0]['center'] = '';
        break;
    }



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
        $clientid = $this->coreFunctions->datareader("select clientid as value from client where iscustomer=1 and center=? order by clientid desc limit 1", [$center]);
      }
      $config['params']['clientid'] = $clientid;
    } else {
      $this->othersClass->checkprofile($doc, $clientid, $config);
    }
    $center = $config['params']['center'];
    $head = [];
    $fields = 'client.clientid, client.client as docno';

    switch ($companyid) {
      case 19: //housegem
        array_push($this->fields, 'iscis', 'addr2');
        array_push($this->blnfields, 'iscis');
        break;
      case 22: //eipi
        array_push($this->fields, 'addr2', 'ewt', 'ewtrate');
        break;
      case 10: //afti
        array_push($this->fields, 'ewt', 'ewtrate', 'addr2');
        break;
      case 24: //goodfound
        array_push($this->fields, 'areacode');
        break;
      case 59: //roosevelt
        array_push($this->fields, 'rem', 'ishold');
        break;
    }

    foreach ($this->fields as $key => $value) {
      if ($value == 'charge1') {
        $fields = $fields . ",format(client." . $value . ",2) as charge1";
      } else {
        $fields = $fields . ',client.' . $value;
      }
    }

    foreach ($this->clinfo as $key2 => $value2) {
      $fields = $fields . ',info.' . $value2;
    }

    $allowall = $this->othersClass->checkAccess($config['params']['user'], 4077);
    $adminid = $config['params']['adminid'];
    $leftjoin = "";
    $condition = "";

    if ($this->companysetup->customerperagent($config['params'])) {
      if ($companyid == 34) { //evergreen
        if ($allowall == '0') {
          if ($adminid != 0) {
            $isleader = $this->coreFunctions->getfieldvalue("client", "isleader", "clientid=?", [$adminid]);
            if (floatval($isleader) == 1) {
              $leftjoin .= " left join client as ag on ag.client = client.agent left join client as lead on lead.clientid = ag.parent ";
              $condition  = " and (lead.clientid = " . $adminid . " or  ag.clientid =  " . $adminid . ") ";
            } else {
              $leftjoin .= " left join client as ag on ag.client = client.agent  ";
              $condition  = " and ag.clientid = " . $adminid . " ";
            }
          }
        }
      } else {
        if ($allowall == '0') {
          if ($adminid != 0) {
            $leftjoin .= " left join client as ag on ag.client = client.agent  ";
            $condition  = " and ag.clientid = " . $adminid . " ";
          }
        }
      }
    }

    $addfields = "";
    if ($companyid == 55) { //AFLI Lending
      $addfields = ", info.fname, info.mname, info.lname";
    } else if ($companyid == 59) { //roosevelt
      $addfields = ", '' as dpricegroup";
    }

    $qryselect = "select " . $fields . ", ifnull(a.clientname, '') as agentname, ifnull(coa.acnoname, '') as acnoname, ifnull(ar.acnoname, '') as assetname,
        ifnull(parentcode.clientname, '') as parentname,
        ifnull(category.cat_name, '') as categoryname,
        ifnull(forex.cur, '') as cur,
        ifnull(forwarder.clientid, 0) as truckid,
        ifnull(forwarder.clientname, '') as forwarder $addfields";

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
        where client.clientid = ? and client.iscustomer = 1 " . $condition;
    $head = $this->coreFunctions->opentable($qry, [$clientid]);
    if (!empty($head)) {
      foreach ($this->blnfields as $key => $value) {
        if ($head[0]->$value) {
          $head[0]->$value = "1";
        } else
          $head[0]->$value = "0";
      }
      if ($companyid == 59) {
        switch ($head[0]->class) {
          case 'R':
            $head[0]->dpricegroup = $head[0]->class . '~DEALER';
            break;
          case 'W':
            $head[0]->dpricegroup = $head[0]->class . '~DEALER 2';
            break;
          case 'A':
            $head[0]->dpricegroup = $head[0]->class . '~INDUSTRIAL';
            break;
          case 'B':
            $head[0]->dpricegroup = $head[0]->class . '~WALK-IN';
            break;
          default:
            $head[0]->dpricegroup = $head[0]->class;
            break;
        }
        $head[0]->ishold = $head[0]->ishold == 1 ? '1' : '0';
        $lasttrans = $this->coreFunctions->datareader("select date_format(dateid, '%m-%d-%Y') as value from (select dateid from lahead where client='" . $head[0]->client . "' union all select dateid from glhead where clientid=" . $head[0]->clientid . ") as t order by dateid desc limit 1");
        if ($lasttrans) $head[0]->lasttrans = $lasttrans;
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
    switch ($companyid) {
      case 19: //housegem
        array_push($this->fields, 'iscis', 'addr2');
        break;
      case 22: //eipi
        array_push($this->fields, 'addr2', 'ewt', 'ewtrate');
        break;
      case 10: //afti
        array_push($this->fields,  'ewt', 'ewtrate', 'addr2');
        break;
      case 24: // goodfound
        array_push($this->fields, 'areacode');
        break;
      case 59: //roosevelt
        array_push($this->fields, 'rem', 'ishold');
        break;
    }

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
        $clientinfo[$key] = $head[$key];
        $clientinfo[$key] = $this->othersClass->sanitizekeyfield($key, $clientinfo[$key]);
      } //end if    
    }

    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    $data['dlock'] = $this->othersClass->getCurrentTimeStamp();
    $data['ismirror'] = 0;

    if ($isupdate) {
      if ($companyid == 55) { //AFLI Lending
        $fullName = trim($head['lname'] . ', ' . $head['fname'] . ' ' . $head['mname']);
        $data['clientname'] = $fullName;
      }

      $this->coreFunctions->sbcupdate('client', $data, ['clientid' => $head['clientid']]);
      $clientid = $head['clientid'];
      array_push($this->fields, 'client');
      //info
      $exist = $this->coreFunctions->getfieldvalue("clientinfo", "clientid", "clientid=?", [$clientid], '', true);
      $clientinfo['ismirror'] = 0;

      if ($exist == 0) {
        $clientinfo['clientid'] = $clientid;
        $this->coreFunctions->sbcinsert("clientinfo", $clientinfo);
      } else {
        $clientinfo['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $clientinfo['editby'] = $config['params']['user'];
        $this->coreFunctions->sbcupdate('clientinfo', $clientinfo, ['clientid' => $head['clientid']]);
      }
    } else {
      if ($companyid == 55) { //AFLI Lending
        $fullName = trim($head['lname'] . ', ' . $head['fname'] . ' ' . $head['mname']);
        $data['clientname'] = $fullName;
      }
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $data['iscustomer'] = 1;
      $data['center'] = $center;
      if ($companyid == 10 || $companyid == 12) { //afti & afti usd
        $exist = $this->coreFunctions->getfieldvalue("client", "clientname", "clientname = ? and tin =? and iscustomer =1 and groupid=?", [$head['clientname'], $head['tin'], $head['groupid']]);
      } else {
        $exist = $this->coreFunctions->getfieldvalue("client", "clientname", "clientname = ? and iscustomer =1", [$head['clientname']]);
      }

      if (strlen(($exist)) != 0) {
        return ['status' => false, 'msg' => 'This customer already exist.', 'clientid' => $clientid];
      } else {
        $clientid = $this->coreFunctions->insertGetId('client', $data);
        if (!empty($clientinfo)) {
          $clientinfo['ismirror'] = 0;
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
      $return = $this->coreFunctions->datareader('select client as value from client where  iscustomer=1 order by client desc limit 1');
    } else {
      $return = $this->coreFunctions->datareader('select client as value from client where  iscustomer=1 and left(client,?)=? order by client desc limit 1', [$length, $pref]);
    }
    return $return ?: '';
  }

  public function deletetrans($config)
  {
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $clientid = $config['params']['clientid'];
    $doc = $config['params']['doc'];
    $client = $this->coreFunctions->getfieldvalue('client', 'client', 'clientid=?', [$clientid]);

    $addselect = '';
    $companyid = $config['params']['companyid'];
    if ($companyid == 48) { //seastar
      $addselect = 'union all select lahead.trno as value from lahead where consigneeid=?
                    union all select glhead.trno as value from glhead where consigneeid=?';
    }

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
            select trno as value from hlphead where client=?
            union all
            select clientid as value from item where clientid=?
            union all
            select trno as value from lastock where suppid=?
            union all
            select trno as value from glstock where suppid=?
            union all
            select trno as value from wnhead where client=?
            union all
            select trno as value from hwnhead where client=? $addselect limit 1
            
            ";
    if ($companyid == 48) { //seastar
      $count = $this->coreFunctions->datareader($qry, [$client, $clientid, $client, $client, $clientid, $clientid, $client, $client, $client, $client, $client, $client, $clientid, $clientid, $clientid, $client, $client, $clientid, $clientid]);
    } else {
      $count = $this->coreFunctions->datareader($qry, [$client, $clientid, $client, $client, $clientid, $clientid, $client, $client, $client, $client, $client, $client, $clientid, $clientid, $clientid, $client, $client]);
    }

    if (($count != '')) {
      return ['clientid' => $clientid, 'status' => false, 'msg' => 'Already have transaction...'];
    }

    $qry = "select clientid as value from client where clientid<? and iscustomer=1 order by clientid desc limit 1 ";
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
    $companyid = $config['params']['companyid'];
    $this->logger->sbcviewreportlog($config);

    if ($companyid == 40) { // cdo
      $dataparams = $config['params']['dataparams'];
      if (isset($dataparams['prepared'])) $this->othersClass->writeSignatories($config, 'prepared', $dataparams['prepared']);
      if (isset($dataparams['approved'])) $this->othersClass->writeSignatories($config, 'approved', $dataparams['approved']);
      if (isset($dataparams['received'])) $this->othersClass->writeSignatories($config, 'received', $dataparams['received']);
    }

    $data = app($this->companysetup->getreportpath($config['params']))->generateResult($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
